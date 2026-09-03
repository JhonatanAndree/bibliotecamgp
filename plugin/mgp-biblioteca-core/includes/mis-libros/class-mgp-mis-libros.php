<?php
/**
 * Clase: MGP_Mis_Libros
 *
 * Página "Mis libros": dos shortcodes con datos reales del usuario
 * logueado, mismo patrón ya usado en Inicio (MGP_Inicio) — el diseñador
 * coloca dos widgets "Shortcode" en Elementor y este código imprime el
 * HTML real, reutilizando las mismas clases visuales del catálogo
 * (mgp-row-wrap, mgp-card-slot, mgp-book-card, mgp-progress-*, etc. — ver
 * MEMORIA.md §17).
 *
 * [mgp_guardados]    -> todos los libros que el usuario guardó (botón
 *                       "Guardar" del catálogo/inicio), más recientes primero.
 * [mgp_en_progreso]  -> todos los libros con progreso de lectura entre 1%
 *                       y 99%, más recientes primero. A diferencia del
 *                       "Sigue leyendo" de Inicio (que muestra máximo 6),
 *                       aquí se listan TODOS — esta es la página dedicada.
 *
 * Nota importante (ver MEMORIA.md §18): el progreso de lectura real
 * (MGP_Usuario::actualizar_progreso()) hoy NO se está registrando nunca
 * en la práctica, porque no hay ningún JS enganchado a los eventos de
 * paso de página del lector DearFlip en la página individual del libro
 * (templates/single-libro.php). El backend de esta página (Mis libros)
 * ya está listo para mostrar esos datos en cuanto ese enganche exista.
 *
 * @package MGP_Biblioteca_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MGP_Mis_Libros {

	public function registrar_hooks(): void {
		add_shortcode( 'mgp_guardados', array( $this, 'shortcode_guardados' ) );
		add_shortcode( 'mgp_en_progreso', array( $this, 'shortcode_en_progreso' ) );
	}

	public function shortcode_guardados(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$usuario_id = get_current_user_id();
		$guardados  = (array) get_user_meta( $usuario_id, 'mgp_libros_guardados', true );
		$guardados  = array_filter( array_map( 'absint', $guardados ) );

		if ( empty( $guardados ) ) {
			return '<p class="mgp-page-sub">'
				. esc_html__( 'Todavía no has guardado ningún libro. ', 'mgp-biblioteca' )
				. '<a href="' . esc_url( home_url( '/catalogo/' ) ) . '">' . esc_html__( 'Explora el catálogo.', 'mgp-biblioteca' ) . '</a>'
				. '</p>';
		}

		// mgp_libros_guardados es un array donde lo último guardado queda al
		// final (ver MGP_Usuario::alternar_guardado()) — se invierte para
		// mostrar lo más reciente primero.
		$guardados = array_reverse( $guardados );

		ob_start();
		?>
		<div class="mgp-row-wrap">
			<?php
			foreach ( $guardados as $libro_id ) {
				if ( 'libro' !== get_post_type( $libro_id ) || 'publish' !== get_post_status( $libro_id ) ) {
					continue; // El libro pudo haberse borrado/despublicado después de guardarlo.
				}
				$this->imprimir_tarjeta( $libro_id, true, $this->obtener_porcentaje( $usuario_id, $libro_id ) );
			}
			?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public function shortcode_en_progreso(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$usuario_id  = get_current_user_id();
		$en_progreso = $this->obtener_libros_en_progreso( $usuario_id );

		if ( empty( $en_progreso ) ) {
			return '<p class="mgp-page-sub">'
				. esc_html__( 'No tienes libros en progreso todavía. ', 'mgp-biblioteca' )
				. '<a href="' . esc_url( home_url( '/catalogo/' ) ) . '">' . esc_html__( 'Explora el catálogo para empezar a leer.', 'mgp-biblioteca' ) . '</a>'
				. '</p>';
		}

		$guardados = (array) get_user_meta( $usuario_id, 'mgp_libros_guardados', true );
		$guardados = array_map( 'absint', $guardados );

		ob_start();
		?>
		<div class="mgp-row-wrap">
			<?php
			foreach ( $en_progreso as $item ) {
				$this->imprimir_tarjeta( $item['libro_id'], in_array( $item['libro_id'], $guardados, true ), $item['porcentaje'] );
			}
			?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Recorre TODO el user meta buscando claves mgp_progreso_libro_{id}
	 * (no hay una tabla propia para esto — mismo enfoque ya usado en
	 * MGP_Inicio::shortcode_sigue_leyendo(), ver MEMORIA.md §17). Solo
	 * cuenta como "en progreso" el rango 1-99%; 100% se considera
	 * terminado y no aparece aquí (posible sección futura "Terminados").
	 *
	 * @return array<int, array{libro_id:int, porcentaje:int, actualizado:string}>
	 */
	private function obtener_libros_en_progreso( int $usuario_id ): array {
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
				continue;
			}
			if ( 'libro' !== get_post_type( $libro_id ) || 'publish' !== get_post_status( $libro_id ) ) {
				continue;
			}
			$en_progreso[] = array(
				'libro_id'    => $libro_id,
				'porcentaje'  => $porcentaje,
				'actualizado' => $progreso['actualizado'] ?? '',
			);
		}

		usort( $en_progreso, static function ( $a, $b ) {
			return strcmp( $b['actualizado'], $a['actualizado'] );
		} );

		return $en_progreso;
	}

	/** Porcentaje de progreso de un libro puntual, o null si no tiene. */
	private function obtener_porcentaje( int $usuario_id, int $libro_id ): ?int {
		$progreso = get_user_meta( $usuario_id, 'mgp_progreso_libro_' . $libro_id, true );
		$progreso = maybe_unserialize( $progreso );
		if ( ! is_array( $progreso ) || empty( $progreso['porcentaje'] ) ) {
			return null;
		}
		$porcentaje = (int) $progreso['porcentaje'];
		return ( $porcentaje >= 1 && $porcentaje <= 100 ) ? $porcentaje : null;
	}

	/**
	 * Imprime una tarjeta de libro reutilizando las mismas clases reales
	 * del catálogo. A diferencia de la tarjeta del catálogo (siempre
	 * arranca en "Guardar"), acá el botón refleja el estado real ya
	 * guardado, y la barra de progreso es opcional (null = sin progreso
	 * registrado todavía, ej. un libro guardado pero no empezado).
	 */
	private function imprimir_tarjeta( int $libro_id, bool $guardado, ?int $porcentaje ): void {
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
				<?php if ( null !== $porcentaje ) : ?>
					<div class="mgp-progress">
						<div class="mgp-progress-track">
							<div class="mgp-progress-fill" style="width:<?php echo esc_attr( $porcentaje ); ?>%;"></div>
						</div>
						<p class="mgp-progress-label"><?php echo esc_html( sprintf( __( 'Vas por el %d%%', 'mgp-biblioteca' ), $porcentaje ) ); ?></p>
					</div>
				<?php endif; ?>
				<div class="mgp-btn-row">
					<a class="mgp-btn mgp-btn-primary" href="<?php echo esc_url( home_url( '/leer/' . $libro_id . '/' ) ); ?>">
						<?php esc_html_e( 'Leer', 'mgp-biblioteca' ); ?>
					</a>
					<button type="button"
						class="mgp-btn mgp-btn-ghost mgp-btn-guardar<?php echo $guardado ? ' mgp-btn-saved' : ''; ?>"
						data-libro-id="<?php echo esc_attr( $libro_id ); ?>">
						<?php echo $guardado ? esc_html__( 'Guardado', 'mgp-biblioteca' ) : esc_html__( 'Guardar', 'mgp-biblioteca' ); ?>
					</button>
			</div>
		</div>
		<?php
	}
}
