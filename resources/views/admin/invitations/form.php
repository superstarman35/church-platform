<?php use App\Core\Csrf; use App\Core\View; $v=static fn(string $key,string $default='')=>View::e($item[$key]??$default); ?>
<div class="page-header"><div><p class="eyebrow">초대장 편집</p><h1><?= View::e($title) ?></h1><p class="muted">영상은 업로드하지 않고 YouTube 링크만 입력합니다.</p></div><a class="button secondary" href="/admin/invitations">목록</a></div>
<?php if ($error): ?><div class="alert alert-error"><?= View::e($error) ?></div><?php endif; ?><?php if (!empty($success)): ?><div class="alert alert-success"><?= View::e($success) ?></div><?php endif; ?>
<form method="post" action="<?= View::e($action) ?>" class="panel form-grid" enctype="multipart/form-data"><input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
<h2>기본 정보</h2>
<label>제목<input name="title" required maxlength="150" value="<?= $v('title') ?>" placeholder="새가족 초청예배"></label>
<label>공개 주소<input name="slug" required pattern="[a-z0-9][a-z0-9-]{2,79}" value="<?= $v('slug') ?>" placeholder="welcome-2026"><small>영문 소문자·숫자·하이픈만 사용</small></label>
<label>행사 유형<select name="event_type"><?php foreach(['worship'=>'예배','evangelism'=>'전도·초청','conference'=>'집회·세미나','education'=>'교육','volunteer'=>'봉사','community'=>'교제·공동체','other'=>'기타'] as $k=>$label): ?><option value="<?= $k ?>" <?= ($item['event_type']??'worship')===$k?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label>
<label>모바일 디자인<select name="template_code"><?php foreach(['portrait'=>'세로형','square'=>'정사각형','landscape'=>'가로형'] as $k=>$label): ?><option value="<?= $k ?>" <?= ($item['template_code']??'portrait')===$k?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label>
<label class="full">한 줄 소개<input name="summary" maxlength="255" value="<?= $v('summary') ?>" placeholder="당신을 특별한 예배에 초대합니다."></label>
<label class="full">상세 내용<textarea name="body" rows="8" maxlength="10000"><?= $v('body') ?></textarea></label>
<h2>일정과 장소</h2>
<label>행사 일시<input type="datetime-local" name="event_at" value="<?= $v('event_at') ? View::e(str_replace(' ','T',substr((string)($item['event_at']??''),0,16))) : '' ?>"></label>
<label>장소명<input name="venue_name" maxlength="150" value="<?= $v('venue_name') ?>"></label>
<label class="full">주소<input name="venue_address" maxlength="255" value="<?= $v('venue_address') ?>"></label>
<label class="full">지도 링크<input type="url" name="map_url" maxlength="500" value="<?= $v('map_url') ?>" placeholder="https://..."></label>
<h2>미디어와 문의</h2><label class="full">대표 이미지<input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp"><small>JPG·PNG·WebP, 원본 최대 1MB. 업로드 시 최대 1080×1350 WebP로 자동 최적화됩니다.</small></label>
<label class="full">YouTube 링크<input type="url" name="youtube_url" maxlength="500" value="<?= $v('youtube_url') ?>" placeholder="https://youtu.be/..."></label>
<label>문의 담당자<input name="contact_name" maxlength="100" value="<?= $v('contact_name') ?>"></label>
<label>문의 전화<input name="contact_phone" maxlength="30" value="<?= $v('contact_phone') ?>"></label>
<div class="form-actions"><button class="primary" type="submit"><?= $editing?'변경사항 저장':'초대장 임시저장' ?></button><?php if($editing): ?><a class="button secondary" target="_blank" href="/admin/invitations">게시 상태 확인</a><?php endif; ?></div>
</form>
