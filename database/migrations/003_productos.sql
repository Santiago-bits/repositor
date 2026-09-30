-- Categorías (subcategoría = categoría con padre), productos y productos por local

CREATE TABLE IF NOT EXISTS categorias (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id   INT UNSIGNED NULL,
    nombre      VARCHAR(80)  NOT NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categorias_nombre (parent_id, nombre),
    CONSTRAINT fk_categorias_parent FOREIGN KEY (parent_id) REFERENCES categorias (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS productos (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(150) NOT NULL,
    marca          VARCHAR(80)  NULL,
    codigo_barras  VARCHAR(32)  NULL UNIQUE,
    categoria_id   INT UNSIGNED NULL,
    presentacion   VARCHAR(40)  NULL,
    unidad_medida  VARCHAR(20)  NULL,
    imagen_path    VARCHAR(255) NULL,
    descripcion    TEXT         NULL,
    activo         TINYINT(1)   NOT NULL DEFAULT 1,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at     DATETIME     NULL,
    INDEX idx_productos_nombre (nombre),
    INDEX idx_productos_marca (marca),
    INDEX idx_productos_categoria (categoria_id),
    CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS producto_local (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producto_id        INT UNSIGNED NOT NULL,
    local_id           INT UNSIGNED NOT NULL,
    activo             TINYINT(1)   NOT NULL DEFAULT 1,
    stock_habitual     SMALLINT UNSIGNED NULL,
    ubicacion_gondola  VARCHAR(80)  NULL,
    observaciones      VARCHAR(255) NULL,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_producto_local (producto_id, local_id),
    INDEX idx_producto_local_local (local_id),
    CONSTRAINT fk_producto_local_producto FOREIGN KEY (producto_id) REFERENCES productos (id) ON DELETE CASCADE,
    CONSTRAINT fk_producto_local_local    FOREIGN KEY (local_id)    REFERENCES locales (id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
