const API = '/api_clientes.php';
let clientes = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarClientes();
    document.getElementById('input-busqueda').addEventListener('input', filtrarClientes);

    const nombreInput = document.getElementById('cli-nombre');
    nombreInput.addEventListener('input', function () {
        this.value = this.value.replace(/[0-9]/g, '');
        validarNombreVisual(this);
    });

    document.getElementById('cli-cedula').addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
    });
    document.getElementById('cli-cedula').addEventListener('blur', function () {
        validarCedulaVisual(this);
    });

    document.getElementById('cli-telefono').addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
    });
    document.getElementById('cli-telefono').addEventListener('blur', function () {
        validarTelefonoVisual(this);
    });

    document.getElementById('cli-correo').addEventListener('blur', function () {
        validarCorreoVisual(this);
    });
});

function cargarClientes() {
    fetch(API)
        .then(res => res.json())
        .then(data => {
            clientes = data;
            renderizarTabla(clientes);
        })
        .catch(err => console.error('Error al cargar clientes:', err));
}

function renderizarTabla(lista) {
    const tbody = document.getElementById('cuerpo-tabla');
    if (lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No se encontraron clientes</td></tr>';
        return;
    }
    tbody.innerHTML = lista.map(c => {
        const esConsumidor = c.nombre_completo === 'CONSUMIDOR FINAL';
        return `
        <tr class="${esConsumidor ? 'table-light' : ''}">
            <td><strong>${c.cedula}</strong></td>
            <td>
                <strong>${c.nombre_completo}</strong>
                ${esConsumidor ? '<span class="badge bg-secondary ms-2">DEFAULT</span>' : ''}
            </td>
            <td>${c.correo || '-'}</td>
            <td>${c.telefono || '-'}</td>
            <td>
                ${esConsumidor
                    ? '<span class="text-muted fst-italic">Sistema</span>'
                    : `<button class="btn btn-sm btn-warning" onclick="editarCliente(${c.id})">EDITAR</button>
                       <button class="btn btn-sm btn-danger" onclick="eliminarCliente(${c.id})">ELIMINAR</button>`
                }
            </td>
            <td>
                ${esConsumidor
                    ? '<span class="text-muted fst-italic">-</span>'
                    : `<button class="btn btn-sm btn-info text-white" onclick="verEstadisticas(${c.id}, '${c.nombre_completo.replace(/'/g, "\\'")}')">📊 VER</button>`
                }
            </td>
        </tr>`;
    }).join('');
}

function filtrarClientes() {
    const q = document.getElementById('input-busqueda').value.toLowerCase();
    const filtrados = clientes.filter(c =>
        c.nombre_completo.toLowerCase().includes(q) ||
        c.cedula.toLowerCase().includes(q) ||
        (c.correo && c.correo.toLowerCase().includes(q)) ||
        (c.telefono && c.telefono.toLowerCase().includes(q))
    );
    renderizarTabla(filtrados);
}

function abrirModal(cliente = null) {
    document.getElementById('modalTitulo').textContent = cliente ? 'Editar Cliente' : 'Nuevo Cliente';
    document.getElementById('cli-id').value = cliente ? cliente.id : '';
    document.getElementById('cli-nombre').value = cliente ? cliente.nombre_completo : '';
    document.getElementById('cli-cedula').value = cliente ? cliente.cedula : '';
    document.getElementById('cli-correo').value = cliente ? (cliente.correo || '') : '';
    document.getElementById('cli-telefono').value = cliente ? (cliente.telefono || '') : '';

    ['cli-nombre', 'cli-cedula', 'cli-correo', 'cli-telefono'].forEach(id => {
        const el = document.getElementById(id);
        el.classList.remove('is-invalid');
        const fb = el.parentElement.querySelector('.invalid-feedback');
        if (fb) fb.textContent = '';
    });

    new bootstrap.Modal(document.getElementById('modalCliente')).show();
}

function mostrarError(input, mensaje) {
    input.classList.add('is-invalid');
    let fb = input.parentElement.querySelector('.invalid-feedback');
    if (!fb) {
        fb = document.createElement('div');
        fb.className = 'invalid-feedback';
        input.parentElement.appendChild(fb);
    }
    fb.textContent = mensaje;
}

function limpiarError(input) {
    input.classList.remove('is-invalid');
    const fb = input.parentElement.querySelector('.invalid-feedback');
    if (fb) fb.textContent = '';
}

function validarNombreVisual(input) {
    const valor = input.value.trim();
    if (valor.length === 0) { limpiarError(input); return false; }
    if (valor.length < 2) { mostrarError(input, 'El nombre debe tener al menos 2 letras.'); return false; }
    limpiarError(input);
    return true;
}

function validarCorreoVisual(input) {
    const valor = input.value.trim();
    if (valor.length === 0) { limpiarError(input); return true; }
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!regex.test(valor)) { mostrarError(input, 'Ingrese un correo electrónico válido (ej: usuario@dominio.com).'); return false; }
    limpiarError(input);
    return true;
}

function validarTelefonoVisual(input) {
    const valor = input.value.trim();
    if (valor.length === 0) { limpiarError(input); return true; }
    if (!/^\d{10}$/.test(valor)) { mostrarError(input, 'El teléfono debe tener exactamente 10 dígitos.'); return false; }
    if (!valor.startsWith('0')) { mostrarError(input, 'El teléfono debe comenzar con 0 (ej: 0991234567).'); return false; }
    limpiarError(input);
    return true;
}

function validarCedulaEcuatoriana(cedula) {
    cedula = cedula.trim();

    if (!/^\d{10}$/.test(cedula)) {
        return { valida: false, mensaje: 'La cédula debe tener exactamente 10 dígitos.' };
    }

    const provincia = parseInt(cedula.substring(0, 2));
    if (provincia < 1 || provincia > 24 && provincia !== 30) {
        return { valida: false, mensaje: 'Los dos primeros dígitos no corresponden a una provincia válida (01-24, 30).' };
    }

    const tercerDigito = parseInt(cedula.charAt(2));
    if (tercerDigito > 5) {
        return { valida: false, mensaje: 'El tercer dígito debe ser menor a 6.' };
    }

    const pesos = [2, 1, 2, 1, 2, 1, 2, 1, 2];
    let suma = 0;

    for (let i = 0; i < 9; i++) {
        let producto = parseInt(cedula.charAt(i)) * pesos[i];
        if (producto >= 10) producto -= 9;
        suma += producto;
    }

    const digitoVerificador = parseInt(cedula.charAt(9));
    const residuo = suma % 10;
    const resultado = residuo === 0 ? 0 : 10 - residuo;

    if (resultado !== digitoVerificador) {
        return { valida: false, mensaje: `Cédula inválida. Dígito verificador incorrecto (debería ser ${resultado}).` };
    }

    return { valida: true, mensaje: '' };
}

function validarCedulaVisual(input) {
    const valor = input.value.trim();
    if (valor.length === 0) { limpiarError(input); return true; }
    const resultado = validarCedulaEcuatoriana(valor);
    if (!resultado.valida) { mostrarError(input, resultado.mensaje); return false; }
    limpiarError(input);
    return true;
}

function guardarCliente() {
    const id = document.getElementById('cli-id').value;
    const nombreInput = document.getElementById('cli-nombre');
    const cedulaInput = document.getElementById('cli-cedula');
    const correoInput = document.getElementById('cli-correo');
    const telefonoInput = document.getElementById('cli-telefono');

    const datos = {
        nombre_completo: nombreInput.value.trim(),
        cedula:          cedulaInput.value.trim(),
        correo:          correoInput.value.trim(),
        telefono:        telefonoInput.value.trim()
    };

    if (!datos.nombre_completo || !datos.cedula) {
        alert('Nombre y cédula son obligatorios.');
        return;
    }

    if (!validarNombreVisual(nombreInput)) {
        nombreInput.focus();
        return;
    }

    if (!validarCedulaVisual(cedulaInput)) {
        cedulaInput.focus();
        return;
    }

    if (!validarCorreoVisual(correoInput)) {
        alert('El correo electrónico no es válido.');
        correoInput.focus();
        return;
    }

    if (!validarTelefonoVisual(telefonoInput)) {
        alert('El número de teléfono no es válido.');
        telefonoInput.focus();
        return;
    }

    const esEdicion = id !== '';
    if (esEdicion) datos.id = parseInt(id);

    fetch(API, {
        method: esEdicion ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
    .then(res => res.json().then(body => ({ ok: res.ok, body })))
    .then(({ ok, body }) => {
        if (!ok) {
            alert(body.error || 'Error al guardar el cliente.');
            return;
        }
        bootstrap.Modal.getInstance(document.getElementById('modalCliente')).hide();
        cargarClientes();
    })
    .catch(err => {
        console.error('Error al guardar:', err);
        alert('Error de conexión al guardar el cliente.');
    });
}

function editarCliente(id) {
    const cliente = clientes.find(c => c.id === id);
    if (cliente) abrirModal(cliente);
}

function eliminarCliente(id) {
    if (!confirm('¿Estás seguro de eliminar este cliente?')) return;
    fetch(API, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    })
    .then(res => res.json().then(body => ({ ok: res.ok, body })))
    .then(({ ok, body }) => {
        if (!ok) {
            alert(body.error || 'Error al eliminar el cliente.');
            return;
        }
        cargarClientes();
    })
    .catch(err => {
        console.error('Error al eliminar:', err);
        alert('Error de conexión al eliminar el cliente.');
    });
}

let modalEstadisticasInstance = null;

function verEstadisticas(clienteId, nombre) {
    document.getElementById('est-cli-nombre').textContent = nombre;
    document.getElementById('est-total-compras').textContent = '...';
    document.getElementById('est-total-gastado').textContent = '...';
    document.getElementById('est-promedio').textContent = '...';
    document.getElementById('est-primera-compra').textContent = '...';
    document.getElementById('est-ultima-compra').textContent = '...';
    document.getElementById('est-productos-body').innerHTML = '<tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>';
    document.getElementById('est-historial-body').innerHTML = '<tr><td colspan="6" class="text-center text-muted">Cargando...</td></tr>';

    if (!modalEstadisticasInstance) {
        modalEstadisticasInstance = new bootstrap.Modal(document.getElementById('modalEstadisticas'));
    }
    modalEstadisticasInstance.show();

    fetch(`${API}?action=estadisticas&cliente_id=${clienteId}`)
        .then(res => {
            if (!res.ok) return res.json().then(d => { throw new Error(d.error || 'Error'); });
            return res.json();
        })
        .then(data => {
            const r = data.resumen;
            document.getElementById('est-total-compras').textContent = r.total_compras;
            document.getElementById('est-total-gastado').textContent = '$' + parseFloat(r.total_gastado).toFixed(2);
            document.getElementById('est-promedio').textContent = '$' + parseFloat(r.promedio_factura).toFixed(2);

            document.getElementById('est-primera-compra').textContent = r.primera_compra
                ? new Date(r.primera_compra).toLocaleDateString('es-EC', { day: '2-digit', month: '2-digit', year: 'numeric' })
                : '-';
            document.getElementById('est-ultima-compra').textContent = r.ultima_compra
                ? new Date(r.ultima_compra).toLocaleDateString('es-EC', { day: '2-digit', month: '2-digit', year: 'numeric' })
                : '-';

            const tbodyProd = document.getElementById('est-productos-body');
            if (data.productos.length === 0) {
                tbodyProd.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Este cliente no ha comprado productos aún</td></tr>';
            } else {
                tbodyProd.innerHTML = data.productos.map(p => `
                    <tr>
                        <td>${p.codigo_barras}</td>
                        <td><strong>${p.nombre_producto}</strong></td>
                        <td class="text-center"><span class="badge bg-success">${p.veces_comprado}</span></td>
                        <td class="text-end">$${parseFloat(p.precio_promedio).toFixed(2)}</td>
                        <td class="text-end fw-bold">$${parseFloat(p.total_gastado_producto).toFixed(2)}</td>
                    </tr>
                `).join('');
            }

            const tbodyHist = document.getElementById('est-historial-body');
            if (data.historial.length === 0) {
                tbodyHist.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Sin historial de compras</td></tr>';
            } else {
                tbodyHist.innerHTML = data.historial.map(v => {
                    const fecha = new Date(v.fecha_emision).toLocaleString('es-EC', {
                        day: '2-digit', month: '2-digit', year: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });
                    const esAnulada = v.estado === 'Anulada';
                    const badgeClass = esAnulada ? 'bg-danger' : 'bg-success';
                    const badgeText = esAnulada ? 'Anulada' : 'Pagada';
                    return `
                        <tr class="${esAnulada ? 'table-danger' : ''}">
                            <td><strong>#${v.id}</strong></td>
                            <td>${fecha}</td>
                            <td class="text-center"><span class="badge ${badgeClass}">${badgeText}</span></td>
                            <td class="text-end">$${parseFloat(v.subtotal).toFixed(2)}</td>
                            <td class="text-end">$${parseFloat(v.iva).toFixed(2)}</td>
                            <td class="text-end fw-bold">$${parseFloat(v.total_factura).toFixed(2)}</td>
                        </tr>
                    `;
                }).join('');
            }
        })
        .catch(err => {
            console.error('Error al cargar estadísticas:', err);
            document.getElementById('est-productos-body').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error al cargar datos</td></tr>';
            document.getElementById('est-historial-body').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error al cargar datos</td></tr>';
        });
}
