<?php

declare(strict_types=1);

use App\Core\TenantContext;
use App\Repositories\ChurchRepository;
use App\Repositories\ChurchUserRepository;
use App\Repositories\InvitationApplicationRepository;
use App\Repositories\InvitationRepository;
use App\Repositories\InvitationStatsRepository;
use App\Repositories\UserRepository;
use App\Services\ChurchProvisioningService;
use App\Services\SubscriptionEntitlementService;

$pdo = require dirname(__DIR__) . '/bootstrap/app.php';
$failures=[]; $passes=0;
function integrationCheck(bool $condition,string $message):void{global $failures,$passes;if($condition){$passes++;echo "PASS {$message}\n";return;}$failures[]=$message;echo "FAIL {$message}\n";}

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach(['invitation_daily_stats','invitation_applications','invitation_media','invitations','admin_audit_logs','subscriptions','church_users','platform_user_roles','users','churches'] as $table){$pdo->exec("TRUNCATE TABLE {$table}");}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

$users=new UserRepository($pdo);
$platformId=$users->create('Platform Admin','platform@example.test',password_hash('Platform1234!',PASSWORD_DEFAULT));
$role=$pdo->prepare("INSERT INTO platform_user_roles (user_id, role) VALUES (:user_id, 'platform_admin')");$role->execute(['user_id'=>$platformId]);
$service=new ChurchProvisioningService($pdo);
$churchA=$service->createChurchWithOwner(['name'=>'A 교회','slug'=>'church-a','organization_type'=>'church','contact_name'=>'A 담당자','contact_email'=>'contact-a@example.test','contact_phone'=>'010-0000-0001'],['name'=>'A 관리자','email'=>'admin-a@example.test','password'=>'AdminA12345!'],$platformId);
$churchB=$service->createChurchWithOwner(['name'=>'B 교회','slug'=>'church-b','organization_type'=>'church','contact_name'=>'B 담당자','contact_email'=>'contact-b@example.test','contact_phone'=>'010-0000-0002'],['name'=>'B 관리자','email'=>'admin-b@example.test','password'=>'AdminB12345!'],$platformId);
integrationCheck($churchA!==$churchB,'two churches are provisioned separately');
integrationCheck((int)$pdo->query('SELECT COUNT(*) FROM subscriptions')->fetchColumn()===2,'each church receives one trial subscription');
integrationCheck((int)$pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'trialing'")->fetchColumn()===2,'trial subscriptions are active');

$tenantA=new TenantContext($churchA,'owner');$tenantB=new TenantContext($churchB,'owner');
$memberships=new ChurchUserRepository($pdo);$aAdmins=$memberships->listForTenant($tenantA);$bAdmins=$memberships->listForTenant($tenantB);
integrationCheck(count($aAdmins)===1&&$aAdmins[0]['email']==='admin-a@example.test','tenant A only lists tenant A administrators');
integrationCheck(count($bAdmins)===1&&$bAdmins[0]['email']==='admin-b@example.test','tenant B only lists tenant B administrators');
$churches=new ChurchRepository($pdo);
integrationCheck($churches->findForTenant($churchA,$churchA)!==null,'tenant can read its own church');
integrationCheck($churches->findForTenant($churchB,$churchA)===null,'tenant cannot read another church by changing id');
$stored=$users->findByEmail('admin-a@example.test');
integrationCheck(is_array($stored)&&$stored['password_hash']!=='AdminA12345!'&&password_verify('AdminA12345!',$stored['password_hash']),'administrator password is hashed and verifiable');

$inviteRepo=new InvitationRepository($pdo);
$input=['slug'=>'welcome','title'=>'환영 예배','event_type'=>'worship','template_code'=>'portrait','summary'=>'환영합니다','body'=>'초대합니다','event_at'=>'2026-09-10 11:00:00','venue_name'=>'본당','venue_address'=>'서울','map_url'=>'','youtube_url'=>'https://youtu.be/example','contact_name'=>'담당자','contact_phone'=>'010-1234-5678'];
$adminA=$users->findByEmail('admin-a@example.test');$inviteId=$inviteRepo->create($tenantA,$input,(int)$adminA['id']);
integrationCheck($inviteRepo->findForTenant($tenantA,$inviteId)!==null,'tenant can read its invitation');
integrationCheck($inviteRepo->findForTenant($tenantB,$inviteId)===null,'church_id prevents cross-tenant invitation access');
integrationCheck(count($inviteRepo->listForTenant($tenantB))===0,'tenant B list excludes tenant A invitation');
$inviteRepo->setStatus($tenantA,$inviteId,'published',(int)$adminA['id']);
$public=$inviteRepo->findPublished('church-a','welcome');
integrationCheck(is_array($public)&&$public['church_id']===$churchA,'published invitation resolves by church and invitation slugs');
integrationCheck($inviteRepo->findPublished('church-b','welcome')===null,'public route cannot cross church slug');

$entitlements=new SubscriptionEntitlementService($pdo);$snapshot=$entitlements->snapshot($churchA);
integrationCheck($entitlements->limit($snapshot,'traffic.monthly_bytes')===2147483648,'trial traffic limit is loaded from plan features');
$apps=new InvitationApplicationRepository($pdo);
$apps->create($public,['applicant_name'=>'신청자','phone'=>'010-1111-2222','email'=>'guest@example.test','attendee_count'=>2,'message'=>'참석합니다']);
integrationCheck(count($apps->listForTenant($tenantA,$inviteId))===1,'tenant A can list its invitation applications');
integrationCheck(count($apps->listForTenant($tenantB,$inviteId))===0,'tenant B cannot list tenant A applications');
$stats=new InvitationStatsRepository($pdo);$stats->increment($churchA,$inviteId,'views',2048);$stats->increment($churchA,$inviteId,'applications',512);
$usage=$stats->usage($churchA);
integrationCheck((int)$usage['views']===1&&(int)$usage['applications']===1&&(int)$usage['traffic_bytes']===2560,'daily usage aggregates views applications and traffic');
integrationCheck((int)$pdo->query('SELECT COUNT(*) FROM admin_audit_logs')->fetchColumn()>=2,'church provisioning writes audit logs');

if($failures!==[]){fwrite(STDERR,count($failures)." integration test(s) failed.\n");exit(1);}echo "OK {$passes} integration tests passed.\n";
