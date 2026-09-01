<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Csrf; use App\Core\Response; use App\Core\Session; use App\Core\View;
use App\Repositories\AuditLogRepository; use App\Repositories\SupportTicketRepository; use PDO;
final class PlatformSupportController
{
    public function __construct(private readonly PDO $pdo) {}
    public function index(): void
    {
        $filters=['status'=>(string)($_GET['status']??''),'priority'=>(string)($_GET['priority']??'')];
        View::render('control.support.index',['title'=>'고객지원 관리','tickets'=>(new SupportTicketRepository($this->pdo))->listForPlatform($filters),'filters'=>$filters,'success'=>Session::pullFlash('success'),'error'=>Session::pullFlash('error')]);
    }
    public function review(int $id): void
    {
        Csrf::verify($_POST['_token']??null); $status=(string)($_POST['status']??''); $response=trim((string)($_POST['response_summary']??''));
        if(!in_array($status,['in_progress','answered','closed'],true)||mb_strlen($response)>10000||($status==='answered'&&mb_strlen($response)<2)) Response::abort(422,'처리 상태와 답변 내용을 확인해 주세요.');
        $auth=Session::get('auth',[]); $userId=(int)($auth['user_id']??0); $ticket=(new SupportTicketRepository($this->pdo))->reviewForPlatform($id,$userId,$status,$response);
        if($ticket===null) Response::abort(404,'문의를 찾을 수 없습니다.');
        (new AuditLogRepository($this->pdo))->record($userId,(int)$ticket['church_id'],'support_ticket.reviewed','support_ticket',$id,['previous_status'=>$ticket['status'],'status'=>$status]);
        Session::flash('success','문의 처리 상태를 저장했습니다.'); Response::redirect('/control/support');
    }
}
