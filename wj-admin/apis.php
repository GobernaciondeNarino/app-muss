<?php
/**
 * Musa Café · Configuración y verificación de las APIs de IA
 */
require_once __DIR__ . '/comun.php';

$verificacion = null;
$prueba = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    musa_exigir_token(isset($_POST['token']) ? $_POST['token'] : '');
    $accion = musa_texto(isset($_POST['accion']) ? $_POST['accion'] : 'guardar', 20);
    $nuevos = $ajustesPanel;

    if ($accion === 'guardar') {
        $proveedor = musa_texto(isset($_POST['ia']['proveedor']) ? $_POST['ia']['proveedor'] : 'ninguno', 20);
        if (array_key_exists($proveedor, musa_ia_proveedores())) {
            musa_fijar($nuevos, 'ia.proveedor', $proveedor);
        }
        musa_fijar($nuevos, 'ia.generacion_automatica', !empty($_POST['ia']['generacion_automatica']));
        musa_fijar($nuevos, 'ia.instruccion_letra', musa_texto(isset($_POST['ia']['instruccion_letra']) ? $_POST['ia']['instruccion_letra'] : '', 1500));

        // Las claves solo se cambian si se escribe una nueva.
        $claveEleven = trim((string) (isset($_POST['ia']['elevenlabs']['api_key']) ? $_POST['ia']['elevenlabs']['api_key'] : ''));
        if (!empty($_POST['borrar_elevenlabs'])) { musa_fijar($nuevos, 'ia.elevenlabs.api_key', ''); }
        elseif ($claveEleven !== '') { musa_fijar($nuevos, 'ia.elevenlabs.api_key', musa_texto($claveEleven, 200)); }

        $claveGoogle = trim((string) (isset($_POST['ia']['google']['api_key']) ? $_POST['ia']['google']['api_key'] : ''));
        if (!empty($_POST['borrar_google'])) { musa_fijar($nuevos, 'ia.google.api_key', ''); }
        elseif ($claveGoogle !== '') { musa_fijar($nuevos, 'ia.google.api_key', musa_texto($claveGoogle, 200)); }

        musa_fijar($nuevos, 'ia.elevenlabs.endpoint', musa_texto($_POST['ia']['elevenlabs']['endpoint'] ?? '', 200));
        musa_fijar($nuevos, 'ia.elevenlabs.modelo', musa_texto($_POST['ia']['elevenlabs']['modelo'] ?? 'music_v1', 60));
        musa_fijar($nuevos, 'ia.elevenlabs.duracion_ms', max(10000, min(300000, (int) ($_POST['ia']['elevenlabs']['duracion_ms'] ?? 60000))));
        musa_fijar($nuevos, 'ia.elevenlabs.formato', musa_texto($_POST['ia']['elevenlabs']['formato'] ?? 'mp3_44100_128', 40));
        musa_fijar($nuevos, 'ia.google.endpoint', musa_texto($_POST['ia']['google']['endpoint'] ?? '', 200));
        musa_fijar($nuevos, 'ia.google.modelo', musa_texto($_POST['ia']['google']['modelo'] ?? 'gemini-3.6-flash', 80));
        musa_fijar($nuevos, 'ia.google.modelo_musica', musa_texto($_POST['ia']['google']['modelo_musica'] ?? 'lyria-3.5', 80));
        musa_fijar($nuevos, 'ia.google.generar_audio', !empty($_POST['ia']['google']['generar_audio']));

        musa_guardar_ajustes($nuevos);
        musa_log('Configuración de IA guardada', array('usuario' => $usuarioActual, 'proveedor' => musa_dato($nuevos, 'ia.proveedor', '')));
        musa_panel_mensaje('Configuración de APIs guardada.');
        header('Location: apis.php');
        exit;
    }

    if ($accion === 'verificar') {
        $objetivo = musa_texto(isset($_POST['proveedor']) ? $_POST['proveedor'] : '', 20);
        $verificacion = musa_ia_verificar($objetivo, $ajustesPanel);
        $verificacion['proveedor'] = $objetivo;
        musa_fijar($ajustesPanel, 'ia.ultima_verificacion', array(
            'proveedor' => $objetivo,
            'ok'        => (bool) $verificacion['ok'],
            'mensaje'   => $verificacion['mensaje'],
            'fecha'     => date('Y-m-d H:i:s'),
        ));
        musa_guardar_ajustes($ajustesPanel);
        $ajustesPanel = musa_ajustes(true);
    }

    if ($accion === 'probar') {
        @set_time_limit(600);
        $objetivo = musa_texto(isset($_POST['proveedor']) ? $_POST['proveedor'] : '', 20);
        $prueba = musa_ia_prueba_generacion($objetivo, $ajustesPanel);
        $prueba['proveedor'] = $objetivo;
    }
}

