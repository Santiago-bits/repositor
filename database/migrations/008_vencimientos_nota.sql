-- Nota libre en cada vencimiento (por ejemplo, dónde está: "depósito", "heladera 2").
ALTER TABLE vencimientos ADD COLUMN IF NOT EXISTS nota VARCHAR(120) NULL AFTER cantidad;
