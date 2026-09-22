# Musa Café · Crea tu canción

Aplicación web interactiva para la **Gobernación de Nariño**. La persona elige un género
musical en una interfaz 3D hecha con **three.js**, cuenta su historia, deja sus datos y el
sistema compone la canción con inteligencia artificial y se la envía por correo.

Todo lo que se ve (colores, imágenes, logos, textos y géneros musicales) se configura desde
el panel **wj-admin**, sin tocar código.

---

## 1. Qué incluye

| Parte | Descripción |
|---|---|
| **Interfaz pública** (`index.php`) | Escena WebGL con las 16 tarjetas de género, retratos, partículas reactivas al audio y formulario en tres pasos. Funciona también sin WebGL. |
| **Panel** (`wj-admin/`) | Registros con todos los datos y casillas **Creado SÍ/NO** y **Enviado SÍ/NO**, configuración de apariencia y géneros, APIs de IA con verificación, correo y credenciales. |
| **Núcleo** (`wj-includes/`) | Configuración, almacenamiento en JSON, seguridad, correo (mail y SMTP), clientes de ElevenLabs y Google, y los puntos de acceso de la API pública. |
| **Contenido** (`wj-content/`) | Ajustes, registros, audios generados, imágenes subidas y bitácoras. Es la única carpeta que necesita permisos de escritura. |

Solo existen tres carpetas en la raíz del proyecto: `wj-admin`, `wj-includes` y `wj-content`.

---

## 2. Instalación en Plesk

1. **Sube los archivos.** Descarga el repositorio y copia todo su contenido dentro de
   `httpdocs` (o la carpeta del dominio/subdominio).
   No requiere Composer, Node ni base de datos.

2. **Versión de PHP.** En *Plesk → Dominios → Configuración de PHP*, selecciona **PHP 7.4 o
   superior** (probado en PHP 8.4). Deja activadas las extensiones `curl`, `json` y `mbstring`.

3. **Permisos de escritura.** La carpeta `wj-content` y sus subcarpetas deben ser escribibles
   por el usuario del servidor web:

   ```bash
   chmod -R 775 wj-content
   ```

   En *Plesk → Administrador de archivos* basta con dar permiso de escritura al grupo sobre
   `wj-content`. El sistema crea solo las subcarpetas que falten.

4. **Claves de las APIs.** Por seguridad **no viajan en el repositorio**. Tienes dos formas
   de cargarlas:

   - *La sencilla:* abre el panel y pégalas en *APIs de IA* (se guardan cifradas de la vista
     y enmascaradas en pantalla).
   - *La automática:* antes de la primera visita, copia
     `wj-content/config/claves.ejemplo.php` como `wj-content/config/claves.php` y escribe
     allí las claves. El sistema las toma en el primer arranque. Ese archivo está excluido
     del repositorio.

5. **Abre el sitio.** En la primera visita se genera `wj-content/config/ajustes.json.php` a
   partir del archivo de ejemplo y, si existe, de `claves.php`.

6. **Entra al panel:** `https://tu-dominio/wj-admin/`

   | Usuario | Contraseña |
   |---|---|
   | `admin` | `MusaCafe2026*Narino` |

   > **Cámbiala apenas ingreses**, en *Acceso → Usuario y contraseña*.

7. **Revisa las APIs** en *APIs de IA* y pulsa **Verificar conexión**.

8. **Configura el correo** en *Correo*. Con Plesk, `mail()` funciona si el dominio tiene
   servicio de correo activo; si no, usa SMTP autenticado.

---

## 3. Usuario y contraseña del panel (.htaccess)

Las credenciales viven en **`wj-admin/.htpasswd`**, cifradas con bcrypt: el mismo formato que
usa Apache. El panel siempre pide usuario y contraseña:

- Si el servidor aplica `.htaccess` (Apache, lo normal en Plesk), puedes activar además la
  ventana de autenticación del navegador quitando el comentario de estas líneas en
  `wj-admin/.htaccess` y poniendo la ruta absoluta real:

  ```apache
  AuthType Basic
  AuthName "Panel Musa Cafe"
  AuthUserFile /var/www/vhosts/TU-DOMINIO/httpdocs/wj-admin/.htpasswd
  Require valid-user
  ```

  La ruta exacta de tu servidor aparece calculada en el panel, en *Acceso*.

