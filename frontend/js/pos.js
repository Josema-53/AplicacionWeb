// ══════════════════════════════════════════════════════════════
//  POS.JS - Lógica del Cliente para el Punto de Venta
// ══════════════════════════════════════════════════════════════

const API = '/api_pos.php';
const IVA_PCT = 0.15;

// ─── Estado del POS ───
let carrito = [];          // [{id, codigo_barras, nombre_producto, precio, cantidad, subtotal}]
let clienteSeleccionado = null;  // {id, nombre, cedula} o null (Consumidor Final)
let debounceTimer = null;
let debounceClienteTimer = null;

// ══════════════════════════════════════════════════════════════
//  INICIALIZACIÓN
// ══════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {
    const inputBusqueda = document.getElementById('pos-busqueda');
    const inputCliente  = document.getElementById('pos-busqueda-cliente');
    const inputPago     = document.getElementById('pos-monto-pago');

    // Focus automático en el buscador principal
    inputBusqueda.focus();

    // Mantener foco en el buscador al hacer click en cualquier parte del body
    document.body.addEventListener('click', (e) => {
        if (!e.target.closest('.pos-input-cliente') &&
            !e.target.closest('.pos-resultados-cliente') &&
            !e.target.closest('.pos-btn-consumidor') &&
            !e.target.closest('.pos-pago-input') &&
            !e.target.closest('.pos-monto-rapido') &&
            !e.target.closest('.pos-btn-procesar') &&
            !e.target.closest('#modalScanner') &&
            !e.target.closest('#btn-abrir-scanner')) {
            inputBusqueda.focus();
        }
    });

    // Búsqueda de productos con debounce
    inputBusqueda.addEventListener('input', () => {
        const valor = inputBusqueda.value.trim();
        clearTimeout(debounceTimer);

        // Si es solo números y tiene 3+ dígitos, podría ser código de barras
        // Buscar inmediatamente para lectores de código de barras (terminan con enter)
        debounceTimer = setTimeout(() => {
            if (valor.length >= 1) {
                buscarProductos(valor);
            } else {
                ocultarResultados();
            }
        }, 200);
    });

    // Manejar Enter en el buscador (lector de código de barras)
    inputBusqueda.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const valor = inputBusqueda.value.trim();
            if (valor.length >= 1) {
                // Intentar buscar por código exacto primero
                buscarPorCodigo(valor);
            }
        }
        // Escape para cerrar resultados
        if (e.key === 'Escape') {
            ocultarResultados();
        }
    });

    // Búsqueda de clientes con debounce
    inputCliente.addEventListener('input', () => {
        const valor = inputCliente.value.trim();
        clearTimeout(debounceClienteTimer);
        debounceClienteTimer = setTimeout(() => {
            if (valor.length >= 1) {
                buscarClientes(valor);
            } else {
                ocultarResultadosCliente();
            }
        }, 300);
    });

    // Monto de pago - calcular cambio en tiempo real
    inputPago.addEventListener('input', () => {
        calcularCambio();
        actualizarBotonProcesar();
    });

    // Botón abrir escáner
    document.getElementById('btn-abrir-scanner').addEventListener('click', (e) => {
        e.stopPropagation();
        abrirScanner();
    });

    // Botones cerrar escáner
    document.getElementById('btn-cerrar-scanner').addEventListener('click', cerrarScanner);
    document.getElementById('btn-cancelar-scanner').addEventListener('click', cerrarScanner);

    // Cerrar escáner al cerrar el modal
    document.getElementById('modalScanner').addEventListener('hidden.bs.modal', cerrarScanner);
});

// ══════════════════════════════════════════════════════════════
//  BÚSQUEDA DE PRODUCTOS
// ══════════════════════════════════════════════════════════════

async function buscarProductos(query) {
    try {
        const resp = await fetch(`${API}?action=buscar_producto&q=${encodeURIComponent(query)}`);
        const productos = await resp.json();
        mostrarResultados(productos);
    } catch (err) {
        console.error('Error buscando productos:', err);
    }
}

