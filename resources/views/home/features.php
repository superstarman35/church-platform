<?php
use App\Core\View;
$dashboardUrl=isset($dashboardUrl)&&is_string($dashboardUrl)?$dashboardUrl:null;
$startUrl=$dashboardUrl??'/login';
$features=[
 ['01','design','DESIGN · TEMPLATE','행사마다 어울리는 디자인','새생명축제와 절기예배, 찬양집회와 수련회까지 행사 성격에 맞는 구성을 선택합니다. 사진과 교회명, 초대 문구를 바꾸면 우리 교회만의 초대장이 완성됩니다.',['세로형·정사각형·가로형 화면','대표 이미지와 사진 갤러리','교회 행사 중심의 쉬운 편집']],
 ['02','info','EVENT · INFORMATION','일정과 장소를 한 화면에','행사 일시와 장소, 문의처를 한곳에 정리해 처음 방문하는 분도 필요한 정보를 쉽게 찾을 수 있습니다. 안내가 바뀌면 링크를 다시 보내지 않고 내용만 수정합니다.',['일시·장소·문의처 안내','지도와 길찾기 연결','수정 내용 즉시 반영']],
 ['03','media','PHOTO · YOUTUBE','사진과 영상으로 생생하게','교회의 따뜻한 분위기는 사진으로, 행사 소개와 찬양은 YouTube 영상으로 전합니다. 이미지는 모바일에 맞게 가볍게 제공하고 영상 파일은 서버에 올리지 않습니다.',['WebP 기반 이미지 최적화','사진 갤러리와 대표 이미지','YouTube 링크 연결']],
 ['04','share','SHARE · QR','링크와 QR로 어디서든 공유','초대장 주소 하나를 문자와 메신저로 나누고, QR 코드는 주보와 포스터, 현수막에 활용합니다. 온라인과 교회 현장의 초대가 자연스럽게 이어집니다.',['모바일 링크 공유','QR 코드 활용','공개·비밀번호·비공개 설정']],
 ['05','apply','APPLICATION · GUEST','모바일에서 바로 참석 신청','방문자는 별도 앱 없이 초대장에서 참석 의사를 전합니다. 관리자는 신청자와 확인 상태를 한곳에서 관리하고 필요한 경우 CSV로 내보낼 수 있습니다.',['맞춤 질문과 개인정보 동의','신청자 상태·출석 관리','권한이 있는 관리자만 열람']],
 ['06','manage','MANAGE · MULTIPLE','여러 초대장도 차분하게 관리','한 교회가 여러 행사를 동시에 운영해도 목록에서 초대장을 복제하고 게시하거나 종료할 수 있습니다. 공개 기간과 구독 한도도 관리자 화면에서 함께 확인합니다.',['생성·복제·미리보기','게시·종료·만료 관리','교회별 권한과 데이터 격리']],
 ['07','insight','VISIT · INSIGHT','조회부터 신청까지 흐름 확인','초대장이 얼마나 열리고 공유되었는지, 실제 신청으로 얼마나 이어졌는지 확인합니다. 단순 숫자를 넘어 다음 초청 행사를 준비하는 근거로 활용합니다.',['조회·공유·신청 통계','주간·월간 흐름 확인','요금제별 통계 보관']],
];
?>
<header class="site-header detail-header"><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-nav">메뉴</button><nav id="main-nav"><a href="/templates">초대장 예시</a><a class="active" href="/features">주요 기능</a><a href="/#steps">이용 방법</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a></nav><a class="login-link" href="<?=View::e($startUrl)?>"><?=$dashboardUrl?'관리자 화면':'로그인'?></a></header>
<main class="features-page">
<section class="feature-hero"><p class="eyebrow center"><i></i>BUILT FOR CHURCH<i></i></p><h1>교회의 초대에 필요한 기능을<br><em>하나의 흐름</em>으로 담았습니다.</h1><p>만들기부터 공유, 신청자 확인까지. 교회 담당자는 쉽게 운영하고<br>초대받은 분은 모바일에서 편안하게 참여합니다.</p><nav class="feature-jump" aria-label="주요 기능 바로가기"><?php foreach($features as [$number,$code,$label,$title]):?><a href="#feature-<?=View::e($code)?>"><span><?=View::e($number)?></span><?=View::e($title)?></a><?php endforeach?></nav></section>
<section class="feature-flow" aria-label="초대장 주요 기능">
<?php foreach($features as $index=>[$number,$code,$label,$title,$description,$points]):?>
<article class="feature-detail <?=$index%2===1?'reverse':''?>" id="feature-<?=View::e($code)?>">
 <div class="feature-copy"><p class="feature-label"><?=View::e($label)?></p><span class="feature-number"><?=View::e($number)?></span><h2><?=View::e($title)?></h2><p><?=View::e($description)?></p><ul><?php foreach($points as $point):?><li><?=View::e($point)?></li><?php endforeach?></ul></div>
 <div class="feature-visual visual-<?=View::e($code)?>" aria-hidden="true">
 <?php if($code==='design'):?><div class="invite-stack"><div><small>NEW LIFE</small><strong>당신을 위한<br>따뜻한 초대</strong></div><div><small>WORSHIP</small><strong>함께<br>예배해요</strong></div><div><small>EASTER</small><strong>생명의<br>기쁨으로</strong></div></div>
 <?php elseif($code==='info'):?><div class="event-sheet"><span>2026. 09. 20 SUN</span><h3>이웃초청 감사예배</h3><dl><div><dt>일시</dt><dd>오전 11시</dd></div><div><dt>장소</dt><dd>사랑교회 본당</dd></div><div><dt>문의</dt><dd>교회 사무실</dd></div></dl><button type="button">지도에서 보기</button></div>
 <?php elseif($code==='media'):?><div class="media-grid"><img src="/assets/images/home/community-preparation.webp" alt=""><img src="/assets/images/home/youth-welcome.webp" alt=""><div class="play">▶<small>YouTube</small></div></div>
 <?php elseif($code==='share'):?><div class="share-phone"><div class="qr-grid"><?php for($i=0;$i<64;$i++):?><i class="<?=($i%3===0||$i%7===0)?'on':''?>"></i><?php endfor?></div><strong>초대장 공유하기</strong><p>링크가 복사되었습니다</p><div><span>문자</span><span>메신저</span><span>QR</span></div></div>
 <?php elseif($code==='apply'):?><div class="apply-card"><span>참석 신청</span><label>이름<i></i></label><label>연락처<i></i></label><label>함께 오는 인원<i></i></label><p><b>✓</b> 개인정보 수집에 동의합니다.</p><button type="button">신청 완료</button></div>
 <?php elseif($code==='manage'):?><div class="manage-list"><header><strong>초대장 관리</strong><span>+ 새 초대장</span></header><?php foreach(['새생명축제','청년 찬양집회','성탄절 초청예배'] as $i=>$item):?><div><i class="tone-<?=$i?>"></i><p><strong><?=$item?></strong><small><?=$i===2?'작성 중':'게시 중'?></small></p><button type="button">관리</button></div><?php endforeach?></div>
 <?php else:?><div class="insight-card"><header><p>이번 달 초대 현황</p><strong>1,284 <small>조회</small></strong></header><div class="chart"><?php foreach([35,58,46,75,62,88,72] as $height):?><i style="--h:<?=$height?>%"></i><?php endforeach?></div><footer><span><b>214</b>공유</span><span><b>86</b>신청</span><span><b>6.7%</b>전환</span></footer></div><?php endif?>
 </div>
