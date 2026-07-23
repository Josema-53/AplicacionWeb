<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (preg_match('#^https://[a-z0-9-]+\.vercel\.app$#i', $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/../lib/conexion.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET' && $action === 'estadisticas') {
        $clienteId = (int)($_GET['cliente_id'] ?? 0);
        if ($clienteId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'cliente_id requerido']);
            exit();
        }

        $stmtResumen = $pdo->prepare("
            SELECT 
                COUNT(v.id) AS total_compras,
                COALESCE(SUM(v.total_factura), 0) AS total_gastado,
                CASE WHEN COUNT(v.id) > 0 THEN COALESCE(SUM(v.total_factura), 0) / COUNT(v.id) ELSE 0 END AS promedio_factura,
                MIN(v.fecha_emision) AS primera_compra,
                MAX(v.fecha_emision) AS ultima_compra
            FROM ventas v WHERE v.cliente_id = :cliente_id AND v.estado = 'Pagada'
        ");
        $stmtResumen->execute([':cliente_id' => $clienteId]);
        $resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC);

        $stmtProductos = $pdo->prepare("
            SELECT p.nombre_producto, p.codigo_barras,
                   SUM(dv.cantidad) AS veces_comprado,
                   SUM(dv.subtotal) AS total_gastado_producto,
                   AVG(dv.precio_congelado) AS precio_promedio
            FROM detalles_venta dv
            INNER JOIN ventas v ON dv.venta_id = v.id
            INNER JOIN productos p ON dv.producto_id = p.id
            WHERE v.cliente_id = :cliente_id AND v.estado = 'Pagada'
            GROUP BY p.id, p.nombre_producto, p.codigo_barras
            ORDER BY veces_comprado DESC
        ");
        $stmtProductos->execute([':cliente_id' => $clienteId]);
        $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

        $stmtHistorial = $pdo->prepare("
            SELECT v.id, v.fecha_emision, v.total_factura, v.estado, v.subtotal, v.iva
            FROM ventas v WHERE v.cliente_id = :cliente_id
            ORDER BY v.fecha_emision DESC LIMIT 50
        ");
        $stmtHistorial->execute([':cliente_id' => $clienteId]);
        $historial = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['resumen' => $resumen, 'productos' => $productos, 'historial' => $historial]);
        exit();
    }

    switch ($method) {
        case 'GET':
            $search = $_GET['q'] ?? '';
            $stmt = $pdo->prepare("SELECT * FROM clientes WHERE nombre_completo LIKE ? OR cedula LIKE ? OR correo LIKE ? OR telefono LIKE ?");
            $stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%"]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        case 'POST':
            $stmt = $pdo->prepare("INSERT INTO clientes (nombre_completo, cedula, correo, telefono) VALUES (:nombre_completo, :cedula, :correo, :telefono)");
            $stmt->execute([
                ':nombre_completo' => $input['nombre_completo'],
                ':cedula' => $input['cedula'],
                ':correo' => $input['correo'] ?: null,
                ':telefono' => $input['telefono'] ?: null
            ]);
            echo json_encode(['message' => 'Cliente creado con exito', 'id' => (int)$pdo->lastInsertId()]);
            break;
        case 'PUT':
            if ((int)$input['id'] === 7) {
                http_response_code(403);
                echo json_encode(['error' => 'No se puede editar el cliente CONSUMIDOR FINAL']);
                break;
            }
            $stmt = $pdo->prepare("UPDATE clientes SET nombre_completo = :nombre_completo, cedula = :cedula, correo = :correo, telefono = :telefono WHERE id = :id");
            $stmt->execute([
                ':id' => $input['id'],
                ':nombre_completo' => $input['nombre_completo'],
                ':cedula' => $input['cedula'],
                ':correo' => $input['correo'] ?: null,
                ':telefono' => $input['telefono'] ?: null
            ]);
            echo json_encode(['message' => 'Cliente actualizado con exito']);
            break;
        case 'DELETE':
            if ((int)$input['id'] === 7) {
                http_response_code(403);
                echo json_encode(['error' => 'No se puede eliminar el cliente CONSUMIDOR FINAL']);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
            $stmt->execute([':id' => $input['id']]);
            echo json_encode(['message' => 'Cliente eliminado con exito']);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Metodo no permitido']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
