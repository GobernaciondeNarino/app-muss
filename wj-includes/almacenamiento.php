<?php
/**
 * Musa Café · Almacenamiento de registros en JSON
 * Archivo: wj-content/datos/registros.json
 */
if (!defined('MUSA_ARRANQUE')) { http_response_code(403); exit('Acceso directo no permitido.'); }

/** Estructura vacía del archivo de registros. */
function musa_registros_vacio() {
    return array('version' => 1, 'secuencia' => 0, 'actualizado' => date('c'), 'registros' => array());
}

/** Carga todos los registros. */
function musa_registros_cargar() {
    $datos = musa_leer_json(MUSA_ARCHIVO_REGISTROS, musa_registros_vacio());
    if (!isset($datos['registros']) || !is_array($datos['registros'])) { $datos['registros'] = array(); }
    if (!isset($datos['secuencia'])) { $datos['secuencia'] = count($datos['registros']); }
    return $datos;
}

/** Guarda el archivo completo de registros. */
function musa_registros_guardar($datos) {
    $datos['actualizado'] = date('c');
    return musa_escribir_json(MUSA_ARCHIVO_REGISTROS, $datos);
}

/**
 * Ejecuta una operación sobre los registros con bloqueo exclusivo,
 * de modo que dos visitantes simultáneos no se pisen los datos.
 * La función recibe el arreglo completo y debe devolverlo modificado.
 */
function musa_registros_transaccion($operacion) {
    if (!is_dir(MUSA_DIR_DATOS)) { @mkdir(MUSA_DIR_DATOS, 0775, true); }
    $candado = MUSA_DIR_DATOS . '/registros.lock';
    $puntero = @fopen($candado, 'c+');
    if ($puntero === false) {
        $datos = musa_registros_cargar();
        $resultado = $operacion($datos);
        musa_registros_guardar(isset($resultado['datos']) ? $resultado['datos'] : $datos);
        return isset($resultado['retorno']) ? $resultado['retorno'] : null;
    }
    @flock($puntero, LOCK_EX);
    $datos = musa_registros_cargar();
    $resultado = $operacion($datos);
    if (isset($resultado['datos'])) { musa_registros_guardar($resultado['datos']); }
    @flock($puntero, LOCK_UN);
    @fclose($puntero);
    return isset($resultado['retorno']) ? $resultado['retorno'] : null;
}

/** Campos y valores por defecto de un registro. */
function musa_registro_base() {
    return array(
        'id'            => '',
        'codigo'        => '',
        'fecha'         => '',
        'nombre'        => '',
        'correo'        => '',
        'telefono'      => '',
        'ciudad'        => '',
        'genero'        => '',
        'genero_nombre' => '',
        'tema'          => '',
        'dedicatoria'   => '',
        'creado'        => false,
        'enviado'       => false,
        'estado'        => 'pendiente',
        'proveedor'     => '',
        'letra'         => '',
        'audio'         => '',
        'titulo_cancion'=> '',
        'mensaje'       => '',
        'fecha_creado'  => '',
        'fecha_enviado' => '',
        'notas'         => '',
        'autorizacion'  => false,
        'ip'            => '',
        'navegador'     => '',
        'token'         => '',
        'actualizado'   => '',
    );
}

/** Crea un registro nuevo y devuelve el registro completo. */
function musa_registro_crear($campos) {
    $ajustes = musa_ajustes();
    $prefijo = musa_dato($ajustes, 'sistema.prefijo_codigo', 'MUSA');

    return musa_registros_transaccion(function ($datos) use ($campos, $prefijo) {
        $secuencia = (int) $datos['secuencia'] + 1;
        $registro = array_merge(musa_registro_base(), $campos);
        $registro['id']          = uniqid('reg', true);
        $registro['codigo']      = $prefijo . '-' . date('Ymd') . '-' . str_pad((string) $secuencia, 4, '0', STR_PAD_LEFT);
        $registro['fecha']       = date('Y-m-d H:i:s');
        $registro['actualizado'] = date('Y-m-d H:i:s');
        $registro['token']       = bin2hex(random_bytes(16));

        $datos['secuencia'] = $secuencia;
        array_unshift($datos['registros'], $registro);

        return array('datos' => $datos, 'retorno' => $registro);
    });
}

