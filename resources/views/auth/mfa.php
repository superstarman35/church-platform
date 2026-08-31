<section class="auth-card">
    <p class="eyebrow">플랫폼 관리자 보안</p>
    <h1>2단계 인증</h1>
    <p class="muted">인증 앱에 표시된 6자리 번호를 입력해 주세요.</p>
    <?php if (!empty($error)): ?><div class="alert alert-error"><?= \App\Core\View::e($error) ?></div><?php endif; ?>
    <form method="post" action="/mfa" class="form-stack">
        <input type="hidden" name="_token" value="<?= \App\Core\View::e(\App\Core\Csrf::token()) ?>">
        <label>인증번호<input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus></label>
        <button class="button primary" type="submit">인증하기</button>
    </form>
</section>
