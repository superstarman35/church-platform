<?php use App\Core\View; ?>
<div class="page-header"><div><p class="eyebrow">플랫폼 본사 관리자</p><h1>공개 문의 관리</h1><p class="muted">홈페이지 공개 문의(문의하기) 내용을 확인합니다.</p></div></div>
<?php if ($success): ?><div class="alert alert-success"><?= View::e($success) ?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

<section class="panel">
  <form method="get" action="/control/public-contacts" class="form-grid">
    <label>문의 유형
      <select name="category">
        <option value="">전체</option>
        <?php foreach (['general' => '일반', 'subscription' => '요금·구독', 'technical' => '기능 오류/사용법', 'policy' => '개인정보·보안'] as $value => $label): ?>
          <option value="<?= $value ?>" <?= ($filters['category'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </label>
      <label>상태
        <select name="status">
          <option value="">전체</option>
        <?php foreach (['open'=>'접수', 'in_progress'=>'처리 중', 'answered'=>'답변 완료', 'closed'=>'종료'] as $value => $label): ?>
          <option value="<?= $value ?>" <?= ($filters['status'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit">조회</button>
    <?php
    $exportQuery = [];
    if (($filters['category'] ?? '') !== '') {
        $exportQuery['category'] = (string)$filters['category'];
    }
    if (($filters['status'] ?? '') !== '') {
        $exportQuery['status'] = (string)$filters['status'];
    }
    ?>
    <a class="button secondary" href="/control/public-contacts/export<?= $exportQuery === [] ? '' : ('?' . http_build_query($exportQuery)) ?>">CSV 내보내기</a>
  </form>
</section>

<section class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>번호</th>
          <th>유형</th>
          <th>상태</th>
          <th>신고자</th>
          <th>교회명</th>
          <th>이메일</th>
          <th>제목</th>
          <th>문의 내용</th>
          <th>접수일</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $categories = [
          'general' => '일반',
          'subscription' => '요금·구독',
          'technical' => '기능 오류/사용법',
          'policy' => '개인정보·보안',
        ];
        $statusLabels = [
          'open' => '접수',
          'in_progress' => '처리 중',
          'answered' => '답변 완료',
          'closed' => '종료',
        ];
        ?>
        <?php foreach ($requests as $request): ?>
          <tr>
            <td>#<?= (int)$request['id'] ?></td>
            <td><?= View::e($categories[$request['category']] ?? $request['category']) ?></td>
            <td><?= View::e($statusLabels[(string)($request['status'] ?? 'open')] ?? '접수') ?></td>
            <td><?= View::e($request['name']) ?></td>
            <td><?= View::e($request['church_name'] ?: '-') ?></td>
            <td><?= View::e($request['email']) ?></td>
            <td><?= View::e($request['subject']) ?></td>
            <td>
              <details>
                <summary><?= View::e(mb_strlen((string)$request['message']) > 70 ? mb_substr((string)$request['message'], 0, 70) . '…' : (string)$request['message']) ?></summary>
                <p><?= View::e((string)$request['message']) ?></p>
                <small style="display:block;color:var(--muted);font-size:11px;margin-top:6px;">연락처: <?= View::e($request['phone'] ?: '-') ?></small>
                <small style="display:block;color:var(--muted);font-size:11px;">User-Agent: <?= View::e($request['user_agent'] ?: '-') ?></small>
              </details>
              <small style="display:block;color:var(--muted);font-size:12px;">IP: <?= View::e($request['ip_address'] ?: '-') ?></small>
            </td>
            <td>
              <a href="/control/public-contacts/<?= (int)$request['id'] ?>">상세보기</a>
            </td>
            <td><?= View::e($request['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($requests === []): ?>
          <tr>
            <td colspan="9" class="empty">조건에 맞는 공개 문의가 없습니다.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
