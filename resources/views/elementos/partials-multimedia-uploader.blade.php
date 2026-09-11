{{--
    Subida de multimedia por partes.

    El hosting compartido no permite subir upload_max_filesize, asi que los
    videos se cortan en trozos pequenos y se envian uno por uno. El formulario
    del elemento nunca lleva el archivo: solo el token que devuelve el server.
--}}
@php
    $sgcrMultimediaConfig = [
        'chunkSize'   => \App\Support\ChunkUpload::chunkSizeBytes(),
        'concurrency' => \App\Support\ChunkUpload::concurrency(),
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

            // Mientras sube, los botones que envian o avanzan el wizard quedan
            // deshabilitados. El guard del submit sigue siendo la red de atras.
            // Los estilos van inline: el bundle compilado no trae estas clases.
            function bloquearBotones(bloquear) {
                if (!form) {
                    return;
                }

                const botones = form.querySelectorAll('button[type="submit"], button[onclick="nextStep()"], button[onclick="mostrarModalActualizacion()"]');

                Array.prototype.forEach.call(botones, function (btn) {
                    btn.disabled = bloquear;
                    btn.style.opacity = bloquear ? '0.5' : '';
                    btn.style.cursor = bloquear ? 'not-allowed' : '';

                    if (bloquear) {
                        btn.setAttribute('title', 'Espera a que termine la subida del archivo multimedia.');
                    } else {
                        btn.removeAttribute('title');
                    }
                });
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
            function enviarTrozo(archivo, indice, total, uploadId, alAvanzar, enCurso) {
                const inicio = indice * CONFIG.chunkSize;
                const trozo = archivo.slice(inicio, inicio + CONFIG.chunkSize);

                const fd = new FormData();
                fd.append('upload_id', uploadId);
                fd.append('chunk_index', indice);
                fd.append('total_chunks', total);
                fd.append('chunk_size', CONFIG.chunkSize);
                fd.append('file_size', archivo.size);
                fd.append('file_name', archivo.name);
                fd.append('tipo_elemento_id', tipoActual());
                fd.append('chunk', trozo, 'chunk');

                return new Promise(function (resolve, reject) {
                    const xhr = new XMLHttpRequest();

                    enCurso.add(xhr);
                    xhr.addEventListener('loadend', function () {
                        enCurso.delete(xhr);
                    });

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

                        const error = new Error(
                            datos.message || ('Error ' + xhr.status + ' al subir la parte ' + (indice + 1))
                        );
                        // 4xx es un rechazo del servidor: reintentar no lo arregla.
                        error.definitivo = xhr.status >= 400 && xhr.status < 500 && xhr.status !== 408 && xhr.status !== 429;
                        reject(error);
                    });

                    xhr.addEventListener('error', function () {
                        reject(new Error('Se corto la conexion en la parte ' + (indice + 1) + '.'));
                    });

                    xhr.addEventListener('abort', function () {
                        const error = new Error('Subida cancelada.');
                        error.definitivo = true;
                        reject(error);
                    });

                    xhr.send(fd);
                });
            }

            async function subir(archivo) {
                const total = Math.max(1, Math.ceil(archivo.size / CONFIG.chunkSize));
                const uploadId = nuevoUploadId();

                uploadIdActual = uploadId;
                subiendo = true;
                bloquearBotones(true);
                tokenInput.value = '';

                // Bytes ya confirmados por el servidor. Lo que va en vuelo se
                // lleva por parte para que la barra avance dentro de cada una.
                let confirmados = 0;
                let terminadas = 0;
                let siguiente = 0;
                let fallo = null;
                const enVuelo = {};
                const enCurso = new Set();

                function pintarProgreso() {
                    let vuelo = 0;
                    Object.keys(enVuelo).forEach(function (k) { vuelo += enVuelo[k]; });

                    const hechos = Math.min(confirmados + vuelo, archivo.size);
                    const pct = Math.min(100, (hechos / archivo.size) * 100);

                    estado(
                        'Subiendo ' + archivo.name + '  ' + mb(hechos) + ' / ' + mb(archivo.size)
                        + ' MB  (' + terminadas + ' de ' + total + ' partes)',
                        pct
                    );
                }

                // Cada trabajador toma la siguiente parte libre hasta que no
                // quedan. Varias en paralelo tapan la latencia de cada request.
                async function trabajador() {
                    while (!fallo && siguiente < total) {
                        const i = siguiente++;
                        const tamano = Math.min(CONFIG.chunkSize, archivo.size - i * CONFIG.chunkSize);
                        let datos = null;
                        let ultimoError = null;

                        // Una parte suelta puede fallar por red; se reintenta antes de rendirse.
                        for (let intento = 0; intento < 4 && !datos && !fallo; intento++) {
                            try {
                                datos = await enviarTrozo(archivo, i, total, uploadId, function (bytes) {
                                    enVuelo[i] = Math.min(bytes, tamano);
                                    pintarProgreso();
                                }, enCurso);
                            } catch (e) {
                                ultimoError = e;
                                enVuelo[i] = 0;
                                pintarProgreso();
                                if (e.definitivo) {
                                    break;
                                }
                                await new Promise(function (r) { setTimeout(r, 1000 * (intento + 1)); });
                            }
                        }

                        delete enVuelo[i];

                        if (!datos) {
                            fallo = fallo || ultimoError || new Error('No se pudo subir el archivo.');
                            // Corta las demas partes en vuelo: ya no sirven.
                            enCurso.forEach(function (x) { x.abort(); });
                            return;
                        }

                        confirmados += tamano;
                        terminadas++;
                        pintarProgreso();

                        if (datos.done) {
                            tokenInput.value = datos.token;
                        }
                    }
                }

                try {
                    pintarProgreso();

                    const trabajadores = [];
                    for (let t = 0; t < Math.min(CONFIG.concurrency, total); t++) {
                        trabajadores.push(trabajador());
                    }
                    await Promise.all(trabajadores);

                    if (fallo) {
                        throw fallo;
                    }

                    if (tokenInput.value) {
                        estado('Listo: ' + archivo.name + '  (' + mb(archivo.size) + ' MB)', 100);
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
                    bloquearBotones(false);
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
