<?php
/**
 * Musa Café · Integración con las APIs de inteligencia artificial
 * Proveedores: ElevenLabs (audio) y Google Gemini / Lyria (letra y audio).
 * El proveedor activo se elige desde wj-admin → APIs.
 */
if (!defined('MUSA_ARRANQUE')) { http_response_code(403); exit('Acceso directo no permitido.'); }

/** Lista de proveedores disponibles con su descripción. */
function musa_ia_proveedores() {
    return array(
        'elevenlabs' => array('nombre' => 'ElevenLabs', 'detalle' => 'Compone el audio de la canción (MP3).'),
        'google'     => array('nombre' => 'Google Gemini', 'detalle' => 'Escribe la letra y, si se activa Lyria, también el audio.'),
        'ambos'      => array('nombre' => 'Google + ElevenLabs', 'detalle' => 'Google escribe la letra y ElevenLabs compone el audio.'),
        'ninguno'    => array('nombre' => 'Sin IA', 'detalle' => 'Solo guarda el registro para producirlo manualmente.'),
    );
}

/** Proveedor configurado. */
function musa_ia_proveedor($ajustes = null) {
    if ($ajustes === null) { $ajustes = musa_ajustes(); }
    $proveedor = (string) musa_dato($ajustes, 'ia.proveedor', 'elevenlabs');
    return array_key_exists($proveedor, musa_ia_proveedores()) ? $proveedor : 'ninguno';
}

/** Traduce el cuerpo de error de una API a un texto entendible. */
function musa_ia_error_legible($respuesta) {
    $cuerpo = (string) ($respuesta['cuerpo'] ?? '');
    if ($cuerpo === '') { return (string) ($respuesta['error'] ?? 'Sin respuesta del servicio.'); }
    $datos = json_decode($cuerpo, true);
    if (is_array($datos)) {
        if (isset($datos['error']['message'])) { return (string) $datos['error']['message']; }
        if (isset($datos['detail']['message'])) { return (string) $datos['detail']['message']; }
        if (isset($datos['detail']) && is_string($datos['detail'])) { return $datos['detail']; }
        if (isset($datos['message'])) { return (string) $datos['message']; }
    }
    return musa_texto($cuerpo, 300);
}

/** Estado devuelto por ElevenLabs dentro del cuerpo de error (p. ej. missing_permissions). */
function musa_ia_estado_elevenlabs($respuesta) {
    $datos = json_decode((string) ($respuesta['cuerpo'] ?? ''), true);
    return is_array($datos) && isset($datos['detail']['status']) ? (string) $datos['detail']['status'] : '';
}

/**
 * Verifica que una API responda correctamente (sin consumir créditos de generación).
 * Devuelve array('ok', 'mensaje', 'detalle').
 */
