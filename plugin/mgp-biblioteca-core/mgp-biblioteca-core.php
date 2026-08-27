<?php
/**
 * Plugin Name:       Biblioteca Virtual MGP — Core
 * Plugin URI:        https://biblioteca.mgp.edu.pe/
 * Description:       Motor de datos y funciones de la biblioteca virtual del IESTP Manuel Gonzales Prada:
 *                     catálogo (CPT + taxonomía), lector de PDF protegido, guardado/progreso de lectura
 *                     y filtros del catálogo. Diseñado para hosting con recursos limitados (~1GB RAM).
 * Version:           0.3.0
 * Requires PHP:      8.0
 * Requires at least: 6.4
 * Author:            TI — IESTP Manuel Gonzales Prada
 * Text Domain:        mgp-biblioteca
 * License:           GPL-2.0-or-later
 *
 * NOTA PARA EL DESARROLLADOR QUE CONTINÚE ESTE PLUGIN:
 * Este plugin es intencionalmente independiente del tema. No debe depender de Elementor,
 * Hello Elementor, ni de ningún otro plugin salvo DearFlip (lector) y Members (login), que
 * se degradan con seguridad si no están activos (ver comprobaciones is_plugin_active más abajo).
 * Toda la lógica vive aquí para que sobreviva cambios de tema o de diseño futuro.
 */

// Seguridad: impedir acceso directo al archivo fuera de WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Constantes del plugin. Se usan en todo el código en vez de rutas/URLs
// escritas a mano, así un cambio de ubicación del plugin no rompe nada.
// ---------------------------------------------------------------------------
define( 'MGP_BIB_VERSION', '0.3.0' );
define( 'MGP_BIB_PATH', plugin_dir_path( __FILE__ ) );
define( 'MGP_BIB_URL', plugin_dir_url( __FILE__ ) );
define( 'MGP_BIB_BASENAME', plugin_basename( __FILE__ ) );

// ---------------------------------------------------------------------------
// Carga del núcleo. Cada clase se ocupa de UNA sola responsabilidad
// (principio de responsabilidad única) para que el plugin sea fácil de
// escalar: agregar una función nueva casi nunca implica tocar un archivo
// existente, solo sumar uno nuevo y registrarlo en el loader.
// ---------------------------------------------------------------------------
require_once MGP_BIB_PATH . 'includes/class-mgp-loader.php';

/**
 * Arranca el plugin enganchado a 'plugins_loaded' (no antes), para asegurar
 * que WordPress y los demás plugins (Elementor, Members, DearFlip) ya estén
 * disponibles antes de registrar nuestros hooks.
 */
function mgp_bib_bootstrap() {
	MGP_Loader::instance()->run();
}
add_action( 'plugins_loaded', 'mgp_bib_bootstrap' );

/**
 * Activación: registra el CPT/taxonomía y refresca los permalinks UNA vez.
 * Si esto no se hiciera, las URLs del catálogo (/catalogo/, /libro/xxx/)
 * darían 404 hasta que alguien visite manualmente Ajustes > Enlaces permanentes.
 */
function mgp_bib_activar() {
	require_once MGP_BIB_PATH . 'includes/cpt/class-mgp-cpt-libro.php';
	require_once MGP_BIB_PATH . 'includes/cpt/class-mgp-tax-categoria.php';
	require_once MGP_BIB_PATH . 'includes/lector/class-mgp-lector.php';
	( new MGP_CPT_Libro() )->registrar();
	( new MGP_Tax_Categoria() )->registrar();
	// IMPORTANTE: la regla de reescritura '/leer/{id}/' (lector de PDF) debe
	// quedar registrada ANTES del flush_rewrite_rules() de abajo. Si no,
	// WordPress guarda el set de reglas SIN la nuestra y la ruta da 404 hasta
	// el próximo flush manual. Bug real detectado y corregido el 27/08/2026
	// tras verificación en vivo — ver MEMORIA.md.
	( new MGP_Lector() )->registrar_ruta();
	MGP_Loader::crear_rol_bibliotecario();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'mgp_bib_activar' );

/**
 * Desactivación: solo limpia reglas de reescritura. NO borra datos (libros,
 * categorías, progreso de lectura de los estudiantes) — eso solo debe pasar
 * si el instituto lo pide explícitamente vía uninstall.php.
 */
function mgp_bib_desactivar() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'mgp_bib_desactivar' );
