<?php
/**
 * Musa Café · Claves de API (opcional)
 *
 * Copia este archivo como  wj-content/config/claves.php  y escribe tus claves.
 * Se usan la primera vez que arranca el sitio para dejar las APIs listas sin
 * tener que escribirlas a mano en el panel. A partir de ahí, la configuración
 * vive en wj-content/config/ajustes.json.php y se edita desde wj-admin → APIs.
 *
 * IMPORTANTE: claves.php está excluido del repositorio (.gitignore).
 * Nunca subas claves de API a GitHub: los servicios las revocan al detectarlas.
 */

return array(
    'ia' => array(
        'proveedor'  => 'elevenlabs',           // elevenlabs | google | ambos | ninguno
        'elevenlabs' => array(
            'api_key' => 'sk_TU_CLAVE_DE_ELEVENLABS',
        ),
        'google' => array(
            'api_key' => 'TU_CLAVE_DE_GOOGLE',
        ),
    ),
);
