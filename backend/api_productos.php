<?php
declare(strict_types=1);

//cabeceras necesarias para el intercambio de JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

//Incluir la conexión

require_once 'conexion.php';

//Capturar el metodo de GET POST

$method = $_SERVER['REQUEST_METHOD'];

//Capturamos el cuerpo de la petición para el POST, PUT

$input = json_decode(file_get_contents('php://input'), true);
try{
    switch($method) {
        case 'GET':
            //Obtener todos los productos
            $search = $_GET['q'] ?? '';
            $sql='SELECT * FROM productos WHERE nombre_producto LIKE ? OR codigo_barras LIKE ?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute(["%$search%", "%$search%"]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        case 'POST':
            //Insertar un nuevo producto
            $sql='INSERT INTO productos (codigo_barras, nombre_producto, precio_actual, stock_disponible) VALUES (:codigo_barras, :nombre_producto, :precio_actual, :stock_disponible)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':codigo_barras' => $input['codigo_barras'],
                ':nombre_producto' => $input['nombre_producto'],
                ':precio_actual' => $input['precio_actual'],
                ':stock_disponible' => $input['stock_disponible']
            ]);
            echo json_encode(['message' => 'Producto creado con éxito']);
            break;
        case 'PUT':
            //Actualizar un producto existente
            $sql='UPDATE productos SET codigo_barras = :codigo_barras, nombre_producto = :nombre_producto, precio_actual = :precio_actual, stock_disponible = :stock_disponible WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $input['id'],
                ':codigo_barras' => $input['codigo_barras'],
                ':nombre_producto' => $input['nombre_producto'],
                ':precio_actual' => $input['precio_actual'],
                ':stock_disponible' => $input['stock_disponible']
            ]);
            echo json_encode(['message' => 'Producto actualizado con éxito']);
            break;
        case 'DELETE':
            //Eliminar un producto existente
            $sql='DELETE FROM productos WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $input['id']
            ]);
            echo json_encode(['message' => 'Producto eliminado con éxito']);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
}catch(PDOException $e){
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
