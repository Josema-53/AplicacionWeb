<?php

session_start();
if (isset($_SESSION['usuario_activo'])) {
    header('Location: dashboard.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body class="bg-light d-flex align-items-center justify-content-center vh-1000">
    <div class="card-shadow p-4" style="width:100% ; max-width: 400px;">

        <div class="text-center mb-4">
            <h3 class="text-primary">SISTEMA POS</h3>
            <p class="text-muted">Ingrese su nombre de usuario y contraseña para acceder al sistema.</p>
        </div>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger" role="alert">
               Usuario o contraseña incorrectos.
        <?php endif; ?>

        <form method="POST" action="backend/procesar_login.php">
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuario</label>
                <input type="text" class="form-control" id="usuario" name="usuario" required>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
        </form>

    </div>

</body>

</html>