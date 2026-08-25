<?php
/**
 * Clase: MGP_Lector
 *
 * Entrega el archivo PDF de un libro a través de una ruta propia
 * (/leer/{id}/) en vez de exponer la URL directa de /wp-content/uploads/.
 *
 * Por qué: la nota del diseño dice "la descarga está desactivada. Los
 * libros solo se leen dentro de la biblioteca digital". La media library
 * de WordPress es pública por defecto — cualquiera con la URL del PDF
 * puede descargarlo, esté logueado o no. Sirviendo el archivo por PHP
 * podemos: (a) verificar sesión/permiso en cada solicitud, (b) no revelar
 * la URL real del archivo en el HTML, (c) mandar cabeceras que indican al
 * navegador que lo muestre en línea, no que lo descargue.
 *
 * Esto NO es DRM — un usuario logueado con suficiente conocimiento técnico
 * podría igual guardar el PDF. Es una barrera razonable para uso normal,
 * no una protección absoluta. Documentado así para que quede claro el
 * alcance real ante el instituto.
 *
 * @package MGP_Biblioteca_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MGP_Lector {

	public function registrar_hooks(): void {
		add_action( 'init', array( $this, 'registrar_ruta' ) );
		add_filter( 'query_vars', array( $this, 'registrar_query_var' ) );
		add_action( 'template_redirect', array( $this, 'interceptar_solicitud' ) );
	}

	/** Crea la regla de reescritura: /leer/123/ -> index.php?mgp_leer_id=123 */
	public function registrar_ruta(): void {
		add_rewrite_rule( '^leer/([0-9]+)/?$', 'index.php?mgp_leer_id=$matches[1]', 'top' );
	}

	public function registrar_query_var( array $vars ): array {
		$vars[] = 'mgp_leer_id';
		return $vars;
	}

	/**
	 * Punto de entrada: se ejecuta en CADA carga de página del sitio, pero
	 * corta de inmediato (return) si la URL no es una solicitud de lectura.
	 * Es la única función "cara" de este archivo y solo hace trabajo real
	 * cuando corresponde — importante para no afectar el rendimiento del
	 * resto del sitio.
	 */
	public function interceptar_solicitud(): void {
		$libro_id = get_query_var( 'mgp_leer_id' );

		if ( empty( $libro_id ) ) {
			return;
		}

		$libro_id = absint( $libro_id );
		$libro    = get_post( $libro_id );

		if ( ! $libro || 'libro' !== $libro->post_type || 'publish' !== $libro->post_status ) {
			wp_die( esc_html__( 'Libro no encontrado.', 'mgp-biblioteca' ), 404 );
		}

		if ( ! $this->usuario_tiene_acceso( $libro_id ) ) {
			// Sin permiso: al login, con retorno a esta misma URL tras autenticarse.
			$url_login = wp_login_url( get_permalink() );
			wp_safe_redirect( $url_login );
			exit;
		}

		$this->entregar_pdf( $libro_id );
	}

	private function usuario_tiene_acceso( int $libro_id ): bool {
		$acceso_publico = get_post_meta( $libro_id, '_mgp_acceso_publico', true );

		if ( '1' === $acceso_publico ) {
			return true;
		}

		return is_user_logged_in();
	}

	/**
	 * Envía el archivo al navegador SIN cargarlo entero en memoria PHP
	 * (crítico en un servidor de 1GB de RAM: un PDF de 40-80MB cargado con
	 * file_get_contents podría agotar la memoria disponible si varios
	 * estudiantes leen a la vez). Se usa fpassthru(), que transmite el
	 * archivo en streaming por partes.
	 */
	private function entregar_pdf( int $libro_id ): void {
		$adjunto_id = (int) get_post_meta( $libro_id, '_mgp_archivo_pdf_id', true );
		$ruta       = $adjunto_id ? get_attached_file( $adjunto_id ) : false;

		if ( ! $ruta || ! file_exists( $ruta ) ) {
			wp_die( esc_html__( 'El archivo de este libro no está disponible todavía.', 'mgp-biblioteca' ), 404 );
		}

		// Cabeceras de seguridad y de presentación en línea (no descarga).
		nocache_headers(); // No permitir que navegadores/proxies cacheen contenido restringido.
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: inline; filename="' . basename( $ruta ) . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Length: ' . filesize( $ruta ) );

		$manejador = fopen( $ruta, 'rb' );
		if ( $manejador ) {
			fpassthru( $manejador );
			fclose( $manejador );
		}
		exit;
	}
}
