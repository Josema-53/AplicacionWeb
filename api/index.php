<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/conexion.php';

$uri = $_SERVER['REQUEST_URI'] ?? '/';

if ($uri === '/logout.php' || $uri === '/logout') {
    auth_logout();
    header('Location: /index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($uri === '/procesar_login.php' || $uri === '/procesar_login' || $uri === '/')) {
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
            if (password_verify($passwordInput, (string)$usuarioDB['password_hash'])) { $passwordOk = true; }
            elseif ($passwordInput === $usuarioDB['password_hash']) { $passwordOk = true; }
        }
        if ($usuarioDB && $passwordOk) {
            auth_set_user(['id' => (int)$usuarioDB['id'], 'usuario' => $usuarioDB['usuario'], 'nombre' => $usuarioDB['usuario'], 'rol' => $usuarioDB['rol']]);
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
}

$user = auth_get_user();
if ($user) {
    header('Location: /dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow p-4" style="width:100%; max-width: 400px;">
        <div class="text-center mb-4">
            <h3 class="text-primary">SISTEMA POS</h3>
            <p class="text-muted">Ingrese su usuario y contrasena para acceder.</p>
        </div>
        <?php if(isset($_GET['locked'])): ?>
            <div class="alert alert-danger">Demasiados intentos. Intente de nuevo en 5 minutos.</div>
        <?php elseif(isset($_GET['error'])): ?>
            <div class="alert alert-danger">Usuario o contrasena incorrectos.</div>
        <?php endif; ?>
        <form method="POST" action="/">
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuario</label>
                <input type="text" class="form-control" id="usuario" name="usuario" required>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Contrasena</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Iniciar Sesion</button>
        </form>
    </div>
</body>
</html>
