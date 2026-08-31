<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Totp;
use App\Repositories\MfaRepository;
use App\Repositories\UserRepository;

$pdo = require dirname(__DIR__) . '/bootstrap/app.php';

function prompt(string $label): string
{
    fwrite(STDOUT, $label);
    return trim((string) fgets(STDIN));
}

function secretPrompt(string $label): string
{
    fwrite(STDOUT, $label);
    if (PHP_OS_FAMILY !== 'Windows') {
        shell_exec('stty -echo');
    }
    $value = trim((string) fgets(STDIN));
    if (PHP_OS_FAMILY !== 'Windows') {
        shell_exec('stty echo');
    }
    fwrite(STDOUT, PHP_EOL);
    return $value;
}

$name = prompt('관리자 이름: ');
$email = mb_strtolower(prompt('관리자 이메일: '));
$password = secretPrompt('관리자 비밀번호(영문·숫자 포함 10자 이상): ');
if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 10 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    fwrite(STDERR, "입력값을 확인해 주세요.\n");
    exit(1);
}

$users = new UserRepository($pdo);
if ($users->findByEmail($email) !== null) {
    fwrite(STDERR, "이미 등록된 이메일입니다.\n");
    exit(1);
}

$pdo->beginTransaction();
try {
    $userId = $users->create($name, $email, password_hash($password, PASSWORD_DEFAULT));
    $statement = $pdo->prepare("INSERT INTO platform_user_roles (user_id, role) VALUES (:user_id, 'platform_admin')");
    $statement->execute(['user_id' => $userId]);
    $secret = Totp::secret();
    (new MfaRepository($pdo))->enable($userId, $secret);
    $pdo->commit();
    $issuer = (string) Config::get('app.name', 'Church Invitation Platform');
    fwrite(STDOUT, "플랫폼 관리자를 생성했습니다. ID={$userId}\n");
    fwrite(STDOUT, "MFA secret: {$secret}\n");
    fwrite(STDOUT, 'MFA URI: ' . Totp::uri($issuer, $email, $secret) . "\n");
    fwrite(STDOUT, "위 값을 인증 앱에 즉시 등록하고 안전한 장소에 보관하세요.\n");
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}
