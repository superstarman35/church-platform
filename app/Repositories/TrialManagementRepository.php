<?php
declare(strict_types=1);
namespace App\Repositories;
use PDO;use RuntimeException;
final class TrialManagementRepository
{
 public function __construct(private readonly PDO $pdo){}
 public function search(string $query,string $status):array
 {
  $where=["p.billing_cycle='trial'"];$params=[];
  if($query!==''){$where[]='(c.name LIKE :query OR c.slug LIKE :query)';$params['query']='%'.str_replace(['%','_'],['\\%','\\_'],$query).'%';}
  if(in_array($status,['trialing','expired','suspended','cancelled'],true)){$where[]='s.status=:status';$params['status']=$status;}
  $sql="SELECT c.id church_id,c.name,c.slug,c.status church_status,s.id subscription_id,s.status subscription_status,s.starts_at,s.trial_ends_at,DATEDIFF(s.trial_ends_at,NOW()) remaining_days,p.name plan_name,COALESCE(stats.traffic_bytes,0) traffic_bytes,COALESCE(media.storage_bytes,0) storage_bytes,COALESCE(invites.invitation_count,0) invitation_count,admins.last_login_at FROM churches c JOIN subscriptions s ON s.id=(SELECT s2.id FROM subscriptions s2 JOIN plans p2 ON p2.id=s2.plan_id WHERE s2.church_id=c.id AND p2.billing_cycle='trial' ORDER BY s2.id DESC LIMIT 1) JOIN plans p ON p.id=s.plan_id LEFT JOIN (SELECT church_id,SUM(traffic_bytes) traffic_bytes FROM invitation_daily_stats WHERE stat_date>=DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01') GROUP BY church_id) stats ON stats.church_id=c.id LEFT JOIN (SELECT church_id,SUM(file_bytes) storage_bytes FROM invitation_media WHERE deleted_at IS NULL GROUP BY church_id) media ON media.church_id=c.id LEFT JOIN (SELECT church_id,COUNT(*) invitation_count FROM invitations WHERE deleted_at IS NULL GROUP BY church_id) invites ON invites.church_id=c.id LEFT JOIN (SELECT cu.church_id,MAX(u.last_login_at) last_login_at FROM church_users cu JOIN users u ON u.id=cu.user_id WHERE cu.status='active' GROUP BY cu.church_id) admins ON admins.church_id=c.id WHERE ".implode(' AND ',$where).' ORDER BY s.trial_ends_at ASC,c.id DESC LIMIT 200';
  $st=$this->pdo->prepare($sql);$st->execute($params);return $st->fetchAll();
 }
 public function operate(int $subscriptionId,int $actor,string $operation,int $days,string $reason):array
 {
  if(!in_array($operation,['extend','expire','recover'],true))throw new RuntimeException('허용되지 않은 체험 작업입니다.');
  if(mb_strlen($reason)<10||mb_strlen($reason)>500)throw new RuntimeException('작업 사유를 10자 이상 500자 이하로 입력해 주세요.');
  if($operation==='extend'&&($days<1||$days>30))throw new RuntimeException('체험 연장은 한 번에 1~30일만 가능합니다.');
  $this->pdo->beginTransaction();
  try{$st=$this->pdo->prepare("SELECT s.id,s.church_id,s.status,s.trial_ends_at,p.billing_cycle FROM subscriptions s JOIN plans p ON p.id=s.plan_id WHERE s.id=:id FOR UPDATE");$st->execute(['id'=>$subscriptionId]);$trial=$st->fetch();
   if(!$trial||$trial['billing_cycle']!=='trial')throw new RuntimeException('체험 구독을 찾을 수 없습니다.');
   if($operation==='recover'&&($trial['trial_ends_at']===null||strtotime((string)$trial['trial_ends_at'])<strtotime('-30 days')))throw new RuntimeException('30일 데이터 보관 기간이 지난 체험은 이 화면에서 복구할 수 없습니다.');
   $newStatus=$operation==='expire'?'expired':'trialing';$newEnd=$trial['trial_ends_at'];
   if($operation==='extend'){$base=max(time(),strtotime((string)$trial['trial_ends_at']));$newEnd=date('Y-m-d H:i:s',strtotime('+'.$days.' days',$base));}elseif($operation==='recover'&&strtotime((string)$newEnd)<time()){$newEnd=date('Y-m-d H:i:s',strtotime('+7 days'));}
   $st=$this->pdo->prepare('UPDATE subscriptions SET status=:status,trial_ends_at=:trial_ends_at,current_period_ends_at=:trial_ends_at WHERE id=:id AND church_id=:church_id');$st->execute(['status'=>$newStatus,'trial_ends_at'=>$newEnd,'id'=>$subscriptionId,'church_id'=>$trial['church_id']]);
   $st=$this->pdo->prepare('UPDATE churches SET status=:status WHERE id=:church_id');$st->execute(['status'=>$newStatus==='expired'?'suspended':'trial','church_id'=>$trial['church_id']]);
   $st=$this->pdo->prepare('INSERT INTO trial_operation_logs(church_id,subscription_id,actor_user_id,operation,previous_status,new_status,previous_trial_ends_at,new_trial_ends_at,reason) VALUES(:church_id,:subscription_id,:actor,:operation,:previous_status,:new_status,:previous_end,:new_end,:reason)');$st->execute(['church_id'=>$trial['church_id'],'subscription_id'=>$subscriptionId,'actor'=>$actor,'operation'=>$operation,'previous_status'=>$trial['status'],'new_status'=>$newStatus,'previous_end'=>$trial['trial_ends_at'],'new_end'=>$newEnd,'reason'=>$reason]);
   $this->pdo->commit();return ['church_id'=>(int)$trial['church_id'],'previous_status'=>$trial['status'],'new_status'=>$newStatus,'previous_trial_ends_at'=>$trial['trial_ends_at'],'new_trial_ends_at'=>$newEnd,'reason'=>$reason];
  }catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}
 }

 public function expireDue(int $limit=100):array
 {
  if($limit<1||$limit>500)throw new RuntimeException('자동 만료 처리량은 1~500이어야 합니다.');
  $this->pdo->beginTransaction();
  try{
   $st=$this->pdo->prepare("SELECT s.id,s.church_id,s.status,s.trial_ends_at FROM subscriptions s JOIN plans p ON p.id=s.plan_id WHERE s.status='trialing' AND s.trial_ends_at IS NOT NULL AND s.trial_ends_at<=NOW() AND p.billing_cycle='trial' ORDER BY s.trial_ends_at,s.id LIMIT {$limit} FOR UPDATE");
   $st->execute();$rows=$st->fetchAll();$expired=[];
   $updateSubscription=$this->pdo->prepare("UPDATE subscriptions SET status='expired',current_period_ends_at=LEAST(current_period_ends_at,NOW()) WHERE id=:id AND church_id=:church_id AND status='trialing' AND trial_ends_at<=NOW()");
   $updateChurch=$this->pdo->prepare("UPDATE churches SET status='suspended' WHERE id=:church_id AND status='trial'");
   $log=$this->pdo->prepare("INSERT INTO trial_operation_logs(church_id,subscription_id,actor_user_id,operation,previous_status,new_status,previous_trial_ends_at,new_trial_ends_at,reason) VALUES(:church_id,:subscription_id,NULL,'expire','trialing','expired',:trial_end,:trial_end,'30일 무료체험 기간 자동 만료')");
   $audit=$this->pdo->prepare("INSERT INTO admin_audit_logs(actor_user_id,church_id,action,subject_type,subject_id,metadata) VALUES(NULL,:church_id,'trial.auto_expired','subscription',:subject_id,:metadata)");
   foreach($rows as $row){$updateSubscription->execute(['id'=>$row['id'],'church_id'=>$row['church_id']]);if($updateSubscription->rowCount()!==1)continue;$updateChurch->execute(['church_id'=>$row['church_id']]);$log->execute(['church_id'=>$row['church_id'],'subscription_id'=>$row['id'],'trial_end'=>$row['trial_ends_at']]);$audit->execute(['church_id'=>$row['church_id'],'subject_id'=>(string)$row['id'],'metadata'=>json_encode(['trial_ends_at'=>$row['trial_ends_at'],'source'=>'scheduled_job'],JSON_THROW_ON_ERROR)]);$expired[]=(int)$row['id'];}
   $this->pdo->commit();return $expired;
  }catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}
 }
}
