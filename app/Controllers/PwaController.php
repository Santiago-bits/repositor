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
        'assets/js/anotador.js',
        'assets/js/avisos.js',
        'assets/js/productos.js',
        'assets/js/escaner.js',
        'assets/img/logo.svg',
        'assets/icons/icon-192.png',
    ];

    public function manifest(): never
    {
        header('Content-Type: application/manifest+json; charset=utf-8');
        echo json_encode([
            'id'               => url('/'),
            'name'             => MARCA . ' · ' . MARCA_LEMA,
            'short_name'       => MARCA,
            'description'      => MARCA_LEMA,
            'lang'             => 'es-AR',
            'start_url'        => url('/'),
            'scope'            => url('/'),
            'display'          => 'standalone',
            'orientation'      => 'portrait',
            'background_color' => '#F4F7FA',
            'theme_color'      => '#1570B8',
            'icons'            => [
                ['src' => url('assets/icons/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => url('assets/icons/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => url('assets/icons/icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts'        => [
                ['name' => 'Anotar', 'url' => url('/')],
                ['name' => 'Fechas', 'url' => url('/fechas')],
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
            . "const RESUMEN = " . json_encode(url('/avisos/resumen'), JSON_UNESCAPED_SLASHES) . ";\n"
            . "const FECHAS = " . json_encode(url('/fechas'), JSON_UNESCAPED_SLASHES) . ";\n"
            . "const ICONO = " . json_encode(url('assets/icons/icon-192.png'), JSON_UNESCAPED_SLASHES) . ";\n"
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

// Aviso de fechas por vencer: llega un "toque" vacío y se pregunta a la app qué mostrar.
self.addEventListener('push', (e) => {
    e.waitUntil(
        fetch(RESUMEN, { credentials: 'include', headers: { Accept: 'application/json' } })
            .then((r) => (r.ok ? r.json() : null))
            .catch(() => null)
            .then((d) => {
                d = d || { titulo: 'Fechas por vencer', cuerpo: 'Tenés productos que vencen pronto.', url: FECHAS };
                return self.registration.showNotification(d.titulo, {
                    body: d.cuerpo, icon: ICONO, badge: ICONO, tag: 'fechas', renotify: true, data: { url: d.url || FECHAS },
                });
            })
    );
});

self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    const destino = (e.notification.data && e.notification.data.url) || FECHAS;
    e.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((ventanas) => {
            for (const v of ventanas) {
                if ('focus' in v) return v.navigate(destino).then((w) => (w || v).focus());
            }
            return self.clients.openWindow(destino);
        })
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
