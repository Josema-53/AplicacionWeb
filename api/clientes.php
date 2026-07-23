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
    <title>Gestion de Clientes - Sistema POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/frontend/css/dashboard.css">
    <style>
        .btn-verde { background-color: var(--verde-oscuro); color: white; }
        .btn-verde:hover { background-color: var(--verde-medio); color: white; }
        #cli-cedula.is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccx cx='6' cy='8.2' r='.5' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1.25em 1.25em;
        }
        .stat-card { border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1) !important; }
        .stat-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.6em; flex-shrink: 0; }
        .stat-icon-compras { background: linear-gradient(135deg, #e3f2fd, #bbdefb); }
        .stat-icon-gastado { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); }
        .stat-icon-promedio { background: linear-gradient(135deg, #fff3e0, #ffe0b2); }
        .stat-label { font-size: 0.8em; text-transform: uppercase; letter-spacing: 0.5px; color: #888; font-weight: 600; }
        .stat-valor { font-size: 1.6em; font-weight: 800; color: var(--verde-oscuro); line-height: 1.2; }
        .hist-thead tr { background-color: var(--verde-oscuro); }
        .hist-thead th { background-color: var(--verde-oscuro); color: #fff; padding: 12px 10px; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; border: none; }
        #tabla-estadistica-productos tbody tr { transition: background 0.2s; }
        #tabla-estadistica-productos tbody tr:hover { background-color: #f0faf4 !important; }
        #tabla-estadistica-historial tbody tr { transition: background 0.2s; }
        #tabla-estadistica-historial tbody tr:hover { background-color: #f0faf4 !important; }
    </style>
</head>

<body>
    <div class="d-flex">
        <?php include __DIR__ . '/../lib/sidebar.php'; ?>
        <div id="content" class="w-100" style="margin-left: 280px;">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm p-3 mb-4">
                <div class="container-fluid d-flex justify-content-between align-items-center">
                    <span class="navbar-brand mb-0 h4 text-secondary">>CLIENTES</span>
                    <div>
                        <span class="me-4 fw-bold" style="color: var(--verde-oscuro);">
                            <?php echo strtoupper($usuario['nombre'] . ' | Rol: ' . ucfirst($usuario['rol'])); ?>
                        </span>
                        <a href="/logout.php" class="btn btn-sm btn-outline-danger fw-bold">Cerrar sesion</a>
                    </div>
                </div>
            </nav>

            <div class="container-fluid px-4">
                <div class="d-flex justify-content-between mb-4">
                    <input type="text" id="input-busqueda" class="form-control w-25"
                        placeholder="Buscar por nombre, cedula, correo o telefono">
                    <button class="btn btn-verde" onclick="abrirModal()"> + Nuevo Cliente</button>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Cedula</th>
                                    <th>Nombre Completo</th>
                                    <th>Correo</th>
                                    <th>Telefono</th>
                                    <th>Acciones</th>
                                    <th>Estadisticas</th>
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

    <div class="modal fade" id="modalCliente" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Gestionar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cli-id">
                    <div class="mb-3">
                        <label>Nombre Completo *</label>
                        <input type="text" id="cli-nombre" class="form-control" placeholder="Ej: Juan Perez">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label>Cedula *</label>
                        <input type="text" id="cli-cedula" class="form-control" placeholder="Ej: 1234567890" maxlength="10">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label>Correo</label>
                        <input type="email" id="cli-correo" class="form-control" placeholder="Ej: juan@correo.com">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label>Telefono</label>
                        <input type="text" id="cli-telefono" class="form-control" placeholder="Ej: 0991234567" maxlength="10">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-verde" onclick="guardarCliente()">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEstadisticas" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--verde-oscuro); color: #fff;">
                    <h5 class="modal-title">Estadisticas de <span id="est-cli-nombre"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card stat-card shadow-sm border-0 h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="stat-icon stat-icon-compras">Compras</div>
                                    <div>
                                        <div class="stat-label">Total Compras</div>
                                        <div class="stat-valor" id="est-total-compras">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card shadow-sm border-0 h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="stat-icon stat-icon-gastado">Gastado</div>
                                    <div>
                                        <div class="stat-label">Total Gastado</div>
                                        <div class="stat-valor" id="est-total-gastado">$0.00</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card shadow-sm border-0 h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="stat-icon stat-icon-promedio">Promedio</div>
                                    <div>
                                        <div class="stat-label">Promedio por Factura</div>
                                        <div class="stat-valor" id="est-promedio">$0.00</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <small class="text-muted">Primera compra: <strong id="est-primera-compra">-</strong></small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">Ultima compra: <strong id="est-ultima-compra">-</strong></small>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3" style="color: var(--verde-oscuro);">Productos Comprados</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm" id="tabla-estadistica-productos">
                            <thead class="hist-thead">
                                <tr>
                                    <th>Codigo</th>
                                    <th>Producto</th>
                                    <th class="text-center">Veces Comprado</th>
                                    <th class="text-end">Precio Promedio</th>
                                    <th class="text-end">Total Gastado</th>
                                </tr>
                            </thead>
                            <tbody id="est-productos-body">
                                <tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="fw-bold mb-3" style="color: var(--verde-oscuro);">Historial de Compras</h6>
                    <div class="table-responsive">
                        <table class="table table-sm" id="tabla-estadistica-historial">
                            <thead class="hist-thead">
                                <tr>
                                    <th># Factura</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-end">Subtotal</th>
                                    <th class="text-end">IVA</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody id="est-historial-body">
                                <tr><td colspan="6" class="text-center text-muted">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/frontend/js/clientes.js?v=2"></script>
</body>

</html>