function musa_ia_verificar($proveedor, $ajustes = null) {
    if ($ajustes === null) { $ajustes = musa_ajustes(); }

    if ($proveedor === 'elevenlabs') {
        $clave = trim((string) musa_dato($ajustes, 'ia.elevenlabs.api_key', ''));
        if ($clave === '') { return array('ok' => false, 'mensaje' => 'Falta la clave de ElevenLabs.', 'detalle' => ''); }
        $base = rtrim((string) musa_dato($ajustes, 'ia.elevenlabs.endpoint', 'https://api.elevenlabs.io'), '/');

        $respuesta = musa_http($base . '/v1/user/subscription', array(
            'cabeceras' => array('xi-api-key: ' . $clave, 'Accept: application/json'),
            'tiempo'    => 25,
        ));
        if ($respuesta['ok']) {
            $datos = json_decode($respuesta['cuerpo'], true);
            $detalle = 'Respuesta HTTP ' . $respuesta['codigo'];
            if (is_array($datos) && isset($datos['tier'])) {
                $detalle = 'Plan: ' . $datos['tier']
                    . ' · Créditos usados: ' . (int) ($datos['character_count'] ?? 0)
                    . ' de ' . (int) ($datos['character_limit'] ?? 0);
            }
            return array('ok' => true, 'mensaje' => 'ElevenLabs responde correctamente.', 'detalle' => $detalle);
        }
        // Una clave con permisos acotados (solo música) responde 401 con estado missing_permissions:
        // eso confirma que la clave es válida y está autenticando.
        if (musa_ia_estado_elevenlabs($respuesta) === 'missing_permissions') {
            return array(
                'ok' => true,
                'mensaje' => 'ElevenLabs acepta la clave (permisos acotados).',
                'detalle' => 'La clave autentica correctamente pero no tiene permiso de lectura de cuenta. Para confirmar la música usa la prueba de generación.',
            );
        }
        return array(
            'ok' => false,
            'mensaje' => 'ElevenLabs respondió con error ' . $respuesta['codigo'] . '.',
            'detalle' => musa_ia_error_legible($respuesta),
        );
    }

    if ($proveedor === 'google') {
        $clave = trim((string) musa_dato($ajustes, 'ia.google.api_key', ''));
        if ($clave === '') { return array('ok' => false, 'mensaje' => 'Falta la clave de Google.', 'detalle' => ''); }
        $base = rtrim((string) musa_dato($ajustes, 'ia.google.endpoint', 'https://generativelanguage.googleapis.com'), '/');
        $modelo = (string) musa_dato($ajustes, 'ia.google.modelo', 'gemini-3.6-flash');

        $respuesta = musa_http($base . '/v1beta/models/' . rawurlencode($modelo), array(
            'cabeceras' => array('x-goog-api-key: ' . $clave, 'Accept: application/json'),
            'tiempo'    => 25,
        ));
        if ($respuesta['ok']) {
            $datos = json_decode($respuesta['cuerpo'], true);
            $nombre = is_array($datos) && isset($datos['displayName']) ? (string) $datos['displayName'] : $modelo;
            return array('ok' => true, 'mensaje' => 'Google responde correctamente.', 'detalle' => 'Modelo disponible: ' . $nombre);
        }
        if ($respuesta['codigo'] === 404) {
            return array(
                'ok' => false,
                'mensaje' => 'El modelo "' . $modelo . '" no está disponible para esta clave.',
                'detalle' => musa_ia_error_legible($respuesta),
            );
        }
        return array(
            'ok' => false,
            'mensaje' => 'Google respondió con error ' . $respuesta['codigo'] . '.',
            'detalle' => musa_ia_error_legible($respuesta),
        );
    }

    if ($proveedor === 'ambos') {
        $uno = musa_ia_verificar('google', $ajustes);
        $dos = musa_ia_verificar('elevenlabs', $ajustes);
        return array(
            'ok' => ($uno['ok'] && $dos['ok']),
            'mensaje' => 'Google: ' . $uno['mensaje'] . ' · ElevenLabs: ' . $dos['mensaje'],
            'detalle' => trim($uno['detalle'] . ' · ' . $dos['detalle'], ' ·'),
        );
    }

    return array('ok' => true, 'mensaje' => 'Modo sin IA: no se consulta ninguna API.', 'detalle' => '');
}

/** Lista los modelos que la clave de Google puede usar (para el panel). */
function musa_ia_modelos_google($ajustes = null) {
    if ($ajustes === null) { $ajustes = musa_ajustes(); }
    $clave = trim((string) musa_dato($ajustes, 'ia.google.api_key', ''));
    if ($clave === '') { return array(); }
    $base = rtrim((string) musa_dato($ajustes, 'ia.google.endpoint', 'https://generativelanguage.googleapis.com'), '/');
    $respuesta = musa_http($base . '/v1beta/models?pageSize=200', array(
        'cabeceras' => array('x-goog-api-key: ' . $clave, 'Accept: application/json'),
        'tiempo'    => 25,
    ));
    if (!$respuesta['ok']) { return array(); }
    $datos = json_decode($respuesta['cuerpo'], true);
    $modelos = array();
    if (isset($datos['models']) && is_array($datos['models'])) {
        foreach ($datos['models'] as $modelo) {
            $metodos = isset($modelo['supportedGenerationMethods']) ? $modelo['supportedGenerationMethods'] : array();
            if (!in_array('generateContent', $metodos, true)) { continue; }
            $modelos[] = str_replace('models/', '', (string) $modelo['name']);
        }
    }
    sort($modelos);
    return $modelos;
}

