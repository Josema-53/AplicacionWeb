<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

$host = getenv('DB_HOST') ?: 'NO_SET';
$port = getenv('DB_PORT') ?: 'NO_SET';
$user = getenv('DB_USER') ?: 'NO_SET';
$password = getenv('DB_PASS') ?: 'NO_SET';
$database = getenv('DB_NAME') ?: 'NO_SET';

$dns = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";

$opciones = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 10,
];

if ($host !== 'localhost' && $host !== '127.0.0.1' && $host !== 'NO_SET') {
    $opciones[PDO::MYSQL_ATTR_SSL_CA] = '';
    $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

try {
    $pdo = new PDO($dns, $user, $password, $opciones);
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $row = $stmt->fetch();
    echo json_encode([
        'estado' => 'OK',
        'env' => ['host' => $host, 'port' => $port, 'user' => $user, 'db' => $database, 'pass' => ($password ? 'SET' : 'NO_SET')],
        'usuarios' => $row['total']
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'estado' => 'error',
        'error' => $e->getMessage(),
        'env' => ['host' => $host, 'port' => $port, 'user' => $user, 'db' => $database, 'pass' => ($password ? 'SET' : 'NO_SET')]
    ]);
}
