<?php
use App\Core\Csrf;
use App\Core\View;

$youtubeId = '';
if (!empty($item['youtube_url'])) {
    $host = parse_url($item['youtube_url'], PHP_URL_HOST);
    $videoPath = trim((string) parse_url($item['youtube_url'], PHP_URL_PATH), '/');
    $youtubeId = $host === 'youtu.be' ? $videoPath : (string) (parse_url($item['youtube_url'], PHP_URL_QUERY) ?? '');
    if (str_contains($youtubeId, 'v=')) { parse_str($youtubeId, $query); $youtubeId = (string) ($query['v'] ?? ''); }
}
$eventTypes = [
    'worship'=>'예배', 'evangelism'=>'전도·초청 행사', 'conference'=>'집회·세미나', 'education'=>'교육 행사',
    'volunteer'=>'선교·봉사', 'community'=>'교제·공동체', 'other'=>'교회 행사',
];
$eventLabel = $eventTypes[$item['event_type'] ?? ''] ?? '교회 행사';
$eventTime = !empty($item['event_at']) ? strtotime((string) $item['event_at']) : false;
$phoneHref = preg_replace('/[^0-9+]/', '', (string) ($item['contact_phone'] ?? ''));
?>
<main class="invite <?= View::e($item['template_code']) ?> color-<?= View::e($item['color_preset'] ?? 'forest') ?> font-<?= View::e($item['font_preset'] ?? 'sans') ?> button-<?= View::e($item['button_preset'] ?? 'rounded') ?>">
    <header class="invite-hero<?= $item['hero_uuid'] ? ' has-image' : '' ?>">
        <?php if ($item['hero_uuid']): ?><img class="invite-hero-image" src="/media/<?= View::e($item['hero_uuid']) ?>" alt="<?= View::e($item['title']) ?> 대표 이미지" loading="eager"><?php endif; ?>
        <div class="hero-shade"></div>
        <div class="hero-topline"><span class="cross-mark" aria-hidden="true">✦</span><span><?= View::e($item['church_name']) ?></span></div>
        <div class="hero-copy">
            <p class="invite-kicker">YOU ARE INVITED · <?= View::e($eventLabel) ?></p>
            <h1><?= View::e($item['title']) ?></h1>
            <?php if ($item['summary']): ?><p class="hero-lead"><?= View::e($item['summary']) ?></p><?php endif; ?>
        </div>
        <a class="scroll-cue" href="#invitation-message" aria-label="초대 내용 보기"><span>SCROLL</span><i></i></a>
    </header>

    <section id="invitation-message" class="invite-section opening-message reveal">
        <p class="section-symbol" aria-hidden="true">✝</p>
        <p class="section-kicker">A JOYFUL INVITATION</p>
        <h2>함께 예배하고<br>함께 기뻐해 주세요</h2>
        <?php if ($item['body']): ?><div class="invitation-body"><?= nl2br(View::e($item['body'])) ?></div><?php endif; ?>
        <p class="church-signature"><?= View::e($item['church_name']) ?></p>
    </section>

    <?php if ($eventTime || $item['venue_name']): ?>
    <section class="invite-section event-section reveal">
        <div class="section-heading"><p class="section-kicker">WORSHIP &amp; EVENT</p><h2>행사 안내</h2></div>
        <div class="event-card">
            <?php if ($eventTime): ?><div class="date-block"><span><?= date('Y', $eventTime) ?></span><strong><?= date('m.d', $eventTime) ?></strong><em><?= View::e(['일','월','화','수','목','금','토'][(int)date('w',$eventTime)]) ?>요일 · <?= date('H:i', $eventTime) ?></em></div><?php endif; ?>
            <?php if ($item['venue_name']): ?><div class="venue-block"><span>PLACE</span><strong><?= View::e($item['venue_name']) ?></strong><?php if($item['venue_address']): ?><p><?= View::e($item['venue_address']) ?></p><?php endif; ?></div><?php endif; ?>
        </div>
        <div class="event-actions">
            <?php if($item['map_url']): ?><a class="outline-action" href="<?= View::e($item['map_url']) ?>" target="_blank" rel="noopener"><span aria-hidden="true">⌖</span> 길찾기</a><?php endif; ?>
            <?php if($phoneHref !== ''): ?><a class="outline-action" href="tel:<?= View::e($phoneHref) ?>"><span aria-hidden="true">☎</span> 문의하기</a><?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($gallery)): ?>
    <section class="gallery-section reveal"><div class="section-heading"><p class="section-kicker">OUR COMMUNITY</p><h2>함께하는 순간</h2><p>우리 공동체의 기쁨과 은혜의 순간을 나눕니다.</p></div><div class="gallery-grid"><?php foreach ($gallery as $index => $media): ?><figure class="gallery-item gallery-item-<?= $index % 5 ?>"><img src="/media/<?= View::e($media['uuid']) ?>" width="<?= (int)$media['width'] ?>" height="<?= (int)$media['height'] ?>" alt="<?= View::e($media['alt_text'] ?: $item['church_name'].' 행사 사진') ?>" loading="lazy"></figure><?php endforeach; ?></div></section>
    <?php endif; ?>

    <?php if ($youtubeId): ?>
    <section class="invite-section video-section reveal"><div class="section-heading"><p class="section-kicker">MESSAGE &amp; PRAISE</p><h2>영상으로 미리 만나요</h2></div><div class="video-frame"><iframe src="https://www.youtube-nocookie.com/embed/<?= View::e($youtubeId) ?>" title="<?= View::e($item['title']) ?> 영상" loading="lazy" allowfullscreen></iframe></div></section>
    <?php endif; ?>

    <section id="apply" class="invite-section apply reveal">
        <div class="section-heading"><p class="section-kicker">RSVP</p><h2>참석 신청</h2><p>함께하실 분의 정보를 남겨 주세요.<br>정성껏 준비하고 기다리겠습니다.</p></div>
        <?php if($success): ?><div class="notice success"><?= View::e($success) ?></div><?php endif; ?>
        <?php if($error): ?><div class="notice error"><?= View::e($error) ?></div><?php endif; ?>
        <form method="post" action="/i/<?= View::e($item['church_slug']) ?>/<?= View::e($item['slug']) ?>/apply"><input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
            <div class="field-row"><label>이름<input name="applicant_name" required maxlength="100" placeholder="성함을 입력해 주세요"></label><label>참석 인원<input type="number" name="attendee_count" value="1" min="1" max="100"></label></div>
            <label>연락처<input name="phone" maxlength="30" inputmode="tel" placeholder="연락 가능한 번호"></label>
            <label>이메일 <small>선택</small><input type="email" name="email" maxlength="190" placeholder="example@email.com"></label>
            <label>남기실 말씀 <small>선택</small><textarea name="message" rows="3" maxlength="1000" placeholder="기도 제목이나 전하실 말씀을 남겨 주세요"></textarea></label>
            <?php foreach($questions as $question): $answerName='answers['.(int)$question['id'].']'; $required=!empty($question['is_required']); $options=json_decode((string)($question['options_json']??'[]'),true); $options=is_array($options)?$options:[]; ?><label><?= View::e($question['label']) ?><?= $required?' *':'' ?><?php if($question['question_type']==='textarea'): ?><textarea name="<?= View::e($answerName) ?>" rows="3" maxlength="1000" <?= $required?'required':'' ?>></textarea><?php elseif($question['question_type']==='select'): ?><select name="<?= View::e($answerName) ?>" <?= $required?'required':'' ?>><option value="">선택해 주세요</option><?php foreach($options as $option): ?><option value="<?= View::e((string)$option) ?>"><?= View::e((string)$option) ?></option><?php endforeach; ?></select><?php elseif($question['question_type']==='checkbox'): ?><span class="check-option"><input type="checkbox" name="<?= View::e($answerName) ?>" value="1" <?= $required?'required':'' ?>> 확인</span><?php else: ?><input type="text" name="<?= View::e($answerName) ?>" maxlength="1000" <?= $required?'required':'' ?>><?php endif; ?></label><?php endforeach; ?>
            <label class="consent"><input type="checkbox" name="privacy_consent" value="1" required><span>신청 접수를 위한 개인정보 수집에 동의합니다.</span></label><button class="submit-application" type="submit">참석 신청 보내기</button>
        </form>
    </section>

    <section class="closing-section reveal"><span class="cross-mark large" aria-hidden="true">✦</span><p>주님의 사랑 안에서<br>기쁜 마음으로 기다리겠습니다.</p><strong><?= View::e($item['church_name']) ?></strong><?php if($item['contact_name'] || $item['contact_phone']): ?><small>문의 <?= View::e($item['contact_name']) ?> <?= View::e($item['contact_phone']) ?></small><?php endif; ?></section>

    <nav class="sticky-actions" aria-label="초대장 바로가기"><?php if($item['map_url']): ?><a href="<?= View::e($item['map_url']) ?>" target="_blank" rel="noopener"><span>⌖</span>길찾기</a><?php endif; ?><a href="#apply"><span>✓</span>참석 신청</a><button type="button" id="share"><span>↗</span><b>공유</b></button></nav>
</main>
<script>
const shareButton=document.getElementById('share');shareButton?.addEventListener('click',async()=>{const token='<?= View::e(Csrf::token()) ?>';fetch(location.pathname+'/share',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'_token='+encodeURIComponent(token)});try{if(navigator.share){await navigator.share({title:document.title,url:location.href});}else{await navigator.clipboard.writeText(location.href);const label=shareButton.querySelector('b');if(label){label.textContent='복사됨';setTimeout(()=>label.textContent='공유',1500);}}}catch(e){}});
if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches){const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting){entry.target.classList.add('is-visible');observer.unobserve(entry.target);}}),{threshold:.12});document.querySelectorAll('.reveal').forEach(el=>observer.observe(el));}else{document.querySelectorAll('.reveal').forEach(el=>el.classList.add('is-visible'));}
</script>