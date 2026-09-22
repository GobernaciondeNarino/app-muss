<?php
/**
 * Musa Café · Interfaz pública
 * Gobernación de Nariño
 */
require_once __DIR__ . '/wj-includes/arranque.php';

musa_cabeceras_seguridad();
musa_sesion();

$ajustes  = musa_ajustes();
$generos  = musa_generos_activos($ajustes);
$colores  = musa_dato($ajustes, 'colores', array());
$textos   = musa_dato($ajustes, 'textos', array());
$marca    = musa_dato($ajustes, 'marca', array());
$form     = musa_dato($ajustes, 'formulario', array());
$token    = musa_token();

$imagen = function ($ruta, $respaldo = '') {
    return musa_ruta_imagen_valida($ruta) ? musa_url($ruta) : ($respaldo !== '' ? musa_url($respaldo) : '');
};

$logo  = $imagen(musa_dato($marca, 'logo', ''), 'wj-includes/images/optimizadas/logo_musacafe.png');
$rama  = $imagen(musa_dato($marca, 'fondo', ''), 'wj-includes/images/optimizadas/bg.png');
$barra = $imagen(musa_dato($marca, 'barra', ''), 'wj-includes/images/optimizadas/bg_barra.png');
$favicon = $imagen(musa_dato($marca, 'favicon', ''), 'wj-includes/images/optimizadas/logo_musacafe.png');

$generosJs = array();
foreach ($generos as $genero) {
    $generosJs[] = array(
        'id'          => (string) $genero['id'],
        'nombre'      => (string) $genero['nombre'],
        'descripcion' => (string) $genero['descripcion'],
        'imagen'      => $imagen((string) $genero['imagen']),
    );
}

