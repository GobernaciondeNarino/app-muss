<?php
/**
 * Musa Café · Apariencia, textos y géneros musicales
 * Todo lo que se ve en la interfaz pública se configura aquí.
 */
require_once __DIR__ . '/comun.php';

/** Sube una imagen al directorio wj-content/subidas y devuelve su ruta relativa. */
function musa_subir_imagen($campo, $indice = null) {
    if (!isset($_FILES[$campo])) { return ''; }
    $archivo = $_FILES[$campo];
    if ($indice !== null) {
        if (!isset($archivo['name'][$indice])) { return ''; }
        $archivo = array(
            'name'     => $archivo['name'][$indice],
            'type'     => $archivo['type'][$indice],
            'tmp_name' => $archivo['tmp_name'][$indice],
            'error'    => $archivo['error'][$indice],
            'size'     => $archivo['size'][$indice],
        );
    }
    if ((int) $archivo['error'] === UPLOAD_ERR_NO_FILE) { return ''; }
    if ((int) $archivo['error'] !== UPLOAD_ERR_OK) { return ''; }
    if ((int) $archivo['size'] > 5 * 1024 * 1024) { return ''; }

    $informacion = @getimagesize($archivo['tmp_name']);
    if ($informacion === false) { return ''; }
    $permitidos = array(IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp');
    if (!isset($permitidos[$informacion[2]])) { return ''; }

    if (!is_dir(MUSA_DIR_SUBIDAS)) { @mkdir(MUSA_DIR_SUBIDAS, 0775, true); }
    $nombre = musa_slug(pathinfo((string) $archivo['name'], PATHINFO_FILENAME));
    if ($nombre === '') { $nombre = 'imagen'; }
    $nombre = substr($nombre, 0, 40) . '-' . bin2hex(random_bytes(3)) . '.' . $permitidos[$informacion[2]];
    $destino = MUSA_DIR_SUBIDAS . '/' . $nombre;
    if (!@move_uploaded_file($archivo['tmp_name'], $destino)) { return ''; }
    @chmod($destino, 0664);
    return 'wj-content/subidas/' . $nombre;
}

/** Imágenes disponibles para elegir en los selectores. */
function musa_imagenes_disponibles() {
    $lista = array();
    foreach (array('wj-includes/images', 'wj-includes/images/optimizadas', 'wj-content/subidas') as $carpeta) {
        $ruta = MUSA_RAIZ . '/' . $carpeta;
        if (!is_dir($ruta)) { continue; }
        foreach ((array) scandir($ruta) as $archivo) {
            if ($archivo === '.' || $archivo === '..') { continue; }
            if (!preg_match('/\.(png|jpe?g|gif|webp)$/i', $archivo)) { continue; }
            $lista[] = $carpeta . '/' . $archivo;
        }
    }
    sort($lista);
    return $lista;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    musa_exigir_token(isset($_POST['token']) ? $_POST['token'] : '');
    $nuevos = $ajustesPanel;

    foreach (array('nombre', 'eslogan', 'entidad', 'titulo_sitio', 'descripcion', 'sitio_entidad') as $campo) {
        musa_fijar($nuevos, 'marca.' . $campo, musa_texto(isset($_POST['marca'][$campo]) ? $_POST['marca'][$campo] : '', 200));
    }
    foreach (array('logo', 'fondo', 'barra', 'favicon') as $campo) {
        $subida = musa_subir_imagen('archivo_' . $campo);
        $ruta = $subida !== '' ? $subida : musa_texto(isset($_POST['marca'][$campo]) ? $_POST['marca'][$campo] : '', 200);
        if ($ruta === '' || musa_ruta_imagen_valida($ruta)) {
            musa_fijar($nuevos, 'marca.' . $campo, $ruta);
        }
    }

    foreach ((array) musa_dato($nuevos, 'colores', array()) as $clave => $valorActual) {
        $color = musa_color(isset($_POST['colores'][$clave]) ? $_POST['colores'][$clave] : '', null);
        if ($color !== null) { musa_fijar($nuevos, 'colores.' . $clave, $color); }
    }

    foreach ((array) musa_dato($nuevos, 'textos', array()) as $clave => $valorActual) {
        if (isset($_POST['textos'][$clave])) {
            musa_fijar($nuevos, 'textos.' . $clave, musa_texto($_POST['textos'][$clave], 400));
        }
    }

    musa_fijar($nuevos, 'formulario.pedir_telefono', !empty($_POST['formulario']['pedir_telefono']));
    musa_fijar($nuevos, 'formulario.pedir_ciudad', !empty($_POST['formulario']['pedir_ciudad']));
    musa_fijar($nuevos, 'formulario.pedir_dedicatoria', !empty($_POST['formulario']['pedir_dedicatoria']));
    musa_fijar($nuevos, 'formulario.telefono_obligatorio', !empty($_POST['formulario']['telefono_obligatorio']));
    musa_fijar($nuevos, 'formulario.ciudad_obligatoria', !empty($_POST['formulario']['ciudad_obligatoria']));
    musa_fijar($nuevos, 'formulario.minimo_tema', max(5, min(300, (int) ($_POST['formulario']['minimo_tema'] ?? 15))));
    musa_fijar($nuevos, 'formulario.maximo_tema', max(100, min(2000, (int) ($_POST['formulario']['maximo_tema'] ?? 600))));

    musa_fijar($nuevos, 'sistema.efectos_3d', !empty($_POST['sistema']['efectos_3d']));
    musa_fijar($nuevos, 'sistema.registros_por_pagina', max(5, min(200, (int) ($_POST['sistema']['registros_por_pagina'] ?? 25))));
    musa_fijar($nuevos, 'sistema.prefijo_codigo', strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', (string) ($_POST['sistema']['prefijo_codigo'] ?? 'MUSA'))));
    $zona = musa_texto($_POST['sistema']['zona_horaria'] ?? 'America/Bogota', 60);
    if (in_array($zona, timezone_identifiers_list(), true)) { musa_fijar($nuevos, 'sistema.zona_horaria', $zona); }
    musa_fijar($nuevos, 'seguridad.limite_por_hora', max(0, min(100, (int) ($_POST['seguridad']['limite_por_hora'] ?? 5))));
    musa_fijar($nuevos, 'seguridad.limite_por_dia', max(0, min(500, (int) ($_POST['seguridad']['limite_por_dia'] ?? 20))));
    musa_fijar($nuevos, 'seguridad.exigir_aceptacion', !empty($_POST['seguridad']['exigir_aceptacion']));

    $generos = array();
    $entrada = isset($_POST['generos']) && is_array($_POST['generos']) ? $_POST['generos'] : array();
    $indice = 0;
    foreach ($entrada as $posicion => $fila) {
        $nombre = musa_texto(isset($fila['nombre']) ? $fila['nombre'] : '', 60);
        if ($nombre === '') { continue; }
        $id = musa_slug(isset($fila['id']) && $fila['id'] !== '' ? $fila['id'] : $nombre);
        if ($id === '') { $id = 'genero-' . ($indice + 1); }
        $subida = musa_subir_imagen('archivo_genero', $posicion);
        $imagen = $subida !== '' ? $subida : musa_texto(isset($fila['imagen']) ? $fila['imagen'] : '', 200);
        if ($imagen !== '' && !musa_ruta_imagen_valida($imagen)) { $imagen = ''; }
        $generos[] = array(
            'id'          => $id,
            'nombre'      => $nombre,
            'descripcion' => musa_texto(isset($fila['descripcion']) ? $fila['descripcion'] : '', 80),
            'imagen'      => $imagen,
            'prompt'      => musa_texto(isset($fila['prompt']) ? $fila['prompt'] : '', 400),
            'activo'      => !empty($fila['activo']),
            'orden'       => ++$indice,
        );
    }
    if ($generos !== array()) { musa_fijar($nuevos, 'generos', $generos); }

    musa_guardar_ajustes($nuevos);
    musa_log('Ajustes de apariencia guardados', array('usuario' => $usuarioActual));
    musa_panel_mensaje('Los cambios se guardaron y ya están visibles en la página pública.');
    header('Location: ajustes.php');
    exit;
}

$imagenes = musa_imagenes_disponibles();
$colores = musa_dato($ajustesPanel, 'colores', array());
$textos = musa_dato($ajustesPanel, 'textos', array());
$generos = musa_dato($ajustesPanel, 'generos', array());

$etiquetasColor = array(
    'fondo' => 'Fondo principal', 'fondo_profundo' => 'Fondo profundo', 'tarjeta' => 'Tarjeta',
    'tarjeta_borde' => 'Borde de tarjeta', 'tarjeta_activa' => 'Tarjeta seleccionada', 'texto' => 'Texto',
    'texto_suave' => 'Texto secundario', 'texto_activo' => 'Texto sobre selección', 'acento' => 'Acento (botones)',
    'acento_secundario' => 'Acento secundario', 'exito' => 'Éxito', 'error' => 'Error',
);

musa_panel_inicio('Apariencia y géneros', 'ajustes');
musa_panel_mensaje();
?>

<form method="post" action="ajustes.php" enctype="multipart/form-data" class="formulario-panel">
<?php musa_campo_token(); ?>

<section class="bloque-panel">
  <h2>Identidad</h2>
  <div class="rejilla">
    <label>Nombre de la marca<input type="text" name="marca[nombre]" value="<?php echo musa_e(musa_dato($ajustesPanel, 'marca.nombre', '')); ?>"></label>
    <label>Eslogan<input type="text" name="marca[eslogan]" value="<?php echo musa_e(musa_dato($ajustesPanel, 'marca.eslogan', '')); ?>"></label>
    <label>Entidad<input type="text" name="marca[entidad]" value="<?php echo musa_e(musa_dato($ajustesPanel, 'marca.entidad', '')); ?>"></label>
    <label>Título del sitio (pestaña)<input type="text" name="marca[titulo_sitio]" value="<?php echo musa_e(musa_dato($ajustesPanel, 'marca.titulo_sitio', '')); ?>"></label>
    <label>Sitio de la entidad<input type="text" name="marca[sitio_entidad]" value="<?php echo musa_e(musa_dato($ajustesPanel, 'marca.sitio_entidad', '')); ?>"></label>
    <label class="ancho-total">Descripción (SEO)<input type="text" name="marca[descripcion]" value="<?php echo musa_e(musa_dato($ajustesPanel, 'marca.descripcion', '')); ?>"></label>
  </div>
</section>

<section class="bloque-panel">
  <h2>Imágenes y logos</h2>
  <div class="rejilla">
    <?php foreach (array('logo' => 'Logo', 'fondo' => 'Rama decorativa (esquina superior)', 'barra' => 'Barra lateral', 'favicon' => 'Favicon') as $campo => $etiqueta) :
        $valor = (string) musa_dato($ajustesPanel, 'marca.' . $campo, ''); ?>
      <div class="campo-imagen">
        <strong><?php echo musa_e($etiqueta); ?></strong>
        <?php if ($valor !== '' && musa_ruta_imagen_valida($valor)) : ?>
          <img src="<?php echo musa_e(musa_url($valor)); ?>" alt="" class="vista-previa">
        <?php endif; ?>
        <select name="marca[<?php echo musa_e($campo); ?>]">
          <option value="">— sin imagen —</option>
          <?php foreach ($imagenes as $imagen) : ?>
            <option value="<?php echo musa_e($imagen); ?>" <?php echo $valor === $imagen ? 'selected' : ''; ?>><?php echo musa_e($imagen); ?></option>
          <?php endforeach; ?>
        </select>
        <input type="file" name="archivo_<?php echo musa_e($campo); ?>" accept="image/png,image/jpeg,image/gif,image/webp">
      </div>
    <?php endforeach; ?>
  </div>
  <p class="nota">Las imágenes que subas se guardan en <code>wj-content/subidas</code>. Tamaño máximo: 5 MB.</p>
</section>

<section class="bloque-panel">
  <h2>Colores</h2>
  <div class="rejilla colores">
    <?php foreach ($etiquetasColor as $clave => $etiqueta) :
        $valor = musa_color(musa_dato($colores, $clave, ''), '#000000'); ?>
      <label class="color">
        <span><?php echo musa_e($etiqueta); ?></span>
        <input type="color" name="colores[<?php echo musa_e($clave); ?>]" value="<?php echo musa_e($valor); ?>">
        <code><?php echo musa_e($valor); ?></code>
      </label>
    <?php endforeach; ?>
  </div>
</section>

<section class="bloque-panel">
  <h2>Textos de la interfaz</h2>
  <div class="rejilla">
    <?php foreach ($textos as $clave => $valor) : ?>
      <label class="<?php echo strlen((string) $valor) > 60 ? 'ancho-total' : ''; ?>">
        <?php echo musa_e(ucfirst(str_replace('_', ' ', $clave))); ?>
        <?php if (strlen((string) $valor) > 60) : ?>
          <textarea name="textos[<?php echo musa_e($clave); ?>]" rows="2"><?php echo musa_e($valor); ?></textarea>
        <?php else : ?>
          <input type="text" name="textos[<?php echo musa_e($clave); ?>]" value="<?php echo musa_e($valor); ?>">
        <?php endif; ?>
      </label>
    <?php endforeach; ?>
  </div>
</section>

<section class="bloque-panel">
  <h2>Formulario y sistema</h2>
  <div class="rejilla">
    <label class="interruptor"><input type="checkbox" name="formulario[pedir_telefono]" <?php echo !empty(musa_dato($ajustesPanel, 'formulario.pedir_telefono', true)) ? 'checked' : ''; ?>> Pedir teléfono</label>
    <label class="interruptor"><input type="checkbox" name="formulario[telefono_obligatorio]" <?php echo !empty(musa_dato($ajustesPanel, 'formulario.telefono_obligatorio', false)) ? 'checked' : ''; ?>> Teléfono obligatorio</label>
    <label class="interruptor"><input type="checkbox" name="formulario[pedir_ciudad]" <?php echo !empty(musa_dato($ajustesPanel, 'formulario.pedir_ciudad', true)) ? 'checked' : ''; ?>> Pedir municipio</label>
    <label class="interruptor"><input type="checkbox" name="formulario[ciudad_obligatoria]" <?php echo !empty(musa_dato($ajustesPanel, 'formulario.ciudad_obligatoria', false)) ? 'checked' : ''; ?>> Municipio obligatorio</label>
    <label class="interruptor"><input type="checkbox" name="formulario[pedir_dedicatoria]" <?php echo !empty(musa_dato($ajustesPanel, 'formulario.pedir_dedicatoria', true)) ? 'checked' : ''; ?>> Pedir dedicatoria</label>
    <label class="interruptor"><input type="checkbox" name="sistema[efectos_3d]" <?php echo !empty(musa_dato($ajustesPanel, 'sistema.efectos_3d', true)) ? 'checked' : ''; ?>> Efectos 3D (three.js)</label>
    <label class="interruptor"><input type="checkbox" name="seguridad[exigir_aceptacion]" <?php echo !empty(musa_dato($ajustesPanel, 'seguridad.exigir_aceptacion', true)) ? 'checked' : ''; ?>> Exigir autorización de datos</label>
    <label>Mínimo de caracteres de la historia<input type="number" name="formulario[minimo_tema]" min="5" max="300" value="<?php echo (int) musa_dato($ajustesPanel, 'formulario.minimo_tema', 15); ?>"></label>
    <label>Máximo de caracteres de la historia<input type="number" name="formulario[maximo_tema]" min="100" max="2000" value="<?php echo (int) musa_dato($ajustesPanel, 'formulario.maximo_tema', 600); ?>"></label>
    <label>Registros por página<input type="number" name="sistema[registros_por_pagina]" min="5" max="200" value="<?php echo (int) musa_dato($ajustesPanel, 'sistema.registros_por_pagina', 25); ?>"></label>
    <label>Prefijo del código<input type="text" name="sistema[prefijo_codigo]" maxlength="10" value="<?php echo musa_e(musa_dato($ajustesPanel, 'sistema.prefijo_codigo', 'MUSA')); ?>"></label>
    <label>Zona horaria
      <select name="sistema[zona_horaria]">
        <?php foreach (timezone_identifiers_list() as $zona) : ?>
          <option value="<?php echo musa_e($zona); ?>" <?php echo musa_dato($ajustesPanel, 'sistema.zona_horaria', '') === $zona ? 'selected' : ''; ?>><?php echo musa_e($zona); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Máximo de envíos por hora (por correo o IP)<input type="number" name="seguridad[limite_por_hora]" min="0" max="100" value="<?php echo (int) musa_dato($ajustesPanel, 'seguridad.limite_por_hora', 5); ?>"></label>
    <label>Máximo de envíos por día<input type="number" name="seguridad[limite_por_dia]" min="0" max="500" value="<?php echo (int) musa_dato($ajustesPanel, 'seguridad.limite_por_dia', 20); ?>"></label>
  </div>
</section>

<section class="bloque-panel">
  <h2>Géneros musicales</h2>
  <p class="nota">Cambia el nombre, la imagen y la descripción musical que se envía a la IA. Para quitar un género, desmarca «Activo» o borra su nombre.</p>
  <div id="lista-generos">
    <?php foreach ($generos as $posicion => $genero) : ?>
      <div class="genero-fila">
        <input type="hidden" name="generos[<?php echo (int) $posicion; ?>][id]" value="<?php echo musa_e($genero['id']); ?>">
        <div class="genero-imagen">
          <?php if (!empty($genero['imagen']) && musa_ruta_imagen_valida($genero['imagen'])) : ?>
            <img src="<?php echo musa_e(musa_url($genero['imagen'])); ?>" alt="">
          <?php endif; ?>
        </div>
        <label>Nombre<input type="text" name="generos[<?php echo (int) $posicion; ?>][nombre]" value="<?php echo musa_e($genero['nombre']); ?>"></label>
        <label>Descripción corta<input type="text" name="generos[<?php echo (int) $posicion; ?>][descripcion]" value="<?php echo musa_e($genero['descripcion']); ?>"></label>
        <label>Imagen
          <select name="generos[<?php echo (int) $posicion; ?>][imagen]">
            <option value="">— sin imagen —</option>
            <?php foreach ($imagenes as $imagen) : ?>
              <option value="<?php echo musa_e($imagen); ?>" <?php echo (string) $genero['imagen'] === $imagen ? 'selected' : ''; ?>><?php echo musa_e($imagen); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Subir imagen<input type="file" name="archivo_genero[<?php echo (int) $posicion; ?>]" accept="image/png,image/jpeg,image/gif,image/webp"></label>
        <label class="ancho-total">Descripción musical para la IA<textarea name="generos[<?php echo (int) $posicion; ?>][prompt]" rows="2"><?php echo musa_e($genero['prompt']); ?></textarea></label>
        <label class="interruptor"><input type="checkbox" name="generos[<?php echo (int) $posicion; ?>][activo]" <?php echo !empty($genero['activo']) ? 'checked' : ''; ?>> Activo</label>
      </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="boton-linea" id="agregar-genero" data-siguiente="<?php echo count($generos); ?>">Añadir género</button>
</section>

<div class="acciones-panel">
  <button type="submit" class="boton">Guardar cambios</button>
</div>
</form>

<?php musa_panel_fin(); ?>
