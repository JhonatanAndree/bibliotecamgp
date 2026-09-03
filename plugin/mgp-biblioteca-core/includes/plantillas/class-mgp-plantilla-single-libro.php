<?php
/**
 * Clase: MGP_Plantilla_Single_Libro
 *
 * Página individual de un libro (biblioteca.mgp.edu.pe/libro/nombre-del-libro/).
 * Muestra la portada, datos del libro y el lector DearFlip embebido, apuntando
 * SIEMPRE a la ruta protegida /leer/{id}/ de MGP_Lector — nunca a la URL
 * directa del adjunto en la media library.
 *
 * Por qué una plantilla propia y no un "Single Post" de Elementor Pro: este
 * sitio solo tiene la versión gratuita (Elementor + Header Footer Elementor),
 * que no incluye Theme Builder para tipos de contenido personalizados. Una
 * plantilla PHP en el propio plugin además cumple el principio ya establecido
 * en este proyecto: el plugin no debe depender de Elementor para funcionar.
 * Sí reutiliza el header/footer del sitio vía get_header()/get_footer(), así
 * que el menú, el pie de página y la sesión del estudiante se ven idénticos
 * al resto del sitio.
 *
 * El color/tipografía/espaciado de esta plantilla replica el sistema de
 * diseño activo del sitio "Biblioteca MGP Nocturna" (ver DESIGN.md vía
 * Novamira) a través de variables CSS en mgp-single-libro.css — no depende
 * de que Elementor esté activo para verse bien.
 *
 * @package MGP_Biblioteca_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MGP_Plantilla_Single_Libro {

	public function registrar_hooks(): void {
		add_filter( 'single_template', array( $this, 'cargar_plantilla' ) );
		// Prioridad 20: DearFlip Lite registra 'dflip-script'/'dflip-style' en
		// 'wp_enqueue_scripts' con prioridad por defecto (10). Encolamos
		// después para que el handle ya exista cuando lo pedimos aquí.
		add_action( 'wp_enqueue_scripts', array( $this, 'encolar_assets' ), 20 );
	}

	/**
	 * Sustituye la plantilla "single" de WordPress por la nuestra SOLO para
	 * el CPT `libro`. Cualquier otro tipo de contenido sigue su plantilla
	 * normal del tema — esta clase no toca nada fuera de su alcance.
	 */
	public function cargar_plantilla( string $plantilla ): string {
		if ( is_singular( 'libro' ) ) {
			$plantilla_propia = MGP_BIB_PATH . 'templates/single-libro.php';
			if ( file_exists( $plantilla_propia ) ) {
				return $plantilla_propia;
			}
		}
		return $plantilla;
	}

	/**
	 * Encola el lector DearFlip y nuestros estilos SOLO en la página de un
	 * libro — nunca en el resto del sitio (1GB de RAM: cada script/estilo
	 * de más cargado en todas las páginas es memoria y ancho de banda
	 * desperdiciados).
	 */
	public function encolar_assets(): void {
		if ( ! is_singular( 'libro' ) ) {
			return;
		}

		// DearFlip Lite registra estos handles en su propio hook
		// 'wp_enqueue_scripts'. Si por alguna razón no llegó a registrarlos
		// (plugin desactivado, orden de carga distinto), no rompemos la
		// página: simplemente no se muestra el lector visual y queda el
		// aviso de "archivo no disponible" (ver templates/single-libro.php).
		if ( wp_style_is( 'dflip-style', 'registered' ) ) {
			wp_enqueue_style( 'dflip-style' );
		}
		if ( wp_script_is( 'dflip-script', 'registered' ) ) {
			wp_enqueue_script( 'dflip-script' );
		}

		wp_enqueue_style(
			'mgp-single-libro',
			MGP_BIB_URL . 'assets/css/mgp-single-libro.css',
			array(),
			MGP_BIB_VERSION
		);

		// Registro real del progreso de lectura (ver MEMORIA.md §23): se
		// investigó el JS fuente de DearFlip Lite en el servidor y se
		// confirmó que las opciones documentadas onPageChanged/onFlip/onReady
		// nunca se ejecutan en la versión Lite (executeCallback() está vacío
		// a propósito, candado de la versión de pago). mgp-lector.js NO
		// depende de esos callbacks: sondea directamente las propiedades
		// públicas currentPageNumber/pageCount de la instancia del lector,
		// que sí se actualizan en cada cambio de página. Depende de 'jquery'
		// porque dflip.js guarda esa instancia en el elemento .df-element
		// vía jQuery.data('df-app', ...), no como atributo del DOM.
		wp_enqueue_script(
			'mgp-lector',
			MGP_BIB_URL . 'assets/js/mgp-lector.js',
			array( 'jquery' ),
			MGP_BIB_VERSION,
			true
		);

		$libro_id = get_queried_object_id();
		wp_localize_script(
			'mgp-lector',
			'mgpLector',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mgp_biblioteca_nonce' ),
				'libroId' => $libro_id,
			)
		);
	}
}
