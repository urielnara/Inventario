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

$nombre = $input['nombre'] ?? null;
$email = $input['email'] ?? null;
$password = $input['password'] ?? null;
$passwordConfirm = $input['password_confirm'] ?? null;

if (!is_string($nombre) || trim($nombre) === '' ||
    mb_strlen(trim($nombre), 'UTF-8') > 100) {
    jsonResponse(422, [
        'success' => false,
        'message' => 'Ingresa un nombre válido de máximo 100 caracteres.'
    ]);
}

$nombre = trim($nombre);

if (!is_string($email) ||
    !filter_var(trim($email), FILTER_VALIDATE_EMAIL) ||
    strlen(trim($email)) > 150) {
    jsonResponse(422, [
        'success' => false,
        'message' => 'Ingresa un correo electrónico válido.'
    ]);
}

$email = trim($email);

if (!is_string($password) ||
    strlen($password) < 8 ||
    strlen($password) > 1024) {
    jsonResponse(422, [
        'success' => false,
        'message' => 'La contraseña debe tener al menos 8 caracteres.'
    ]);
}

if (!is_string($passwordConfirm) || $password !== $passwordConfirm) {
    jsonResponse(422, [
        'success' => false,
        'message' => 'Las contraseñas no coinciden.'
    ]);
}

try {
    require_once __DIR__ . '/db.php';

    $pdo = getConnection();

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nombre, email, password_hash, rol)
         VALUES (:nombre, :email, :password_hash, :rol)'
    );

    $stmt->execute([
        'nombre' => $nombre,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'rol' => 'vendedor'
    ]);

    jsonResponse(201, [
        'success' => true,
        'message' => 'Usuario registrado correctamente.'
    ]);

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        jsonResponse(409, [
            'success' => false,
            'message' => 'Ya existe una cuenta con ese correo electrónico.'
        ]);
    }

    error_log('Error de registro: ' . $e->getMessage());

    jsonResponse(500, [
        'success' => false,
        'message' => 'Error interno del servidor.'
    ]);

} catch (Throwable $e) {
    error_log('Error de registro: ' . $e->getMessage());

    jsonResponse(500, [
        'success' => false,
        'message' => 'Error interno del servidor.'
    ]);
}
