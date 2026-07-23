<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (preg_match('#^https://[a-z0-9-]+\.vercel\.app$#i', $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/conexion.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch($method) {
        case 'GET':
            $search = $_GET['q'] ?? '';
            $stmt = $pdo->prepare("SELECT * FROM productos WHERE nombre_producto LIKE ? OR codigo_barras LIKE ?");
            $stmt->execute(["%$search%", "%$search%"]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        case 'POST':
            $stmt = $pdo->prepare("INSERT INTO productos (codigo_barras, nombre_producto, precio_actual, stock_disponible) VALUES (:codigo_barras, :nombre_producto, :precio_actual, :stock_disponible)");
            $stmt->execute([
                ':codigo_barras' => $input['codigo_barras'],
                ':nombre_producto' => $input['nombre_producto'],
                ':precio_actual' => $input['precio_actual'],
                ':stock_disponible' => $input['stock_disponible']
            ]);
            echo json_encode(['message' => 'Producto creado con exito']);
            break;
        case 'PUT':
            $stmt = $pdo->prepare("UPDATE productos SET codigo_barras = :codigo_barras, nombre_producto = :nombre_producto, precio_actual = :precio_actual, stock_disponible = :stock_disponible WHERE id = :id");
            $stmt->execute([
                ':id' => $input['id'],
                ':codigo_barras' => $input['codigo_barras'],
                ':nombre_producto' => $input['nombre_producto'],
                ':precio_actual' => $input['precio_actual'],
                ':stock_disponible' => $input['stock_disponible']
            ]);
            echo json_encode(['message' => 'Producto actualizado con exito']);
            break;
        case 'DELETE':
            $stmt = $pdo->prepare("DELETE FROM productos WHERE id = :id");
            $stmt->execute([':id' => $input['id']]);
            echo json_encode(['message' => 'Producto eliminado con exito']);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Metodo no permitido']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
