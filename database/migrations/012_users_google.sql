-- Cuenta de Google vinculada (para entrar con "Iniciar sesión con Google").
ALTER TABLE users ADD COLUMN IF NOT EXISTS google_email VARCHAR(190) NULL AFTER email;
ALTER TABLE users ADD UNIQUE INDEX IF NOT EXISTS uq_users_google_email (google_email);
