/**
 * Selector visual de PDF para el meta box "Detalles del libro".
 *
 * Usa la librería nativa de medios de WordPress (wp.media) filtrada a
 * archivos PDF, en vez de pedir al bibliotecario que copie/pegue un ID
 * numérico de adjunto a mano. Vanilla JS, sin jQuery, solo se carga en la
 * pantalla de editar/crear un libro (ver MGP_Campos_Libro::encolar_selector_medios).
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var btnSeleccionar = document.getElementById( 'mgp_seleccionar_pdf' );
		var btnQuitar      = document.getElementById( 'mgp_quitar_pdf' );
		var campoId        = document.getElementById( 'mgp_archivo_pdf_id' );
		var preview        = document.getElementById( 'mgp_pdf_preview' );

		if ( ! btnSeleccionar || ! campoId || ! preview || typeof wp === 'undefined' || ! wp.media ) {
			return;
		}

		var frameMedios = null;

		function actualizarPreview( nombreArchivo ) {
			preview.innerHTML = '';
			var icono = document.createElement( 'span' );
			icono.className = 'dashicons dashicons-media-document';
			icono.style.color = '#2271b1';
			preview.appendChild( icono );
			preview.appendChild( document.createTextNode( ' ' + nombreArchivo ) );
		}

		btnSeleccionar.addEventListener( 'click', function ( evento ) {
			evento.preventDefault();

			// Reutiliza el mismo frame en clics repetidos, en vez de crear
			// uno nuevo cada vez (evita fugas de memoria en el navegador).
			if ( frameMedios ) {
				frameMedios.open();
				return;
			}

			frameMedios = wp.media( {
				title: 'Seleccionar PDF del libro',
				button: { text: 'Usar este PDF' },
				library: { type: 'application/pdf' },
				multiple: false,
			} );

			frameMedios.on( 'select', function () {
				var adjunto = frameMedios.state().get( 'selection' ).first().toJSON();
				campoId.value = adjunto.id;
				actualizarPreview( adjunto.filename || adjunto.title || ( 'Adjunto #' + adjunto.id ) );
				btnQuitar.style.display = '';
			} );

			frameMedios.open();
		} );

		if ( btnQuitar ) {
			btnQuitar.addEventListener( 'click', function ( evento ) {
				evento.preventDefault();
				campoId.value = '';
				preview.innerHTML = '<em>Ningún archivo seleccionado.</em>';
				btnQuitar.style.display = 'none';
			} );
		}
	} );
} )();
