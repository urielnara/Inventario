<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

function responder(int $codigo, array $datos): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function leerJSON(): array
{
    $datos = json_decode(file_get_contents('php://input'), true);

    if (!is_array($datos)) {
        responder(400, ['error' => 'La solicitud no contiene JSON válido.']);
    }

    return $datos;
}

if (empty($_SESSION['usuario_id'])) {
    responder(401, ['error' => 'Tu sesión ha expirado. Inicia sesión nuevamente.']);
}

try {
    $pdo = getConnection();
    $metodo = $_SERVER['REQUEST_METHOD'];

    // Consultar productos y categorías.
    if ($metodo === 'GET') {
        $categorias = $pdo->query(
            'SELECT id, nombre FROM categorias ORDER BY nombre'
        )->fetchAll();

        $productos = $pdo->query(
            'SELECT p.id, p.codigo, p.nombre, p.categoria_id,
                    c.nombre AS categoria, p.precio, p.stock,
                    p.stock_minimo, p.activo
             FROM productos p
             LEFT JOIN categorias c ON c.id = p.categoria_id
             ORDER BY p.id DESC'
        )->fetchAll();

        foreach ($productos as &$producto) {
            $producto['id'] = (int) $producto['id'];
            $producto['categoria_id'] = $producto['categoria_id'] !== null
                ? (int) $producto['categoria_id'] : null;
            $producto['precio'] = (float) $producto['precio'];
            $producto['stock'] = (int) $producto['stock'];
            $producto['stock_minimo'] = (int) $producto['stock_minimo'];
            $producto['activo'] = (bool) $producto['activo'];
        }
        unset($producto);

        responder(200, [
            'productos' => $productos,
            'categorias' => $categorias
        ]);
    }

    // Las operaciones que modifican datos requieren POST.
    if ($metodo !== 'POST') {
        header('Allow: GET, POST');
        responder(405, ['error' => 'Método no permitido.']);
    }

    // Solo administradores pueden modificar el catálogo.
    if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
        responder(403, ['error' => 'No tienes permisos para modificar productos.']);
    }

    $datos = leerJSON();
    $accion = $datos['accion'] ?? '';

    // Activar o desactivar un producto.
    if ($accion === 'estado') {
        $id = filter_var($datos['id'] ?? null, FILTER_VALIDATE_INT);
        $activo = $datos['activo'] ?? null;

        if (!$id || !is_bool($activo)) {
            responder(422, ['error' => 'Los datos del estado no son válidos.']);
        }

        $stmt = $pdo->prepare(
            'UPDATE productos SET activo = :activo WHERE id = :id'
        );
        $stmt->execute(['activo' => (int) $activo, 'id' => $id]);

        if ($stmt->rowCount() === 0) {
            $check = $pdo->prepare('SELECT id FROM productos WHERE id = ?');
            $check->execute([$id]);

            if (!$check->fetch()) {
                responder(404, ['error' => 'El producto no existe.']);
            }
        }

        responder(200, ['mensaje' => 'Estado actualizado correctamente.']);
    }

    // Validar los datos para crear o editar.
    if (!in_array($accion, ['crear', 'editar'], true)) {
        responder(422, ['error' => 'La acción solicitada no es válida.']);
    }

    $id = filter_var($datos['id'] ?? null, FILTER_VALIDATE_INT);
    $codigo = trim((string) ($datos['codigo'] ?? ''));
    $nombre = trim((string) ($datos['nombre'] ?? ''));
    $categoriaId = $datos['categoria_id'] ?? null;
    $precioRaw = $datos['precio'] ?? null;
    $stockRaw = $datos['stock'] ?? null;
    $minimoRaw = $datos['stock_minimo'] ?? 5;
    $activo = $datos['activo'] ?? true;

    if (
        $codigo === '' || mb_strlen($codigo) > 30 ||
        $nombre === '' || mb_strlen($nombre) > 150
    ) {
        responder(422, ['error' => 'El código o el nombre están vacíos o exceden su longitud permitida.']);
    }

    if (
        !is_numeric($precioRaw) ||
        !is_numeric($stockRaw) ||
        !is_numeric($minimoRaw) ||
        !is_bool($activo)
    ) {
        responder(422, ['error' => 'Precio, existencias, stock mínimo o estado no válidos.']);
    }

    $precio = (float) $precioRaw;
    $stock = filter_var($stockRaw, FILTER_VALIDATE_INT);
    $minimo = filter_var($minimoRaw, FILTER_VALIDATE_INT);

    if (
        !is_finite($precio) || $precio < 0 || $precio > 99999999.99 ||
        $stock === false || $stock < 0 ||
        $minimo === false || $minimo < 0
    ) {
        responder(422, ['error' => 'Verifica el precio y las cantidades.']);
    }

    if ($categoriaId === '' || $categoriaId === null) {
        $categoriaId = null;
    } else {
        $categoriaId = filter_var($categoriaId, FILTER_VALIDATE_INT);

        if (!$categoriaId || $categoriaId < 1) {
            responder(422, ['error' => 'La categoría seleccionada no es válida.']);
        }

        $stmt = $pdo->prepare('SELECT id FROM categorias WHERE id = ?');
        $stmt->execute([$categoriaId]);

        if (!$stmt->fetch()) {
            responder(422, ['error' => 'La categoría seleccionada no existe.']);
        }
    }

    // Evitar códigos duplicados.
    $sql = 'SELECT id FROM productos WHERE codigo = :codigo';
    $parametros = ['codigo' => $codigo];

    if ($accion === 'editar') {
        if (!$id || $id < 1) {
            responder(422, ['error' => 'El ID del producto no es válido.']);
        }

        $sql .= ' AND id <> :id';
        $parametros['id'] = $id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);

    if ($stmt->fetch()) {
        responder(409, ['error' => 'Ya existe un producto con ese código.']);
    }

    if ($accion === 'crear') {
        $stmt = $pdo->prepare(
            'INSERT INTO productos
                (codigo, nombre, categoria_id, precio, stock, stock_minimo, activo)
             VALUES
                (:codigo, :nombre, :categoria_id, :precio, :stock, :minimo, :activo)'
        );

        $stmt->execute([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'categoria_id' => $categoriaId,
            'precio' => $precio,
            'stock' => $stock,
            'minimo' => $minimo,
            'activo' => (int) $activo
        ]);

        responder(201, [
            'mensaje' => 'Producto registrado correctamente.',
            'id' => (int) $pdo->lastInsertId()
        ]);
    }

    $stmt = $pdo->prepare(
        'UPDATE productos
         SET codigo = :codigo, nombre = :nombre,
             categoria_id = :categoria_id, precio = :precio,
             stock = :stock, stock_minimo = :minimo, activo = :activo
         WHERE id = :id'
    );

    $stmt->execute([
        'codigo' => $codigo,
        'nombre' => $nombre,
        'categoria_id' => $categoriaId,
        'precio' => $precio,
        'stock' => $stock,
        'minimo' => $minimo,
        'activo' => (int) $activo,
        'id' => $id
    ]);

    if ($stmt->rowCount() === 0) {
        $check = $pdo->prepare('SELECT id FROM productos WHERE id = ?');
        $check->execute([$id]);

        if (!$check->fetch()) {
            responder(404, ['error' => 'El producto que intentas editar no existe.']);
        }
    }

    responder(200, ['mensaje' => 'Producto actualizado correctamente.']);

} catch (PDOException $e) {
    error_log('Error en api/productos.php: ' . $e->getMessage());

    if ($e->getCode() === '23000') {
        responder(409, ['error' => 'No se pudo guardar: revisa el código y la categoría seleccionada.']);
    }

    responder(500, ['error' => 'Ocurrió un error al consultar o guardar los productos.']);
} catch (Throwable $e) {
    error_log('Error en api/productos.php: ' . $e->getMessage());
    responder(500, ['error' => 'Ocurrió un error interno.']);
}
