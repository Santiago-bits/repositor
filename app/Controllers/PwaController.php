<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;

/**
 * Archivos de la PWA (app instalable). Se generan desde PHP para que las rutas
 * funcionen igual en /repositor (XAMPP) y en la raíz (Hostinger).
 */
final class PwaController extends Controller
{
    /** Archivos que se guardan en el celular para abrir rápido y mostrar la pantalla sin conexión. */
    private const ARCHIVOS = [
        'assets/vendor/bootstrap/bootstrap.min.css',
        'assets/vendor/bootstrap/bootstrap.bundle.min.js',
        'assets/vendor/bootstrap-icons/bootstrap-icons.min.css',
        'assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff2',
        'assets/css/jacob.css',
        'assets/js/app.js',
        'assets/js/inicio.js',
        'assets/js/productos.js',
        'assets/js/escaner.js',
        'assets/js/visita.js',
        'assets/img/logo.svg',
        'assets/icons/icon-192.png',
    ];

    public function manifest(): never
    {
        header('Content-Type: application/manifest+json; charset=utf-8');
        echo json_encode([
            'id'               => url('/'),
            'name'             => 'JACOB · ' . APP_TAGLINE,
            'short_name'       => APP_NAME,
            'description'      => APP_TAGLINE,
            'lang'             => 'es-AR',
            'start_url'        => url('/'),
            'scope'            => url('/'),
            'display'          => 'standalone',
            'orientation'      => 'portrait',
            'background_color' => '#F7F6FB',
            'theme_color'      => '#7C3AED',
            'icons'            => [
                ['src' => url('assets/icons/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => url('assets/icons/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => url('assets/icons/icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts'        => [
                ['name' => 'Productos', 'url' => url('/productos')],
                ['name' => 'Mensaje del día', 'url' => url('/mensaje')],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Service worker: guarda CSS/JS/íconos (se abren al instante) y, si no hay señal,
     * muestra una pantalla de "sin conexión". Nunca guarda páginas con datos.
     */
    public function serviceWorker(): never
    {
        $firma = '';
        foreach (self::ARCHIVOS as $archivo) {
            $firma .= $archivo . @filemtime(BASE_PATH . '/public/' . $archivo);
        }
        $cache = 'jacob-' . substr(md5($firma), 0, 10);
        // Con la misma versión (?v=) que piden las páginas: así nunca se sirve un CSS viejo.
        $precache = array_map(fn ($a) => asset($a), self::ARCHIVOS);
        $precache[] = url('/offline');

        header('Content-Type: application/javascript; charset=utf-8');
        header('Cache-Control: no-cache');
        echo "const CACHE = " . json_encode($cache) . ";\n"
            . "const PRECACHE = " . json_encode($precache, JSON_UNESCAPED_SLASHES) . ";\n"
            . "const OFFLINE = " . json_encode(url('/offline'), JSON_UNESCAPED_SLASHES) . ";\n"
            . <<<'JS'

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then((c) => c.addAll(PRECACHE.map((u) => new Request(u, { cache: 'reload' })))).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys()
            .then((claves) => Promise.all(claves.filter((k) => k.startsWith('jacob-') && k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    // Páginas: siempre de la red (datos al día); sin señal, la pantalla offline.
    if (req.mode === 'navigate') {
        e.respondWith(fetch(req).catch(() => caches.match(OFFLINE)));
        return;
    }

    // CSS, JS, fuentes e íconos: primero del celular.
    if (url.pathname.includes('/assets/')) {
        e.respondWith(
            caches.match(req).then((guardado) => guardado || fetch(req).then((res) => {
                if (res.ok) {
                    const copia = res.clone();
                    caches.open(CACHE).then((c) => c.put(req, copia));
                }
                return res;
            }))
        );
    }
});
JS;
        exit;
    }

    public function offline(): void
    {
        View::render('offline', [], null);
    }
}
