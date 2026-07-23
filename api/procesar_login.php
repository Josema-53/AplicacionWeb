<?php
declare(strict_types=1);

error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php');
    exit();
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!auth_rate_limit('login:' . $ip, 5, 300)) {
    header('Location: /index.php?error=1&locked=1');
    exit();
}

$usuarioInput = $_POST['usuario'] ?? '';
$passwordInput = $_POST['password'] ?? '';

if (empty($usuarioInput) || empty($passwordInput)) {
    header('Location: /index.php?error=1');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND estado=1");
    $stmt->execute([$usuarioInput]);
    $usuarioDB = $stmt->fetch(PDO::FETCH_ASSOC);

    $passwordOk = false;
    if ($usuarioDB) {
        if (password_verify($passwordInput, (string)$usuarioDB['password_hash'])) {
            $passwordOk = true;
        } elseif ($passwordInput === $usuarioDB['password_hash']) {
            $passwordOk = true;
        }
    }

    if ($usuarioDB && $passwordOk) {
        auth_set_user([
            'id' => (int)$usuarioDB['id'],
            'usuario' => $usuarioDB['usuario'],
            'nombre' => $usuarioDB['usuario'],
            'rol' => $usuarioDB['rol']
        ]);
        header('Location: /dashboard.php');
        exit();
    } else {
        header('Location: /index.php?error=1');
        exit();
    }
} catch (PDOException $e) {
    header('Location: /index.php?error=1');
    exit();
}
