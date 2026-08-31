<div class="page-header"><div><p class="eyebrow">플랫폼 관리</p><h1>교회·단체 생성</h1><p class="muted">초대장 전용 30일 체험과 대표 관리자를 함께 생성합니다.</p></div><a href="/control/churches">목록</a></div>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= \App\Core\View::e($error) ?></div><?php endif; ?>
<form method="post" action="/control/churches" class="panel form-grid">
<input type="hidden" name="_token" value="<?= \App\Core\View::e(\App\Core\Csrf::token()) ?>">
<h2>교회·단체 정보</h2>
<label>구분<select name="organization_type"><option value="church">교회</option><option value="organization">단체</option></select></label>
<label>이름<input name="name" required maxlength="150" value="<?= \App\Core\View::e($old['name'] ?? '') ?>"></label>
<label>서비스 주소<input name="slug" required minlength="3" maxlength="80" pattern="[a-z0-9][a-z0-9-]{2,79}" placeholder="sample-church" value="<?= \App\Core\View::e($old['slug'] ?? '') ?>"><small>영문 소문자, 숫자, 하이픈만 사용합니다.</small></label>
<label>담당자 이름<input name="contact_name" required maxlength="100" value="<?= \App\Core\View::e($old['contact_name'] ?? '') ?>"></label>
<label>담당자 이메일<input type="email" name="contact_email" required maxlength="190" value="<?= \App\Core\View::e($old['contact_email'] ?? '') ?>"></label>
<label>담당자 전화<input name="contact_phone" maxlength="30" value="<?= \App\Core\View::e($old['contact_phone'] ?? '') ?>"></label>
<h2>대표 관리자</h2>
<label>관리자 이름<input name="owner_name" required maxlength="100" value="<?= \App\Core\View::e($old['owner_name'] ?? '') ?>"></label>
<label>관리자 이메일<input type="email" name="owner_email" required maxlength="190" autocomplete="off" value="<?= \App\Core\View::e($old['owner_email'] ?? '') ?>"></label>
<label>임시 비밀번호<input type="password" name="owner_password" required minlength="10" autocomplete="new-password"><small>영문과 숫자를 포함한 10자 이상. 실제 운영에서는 초대 링크 방식으로 교체합니다.</small></label>
<div class="form-actions"><button class="button primary" type="submit">30일 체험 생성</button></div>
</form>
