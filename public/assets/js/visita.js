/*
 * JACOB · Carga de datos durante la visita, sin recargar la página:
 * stock, vencimientos, fotos (achicadas en el celular antes de subir) y observaciones.
 * El servidor devuelve JSON { ok, message, html? }; el html ya viene escapado desde PHP.
 */
(function () {
    'use strict';

    var csrf = document.querySelector('meta[name="csrf-token"]').content;

    // ---------- Utilidades ----------
    function toast(mensaje, tipo) {
        var t = document.querySelector('.toast-jacob');
        if (!t) {
            t = document.createElement('div');
            t.className = 'toast-jacob';
            t.setAttribute('role', 'status');
            t.setAttribute('aria-live', 'polite');
            document.body.appendChild(t);
        }
        t.textContent = mensaje;
        t.className = 'toast-jacob is-visible is-' + (tipo || 'ok');
        clearTimeout(t._espera);
        t._espera = setTimeout(function () { t.classList.remove('is-visible'); }, tipo === 'error' ? 4500 : 2200);
    }

    function enviar(url, body) {
        if (!(body instanceof FormData)) body = new URLSearchParams(body || '');
        body.set('_token', csrf);

        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json', 'X-CSRF-Token': csrf },
            body: body
        }).catch(function () {
            throw new Error('Sin conexión. Revisá la señal y probá de nuevo.');
        }).then(function (r) {
            if (r.status === 401) { window.location.reload(); throw new Error('Tu sesión expiró.'); }
            return r.json().catch(function () {
                return { ok: false, message: r.status === 413 ? 'El archivo es demasiado pesado.' : 'Error inesperado del servidor.' };
            }).then(function (data) {
                if (!r.ok || !data.ok) throw new Error(data.message || 'No se pudo guardar. Probá de nuevo.');
                return data;
            });
        });
    }

    function reemplazar(selector, html) {
        var destino = selector && document.querySelector(selector);
        if (destino && typeof html === 'string') destino.innerHTML = html;
    }

    function ocupado(form, si) {
        form.querySelectorAll('button[type=submit]').forEach(function (b) { b.disabled = si; });
    }

    // ---------- Botones + / − de stock (registro y conteo) ----------
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-paso]');
        if (!b) return;
        var campo = b.closest('form').querySelector('[name=stock]');
        campo.value = Math.max(0, (parseInt(campo.value, 10) || 0) + parseInt(b.dataset.paso, 10));
        campo.dispatchEvent(new Event('input'));
        if (navigator.vibrate) navigator.vibrate(8);
    });

    // ---------- Promos del finde: anotar el stock marca el producto ----------
    document.addEventListener('input', function (e) {
        if (!e.target.matches('[data-auto-marcar]') || e.target.value === '') return;
        var check = e.target.closest('.promo-fila').querySelector('input[type=checkbox]');
        if (check) check.checked = true;
    });

    // ---------- Stock ----------
    var stockForm = document.querySelector('[data-stock-form]');
    if (stockForm) {
        var input = stockForm.querySelector('[name=stock]');
        var guardado = stockForm.querySelector('[data-guardado]');

        var estadoActual = function () {
            var r = stockForm.querySelector('[name=estado_stock]:checked');
            return r ? r.value : '';
        };
        var marcar = function (valor) {
            var r = stockForm.querySelector('[name=estado_stock][value="' + valor + '"]');
            if (r) r.checked = true;
        };

        // 0 → "Sin stock"; con número y sin estado elegido → "Normal". "Bajo" y "No exhibido" se respetan.
        input.addEventListener('input', function () {
            if (input.value === '') return;
            var n = parseInt(input.value, 10);
            if (n === 0) marcar('sin_stock');
            else if (n > 0 && (estadoActual() === '' || estadoActual() === 'sin_stock')) marcar('normal');
        });

        stockForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var datos = new FormData(stockForm);
            datos.set('siguiente', e.submitter && e.submitter.value === '1' ? '1' : '0');
            ocupado(stockForm, true);

            enviar(stockForm.action, datos).then(function (d) {
                guardado.textContent = '✅ Guardado ' + d.hora;
                if (d.siguiente_url) {
                    toast(d.completo ? '✅ ¡Todos los productos del local registrados!' : '✅ Registrado. Siguiente…');
                    setTimeout(function () { window.location.href = d.siguiente_url; }, d.completo ? 1200 : 350);
                } else {
                    toast('✅ Producto registrado');
                    ocupado(stockForm, false);
                }
            }).catch(function (err) {
                toast('⚠️ ' + err.message, 'error');
                ocupado(stockForm, false);
            });
        });
    }

    // ---------- Vencimientos y observaciones (formularios que reemplazan una lista) ----------
    document.querySelectorAll('[data-venc-form], [data-obs-form]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            ocupado(form, true);
            enviar(form.action, new FormData(form)).then(function (d) {
                reemplazar(form.dataset.destino, d.html);
                var campoPrincipal = form.querySelector('[name=fecha], [name=texto]');
                form.querySelectorAll('input:not([type=hidden]), textarea').forEach(function (c) { c.value = ''; });
                toast('✅ ' + d.message);
                if (campoPrincipal && form.hasAttribute('data-venc-form')) campoPrincipal.focus();
            }).catch(function (err) {
                toast('⚠️ ' + err.message, 'error');
            }).then(function () { ocupado(form, false); });
        });
    });

    // Frases rápidas de observación: se agregan al texto.
    document.addEventListener('click', function (e) {
        var chip = e.target.closest('[data-chip]');
        if (!chip) return;
        var texto = chip.closest('form').querySelector('textarea');
        var actual = texto.value.trim();
        texto.value = actual ? actual + ' ' + chip.dataset.chip : chip.dataset.chip;
        texto.focus();
    });

    // ---------- Botones de acción: eliminar, copiar lotes ----------
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-accion]');
        if (!b) return;
        e.preventDefault();
        if (b.dataset.confirm && !window.confirm(b.dataset.confirm)) return;
        b.disabled = true;
        enviar(b.dataset.accion, new URLSearchParams(b.dataset.extra || '')).then(function (d) {
            reemplazar(b.dataset.destino, d.html);
            toast(d.message);
        }).catch(function (err) {
            toast('⚠️ ' + err.message, 'error');
            b.disabled = false;
        });
    });

    // ---------- Fotos ----------
    /** Achica la foto en el celular (lado mayor 1600 px, JPEG 82%) para subir rápido con poca señal. */
    function comprimir(archivo) {
        if (!window.createImageBitmap || !/^image\//.test(archivo.type)) return Promise.resolve(archivo);
        return createImageBitmap(archivo, { imageOrientation: 'from-image' }).then(function (bmp) {
            var escala = Math.min(1, 1600 / Math.max(bmp.width, bmp.height));
            var canvas = document.createElement('canvas');
            canvas.width = Math.round(bmp.width * escala);
            canvas.height = Math.round(bmp.height * escala);
            canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
            if (bmp.close) bmp.close();
            return new Promise(function (resolve) {
                canvas.toBlob(function (blob) { resolve(blob || archivo); }, 'image/jpeg', 0.82);
            });
        }).catch(function () { return archivo; }); // formato no soportado (ej. HEIC): se sube tal cual
    }

    function subirFoto(zona, archivo) {
        var estado = document.querySelector(zona.dataset.estado);
        var aviso = document.createElement('div');
        aviso.className = 'foto-subiendo';
        aviso.textContent = '⏳ Preparando foto…';
        if (estado) estado.appendChild(aviso);

        comprimir(archivo).then(function intentar(blob) {
            aviso.className = 'foto-subiendo';
            aviso.textContent = '⏳ Subiendo foto…';

            var datos = new FormData();
            datos.append('foto', blob, blob.name || 'foto.jpg');
            datos.append('producto_id', zona.dataset.producto || '');
            datos.append('scope', zona.dataset.scope || 'visita');

            enviar(zona.dataset.endpoint, datos).then(function (d) {
                reemplazar(zona.dataset.destino, d.html);
                aviso.remove();
                toast('📷 Foto guardada');
            }).catch(function (err) {
                // La foto queda en memoria: se puede reintentar sin volver a sacarla.
                aviso.className = 'alert alert-warning d-flex align-items-center gap-2 py-2 mb-2';
                aviso.textContent = '';
                var texto = document.createElement('span');
                texto.className = 'flex-grow-1 small';
                texto.textContent = '⚠️ No se pudo subir la fotografía. ' + err.message;
                var reintentar = document.createElement('button');
                reintentar.type = 'button';
                reintentar.className = 'btn btn-sm btn-warning fw-semibold';
                reintentar.textContent = 'REINTENTAR';
                reintentar.addEventListener('click', function () { intentar(blob); });
                aviso.append(texto, reintentar);
            });
        });
    }

    document.querySelectorAll('[data-foto-subir]').forEach(function (zona) {
        zona.querySelectorAll('[data-foto-input]').forEach(function (input) {
            input.addEventListener('change', function () {
                Array.prototype.forEach.call(input.files, function (archivo) { subirFoto(zona, archivo); });
                input.value = '';
            });
        });
    });
})();
