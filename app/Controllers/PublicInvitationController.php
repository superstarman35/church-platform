<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Repositories\InvitationApplicationRepository;
use App\Repositories\InvitationRepository;
use App\Repositories\InvitationMediaRepository;
use App\Repositories\InvitationStatsRepository;
use App\Services\SubscriptionEntitlementService;
use PDO;
use RuntimeException;

final class PublicInvitationController
{
    private InvitationRepository $invitations;
    private InvitationStatsRepository $stats;

    public function __construct(private readonly PDO $pdo)
    {
        $this->invitations = new InvitationRepository($pdo);
        $this->stats = new InvitationStatsRepository($pdo);
    }

    public function show(string $churchSlug, string $slug): void
    {
        $item = $this->published($churchSlug, $slug);
        $this->requireVisibilityAccess($item,$churchSlug,$slug);
        $this->stats->increment((int) $item['church_id'], (int) $item['id'], 'views', 24576);
        View::render('public.invitation', [
            'title' => $item['title'], 'item' => $item,
            'gallery' => (new InvitationMediaRepository($this->pdo))->galleryPublic((int) $item['church_id'], (int) $item['id']),
            'questions'=>(new \App\Repositories\InvitationQuestionRepository($this->pdo))->activePublic((int)$item['church_id'],(int)$item['id']),'success' => Session::pullFlash('public_success'), 'error' => Session::pullFlash('public_error'),
        ], 'public.layout');
    }

