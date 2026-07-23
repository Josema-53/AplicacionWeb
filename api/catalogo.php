<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
auth_require();
$usuario = auth_get_user();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogo de productos Sistema POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/frontend/css/dashboard.css">
    <style>
        .btn-verde { background-color: var(--verde-oscuro); color: white; }
        .btn-verde:hover { background-color: var(--verde-medio); color: white; }
    </style>
</head>

<body>
    <div class="d-flex">
        <?php include __DIR__ . '/../lib/sidebar.php'; ?>
        <div id="content" class="w-100" style="margin-left: 280px;">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm p-3 mb-4">
                <div class="container-fluid d-flex justify-content-between align-items-center">
                    <span class="navbar-brand mb-0 h4 text-secondary">>CATALOGO</span>
                    <div>
                        <span class="me-4 fw-bold" style="color: var(--verde-oscuro);">
                            <?php echo strtoupper($usuario['nombre'] . '| Rol:' . ucfirst($usuario['rol'])); ?>
                        </span>
                        <a href="/logout.php" class="btn btn-sm btn-outline-danger fw-bold">Cerrar sesion</a>
                    </div>
                </div>
            </nav>

            <div class="container-fluid px-4">
                <div class="d-flex justify-content-between mb-4">
                    <input type="text" id="input-busqueda" class="form-control w-25"
                        placeholder="Buscar por codigo o nombre">
                    <button class="btn btn-verde" onclick="abrirModal()"> + Nuevo Producto</button>
                </div>
                <div class="card-shadow-sm">
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Codigo</th>
                                    <th>Nombre</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpo-tabla">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalProducto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Gestionar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="prod-id">
                    <div class="mb-3">
                        <label>Codigo de Barras</label>
                        <input type="text" id="prod-codigo" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Nombre</label>
                        <input type="text" id="prod-nombre" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Precio</label>
                        <input type="number" id="prod-precio" class="form-control" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label>Stock</label>
                        <input type="number" id="prod-stock" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-verde" onclick="guardarProducto()">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/frontend/js/catalogo.js?v=2"></script>
</body>

</html>
