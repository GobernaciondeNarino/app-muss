<?php
/**
 * Musa Café · API pública: guarda un registro nuevo.
 * Método: POST (JSON) · Respuesta: JSON
 */
require_once dirname(__DIR__) . '/arranque.php';

musa_cabeceras_seguridad();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    musa_responder_json(array('ok' => false, 'mensaje' => 'Método no permitido.'), 405);
}

$ajustes = musa_ajustes();
$datos = musa_cuerpo_json();
if ($datos === array()) { $datos = $_POST; }

musa_exigir_token(isset($datos['token']) ? $datos['token'] : '', true);

// Trampa anti-robots: campo oculto que una persona nunca llena.
if (!empty($datos['sitio_web'])) {
    musa_responder_json(array('ok' => false, 'mensaje' => 'No fue posible procesar el formulario.'), 400);
}

$errores = array();

$generoId = musa_texto(isset($datos['genero']) ? $datos['genero'] : '', 60);
$genero = musa_genero($generoId, $ajustes);
if ($genero === null || empty($genero['activo'])) {
    $errores['genero'] = 'Selecciona un género musical.';
}

$minimo = (int) musa_dato($ajustes, 'formulario.minimo_tema', 15);
$maximo = (int) musa_dato($ajustes, 'formulario.maximo_tema', 600);
$tema = musa_texto(isset($datos['tema']) ? $datos['tema'] : '', $maximo);
if (mb_strlen($tema, 'UTF-8') < $minimo) {
    $errores['tema'] = 'Cuéntanos un poco más de la historia (mínimo ' . $minimo . ' caracteres).';
}

$nombre = musa_texto(isset($datos['nombre']) ? $datos['nombre'] : '', 120);
if (mb_strlen($nombre, 'UTF-8') < 3) {
    $errores['nombre'] = 'Escribe tu nombre completo.';
}

$correo = musa_texto(isset($datos['correo']) ? $datos['correo'] : '', 160);
if (!musa_correo_valido($correo)) {
    $errores['correo'] = 'Escribe un correo electrónico válido.';
}

$telefono = musa_texto(isset($datos['telefono']) ? $datos['telefono'] : '', 40);
if (!empty(musa_dato($ajustes, 'formulario.telefono_obligatorio', false)) && $telefono === '') {
    $errores['telefono'] = 'Escribe un número de contacto.';
}
if ($telefono !== '' && !preg_match('/^[0-9+()\s\-]{7,25}$/', $telefono)) {
    $errores['telefono'] = 'El número de contacto no es válido.';
}

$ciudad = musa_texto(isset($datos['ciudad']) ? $datos['ciudad'] : '', 80);
if (!empty(musa_dato($ajustes, 'formulario.ciudad_obligatoria', false)) && $ciudad === '') {
    $errores['ciudad'] = 'Escribe tu municipio o ciudad.';
}

$dedicatoria = musa_texto(isset($datos['dedicatoria']) ? $datos['dedicatoria'] : '', 200);

$autoriza = !empty($datos['autorizacion']);
if (!empty(musa_dato($ajustes, 'seguridad.exigir_aceptacion', true)) && !$autoriza) {
    $errores['autorizacion'] = 'Debes autorizar el tratamiento de tus datos personales.';
}

if ($errores !== array()) {
    musa_responder_json(array('ok' => false, 'mensaje' => 'Revisa los datos del formulario.', 'errores' => $errores), 422);
}

$ip = musa_ip();
if (musa_limite_superado($correo, $ip)) {
    musa_responder_json(array(
        'ok' => false,
        'mensaje' => 'Ya registraste varias canciones en poco tiempo. Inténtalo de nuevo más tarde.',
    ), 429);
}

$registro = musa_registro_crear(array(
    'nombre'        => $nombre,
    'correo'        => $correo,
    'telefono'      => $telefono,
    'ciudad'        => $ciudad,
    'genero'        => $genero['id'],
    'genero_nombre' => $genero['nombre'],
    'tema'          => $tema,
    'dedicatoria'   => $dedicatoria,
    'autorizacion'  => $autoriza,
    'estado'        => 'pendiente',
    'ip'            => $ip,
    'navegador'     => musa_texto(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '', 255),
));

if ($registro === null) {
    musa_responder_json(array('ok' => false, 'mensaje' => 'No fue posible guardar el registro. Inténtalo de nuevo.'), 500);
}

musa_log('Registro creado', array('codigo' => $registro['codigo'], 'genero' => $registro['genero'], 'correo' => $registro['correo']));

$automatica = !empty(musa_dato($ajustes, 'ia.generacion_automatica', true)) && musa_ia_proveedor($ajustes) !== 'ninguno';

musa_responder_json(array(
    'ok'       => true,
    'codigo'   => $registro['codigo'],
    'clave'    => $registro['token'],
    'generar'  => $automatica,
    'mensaje'  => $automatica
        ? 'Registro guardado. Estamos componiendo tu canción.'
        : 'Registro guardado. Te avisaremos al correo cuando la canción esté lista.',
));
