/*
 * JACOB · Lector de códigos de barras con la cámara.
 *
 * Usa el lector nativo del navegador (BarcodeDetector, Chrome en Android) y, si no existe
 * (iPhone, Firefox), carga ZXing solo en ese momento. Siempre ofrece escribir el código a mano.
 *
 * Uso:  JacobEscaner.abrir({ onCodigo: function (codigo) { … } })
 *       <button data-escanear-a="idDelInput">  → completa ese input con el código leído
 */
(function () {
    'use strict';

    var FORMATOS = ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39', 'itf'];
    var ZXING_SRC = document.currentScript.src.replace(/js\/escaner\.js.*$/, 'vendor/zxing/zxing.min.js');

    var ui = null;
    var stream = null;
    var timer = null;
    var lectorZxing = null;
    var alLeer = null;
    var ultimo = null;
    var repeticiones = 0;

    // ---------- Validación: los EAN/UPC traen un dígito verificador ----------
    function digitoValido(codigo) {
        var d = codigo.split('').map(Number);
        var control = d.pop();
        var suma = 0;
        d.reverse().forEach(function (n, i) { suma += n * (i % 2 === 0 ? 3 : 1); });
        return (10 - (suma % 10)) % 10 === control;
    }

    function esGtin(codigo) {
        return /^\d+$/.test(codigo) && [8, 12, 13, 14].indexOf(codigo.length) !== -1;
    }

    // ---------- Interfaz ----------
    function crearUI() {
        if (ui) return;
        var root = document.createElement('div');
        root.className = 'escaner';
        root.hidden = true;
        root.setAttribute('role', 'dialog');
        root.setAttribute('aria-modal', 'true');
        root.setAttribute('aria-label', 'Escanear código de barras');
        root.innerHTML =
            '<div class="escaner-top">' +
            '  <span class="fw-semibold"><i class="bi bi-upc-scan"></i> Escanear producto</span>' +
            '  <div class="d-flex gap-2">' +
            '    <button type="button" class="escaner-btn" data-accion="linterna" hidden aria-label="Linterna"><i class="bi bi-lightning-charge"></i></button>' +
            '    <button type="button" class="escaner-btn" data-accion="cerrar" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>' +
            '  </div>' +
            '</div>' +
            '<div class="escaner-visor">' +
            '  <video playsinline muted></video>' +
            '  <div class="escaner-marco"><span class="escaner-linea"></span></div>' +
            '</div>' +
            '<div class="escaner-bottom">' +
            '  <p class="escaner-estado" role="status" aria-live="polite"></p>' +
            '  <form class="escaner-manual">' +
            '    <input type="text" inputmode="numeric" autocomplete="off" placeholder="…o escribí el código" aria-label="Código de barras">' +
            '    <button type="submit" class="btn btn-primary">Buscar</button>' +
            '  </form>' +
            '</div>';
        document.body.appendChild(root);

        ui = {
            root: root,
            video: root.querySelector('video'),
            estado: root.querySelector('.escaner-estado'),
            linterna: root.querySelector('[data-accion="linterna"]'),
            manual: root.querySelector('.escaner-manual'),
            input: root.querySelector('.escaner-manual input')
        };

        root.querySelector('[data-accion="cerrar"]').addEventListener('click', cerrar);
        ui.linterna.addEventListener('click', alternarLinterna);
        ui.manual.addEventListener('submit', function (e) {
            e.preventDefault();
            var codigo = ui.input.value.replace(/\s+/g, '');
            if (codigo.length < 4) {
                mostrarEstado('Escribí el código completo.', true);
                return;
            }
            entregar(codigo);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && ui && !ui.root.hidden) cerrar();
        });
        document.addEventListener('visibilitychange', function () {
            if (document.hidden && ui && !ui.root.hidden) detenerCamara();
        });
    }

    function mostrarEstado(texto, esError) {
        ui.estado.textContent = texto;
        ui.estado.classList.toggle('is-error', !!esError);
    }

    // ---------- Abrir / cerrar ----------
    function abrir(opciones) {
        crearUI();
        alLeer = opciones && opciones.onCodigo;
        ultimo = null;
        repeticiones = 0;
        ui.input.value = '';
        ui.root.hidden = false;
        document.body.classList.add('escaner-abierto');
        mostrarEstado('Iniciando cámara…');

        if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            mostrarEstado('⚠️ La cámara necesita HTTPS (en el celular funciona en baseocho.tienda). Podés escribir el código abajo.', true);
            ui.input.focus();
            return;
        }

        iniciar().then(function () {
            mostrarEstado('Apuntá al código de barras');
        }).catch(function (err) {
            detenerCamara();
            mostrarEstado(mensajeError(err), true);
        });
    }

    function cerrar() {
        detenerCamara();
        if (ui) ui.root.hidden = true;
        document.body.classList.remove('escaner-abierto');
    }

    function entregar(codigo) {
        if (navigator.vibrate) navigator.vibrate(80);
        var callback = alLeer;
        cerrar();
        if (callback) callback(codigo);
    }

    function mensajeError(err) {
        switch (err && err.name) {
            case 'NotAllowedError':
            case 'SecurityError':
                return '⚠️ No diste permiso para usar la cámara. Habilitalo en el navegador o escribí el código abajo.';
            case 'NotFoundError':
            case 'OverconstrainedError':
                return '⚠️ No se encontró una cámara. Escribí el código abajo.';
            case 'NotReadableError':
                return '⚠️ La cámara está siendo usada por otra aplicación. Cerrala y probá de nuevo.';
            default:
                return '⚠️ No se pudo acceder a la cámara. Podés escribir el código abajo.';
        }
    }

    // ---------- Lectura ----------
    function iniciar() {
        if ('BarcodeDetector' in window) {
            return BarcodeDetector.getSupportedFormats().then(function (soportados) {
                var formatos = FORMATOS.filter(function (f) { return soportados.indexOf(f) !== -1; });
                return formatos.length ? iniciarNativo(formatos) : iniciarZxing();
            }).catch(function (err) {
                if (err && err.name) throw err; // error de cámara: no reintentar
                return iniciarZxing();
            });
        }
        return iniciarZxing();
    }

    var RESTRICCIONES = {
        audio: false,
        video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }
    };

    function iniciarNativo(formatos) {
        var detector = new BarcodeDetector({ formats: formatos });
        return navigator.mediaDevices.getUserMedia(RESTRICCIONES).then(function (s) {
            stream = s;
            ui.video.srcObject = s;
            return ui.video.play();
        }).then(function () {
            prepararLinterna();
            var leer = function () {
                if (!stream) return;
                detector.detect(ui.video).then(function (codigos) {
                    if (codigos.length) procesar(codigos[0].rawValue);
                }).catch(function () { /* cuadro sin código */ }).then(function () {
                    if (stream) timer = setTimeout(leer, 120);
                });
            };
            leer();
        });
    }

    function cargarZxing() {
        if (window.ZXing) return Promise.resolve();
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = ZXING_SRC;
            s.onload = resolve;
            s.onerror = function () { reject(new Error('No se pudo cargar el lector.')); };
            document.head.appendChild(s);
        });
    }

    function iniciarZxing() {
        return cargarZxing().then(function () {
            var Z = window.ZXing;
            var hints = new Map();
            hints.set(Z.DecodeHintType.POSSIBLE_FORMATS, [
                Z.BarcodeFormat.EAN_13, Z.BarcodeFormat.EAN_8, Z.BarcodeFormat.UPC_A, Z.BarcodeFormat.UPC_E,
                Z.BarcodeFormat.CODE_128, Z.BarcodeFormat.CODE_39, Z.BarcodeFormat.ITF
            ]);
            lectorZxing = new Z.BrowserMultiFormatReader(hints, 150);
            return lectorZxing.decodeFromConstraints(RESTRICCIONES, ui.video, function (resultado) {
                if (resultado) procesar(resultado.getText());
            });
        }).then(function () {
            stream = ui.video.srcObject;
            prepararLinterna();
        });
    }

    /** Un EAN/UPC con dígito verificador correcto se acepta al instante; el resto, al leerse 2 veces igual. */
    function procesar(valor) {
        var codigo = String(valor).trim();
        if (!codigo) return;

        if (esGtin(codigo)) {
            if (digitoValido(codigo)) entregar(codigo);
            return;
        }
        repeticiones = codigo === ultimo ? repeticiones + 1 : 1;
        ultimo = codigo;
        if (repeticiones >= 2) entregar(codigo);
    }

    function detenerCamara() {
        clearTimeout(timer);
        timer = null;
        if (lectorZxing) {
            try { lectorZxing.reset(); } catch (e) { /* ya detenido */ }
            lectorZxing = null;
        }
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        if (ui) {
            ui.video.srcObject = null;
            ui.linterna.hidden = true;
            ui.linterna.classList.remove('active');
        }
    }

    // ---------- Linterna (si el celular la permite) ----------
    function pista() {
        return stream && stream.getVideoTracks ? stream.getVideoTracks()[0] : null;
    }

    function prepararLinterna() {
        var track = pista();
        var caps = track && track.getCapabilities ? track.getCapabilities() : {};
        ui.linterna.hidden = !caps.torch;
    }

    function alternarLinterna() {
        var track = pista();
        if (!track) return;
        var encender = !ui.linterna.classList.contains('active');
        track.applyConstraints({ advanced: [{ torch: encender }] }).then(function () {
            ui.linterna.classList.toggle('active', encender);
        }).catch(function () { ui.linterna.hidden = true; });
    }

    // ---------- Botones "Escanear" que completan un campo ----------
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-escanear-a]');
        if (!btn) return;
        var input = document.getElementById(btn.dataset.escanearA);
        abrir({
            onCodigo: function (codigo) {
                input.value = codigo;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.focus();
            }
        });
    });

    // ---------- Botones "Escanear" que eligen el producto en un <select> ----------
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-escanear-select]');
        if (!btn) return;
        var select = document.getElementById(btn.dataset.escanearSelect);
        abrir({
            onCodigo: function (codigo) {
                fetch(btn.dataset.codigoEndpoint + '?c=' + encodeURIComponent(codigo), { headers: { Accept: 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (d.found && select.querySelector('option[value="' + d.id + '"]')) {
                            select.value = String(d.id);
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                        } else {
                            window.alert(d.found
                                ? 'Ese producto no está disponible para elegir.'
                                : 'Producto no registrado (' + codigo + '). Crealo primero desde Productos.');
                        }
                    })
                    .catch(function () { window.alert('No se pudo buscar el código. Revisá la conexión.'); });
            }
        });
    });

    window.JacobEscaner = { abrir: abrir, cerrar: cerrar };
})();
