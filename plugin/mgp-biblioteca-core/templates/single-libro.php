<?php
/**
 * Plantilla: página individual de un libro.
 *
 * Cargada por MGP_Plantilla_Single_Libro::cargar_plantilla() SOLO para el
 * CPT `libro`. get_header()/get_footer() reutilizan el header y footer
 * reales del sitio (menú, sesión del estudiante, pie de página) — el resto
 * del marcado usa las clases mgp-single-libro__* definidas en
 * assets/css/mgp-single-libro.css, que replican el sistema de diseño
 * "Biblioteca MGP Nocturna" con variables CSS propias (no depende de que
 * Elementor esté activo para verse bien).
 *
 * @package MGP_Biblioteca_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$libro_id       = get_the_ID();
	$autor          = get_post_meta( $libro_id, '_mgp_autor', true );
	$archivo_pdf_id = (int) get_post_meta( $libro_id, '_mgp_archivo_pdf_id', true );
	$acceso_publico = '1' === get_post_meta( $libro_id, '_mgp_acceso_publico', true );
	$puede_leer     = $acceso_publico || is_user_logged_in();
	$url_lectura    = home_url( '/leer/' . $libro_id . '/' );
	$url_portada    = get_the_post_thumbnail_url( $libro_id, 'medium' );

	$categorias = get_the_terms( $libro_id, 'categoria_tecnica' );
	$categoria  = ( $categorias && ! is_wp_error( $categorias ) && ! empty( $categorias ) ) ? $categorias[0] : null;
	?>

	<section class="mgp-single-libro">
		<div class="mgp-single-libro__col">

			<nav class="mgp-single-libro__volver">
				<a href="<?php echo esc_url( home_url( '/catalogo/' ) ); ?>">&larr; <?php esc_html_e( 'Volver al catálogo', 'mgp-biblioteca' ); ?></a>
			</nav>

			<div class="mgp-single-libro__cabecera">
				<div class="mgp-single-libro__portada">
					<?php if ( $url_portada ) : ?>
						<img src="<?php echo esc_url( $url_portada ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>">
					<?php else : ?>
						<div class="mgp-single-libro__portada-vacia" aria-hidden="true"></div>
					<?php endif; ?>
				</div>

				<div class="mgp-single-libro__info">
					<?php if ( $categoria ) : ?>
						<span class="mgp-single-libro__tag" data-categoria="<?php echo esc_attr( $categoria->slug ); ?>">
							<?php echo esc_html( $categoria->name ); ?>
						</span>
					<?php endif; ?>

					<h1 class="mgp-single-libro__titulo"><?php the_title(); ?></h1>

					<?php if ( $autor ) : ?>
						<p class="mgp-single-libro__autor"><?php echo esc_html( $autor ); ?></p>
					<?php endif; ?>

					<p class="mgp-single-libro__acceso">
						<?php if ( $acceso_publico ) : ?>
							<?php esc_html_e( 'Acceso público — no necesitas iniciar sesión.', 'mgp-biblioteca' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Requiere iniciar sesión con tu código de estudiante.', 'mgp-biblioteca' ); ?>
						<?php endif; ?>
					</p>

					<?php if ( ! $puede_leer ) : ?>
						<a class="mgp-single-libro__btn mgp-single-libro__btn--primario"
							href="<?php echo esc_url( wp_login_url( get_permalink( $libro_id ) ) ); ?>">
							<?php esc_html_e( 'Iniciar sesión para leer', 'mgp-biblioteca' ); ?>
						</a>
					<?php elseif ( $archivo_pdf_id ) : ?>
						<a class="mgp-single-libro__btn mgp-single-libro__btn--primario" href="#mgp-lector-libro">
							<?php esc_html_e( 'Empezar a leer', 'mgp-biblioteca' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( $puede_leer ) : ?>
				<div id="mgp-lector-libro" class="mgp-single-libro__lector">
					<?php if ( $archivo_pdf_id ) : ?>
						<?php
						// Marcado de "incrustación manual" documentado por DearFlip: el
						// script dflip.js escanea el DOM en busca de elementos con la
						// clase df-element y arma el lector leyendo su atributo `source`.
						// No usamos el shortcode [dflip] porque este espera un post del
						// CPT propio de DearFlip — nuestros libros viven en el CPT
						// `libro`, con el PDF servido por la ruta protegida /leer/{id}/.
						?>
						<div class="df-element"
							id="mgp-flipbook-<?php echo esc_attr( $libro_id ); ?>"
							source="<?php echo esc_url( $url_lectura ); ?>"
							height="720px"
							webgl="false"
							data-download="false"></div>
					<?php else : ?>
						<p class="mgp-single-libro__aviso">
							<?php esc_html_e( 'Este libro todavía no tiene un archivo PDF cargado. Vuelve más tarde.', 'mgp-biblioteca' ); ?>
						</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</div>
	</section>

	<?php
endwhile;

get_footer();
