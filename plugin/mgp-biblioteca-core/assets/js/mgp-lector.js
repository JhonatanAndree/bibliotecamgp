/**
 * Registra el progreso real de lectura del lector DearFlip Lite embebido
 * en la página individual del libro (templates/single-libro.php).
 *
 * IMPORTANTE — por qué este archivo NO usa las opciones onPageChanged /
 * onFlip / onReady que documenta la API pública de DearFlip (ver el
 * objeto de opciones por defecto en dflip.js): se revisó el JS fuente
 * del plugin directamente en el servidor real (assets/js/dflip.js de
 * 3d-flipbook-dflip-lite) y se confirmó que, en la versión Lite, el
 * método interno que de verdad ejecuta esos callbacks está vacío a
 * propósito:
 *
 *   key: "executeCallback",
 *   value: function executeCallback(callbackName) {}
 *
 * Es decir, esos callbacks existen en la lista de opciones que acepta el
 * lector (para que el código "parezca" configurable), pero la versión
 * Lite nunca los llama — es un candado de la versión de pago, no un bug
 * ni una casualidad. Una versión anterior de este mismo archivo, escrita
 * sin verificar esto, escuchaba un evento de DOM 'dflip.pageFlip' que
 * jamás se dispara en ningún lado del código fuente — nunca pudo haber
 * funcionado.
 *
 * Lo que SÍ funciona, confirmado en el código fuente (dflip.js, método
 * gotoPage() de la clase App, línea ~12950): cada vez que el usuario
 * cambia de página, dflip.js actualiza dos propiedades públicas simples
 * en su propia instancia — app.currentPageNumber (página actual,
 * 1-indexada) y app.pageCount (total de páginas) — ANTES de llamar al
 * executeCallback vacío. Y esa instancia queda guardada en el propio
 * elemento .df-element vía jQuery: options.element.data('df-app', app)
 * (dflip.js línea ~13091) — NO como atributo del DOM, por eso hace falta
 * jQuery (ya cargado: dflip-script depende de 'jquery').
 *
 * Estrategia: sondear (polling) esas dos propiedades cada 2 segundos una
 * vez que el lector esté listo, y reportar el progreso al backend
 * (mgp_actualizar_progreso, ver MGP_Usuario) solo cuando la página
 * cambió Y se estabiliza 1.5s sin un cambio nuevo — así no se manda una
 * petición AJAX por cada hoja que el estudiante pasa rápido buscando
 * dónde se quedó.
 */
( function ( $ ) {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		if ( typeof mgpLector === 'undefined' || typeof $ === 'undefined' ) {
			return;
		}

		var elementoLector = document.querySelector( '.df-element' );
		if ( ! elementoLector || ! mgpLector.libroId ) {
			return;
		}

		var $lector = $( elementoLector );
		var ultimaPaginaReportada = 0;
		var temporizadorReporte   = null;
		var intervaloSondeo       = null;
		var intentosSinApp        = 0;
		// ~5 minutos de espera (150 intentos x 2s): cubre PDFs grandes que
		// tardan en terminar de cargar antes de que exista app.currentPageNumber.
		var MAX_INTENTOS_SIN_APP = 150;

		function reportarProgreso( pagina, total ) {
			if ( ! total || pagina === ultimaPaginaReportada ) {
				return;
			}
			ultimaPaginaReportada = pagina;

			var datos = new FormData();
			datos.append( 'action', 'mgp_actualizar_progreso' );
			datos.append( 'nonce', mgpLector.nonce );
			datos.append( 'libro_id', mgpLector.libroId );
			datos.append( 'pagina', pagina );
			datos.append( 'total', total );

			fetch( mgpLector.ajaxUrl, { method: 'POST', body: datos, credentials: 'same-origin' } )
				.catch( function () {
					// Fallo de red: no es crítico, el próximo cambio de página lo reintenta.
				} );
		}

		function revisarPagina() {
			var app = $lector.data( 'df-app' );
			if ( ! app || ! app.pageCount ) {
				intentosSinApp++;
				if ( intentosSinApp > MAX_INTENTOS_SIN_APP ) {
					clearInterval( intervaloSondeo ); // El lector nunca terminó de cargar: dejar de sondear.
				}
				return;
			}

			var paginaActual = app.currentPageNumber;
			if ( ! paginaActual || paginaActual === ultimaPaginaReportada ) {
				return;
			}

			// Debounce: espera 1.5s de silencio antes de reportar.
			clearTimeout( temporizadorReporte );
			temporizadorReporte = setTimeout( function () {
				reportarProgreso( app.currentPageNumber, app.pageCount );
			}, 1500 );
		}

		intervaloSondeo = setInterval( revisarPagina, 2000 );
	} );
}( window.jQuery ) );