</article>
<?php endforeach?>
</section>
<section class="trust-section"><div><p class="eyebrow"><i></i>SAFE & SIMPLE</p><h2>교회도 방문자도<br>안심할 수 있도록</h2></div><div class="trust-grid"><article><span>01</span><h3>교회별 데이터 격리</h3><p>다른 교회의 초대장과 신청자 정보에 접근할 수 없도록 서버에서 교회를 확인합니다.</p></article><article><span>02</span><h3>관리자 권한 분리</h3><p>신청자 개인정보는 허용된 역할만 열람하고 중요한 작업은 기록으로 남깁니다.</p></article><article><span>03</span><h3>가벼운 모바일 화면</h3><p>이미지를 화면 크기에 맞게 제공하고 필요한 콘텐츠는 지연 로딩합니다.</p></article></div></section>
<section class="feature-cta"><span>✦</span><div><p>우리 교회의 첫 초대장을 준비해 보세요.</p><h2>복잡한 도구 없이,<br>따뜻한 초대를 시작합니다.</h2></div><div><a class="btn dark" href="<?=View::e($startUrl)?>"><?=$dashboardUrl?'초대장 관리하기':'관리자 로그인'?></a><a href="/templates">디자인 먼저 둘러보기 →</a></div></section>
</main>
<footer><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><p>교회와 이웃의 마음을 잇는 기독교 모바일 초대장</p><nav><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a><a href="/login">관리자 로그인</a></nav><small>© <?=date('Y')?> Church Invitation. All rights reserved.</small></footer>
<script>const menu=document.querySelector('.menu-toggle'),nav=document.querySelector('#main-nav');menu?.addEventListener('click',()=>{const open=menu.getAttribute('aria-expanded')==='true';menu.setAttribute('aria-expanded',String(!open));nav?.classList.toggle('open',!open)});nav?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{menu?.setAttribute('aria-expanded','false');nav?.classList.remove('open')}));</script>
