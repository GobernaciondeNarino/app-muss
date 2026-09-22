<?php
/**
 * Musa Café · Registros recibidos
 * Muestra todos los datos capturados y permite marcar
 * las casillas "Creado SI/NO" y "Enviado SI/NO".
 */
require_once __DIR__ . '/comun.php';

$filtros = array(
    'busqueda' => musa_texto(isset($_GET['q']) ? $_GET['q'] : '', 80),
    'genero'   => musa_texto(isset($_GET['genero']) ? $_GET['genero'] : '', 60),
    'creado'   => in_array(isset($_GET['creado']) ? $_GET['creado'] : '', array('si', 'no'), true) ? $_GET['creado'] : '',
    'enviado'  => in_array(isset($_GET['enviado']) ? $_GET['enviado'] : '', array('si', 'no'), true) ? $_GET['enviado'] : '',
    'desde'    => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) (isset($_GET['desde']) ? $_GET['desde'] : '')) ? $_GET['desde'] : '',
    'hasta'    => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) (isset($_GET['hasta']) ? $_GET['hasta'] : '')) ? $_GET['hasta'] : '',
);

$porPagina = (int) musa_dato($ajustesPanel, 'sistema.registros_por_pagina', 25);
$pagina = max(1, (int) (isset($_GET['pagina']) ? $_GET['pagina'] : 1));
$resultado = musa_registros_filtrar($filtros, $pagina, $porPagina);
$resumen = musa_registros_resumen();
$generos = musa_dato($ajustesPanel, 'generos', array());
$proveedor = musa_ia_proveedor($ajustesPanel);

$consulta = array_filter(array(
    'q' => $filtros['busqueda'], 'genero' => $filtros['genero'], 'creado' => $filtros['creado'],
    'enviado' => $filtros['enviado'], 'desde' => $filtros['desde'], 'hasta' => $filtros['hasta'],
), function ($v) { return $v !== ''; });

musa_panel_inicio('Registros', 'registros');
musa_panel_mensaje();
?>

<section class="tarjetas-resumen">
  <div class="resumen"><span><?php echo (int) $resumen['total']; ?></span> registros</div>
  <div class="resumen"><span><?php echo (int) $resumen['hoy']; ?></span> hoy</div>
  <div class="resumen ok"><span><?php echo (int) $resumen['creados']; ?></span> creados</div>
  <div class="resumen aviso"><span><?php echo (int) $resumen['pendientes_creacion']; ?></span> por crear</div>
  <div class="resumen ok"><span><?php echo (int) $resumen['enviados']; ?></span> enviados</div>
  <div class="resumen aviso"><span><?php echo (int) $resumen['pendientes_envio']; ?></span> por enviar</div>
  <?php if ($resumen['errores'] > 0) : ?><div class="resumen mal"><span><?php echo (int) $resumen['errores']; ?></span> con error</div><?php endif; ?>
</section>

<form class="filtros" method="get" action="index.php">
  <input type="search" name="q" value="<?php echo musa_e($filtros['busqueda']); ?>" placeholder="Buscar por nombre, correo, código…">
  <select name="genero">
    <option value="">Todos los géneros</option>
    <?php foreach ($generos as $genero) : ?>
      <option value="<?php echo musa_e($genero['id']); ?>" <?php echo $filtros['genero'] === $genero['id'] ? 'selected' : ''; ?>><?php echo musa_e($genero['nombre']); ?></option>
    <?php endforeach; ?>
  </select>
  <select name="creado">
    <option value="">Creado: todos</option>
    <option value="si" <?php echo $filtros['creado'] === 'si' ? 'selected' : ''; ?>>Creado: SÍ</option>
    <option value="no" <?php echo $filtros['creado'] === 'no' ? 'selected' : ''; ?>>Creado: NO</option>
  </select>
  <select name="enviado">
    <option value="">Enviado: todos</option>
    <option value="si" <?php echo $filtros['enviado'] === 'si' ? 'selected' : ''; ?>>Enviado: SÍ</option>
    <option value="no" <?php echo $filtros['enviado'] === 'no' ? 'selected' : ''; ?>>Enviado: NO</option>
  </select>
  <label>Desde <input type="date" name="desde" value="<?php echo musa_e($filtros['desde']); ?>"></label>
  <label>Hasta <input type="date" name="hasta" value="<?php echo musa_e($filtros['hasta']); ?>"></label>
  <button type="submit" class="boton">Filtrar</button>
  <a class="boton-linea" href="index.php">Limpiar</a>
  <a class="boton-linea" href="acciones.php?accion=exportar&amp;<?php echo musa_e(http_build_query($consulta)); ?>">Exportar CSV</a>
