-- Locales y asignación de locales a usuarios

CREATE TABLE IF NOT EXISTS locales (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(120) NOT NULL,
    tipo           ENUM('supermercado','chino','autoservicio','mayorista','otro') NOT NULL DEFAULT 'supermercado',
    direccion      VARCHAR(200) NULL,
    latitud        DECIMAL(10,7) NULL,
    longitud       DECIMAL(10,7) NULL,
    radio_m        SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    telefono       VARCHAR(40)  NULL,
    contacto       VARCHAR(120) NULL,
    activo         TINYINT(1)   NOT NULL DEFAULT 1,
    es_prueba      TINYINT(1)   NOT NULL DEFAULT 0,
    observaciones  TEXT         NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at     DATETIME     NULL,
    INDEX idx_locales_activo (activo, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS local_user (
    user_id   INT UNSIGNED NOT NULL,
    local_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, local_id),
    INDEX idx_local_user_local (local_id),
    CONSTRAINT fk_local_user_user  FOREIGN KEY (user_id)  REFERENCES users (id)   ON DELETE CASCADE,
    CONSTRAINT fk_local_user_local FOREIGN KEY (local_id) REFERENCES locales (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
