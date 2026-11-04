/* repositores.rv · Anotador de fechas: local por GPS, buscar o escanear el producto, fecha rápida y lista */
(function () {
    'use strict';

    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function post(url, datos) {
        var cuerpo = new URLSearchParams(datos || {});
        cuerpo.set('_token', token);
        return fetch(url, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': token },
            body: cuerpo
        }).then(function (r) {
            if (r.status === 401) { window.location.reload(); throw new Error('sesion'); }
            return r.json().catch(function () { return { ok: false, message: 'Error del servidor.' }; });
        });
    }

    function toast(texto, error) {
        var t = document.createElement('div');
        t.className = 'toast-jacob' + (error ? ' is-error' : '');
        t.setAttribute('role', 'status');
        t.textContent = texto;
        document.body.appendChild(t);
        requestAnimationFrame(function () { t.classList.add('is-visible'); });
        setTimeout(function () { t.classList.remove('is-visible'); setTimeout(function () { t.remove(); }, 300); }, 2600);
    }

    /** Lista de productos para tocar, más la opción de dejarlo escrito (al anotar y al corregir). */
    function pintarSugerencias(caja, productos, q, alElegir, alEscribir) {
        caja.innerHTML = '';
        productos.forEach(function (p) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'anot-sug';
            var n = document.createElement('span');
            n.textContent = p.nombre;
            b.appendChild(n);
            if (p.marca) {
                var m = document.createElement('small');
                m.textContent = p.marca;
                b.appendChild(m);
            }
            b.addEventListener('click', function () { alElegir(p); });
            caja.appendChild(b);
        });
        var libre = document.createElement('button');
        libre.type = 'button';
        libre.className = 'anot-sug anot-sug-libre';
        libre.textContent = '✎ Anotar «' + q + '» así, escrito';
        libre.addEventListener('click', alEscribir);
        caja.appendChild(libre);
        caja.hidden = false;
    }

    // ---------- Enter del celular: pasa al campo siguiente; en el último, guarda ----------
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' || e.defaultPrevented) return;
        var form = e.target.closest('[data-enter-sigue]');
        if (!form || e.target.tagName !== 'INPUT') return;
        e.preventDefault();
        var campos = Array.prototype.filter.call(form.querySelectorAll('input'), function (c) {
            return c.type !== 'hidden' && c.type !== 'date' && !c.disabled;
        });
        var siguiente = campos[campos.indexOf(e.target) + 1];
        if (siguiente) siguiente.focus();
        else form.requestSubmit();
    });

    // Fecha: se escriben solo los números (15112026) y las barras se ponen solas.
    document.addEventListener('input', function (e) {
        if (!e.target.matches('[data-mascara-fecha]')) return;
        var v = e.target.value.replace(/\D/g, '').slice(0, 8);
        var out = v.slice(0, 2);
        if (v.length > 2) out += '/' + v.slice(2, 4);
        if (v.length > 4) out += '/' + v.slice(4);
        e.target.value = out;
    });

    // ---------- Editar una fecha ya anotada ----------
    function campo(etiqueta, atributos, valor, clase) {
        var col = document.createElement('div');
        col.className = clase;
        var label = document.createElement('label');
        label.className = 'form-label small mb-1 d-block';
        label.textContent = etiqueta;
        var input = document.createElement('input');
        input.className = 'form-control';
        Object.keys(atributos).forEach(function (k) { input.setAttribute(k, atributos[k]); });
        input.value = valor;
        label.appendChild(input);
        col.appendChild(label);
        return col;
    }

    function abrirEditor(fila) {
        var abierto = document.querySelector('.fecha-editor');
        if (abierto) {
            var mismo = abierto.parentElement === fila;
            abierto.remove();
            if (mismo) return;
        }
        var d = fila.dataset;
        var form = document.createElement('form');
        form.className = 'fecha-editor';
        form.noValidate = true;
        form.setAttribute('data-enter-sigue', '');

        var inId = document.createElement('input');
        inId.type = 'hidden';
        inId.name = 'producto_id';
        var sugerencias = document.createElement('div');
        sugerencias.className = 'anot-sugerencias';
        sugerencias.hidden = true;

        var filaCampos = document.createElement('div');
        filaCampos.className = 'row g-2';
        filaCampos.append(
            campo('Producto', { name: 'producto_texto', maxlength: '150', enterkeyhint: 'next', autocomplete: 'off', autocapitalize: 'off' }, d.nombre, 'col-12'),
            campo('Vence', { name: 'fecha', inputmode: 'numeric', maxlength: '10', enterkeyhint: 'next', autocomplete: 'off', 'data-mascara-fecha': '' }, d.fecha, 'col-7'),
            campo('Cantidad', { name: 'cantidad', type: 'number', min: '0', max: '99999', inputmode: 'numeric', enterkeyhint: 'next' }, d.cantidad, 'col-5'),
            campo('Nota', { name: 'nota', maxlength: '120', enterkeyhint: 'done', placeholder: 'Depósito, heladera 2…' }, d.nota, 'col-12')
        );

        var botones = document.createElement('div');
        botones.className = 'fecha-editor-botones';
        var borrar = document.createElement('button');
        borrar.type = 'button';
        borrar.className = 'btn-icon';
        borrar.setAttribute('aria-label', 'Borrar');
        borrar.setAttribute('data-fecha-accion', 'borrar');
        borrar.dataset.url = d.url + '/eliminar';
        borrar.innerHTML = '<i class="bi bi-trash3"></i>';
        var cancelar = document.createElement('button');
        cancelar.type = 'button';
        cancelar.className = 'btn btn-outline-secondary';
        cancelar.textContent = 'Cancelar';
        cancelar.addEventListener('click', function () { form.remove(); });
        var guardar = document.createElement('button');
        guardar.type = 'submit';
        guardar.className = 'btn btn-primary flex-grow-1';
        guardar.textContent = 'Guardar';
        botones.append(borrar, cancelar, guardar);

        var aviso = document.createElement('p');
        aviso.className = 'anot-form-estado';
        aviso.setAttribute('role', 'status');
        form.append(inId, filaCampos, botones, aviso);
        filaCampos.firstElementChild.appendChild(sugerencias);

        // Producto: al escribir aparece el catálogo, igual que al anotar.
        var inNombre = form.elements.producto_texto;
        var espera = null;
        function cerrar() { sugerencias.hidden = true; sugerencias.innerHTML = ''; }
        inNombre.addEventListener('focus', function () { inNombre.select(); });
        inNombre.addEventListener('input', function () {
            inId.value = '';
            var q = inNombre.value.trim();
            clearTimeout(espera);
            if (q.length < 2) { cerrar(); return; }
            espera = setTimeout(function () {
                fetch(d.buscarUrl + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (inNombre.value.trim() !== q) return;
                        pintarSugerencias(sugerencias, res.productos || [], q, function (p) {
                            inId.value = p.id;
                            inNombre.value = p.nombre;
                            cerrar();
                            form.elements.fecha.focus();
                        }, function () { cerrar(); form.elements.fecha.focus(); });
                    })
                    .catch(function () { /* sin señal: queda escrito a mano */ });
            }, 150);
        });
        inNombre.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            var primero = sugerencias.hidden ? null : sugerencias.querySelector('.anot-sug:not(.anot-sug-libre)');
            if (!primero) { cerrar(); return; } // sigue al campo de la fecha
            e.preventDefault();
            primero.click();
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var datos = { con_local: d.conLocal };
            new FormData(form).forEach(function (valor, clave) { datos[clave] = valor; });
            guardar.disabled = true;
            aviso.textContent = 'Guardando…';
            post(d.url, datos).then(function (r) {
                guardar.disabled = false;
                if (!r.ok) { aviso.textContent = '⚠️ ' + (r.message || 'No se pudo guardar.'); return; }
                fila.outerHTML = r.fila;
                toast('✓ Cambios guardados');
            }).catch(function (err) {
                guardar.disabled = false;
                if (err.message !== 'sesion') aviso.textContent = '⚠️ Sin conexión. Probá de nuevo.';
            });
        });

        fila.appendChild(form);
        form.elements.nota.focus();
    }

    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-fecha-editar]');
        if (b) abrirEditor(b.closest('[data-fecha-fila]'));
    });

    // ---------- Botones de cada fecha: ya no está (✓) y borrar ----------
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-fecha-accion]');
        if (!b) return;
        var borrar = b.dataset.fechaAccion === 'borrar';
        if (borrar && !window.confirm('¿Borrar esta fecha?')) return;
        var fila = b.closest('[data-fecha-fila]');
        b.disabled = true;
        post(b.dataset.url).then(function (d) {
            if (!d.ok) { b.disabled = false; toast(d.message || 'No se pudo.', true); return; }
            fila.classList.add('is-saliendo');
            setTimeout(function () {
                var lista = fila.parentElement;
                fila.remove();
                if (lista && !lista.querySelector('[data-fecha-fila]')) {
                    lista.outerHTML = '<div class="empty-state card-soft"><i class="bi bi-calendar-check"></i><p class="mb-0">No quedan fechas pendientes.</p></div>';
                }
                actualizarContador(-1);
            }, 220);
            toast(borrar ? 'Fecha borrada' : '✓ Listo, ya no aparece');
        }).catch(function (err) {
            b.disabled = false;
            if (err.message !== 'sesion') toast('Sin conexión. Probá de nuevo.', true);
        });
    });

    var raiz = document.getElementById('anotador');

    function actualizarContador(delta, total) {
        if (!raiz) return;
        var chip = raiz.querySelector('.anot-chip.active');
        if (!chip) return;
        var n = chip.querySelector('[data-chip-n]');
        if (!n) {
            n = document.createElement('span');
            n.className = 'anot-chip-n';
            n.setAttribute('data-chip-n', '');
            n.textContent = '0';
            chip.appendChild(n);
        }
        var valor = total !== undefined ? total : Math.max(0, parseInt(n.textContent, 10) + delta);
        n.textContent = valor;
        n.hidden = valor === 0;
    }

    if (!raiz) return;

    // ---------- En qué local estás (GPS) ----------
    var estadoGps = raiz.querySelector('[data-detectar-estado]');
    function avisoGps(texto) {
        estadoGps.hidden = !texto;
        estadoGps.textContent = texto || '';
    }

    function detectar(manual) {
        if (!navigator.geolocation) {
            if (manual) avisoGps('Este celular no deja usar la ubicación. Elegí el local de la lista.');
            return;
        }
        avisoGps('📍 Buscando en qué local estás…');
        var mejor = null, listo = false, vigilancia = null;

        function terminar() {
            if (listo) return;
            listo = true;
            if (vigilancia !== null) navigator.geolocation.clearWatch(vigilancia);
            if (!mejor) {
                avisoGps(manual ? 'No se pudo obtener tu ubicación. Elegí el local de la lista.' : '');
                return;
            }
            post(raiz.dataset.detectarUrl, { lat: mejor.latitude, lng: mejor.longitude, precision: mejor.accuracy }).then(function (d) {
                if (d.estado === 'detectado') {
                    var l = d.locales[0];
                    if (String(l.id) === raiz.dataset.local) {
                        avisoGps('📍 Estás en ' + l.nombre);
                        setTimeout(function () { avisoGps(''); }, 2500);
                    } else {
                        window.location.href = raiz.dataset.inicioUrl + '?local=' + l.id;
                    }
                } else if (d.estado === 'varios') {
                    avisoGps('Hay varios locales cerca: ' + d.locales.map(function (l) { return l.nombre; }).join(', ') + '. Tocá el tuyo.');
                } else {
                    var cerca = d.locales && d.locales[0];
                    avisoGps(manual ? 'No estás en ninguno de los locales cargados' + (cerca ? ' (el más cerca, ' + cerca.nombre + ', está a ' + cerca.texto + ').' : '.') : '');
                }
            }).catch(function () { avisoGps(''); });
        }

        vigilancia = navigator.geolocation.watchPosition(function (p) {
            if (!mejor || p.coords.accuracy < mejor.accuracy) mejor = p.coords;
            if (mejor.accuracy <= 30) terminar();
        }, function () { terminar(); }, { enableHighAccuracy: true, maximumAge: 0, timeout: 10000 });
        setTimeout(terminar, 8000);
    }

    raiz.querySelector('[data-detectar]').addEventListener('click', function () { detectar(true); });
    if (raiz.dataset.autoDetectar === '1') detectar(false);

    var activo = raiz.querySelector('.anot-chip.active');
    if (activo) activo.scrollIntoView({ block: 'nearest', inline: 'center' });

    // ---------- Anotar ----------
    var form = raiz.querySelector('[data-anotar]');
    if (!form) return;

    var inProd = form.querySelector('[data-producto]');
    var inId = form.querySelector('[data-producto-id]');
    var sug = form.querySelector('[data-sugerencias]');
    var estProd = form.querySelector('[data-producto-estado]');
    var inFecha = form.querySelector('[data-fecha]');
    var picker = form.querySelector('[data-fecha-picker]');
    var estado = form.querySelector('[data-form-estado]');
    var lista = raiz.querySelector('[data-lista]');
    var codigoPendiente = '';
    var espera = null;
    var pedido = null;

    function estadoProducto(texto) {
        estProd.hidden = !texto;
        estProd.textContent = texto || '';
    }

    function cerrarSugerencias() {
        sug.hidden = true;
        sug.innerHTML = '';
    }

    function elegir(p) {
        inId.value = p.id;
        inProd.value = p.nombre;
        cerrarSugerencias();
        if (codigoPendiente) {
            var codigo = codigoPendiente;
            codigoPendiente = '';
            post(raiz.dataset.asociarUrl.replace('{id}', p.id), { codigo: codigo }).then(function (d) {
                if (d.ok) toast('Código guardado: la próxima lo reconoce solo');
            });
        }
        estadoProducto('✓ Del catálogo' + (p.marca ? ' · ' + p.marca : ''));
        inFecha.focus();
    }

    function pintar(productos, q) {
        pintarSugerencias(sug, productos, q, elegir, function () {
            cerrarSugerencias();
            estadoProducto('Se guarda escrito así (no está en el catálogo).');
            inFecha.focus();
        });
    }

    inProd.addEventListener('input', function () {
        inId.value = '';
        estadoProducto(codigoPendiente ? 'Código nuevo (' + codigoPendiente + '): elegí el producto y queda guardado.' : '');
        var q = inProd.value.trim();
        clearTimeout(espera);
        if (q.length < 2) { cerrarSugerencias(); return; }
        espera = setTimeout(function () {
            if (pedido) pedido.abort();
            pedido = new AbortController();
            fetch(raiz.dataset.buscarUrl + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' }, signal: pedido.signal })
                .then(function (r) { return r.json(); })
                .then(function (d) { if (inProd.value.trim() === q) pintar(d.productos || [], q); })
                .catch(function () { /* cancelada o sin señal */ });
        }, 150);
    });

    inProd.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        var primero = sug.hidden ? null : sug.querySelector('.anot-sug:not(.anot-sug-libre)');
        if (primero) primero.click();
        else { cerrarSugerencias(); inFecha.focus(); }
    });

    document.addEventListener('click', function (e) {
        if (!sug.hidden && !sug.contains(e.target) && e.target !== inProd) cerrarSugerencias();
    });

    // Escanear: si el código ya es de un producto, queda elegido y sigue la fecha.
    form.querySelector('[data-escanear]').addEventListener('click', function () {
        window.JacobEscaner.abrir({
            onCodigo: function (codigo) {
                fetch(raiz.dataset.codigoUrl + '?c=' + encodeURIComponent(codigo), { headers: { Accept: 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (d.found) {
                            codigoPendiente = '';
                            elegir({ id: d.id, nombre: d.nombre, marca: '' });
                        } else {
                            codigoPendiente = d.codigo || codigo;
                            inId.value = '';
                            inProd.value = '';
                            inProd.focus();
                            estadoProducto('Código nuevo (' + codigoPendiente + '): escribí el producto y elegilo; queda guardado para la próxima.');
                        }
                    })
                    .catch(function () { toast('No se pudo buscar el código.', true); });
            }
        });
    });


    form.querySelector('[data-calendario]').addEventListener('click', function () {
        try { picker.showPicker(); } catch (err) { picker.focus(); picker.click(); }
    });
    picker.addEventListener('change', function () {
        if (!picker.value) return;
        var p = picker.value.split('-');
        inFecha.value = p[2] + '/' + p[1] + '/' + p[0];
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!inProd.value.trim()) { estado.textContent = 'Escribí o escaneá el producto.'; inProd.focus(); return; }
        if (!inFecha.value.trim()) { estado.textContent = 'Poné la fecha de vencimiento.'; inFecha.focus(); return; }

        var btn = form.querySelector('[data-guardar]');
        var datos = {};
        new FormData(form).forEach(function (valor, clave) { datos[clave] = valor; });
        btn.disabled = true;
        estado.textContent = 'Guardando…';

        post(raiz.dataset.guardarUrl, datos).then(function (d) {
            btn.disabled = false;
            if (!d.ok) { estado.textContent = '⚠️ ' + (d.message || 'No se pudo guardar.'); return; }
            estado.textContent = '';
            toast('✓ ' + d.mensaje);
            lista.innerHTML = d.lista;
            actualizarContador(0, d.total);
            ['producto_id', 'producto_texto', 'fecha', 'cantidad', 'nota'].forEach(function (n) { form.elements[n].value = ''; });
            codigoPendiente = '';
            estadoProducto('');
            cerrarSugerencias();
            inProd.focus();
        }).catch(function (err) {
            btn.disabled = false;
            if (err.message !== 'sesion') estado.textContent = '⚠️ Sin conexión. Probá de nuevo.';
        });
    });
})();
