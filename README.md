# repositores.rv · Reposición RAVSA

Anotador rápido de **fechas de vencimiento** para repositores: el GPS detecta en qué local estás, buscás o escaneás el producto, ponés la fecha y listo. Te avisa con una notificación en el celular unos días antes de que venza. Se instala en el celular como app (PWA).

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

### Avisos (notificaciones)

Funcionan solos: la app revisa como mucho una vez por hora (de 8 a 21) cuando alguien la abre. Para que salgan aunque nadie la abra, en hPanel → **Avanzado → Cron Jobs** agregar una tarea **cada hora**:

```
php /home/USUARIO/domains/DOMINIO/public_html/database/enviar-avisos.php
```

(La ruta exacta la muestra hPanel en el Administrador de archivos.) Las claves de los avisos se crean solas la primera vez.

## Cómo se usa

- **Anotar (pantalla principal):** al abrir la app, el GPS busca en qué local estás y lo elige solo (también podés tocar el local arriba, o **Detectar**). Después:
  1. Escribí 2-3 letras del producto (ej: «quil») y tocalo, o tocá **Escanear**. Si no está en el catálogo, tocá «Anotar … así, escrito».
  2. La fecha se escribe solo con números: `2011` = 20/11 (de este año o del que viene), `201126` o `20112026`. Las barras se ponen solas. También está el calendario.
  3. Cantidad y nota son opcionales. **Anotar** y ya queda listo para el siguiente producto.
- **Corregir:** tocá la fecha o el lápiz: cambiás producto, fecha, cantidad o nota sin borrarla (el tacho está adentro). Al escribir el producto aparece el catálogo para elegir, o lo dejás escrito. Si cambiás la fecha, vuelve a avisar cuando corresponda.
- **Enter del celular:** pasa al campo siguiente (producto → fecha → cantidad → nota) y en el último guarda.
- **Código nuevo:** si escaneás un código que no está asociado, escribí el producto y elegilo: el código queda guardado y la próxima vez lo reconoce solo.
- **Fechas:** todas tus fechas pendientes, por local, de la más próxima a la más lejana. Con **✓** marcás que ya no está (se vendió o lo retiraste) y deja de aparecer.
- **Colores:** rojo vencido, naranja pocos días, amarillo próximamente, verde sin apuro (se ajusta en Gestión → Configuración).
- **Avisos:** Mi cuenta → **Avisos** → «Activar avisos en este celular» y elegí cuántos días antes (7 por defecto). Te llega una notificación cuando algo entra en ese plazo, con el producto y el local. «Probar aviso» manda uno de prueba. En iPhone: primero instalá la app (Compartir → Agregar a inicio) y activá los avisos desde ahí.
- **Cada uno lo suyo:** cada repositor ve solo sus fechas. El admin ve las de todos (con el nombre de quién la anotó).
- **Locales:** son de todos. Cualquiera crea uno con **+ Local** (link de Google Maps, Plus Code o coordenadas para que el GPS lo detecte). Editar o borrar: Gestión → Locales (admin).
- **Productos:** el catálogo de Chess (Gestión → Productos → subir el JSON) más los que se cargan a mano. En la ficha de cada producto se ven sus fechas anotadas.
- **Mi cuenta:** el círculo de arriba a la derecha: foto, nombre, Gmail, contraseña, avisos, apariencia y cerrar sesión.
- **Usuarios y entrar con Google:** Gestión → Usuarios (admin). Para el botón de Google, el admin pega el ID de cliente en Gestión → Configuración.
## Cambios

