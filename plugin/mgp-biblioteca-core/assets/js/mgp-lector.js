/**
 * Controles del lector (guardar/quitar de "Mis libros" y reportar progreso
 * de lectura de vuelta al servidor). La renderización del PDF en sí la hace
 * DearFlip Lite; este archivo solo conecta los botones del diseño con el
 * backend del plugin.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		if ( typeof mgpBiblioteca === 'undefined' ) {
			return;
		}

		document.body.addEventListener( 'click', function ( evento ) {
			var boton = evento.target.closest( '.mgp-btn-guardar' );
			if ( ! boton ) {
				return;
			}

			var libroId = boton.getAttribute( 'data-libro-id' );
			var datos = new FormData();
			datos.append( 'action', 'mgp_guardar_libro' );
			datos.append( 'nonce', mgpBiblioteca.nonce );
			datos.append( 'libro_id', libroId );

			fetch( mgpBiblioteca.ajaxUrl, { method: 'POST', body: datos, credentials: 'same-origin' } )
				.then( function ( respuesta ) { return respuesta.json(); } )
				.then( function ( json ) {
					if ( json.success && 'guardado' === json.data.estado ) {
						boton.textContent = 'Guardado';
						boton.classList.add( 'g-mgp-btn-saved' );
					} else if ( json.success ) {
						boton.textContent = 'Guardar';
						boton.classList.remove( 'g-mgp-btn-saved' );
					}
				} );
		} );

		// Ejemplo de integración con el evento de cambio de página de DearFlip.
		// DearFlip dispara 'dflip.pageFlip' en el documento con el número de
		// página actual — se reporta al backend cada vez que el estudiante avanza.
		document.addEventListener( 'dflip.pageFlip', function ( evento ) {
			var lector = document.querySelector( '[data-mgp-libro-id]' );
			if ( ! lector || ! evento.detail ) {
				return;
			}

			var datos = new FormData();
			datos.append( 'action', 'mgp_actualizar_progreso' );
			datos.append( 'nonce', mgpBiblioteca.nonce );
			datos.append( 'libro_id', lector.getAttribute( 'data-mgp-libro-id' ) );
			datos.append( 'pagina', evento.detail.page || 0 );
			datos.append( 'total', evento.detail.totalPages || 0 );

			fetch( mgpBiblioteca.ajaxUrl, { method: 'POST', body: datos, credentials: 'same-origin' } );
		} );
	} );
}() );