/** Construye la descripción musical que se envía a la API. */
function musa_ia_prompt($registro, $ajustes = null) {
    if ($ajustes === null) { $ajustes = musa_ajustes(); }
    $genero = musa_genero((string) ($registro['genero'] ?? ''), $ajustes);
    $estilo = $genero !== null ? (string) $genero['prompt'] : (string) ($registro['genero_nombre'] ?? 'canción popular');
    $nombreGenero = $genero !== null ? (string) $genero['nombre'] : (string) ($registro['genero_nombre'] ?? '');

    $partes = array();
    $partes[] = 'Canción original en español, género ' . $nombreGenero . ': ' . $estilo . '.';
    $partes[] = 'Historia: ' . (string) ($registro['tema'] ?? '');
    if (!empty($registro['dedicatoria'])) { $partes[] = 'Dedicatoria: ' . (string) $registro['dedicatoria']; }
    $partes[] = 'Voz principal cantada, letra clara, tono respetuoso y familiar, producción limpia.';
    return implode(' ', $partes);
}

/** Extrae el texto y el audio incrustado de una respuesta de Google. */
function musa_ia_partes_google($cuerpo) {
    $datos = json_decode((string) $cuerpo, true);
    $texto = '';
    $audio = '';
    $tipo = '';
    if (isset($datos['candidates'][0]['content']['parts']) && is_array($datos['candidates'][0]['content']['parts'])) {
        foreach ($datos['candidates'][0]['content']['parts'] as $parte) {
            if (isset($parte['text'])) { $texto .= $parte['text']; }
            if (isset($parte['inlineData']['data'])) {
                $audio = (string) $parte['inlineData']['data'];
                $tipo = (string) ($parte['inlineData']['mimeType'] ?? '');
            }
        }
    }
    return array('texto' => trim($texto), 'audio' => $audio, 'tipo' => $tipo);
}

/** Genera la letra con Google Gemini. */
function musa_ia_generar_letra($registro, $ajustes = null) {
    if ($ajustes === null) { $ajustes = musa_ajustes(); }
    $clave = trim((string) musa_dato($ajustes, 'ia.google.api_key', ''));
    if ($clave === '') { return array('ok' => false, 'mensaje' => 'Falta la clave de Google.'); }

    $base = rtrim((string) musa_dato($ajustes, 'ia.google.endpoint', 'https://generativelanguage.googleapis.com'), '/');
    $modelo = (string) musa_dato($ajustes, 'ia.google.modelo', 'gemini-3.6-flash');
    $instruccion = (string) musa_dato($ajustes, 'ia.instruccion_letra', '');

    $peticion = array(
        'systemInstruction' => array('parts' => array(array('text' => $instruccion))),
        'contents' => array(array(
            'role' => 'user',
            'parts' => array(array('text' =>
                musa_ia_prompt($registro, $ajustes) . "\n\n" .
                'Devuelve primero una línea con "TÍTULO: " y el título de la canción, y a continuación la letra completa.'
            )),
        )),
        'generationConfig' => array('temperature' => 0.9, 'maxOutputTokens' => 4000),
    );

    $respuesta = musa_http_reintento($base . '/v1beta/models/' . rawurlencode($modelo) . ':generateContent', array(
        'metodo'    => 'POST',
        'cabeceras' => array('Content-Type: application/json', 'x-goog-api-key: ' . $clave),
        'cuerpo'    => json_encode($peticion, JSON_UNESCAPED_UNICODE),
        'tiempo'    => 120,
    ), 3, 4);

    if (!$respuesta['ok']) {
        return array('ok' => false, 'mensaje' => 'Google: ' . musa_ia_error_legible($respuesta));
    }

    $partes = musa_ia_partes_google($respuesta['cuerpo']);
    $texto = $partes['texto'];
    if ($texto === '') {
        return array('ok' => false, 'mensaje' => 'Google no devolvió texto. Revisa el modelo configurado en el panel.');
    }

    $titulo = '';
    if (preg_match('/^\s*(?:T[ÍI]TULO|TITULO)\s*:\s*(.+)$/mu', $texto, $coincidencias)) {
        $titulo = trim($coincidencias[1], " \t\"'“”*");
        $texto = trim((string) preg_replace('/^\s*(?:T[ÍI]TULO|TITULO)\s*:.*$/mu', '', $texto, 1));
    }
    return array('ok' => true, 'letra' => $texto, 'titulo' => $titulo, 'mensaje' => 'Letra generada con Google.');
}

