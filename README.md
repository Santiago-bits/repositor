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

### Actualización automática

Cada cambio se sube a `main` en GitHub. Si en hPanel está activado **GIT → Auto Deployment** (con el webhook de GitHub), la web se actualiza sola. Si el cambio trae un archivo nuevo en `database/migrations/`, se aplica solo en la primera visita a la web (también se puede correr `php database/migrate.php` por SSH).

## Cómo se usa

- **Productos:** el catálogo es único y vale para todos los locales (una Quilmes es la misma en cualquier súper o chino). Se cargan de a uno, escaneando, o todos juntos desde un Excel.
- **Subir Excel:** Gestión → Productos → **Excel** (o el botón Excel en Productos). Acepta `.xlsx` o `.csv` con los títulos *Nombre, Marca, Presentación, Código, Categoría, Subcategoría* (solo Nombre es obligatorio). Si el producto ya existe se actualiza, no se duplica; las celdas vacías no borran nada; las categorías se crean solas y la unidad (ml, cc, L) se saca de la presentación. Hay una plantilla para descargar.
- **En el local:** arriba ves lo que vence primero (ponelo adelante en la heladera; si ya lo sacaste, Retirar). Después: Vencimientos, Promos del finde y Faltantes.
- **En el local:** abajo tenés siempre Vencimientos, Promos, Faltantes y Fotos.
- **Fotos:** elegí de qué es (Góndola, Heladera, Exhibición externa u Otra), escribí qué se ve si querés, y sacala.
- **Faltantes:** botón «Faltantes» en el local. Buscás el producto, lo tocás y elegís «Sin stock» o «Poco». Con «Guardar y armar lista para el vendedor» sale la lista agrupada por categoría (Cervezas, Gaseosas, Agua…) para copiar o mandar por WhatsApp. También desde la visita: «Lista vendedor».
- **Conteo de promos:** cuando quieras, desde «Promos del finde» → «Anotar cuánto quedó de las promos que terminaron».
- **Visitas:** no hay que finalizarlas. Entrás al local, cargás lo que quieras y listo: se cierra sola cuando el GPS ve que te fuiste, cuando entrás a otro local o al día siguiente. Si volvés el mismo día, seguís en la misma visita.
- **Ubicación de un local:** en Gestión → Locales, pegá el link de Google Maps (Compartir → copiar link), un Plus Code (ej: JX3M+PF) o las coordenadas. Se completa al tocar «Buscar» o al guardar.
- **Vencimientos:** en la visita, botón «Vencimientos». Buscás el producto, lo tocás y le ponés la fecha, la cantidad (opcional) y una nota con la ubicación (ej: depósito). Podés agregar varios (también el mismo producto con otro lote) y guardar todo junto.
- **Fechas cortas:** en Inicio, debajo de tus locales, ves lo que vence en los próximos días (según Configuración, 15 por defecto) y lo vencido en la última semana, con local, cantidad y nota. Se toma el último relevamiento de cada producto en cada local. Con **Retirar** marcás que ya lo sacaste y deja de aparecer.
- **Promos del finde:** en la visita, escribís en el buscador, tocás el producto y queda abajo en "En promo". Sin buscar no se muestra la lista completa. El stock es opcional. Después, "Guardar y armar mensaje".

## Cambios

- **2026-10-01** · Detección del local más precisa: espera hasta tener una ubicación nueva y precisa (±25 m o lo mejor en 10 s) y muestra el margen de error · Gestión → Locales avisa si dos locales tienen la misma ubicación (casi seguro mal cargada).
- **2026-10-01** · Dentro del local, la barra de abajo cambia a: Local, Vencimientos, Promos, Faltantes y Fotos · Fotos con tipo (Góndola, Heladera, Exhibición externa, Otra) y descripción de qué se ve (migración 010) · La imagen de cada producto aparece en las listas (buscador, faltantes, promos, vencimientos, fechas cortas y lo registrado).
- **2026-09-30** · Se sacó «Registrar productos» de la visita (contaba stock de todo). Para buscar o escanear un código de barras está la pestaña **Productos**.
- **2026-09-30** · «Registrado en esta visita» muestra solo lo que quedó anotado (faltantes, vencimientos sin retirar, promo vigente); si sacás algo, desaparece.
- **2026-09-30** · **Faltantes** en vez de contar stock: en el local buscás lo que no hay o hay poco y armás la lista para el vendedor (agrupada por categoría, lista para WhatsApp) · En cada producto, solo «Hay / Poco / Sin stock» (se guarda al tocar) · Sin recordatorios del conteo de promos: se entra cuando querés desde «Promos del finde» · Resumen: «Faltantes hoy».
- **2026-09-30** · Revisión según el trabajo diario: la visita arranca con «Vence primero en este local» (para acomodar la heladera, con Retirar) y los botones en orden: Vencimientos, Promos del finde, Contar promos (resaltado los lunes o si hay para contar), Foto, Observación y Stock · En cada producto, vencimientos primero (con nota) y el stock opcional abajo · Inicio muestra cuántas promos hay para contar en cada local · Resumen: «Promos para contar» en vez de promos activas · Se sacaron Usuarios y Reportes.
- **2026-09-30** · Visita: "Registrado en esta visita" muestra en palabras el stock, cada lote con su fecha, cantidad y nota, y si está en promo · Observaciones sin frases precargadas.
- **2026-09-30** · Resumen renovado: tarjetas (visitas de hoy, sin stock, fechas cortas, promos), tu semana con barritas por día, locales que hace mucho no visitás, faltantes y últimas visitas.
- **2026-09-30** · Fechas cortas: botón **Retirar** para cuando sacás el producto de la góndola (el lote deja de aparecer pero queda en el historial) · Barra de abajo pareja con 4 botones.
- **2026-09-30** · Se sacaron las Tareas (pestaña, Gestión → Tareas y las tareas dentro de la visita). Las tablas quedan en la base sin usarse.
- **2026-09-30** · Se sacó la sección Gestión → Relevamientos (las visitas se ven en Historial).
- **2026-09-30** · Inicio: sección **Fechas cortas** con lo que vence en los próximos días (y lo vencido hace poco) en tus locales.
- **2026-09-30** · Botón **Vencimientos** en la visita: buscás el producto, le ponés fecha, cantidad y una nota (ubicación); se pueden cargar varios juntos · Las migraciones nuevas se aplican solas en el servidor después de cada deploy (sin SSH).
- **2026-09-30** · Visitas sin "en proceso": se cierran solas cuando el GPS detecta que te fuiste del local (o al entrar a otro, o al día siguiente); si volvés el mismo día sigue la misma visita · Locales: se puede pegar el link de Google Maps (también los cortos), un Plus Code o coordenadas y la ubicación se completa sola.
- **2026-09-30** · Promos del finde: la lista completa ya no se muestra; los productos aparecen solo al buscar y abajo quedan los marcados · Se sacó el aviso rojo "Conteo de promociones" de la visita.
- **2026-09-30** · Productos universales (ya no se asignan por local) · Importar productos desde Excel/CSV · Promos del finde con buscador y los marcados arriba · La app ya no muestra diseño viejo después de actualizar (se recarga sola una vez).
- Promos del finde sin fechas · temática de bebidas (ml, cc, L).
- Todo se puede borrar · visitas sin tiempo · tarjetas más compactas.
- Etapas 1 a 7: sistema completo de relevamiento.

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
