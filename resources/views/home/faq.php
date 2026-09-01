<?php
use App\Core\View;

$dashboardUrl = isset($dashboardUrl) && is_string($dashboardUrl) ? $dashboardUrl : null;
$loginUrl = $dashboardUrl ?? '/login';
$now = date('Y');
?>
<header class="site-header"><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><button class="menu-toggle" aria-expanded="false" aria-controls="main-nav">메뉴</button><nav id="main-nav"><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a class="active" href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a></nav><a class="login-link" href="<?= View::e($loginUrl) ?>"><?= $dashboardUrl ? '관리자 화면' : '로그인' ?></a></header>
<main class="info-page">
  <section class="info-hero">
    <p class="eyebrow center"><i></i>FAQ<i></i></p>
    <h1>자주 묻는 질문</h1>
    <p>운영에 바로 쓸 수 있도록 질문과 답을 먼저 정리했습니다.</p>
  </section>
  <section class="info-wrap">
    <article>
      <h2>계정·로그인</h2>
      <details><summary>로그인이 안 될 때는 어떻게 해야 하나요?</summary><p>브라우저 캐시를 지우고 로그인 페이지에서 다시 진입하세요. 관리자에게 권한이 비활성 상태로 설정된 경우 초기 로그인 안내를 확인해 주세요.</p></details>
      <details><summary>새 초대장을 만들면 어디에 노출되나요?</summary><p>교회 링크에서 공개 설정된 상태로 게시되며, 모바일 공유와 QR 코드로 배포할 수 있습니다.</p></details>
    </article>
    <article>
      <h2>요금·구독</h2>
      <details><summary>초대장 기본형에서 성장형으로 변경이 가능한가요?</summary><p>관리자 화면에서 구독 변경 요청을 하면 운영 정책에 따라 반영됩니다.</p></details>
      <details><summary>사용량 초과 시 어떤 제약이 생기나요?</summary><p>70%, 85%, 100% 임계점에서 안내와 제한이 단계적으로 적용되며, 운영에서 확장 제안이 가능합니다.</p></details>
    </article>
    <article>
      <h2>신청자 관리</h2>
      <details><summary>신청자 목록은 어디서 볼 수 있나요?</summary><p>초대장 관리자 화면에서 행사별 신청자 목록과 참석 상태를 확인할 수 있습니다.</p></details>
      <details><summary>신청자 데이터는 안전한가요?</summary><p>교회별 데이터 격리와 권한 기반 조회 제한이 적용되며, 요청된 보존 규칙을 따릅니다.</p></details>
    </article>
  </section>
</main>
<footer><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><p>교회와 이웃의 마음을 잇는 기독교 모바일 초대장</p><nav><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a><a href="/login">관리자 로그인</a></nav><small>© <?= View::e($now) ?> Church Invitation. All rights reserved.</small></footer>
<script>const menu=document.querySelector('.menu-toggle'),nav=document.querySelector('#main-nav');menu?.addEventListener('click',()=>{const open=menu.getAttribute('aria-expanded')==='true';menu.setAttribute('aria-expanded',String(!open));nav?.classList.toggle('open',!open)});nav?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{menu?.setAttribute('aria-expanded','false');nav?.classList.remove('open')}));</script>
