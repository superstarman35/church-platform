<div class="page-header"><div><p class="eyebrow">플랫폼 관리</p><h1>교회·단체</h1></div><a class="button primary" href="/control/churches/create">새 교회·단체</a></div>
<?php if (!empty($success)): ?><div class="alert alert-success"><?= \App\Core\View::e($success) ?></div><?php endif; ?>
<div class="table-wrap"><table><thead><tr><th>ID</th><th>이름</th><th>주소</th><th>유형</th><th>상품</th><th>상태</th><th>체험 종료</th><th></th></tr></thead><tbody>
<?php foreach ($churches as $church): ?><tr>
<td><?= (int) $church['id'] ?></td><td><?= \App\Core\View::e($church['name']) ?></td><td><?= \App\Core\View::e($church['slug']) ?></td><td><?= \App\Core\View::e($church['organization_type']) ?></td><td><?= \App\Core\View::e($church['product_family']) ?></td><td><?= \App\Core\View::e($church['subscription_status'] ?? $church['status']) ?></td><td><?= \App\Core\View::e($church['trial_ends_at'] ?? '-') ?></td><td><a href="/control/churches/<?= (int) $church['id'] ?>/admins/create">관리자 추가</a></td>
</tr><?php endforeach; ?>
<?php if ($churches === []): ?><tr><td colspan="8" class="empty">등록된 교회·단체가 없습니다.</td></tr><?php endif; ?>
</tbody></table></div>
