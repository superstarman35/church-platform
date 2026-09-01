<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Csrf; use App\Core\Response; use App\Core\Session; use App\Core\TenantContext; use App\Core\View;
use App\Repositories\AuditLogRepository; use App\Repositories\InvitationApplicationRepository; use PDO;
final class EventAdminController
{
    public function __construct(private readonly PDO $pdo) {}
    public function index(): void
    {
        $tenant=TenantContext::fromSession();
        $this->authorizeApplicantData($tenant);
        $filters=['invitation_id'=>max(0,(int)($_GET['invitation_id']??0)),'status'=>(string)($_GET['status']??''),'q'=>trim((string)($_GET['q']??''))];
        $repository=new InvitationApplicationRepository($this->pdo);
        View::render('admin.events.index',['title'=>'행사·신청자','events'=>$repository->eventSummariesForTenant($tenant),'applications'=>$repository->listAllForTenant($tenant,$filters),'filters'=>$filters,'success'=>Session::pullFlash('success')]);
    }
    public function updateStatus(int $invitationId,int $applicationId): void
    {
        Csrf::verify($_POST['_token']??null); $tenant=TenantContext::fromSession(); $status=(string)($_POST['status']??'');
        $this->authorizeApplicantData($tenant);
        if(!(new InvitationApplicationRepository($this->pdo))->updateStatusForTenant($tenant,$invitationId,$applicationId,$status)) Response::abort(404,'신청자를 찾을 수 없거나 상태값이 올바르지 않습니다.');
        $auth=Session::get('auth',[]); (new AuditLogRepository($this->pdo))->record((int)($auth['user_id']??0),$tenant->churchId(),'event_application.status_changed','invitation_application',$applicationId,['status'=>$status]);
        Session::flash('success','신청자 상태를 변경했습니다.'); Response::redirect('/admin/events');
    }
    public function updateAttendance(int $invitationId,int $applicationId):void{Csrf::verify($_POST['_token']??null);$tenant=TenantContext::fromSession();$this->authorizeApplicantData($tenant);$status=(string)($_POST['attendance_status']??'');if(!(new InvitationApplicationRepository($this->pdo))->updateAttendanceForTenant($tenant,$invitationId,$applicationId,$status))Response::abort(404,'신청자를 찾을 수 없거나 출석 상태가 올바르지 않습니다.');$auth=Session::get('auth',[]);(new AuditLogRepository($this->pdo))->record((int)($auth['user_id']??0),$tenant->churchId(),'event_application.attendance_changed','invitation_application',$applicationId,['attendance_status'=>$status]);Session::flash('success','출석 상태를 변경했습니다.');Response::redirect('/admin/events');}
    private function authorizeApplicantData(TenantContext $tenant): void
    {
        if(!in_array($tenant->role(),['owner','admin'],true)) Response::abort(403,'신청자 개인정보 열람 권한이 없습니다.');
    }
}
