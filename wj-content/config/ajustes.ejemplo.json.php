<?php http_response_code(403); exit; ?>
{
    "marca": {
        "nombre": "Musa Café",
        "eslogan": "Tu historia hecha canción",
        "entidad": "Gobernación de Nariño",
        "titulo_sitio": "Musa Café · Crea tu canción",
        "descripcion": "Elige un género, cuéntanos tu historia y recibe tu canción en el correo.",
        "logo": "wj-includes/images/optimizadas/logo_musacafe.png",
        "fondo": "wj-includes/images/optimizadas/bg.png",
        "barra": "wj-includes/images/optimizadas/bg_barra.png",
        "favicon": "wj-includes/images/optimizadas/logo_musacafe.png",
        "sitio_entidad": "https://www.narino.gov.co"
    },
    "colores": {
        "fondo": "#AE1D2C",
        "fondo_profundo": "#7E0E1C",
        "tarjeta": "#9F1427",
        "tarjeta_borde": "#C3364A",
        "tarjeta_activa": "#F6EDD9",
        "texto": "#F7EFE0",
        "texto_suave": "#EBC9CE",
        "texto_activo": "#7E0E1C",
        "acento": "#F2B705",
        "acento_secundario": "#12A5C4",
        "exito": "#2E9E6B",
        "error": "#FFB3BC"
    },
    "textos": {
        "titulo": "Crea tu canción",
        "subtitulo": "Elige el género, cuéntanos de qué se trata y te la enviamos al correo.",
        "paso1_titulo": "¿En qué género quieres que suene?",
        "paso1_ayuda": "Selecciona un género para continuar",
        "tema_etiqueta": "Cuéntanos la historia de tu canción",
        "tema_ejemplo": "Un café compartido en Pasto una tarde de lluvia, entre amigos que no se veían hace años…",
        "paso2_titulo": "¿A nombre de quién la componemos?",
        "paso2_ayuda": "Enviaremos la canción terminada a este correo.",
        "boton_continuar": "Continuar",
        "boton_enviar": "Componer mi canción",
        "generando": "Estamos componiendo tu canción…",
        "listo_titulo": "¡Listo! Tu canción va en camino",
        "listo_texto": "Te avisaremos al correo apenas esté lista. Guarda tu código de seguimiento.",
        "aviso_datos": "Autorizo el tratamiento de mis datos personales conforme a la Ley 1581 de 2012 y la política de la Gobernación de Nariño.",
        "pie": "Gobernación de Nariño · Musa Café"
    },
    "generos": [
        {
            "id": "pop",
            "nombre": "Pop",
            "descripcion": "brillante y pegajoso",
            "imagen": "wj-includes/images/optimizadas/pop.png",
            "prompt": "pop luminoso y moderno, sintetizadores cálidos, coro contagioso",
            "activo": true,
            "orden": 1
        },
        {
            "id": "rock",
            "nombre": "Rock",
            "descripcion": "eléctrico y potente",
            "imagen": "wj-includes/images/optimizadas/rock.png",
            "prompt": "rock con guitarras eléctricas, batería marcada y energía en el estribillo",
            "activo": true,
            "orden": 2
        },
        {
            "id": "balada",
            "nombre": "Balada",
            "descripcion": "íntima y sentida",
            "imagen": "wj-includes/images/optimizadas/balada.png",
            "prompt": "balada romántica con piano, cuerdas suaves y voz cercana",
            "activo": true,
            "orden": 3
        },
        {
            "id": "cumbia",
            "nombre": "Cumbia",
            "descripcion": "raíz y fiesta",
            "imagen": "wj-includes/images/optimizadas/cumbia.png",
            "prompt": "cumbia colombiana con gaita, acordeón, guacharaca y tambor alegre",
            "activo": true,
            "orden": 4
        },
        {
            "id": "salsa",
            "nombre": "Salsa",
            "descripcion": "caliente y brava",
            "imagen": "wj-includes/images/optimizadas/salsa.png",
            "prompt": "salsa con metales brillantes, piano montuno, timbales y coro sabroso",
            "activo": true,
            "orden": 5
        },
        {
            "id": "jazz",
            "nombre": "Jazz",
            "descripcion": "libre y elegante",
            "imagen": "wj-includes/images/optimizadas/jazz.png",
            "prompt": "jazz suave con piano, contrabajo, escobillas y saxofón improvisando",
            "activo": true,
            "orden": 6
        },
        {
            "id": "hip-hop",
            "nombre": "Hip-Hop",
            "descripcion": "urbano y directo",
            "imagen": "wj-includes/images/optimizadas/hip-hop.png",
            "prompt": "hip hop con beat marcado, bajo profundo y flow narrativo en español",
            "activo": true,
            "orden": 7
        },
        {
            "id": "reggaeton",
            "nombre": "Reggaetón",
            "descripcion": "perreo y calle",
            "imagen": "wj-includes/images/optimizadas/reggaeton.png",
            "prompt": "reggaetón moderno con dembow, sintetizadores y estribillo pegajoso",
            "activo": true,
            "orden": 8
        },
        {
            "id": "electronica",
            "nombre": "Electrónica",
            "descripcion": "sintética y vibrante",
            "imagen": "wj-includes/images/optimizadas/electronica.png",
            "prompt": "electrónica melódica con sintetizadores, bombo constante y atmósfera amplia",
            "activo": true,
            "orden": 9
        },
        {
            "id": "bolero",
            "nombre": "Bolero",
            "descripcion": "nostálgico y romántico",
            "imagen": "wj-includes/images/optimizadas/bolero.png",
            "prompt": "bolero clásico con guitarra, requinto y voz nostálgica",
            "activo": true,
            "orden": 10
        },
        {
            "id": "vallenato",
            "nombre": "Vallenato",
            "descripcion": "juglar y parranda",
            "imagen": "wj-includes/images/optimizadas/vallenato.png",
            "prompt": "vallenato con acordeón, caja y guacharaca, aire de parranda",
            "activo": true,
            "orden": 11
        },
        {
            "id": "bachata",
            "nombre": "Bachata",
            "descripcion": "dulce y bailable",
            "imagen": "wj-includes/images/optimizadas/bachata.png",
            "prompt": "bachata con guitarra requinto, bongó y güira, romántica y bailable",
            "activo": true,
            "orden": 12
        },
        {
            "id": "folk-andino",
            "nombre": "Folk Andino",
            "descripcion": "altiplano y raíz",
            "imagen": "wj-includes/images/optimizadas/folk-andino.png",
            "prompt": "música andina con quena, charango, zampoña y aire de altiplano nariñense",
            "activo": true,
            "orden": 13
        },
        {
            "id": "bossa-nova",
            "nombre": "Bossa Nova",
            "descripcion": "suave y sofisticada",
            "imagen": "wj-includes/images/optimizadas/bossa-nova.png",
            "prompt": "bossa nova con guitarra sincopada, voz susurrada y percusión ligera",
            "activo": true,
            "orden": 14
        },
        {
            "id": "funk",
            "nombre": "Funk",
            "descripcion": "groove y ritmo",
            "imagen": "wj-includes/images/optimizadas/funk.png",
            "prompt": "funk con bajo slap, guitarra wah, metales y groove bailable",
            "activo": true,
            "orden": 15
        },
        {
            "id": "ranchera",
            "nombre": "Ranchera",
            "descripcion": "bravía y sentida",
            "imagen": "wj-includes/images/optimizadas/ranchera.png",
            "prompt": "ranchera mexicana con mariachi, trompetas y voz desgarrada",
            "activo": true,
            "orden": 16
        }
    ],
    "formulario": {
        "pedir_telefono": true,
        "pedir_ciudad": true,
        "pedir_dedicatoria": true,
        "telefono_obligatorio": false,
        "ciudad_obligatoria": false,
        "minimo_tema": 15,
        "maximo_tema": 600
    },
    "ia": {
        "proveedor": "elevenlabs",
        "generacion_automatica": true,
        "elevenlabs": {
            "api_key": "",
            "endpoint": "https://api.elevenlabs.io",
            "modelo": "music_v1",
            "duracion_ms": 60000,
            "formato": "mp3_44100_128"
        },
        "google": {
            "api_key": "",
            "endpoint": "https://generativelanguage.googleapis.com",
            "modelo": "gemini-3.6-flash",
            "modelo_musica": "lyria-3.5",
            "generar_audio": false
        },
        "instruccion_letra": "Eres un compositor colombiano. Escribe la letra de una canción original en español, con título, dos estrofas, un coro que se repita y un puente. Debe ser respetuosa, familiar y emotiva. Menciona de forma natural el café y la región de Nariño solo si encaja con la historia."
    },
    "correo": {
        "activo": true,
        "metodo": "mail",
        "remitente": "no-responder@narino.gov.co",
        "nombre_remitente": "Musa Café · Gobernación de Nariño",
        "responder_a": "",
        "copia_oculta": "",
        "asunto": "Tu canción de Musa Café ya está lista",
        "adjuntar_audio": true,
        "maximo_adjunto_mb": 8,
        "mensaje": "Hola {nombre},\n\nTu canción en género {genero} ya está lista.\n\nHistoria: {tema}\n\nCódigo de seguimiento: {codigo}\n\nGracias por pasar por Musa Café.\nGobernación de Nariño",
        "smtp": {
            "host": "",
            "puerto": 587,
            "seguridad": "tls",
            "usuario": "",
            "clave": ""
        }
    },
    "seguridad": {
        "limite_por_hora": 5,
        "limite_por_dia": 20,
        "exigir_aceptacion": true
    },
    "sistema": {
        "zona_horaria": "America/Bogota",
        "registros_por_pagina": 25,
        "prefijo_codigo": "MUSA",
        "efectos_3d": true
    }
}