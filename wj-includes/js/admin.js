/* Musa Café · Panel de administración */

(function () {
  'use strict';

  var doc = document;

  function $(sel, ctx) { return (ctx || doc).querySelector(sel); }
  function $$(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }

  /* Casillas "Creado" y "Enviado": se guardan al instante. */
  var tabla = $('#tabla-registros');
  if (tabla) {
    var token = tabla.dataset.token;
    $$('.marca', tabla).forEach(function (casilla) {
      casilla.addEventListener('change', function () {
        var etiqueta = casilla.closest('.casilla');
        var texto = etiqueta ? etiqueta.querySelector('span') : null;
        etiqueta.classList.add('ocupada');
        casilla.disabled = true;

        fetch('acciones.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
          body: JSON.stringify({
            token: token,
            accion: 'marcar',
            id: casilla.dataset.id,
            campo: casilla.dataset.campo,
            valor: casilla.checked
          })
        }).then(function (r) { return r.json(); }).then(function (cuerpo) {
          if (!cuerpo.ok) {
            casilla.checked = !casilla.checked;
            alert(cuerpo.mensaje || 'No fue posible guardar el cambio.');
          } else if (texto) {
            texto.textContent = casilla.checked ? 'SÍ' : 'NO';
          }
        }).catch(function () {
          casilla.checked = !casilla.checked;
          alert('No hay conexión con el servidor.');
        }).then(function () {
          etiqueta.classList.remove('ocupada');
          casilla.disabled = false;
        });
      });
    });
  }

  /* Fila de detalle */
  $$('.ver-detalle').forEach(function (boton) {
    boton.addEventListener('click', function () {
      var fila = $('#detalle-' + CSS.escape(boton.dataset.id));
      if (!fila) { return; }
      fila.hidden = !fila.hidden;
      boton.textContent = fila.hidden ? 'Detalle' : 'Ocultar';
    });
  });

  /* Confirmaciones */
  $$('form.confirmar').forEach(function (formulario) {
    formulario.addEventListener('submit', function (evento) {
      if (!window.confirm(formulario.dataset.confirmar || '¿Confirmas esta acción?')) {
        evento.preventDefault();
      }
    });
  });

  /* Selectores de color: muestran el valor en hexadecimal */
  $$('.color input[type="color"]').forEach(function (entrada) {
    entrada.addEventListener('input', function () {
      var codigo = entrada.parentNode.querySelector('code');
      if (codigo) { codigo.textContent = entrada.value.toUpperCase(); }
    });
  });

  /* Añadir un género nuevo */
  var agregar = $('#agregar-genero');
  if (agregar) {
    agregar.addEventListener('click', function () {
      var indice = parseInt(agregar.dataset.siguiente, 10) || 0;
      var lista = $('#lista-generos');
      var plantilla = lista.querySelector('.genero-fila');
      var opciones = plantilla ? plantilla.querySelector('select').innerHTML : '<option value=""></option>';

      var fila = doc.createElement('div');
      fila.className = 'genero-fila';
      fila.innerHTML =
        '<div class="genero-imagen"></div>' +
        '<label>Nombre<input type="text" name="generos[' + indice + '][nombre]" value=""></label>' +
        '<label>Descripción corta<input type="text" name="generos[' + indice + '][descripcion]" value=""></label>' +
        '<label>Imagen<select name="generos[' + indice + '][imagen]">' + opciones + '</select></label>' +
        '<label>Subir imagen<input type="file" name="archivo_genero[' + indice + ']" accept="image/png,image/jpeg,image/gif,image/webp"></label>' +
        '<label class="ancho-total">Descripción musical para la IA<textarea name="generos[' + indice + '][prompt]" rows="2"></textarea></label>' +
        '<label class="interruptor"><input type="checkbox" name="generos[' + indice + '][activo]" checked> Activo</label>';
      lista.appendChild(fila);
      agregar.dataset.siguiente = indice + 1;
      var primero = fila.querySelector('input[type="text"]');
      if (primero) { primero.focus(); }
    });
  }

  /* Tarjetas de proveedor de IA */
  $$('.opcion input[type="radio"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      $$('.opcion').forEach(function (opcion) { opcion.classList.remove('activa'); });
      if (radio.checked) { radio.closest('.opcion').classList.add('activa'); }
    });
  });
})();
