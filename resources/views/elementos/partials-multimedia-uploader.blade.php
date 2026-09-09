{{--
    Subida de multimedia por partes.

    El hosting compartido no permite subir upload_max_filesize, asi que los
    videos se cortan en trozos pequenos y se envian uno por uno. El formulario
    del elemento nunca lleva el archivo: solo el token que devuelve el server.
--}}
@php
    $sgcrMultimediaConfig = [
        'chunkSize'   => (int) config('uploads.chunk_size_bytes'),
        'maxBytes'    => (int) config('uploads.video.max_size_bytes'),
        'extensiones' => array_values((array) config('uploads.video.extensiones', [])),
        'tipos'       => array_map('intval', \App\Models\TipoElemento::idsQuePermitenMultimedia()),
        'urlSubir'    => route('uploads.chunk.store'),
        'urlAbortar'  => url('/uploads/chunk'),
        'csrf'        => csrf_token(),
    ];
@endphp
<script>
    window.SGCRMultimedia = (function () {
        const CONFIG = @json($sgcrMultimediaConfig);

        // Los formularios de elemento se envian con form.submit(), que no
        // dispara el evento 'submit'. Cada llamador consulta este registro
        // antes de enviar para no perder una subida a medias.
        const instancias = [];

        const ACCEPT_DOCS = '.pdf,.doc,.docx';

        function extensionDe(nombre) {
            const partes = String(nombre || '').split('.');
            return partes.length > 1 ? partes.pop().toLowerCase() : '';
        }

        function esMultimedia(nombre) {
            return CONFIG.extensiones.indexOf(extensionDe(nombre)) !== -1;
        }

        function mb(bytes) {
            return (bytes / 1024 / 1024).toFixed(1);
        }

        function nuevoUploadId() {
            const buf = new Uint8Array(16);
            (window.crypto || window.msCrypto).getRandomValues(buf);
            return Array.from(buf).map(function (b) {
                return b.toString(16).padStart(2, '0');
            }).join('');
        }

        function init(opts) {
            const input = document.getElementById(opts.inputId);
            const tokenInput = document.getElementById(opts.tokenInputId);
            const panel = document.getElementById(opts.panelId);
            const barra = document.getElementById(opts.barraId);
            const texto = document.getElementById(opts.textoId);
            const hint = opts.hintId ? document.getElementById(opts.hintId) : null;
            const form = input ? input.closest('form') : null;

            if (!input || !tokenInput || !panel || !barra || !texto) {
                return null;
            }

            let subiendo = false;
            let uploadIdActual = null;

            function tipoActual() {
                if (opts.tipoFijo) {
                    return parseInt(opts.tipoFijo, 10);
                }
                const sel = document.getElementById(opts.tipoSelectId);
                return sel ? parseInt(sel.value, 10) : NaN;
            }

            function tipoAceptaMultimedia() {
                return CONFIG.tipos.indexOf(tipoActual()) !== -1;
            }

            function sincronizarAccept() {
                if (tipoAceptaMultimedia()) {
                    const media = CONFIG.extensiones.map(function (e) { return '.' + e; }).join(',');
                    input.accept = ACCEPT_DOCS + ',' + media;
                    if (hint) {
                        hint.textContent = 'DOCX, PDF, o video/audio';
                    }
                } else {
                    input.accept = ACCEPT_DOCS;
                    if (hint) {
                        hint.textContent = 'DOCX';
                    }
                    limpiar();
                }
            }

            function limpiar() {
                tokenInput.value = '';
                panel.classList.add('hidden');
                barra.style.width = '0%';
                texto.textContent = '';
            }

            function avisarSubidaEnCurso() {
                estado('Espera a que termine la subida del archivo multimedia.', 100, true);

                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'info',
                        title: 'Subida en curso',
                        text: 'El video todavia se esta subiendo. Espera a que la barra llegue al 100% antes de guardar.',
                    });
                }

                panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // El color va por estilo inline porque el bundle compilado no trae
            // las clases de Tailwind que usaria aqui.
            function estado(msg, pct, error) {
                panel.classList.remove('hidden');
                texto.textContent = msg;
                texto.classList.toggle('text-red-600', !!error);
                barra.style.width = (pct || 0) + '%';
                barra.style.background = error ? '#ef4444' : '#6366f1';
            }

            /**
             * XHR en vez de fetch: fetch no reporta cuanto lleva subido, y con
             * el se veria un salto seco por trozo en lugar de una barra que
             * avanza.
             */
            function enviarTrozo(archivo, indice, total, uploadId, alAvanzar) {
                const inicio = indice * CONFIG.chunkSize;
                const trozo = archivo.slice(inicio, inicio + CONFIG.chunkSize);

                const fd = new FormData();
                fd.append('upload_id', uploadId);
                fd.append('chunk_index', indice);
                fd.append('total_chunks', total);
                fd.append('file_name', archivo.name);
                fd.append('tipo_elemento_id', tipoActual());
                fd.append('chunk', trozo, 'chunk');

                return new Promise(function (resolve, reject) {
                    const xhr = new XMLHttpRequest();

                    xhr.open('POST', CONFIG.urlSubir, true);
                    xhr.withCredentials = true;
                    xhr.setRequestHeader('X-CSRF-TOKEN', CONFIG.csrf);
                    xhr.setRequestHeader('Accept', 'application/json');

                    xhr.upload.addEventListener('progress', function (ev) {
                        if (ev.lengthComputable && alAvanzar) {
                            alAvanzar(ev.loaded);
                        }
                    });

                    xhr.addEventListener('load', function () {
                        let datos = {};
                        try {
                            datos = JSON.parse(xhr.responseText);
                        } catch (e) {
                            datos = {};
                        }

                        if (xhr.status >= 200 && xhr.status < 300) {
                            resolve(datos);
                            return;
                        }

                        reject(new Error(
                            datos.message || ('Error ' + xhr.status + ' al subir la parte ' + (indice + 1))
                        ));
                    });

                    xhr.addEventListener('error', function () {
                        reject(new Error('Se corto la conexion en la parte ' + (indice + 1) + '.'));
                    });

                    xhr.addEventListener('abort', function () {
                        reject(new Error('Subida cancelada.'));
                    });

                    xhr.send(fd);
                });
            }

            async function subir(archivo) {
                const total = Math.max(1, Math.ceil(archivo.size / CONFIG.chunkSize));
                const uploadId = nuevoUploadId();

                uploadIdActual = uploadId;
                subiendo = true;
                tokenInput.value = '';

                // Bytes ya confirmados por el servidor. Lo que va en vuelo se
                // suma aparte para que la barra avance dentro de cada trozo.
                let confirmados = 0;

                function pintarProgreso(enVuelo, i) {
                    const hechos = Math.min(confirmados + enVuelo, archivo.size);
                    const pct = Math.min(100, (hechos / archivo.size) * 100);

                    estado(
                        'Subiendo ' + archivo.name + '  ' + mb(hechos) + ' / ' + mb(archivo.size)
                        + ' MB  (parte ' + (i + 1) + ' de ' + total + ')',
                        pct
                    );
                }

                try {
                    for (let i = 0; i < total; i++) {
                        let datos = null;
                        let ultimoError = null;

                        pintarProgreso(0, i);

                        // Una parte suelta puede fallar por red; se reintenta antes de rendirse.
                        for (let intento = 0; intento < 3 && !datos; intento++) {
                            try {
                                datos = await enviarTrozo(archivo, i, total, uploadId, function (enVuelo) {
                                    pintarProgreso(enVuelo, i);
                                });
                            } catch (e) {
                                ultimoError = e;
                                pintarProgreso(0, i);
                                await new Promise(function (r) { setTimeout(r, 800 * (intento + 1)); });
                            }
                        }

                        if (!datos) {
                            throw ultimoError || new Error('No se pudo subir el archivo.');
                        }

                        confirmados = Math.min(confirmados + CONFIG.chunkSize, archivo.size);
                        pintarProgreso(0, i);

                        if (datos.done) {
                            tokenInput.value = datos.token;
                            estado('Listo: ' + archivo.name + '  (' + mb(datos.size) + ' MB)', 100);
                        }
                    }

                    if (!tokenInput.value) {
                        throw new Error('El servidor no confirmo la subida.');
                    }

                    // El archivo ya vive en el servidor: el formulario solo manda el token.
                    input.value = '';
                } catch (e) {
                    estado(e.message || 'Error al subir el archivo.', 100, true);
                    tokenInput.value = '';
                    input.value = '';
                    abortar(uploadId);
                } finally {
                    subiendo = false;
                    uploadIdActual = null;
                }
            }

            function abortar(uploadId) {
                if (!uploadId) {
                    return;
                }
                fetch(CONFIG.urlAbortar + '/' + uploadId, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CONFIG.csrf, 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    keepalive: true,
                }).catch(function () {});
            }

            input.addEventListener('change', function () {
                const archivo = input.files && input.files[0];

                if (!archivo) {
                    limpiar();
                    return;
                }

                if (!esMultimedia(archivo.name)) {
                    // Documento normal: sigue el flujo original del formulario.
                    limpiar();
                    return;
                }

                if (!tipoAceptaMultimedia()) {
                    input.value = '';
                    estado('Este tipo de elemento no acepta video ni audio.', 100, true);
                    return;
                }

                if (archivo.size > CONFIG.maxBytes) {
                    input.value = '';
                    estado(
                        'El archivo pesa ' + mb(archivo.size) + ' MB y el limite es '
                        + Math.round(CONFIG.maxBytes / 1024 / 1024) + ' MB.',
                        100,
                        true
                    );
                    return;
                }

                subir(archivo);
            });

            if (!opts.tipoFijo && opts.tipoSelectId) {
                const sel = document.getElementById(opts.tipoSelectId);
                if (sel) {
                    sel.addEventListener('change', sincronizarAccept);
                    if (window.jQuery) {
                        window.jQuery(sel).on('select2:select change', sincronizarAccept);
                    }
                }
            }

            // form.submit() no dispara el evento 'submit', y el formulario del
            // elemento se envia asi. El guard tiene que estar tambien en los
            // llamadores; aqui solo se cubre el envio normal.
            if (form) {
                form.addEventListener('submit', function (ev) {
                    if (subiendo) {
                        ev.preventDefault();
                        ev.stopImmediatePropagation();
                        avisarSubidaEnCurso();
                    }
                });
            }

            instancias.push({ estaSubiendo: function () { return subiendo; }, avisar: avisarSubidaEnCurso });

            window.addEventListener('beforeunload', function (ev) {
                if (subiendo) {
                    abortar(uploadIdActual);
                    ev.preventDefault();
                    ev.returnValue = '';
                }
            });

            sincronizarAccept();

            return { sincronizarAccept: sincronizarAccept, limpiar: limpiar };
        }

        /**
         * true si alguna subida sigue en curso. Avisa al usuario de paso, para
         * que el llamador solo tenga que abortar su envio.
         */
        function bloqueaEnvio() {
            const ocupada = instancias.find(function (i) { return i.estaSubiendo(); });

            if (!ocupada) {
                return false;
            }

            ocupada.avisar();

            return true;
        }

        return {
            init: init,
            esMultimedia: esMultimedia,
            bloqueaEnvio: bloqueaEnvio,
            config: CONFIG,
        };
    })();
</script>
