<?php use App\Core\Csrf; use App\Core\View;
$statusLabels = ['new' => '신규', 'confirmed' => '확인함', 'cancelled' => '취소'];
$query = http_build_query(array_filter($filters, static fn ($value) => $value !== ''));
?>
<div class="page-header"><div><p class="eyebrow">신청자 관리</p><h1><?= View::e($item['title']) ?></h1><p class="muted">접수된 신청 정보는 교회 관리자만 볼 수 있습니다.</p></div><a class="button secondary" href="/admin/invitations">목록</a></div>
<?php if($success): ?><div class="alert alert-success"><?= View::e($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>
<section class="panel"><form method="get" class="filter-form">
<label>검색<input type="search" name="q" value="<?= View::e($filters['q']) ?>" placeholder="이름·연락처·이메일"></label>
<label>상태<select name="status"><option value="">전체</option><?php foreach($statusLabels as $value=>$label): ?><option value="<?= $value ?>" <?= $filters['status']===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label>
<label>시작일<input type="date" name="date_from" value="<?= View::e($filters['date_from']) ?>"></label>
<label>종료일<input type="date" name="date_to" value="<?= View::e($filters['date_to']) ?>"></label>
<div class="filter-actions"><button class="button primary" type="submit">조회</button><a class="button secondary" href="/admin/invitations/<?= (int)$item['id'] ?>/applications">초기화</a><a class="button secondary" href="/admin/invitations/<?= (int)$item['id'] ?>/applications/export<?= $query!==''?'?'.View::e($query):'' ?>">CSV 내보내기</a></div>
</form></section>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>접수일</th><th>이름</th><th>연락처</th><th>이메일</th><th>인원</th><th>메시지</th><th>상태</th></tr></thead><tbody>
<?php if($applications===[]): ?><tr><td class="empty" colspan="7">접수된 신청이 없습니다.</td></tr><?php endif; ?>
<?php foreach($applications as $a): ?><tr><td><?= View::e($a['created_at']) ?></td><td><?= View::e($a['applicant_name']) ?></td><td><?= View::e($a['phone']) ?></td><td><?= View::e($a['email']) ?></td><td><?= (int)$a['attendee_count'] ?></td><td><?= View::e($a['message']) ?></td><td><form method="post" action="/admin/invitations/<?= (int)$item['id'] ?>/applications/<?= (int)$a['id'] ?>/status" class="inline status-form"><input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>"><select name="status" aria-label="<?= View::e($a['applicant_name']) ?> 신청 상태"><?php foreach($statusLabels as $value=>$label): ?><option value="<?= $value ?>" <?= $a['status']===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select><button class="button primary small" type="submit">저장</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></section>
