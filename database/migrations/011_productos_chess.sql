-- Datos del maestro de artículos de Chess (RAVSA): código de artículo, bulto y precios por lista.
ALTER TABLE productos ADD COLUMN IF NOT EXISTS codigo_interno VARCHAR(20) NULL AFTER codigo_barras;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS unidades_bulto SMALLINT UNSIGNED NULL AFTER unidad_medida;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS presentacion_bulto VARCHAR(30) NULL AFTER unidades_bulto;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS precio_unidad DECIMAL(12,2) NULL AFTER presentacion_bulto;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS precio_bulto DECIMAL(12,2) NULL AFTER precio_unidad;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS precio_base_unidad DECIMAL(12,2) NULL AFTER precio_bulto;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS precio_base_bulto DECIMAL(12,2) NULL AFTER precio_base_unidad;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS precio_vigente DATE NULL AFTER precio_base_bulto;
ALTER TABLE productos ADD UNIQUE INDEX IF NOT EXISTS uq_productos_codigo_interno (codigo_interno);