- Si el servidor **no** aplica `.htaccess` (nginx sin Apache), el panel usa su propio
  formulario de acceso validando contra ese mismo archivo. No hay que configurar nada.

Para cambiar usuario o contraseña: *panel → Acceso*. También puedes generar el archivo a mano:

```bash
htpasswd -B -c wj-admin/.htpasswd admin
```

---

## 4. Configuración desde el panel

### Apariencia y géneros
- **Identidad:** nombre, eslogan, entidad, título de la pestaña y descripción.
- **Imágenes y logos:** logo, rama decorativa, barra lateral y favicon. Puedes elegir una
  imagen existente o subir una nueva (se guarda en `wj-content/subidas`, máximo 5 MB).
- **Colores:** los doce colores de la interfaz, con selector visual.
- **Textos:** todos los textos visibles de la experiencia.
- **Formulario y sistema:** qué campos se piden, longitud de la historia, límites
  antispam, zona horaria, prefijo del código y encendido/apagado de los efectos 3D.
- **Géneros musicales:** nombre, descripción corta, imagen, descripción musical para la IA
  y estado activo. Puedes añadir, editar o desactivar los que quieras.

### APIs de IA
Cuatro modos, seleccionables con un clic:

| Modo | Qué hace |
|---|---|
| **ElevenLabs** | Compone el audio de la canción (MP3). |
| **Google Gemini** | Escribe la letra; opcionalmente también el audio con Lyria. |
| **Google + ElevenLabs** | Google escribe la letra y ElevenLabs la convierte en canción. |
| **Sin IA** | Solo guarda el registro para producirlo manualmente. |

Cada proveedor tiene dos comprobaciones:

- **Verificar conexión:** consulta la API sin consumir créditos.
- **Prueba de generación:** genera 10 segundos de música o una estrofa real (sí consume
  créditos) para confirmar que el servicio funciona de punta a punta.

Las claves se muestran enmascaradas; si dejas el campo vacío se conserva la anterior.
Nunca se escriben en el repositorio: viven en `wj-content/config/ajustes.json.php` (o en
`claves.php`), ambos excluidos por `.gitignore`.

### Correo
Remitente, asunto, plantilla del mensaje con etiquetas (`{nombre}`, `{genero}`, `{tema}`,
`{codigo}`, `{titulo}`, `{letra}`…), adjunto MP3 y envío por `mail()` o SMTP. Incluye un
botón para enviar un correo de prueba.

---

## 5. Registros

Cada envío queda guardado en `wj-content/datos/registros.json.php` con todos sus datos:
código, fecha, nombre, correo, teléfono, ciudad, género, historia, dedicatoria,
autorización de datos, estado, proveedor, letra, audio, notas internas e IP.

En el listado del panel puedes:

- Marcar **Creado SÍ/NO** y **Enviado SÍ/NO** (se guardan al instante, sin recargar).
- Filtrar por texto, género, estado de las casillas y rango de fechas.
- Ver el detalle completo con el reproductor de audio y la letra.
- **Generar** la canción o **Enviar** el correo manualmente.
- Escribir notas internas, eliminar registros y **exportar a CSV** (abre en Excel).

---

## 6. Seguridad

- Panel protegido con bcrypt, bloqueo tras 8 intentos fallidos y cierre de sesión por
  inactividad (2 horas). El bloqueo y el registro en bitácora se aplican tanto al
  formulario del panel como a las credenciales enviadas por cabecera.
- Token CSRF en todos los formularios y llamadas del panel.
- Límite de envíos por correo y por IP (configurable), más un campo trampa antirrobots.
- La IP del visitante se toma de la conexión real. Si el sitio está detrás de un
  balanceador o una CDN, agrega su dirección en `seguridad.proxies_confiables` dentro de
  `wj-content/config/ajustes.json.php` para que se lean las cabeceras `X-Real-IP` o
  `CF-Connecting-IP`; de lo contrario cualquiera podría falsear su IP y saltarse los límites.
