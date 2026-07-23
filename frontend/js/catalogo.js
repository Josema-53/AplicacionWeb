const API = '/api_productos.php';
let productos = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarProductos();
    document.getElementById('input-busqueda').addEventListener('input', filtrarProductos);
});

function cargarProductos() {
    fetch(API)
        .then(res => res.json())
        .then(data => {
            productos = data;
            renderizarTabla(productos);
        })
        .catch(err => console.error('Error al cargar productos:', err));
}

function renderizarTabla(lista) {
    const tbody = document.getElementById('cuerpo-tabla');
    if (lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No se encontraron productos</td></tr>';
        return;
    }
    tbody.innerHTML = lista.map(p => `
        <tr>
            <td>${p.codigo_barras}</td>
            <td>${p.nombre_producto}</td>
            <td>$${parseFloat(p.precio_actual).toFixed(2)}</td>
            <td>${p.stock_disponible}</td>
            <td>
                <button class="btn btn-sm btn-warning" onclick="editarProducto(${p.id})">EDITAR</button>
                <button class="btn btn-sm btn-danger" onclick="eliminarProducto(${p.id})">ELIMINAR</button>
            </td>
        </tr>
    `).join('');
}

function filtrarProductos() {
    const q = document.getElementById('input-busqueda').value.toLowerCase();
    const filtrados = productos.filter(p =>
        p.nombre_producto.toLowerCase().includes(q) ||
        p.codigo_barras.toLowerCase().includes(q)
    );
    renderizarTabla(filtrados);
}

function abrirModal(producto = null) {
    document.getElementById('modalTitulo').textContent = producto ? 'Editar Producto' : 'Nuevo Producto';
    document.getElementById('prod-id').value = producto ? producto.id : '';
    document.getElementById('prod-codigo').value = producto ? producto.codigo_barras : '';
    document.getElementById('prod-nombre').value = producto ? producto.nombre_producto : '';
    document.getElementById('prod-precio').value = producto ? producto.precio_actual : '';
    document.getElementById('prod-stock').value = producto ? producto.stock_disponible : '';
    new bootstrap.Modal(document.getElementById('modalProducto')).show();
}

function guardarProducto() {
    const id = document.getElementById('prod-id').value;
    const datos = {
        codigo_barras: document.getElementById('prod-codigo').value,
        nombre_producto: document.getElementById('prod-nombre').value,
        precio_actual: parseFloat(document.getElementById('prod-precio').value),
        stock_disponible: parseInt(document.getElementById('prod-stock').value)
    };

    if (!datos.codigo_barras || !datos.nombre_producto || isNaN(datos.precio_actual) || isNaN(datos.stock_disponible)) {
        alert('Todos los campos son obligatorios.');
        return;
    }

    const esEdicion = id !== '';
    if (esEdicion) datos.id = parseInt(id);

    fetch(API, {
        method: esEdicion ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
    .then(res => res.json())
    .then(() => {
        bootstrap.Modal.getInstance(document.getElementById('modalProducto')).hide();
        cargarProductos();
    })
    .catch(err => console.error('Error al guardar:', err));
}

function editarProducto(id) {
    const producto = productos.find(p => p.id === id);
    if (producto) abrirModal(producto);
}

function eliminarProducto(id) {
    if (!confirm('¿Estás seguro de eliminar este producto?')) return;
    fetch(API, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    })
    .then(res => res.json())
    .then(() => cargarProductos())
    .catch(err => console.error('Error al eliminar:', err));
}
