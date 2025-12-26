<?php
/**
 * Plugin Name: SearchFilterSort
 * Description: Powerful AJAX search, filtering, and sorting for posts, custom post types, and WooCommerce products. Fully customizable and easy to use.
 * Version:     1.0.0
 * Author: Creative Werk Designs
 * Author URI: https://www.creativewerkdesigns.com/
 * Text Domain: searchfiltersort
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package searchfiltersort
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/autoload.php';

use SearchFilterSort\SFLTS_Plugin;

/**
 * Load the core plugin class.
 */

// require_once SFLTS_PLUGIN_DIR . 'includes/class-sflts-plugin.php'; phpcs:ignore.

/**
 * Initialize plugin.
 */
SFLTS_Plugin::sflts_plugin()->init();

/**
 * Add Settings link in the Plugins list.
 */
add_filter( 'plugin_action_links_' . SFLTS_PLUGIN_BASENAME, 'sflts_plugin_action_links' );

/**
 * Adds the Settings link to the plugin action links.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified action links.
 */
function sflts_plugin_action_links( $links ) {

	$settings_url = admin_url( 'edit.php?post_type=sflts-filters&page=sflts-settings' );

	$settings_link = '<a href="' . esc_url( $settings_url ) . '">' .
						esc_html__( 'Settings', 'searchfiltersort' ) .
					'</a>';

	array_unshift( $links, $settings_link );

	return $links;
}
