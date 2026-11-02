-- Anotador de fechas: cada fecha es de un usuario, en un local, de un producto (del catálogo o escrito a mano).
-- Reemplaza a los vencimientos que dependían de las visitas (esas tablas quedan, pero ya no se usan).

CREATE TABLE IF NOT EXISTS fechas (
    id                INT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id           INT UNSIGNED  NOT NULL,
    local_id          INT UNSIGNED  NOT NULL,
    producto_id       INT UNSIGNED  NULL,
    producto_texto    VARCHAR(150)  NULL,
    fecha_vencimiento DATE          NOT NULL,
    cantidad          INT UNSIGNED  NULL,
    nota              VARCHAR(120)  NULL,
    retirado_at       DATETIME      NULL,
    avisado_at        DATETIME      NULL,
    created_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME      NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_fechas_user_fecha (user_id, fecha_vencimiento),
    KEY idx_fechas_local (local_id),
    CONSTRAINT fk_fechas_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_fechas_local FOREIGN KEY (local_id) REFERENCES locales (id) ON DELETE CASCADE,
    CONSTRAINT fk_fechas_producto FOREIGN KEY (producto_id) REFERENCES productos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Celulares donde cada usuario activó los avisos (notificaciones push).
CREATE TABLE IF NOT EXISTS push_suscripciones (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED  NOT NULL,
    endpoint    VARCHAR(500)  NOT NULL,
    p256dh      VARCHAR(255)  NOT NULL,
    auth        VARCHAR(255)  NOT NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_push_endpoint (endpoint(255)),
    CONSTRAINT fk_push_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cuántos días antes avisar (cada uno elige el suyo en Mi cuenta).
ALTER TABLE users ADD COLUMN IF NOT EXISTS dias_aviso TINYINT UNSIGNED NOT NULL DEFAULT 7 AFTER sesion_version;

-- Pasar los vencimientos que siguen en góndola al anotador nuevo.
INSERT INTO fechas (user_id, local_id, producto_id, fecha_vencimiento, cantidad, nota, created_at)
SELECT r.user_id, r.local_id, rp.producto_id, v.fecha_vencimiento, v.cantidad, v.nota, v.created_at
FROM vencimientos v
JOIN relevamiento_productos rp ON rp.id = v.relevamiento_producto_id
JOIN relevamientos r ON r.id = rp.relevamiento_id
WHERE v.retirado_at IS NULL
  AND r.estado <> 'cancelado'
  AND v.fecha_vencimiento >= CURDATE() - INTERVAL 30 DAY
  AND NOT EXISTS (SELECT 1 FROM fechas);
