<?php
/**
 * Clase: MGP_Acceso
 *
 * Control de acceso de todo el sitio: la biblioteca es exclusiva para
 * estudiantes matriculados. Cualquier visitante NO logueado que intente
 * ver cualquier página del sitio (excepto la propia página de login) es
 * redirigido a /login/. Al loguearse, el formulario lo lleva a /inicio/.
 *
 * También registra el shortcode [mgp_login], que imprime el formulario de
 * acceso (usuario = código de estudiante, contraseña normal) con el mismo
 * lenguaje visual del resto del sitio. Se apoya en wp_login_form() del
 * núcleo de WordPress para no reinventar la autenticación ni sus
 * protecciones (límite de intentos ya cubierto por Wordfence).
 *
 * @package MGP_Biblioteca_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MGP_Acceso {

	public function registrar_hooks(): void {
		add_action( 'template_redirect', array( $this, 'exigir_login' ) );
		add_shortcode( 'mgp_login', array( $this, 'shortcode_login' ) );
	}

	/**
	 * Puerta de entrada del sitio. Si no hay sesión iniciada, solo se
	 * permite ver la página de login; cualquier otra URL redirige ahí.
	 * AJAX, cron y REST no pasan por template_redirect en el flujo normal
	 * de WordPress, pero se excluyen explícitamente por seguridad ante
	 * cambios futuros del núcleo.
	 */
	public function exigir_login(): void {
		if ( is_user_logged_in() ) {
			return;
		}

		if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( is_page( 'login' ) ) {
			return;
		}

		wp_safe_redirect( home_url( '/login/' ) );
		exit;
	}

	/**
	 * [mgp_login] — formulario de acceso. El "usuario" es, en la
	 * práctica, el código de estudiante que el bibliotecario asigna al
	 * crear la cuenta (rol "subscriber" o "bibliotecario" según el caso).
	 */
	public function shortcode_login(): string {
		if ( is_user_logged_in() ) {
			wp_safe_redirect( home_url( '/inicio/' ) );
			exit;
		}

		ob_start();
		?>
		<div class="mgp-login">
			<?php
			wp_login_form(
				array(
					'redirect'       => home_url( '/inicio/' ),
					'label_username' => __( 'Código de estudiante', 'mgp-biblioteca' ),
					'label_password' => __( 'Contraseña', 'mgp-biblioteca' ),
					'label_log_in'   => __( 'Ingresar', 'mgp-biblioteca' ),
					'remember'       => true,
				)
			);
			?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
