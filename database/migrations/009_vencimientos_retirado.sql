-- Lote retirado de la góndola (fecha corta ya sacada): deja de aparecer en "Fechas cortas".
ALTER TABLE vencimientos ADD COLUMN IF NOT EXISTS retirado_at DATETIME NULL AFTER nota;
