/* repositores.rv · Avisos en el celular (notificaciones push): activar, apagar y probar */
(function () {
    'use strict';

    var caja = document.querySelector('[data-avisos]');
    if (!caja) return;

    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var estado = caja.querySelector('[data-avisos-estado]');
    var btnActivar = caja.querySelector('[data-avisos-activar]');
    var btnApagar = caja.querySelector('[data-avisos-apagar]');
    var btnProbar = caja.querySelector('[data-avisos-probar]');

    function decir(texto) { estado.textContent = texto; }

    function post(url, datos) {
        var cuerpo = new URLSearchParams(datos || {});
        cuerpo.set('_token', token);
        return fetch(url, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-Token': token }, body: cuerpo })
            .then(function (r) { return r.json(); });
    }

    function claveABytes(b64) {
        var relleno = '='.repeat((4 - b64.length % 4) % 4);
        var bin = atob((b64 + relleno).replace(/-/g, '+').replace(/_/g, '/'));
        var bytes = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
        return bytes;
    }

    var soporta = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    var esIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
    var instalada = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    function mostrar(suscripcion) {
        btnActivar.hidden = !!suscripcion;
        btnApagar.hidden = !suscripcion;
        btnProbar.hidden = !suscripcion;
        if (Notification.permission === 'denied') {
            btnActivar.hidden = true;
            decir('Las notificaciones están bloqueadas. Permitilas en los ajustes del navegador para esta página.');
        } else {
            decir(suscripcion ? '✅ Avisos activados en este celular.' : 'Los avisos están apagados en este celular.');
        }
    }

    if (!soporta) {
        decir(esIos && !instalada
            ? 'En iPhone: primero instalá la app (Compartir → Agregar a inicio), abrila desde el ícono y activá los avisos ahí.'
            : 'Este navegador no permite notificaciones. Probá con Chrome.');
        return;
    }

    navigator.serviceWorker.ready
        .then(function (reg) { return reg.pushManager.getSubscription(); })
        .then(mostrar)
        .catch(function () { decir('No se pudo revisar los avisos.'); });

    btnActivar.addEventListener('click', function () {
        btnActivar.disabled = true;
        decir('Activando…');
        Notification.requestPermission()
            .then(function (permiso) {
                if (permiso !== 'granted') throw new Error('permiso');
                return fetch(caja.dataset.claveUrl, { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); });
            })
            .then(function (d) {
                if (!d.clave) throw new Error(d.message || 'sin clave');
                return navigator.serviceWorker.ready.then(function (reg) {
                    return reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: claveABytes(d.clave) });
                });
            })
            .then(function (sub) {
                var j = sub.toJSON();
                return post(caja.dataset.suscribirUrl, { endpoint: j.endpoint, p256dh: j.keys.p256dh, auth: j.keys.auth })
                    .then(function (r) {
                        if (!r.ok) throw new Error(r.message || 'no se guardó');
                        mostrar(sub);
                    });
            })
            .catch(function (e) {
                decir(e.message === 'permiso' ? 'No diste permiso para las notificaciones.' : 'No se pudo activar (' + e.message + ').');
            })
            .then(function () { btnActivar.disabled = false; });
    });

    btnApagar.addEventListener('click', function () {
        navigator.serviceWorker.ready
            .then(function (reg) { return reg.pushManager.getSubscription(); })
            .then(function (sub) {
                if (!sub) return mostrar(null);
                return post(caja.dataset.apagarUrl, { endpoint: sub.endpoint })
                    .then(function () { return sub.unsubscribe(); })
                    .then(function () { mostrar(null); });
            });
    });

    btnProbar.addEventListener('click', function () {
        btnProbar.disabled = true;
        decir('Enviando…');
        post(caja.dataset.probarUrl).then(function (r) {
            decir(r.message || (r.ok ? 'Enviado.' : 'No se pudo enviar.'));
        }).catch(function () {
            decir('Sin conexión.');
        }).then(function () { btnProbar.disabled = false; });
    });
})();
