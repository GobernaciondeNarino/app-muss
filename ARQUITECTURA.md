# Musa Café · Documento técnico

Complemento del `README.md` para quien vaya a mantener o ampliar el sistema.

---

## 1. Principios

- **Sin dependencias de servidor.** Solo PHP 7.4+ con `curl`, `json` y `mbstring`.
  Sin Composer, sin base de datos, sin proceso de compilación. Se despliega copiando archivos.
- **Tres carpetas.** `wj-admin` (panel), `wj-includes` (núcleo y recursos) y `wj-content`
  (datos y archivos generados, la única carpeta escribible).
- **Todo configurable.** La interfaz pública no tiene textos, colores ni géneros escritos en
  el código: se leen de `wj-content/config/ajustes.json.php`.
- **Sin claves en el repositorio.** En el primer arranque los ajustes se arman con
  `musa_ajustes_predeterminados()` + `ajustes.ejemplo.json.php` + `musa_claves_locales()`
  (el opcional `wj-content/config/claves.php`). Después manda el panel.
- **Degradación elegante.** Sin WebGL la grilla 3D se sustituye por una rejilla HTML
  equivalente; sin IA el sistema sigue registrando solicitudes.

---

## 2. Flujo de una solicitud

```
Visitante                     Servidor                          Servicios
   │                             │                                  │
   │ 1. GET /                    │                                  │
   │───────────────────────────► │ index.php lee los ajustes        │
   │ ◄───────────────────────────│ y envía HTML + MUSA_CONFIG       │
   │                             │                                  │
   │ 2. POST api/registro.php    │                                  │
   │───────────────────────────► │ valida, comprueba CSRF y         │
   │                             │ límites, guarda el registro      │
   │ ◄───────────────────────────│ devuelve código + clave          │
   │                             │                                  │
   │ 3. POST api/generar.php     │                                  │
   │───────────────────────────► │ musa_ia_generar()                │
   │                             │─────────────────────────────────►│ Google: letra
   │                             │─────────────────────────────────►│ ElevenLabs: audio
   │                             │ guarda el MP3 y marca "creado"   │
   │                             │ envía el correo y marca "enviado"│
   │ ◄───────────────────────────│ título, letra y URL del audio    │
   │                             │                                  │
   │ 4. GET api/estado.php       │ (solo si se cortó la conexión)   │
```

El paso 3 puede tardar entre 30 y 90 segundos. La sesión se cierra antes de llamar a la IA
(`session_write_close`) para no bloquear otras peticiones del mismo visitante, y se amplía
`max_execution_time`. Si la conexión se corta, la interfaz consulta `api/estado.php` cada
6 segundos hasta 20 veces.

---

## 3. Puntos de acceso (API interna)

Todas las respuestas son JSON con `Content-Type: application/json; charset=utf-8`.

### `POST wj-includes/api/registro.php`

```json
{ "token": "<csrf>", "genero": "cumbia", "tema": "…", "nombre": "…",
  "correo": "…", "telefono": "", "ciudad": "", "dedicatoria": "",
  "autorizacion": true, "sitio_web": "" }
```

| Respuesta | Código |
|---|---|
| `{ "ok": true, "codigo": "MUSA-20260922-0001", "clave": "…", "generar": true }` | 200 |
| `{ "ok": false, "errores": { "correo": "…" } }` | 422 |
| Límite de envíos superado | 429 |
| Token vencido | 419 |

`sitio_web` es el campo trampa: si llega con contenido, la petición se descarta.

Los límites se cuentan contra la IP real (`REMOTE_ADDR`). Las cabeceras `X-Real-IP`,
`CF-Connecting-IP` o `X-Forwarded-For` solo se leen cuando la conexión viene de una
dirección incluida en `seguridad.proxies_confiables`: de otro modo bastaría con cambiar
una cabecera para reiniciar el contador.

### `POST wj-includes/api/generar.php`

```json
{ "token": "<csrf>", "codigo": "MUSA-20260922-0001", "clave": "<token del registro>" }
```

