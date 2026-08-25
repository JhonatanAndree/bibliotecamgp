<?php
/**
 * Clase: MGP_Usuario
 *
 * "Guardar" (bookmarks) y progreso de lectura, ambos ligados al usuario
 * logueado vía user meta — NO vía cookies ni localStorage, para que el
 * estudiante vea lo mismo sin importar el dispositivo desde el que entre.
 *
 * Meta keys en wp_usermeta:
 *   mgp_libros_guardados  (array de post IDs)
 *   mgp_progreso_libro_{id}  (array: pagina_actual, total_paginas, porcentaje, actualizado)
 *
 * @package MGP_Biblioteca_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MGP_Usuario {

	public function registrar_hooks(): void {
		// Estas dos acciones SOLO tienen sentido logueado: sin variante "nopriv".
		add_action( 'wp_ajax_mgp_guardar_libro', array( $this, 'alternar_guardado' ) );
		add_action( 'wp_ajax_mgp_actualizar_progreso', array( $this, 'actualizar_progreso' ) );
	}

	public function alternar_guardado(): void {
		if ( ! is_user_logged_in() || ! MGP_Seguridad::verificar_nonce_ajax() ) {
			wp_send_json_error( array( 'mensaje' => 'No autorizado.' ), 401 );
		}

		$libro_id = isset( $_POST['libro_id'] ) ? absint( $_POST['libro_id'] ) : 0;
		if ( ! $libro_id || 'libro' !== get_post_type( $libro_id ) ) {
			wp_send_json_error( array( 'mensaje' => 'Libro inválido.' ), 400 );
		}

		$usuario_id = get_current_user_id();
		$guardados  = (array) get_user_meta( $usuario_id, 'mgp_libros_guardados', true );

		if ( in_array( $libro_id, $guardados, true ) ) {
			$guardados = array_diff( $guardados, array( $libro_id ) );
			$estado    = 'quitado';
		} else {
			$guardados[] = $libro_id;
			$estado      = 'guardado';
		}

		update_user_meta( $usuario_id, 'mgp_libros_guardados', array_values( $guardados ) );
		wp_send_json_success( array( 'estado' => $estado ) );
	}

	public function actualizar_progreso(): void {
		if ( ! is_user_logged_in() || ! MGP_Seguridad::verificar_nonce_ajax() ) {
			wp_send_json_error( array( 'mensaje' => 'No autorizado.' ), 401 );
		}

		if ( ! MGP_Seguridad::limite_de_peticiones( 'progreso', 60, 60 ) ) {
			wp_send_json_error( array( 'mensaje' => 'Demasiadas peticiones.' ), 429 );
		}

		$libro_id       = isset( $_POST['libro_id'] ) ? absint( $_POST['libro_id'] ) : 0;
		$pagina_actual  = isset( $_POST['pagina'] ) ? absint( $_POST['pagina'] ) : 0;
		$total_paginas  = isset( $_POST['total'] ) ? absint( $_POST['total'] ) : 0;

		if ( ! $libro_id || 'libro' !== get_post_type( $libro_id ) || $total_paginas < 1 ) {
			wp_send_json_error( array( 'mensaje' => 'Datos inválidos.' ), 400 );
		}

		$porcentaje = (int) round( ( $pagina_actual / $total_paginas ) * 100 );
		$porcentaje = max( 0, min( 100, $porcentaje ) ); // Nunca fuera de 0-100.

		update_user_meta(
			get_current_user_id(),
			'mgp_progreso_libro_' . $libro_id,
			array(
				'pagina_actual' => $pagina_actual,
				'total_paginas' => $total_paginas,
				'porcentaje'    => $porcentaje,
				'actualizado'   => current_time( 'mysql' ),
			)
		);

		wp_send_json_success( array( 'porcentaje' => $porcentaje ) );
	}
}