async function buscarPorCodigo(codigo) {
    try {
        const resp = await fetch(`${API}?action=buscar_por_codigo&codigo=${encodeURIComponent(codigo)}`);
        if (resp.ok) {
            const producto = await resp.json();
            agregarAlCarrito(producto);
            document.getElementById('pos-busqueda').value = '';
            ocultarResultados();
        } else {
            // Si no se encuentra por código exacto, buscar por nombre
            buscarProductos(codigo);
        }
    } catch (err) {
        console.error('Error buscando por código:', err);
    }
}

function mostrarResultados(productos) {
    const contenedor = document.getElementById('pos-resultados');
    if (!productos || productos.length === 0) {
        contenedor.innerHTML = '<div class="resultado-item" style="color:#999;cursor:default;">No se encontraron productos</div>';
        contenedor.style.display = 'block';
        return;
    }

    contenedor.innerHTML = productos.map(p => `
        <div class="resultado-item" data-id="${p.id}" data-nombre="${escapeHtml(p.nombre_producto)}" data-precio="${p.precio}" data-stock="${p.stock}" data-codigo="${escapeHtml(p.codigo_barras || '')}">
            <div>
                <span class="prod-nombre">${escapeHtml(p.nombre_producto)}</span><br>
                <small class="prod-stock">Stock: ${p.stock} | Código: ${escapeHtml(p.codigo_barras || 'N/A')}</small>
            </div>
            <span class="prod-precio">$${parseFloat(p.precio).toFixed(2)}</span>
        </div>
    `).join('');
    contenedor.style.display = 'block';

    contenedor.querySelectorAll('.resultado-item[data-id]').forEach(item => {
        item.addEventListener('click', () => {
            agregarAlCarrito({
                id: parseInt(item.dataset.id),
                nombre_producto: item.dataset.nombre,
                precio: parseFloat(item.dataset.precio),
                stock: parseInt(item.dataset.stock),
                codigo_barras: item.dataset.codigo,
            });
            document.getElementById('pos-busqueda').value = '';
            ocultarResultados();
            document.getElementById('pos-busqueda').focus();
        });
    });
}

function ocultarResultados() {
    document.getElementById('pos-resultados').style.display = 'none';
}

// ══════════════════════════════════════════════════════════════
//  CARRITO DE COMPRAS
// ══════════════════════════════════════════════════════════════

function agregarAlCarrito(producto) {
    // Verificar si ya está en el carrito
    const existente = carrito.find(item => item.id === producto.id);

    if (existente) {
        if (existente.cantidad < (producto.stock || 999)) {
            existente.cantidad++;
        } else {
            mostrarNotificacion('Stock insuficiente', 'warning');
            return;
        }
    } else {
        carrito.push({
            id: producto.id,
            codigo_barras: producto.codigo_barras || '',
            nombre_producto: producto.nombre_producto,
            precio: parseFloat(producto.precio),
            cantidad: 1,
            stock: producto.stock || 999,
        });
    }

    mostrarNotificacion(`${producto.nombre_producto} agregado al carrito`, 'success');
    renderizarCarrito();
    actualizarTotales();
}

function aumentarCantidad(index) {
    const item = carrito[index];
    if (item.cantidad < item.stock) {
        item.cantidad++;
        renderizarCarrito();
        actualizarTotales();
    } else {
        mostrarNotificacion('Stock máximo alcanzado', 'warning');
    }
}

function disminuirCantidad(index) {
    const item = carrito[index];
    if (item.cantidad > 1) {
        item.cantidad--;
        renderizarCarrito();
        actualizarTotales();
    } else {
        eliminarDelCarrito(index);
    }
}

function eliminarDelCarrito(index) {
    const item = carrito[index];
    carrito.splice(index, 1);
    mostrarNotificacion(`${item.nombre_producto} eliminado`, 'info');
    renderizarCarrito();
    actualizarTotales();
}

