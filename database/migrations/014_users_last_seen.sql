-- Última vez que el usuario usó la app (para supervisar al equipo; se actualiza como mucho cada 5 minutos).
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_seen_at DATETIME NULL AFTER last_login_at;
