# JACOB · Gestión inteligente de reposición

App web mobile-first para repositores: detección del local por GPS, registro de stock, vencimientos, fotos y observaciones, conteo de promociones, tareas programadas, historial, reportes CSV y mensaje para el supervisor. Se instala en el celular como app (PWA).

**Stack:** PHP 8.2+ sin framework (MVC propio) · MySQL / MariaDB · Bootstrap 5.3 · JavaScript sin dependencias (ZXing para el escáner en iPhone).

## Instalación local (XAMPP)

1. Clonar en `C:\xampp\htdocs\repositor`.
2. Copiar `config/config.example.php` como `config/config.php` (la parte local ya funciona con XAMPP).
3. Con MySQL de XAMPP encendido, crear la base y cargar datos de prueba:
   ```
   c:\xampp\php\php.exe database/migrate.php
   c:\xampp\php\php.exe database/seed.php
   ```
4. Abrir `http://localhost/repositor` · Admin: `admin@jacob.test` · Repositor: `repositor@jacob.test` · Contraseña: `Jacob2026!` (solo datos de prueba locales).

> Usar el PHP de XAMPP: necesita las extensiones `pdo_mysql` y `gd` (fotos).

## Servidor (Hostinger)

1. Subir el proyecto a `public_html/` (el `.htaccess` de la raíz redirige todo a `public/`).
2. Crear la base en hPanel y completar el bloque del servidor en `config/config.php`.
3. Por SSH: `php database/migrate.php` y `php database/crear-admin.php "Nombre" "Apellido" email@dominio.com`.
4. PHP 8.2 o superior. La cámara, el GPS y la instalación como app requieren HTTPS.

## Estructura

```
app/Core         Router, sesión, CSRF, vistas, validación, base de datos
app/Controllers  Controladores (Admin/ para el panel)
app/Models       Acceso a datos (PDO)
app/Requests     Validación de formularios
app/Services     Lógica de negocio (visitas, conteo, mensajes, reportes, imágenes)
app/Views        Plantillas
database/        Migraciones SQL, datos de prueba y scripts
public/          Punto de entrada, CSS, JS e íconos
storage/         Fotos y logs (fuera del alcance del navegador)
```
