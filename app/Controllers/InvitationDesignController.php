<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Csrf;use App\Core\Response;use App\Core\Session;use App\Core\TenantContext;use App\Core\View;
use App\Repositories\AuditLogRepository;use App\Repositories\InvitationRepository;use PDO;
final class InvitationDesignController
{
    private const TEMPLATES=['portrait','square','landscape'];
    private const COLORS=['forest','navy','rose','sand'];
    private const FONTS=['sans','serif','rounded'];
    private const BUTTONS=['rounded','pill','square'];
    public function __construct(private readonly PDO $pdo){}
    public function index():void{$tenant=TenantContext::fromSession();$this->authorize($tenant);View::render('admin.invitation-design.index',['title'=>'초대장 디자인','items'=>(new InvitationRepository($this->pdo))->listForTenant($tenant),'success'=>Session::pullFlash('success')]);}
    public function update(int $id):void
    {
        Csrf::verify($_POST['_token']??null);$tenant=TenantContext::fromSession();$this->authorize($tenant);$repository=new InvitationRepository($this->pdo);if($repository->findForTenant($tenant,$id)===null)Response::abort(404,'초대장을 찾을 수 없습니다.');
        $design=['template_code'=>(string)($_POST['template_code']??''),'color_preset'=>(string)($_POST['color_preset']??''),'font_preset'=>(string)($_POST['font_preset']??''),'button_preset'=>(string)($_POST['button_preset']??'')];
        if(!in_array($design['template_code'],self::TEMPLATES,true)||!in_array($design['color_preset'],self::COLORS,true)||!in_array($design['font_preset'],self::FONTS,true)||!in_array($design['button_preset'],self::BUTTONS,true))Response::abort(422,'허용되지 않은 디자인 설정입니다.');
        $auth=Session::get('auth',[]);if(!$repository->updateDesign($tenant,$id,$design,(int)($auth['user_id']??0)))Response::abort(404,'디자인을 변경할 수 없습니다.');
        (new AuditLogRepository($this->pdo))->record((int)($auth['user_id']??0),$tenant->churchId(),'invitation.design_updated','invitation',$id,$design);Session::flash('success','초대장 디자인을 저장했습니다.');Response::redirect('/admin/invitation-design');
    }
    private function authorize(TenantContext $tenant):void{if(!in_array($tenant->role(),['owner','admin'],true))Response::abort(403,'디자인 관리 권한이 없습니다.');}
}
