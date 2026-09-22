<?php
/**
 * Musa Café · Configuración
 * Todo lo configurable (colores, imágenes, logos, géneros, APIs y correo)
 * vive en wj-content/config/ajustes.json y se edita desde wj-admin.
 */
if (!defined('MUSA_ARRANQUE')) { http_response_code(403); exit('Acceso directo no permitido.'); }

/** Géneros musicales predeterminados (editables desde wj-admin). */
function musa_generos_predeterminados() {
    $lista = array(
        array('pop', 'Pop', 'brillante y pegajoso', 'pop luminoso y moderno, sintetizadores cálidos, coro contagioso'),
        array('rock', 'Rock', 'eléctrico y potente', 'rock con guitarras eléctricas, batería marcada y energía en el estribillo'),
        array('balada', 'Balada', 'íntima y sentida', 'balada romántica con piano, cuerdas suaves y voz cercana'),
        array('cumbia', 'Cumbia', 'raíz y fiesta', 'cumbia colombiana con gaita, acordeón, guacharaca y tambor alegre'),
        array('salsa', 'Salsa', 'caliente y brava', 'salsa con metales brillantes, piano montuno, timbales y coro sabroso'),
        array('jazz', 'Jazz', 'libre y elegante', 'jazz suave con piano, contrabajo, escobillas y saxofón improvisando'),
        array('hip-hop', 'Hip-Hop', 'urbano y directo', 'hip hop con beat marcado, bajo profundo y flow narrativo en español'),
        array('reggaeton', 'Reggaetón', 'perreo y calle', 'reggaetón moderno con dembow, sintetizadores y estribillo pegajoso'),
        array('electronica', 'Electrónica', 'sintética y vibrante', 'electrónica melódica con sintetizadores, bombo constante y atmósfera amplia'),
        array('bolero', 'Bolero', 'nostálgico y romántico', 'bolero clásico con guitarra, requinto y voz nostálgica'),
        array('vallenato', 'Vallenato', 'juglar y parranda', 'vallenato con acordeón, caja y guacharaca, aire de parranda'),
        array('bachata', 'Bachata', 'dulce y bailable', 'bachata con guitarra requinto, bongó y güira, romántica y bailable'),
        array('folk-andino', 'Folk Andino', 'altiplano y raíz', 'música andina con quena, charango, zampoña y aire de altiplano nariñense'),
        array('bossa-nova', 'Bossa Nova', 'suave y sofisticada', 'bossa nova con guitarra sincopada, voz susurrada y percusión ligera'),
        array('funk', 'Funk', 'groove y ritmo', 'funk con bajo slap, guitarra wah, metales y groove bailable'),
        array('ranchera', 'Ranchera', 'bravía y sentida', 'ranchera mexicana con mariachi, trompetas y voz desgarrada'),
    );

    $generos = array();
    foreach ($lista as $indice => $fila) {
        list($id, $nombre, $descripcion, $prompt) = $fila;
        $generos[] = array(
            'id'          => $id,
            'nombre'      => $nombre,
            'descripcion' => $descripcion,
            'imagen'      => 'wj-includes/images/optimizadas/' . $id . '.png',
            'prompt'      => $prompt,
            'activo'      => true,
            'orden'       => $indice + 1,
        );
    }
    return $generos;
}

