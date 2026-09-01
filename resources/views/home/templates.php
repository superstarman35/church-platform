<?php
use App\Core\View;
$dashboardUrl=isset($dashboardUrl)&&is_string($dashboardUrl)?$dashboardUrl:null;
$startUrl=$dashboardUrl??'/login';
$categories=['all'=>'전체','evangelism'=>'새생명·지역초청','season'=>'절기예배','worship'=>'찬양·특별예배','retreat'=>'수련회·교육','service'=>'선교·봉사'];
$templates=[
 ['new-life','새생명축제','당신을 위한 따뜻한 초대','evangelism','portrait','forest','/assets/images/home/community-preparation.webp','처음 교회를 찾는 이웃에게 편안하고 따뜻한 인상을 전하는 초대장'],
 ['easter-light','부활절 감사예배','다시, 생명의 기쁨으로','season','portrait','ivory','/assets/images/home/invitation-hero.webp','부활의 소망을 밝은 빛과 여백으로 담은 절기예배 디자인'],
 ['worship-night','청년 찬양집회','우리의 노래가 예배가 되는 밤','worship','portrait','navy','/assets/images/home/youth-welcome.webp','청년과 다음 세대 집회에 어울리는 깊고 현대적인 디자인'],
 ['summer-retreat','여름 수련회','믿음 안에서 함께 자라요','retreat','landscape','terracotta','/assets/images/home/youth-welcome.webp','일정과 장소, 준비물을 시원하게 전달하는 수련회 초대장'],
 ['christmas-joy','성탄절 초청예배','기쁨의 소식을 함께 나눠요','season','square','burgundy','/assets/images/home/invitation-hero.webp','성탄의 기쁨과 초청 메시지를 차분하게 전하는 디자인'],
 ['community-day','지역 이웃초청','우리 동네, 함께 웃는 하루','evangelism','landscape','sunny','/assets/images/home/community-preparation.webp','가족과 이웃이 편안하게 참여할 수 있는 밝은 행사 디자인'],
 ['mission-day','선교·봉사 주일','사랑을 전하고 함께 섬겨요','service','square','olive','/assets/images/home/community-preparation.webp','선교와 봉사의 의미, 신청 정보를 균형 있게 담는 초대장'],
 ['revival','부흥회·특별예배','은혜를 사모하며 함께 예배해요','worship','portrait','blue','/assets/images/home/invitation-hero.webp','말씀과 예배에 집중하도록 절제된 구성으로 만든 디자인'],
];
?>
<header class="site-header detail-header"><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-nav">메뉴</button><nav id="main-nav"><a class="active" href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/#steps">이용 방법</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a></nav><a class="login-link" href="<?=View::e($startUrl)?>"><?=$dashboardUrl?'관리자 화면':'로그인'?></a></header>
<main class="templates-page">
<section class="catalog-hero">
 <div><p class="eyebrow"><i></i>CHRISTIAN INVITATION COLLECTION</p><h1>행사의 마음과 목적에 맞는<br><em>초대장 디자인</em>을 찾아보세요.</h1><p>교회의 소중한 만남이 잘 전해지도록 행사별 메시지와 분위기에 맞춘 디자인을 준비했습니다.</p></div>
 <aside><strong><?=count($templates)?></strong><span>현재 제공 디자인</span><small>세로형 · 정사각형 · 가로형</small></aside>
</section>

<section class="catalog-section" aria-labelledby="catalog-title">
 <div class="catalog-toolbar">
  <div><p class="toolbar-label">행사 유형</p><div class="filter-chips" role="group" aria-label="행사 유형 필터"><?php foreach($categories as $key=>$label):?><button type="button" class="<?=$key==='all'?'active':''?>" data-category="<?=View::e($key)?>"><?=View::e($label)?></button><?php endforeach?></div></div>
  <label class="orientation-filter">화면 비율<select id="orientation-filter"><option value="all">전체 비율</option><option value="portrait">세로형</option><option value="square">정사각형</option><option value="landscape">가로형</option></select></label>
 </div>
 <div class="catalog-summary"><h2 id="catalog-title">전체 디자인</h2><p><strong id="result-count"><?=count($templates)?></strong>개의 디자인</p></div>
 <div class="template-catalog" id="template-catalog">
 <?php foreach($templates as [$code,$type,$title,$category,$orientation,$tone,$image,$description]):?>
  <article class="catalog-card" data-category="<?=View::e($category)?>" data-orientation="<?=View::e($orientation)?>" data-code="<?=View::e($code)?>">
   <button class="card-preview" type="button" data-preview data-title="<?=View::e($title)?>" data-type="<?=View::e($type)?>" data-tone="<?=View::e($tone)?>" data-image="<?=View::e($image)?>" aria-label="<?=View::e($type)?> 초대장 미리보기">
    <span class="orientation-badge"><?=$orientation==='portrait'?'세로형':($orientation==='square'?'정사각형':'가로형')?></span>
    <span class="invitation-frame <?=View::e($orientation)?> tone-<?=View::e($tone)?>" style="--cover-image:url('<?=View::e($image)?>')"><small>YOU ARE INVITED</small><i>✦</i><span><?=View::e($type)?></span><strong><?=View::e($title)?></strong><em>미리보기</em></span>
   </button>
   <div class="card-info"><div><p><?=View::e($type)?></p><h3><?=View::e($title)?></h3></div><button type="button" data-preview data-title="<?=View::e($title)?>" data-type="<?=View::e($type)?>" data-tone="<?=View::e($tone)?>" data-image="<?=View::e($image)?>">크게 보기</button></div>
   <p class="card-description"><?=View::e($description)?></p>
   <div class="card-meta"><span class="swatch tone-<?=View::e($tone)?>"></span><span><?=$orientation==='portrait'?'1080 × 1440':($orientation==='square'?'1080 × 1080':'1200 × 675')?></span><a href="<?=View::e($startUrl)?>">이 디자인으로 시작</a></div>
  </article>
 <?php endforeach?>
 </div>
 <div class="catalog-empty" id="catalog-empty" hidden><span>✦</span><h3>조건에 맞는 디자인이 없습니다.</h3><p>다른 행사 유형이나 화면 비율을 선택해 주세요.</p></div>
