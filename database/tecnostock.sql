-- ============================================================
-- TecnoStock - Caso 1 Sistema de Inventario
-- Base de datos MySQL / MariaDB para XAMPP
-- ============================================================

DROP DATABASE IF EXISTS tecnostock;
CREATE DATABASE tecnostock
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE tecnostock;

-- ------------------------------------------------------------
-- Tabla: usuarios
-- ------------------------------------------------------------
CREATE TABLE usuarios (
    id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    clave VARCHAR(255) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_usuarios_correo UNIQUE (correo),
    CONSTRAINT chk_usuarios_estado CHECK (estado IN (0,1))
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabla: categorias
-- ------------------------------------------------------------
CREATE TABLE categorias (
    id_categoria INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_categorias_nombre UNIQUE (nombre),
    CONSTRAINT chk_categorias_estado CHECK (estado IN (0,1))
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabla: productos
-- ------------------------------------------------------------
CREATE TABLE productos (
    id_producto INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_actual INT UNSIGNED NOT NULL DEFAULT 0,
    stock_minimo INT UNSIGNED NOT NULL DEFAULT 0,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    id_categoria INT UNSIGNED NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_productos_codigo UNIQUE (codigo),
    CONSTRAINT fk_productos_categoria
        FOREIGN KEY (id_categoria)
        REFERENCES categorias(id_categoria)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_productos_precio CHECK (precio >= 0),
    CONSTRAINT chk_productos_estado CHECK (estado IN (0,1))
) ENGINE=InnoDB;

CREATE INDEX idx_productos_nombre ON productos(nombre);
CREATE INDEX idx_productos_categoria ON productos(id_categoria);
CREATE INDEX idx_productos_estado ON productos(estado);

-- ------------------------------------------------------------
-- Tabla: movimientos
-- ------------------------------------------------------------
CREATE TABLE movimientos (
    id_movimiento BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo ENUM('ENTRADA','SALIDA') NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    observacion VARCHAR(255) NULL,
    id_producto INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,

    CONSTRAINT fk_movimientos_producto
        FOREIGN KEY (id_producto)
        REFERENCES productos(id_producto)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_movimientos_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_movimientos_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB;

CREATE INDEX idx_movimientos_producto ON movimientos(id_producto);
CREATE INDEX idx_movimientos_usuario ON movimientos(id_usuario);
CREATE INDEX idx_movimientos_fecha ON movimientos(fecha_hora);
CREATE INDEX idx_movimientos_tipo ON movimientos(tipo);

-- ------------------------------------------------------------
-- Datos iniciales para pruebas
-- ------------------------------------------------------------
INSERT INTO categorias (nombre, descripcion) VALUES
('Periféricos', 'Teclados, mouse y accesorios de entrada'),
('Audio', 'Audífonos, parlantes y accesorios de sonido'),
('Almacenamiento', 'Pendrives, discos y unidades de almacenamiento'),
('Conectividad', 'Cables, adaptadores y dispositivos de red');

INSERT INTO productos
(codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria)
VALUES
('TEC-001', 'Mouse inalámbrico', 'Mouse óptico inalámbrico USB', 12990.00, 20, 5, 1, 1),
('TEC-002', 'Teclado USB', 'Teclado alámbrico de tamaño completo', 15990.00, 8, 5, 1, 1),
('TEC-003', 'Audífonos con micrófono', 'Audífonos estéreo con micrófono integrado', 19990.00, 3, 5, 1, 2),
('TEC-004', 'Pendrive 64 GB', 'Unidad flash USB 3.0 de 64 GB', 9990.00, 15, 4, 1, 3),
('TEC-005', 'Cable HDMI 2 m', 'Cable HDMI de alta velocidad de 2 metros', 7990.00, 2, 3, 1, 4);

-- NOTA:
-- El usuario inicial se creará desde PHP usando password_hash().
-- No se almacenarán contraseñas en texto plano.

-- ------------------------------------------------------------
-- Vista útil para detectar productos con stock bajo
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW vista_productos_stock AS
SELECT
    p.id_producto,
    p.codigo,
    p.nombre,
    p.precio,
    p.stock_actual,
    p.stock_minimo,
    p.estado,
    c.nombre AS categoria,
    CASE
        WHEN p.stock_actual < p.stock_minimo THEN 1
        ELSE 0
    END AS stock_bajo
FROM productos p
INNER JOIN categorias c ON c.id_categoria = p.id_categoria;