/** Obtiene un registro por id o por código. */
function musa_registro_obtener($identificador) {
    $datos = musa_registros_cargar();
    foreach ($datos['registros'] as $registro) {
        if ((isset($registro['id']) && $registro['id'] === $identificador)
            || (isset($registro['codigo']) && $registro['codigo'] === $identificador)) {
            return $registro;
        }
    }
    return null;
}

/** Actualiza campos de un registro. Devuelve el registro actualizado o null. */
function musa_registro_actualizar($identificador, $cambios) {
    return musa_registros_transaccion(function ($datos) use ($identificador, $cambios) {
        $actualizado = null;
        foreach ($datos['registros'] as $indice => $registro) {
            $coincide = (isset($registro['id']) && $registro['id'] === $identificador)
                || (isset($registro['codigo']) && $registro['codigo'] === $identificador);
            if (!$coincide) { continue; }
            $registro = array_merge(musa_registro_base(), $registro, $cambios);
            $registro['actualizado'] = date('Y-m-d H:i:s');
            $datos['registros'][$indice] = $registro;
            $actualizado = $registro;
            break;
        }
        return array('datos' => $datos, 'retorno' => $actualizado);
    });
}

/** Elimina un registro (y su audio asociado). */
function musa_registro_eliminar($identificador) {
    return musa_registros_transaccion(function ($datos) use ($identificador) {
        $eliminado = false;
        foreach ($datos['registros'] as $indice => $registro) {
            $coincide = (isset($registro['id']) && $registro['id'] === $identificador)
                || (isset($registro['codigo']) && $registro['codigo'] === $identificador);
            if (!$coincide) { continue; }
            if (!empty($registro['audio'])) {
                $archivo = MUSA_RAIZ . '/' . ltrim($registro['audio'], '/');
                if (strpos(str_replace('\\', '/', (string) realpath($archivo)), str_replace('\\', '/', (string) realpath(MUSA_DIR_AUDIO))) === 0) {
                    @unlink($archivo);
                }
            }
            array_splice($datos['registros'], $indice, 1);
            $eliminado = true;
            break;
        }
        return array('datos' => $datos, 'retorno' => $eliminado);
    });
}

/**
 * Filtra, ordena y pagina los registros para el panel.
 * Filtros: busqueda, genero, creado ('si'|'no'|''), enviado, desde, hasta.
 */
function musa_registros_filtrar($filtros = array(), $pagina = 1, $porPagina = 25) {
    $datos = musa_registros_cargar();
    $lista = $datos['registros'];

    $busqueda = isset($filtros['busqueda']) ? mb_strtolower(trim((string) $filtros['busqueda']), 'UTF-8') : '';
    $genero   = isset($filtros['genero']) ? (string) $filtros['genero'] : '';
    $creado   = isset($filtros['creado']) ? (string) $filtros['creado'] : '';
    $enviado  = isset($filtros['enviado']) ? (string) $filtros['enviado'] : '';
    $desde    = isset($filtros['desde']) ? (string) $filtros['desde'] : '';
    $hasta    = isset($filtros['hasta']) ? (string) $filtros['hasta'] : '';

    $filtrados = array();
    foreach ($lista as $registro) {
        if ($busqueda !== '') {
            $heno = mb_strtolower(
                (string) ($registro['nombre'] ?? '') . ' ' . (string) ($registro['correo'] ?? '') . ' ' .
                (string) ($registro['codigo'] ?? '') . ' ' . (string) ($registro['tema'] ?? '') . ' ' .
                (string) ($registro['ciudad'] ?? '') . ' ' . (string) ($registro['telefono'] ?? ''),
                'UTF-8'
            );
            if (strpos($heno, $busqueda) === false) { continue; }
        }
        if ($genero !== '' && (string) ($registro['genero'] ?? '') !== $genero) { continue; }
        if ($creado === 'si' && empty($registro['creado'])) { continue; }
        if ($creado === 'no' && !empty($registro['creado'])) { continue; }
        if ($enviado === 'si' && empty($registro['enviado'])) { continue; }
        if ($enviado === 'no' && !empty($registro['enviado'])) { continue; }
        $fecha = substr((string) ($registro['fecha'] ?? ''), 0, 10);
        if ($desde !== '' && $fecha < $desde) { continue; }
        if ($hasta !== '' && $fecha > $hasta) { continue; }
        $filtrados[] = $registro;
    }

    $total = count($filtrados);
    $porPagina = max(5, (int) $porPagina);
    $paginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min(max(1, (int) $pagina), $paginas);
    $pagina_items = array_slice($filtrados, ($pagina - 1) * $porPagina, $porPagina);

    return array(
        'registros' => $pagina_items,
        'total'     => $total,
        'pagina'    => $pagina,
        'paginas'   => $paginas,
        'porPagina' => $porPagina,
    );
}

