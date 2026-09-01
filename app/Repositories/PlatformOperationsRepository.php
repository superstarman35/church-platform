<?php
declare(strict_types=1);
namespace App\Repositories;
use PDO;
final class PlatformOperationsRepository
{
    public function __construct(private readonly PDO $pdo) {}
    public function search(string $query,string $churchStatus,string $family,string $subscriptionStatus): array
    {
        $where=[];$params=[];
        if($query!==''){$where[]='(c.name LIKE :query OR c.slug LIKE :query)';$params['query']='%'.str_replace(['%','_'],['\\%','\\_'],$query).'%';}
        if(in_array($churchStatus,['trial','active','suspended','archived'],true)){$where[]='c.status=:church_status';$params['church_status']=$churchStatus;}
        if(in_array($family,['invitation','website','custom'],true)){$where[]='c.product_family=:family';$params['family']=$family;}
        if(in_array($subscriptionStatus,['trialing','active','past_due','suspended','cancelled','expired'],true)){$where[]='s.status=:subscription_status';$params['subscription_status']=$subscriptionStatus;}
        $sql="SELECT c.id,c.name,c.slug,c.status,c.product_family,c.created_at,s.status subscription_status,p.name plan_name,
          COALESCE(st.traffic_bytes,0) traffic_bytes,COALESCE(m.storage_bytes,0) storage_bytes,
          COALESCE(tl.limit_value,0)+COALESCE(tover.extra_limit,0) traffic_limit,
          COALESCE(sl.limit_value,0)+COALESCE(sover.extra_limit,0) storage_limit
          FROM churches c
          LEFT JOIN subscriptions s ON s.id=(SELECT s2.id FROM subscriptions s2 WHERE s2.church_id=c.id ORDER BY s2.id DESC LIMIT 1)
          LEFT JOIN plans p ON p.id=s.plan_id
          LEFT JOIN plan_features tl ON tl.plan_id=p.id AND tl.feature_code='traffic.monthly_bytes'
          LEFT JOIN plan_features sl ON sl.plan_id=p.id AND sl.feature_code='storage.total_bytes'
          LEFT JOIN quota_overrides tover ON tover.church_id=c.id AND tover.feature_code='traffic.monthly_bytes'
          LEFT JOIN quota_overrides sover ON sover.church_id=c.id AND sover.feature_code='storage.total_bytes'
          LEFT JOIN (SELECT church_id,SUM(traffic_bytes) traffic_bytes FROM invitation_daily_stats WHERE stat_date>=DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01') GROUP BY church_id) st ON st.church_id=c.id
          LEFT JOIN (SELECT church_id,SUM(file_bytes) storage_bytes FROM invitation_media WHERE deleted_at IS NULL GROUP BY church_id) m ON m.church_id=c.id"
          .($where?' WHERE '.implode(' AND ',$where):'')." ORDER BY c.id DESC LIMIT 200";
        $statement=$this->pdo->prepare($sql);$statement->execute($params);return $statement->fetchAll();
    }
}
