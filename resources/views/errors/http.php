<section class="auth-card"><p class="eyebrow">오류 <?= (int) $status ?></p><h1>요청을 처리할 수 없습니다.</h1><p><?= \App\Core\View::e($message ?: '잠시 후 다시 시도해 주세요.') ?></p><a href="/">처음으로</a></section>