/** Totales para el tablero del panel. */
function musa_registros_resumen() {
    $datos = musa_registros_cargar();
    $resumen = array('total' => 0, 'creados' => 0, 'pendientes_creacion' => 0, 'enviados' => 0, 'pendientes_envio' => 0, 'hoy' => 0, 'errores' => 0, 'generos' => array());
    $hoy = date('Y-m-d');
    foreach ($datos['registros'] as $registro) {
        $resumen['total']++;
        if (!empty($registro['creado'])) { $resumen['creados']++; } else { $resumen['pendientes_creacion']++; }
        if (!empty($registro['enviado'])) { $resumen['enviados']++; } else { $resumen['pendientes_envio']++; }
        if (substr((string) ($registro['fecha'] ?? ''), 0, 10) === $hoy) { $resumen['hoy']++; }
        if (($registro['estado'] ?? '') === 'error') { $resumen['errores']++; }
        $g = (string) ($registro['genero_nombre'] ?? $registro['genero'] ?? 'Sin género');
        if ($g === '') { $g = 'Sin género'; }
        $resumen['generos'][$g] = (isset($resumen['generos'][$g]) ? $resumen['generos'][$g] : 0) + 1;
    }
    arsort($resumen['generos']);
    return $resumen;
}

/** Cuenta los registros de un correo o IP en las últimas N horas. */
function musa_registros_recientes($correo, $ip, $horas = 1) {
    $limite = time() - ($horas * 3600);
    $datos = musa_registros_cargar();
    $conteo = 0;
    foreach ($datos['registros'] as $registro) {
        $marca = strtotime((string) ($registro['fecha'] ?? ''));
        if ($marca === false || $marca < $limite) { continue; }
        if (($correo !== '' && strcasecmp((string) ($registro['correo'] ?? ''), $correo) === 0)
            || ($ip !== '' && (string) ($registro['ip'] ?? '') === $ip)) {
            $conteo++;
        }
    }
    return $conteo;
}

/** Exporta los registros filtrados a CSV (separador ; para Excel en español). */
function musa_registros_csv($filtros = array()) {
    $resultado = musa_registros_filtrar($filtros, 1, PHP_INT_MAX);
    $columnas = array('codigo' => 'Código', 'fecha' => 'Fecha', 'nombre' => 'Nombre', 'correo' => 'Correo',
        'telefono' => 'Teléfono', 'ciudad' => 'Ciudad', 'genero_nombre' => 'Género', 'tema' => 'Historia',
        'dedicatoria' => 'Dedicatoria', 'creado' => 'Creado', 'enviado' => 'Enviado', 'estado' => 'Estado',
        'proveedor' => 'Proveedor', 'titulo_cancion' => 'Título', 'audio' => 'Audio', 'fecha_creado' => 'Fecha creación',
        'fecha_enviado' => 'Fecha envío', 'notas' => 'Notas', 'autorizacion' => 'Autoriza datos', 'ip' => 'IP');

    $lineas = array();
    $lineas[] = implode(';', array_values($columnas));
    foreach ($resultado['registros'] as $registro) {
        $fila = array();
        foreach (array_keys($columnas) as $clave) {
            $valor = isset($registro[$clave]) ? $registro[$clave] : '';
            if (is_bool($valor)) { $valor = $valor ? 'SI' : 'NO'; }
            $valor = str_replace(array("\r", "\n"), ' ', (string) $valor);
            // La hoja de cálculo interpreta como fórmula lo que empieza por = + - @ o tabulación.
            // Como estos datos los escribe el público, anteponemos un apóstrofo.
            if ($valor !== '' && strpos("=+-@\t", $valor[0]) !== false) { $valor = "'" . $valor; }
            $valor = '"' . str_replace('"', '""', $valor) . '"';
            $fila[] = $valor;
        }
        $lineas[] = implode(';', $fila);
    }
    return "\xEF\xBB\xBF" . implode("\r\n", $lineas);
}
