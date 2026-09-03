/**
 * Filtros de categoría + buscador de la página /catalogo/, y botón
 * "Guardar" reutilizado en varias páginas (catálogo, inicio, mis libros).
 * Vanilla JS (sin jQuery): más liviano, sin dependencias extra que pesen
 * en el hosting. Usa fetch() nativo del navegador contra admin-ajax.php.
 *
 * Identificación del chip de categoría: con Elementor PRO, cada chip lleva
 * un atributo personalizado data-mgp-categoria con el slug de la taxonomía
 * directo (ej. data-mgp-categoria="contabilidad") — panel Avanzado >
 * Atributos personalizados de cada widget.
 *
 * Clases CSS del diseño: son las REALES de Elementor (Clases globales),
 * SIN el prefijo "g-" que se usó por error en versiones anteriores del
 * plugin (v0.1.0 a v0.5.0) — ver MEMORIA.md §17. mgp-row-wrap es la grilla
 * de tarjetas y la usan TANTO el catálogo como "Sigue leyendo" en Inicio,
 * así que este script solo activa el filtrado/búsqueda AJAX cuando
 * mgpBiblioteca.pagina === 'catalogo' (dato localizado desde PHP), para no
 * pisar la grilla de Inicio con una petición que no le corresponde.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		if ( typeof mgpBiblioteca === 'undefined' ) {
			return; // El script no debería cargar fuera de estas páginas, pero por si acaso.
		}

		// --- Botón "Guardar": funciona en cualquier página que lo use ------
		// Delegado en document para que también sirva sobre tarjetas
		// insertadas dinámicamente por AJAX (el catálogo reemplaza su HTML
		// completo al filtrar).
		document.addEventListener( 'click', function ( evento ) {
			var boton = evento.target.closest( '.mgp-btn-guardar' );
			if ( ! boton ) {
				return;
			}
			evento.preventDefault();
			alternarGuardado( boton );
		} );

		function alternarGuardado( boton ) {
			var libroId = boton.getAttribute( 'data-libro-id' );
			if ( ! libroId || boton.disabled ) {
				return;
			}

			boton.disabled = true;

			var datos = new FormData();
			datos.append( 'action', 'mgp_guardar_libro' );
			datos.append( 'nonce', mgpBiblioteca.nonce );
			datos.append( 'libro_id', libroId );

			fetch( mgpBiblioteca.ajaxUrl, { method: 'POST', body: datos, credentials: 'same-origin' } )
				.then( function ( respuesta ) { return respuesta.json(); } )
				.then( function ( json ) {
					if ( json.success ) {
						var guardado = 'guardado' === json.data.estado;
						boton.textContent = guardado ? 'Guardado' : 'Guardar';
						boton.classList.toggle( 'mgp-btn-saved', guardado );
					}
				} )
				.catch( function () {
					// Fallo de red: no cambiar el texto del botón, el usuario puede reintentar.
				} )
				.finally( function () {
					boton.disabled = false;
				} );
		}

		// --- Catálogo: filtros por categoría + buscador ---------------------
		if ( 'catalogo' !== mgpBiblioteca.pagina ) {
			return;
		}

		var contenedor = document.querySelector( '.mgp-row-wrap' ); // Grilla de tarjetas.
		var chips      = document.querySelectorAll( '[data-mgp-categoria]' );
		var buscador   = document.querySelector( '.mgp-search-slot input' );

		if ( ! contenedor ) {
			return;
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
				categoriaActual = chip.getAttribute( 'data-mgp-categoria' ) || 'todos';
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