- La exportación CSV neutraliza los valores que empiezan por `=`, `+`, `-` o `@`, para que
  una historia enviada desde la web no se convierta en fórmula al abrir el archivo en Excel.
- Los archivos de datos (`ajustes.json.php`, `registros.json.php`, bitácoras) empiezan con
  una línea PHP que responde 403: aunque el servidor no aplique `.htaccess`, nunca muestran
  su contenido por la web.
- `.htaccess` bloquea además los archivos de configuración, credenciales y datos, y prohíbe
  ejecutar PHP en las carpetas de subidas y audio.
- Validación y saneamiento de todos los campos; las imágenes subidas se verifican con
  `getimagesize()` y se renombran.
- Las claves de API nunca están en el repositorio. Si alguna vez se publica una por error,
  cámbiala en el proveedor: GitHub y los propios servicios detectan y revocan claves
  expuestas.

> **Datos personales:** el formulario pide autorización explícita conforme a la
> Ley 1581 de 2012. El archivo de registros contiene datos personales: haz copias de
> seguridad y trátalo según la política de la entidad.

> **Correos salientes:** el sistema envía la canción a la dirección que escribe la persona,
> sin confirmarla. Es lo que hace la experiencia fluida, pero significa que alguien podría
> usar el formulario para que llegue un mensaje —con su propio texto en la historia— a un
> tercero desde el dominio de la entidad. Los límites por IP y por correo acotan el riesgo.
> Si necesitas control estricto, desactiva **«Generar la canción automáticamente»** en
> *APIs de IA*: los registros quedarán en el panel y el correo saldrá solo cuando alguien
> pulse **Enviar** después de revisarlos.

---

## 7. Mantenimiento

| Tarea | Cómo |
|---|---|
| Copia de seguridad | Guarda `wj-content/` completo. |
| Actualizar desde el repositorio | `git pull`. Tus ajustes y registros no se sobrescriben: están en archivos ignorados por git. |
| Limpiar audios antiguos | Borra los MP3 de `wj-content/audio` que ya no necesites. |
| Revisar errores | Bitácora mensual en `wj-content/logs/`. |
| Diagnóstico del servidor | *Panel → Acceso → Estado de la instalación*. |

---

## 8. Estructura de archivos

```
index.php                     Interfaz pública
.htaccess                     Seguridad, caché y compresión
README.md                     Este documento
ARQUITECTURA.md               Detalle técnico y API interna

wj-admin/
  .htaccess  .htpasswd        Protección y credenciales del panel
  index.php                   Registros, casillas y acciones
  ajustes.php                 Apariencia, textos y géneros
  apis.php                    Proveedores de IA y verificación
  correo.php                  Configuración y prueba de correo
  cuenta.php                  Credenciales y estado del sistema
  acceso.php  salir.php       Inicio y cierre de sesión
  acciones.php  comun.php     Acciones del panel y plantilla común

wj-includes/
  arranque.php                Constantes y carga del sistema
  funciones.php               Utilidades (JSON, HTTP, rutas, textos)
  configuracion.php           Ajustes predeterminados y guardado
  almacenamiento.php          Registros en JSON con bloqueo
  seguridad.php               Sesión, CSRF, .htpasswd, límites
  correo.php                  Envío por mail() y SMTP
  ia.php                      ElevenLabs y Google (verificar y generar)
  api/registro.php            Guarda un registro (POST)
  api/generar.php             Genera la canción y envía el correo (POST)
  api/estado.php              Consulta el estado (GET)
  css/app.css  css/admin.css  Estilos
  js/app.js  js/admin.js      Lógica de la experiencia y del panel
  js/vendor/three.min.js      three.js r149
  images/                     Imágenes originales
  images/optimizadas/         Versiones ligeras que usa el sitio

wj-content/
  config/                     Ajustes vigentes, ejemplo y claves.php (opcional)
  datos/                      registros.json.php
  audio/                      Canciones generadas
  subidas/                    Imágenes cargadas desde el panel
  logs/                       Bitácoras mensuales
```

---

Gobernación de Nariño · Musa Café · versión 1.0.0
