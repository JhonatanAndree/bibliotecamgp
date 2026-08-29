<?php
/**
 * Template part: una tarjeta de libro, reutilizando EXACTAMENTE las clases
 * CSS del diseño Elementor ya aprobado (mgp-book-card, mgp-tag, etc. — sin
 * prefijo "g-", ver MEMORIA.md §17).
 * Este archivo solo imprime HTML — cero lógica de negocio aquí a propósito,
 * así el diseñador puede tocarlo sin entender PHP de backend.
 *
 * Variables disponibles: el post actual, vía las funciones estándar de
 * WordPress (get_the_ID(), get_the_title(), etc.) porque se incluye dentro
 * del bucle the_post() de MGP_Catalogo::generar_html().
 *
 * @package MGP_Biblioteca_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$libro_id       = get_the_ID();
$autor          = get_post_meta( $libro_id, '_mgp_autor', true );
$terminos       = get_the_terms( $libro_id, 'categoria_tecnica' );
$categoria      = ( $terminos && ! is_wp_error( $terminos ) ) ? $terminos[0] : null;
$clase_tag      = $categoria ? MGP_Catalogo::clase_tag_categoria( $categoria->slug ) : '';
?>
<div class="mgp-card-slot">
	<div class="mgp-book-card">
		<div class="mgp-book-cover">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'medium', array( 'loading' => 'lazy' ) ); ?>
			<?php else : ?>
				<span class="mgp-book-cover-label"><?php esc_html_e( 'portada pendiente', 'mgp-biblioteca' ); ?></span>
			<?php endif; ?>
		</div>

		<?php if ( $categoria ) : ?>
			<span class="mgp-tag<?php echo $clase_tag ? ' ' . esc_attr( $clase_tag ) : ''; ?>">
				<?php echo esc_html( $categoria->name ); ?>
			</span>
		<?php endif; ?>

		<div class="mgp-book-meta">
			<h3 class="mgp-book-title"><?php the_title(); ?></h3>
			<p class="mgp-book-author"><?php echo esc_html( $autor ); ?></p>
		</div>

		<div class="mgp-btn-row">
			<a class="mgp-btn mgp-btn-primary" href="<?php echo esc_url( home_url( '/leer/' . $libro_id . '/' ) ); ?>">
				<?php esc_html_e( 'Leer', 'mgp-biblioteca' ); ?>
			</a>
			<button type="button" class="mgp-btn mgp-btn-ghost mgp-btn-guardar" data-libro-id="<?php echo esc_attr( $libro_id ); ?>">
				<?php esc_html_e( 'Guardar', 'mgp-biblioteca' ); ?>
			</button>
		</div>
	</div>
</div>