/** Ajustes predeterminados del sistema. */
function musa_ajustes_predeterminados() {
    return array(
        'marca' => array(
            'nombre'        => 'Musa Café',
            'eslogan'       => 'Tu historia hecha canción',
            'entidad'       => 'Gobernación de Nariño',
            'titulo_sitio'  => 'Musa Café · Crea tu canción',
            'descripcion'   => 'Elige un género, cuéntanos tu historia y recibe tu canción en el correo.',
            'logo'          => 'wj-includes/images/optimizadas/logo_musacafe.png',
            'fondo'         => 'wj-includes/images/optimizadas/bg.png',
            'barra'         => 'wj-includes/images/optimizadas/bg_barra.png',
            'favicon'       => 'wj-includes/images/optimizadas/logo_musacafe.png',
            'sitio_entidad' => 'https://www.narino.gov.co',
        ),
        'colores' => array(
            'fondo'            => '#AE1D2C',
            'fondo_profundo'   => '#7E0E1C',
            'tarjeta'          => '#9F1427',
            'tarjeta_borde'    => '#C3364A',
            'tarjeta_activa'   => '#F6EDD9',
            'texto'            => '#F7EFE0',
            'texto_suave'      => '#EBC9CE',
            'texto_activo'     => '#7E0E1C',
            'acento'           => '#F2B705',
            'acento_secundario'=> '#12A5C4',
            'exito'            => '#2E9E6B',
            'error'            => '#FFB3BC',
        ),
        'textos' => array(
            'titulo'          => 'Crea tu canción',
            'subtitulo'       => 'Elige el género, cuéntanos de qué se trata y te la enviamos al correo.',
            'paso1_titulo'    => '¿En qué género quieres que suene?',
            'paso1_ayuda'     => 'Selecciona un género para continuar',
            'tema_etiqueta'   => 'Cuéntanos la historia de tu canción',
            'tema_ejemplo'    => 'Un café compartido en Pasto una tarde de lluvia, entre amigos que no se veían hace años…',
            'paso2_titulo'    => '¿A nombre de quién la componemos?',
            'paso2_ayuda'     => 'Enviaremos la canción terminada a este correo.',
            'boton_continuar' => 'Continuar',
            'boton_enviar'    => 'Componer mi canción',
            'generando'       => 'Estamos componiendo tu canción…',
            'listo_titulo'    => '¡Listo! Tu canción va en camino',
            'listo_texto'     => 'Te avisaremos al correo apenas esté lista. Guarda tu código de seguimiento.',
            'aviso_datos'     => 'Autorizo el tratamiento de mis datos personales conforme a la Ley 1581 de 2012 y la política de la Gobernación de Nariño.',
            'pie'             => 'Gobernación de Nariño · Musa Café',
        ),
        'generos' => musa_generos_predeterminados(),
        'formulario' => array(
            'pedir_telefono'     => true,
            'pedir_ciudad'       => true,
            'pedir_dedicatoria'  => true,
            'telefono_obligatorio' => false,
            'ciudad_obligatoria'   => false,
            'minimo_tema'        => 15,
            'maximo_tema'        => 600,
        ),
        'ia' => array(
            'proveedor'             => 'elevenlabs',
            'generacion_automatica' => true,
            'elevenlabs' => array(
                'api_key'      => '',
                'endpoint'     => 'https://api.elevenlabs.io',
                'modelo'       => 'music_v1',
                'duracion_ms'  => 60000,
                'formato'      => 'mp3_44100_128',
            ),
            'google' => array(
                'api_key'  => '',
                'endpoint' => 'https://generativelanguage.googleapis.com',
                'modelo'         => 'gemini-3.6-flash',
                'modelo_musica'  => 'lyria-3.5',
                'generar_audio'  => false,
            ),
            'instruccion_letra' => "Eres un compositor colombiano. Escribe la letra de una canción original en español, con título, dos estrofas, un coro que se repita y un puente. Debe ser respetuosa, familiar y emotiva. Menciona de forma natural el café y la región de Nariño solo si encaja con la historia.",
        ),
        'correo' => array(
            'activo'          => true,
            'metodo'          => 'mail',
            'remitente'       => 'no-responder@narino.gov.co',
            'nombre_remitente'=> 'Musa Café · Gobernación de Nariño',
            'responder_a'     => '',
            'copia_oculta'    => '',
            'asunto'          => 'Tu canción de Musa Café ya está lista',
            'adjuntar_audio'  => true,
            'maximo_adjunto_mb' => 8,
            'mensaje'         => "Hola {nombre},\n\nTu canción en género {genero} ya está lista.\n\nHistoria: {tema}\n\nCódigo de seguimiento: {codigo}\n\nGracias por pasar por Musa Café.\nGobernación de Nariño",
            'smtp' => array(
                'host'      => '',
                'puerto'    => 587,
                'seguridad' => 'tls',
                'usuario'   => '',
                'clave'     => '',
            ),
        ),
        'seguridad' => array(
            'limite_por_hora'     => 5,
            'limite_por_dia'      => 20,
            'exigir_aceptacion'   => true,
            // IPs de proxys propios (balanceador, CDN) cuyas cabeceras X-Real-IP
            // o CF-Connecting-IP sí se pueden creer. Vacío = usar solo REMOTE_ADDR.
            'proxies_confiables'  => array(),
        ),
        'sistema' => array(
            'zona_horaria'         => 'America/Bogota',
            'registros_por_pagina' => 25,
            'prefijo_codigo'       => 'MUSA',
            'efectos_3d'           => true,
        ),
    );
}

