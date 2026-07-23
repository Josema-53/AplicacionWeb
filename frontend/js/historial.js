const API_HISTORIAL = '/api_historial.php';

document.addEventListener('DOMContentLoaded', () => {
    cargarDatos();

    document.getElementById('btn-buscar').addEventListener('click', cargarDatos);
    document.getElementById('btn-limpiar').addEventListener('click', limpiarFiltros);

    document.getElementById('filtro-fecha-inicio').addEventListener('change', cargarDatos);
    document.getElementById('filtro-fecha-fin').addEventListener('change', cargarDatos);

    let debounceCliente;
    document.getElementById('filtro-cliente').addEventListener('input', () => {
        clearTimeout(debounceCliente);
        debounceCliente = setTimeout(cargarDatos, 400);
    });

    let debounceFactura;
    document.getElementById('filtro-factura').addEventListener('input', () => {
        clearTimeout(debounceFactura);
        debounceFactura = setTimeout(cargarDatos, 400);
    });
});

function obtenerFiltros() {
    return {
        fecha_inicio: document.getElementById('filtro-fecha-inicio').value,
        fecha_fin:    document.getElementById('filtro-fecha-fin').value,
        cliente:      document.getElementById('filtro-cliente').value.trim(),
        factura:      document.getElementById('filtro-factura').value.trim()
    };
}

function cargarDatos() {
    const f = obtenerFiltros();
    const params = new URLSearchParams(f);

    Promise.all([
        fetch(`${API_HISTORIAL}?action=obtener_resumen&${params}`).then(r => r.json()),
        fetch(`${API_HISTORIAL}?action=buscar_facturas&${params}`).then(r => r.json())
    ]).then(([resumen, facturas]) => {
        renderizarResumen(resumen);
        renderizarTabla(facturas);
    }).catch(err => {
        console.error('Error al cargar datos:', err);
        mostrarNotificacion('Error al cargar los datos del historial', 'danger');
    });
}

function renderizarResumen(r) {
    document.getElementById('stat-total').textContent = '$' + parseFloat(r.total_vendido).toFixed(2);
    document.getElementById('stat-cantidad').textContent = r.cantidad_facturas;
    document.getElementById('stat-promedio').textContent = '$' + parseFloat(r.ticket_promedio).toFixed(2);
}