</form>

<p class="nota">
  Proveedor de IA activo: <strong><?php echo musa_e(musa_dato(musa_ia_proveedores(), $proveedor . '.nombre', $proveedor)); ?></strong>.
  Las casillas <strong>Creado</strong> y <strong>Enviado</strong> se guardan al instante.
</p>

<?php if (empty($resultado['registros'])) : ?>
  <div class="vacio">Todavía no hay registros que coincidan con el filtro.</div>
<?php else : ?>
<div class="tabla-envoltura">
<table class="tabla" id="tabla-registros" data-token="<?php echo musa_e(musa_token()); ?>">
  <thead>
    <tr>
      <th>Código y fecha</th>
      <th>Persona</th>
      <th>Contacto</th>
      <th>Género</th>
      <th>Historia</th>
      <th class="centro">Creado</th>
      <th class="centro">Enviado</th>
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($resultado['registros'] as $registro) :
      $id = (string) $registro['id'];
      $audio = (string) $registro['audio'];
      $tieneAudio = $audio !== '' && file_exists(MUSA_RAIZ . '/' . ltrim($audio, '/'));
  ?>
    <tr id="fila-<?php echo musa_e($id); ?>">
      <td>
        <strong><?php echo musa_e($registro['codigo']); ?></strong>
        <span class="tenue"><?php echo musa_e($registro['fecha']); ?></span>
      </td>
      <td>
        <?php echo musa_e($registro['nombre']); ?>
        <?php if ($registro['ciudad'] !== '') : ?><span class="tenue"><?php echo musa_e($registro['ciudad']); ?></span><?php endif; ?>
      </td>
      <td>
        <a href="mailto:<?php echo musa_e($registro['correo']); ?>"><?php echo musa_e($registro['correo']); ?></a>
        <?php if ($registro['telefono'] !== '') : ?><span class="tenue"><?php echo musa_e($registro['telefono']); ?></span><?php endif; ?>
      </td>
      <td><?php echo musa_e($registro['genero_nombre'] !== '' ? $registro['genero_nombre'] : $registro['genero']); ?></td>
      <td class="historia"><?php echo musa_e(mb_substr((string) $registro['tema'], 0, 110, 'UTF-8')); ?><?php echo mb_strlen((string) $registro['tema'], 'UTF-8') > 110 ? '…' : ''; ?></td>
      <td class="centro">
        <label class="casilla">
          <input type="checkbox" class="marca" data-id="<?php echo musa_e($id); ?>" data-campo="creado" <?php echo !empty($registro['creado']) ? 'checked' : ''; ?>>
          <span><?php echo !empty($registro['creado']) ? 'SÍ' : 'NO'; ?></span>
        </label>
      </td>
      <td class="centro">
        <label class="casilla">
          <input type="checkbox" class="marca" data-id="<?php echo musa_e($id); ?>" data-campo="enviado" <?php echo !empty($registro['enviado']) ? 'checked' : ''; ?>>
          <span><?php echo !empty($registro['enviado']) ? 'SÍ' : 'NO'; ?></span>
        </label>
      </td>
      <td>
        <span class="etiqueta estado-<?php echo musa_e($registro['estado']); ?>"><?php echo musa_e($registro['estado']); ?></span>
      </td>
      <td class="acciones-fila">
        <button type="button" class="boton-linea pequeno ver-detalle" data-id="<?php echo musa_e($id); ?>">Detalle</button>
        <form method="post" action="acciones.php" class="en-linea confirmar" data-confirmar="¿Generar la canción con la IA configurada? Puede tardar hasta un minuto.">
          <?php musa_campo_token(); ?>
          <input type="hidden" name="accion" value="generar">
          <input type="hidden" name="id" value="<?php echo musa_e($id); ?>">
          <button type="submit" class="boton-linea pequeno">Generar</button>
        </form>
        <form method="post" action="acciones.php" class="en-linea confirmar" data-confirmar="¿Enviar el correo a esta persona?">
          <?php musa_campo_token(); ?>
          <input type="hidden" name="accion" value="enviar">
          <input type="hidden" name="id" value="<?php echo musa_e($id); ?>">
          <button type="submit" class="boton-linea pequeno">Enviar</button>
        </form>
        <form method="post" action="acciones.php" class="en-linea confirmar" data-confirmar="¿Eliminar definitivamente este registro y su audio?">
          <?php musa_campo_token(); ?>
          <input type="hidden" name="accion" value="eliminar">
          <input type="hidden" name="id" value="<?php echo musa_e($id); ?>">
          <button type="submit" class="boton-linea pequeno peligro">Eliminar</button>
        </form>
      </td>
    </tr>
    <tr class="detalle" id="detalle-<?php echo musa_e($id); ?>" hidden>
      <td colspan="9">
        <div class="detalle-rejilla">
          <div>
            <h3>Datos del registro</h3>
            <dl>
              <dt>Código</dt><dd><?php echo musa_e($registro['codigo']); ?></dd>
              <dt>Fecha</dt><dd><?php echo musa_e($registro['fecha']); ?></dd>
              <dt>Nombre</dt><dd><?php echo musa_e($registro['nombre']); ?></dd>
              <dt>Correo</dt><dd><?php echo musa_e($registro['correo']); ?></dd>
              <dt>Teléfono</dt><dd><?php echo musa_e($registro['telefono'] !== '' ? $registro['telefono'] : '—'); ?></dd>
              <dt>Ciudad</dt><dd><?php echo musa_e($registro['ciudad'] !== '' ? $registro['ciudad'] : '—'); ?></dd>
              <dt>Género</dt><dd><?php echo musa_e($registro['genero_nombre']); ?></dd>
              <dt>Dedicatoria</dt><dd><?php echo musa_e($registro['dedicatoria'] !== '' ? $registro['dedicatoria'] : '—'); ?></dd>
              <dt>Autoriza datos</dt><dd><?php echo !empty($registro['autorizacion']) ? 'SÍ' : 'NO'; ?></dd>
              <dt>Proveedor</dt><dd><?php echo musa_e($registro['proveedor'] !== '' ? $registro['proveedor'] : '—'); ?></dd>
              <dt>Creado el</dt><dd><?php echo musa_e($registro['fecha_creado'] !== '' ? $registro['fecha_creado'] : '—'); ?></dd>
              <dt>Enviado el</dt><dd><?php echo musa_e($registro['fecha_enviado'] !== '' ? $registro['fecha_enviado'] : '—'); ?></dd>
              <dt>IP</dt><dd><?php echo musa_e($registro['ip']); ?></dd>
            </dl>
          </div>
          <div>
            <h3>Historia</h3>
            <p class="bloque"><?php echo nl2br(musa_e($registro['tema'])); ?></p>
            <?php if ($registro['mensaje'] !== '') : ?>
              <h3>Mensaje del sistema</h3>
              <p class="bloque"><?php echo musa_e($registro['mensaje']); ?></p>
            <?php endif; ?>
            <form method="post" action="acciones.php" class="nota-form">
              <?php musa_campo_token(); ?>
              <input type="hidden" name="accion" value="nota">
              <input type="hidden" name="id" value="<?php echo musa_e($id); ?>">
              <label for="nota-<?php echo musa_e($id); ?>">Notas internas</label>
              <textarea id="nota-<?php echo musa_e($id); ?>" name="notas" rows="3"><?php echo musa_e($registro['notas']); ?></textarea>
              <button type="submit" class="boton-linea pequeno">Guardar nota</button>
            </form>
          </div>
          <div>
            <h3>Canción</h3>
            <?php if ($registro['titulo_cancion'] !== '') : ?><p><strong><?php echo musa_e($registro['titulo_cancion']); ?></strong></p><?php endif; ?>
            <?php if ($tieneAudio) : ?>
              <audio controls preload="none" src="<?php echo musa_e(musa_url($audio)); ?>"></audio>
              <p><a class="boton-linea pequeno" href="<?php echo musa_e(musa_url($audio)); ?>" download>Descargar MP3</a></p>
            <?php else : ?>
              <p class="tenue">Sin audio generado.</p>
            <?php endif; ?>
            <?php if ($registro['letra'] !== '') : ?>
              <h3>Letra</h3>
              <p class="bloque letra"><?php echo nl2br(musa_e($registro['letra'])); ?></p>
            <?php endif; ?>
          </div>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php if ($resultado['paginas'] > 1) : ?>
<nav class="paginacion" aria-label="Paginación">
  <?php for ($i = 1; $i <= $resultado['paginas']; $i++) :
      $consulta['pagina'] = $i; ?>
    <a href="index.php?<?php echo musa_e(http_build_query($consulta)); ?>" class="<?php echo $i === $resultado['pagina'] ? 'activo' : ''; ?>"><?php echo $i; ?></a>
  <?php endfor; ?>
</nav>
<?php endif; ?>
<?php endif; ?>

<?php musa_panel_fin(); ?>