function renderizarCarrito() {
    const tbody = document.getElementById('pos-carrito-body');
    const vacio = document.getElementById('pos-carrito-vacio');

    if (carrito.length === 0) {
        tbody.innerHTML = '';
        vacio.style.display = 'block';
        return;
    }

    vacio.style.display = 'none';

    tbody.innerHTML = carrito.map((item, i) => {
        const sub = item.precio * item.cantidad;
        return `
            <tr>
                <td>
                    <strong style="color:var(--verde-oscuro);">${escapeHtml(item.nombre_producto)}</strong><br>
                    <small class="text-muted">${escapeHtml(item.codigo_barras || '')}</small>
                </td>
                <td class="text-center">$${item.precio.toFixed(2)}</td>
                <td class="text-center">
                    <button class="pos-btn-cantidad" onclick="disminuirCantidad(${i})">−</button>
                    <span class="pos-cantidad-display">${item.cantidad}</span>
                    <button class="pos-btn-cantidad" onclick="aumentarCantidad(${i})">+</button>
                </td>
                <td class="text-center fw-bold" style="color:var(--verde-oscuro);">$${sub.toFixed(2)}</td>
                <td class="text-center">
                    <button class="pos-btn-eliminar" onclick="eliminarDelCarrito(${i})" title="Eliminar">✕</button>
                </td>
            </tr>
        `;
    }).join('');
}

// ══════════════════════════════════════════════════════════════
//  CÁLCULOS TOTALES
// ══════════════════════════════════════════════════════════════

function calcularSubtotal() {
    return carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
}

