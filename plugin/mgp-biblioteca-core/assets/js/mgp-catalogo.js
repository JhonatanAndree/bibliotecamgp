/**
 * Filtros de categoría + buscador de la página /catalogo/.
 * Vanilla JS (sin jQuery): más liviano, sin dependencias extra que pesen
 * en el hosting. Usa fetch() nativo del navegador contra admin-ajax.php.
 *
 * Identificación del chip de categoría: Elementor en su versión GRATUITA
 * no tiene el panel de "Atributos personalizados" (eso es de la versión
 * de pago), así que en vez de pedir un atributo data-mgp-categoria a
 * mano, cada chip se identifica por una CLASE CSS de la lista de abajo
 * — el campo "Clases CSS" del panel Avanzado SÍ existe en la versión
 * gratuita de Elementor, en cualquier widget. Si en el futuro se agrega
 * una categoría nueva, solo hay que sumar una entrada aquí.
 */
( function () {
	'use strict';

	var MAPA_CLASE_A_SLUG = {
		'cat-todos': 'todos',
		'cat-computacion': 'computacion-e-informatica',
		'cat-contabilidad': 'contabilidad',
		'cat-mecanica': 'mecanica-de-produccion',
	};

	document.addEventListener( 'DOMContentLoaded', function () {
		var contenedor = document.querySelector( '.g-mgp-row-wrap' ); // Grilla de tarjetas.
		var chips       = document.querySelectorAll( '.g-mgp-cat-card' );
		var buscador     = document.querySelector( '.g-mgp-search-slot input' );

		if ( ! contenedor || typeof mgpBiblioteca === 'undefined' ) {
			return; // No estamos en la página del catálogo, no hacer nada.
		}

		var categoriaActual = 'todos';
		var temporizadorBusqueda = null;

		function pedirCatalogo() {
			var datos = new FormData();
			datos.append( 'action', 'mgp_filtrar_catalogo' );
			datos.append( 'nonce', mgpBiblioteca.nonce );
			datos.append( 'categoria', categoriaActual );
			datos.append( 'busqueda', buscador ? buscador.value : '' );

			fetch( mgpBiblioteca.ajaxUrl, { method: 'POST', body: datos, credentials: 'same-origin' } )
				.then( function ( respuesta ) { return respuesta.json(); } )
				.then( function ( json ) {
					if ( json.success ) {
						contenedor.innerHTML = json.data.html;
					}
				} )
				.catch( function () {
					// Fallo de red: dejar las tarjetas actuales visibles en vez de romper la página.
				} );
		}

		chips.forEach( function ( chip ) {
			chip.addEventListener( 'click', function ( evento ) {
				evento.preventDefault();

				// Busca en las clases del chip cuál de las categorías
				// conocidas trae encima (ej. "g-mgp-cat-card cat-contabilidad").
				var slugEncontrado = 'todos';
				for ( var clase in MAPA_CLASE_A_SLUG ) {
					if ( chip.classList.contains( clase ) ) {
						slugEncontrado = MAPA_CLASE_A_SLUG[ clase ];
						break;
					}
				}

				categoriaActual = slugEncontrado;
				pedirCatalogo();
			} );
		} );

		if ( buscador ) {
			// "Debounce": espera 400ms de silencio antes de buscar, para no
			// disparar una petición por cada letra tecleada.
			buscador.addEventListener( 'input', function () {
				clearTimeout( temporizadorBusqueda );
				temporizadorBusqueda = setTimeout( pedirCatalogo, 400 );
			} );
		}

		// Carga inicial: pintar los libros reales apenas se abre la página,
		// en vez de dejar la grilla vacía hasta el primer clic/búsqueda.
		pedirCatalogo();
	} );
}() );
