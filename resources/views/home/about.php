<?php
use App\Core\View;

$dashboardUrl = isset($dashboardUrl) && is_string($dashboardUrl) ? $dashboardUrl : null;
$loginUrl = $dashboardUrl ?? '/login';
$now = date('Y');
?>
<header class="site-header"><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><button class="menu-toggle" aria-expanded="false" aria-controls="main-nav">메뉴</button><nav id="main-nav"><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a class="active" href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a></nav><a class="login-link" href="<?= View::e($loginUrl) ?>"><?= $dashboardUrl ? '관리자 화면' : '로그인' ?></a></header>
<main class="info-page">
  <section class="info-hero">
    <p class="eyebrow center"><i></i>ABOUT<i></i></p>
    <h1>교회 초대의 시작부터 운영까지<br>한 번에 담은 플랫폼</h1>
    <p>이 서비스는 홈페이지가 없어도 초대장 중심으로 행사를 운영할 수 있도록 만들었습니다.</p>
  </section>
  <section class="info-wrap">
    <article>
      <h2>우리가 만드는 방식</h2>
      <p>초대장만으로도 초청-공유-신청-확인까지 이어지는 흐름이 구성되도록 최소 기능을 우선 제공하고, 운영권한은 교회 단위로 분리합니다.</p>
    </article>
    <article>
      <h2>핵심 포인트</h2>
      <ul>
        <li>전도지·초대장 전용 화면과 신청 폼</li>
        <li>모바일 공유에 최적화된 한 장 구성</li>
        <li>관리자 대시보드의 신청자·통계·미디어 관리</li>
      </ul>
    </article>
    <article>
      <h2>초기 제공 범위</h2>
      <p>초대장 전용 MVP에서 시작해, 홈페이지 기능은 단계적으로 확장합니다. 데이터 격리, 트래픽/저장공간, 백업, 구독 한도는 초기부터 운영 규칙으로 반영합니다.</p>
    </article>
  </section>
</main>
<footer><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><p>교회와 이웃의 마음을 잇는 기독교 모바일 초대장</p><nav><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a><a href="/login">관리자 로그인</a></nav><small>© <?= View::e($now) ?> Church Invitation. All rights reserved.</small></footer>
<script>const menu=document.querySelector('.menu-toggle'),nav=document.querySelector('#main-nav');menu?.addEventListener('click',()=>{const open=menu.getAttribute('aria-expanded')==='true';menu.setAttribute('aria-expanded',String(!open));nav?.classList.toggle('open',!open)});nav?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{menu?.setAttribute('aria-expanded','false');nav?.classList.remove('open')}));</script>
