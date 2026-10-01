-- De qué es cada foto del local (góndola, heladera, exhibición externa…) y una descripción opcional.
ALTER TABLE fotos ADD COLUMN IF NOT EXISTS tipo VARCHAR(20) NULL AFTER producto_id;
ALTER TABLE fotos ADD COLUMN IF NOT EXISTS descripcion VARCHAR(120) NULL AFTER tipo;
