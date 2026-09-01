<?php
use App\Core\Csrf;
use App\Core\View;

$statusLabels = [
    'open' => '접수',
    'in_progress' => '처리 중',
    'answered' => '답변 완료',
    'closed' => '종료',
];
$categoryLabels = [
    'general' => '일반',
    'subscription' => '요금·구독',
    'technical' => '기능 오류/사용법',
    'policy' => '개인정보·보안',
];
?>
<div class="page-header">
  <div>
    <p class="eyebrow">플랫폼 본사 관리자</p>
    <h1>공개 문의 상세</h1>
  </div>
  <p class="muted">요청 #<?= (int)$request['id'] ?></p>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= View::e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?>

<section class="panel">
  <div class="section-head"><h2><?= View::e($request['subject']) ?></h2><small><?= View::e($request['created_at']) ?></small></div>
  <div class="kvs" style="display:grid;grid-template-columns: repeat(2,minmax(0,1fr));gap:12px;margin-bottom:12px;">
    <div><strong>문의유형</strong><br><?= View::e($categoryLabels[$request['category']] ?? $request['category']) ?></div>
    <div><strong>상태</strong><br><?= View::e($statusLabels[(string)($request['status'] ?? 'open')]) ?></div>
    <div><strong>이름</strong><br><?= View::e($request['name']) ?></div>
    <div><strong>이메일</strong><br><a href="mailto:<?= View::e($request['email']) ?>"><?= View::e($request['email']) ?></a></div>
    <div><strong>연락처</strong><br><?= View::e($request['phone'] ?: '-') ?></div>
    <div><strong>단체명</strong><br><?= View::e($request['church_name'] ?: '-') ?></div>
    <div><strong>개인정보 동의</strong><br><?= ($request['agreed_terms'] ?? 0) ? '동의' : '미동의' ?></div>
    <div><strong>IP / UA</strong><br><?= View::e($request['ip_address'] ?: '-') ?> / <?= View::e($request['user_agent'] ?: '-') ?></div>
  </div>
</section>

<section class="panel">
  <h2>문의 내용</h2>
  <p><?= nl2br(View::e((string)$request['message'])) ?></p>
</section>

<section class="panel">
  <h2>처리 정보</h2>
  <div class="kvs" style="display:grid;grid-template-columns: repeat(2,minmax(0,1fr));gap:12px;margin-bottom:12px;">
    <div><strong>처리일시</strong><br><?= View::e($request['handled_at'] ?: '-') ?></div>
    <div><strong>담당자</strong><br><?= View::e($request['handled_by_name'] ?: (($request['handled_by_user_id'] ? (string)$request['handled_by_user_id'] : '-'))) ?></div>
  </div>
  <form method="post" action="/control/public-contacts/<?= (int)$request['id'] ?>/status" class="form-grid">
    <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
    <label>상태
      <select name="status" required>
        <?php foreach (['open'=>'접수', 'in_progress'=>'처리 중', 'answered'=>'답변 완료', 'closed'=>'종료'] as $value => $label): ?>
          <option value="<?= $value ?>" <?= (($request['status'] ?? 'open') === $value) ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="full">처리 메모
      <textarea name="handled_note" rows="5" maxlength="4000"><?= View::e((string)($request['handled_note'] ?? '')) ?></textarea>
    </label>
    <button class="button primary" type="submit">처리 내용 저장</button>
  </form>
</section>

<p class="muted"><a href="/control/public-contacts">목록으로 돌아가기</a></p>