$proveedorActual = musa_ia_proveedor($ajustesPanel);
$modelosGoogle = musa_ia_modelos_google($ajustesPanel);
$ultima = musa_dato($ajustesPanel, 'ia.ultima_verificacion', array());

musa_panel_inicio('APIs de inteligencia artificial', 'apis');
musa_panel_mensaje();
?>

<?php if ($verificacion !== null) : ?>
  <div class="alerta <?php echo $verificacion['ok'] ? 'exito' : 'error'; ?>">
    <strong>Verificación de <?php echo musa_e($verificacion['proveedor']); ?>:</strong>
    <?php echo musa_e($verificacion['mensaje']); ?>
    <?php if (!empty($verificacion['detalle'])) : ?><br><small><?php echo musa_e($verificacion['detalle']); ?></small><?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($prueba !== null) : ?>
  <div class="alerta <?php echo $prueba['ok'] ? 'exito' : 'error'; ?>">
    <strong>Prueba de generación (<?php echo musa_e($prueba['proveedor']); ?>):</strong>
    <?php echo musa_e($prueba['mensaje']); ?>
    <?php if (!empty($prueba['detalle'])) : ?><br><small><?php echo musa_e($prueba['detalle']); ?></small><?php endif; ?>
  </div>
<?php endif; ?>

<section class="bloque-panel">
  <h2>Estado del servicio</h2>
  <div class="verificaciones">
    <?php foreach (array('elevenlabs', 'google') as $objetivo) :
        $clave = (string) musa_dato($ajustesPanel, 'ia.' . $objetivo . '.api_key', ''); ?>
      <div class="verificacion">
        <h3><?php echo musa_e(musa_dato(musa_ia_proveedores(), $objetivo . '.nombre', $objetivo)); ?></h3>
        <p class="tenue">Clave: <?php echo $clave === '' ? '<em>sin configurar</em>' : '<code>' . musa_e(musa_enmascarar_clave($clave)) . '</code>'; ?></p>
        <div class="fila-botones">
          <form method="post" action="apis.php" class="en-linea">
            <?php musa_campo_token(); ?>
            <input type="hidden" name="accion" value="verificar">
            <input type="hidden" name="proveedor" value="<?php echo musa_e($objetivo); ?>">
            <button type="submit" class="boton-linea pequeno">Verificar conexión</button>
          </form>
          <form method="post" action="apis.php" class="en-linea confirmar" data-confirmar="La prueba real consume créditos de la API. ¿Continuar?">
            <?php musa_campo_token(); ?>
            <input type="hidden" name="accion" value="probar">
            <input type="hidden" name="proveedor" value="<?php echo musa_e($objetivo); ?>">
            <button type="submit" class="boton-linea pequeno">Prueba de generación</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!empty($ultima)) : ?>
    <p class="nota">Última verificación: <strong><?php echo musa_e($ultima['proveedor'] ?? ''); ?></strong> ·
      <?php echo !empty($ultima['ok']) ? 'correcta' : 'con error'; ?> · <?php echo musa_e($ultima['fecha'] ?? ''); ?>
      · <?php echo musa_e($ultima['mensaje'] ?? ''); ?></p>
  <?php endif; ?>
</section>

<form method="post" action="apis.php" class="formulario-panel">
<?php musa_campo_token(); ?>
<input type="hidden" name="accion" value="guardar">

<section class="bloque-panel">
  <h2>¿Qué servicio se usa?</h2>
  <div class="opciones-proveedor">
    <?php foreach (musa_ia_proveedores() as $clave => $info) : ?>
      <label class="opcion <?php echo $proveedorActual === $clave ? 'activa' : ''; ?>">
        <input type="radio" name="ia[proveedor]" value="<?php echo musa_e($clave); ?>" <?php echo $proveedorActual === $clave ? 'checked' : ''; ?>>
        <strong><?php echo musa_e($info['nombre']); ?></strong>
        <span><?php echo musa_e($info['detalle']); ?></span>
      </label>
    <?php endforeach; ?>
  </div>
  <label class="interruptor"><input type="checkbox" name="ia[generacion_automatica]" <?php echo !empty(musa_dato($ajustesPanel, 'ia.generacion_automatica', true)) ? 'checked' : ''; ?>>
    Generar la canción automáticamente cuando la persona envía el formulario</label>
