<?php use App\Core\View; ?>
<div class="page-header"><div><p class="eyebrow">전도지·초대장</p><h1><?= View::e($item['title']) ?> 통계</h1><p class="muted">조회·공유·신청의 일별 추이와 선택 기간 합계입니다.</p></div><a class="button secondary" href="/admin/invitations">목록</a></div>
<section class="panel">
<form method="get" class="filter-form"><label>시작일<input type="date" name="from" value="<?= View::e($from) ?>"></label><label>종료일<input type="date" name="to" value="<?= View::e($to) ?>"></label><button class="primary">조회</button></form>
<?php if ($retentionDays !== null): ?><p class="muted">현재 구독 기능값에 따라 최근 <?= number_format((int)$retentionDays) ?>일 범위에서 조회됩니다. 기존 통계 데이터는 이 화면에서 삭제하지 않습니다.</p><?php endif; ?>
</section>
<div class="dashboard-grid">
<section class="stat-card"><span>조회</span><strong><?= number_format((int)$summary['views']) ?></strong></section>
<section class="stat-card"><span>공유</span><strong><?= number_format((int)$summary['shares']) ?></strong></section>
<section class="stat-card"><span>신청</span><strong><?= number_format((int)$summary['applications']) ?></strong></section>
<section class="stat-card"><span>신청 전환율</span><strong><?= (int)$summary['views'] > 0 ? number_format((int)$summary['applications'] * 100 / (int)$summary['views'], 1) : '0.0' ?>%</strong></section>
</div>
<section class="panel"><h2>일별 추이</h2><div class="table-wrap"><table><thead><tr><th>날짜</th><th>조회</th><th>공유</th><th>신청</th><th>전송량</th></tr></thead><tbody>
<?php foreach ($daily as $row): ?><tr><td><?= View::e($row['stat_date']) ?></td><td><?= number_format((int)$row['views']) ?></td><td><?= number_format((int)$row['shares']) ?></td><td><?= number_format((int)$row['applications']) ?></td><td><?= number_format((int)$row['traffic_bytes']/1048576, 2) ?> MB</td></tr><?php endforeach; ?>
</tbody></table></div></section>
