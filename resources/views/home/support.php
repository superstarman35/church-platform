<?php
use App\Core\View;

$dashboardUrl = isset($dashboardUrl) && is_string($dashboardUrl) ? $dashboardUrl : null;
$loginUrl = $dashboardUrl ?? '/login';
$now = date('Y');
?>
<header class="site-header"><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><button class="menu-toggle" aria-expanded="false" aria-controls="main-nav">메뉴</button><nav id="main-nav"><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a class="active" href="/support">고객지원</a><a href="/contact">문의하기</a></nav><a class="login-link" href="<?= View::e($loginUrl) ?>"><?= $dashboardUrl ? '관리자 화면' : '로그인' ?></a></header>
<main class="info-page">
  <section class="info-hero">
    <p class="eyebrow center"><i></i>HELP CENTER<i></i></p>
    <h1>고객지원</h1>
    <p>초대장 설정, 이용권, 신청자 관리에서 막히는 부분을 빠르게 확인할 수 있도록 정리했습니다.</p>
  </section>

  <section class="info-wrap">
    <article>
      <h2>자주 묻는 질문</h2>
      <details>
        <summary>요금제 변경은 어떻게 하나요?</summary>
        <p>관리자 화면의 구독 메뉴에서 변경 요청을 넣으면 운영자가 검토 후 반영합니다.</p>
      </details>
      <details>
        <summary>체험 기간이 끝나도 데이터는 남나요?</summary>
        <p>체험에서 유료 전환 시 기존 데이터는 이전/보존 흐름을 따르며, 전환 실패 시 롤백 경로가 마련됩니다.</p>
      </details>
      <details>
        <summary>초대장 이미지는 어떤 형식이 맞을까요?</summary>
        <p>이미지는 모바일 환경에 맞춘 최적화를 권장하며, 동영상은 YouTube 링크로 연결합니다.</p>
      </details>
      <details>
        <summary>신청자 개인정보는 어디까지 저장되나요?</summary>
        <p>행사 운영에 필요한 범위로 최소한만 저장하며, 보관 정책은 상품권한과 내부 운영 방침을 따릅니다.</p>
      </details>
    </article>
    <article>
      <h2>기술 문의 흐름</h2>
      <ol>
        <li>문제 재현 화면(공유 링크 또는 초대장 주소) 준비</li>
        <li>브라우저 종류/기기/발생 시간 기록</li>
        <li>관리자 화면의 담당자 문의 기능 또는 운영 채널로 전달</li>
      </ol>
    </article>
    <article>
      <h2>연락처</h2>
      <p>운영 시간: 평일 10:00~18:00 (점심 12:30~13:30)</p>
      <p>이메일: support@church-invitation.example</p>
    </article>
  </section>
</main>
<footer><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><p>교회와 이웃의 마음을 잇는 기독교 모바일 초대장</p><nav><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a><a href="/login">관리자 로그인</a></nav><small>© <?= View::e($now) ?> Church Invitation. All rights reserved.</small></footer>
<script>const menu=document.querySelector('.menu-toggle'),nav=document.querySelector('#main-nav');menu?.addEventListener('click',()=>{const open=menu.getAttribute('aria-expanded')==='true';menu.setAttribute('aria-expanded',String(!open));nav?.classList.toggle('open',!open)});nav?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{menu?.setAttribute('aria-expanded','false');nav?.classList.remove('open')}));</script>
