<?php use App\Core\Csrf; use App\Core\View; ?>
<div class="page-header"><div><p class="eyebrow">전도지·초대장</p><h1>초대장 관리</h1><p class="muted">여러 개의 초대장을 만들고 복제하여 행사별로 운영할 수 있습니다.</p></div><a class="button primary" href="/admin/invitations/create">새 초대장</a></div>
<?php if ($success): ?><div class="alert alert-success"><?= View::e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>
<section class="panel">
<div class="table-wrap"><table><thead><tr><th>제목·주소</th><th>유형</th><th>디자인</th><th>상태</th><th>조회/신청</th><th>작업</th></tr></thead><tbody>
<?php if ($items === []): ?><tr><td class="empty" colspan="6">아직 초대장이 없습니다. 첫 초대장을 만들어 보세요.</td></tr><?php endif; ?>
<?php foreach ($items as $item): ?>
<tr><td><strong><?= View::e($item['title']) ?></strong><br><small>/<?= View::e($item['slug']) ?></small></td><td><?= View::e($item['event_type']) ?></td><td><?= View::e($item['template_code']) ?></td><td><span class="badge badge-<?= View::e($item['status']) ?>"><?= View::e($item['status']) ?></span></td><td><?= number_format((int)$item['views']) ?> / <?= number_format((int)$item['application_count']) ?></td>
<td><div class="actions"><a class="button secondary" href="/admin/invitations/<?= (int)$item['id'] ?>/edit">수정</a><a class="button secondary" href="/admin/invitations/<?= (int)$item['id'] ?>/applications">신청자</a>
<?php if ($item['status']==='published'): ?><a class="button secondary" target="_blank" href="/i/<?= View::e($item['church_slug']) ?>/<?= View::e($item['slug']) ?>" data-public-link>보기</a><?php endif; ?>
<form method="post" action="/admin/invitations/<?= (int)$item['id'] ?>/clone"><input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>"><button class="secondary">복제</button></form>
<?php if ($item['status']!=='published'): ?><form method="post" action="/admin/invitations/<?= (int)$item['id'] ?>/publish"><input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>"><button class="primary">게시</button></form><?php else: ?><form method="post" action="/admin/invitations/<?= (int)$item['id'] ?>/end"><input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>"><button class="danger">종료</button></form><?php endif; ?>
</div></td></tr>
<?php endforeach; ?></tbody></table></div></section>


