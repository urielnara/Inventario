<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

try {
    require_once __DIR__ . '/db.php';

    $pdo = getConnection();

    $resultado = $pdo->query('SELECT DATABASE() AS base_datos')->fetch();

    echo "CONEXIÓN EXITOSA\n";
    echo "Base de datos: " . $resultado['base_datos'] . "\n";

} catch (Throwable $e) {
    http_response_code(500);
    error_log($e->getMessage());

    echo "ERROR DE CONEXIÓN\n";
    echo "Revisa api/db.php, config/config.php y que MySQL esté iniciado.";
}