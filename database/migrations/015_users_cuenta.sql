-- Mi cuenta: foto de perfil, teléfono y versión de sesión (al subirla se cierran las sesiones en los demás dispositivos).
ALTER TABLE users ADD COLUMN IF NOT EXISTS foto_path VARCHAR(255) NULL AFTER apellido;
ALTER TABLE users ADD COLUMN IF NOT EXISTS telefono VARCHAR(40) NULL AFTER email;
ALTER TABLE users ADD COLUMN IF NOT EXISTS sesion_version INT UNSIGNED NOT NULL DEFAULT 0 AFTER password_hash;
