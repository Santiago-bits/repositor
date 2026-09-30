-- Promociones (las registra el repositor al verlas en el local, o el admin)

CREATE TABLE IF NOT EXISTS promociones (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producto_id     INT UNSIGNED NOT NULL,
    fecha_inicio    DATE         NOT NULL,
    fecha_fin       DATE         NOT NULL,
    precio_normal   DECIMAL(12,2) NULL,
    precio_promo    DECIMAL(12,2) NULL,
    observaciones   VARCHAR(255) NULL,
    estado          ENUM('activa','finalizada','cancelada') NOT NULL DEFAULT 'activa',
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME     NULL,
    INDEX idx_promociones_fechas (fecha_fin, fecha_inicio),
    INDEX idx_promociones_producto (producto_id),
    CONSTRAINT fk_promociones_producto FOREIGN KEY (producto_id) REFERENCES productos (id),
    CONSTRAINT fk_promociones_user     FOREIGN KEY (created_by)  REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS promocion_local (
    promocion_id  INT UNSIGNED NOT NULL,
    local_id      INT UNSIGNED NOT NULL,
    PRIMARY KEY (promocion_id, local_id),
    INDEX idx_promocion_local_local (local_id),
    CONSTRAINT fk_promocion_local_promo FOREIGN KEY (promocion_id) REFERENCES promociones (id) ON DELETE CASCADE,
    CONSTRAINT fk_promocion_local_local FOREIGN KEY (local_id)     REFERENCES locales (id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
