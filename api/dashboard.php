<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
auth_require();
$usuario = auth_get_user();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/frontend/css/dashboard.css">
</head>
<body>
    <div class="d-flex">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <div id="content" class="w-100" style="margin-left: 280px;">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm p-3 mb-4">
                <div class="container-fluid d-flex justify-content-between align-items-center">
                    <span class="navbar-brand mb-0 h4 text-secondary">>DASHBOARD GENERAL</span>
                    <div>
                        <span class="me-4 fw-bold" style="color: var(--verde-oscuro);">
                            <?php echo strtoupper($usuario['nombre'] . '| Rol:' . ucfirst($usuario['rol'])); ?>
                        </span>
                        <a href="/logout.php" class="btn btn-sm btn-outline-danger fw-bold">Cerrar sesion</a>
                    </div>
                </div>
            </nav>

            <div class="container-fluid px-4">
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 border-top border-4" style="border-color: var(--verde-medio);">
                            <div class="card-body py-5 text-center bg-white rounded">
                                <h2 style="color: var(--verde-oscuro);"> Bienvenido
                                <?php echo ucfirst($usuario['rol']); ?>
                                </h2>
                                <p class="text-muted fs-5 mt-3">Seleccione una opcion del menu lateral para operar el sistema de ventas</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
