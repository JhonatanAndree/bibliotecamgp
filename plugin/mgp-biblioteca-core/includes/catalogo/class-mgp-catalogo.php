<?php
/**
 * Clase: MGP_Catalogo
 *
 * Endpoint AJAX que conecta los chips de filtro y el buscador de la página
 * /catalogo/ (ya diseñados en Elementor) con los libros reales. Devuelve
 * HTML ya armado con las mismas clases CSS del diseño real (mgp-book-card,
 * mgp-tag, etc. — sin prefijo "g-", ver nota de v0.5.1 en readme.txt) para
 * no tocar el diseño, solo llenarlo con datos.
 *
 * @package MGP_Biblioteca_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MGP_Catalogo {

	public function registrar_hooks(): void {
		// nopriv: el catálogo es visible sin iniciar sesión (leer libros
		// restringidos sí pide login, pero VER el catálogo no).
		add_action( 'wp_ajax_mgp_filtrar_catalogo', array( $this, 'manejar_peticion' ) );
		add_action( 'wp_ajax_nopriv_mgp_filtrar_catalogo', array( $this, 'manejar_peticion' ) );
	}

	public function manejar_peticion(): void {
		if ( ! MGP_Seguridad::verificar_nonce_ajax() ) {
			wp_send_json_error( array( 'mensaje' => 'Nonce inválido.' ), 403 );
		}

		if ( ! MGP_Seguridad::limite_de_peticiones( 'catalogo', 30, 60 ) ) {
			wp_send_json_error( array( 'mensaje' => 'Demasiadas peticiones, espera un momento.' ), 429 );
		}

		$categoria = isset( $_POST['categoria'] ) ? MGP_Seguridad::sanitizar_slug_categoria( sanitize_text_field( wp_unslash( $_POST['categoria'] ) ) ) : '';
		$busqueda  = isset( $_POST['busqueda'] ) ? sanitize_text_field( wp_unslash( $_POST['busqueda'] ) ) : '';

		$clave_cache = md5( $categoria . '|' . $busqueda );
		$html_cacheado = MGP_Cache::get( 'catalogo', $clave_cache );

		if ( false !== $html_cacheado ) {
			wp_send_json_success( array( 'html' => $html_cacheado, 'cache' => true ) );
		}

		$html = $this->generar_html( $categoria, $busqueda );
		MGP_Cache::set( 'catalogo', $clave_cache, $html );

		wp_send_json_success( array( 'html' => $html, 'cache' => false ) );
	}

	private function generar_html( string $categoria, string $busqueda ): string {
		$argumentos = array(
			'post_type'      => 'libro',
			'post_status'    => 'publish',
			'posts_per_page' => 30, // Límite fijo: evita cargar cientos de tarjetas de una vez.
			'no_found_rows'  => true, // No calcular el total (más rápido, no hay paginación numerada aún).
		);

		if ( '' !== $categoria ) {
			$argumentos['tax_query'] = array(
				array(
					'taxonomy' => 'categoria_tecnica',
					'field'    => 'slug',
					'terms'    => $categoria,
				),
			);
		}

		if ( '' !== $busqueda ) {
			$argumentos['s'] = $busqueda;
		}

		$consulta = new WP_Query( $argumentos );

		if ( ! $consulta->have_posts() ) {
			return '<p class="mgp-page-sub">' . esc_html__( 'No se encontraron libros con ese filtro.', 'mgp-biblioteca' ) . '</p>';
		}

		ob_start();
		while ( $consulta->have_posts() ) {
			$consulta->the_post();
			include MGP_BIB_PATH . 'includes/catalogo/template-tarjeta-libro.php';
		}
		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Slug de taxonomía -> clase CSS global de color para el "chip" de
	 * categoría sobre la portada (mgp-tag-comp, mgp-tag-conta...). Estas
	 * clases con color viven en Elementor (Clases globales), no en este
	 * plugin — si en el futuro se agrega una carrera técnica nueva, hay que
	 * (1) sumar su entrada aquí y (2) crear la clase global mgp-tag-{codigo}
	 * en Elementor con un color propio, o el tag se verá sin color (solo la
	 * forma base de mgp-tag, sin fondo).
	 *
	 * "mecanica-de-produccion" -> mgp-tag-mec: la clase todavía NO existe en
	 * Elementor (no se creó color para Mecánica en el diseño original,
	 * solo para Computación y Contabilidad) — ver MEMORIA.md §17.
	 */
	public static function clase_tag_categoria( string $slug ): string {
		$mapa = array(
			'computacion-e-informatica' => 'mgp-tag-comp',
			'contabilidad'              => 'mgp-tag-conta',
			'mecanica-de-produccion'    => 'mgp-tag-mec',
		);

		return $mapa[ $slug ] ?? '';
	}
}
