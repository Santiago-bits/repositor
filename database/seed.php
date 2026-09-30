<?php
/**
 * Carga DATOS DE PRUEBA (usuarios, 3 locales con coordenadas ficticias, productos, promos y tareas).
 *
 *   c:\xampp\php\php.exe database/seed.php
 *
 * Solo corre en local. Si la base ya tiene usuarios, no hace nada.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Core\Database;

if (APP_ENV !== 'local') {
    fwrite(STDERR, "Los datos de prueba solo se cargan en local (tienen contraseñas conocidas).\n"
        . "En el servidor usá: php database/crear-admin.php\n");
    exit(1);
}

$db = Database::connection();

if ((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
    echo "La base ya tiene usuarios: no se cargan datos de prueba.\n"
        . "Para empezar de cero: c:\\xampp\\php\\php.exe database/migrate.php --fresh\n";
    exit(0);
}

/** Código EAN-13 ficticio pero con dígito verificador válido (sirve para probar el escáner). */
function ean13(string $doce): string
{
    $suma = 0;
    foreach (str_split($doce) as $i => $d) {
        $suma += (int) $d * ($i % 2 === 0 ? 1 : 3);
    }
    return $doce . ((10 - $suma % 10) % 10);
}

$insert = function (string $sql, array $params) use ($db): int {
    $db->prepare($sql)->execute($params);
    return (int) $db->lastInsertId();
};

$password = 'Jacob2026!';