Devuelve `ok`, `estado`, `creado`, `enviado`, `titulo`, `letra`, `audio` y `correo`.
La `clave` es un token aleatorio de 32 caracteres creado con el registro: sin él no se
puede disparar la generación de una solicitud ajena.

### `GET wj-includes/api/estado.php?codigo=…&clave=…`

Devuelve el estado actual del registro. Mismo control de acceso por `clave`.

### `POST wj-includes/api/transcribir.php`

`multipart/form-data` con `token` (CSRF) y `audio` (el dictado grabado en el navegador).
Devuelve `{ "ok": true, "texto": "…", "motor": "ElevenLabs" }`.

Controles antes de gastar créditos: tamaño máximo de 10 MB, el tipo se reconoce por los
primeros bytes del archivo (`musa_tipo_audio()`, no por lo que declara el cliente), el
temporal se borra en cuanto se lee, y `musa_limite_uso()` corta a N transcripciones por
hora y por IP. El audio nunca se guarda en disco.

---

## 4. Almacenamiento

`wj-content/datos/registros.json.php`

```jsonc
{
  "version": 1,
  "secuencia": 12,            // consecutivo para los códigos
  "actualizado": "2026-09-22T10:26:31-05:00",
  "registros": [ { /* el más reciente primero */ } ]
}
```

Campos de un registro: `id`, `codigo`, `fecha`, `nombre`, `correo`, `telefono`, `ciudad`,
`genero`, `genero_nombre`, `tema`, `dedicatoria`, `creado`, `enviado`, `estado`,
`proveedor`, `letra`, `audio`, `titulo_cancion`, `mensaje`, `fecha_creado`, `fecha_enviado`,
`notas`, `autorizacion`, `ip`, `navegador`, `token`, `actualizado`.

Toda escritura pasa por `musa_registros_transaccion()`, que toma un bloqueo exclusivo
(`flock`) sobre `registros.lock`, lee, aplica el cambio y guarda de forma atómica
(escritura a archivo temporal + `rename`). Así dos visitantes simultáneos no se pisan.

`estado` puede ser `pendiente`, `generando`, `listo` o `error`. Las casillas `creado` y
`enviado` son independientes del estado: la IA las marca al terminar, y el panel permite
cambiarlas a mano en cualquier momento.

---

## 5. La escena 3D (`wj-includes/js/app.js`)

- Un `WebGLRenderer` con fondo transparente sobre el degradado CSS de la página.
- Cada género es un `Group` con dos mallas: el fondo de la tarjeta (textura dibujada en un
  `<canvas>` 2D: rectángulo redondeado, nombre y descripción) y el retrato del personaje.
- **La posición manda desde el DOM.** `cajasDeBotones()` calcula la rejilla en píxeles CSS,
  coloca los botones accesibles y convierte esas mismas cajas a unidades de mundo con
  `pantallaAMundo()`. Así el botón invisible y la tarjeta 3D ocupan exactamente el mismo
  lugar: el ratón, el teclado y los lectores de pantalla trabajan sobre HTML real y three.js
  solo pone la profundidad y el movimiento.
- Un `ResizeObserver` sobre la zona de géneros recoloca todo cuando el panel cambia de alto
  al pasar de un paso a otro.
- Partículas (`THREE.Points`) que reaccionan al volumen de la canción mediante un
  `AnalyserNode` conectado al reproductor.
- `renderOrder` fijo (fondo 1, retrato 2; la tarjeta elegida 10 y 11) porque las mallas son
  transparentes y al girar cambiaría el orden por distancia.
- Respeta `prefers-reduced-motion`: sin flotación ni giros.

Si `THREE` no está disponible, WebGL falla o los efectos 3D están apagados en el panel,
`document.body` recibe la clase `sin-webgl` y la misma lista de botones se muestra como
rejilla CSS con las imágenes y los colores configurados.

---

## 6. Dictado por voz

Dos motores, escogidos en el navegador por `Dictado.elegirMotor()` según el modo configurado:

1. **Navegador** (`SpeechRecognition` / `webkitSpeechRecognition`): sin costo ni servidor.
   Muestra el texto provisional en la línea de estado y añade al textarea solo los tramos
   finales. Si el motor falla con un error de servicio y hay API disponible, cambia solo al
   segundo motor.
