<?php
declare(strict_types=1);
namespace App\Repositories;
use App\Core\TenantContext; use PDO;
final class SupportTicketRepository
{
    public function __construct(private readonly PDO $pdo) {}
    public function listForTenant(TenantContext $tenant): array
    {
        $statement=$this->pdo->prepare('SELECT id,ticket_type,priority,subject,status,response_summary,answered_at,created_at,updated_at FROM support_tickets WHERE church_id=:church_id ORDER BY id DESC LIMIT 100');
        $statement->execute(['church_id'=>$tenant->churchId()]); return $statement->fetchAll();
    }
    public function createForTenant(TenantContext $tenant,int $userId,array $data): int
    {
        $statement=$this->pdo->prepare('INSERT INTO support_tickets (church_id,requester_user_id,ticket_type,priority,subject,body,related_url,occurred_at) VALUES (:church_id,:user_id,:ticket_type,:priority,:subject,:body,:related_url,:occurred_at)');
        $statement->execute(['church_id'=>$tenant->churchId(),'user_id'=>$userId,'ticket_type'=>$data['ticket_type'],'priority'=>$data['priority'],'subject'=>$data['subject'],'body'=>$data['body'],'related_url'=>$data['related_url']?:null,'occurred_at'=>$data['occurred_at']?:null]);
        return (int)$this->pdo->lastInsertId();
    }
    public function listForPlatform(array $filters=[]): array
    {
        $sql='SELECT t.*,c.name AS church_name FROM support_tickets t JOIN churches c ON c.id=t.church_id WHERE 1=1'; $params=[];
        $status=(string)($filters['status']??''); if(in_array($status,['open','in_progress','answered','closed'],true)){ $sql.=' AND t.status=:status'; $params['status']=$status; }
        $priority=(string)($filters['priority']??''); if(in_array($priority,['normal','high','urgent'],true)){ $sql.=' AND t.priority=:priority'; $params['priority']=$priority; }
        $statement=$this->pdo->prepare($sql.' ORDER BY FIELD(t.priority,\'urgent\',\'high\',\'normal\'),t.id ASC LIMIT 200'); $statement->execute($params); return $statement->fetchAll();
    }
    public function reviewForPlatform(int $ticketId,int $reviewerId,string $status,string $response): ?array
    {
        if(!in_array($status,['in_progress','answered','closed'],true)) return null;
        $this->pdo->beginTransaction();
        try {
            $find=$this->pdo->prepare('SELECT id,church_id,status FROM support_tickets WHERE id=:id FOR UPDATE'); $find->execute(['id'=>$ticketId]); $ticket=$find->fetch();
            if(!$ticket){$this->pdo->rollBack();return null;}
            $update=$this->pdo->prepare('UPDATE support_tickets SET status=:status,assigned_user_id=:reviewer_id,response_summary=:response,answered_at=CASE WHEN :status_check=\'answered\' THEN NOW() ELSE answered_at END WHERE id=:id');
            $update->execute(['status'=>$status,'reviewer_id'=>$reviewerId,'response'=>$response?:null,'status_check'=>$status,'id'=>$ticketId]); $this->pdo->commit(); return $ticket;
        } catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}
    }
}
