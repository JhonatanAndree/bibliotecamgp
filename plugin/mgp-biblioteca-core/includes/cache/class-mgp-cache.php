<?php
/**
 * Clase: MGP_Cache
 *
 * Helpers estáticos de caché sobre la Transients API de WordPress.
 *
 * Por qué esto importa en un hosting de 1GB de RAM: cada búsqueda o filtro
 * del catálogo, sin caché, dispara una consulta a MySQL. Con varios
 * estudiantes navegando el catálogo a la vez, eso satura CPU/RAM rápido en
 * hosting compartido. Cacheamos el HTML resultante unos minutos: la carga
 * real a MySQL baja drásticamente sin que el catálogo se sienta desactualizado.
 *
 * Nota técnica: si el hosting tiene Redis u Object Cache persistente
 * instalado, WordPress usa ese backend automáticamente para transients de
 * forma transparente — este código no necesita cambiar.
 *
 * Invalidación por grupo: en vez de recordar cada clave individual para
 * borrarla (imposible de mantener), usamos un "número de versión" por
 * grupo. Al invalidar, solo subimos la versión — las claves viejas quedan
 * huérfanas y expiran solas (TTL corto), sin necesitar limpieza manual.
 *
 * @package MGP_Biblioteca_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MGP_Cache {

	/** Tiempo de vida por defecto de una entrada de caché: 5 minutos. */
	const TTL_DEFECTO = 5 * MINUTE_IN_SECONDS;

	/**
	 * Arma la clave real de transient incluyendo la versión vigente del grupo.
	 */
	private static function clave_con_version( string $grupo, string $clave ): string {
		$version = get_option( "mgp_cache_version_{$grupo}", 1 );
		return "mgp_{$grupo}_{$version}_{$clave}";
	}

	public static function get( string $grupo, string $clave ) {
		return get_transient( self::clave_con_version( $grupo, $clave ) );
	}

	public static function set( string $grupo, string $clave, $valor, int $ttl = self::TTL_DEFECTO ): void {
		set_transient( self::clave_con_version( $grupo, $clave ), $valor, $ttl );
	}

	/**
	 * Invalida TODAS las entradas de un grupo (ej. "catalogo") de una sola
	 * vez, sin recorrer la base de datos. Se llama cuando un libro se crea,
	 * edita o borra.
	 */
	public static function invalidar_grupo( string $grupo ): void {
		$version_actual = (int) get_option( "mgp_cache_version_{$grupo}", 1 );
		update_option( "mgp_cache_version_{$grupo}", $version_actual + 1, false );
	}
}
