<?php
/**
 * Clase: MGP_Campos_Libro
 *
 * Campos personalizados de cada libro: autor, archivo PDF y si el acceso es
 * público o solo para la comunidad del instituto. Van como post meta nativo
 * (update_post_meta), sin depender de ACF, para no sumar otro plugin más
 * de lo estrictamente necesario en un hosting de 1GB.
 *
 * Meta keys usadas (documentar aquí SIEMPRE que se agregue una nueva,
 * es la única fuente de verdad de qué datos tiene un libro):
 *   _mgp_autor           (string)  Autor(es) del libro.
 *   _mgp_archivo_pdf_id  (int)     ID de adjunto (media) del PDF.
 *   _mgp_acceso_publico  (0|1)     1 = cualquiera puede leerlo, 0 = requiere login.
 *
 * @package MGP_Biblioteca_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MGP_Campos_Libro {

	public function registrar_hooks(): void {
		add_action( 'add_meta_boxes', array( $this, 'agregar_meta_box' ) );
		add_action( 'save_post_libro', array( $this, 'guardar' ) );
	}

	public function agregar_meta_box(): void {
		add_meta_box(
			'mgp_detalles_libro',
			__( 'Detalles del libro', 'mgp-biblioteca' ),
			array( $this, 'render' ),
			'libro',
			'normal',
			'high'
		);
	}

	public function render( WP_Post $post ): void {
		// Nonce: garantiza que el formulario que llega en guardar() vino de
		// esta pantalla de edición y no de una petición falsificada (CSRF).
		wp_nonce_field( 'mgp_guardar_campos_libro', 'mgp_campos_libro_nonce' );

		$autor           = get_post_meta( $post->ID, '_mgp_autor', true );
		$archivo_pdf_id  = get_post_meta( $post->ID, '_mgp_archivo_pdf_id', true );
		$acceso_publico  = get_post_meta( $post->ID, '_mgp_acceso_publico', true );
		?>
		<p>
			<label for="mgp_autor"><strong><?php esc_html_e( 'Autor(es)', 'mgp-biblioteca' ); ?></strong></label><br>
			<input type="text" id="mgp_autor" name="mgp_autor" class="widefat"
				value="<?php echo esc_attr( $autor ); ?>">
		</p>
		<p>
			<label for="mgp_archivo_pdf_id"><strong><?php esc_html_e( 'Archivo PDF (ID de adjunto)', 'mgp-biblioteca' ); ?></strong></label><br>
			<input type="number" id="mgp_archivo_pdf_id" name="mgp_archivo_pdf_id" class="widefat"
				value="<?php echo esc_attr( $archivo_pdf_id ); ?>"
				min="0" step="1">
			<span class="description">
				<?php esc_html_e( 'Súbelo en Medios y coloca aquí su ID. (Próxima versión: selector visual de medios.)', 'mgp-biblioteca' ); ?>
			</span>
		</p>
		<p>
			<label>
				<input type="checkbox" name="mgp_acceso_publico" value="1" <?php checked( $acceso_publico, '1' ); ?>>
				<?php esc_html_e( 'Acceso público (sin necesidad de iniciar sesión)', 'mgp-biblioteca' ); ?>
			</label>
		</p>
		<?php
	}

	public function guardar( int $post_id ): void {
		// Serie de verificaciones de seguridad estándar de WordPress antes
		// de escribir nada — en este orden, cada una corta la ejecución si falla.

		// 1) Nonce válido (evita CSRF).
		if ( ! isset( $_POST['mgp_campos_libro_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mgp_campos_libro_nonce'] ) ), 'mgp_guardar_campos_libro' ) ) {
			return;
		}

		// 2) No guardar durante un autosave (no hay datos reales del usuario).
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// 3) El usuario actual debe tener permiso real para editar ESTE libro.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// 4) Sanitizar cada campo según su tipo antes de guardarlo.
		if ( isset( $_POST['mgp_autor'] ) ) {
			update_post_meta( $post_id, '_mgp_autor', sanitize_text_field( wp_unslash( $_POST['mgp_autor'] ) ) );
		}

		if ( isset( $_POST['mgp_archivo_pdf_id'] ) ) {
			update_post_meta( $post_id, '_mgp_archivo_pdf_id', absint( $_POST['mgp_archivo_pdf_id'] ) );
		}

		update_post_meta( $post_id, '_mgp_acceso_publico', isset( $_POST['mgp_acceso_publico'] ) ? '1' : '0' );

		// Un libro cambió: invalidar la caché del catálogo para que el
		// cambio se vea de inmediato (ver MGP_Cache::invalidar_grupo()).
		MGP_Cache::invalidar_grupo( 'catalogo' );
	}
}
