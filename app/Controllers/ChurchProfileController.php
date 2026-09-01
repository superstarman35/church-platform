<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Csrf; use App\Core\Response; use App\Core\Session; use App\Core\TenantContext; use App\Core\Validator; use App\Core\View;
use App\Repositories\AuditLogRepository; use App\Repositories\ChurchRepository; use PDO;
final class ChurchProfileController
{
    public function __construct(private readonly PDO $pdo) {}
    public function edit(): void
    {
        $tenant=TenantContext::fromSession(); $this->authorize($tenant);
        $item=Session::pullFlash('old') ?: (new ChurchRepository($this->pdo))->profileForTenant($tenant);
        if(!is_array($item)) Response::abort(404,'교회 정보를 찾을 수 없습니다.');
        View::render('admin.church.form',['title'=>'교회 기본정보','item'=>$item,'success'=>Session::pullFlash('success'),'error'=>Session::pullFlash('error')]);
    }
    public function update(): void
    {
        Csrf::verify($_POST['_token']??null); $tenant=TenantContext::fromSession(); $this->authorize($tenant); $data=$this->input();
        if(!$this->valid($data)){Session::flash('old',$data);Session::flash('error','필수 항목, 글자 수 또는 URL 형식을 확인해 주세요.');Response::redirect('/admin/church');}
        (new ChurchRepository($this->pdo))->updateProfileForTenant($tenant,$data,$this->userId());
        (new AuditLogRepository($this->pdo))->record($this->userId(),$tenant->churchId(),'church.profile_updated','church',$tenant->churchId(),['fields'=>array_keys($data)]);
        Session::flash('success','교회 기본정보를 저장했습니다.'); Response::redirect('/admin/church');
    }
    private function authorize(TenantContext $tenant): void {if(!in_array($tenant->role(),['owner','admin'],true)) Response::abort(403,'기본정보 수정 권한이 없습니다.');}
    private function input(): array {$fields=['name','english_name','short_description','representative_name','representative_title','contact_name','contact_email','contact_phone','postal_code','address_line1','address_detail','map_url','website_url','youtube_url','instagram_url','facebook_url'];$data=[];foreach($fields as $field)$data[$field]=trim((string)($_POST[$field]??''));return $data;}
    private function valid(array $d): bool
    {
        if(!Validator::text($d['name'],2,150)||mb_strlen($d['short_description'])>500)return false;
        foreach(['english_name'=>150,'representative_name'=>100,'representative_title'=>50,'contact_name'=>100,'contact_phone'=>30,'postal_code'=>10,'address_line1'=>255,'address_detail'=>150] as $field=>$max)if(mb_strlen($d[$field])>$max)return false;
        if($d['contact_email']!==''&&!Validator::email($d['contact_email']))return false;
        foreach(['map_url','website_url','youtube_url','instagram_url','facebook_url'] as $field)if(!$this->urlAllowed($d[$field]))return false; return true;
    }
    private function urlAllowed(string $url): bool {return $url===''||(filter_var($url,FILTER_VALIDATE_URL)!==false&&str_starts_with($url,'https://'));}
    private function userId(): int {$auth=Session::get('auth',[]);return (int)($auth['user_id']??0);}
}
