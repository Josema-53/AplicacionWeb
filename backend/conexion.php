<?php
    declare(strict_types=1);

    $host = 'gateway01.us-east-1.prod.aws.tidbcloud.com';
    $user = 'jePixA9eWecvF7W.root';
    $password = 'xAxfbihqdA6Irv1o';
    $database = 'bdventas';
    $charset = 'utf8mb4';

    $dns = "mysql:host=$host;dbname=$database;charset=$charset";

    $opciones = [
        //Obliga a PDO a lanzar excepciones en caso de error SQL
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo= new PDO($dns, $user, $password, $opciones);
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'estado' => 'error',
            'mensaje' => 'Error de conexión a la base de datos'
        ]);
        exit;
    }

?>