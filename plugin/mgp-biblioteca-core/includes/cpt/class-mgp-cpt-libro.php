<?php
/**
 * Clase: MGP_CPT_Libro
 *
 * Registra el Custom Post Type "libro": cada libro/documento del catálogo
 * es un post de este tipo. Usamos un CPT nativo de WordPress (no una tabla
 * propia) porque así heredamos gratis: revisiones, papelera, búsqueda,
 * REST API (para el Loop Grid de Elementor) y roles/capacidades.
 *
 * @package MGP_Biblioteca_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MGP_CPT_Libro {

	public function registrar_hooks(): void {
		add_action( 'init', array( $this, 'registrar' ) );
	}

	public function registrar(): void {
		$etiquetas = array(
			'name'          => __( 'Libros', 'mgp-biblioteca' ),
			'singular_name' => __( 'Libro', 'mgp-biblioteca' ),
			'add_new_item'  => __( 'Agregar nuevo libro', 'mgp-biblioteca' ),
			'edit_item'     => __( 'Editar libro', 'mgp-biblioteca' ),
			'search_items'  => __( 'Buscar libros', 'mgp-biblioteca' ),
			'not_found'     => __( 'No se encontraron libros', 'mgp-biblioteca' ),
		);

		register_post_type(
			'libro',
			array(
				'labels'              => $etiquetas,
				'public'              => true,
				// Sin archivo propio de WordPress: el listado real lo maneja
				// la página /catalogo/ ya diseñada en Elementor, vía AJAX.
				'has_archive'         => false,
				'publicly_queryable'  => true,
				'show_in_rest'        => true, // Necesario para Elementor Loop Grid y Gutenberg.
				'rewrite'             => array( 'slug' => 'libro' ),
				'menu_icon'           => 'dashicons-book-alt',
				'supports'            => array( 'title', 'thumbnail', 'excerpt', 'custom-fields' ),
				'capability_type'     => 'libro',
				'map_meta_cap'        => true, // Necesario para que el rol "bibliotecario" funcione.
				'show_in_menu'        => true,
			)
		);
	}
}
