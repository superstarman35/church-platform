<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Csrf; use App\Core\Response; use App\Core\Session; use App\Core\TenantContext; use App\Core\Validator; use App\Core\View;
use App\Repositories\AuditLogRepository; use App\Repositories\SupportTicketRepository; use PDO;
final class SupportController
{
    public function __construct(private readonly PDO $pdo) {}
    public function index(): void
    {
        $tenant=TenantContext::fromSession();
        View::render('admin.support.index',['title'=>'고객지원','tickets'=>(new SupportTicketRepository($this->pdo))->listForTenant($tenant),'success'=>Session::pullFlash('success'),'error'=>Session::pullFlash('error'),'old'=>Session::pullFlash('old')??[]]);
    }
    public function store(): void
    {
        Csrf::verify($_POST['_token']??null); $tenant=TenantContext::fromSession();
        $data=['ticket_type'=>(string)($_POST['ticket_type']??''),'priority'=>(string)($_POST['priority']??''),'subject'=>trim((string)($_POST['subject']??'')),'body'=>trim((string)($_POST['body']??'')),'related_url'=>trim((string)($_POST['related_url']??'')),'occurred_at'=>trim((string)($_POST['occurred_at']??''))];
        $valid=in_array($data['ticket_type'],['question','error','feature'],true)&&in_array($data['priority'],['normal','high','urgent'],true)&&Validator::text($data['subject'],2,190)&&Validator::text($data['body'],10,10000)&&($data['related_url']===''||(filter_var($data['related_url'],FILTER_VALIDATE_URL)!==false&&str_starts_with($data['related_url'],'https://')))&&$this->validOccurredAt($data['occurred_at']);
        if(!$valid){Session::flash('error','문의 유형, 제목, 10자 이상 내용과 HTTPS 관련 주소를 확인해 주세요.');Session::flash('old',$data);Response::redirect('/admin/support');}
        if($data['occurred_at']!=='') $data['occurred_at']=str_replace('T',' ',$data['occurred_at']).':00';
        $auth=Session::get('auth',[]); $id=(new SupportTicketRepository($this->pdo))->createForTenant($tenant,(int)($auth['user_id']??0),$data);
        (new AuditLogRepository($this->pdo))->record((int)($auth['user_id']??0),$tenant->churchId(),'support_ticket.created','support_ticket',$id,['ticket_type'=>$data['ticket_type'],'priority'=>$data['priority']]);
        Session::flash('success','문의가 접수되었습니다. 처리 상태는 이 화면에서 확인할 수 있습니다.'); Response::redirect('/admin/support');
    }
    private function validOccurredAt(string $value): bool
    {
        if($value==='') return true;
        $date=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$value);
        $errors=\DateTimeImmutable::getLastErrors();
        return $date!==false&&($errors===false||($errors['warning_count']===0&&$errors['error_count']===0))&&$date->format('Y-m-d\TH:i')===$value;
    }
}
