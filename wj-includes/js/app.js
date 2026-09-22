/* Musa Café · Experiencia interactiva
   Escena 3D con three.js + flujo de registro.
   Requiere window.MUSA_CONFIG (lo inyecta index.php). */

(function () {
  'use strict';

  var CFG = window.MUSA_CONFIG || {};
  var COL = CFG.colores || {};
  var GENEROS = CFG.generos || [];
  var doc = document;

  function $(sel, ctx) { return (ctx || doc).querySelector(sel); }
  function $$(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }
  function limitar(v, min, max) { return Math.max(min, Math.min(max, v)); }
  function menosMovimiento() {
    return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  /* ============================================================ Escena 3D */

  var Escena = (function () {
    var THREE = window.THREE;
    var lienzo, render, escena, camara, reloj;
    var grupo, tarjetas = [], particulas = null, materialParticulas = null;
    var anchoP = 0, altoP = 0, upp = 1, distancia = 620;
    var puntero = { x: 0, y: 0, activo: false };
    var animando = false, cuadro = 0;
    var analizador = null, buffer = null, nivelAudio = 0;
    var seleccionado = null, etapa = 'generos';
    var centroGrilla = null;

    function disponible() {
      if (!THREE || !CFG.efectos3d) { return false; }
      try {
        var prueba = doc.createElement('canvas');
        return !!(window.WebGLRenderingContext &&
          (prueba.getContext('webgl2') || prueba.getContext('webgl') || prueba.getContext('experimental-webgl')));
      } catch (e) { return false; }
    }

    /* Dibuja el fondo de una tarjeta (rectángulo redondeado + textos). */
    function pintarTarjeta(t) {
      var anchoCSS = Math.max(60, t.ancho), altoCSS = Math.max(40, t.alto);
      var dpr = Math.min(2, window.devicePixelRatio || 1);
      var c = t.lienzo, ctx = c.getContext('2d');
      c.width = Math.round(anchoCSS * dpr);
      c.height = Math.round(altoCSS * dpr);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      ctx.clearRect(0, 0, anchoCSS, altoCSS);

      var sel = t.seleccionada;
      var radio = Math.min(18, altoCSS * 0.28);
      var x = 1, y = 1, w = anchoCSS - 2, h = altoCSS - 2;

      ctx.beginPath();
      if (ctx.roundRect) { ctx.roundRect(x, y, w, h, radio); }
      else {
        ctx.moveTo(x + radio, y);
        ctx.arcTo(x + w, y, x + w, y + h, radio);
        ctx.arcTo(x + w, y + h, x, y + h, radio);
        ctx.arcTo(x, y + h, x, y, radio);
        ctx.arcTo(x, y, x + w, y, radio);
      }
      ctx.closePath();

      if (sel) {
        var grad = ctx.createLinearGradient(0, 0, 0, altoCSS);
        grad.addColorStop(0, COL.tarjetaActiva || '#F6EDD9');
        grad.addColorStop(1, COL.acento || '#F2B705');
        ctx.fillStyle = grad;
      } else {
        ctx.fillStyle = COL.tarjeta || '#9F1427';
      }
      ctx.fill();
      ctx.lineWidth = sel ? 2 : 1;
      ctx.strokeStyle = sel ? (COL.acento || '#F2B705') : (COL.tarjetaBorde || '#C3364A');
      ctx.stroke();

      var margen = t.lado || Math.min(altoCSS * 1.2, anchoCSS * 0.34);
      var xTexto = margen * 0.9 + 8;
      var anchoLibre = anchoCSS - xTexto - 12;
      if (anchoLibre < 40) { xTexto = 14; anchoLibre = anchoCSS - 26; }

      var tamNombre = limitar(Math.round(altoCSS * 0.26), 12, 19);
      var tamMood = limitar(Math.round(altoCSS * 0.17), 9, 13);

      ctx.textBaseline = 'alphabetic';
      ctx.fillStyle = sel ? (COL.textoActivo || '#7E0E1C') : (COL.texto || '#F7EFE0');
      ctx.font = '600 ' + tamNombre + 'px Poppins, "Segoe UI", system-ui, sans-serif';
      ctx.fillText(recortar(ctx, t.datos.nombre, anchoLibre), xTexto, altoCSS / 2 + (t.datos.descripcion ? -2 : tamNombre * 0.35));

      if (t.datos.descripcion) {
        ctx.fillStyle = sel ? 'rgba(0,0,0,.55)' : (COL.textoSuave || '#EBC9CE');
        ctx.font = '400 ' + tamMood + 'px Poppins, "Segoe UI", system-ui, sans-serif';
        ctx.fillText(recortar(ctx, t.datos.descripcion, anchoLibre), xTexto, altoCSS / 2 + tamMood + 4);
      }

      if (t.textura) { t.textura.needsUpdate = true; }
    }

    function recortar(ctx, texto, ancho) {
      texto = String(texto || '');
      if (ctx.measureText(texto).width <= ancho) { return texto; }
      while (texto.length > 1 && ctx.measureText(texto + '…').width > ancho) {
        texto = texto.slice(0, -1);
      }
      return texto + '…';
    }

    function texturaNota() {
      var c = doc.createElement('canvas');
      c.width = c.height = 96;
      var ctx = c.getContext('2d');
      var grad = ctx.createRadialGradient(48, 48, 2, 48, 48, 46);
      grad.addColorStop(0, 'rgba(255,255,255,.95)');
      grad.addColorStop(.45, 'rgba(255,255,255,.35)');
      grad.addColorStop(1, 'rgba(255,255,255,0)');
      ctx.fillStyle = grad;
      ctx.fillRect(0, 0, 96, 96);
      ctx.font = '700 46px serif';
      ctx.fillStyle = 'rgba(255,255,255,.92)';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText('♪', 48, 50);
      var tex = new THREE.CanvasTexture(c);
      tex.needsUpdate = true;
      return tex;
    }

    function crearParticulas() {
      var total = window.innerWidth < 760 ? 70 : 150;
      var geo = new THREE.BufferGeometry();
      var pos = new Float32Array(total * 3);
      var datos = new Float32Array(total * 3); // velocidad, fase, escala
      for (var i = 0; i < total; i++) {
        pos[i * 3] = (Math.random() - 0.5) * 1600;
        pos[i * 3 + 1] = (Math.random() - 0.5) * 900;
        pos[i * 3 + 2] = -260 - Math.random() * 420;
        datos[i * 3] = 8 + Math.random() * 26;
        datos[i * 3 + 1] = Math.random() * Math.PI * 2;
        datos[i * 3 + 2] = 0.6 + Math.random() * 1.6;
      }
      geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
      geo.userData.datos = datos;

      materialParticulas = new THREE.PointsMaterial({
        size: 22,
        map: texturaNota(),
        transparent: true,
        opacity: 0.5,
        depthWrite: false,
        depthTest: false,
        blending: THREE.AdditiveBlending,
        sizeAttenuation: true
      });
      particulas = new THREE.Points(geo, materialParticulas);
      particulas.renderOrder = -1;
      escena.add(particulas);
    }

    function crearTarjetas() {
      var cargador = new THREE.TextureLoader();
      GENEROS.forEach(function (genero, indice) {
        var grupoTarjeta = new THREE.Group();

        var lienzoTarjeta = doc.createElement('canvas');
        lienzoTarjeta.width = 512; lienzoTarjeta.height = 160;
        var textura = new THREE.CanvasTexture(lienzoTarjeta);
        textura.anisotropy = render.capabilities.getMaxAnisotropy ? render.capabilities.getMaxAnisotropy() : 1;
        if ('colorSpace' in textura) { textura.colorSpace = THREE.SRGBColorSpace; }
        else if ('encoding' in textura) { textura.encoding = THREE.sRGBEncoding; }

        var fondo = new THREE.Mesh(
          new THREE.PlaneGeometry(1, 1),
          new THREE.MeshBasicMaterial({ map: textura, transparent: true, depthWrite: false, depthTest: false })
        );
        fondo.renderOrder = 1;
        grupoTarjeta.add(fondo);

        var retrato = null;
        if (genero.imagen) {
          var texturaRetrato = cargador.load(genero.imagen, function () { pedirCuadro(); });
          if ('colorSpace' in texturaRetrato) { texturaRetrato.colorSpace = THREE.SRGBColorSpace; }
          else if ('encoding' in texturaRetrato) { texturaRetrato.encoding = THREE.sRGBEncoding; }
          retrato = new THREE.Mesh(
            new THREE.PlaneGeometry(1, 1),
            new THREE.MeshBasicMaterial({ map: texturaRetrato, transparent: true, depthWrite: false, depthTest: false })
          );
          retrato.position.z = 6;
          retrato.renderOrder = 2;
          grupoTarjeta.add(retrato);
        }

        grupo.add(grupoTarjeta);
        tarjetas.push({
          datos: genero,
          indice: indice,
          grupo: grupoTarjeta,
          fondo: fondo,
          retrato: retrato,
          lienzo: lienzoTarjeta,
          textura: textura,
          ancho: 200, alto: 70,
          hover: 0, hoverMeta: 0,
          seleccionada: false, sel: 0, selMeta: 0,
          opacidad: 1, opacidadMeta: 1,
          fase: Math.random() * Math.PI * 2,
          destino: new THREE.Vector3(),
          escala: 1, escalaMeta: 1
        });
      });
    }

    function pantallaAMundo(px, py, salida) {
      salida.x = (px - anchoP / 2) * upp;
      salida.y = (altoP / 2 - py) * upp;
      salida.z = 0;
      return salida;
    }

    function medir() {
      anchoP = window.innerWidth;
      altoP = window.innerHeight;
      render.setSize(anchoP, altoP, false);
      render.setPixelRatio(Math.min(2, window.devicePixelRatio || 1));
      camara.aspect = anchoP / altoP;
      camara.updateProjectionMatrix();
      var alturaVisible = 2 * distancia * Math.tan((camara.fov * Math.PI / 180) / 2);
      upp = alturaVisible / altoP;
    }

    /* Coloca las tarjetas 3D exactamente sobre los botones accesibles del DOM. */
    function disponer(cajas) {
      if (!animando) { return; }
      medir();
      cajas.forEach(function (caja, i) {
        var t = tarjetas[i];
        if (!t) { return; }
        var cambioTamano = Math.abs(t.ancho - caja.ancho) > 1 || Math.abs(t.alto - caja.alto) > 1;
        t.ancho = caja.ancho;
        t.alto = caja.alto;
        pantallaAMundo(caja.x + caja.ancho / 2, caja.y + caja.alto / 2, t.destino);
        t.fondo.scale.set(caja.ancho * upp, caja.alto * upp, 1);
        var lado = Math.min(caja.alto * 1.2, caja.ancho * 0.34);
        if (Math.abs((t.lado || 0) - lado) > 0.5) { t.lado = lado; cambioTamano = true; }
        if (t.retrato) {
          t.retrato.scale.set(lado * upp, lado * upp, 1);
          t.retrato.position.x = (-caja.ancho / 2 + lado * 0.45) * upp;
          t.retrato.position.y = caja.alto * 0.08 * upp;
        }
        if (cambioTamano) { pintarTarjeta(t); }
        t.grupo.position.copy(t.destino);
      });

      if (!centroGrilla) { centroGrilla = new THREE.Vector3(); }
      if (cajas.length) {
        var x1 = Infinity, y1 = Infinity, x2 = -Infinity, y2 = -Infinity;
        cajas.forEach(function (caja) {
          x1 = Math.min(x1, caja.x); y1 = Math.min(y1, caja.y);
          x2 = Math.max(x2, caja.x + caja.ancho); y2 = Math.max(y2, caja.y + caja.alto);
        });
        pantallaAMundo((x1 + x2) / 2, (y1 + y2) / 2, centroGrilla);
      }
      pedirCuadro();
    }

    function marcarSeleccion(id) {
      seleccionado = id;
      tarjetas.forEach(function (t) {
        var antes = t.seleccionada;
        t.seleccionada = (t.datos.id === id);
        t.selMeta = t.seleccionada ? 1 : 0;
        if (antes !== t.seleccionada) { pintarTarjeta(t); }
      });
      actualizarEnfoque();
    }

    function marcarHover(id, valor) {
      tarjetas.forEach(function (t) {
        if (t.datos.id === id) { t.hoverMeta = valor ? 1 : 0; }
      });
      pedirCuadro();
    }

    function actualizarEnfoque() {
      tarjetas.forEach(function (t) {
        t.fondo.renderOrder = t.seleccionada ? 10 : 1;
        if (t.retrato) { t.retrato.renderOrder = t.seleccionada ? 11 : 2; }
        if (etapa === 'generos') {
          t.opacidadMeta = 1;
          t.escalaMeta = t.seleccionada ? 1.04 : 1;
        } else if (etapa === 'datos') {
          t.opacidadMeta = t.seleccionada ? 1 : 0.28;
          t.escalaMeta = t.seleccionada ? 1.06 : 0.97;
        } else {
          t.opacidadMeta = t.seleccionada ? 1 : 0.12;
          t.escalaMeta = t.seleccionada ? 1.3 : 0.88;
        }
      });
      pedirCuadro();
    }

    function fijarEtapa(nueva) {
      etapa = nueva;
      actualizarEnfoque();
    }

    function conectarAudio(elemento) {
      try {
        var Contexto = window.AudioContext || window.webkitAudioContext;
        if (!Contexto || !elemento) { return; }
        var ctx = new Contexto();
        var fuente = ctx.createMediaElementSource(elemento);
        analizador = ctx.createAnalyser();
        analizador.fftSize = 64;
        buffer = new Uint8Array(analizador.frequencyBinCount);
        fuente.connect(analizador);
        analizador.connect(ctx.destination);
        elemento.addEventListener('play', function () {
          if (ctx.state === 'suspended') { ctx.resume(); }
          pedirCuadro();
        });
      } catch (e) { analizador = null; }
    }

    function nivel() {
      if (!analizador) { return 0; }
      analizador.getByteFrequencyData(buffer);
      var suma = 0;
      for (var i = 0; i < buffer.length; i++) { suma += buffer[i]; }
      return (suma / buffer.length) / 255;
    }

    function pedirCuadro() {
      if (!animando || cuadro) { return; }
      cuadro = requestAnimationFrame(animar);
    }

    function animar() {
      cuadro = 0;
      var t = reloj.getElapsedTime();
      var suave = menosMovimiento() ? 1 : 0.14;
      nivelAudio += (nivel() - nivelAudio) * 0.2;

      var movimiento = false;
      tarjetas.forEach(function (c) {
        c.hover += (c.hoverMeta - c.hover) * suave;
        c.sel += (c.selMeta - c.sel) * suave;
        c.opacidad += (c.opacidadMeta - c.opacidad) * suave;
        c.escala += (c.escalaMeta - c.escala) * suave;

        var flotar = menosMovimiento() ? 0 : Math.sin(t * 0.9 + c.fase) * (2.4 + nivelAudio * 7) * upp;
        var empuje = (c.hover * 26 + c.sel * 34) * upp;
        var meta = (etapa === 'proceso' && c.seleccionada && centroGrilla) ? centroGrilla : c.destino;
        c.grupo.position.x += (meta.x - c.grupo.position.x) * 0.08;
        c.grupo.position.y += (meta.y + flotar - c.grupo.position.y) * 0.08;
        c.grupo.position.z = meta.z + empuje;

        var escala = c.escala * (1 + c.hover * 0.035 + nivelAudio * 0.02 * c.sel);
        c.grupo.scale.set(escala, escala, 1);

        if (puntero.activo && !menosMovimiento()) {
          var dx = (puntero.x - c.grupo.position.x / upp - anchoP / 2) / anchoP;
          var dy = (puntero.y - (altoP / 2 - c.grupo.position.y / upp)) / altoP;
          c.grupo.rotation.y += (limitar(-dx * 0.5, -0.32, 0.32) * (0.25 + c.hover) - c.grupo.rotation.y) * 0.1;
          c.grupo.rotation.x += (limitar(dy * 0.4, -0.3, 0.3) * (0.25 + c.hover) - c.grupo.rotation.x) * 0.1;
        } else {
          c.grupo.rotation.y += (0 - c.grupo.rotation.y) * 0.08;
          c.grupo.rotation.x += (0 - c.grupo.rotation.x) * 0.08;
        }

        if (etapa === 'proceso' && c.seleccionada && !menosMovimiento()) {
          c.grupo.rotation.y = Math.sin(t * 0.8) * 0.5;
        }

        c.fondo.material.opacity = c.opacidad;
        if (c.retrato) { c.retrato.material.opacity = c.opacidad; }
        if (Math.abs(c.hoverMeta - c.hover) > 0.002 || Math.abs(c.selMeta - c.sel) > 0.002 ||
            Math.abs(c.opacidadMeta - c.opacidad) > 0.002 || Math.abs(c.escalaMeta - c.escala) > 0.002) {
          movimiento = true;
        }
      });

      if (particulas) {
        var pos = particulas.geometry.getAttribute('position');
        var datos = particulas.geometry.userData.datos;
        var dt = 0.016;
        for (var i = 0; i < pos.count; i++) {
          var vel = datos[i * 3] * (1 + nivelAudio * 2.2);
          var fase = datos[i * 3 + 1];
          pos.array[i * 3 + 1] += vel * dt * 6;
          pos.array[i * 3] += Math.sin(t * 0.6 + fase) * 0.35;
          if (pos.array[i * 3 + 1] > 520) {
            pos.array[i * 3 + 1] = -520;
            pos.array[i * 3] = (Math.random() - 0.5) * 1600;
          }
        }
        pos.needsUpdate = true;
        materialParticulas.opacity = 0.32 + nivelAudio * 0.4;
        materialParticulas.size = 20 + nivelAudio * 26;
      }

      grupo.rotation.y += ((puntero.activo ? (puntero.x / anchoP - 0.5) * 0.06 : 0) - grupo.rotation.y) * 0.06;
      grupo.rotation.x += ((puntero.activo ? (puntero.y / altoP - 0.5) * 0.04 : 0) - grupo.rotation.x) * 0.06;

      render.render(escena, camara);

      if (movimiento || particulas || analizador || !menosMovimiento()) {
        cuadro = requestAnimationFrame(animar);
      }
    }

    function iniciar() {
      lienzo = $('#escena');
      if (!lienzo || !disponible()) { return false; }
      try {
        render = new THREE.WebGLRenderer({ canvas: lienzo, antialias: true, alpha: true, powerPreference: 'high-performance' });
      } catch (e) { return false; }
      render.setClearColor(0x000000, 0);
      if ('outputColorSpace' in render && THREE.SRGBColorSpace) { render.outputColorSpace = THREE.SRGBColorSpace; }
      else if ('outputEncoding' in render && THREE.sRGBEncoding) { render.outputEncoding = THREE.sRGBEncoding; }
      escena = new THREE.Scene();
      camara = new THREE.PerspectiveCamera(45, 1, 1, 4000);
      camara.position.set(0, 0, distancia);
      reloj = new THREE.Clock();
      grupo = new THREE.Group();
      escena.add(grupo);

      medir();
      crearTarjetas();
      crearParticulas();
      animando = true;

      window.addEventListener('pointermove', function (e) {
        puntero.x = e.clientX; puntero.y = e.clientY; puntero.activo = true;
        pedirCuadro();
      }, { passive: true });
      window.addEventListener('pointerleave', function () { puntero.activo = false; pedirCuadro(); });
      doc.addEventListener('visibilitychange', function () {
        if (!doc.hidden) { pedirCuadro(); }
      });

      pedirCuadro();
      return true;
    }

    return {
      iniciar: iniciar,
      disponer: disponer,
      seleccionar: marcarSeleccion,
      hover: marcarHover,
      etapa: fijarEtapa,
      audio: conectarAudio,
      activa: function () { return animando; }
    };
  })();

  /* ============================================================ Flujo */

  var Flujo = (function () {
    var estado = { paso: 1, genero: null, codigo: '', clave: '' };
    var zona, contenedor, botones = [];
    var vistas = {}, marcasPaso = [];

    function cajasDeBotones() {
      var rect = zona.getBoundingClientRect();
      var total = botones.length;
      if (!total) { return []; }
      var ancho = rect.width, alto = rect.height;
      var columnas = ancho >= 900 ? 4 : (ancho >= 620 ? 3 : 2);
      var filas = Math.ceil(total / columnas);
      var hueco = Math.max(8, Math.min(14, ancho * 0.012));
      var anchoTarjeta = (ancho - hueco * (columnas - 1)) / columnas;
      var altoTarjeta = (alto - hueco * (filas - 1)) / filas;

      return botones.map(function (boton, i) {
        var columna = i % columnas;
        var fila = Math.floor(i / columnas);
        return {
          x: rect.left + columna * (anchoTarjeta + hueco),
          y: rect.top + fila * (altoTarjeta + hueco),
          izquierda: columna * (anchoTarjeta + hueco),
          arriba: fila * (altoTarjeta + hueco),
          ancho: anchoTarjeta,
          alto: altoTarjeta
        };
      });
    }

    function acomodar() {
      if (!zona) { return; }
      var cajas = cajasDeBotones();
      if (!doc.body.classList.contains('sin-webgl')) {
        cajas.forEach(function (caja, i) {
          var boton = botones[i];
          boton.style.width = caja.ancho + 'px';
          boton.style.height = caja.alto + 'px';
          boton.style.transform = 'translate(' + Math.round(caja.izquierda) + 'px,' + Math.round(caja.arriba) + 'px)';
        });
        Escena.disponer(cajas);
      }
    }

    function elegirGenero(id) {
      estado.genero = id;
      botones.forEach(function (boton) {
        boton.setAttribute('aria-pressed', boton.dataset.id === id ? 'true' : 'false');
      });
      if (Escena.activa()) { Escena.seleccionar(id); }
      validarPaso1();
    }

    function validarPaso1() {
      var tema = $('#tema').value.trim();
      var minimo = CFG.minimoTema || 15;
      var listo = !!estado.genero && tema.length >= minimo;
      $('#continuar').disabled = !listo;
      var ayuda = $('#ayuda-paso1');
      if (!estado.genero) { ayuda.textContent = CFG.textos.paso1Ayuda; }
      else if (tema.length < minimo) { ayuda.textContent = 'Cuéntanos un poco más (' + tema.length + '/' + minimo + ' caracteres).'; }
      else { ayuda.textContent = 'Género ' + nombreGenero(estado.genero) + ' · listo para continuar.'; }
      $('#contador-tema').textContent = tema.length + ' / ' + (CFG.maximoTema || 600);
      return listo;
    }

    function nombreGenero(id) {
      for (var i = 0; i < GENEROS.length; i++) {
        if (GENEROS[i].id === id) { return GENEROS[i].nombre; }
      }
      return '';
    }

    function irA(paso) {
      estado.paso = paso;
      Object.keys(vistas).forEach(function (clave) {
        vistas[clave].classList.toggle('activa', Number(clave) === paso);
      });
      marcasPaso.forEach(function (marca, i) {
        marca.classList.toggle('activo', i + 1 === paso);
        marca.classList.toggle('hecho', i + 1 < paso);
      });
      if (Escena.activa()) {
        Escena.etapa(paso === 1 ? 'generos' : (paso === 2 ? 'datos' : 'proceso'));
      }
      botones.forEach(function (boton) { boton.disabled = paso !== 1; });
      var foco = $('.vista.activa input, .vista.activa textarea, .vista.activa button');
      if (foco && paso !== 1) { try { foco.focus({ preventScroll: true }); } catch (e) { foco.focus(); } }
      acomodar();
    }

    function mostrarErrores(errores) {
      $$('.error-campo').forEach(function (nodo) { nodo.textContent = ''; });
      Object.keys(errores || {}).forEach(function (campo) {
        var nodo = $('#error-' + campo);
        if (nodo) { nodo.textContent = errores[campo]; }
      });
    }

    function pedir(url, datos) {
      return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(datos)
      }).then(function (respuesta) {
        return respuesta.json().then(function (cuerpo) {
          return { estado: respuesta.status, cuerpo: cuerpo };
        }).catch(function () {
          return { estado: respuesta.status, cuerpo: { ok: false, mensaje: 'El servidor respondió de forma inesperada.' } };
        });
      });
    }

    function enviar() {
      var datos = {
        token: CFG.token,
        genero: estado.genero,
        tema: $('#tema').value.trim(),
        nombre: $('#nombre').value.trim(),
        correo: $('#correo').value.trim(),
        telefono: $('#telefono') ? $('#telefono').value.trim() : '',
        ciudad: $('#ciudad') ? $('#ciudad').value.trim() : '',
        dedicatoria: $('#dedicatoria') ? $('#dedicatoria').value.trim() : '',
        autorizacion: $('#autorizacion') ? $('#autorizacion').checked : true,
        sitio_web: $('#sitio_web').value
      };

      $('#enviar').disabled = true;
      mostrarErrores({});
      $('#aviso-datos').textContent = '';
      $('#aviso-datos').className = 'aviso';
      $('#aviso-datos').hidden = true;

      pedir(CFG.rutas.registro, datos).then(function (r) {
        $('#enviar').disabled = false;
        if (!r.cuerpo.ok) {
          mostrarErrores(r.cuerpo.errores);
          var aviso = $('#aviso-datos');
          aviso.hidden = false;
          aviso.className = 'aviso error';
          aviso.textContent = r.cuerpo.mensaje || 'No fue posible guardar tu registro.';
          return;
        }
        estado.codigo = r.cuerpo.codigo;
        estado.clave = r.cuerpo.clave;
        $('#codigo-seguimiento').textContent = r.cuerpo.codigo;
        irA(3);
        if (r.cuerpo.generar) {
          generar();
        } else {
          mostrarResultado({ ok: true, mensaje: r.cuerpo.mensaje, titulo: '', letra: '', audio: '' }, false);
        }
      }).catch(function () {
        $('#enviar').disabled = false;
        var aviso = $('#aviso-datos');
        aviso.hidden = false;
        aviso.className = 'aviso error';
        aviso.textContent = 'No hay conexión con el servidor. Inténtalo de nuevo.';
      });
    }

    function generar() {
      var frases = [
        'Afinando los instrumentos…',
        'Escribiendo la letra…',
        'Grabando la voz principal…',
        'Mezclando la canción…'
      ];
      var i = 0;
      var linea = $('#linea-proceso');
      linea.textContent = frases[0];
      var giro = setInterval(function () {
        i = (i + 1) % frases.length;
        linea.textContent = frases[i];
      }, 4200);

      pedir(CFG.rutas.generar, { token: CFG.token, codigo: estado.codigo, clave: estado.clave })
        .then(function (r) {
          clearInterval(giro);
          mostrarResultado(r.cuerpo, true);
        })
        .catch(function () {
          clearInterval(giro);
          consultarEstado(0);
        });
    }

    /* Si la conexión se corta durante una generación larga, consultamos el estado. */
    function consultarEstado(intento) {
      if (intento > 20) {
        mostrarResultado({ ok: false, mensaje: 'La canción sigue en proceso. Te avisaremos por correo cuando esté lista.' }, true);
        return;
      }
      setTimeout(function () {
        fetch(CFG.rutas.estado + '?codigo=' + encodeURIComponent(estado.codigo) + '&clave=' + encodeURIComponent(estado.clave), { credentials: 'same-origin' })
          .then(function (res) { return res.json(); })
          .then(function (cuerpo) {
            if (cuerpo.ok && (cuerpo.estado === 'listo' || cuerpo.estado === 'error')) {
              mostrarResultado({
                ok: cuerpo.estado === 'listo',
                mensaje: cuerpo.mensaje,
                titulo: cuerpo.titulo,
                letra: cuerpo.letra,
                audio: cuerpo.audio
              }, true);
            } else {
              consultarEstado(intento + 1);
            }
          })
          .catch(function () { consultarEstado(intento + 1); });
      }, 6000);
    }

    function mostrarResultado(cuerpo, conIA) {
      $('#proceso').hidden = true;
      var caja = $('#resultado');
      caja.hidden = false;

      $('#titulo-resultado').textContent = cuerpo.ok
        ? (CFG.textos.listoTitulo || '¡Listo!')
        : 'Tu registro quedó guardado';
      $('#texto-resultado').textContent = cuerpo.mensaje || CFG.textos.listoTexto || '';

      if (cuerpo.titulo) {
        $('#titulo-cancion').textContent = cuerpo.titulo;
        $('#titulo-cancion').hidden = false;
      }
      if (cuerpo.letra) {
        $('#letra').textContent = cuerpo.letra;
        $('#letra').hidden = false;
      }
      if (cuerpo.audio) {
        var audio = $('#audio');
        audio.src = cuerpo.audio;
        audio.hidden = false;
        $('#descargar').href = cuerpo.audio;
        $('#descargar').hidden = false;
        if (Escena.activa()) { Escena.audio(audio); }
      }
      if (!conIA) { $('#linea-proceso').textContent = ''; }
    }

    function reiniciar() {
      estado = { paso: 1, genero: null, codigo: '', clave: '' };
      $('#tema').value = '';
      $$('#formulario input, #formulario textarea').forEach(function (campo) {
        if (campo.type === 'checkbox') { campo.checked = false; } else { campo.value = ''; }
      });
      botones.forEach(function (boton) { boton.setAttribute('aria-pressed', 'false'); });
      if (Escena.activa()) { Escena.seleccionar(null); }
      $('#proceso').hidden = false;
      $('#resultado').hidden = true;
      ['titulo-cancion', 'letra', 'audio', 'descargar'].forEach(function (id) {
        var nodo = $('#' + id);
        if (nodo) { nodo.hidden = true; }
      });
      mostrarErrores({});
      validarPaso1();
      irA(1);
    }

    function iniciar() {
      zona = $('#zona-generos');
      contenedor = $('#generos');
      botones = $$('.genero', contenedor);
      vistas = { 1: $('#vista-1'), 2: $('#vista-2'), 3: $('#vista-3') };
      marcasPaso = $$('.paso-marca');

      var conEscena = Escena.iniciar();
      doc.body.classList.add(conEscena ? 'con-webgl' : 'sin-webgl');

      botones.forEach(function (boton) {
        boton.addEventListener('click', function () { elegirGenero(boton.dataset.id); });
        boton.addEventListener('pointerenter', function () { if (conEscena) { Escena.hover(boton.dataset.id, true); } });
        boton.addEventListener('pointerleave', function () { if (conEscena) { Escena.hover(boton.dataset.id, false); } });
        boton.addEventListener('focus', function () { if (conEscena) { Escena.hover(boton.dataset.id, true); } });
        boton.addEventListener('blur', function () { if (conEscena) { Escena.hover(boton.dataset.id, false); } });
      });

      $('#tema').addEventListener('input', validarPaso1);
      $('#continuar').addEventListener('click', function () { if (validarPaso1()) { irA(2); } });
      $('#atras').addEventListener('click', function () { irA(1); });
      $('#formulario').addEventListener('submit', function (e) { e.preventDefault(); enviar(); });
      var otra = $('#otra');
      if (otra) { otra.addEventListener('click', reiniciar); }

      // La zona de géneros cambia de alto según el paso: recolocamos las tarjetas.
      if (window.ResizeObserver) {
        var observador = new ResizeObserver(function () { acomodar(); });
        observador.observe(zona);
      }

      var temporizador;
      window.addEventListener('resize', function () {
        clearTimeout(temporizador);
        temporizador = setTimeout(acomodar, 120);
      });
      window.addEventListener('orientationchange', function () { setTimeout(acomodar, 250); });

      acomodar();
      setTimeout(acomodar, 350);
      if (doc.fonts && doc.fonts.ready) { doc.fonts.ready.then(function () { setTimeout(acomodar, 60); }); }
      validarPaso1();
      irA(1);
    }

    return { iniciar: iniciar, acomodar: acomodar };
  })();

  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', Flujo.iniciar);
  } else {
    Flujo.iniciar();
  }
})();