function renderizarTabla(lista) {
    const tbody = document.getElementById('cuerpo-tabla-historial');
    if (!lista || lista.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">
            <div style="font-size:2.5em; opacity:0.3;">📋</div>
            <p class="mt-2">No se encontraron facturas con los filtros aplicados</p>
        </td></tr>`;
        return;
    }

    tbody.innerHTML = lista.map(v => {
        const fecha = new Date(v.fecha_emision).toLocaleString('es-EC', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
        const esAnulada = v.estado === 'Anulada';
        const badgeClass = esAnulada ? 'bg-danger' : 'bg-success';
        const badgeText = esAnulada ? 'Anulada' : 'Pagada';
        const total = parseFloat(v.total_factura).toFixed(2);
        const cliente = v.cliente_nombre ? v.cliente_nombre : '<span class="text-muted fst-italic">Consumidor Final</span>';
        const vendedor = v.vendedor_nombre || '<span class="text-muted">-</span>';

        return `<tr class="${esAnulada ? 'table-danger' : ''}">
            <td><strong>#${v.id}</strong></td>
            <td>${fecha}</td>
            <td>${cliente}</td>
            <td>${vendedor}</td>
            <td class="text-end fw-bold">$${total}</td>
            <td class="text-center"><span class="badge ${badgeClass}">${badgeText}</span></td>
            <td class="text-center text-nowrap">
                <button class="btn btn-sm btn-info text-white" onclick="verDetalles(${v.id})" title="Ver Detalles">👁</button>
                <button class="btn btn-sm btn-secondary" onclick="reimprimir(${v.id})" title="Re-imprimir" ${esAnulada ? 'disabled' : ''}>🖨</button>
                <button class="btn btn-sm btn-danger" onclick="anularFactura(${v.id})" title="Anular Factura" ${esAnulada ? 'disabled' : ''}>🚫</button>
            </td>
        </tr>`;
    }).join('');
}

function limpiarFiltros() {
    document.getElementById('filtro-fecha-inicio').value = '';
    document.getElementById('filtro-fecha-fin').value = '';
    document.getElementById('filtro-cliente').value = '';
    document.getElementById('filtro-factura').value = '';
    cargarDatos();
}

let modalDetallesInstance = null;

function verDetalles(ventaId) {
    const btn = event.currentTarget;
    btn.disabled = true;
    btn.textContent = '...';

    fetch(`${API_HISTORIAL}?action=obtener_detalle&venta_id=${ventaId}`)
        .then(r => {
            if (!r.ok) return r.json().then(d => { throw new Error(d.error || 'Error'); });
            return r.json();
        })
        .then(data => {
            const v = data.venta;
            const detalles = data.detalles;
            const esAnulada = v.estado === 'Anulada';

            const fecha = new Date(v.fecha_emision).toLocaleString('es-EC', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            });

            document.getElementById('detalle-num-factura').textContent = '#' + v.id;

            const badgeEl = document.getElementById('detalle-estado-badge');
            badgeEl.className = `badge ${esAnulada ? 'bg-danger' : 'bg-success'}`;
            badgeEl.textContent = esAnulada ? 'ANULADA' : 'PAGADA';

            document.getElementById('detalle-fecha').textContent = fecha;
            document.getElementById('detalle-vendedor').textContent = v.vendedor_nombre || '-';
            document.getElementById('detalle-cliente').textContent = v.cliente_nombre || 'Consumidor Final';
            document.getElementById('detalle-cedula').textContent = v.cliente_cedula || '-';
            document.getElementById('detalle-telefono').textContent = v.cliente_telefono || '-';
            document.getElementById('detalle-correo').textContent = v.cliente_correo || '-';

            document.getElementById('detalle-subtotal').textContent = '$' + parseFloat(v.subtotal).toFixed(2);
            document.getElementById('detalle-iva').textContent = '$' + parseFloat(v.iva).toFixed(2);
            document.getElementById('detalle-total').textContent = '$' + parseFloat(v.total_factura).toFixed(2);
            document.getElementById('detalle-pago').textContent = '$' + parseFloat(v.monto_pago).toFixed(2);
            document.getElementById('detalle-cambio').textContent = '$' + parseFloat(v.cambio).toFixed(2);

            const tbodyDet = document.getElementById('detalle-productos-body');
            tbodyDet.innerHTML = detalles.map(d => `
                <tr>
                    <td>${d.codigo_barras}</td>
                    <td>${d.nombre_producto}</td>
                    <td class="text-center">${d.cantidad}</td>
                    <td class="text-end">$${parseFloat(d.precio_congelado).toFixed(2)}</td>
                    <td class="text-end fw-bold">$${parseFloat(d.subtotal).toFixed(2)}</td>
                </tr>
            `).join('');

            const modalEl = document.getElementById('modalDetalles');
            if (!modalDetallesInstance) {
                modalDetallesInstance = new bootstrap.Modal(modalEl);
            }
            modalDetallesInstance.show();
        })
        .catch(err => {
            console.error('Error al obtener detalles:', err);
            mostrarNotificacion('Error al cargar los detalles: ' + err.message, 'danger');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = '👁';
        });
}

function reimprimir(ventaId) {
    window.open('/generar_recibo.php?venta_id=' + ventaId, '_blank');
}

function anularFactura(ventaId) {
    if (!confirm(`¿Está seguro de anular la factura #${ventaId}?\n\nEsta acción:\n• Cambiará el estado a "Anulada"\n• Devolverá el stock de todos los productos al inventario\n• Esta acción es IRREVERSIBLE`)) {
        return;
    }

    fetch(`${API_HISTORIAL}?action=anular_venta`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ venta_id: ventaId })
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (!ok) {
            mostrarNotificacion(data.error || 'Error al anular la factura', 'danger');
            return;
        }
        mostrarNotificacion(data.mensaje, 'success');
        cargarDatos();
    })
    .catch(err => {
        console.error('Error al anular:', err);
        mostrarNotificacion('Error de conexión al anular la factura', 'danger');
    });
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    let container = document.getElementById('notif-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notif-container';
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
        document.body.appendChild(container);
    }

    const colores = { success: '#28a745', danger: '#dc3545', warning: '#ffc107', info: '#17a2b8' };
    const notif = document.createElement('div');
    notif.style.cssText = `padding:14px 20px;border-radius:8px;color:#fff;font-weight:600;box-shadow:0 4px 12px rgba(0,0,0,0.2);min-width:280px;opacity:0;transition:opacity 0.3s;background:${colores[tipo] || colores.info};`;
    notif.textContent = mensaje;
    container.appendChild(notif);

    requestAnimationFrame(() => notif.style.opacity = '1');
    setTimeout(() => {
        notif.style.opacity = '0';
        setTimeout(() => notif.remove(), 300);
    }, 3500);
}