/** Guarda un audio en wj-content/audio y devuelve su ruta relativa. */
function musa_ia_guardar_audio($contenido, $registro, $extension = 'mp3') {
    if (!is_dir(MUSA_DIR_AUDIO)) { @mkdir(MUSA_DIR_AUDIO, 0775, true); }
    $extension = preg_match('/^[a-z0-9]{2,4}$/', $extension) ? $extension : 'mp3';
    $nombre = musa_slug((string) ($registro['codigo'] ?? 'musa')) . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destino = MUSA_DIR_AUDIO . '/' . $nombre;
    if (@file_put_contents($destino, $contenido) === false) { return ''; }
    @chmod($destino, 0664);
    return 'wj-content/audio/' . $nombre;
}

/** Genera el audio con ElevenLabs y lo guarda en wj-content/audio. */
function musa_ia_generar_audio($registro, $ajustes = null, $letra = '') {
    if ($ajustes === null) { $ajustes = musa_ajustes(); }
    $clave = trim((string) musa_dato($ajustes, 'ia.elevenlabs.api_key', ''));
    if ($clave === '') { return array('ok' => false, 'mensaje' => 'Falta la clave de ElevenLabs.'); }

    $base = rtrim((string) musa_dato($ajustes, 'ia.elevenlabs.endpoint', 'https://api.elevenlabs.io'), '/');
    $modelo = (string) musa_dato($ajustes, 'ia.elevenlabs.modelo', 'music_v1');
    $duracion = (int) musa_dato($ajustes, 'ia.elevenlabs.duracion_ms', 60000);
    $duracion = max(10000, min(300000, $duracion));
    $formato = (string) musa_dato($ajustes, 'ia.elevenlabs.formato', 'mp3_44100_128');

    $prompt = musa_ia_prompt($registro, $ajustes);
    if (trim($letra) !== '') { $prompt .= ' Utiliza esta letra: ' . musa_texto($letra, 1500); }

    $respuesta = musa_http_reintento($base . '/v1/music?output_format=' . rawurlencode($formato), array(
        'metodo'    => 'POST',
        'cabeceras' => array('Content-Type: application/json', 'xi-api-key: ' . $clave, 'Accept: audio/mpeg'),
        'cuerpo'    => json_encode(array(
            'prompt'          => $prompt,
            'music_length_ms' => $duracion,
            'model_id'        => $modelo,
        ), JSON_UNESCAPED_UNICODE),
        'tiempo'    => 300,
    ), 2, 5);

    if (!$respuesta['ok']) {
        return array('ok' => false, 'mensaje' => 'ElevenLabs: ' . musa_ia_error_legible($respuesta));
    }
    $contenido = (string) $respuesta['cuerpo'];
    if (strlen($contenido) < 2048 || stripos((string) $respuesta['tipo'], 'json') !== false) {
        return array('ok' => false, 'mensaje' => 'ElevenLabs no devolvió audio válido: ' . musa_ia_error_legible($respuesta));
    }

    $ruta = musa_ia_guardar_audio($contenido, $registro, 'mp3');
    if ($ruta === '') {
        return array('ok' => false, 'mensaje' => 'No fue posible guardar el audio en wj-content/audio (revisa permisos de escritura).');
    }
    return array('ok' => true, 'audio' => $ruta, 'mensaje' => 'Audio generado con ElevenLabs (' . musa_peso(strlen($contenido)) . ').');
}

