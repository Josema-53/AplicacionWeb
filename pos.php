<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit();
}
$usuario = $_SESSION['usuario_activo'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto de Venta - Sistema POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="frontend/css/dashboard.css">
    <script src="frontend/js/html5-qrcode.min.js"></script>
</head>

<body>
    <div class="d-flex">
        <?php include __DIR__ . '/backend/includes/sidebar.php'; ?>

        <div id="content" class="w-100" style="margin-left: 280px;">
            <!-- Navbar superior -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm p-3 mb-0">
                <div class="container-fluid d-flex justify-content-between align-items-center">
                    <span class="navbar-brand mb-0 h4 text-secondary">>PUNTO DE VENTA</span>
                    <div>
                        <span class="me-4 fw-bold" style="color: var(--verde-oscuro);">
                            👤<?php echo strtoupper($usuario['nombre'] . ' | Rol: ' . ucfirst($usuario['rol'])); ?>
                        </span>
                        <a href="backend/logout.php" class="btn btn-sm btn-outline-danger fw-bold">Cerrar sesión</a>
                    </div>
                </div>
            </nav>

            <!-- Contenedor principal del POS -->
            <div class="pos-container">

                <!-- ═══ COLUMNA IZQUIERDA: Zona de Operación (70%) ═══ -->
                <div class="pos-zona-operacion">

                    <!-- Buscador / Lector de código de barras -->
                    <div class="pos-buscador-wrapper">
                        <div class="pos-buscador-row">
                            <input type="text" id="pos-busqueda" class="pos-input-busqueda"
                                placeholder="🔍  Escanee código de barras o escriba el nombre del producto..."
                                autocomplete="off" autofocus>
                            <button type="button" class="pos-btn-scanner" id="btn-abrir-scanner" title="Escanear con cámara">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M2 2h2v2H2zm3 0h1v2H5zm3 0h2v2H8zm3 0h1v2h-1zm3 0h1v2h-1zM1 4h1v1H1zm13 0h1v1h-1zM2 5h2v1H2zm3 0h1v1H5zm3 0h2v1H8zm3 0h1v1h-1zm3 0h1v1h-1zM1 7h1v2H1zm13 0h1v2h-1zM2 7h2v2H2zm3 0h1v2H5zm3 0h2v2H8zm3 0h1v2h-1zm3 0h1v2h-1zM1 10h1v1H1zm13 0h1v1h-1zM2 11h2v1H2zm3 0h1v1H5zm3 0h2v1H8zm3 0h1v1h-1zm3 0h1v1h-1zM2 12h2v2H2zm3 0h1v2H5zm3 0h2v2H8zm3 0h1v2h-1zm3 0h1v2h-1z"/>
                                </svg>
                                <span class="scanner-btn-label">Escanear</span>
                            </button>
                        </div>
                        <div id="pos-resultados" class="pos-resultados-busqueda"></div>
                    </div>

                    <!-- Tabla del carrito de compras -->
                    <div class="pos-carrito-wrapper">
                        <table class="table pos-carrito-tabla mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40%;">Producto</th>
                                    <th style="width:15%;" class="text-center">Precio</th>
                                    <th style="width:20%;" class="text-center">Cantidad</th>
                                    <th style="width:15%;" class="text-center">Subtotal</th>
                                    <th style="width:10%;" class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="pos-carrito-body">
                            </tbody>
                        </table>
                        <!-- Mensaje cuando el carrito está vacío -->
                        <div id="pos-carrito-vacio" class="pos-carrito-vacio">
                            <span class="icono-vacio">🛒</span>
                            <h5>Carrito vacío</h5>
                            <p class="text-muted">Escanee un producto o escriba para buscar</p>
                        </div>
                    </div>
                </div>

                <!-- ═══ COLUMA DERECHA: Panel de Facturación (30%) ═══ -->
                <div class="pos-panel-facturacion">

                    <!-- Sección: Cliente -->
                    <div class="pos-seccion">
                        <div class="pos-seccion-titulo">👤 Cliente</div>
                        <div id="pos-cliente-area">
                            <div class="pos-busqueda-cliente">
                                <input type="text" id="pos-busqueda-cliente" class="pos-input-cliente"
                                    placeholder="Buscar cliente por nombre o cédula..." autocomplete="off">
                                <div id="pos-resultados-cliente" class="pos-resultados-cliente"></div>
                            </div>
                            <button class="pos-btn-consumidor" onclick="seleccionarConsumidorFinal()">
                                ⚡ Consumidor Final
                            </button>
                        </div>
                        <div id="pos-cliente-seleccionado" style="display:none;"></div>
                    </div>

                    <!-- Sección: Resumen de Totales -->
                    <div class="pos-seccion">
                        <div class="pos-seccion-titulo">💰 Resumen</div>
                        <div class="pos-totales">
                            <div class="pos-total-fila subtotal">
                                <span>Subtotal:</span>
                                <span id="pos-subtotal">$0.00</span>
                            </div>
                            <div class="pos-total-fila iva">
                                <span>IVA (15%):</span>
                                <span id="pos-iva">$0.00</span>
                            </div>
                            <div class="pos-total-fila total-general">
                                <span>TOTAL:</span>
                                <span id="pos-total">$0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Pago -->
                    <div class="pos-seccion">
                        <div class="pos-seccion-titulo">💵 Pago</div>
                        <div class="pos-monto-rapido">
                            <button onclick="pagarRapido(5)">$5</button>
                            <button onclick="pagarRapido(10)">$10</button>
                            <button onclick="pagarRapido(20)">$20</button>
                            <button onclick="pagarRapido(50)">$50</button>
                            <button onclick="pagarRapido(100)">$100</button>
                        </div>
                        <div class="pos-pago-input-group">
                            <label>Monto:</label>
                            <input type="number" id="pos-monto-pago" class="pos-pago-input"
                                placeholder="0.00" step="0.01" min="0" value="">
                        </div>

                        <!-- Cambio / Vuelto -->
                        <div id="pos-cambio" class="pos-cambio">
                            <div class="pos-cambio-label">Cambio / Vuelto</div>
                            <div class="pos-cambio-valor" id="pos-cambio-valor">$0.00</div>
                        </div>
                    </div>

                    <!-- Botón Procesar Venta -->
                    <button id="pos-btn-procesar" class="pos-btn-procesar" onclick="procesarVenta()" disabled>
                        💳 PROCESAR VENTA
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ MODAL ESCÁNER DE CÓDIGOS DE BARRAS ═══ -->
    <div class="modal fade" id="modalScanner" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--verde-oscuro); color: #fff;">
                    <h5 class="modal-title">📷 Escanear Código de Barras</h5>
                    <button type="button" class="btn-close btn-close-white" id="btn-cerrar-scanner"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <div id="scanner-reader"></div>
                    <div id="scanner-resultado" class="scanner-resultado" style="display:none;"></div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" id="btn-cancelar-scanner">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <!-- POS JavaScript -->
    <script src="frontend/js/pos.js"></script>
</body>

</html>
