<?php
declare(strict_types=1);

$host = getenv('DB_HOST') ?: 'gateway01.us-east-1.prod.aws.tidbcloud.com';
$port = getenv('DB_PORT') ?: '4000';
$user = getenv('DB_USER') ?: 'jePixA9eWecvF7W.root';
$password = getenv('DB_PASS') ?: 'xAxfbihqdA6Irv1o';
$database = getenv('DB_NAME') ?: 'BDVentas';
$charset = 'utf8mb4';

$dns = "mysql:host=$host;port=$port;dbname=$database;charset=$charset";

$opciones = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 10,
];

if ($host !== 'localhost' && $host !== '127.0.0.1') {
    $opciones[PDO::MYSQL_ATTR_SSL_CA] = '';
    $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

try {
    $pdo = new PDO($dns, $user, $password, $opciones);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'estado' => 'error',
        'mensaje' => 'Error de conexion a la base de datos'
    ]);
    exit;
}
