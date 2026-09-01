<?php
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;

$auth = Session::get('auth', []);
$auth = is_array($auth) ? $auth : [];
$isPlatform = !empty($auth['platform_role']);
$isManager = in_array($auth['church_role'] ?? '', ['owner', 'admin'], true);
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$active = static fn(string $href): string => $path === $href || ($href !== '/admin' && $href !== '/control' && str_starts_with($path, $href)) ? ' active' : '';
$tenantNav = [
    ['대시보드', '/admin', '⌂'], ['초대장 관리', '/admin/invitations', '▣'], ['행사·신청자', '/admin/events', '☷'],
    ['사진 관리', '/admin/media', '▧'], ['디자인 템플릿', '/admin/invitation-design', '◆'], ['방문·공유 통계', '/admin/analytics', '↗'],
    ['트래픽', '/admin/traffic', '⇅'], ['저장공간', '/admin/storage', '▰'], ['구독·결제', '/admin/subscription', '₩'], ['고객지원', '/admin/support', '?'],
];
$platformNav = [
    ['운영 대시보드', '/control', '⌂'], ['교회·단체 관리', '/control/churches', '▦'], ['체험 계정', '/control/trials', '◷'], ['고객지원', '/control/support', '?'],
];
?><!doctype html>
<html lang="ko"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?= View::e($title ?? '관리자') ?></title><link rel="stylesheet" href="/assets/admin.css"><link rel="stylesheet" href="/assets/design-presets.css"></head>
<body class="<?= $auth === [] ? 'auth-page' : 'admin-page' ?>">
<?php if ($auth === []): ?>
<header class="topbar auth-topbar"><a class="brand" href="/login"><span class="brand-mark">CI</span><span>Church Invitation</span></a></header><main class="container auth-container"><?= $content ?></main>
<?php else: ?>
<div class="admin-shell"><input class="sidebar-toggle" type="checkbox" id="sidebar-toggle" aria-label="관리자 메뉴 열기">
<aside class="sidebar"><a class="sidebar-brand" href="<?= $isPlatform ? '/control' : '/admin' ?>"><span class="brand-mark">CI</span><span><strong>Church Invitation</strong><small><?= $isPlatform ? '플랫폼 운영센터' : '초대장 관리센터' ?></small></span></a>
<nav class="sidebar-nav" aria-label="관리자 메뉴"><p class="nav-label"><?= $isPlatform ? '플랫폼 운영' : '초대장 운영' ?></p>
<?php foreach ($isPlatform ? $platformNav : $tenantNav as [$label, $href, $icon]): ?><a class="nav-item<?= $active($href) ?>" href="<?= $href ?>"><span class="nav-icon" aria-hidden="true"><?= $icon ?></span><span><?= $label ?></span></a><?php endforeach; ?>
<?php if (!$isPlatform && $isManager): ?><p class="nav-label nav-label-spaced">환경 설정</p><a class="nav-item<?= $active('/admin/church') ?>" href="/admin/church"><span class="nav-icon" aria-hidden="true">⚙</span><span>단체 기본정보</span></a><a class="nav-item<?= $active('/admin/managers') ?>" href="/admin/managers"><span class="nav-icon" aria-hidden="true">♙</span><span>관리자 관리</span></a><?php endif; ?></nav>
<div class="sidebar-account"><span class="account-avatar"><?= View::e(mb_substr((string)($auth['name'] ?? '관'), 0, 1)) ?></span><span class="account-copy"><strong><?= View::e($auth['name'] ?? '관리자') ?></strong><small><?= View::e($isPlatform ? ($auth['platform_role'] ?? '') : ($auth['church_role'] ?? '')) ?></small></span><form method="post" action="/logout" class="inline"><input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>"><button class="logout-button" title="로그아웃" aria-label="로그아웃">↪</button></form></div></aside>
<label class="sidebar-scrim" for="sidebar-toggle" aria-hidden="true"></label><div class="admin-workspace"><header class="admin-topbar"><div class="topbar-title"><label class="menu-button" for="sidebar-toggle" aria-label="메뉴 열기">☰</label><span><?= View::e($title ?? '관리자 대시보드') ?></span></div><div class="topbar-actions"><span class="status-dot"></span><span class="topbar-role"><?= $isPlatform ? '플랫폼 관리자' : '교회 관리자' ?></span><a class="topbar-help" href="<?= $isPlatform ? '/control/support' : '/admin/support' ?>">도움말</a></div></header><main class="container admin-container"><?= $content ?></main></div></div>
<?php endif; ?></body></html>
