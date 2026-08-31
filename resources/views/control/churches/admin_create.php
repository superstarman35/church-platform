<div class="page-header"><div><p class="eyebrow"><?= \App\Core\View::e($church['name']) ?></p><h1>교회 관리자 추가</h1></div><a href="/control/churches">목록</a></div>
<?php if (!empty($error)): ?><div class="alert alert-error"><?= \App\Core\View::e($error) ?></div><?php endif; ?>
<form method="post" action="/control/churches/<?= (int) $church['id'] ?>/admins" class="panel form-stack narrow">
<input type="hidden" name="_token" value="<?= \App\Core\View::e(\App\Core\Csrf::token()) ?>">
<label>이름<input name="name" required maxlength="100"></label>
<label>이메일<input type="email" name="email" required maxlength="190" autocomplete="off"></label>
<label>임시 비밀번호<input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
<label>역할<select name="role"><option value="admin">교회 관리자</option><option value="content_manager">콘텐츠 담당자</option><option value="owner">대표 관리자</option></select></label>
<button class="button primary" type="submit">관리자 추가</button>
</form>