/** Genera el audio con Google Lyria (opcional; requiere plan con cupo). */
function musa_ia_generar_audio_google($registro, $ajustes = null, $letra = '') {
    if ($ajustes === null) { $ajustes = musa_ajustes(); }
    $clave = trim((string) musa_dato($ajustes, 'ia.google.api_key', ''));
    if ($clave === '') { return array('ok' => false, 'mensaje' => 'Falta la clave de Google.'); }

    $base = rtrim((string) musa_dato($ajustes, 'ia.google.endpoint', 'https://generativelanguage.googleapis.com'), '/');
    $modelo = (string) musa_dato($ajustes, 'ia.google.modelo_musica', 'lyria-3.5');

    $prompt = musa_ia_prompt($registro, $ajustes);
    if (trim($letra) !== '') { $prompt .= ' Letra: ' . musa_texto($letra, 1200); }

    $respuesta = musa_http_reintento($base . '/v1beta/models/' . rawurlencode($modelo) . ':generateContent', array(
        'metodo'    => 'POST',
        'cabeceras' => array('Content-Type: application/json', 'x-goog-api-key: ' . $clave),
        'cuerpo'    => json_encode(array(
            'contents' => array(array('role' => 'user', 'parts' => array(array('text' => $prompt)))),
        ), JSON_UNESCAPED_UNICODE),
        'tiempo'    => 300,
    ), 2, 5);

    if (!$respuesta['ok']) {
        return array('ok' => false, 'mensaje' => 'Google (' . $modelo . '): ' . musa_ia_error_legible($respuesta));
    }
    $partes = musa_ia_partes_google($respuesta['cuerpo']);
    if ($partes['audio'] === '') {
        return array('ok' => false, 'mensaje' => 'El modelo ' . $modelo . ' no devolvió audio.');
    }
    $binario = base64_decode($partes['audio'], true);
    if ($binario === false || strlen($binario) < 2048) {
        return array('ok' => false, 'mensaje' => 'El audio devuelto por Google no es válido.');
    }
    $extension = (stripos($partes['tipo'], 'wav') !== false) ? 'wav' : 'mp3';
    $ruta = musa_ia_guardar_audio($binario, $registro, $extension);
    if ($ruta === '') {
        return array('ok' => false, 'mensaje' => 'No fue posible guardar el audio en wj-content/audio (revisa permisos de escritura).');
    }
    return array('ok' => true, 'audio' => $ruta, 'mensaje' => 'Audio generado con Google ' . $modelo . ' (' . musa_peso(strlen($binario)) . ').');
}

/**
 * Prueba real de generación (consume créditos): 10 segundos de música
 * o una estrofa corta, según el proveedor. Se usa desde wj-admin.
 */
function musa_ia_prueba_generacion($proveedor, $ajustes = null) {
    if ($ajustes === null) { $ajustes = musa_ajustes(); }
    $registro = array_merge(musa_registro_base(), array(
        'codigo'        => 'PRUEBA-' . date('YmdHis'),
        'nombre'        => 'Prueba del sistema',
        'genero'        => 'cumbia',
        'genero_nombre' => 'Cumbia',
        'tema'          => 'Una prueba corta para verificar que la API responde.',
    ));

    if ($proveedor === 'elevenlabs' || $proveedor === 'ambos') {
        $copia = $ajustes;
        musa_fijar($copia, 'ia.elevenlabs.duracion_ms', 10000);
        $resultado = musa_ia_generar_audio($registro, $copia);
        if (!empty($resultado['ok']) && !empty($resultado['audio'])) {
            @unlink(MUSA_RAIZ . '/' . $resultado['audio']);
        }
        return array('ok' => !empty($resultado['ok']), 'mensaje' => $resultado['mensaje'], 'detalle' => 'Prueba de 10 segundos con ElevenLabs.');
    }

    if ($proveedor === 'google') {
        $resultado = musa_ia_generar_letra($registro, $ajustes);
        return array(
            'ok' => !empty($resultado['ok']),
            'mensaje' => $resultado['mensaje'],
            'detalle' => !empty($resultado['ok']) ? musa_texto($resultado['letra'], 220) : '',
        );
    }

    return array('ok' => true, 'mensaje' => 'Modo sin IA: no hay nada que probar.', 'detalle' => '');
}

/**
 * Ejecuta la generación completa según el proveedor configurado
 * y actualiza el registro (letra, audio, estado y casilla "Creado").
 */
