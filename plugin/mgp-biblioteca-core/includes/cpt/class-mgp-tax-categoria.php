<?php
/**
 * Clase: MGP_Tax_Categoria
 *
 * Taxonomía "categoria_tecnica": la carrera técnica a la que pertenece cada
 * libro (Computación e informática, Contabilidad, Mecánica de producción...).
 * Es lo que alimenta tanto los chips de filtro del catálogo como las
 * tarjetas de "Categorías" en la página de Inicio.
 *
 * Usamos taxonomía (no un simple campo de texto) porque:
 *  - WordPress la indexa (wp_term_relationships), los filtros son rápidos
 *    incluso con miles de libros — importante en hosting de 1GB de RAM.
 *  - El conteo de libros por categoría ("5 libros") viene gratis con
 *    get_term()->count, sin tener que contar manualmente en cada carga.
 *
 * @package MGP_Biblioteca_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MGP_Tax_Categoria {

	public function registrar_hooks(): void {
		add_action( 'init', array( $this, 'registrar' ) );
	}

	public function registrar(): void {
		register_taxonomy(
			'categoria_tecnica',
			'libro',
			array(
				'labels'            => array(
					'name'          => __( 'Categorías técnicas', 'mgp-biblioteca' ),
					'singular_name' => __( 'Categoría técnica', 'mgp-biblioteca' ),
				),
				'hierarchical'      => true, // Se comporta como "Categorías", no como "Etiquetas".
				'public'            => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'categoria' ),
			)
		);
	}
}
