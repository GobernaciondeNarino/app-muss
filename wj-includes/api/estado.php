<?php
/**
 * Musa Café · API pública: consulta el estado de una canción.
 * Método: GET (codigo y clave) · Respuesta: JSON
 */
require_once dirname(__DIR__) . '/arranque.php';

musa_cabeceras_seguridad();

$codigo = musa_texto(isset($_GET['codigo']) ? $_GET['codigo'] : '', 60);
$clave  = musa_texto(isset($_GET['clave']) ? $_GET['clave'] : '', 80);

$registro = musa_registro_obtener($codigo);
if ($registro === null || $clave === '' || !hash_equals((string) $registro['token'], $clave)) {
    musa_responder_json(array('ok' => false, 'mensaje' => 'No encontramos ese registro.'), 404);
}

musa_responder_json(array(
    'ok'      => true,
    'estado'  => (string) $registro['estado'],
    'creado'  => !empty($registro['creado']),
    'enviado' => !empty($registro['enviado']),
    'titulo'  => (string) $registro['titulo_cancion'],
    'letra'   => (string) $registro['letra'],
    'audio'   => $registro['audio'] !== '' ? musa_url($registro['audio']) : '',
    'mensaje' => (string) $registro['mensaje'],
));
