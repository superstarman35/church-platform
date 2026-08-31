<?php

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;

$auth = Session::get('auth', []);
$auth = is_array($auth) ? $auth : [];
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= View::e($title ?? '관리자') ?></title>
    <link rel="stylesheet" href="/assets/admin.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= !empty($auth['platform_role']) ? '/control' : '/admin' ?>">Church Invitation</a>
    <?php if ($auth !== []): ?>
        <nav>
            <?php if (!empty($auth['platform_role'])): ?><a href="/control/churches">교회·단체</a><?php endif; ?>
            <span><?= View::e($auth['name'] ?? '') ?></span>
            <form method="post" action="/logout" class="inline"><input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>"><button class="link-button" type="submit">로그아웃</button></form>
        </nav>
    <?php endif; ?>
</header>
<main class="container">
    <?= $content ?>
</main>
</body>
</html>
