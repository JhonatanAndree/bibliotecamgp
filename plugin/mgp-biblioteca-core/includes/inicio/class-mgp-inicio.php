<?php
/**
 * Clase: MGP_Inicio
 *
 * Contenido dinámico de la página /inicio/: el saludo con el nombre real
 * del estudiante logueado, y la lista "Sigue leyendo" con sus libros en
 * progreso (datos reales de MGP_Usuario, no las tarjetas de muestra
 * hechas a mano en Elementor).
 *
 * Registra los shortcodes para insertarse en Elementor con el widget
 * "Shortcode" (Elementor Pro), reemplazando bloques de saludo/muestra:
 *   [mgp_saludo]         -> título "Hola, {nombre}" + subtítulo
 *   [mgp_sigue_leyendo]  -> grilla de tarjetas con progreso real
 *   [mgp_categorias]     -> conteo real de libros publicados por categoría
 *                           (bug real detectado 30/08/2026: "Categorías" y
 *                           "Recomendados para ti" eran texto de muestra
 *                           escrito a mano en Elementor, con conteos y
 *                           libros ficticios que nunca cambiaban con datos
 *                           reales — ver MEMORIA.md §21).
 *   [mgp_recomendados]   -> los 4 libros publicados más recientes, reales,
 *                           con botón Guardar reflejando el estado real.
 *
 * @package MGP_Biblioteca_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MGP_Inicio {

	public function registrar_hooks(): void {
		add_shortcode( 'mgp_saludo', array( $this, 'shortcode_saludo' ) );
		add_shortcode( 'mgp_sigue_leyendo', array( $this, 'shortcode_sigue_leyendo' ) );
		add_shortcode( 'mgp_categorias', array( $this, 'shortcode_categorias' ) );
		add_shortcode( 'mgp_recomendados', array( $this, 'shortcode_recomendados' ) );
	}

	/**
	 * [mgp_saludo] — usa el nombre de pila si el estudiante lo cargó en su
	 * perfil; si no, cae al display_name (que en la práctica suele ser el
	 * código de estudiante, por cómo se crean las cuentas — ver
	 * MGP_Acceso). Nunca deja el saludo vacío.
	 */
	public function shortcode_saludo(): string {
		if ( ! is_user_logged_in() ) {
			return ''; // No debería pasar (MGP_Acceso ya exige login), pero por si se previsualiza sin sesión.
		}

		$usuario = wp_get_current_user();
		$nombre  = $usuario->first_name ? $usuario->first_name : $usuario->display_name;

		ob_start();
		?>
		<div class="mgp-stack-xs">
			<h1 class="mgp-page-title"><?php echo esc_html( sprintf( /* translators: %s: nombre del estudiante */ __( 'Hola, %s', 'mgp-biblioteca' ), $nombre ) ); ?></h1>
			<p class="mgp-page-sub"><?php esc_html_e( 'Esto es lo nuevo en tu biblioteca esta semana.', 'mgp-biblioteca' ); ?></p>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * [mgp_sigue_leyendo] — libros con progreso entre 1% y 99% (ni sin
	 * empezar, ni ya terminados), ordenados por los actualizados más
	 * recientemente, máximo 6. El progreso vive en user meta
	 * (mgp_progreso_libro_{id}), no hay que consultarlo por libro uno por
	 * uno: se lee todo el meta del usuario de una vez (más liviano en
	 * hosting de 1GB de RAM que 6+ consultas sueltas).
	 */
	public function shortcode_sigue_leyendo(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$usuario_id  = get_current_user_id();
		$todo_meta   = get_user_meta( $usuario_id );
		$en_progreso = array();

		foreach ( $todo_meta as $clave => $valores ) {
			if ( 0 !== strpos( $clave, 'mgp_progreso_libro_' ) ) {
				continue;
			}

			$libro_id = (int) str_replace( 'mgp_progreso_libro_', '', $clave );
			$progreso = maybe_unserialize( $valores[0] );

			if ( ! is_array( $progreso ) || empty( $progreso['porcentaje'] ) ) {
				continue;
			}

			$porcentaje = (int) $progreso['porcentaje'];
			if ( $porcentaje < 1 || $porcentaje > 99 ) {
				continue; // Sin empezar o ya terminado: no es "sigue leyendo".
			}

			if ( 'libro' !== get_post_type( $libro_id ) || 'publish' !== get_post_status( $libro_id ) ) {
				continue; // El libro pudo haberse borrado o despublicado desde entonces.
			}

			$en_progreso[] = array(
				'libro_id'    => $libro_id,
				'porcentaje'  => $porcentaje,
				'actualizado' => $progreso['actualizado'] ?? '',
			);
		}

		if ( empty( $en_progreso ) ) {
			return '<p class="mgp-page-sub">'
				. esc_html__( 'Aún no tienes libros en progreso. ', 'mgp-biblioteca' )
				. '<a href="' . esc_url( home_url( '/catalogo/' ) ) . '">' . esc_html__( 'Explora el catálogo para empezar a leer.', 'mgp-biblioteca' ) . '</a>'
				. '</p>';
		}

		usort(
			$en_progreso,
			static function ( $a, $b ) {
				return strcmp( $b['actualizado'], $a['actualizado'] ); // Más reciente primero.
			}
		);

		$en_progreso = array_slice( $en_progreso, 0, 6 );

		ob_start();
		?>
		<div class="mgp-row-wrap">
			<?php foreach ( $en_progreso as $item ) : ?>
				<?php $this->imprimir_tarjeta_progreso( $item['libro_id'], $item['porcentaje'] ); ?>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Una tarjeta de "Sigue leyendo": la misma base visual que la tarjeta
	 * del catálogo (mgp-book-card) más la barra de progreso propia de esta
	 * sección (mgp-progress-*, ya definida como clase global en Elementor
	 * junto a las demás — ver MEMORIA.md §17).
	 */
	private function imprimir_tarjeta_progreso( int $libro_id, int $porcentaje ): void {
		$autor     = get_post_meta( $libro_id, '_mgp_autor', true );
		$terminos  = get_the_terms( $libro_id, 'categoria_tecnica' );
		$categoria = ( $terminos && ! is_wp_error( $terminos ) ) ? $terminos[0] : null;
		$clase_tag = $categoria ? MGP_Catalogo::clase_tag_categoria( $categoria->slug ) : '';
		?>
		<div class="mgp-card-slot">
			<div class="mgp-book-card">
				<div class="mgp-book-cover">
					<?php if ( has_post_thumbnail( $libro_id ) ) : ?>
						<?php echo get_the_post_thumbnail( $libro_id, 'medium', array( 'loading' => 'lazy' ) ); ?>
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
					<h3 class="mgp-book-title"><?php echo esc_html( get_the_title( $libro_id ) ); ?></h3>
					<p class="mgp-book-author"><?php echo esc_html( $autor ); ?></p>
				</div>

				<div class="mgp-progress">
					<div class="mgp-progress-track">
						<div class="mgp-progress-fill" style="width:<?php echo esc_attr( $porcentaje ); ?>%;"></div>
					</div>
					<p class="mgp-progress-label"><?php echo esc_html( sprintf( /* translators: %d: porcentaje de avance */ __( 'Vas por el %d%%', 'mgp-biblioteca' ), $porcentaje ) ); ?></p>
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
		<?php
	}

	/**
	 * [mgp_categorias] — conteo real de libros publicados por cada una de
	 * las 3 carreras técnicas confirmadas (ver MEMORIA.md §3/§12). Usa
	 * $termino->count, que WordPress mantiene automáticamente al
	 * publicar/despublicar libros — no hace falta contar a mano.
	 * Reemplaza el texto de muestra "5 libros"/"2 libros"/"0 libros" que
	 * vivía hardcodeado en Elementor (nunca cambiaba con datos reales).
	 */
	public function shortcode_categorias(): string {
		$mapa_categorias = array(
			'computacion-e-informatica' => __( 'Computación e informática', 'mgp-biblioteca' ),
			'contabilidad'              => __( 'Contabilidad', 'mgp-biblioteca' ),
			'mecanica-de-produccion'    => __( 'Mecánica de producción', 'mgp-biblioteca' ),
		);

		ob_start();
		?>
		<div class="mgp-row-wrap">
			<?php foreach ( $mapa_categorias as $slug => $nombre ) : ?>
				<?php
				$termino = get_term_by( 'slug', $slug, 'categoria_tecnica' );
				$conteo  = $termino ? (int) $termino->count : 0;
				?>
				<div class="mgp-cat-card">
					<h3 class="mgp-cat-name"><?php echo esc_html( $nombre ); ?></h3>
					<p class="mgp-cat-count">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: cantidad de libros publicados en la categoría */
								_n( '%d libro', '%d libros', $conteo, 'mgp-biblioteca' ),
								$conteo
							)
						);
						?>
					</p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * [mgp_recomendados] — los 4 libros publicados más recientes (datos
	 * reales), con el botón Guardar reflejando si el usuario ya lo tiene
	 * guardado. Reemplaza las 4 tarjetas de muestra con libros ficticios
	 * ("Ultimate Python Hola Mundo", etc.) que vivían hardcodeadas en
	 * Elementor. No es un motor de recomendación — es honestamente "lo
	 * último agregado al catálogo", que es preferible a mostrar libros
	 * que no existen.
	 */
	public function shortcode_recomendados(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$usuario_id = get_current_user_id();
		$guardados  = get_user_meta( $usuario_id, 'mgp_libros_guardados', true );
		$guardados  = is_array( $guardados ) ? $guardados : array();

		$consulta = new WP_Query(
			array(
				'post_type'      => 'libro',
				'post_status'    => 'publish',
				'posts_per_page' => 4,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		if ( ! $consulta->have_posts() ) {
			return '<p class="mgp-page-sub">' . esc_html__( 'Aún no hay libros para recomendar — vuelve pronto.', 'mgp-biblioteca' ) . '</p>';
		}

		ob_start();
		?>
		<div class="mgp-row-wrap">
			<?php
			while ( $consulta->have_posts() ) :
				$consulta->the_post();
				$this->imprimir_tarjeta_recomendado( get_the_ID(), in_array( get_the_ID(), $guardados, true ) );
			endwhile;
			?>
		</div>
		<?php
		wp_reset_postdata();
		return (string) ob_get_clean();
	}

	/**
	 * Tarjeta de "Recomendados": igual a la del catálogo, pero el botón
	 * Guardar arranca en estado "Guardado" si el libro ya está en
	 * mgp_libros_guardados (mismo patrón que MGP_Mis_Libros).
	 */
	private function imprimir_tarjeta_recomendado( int $libro_id, bool $guardado ): void {
		$autor     = get_post_meta( $libro_id, '_mgp_autor', true );
		$terminos  = get_the_terms( $libro_id, 'categoria_tecnica' );
		$categoria = ( $terminos && ! is_wp_error( $terminos ) ) ? $terminos[0] : null;
		$clase_tag = $categoria ? MGP_Catalogo::clase_tag_categoria( $categoria->slug ) : '';
		?>
		<div class="mgp-card-slot">
			<div class="mgp-book-card">
				<div class="mgp-book-cover">
					<?php if ( has_post_thumbnail( $libro_id ) ) : ?>
						<?php echo get_the_post_thumbnail( $libro_id, 'medium', array( 'loading' => 'lazy' ) ); ?>
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
					<h3 class="mgp-book-title"><?php echo esc_html( get_the_title( $libro_id ) ); ?></h3>
					<p class="mgp-book-author"><?php echo esc_html( $autor ); ?></p>
				</div>

				<div class="mgp-btn-row">
					<a class="mgp-btn mgp-btn-primary" href="<?php echo esc_url( home_url( '/leer/' . $libro_id . '/' ) ); ?>">
						<?php esc_html_e( 'Leer', 'mgp-biblioteca' ); ?>
					</a>
					<button type="button" class="mgp-btn mgp-btn-ghost mgp-btn-guardar<?php echo $guardado ? ' mgp-btn-guardado' : ''; ?>" data-libro-id="<?php echo esc_attr( $libro_id ); ?>">
						<?php echo $guardado ? esc_html__( 'Guardado', 'mgp-biblioteca' ) : esc_html__( 'Guardar', 'mgp-biblioteca' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}
}
