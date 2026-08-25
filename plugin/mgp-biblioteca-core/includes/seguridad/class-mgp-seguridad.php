<?php
/**
 * Clase: MGP_Seguridad
 *
 * Helpers estáticos de seguridad reutilizados por el resto del plugin.
 * Centralizar esto evita que cada módulo reinvente su propia validación
 * (y se le escape algún caso) — un solo lugar para auditar.
 *
 * @package MGP_Biblioteca_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MGP_Seguridad {

	/**
	 * Verifica el nonce de una petición AJAX del plugin. Devuelve true/false
	 * en vez de morir con wp_die(), para que quien llama decida la respuesta.
	 */
	public static function verificar_nonce_ajax(): bool {
		return isset( $_REQUEST['nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'mgp_biblioteca_nonce' );
	}

	/**
	 * Valida que un slug de categoría recibido del cliente sea REALMENTE
	 * una categoría existente de la taxonomía 'categoria_tecnica'.
	 * Nunca se debe pasar un valor de $_POST/$_GET directo a WP_Query sin
	 * esto: evita que alguien inyecte valores arbitrarios en la consulta.
	 */
	public static function sanitizar_slug_categoria( string $slug ): string {
		$slug = sanitize_title( $slug );

		if ( 'todos' === $slug || '' === $slug ) {
			return '';
		}

		$termino = get_term_by( 'slug', $slug, 'categoria_tecnica' );
		return $termino ? $slug : '';
	}

	/**
	 * Límite simple de peticiones por usuario/IP para endpoints AJAX
	 * sensibles (ej. progreso de lectura), usando un transient como
	 * contador. No reemplaza un firewall (eso lo cubre Wordfence), pero
	 * evita que un script mal hecho en el navegador sature el servidor.
	 */
	public static function limite_de_peticiones( string $accion, int $maximo = 20, int $ventana_segundos = 60 ): bool {
		$identificador = get_current_user_id() ? 'u' . get_current_user_id() : 'ip' . md5( self::ip_del_cliente() );
		$clave         = "mgp_rate_{$accion}_{$identificador}";
		$conteo        = (int) get_transient( $clave );

		if ( $conteo >= $maximo ) {
			return false; // Límite alcanzado: rechazar.
		}

		set_transient( $clave, $conteo + 1, $ventana_segundos );
		return true;
	}

	private static function ip_del_cliente(): string {
		// Nota: en hosting detrás de proxy/CDN (ej. Cloudflare), considerar
		// leer HTTP_CF_CONNECTING_IP en vez de REMOTE_ADDR más adelante.
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	}
}
