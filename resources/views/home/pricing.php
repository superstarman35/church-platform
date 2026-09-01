<?php
use App\Core\View;

$dashboardUrl = isset($dashboardUrl) && is_string($dashboardUrl) ? $dashboardUrl : null;
$startUrl = $dashboardUrl ?? '/login';
$plans = [
    ['trial', '30일 무료체험', '체험형', '0', '처음 초대장을 만들어 보는 교회와 단체', ['월 트래픽 2GB', '저장공간 200MB', '활성 초대장 2개', '월 초대장 생성 2개', '관리자 1명', '사진 초대장당 5장', '신청자 초대장당 50명', '통계 보관 30일']],
    ['basic', '초대장 기본형', '기본형', '4,900', '소규모 행사를 꾸준히 운영하는 교회와 단체', ['월 트래픽 10GB', '저장공간 500MB', '활성 초대장 5개', '월 초대장 생성 10개', '관리자 2명', '사진 초대장당 15장', '신청자 월 500명', '통계 보관 6개월']],
    ['growth', '초대장 성장형', '성장형', '9,900', '여러 부서와 행사를 함께 운영하는 교회', ['월 트래픽 30GB', '저장공간 1.5GB', '활성 초대장 20개', '월 초대장 생성 50개', '관리자 5명', '사진 초대장당 30장', '신청자 월 3,000명', '통계 보관 12개월']],
];
?>
<header class="site-header detail-header"><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-nav">메뉴</button><nav id="main-nav"><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/#steps">이용 방법</a><a class="active" href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a></nav><a class="login-link" href="<?= View::e($startUrl) ?>"><?= $dashboardUrl ? '관리자 화면' : '로그인' ?></a></header>
<main class="pricing-page">
<section class="pricing-hero"><p class="eyebrow center"><i></i>SIMPLE PRICING<i></i></p><h1>필요한 만큼 선택하고<br><em>부담 없이</em> 시작하세요.</h1><p>홈페이지 없이 모바일 초대장만 사용할 수 있는 전용 상품입니다.<br>모든 상품에서 YouTube 연결과 모바일 초대장을 이용할 수 있습니다.</p></section>
<section class="pricing-cards" aria-label="초대장 전용 상품 비교">
<?php foreach ($plans as [$code, $label, $name, $price, $description, $limits]): ?>
<article class="pricing-card <?= $code === 'basic' ? 'recommended' : '' ?>">
 <?php if ($code === 'basic'): ?><strong class="recommend-label">추천</strong><?php endif; ?>
 <p><?= View::e($label) ?></p><h2><?= View::e($name) ?></h2><p class="plan-description"><?= View::e($description) ?></p>
 <div class="plan-price"><b><?= View::e($price) ?></b><span>원<?= $code === 'trial' ? '' : ' / 월' ?></span></div>
 <a href="<?= View::e($startUrl) ?>"><?= $dashboardUrl ? '관리자 화면으로' : ($code === 'trial' ? '무료체험 시작하기' : '로그인하고 시작하기') ?></a>
 <ul><?php foreach ($limits as $limit): ?><li><?= View::e($limit) ?></li><?php endforeach; ?></ul>
</article>
<?php endforeach; ?>
</section>
<p class="price-note">가격의 부가세 포함 여부와 연간 결제 조건은 판매 전에 확정하여 안내합니다.</p>
<section class="included"><div><p class="eyebrow"><i></i>EVERY PLAN</p><h2>어떤 상품을 선택해도<br>초대의 기본은 함께합니다.</h2></div><div class="included-grid"><article><span>01</span><h3>모바일 초대장</h3><p>행사 정보, 사진, 지도와 신청을 하나의 주소로 전합니다.</p></article><article><span>02</span><h3>안전한 교회별 관리</h3><p>교회별 데이터와 관리자 권한을 서버에서 분리해 확인합니다.</p></article><article><span>03</span><h3>YouTube 연결</h3><p>영상 파일을 직접 올리지 않고 YouTube 링크로 가볍게 연결합니다.</p></article></div></section>
<section class="usage-policy"><div><p class="eyebrow"><i></i>USAGE GUIDE</p><h2>사용량이 늘어나도<br>미리 확인할 수 있습니다.</h2><p>관리자 화면에서 트래픽과 저장공간을 확인하고, 한도에 가까워지면 단계별로 안내합니다.</p></div><ol><li><b>70%</b><span>관리자 안내</span></li><li><b>85%</b><span>경고와 상향 안내</span></li><li><b>100%</b><span>신규 대용량 업로드 우선 제한</span></li></ol></section>
<section class="pricing-cta"><span>✦</span><h2>30일 동안 직접 만들고<br>교회의 첫 초대를 전해보세요.</h2><p>카드 등록 없이 무료체험을 시작할 수 있습니다.</p><a class="btn dark" href="<?= View::e($startUrl) ?>"><?= $dashboardUrl ? '초대장 관리하기' : '무료체험 시작하기' ?></a></section>
</main>
<footer><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><p>교회와 이웃의 마음을 잇는 기독교 모바일 초대장</p><nav><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a><a href="/login">관리자 로그인</a></nav><small>© <?= date('Y') ?> Church Invitation. All rights reserved.</small></footer>
<script>const menu=document.querySelector('.menu-toggle'),nav=document.querySelector('#main-nav');menu?.addEventListener('click',()=>{const open=menu.getAttribute('aria-expanded')==='true';menu.setAttribute('aria-expanded',String(!open));nav?.classList.toggle('open',!open)});nav?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{menu?.setAttribute('aria-expanded','false');nav?.classList.remove('open')}));</script>
