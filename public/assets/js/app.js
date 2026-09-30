/* JACOB · comportamiento general (sin dependencias) */
(function () {
    'use strict';

    // ---------- Tema claro / oscuro ----------
    var THEME_KEY = 'jacob-theme';
    var media = window.matchMedia('(prefers-color-scheme: dark)');

    function readTheme() {
        try { return localStorage.getItem(THEME_KEY) || 'auto'; } catch (e) { return 'auto'; }
    }

    function applyTheme(theme) {
        var dark = theme === 'dark' || (theme === 'auto' && media.matches);
        document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
        document.querySelectorAll('[data-theme-set]').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.themeSet === theme);
        });
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-theme-set]');
        if (!btn) return;
        try { localStorage.setItem(THEME_KEY, btn.dataset.themeSet); } catch (err) { /* sin almacenamiento */ }
        applyTheme(btn.dataset.themeSet);
    });

    media.addEventListener('change', function () {
        if (readTheme() === 'auto') applyTheme('auto');
    });

    applyTheme(readTheme());

    // ---------- Formularios: confirmación y evitar doble envío ----------
    document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) return; // lo maneja otro script (envío con fetch)
        var form = e.target;
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
            e.preventDefault();
            return;
        }
        var btn = form.querySelector('[type=submit]');
        if (btn) setTimeout(function () { btn.disabled = true; }, 0);
    });

    // Al volver con el botón "atrás", reactivar botones.
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('[type=submit]:disabled').forEach(function (b) { b.disabled = false; });
    });

    // ---------- Mostrar / ocultar contraseña ----------
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-toggle-password]');
        if (!btn) return;
        var input = document.getElementById(btn.dataset.togglePassword);
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.querySelector('.bi').className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
    });

    // ---------- "Volver": si venimos de una página de JACOB, volver a ella (conserva la búsqueda) ----------
    document.addEventListener('click', function (e) {
        var link = e.target.closest('[data-volver]');
        if (!link || !document.referrer) return;
        if (new URL(document.referrer).origin === window.location.origin && history.length > 1) {
            e.preventDefault();
            history.back();
        }
    });

    // ---------- Valores rápidos: <button data-set-valor="2026-10-05" data-target="fecha_fin"> ----------
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-set-valor]');
        if (!b) return;
        var campo = document.getElementById(b.dataset.target);
        if (campo) {
            campo.value = b.dataset.setValor;
            campo.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    // ---------- Mostrar según un select: <div data-mostrar-si="periodicidad:semanal,mensual"> ----------
    var condicionales = document.querySelectorAll('[data-mostrar-si]');
    function actualizarCondicionales() {
        condicionales.forEach(function (el) {
            var partes = el.dataset.mostrarSi.split(':');
            var select = document.getElementById(partes[0]);
            if (select) el.hidden = partes[1].split(',').indexOf(select.value) === -1;
        });
    }
    if (condicionales.length) {
        document.addEventListener('change', actualizarCondicionales);
        actualizarCondicionales();
    }

    // ---------- Copiar al portapapeles: <button data-copiar="#mensaje"> ----------
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-copiar]');
        if (!b) return;
        var origen = document.querySelector(b.dataset.copiar);
        var texto = origen.value !== undefined ? origen.value : origen.textContent;
        var original = b.innerHTML;
        var listo = function () {
            b.innerHTML = '<i class="bi bi-check2-circle me-1"></i> ¡COPIADO!';
            setTimeout(function () { b.innerHTML = original; }, 2000);
        };
        var alternativa = function () { // sin HTTPS no hay portapapeles moderno
            origen.focus();
            origen.select();
            try { document.execCommand('copy'); listo(); } catch (err) { window.alert('Seleccioná el texto y copialo a mano.'); }
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(texto).then(listo, alternativa);
        } else {
            alternativa();
        }
    });

    // ---------- App instalable (PWA) ----------
    var base = (document.querySelector('meta[name="base-url"]') || {}).content || '/';
    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(base + 'sw.js').catch(function () { /* sin PWA, la web funciona igual */ });
        });
    }

    var pedidoInstalacion = null;
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        pedidoInstalacion = e;
        document.querySelectorAll('[data-instalar]').forEach(function (b) { b.hidden = false; });
    });
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-instalar]');
        if (!b || !pedidoInstalacion) return;
        pedidoInstalacion.prompt();
        pedidoInstalacion.userChoice.then(function () {
            pedidoInstalacion = null;
            b.hidden = true;
        });
    });

    // iPhone no tiene botón de instalar: se muestran las instrucciones.
    var instalada = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    var esIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
    document.querySelectorAll('[data-ios-instalar]').forEach(function (el) { el.hidden = !(esIOS && !instalada); });

    // ---------- Mensajes de éxito que se cierran solos ----------
    document.querySelectorAll('.alert[data-autohide="1"]').forEach(function (el) {
        setTimeout(function () {
            if (window.bootstrap) bootstrap.Alert.getOrCreateInstance(el).close();
        }, 4000);
    });

    // ---------- Ubicación actual → latitud / longitud ----------
    function setStatus(el, type, text) {
        if (!el) return;
        el.className = 'small mb-2 text-' + ({ ok: 'success', warn: 'warning-emphasis', info: 'body-secondary' }[type] || 'body-secondary');
        el.textContent = text;
    }

    var GEO_ERRORES = {
        1: 'No diste permiso de ubicación. Podés habilitarlo en el navegador o cargar las coordenadas a mano.',
        2: 'No se pudo obtener tu ubicación. Probá cerca de una ventana o cargala a mano.',
        3: 'La ubicación tardó demasiado. Probá de nuevo.'
    };

    document.querySelectorAll('[data-geo-fill]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var lat = document.getElementById(btn.dataset.lat);
            var lng = document.getElementById(btn.dataset.lng);
            var status = document.getElementById(btn.dataset.status);

            if (!window.isSecureContext) {
                setStatus(status, 'warn', 'La ubicación necesita HTTPS. En la PC usá http://localhost; en el celular funciona en baseocho.tienda.');
                return;
            }
            if (!('geolocation' in navigator)) {
                setStatus(status, 'warn', 'Este navegador no permite obtener la ubicación. Cargá las coordenadas a mano.');
                return;
            }

            btn.disabled = true;
            setStatus(status, 'info', 'Obteniendo ubicación…');

            navigator.geolocation.getCurrentPosition(function (pos) {
                lat.value = pos.coords.latitude.toFixed(7);
                lng.value = pos.coords.longitude.toFixed(7);
                lat.dispatchEvent(new Event('input', { bubbles: true }));
                setStatus(status, 'ok', 'Ubicación cargada (precisión aprox. ' + Math.round(pos.coords.accuracy) + ' m).');
                btn.disabled = false;
            }, function (err) {
                setStatus(status, 'warn', GEO_ERRORES[err.code] || 'No se pudo obtener tu ubicación.');
                btn.disabled = false;
            }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
        });
    });

    // ---------- Link "Ver en mapa" ----------
    document.querySelectorAll('[data-map-link]').forEach(function (link) {
        var lat = document.getElementById(link.dataset.lat);
        var lng = document.getElementById(link.dataset.lng);

        function update() {
            var a = parseFloat(String(lat.value).replace(',', '.'));
            var b = parseFloat(String(lng.value).replace(',', '.'));
            var ok = isFinite(a) && isFinite(b);
            link.hidden = !ok;
            if (ok) link.href = 'https://www.google.com/maps?q=' + a + ',' + b;
        }

        lat.addEventListener('input', update);
        lng.addEventListener('input', update);
        update();
    });
})();
