/*
 * JACOB · Detección automática del local en la pantalla de inicio.
 * La ubicación se manda al servidor solo para calcular distancias; no se guarda.
 * Si algo falla (permiso, GPS, HTTPS, conexión) siempre queda la selección manual.
 */
(function () {
    'use strict';

    var box = document.getElementById('deteccion');
    if (!box) return;

    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var abierta = {
        localId: box.dataset.abiertaLocal ? parseInt(box.dataset.abiertaLocal, 10) : null,
        hoy: box.dataset.abiertaHoy === '1',
        nombre: box.dataset.abiertaNombre,
        url: box.dataset.abiertaUrl
    };
    var ultimaPosicion = null;

    // ---------- Helpers de DOM (textContent: los nombres nunca se interpretan como HTML) ----------
    function el(tag, clase, texto) {
        var n = document.createElement(tag);
        if (clase) n.className = clase;
        if (texto !== undefined && texto !== null) n.textContent = texto;
        return n;
    }

    function icono(nombre) {
        return el('i', 'bi ' + nombre);
    }

    function mostrar() {
        box.replaceChildren.apply(box, Array.prototype.slice.call(arguments).filter(Boolean));
    }

    function botonManual(texto) {
        var a = el('a', 'btn btn-outline-primary btn-xl w-100 mt-3', texto || 'Seleccionar local manualmente');
        a.href = '#locales';
        return a;
    }

    function botonReintentar() {
        var b = el('button', 'btn btn-link btn-sm w-100 mt-1');
        b.type = 'button';
        b.append(icono('bi-arrow-clockwise'), ' Volver a detectar');
        b.addEventListener('click', detectar);
        return b;
    }

    function cabecera(iconoNombre, titulo, texto, tono) {
        var wrap = el('div', 'deteccion-cabecera' + (tono ? ' tono-' + tono : ''));
        var ic = el('div', 'deteccion-icono');
        ic.appendChild(icono(iconoNombre));
        var cuerpo = el('div', 'min-w-0');
        cuerpo.appendChild(el('div', 'fw-bold', titulo));
        if (texto) cuerpo.appendChild(el('div', 'small text-body-secondary', texto));
        wrap.append(ic, cuerpo);
        return wrap;
    }

    /** Botón para entrar al local (o "Continuar" si ya hay una visita abierta ahí). */
    function accionLocal(local, grande) {
        var clase = grande ? 'btn btn-primary btn-xl w-100' : 'btn btn-outline-primary btn-sm';

        if (abierta.localId === local.id && abierta.hoy) {
            var a = el('a', clase, grande ? 'Continuar visita' : 'Continuar');
            a.href = abierta.url;
            return a;
        }

        var form = el('form');
        form.method = 'post';
        form.action = box.dataset.visitas;
        form.dataset.ingresar = '';

        var campos = { _token: csrf, local_id: local.id };
        if (ultimaPosicion) {
            campos.lat_inicio = ultimaPosicion.latitude.toFixed(7);
            campos.lng_inicio = ultimaPosicion.longitude.toFixed(7);
        }
        Object.keys(campos).forEach(function (k) {
            var i = el('input');
            i.type = 'hidden';
            i.name = k;
            i.value = campos[k];
            form.appendChild(i);
        });

        var btn = el('button', clase, grande ? 'INGRESAR' : 'Ingresar');
        btn.type = 'submit';
        form.appendChild(btn);
        return form;
    }

    function filaLocal(local) {
        var fila = el('div', 'deteccion-fila');
        var ic = el('div', 'item-icon item-icon-sm');
        ic.appendChild(icono(local.icono));
        var cuerpo = el('div', 'min-w-0 flex-grow-1');
        cuerpo.append(el('div', 'fw-semibold text-truncate', local.nombre), el('div', 'small text-body-secondary', 'A ' + local.texto));
        fila.append(ic, cuerpo, accionLocal(local, false));
        return fila;
    }

    function listaTareas(tareas) {
        if (!tareas || !tareas.length) return null;
        var wrap = el('div', 'deteccion-tareas');
        wrap.appendChild(el('div', 'small fw-semibold text-body-secondary mb-1', 'Tareas pendientes'));
        tareas.forEach(function (t) {
            var fila = el('div', 'tarea-mini' + (t.hecha ? ' is-hecha' : ''));
            fila.appendChild(t.hecha ? icono('bi-check-circle-fill text-success') : el('span', 'prio-dot prio-' + t.prioridad));
            fila.appendChild(el('span', null, t.nombre));
            wrap.appendChild(fila);
        });
        return wrap;
    }

    // ---------- Estados ----------
    function cargando(texto) {
        var d = el('div', 'deteccion-cargando');
        var s = el('span', 'spinner-border spinner-border-sm text-primary');
        s.setAttribute('aria-hidden', 'true');
        d.append(s, el('span', null, texto));
        mostrar(d);
    }

    function pedirPermiso() {
        var b = el('button', 'btn btn-primary btn-xl w-100 mt-3');
        b.type = 'button';
        b.append(icono('bi-geo-alt-fill'), ' Detectar local');
        b.addEventListener('click', detectar);
        mostrar(
            cabecera('bi-geo-alt', '¿En qué local estás?', 'Usamos tu ubicación solo para identificar el local. No se guarda tu recorrido.'),
            b,
            botonManual()
        );
    }

    function aviso(texto, conReintento) {
        mostrar(
            cabecera('bi-exclamation-triangle', 'No podemos detectar automáticamente el local', texto, 'warn'),
            botonManual(),
            conReintento ? botonReintentar() : null
        );
    }

    function resultado(data) {
        if (data.estado === 'detectado') {
            var local = data.locales[0];
            var cab = cabecera('bi-geo-alt-fill', 'Local detectado', null, 'ok');
            var nombre = el('div', 'deteccion-local', local.nombre);
            var dist = el('div', 'small text-body-secondary', 'Distancia aproximada: ' + local.texto);
            var otro = el('a', 'btn btn-link btn-sm w-100 mt-1', 'No es este local');
            otro.href = '#locales';
            mostrar(cab, nombre, dist, listaTareas(data.tareas), el('div', 'mt-3'), accionLocal(local, true), otro);
            return;
        }

        if (data.estado === 'varios') {
            var lista = el('div', 'deteccion-lista');
            data.locales.forEach(function (l) { lista.appendChild(filaLocal(l)); });
            mostrar(cabecera('bi-geo-alt-fill', 'Hay varios locales cerca', 'Elegí en cuál estás.', 'ok'), lista, botonReintentar());
            return;
        }

        // Ninguno dentro del radio.
        var texto = 'Podés elegirlo de la lista.';
        if (data.precision_baja) texto = 'La señal del GPS es imprecisa (±' + data.precision + ' m). Probá cerca de una ventana o elegilo de la lista.';
        if (!data.locales.length && data.sin_coordenadas) texto = 'Tus locales todavía no tienen la ubicación cargada. Elegilo de la lista.';

        var partes = [cabecera('bi-geo', 'No pudimos identificar el local.', texto, 'warn')];
        if (data.locales.length) {
            var cercanos = el('div', 'deteccion-lista');
            cercanos.appendChild(el('div', 'small fw-semibold text-body-secondary mt-2', 'Lo más cerca'));
            data.locales.forEach(function (l) { cercanos.appendChild(filaLocal(l)); });
            partes.push(cercanos);
        }
        partes.push(botonManual(), botonReintentar());
        mostrar.apply(null, partes);
    }

    // ---------- Ubicación ----------
    var ERRORES = {
        1: 'No diste permiso de ubicación. Podés habilitarlo desde el candado de la barra de direcciones.',
        2: 'No se pudo obtener tu ubicación. Revisá que el GPS esté prendido.',
        3: 'La ubicación tardó demasiado.'
    };

    function detectar() {
        cargando('Buscando el local…');
        navigator.geolocation.getCurrentPosition(function (pos) {
            ultimaPosicion = pos.coords;
            completarFormulariosManuales(pos.coords);
            consultar(pos.coords);
        }, function (err) {
            aviso(ERRORES[err.code] || 'No se pudo obtener tu ubicación.', err.code !== 1);
        }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 });
    }

    function consultar(coords) {
        var body = new URLSearchParams({
            _token: csrf,
            lat: coords.latitude,
            lng: coords.longitude,
            precision: Math.round(coords.accuracy || 0)
        });

        fetch(box.dataset.endpoint, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            body: body
        }).then(function (r) {
            if (r.status === 401 || r.status === 403) { window.location.reload(); throw new Error('sesion'); }
            if (!r.ok) throw new Error('http');
            return r.json();
        }).then(function (data) {
            cerrarSiTeFuiste(data);
            resultado(data);
        }).catch(function (err) {
            if (err.message === 'sesion') return;
            aviso('No se pudo consultar el servidor. Revisá la conexión.', true);
        });
    }

    /**
     * Si la visita abierta es de un local del que ya estás afuera, se cierra sola.
     * No hay nada que perder: si volvés hoy a ese local, sigue la misma visita.
     */
    function cerrarSiTeFuiste(data) {
        if (!abierta.localId || !data.fuera || data.fuera.indexOf(abierta.localId) === -1) return;

        var nombre = abierta.nombre;
        fetch(box.dataset.abiertaCerrar, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            body: new URLSearchParams({ _token: csrf })
        }).then(function (r) {
            if (!r.ok) return;
            var tarjeta = document.getElementById('visita-activa');
            if (tarjeta) {
                var nota = el('div', 'small text-body-secondary mb-3');
                nota.append(icono('bi-check-circle text-success'), ' Saliste de ' + nombre + ': la visita quedó guardada.');
                tarjeta.replaceWith(nota);
            }
        }).catch(function () { /* sin conexión: se cierra sola al entrar a otro local */ });

        // Desde ya, los botones muestran "Ingresar" (no "Continuar").
        abierta.localId = null;
        abierta.hoy = false;
    }

    /** Los botones "Ingresar" de la lista también guardan dónde empezó la visita. */
    function completarFormulariosManuales(coords) {
        document.querySelectorAll('form[data-ingresar]').forEach(function (f) {
            if (f.lat_inicio) f.lat_inicio.value = coords.latitude.toFixed(7);
            if (f.lng_inicio) f.lng_inicio.value = coords.longitude.toFixed(7);
        });
    }

    // ---------- Arranque ----------
    if (!window.isSecureContext) {
        aviso('La ubicación necesita HTTPS. En el celular funciona en baseocho.tienda; en la PC, entrando por localhost.', false);
        return;
    }
    if (!('geolocation' in navigator)) {
        aviso('Este navegador no permite obtener la ubicación.', false);
        return;
    }

    if (navigator.permissions && navigator.permissions.query) {
        navigator.permissions.query({ name: 'geolocation' }).then(function (p) {
            if (p.state === 'granted') detectar();
            else if (p.state === 'denied') aviso(ERRORES[1], false);
            else pedirPermiso();
        }).catch(pedirPermiso);
    } else {
        pedirPermiso(); // Safari viejo: preguntar con un botón, nunca de golpe.
    }
})();
