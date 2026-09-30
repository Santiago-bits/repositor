/* JACOB · Búsqueda de productos mientras se escribe + resultado del escáner */
(function () {
    'use strict';

    var input = document.getElementById('buscar-producto');
    if (!input) return;

    var resultados = document.getElementById('resultados');
    var form = input.closest('form');
    var ultimo = input.value.trim();
    var espera = null;
    var pedido = null;

    function mostrarAviso(texto) {
        resultados.innerHTML = '';
        var div = document.createElement('div');
        div.className = 'alert alert-warning d-flex gap-2';
        div.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>';
        var span = document.createElement('span');
        span.textContent = texto;
        div.appendChild(span);
        resultados.appendChild(div);
    }

    function buscar(q) {
        if (pedido) pedido.abort();
        pedido = new AbortController();
        resultados.setAttribute('aria-busy', 'true');

        fetch(input.dataset.endpoint + '?q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: pedido.signal
        }).then(function (r) {
            if (r.status === 401) { window.location.reload(); throw new Error('sesion'); }
            if (!r.ok) throw new Error('http');
            return r.text();
        }).then(function (html) {
            resultados.innerHTML = html;
            resultados.removeAttribute('aria-busy');
        }).catch(function (err) {
            if (err.name === 'AbortError' || err.message === 'sesion') return;
            resultados.removeAttribute('aria-busy');
            mostrarAviso('No se pudo buscar. Revisá la conexión y probá de nuevo.');
        });

        // Mantener la búsqueda al volver atrás desde la ficha.
        history.replaceState(null, '', q ? '?q=' + encodeURIComponent(q) : window.location.pathname);
    }

    input.addEventListener('input', function () {
        var q = input.value.trim();
        if (q === ultimo) return;
        ultimo = q;
        clearTimeout(espera);
        espera = setTimeout(function () { buscar(q); }, q ? 180 : 0);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearTimeout(espera);
        ultimo = input.value.trim();
        buscar(ultimo);
        input.blur(); // cierra el teclado en el celular
    });

    // ---------- Escanear → abrir el producto o proponer crearlo ----------
    function noRegistrado(codigo, urlCrear) {
        resultados.innerHTML =
            '<div class="empty-state card-soft">' +
            '  <i class="bi bi-upc-scan"></i>' +
            '  <p class="fw-semibold mb-1">Producto no registrado</p>' +
            '  <p class="font-monospace" data-codigo></p>' +
            '  <a class="btn btn-primary btn-xl" data-crear><i class="bi bi-plus-lg me-1"></i> Crear producto</a>' +
            '</div>';
        resultados.querySelector('[data-codigo]').textContent = codigo;
        resultados.querySelector('[data-crear]').href = urlCrear;
    }

    function resolverCodigo(codigo) {
        input.value = codigo;
        ultimo = codigo;
        resultados.setAttribute('aria-busy', 'true');

        fetch(input.dataset.codigoEndpoint + '?c=' + encodeURIComponent(codigo), {
            headers: { Accept: 'application/json' }
        }).then(function (r) {
            if (r.status === 401) { window.location.reload(); throw new Error('sesion'); }
            return r.json();
        }).then(function (data) {
            resultados.removeAttribute('aria-busy');
            if (data.found) {
                // Dentro de una visita: ir a registrar el producto, no a su ficha.
                window.location.href = input.dataset.productoUrl
                    ? input.dataset.productoUrl.replace('{id}', data.id)
                    : data.url;
            } else if (data.crear_url) {
                noRegistrado(data.codigo, data.crear_url + (input.dataset.crearExtra || ''));
            } else {
                mostrarAviso(data.message || 'No se pudo leer el código. Probá de nuevo.');
            }
        }).catch(function (err) {
            if (err.message === 'sesion') return;
            resultados.removeAttribute('aria-busy');
            mostrarAviso('No se pudo buscar el código. Revisá la conexión y probá de nuevo.');
        });
    }

    document.querySelectorAll('[data-escanear]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            window.JacobEscaner.abrir({ onCodigo: resolverCodigo });
        });
    });
})();
