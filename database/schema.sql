-- =====================================================
-- Sistema de Inventario - Esquema de base de datos
-- Motor: MySQL 8 / MariaDB, InnoDB, utf8mb4
-- =====================================================

CREATE DATABASE IF NOT EXISTS inventario
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE inventario;

-- -----------------------------------------------------
-- Usuarios (login)
-- La contraseña NUNCA se guarda en texto plano:
-- en PHP usa password_hash() y password_verify().
-- -----------------------------------------------------
CREATE TABLE usuarios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol           ENUM('admin', 'vendedor') NOT NULL DEFAULT 'vendedor',
  creado_en     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Categorías de productos
-- -----------------------------------------------------
CREATE TABLE categorias (
  id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Productos
-- -----------------------------------------------------
CREATE TABLE productos (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo       VARCHAR(30)  NOT NULL UNIQUE,
  nombre       VARCHAR(150) NOT NULL,
  categoria_id INT UNSIGNED NULL,
  precio       DECIMAL(10,2) NOT NULL CHECK (precio >= 0),
  stock        INT NOT NULL DEFAULT 0 CHECK (stock >= 0),
  stock_minimo INT NOT NULL DEFAULT 5 CHECK (stock_minimo >= 0),
  activo       TINYINT(1) NOT NULL DEFAULT 1,
  creado_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_producto_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Ventas (encabezado) y su detalle (líneas)
-- Se guarda precio_unitario en el detalle para que un
-- cambio futuro de precio no altere ventas pasadas.
-- -----------------------------------------------------
CREATE TABLE ventas (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  total      DECIMAL(10,2) NOT NULL DEFAULT 0,
  fecha      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_venta_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE detalle_ventas (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  venta_id        INT UNSIGNED NOT NULL,
  producto_id     INT UNSIGNED NOT NULL,
  cantidad        INT NOT NULL CHECK (cantidad > 0),
  precio_unitario DECIMAL(10,2) NOT NULL,
  subtotal        DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_detalle_venta
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
  CONSTRAINT fk_detalle_producto
    FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB;

-- Índices útiles para búsquedas y reportes
CREATE INDEX idx_productos_nombre ON productos(nombre);
CREATE INDEX idx_ventas_fecha     ON ventas(fecha);

-- -----------------------------------------------------
-- Datos de prueba
-- (El usuario administrador se crea desde PHP para
--  poder generar el hash de la contraseña correctamente.)
-- -----------------------------------------------------
INSERT INTO categorias (nombre) VALUES
  ('Abarrotes'), ('Bebidas'), ('Botanas'), ('Limpieza');

INSERT INTO productos (codigo, nombre, categoria_id, precio, stock, stock_minimo) VALUES
  ('ABA-001', 'Arroz 1 kg',          1, 28.50, 40, 10),
  ('ABA-002', 'Frijol negro 1 kg',   1, 34.00, 25, 10),
  ('BEB-001', 'Refresco cola 600 ml', 2, 18.00, 60, 12),
  ('BEB-002', 'Agua natural 1 L',     2, 12.00,  8, 10),
  ('BOT-001', 'Papas fritas 45 g',    3, 17.00, 30,  8),
  ('LIM-001', 'Detergente 1 kg',      4, 42.00,  4,  6);

-- -----------------------------------------------------
-- Ejemplo de venta con TRANSACCIÓN (para tu API en PHP):
-- o se guarda todo, o no se guarda nada.
-- -----------------------------------------------------
-- START TRANSACTION;
--   INSERT INTO ventas (usuario_id, total) VALUES (1, 0);
--   SET @venta_id = LAST_INSERT_ID();
--   INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal)
--     VALUES (@venta_id, 3, 2, 18.00, 36.00);
--   UPDATE productos SET stock = stock - 2 WHERE id = 3 AND stock >= 2;
--   UPDATE ventas SET total = 36.00 WHERE id = @venta_id;
-- COMMIT;  -- si algo falla: ROLLBACK;

-- Consulta de alerta de stock bajo:
-- SELECT nombre, stock, stock_minimo FROM productos
-- WHERE activo = 1 AND stock <= stock_minimo;

CREATE TABLE IF NOT EXISTS remember_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    selector CHAR(32) NOT NULL UNIQUE,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_remember_usuario (usuario_id),
    INDEX idx_remember_expira (expires_at),
    CONSTRAINT fk_remember_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reset_usuario (usuario_id),
    INDEX idx_reset_expira (expires_at),
    CONSTRAINT fk_reset_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;