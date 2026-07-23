<?php
declare(strict_types=1);

function auth_set_user(array $user): void {
    $secret = getenv('AUTH_SECRET') ?: 'sistema-pos-secret-key-2026';
    $data = json_encode($user);
    $signature = hash_hmac('sha256', $data, $secret);
    $cookie = base64_encode($data) . '.' . $signature;
    setcookie('pos_auth', $cookie, [
        'expires'  => time() + 86400,
        'path'     => '/',
        'secure'   => true,
        'httponly'  => true,
        'samesite' => 'Lax'
    ]);
}

function auth_get_user(): ?array {
    if (!isset($_COOKIE['pos_auth'])) return null;
    $secret = getenv('AUTH_SECRET') ?: 'sistema-pos-secret-key-2026';
    $parts = explode('.', $_COOKIE['pos_auth']);
    if (count($parts) !== 2) return null;
    $data = base64_decode($parts[0]);
    if ($data === false) return null;
    $expectedSig = hash_hmac('sha256', $data, $secret);
    if (!hash_equals($expectedSig, $parts[1])) return null;
    $user = json_decode($data, true);
    if (!is_array($user)) return null;
    return $user;
}

function auth_require(): void {
    $user = auth_get_user();
    if (!$user) {
        header('Location: /index.php');
        exit();
    }
}

function auth_logout(): void {
    setcookie('pos_auth', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => true,
        'httponly'  => true,
        'samesite' => 'Lax'
    ]);
}