function musa_ia_generar($identificador, $ajustes = null) {
    if ($ajustes === null) { $ajustes = musa_ajustes(); }
    $registro = musa_registro_obtener($identificador);
    if ($registro === null) { return array('ok' => false, 'mensaje' => 'El registro no existe.'); }

    $proveedor = musa_ia_proveedor($ajustes);
    if ($proveedor === 'ninguno') {
        musa_registro_actualizar($registro['id'], array('estado' => 'pendiente', 'proveedor' => 'ninguno', 'mensaje' => 'Producción manual: la IA está desactivada.'));
        return array('ok' => false, 'mensaje' => 'La generación automática está desactivada (modo sin IA).');
    }

    musa_registro_actualizar($registro['id'], array('estado' => 'generando', 'proveedor' => $proveedor, 'mensaje' => ''));

    $letra = '';
    $titulo = '';
    $audio = '';
    $avisos = array();

    if ($proveedor === 'google' || $proveedor === 'ambos') {
        $resultado = musa_ia_generar_letra($registro, $ajustes);
        if (!$resultado['ok']) {
            if ($proveedor === 'google') {
                musa_registro_actualizar($registro['id'], array('estado' => 'error', 'mensaje' => $resultado['mensaje']));
                musa_log('Error generando letra', array('codigo' => $registro['codigo'], 'detalle' => $resultado['mensaje']));
                return array('ok' => false, 'mensaje' => $resultado['mensaje']);
            }
            $avisos[] = $resultado['mensaje'];
        } else {
            $letra = $resultado['letra'];
            $titulo = $resultado['titulo'];
        }
    }

    if ($proveedor === 'elevenlabs' || $proveedor === 'ambos') {
        $resultado = musa_ia_generar_audio($registro, $ajustes, $letra);
        if (!$resultado['ok']) {
            $mensaje = $resultado['mensaje'] . ($avisos ? ' · ' . implode(' · ', $avisos) : '');
            musa_registro_actualizar($registro['id'], array('estado' => 'error', 'mensaje' => $mensaje, 'letra' => $letra, 'titulo_cancion' => $titulo));
            musa_log('Error generando audio', array('codigo' => $registro['codigo'], 'detalle' => $resultado['mensaje']));
            return array('ok' => false, 'mensaje' => $mensaje);
        }
        $audio = $resultado['audio'];
    } elseif ($proveedor === 'google' && !empty(musa_dato($ajustes, 'ia.google.generar_audio', false))) {
        $resultado = musa_ia_generar_audio_google($registro, $ajustes, $letra);
        if (!$resultado['ok']) {
            $avisos[] = $resultado['mensaje'];
        } else {
            $audio = $resultado['audio'];
        }
    }

    $cambios = array(
        'estado'        => 'listo',
        'creado'        => true,
        'fecha_creado'  => date('Y-m-d H:i:s'),
        'proveedor'     => $proveedor,
        'letra'         => $letra,
        'titulo_cancion'=> $titulo,
        'audio'         => $audio,
        'mensaje'       => $avisos ? implode(' · ', $avisos) : 'Canción generada correctamente.',
    );
    $actualizado = musa_registro_actualizar($registro['id'], $cambios);
    musa_log('Canción generada', array('codigo' => $registro['codigo'], 'proveedor' => $proveedor, 'audio' => $audio));

    return array('ok' => true, 'mensaje' => $cambios['mensaje'], 'registro' => $actualizado);
}

/* ------------------------------------------------------------------ */
/* Transcripción de voz a texto                                        */
/* ------------------------------------------------------------------ */

/** Modo de dictado configurado: navegador, servidor, ambos o ninguno. */
function musa_transcripcion_modo($ajustes = null) {
    if ($ajustes === null) { $ajustes = musa_ajustes(); }
    $modo = (string) musa_dato($ajustes, 'ia.transcripcion.modo', 'ambos');
    return in_array($modo, array('navegador', 'servidor', 'ambos', 'ninguno'), true) ? $modo : 'ambos';
}

/** ¿Hay una API disponible para transcribir en el servidor? */
function musa_transcripcion_servidor_disponible($ajustes = null) {
    if ($ajustes === null) { $ajustes = musa_ajustes(); }
    if (!in_array(musa_transcripcion_modo($ajustes), array('servidor', 'ambos'), true)) { return false; }
    return trim((string) musa_dato($ajustes, 'ia.elevenlabs.api_key', '')) !== ''
        || trim((string) musa_dato($ajustes, 'ia.google.api_key', '')) !== '';
}

