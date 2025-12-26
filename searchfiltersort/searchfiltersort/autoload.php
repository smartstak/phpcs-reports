<?php
/**
 * Plugin Autoloader Bootstrap file
 *
 * @package searchfiltersort
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Composer autoloader path.
$sflts_composer_autoload = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $sflts_composer_autoload ) ) {
	require_once $sflts_composer_autoload;
} else {
	exit;
}

/**
 * Core plugin constants.
 */
define( 'SFLTS_ENVIRONMENT', 'production' ); // or development, Changed.
define( 'SFLTS_VERSION', '1.0.0' );
define( 'SFLTS_PLUGIN_FILE', __FILE__ );
define( 'SFLTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SFLTS_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) ); // Changed.
define( 'SFLTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SFLTS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
