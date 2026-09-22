<?php
/**
 * Musa Café · Acciones sobre los registros
 * Marcar casillas (AJAX), generar, enviar, eliminar, notas y exportar.
 */
require_once __DIR__ . '/comun.php';

$esAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

$datos = $esAjax ? musa_cuerpo_json() : $_POST;
if ($datos === array() && $_SERVER['REQUEST_METHOD'] === 'GET') { $datos = $_GET; }

$accion = musa_texto(isset($datos['accion']) ? $datos['accion'] : '', 30);

/* La exportación es una descarga por GET; el resto exige token CSRF. */
if ($accion === 'exportar') {
    $filtros = array(
        'busqueda' => musa_texto(isset($_GET['q']) ? $_GET['q'] : '', 80),
        'genero'   => musa_texto(isset($_GET['genero']) ? $_GET['genero'] : '', 60),
        'creado'   => in_array(isset($_GET['creado']) ? $_GET['creado'] : '', array('si', 'no'), true) ? $_GET['creado'] : '',
        'enviado'  => in_array(isset($_GET['enviado']) ? $_GET['enviado'] : '', array('si', 'no'), true) ? $_GET['enviado'] : '',
        'desde'    => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) (isset($_GET['desde']) ? $_GET['desde'] : '')) ? $_GET['desde'] : '',
        'hasta'    => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) (isset($_GET['hasta']) ? $_GET['hasta'] : '')) ? $_GET['hasta'] : '',
    );
    $csv = musa_registros_csv($filtros);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="musa-registros-' . date('Ymd-His') . '.csv"');
    header('Content-Length: ' . strlen($csv));
    echo $csv;
    exit;
}

musa_exigir_token(isset($datos['token']) ? $datos['token'] : '', $esAjax);

$id = musa_texto(isset($datos['id']) ? $datos['id'] : '', 60);
$registro = $id !== '' ? musa_registro_obtener($id) : null;

if ($registro === null) {
    if ($esAjax) { musa_responder_json(array('ok' => false, 'mensaje' => 'El registro no existe.'), 404); }
    musa_panel_mensaje('El registro no existe.', 'error');
    header('Location: index.php');
    exit;
}

switch ($accion) {

    case 'marcar':
        $campo = musa_texto(isset($datos['campo']) ? $datos['campo'] : '', 20);
        if (!in_array($campo, array('creado', 'enviado'), true)) {
            musa_responder_json(array('ok' => false, 'mensaje' => 'Campo no permitido.'), 400);
        }
        $valor = !empty($datos['valor']);
        $cambios = array($campo => $valor);
        $cambios[$campo === 'creado' ? 'fecha_creado' : 'fecha_enviado'] = $valor ? date('Y-m-d H:i:s') : '';
        if ($campo === 'creado' && $valor && (string) $registro['estado'] === 'pendiente') {
            $cambios['estado'] = 'listo';
        }
        $actualizado = musa_registro_actualizar($registro['id'], $cambios);
        musa_log('Casilla actualizada', array('codigo' => $registro['codigo'], 'campo' => $campo, 'valor' => $valor ? 'SI' : 'NO'));
        musa_responder_json(array(
            'ok' => $actualizado !== null,
            'valor' => $valor,
            'estado' => $actualizado !== null ? $actualizado['estado'] : '',
            'mensaje' => ($campo === 'creado' ? 'Creado' : 'Enviado') . ': ' . ($valor ? 'SÍ' : 'NO'),
        ));
        break;

    case 'generar':
        @set_time_limit(600);
        $resultado = musa_ia_generar($registro['id'], $ajustesPanel);
        musa_panel_mensaje(
            ($resultado['ok'] ? 'Canción generada para ' . $registro['codigo'] . '. ' : 'No fue posible generar: ') . $resultado['mensaje'],
            $resultado['ok'] ? 'exito' : 'error'
        );
        break;

    case 'enviar':
        $actual = musa_registro_obtener($registro['id']);
        $envio = musa_correo_enviar($actual, $ajustesPanel);
        if ($envio['ok']) {
            musa_registro_actualizar($actual['id'], array('enviado' => true, 'fecha_enviado' => date('Y-m-d H:i:s')));
        }
        musa_panel_mensaje(
            ($envio['ok'] ? 'Correo enviado a ' . $actual['correo'] . '. ' : 'No fue posible enviar el correo: ') . $envio['mensaje'],
            $envio['ok'] ? 'exito' : 'error'
        );
        break;

    case 'nota':
        musa_registro_actualizar($registro['id'], array('notas' => musa_texto(isset($datos['notas']) ? $datos['notas'] : '', 1000)));
        musa_panel_mensaje('Nota guardada para ' . $registro['codigo'] . '.');
        break;

    case 'eliminar':
        musa_registro_eliminar($registro['id']);
        musa_log('Registro eliminado', array('codigo' => $registro['codigo'], 'usuario' => $usuarioActual));
        musa_panel_mensaje('Registro ' . $registro['codigo'] . ' eliminado.');
        break;

    default:
        musa_panel_mensaje('Acción no reconocida.', 'error');
}

$destino = 'index.php';
if (!empty($_SERVER['HTTP_REFERER'])) {
    $partes = parse_url($_SERVER['HTTP_REFERER']);
    if (isset($partes['path']) && strpos($partes['path'], '/wj-admin/') !== false) {
        $destino = basename($partes['path']) . (isset($partes['query']) ? '?' . $partes['query'] : '');
    }
}
header('Location: ' . $destino);
exit;
