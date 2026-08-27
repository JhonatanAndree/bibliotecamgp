<?php
/**
 * Clase: MGP_Aviso_Categorias
 *
 * Muestra un aviso en la pantalla de "Añadir/editar libro" recordando qué
 * categorías técnicas (carreras) existen HOY, para que quien cargue un
 * libro de una carrera nueva cree antes la categoría correspondiente en
 * vez de dejar el libro sin categorizar o forzarlo dentro de una que no
 * corresponde.
 *
 * Las categorías SÍ se pueden crear en cualquier momento sin tocar código
 * — es una taxonomía nativa de WordPress (`categoria_tecnica`, ver
 * MGP_Tax_Categoria). Este aviso es solo un recordatorio de flujo de
 * trabajo para el bibliotecario, no una restricción técnica.
 *
 * @package MGP_Biblioteca_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MGP_Aviso_Categorias {

	public function registrar_hooks(): void {
		add_action( 'admin_notices', array( $this, 'mostrar_aviso' ) );
	}

	/**
	 * Se muestra SOLO en las pantallas de creación/edición de un libro —
	 * en cualquier otra pantalla del admin no aporta nada y sería ruido.
	 */
	public function mostrar_aviso(): void {
		$pantalla = get_current_screen();

		if ( ! $pantalla || 'libro' !== $pantalla->post_type || ! in_array( $pantalla->base, array( 'post', 'edit' ), true ) ) {
			return;
		}

		$categorias = get_terms(
			array(
				'taxonomy'   => 'categoria_tecnica',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $categorias ) ) {
			return;
		}

		$nombres = wp_list_pluck( $categorias, 'name' );
		$url_categorias = admin_url( 'edit-tags.php?taxonomy=categoria_tecnica&post_type=libro' );
		?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'Categorías técnicas disponibles hoy:', 'mgp-biblioteca' ); ?></strong>
				<?php echo esc_html( $nombres ? implode( ', ', $nombres ) : __( '(ninguna creada todavía)', 'mgp-biblioteca' ) ); ?>
			</p>
			<p>
				<?php esc_html_e( '¿Vas a subir un libro de una carrera técnica que no está en esta lista? Créala primero en', 'mgp-biblioteca' ); ?>
				<a href="<?php echo esc_url( $url_categorias ); ?>"><?php esc_html_e( 'Libros → Categorías técnicas', 'mgp-biblioteca' ); ?></a>
				<?php esc_html_e( 'antes de publicar el libro — se pueden crear nuevas categorías en cualquier momento, sin necesidad de tocar código.', 'mgp-biblioteca' ); ?>
			</p>
		</div>
		<?php
	}
}
