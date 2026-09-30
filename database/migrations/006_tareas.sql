-- Tareas programables por local y su registro de ejecución (sin cron: lo pendiente se calcula)

CREATE TABLE IF NOT EXISTS tareas (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    descripcion  VARCHAR(255) NULL,
    tipo         ENUM('reposicion','vencimientos','conteo_promociones','fotos','otra') NOT NULL DEFAULT 'otra',
    periodicidad ENUM('unica','diaria','semanal','mensual','cada_visita') NOT NULL DEFAULT 'cada_visita',
    dias_semana  VARCHAR(20)  NULL COMMENT 'ISO 1=lunes … 7=domingo, separados por coma',
    dia_mes      TINYINT UNSIGNED NULL,
    fecha        DATE         NULL COMMENT 'Solo para periodicidad única',
    prioridad    ENUM('alta','media','baja') NOT NULL DEFAULT 'media',
    activo       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at   DATETIME     NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tarea_local (
    tarea_id  INT UNSIGNED NOT NULL,
    local_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (tarea_id, local_id),
    INDEX idx_tarea_local_local (local_id),
    CONSTRAINT fk_tarea_local_tarea FOREIGN KEY (tarea_id) REFERENCES tareas (id)  ON DELETE CASCADE,
    CONSTRAINT fk_tarea_local_local FOREIGN KEY (local_id) REFERENCES locales (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tarea_ejecuciones (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tarea_id         INT UNSIGNED NOT NULL,
    local_id         INT UNSIGNED NOT NULL,
    relevamiento_id  INT UNSIGNED NULL,
    user_id          INT UNSIGNED NOT NULL,
    fecha            DATE         NOT NULL,
    estado           ENUM('completada','omitida') NOT NULL DEFAULT 'completada',
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tarea_local_fecha (tarea_id, local_id, fecha),
    INDEX idx_te_local_fecha (local_id, fecha),
    CONSTRAINT fk_te_tarea        FOREIGN KEY (tarea_id)        REFERENCES tareas (id)        ON DELETE CASCADE,
    CONSTRAINT fk_te_local        FOREIGN KEY (local_id)        REFERENCES locales (id)       ON DELETE CASCADE,
    CONSTRAINT fk_te_relevamiento FOREIGN KEY (relevamiento_id) REFERENCES relevamientos (id) ON DELETE SET NULL,
    CONSTRAINT fk_te_user         FOREIGN KEY (user_id)         REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
