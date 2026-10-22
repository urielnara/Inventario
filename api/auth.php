<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function jsonResponse(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    jsonResponse(405, [
        'success' => false,
        'message' => 'Método no permitido.'
    ]);
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin !== '') {
    $originHost = parse_url($origin, PHP_URL_HOST);
    $requestHost = $_SERVER['HTTP_HOST'] ?? '';

    if (
        !is_string($originHost) ||
        strcasecmp($originHost, $requestHost) !== 0
    ) {
        jsonResponse(403, [
            'success' => false,
            'message' => 'Origen no permitido.'
        ]);
    }
}

$secureCookie = isset($_SERVER['HTTPS'])
    && $_SERVER['HTTPS'] !== 'off'
    && $_SERVER['HTTPS'] !== '';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

$contentType = strtolower(
    trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])
);

if ($contentType !== 'application/json') {
    jsonResponse(415, [
        'success' => false,
        'message' => 'Se requiere contenido JSON.'
    ]);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    jsonResponse(400, [
        'success' => false,
        'message' => 'Solicitud inválida.'
    ]);
}

$email = $input['email'] ?? null;
$password = $input['password'] ?? null;

if (
    !is_string($email) ||
    !filter_var(trim($email), FILTER_VALIDATE_EMAIL) ||
    strlen(trim($email)) > 150 ||
    !is_string($password) ||
    $password === '' ||
    strlen($password) > 1024
) {
    jsonResponse(422, [
        'success' => false,
        'message' => 'Ingresa un correo y contraseña válidos.'
    ]);
}

try {
    require_once __DIR__ . '/db.php';

    $pdo = getConnection();

    $stmt = $pdo->prepare(
        'SELECT id, nombre, email, password_hash, rol
         FROM usuarios
         WHERE email = :email
         LIMIT 1'
    );

    $stmt->execute([
        'email' => trim($email)
    ]);

    $usuario = $stmt->fetch();

    if (
        !$usuario ||
        !password_verify($password, $usuario['password_hash'])
    ) {
        jsonResponse(401, [
            'success' => false,
            'message' => 'Correo o contraseña incorrectos.'
        ]);
    }

    session_regenerate_id(true);

    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_rol'] = $usuario['rol'];

    jsonResponse(200, [
        'success' => true,
        'message' => 'Inicio de sesión correcto.',
        'user' => [
            'id' => (int) $usuario['id'],
            'nombre' => $usuario['nombre'],
            'email' => $usuario['email'],
            'rol' => $usuario['rol']
        ]
    ]);
} catch (Throwable $e) {
    error_log('Error de autenticación: ' . $e->getMessage());

    jsonResponse(500, [
        'success' => false,
        'message' => 'Error interno del servidor.'
    ]);
}

?>