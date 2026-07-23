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

        case 'buscar_facturas':
            $fechaInicio = $_GET['fecha_inicio'] ?? '';
            $fechaFin    = $_GET['fecha_fin'] ?? '';
            $cliente     = $_GET['cliente'] ?? '';
            $factura     = $_GET['factura'] ?? '';

            $conditions = [];
            $params = [];

            if (!empty($fechaInicio)) {
                $conditions[] = "DATE(v.fecha_emision) >= :fecha_inicio";
                $params[':fecha_inicio'] = $fechaInicio;
            }
            if (!empty($fechaFin)) {
                $conditions[] = "DATE(v.fecha_emision) <= :fecha_fin";
                $params[':fecha_fin'] = $fechaFin;
            }
            if (!empty($cliente)) {
                $conditions[] = "(c.nombre_completo LIKE :cliente OR c.cedula LIKE :cliente2)";
                $params[':cliente']  = "%$cliente%";
                $params[':cliente2'] = "%$cliente%";
            }
            if (!empty($factura)) {
                $conditions[] = "v.id = :factura";
                $params[':factura'] = (int)$factura;
            }

            $where = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

            $sql = "SELECT v.id, v.fecha_emision, v.total_factura, v.estado,
                           v.subtotal, v.iva, v.monto_pago, v.cambio,
                           c.nombre_completo AS cliente_nombre, c.cedula AS cliente_cedula,
                           u.usuario AS vendedor_nombre
                    FROM ventas v
                    LEFT JOIN clientes c ON v.cliente_id = c.id
                    LEFT JOIN usuarios u ON v.usuario_id = u.id
                    $where
                    ORDER BY v.fecha_emision DESC
                    LIMIT 500";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'obtener_resumen':
            $fechaInicio = $_GET['fecha_inicio'] ?? '';
            $fechaFin    = $_GET['fecha_fin'] ?? '';
            $cliente     = $_GET['cliente'] ?? '';
            $factura     = $_GET['factura'] ?? '';

            $conditions = [];
            $params = [];

            if (!empty($fechaInicio)) {
                $conditions[] = "DATE(v.fecha_emision) >= :fecha_inicio";
                $params[':fecha_inicio'] = $fechaInicio;
            }
            if (!empty($fechaFin)) {
                $conditions[] = "DATE(v.fecha_emision) <= :fecha_fin";
                $params[':fecha_fin'] = $fechaFin;
            }
            if (!empty($cliente)) {
                $conditions[] = "(c.nombre_completo LIKE :cliente OR c.cedula LIKE :cliente2)";
                $params[':cliente']  = "%$cliente%";
                $params[':cliente2'] = "%$cliente%";
            }
            if (!empty($factura)) {
                $conditions[] = "v.id = :factura";
                $params[':factura'] = (int)$factura;
            }

            $where = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

            $sql = "SELECT 
                        COALESCE(SUM(v.total_factura), 0) AS total_vendido,
                        COUNT(v.id) AS cantidad_facturas,
                        CASE WHEN COUNT(v.id) > 0 THEN COALESCE(SUM(v.total_factura), 0) / COUNT(v.id) ELSE 0 END AS ticket_promedio
                    FROM ventas v
                    LEFT JOIN clientes c ON v.cliente_id = c.id
                    $where";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
            break;

        case 'obtener_detalle':
            $venta_id = (int)($_GET['venta_id'] ?? 0);
            if ($venta_id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de venta requerido']);
                break;
            }

            $stmtVenta = $pdo->prepare("
                SELECT v.*, c.nombre_completo AS cliente_nombre, c.cedula AS cliente_cedula,
                       c.telefono AS cliente_telefono, c.correo AS cliente_correo,
                       u.usuario AS vendedor_nombre
                FROM ventas v
                LEFT JOIN clientes c ON v.cliente_id = c.id
                LEFT JOIN usuarios u ON v.usuario_id = u.id
                WHERE v.id = :id
            ");
            $stmtVenta->execute([':id' => $venta_id]);
            $venta = $stmtVenta->fetch(PDO::FETCH_ASSOC);

            if (!$venta) {
                http_response_code(404);
                echo json_encode(['error' => 'Venta no encontrada']);
                break;
            }

            $stmtDet = $pdo->prepare("
                SELECT dv.cantidad, dv.precio_congelado, dv.subtotal,
                       p.nombre_producto, p.codigo_barras
                FROM detalles_venta dv
                INNER JOIN productos p ON dv.producto_id = p.id
                WHERE dv.venta_id = :venta_id
            ");
            $stmtDet->execute([':venta_id' => $venta_id]);
            $detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'venta'   => $venta,
                'detalles' => $detalles
            ]);
            break;

        case 'anular_venta':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Metodo no permitido']);
                break;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $venta_id = (int)($input['venta_id'] ?? 0);

            if ($venta_id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de venta requerido']);
                break;
            }

            $stmtCheck = $pdo->prepare("SELECT estado FROM ventas WHERE id = :id");
            $stmtCheck->execute([':id' => $venta_id]);
            $venta = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$venta) {
                http_response_code(404);
                echo json_encode(['error' => 'Venta no encontrada']);
                break;
            }

            if ($venta['estado'] === 'Anulada') {
                http_response_code(400);
                echo json_encode(['error' => 'Esta factura ya fue anulada anteriormente']);
                break;
            }

            $pdo->beginTransaction();

            try {
                $stmtAnular = $pdo->prepare("UPDATE ventas SET estado = 'Anulada' WHERE id = :id");
                $stmtAnular->execute([':id' => $venta_id]);

                $stmtDet = $pdo->prepare("SELECT producto_id, cantidad FROM detalles_venta WHERE venta_id = :venta_id");
                $stmtDet->execute([':venta_id' => $venta_id]);
                $detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

                $stmtStock = $pdo->prepare("UPDATE productos SET stock_disponible = stock_disponible + :cant WHERE id = :id");

                foreach ($detalles as $det) {
                    $stmtStock->execute([
                        ':cant' => (int)$det['cantidad'],
                        ':id'   => (int)$det['producto_id']
                    ]);
                }

                $pdo->commit();

                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Factura anulada exitosamente. Stock devuelto al inventario.'
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