- **2026-11-04** · Al corregir una fecha, el producto se busca en el catálogo igual que al anotar.
- **2026-11-04** · **Editar lo anotado** (producto, fecha, cantidad y nota) sin borrar y volver a crear · El Enter del celular pasa al campo siguiente y en el último guarda · Los botones de las fechas ahora también funcionan en la ficha del producto.
- **2026-11-02** · **Nuevo enfoque: anotador de fechas.** La app pasa a ser un anotador rápido de fechas de vencimiento: el GPS elige el local al abrir, buscás o escaneás el producto, escribís la fecha solo con números y queda anotado. Pestaña **Fechas** con todo lo pendiente por local (✓ cuando ya no está). **Avisos** con notificación en el celular unos días antes (cada uno elige cuántos). Se sacaron visitas, faltantes, promos, fotos, historial, equipo y resumen; las tablas viejas quedan en la base. Las fechas que ya estaban cargadas pasaron al anotador (migraciones 016 y 017).
- **2026-10-14** · Diseño: **Promociones** con pestañas parejas y contador, tarjetas con foto, fechas, «quedan X días» y precio promo vs normal · **Usuarios** ordenado en Datos, Acceso y Rol (cada rol explica qué puede hacer) · La barra de Guardar fija ahora tiene fondo y no tapa lo de abajo.
- **2026-10-14** · Mi cuenta: se sacó el teléfono y se puede **cambiar el Gmail** con el que se entra (pide la contraseña).
- **2026-10-14** · Mi cuenta: **foto de perfil**, nombre, apellido y teléfono, **cambiar contraseña** y **cerrar sesión en los otros dispositivos** (también pasa al cambiar la contraseña). Migración 015.
- **2026-10-14** · «Perfil» pasa a ser **Mi cuenta** (se abre desde el círculo de arriba) y solo tiene tus ajustes. **Gestión** sale de ahí: en el celular es una pestaña de la barra de abajo (solo admin).
- **2026-10-14** · Google: ya no se vincula desde Perfil. El admin carga el «Gmail para entrar con Google» al crear o editar el usuario (opcional si el email ya es ese Gmail).
- **2026-10-14** · Los repositores pueden **crear locales** (Inicio → Locales → + Nuevo) y los locales son de todos: ya no se asignan por usuario · Diseño **centrado**: saludo, títulos de cada pantalla y títulos de sección.
- **2026-10-14** · Datos separados por usuario: fechas cortas, promos (salvo las que carga el admin), historial de stock de un producto y Retirar son de cada uno. Antes se mezclaban si dos repositores iban al mismo local. El admin sigue viendo todo.
- **2026-10-14** · Configuración: el ID de cliente de Google ya no se muestra (solo «Configurado»). Para cambiarlo se pega uno nuevo; dejándolo vacío se conserva; con «Quitar» se saca el botón de Google.
- **2026-10-14** · Supervisión: nueva sección **Gestión → Equipo** (última vez activo de cada uno, visitas, faltantes, vencimientos y fotos por período, y el detalle de cada persona) · Fotos: filtro por persona y quién la sacó · Resumen: las últimas visitas dicen quién las hizo (migración 014).
- **2026-10-14** · Rol **Vendedor** (migración 013): ve los faltantes de cada local según la última visita, las fechas cortas y los productos. No puede hacer visitas ni entrar a Gestión.
- **2026-10-14** · Vencimientos: botón **Escanear**. El producto se agrega solo y queda abierta la fecha; si el código no estaba asociado, lo buscás por nombre y queda guardado para la próxima.
- **2026-10-14** · La app pasa a llamarse **repositores.rv** y usa los colores de RAVSA (azul #1570B8) en modo claro y oscuro, con ícono nuevo «rv». El nombre ahora está en `app/bootstrap.php` (ya no en `config.php`), así llega solo al servidor. En el celular, si ya estaba instalada, puede tardar en cambiar el ícono: desinstalar y volver a instalar.
- **2026-10-14** · Vuelve **Gestión → Usuarios**: el administrador puede crear usuarios nuevos, editarlos, desactivarlos o borrarlos. Se ve quién entra con Google.
- **2026-10-14** · **Iniciar sesión con Google**: botón en el login; cada usuario vincula su Gmail en Perfil (migración 012). El ID de cliente se carga en Configuración.
- **2026-10-07** · Escáner: si el código no está asociado, te pregunta qué producto es (busca entre los que no tienen código) y lo guarda; la próxima vez lo reconoce solo.
- **2026-10-07** · Importar el maestro de artículos de **Chess** (JSON): nombres legibles (marca + sabor + envase), categoría por división, código de artículo, unidades por bulto y precios (consumidor final y base, por unidad y por bulto). Se ven en la ficha del producto y en la visita. Se busca también por código de artículo (migración 011).
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
