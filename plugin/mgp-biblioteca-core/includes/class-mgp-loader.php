<?php
/**
 * Clase: MGP_Loader
 *
 * Orquestador central del plugin (patrón Singleton). En vez de que cada
 * archivo se enganche a hooks de WordPress por su cuenta y de forma
 * dispersa, este loader carga cada clase y llama a su método de registro.
 *
 * Ventaja para quien mantenga esto en el futuro: para saber TODO lo que
 * hace el plugin, basta con leer el método run() de este archivo — es el
 * mapa completo del sistema.
 *
 * @package MGP_Biblioteca_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MGP_Loader {

	/**
	 * Única instancia del loader (Singleton). Evita registrar los mismos
	 * hooks dos veces si algo llama a mgp_bib_bootstrap() más de una vez.
	 *
	 * @var MGP_Loader|null
	 */
	private static ?MGP_Loader $instancia = null;

	public static function instance(): MGP_Loader {
		if ( null === self::$instancia ) {
			self::$instancia = new self();
		}
		return self::$instancia;
	}

	/**
	 * Constructor privado: nadie puede hacer `new MGP_Loader()` desde
	 * afuera, solo a través de instance(). Así garantizamos un solo loader.
	 */
	private function __construct() {}

	/**
	 * Carga los archivos de cada módulo e invoca su registro de hooks.
	 * Si en el futuro se agrega un módulo nuevo (ej. reseñas de libros),
	 * solo hay que sumar dos líneas aquí: el require y la instancia.
	 */
	public function run(): void {

		// --- Acceso: puerta de login de todo el sitio ---------------------
		require_once MGP_BIB_PATH . 'includes/acceso/class-mgp-acceso.php';
		( new MGP_Acceso() )->registrar_hooks();

		// --- Catálogo: qué ES un libro y cómo se categoriza --------------
		require_once MGP_BIB_PATH . 'includes/cpt/class-mgp-cpt-libro.php';
		require_once MGP_BIB_PATH . 'includes/cpt/class-mgp-tax-categoria.php';
		( new MGP_CPT_Libro() )->registrar_hooks();
		( new MGP_Tax_Categoria() )->registrar_hooks();

		// --- Campos personalizados del libro (autor, PDF, portada...) ----
		require_once MGP_BIB_PATH . 'includes/cpt/class-mgp-campos-libro.php';
		( new MGP_Campos_Libro() )->registrar_hooks();

		// --- Utilidades transversales: caché y seguridad ------------------
		require_once MGP_BIB_PATH . 'includes/cache/class-mgp-cache.php';
		require_once MGP_BIB_PATH . 'includes/seguridad/class-mgp-seguridad.php';
		// Estas dos son clases de helpers estáticos: no necesitan hooks propios.

		// --- Lector protegido de PDF --------------------------------------
		require_once MGP_BIB_PATH . 'includes/lector/class-mgp-lector.php';
		( new MGP_Lector() )->registrar_hooks();

		// --- Catálogo dinámico: filtros + buscador (AJAX) -----------------
		require_once MGP_BIB_PATH . 'includes/catalogo/class-mgp-catalogo.php';
		( new MGP_Catalogo() )->registrar_hooks();

		// --- Cuenta del estudiante: guardados y progreso de lectura -------
		require_once MGP_BIB_PATH . 'includes/usuario/class-mgp-usuario.php';
		( new MGP_Usuario() )->registrar_hooks();

		// --- Página Inicio: saludo real + "Sigue leyendo" con datos reales -
		require_once MGP_BIB_PATH . 'includes/inicio/class-mgp-inicio.php';
		( new MGP_Inicio() )->registrar_hooks();

		// --- Página Mis libros: guardados + en progreso, datos reales ----
		require_once MGP_BIB_PATH . 'includes/mis-libros/class-mgp-mis-libros.php';
		( new MGP_Mis_Libros() )->registrar_hooks();

		// --- Página individual del libro (plantilla + lector DearFlip) ---
		require_once MGP_BIB_PATH . 'includes/plantillas/class-mgp-plantilla-single-libro.php';
		( new MGP_Plantilla_Single_Libro() )->registrar_hooks();

		// --- Aviso en el admin: categorías técnicas disponibles hoy -------
		require_once MGP_BIB_PATH . 'includes/cpt/class-mgp-aviso-categorias.php';
		( new MGP_Aviso_Categorias() )->registrar_hooks();

		// --- Assets (CSS/JS), cargados solo en las páginas que los usan ---
		add_action( 'wp_enqueue_scripts', array( $this, 'cargar_assets' ) );

		// --- Reparación de capacidades del administrador (ver v0.5.4) ----
		add_action( 'admin_init', array( $this, 'reparar_capacidades_admin' ) );
	}

	/**
	 * Encola CSS/JS del plugin SOLO cuando hace falta. Cargar assets en
	 * todo el sitio es una de las causas más comunes de sitios lentos en
	 * hosting compartido — aquí se evita a propósito por el límite de 1GB
	 * de RAM del servidor.
	 */
	public function cargar_assets(): void {
		// El catálogo y el lector viven en páginas específicas (slugs fijos
		// del sitio: catalogo, mis-libros, inicio). Si cambian esos slugs,
		// actualizar este arreglo.
		$paginas_con_biblioteca = array( 'catalogo', 'mis-libros', 'inicio' );

		if ( is_page( $paginas_con_biblioteca ) ) {
			wp_enqueue_style(
				'mgp-biblioteca',
				MGP_BIB_URL . 'assets/css/mgp-biblioteca.css',
				array(),
				MGP_BIB_VERSION
			);

			wp_enqueue_script(
				'mgp-catalogo',
				MGP_BIB_URL . 'assets/js/mgp-catalogo.js',
				array(), // Sin dependencia de jQuery: usa fetch() nativo (más liviano).
				MGP_BIB_VERSION,
				true // Cargar en el footer.
			);

			// Datos que el JS necesita del backend: URL de AJAX, nonce de
			// seguridad, y en qué página estamos. Este último dato es
			// necesario porque catálogo (/catalogo/) e inicio (/inicio/)
			// comparten la misma clase visual "mgp-row-wrap" para su grilla
			// de tarjetas (es la clase real del diseño, ver MEMORIA.md §17)
			// — sin esto, el JS del catálogo confundiría la grilla de
			// "Sigue leyendo" en Inicio con la suya propia y la pisaría con
			// una petición AJAX que no le corresponde a esa página.
			wp_localize_script(
				'mgp-catalogo',
				'mgpBiblioteca',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'mgp_biblioteca_nonce' ),
					'pagina'  => is_page( 'catalogo' ) ? 'catalogo' : ( is_page( 'inicio' ) ? 'inicio' : ( is_page( 'mis-libros' ) ? 'mis-libros' : '' ) ),
				)
			);

			// NOTA (ver MEMORIA.md §18): antes había aquí un enqueue de
			// 'mgp-lector.js' — archivo que nunca llegó a crearse, y que
			// además estaba mal ubicado (esta condición es la página
			// "mis-libros", una vitrina de tarjetas; el lector DearFlip vive
			// en la página individual del libro, templates/single-libro.php,
			// vía MGP_Plantilla_Single_Libro). Se quitó para no pedirle al
			// navegador un archivo que da 404. El registro real de progreso
			// de lectura (MGP_Usuario::actualizar_progreso()) sigue sin
			// tener ningún JS que lo dispare — pendiente, requiere revisar
			// primero la API de eventos real de DearFlip Lite antes de
			// escribir ese enganche a ciegas.
		}

		// La página de login solo necesita su propio CSS: formulario de
		// acceso vía el shortcode [mgp_login], sin catálogo ni lector.
		if ( is_page( 'login' ) ) {
			wp_enqueue_style(
				'mgp-login',
				MGP_BIB_URL . 'assets/css/mgp-login.css',
				array(),
				MGP_BIB_VERSION
			);
		}
	}

	/**
	 * Crea (si no existe) el rol "bibliotecario": personal de biblioteca
	 * que puede subir/editar libros SIN necesitar una cuenta de
	 * Administrador completo de WordPress (principio de mínimo privilegio).
	 * Se ejecuta solo en la activación del plugin, no en cada carga.
	 */
	public static function crear_rol_bibliotecario(): void {
		if ( get_role( 'bibliotecario' ) ) {
			return; // Ya existe, no lo pisamos (por si el instituto ajustó permisos).
		}

		add_role(
			'bibliotecario',
			__( 'Bibliotecario', 'mgp-biblioteca' ),
			array(
				'read'                   => true,
				'edit_posts'             => true,
				'edit_published_posts'   => true,
				'publish_posts'          => true,
				'delete_posts'           => true,
				'upload_files'           => true,
				'edit_libros'            => true,
				'edit_published_libros'  => true,
				'publish_libros'         => true,
				'delete_libros'          => true,
			)
		);
	}

	/**
	 * Bug real detectado en producción (30/08/2026): el CPT "libro" usa
	 * capacidades propias (capability_type => 'libro', map_meta_cap =>
	 * true), pero solo el rol "bibliotecario" las recibía en
	 * crear_rol_bibliotecario(). El rol "administrator" de WordPress NO
	 * hereda automáticamente capacidades de un capability_type
	 * personalizado — por eso el menú "Libros" nunca apareció en
	 * wp-admin, ni para el propio administrador del sitio. Este método
	 * las agrega también a "administrator", una sola vez (controlado por
	 * la opción mgp_bib_caps_admin_v1 para no repetir el trabajo en cada
	 * carga de /wp-admin/). Ver MEMORIA.md §20.
	 */
	public function reparar_capacidades_admin(): void {
		if ( get_option( 'mgp_bib_caps_admin_v1' ) ) {
			return;
		}

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$capacidades = array(
				'edit_libro',
				'read_libro',
				'delete_libro',
				'edit_libros',
				'edit_others_libros',
				'publish_libros',
				'read_private_libros',
				'delete_libros',
				'delete_private_libros',
				'delete_published_libros',
				'delete_others_libros',
				'edit_private_libros',
				'edit_published_libros',
			);
			foreach ( $capacidades as $capacidad ) {
				$admin->add_cap( $capacidad );
			}
		}

		update_option( 'mgp_bib_caps_admin_v1', 1 );
	}
}
