<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Csrf; use App\Core\Response; use App\Core\Session; use App\Core\TenantContext; use App\Core\Validator; use App\Core\View;
use App\Repositories\AuditLogRepository; use App\Repositories\ChurchUserRepository; use App\Services\SubscriptionEntitlementService; use PDO; use RuntimeException;
final class ChurchAdminController
{
    public function __construct(private readonly PDO $pdo) {}
    public function index(): void
    {
        $tenant=TenantContext::fromSession(); $this->authorize($tenant);
        $snapshot=(new SubscriptionEntitlementService($this->pdo))->snapshot($tenant->churchId());
        View::render('admin.managers.index',['title'=>'관리자 관리','managers'=>(new ChurchUserRepository($this->pdo))->listForTenant($tenant),'limit'=>(new SubscriptionEntitlementService($this->pdo))->limit($snapshot,'admin.max_count'),'role'=>$tenant->role(),'success'=>Session::pullFlash('success'),'error'=>Session::pullFlash('error')]);
    }
    public function store(): void
    {
        Csrf::verify($_POST['_token']??null); $tenant=TenantContext::fromSession(); $this->authorize($tenant);
        $name=trim((string)($_POST['name']??'')); $email=mb_strtolower(trim((string)($_POST['email']??''))); $password=(string)($_POST['password']??''); $role=(string)($_POST['role']??'');
        if(!Validator::text($name,2,100)||!Validator::email($email)||strlen($password)<10||!in_array($role,['owner','admin','content_manager'],true)){return $this->fail('이름, 이메일, 10자 이상 비밀번호와 역할을 확인해 주세요.');}
        if($tenant->role()!=='owner'&&$role==='owner') Response::abort(403,'일반 관리자는 대표관리자를 추가할 수 없습니다.');
        try {
            $entitlements=new SubscriptionEntitlementService($this->pdo); $snapshot=$entitlements->snapshot($tenant->churchId()); $entitlements->assertUsable($snapshot);
            $repo=new ChurchUserRepository($this->pdo); $entitlements->assertBelow($snapshot,'admin.max_count',$repo->countActive($tenant->churchId()),'현재 구독의 관리자 수 한도에 도달했습니다.');
            $id=$repo->createForTenant($tenant,$name,$email,password_hash($password,PASSWORD_DEFAULT),$role);
            (new AuditLogRepository($this->pdo))->record($this->userId(),$tenant->churchId(),'church_admin.created','user',$id,['role'=>$role]);
            Session::flash('success','관리자 계정을 추가했습니다. 비밀번호는 다시 표시되지 않습니다.');
        } catch(RuntimeException $e) { return $this->fail($e->getMessage()); }
        Response::redirect('/admin/managers');
    }
    public function suspend(int $userId): void
    {
        Csrf::verify($_POST['_token']??null); $tenant=TenantContext::fromSession(); $this->authorize($tenant);
        if($userId===$this->userId()) Response::abort(422,'자기 자신은 비활성화할 수 없습니다.');
        $repo=new ChurchUserRepository($this->pdo); $target=$repo->findForTenant($tenant,$userId); if($target===null) Response::abort(404,'관리자를 찾을 수 없습니다.');
        if($target['role']==='owner'&&$tenant->role()!=='owner') Response::abort(403,'일반 관리자는 대표관리자를 비활성화할 수 없습니다.');
        if($target['role']==='owner'&&$repo->countActiveOwners($tenant)<=1) Response::abort(422,'마지막 대표관리자는 비활성화할 수 없습니다.');
        try {$repo->suspendForTenant($tenant,$userId);} catch(RuntimeException $e){return $this->fail($e->getMessage());}
        (new AuditLogRepository($this->pdo))->record($this->userId(),$tenant->churchId(),'church_admin.suspended','user',$userId,['role'=>$target['role']]);
        Session::flash('success','관리자 소속을 비활성화했습니다.'); Response::redirect('/admin/managers');
    }
    private function authorize(TenantContext $tenant): void {if(!in_array($tenant->role(),['owner','admin'],true)) Response::abort(403,'관리자 관리 권한이 없습니다.');}
    private function userId(): int {$auth=Session::get('auth',[]);return (int)($auth['user_id']??0);}
    private function fail(string $message): void {Session::flash('error',$message);Response::redirect('/admin/managers');}
}
