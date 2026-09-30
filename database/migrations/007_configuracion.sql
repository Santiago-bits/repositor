-- Parámetros configurables del sistema

CREATE TABLE IF NOT EXISTS configuracion (
    clave        VARCHAR(60)  NOT NULL PRIMARY KEY,
    valor        VARCHAR(255) NOT NULL,
    descripcion  VARCHAR(255) NULL,
    updated_at   DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO configuracion (clave, valor, descripcion) VALUES
    ('vencimiento_dias_critico', '3',  'Días hasta el vencimiento para marcarlo como "vence en pocos días"'),
    ('vencimiento_dias_proximo', '15', 'Días hasta el vencimiento para marcarlo como "vence próximamente"'),
    ('conteo_promos_dias_gracia', '2', 'Días después del fin de una promo en que todavía aparece para contar');
