<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/auth.php';

$user = auth_get_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        case 'buscar_producto':
            $q = $_GET['q'] ?? '';
            if (strlen($q) < 1) {
                echo json_encode([]);
                break;
            }
            $sql = "SELECT id, codigo_barras, nombre_producto, precio_actual AS precio, stock_disponible AS stock 
                    FROM productos 
                    WHERE nombre_producto LIKE :q1 OR codigo_barras LIKE :q2 
                    ORDER BY nombre_producto ASC 
                    LIMIT 20";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':q1' => "%$q%", ':q2' => "%$q%"]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'buscar_por_codigo':
            $codigo = $_GET['codigo'] ?? '';
            if (empty($codigo)) {
                http_response_code(400);
                echo json_encode(['error' => 'Codigo requerido']);
                break;
            }
            $sql = "SELECT id, codigo_barras, nombre_producto, precio_actual AS precio, stock_disponible AS stock 
                    FROM productos 
                    WHERE codigo_barras = :codigo LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':codigo' => $codigo]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($producto) {
                echo json_encode($producto);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Producto no encontrado']);
            }
            break;

        case 'buscar_cliente':
            $q = $_GET['q'] ?? '';
            if (strlen($q) < 1) {
                echo json_encode([]);
                break;
            }
            $sql = "SELECT id, nombre_completo AS nombre, cedula, telefono 
                    FROM clientes 
                    WHERE nombre_completo LIKE :q1 OR cedula LIKE :q2 OR telefono LIKE :q3
                    ORDER BY nombre_completo ASC 
                    LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':q1' => "%$q%", ':q2' => "%$q%", ':q3' => "%$q%"]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'procesar_venta':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Metodo no permitido']);
                break;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['items']) || !is_array($input['items'])) {
                http_response_code(400);
                echo json_encode(['error' => 'No hay productos en la venta']);
                break;
            }

            $cliente_id = $input['cliente_id'] ?? 7;
            if (empty($cliente_id)) $cliente_id = 7;
            $subtotal   = (float)($input['subtotal'] ?? 0);
            $iva        = (float)($input['iva'] ?? 0);
            $total      = (float)($input['total'] ?? 0);
            $monto_pago = (float)($input['monto_pago'] ?? 0);
            $cambio     = (float)($input['cambio'] ?? 0);
            $usuario_id = $user['id'];

            $pdo->beginTransaction();

            try {
                $sqlVenta = "INSERT INTO ventas (cliente_id, usuario_id, total_factura, subtotal, iva, monto_pago, cambio, fecha_emision) 
                             VALUES (:cliente_id, :usuario_id, :total, :subtotal, :iva, :monto_pago, :cambio, NOW())";
                $stmtVenta = $pdo->prepare($sqlVenta);
                $stmtVenta->execute([
                    ':cliente_id'  => $cliente_id,
                    ':usuario_id'  => $usuario_id,
                    ':total'       => $total,
                    ':subtotal'    => $subtotal,
                    ':iva'         => $iva,
                    ':monto_pago'  => $monto_pago,
                    ':cambio'      => $cambio,
                ]);
                $venta_id = $pdo->lastInsertId();

                $sqlDetalle = "INSERT INTO detalles_venta (venta_id, producto_id, cantidad, precio_congelado, subtotal) 
                               VALUES (:venta_id, :producto_id, :cantidad, :precio_congelado, :subtotal)";
                $stmtDetalle = $pdo->prepare($sqlDetalle);

                $sqlStock = "UPDATE productos SET stock_disponible = stock_disponible - :cant WHERE id = :id AND stock_disponible >= :cant2";
                $stmtStock = $pdo->prepare($sqlStock);

                foreach ($input['items'] as $item) {
                    $prod_id   = (int)$item['id'];
                    $cant      = (int)$item['cantidad'];
                    $precio    = (float)$item['precio'];
                    $subItem   = $precio * $cant;

                    $stmtCheck = $pdo->prepare("SELECT stock_disponible FROM productos WHERE id = :id");
                    $stmtCheck->execute([':id' => $prod_id]);
                    $stockActual = (int)$stmtCheck->fetchColumn();

                    if ($stockActual < $cant) {
                        $pdo->rollBack();
                        http_response_code(400);
                        echo json_encode(['error' => "Stock insuficiente para producto ID $prod_id (disponible: $stockActual)"]);
                        exit();
                    }

                    $stmtDetalle->execute([
                        ':venta_id'        => $venta_id,
                        ':producto_id'     => $prod_id,
                        ':cantidad'        => $cant,
                        ':precio_congelado'=> $precio,
                        ':subtotal'        => $subItem,
                    ]);

                    $stmtStock->execute([
                        ':cant'  => $cant,
                        ':id'    => $prod_id,
                        ':cant2' => $cant,
                    ]);
                }

                $pdo->commit();

                echo json_encode([
                    'success'  => true,
                    'venta_id' => $venta_id,
                    'mensaje'  => 'Venta procesada exitosamente',
                    'total'    => $total,
                    'cambio'   => $cambio,
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Accion no valida']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
