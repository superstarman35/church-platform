<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Csrf;use App\Core\Response;use App\Core\Session;use App\Core\View;use App\Repositories\AuditLogRepository;use App\Repositories\TrialManagementRepository;use PDO;use RuntimeException;
final class PlatformTrialController
{
 public function __construct(private readonly PDO $pdo){}
 public function index():void{$query=mb_substr(trim((string)($_GET['q']??'')),0,100);$status=(string)($_GET['status']??'');View::render('control.trials.index',['title'=>'체험 계정 관리','trials'=>(new TrialManagementRepository($this->pdo))->search($query,$status),'filters'=>compact('query','status'),'success'=>Session::pullFlash('success'),'error'=>Session::pullFlash('error')]);}
 public function operate(int $id,string $operation):void
 {
  Csrf::verify($_POST['_token']??null);$auth=Session::get('auth',[]);
  try{$result=(new TrialManagementRepository($this->pdo))->operate($id,(int)($auth['user_id']??0),$operation,(int)($_POST['days']??0),trim((string)($_POST['reason']??'')));(new AuditLogRepository($this->pdo))->record((int)($auth['user_id']??0),$result['church_id'],'trial.'.$operation,'subscription',$id,$result);Session::flash('success',['extend'=>'체험 기간을 연장했습니다.','expire'=>'체험을 종료했습니다. 데이터는 삭제하지 않았습니다.','recover'=>'보관 중인 체험을 복구했습니다.'][$operation]);}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}
  Response::redirect('/control/trials');
 }
}
