<?php
use App\Core\View;
use App\Core\Csrf;

$dashboardUrl = isset($dashboardUrl) && is_string($dashboardUrl) ? $dashboardUrl : null;
$loginUrl = $dashboardUrl ?? '/login';
$now = date('Y');
$success = isset($success) && is_string($success) ? $success : null;
$error = isset($error) && is_string($error) ? $error : null;
$old = is_array($old ?? null) ? $old : [];
$v = static function (string $key, string $default = '') use ($old): string {
    $value = $old[$key] ?? $default;
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    return is_string($value) ? $value : $default;
};
?>
<header class="site-header"><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><button class="menu-toggle" aria-expanded="false" aria-controls="main-nav">메뉴</button><nav id="main-nav"><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a class="active" href="/contact">문의하기</a></nav><a class="login-link" href="<?= View::e($loginUrl) ?>"><?= $dashboardUrl ? '관리자 화면' : '로그인' ?></a></header>

<main class="info-page">
  <section class="info-hero">
    <p class="eyebrow center"><i></i>CONTACT<i></i></p>
    <h1>문의는 언제든지 편하게 남겨 주세요.</h1>
    <p>서비스 이용, 계정 설정, 초대장 제작 흐름에 대한 궁금한 점을 받습니다.</p>
  </section>

  <section class="info-wrap">
    <article>
      <h2>온라인 문의</h2>
      <?php if($success): ?><p style="color:#2f8f3b;font-weight:700;"><?= View::e($success) ?></p><?php endif; ?>
      <?php if($error): ?><p style="color:#d84e4e;font-weight:700;"><?= View::e($error) ?></p><?php endif; ?>
      <form method="post" action="/contact" class="form-grid">
        <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
        <label class="full">문의 유형
          <select name="category" required>
            <option value="general" <?= $v('category','general')==='general'?'selected':'' ?>>일반 문의</option>
            <option value="subscription" <?= $v('category')==='subscription'?'selected':'' ?>>요금·구독</option>
            <option value="technical" <?= $v('category')==='technical'?'selected':'' ?>>기능 오류/사용법</option>
            <option value="policy" <?= $v('category')==='policy'?'selected':'' ?>>개인정보·보안</option>
          </select>
        </label>
        <label>담당자 이름<input name="name" required maxlength="80" value="<?= View::e($v('name')) ?>"></label>
        <label>이메일<input type="email" name="email" required maxlength="190" value="<?= View::e($v('email')) ?>"></label>
        <label>연락처(선택)<input name="phone" maxlength="30" value="<?= View::e($v('phone')) ?>"></label>
        <label class="full">교회 또는 단체명(선택)<input name="church_name" maxlength="120" value="<?= View::e($v('church_name')) ?>"></label>
        <label class="full">제목<input name="subject" required minlength="2" maxlength="120" value="<?= View::e($v('subject')) ?>"></label>
        <label class="full">문의 내용<textarea name="message" required minlength="10" maxlength="3000" rows="7"><?= View::e($v('message')) ?></textarea></label>
        <label class="full"><input type="checkbox" name="agree_terms" value="1" <?= $v('agree_terms')==='1'?'checked':'' ?>> 개인정보 처리에 동의합니다.</label>
        <button class="button primary" type="submit">문의 접수</button>
      </form>
    </article>

    <article>
      <h2>문의 채널</h2>
      <p>이메일: support@church-invitation.example</p>
      <p>지원 시간: 평일 10:00~18:00 (점심 12:30~13:30)</p>
      <p>응답 목표: 영업일 기준 24시간 내</p>
    </article>

    <article>
      <h2>서비스 안내</h2>
      <ul>
        <li>요금제 변경 요청과 승인 처리 안내</li>
        <li>초대장 공개/비공개/만료 정책 관련 확인</li>
        <li>모바일 초대장 이미지·YouTube·지도 설정 점검</li>
      </ul>
    </article>
  </section>
</main>

<footer><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><p>교회와 이웃의 마음을 잇는 기독교 모바일 초대장</p><nav><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a class="active" href="/contact">문의하기</a><a href="/login">관리자 로그인</a></nav><small>© <?= View::e($now) ?> Church Invitation. All rights reserved.</small></footer>
<script>const menu=document.querySelector('.menu-toggle'),nav=document.querySelector('#main-nav');menu?.addEventListener('click',()=>{const open=menu.getAttribute('aria-expanded')==='true';menu.setAttribute('aria-expanded',String(!open));nav?.classList.toggle('open',!open)});nav?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{menu?.setAttribute('aria-expanded','false');nav?.classList.remove('open')}));</script>