    public function apply(string $churchSlug, string $slug): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        $item = $this->published($churchSlug, $slug);
        $this->requireVisibilityAccess($item,$churchSlug,$slug);
        $data = [
            'applicant_name' => trim((string) ($_POST['applicant_name'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'email' => mb_strtolower(trim((string) ($_POST['email'] ?? ''))),
            'attendee_count' => max(1, min(100, (int) ($_POST['attendee_count'] ?? 1))),
            'message' => trim((string) ($_POST['message'] ?? '')),
        ];
        $consent = ($_POST['privacy_consent'] ?? '') === '1';
        if (!Validator::text($data['applicant_name'], 2, 100) || ($data['email'] !== '' && !Validator::email($data['email'])) || mb_strlen($data['message']) > 1000 || !$consent) {
            Session::flash('public_error', '신청자 이름과 개인정보 수집 동의를 확인해 주세요.');
            Response::redirect("/i/{$churchSlug}/{$slug}#apply");
        }
        try {
            $entitlements = new SubscriptionEntitlementService($this->pdo);
            $snapshot = $entitlements->snapshot((int) $item['church_id']);
            $entitlements->assertUsable($snapshot);
            $apps = new InvitationApplicationRepository($this->pdo);
            $questions=(new \App\Repositories\InvitationQuestionRepository($this->pdo))->activePublic((int)$item['church_id'],(int)$item['id']);$posted=is_array($_POST['answers']??null)?$_POST['answers']:[];$data['answers']=[];foreach($questions as $q){$value=$posted[(string)$q['id']]??'';$value=is_array($value)?array_values(array_map('strval',$value)):trim((string)$value);$options=json_decode((string)($q['options_json']??'[]'),true)?:[];if($q['question_type']==='select'&&$value!==''&&!in_array($value,$options,true))throw new RuntimeException('신청 질문의 선택값이 올바르지 않습니다.');if($q['question_type']==='checkbox')$value=$value==='1'?'1':'';if($q['is_required']&&($value===''||$value===[]))throw new RuntimeException('필수 신청 질문에 답해 주세요.');if(is_string($value)&&mb_strlen($value)>1000)throw new RuntimeException('신청 답변은 1000자 이하여야 합니다.');$data['answers'][(string)$q['id']]=$value;}
            $now=time();if(!empty($item['registration_starts_at'])&&$now<strtotime((string)$item['registration_starts_at']))throw new RuntimeException('아직 신청 접수 기간이 아닙니다.');if(!empty($item['registration_ends_at'])&&$now>strtotime((string)$item['registration_ends_at']))throw new RuntimeException('신청 접수가 종료되었습니다.');$capacity=(int)($item['capacity']??0);if($capacity>0&&$apps->attendeeCountForInvitation((int)$item['church_id'],(int)$item['id'])+$data['attendee_count']>$capacity){if(!$item['waitlist_enabled'])throw new RuntimeException('신청 정원이 마감되었습니다.');$data['is_waitlisted']=true;}
            $current = $apps->countForPeriod((int) $item['church_id'], (string) $snapshot['period_starts_at'], (string) $snapshot['period_ends_at']);
            $entitlements->assertBelow($snapshot, 'application.max_count', $current, '신청 접수 한도가 모두 사용되었습니다.');
            $apps->create($item, $data);
            $this->stats->increment((int) $item['church_id'], (int) $item['id'], 'applications', 2048);
            Session::flash('public_success', '신청이 접수되었습니다. 감사합니다.');
        } catch (RuntimeException $e) {
            Session::flash('public_error', $e->getMessage());
        }
        Response::redirect("/i/{$churchSlug}/{$slug}#apply");
    }

    public function share(string $churchSlug, string $slug): void
    {
        Csrf::verify($_POST['_token'] ?? null);
        $item = $this->published($churchSlug, $slug);
        $this->requireVisibilityAccess($item,$churchSlug,$slug);
        $this->stats->increment((int) $item['church_id'], (int) $item['id'], 'shares', 512);
        http_response_code(204);
    }

    private function published(string $churchSlug, string $slug): array
    {
        $item = $this->invitations->findPublished($churchSlug, $slug);
        if ($item === null) {
            Response::abort(404, '게시 중인 초대장을 찾을 수 없습니다.');
        }
        return $item;
    }
    public function unlock(string $churchSlug,string $slug):void{Csrf::verify($_POST['_token']??null);$item=$this->published($churchSlug,$slug);if($item['visibility']!=='password')Response::redirect("/i/{$churchSlug}/{$slug}");$key='invite_access_'.$item['uuid'];$attempt=Session::get($key.'_attempt',['count'=>0,'locked_until'=>0]);if((int)($attempt['locked_until']??0)>time()){Session::flash('access_error','잠시 후 다시 시도해 주세요.');Response::redirect("/i/{$churchSlug}/{$slug}/access");}if(!password_verify((string)($_POST['password']??''),(string)$item['access_password_hash'])){$count=(int)($attempt['count']??0)+1;Session::put($key.'_attempt',['count'=>$count,'locked_until'=>$count>=5?time()+900:0]);$s=$this->pdo->prepare('INSERT INTO invitation_access_failures(church_id,invitation_id,ip_hash) VALUES(:church_id,:invitation_id,:ip_hash)');$s->execute(['church_id'=>$item['church_id'],'invitation_id'=>$item['id'],'ip_hash'=>hash('sha256',(string)($_SERVER['REMOTE_ADDR']??''))]);(new \App\Repositories\AuditLogRepository($this->pdo))->record(null,(int)$item['church_id'],'invitation.access_failed','invitation',(int)$item['id']);Session::flash('access_error','비밀번호를 확인해 주세요.');Response::redirect("/i/{$churchSlug}/{$slug}/access");}Session::put($key,true);Session::forget($key.'_attempt');Response::redirect("/i/{$churchSlug}/{$slug}");}
    public function access(string $churchSlug,string $slug):void{$item=$this->published($churchSlug,$slug);if($item['visibility']!=='password')Response::redirect("/i/{$churchSlug}/{$slug}");View::render('public.invitation-access',['title'=>'초대장 비밀번호','item'=>$item,'error'=>Session::pullFlash('access_error')],'public.layout');}
    private function requireVisibilityAccess(array $item,string $churchSlug,string $slug):void{if($item['visibility']==='private')Response::abort(404,'초대장을 찾을 수 없습니다.');if($item['visibility']==='password'&&!Session::get('invite_access_'.$item['uuid'],false))Response::redirect("/i/{$churchSlug}/{$slug}/access");}
}

