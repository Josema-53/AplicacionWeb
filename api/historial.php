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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Facturas - Sistema POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/frontend/css/dashboard.css">
    <style>
        .btn-verde { background-color: var(--verde-oscuro); color: white; }
        .btn-verde:hover { background-color: var(--verde-medio); color: white; }
    </style>
</head>

<body>
    <div class="d-flex">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div id="content" class="w-100" style="margin-left: 280px;">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm p-3 mb-4">
                <div class="container-fluid d-flex justify-content-between align-items-center">
                    <span class="navbar-brand mb-0 h4 text-secondary">>HISTORIAL DE FACTURAS</span>
                    <div>
                        <span class="me-4 fw-bold" style="color: var(--verde-oscuro);">
                            <?php echo strtoupper($usuario['nombre'] . ' | Rol: ' . ucfirst($usuario['rol'])); ?>
                        </span>
                        <a href="/logout.php" class="btn btn-sm btn-outline-danger fw-bold">Cerrar sesion</a>
                    </div>
                </div>
            </nav>

            <div class="container-fluid px-4">

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon stat-icon-total me-3">Total</div>
                                <div>
                                    <div class="stat-label">Total Vendido</div>
                                    <div class="stat-valor" id="stat-total">$0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon stat-icon-cantidad me-3">Cant</div>
                                <div>
                                    <div class="stat-label">Cantidad de Facturas</div>
                                    <div class="stat-valor" id="stat-cantidad">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card border-0 shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="stat-icon stat-icon-promedio me-3">Prom</div>
                                <div>
                                    <div class="stat-label">Ticket Promedio</div>
                                    <div class="stat-valor" id="stat-promedio">$0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="card-title fw-bold text-uppercase mb-3" style="color: var(--verde-oscuro); letter-spacing:1px;">
                            Busqueda Avanzada
                        </h6>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label fw-semibold small">Fecha Inicio</label>
                                <input type="date" id="filtro-fecha-inicio" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold small">Fecha Fin</label>
                                <input type="date" id="filtro-fecha-fin" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Cliente (nombre o cedula)</label>
                                <input type="text" id="filtro-cliente" class="form-control" placeholder="Escriba para buscar...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold small">N Factura</label>
                                <input type="number" id="filtro-factura" class="form-control" placeholder="ID #">
                            </div>
                            <div class="col-md-3 d-flex gap-2">
                                <button class="btn btn-verde flex-fill" id="btn-buscar">Buscar</button>
                                <button class="btn btn-outline-secondary flex-fill" id="btn-limpiar">Limpiar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="tabla-historial" style="table-layout: fixed; width: 100%;">
                                <colgroup>
                                    <col style="width: 9%;">
                                    <col style="width: 17%;">
                                    <col style="width: 20%;">
                                    <col style="width: 15%;">
                                    <col style="width: 12%;">
                                    <col style="width: 10%;">
                                    <col style="width: 17%;">
                                </colgroup>
                                <thead class="hist-thead">
                                    <tr>
                                        <th>N Factura</th>
                                        <th>Fecha y Hora</th>
                                        <th>Cliente</th>
                                        <th>Vendedor</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpo-tabla-historial">
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            Cargando facturas...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetalles" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--verde-oscuro); color: #fff;">
                    <h5 class="modal-title">
                        Detalle Factura <span id="detalle-num-factura"></span>
                        <span id="detalle-estado-badge" class="badge ms-2"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="fw-bold" style="color: var(--verde-medio);">Informacion General</h6>
                            <p class="mb-1"><strong>Fecha:</strong> <span id="detalle-fecha"></span></p>
                            <p class="mb-1"><strong>Vendedor:</strong> <span id="detalle-vendedor"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold" style="color: var(--verde-medio);">Datos del Cliente</h6>
                            <p class="mb-1"><strong>Nombre:</strong> <span id="detalle-cliente"></span></p>
                            <p class="mb-1"><strong>Cedula:</strong> <span id="detalle-cedula"></span></p>
                            <p class="mb-1"><strong>Telefono:</strong> <span id="detalle-telefono"></span></p>
                            <p class="mb-1"><strong>Correo:</strong> <span id="detalle-correo"></span></p>
                        </div>
                    </div>

                    <hr>

                    <h6 class="fw-bold mb-3" style="color: var(--verde-medio);">Productos</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Codigo</th>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">Precio Unit.</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="detalle-productos-body"></tbody>
                        </table>
                    </div>

                    <hr>

                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <div class="p-3 rounded" style="background: #f8f9fa;">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Subtotal:</span>
                                    <span id="detalle-subtotal" class="fw-semibold">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">IVA (15%):</span>
                                    <span id="detalle-iva" class="fw-semibold">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 pt-2 border-top">
                                    <span class="fw-bold fs-5">TOTAL:</span>
                                    <span id="detalle-total" class="fw-bold fs-5" style="color: var(--verde-oscuro);">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Pago con:</span>
                                    <span id="detalle-pago" class="fw-semibold">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Cambio:</span>
                                    <span id="detalle-cambio" class="fw-semibold">$0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/frontend/js/historial.js"></script>
</body>

</html>
