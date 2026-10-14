/* JACOB · Botón "Iniciar sesión con Google" (Google Identity Services), para entrar o para vincular la cuenta */
(function () {
    'use strict';

    var caja = document.querySelector('[data-google]');
    if (!caja) return;

    var estado = document.querySelector('[data-google-estado]');
    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function avisar(texto) {
        if (estado) estado.textContent = texto;
    }

    function alVolverDeGoogle(respuesta) {
        avisar('Verificando…');
        fetch(caja.dataset.endpoint, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': token },
            body: new URLSearchParams({ _token: token, credential: respuesta.credential })
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (d.ok) { window.location.href = d.redirect; return; }
            avisar('⚠️ ' + (d.message || 'No se pudo entrar con Google.'));
        }).catch(function () { avisar('⚠️ No se pudo conectar. Revisá la conexión.'); });
    }

    function iniciar() {
        google.accounts.id.initialize({ client_id: caja.dataset.clientId, callback: alVolverDeGoogle });
        google.accounts.id.renderButton(caja, {
            type: 'standard', theme: 'outline', size: 'large', shape: 'pill',
            text: caja.dataset.texto || 'signin_with', locale: 'es',
            width: Math.min(caja.offsetWidth || 320, 400)
        });
    }

    var s = document.createElement('script');
    s.src = 'https://accounts.google.com/gsi/client';
    s.async = true;
    s.onload = iniciar;
    s.onerror = function () { avisar('No se pudo cargar el botón de Google (¿sin conexión?).'); };
    document.head.appendChild(s);
})();
