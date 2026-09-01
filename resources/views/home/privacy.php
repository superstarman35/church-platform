<?php
use App\Core\View;

$dashboardUrl = isset($dashboardUrl) && is_string($dashboardUrl) ? $dashboardUrl : null;
$loginUrl = $dashboardUrl ?? '/login';
$now = date('Y');
?>
<header class="site-header"><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><button class="menu-toggle" aria-expanded="false" aria-controls="main-nav">메뉴</button><nav id="main-nav"><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a></nav><a class="login-link" href="<?= View::e($loginUrl) ?>"><?= $dashboardUrl ? '관리자 화면' : '로그인' ?></a></header>
<main class="info-page">
  <section class="info-hero">
    <p class="eyebrow center"><i></i>PRIVACY POLICY<i></i></p>
    <h1>개인정보처리방침</h1>
    <p>신청자와 관리자 정보는 교회별 범위와 역할 기준으로 안전하게 관리됩니다.</p>
  </section>

  <section class="info-wrap">
    <article>
      <h2>수집 항목</h2>
      <ul>
        <li>단체 기본정보: 교회명, 담당자 정보, 연락 채널</li>
        <li>초대장 이용정보: 행사명, 일정, 장소, 지도 링크, 신청 질문</li>
        <li>신청자 정보: 이름, 연락처, 참석 여부, 추가 메모</li>
      </ul>
    </article>
    <article>
      <h2>수집 목적</h2>
      <ul>
        <li>초대장 공개·공유 기능 제공 및 신청 접수 운영</li>
        <li>초대장 통계, 조회·공유·신청 흐름 분석</li>
        <li>서비스 이용자 문의 대응 및 보안 점검</li>
      </ul>
    </article>
    <article>
      <h2>보관 및 파기</h2>
      <p>요청된 법정 보존기준과 교회·상품 정책에 따라 보관 기간을 설정합니다. 보관 기간이 지나면 삭제 또는 비식별화 처리합니다.</p>
    </article>
    <article>
      <h2>제3자 제공</h2>
      <p>필요한 결제·백업 운영·운영자 감사 기능 이외에는 외부 제3자에게 무단 제공하지 않습니다.</p>
    </article>
    <article>
      <h2>보안 조치</h2>
      <ul>
        <li>교회별 데이터 격리(`church_id`) 기반 조회 제한</li>
        <li>관리자 권한 로그와 중요 작업 감사 기록</li>
        <li>업로드 파일 검사, 백신·확장자·용량 제한</li>
      </ul>
    </article>
  </section>
</main>
<footer><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><p>교회와 이웃의 마음을 잇는 기독교 모바일 초대장</p><nav><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a><a href="/login">관리자 로그인</a></nav><small>© <?= View::e($now) ?> Church Invitation. All rights reserved.</small></footer>
<script>const menu=document.querySelector('.menu-toggle'),nav=document.querySelector('#main-nav');menu?.addEventListener('click',()=>{const open=menu.getAttribute('aria-expanded')==='true';menu.setAttribute('aria-expanded',String(!open));nav?.classList.toggle('open',!open)});nav?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{menu?.setAttribute('aria-expanded','false');nav?.classList.remove('open')}));</script>