function actualizarTotales() {
    const subtotal = calcularSubtotal();
    const iva = subtotal * IVA_PCT;
    const total = subtotal + iva;

    document.getElementById('pos-subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('pos-iva').textContent = `$${iva.toFixed(2)}`;
    document.getElementById('pos-total').textContent = `$${total.toFixed(2)}`;

    calcularCambio();
    actualizarBotonProcesar();
}

function calcularCambio() {
    const subtotal = calcularSubtotal();
    const iva = subtotal * IVA_PCT;
    const total = subtotal + iva;
    const pago = parseFloat(document.getElementById('pos-monto-pago').value) || 0;
    const cambio = pago - total;

    const contenedor = document.getElementById('pos-cambio');
    const valor = document.getElementById('pos-cambio-valor');

    contenedor.classList.remove('negativo', 'positivo');

    if (pago <= 0) {
        valor.textContent = '$0.00';
    } else if (cambio < 0) {
        contenedor.classList.add('negativo');
        valor.textContent = `-$${Math.abs(cambio).toFixed(2)}`;
    } else {
        contenedor.classList.add('positivo');
        valor.textContent = `$${cambio.toFixed(2)}`;
    }
}

function actualizarBotonProcesar() {
    const btn = document.getElementById('pos-btn-procesar');
    const subtotal = calcularSubtotal();
    const pago = parseFloat(document.getElementById('pos-monto-pago').value) || 0;
    const total = subtotal + (subtotal * IVA_PCT);

    btn.disabled = carrito.length === 0 || pago < total;
}

function pagarRapido(monto) {
    document.getElementById('pos-monto-pago').value = monto.toFixed(2);
    calcularCambio();
    actualizarBotonProcesar();
}

// ══════════════════════════════════════════════════════════════
//  BÚSQUEDA Y SELECCIÓN DE CLIENTES
// ══════════════════════════════════════════════════════════════

async function buscarClientes(query) {
    try {
        const resp = await fetch(`${API}?action=buscar_cliente&q=${encodeURIComponent(query)}`);
        const clientes = await resp.json();
        mostrarResultadosCliente(clientes);
    } catch (err) {
        console.error('Error buscando clientes:', err);
    }
}

function mostrarResultadosCliente(clientes) {
    const contenedor = document.getElementById('pos-resultados-cliente');
    if (!clientes || clientes.length === 0) {
        contenedor.innerHTML = '<div class="cliente-item" style="color:#999;cursor:default;">No se encontraron clientes</div>';
        contenedor.style.display = 'block';
        return;
    }

    contenedor.innerHTML = clientes.map(c => `
        <div class="cliente-item" data-id="${c.id}" data-nombre="${escapeHtml(c.nombre)}" data-cedula="${escapeHtml(c.cedula || '')}">
            <strong>${escapeHtml(c.nombre)}</strong><br>
            <small class="text-muted">Cédula: ${escapeHtml(c.cedula || 'N/A')} | Tel: ${escapeHtml(c.telefono || 'N/A')}</small>
        </div>
    `).join('');
    contenedor.style.display = 'block';

    contenedor.querySelectorAll('.cliente-item[data-id]').forEach(item => {
        item.addEventListener('click', () => {
            const id = parseInt(item.dataset.id);
            const nombre = item.dataset.nombre;
            const cedula = item.dataset.cedula;
            seleccionarCliente(id, nombre, cedula);
        });
    });
}

function ocultarResultadosCliente() {
    document.getElementById('pos-resultados-cliente').style.display = 'none';
}

function seleccionarCliente(id, nombre, cedula) {
    clienteSeleccionado = { id, nombre, cedula };

    const areaBusqueda = document.getElementById('pos-cliente-area');
    const areaSeleccion = document.getElementById('pos-cliente-seleccionado');

    areaBusqueda.style.display = 'none';
    areaSeleccion.style.display = 'block';
    areaSeleccion.innerHTML = `
        <div class="pos-cliente-seleccionado">
            <div class="cliente-info">
                👤 ${escapeHtml(nombre)}<br>
                <small class="text-muted">Cédula: ${escapeHtml(cedula || 'N/A')}</small>
            </div>
            <button class="btn-quitar-cliente" onclick="quitarCliente()">✕</button>
        </div>
    `;
}

function seleccionarConsumidorFinal() {
    clienteSeleccionado = null;

    const areaBusqueda = document.getElementById('pos-cliente-area');
    const areaSeleccion = document.getElementById('pos-cliente-seleccionado');

    areaBusqueda.style.display = 'none';
    areaSeleccion.style.display = 'block';
    areaSeleccion.innerHTML = `
        <div class="pos-cliente-seleccionado">
            <div class="cliente-info">⚡ Consumidor Final</div>
            <button class="btn-quitar-cliente" onclick="quitarCliente()">✕</button>
        </div>
    `;
}

function quitarCliente() {
    clienteSeleccionado = null;
    document.getElementById('pos-cliente-area').style.display = 'block';
    document.getElementById('pos-cliente-seleccionado').style.display = 'none';
    document.getElementById('pos-busqueda-cliente').value = '';
}

// ══════════════════════════════════════════════════════════════
//  PROCESAR VENTA
// ══════════════════════════════════════════════════════════════

async function procesarVenta() {
    if (carrito.length === 0) {
        mostrarNotificacion('El carrito está vacío', 'warning');
        return;
    }

    const subtotal = calcularSubtotal();
    const iva = subtotal * IVA_PCT;
    const total = subtotal + iva;
    const pago = parseFloat(document.getElementById('pos-monto-pago').value) || 0;

    if (pago < total) {
        mostrarNotificacion('El monto de pago es insuficiente', 'warning');
        return;
    }

    const cambio = pago - total;

    // Preparar datos de la venta
    const datosVenta = {
        cliente_id: clienteSeleccionado ? clienteSeleccionado.id : 7,
        items: carrito.map(item => ({
            id: item.id,
            cantidad: item.cantidad,
            precio: item.precio,
        })),
        subtotal: subtotal,
        iva: iva,
        total: total,
        monto_pago: pago,
        cambio: cambio,
    };

    const btn = document.getElementById('pos-btn-procesar');
    btn.disabled = true;
    btn.textContent = '⏳ PROCESANDO...';

    try {
        const resp = await fetch(`${API}?action=procesar_venta`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datosVenta),
        });

        const resultado = await resp.json();

        if (resp.ok && resultado.success) {
            mostrarNotificacion(`✅ Venta #${resultado.venta_id} procesada exitosamente`, 'success');

            // Abrir recibo en nueva ventana
            window.open(`/generar_recibo.php?venta_id=${resultado.venta_id}`, '_blank');

            // Limpiar el POS
            reiniciarPOS();
        } else {
            mostrarNotificacion(`❌ Error: ${resultado.error || 'Error desconocido'}`, 'danger');
            btn.disabled = false;
            btn.textContent = '💳 PROCESAR VENTA';
        }
    } catch (err) {
        console.error('Error procesando venta:', err);
        mostrarNotificacion('❌ Error de conexión al procesar la venta', 'danger');
        btn.disabled = false;
        btn.textContent = '💳 PROCESAR VENTA';
    }
}

