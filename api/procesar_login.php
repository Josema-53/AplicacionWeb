<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioInput = $_POST['usuario'];
    $passwordInput = $_POST['password'];

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
                'id' => $usuarioDB['id'],
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
        die("Error en la base de datos: " . $e->getMessage());
    }
} else {
    header('Location: /index.php');
    exit();
}