Database::transaction(function () use ($db, $insert, $password): void {
    $roles = $db->query('SELECT slug, id FROM roles')->fetchAll(PDO::FETCH_KEY_PAIR);
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // ── Usuarios ──────────────────────────────────────────────
    $sqlUser = 'INSERT INTO users (role_id, nombre, apellido, email, password_hash) VALUES (?, ?, ?, ?, ?)';
    $adminId = $insert($sqlUser, [$roles['admin'], 'Admin', 'JACOB', 'admin@jacob.test', $hash]);
    $repoId = $insert($sqlUser, [$roles['repositor'], 'Santiago', 'Repositor', 'repositor@jacob.test', $hash]);

    // ── Locales (COORDENADAS FICTICIAS cerca del centro de Concordia) ──
    $sqlLocal = 'INSERT INTO locales (nombre, tipo, direccion, latitud, longitud, radio_m, es_prueba, observaciones)
                 VALUES (?, ?, ?, ?, ?, ?, 1, ?)';
    $nota = 'DATOS DE PRUEBA: dirección y coordenadas ficticias.';
    $locales = [
        $insert($sqlLocal, ['Supermercado A', 'supermercado', 'Av. Ejemplo 123', -31.3915000, -58.0170000, 100, $nota]),
        $insert($sqlLocal, ['Supermercado B', 'supermercado', 'Calle Prueba 456', -31.3980000, -58.0250000, 100, $nota]),
        $insert($sqlLocal, ['Chino A', 'chino', 'Pasaje Demo 789', -31.3880000, -58.0300000, 60, $nota]),
    ];
    foreach ($locales as $localId) {
        $db->prepare('INSERT INTO local_user (user_id, local_id) VALUES (?, ?)')->execute([$repoId, $localId]);
    }

    // ── Categorías ───────────────────────────────────────────
    $sqlCat = 'INSERT INTO categorias (parent_id, nombre) VALUES (?, ?)';
    $conAlcohol = $insert($sqlCat, [null, 'Bebidas con alcohol']);
    $sinAlcohol = $insert($sqlCat, [null, 'Bebidas sin alcohol']);
    $aperitivos = $insert($sqlCat, [$conAlcohol, 'Aperitivos']);
    $cervezas = $insert($sqlCat, [$conAlcohol, 'Cervezas']);
    $vinos = $insert($sqlCat, [$conAlcohol, 'Vinos']);
    $insert($sqlCat, [$conAlcohol, 'Destilados']);
    $gaseosas = $insert($sqlCat, [$sinAlcohol, 'Gaseosas']);
    $aguas = $insert($sqlCat, [$sinAlcohol, 'Aguas']);

    // ── Productos (bebidas de ejemplo; códigos de barras FICTICIOS) ──
    $sqlProd = 'INSERT INTO productos (nombre, marca, codigo_barras, categoria_id, presentacion, unidad_medida)
                VALUES (?, ?, ?, ?, ?, ?)';
    $fernet = $insert($sqlProd, ['Fernet Branca', 'Branca', ean13('779000000001'), $aperitivos, '750 ml', 'ml']);
    $gancia = $insert($sqlProd, ['Gancia', 'Gancia', ean13('779000000002'), $aperitivos, '950 ml', 'ml']);
    $cerveza = $insert($sqlProd, ['Cerveza Quilmes', 'Quilmes', ean13('779000000003'), $cervezas, '1 L', 'l']);
    $lata = $insert($sqlProd, ['Cerveza Quilmes lata', 'Quilmes', ean13('779000000004'), $cervezas, '473 cc', 'cc']);
    $vino = $insert($sqlProd, ['Vino Malbec', 'Bodega Ejemplo', ean13('779000000005'), $vinos, '750 ml', 'ml']);
    $coca = $insert($sqlProd, ['Coca-Cola', 'Coca-Cola', ean13('779000000006'), $gaseosas, '2,25 L', 'l']);
    $agua = $insert($sqlProd, ['Agua Villavicencio', 'Villavicencio', ean13('779000000007'), $aguas, '1,5 L', 'l']);

    foreach ([$fernet, $gancia, $cerveza, $lata, $vino, $coca, $agua] as $productoId) {
        foreach ($locales as $localId) {
            $db->prepare('INSERT INTO producto_local (producto_id, local_id, stock_habitual) VALUES (?, ?, 12)')
                ->execute([$productoId, $localId]);
        }
    }

    // ── Promociones: una que termina hoy (para probar el conteo) y otra de viernes a lunes ──
    $sqlPromo = 'INSERT INTO promociones (producto_id, fecha_inicio, fecha_fin, precio_normal, precio_promo, observaciones, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)';
    $hoy = date('Y-m-d');
    $viernes = date('Y-m-d', strtotime('next friday'));
    $lunes = date('Y-m-d', strtotime($viernes . ' +3 days'));

    $promos = [
        [$insert($sqlPromo, [$fernet, date('Y-m-d', strtotime('-3 days')), $hoy, 12500, 10900, 'Prueba: termina hoy', $adminId]), $locales],
        [$insert($sqlPromo, [$cerveza, date('Y-m-d', strtotime('-3 days')), $hoy, 2800, 2300, 'Prueba: termina hoy', $adminId]), $locales],
        [$insert($sqlPromo, [$coca, $viernes, $lunes, null, null, 'Prueba: viernes a lunes', $adminId]), [$locales[0], $locales[1]]],
    ];
    foreach ($promos as [$promoId, $localesPromo]) {
        foreach ($localesPromo as $localId) {
            $db->prepare('INSERT INTO promocion_local (promocion_id, local_id) VALUES (?, ?)')->execute([$promoId, $localId]);
        }
    }

    // ── Tareas de ejemplo (configurables desde admin en la Etapa 5) ──
    $sqlTarea = 'INSERT INTO tareas (nombre, tipo, periodicidad, dias_semana, prioridad) VALUES (?, ?, ?, ?, ?)';
    $tareas = [
        $insert($sqlTarea, ['Conteo de promociones', 'conteo_promociones', 'semanal', '1', 'alta']),
        $insert($sqlTarea, ['Control de vencimientos', 'vencimientos', 'cada_visita', null, 'media']),
        $insert($sqlTarea, ['Reposición', 'reposicion', 'cada_visita', null, 'baja']),
    ];
    foreach ($tareas as $tareaId) {
        foreach ($locales as $localId) {
            $db->prepare('INSERT INTO tarea_local (tarea_id, local_id) VALUES (?, ?)')->execute([$tareaId, $localId]);
        }
    }
});

echo "Datos de prueba cargados.\n\n";
echo "  Admin:      admin@jacob.test      / {$password}\n";
echo "  Repositor:  repositor@jacob.test  / {$password}\n";