function reiniciarPOS() {
    carrito = [];
    clienteSeleccionado = null;
    renderizarCarrito();
    actualizarTotales();

    document.getElementById('pos-monto-pago').value = '';
    document.getElementById('pos-busqueda').value = '';

    quitarCliente();

    const btn = document.getElementById('pos-btn-procesar');
    btn.disabled = true;
    btn.textContent = '💳 PROCESAR VENTA';

    document.getElementById('pos-busqueda').focus();
}

// ══════════════════════════════════════════════════════════════
//  NOTIFICACIONES (Toast)
// ══════════════════════════════════════════════════════════════

function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear contenedor de toasts si no existe
    let toastContainer = document.getElementById('pos-toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'pos-toast-container';
        toastContainer.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
        document.body.appendChild(toastContainer);
    }

    const colores = {
        success: '#1b4332',
        warning: '#f59e0b',
        danger: '#dc3545',
        info: '#0d6efd',
    };

    const toast = document.createElement('div');
    toast.style.cssText = `
        padding: 12px 20px;
        border-radius: 8px;
        color: #fff;
        font-weight: 600;
        font-size: 0.9em;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        animation: slideIn 0.3s ease;
        max-width: 350px;
        cursor: pointer;
    `;
    toast.style.backgroundColor = colores[tipo] || colores.info;
    toast.textContent = mensaje;

    toast.addEventListener('click', () => toast.remove());

    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ══════════════════════════════════════════════════════════════
//  ESCÁNER DE CÓDIGOS DE BARRAS (CÁMARA)
// ══════════════════════════════════════════════════════════════

let html5QrCode = null;
let scannerActivo = false;
let cerrandoScanner = false;

function abrirScanner() {
    try {
        const contenedor = document.getElementById('scanner-reader');
        contenedor.innerHTML = '<div class="scanner-loading">Solicitando acceso a la cámara...</div>';

        const resultado = document.getElementById('scanner-resultado');
        if (resultado) resultado.style.display = 'none';

        const btnScan = document.getElementById('btn-abrir-scanner');
        if (btnScan) btnScan.disabled = true;

        const modal = new bootstrap.Modal(document.getElementById('modalScanner'));
        modal.show();

        if (typeof Html5Qrcode === 'undefined') {
            contenedor.innerHTML = '';
            mostrarResultadoScanner('Error: La librería del escáner no se cargó. Recargue la página.', 'danger');
            return;
        }

        navigator.mediaDevices.getUserMedia({ video: true }).then(stream => {
            stream.getTracks().forEach(t => t.stop());

            setTimeout(iniciarLectura, 300);
        }).catch(err => {
            console.error('Permiso de cámara denegado:', err);
            contenedor.innerHTML = '';
            mostrarResultadoScanner('Permiso de cámara denegado. Habilite el acceso a la cámara en la configuración del navegador.', 'danger');
        });
    } catch (err) {
        console.error('Error al abrir scanner:', err);
        mostrarResultadoScanner('Error inesperado al abrir el escáner: ' + err.message, 'danger');
    }
}