</section>

<section class="bloque-panel">
  <h2>ElevenLabs</h2>
  <div class="rejilla">
    <label class="ancho-total">Clave de API (déjala vacía para conservar la actual)
      <input type="password" name="ia[elevenlabs][api_key]" autocomplete="off" placeholder="<?php echo musa_e(musa_enmascarar_clave(musa_dato($ajustesPanel, 'ia.elevenlabs.api_key', ''))); ?>">
    </label>
    <label class="interruptor"><input type="checkbox" name="borrar_elevenlabs"> Borrar la clave guardada</label>
    <label>Endpoint<input type="text" name="ia[elevenlabs][endpoint]" value="<?php echo musa_e(musa_dato($ajustesPanel, 'ia.elevenlabs.endpoint', '')); ?>"></label>
    <label>Modelo de música<input type="text" name="ia[elevenlabs][modelo]" value="<?php echo musa_e(musa_dato($ajustesPanel, 'ia.elevenlabs.modelo', 'music_v1')); ?>"></label>
    <label>Duración (milisegundos)<input type="number" name="ia[elevenlabs][duracion_ms]" min="10000" max="300000" step="1000" value="<?php echo (int) musa_dato($ajustesPanel, 'ia.elevenlabs.duracion_ms', 60000); ?>"></label>
    <label>Formato de salida
      <select name="ia[elevenlabs][formato]">
        <?php foreach (array('mp3_44100_128', 'mp3_44100_192', 'mp3_22050_32') as $formato) : ?>
          <option value="<?php echo musa_e($formato); ?>" <?php echo musa_dato($ajustesPanel, 'ia.elevenlabs.formato', '') === $formato ? 'selected' : ''; ?>><?php echo musa_e($formato); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
</section>

<section class="bloque-panel">
  <h2>Google</h2>
  <div class="rejilla">
    <label class="ancho-total">Clave de API (déjala vacía para conservar la actual)
      <input type="password" name="ia[google][api_key]" autocomplete="off" placeholder="<?php echo musa_e(musa_enmascarar_clave(musa_dato($ajustesPanel, 'ia.google.api_key', ''))); ?>">
    </label>
    <label class="interruptor"><input type="checkbox" name="borrar_google"> Borrar la clave guardada</label>
    <label>Endpoint<input type="text" name="ia[google][endpoint]" value="<?php echo musa_e(musa_dato($ajustesPanel, 'ia.google.endpoint', '')); ?>"></label>
    <label>Modelo de texto (letra)
      <input type="text" name="ia[google][modelo]" list="modelos-google" value="<?php echo musa_e(musa_dato($ajustesPanel, 'ia.google.modelo', '')); ?>">
      <datalist id="modelos-google">
        <?php foreach ($modelosGoogle as $modelo) : ?><option value="<?php echo musa_e($modelo); ?>"></option><?php endforeach; ?>
      </datalist>
    </label>
    <label>Modelo de música (opcional)
      <input type="text" name="ia[google][modelo_musica]" list="modelos-google" value="<?php echo musa_e(musa_dato($ajustesPanel, 'ia.google.modelo_musica', '')); ?>">
    </label>
    <label class="interruptor"><input type="checkbox" name="ia[google][generar_audio]" <?php echo !empty(musa_dato($ajustesPanel, 'ia.google.generar_audio', false)) ? 'checked' : ''; ?>>
      Generar también el audio con Google (requiere cupo del modelo de música)</label>
  </div>
  <?php if ($modelosGoogle === array()) : ?>
    <p class="nota">No se pudo consultar la lista de modelos. Verifica la clave para ver las opciones disponibles.</p>
  <?php else : ?>
    <p class="nota"><?php echo count($modelosGoogle); ?> modelos disponibles con esta clave.</p>
  <?php endif; ?>
</section>

<section class="bloque-panel">
  <h2>Instrucción para la letra</h2>
  <label class="ancho-total">
    <textarea name="ia[instruccion_letra]" rows="4"><?php echo musa_e(musa_dato($ajustesPanel, 'ia.instruccion_letra', '')); ?></textarea>
  </label>
  <p class="nota">Este texto guía al modelo de Google cuando escribe la letra de la canción.</p>
</section>

<div class="acciones-panel">
  <button type="submit" class="boton">Guardar configuración</button>
</div>
</form>

<?php musa_panel_fin(); ?>