$configJs = array(
    'token'    => $token,
    'efectos3d'=> (bool) musa_dato($ajustes, 'sistema.efectos_3d', true),
    'minimoTema' => (int) musa_dato($form, 'minimo_tema', 15),
    'maximoTema' => (int) musa_dato($form, 'maximo_tema', 600),
    'generos'  => $generosJs,
    'colores'  => array(
        'fondo'         => musa_color(musa_dato($colores, 'fondo', ''), '#AE1D2C'),
        'fondoProfundo' => musa_color(musa_dato($colores, 'fondo_profundo', ''), '#7E0E1C'),
        'tarjeta'       => musa_color(musa_dato($colores, 'tarjeta', ''), '#9F1427'),
        'tarjetaBorde'  => musa_color(musa_dato($colores, 'tarjeta_borde', ''), '#C3364A'),
        'tarjetaActiva' => musa_color(musa_dato($colores, 'tarjeta_activa', ''), '#F6EDD9'),
        'texto'         => musa_color(musa_dato($colores, 'texto', ''), '#F7EFE0'),
        'textoSuave'    => musa_color(musa_dato($colores, 'texto_suave', ''), '#EBC9CE'),
        'textoActivo'   => musa_color(musa_dato($colores, 'texto_activo', ''), '#7E0E1C'),
        'acento'        => musa_color(musa_dato($colores, 'acento', ''), '#F2B705'),
        'acentoSecundario' => musa_color(musa_dato($colores, 'acento_secundario', ''), '#12A5C4'),
    ),
    'textos'   => array(
        'paso1Ayuda'  => (string) musa_dato($textos, 'paso1_ayuda', ''),
        'listoTitulo' => (string) musa_dato($textos, 'listo_titulo', ''),
        'listoTexto'  => (string) musa_dato($textos, 'listo_texto', ''),
    ),
    'rutas'    => array(
        'registro' => musa_url('wj-includes/api/registro.php'),
        'generar'  => musa_url('wj-includes/api/generar.php'),
        'estado'   => musa_url('wj-includes/api/estado.php'),
    ),
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="description" content="<?php echo musa_e(musa_dato($marca, 'descripcion', '')); ?>">
<meta name="theme-color" content="<?php echo musa_e(musa_color(musa_dato($colores, 'fondo', ''), '#AE1D2C')); ?>">
<title><?php echo musa_e(musa_dato($marca, 'titulo_sitio', 'Musa Café')); ?></title>
<?php if ($favicon !== '') : ?>
<link rel="icon" href="<?php echo musa_e($favicon); ?>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo musa_e(musa_recurso('wj-includes/css/app.css')); ?>">
<style>
:root{
  --musa-fondo: <?php echo musa_e(musa_color(musa_dato($colores, 'fondo', ''), '#AE1D2C')); ?>;
  --musa-fondo-profundo: <?php echo musa_e(musa_color(musa_dato($colores, 'fondo_profundo', ''), '#7E0E1C')); ?>;
  --musa-tarjeta: <?php echo musa_e(musa_color(musa_dato($colores, 'tarjeta', ''), '#9F1427')); ?>;
  --musa-tarjeta-borde: <?php echo musa_e(musa_color(musa_dato($colores, 'tarjeta_borde', ''), '#C3364A')); ?>;
  --musa-tarjeta-activa: <?php echo musa_e(musa_color(musa_dato($colores, 'tarjeta_activa', ''), '#F6EDD9')); ?>;
  --musa-texto: <?php echo musa_e(musa_color(musa_dato($colores, 'texto', ''), '#F7EFE0')); ?>;
  --musa-texto-rgb: <?php echo musa_e(musa_color_rgb(musa_dato($colores, 'texto', ''), '247, 239, 224')); ?>;
  --musa-texto-suave: <?php echo musa_e(musa_color(musa_dato($colores, 'texto_suave', ''), '#EBC9CE')); ?>;
  --musa-texto-activo: <?php echo musa_e(musa_color(musa_dato($colores, 'texto_activo', ''), '#7E0E1C')); ?>;
  --musa-acento: <?php echo musa_e(musa_color(musa_dato($colores, 'acento', ''), '#F2B705')); ?>;
  --musa-acento-rgb: <?php echo musa_e(musa_color_rgb(musa_dato($colores, 'acento', ''), '242, 183, 5')); ?>;
  --musa-acento-secundario: <?php echo musa_e(musa_color(musa_dato($colores, 'acento_secundario', ''), '#12A5C4')); ?>;
  --musa-exito: <?php echo musa_e(musa_color(musa_dato($colores, 'exito', ''), '#2E9E6B')); ?>;
  --musa-error: <?php echo musa_e(musa_color(musa_dato($colores, 'error', ''), '#FFB3BC')); ?>;
}
</style>
</head>
<body>

<canvas id="escena" aria-hidden="true"></canvas>
<?php if ($rama !== '') : ?><img class="deco deco-rama" src="<?php echo musa_e($rama); ?>" alt="" aria-hidden="true"><?php endif; ?>
<?php if ($barra !== '') : ?><img class="deco deco-barra" src="<?php echo musa_e($barra); ?>" alt="" aria-hidden="true"><?php endif; ?>

<div class="pagina">

  <div class="columna-marca">
    <?php if ($logo !== '') : ?>
      <img class="logo" src="<?php echo musa_e($logo); ?>" alt="<?php echo musa_e(musa_dato($marca, 'nombre', 'Musa Café')); ?>">
    <?php else : ?>
      <strong class="logo"><?php echo musa_e(musa_dato($marca, 'nombre', 'Musa Café')); ?></strong>
    <?php endif; ?>
  </div>

  <main class="columna-contenido">

    <header class="encabezado">
      <div>
        <h1><?php echo musa_e(musa_dato($textos, 'titulo', 'Crea tu canción')); ?></h1>
        <p><?php echo musa_e(musa_dato($textos, 'subtitulo', '')); ?></p>
      </div>
      <nav class="pasos" aria-label="Progreso">
        <span class="paso-marca activo"><span>1</span> Género e historia</span>
        <span class="paso-marca"><span>2</span> Tus datos</span>
        <span class="paso-marca"><span>3</span> Tu canción</span>
      </nav>
    </header>

    <section class="zona-generos" id="zona-generos" aria-labelledby="titulo-generos">
      <h2 id="titulo-generos" class="solo-lectores"><?php echo musa_e(musa_dato($textos, 'paso1_titulo', 'Elige un género')); ?></h2>
      <div class="generos" id="generos" role="group" aria-labelledby="titulo-generos">
        <?php foreach ($generos as $genero) :
            $img = $imagen((string) $genero['imagen']); ?>
          <button type="button" class="genero" data-id="<?php echo musa_e($genero['id']); ?>" aria-pressed="false">
            <?php if ($img !== '') : ?><img src="<?php echo musa_e($img); ?>" alt="" aria-hidden="true" loading="lazy"><?php endif; ?>
            <span class="genero-texto">
              <span class="genero-nombre"><?php echo musa_e($genero['nombre']); ?></span>
              <span class="genero-mood"><?php echo musa_e($genero['descripcion']); ?></span>
            </span>
          </button>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="panel">

      <!-- Paso 1: historia -->
      <div class="vista activa" id="vista-1">
        <div class="campo" style="flex:1">
          <label for="tema"><?php echo musa_e(musa_dato($textos, 'tema_etiqueta', 'Cuéntanos tu historia')); ?></label>
          <textarea id="tema" name="tema" maxlength="<?php echo (int) musa_dato($form, 'maximo_tema', 600); ?>"
            placeholder="<?php echo musa_e(musa_dato($textos, 'tema_ejemplo', '')); ?>"></textarea>
          <span class="contador" id="contador-tema">0 / <?php echo (int) musa_dato($form, 'maximo_tema', 600); ?></span>
        </div>
        <div class="acciones">
          <span class="ayuda" id="ayuda-paso1"><?php echo musa_e(musa_dato($textos, 'paso1_ayuda', '')); ?></span>
          <button type="button" class="boton" id="continuar" disabled><?php echo musa_e(musa_dato($textos, 'boton_continuar', 'Continuar')); ?> →</button>
        </div>
      </div>

      <!-- Paso 2: datos -->
      <div class="vista" id="vista-2">
        <form id="formulario" novalidate>
          <div class="rejilla-campos">
            <div class="campo">
              <label for="nombre">Nombres y apellidos</label>
              <input type="text" id="nombre" name="nombre" autocomplete="name" maxlength="120" required>
              <span class="error-campo" id="error-nombre"></span>
            </div>
            <div class="campo">
              <label for="correo">Correo electrónico</label>
              <input type="email" id="correo" name="correo" autocomplete="email" maxlength="160" required>
              <span class="error-campo" id="error-correo"></span>
            </div>
            <?php if (!empty(musa_dato($form, 'pedir_telefono', true))) : ?>
            <div class="campo">
              <label for="telefono">Teléfono <?php echo empty(musa_dato($form, 'telefono_obligatorio', false)) ? '(opcional)' : ''; ?></label>
              <input type="tel" id="telefono" name="telefono" autocomplete="tel" maxlength="40">
              <span class="error-campo" id="error-telefono"></span>
            </div>
            <?php endif; ?>
            <?php if (!empty(musa_dato($form, 'pedir_ciudad', true))) : ?>
            <div class="campo">
              <label for="ciudad">Municipio o ciudad <?php echo empty(musa_dato($form, 'ciudad_obligatoria', false)) ? '(opcional)' : ''; ?></label>
              <input type="text" id="ciudad" name="ciudad" maxlength="80">
              <span class="error-campo" id="error-ciudad"></span>
            </div>
            <?php endif; ?>
            <?php if (!empty(musa_dato($form, 'pedir_dedicatoria', true))) : ?>
            <div class="campo">
              <label for="dedicatoria">¿A quién se la dedicas? (opcional)</label>
              <input type="text" id="dedicatoria" name="dedicatoria" maxlength="200">
              <span class="error-campo" id="error-dedicatoria"></span>
            </div>
            <?php endif; ?>
          </div>

          <div class="trampa" aria-hidden="true">
            <label for="sitio_web">No llenar</label>
            <input type="text" id="sitio_web" name="sitio_web" tabindex="-1" autocomplete="off">
          </div>

          <label class="acepto" style="margin-top:12px">
            <input type="checkbox" id="autorizacion" name="autorizacion" <?php echo empty(musa_dato($ajustes, 'seguridad.exigir_aceptacion', true)) ? 'checked' : ''; ?>>
            <span><?php echo musa_e(musa_dato($textos, 'aviso_datos', '')); ?></span>
          </label>
          <span class="error-campo" id="error-autorizacion"></span>

          <div class="aviso" id="aviso-datos" hidden></div>

          <div class="acciones">
            <button type="button" class="boton boton-linea" id="atras">← Atrás</button>
            <button type="submit" class="boton" id="enviar"><?php echo musa_e(musa_dato($textos, 'boton_enviar', 'Componer mi canción')); ?></button>
          </div>
        </form>
      </div>

      <!-- Paso 3: proceso y resultado -->
      <div class="vista" id="vista-3">
        <div class="proceso" id="proceso">
          <div class="barras" aria-hidden="true">
            <span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span>
          </div>
          <h2><?php echo musa_e(musa_dato($textos, 'generando', 'Estamos componiendo tu canción…')); ?></h2>
          <p id="linea-proceso" role="status" aria-live="polite">Afinando los instrumentos…</p>
        </div>

        <div class="resultado" id="resultado" hidden>
          <h2 id="titulo-resultado"><?php echo musa_e(musa_dato($textos, 'listo_titulo', '¡Listo!')); ?></h2>
          <p id="texto-resultado" role="status" aria-live="polite"><?php echo musa_e(musa_dato($textos, 'listo_texto', '')); ?></p>
          <span class="codigo">Código: <strong id="codigo-seguimiento">—</strong></span>
          <h3 id="titulo-cancion" hidden style="margin:0;font-size:18px"></h3>
          <audio id="audio" controls preload="none" hidden></audio>
          <div class="letra" id="letra" hidden></div>
          <div class="acciones">
            <a class="boton boton-linea" id="descargar" hidden download>Descargar audio</a>
            <button type="button" class="boton" id="otra">Crear otra canción</button>
          </div>
        </div>
      </div>

    </section>

    <p class="pie"><?php echo musa_e(musa_dato($textos, 'pie', '')); ?></p>
  </main>
</div>

<script>window.MUSA_CONFIG = <?php echo json_encode($configJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<script src="<?php echo musa_e(musa_recurso('wj-includes/js/vendor/three.min.js')); ?>"></script>
<script src="<?php echo musa_e(musa_recurso('wj-includes/js/app.js')); ?>"></script>
</body>
</html>
