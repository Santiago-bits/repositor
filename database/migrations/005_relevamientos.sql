-- Relevamientos (visitas) y todo lo que se registra durante la visita

CREATE TABLE IF NOT EXISTS relevamientos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    local_id    INT UNSIGNED NOT NULL,
    fecha       DATE         NOT NULL,
    inicio_at   DATETIME     NOT NULL,
    fin_at      DATETIME     NULL,
    estado      ENUM('en_proceso','finalizado','cancelado') NOT NULL DEFAULT 'en_proceso',
    lat_inicio  DECIMAL(10,7) NULL,
    lng_inicio  DECIMAL(10,7) NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_relevamientos_user_fecha (user_id, fecha),
    INDEX idx_relevamientos_local_fecha (local_id, fecha),
    INDEX idx_relevamientos_estado (estado),
    CONSTRAINT fk_relevamientos_user  FOREIGN KEY (user_id)  REFERENCES users (id),
    CONSTRAINT fk_relevamientos_local FOREIGN KEY (local_id) REFERENCES locales (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Un registro por producto y visita: volver a guardar actualiza, no duplica.
CREATE TABLE IF NOT EXISTS relevamiento_productos (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    relevamiento_id  INT UNSIGNED NOT NULL,
    producto_id      INT UNSIGNED NOT NULL,
    promocion_id     INT UNSIGNED NULL,
    stock            INT UNSIGNED NULL,
    estado_stock     ENUM('normal','bajo','sin_stock','no_exhibido') NULL,
    con_problema     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_relevamiento_producto (relevamiento_id, producto_id),
    INDEX idx_rp_producto (producto_id, created_at),
    INDEX idx_rp_promocion (promocion_id),
    CONSTRAINT fk_rp_relevamiento FOREIGN KEY (relevamiento_id) REFERENCES relevamientos (id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_producto     FOREIGN KEY (producto_id)     REFERENCES productos (id),
    CONSTRAINT fk_rp_promocion    FOREIGN KEY (promocion_id)    REFERENCES promociones (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vencimientos (
    id                        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    relevamiento_producto_id  INT UNSIGNED NOT NULL,
    fecha_vencimiento         DATE         NOT NULL,
    cantidad                  INT UNSIGNED NULL,
    created_at                DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_vencimientos_fecha (fecha_vencimiento),
    INDEX idx_vencimientos_rp (relevamiento_producto_id),
    CONSTRAINT fk_vencimientos_rp FOREIGN KEY (relevamiento_producto_id) REFERENCES relevamiento_productos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Local, usuario y fecha salen del relevamiento: no se repiten acá.
CREATE TABLE IF NOT EXISTS fotos (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    relevamiento_id  INT UNSIGNED NOT NULL,
    producto_id      INT UNSIGNED NULL,
    path             VARCHAR(255) NOT NULL,
    mime             VARCHAR(30)  NOT NULL,
    bytes            INT UNSIGNED NOT NULL,
    ancho            SMALLINT UNSIGNED NULL,
    alto             SMALLINT UNSIGNED NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at       DATETIME     NULL,
    INDEX idx_fotos_relevamiento (relevamiento_id),
    INDEX idx_fotos_producto (producto_id),
    CONSTRAINT fk_fotos_relevamiento FOREIGN KEY (relevamiento_id) REFERENCES relevamientos (id),
    CONSTRAINT fk_fotos_producto     FOREIGN KEY (producto_id)     REFERENCES productos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sin producto = observación general de la visita.
CREATE TABLE IF NOT EXISTS observaciones (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    relevamiento_id  INT UNSIGNED NOT NULL,
    producto_id      INT UNSIGNED NULL,
    texto            VARCHAR(500) NOT NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_observaciones_relevamiento (relevamiento_id),
    INDEX idx_observaciones_producto (producto_id),
    CONSTRAINT fk_observaciones_relevamiento FOREIGN KEY (relevamiento_id) REFERENCES relevamientos (id) ON DELETE CASCADE,
    CONSTRAINT fk_observaciones_producto     FOREIGN KEY (producto_id)     REFERENCES productos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
