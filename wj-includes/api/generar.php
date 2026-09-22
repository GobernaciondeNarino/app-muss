<?php
/**
 * Musa Café · API pública: genera la canción de un registro y la envía por correo.
 * Método: POST (JSON con codigo y clave) · Respuesta: JSON
 */
require_once dirname(__DIR__) . '/arranque.php';

musa_cabeceras_seguridad();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    musa_responder_json(array('ok' => false, 'mensaje' => 'Método no permitido.'), 405);
}

$datos = musa_cuerpo_json();
if ($datos === array()) { $datos = $_POST; }

musa_exigir_token(isset($datos['token']) ? $datos['token'] : '', true);

$codigo = musa_texto(isset($datos['codigo']) ? $datos['codigo'] : '', 60);
$clave  = musa_texto(isset($datos['clave']) ? $datos['clave'] : '', 80);

$registro = musa_registro_obtener($codigo);
if ($registro === null || $clave === '' || !hash_equals((string) $registro['token'], $clave)) {
    musa_responder_json(array('ok' => false, 'mensaje' => 'No encontramos ese registro.'), 404);
}

// La generación tarda: liberamos la sesión para no bloquear otras peticiones.
if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
@set_time_limit(600);
@ignore_user_abort(true);

$ajustes = musa_ajustes();

if (!empty($registro['creado']) && (string) $registro['estado'] === 'listo') {
    $resultado = array('ok' => true, 'mensaje' => 'La canción ya estaba lista.');
} else {
    $resultado = musa_ia_generar($registro['id'], $ajustes);
}

$registro = musa_registro_obtener($registro['id']);

$envio = array('ok' => false, 'mensaje' => 'Envío de correo desactivado.');
if (!empty($resultado['ok']) && !empty(musa_dato($ajustes, 'correo.activo', true)) && empty($registro['enviado'])) {
    $envio = musa_correo_enviar($registro, $ajustes);
    if ($envio['ok']) {
        $registro = musa_registro_actualizar($registro['id'], array(
            'enviado'       => true,
            'fecha_enviado' => date('Y-m-d H:i:s'),
        ));
    } else {
        musa_registro_actualizar($registro['id'], array('mensaje' => trim((string) $registro['mensaje'] . ' · Correo: ' . $envio['mensaje'], ' ·')));
    }
}

musa_responder_json(array(
    'ok'       => !empty($resultado['ok']),
    'mensaje'  => $resultado['mensaje'],
    'estado'   => (string) $registro['estado'],
    'creado'   => !empty($registro['creado']),
    'enviado'  => !empty($registro['enviado']),
    'titulo'   => (string) $registro['titulo_cancion'],
    'letra'    => (string) $registro['letra'],
    'audio'    => $registro['audio'] !== '' ? musa_url($registro['audio']) : '',
    'correo'   => $envio['mensaje'],
));
