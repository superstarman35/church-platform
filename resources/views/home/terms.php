<?php
use App\Core\View;

$dashboardUrl = isset($dashboardUrl) && is_string($dashboardUrl) ? $dashboardUrl : null;
$loginUrl = $dashboardUrl ?? '/login';
$now = date('Y');
?>
<header class="site-header"><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><button class="menu-toggle" aria-expanded="false" aria-controls="main-nav">메뉴</button><nav id="main-nav"><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a></nav><a class="login-link" href="<?= View::e($loginUrl) ?>"><?= $dashboardUrl ? '관리자 화면' : '로그인' ?></a></header>
<main class="info-page">
  <section class="info-hero">
    <p class="eyebrow center"><i></i>TERMS OF USE<i></i></p>
    <h1>서비스 이용약관</h1>
    <p>기독교 모바일 초대장 서비스 이용 전에 확인해야 할 기본 규칙을 정리했습니다.</p>
  </section>

  <section class="info-wrap">
    <article>
      <h2>제1조 (목적)</h2>
      <p>본 약관은 본 플랫폼에서 제공하는 홈페이지 없는 전도지·초대장 중심의 서비스를 이용할 때 적용됩니다. 본 서비스는 초대장 생성·공개·공유·신청 관리·통계 기능을 제공합니다.</p>
    </article>
    <article>
      <h2>제2조 (계정과 권한)</h2>
      <ul>
        <li>관리자 계정은 교회/단체 단위로 발급되며, 역할과 권한을 분리해 사용합니다.</li>
        <li>교회별 데이터는 다른 교회 데이터에 접근할 수 없도록 기술적으로 격리됩니다.</li>
        <li>비인가 사용으로 로그인 정보를 공유하는 행위는 금지됩니다.</li>
      </ul>
    </article>
    <article>
      <h2>제3조 (초대장 사용)</h2>
      <ul>
        <li>초대장은 모바일 공개 URL, QR, 공유 링크로 배포할 수 있습니다.</li>
        <li>초대장 이미지·문구에는 타인 권리를 침해하는 내용이 없어야 합니다.</li>
        <li>이용 목적에 맞지 않는 상업적/불법적 행사 홍보는 제한될 수 있습니다.</li>
      </ul>
    </article>
    <article>
      <h2>제4조 (요금 및 구독)</h2>
      <ul>
        <li>요금제는 홈페이지형과 초대장형 상품군으로 분리 적용될 수 있습니다.</li>
        <li>상품 변경, 한도 상향, 추가 구매는 관리자 화면의 요청 절차를 따릅니다.</li>
        <li>요금, 부가세 포함 여부, 연간 결제 조건은 정책 공지와 안내문을 기준으로 합니다.</li>
      </ul>
    </article>
    <article>
      <h2>제5조 (중단/해지)</h2>
      <p>약관 위반이 확인되면 접근이 제한될 수 있고, 서비스 중단 시 보관 데이터 및 백업 정책은 공지된 방침에 따라 처리됩니다.</p>
    </article>
  </section>
</main>
<footer><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><p>교회와 이웃의 마음을 잇는 기독교 모바일 초대장</p><nav><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a><a href="/login">관리자 로그인</a></nav><small>© <?= View::e($now) ?> Church Invitation. All rights reserved.</small></footer>
<script>const menu=document.querySelector('.menu-toggle'),nav=document.querySelector('#main-nav');menu?.addEventListener('click',()=>{const open=menu.getAttribute('aria-expanded')==='true';menu.setAttribute('aria-expanded',String(!open));nav?.classList.toggle('open',!open)});nav?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{menu?.setAttribute('aria-expanded','false');nav?.classList.remove('open')}));</script>