/**
 * Claves de API tomadas de wj-content/config/claves.php, si ese archivo existe.
 * Sirve para desplegar las claves sin escribirlas en el repositorio; solo se usan
 * en el primer arranque, porque después el panel es el dueño de la configuración.
 */
function musa_claves_locales() {
    $archivo = MUSA_DIR_CONFIG . '/claves.php';
    if (!file_exists($archivo)) { return array(); }
    $datos = include $archivo;
    return is_array($datos) ? $datos : array();
}

/** Devuelve los ajustes vigentes (predeterminados + guardados). */
function musa_ajustes($recargar = false) {
    static $ajustes = null;
    if ($ajustes !== null && !$recargar) { return $ajustes; }

    $predeterminados = musa_ajustes_predeterminados();
    if (!file_exists(MUSA_ARCHIVO_AJUSTES)) {
        // Primer arranque: se parte del archivo de ejemplo del repositorio
        // y, si existe, del archivo local de claves (que nunca se sube al repositorio).
        $ejemplo = musa_leer_json(MUSA_DIR_CONFIG . '/ajustes.ejemplo.json.php', array());
        $iniciales = $ejemplo !== array() ? musa_combinar($predeterminados, $ejemplo) : $predeterminados;
        $iniciales = musa_combinar($iniciales, musa_claves_locales());
        musa_escribir_json(MUSA_ARCHIVO_AJUSTES, $iniciales);
        $ajustes = $iniciales;
        return $ajustes;
    }
    $guardados = musa_leer_json(MUSA_ARCHIVO_AJUSTES, array());
    $ajustes = musa_combinar($predeterminados, $guardados);
    return $ajustes;
}

/** Guarda los ajustes en disco y refresca la caché en memoria. */
function musa_guardar_ajustes($ajustes) {
    $ok = musa_escribir_json(MUSA_ARCHIVO_AJUSTES, $ajustes);
    if ($ok) {
        $GLOBALS['musa_ajustes'] = musa_ajustes(true);
    }
    return $ok;
}

/** Géneros activos y ordenados. */
function musa_generos_activos($ajustes = null) {
    if ($ajustes === null) { $ajustes = musa_ajustes(); }
    $generos = musa_dato($ajustes, 'generos', array());
    $activos = array();
    foreach ($generos as $genero) {
        if (!empty($genero['activo'])) { $activos[] = $genero; }
    }
    usort($activos, function ($a, $b) {
        $oa = isset($a['orden']) ? (int) $a['orden'] : 0;
        $ob = isset($b['orden']) ? (int) $b['orden'] : 0;
        if ($oa === $ob) { return 0; }
        return ($oa < $ob) ? -1 : 1;
    });
    return $activos;
}

/** Busca un género por su identificador. */
function musa_genero($id, $ajustes = null) {
    if ($ajustes === null) { $ajustes = musa_ajustes(); }
    foreach (musa_dato($ajustes, 'generos', array()) as $genero) {
        if (isset($genero['id']) && $genero['id'] === $id) { return $genero; }
    }
    return null;
}
