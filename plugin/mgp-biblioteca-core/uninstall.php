<?php
/**
 * uninstall.php — se ejecuta SOLO cuando el plugin se elimina desde
 * Plugins > Eliminar (no al desactivar). WordPress lo invoca automáticamente,
 * nunca se llama a mano.
 *
 * Decisión de diseño importante: NO borramos los libros (CPT 'libro'), ni
 * la taxonomía, ni el progreso de lectura de los estudiantes. Esos son
 * datos del instituto que deben sobrevivir aunque alguien desinstale el
 * plugin por error. Solo limpiamos las opciones internas de caché.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'mgp_cache_version_%'" );
