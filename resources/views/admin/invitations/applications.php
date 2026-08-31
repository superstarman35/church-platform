<?php use App\Core\View; ?>
<div class="page-header"><div><p class="eyebrow">신청자 관리</p><h1><?= View::e($item['title']) ?></h1><p class="muted">접수된 신청 정보는 교회 관리자만 볼 수 있습니다.</p></div><a class="button secondary" href="/admin/invitations">목록</a></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>접수일</th><th>이름</th><th>연락처</th><th>이메일</th><th>인원</th><th>메시지</th><th>상태</th></tr></thead><tbody>
<?php if($applications===[]): ?><tr><td class="empty" colspan="7">접수된 신청이 없습니다.</td></tr><?php endif; ?>
<?php foreach($applications as $a): ?><tr><td><?= View::e($a['created_at']) ?></td><td><?= View::e($a['applicant_name']) ?></td><td><?= View::e($a['phone']) ?></td><td><?= View::e($a['email']) ?></td><td><?= (int)$a['attendee_count'] ?></td><td><?= View::e($a['message']) ?></td><td><?= View::e($a['status']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>

