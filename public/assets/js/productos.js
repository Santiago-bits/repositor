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

    // ---------- Escanear → abrir el producto, o asociar el código a uno que ya existe ----------
    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    /** Código desconocido: "¿Qué producto es?" para guardarlo en uno sin código (ej. Andes Oro de Chess). */
    function noRegistrado(codigo, urlCrear) {
        resultados.innerHTML =
            '<div class="card-soft p-3">' +
            '  <div class="d-flex align-items-center gap-2 mb-1"><i class="bi bi-upc-scan fs-4 text-primary"></i>' +
            '    <span class="fw-semibold">Código nuevo</span> <span class="font-monospace small text-body-secondary" data-codigo></span></div>' +
            '  <p class="small text-body-secondary mb-2">¿Qué producto es? Buscalo y tocalo: queda asociado y la próxima vez lo reconoce solo.</p>' +
            '  <div class="search-input mb-2"><i class="bi bi-search"></i>' +
            '    <input type="search" placeholder="Ej: andes oro" autocomplete="off" autocapitalize="off" aria-label="Buscar el producto" data-asociar-buscar></div>' +
            '  <div class="promo-grupo" data-asociar-lista></div>' +
            '  <p class="small text-body-secondary mb-0 mt-2" data-asociar-estado></p>' +
            '</div>' +
            '<a class="btn btn-outline-primary w-100 mt-2" data-crear><i class="bi bi-plus-lg me-1"></i> No está: crear producto nuevo</a>';
        resultados.querySelector('[data-codigo]').textContent = codigo;
        resultados.querySelector('[data-crear]').href = urlCrear;

        var buscador = resultados.querySelector('[data-asociar-buscar]');
        var lista = resultados.querySelector('[data-asociar-lista]');
        var estado = resultados.querySelector('[data-asociar-estado]');
        var esperaAsociar = null;

        var mostrarOpciones = function (productos, q) {
            lista.innerHTML = '';
            estado.textContent = q && !productos.length ? 'No hay productos sin código con ese nombre.' : '';
            productos.forEach(function (p) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'venc-opcion';
                var texto = document.createElement('span');
                texto.className = 'min-w-0 flex-grow-1 text-start';
                var nombre = document.createElement('span');
                nombre.className = 'd-block fw-semibold';
                nombre.textContent = p.nombre;
                texto.appendChild(nombre);
                if (p.marca) {
                    var marca = document.createElement('span');
                    marca.className = 'small text-body-secondary';
                    marca.textContent = p.marca;
                    texto.appendChild(marca);
                }
                var icono = document.createElement('i');
                icono.className = 'bi bi-link-45deg text-primary fs-5';
                b.append(texto, icono);
                b.addEventListener('click', function () { asociar(p, codigo); });
                lista.appendChild(b);
            });
        };

        buscador.addEventListener('input', function () {
            var q = buscador.value.trim();
            clearTimeout(esperaAsociar);
            if (q.length < 2) { mostrarOpciones([], ''); return; }
            esperaAsociar = setTimeout(function () {
                fetch(input.dataset.sinCodigoEndpoint + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (d) { mostrarOpciones(d.productos || [], q); })
                    .catch(function () { estado.textContent = 'No se pudo buscar. Revisá la conexión.'; });
            }, 200);
        });
        buscador.focus();

        function asociar(p, cod) {
            estado.textContent = 'Guardando…';
            fetch(input.dataset.asociarEndpoint.replace('{id}', p.id), {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams({ _token: token, codigo: cod })
            }).then(function (r) { return r.json(); }).then(function (d) {
                if (!d.ok) { estado.textContent = '⚠️ ' + (d.message || 'No se pudo asociar.'); return; }
                estado.textContent = '✅ ' + d.message;
                setTimeout(function () {
                    window.location.href = input.dataset.productoUrl ? input.dataset.productoUrl.replace('{id}', d.id) : d.url;
                }, 900);
            }).catch(function () { estado.textContent = '⚠️ No se pudo guardar. Revisá la conexión.'; });
        }
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