2. **Servidor** (`MediaRecorder` → `api/transcribir.php`): graba en `audio/webm`, corta a
   los segundos configurados y envía el audio a ElevenLabs (`/v1/speech-to-text`) o a
   Google (`generateContent` con el audio en `inlineData`). Si el proveedor principal
   falla, se intenta con el otro.

El botón solo se muestra si alguno de los dos motores puede funcionar de verdad, y el
espacio para él en el textarea se reserva con la clase `con-dictado` para no dejar un
hueco cuando el dictado está desactivado. El texto reconocido se añade al final de lo ya
escrito respetando `maxlength`, y se dispara un evento `input` para que el contador y la
validación del paso 1 se actualicen solos.

Requisitos del navegador: contexto seguro (HTTPS o localhost) y permiso de micrófono.

## 7. Integración con las APIs

### ElevenLabs
`POST {endpoint}/v1/music?output_format=mp3_44100_128` con la cabecera `xi-api-key` y el
cuerpo `{ prompt, music_length_ms, model_id }`. Devuelve el MP3 directamente.

La verificación consulta `/v1/user/subscription`. Una clave con permisos acotados (solo
música) responde `401` con `status: missing_permissions`: el sistema lo interpreta como
**clave válida con permisos limitados**, no como error, y sugiere la prueba de generación.

### Google
- Letra: `POST {endpoint}/v1beta/models/{modelo}:generateContent` con `x-goog-api-key`.
  El modelo predeterminado es `gemini-3.6-flash`; el panel ofrece la lista real de modelos
  disponibles para la clave configurada.
- Audio (opcional): el mismo método sobre un modelo de música (`lyria-3.5`), leyendo el
  audio de `inlineData`. Requiere plan con cupo para ese modelo.

Las llamadas de generación se reintentan hasta 3 veces con espera creciente cuando la
respuesta es `429` o `5xx` (los modelos devuelven `503` cuando están saturados).

---

## 8. Correo

`musa_correo_enviar()` arma un mensaje MIME `multipart/alternative` (texto + HTML con la
identidad de Musa Café) y, si el MP3 pesa menos del máximo configurado, lo envuelve en un
`multipart/mixed` con el adjunto.

Dos caminos de salida: la función `mail()` de PHP o un cliente SMTP propio
(`musa_correo_smtp()`) que habla el protocolo por sockets con `STARTTLS` o `SSL` y
`AUTH LOGIN`. No hay dependencias externas.

---

## 9. Convenciones de código

- Funciones y variables en español, con el prefijo `musa_` en PHP.
- Sintaxis compatible con PHP 7.4: `array()`, sin tipos de retorno nuevos, sin `match`.
- JavaScript en ES5 dentro de una IIFE, sin herramientas de compilación.
- Todo lo que sale al HTML pasa por `musa_e()`; todo lo que entra, por `musa_texto()`,
  `musa_color()`, `musa_correo_valido()` o `musa_ruta_imagen_valida()`.
- Los ajustes se leen y escriben con notación de puntos:
  `musa_dato($ajustes, 'ia.google.modelo')` y `musa_fijar($nuevos, 'ia.proveedor', 'ambos')`.

---

## 10. Cómo añadir algo

| Quiero… | Dónde |
|---|---|
| Un campo nuevo en el formulario | `index.php` (HTML), `wj-includes/api/registro.php` (validación), `musa_registro_base()` y el listado de `wj-admin/index.php`. |
| Otro proveedor de IA | `musa_ia_proveedores()`, una función `musa_ia_generar_*()` y el caso correspondiente en `musa_ia_generar()`. |
| Otro idioma | Los textos ya están en los ajustes; basta con duplicar el archivo de configuración. |
| Cambiar la disposición de la grilla | `cajasDeBotones()` en `wj-includes/js/app.js`. |
| Dictado en otro campo | Reutilizar el módulo `Dictado` apuntando `area` y `boton` al nuevo campo. |

---

Gobernación de Nariño · Musa Café · versión 1.0.0