/** Transcribe con ElevenLabs (modelo scribe). */
function musa_transcribir_elevenlabs($contenido, $tipo, $ajustes) {
    $clave = trim((string) musa_dato($ajustes, 'ia.elevenlabs.api_key', ''));
    if ($clave === '') { return array('ok' => false, 'mensaje' => 'Falta la clave de ElevenLabs.'); }

    $base = rtrim((string) musa_dato($ajustes, 'ia.elevenlabs.endpoint', 'https://api.elevenlabs.io'), '/');
    $modelo = (string) musa_dato($ajustes, 'ia.transcripcion.elevenlabs_modelo', 'scribe_v1');
    $idioma = substr((string) musa_dato($ajustes, 'ia.transcripcion.idioma', 'es-CO'), 0, 2);

    $extension = str_replace(array('audio/', 'x-'), '', $tipo);
    if ($extension === 'mpeg') { $extension = 'mp3'; }
    $partes = musa_multipart(
        array('model_id' => $modelo, 'language_code' => $idioma),
        array('campo' => 'file', 'nombre' => 'dictado.' . $extension, 'tipo' => $tipo, 'contenido' => $contenido)
    );

    $respuesta = musa_http_reintento($base . '/v1/speech-to-text', array(
        'metodo'    => 'POST',
        'cabeceras' => array($partes['cabecera'], 'xi-api-key: ' . $clave, 'Accept: application/json'),
        'cuerpo'    => $partes['cuerpo'],
        'tiempo'    => 120,
    ), 2, 4);

    if (!$respuesta['ok']) {
        return array('ok' => false, 'mensaje' => 'ElevenLabs: ' . musa_ia_error_legible($respuesta));
    }
    $datos = json_decode($respuesta['cuerpo'], true);
    $texto = is_array($datos) && isset($datos['text']) ? trim((string) $datos['text']) : '';
    if ($texto === '') {
        return array('ok' => false, 'mensaje' => 'No se entendió el audio. Intenta de nuevo hablando más cerca del micrófono.');
    }
    return array('ok' => true, 'texto' => $texto, 'motor' => 'ElevenLabs');
}

/** Transcribe con Google (audio incrustado en generateContent). */
function musa_transcribir_google($contenido, $tipo, $ajustes) {
    $clave = trim((string) musa_dato($ajustes, 'ia.google.api_key', ''));
    if ($clave === '') { return array('ok' => false, 'mensaje' => 'Falta la clave de Google.'); }

    $base = rtrim((string) musa_dato($ajustes, 'ia.google.endpoint', 'https://generativelanguage.googleapis.com'), '/');
    $modelo = (string) musa_dato($ajustes, 'ia.transcripcion.google_modelo', 'gemini-3.6-flash');

    $peticion = array(
        'contents' => array(array(
            'role' => 'user',
            'parts' => array(
                array('text' => 'Transcribe literalmente lo que se dice en este audio, en español. Responde solo con la transcripción, sin comentarios ni comillas.'),
                array('inlineData' => array('mimeType' => $tipo, 'data' => base64_encode($contenido))),
            ),
        )),
        'generationConfig' => array('temperature' => 0, 'maxOutputTokens' => 2000),
    );

    $respuesta = musa_http_reintento($base . '/v1beta/models/' . rawurlencode($modelo) . ':generateContent', array(
        'metodo'    => 'POST',
        'cabeceras' => array('Content-Type: application/json', 'x-goog-api-key: ' . $clave),
        'cuerpo'    => json_encode($peticion, JSON_UNESCAPED_UNICODE),
        'tiempo'    => 120,
    ), 2, 4);

    if (!$respuesta['ok']) {
        return array('ok' => false, 'mensaje' => 'Google: ' . musa_ia_error_legible($respuesta));
    }
    $partes = musa_ia_partes_google($respuesta['cuerpo']);
    $texto = trim($partes['texto']);
    if ($texto === '') {
        return array('ok' => false, 'mensaje' => 'No se entendió el audio. Intenta de nuevo hablando más cerca del micrófono.');
    }
    return array('ok' => true, 'texto' => $texto, 'motor' => 'Google');
}

/**
 * Transcribe un audio con el proveedor configurado.
 * Si el proveedor principal falla, intenta con el otro.
 */
function musa_ia_transcribir($contenido, $tipo, $ajustes = null) {
    if ($ajustes === null) { $ajustes = musa_ajustes(); }

    $proveedor = musa_ia_proveedor($ajustes);
    $orden = ($proveedor === 'google') ? array('google', 'elevenlabs') : array('elevenlabs', 'google');

    $mensajes = array();
    foreach ($orden as $motor) {
        $clave = trim((string) musa_dato($ajustes, 'ia.' . $motor . '.api_key', ''));
        if ($clave === '') { continue; }
        $resultado = ($motor === 'google')
            ? musa_transcribir_google($contenido, $tipo, $ajustes)
            : musa_transcribir_elevenlabs($contenido, $tipo, $ajustes);
        if (!empty($resultado['ok'])) { return $resultado; }
        $mensajes[] = $resultado['mensaje'];
    }

    if ($mensajes === array()) {
        return array('ok' => false, 'mensaje' => 'No hay ninguna clave de API configurada para transcribir.');
    }
    return array('ok' => false, 'mensaje' => implode(' · ', $mensajes));
}
