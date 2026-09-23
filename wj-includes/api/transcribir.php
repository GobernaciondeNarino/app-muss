<?php
/**
 * Musa Café · API pública: convierte un audio dictado en texto.
 * Método: POST (multipart con el campo "audio") · Respuesta: JSON
 */
require_once dirname(__DIR__) . '/arranque.php';

musa_cabeceras_seguridad();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    musa_responder_json(array('ok' => false, 'mensaje' => 'Método no permitido.'), 405);
}

musa_exigir_token(isset($_POST['token']) ? $_POST['token'] : '', true);

$ajustes = musa_ajustes();

if (!musa_transcripcion_servidor_disponible($ajustes)) {
    musa_responder_json(array('ok' => false, 'mensaje' => 'La transcripción en el servidor está desactivada.'), 503);
}

if (!isset($_FILES['audio']) || (int) $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    musa_responder_json(array('ok' => false, 'mensaje' => 'No llegó el audio. Inténtalo de nuevo.'), 400);
}

$maximoMb = 10;
if ((int) $_FILES['audio']['size'] > $maximoMb * 1024 * 1024) {
    musa_responder_json(array('ok' => false, 'mensaje' => 'La grabación es muy larga. Habla máximo ' . (int) musa_dato($ajustes, 'ia.transcripcion.maximo_segundos', 120) . ' segundos.'), 413);
}

$temporal = $_FILES['audio']['tmp_name'];
if (!is_uploaded_file($temporal)) {
    musa_responder_json(array('ok' => false, 'mensaje' => 'El archivo recibido no es válido.'), 400);
}

$contenido = (string) file_get_contents($temporal);
@unlink($temporal);

$tipo = musa_tipo_audio($contenido);
if ($tipo === '') {
    musa_responder_json(array('ok' => false, 'mensaje' => 'El formato del audio no es compatible.'), 415);
}

$ip = musa_ip();
$limite = (int) musa_dato($ajustes, 'ia.transcripcion.limite_por_hora', 30);
if (musa_limite_uso('transcripcion', $ip, $limite)) {
    musa_responder_json(array('ok' => false, 'mensaje' => 'Has usado el dictado muchas veces seguidas. Espera unos minutos o escribe el texto.'), 429);
}

if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
@set_time_limit(180);

$resultado = musa_ia_transcribir($contenido, $tipo, $ajustes);

musa_log($resultado['ok'] ? 'Dictado transcrito' : 'Fallo al transcribir', array(
    'tipo'   => $tipo,
    'peso'   => musa_peso(strlen($contenido)),
    'motor'  => isset($resultado['motor']) ? $resultado['motor'] : '',
    'detalle'=> $resultado['ok'] ? '' : $resultado['mensaje'],
));

if (empty($resultado['ok'])) {
    musa_responder_json(array('ok' => false, 'mensaje' => $resultado['mensaje']), 502);
}

musa_responder_json(array(
    'ok'    => true,
    'texto' => musa_texto($resultado['texto'], (int) musa_dato($ajustes, 'formulario.maximo_tema', 600)),
    'motor' => $resultado['motor'],
));