function iniciarLectura() {
    const contenedor = document.getElementById('scanner-reader');
    contenedor.innerHTML = '<div class="scanner-loading">Iniciando cámara...</div>';

    if (html5QrCode) {
        try { html5QrCode.clear(); } catch(e) {}
        html5QrCode = null;
    }

    const config = {
        fps: 10,
        qrbox: { width: 280, height: 120 },
        aspectRatio: 1.5,
        formatsToSupport: [
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.UPC_A,
            Html5QrcodeSupportedFormats.UPC_E,
            Html5QrcodeSupportedFormats.ITF,
            Html5QrcodeSupportedFormats.CODABAR,
        ]
    };

    Html5Qrcode.getCameras().then(cameras => {
        if (!cameras || cameras.length === 0) {
            contenedor.innerHTML = '';
            mostrarResultadoScanner('No se encontraron cámaras en este dispositivo.', 'danger');
            return;
        }

        let cameraId = cameras[0].id;

        for (let i = 0; i < cameras.length; i++) {
            const label = (cameras[i].label || '').toLowerCase();
            if (label.includes('back') || label.includes('rear') || label.includes('environment')) {
                cameraId = cameras[i].id;
                break;
            }
        }

        contenedor.innerHTML = '';
        html5QrCode = new Html5Qrcode('scanner-reader');

        html5QrCode.start(cameraId, config, onScanSuccess, () => {}).then(() => {
            scannerActivo = true;
        }).catch(err => {
            console.error('Error al iniciar cámara:', err);
            contenedor.innerHTML = '';
            mostrarResultadoScanner('Error al iniciar la cámara: ' + err, 'danger');
        });
    }).catch(err => {
        console.error('Error al obtener lista de cámaras:', err);
        contenedor.innerHTML = '';
        mostrarResultadoScanner('No se pudo acceder a la cámara: ' + err, 'danger');
    });
}

function onScanSuccess(codigoDetectado) {
    if (!scannerActivo) return;

    scannerActivo = false;

    mostrarResultadoScanner(`Código detectado: <strong>${codigoDetectado}</strong>`, 'info');

    if (html5QrCode && html5QrCode.isScanning) {
        html5QrCode.stop().then(() => {
            procesarCodigoEscaneado(codigoDetectado);
        }).catch(() => {
            procesarCodigoEscaneado(codigoDetectado);
        });
    } else {
        procesarCodigoEscaneado(codigoDetectado);
    }
}

async function procesarCodigoEscaneado(codigo) {
    try {
        const resp = await fetch(`${API}?action=buscar_por_codigo&codigo=${encodeURIComponent(codigo)}`);
        if (resp.ok) {
            const producto = await resp.json();
            agregarAlCarrito(producto);
            mostrarResultadoScanner(`✅ ${producto.nombre_producto} agregado al carrito`, 'success');
        } else {
            mostrarResultadoScanner(`❌ Código "${codigo}" no encontrado en el catálogo`, 'danger');
        }
    } catch (err) {
        mostrarResultadoScanner('Error de conexión al buscar el producto', 'danger');
    }

    setTimeout(() => {
        const modalEl = document.getElementById('modalScanner');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        document.getElementById('pos-busqueda').value = '';
        document.getElementById('pos-busqueda').focus();
        document.getElementById('scanner-resultado').style.display = 'none';
    }, 1500);
}

function mostrarResultadoScanner(html, tipo) {
    const el = document.getElementById('scanner-resultado');
    const colores = { success: '#d4edda', danger: '#f8d7da', info: '#d1ecf1' };
    el.style.display = 'block';
    el.style.background = colores[tipo] || colores.info;
    el.innerHTML = html;
}

function cerrarScanner() {
    if (cerrandoScanner) return;
    cerrandoScanner = true;
    scannerActivo = false;

    if (html5QrCode) {
        if (html5QrCode.isScanning) {
            html5QrCode.stop().then(() => {
                html5QrCode.clear();
                html5QrCode = null;
            }).catch(() => {
                try { html5QrCode.clear(); } catch(e) {}
                html5QrCode = null;
            });
        } else {
            try { html5QrCode.clear(); } catch(e) {}
            html5QrCode = null;
        }
    }

    const btnScan = document.getElementById('btn-abrir-scanner');
    if (btnScan) btnScan.disabled = false;

    const modalEl = document.getElementById('modalScanner');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) {
        modal.hide();
    }

    document.getElementById('pos-busqueda').value = '';
    document.getElementById('pos-busqueda').focus();
    document.getElementById('scanner-resultado').style.display = 'none';
    document.getElementById('scanner-reader').innerHTML = '';

    setTimeout(() => { cerrandoScanner = false; }, 500);
}

// ══════════════════════════════════════════════════════════════
//  UTILIDADES
// ══════════════════════════════════════════════════════════════

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

// Agregar estilos de animación para toasts
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;
document.head.appendChild(style);
