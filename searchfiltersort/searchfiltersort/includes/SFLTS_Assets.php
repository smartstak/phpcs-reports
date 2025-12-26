<?php
/**
 * Asset manager responsible for loading all admin
 * and frontend CSS/JS files for SearchFilterSort.
 *
 * @package searchfiltersort
 */

namespace SearchFilterSort;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SearchFilterSort\SFLTS_Assets_URL;

/**
 * Class SFLTS_Assets
 *
 * Handles loading and localization of scripts/styles
 * for both the admin settings page and the frontend shortcode.
 */
class SFLTS_Assets {

	/**
	 * Singleton instance.
	 *
	 * @var SFLTS_Assets|null
	 */
	private static $instance = null;

	/**
	 * Accessor for the singleton instance.
	 *
	 * @return SFLTS_Assets
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * Registers admin and frontend enqueue callbacks.
	 */
	private function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'sflts_admin' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'sflts_frontend' ) );
	}

	/**
	 * Load admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function sflts_admin( $hook ) {
		if ( 'sflts-filters_page_sflts-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style(
			'sflts-admin-style',
			SFLTS_Assets_URL::assets_url(
				'css',
				'admin-style',
				'assets/css/',
				'assets/css/build/'
			),
			array(),
			SFLTS_VERSION
		);

		wp_enqueue_script(
			'sflts-admin',
			SFLTS_Assets_URL::assets_url(
				'js',
				'admin',
				'assets/js/',
				'assets/js/build/'
			),
			array( 'jquery', 'wp-color-picker' ),
			SFLTS_VERSION,
			true
		);

		wp_localize_script(
			'sflts-admin',
			'sfltsAdmin',
			array(
				'shortcodeBase' => '[SearchFilterSort',
				'strings'       => array(
					'copied' => esc_html__( 'Copied!', 'searchfiltersort' ),
				),
			)
		);
	}

	/**
	 * Load frontend scripts, styles, and dynamic inline CSS.
	 *
	 * @return void
	 */
	public function sflts_frontend() {
		if ( ! SFLTS_Helpers::sflts_has_shortcode() ) {
			return;
		}

		wp_enqueue_style(
			'sflts-frontend-style',
			SFLTS_Assets_URL::assets_url(
				'css',
				'frontend-style',
				'assets/css/',
				'assets/css/build/'
			),
			array(),
			SFLTS_VERSION
		);

		wp_enqueue_style(
			'sflts-nouislider',
			esc_url( SFLTS_PLUGIN_URL . 'assets/css/nouislider.min.css' ),
			array(),
			'15.7.0'
		);

		wp_enqueue_script(
			'sflts-nouislider',
			esc_url( SFLTS_PLUGIN_URL . 'assets/js/nouislider.min.js' ),
			array(),
			'15.7.0',
			true
		);

		wp_enqueue_script(
			'sflts-frontend',
			SFLTS_Assets_URL::assets_url(
				'js',
				'frontend',
				'assets/js/',
				'assets/js/build/'
			),
			array( 'jquery', 'sflts-nouislider' ),
			SFLTS_VERSION,
			true
		);

		wp_localize_script(
			'sflts-frontend',
			'sfltsData',
			array(
				'ajaxUrl' => esc_url( admin_url( 'admin-ajax.php' ) ),
				'nonce'   => wp_create_nonce( 'sflts_ajax_nonce' ),
				'strings' => array(
					'loading'   => esc_html__( 'Loading...', 'searchfiltersort' ),
					'noResults' => esc_html__( 'No results found.', 'searchfiltersort' ),
					'loadMore'  => esc_html__( 'Load More', 'searchfiltersort' ),
					'resetAll'  => esc_html__( 'Reset All', 'searchfiltersort' ),
					'prev'      => esc_html__( 'Previous', 'searchfiltersort' ),
					'next'      => esc_html__( 'Next', 'searchfiltersort' ),
				),
			)
		);

		// Get saved settings.
		$settings = SFLTS_Helpers::sflts_settings();

		// Default values if settings are missing.
		$defaults = array(
			'bg_color'         => '#ffffff',
			'text_color'       => '#000000',
			'title_color'      => '#333333',
			'padding'          => 10,
			'margin'           => 15,
			'columns'          => 3,
			'btn_bg_color'     => '#0073aa',
			'btn_text_color'   => '#ffffff',
			'btn_hover_bg'     => '#005177',
			'btn_hover_text'   => '#ffffff',
			'btn_border_color' => '#0073aa',
			'btn_radius'       => 5,
			'btn_padding'      => 10,
			'btn_margin'       => 5,
		);

		$settings = wp_parse_args( $settings, $defaults );

		// Escape dynamic values before adding inline CSS.
		$inline_css = sprintf(
			':root {
				--sflts-bg-color: %s;
				--sflts-text-color: %s;
				--sflts-title-color: %s;
				--sflts-item-padding: %spx;
				--sflts-item-margin: %spx;
				--sflts-columns: %s;
				--sflts-button-bg: %s;
				--sflts-button-text: %s;
				--sflts-button-hover-bg: %s;
				--sflts-button-hover-text: %s;
				--sflts-button-border: %s;
				--sflts-button-radius: %spx;
				--sflts-button-padding: %spx;
				--sflts-button-margin: %spx;
			}',
			esc_attr( $settings['bg_color'] ),
			esc_attr( $settings['text_color'] ),
			esc_attr( $settings['title_color'] ),
			intval( $settings['padding'] ),
			intval( $settings['margin'] ),
			intval( $settings['columns'] ),
			esc_attr( $settings['btn_bg_color'] ),
			esc_attr( $settings['btn_text_color'] ),
			esc_attr( $settings['btn_hover_bg'] ),
			esc_attr( $settings['btn_hover_text'] ),
			esc_attr( $settings['btn_border_color'] ),
			intval( $settings['btn_radius'] ),
			intval( $settings['btn_padding'] ),
			intval( $settings['btn_margin'] )
		);

		wp_add_inline_style( 'sflts-frontend', $inline_css );
	}
}
