<?php
/**
 * Initialize the plugin.
 *
 * Loads required files, defines constants, registers hooks,
 * and initializes core components of the plugin.
 *
 * @package searchfiltersort
 */

namespace SearchFilterSort;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SearchFilterSort\SFLTS_Helpers;
use SearchFilterSort\SFLTS_CPT;
use SearchFilterSort\SFLTS_Assets;
use SearchFilterSort\SFLTS_Settings;
use SearchFilterSort\SFLTS_Query;
use SearchFilterSort\SFLTS_Shortcode;
use SearchFilterSort\SFLTS_Ajax;

/**
 * Main plugin controller class.
 *
 * Handles initialization, loading files, constants, activation/deactivation,
 * and bootstrapping all core plugin components.
 *
 * @package searchfiltersort
 */
class SFLTS_Plugin {

	/**
	 * Instance.
	 *
	 * @var SFLTS_Plugin
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return SFLTS_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Bootstrap plugin.
	 */
	public function init() {
		$this->sflts_define_constants();

		add_action( 'init', array( new SFLTS_CPT(), 'sflts_register_cpt' ) );
		register_activation_hook( SFLTS_PLUGIN_FILE, array( $this, 'sflts_activate' ) );
		register_deactivation_hook( SFLTS_PLUGIN_FILE, array( $this, 'sflts_deactivate' ) );

		SFLTS_Settings::instance();
		SFLTS_Assets::instance();
		SFLTS_Shortcode::instance();
		SFLTS_Ajax::instance();
	}

	/**
	 * Constants.
	 */
	private function sflts_define_constants() {
		if ( ! defined( 'SFLTS_FEATURE_FLAGS' ) ) {
			define(
				'SFLTS_FEATURE_FLAGS',
				apply_filters(
					'sflts_feature_flags',
					array(
						'custom_filters'   => false,
						'advanced_layouts' => false,
						'extended_search'  => false,
						'custom_sorting'   => false,
						'elementor_widget' => false,
					)
				)
			);
		}
	}

	/**
	 * Activation hook.
	 */
	public function sflts_activate() {
		if ( ! get_option( 'sflts_settings' ) ) {
			add_option( 'sflts_settings', SFLTS_Helpers::sflts_defaults() );
		}
		new SFLTS_CPT()->sflts_register_cpt();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation hook.
	 */
	public function sflts_deactivate() {
		flush_rewrite_rules();
	}
	/**
	 * Global helper accessor.
	 *
	 * Allows access to the main plugin instance.
	 *
	 * @return SFLTS_Plugin
	 */
	public static function sflts_plugin() {
		return self::instance();
	}
}
