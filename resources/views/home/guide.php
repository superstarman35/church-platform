<?php
use App\Core\View;

$dashboardUrl = isset($dashboardUrl) && is_string($dashboardUrl) ? $dashboardUrl : null;
$loginUrl = $dashboardUrl ?? '/login';
$now = date('Y');
?>
<header class="site-header"><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><button class="menu-toggle" aria-expanded="false" aria-controls="main-nav">메뉴</button><nav id="main-nav"><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a class="active" href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a></nav><a class="login-link" href="<?= View::e($loginUrl) ?>"><?= $dashboardUrl ? '관리자 화면' : '로그인' ?></a></header>
<main class="info-page">
  <section class="info-hero">
    <p class="eyebrow center"><i></i>GETTING STARTED GUIDE<i></i></p>
    <h1>처음 시작하기 가이드</h1>
    <p>처음 오시는 분도 4단계로 초대장을 만들고 배포할 수 있습니다.</p>
  </section>
  <section class="info-wrap">
    <article>
      <h2>Step 1. 관리자 접속</h2>
      <p>회원 가입 후 교회 정보를 등록하고, 초대장 용도로 운영 모드를 확인합니다.</p>
    </article>
    <article>
      <h2>Step 2. 초대장 기본 정보 입력</h2>
      <p>행사명, 일시, 장소, 안내문, 지도 링크를 등록하고 모바일 표시가 자연스러운지 확인합니다.</p>
    </article>
    <article>
      <h2>Step 3. 이미지·신청 설정</h2>
      <p>대표 이미지와 갤러리를 추가하고, 필요하면 맞춤 질문을 넣어 신청자를 분류합니다.</p>
    </article>
    <article>
      <h2>Step 4. 공유와 운영</h2>
      <p>링크·QR로 공개하고, 신청 목록과 조회/공유 통계를 통해 초청 반응을 확인합니다.</p>
    </article>
    <article>
      <h2>자주 쓰는 메뉴</h2>
      <ul>
        <li>초대장 관리: 생성, 복제, 게시, 종료</li>
        <li>행사 신청: 상태 관리, 출석 확인</li>
        <li>데이터 확인: 트래픽, 저장공간, 통계 보관</li>
      </ul>
    </article>
  </section>
</main>
<footer><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><p>교회와 이웃의 마음을 잇는 기독교 모바일 초대장</p><nav><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a><a href="/login">관리자 로그인</a></nav><small>© <?= View::e($now) ?> Church Invitation. All rights reserved.</small></footer>
<script>const menu=document.querySelector('.menu-toggle'),nav=document.querySelector('#main-nav');menu?.addEventListener('click',()=>{const open=menu.getAttribute('aria-expanded')==='true';menu.setAttribute('aria-expanded',String(!open));nav?.classList.toggle('open',!open)});nav?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{menu?.setAttribute('aria-expanded','false');nav?.classList.remove('open')}));</script>