</section>

<section class="template-guide"><div><p class="eyebrow"><i></i>CHOOSING GUIDE</p><h2>어떤 디자인을<br>선택하면 좋을까요?</h2></div><div class="guide-cards"><article><span>01</span><h3>공유 방식부터 생각하세요.</h3><p>문자와 메신저 중심이면 세로형, SNS 게시물은 정사각형, 화면·현수막 연결은 가로형이 잘 맞습니다.</p></article><article><span>02</span><h3>행사의 첫인상을 정하세요.</h3><p>초청 행사는 따뜻한 사진, 절기예배는 상징적인 빛, 청년 행사는 선명한 색상을 추천합니다.</p></article><article><span>03</span><h3>정보의 양을 확인하세요.</h3><p>일정과 신청 질문이 많다면 여백이 충분한 디자인을 선택하는 것이 읽기 편합니다.</p></article></div></section>

<section class="catalog-cta"><span>✦</span><div><p>마음에 드는 디자인을 찾으셨나요?</p><h2>우리 교회의 이야기로<br>초대장을 완성해 보세요.</h2></div><a class="btn dark" href="<?=View::e($startUrl)?>"><?=$dashboardUrl?'초대장 만들러 가기':'관리자 로그인'?></a></section>
</main>
<footer><a class="brand" href="/"><b>✦</b><span>Church Invitation</span></a><p>교회와 이웃의 마음을 잇는 기독교 모바일 초대장</p><nav><a href="/templates">초대장 예시</a><a href="/features">주요 기능</a><a href="/pricing">요금 안내</a><a href="/about">서비스 소개</a><a href="/faq">FAQ</a><a href="/guide">시작 가이드</a><a href="/terms">이용약관</a><a href="/privacy">개인정보처리방침</a><a href="/support">고객지원</a><a href="/contact">문의하기</a><a href="/login">관리자 로그인</a></nav><small>© <?=date('Y')?> Church Invitation. All rights reserved.</small></footer>

<dialog class="preview-dialog" id="preview-dialog" aria-labelledby="preview-title"><button class="dialog-close" type="button" aria-label="미리보기 닫기">×</button><div class="dialog-grid"><div class="preview-stage"><div class="preview-phone"><div class="preview-screen"><small>YOU ARE INVITED</small><i>✦</i><span id="preview-type"></span><strong id="preview-title"></strong><em>함께하는 자리에 초대합니다</em></div></div></div><div class="preview-copy"><p>모바일 미리보기</p><h2 id="preview-heading"></h2><ul><li>대표 이미지와 초대 문구</li><li>행사 일시·장소·지도</li><li>사진·영상과 참석 신청</li></ul><a class="btn primary" href="<?=View::e($startUrl)?>">이 디자인으로 시작하기</a><button class="text-close" type="button">다른 디자인 더 보기</button></div></div></dialog>
<script>
const menu=document.querySelector('.menu-toggle'),nav=document.querySelector('#main-nav');menu?.addEventListener('click',()=>{const open=menu.getAttribute('aria-expanded')==='true';menu.setAttribute('aria-expanded',String(!open));nav?.classList.toggle('open',!open)});
const chips=[...document.querySelectorAll('[data-category]')].filter(el=>el.tagName==='BUTTON'),select=document.querySelector('#orientation-filter'),cards=[...document.querySelectorAll('.catalog-card')],count=document.querySelector('#result-count'),empty=document.querySelector('#catalog-empty');let category='all';
function filterCards(){let visible=0;cards.forEach(card=>{const show=(category==='all'||card.dataset.category===category)&&(select.value==='all'||card.dataset.orientation===select.value);card.hidden=!show;if(show)visible++});count.textContent=String(visible);empty.hidden=visible!==0}
chips.forEach(chip=>chip.addEventListener('click',()=>{chips.forEach(c=>c.classList.remove('active'));chip.classList.add('active');category=chip.dataset.category;filterCards()}));select?.addEventListener('change',filterCards);
const dialog=document.querySelector('#preview-dialog'),screen=dialog?.querySelector('.preview-screen'),title=dialog?.querySelector('#preview-title'),heading=dialog?.querySelector('#preview-heading'),type=dialog?.querySelector('#preview-type');
document.querySelectorAll('[data-preview]').forEach(button=>button.addEventListener('click',()=>{screen.className='preview-screen tone-'+button.dataset.tone;screen.style.setProperty('--preview-image',"url('"+button.dataset.image+"')");title.textContent=button.dataset.title;heading.textContent=button.dataset.title;type.textContent=button.dataset.type;dialog.showModal()}));
dialog?.querySelectorAll('.dialog-close,.text-close').forEach(button=>button.addEventListener('click',()=>dialog.close()));dialog?.addEventListener('click',event=>{if(event.target===dialog)dialog.close()});
</script>
