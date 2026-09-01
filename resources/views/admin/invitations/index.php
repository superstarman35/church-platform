<?php use App\Core\Csrf; use App\Core\View; ?>
<div class="page-header"><div><p class="eyebrow">전도지·초대장</p><h1>초대장 관리</h1><p class="muted">여러 개의 초대장을 만들고 복제하여 행사별로 운영할 수 있습니다.</p></div><a class="button primary" href="/admin/invitations/create">새 초대장</a></div>
<?php if ($success): ?><div class="alert alert-success"><?= View::e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>
<section class="panel">
<div class="table-wrap"><table><thead><tr><th>제목·주소</th><th>유형</th><th>디자인</th><th>상태</th><th>조회/신청</th><th>작업</th></tr></thead><tbody>
<?php if ($items === []): ?><tr><td class="empty" colspan="6">아직 초대장이 없습니다. 첫 초대장을 만들어 보세요.</td></tr><?php endif; ?>
<?php foreach ($items as $item): ?>
<?php $now=time(); $lifecycleStatus=!empty($item['deleted_at'])?'휴지통':(($item['status']==='published' && !empty($item['publish_at']) && strtotime((string)$item['publish_at'])>$now)?'예약':(($item['status']==='published' && !empty($item['expires_at']) && strtotime((string)$item['expires_at'])<=$now)?'만료':$item['status'])); ?>
<tr><td><strong><?= View::e($item['title']) ?></strong><br><small>/<?= View::e($item['slug']) ?></small></td><td><?= View::e($item['event_type']) ?></td><td><?= View::e($item['template_code']) ?></td><td><span class="badge badge-<?= View::e($item['status']) ?>"><?= View::e($lifecycleStatus) ?></span><?php if(!empty($item['publish_at'])): ?><br><small>게시 <?= View::e($item['publish_at']) ?></small><?php endif; ?><?php if(!empty($item['expires_at'])): ?><br><small>만료 <?= View::e($item['expires_at']) ?></small><?php endif; ?></td><td><?= number_format((int)$item['views']) ?> / <?= number_format((int)$item['application_count']) ?></td>
<td><div class="actions">
<?php if (empty($item['deleted_at'])): ?>
<a class="button secondary" href="/admin/invitations/<?= (int)$item['id'] ?>/edit">수정</a><a class="button secondary" href="/admin/invitations/<?= (int)$item['id'] ?>/applications">신청자</a>
<a class="button secondary" href="/admin/invitations/<?= (int)$item['id'] ?>/stats">통계</a>
<?php if ($item['status']==='published'): ?><a class="button secondary" href="/admin/invitations/<?= (int)$item['id'] ?>/share">QR·공유</a><?php endif; ?>
<?php if ($item['status']==='published'): ?><a class="button secondary" target="_blank" href="/i/<?= View::e($item['church_slug']) ?>/<?= View::e($item['slug']) ?>" data-public-link>보기</a><?php endif; ?>
<form method="post" action="/admin/invitations/<?= (int)$item['id'] ?>/clone"><input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>"><button class="secondary">복제</button></form>
<?php if ($item['status']!=='published'): ?><form method="post" action="/admin/invitations/<?= (int)$item['id'] ?>/publish"><input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>"><button class="primary">게시</button></form><?php else: ?><form method="post" action="/admin/invitations/<?= (int)$item['id'] ?>/end"><input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>"><button class="danger">종료</button></form><?php endif; ?>
<form method="post" action="/admin/invitations/<?= (int)$item['id'] ?>/trash"><input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>"><button class="danger">휴지통</button></form>
<?php else: ?>
<form method="post" action="/admin/invitations/<?= (int)$item['id'] ?>/restore"><input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>"><button>복원</button></form>
<?php endif; ?>
</div></td></tr>
<?php endforeach; ?></tbody></table></div></section>
