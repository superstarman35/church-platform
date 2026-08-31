<section class="auth-card">
    <p class="eyebrow">관리자 전용</p>
    <h1>로그인</h1>
    <p class="muted">플랫폼 관리자와 교회 관리자가 같은 보안 로그인 화면을 사용합니다.</p>
    <?php if (!empty($error)): ?><div class="alert alert-error"><?= \App\Core\View::e($error) ?></div><?php endif; ?>
    <form method="post" action="/login" class="form-stack">
        <input type="hidden" name="_token" value="<?= \App\Core\View::e(\App\Core\Csrf::token()) ?>">
        <label>이메일<input type="email" name="email" autocomplete="username" required maxlength="190"></label>
        <label>비밀번호<input type="password" name="password" autocomplete="current-password" required></label>
        <button class="button primary" type="submit">로그인</button>
    </form>
</section>
