<?php

/**

 * Handle plugin installation upon activation.
 *
 * @package wpsyncsheets-elementor
 */

if( !defined( 'ABSPATH' ) ) {
	exit;
}

use ElementorPro\Plugin;

use Elementor\Controls_Manager;

use ElementorPro\Modules\Forms\Module;

use WPSyncSheetsElementor\WPSSLE_Google_API_Functions;

/**

 * Class WPSSLE_Plugin_Setting.
 *
 * @since 1.0.0
 */

class WPSSLE_Plugin_Setting {

	/**

	 * Plugin documentation URL
	 *
	 * @var $documentation
	 */

	protected static $documentation = 'https://docs.wpsyncsheets.com/wpsse-setup-guide/';

	/**

	 * Url for plugin api settings documentation.
	 *
	 * @var $doc_sheet_setting .
	 */

	protected static $doc_sheet_setting = 'https://docs.wpsyncsheets.com/wpsse-google-sheets-api-settings/';

	/**

	 * Url for plugin support.
	 *
	 * @var $submit_ticket
	 */

	protected static $submit_ticket = 'https://wordpress.org/support/plugin/wpsyncsheets-elementor/';

	/**

	 * Instance of Plugin_Settings
	 *
	 * @var $instance
	 */

	private static $instance = null;

	/**

	 * Instance of WPSSLE_Feed_Settings
	 *
	 * @var $instanceaddon
	 */

	private static $instanceaddon = null;

	/**

	 * Instance of Google_API_Functions
	 *
	 * @var $instance_api
	 */

	private static $instance_api = null;

	/**

	 * Initialization
	 */
	public static function wpssle_initilization() {

		register_activation_hook( WPSSLE_BASE_FILE, __CLASS__ . '::wpssle_activation' );

		register_deactivation_hook( WPSSLE_BASE_FILE, __CLASS__ . '::wpssle_deactivation' );

		add_action( 'admin_menu', __CLASS__ . '::wpssle_menu_page', 50 );

		add_action( 'elementor/editor/after_enqueue_scripts', __CLASS__ . '::wpssle_load_wp_admin_style' );

		add_action( 'admin_enqueue_scripts', __CLASS__ . '::wpssle_selectively_enqueue_admin_script' );

		add_action( 'admin_enqueue_scripts', __CLASS__ . '::wpssle_load_wp_admin_style' );

		add_filter( 'plugin_row_meta', __CLASS__ . '::wpssle_plugin_row_meta', 10, 2 );

		add_action( 'elementor_pro/init', __CLASS__ . '::wpssle_init' );

		add_action( 'elementor/ajax/register_actions', __CLASS__ . '::wpssle_ajax_register_action' );

		add_action( 'elementor/editor/after_save', __CLASS__ . '::wpssle_after_save_settings', 9999 );

		add_action( 'wp_ajax_wpssle_reset_settings', __CLASS__ . '::wpssle_reset_settings' );

		self::wpssle_google_api();

		self::instance();

		// Dashboard Install & Active AJAX

		add_action( 'wp_ajax_install_and_activate_plugin', array( __CLASS__, 'wpssle_install_and_activate_plugin' ) );
	}

	/**

	 * Main WPSSLE_Plugin_Settings Instance.
	 *
	 * @since 1.0.0
	 *
	 * @return instance
	 */
	public static function instance() {

		if ( null === self::$instance ) {

			self::$instance = new self();

		}

		return self::$instance;
	}

	/**

	 * Create WPSSLE_Feed_Settings Class Instance.
	 */
	public static function get_addoninstance() {

		if ( null === self::$instanceaddon ) {

			self::$instanceaddon = new \WPSSLE_Feed_Settings();

		}

		return self::$instanceaddon;
	}

	/**

	 * Create Google Api Instance.
	 */
	public static function wpssle_google_api() {

		if ( null === self::$instance_api ) {

			self::$instance_api = new WPSSLE_Google_API_Functions();

		}

		return self::$instance_api;
	}

	/**

	 * WPSSLE Plugin Activation Hook
	 */
	public static function wpssle_activation() {

		update_option( 'active_wpssle', 1 );
	}

	/**

	 * WPSSLE Plugin Deactivation Hook
	 */
	public static function wpssle_deactivation() {

		update_option( 'active_wpssle', '' );
	}

	/**

	 * Action fire after Save from Elementor Editor.
	 *
	 * @param int $wpssle_post_id Post ID.
	 */
	public static function wpssle_after_save_settings( $wpssle_post_id ) {

		global $wpssle_header_list, $wpssle_spreadsheetid, $wpssle_exclude_headertype;

		$wpssle_exclude_headertype = array( 'honeypot', 'recaptcha', 'recaptcha_v3', 'html' );

		// phpcs:ignore

		if ( ! isset( $_REQUEST['actions'] ) || empty( $_REQUEST['actions'] ) ) {

			return;

		}

		// phpcs:ignore

		$wpssle_data = json_decode( sanitize_text_field( wp_unslash( $_REQUEST['actions'] ) ), true );

		$wpssle_data = Plugin::elementor()->db->iterate_data(
			$wpssle_data,
			function ( $wpssle_element ) use ( &$do_update ) {

				if ( 'form' === (string) $wpssle_element['widgetType'] || 'global' === (string) $wpssle_element['widgetType'] ) {

					global $wpssle_header_list, $wpssle_spreadsheetid, $wpssle_exclude_headertype;

					$wpssle_exclude_headertype = array( 'honeypot', 'recaptcha', 'recaptcha_v3', 'html' );

					if ( isset( $wpssle_element['settings'] ) && isset( $wpssle_element['settings']['submit_actions'] ) && in_array( 'WPSyncSheets', $wpssle_element['settings']['submit_actions'], true ) ) {

						$wpssle_settings = $wpssle_element['settings'] ?? array();

						$wpssle_header_list = array();

						$wpssle_spreadsheetid = $wpssle_settings['spreadsheetid'] ?? '';

						$wpssle_sheetname = $wpssle_settings['sheet_name'] ?? '';

						$wpssle_sheetheaders = $wpssle_settings['sheet_headers'] ?? array();

						$wpssle_freeze_header = $wpssle_settings['freeze_header'] ?? '';

						if ( isset( $wpssle_settings['form_fields'] ) ) {

							foreach ( $wpssle_settings['form_fields'] as $wpssle_form_fields ) {

								if ( ( ! isset( $wpssle_form_fields['field_type'] ) || ( isset( $wpssle_form_fields['field_type'] ) && ! in_array( $wpssle_form_fields['field_type'], $wpssle_exclude_headertype, true ) ) ) && in_array( $wpssle_form_fields['custom_id'], $wpssle_sheetheaders, true ) ) {

									$wpssle_header_list[] = $wpssle_form_fields['field_label'] ? $wpssle_form_fields['field_label'] : ucfirst( $wpssle_form_fields['custom_id'] );

								}
							}
						}

						$wpssle_is_new = 0;

						if ( 'new' === (string) $wpssle_spreadsheetid ) {

							$wpssle_newsheetname = $wpssle_settings['new_spreadsheet_name'] ? trim( $wpssle_settings['new_spreadsheet_name'] ) : '';

							/*
							 *Create new spreadsheet

							 */

							$requestbody = self::$instance_api->createspreadsheetobject( $wpssle_newsheetname );

							$wpssle_response = self::$instance_api->createspreadsheet( $requestbody );

							$wpssle_spreadsheetid = $wpssle_response['spreadsheetId'];

							$wpssle_is_new = 1;

						}

						$wpssle_existingsheetsnames = array();

						$response = self::$instance_api->get_sheet_listing( $wpssle_spreadsheetid );

						$wpssle_existingsheetsnames = self::$instance_api->get_sheet_list( $response );

						$wpsse_sheetid = isset( $wpssle_existingsheetsnames[ $wpssle_sheetname ] ) ? $wpssle_existingsheetsnames[ $wpssle_sheetname ] : '';

						if ( ! $wpsse_sheetid ) {

							/*
							 *Create new sheet into spreadsheet

							 */

							$wpssle_body = self::$instance_api->createsheetobject( $wpssle_sheetname );

							try {

								$requestobject = array();

								$requestobject['spreadsheetid'] = $wpssle_spreadsheetid;

								$requestobject['requestbody'] = $wpssle_body;

								self::$instance_api->formatsheet( $requestobject );

							} catch ( Exception $e ) {

								echo esc_html( $e->getMessage() );

							}

							/*
							 * Insert Sheet Headers into sheet

							 */

							$wpssle_header_list = array_values( array_unique( $wpssle_header_list ) );

							$wpssle_range = trim( $wpssle_sheetname ) . '!A1';

							$wpssle_requestbody = self::$instance_api->valuerangeobject( array( $wpssle_header_list ) );

							$wpssle_params = self::$instance->get_row_format();

							$param = self::$instance_api->setparamater( $wpssle_spreadsheetid, $wpssle_range, $wpssle_requestbody, $wpssle_params );

							self::$instance_api->appendentry( $param );

							if ( $wpssle_is_new ) {

								$wpssle_requestbody = self::$instance_api->deletesheetobject();

								$requestobject = array();

								$requestobject['spreadsheetid'] = $wpssle_spreadsheetid;

								$requestobject['requestbody'] = $wpssle_requestbody;

								self::$instance_api->formatsheet( $requestobject );

							}
						} else {

							$wpssle_range = trim( $wpssle_sheetname ) . '!A1:ZZ1';

							$wpssle_response = self::$instance_api->get_row_list( $wpssle_spreadsheetid, $wpssle_range );

							$wpssle_data = $wpssle_response->getValues();

							if ( empty( $wpssle_data ) ) {

								$wpssle_data = array();

								$existingheaders = array();

							} else {

								$existingheaders = $wpssle_data[0];

							}

							$deleterequestarray = array();

							$requestarray = array();

							if ( $existingheaders !== $wpssle_header_list ) {

								// Delete deactivate column from sheet.

								$wpsse_column = array_diff( $existingheaders, $wpssle_header_list );

								if ( ! empty( $wpsse_column ) ) {

									$wpsse_column = array_reverse( $wpsse_column, true );

									foreach ( $wpsse_column as $columnindex => $columnval ) {

										unset( $existingheaders[ $columnindex ] );

										$existingheaders = array_values( $existingheaders );

										$param = array();

										$startindex = $columnindex;

										$endindex = $columnindex + 1;

										$param = self::$instance_api->prepare_param( $wpsse_sheetid, $startindex, $endindex );

										$deleterequestarray[] = self::$instance_api->deleteDimensionrequests( $param );

									}
								}

								try {

									if ( ! empty( $deleterequestarray ) ) {

										$param = array();

										$param['spreadsheetid'] = $wpssle_spreadsheetid;

										$param['requestarray'] = $deleterequestarray;

										$wpsse_response = self::$instance_api->updatebachrequests( $param );

									}
								} catch ( Exception $e ) {

									echo esc_html( 'Message: ' . $e->getMessage() );

								}
							}

							if ( $existingheaders !== $wpssle_header_list ) {

								foreach ( $wpssle_header_list as $key => $hname ) {

									$wpsse_startindex = array_search( $hname, $existingheaders, true );

									if ( false !== $wpsse_startindex && ( isset( $existingheaders[ $key ] ) && $existingheaders[ $key ] !== $hname ) ) {

										unset( $existingheaders[ $wpsse_startindex ] );

										$existingheaders = array_merge( array_slice( $existingheaders, 0, $key ), array( 0 => $hname ), array_slice( $existingheaders, $key, count( $existingheaders ) - $key ) );

										$wpsse_endindex = $wpsse_startindex + 1;

										$wpsse_destindex = $key;

										$param = array();

										$param = self::$instance_api->prepare_param( $wpsse_sheetid, $wpsse_startindex, $wpsse_endindex );

										$param['destindex'] = $wpsse_destindex;

										$requestarray[] = self::$instance_api->moveDimensionrequests( $param );

									} elseif ( false === $wpsse_startindex ) {

										$existingheaders = array_merge( array_slice( $existingheaders, 0, $key ), array( 0 => $hname ), array_slice( $existingheaders, $key, count( $existingheaders ) - $key ) );

										$param = array();

										$wpsse_startindex = $key;

										$wpsse_endindex = $key + 1;

										$param = self::$instance_api->prepare_param( $wpsse_sheetid, $wpsse_startindex, $wpsse_endindex );

										$requestarray[] = self::$instance_api->insertdimensionrequests( $param, 'COLUMNS', false );

									}
								}

								if ( ! empty( $requestarray ) ) {

									$param = array();

									$param['spreadsheetid'] = $wpssle_spreadsheetid;

									$param['requestarray'] = $requestarray;

									$wpsse_response = self::$instance_api->updatebachrequests( $param );

								}

								if ( count( $existingheaders ) > count( $wpssle_header_list ) ) {

									$diff = count( $existingheaders ) - count( $wpssle_header_list );

									$wpssle_header_list = array_merge( $wpssle_header_list, array_fill( 0, $diff, '' ) );

								}
							}

							$wpssle_range = trim( $wpssle_sheetname ) . '!A1';

							$wpssle_requestbody = self::$instance_api->valuerangeobject( array( $wpssle_header_list ) );

							$wpssle_params = self::$instance->get_row_format();

							$param = self::$instance_api->setparamater( $wpssle_spreadsheetid, $wpssle_range, $wpssle_requestbody, $wpssle_params );

							self::$instance_api->updateentry( $param );

						}

						if ( 'yes' === (string) $wpssle_freeze_header ) {

							$wpssle_freeze = 1;

						} else {

							$wpssle_freeze = 0;

						}

						self::$instance->wpssle_freeze_header( $wpssle_spreadsheetid, $wpssle_sheetname, $wpssle_freeze );

					}
				}
			}
		);

		if ( ! empty( $wpssle_spreadsheetid ) && 'new' !== (string) $wpssle_spreadsheetid ) {

			$wpssle_saved_data = get_post_meta( $wpssle_post_id, '_elementor_data' );

			$wpssle_data = json_decode( $wpssle_saved_data[0], true );

			global $existincurrentpage;

			$existincurrentpage = 'no';

			array_walk_recursive(
				$wpssle_data,
				function ( &$existvalue ) {

					if ( 'WPSyncSheets' === (string) $existvalue ) {

						global $existincurrentpage;

						$existincurrentpage = 'yes';

					}
				}
			);

			array_walk_recursive(
				$wpssle_data,
				function ( &$existvalue, $existkey ) {

					if ( 'widgetType' === (string) $existkey ) {

						global $existincurrentpage;

						if ( 'form' === (string) $existvalue ) {

							$existincurrentpage = 'yes';

						} else {

							$existincurrentpage = 'no';

						}
					}
				}
			);

			array_walk_recursive(
				$wpssle_data,
				function ( &$value, $key ) {

					global $existincurrentpage, $wpssle_spreadsheetid;

					if ( 'yes' === (string) $existincurrentpage ) {

						if ( 'spreadsheetid' === (string) $key ) {

							$value = $wpssle_spreadsheetid;

						}

						if ( 'new_spreadsheet_name' === (string) $key ) {

							$value = '';

						}
					}
				}
			);

			if ( 'yes' === (string) $existincurrentpage ) {

				$wpssle_json_value = wp_slash( wp_json_encode( $wpssle_data ) );

				update_post_meta( $wpssle_post_id, '_elementor_data', $wpssle_json_value );

			}
		}
	}

	/**

	 * Freeze First Row of the Google Spreadsheet.
	 *
	 * @param string $wpssle_spreadsheetname Spreadsheet ID.

	 * @param string $wpssle_sheetname Sheet Name.

	 * @param int    $wpssle_freeze 1 - Freeze Header, 0 - Unfreeze header.
	 */
	public static function wpssle_freeze_header( $wpssle_spreadsheetname, $wpssle_sheetname, $wpssle_freeze ) {

		$response = self::$instance_api->get_sheet_listing( $wpssle_spreadsheetname );

		$wpssle_existingsheetsnames = self::$instance_api->get_sheetid_list( $response );

		$wpssle_is_exist = array_search( $wpssle_sheetname, $wpssle_existingsheetsnames, true );

		if ( $wpssle_is_exist ) {

			$requestbody = self::$instance_api->freezeobject( $wpssle_is_exist, $wpssle_freeze );

			$requestobject = array();

			$requestobject['spreadsheetid'] = $wpssle_spreadsheetname;

			$requestobject['requestbody'] = $requestbody;

			self::$instance_api->formatsheet( $requestobject );

		}
	}

	/**

	 * Initialize Feed Addon.
	 */
	public static function wpssle_init() {

		// Here its safe to include our action class file.

		include_once __DIR__ . '/class-wpssle-form-sheets-action.php';

		// Instantiate the action class.

		$wpssle_action = new WPSSLE_Form_Sheets_Action();

		// Register the action with form widget.

		\ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( $wpssle_action->get_name(), $wpssle_action );
	}

	/**

	 * Load JS and CSS File.
	 */
	public static function wpssle_load_wp_admin_style() {

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';// phpcs:ignore

		// phpcs:ignore

		if ( $page && 'wpsyncsheets-elementor' === $page ) {

			wp_register_style(
				'wpssle-wp-admin-style',
				WPSSLE_Assets_URL::assets_url(
					'css',
					'wpssle-admin-style',
					'assets/css/',
					'assets/css/build/'
				),
				false,
				WPSSLE_VERSION
			);

			wp_enqueue_style( 'wpssle-wp-admin-style' );

			wp_register_script(
				'wpssle-wp-admin-script',
				WPSSLE_Assets_URL::assets_url(
					'js',
					'wpssle-admin-script',
					'assets/js/',
					'assets/js/build/'
				),
				false,
				WPSSLE_VERSION,
				true
			);

			wp_localize_script(
				'wpssle-wp-admin-script',
				'admin_ajax_object',
				array(

					'ajaxurl'              => admin_url( 'admin-ajax.php' ),

					'sync_nonce_token'     => wp_create_nonce( 'sync_nonce' ),

					'adminSettingsPageUrl' => admin_url( 'admin.php?page=wpsyncsheets-elementor' ),

				)
			);

			wp_enqueue_script( 'wpssle-wp-admin-script' );

			wp_register_style(
				'wpssle-wp-admin-plugin-setting',
				WPSSLE_Assets_URL::assets_url(
					'css',
					'wpssle-admin-plugin-setting-style',
					'assets/css/',
					'assets/css/build/'
				),
				false,
				WPSSLE_VERSION
			);

			wp_enqueue_style( 'wpssle-wp-admin-plugin-setting' );

		}

		// phpcs:ignore

		if ( isset( $_GET['action'] ) && 'elementor' === $page ) {

			wp_register_script( 'wpssle-wp-customadmin', plugin_dir_url( __DIR__ ) . 'assets/js/wpssle-custom-elementor.js', false, WPSSLE_VERSION, true );

			wp_localize_script(
				'wpssle-wp-customadmin',
				'customadmin_ajax_object',
				array(

					'ajaxurl'          => admin_url( 'admin-ajax.php' ),

					'sync_nonce_token' => wp_create_nonce( 'sync_nonce' ),

				)
			);

			wp_enqueue_script( 'wpssle-wp-customadmin' );

		}

		wp_add_inline_style(
			'wp-admin',
			'#toplevel_page_wpsyncsheets_lite .wp-menu-image img {

	            width: 20px;

	            height: auto;

	            filter: brightness(100);

	        }

	        #toplevel_page_wpsyncsheets_lite:hover .wp-menu-image img {

	            filter: unset;

	        }

	        #toplevel_page_wpsyncsheets_lite.wp-has-current-submenu .wp-menu-image img {

	            filter: brightness(100) !important;

	        }'
		);
	}

	/**

	 * Enqueue css and js files
	 */
	public static function wpssle_selectively_enqueue_admin_script() {

		wp_enqueue_script(
			'wpssle-general-script',
			WPSSLE_Assets_URL::assets_url(
				'js',
				'wpssle-general',
				'assets/js/',
				'assets/js/build/'
			),
			WPSSLE_VERSION,
			true,
			false
		);
	}

	/**

	 * Show row meta on the plugin screen.
	 *
	 * @param  mixed $wpssle_links Plugin Row Meta.

	 * @param  mixed $wpssle_file  Plugin Base file.

	 * @return array
	 */
	public static function wpssle_plugin_row_meta( $wpssle_links, $wpssle_file ) {

		if ( 'wpsyncsheets-elementor/wpsyncsheets-lite-elementor.php' === (string) $wpssle_file ) {

			$wpssle_row_meta = array(

				'docs' => '<a href="' . esc_url( WPSSLE_DOCUMENTATION_URL ) . '" title="' . esc_attr( __( 'View Documentation', 'wpsse' ) ) . '" target="_blank">' . esc_html__( 'View Documentation', 'wpsse' ) . '</a>',

			);

			return array_merge( $wpssle_links, $wpssle_row_meta );

		}

		return (array) $wpssle_links;
	}

	/**

	 * Register a plugin menu page.
	 */
	public static function wpssle_menu_page() {

		global $admin_page_hooks, $_parent_pages;

		if ( ! isset( $admin_page_hooks['wpsyncsheets_lite'] ) ) {

			$wpssle_page = add_menu_page(
				esc_attr__( 'WPSyncSheets Lite', 'wpsse' ),
				'WPSyncSheets Lite',
				'manage_options',
				'wpsyncsheets_lite',
				'',
				WPSSLE_URL . 'assets/images/dashicons-wpsyncsheets.svg',
				90
			);

		}

		add_submenu_page( 'wpsyncsheets_lite', 'Google Sheets API Settings', 'Google Sheets API Settings', 'manage_options', 'wpsyncsheets_lite', __CLASS__ . '::wpssle_elementor_sheets_plugin_page' );

		add_submenu_page( 'wpsyncsheets_lite', 'WPSyncSheets Lite For Elementor', 'For Elementor', 'manage_options', 'wpsyncsheets-elementor', __CLASS__ . '::wpssle_elementor_sheets_plugin_page', 1 );

		self::remove_duplicate_submenu_page();
	}

	/**

	 * Remove duplicate submenu

	 * Submenu page hack: Remove the duplicate WPSyncSheets Plugin link on subpages
	 */
	public static function remove_duplicate_submenu_page() {

		remove_submenu_page( 'wpsyncsheets_lite', 'wpsyncsheets_lite' );
	}

	/**

	 * Show wpssle plugin screen.
	 */
	public static function wpssle_elementor_sheets_plugin_page() {

		$wpssle_error = '';

		$wpssle_token_error = false;

		$wpssle_error_general = '';

		$wpssle_apisettings = '';

		$wpssle_generalsettings = '';

		$wpssle_emsettings = '';

		$wpssle_supportsettings = '';

		if ( ! isset( $_GET['tab'] ) ) {

			// Google API Settings Tab.

			if ( isset( $_POST['submit'] ) || isset( $_POST['revoke'] ) ) {

				if ( ! isset( $_POST['wpssle_api_settings'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpssle_api_settings'] ) ), 'save_api_settings' ) ) {

					$wpssle_error = '<div class="error token_error"><p><strong class="err-msg">Error: Sorry, your nonce did not verify.</strong></p></div>';

				} else {

					if ( isset( $_POST['client_token'] ) ) {

						$wpssle_clienttoken = sanitize_text_field( wp_unslash( $_POST['client_token'] ) );

					} else {

						$wpssle_clienttoken = '';

					}

					if ( isset( $_POST['client_id'] ) && isset( $_POST['client_secret'] ) ) {

						$wpssle_google_settings = array( sanitize_text_field( wp_unslash( $_POST['client_id'] ) ), sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ), $wpssle_clienttoken );

					} else {

						$wpssle_google_settings_value = self::$instance_api->wpssle_option( 'wpsse_google_settings' );

						$wpssle_google_settings = array( $wpssle_google_settings_value[0], $wpssle_google_settings_value[1], $wpssle_clienttoken );

					}

					self::$instance_api->wpssle_update_option( 'wpsse_google_settings', $wpssle_google_settings );

					if ( isset( $_POST['revoke'] ) ) {

						$wpssle_google_settings = self::$instance_api->wpssle_option( 'wpsse_google_settings' );

						$wpssle_google_settings[2] = '';

						self::$instance_api->wpssle_update_option( 'wpsse_google_settings', $wpssle_google_settings );

						self::$instance_api->wpssle_update_option( 'wpsse_google_accessToken', '' );

					}
				}
			}
		}

		if ( isset( $_GET['code'] ) && ! empty( sanitize_text_field( wp_unslash( $_GET['code'] ) ) ) ) {

			$wpssle_code = sanitize_text_field( wp_unslash( $_GET['code'] ) );

			$wpssle_token_value = $wpssle_code;

			$wpssle_google_settings = self::$instance_api->wpssle_option( 'wpsse_google_settings' );

			$wpssle_google_settings[2] = $wpssle_code;

			self::$instance_api->wpssle_update_option( 'wpsse_google_settings', $wpssle_google_settings );

		}

		$wpssle_google_settings_value = self::$instance_api->wpssle_option( 'wpsse_google_settings' );

		if ( ! empty( $wpssle_google_settings_value[2] ) ) {

			if ( ! self::$instance_api->checkcredenatials() ) {

				$wpssle_error = self::$instance_api->getClient( 1 );

				if ( 'Invalid token format' === (string) $wpssle_error ) {

					$wpssle_error = '<div class="error token_error"><p><strong class="err-msg">Error: Invalid Token - Revoke Token with below settings and try again.</strong></p></div>';

				} else {

					$wpssle_error = '<div class="error token_error"><p><strong class="err-msg">Error: ' . $wpssle_error . '</strong></p></div>';

				}

				$wpssle_token_error = true;

			}
		}

		if ( empty( $wpssle_error ) && ! empty( $wpssle_google_settings_value[2] ) ) {

			try {

				$spreadsheets_list = self::$instance_api->get_spreadsheet_listing();

			} catch ( Exception $e ) {

				$error = json_decode( $e->getMessage(), true );

				$reason = '';

				if ( isset( $error['error'] ) && is_array( $error['error'] ) ) {

					$errors = isset( $error['error']['errors'] ) ? $error['error']['errors'] : array();

					foreach ( $errors as $err ) {

						$reason = isset( $err['reason'] ) ? $err['reason'] : '';

					}
				} elseif ( isset( $error['error'] ) ) {

					$reason = $error['error'];

				}

				if ( 'insufficientPermissions' === (string) $reason ) {

					$wpssle_error = '<div class="error token_error"><p><strong class="err-msg">Error: Insufficient Permissions - Revoke Token with below settings and when generating access token select all the permissions.</strong></p></div>';

				}

				if ( 'accessNotConfigured' === (string) $reason ) {

					$wpssle_error = '<div class="error token_error"><p><strong class="err-msg">Error: Access not Configured - Please enable Google Sheets API and Google Drive API. Follow the <a target="_blank" href="https://docs.wpsyncsheets.com/wpssc-google-sheets-api-settings/" style="color:#000;">Google Sheets API Settings documentation</a> to enable APIs.</strong></p></div>';

				}

				if ( empty( $wpssle_error ) && '' !== (string) $reason ) {

					$wpssle_error = '<div class="error token_error"><p><strong class="err-msg">Error: Invalid Credentials - Reset settings and try again.</strong></p></div>';

				}

				$wpssle_token_error = true;

			}
		}

		$show_settings = false;

		if ( false === (bool) $wpssle_token_error ) {

			$show_settings = true;

		}

		?>

		<!-- .wrap -->

		<div class="wpssle-main-wrap">

			<div class="wpssw_order_top_header"> <!-- ONLY this will be hidden -->

						<div class="wpssw_top_header_content_wrapper">

							<p class="wpssw_top_header_text">

								You're using our free version. To unlock more features,

								<a href="<?php echo WPSSLE_BUY_PRO_VERSION_URL; ?>" target="_blank">upgrade to pro →</a>

							</p>

						</div>

						<button class="wpssw_close_btn" onclick="wc_closeTopHeader()">×</button>

			</div>

			<div class="wpssle-header-main">

				<div class="container">

					<div class="wpssle-header-section">

						<div class="wpssle-header-left">

							<div class="wpssle-logo-section">

								<img src="<?php echo esc_url( WPSSLE_URL . 'assets/images/logo.svg?ver=' ); ?><?php echo esc_attr( WPSSLE_VERSION ); ?>">

							</div>

							<div class="wpssle-nav-top">

								<ul>

									<li>

										<button class="navtablinks wpssle-nav-dashboard" onclick="wpssleNavTab(event, 'wpssle-nav-dashboard')">

											<svg xmlns="http://www.w3.org/2000/svg" width="21" height="19" viewBox="0 0 21 19" fill="none">

												<path d="M21.3316 11.8281L10.6923 3.5701L0.0529785 11.8281V8.46321L10.6923 0.203247L21.3316 8.46128V11.8281ZM18.6717 11.5283V19.5087H13.3521V14.1891H8.03245V19.5087H2.7128V11.5293L10.6923 5.54514L18.6717 11.5283Z" fill="#64748B"/>

											</svg>

											Dashboard

										</button>

									</li>

									<li>

										<button class="navtablinks wpssle-nav-googleapi" onclick="wpssleNavTab(event, 'wpssle-nav-googleapi')">

											<svg width="21" height="19" viewBox="0 0 21 19" fill="none" xmlns="http://www.w3.org/2000/svg">

												<path d="M5.21777 19C3.83444 19 2.65527 18.5125 1.68027 17.5375C0.705273 16.5625 0.217773 15.3833 0.217773 14C0.217773 12.7833 0.59694 11.7208 1.35527 10.8125C2.11361 9.90417 3.06777 9.33333 4.21777 9.1V11.175C3.63444 11.375 3.15527 11.7333 2.78027 12.25C2.40527 12.7667 2.21777 13.35 2.21777 14C2.21777 14.8333 2.50944 15.5417 3.09277 16.125C3.67611 16.7083 4.38444 17 5.21777 17C6.05111 17 6.75944 16.7083 7.34277 16.125C7.92611 15.5417 8.21777 14.8333 8.21777 14V13H14.0928C14.2261 12.85 14.3886 12.7292 14.5803 12.6375C14.7719 12.5458 14.9844 12.5 15.2178 12.5C15.6344 12.5 15.9886 12.6458 16.2803 12.9375C16.5719 13.2292 16.7178 13.5833 16.7178 14C16.7178 14.4167 16.5719 14.7708 16.2803 15.0625C15.9886 15.3542 15.6344 15.5 15.2178 15.5C14.9844 15.5 14.7719 15.4542 14.5803 15.3625C14.3886 15.2708 14.2261 15.15 14.0928 15H10.1178C9.88444 16.15 9.31361 17.1042 8.40527 17.8625C7.49694 18.6208 6.43444 19 5.21777 19ZM15.2178 19C14.2844 19 13.4386 18.7708 12.6803 18.3125C11.9219 17.8542 11.3261 17.25 10.8928 16.5H13.5678C13.8011 16.6667 14.0594 16.7917 14.3428 16.875C14.6261 16.9583 14.9178 17 15.2178 17C16.0511 17 16.7594 16.7083 17.3428 16.125C17.9261 15.5417 18.2178 14.8333 18.2178 14C18.2178 13.1667 17.9261 12.4583 17.3428 11.875C16.7594 11.2917 16.0511 11 15.2178 11C14.8844 11 14.5761 11.0458 14.2928 11.1375C14.0094 11.2292 13.7428 11.3667 13.4928 11.55L10.4428 6.475C10.0928 6.40833 9.80111 6.24167 9.56777 5.975C9.33444 5.70833 9.21777 5.38333 9.21777 5C9.21777 4.58333 9.36361 4.22917 9.65527 3.9375C9.94694 3.64583 10.3011 3.5 10.7178 3.5C11.1344 3.5 11.4886 3.64583 11.7803 3.9375C12.0719 4.22917 12.2178 4.58333 12.2178 5V5.2125C12.2178 5.27083 12.2011 5.34167 12.1678 5.425L14.3428 9.075C14.4761 9.04167 14.6178 9.02083 14.7678 9.0125C14.9178 9.00417 15.0678 9 15.2178 9C16.6011 9 17.7803 9.4875 18.7553 10.4625C19.7303 11.4375 20.2178 12.6167 20.2178 14C20.2178 15.3833 19.7303 16.5625 18.7553 17.5375C17.7803 18.5125 16.6011 19 15.2178 19ZM5.21777 15.5C4.80111 15.5 4.44694 15.3542 4.15527 15.0625C3.86361 14.7708 3.71777 14.4167 3.71777 14C3.71777 13.6333 3.83444 13.3167 4.06777 13.05C4.30111 12.7833 4.58444 12.6083 4.91777 12.525L7.26777 8.625C6.78444 8.175 6.40527 7.6375 6.13027 7.0125C5.85527 6.3875 5.71777 5.71667 5.71777 5C5.71777 3.61667 6.20527 2.4375 7.18027 1.4625C8.15527 0.4875 9.33444 0 10.7178 0C12.1011 0 13.2803 0.4875 14.2553 1.4625C15.2303 2.4375 15.7178 3.61667 15.7178 5H13.7178C13.7178 4.16667 13.4261 3.45833 12.8428 2.875C12.2594 2.29167 11.5511 2 10.7178 2C9.88444 2 9.17611 2.29167 8.59277 2.875C8.00944 3.45833 7.71777 4.16667 7.71777 5C7.71777 5.71667 7.93444 6.34583 8.36777 6.8875C8.80111 7.42917 9.35111 7.775 10.0178 7.925L6.64277 13.55C6.67611 13.6333 6.69694 13.7083 6.70527 13.775C6.71361 13.8417 6.71777 13.9167 6.71777 14C6.71777 14.4167 6.57194 14.7708 6.28027 15.0625C5.98861 15.3542 5.63444 15.5 5.21777 15.5Z" fill="#64748B"/>

											</svg>

											API Integration

										</button>

									</li>

									<li>

										<button class="navtablinks wpssle-nav-freevspro" onclick="wpssleNavTab(event, 'wpssle-nav-freevspro')">

											<svg width="21" height="21" viewBox="0 0 24 24" fill="none" class="w-5 h-5">

												<path d="M3 16C3 14.8954 3.89543 14 5 14H8C9.10457 14 10 14.8954 10 16V19C10 20.1046 9.10457 21 8 21H5C3.89543 21 3 20.1046 3 19V16Z" stroke="#1e293b" stroke-width="1.4"></path><path d="M14 16C14 14.8954 14.8954 14 16 14H19C20.1046 14 21 14.8954 21 16V19C21 20.1046 20.1046 21 19 21H16C14.8954 21 14 20.1046 14 19V16Z" stroke="#1e293b" stroke-width="1.4"></path><path d="M3 5C3 3.89543 3.89543 3 5 3H8C9.10457 3 10 3.89543 10 5V8C10 9.10457 9.10457 10 8 10H5C3.89543 10 3 9.10457 3 8V5Z" stroke="#1e293b" stroke-width="1.4"></path><path d="M14 5C14 3.89543 14.8954 3 16 3H19C20.1046 3 21 3.89543 21 5V8C21 9.10457 20.1046 10 19 10H16C14.8954 10 14 9.10457 14 8V5Z" stroke="#1e293b" stroke-width="1.4"></path>

											</svg>

											Free vs Pro

										</button>

									</li>

							</ul>

							</div>

						</div>

						<div class="wpssle-header-right">

							<ul class="wpssle-header-links">

								<li class="version"><span>V<?php echo esc_html( WPSSLE_VERSION ); ?></span></li>

								<li id="wpssleBtn">

									<a target="_blank" href="https://docs.wpsyncsheets.com/wpsse-setup-guide/">

										<svg width="16" height="20" viewBox="0 0 15 19" fill="none" xmlns="http://www.w3.org/2000/svg">

											<path d="M4.61806 7.6H5.54167C5.78662 7.6 6.02155 7.49991 6.19476 7.32175C6.36797 7.14359 6.46528 6.90196 6.46528 6.65C6.46528 6.39804 6.36797 6.15641 6.19476 5.97825C6.02155 5.80009 5.78662 5.7 5.54167 5.7H4.61806C4.3731 5.7 4.13817 5.80009 3.96496 5.97825C3.79175 6.15641 3.69444 6.39804 3.69444 6.65C3.69444 6.90196 3.79175 7.14359 3.96496 7.32175C4.13817 7.49991 4.3731 7.6 4.61806 7.6ZM4.61806 9.5C4.3731 9.5 4.13817 9.60009 3.96496 9.77825C3.79175 9.95641 3.69444 10.198 3.69444 10.45C3.69444 10.702 3.79175 10.9436 3.96496 11.1218C4.13817 11.2999 4.3731 11.4 4.61806 11.4H10.1597C10.4047 11.4 10.6396 11.2999 10.8128 11.1218C10.986 10.9436 11.0833 10.702 11.0833 10.45C11.0833 10.198 10.986 9.95641 10.8128 9.77825C10.6396 9.60009 10.4047 9.5 10.1597 9.5H4.61806ZM14.7778 6.593C14.7682 6.50573 14.7496 6.41975 14.7224 6.3365V6.251C14.678 6.15332 14.6187 6.06353 14.5469 5.985L9.00521 0.285C8.92886 0.211105 8.84156 0.150177 8.7466 0.1045C8.71903 0.100472 8.69104 0.100472 8.66347 0.1045C8.56965 0.0491542 8.46603 0.013627 8.35868 0H2.77083C2.03596 0 1.33119 0.300267 0.811558 0.834746C0.291926 1.36922 0 2.09413 0 2.85V16.15C0 16.9059 0.291926 17.6308 0.811558 18.1653C1.33119 18.6997 2.03596 19 2.77083 19H12.0069C12.7418 19 13.4466 18.6997 13.9662 18.1653C14.4859 17.6308 14.7778 16.9059 14.7778 16.15V6.65C14.7778 6.65 14.7778 6.65 14.7778 6.593ZM9.23611 3.2395L11.6283 5.7H10.1597C9.91477 5.7 9.67984 5.59991 9.50663 5.42175C9.33342 5.24359 9.23611 5.00196 9.23611 4.75V3.2395ZM12.9306 16.15C12.9306 16.402 12.8332 16.6436 12.66 16.8218C12.4868 16.9999 12.2519 17.1 12.0069 17.1H2.77083C2.52588 17.1 2.29095 16.9999 2.11774 16.8218C1.94453 16.6436 1.84722 16.402 1.84722 16.15V2.85C1.84722 2.59804 1.94453 2.35641 2.11774 2.17825C2.29095 2.00009 2.52588 1.9 2.77083 1.9H7.38889V4.75C7.38889 5.50587 7.68081 6.23078 8.20045 6.76525C8.72008 7.29973 9.42485 7.6 10.1597 7.6H12.9306V16.15ZM10.1597 13.3H4.61806C4.3731 13.3 4.13817 13.4001 3.96496 13.5782C3.79175 13.7564 3.69444 13.998 3.69444 14.25C3.69444 14.502 3.79175 14.7436 3.96496 14.9218C4.13817 15.0999 4.3731 15.2 4.61806 15.2H10.1597C10.4047 15.2 10.6396 15.0999 10.8128 14.9218C10.986 14.7436 11.0833 14.502 11.0833 14.25C11.0833 13.998 10.986 13.7564 10.8128 13.5782C10.6396 13.4001 10.4047 13.3 10.1597 13.3Z" fill="#464D58"/>

										</svg>

										<span class="tooltip-text">Documentation</span>

									</a>

								</li>

								<li>

									<a target="_blank" href="<?php echo esc_url( self::$submit_ticket ); ?>">

										<svg width="20" height="20" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">

											<path d="M13.2708 2.87C13.5005 3.00037 13.7724 3.03464 14.0273 2.96532C14.2821 2.896 14.4992 2.72873 14.6311 2.5C14.7403 2.30736 14.9104 2.15639 15.1146 2.07078C15.3188 1.98517 15.5457 1.96975 15.7597 2.02694C15.9736 2.08414 16.1626 2.21071 16.2968 2.38681C16.4311 2.56291 16.5031 2.77858 16.5015 3C16.5015 3.26522 16.3961 3.51957 16.2086 3.70711C16.021 3.89464 15.7666 4 15.5013 4C15.236 4 14.9816 4.10536 14.794 4.29289C14.6065 4.48043 14.5011 4.73478 14.5011 5C14.5011 5.26522 14.6065 5.51957 14.794 5.70711C14.9816 5.89464 15.236 6 15.5013 6C16.0279 5.99966 16.5452 5.86075 17.0011 5.59723C17.4571 5.33371 17.8356 4.95486 18.0987 4.49875C18.3618 4.04264 18.5002 3.52533 18.5 2.9988C18.4998 2.47227 18.361 1.95507 18.0975 1.49917C17.834 1.04326 17.4552 0.664718 16.9991 0.401563C16.5429 0.138409 16.0255 -8.42941e-05 15.4989 3.84915e-08C14.9723 8.43711e-05 14.4549 0.138743 13.9989 0.402044C13.5428 0.665344 13.1641 1.04401 12.9008 1.5C12.8346 1.61413 12.7917 1.74022 12.7745 1.871C12.7574 2.00178 12.7662 2.13466 12.8006 2.262C12.835 2.38934 12.8943 2.50862 12.975 2.61297C13.0557 2.71732 13.1562 2.80467 13.2708 2.87ZM17.5717 10C17.3092 9.96593 17.0438 10.0373 16.8338 10.1985C16.6239 10.3597 16.4864 10.5976 16.4515 10.86C16.2416 12.5552 15.419 14.1151 14.1386 15.246C12.8583 16.3769 11.2085 17.0007 9.50006 17H3.90889L4.55903 16.35C4.74532 16.1626 4.84988 15.9092 4.84988 15.645C4.84988 15.3808 4.74532 15.1274 4.55903 14.94C3.58378 13.9611 2.92007 12.7156 2.65153 11.3603C2.38298 10.005 2.52159 8.60052 3.04992 7.32383C3.57824 6.04714 4.47263 4.95532 5.62044 4.18589C6.76825 3.41646 8.11813 3.00384 9.50006 3C9.76533 3 10.0197 2.89464 10.2073 2.70711C10.3949 2.51957 10.5003 2.26522 10.5003 2C10.5003 1.73478 10.3949 1.48043 10.2073 1.29289C10.0197 1.10536 9.76533 0.999999 9.50006 0.999999C7.80894 1.00705 6.15401 1.49024 4.72486 2.39419C3.29571 3.29815 2.15016 4.58632 1.41944 6.11112C0.688721 7.63592 0.40239 9.33568 0.593251 11.0157C0.784113 12.6956 1.44445 14.2879 2.4986 15.61L0.788246 17.29C0.64946 17.4306 0.555444 17.6092 0.518062 17.8032C0.48068 17.9972 0.501607 18.1979 0.578203 18.38C0.653238 18.5626 0.780663 18.7189 0.944417 18.8293C1.10817 18.9396 1.30093 18.999 1.49839 19H9.50006C11.692 19.0003 13.8087 18.201 15.4531 16.7521C17.0975 15.3031 18.1567 13.3041 18.4319 11.13C18.4502 10.9993 18.4424 10.8662 18.409 10.7385C18.3756 10.6107 18.3172 10.4909 18.2373 10.3858C18.1573 10.2808 18.0573 10.1926 17.9431 10.1264C17.8289 10.0602 17.7026 10.0172 17.5717 10ZM15.8814 7.07C15.6993 6.98945 15.4973 6.96508 15.3013 7L15.1212 7.06L14.9412 7.15L14.7912 7.28C14.7012 7.37215 14.6299 7.48081 14.5811 7.6C14.522 7.72473 14.4945 7.86212 14.5011 8C14.4982 8.13337 14.522 8.26597 14.5711 8.39C14.6228 8.51002 14.6976 8.61873 14.7912 8.71C14.8846 8.80268 14.9955 8.87601 15.1173 8.92577C15.2392 8.97553 15.3697 9.00076 15.5013 9C15.7666 9 16.021 8.89464 16.2086 8.70711C16.3961 8.51957 16.5015 8.26522 16.5015 8C16.5049 7.86882 16.4775 7.73868 16.4215 7.62C16.314 7.37971 16.1217 7.18745 15.8814 7.08V7.07Z" fill="#464D58"/>

										</svg>

										<span class="tooltip-text">Support</span>

									</a>

								</li>

								<li>

									<a class="whatsNew-toggle" href="#">

										<svg width="17" height="18" viewBox="0 0 17 18" fill="none" xmlns="http://www.w3.org/2000/svg">

											<path d="M15.6111 1.5157e-07C15.4944 -6.7285e-05 15.3787 0.0231658 15.2709 0.0683712C15.163 0.113577 15.065 0.179867 14.9824 0.263454C14.8999 0.34704 14.8344 0.446282 14.7897 0.555505C14.7451 0.664729 14.7222 0.781791 14.7222 0.9V1.47305C13.9725 2.41418 13.025 3.17422 11.9487 3.69785C10.8723 4.22147 9.69415 4.4955 8.5 4.5H3.16667C2.45966 4.50078 1.78183 4.7855 1.2819 5.29167C0.781971 5.79785 0.500772 6.48415 0.5 7.2V9C0.500772 9.71584 0.781971 10.4021 1.2819 10.9083C1.78183 11.4145 2.45966 11.6992 3.16667 11.7H3.5967L1.46094 16.7458C1.4029 16.8826 1.37934 17.0319 1.39237 17.1803C1.40539 17.3286 1.45461 17.4714 1.53558 17.5957C1.61656 17.7201 1.72677 17.8221 1.85631 17.8927C1.98585 17.9632 2.13068 18.0001 2.27778 18H5.83333C6.00734 18.0001 6.17753 17.9484 6.32276 17.8514C6.46799 17.7543 6.58185 17.6162 6.65018 17.4542L9.07129 11.7342C10.1652 11.8155 11.231 12.123 12.203 12.6377C13.1749 13.1524 14.0323 13.8634 14.7222 14.7268V15.3C14.7222 15.5387 14.8159 15.7676 14.9826 15.9364C15.1493 16.1052 15.3754 16.2 15.6111 16.2C15.8469 16.2 16.073 16.1052 16.2396 15.9364C16.4063 15.7676 16.5 15.5387 16.5 15.3V0.9C16.5001 0.781791 16.4771 0.664728 16.4325 0.555504C16.3878 0.44628 16.3224 0.347037 16.2398 0.263451C16.1572 0.179865 16.0592 0.113574 15.9514 0.0683688C15.8435 0.0231639 15.7279 -6.84694e-05 15.6111 1.5157e-07ZM3.16667 9.9C2.93097 9.89984 2.70497 9.80497 2.5383 9.63622C2.37164 9.46747 2.27794 9.23864 2.27778 9V7.2C2.27794 6.96135 2.37164 6.73253 2.5383 6.56378C2.70497 6.39504 2.93097 6.30016 3.16667 6.3H4.05556V9.9H3.16667ZM5.2474 16.2H3.62587L5.53038 11.7H7.15191L5.2474 16.2ZM14.7222 12.1696C12.9696 10.7077 10.7708 9.90565 8.5 9.89995H5.83333V6.29995H8.5C10.7709 6.29408 12.9696 5.4919 14.7222 4.02985V12.1696Z" fill="#464D58"/>

										</svg>

										<span class="tooltip-text">Change Log</span>

									</a>

								</li>

							</ul>

							<div class="whatsNew-block">

								<div class="block-header">

									<h3>Change Log</h3>

									<button type="button" class="close-block">

										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>

									</button>

								</div>	

								<div class="wpssle-block-content changeLog-conntent">

									<?php

									$changelog = self::wpssle_get_plugin_changelog( WP_PLUGIN_DIR . '/wpsyncsheets-elementor' );

									// phpcs:ignore

									echo nl2br( $changelog );

									?>

								</div>

							</div>

						</div>

					</div>

				</div>

			</div>

			<!-- messages html -->

			<div class="alert-messages">

				<div class="container">

				</div>

			</div>

			<!-- messages html end-->		

			<div class="wpssle-tabs-main">

				<!-- DashBoard html start   -->

				<div id="wpssle-nav-dashboard" class="navtabcontent vertical-tabs">

					<div class="wpss-dashboard-container">

						<!-- Welcome html start -->

						<div class="wpss-dashboard-left-content">

							<div class="wpss-dashboard-navtabcontent">

								<!-- Header Section -->

								<div class="welcome-wpsyncsheets-section">

									<div class="welcome-data">

										<div class="welcome-title">

											<span>Welcome To WPSyncSheets</span>

										</div>

										<p>

											WPSyncSheets For Elementor is designed to simplify your workflow by syncing your website forms data directly with Google Sheets. To explore all its features, check out the official documentation and video tutorials.

										</p>

									</div>

									<div class="unlock-pro-button-section">

										<a class="underline" href="javascript:void(0)" onclick="wpssleNavTabLetsBegin(event, 'wpssle-nav-googleapi')">

											Let's Connect

										</a>

									</div>

								</div>

							</div>

							<div class="wpss-setup-guide-navtabcontent">

								<!-- Header Section -->

								<div class="setup-guide-section">

									<div class="setup-guide-data">

										<div class="setup-guide-title">

											<span>Setup Guide & Troubleshooting</span>

										</div>

										<p>

											Sync Elementor data with Google Sheets in real-time — effortlessly and accurately.

										</p>

									</div>

									<div class="setup-content-data">

										<div class="setup-row">

											<div class="google-api-setting-guide">

												<h1>Connecting Google Account</h1>

												<ul>

													<li><a href="https://docs.wpsyncsheets.com/wpsse-google-sheets-api-settings/" target="_blank">Google API Settings Guide</a></li>

													<li><a href="https://www.wpsyncsheets.com/how-to-resolve-google-oauth-2-authorization-error-redirect_uri_mismatch/" target="_blank">How to resolve 400 redirect_uri_mismatch?</a></li>

													<li><a href="https://docs.wpsyncsheets.com/wpsse-faq/#appverified" target="_blank">How to resolve "This app isn’t verified"?</a></li>

													<li><a href="https://docs.wpsyncsheets.com/wpsse-faq/#rangeexceed" target="_blank">How to resolve range exceeds grid limits?</a></li>

												</ul>

											</div>

											<div class="google-api-setting-guide">

												<h1>Export</h1>

												<ul>

													<li><a href="https://docs.wpsyncsheets.com/how-to-el-export-entries/" target="_blank">How to Export Entries?</a></li>

												</ul>

											</div>

										</div>

									</div>

								</div>

							</div>

						</div>

						<!-- Import Export plugin -->

						<div class="wpss-dashboard-right-content">

							<div class="wpss-dashboard-related-plugins">

								<div class="wpss-import-export-plugins">

									<h1 class="import-export-title">Automated Sync for WordPress Data with Google Sheets</h1>

									<!-- WooCommerce -->	

									<div class="import-export-plugin-section">

										<div class="free-right-title">

											<span>Free</span>

										</div>

										<div class="import-export-plugin-info">

											<div class="import-export-feature-img">

												<img src="<?php echo esc_url( WPSSLE_URL . 'assets/images/woocommerce-icon.svg' ); ?>">

											</div>

											<div class="import-export-box-border">

												<div class="import-export-pro-content">

													<h1>WPSyncSheets For WooCommerce</h1>

												</div>

													<p class="text">Sync WooCommerce submissions with Google Sheets. Automate data management through real-time imports and exports.</p>

												<div class="import-export-button-section">

													<?php

													$wpssw_plugin_slug = 'wpsyncsheets-woocommerce';

													$wpssw_plugin_file = 'wpsyncsheets-woocommerce/wpsyncsheets-lite-woocommerce.php';

													$wpssw_status = WPSSLE_Dependencies::check_plugin_install_and_activation_status( $wpssw_plugin_slug, $wpssw_plugin_file );

													?>

													<?php wp_nonce_field( 'install_active', 'wpssw_api_dashboard' ); ?>

													<?php

													if ( $wpssw_status['active'] || $wpssw_status['network_active'] ) {

														?>

													<a class="activated" href="javascript:void(0)">

														Activated

													</a>

													<?php } elseif ( ! $wpssw_status['installed'] ) { ?>

													<a class="underline" href="javascript:void(0)" id="wpsswinstallactivebtn">

														Install

													</a>

													<?php } else { ?>

													<a class="underline" href="javascript:void(0)" id="wpsswactivebtn">

														Activate

													</a>

													<?php } ?>

												</div>

											</div>

										</div>

									</div>

									<!-- Gravity Forms -->

									<div class="import-export-plugin-section">

										<div class="free-left-title">

											<span>Free</span>

										</div>

										<div class="import-export-plugin-info">

											<div class="import-export-box-border">

												<div class="import-export-pro-content">

													<h1>WPSyncSheets For Gravity Forms</h1>

												</div>

													<p class="text">Sync Gravity Forms submissions with Google Sheets. Automate data management through real-time exports.</p>

												<div class="import-export-button-section">

													<?php

													$wpssg_plugin_slug = 'wpsyncsheets-gravity-forms';

													$wpssg_plugin_file = 'wpsyncsheets-gravity-forms/wpsyncsheets-lite-gravity-forms.php';

													$wpssg_status = WPSSLE_Dependencies::check_plugin_install_and_activation_status( $wpssg_plugin_slug, $wpssg_plugin_file );

													?>

													<?php wp_nonce_field( 'install_active', 'wpssg_api_dashboard' ); ?>

													<?php

													if ( $wpssg_status['active'] || $wpssg_status['network_active'] ) {

														?>

													<a class="activated" href="javascript:void(0)">

														Activated

													</a>

													<?php } elseif ( ! $wpssg_status['installed'] ) { ?>

													<a class="underline" href="javascript:void(0)" id="wpssginstallactivebtn">

														Install

													</a>

													<?php } else { ?>

													<a class="underline" href="javascript:void(0)" id="wpssgactivebtn">

														Activate

													</a>

													<?php } ?>

												</div>

											</div>

											<div class="import-export-feature-img">

												<img src="<?php echo esc_url( WPSSLE_URL . 'assets/images/GravityForms-icon.svg' ); ?>">

											</div>

										</div>

									</div>

									<!-- Contact Form -->

									<div class="import-export-plugin-section">

										<div class="free-right-title">

											<span>Free</span>

										</div>

										<div class="import-export-plugin-info">

											<div class="import-export-feature-img">

												<img src="<?php echo esc_url( WPSSLE_URL . 'assets/images/ContactForm-icon.svg' ); ?>">

											</div>

											<div class="import-export-box-border">

												<div class="import-export-pro-content">

													<h1>WPSyncSheets For Contact Form 7</h1>

												</div>

													<p class="text">Sync Contact Form 7 submissions with Google Sheets. Automate data management through real-time exports.</p>

												<div class="import-export-button-section">

													<?php

													$wpssc_plugin_slug = 'contactsheets-lite';

													$wpssc_plugin_file = 'contactsheets-lite/wpsyncsheets-lite-contact-form-7.php';

													$wpssc_status = WPSSLE_Dependencies::check_plugin_install_and_activation_status( $wpssc_plugin_slug, $wpssc_plugin_file );

													?>

													<?php wp_nonce_field( 'install_active', 'wpssc_api_dashboard' ); ?>

													<?php

													if ( $wpssc_status['active'] || $wpssc_status['network_active'] ) {

														?>

													<a class="activated" href="javascript:void(0)">

														Activated

													</a>

													<?php } elseif ( ! $wpssc_status['installed'] ) { ?>

													<a class="underline" href="javascript:void(0)" id="wpsscinstallactivebtn">

														Install

													</a>

													<?php } else { ?>

													<a class="underline" href="javascript:void(0)" id="wpsscactivebtn">

														Activate

													</a>

													<?php } ?>

												</div>

											</div>

										</div>

									</div>

									<!-- WPForms -->

									<div class="import-export-plugin-section">

										<div class="free-left-title">

											<span>Free</span>

										</div>

										<div class="import-export-plugin-info">

											<div class="import-export-box-border">

												<div class="import-export-pro-content">

													<h1>WPSyncSheets For WPForms</h1>

												</div>

													<p class="text">Sync WPForms submissions with Google Sheets. Automate data management through real-time exports.</p>

												<div class="import-export-button-section">

													<?php

													$wpsswp_plugin_slug = 'wpsyncsheets-wpforms';

													$wpsswp_plugin_file = 'wpsyncsheets-wpforms/wpsyncsheets-lite-wpforms.php';

													$wpsswp_status = WPSSLE_Dependencies::check_plugin_install_and_activation_status( $wpsswp_plugin_slug, $wpsswp_plugin_file );

													?>

													<?php wp_nonce_field( 'install_active', 'wpsswp_api_dashboard' ); ?>

													<?php

													if ( $wpsswp_status['active'] || $wpsswp_status['network_active'] ) {

														?>

													<a class="activated" href="javascript:void(0)">

														Activated

													</a>

													<?php } elseif ( ! $wpsswp_status['installed'] ) { ?>

													<a class="underline" href="javascript:void(0)" id="wpsswpinstallactivebtn">

														Install

													</a>

													<?php } else { ?>

													<a class="underline" href="javascript:void(0)" id="wpsswpactivebtn">

														Activate

													</a>

													<?php } ?>

												</div>

											</div>

											<div class="import-export-feature-img">

												<img src="<?php echo esc_url( WPSSLE_URL . 'assets/images/WPForms-icon.svg' ); ?>">

											</div>

										</div>

									</div>

								</div>

							</div>

						</div>

					</div>

						<!-- Video Tutorial html start -->

					<div class="wpss-dashboard-bottom-content">

						<div class="wpss-dashboard-related-video">

							<!-- Header Section -->

							<div class="video-tutorial-section">

								<div class="video-title">

									<span>Video Tutorials</span>

								</div>

								<div class="video-grid">

									<div class="video-item">

										<a href="#" target="_blank" class="play-icon" data-video-id="Z6-wzgpRlLE">

											<img src="<?php echo esc_url( WPSSLE_URL . 'assets/images/WPSyncSheets-Intro.jpg' ); ?>" alt="">

										</a>

									</div>

									<div class="video-item">

										<a href="#" target="_blank" class="play-icon" data-video-id="EFLcBu-Jgp8">

											<img src="<?php echo esc_url( WPSSLE_URL . 'assets/images/client-id-client-secret.jpg' ); ?>" alt="">

										</a>

									</div>

								</div>

								<div id="video-popup">

									<div class="popup-inner">

										<span class="close">&times;</span>

										<div class="video-wrapper">

										<iframe id="youtube-video" src="" frameborder="0" allowfullscreen allow="autoplay"></iframe>

										</div>

									</div>

								</div>

							</div>

						</div>

					</div>

				</div>



				<div id="wpssle-nav-googleapi" class="navtabcontent vertical-tabs">

					<div id="googleapi-settings" class="wpssle-navtabcontent">

						<?php

						if ( ! current_user_can( 'edit_wpsyncsheets_elementor_lite_main_settings' ) ) {

							?>

						<div class="wpssle-top-subtext generalSetting-section">

							<div class="generalSetting-left">

								<h4><?php echo esc_html__( 'You do not have permission to access this page.', 'wpsse' ); ?></h4>

							</div>

						</div>

							<?php

						} else {

							?>

						<div class="wpssle-top-subtext generalSetting-section">

							<div class="generalSetting-left">

								<h4><?php echo esc_html__( 'Google API Settings', 'wpsse' ); ?></h4>

								<p><?php echo esc_html__( 'Google APIs allow you to embed Google Services in your online site. To connect Google Drive and Google Sheets with your WordPress website, you need to generate dedicated API keys. To begin the process, kindly log in to your Gmail Account and follow the link to initiate the setup ', 'wpsse' ); ?><a href="<?php echo esc_url( self::$doc_sheet_setting ); ?>" target="_blank"><?php echo esc_html__( 'click here', 'wpsse' ); ?>.</a></p>

							</div>

						</div>

						<form method="post" action="<?php echo esc_html( admin_url( 'admin.php?page=wpsyncsheets-elementor' ) ); ?>">

							<?php wp_nonce_field( 'save_api_settings', 'wpssle_api_settings' ); ?>

							<div id="universal-message-container">

								<div class="generalSetting-section">

									<div class="integrationtabform">

										<div class="options">

											<ul class="form-table">

												<li>

													<label for="client_id"> <?php echo esc_html__( 'Client ID', 'wpsse' ); ?> </label>

													<div class="forminp forminp-text">

														<input type="text" id="client_id" required name="client_id" value="<?php echo isset( $wpssle_google_settings_value[0] ) ? esc_attr( $wpssle_google_settings_value[0] ) : ''; ?>" size="80" class = "googlesettinginput" placeholder="Enter Client Id" 

															<?php

															if ( ! empty( $wpssle_google_settings_value[0] ) ) {

																echo 'readonly';

															}

															?>

														/>

													</div>

												</li>

												<li>

													<label for="client_secret"> <?php echo esc_html__( 'Client Secret Key', 'wpsse' ); ?> </label>

													<div class="forminp forminp-text">

														<input type="text" id="client_secret" required name="client_secret" value="<?php echo isset( $wpssle_google_settings_value[1] ) ? esc_attr( $wpssle_google_settings_value[1] ) : ''; ?>" size="80" class = "googlesettinginput" placeholder="Enter Client Secret" 

															<?php

															if ( ! empty( $wpssle_google_settings_value[1] ) ) {

																echo 'readonly';

															}

															?>

														/>

													</div>

												</li>

												<?php

												if ( ! empty( $wpssle_google_settings_value[0] ) && ! empty( $wpssle_google_settings_value[1] ) ) {

														$wpssle_token_value = $wpssle_google_settings_value[2];

													?>

												<li>

													<label><?php echo esc_html__( 'Client Token', 'wpsse' ); ?></label>

													<?php

													if ( empty( $wpssle_token_value ) && ! isset( $_GET['code'] ) ) {

														$wpssle_auth_url = self::$instance_api->getClient();

														self::$instance_api->wpssle_update_option( 'wpsse_share_enable', true );

														?>

														<div id="authbtn">

															<a href="<?php echo esc_url( $wpssle_auth_url ); ?>" id="authlink" target="_blank" ><div class="wpssle-button wpssle-button-secondary"><?php echo esc_html__( 'Click here to generate an Authentication Token', 'wpsse' ); ?></div></a>

														</div>

														<?php

													}

													$wpssle_code = '';

													$wpssle_google_settings_value = self::$instance_api->wpssle_option( 'wpsse_google_settings' );

													?>

													<div  id="authtext" 

													<?php

													if ( ! empty( $wpssle_token_value ) || $wpssle_code ) {

														echo 'class = "forminp forminp-text wpssle-authtext" ';

													} else {

														echo 'class="forminp forminp-text"'; }

													?>

														><input type="text" name="client_token" value="<?php echo $wpssle_token_value ? esc_attr( $wpssle_token_value ) : esc_attr( $wpssle_code ); ?>" size="80" placeholder="Please enter authentication code" id="client_token" class="googlesettinginput" 

															<?php

															if ( ! empty( $wpssle_google_settings_value[2] ) ) {

																echo 'readonly';

															}

															?>

														/>

													</div>

												</li>

												<?php } if ( ! empty( $wpssle_token_value ) ) { ?>

												<li>

													<label></label>

													<div><input type="submit" name="revoke" id="revoke" value = "Revoke Token" class="wpssle-button wpssle-button-secondary"/></div>

												</li>

												<?php } ?>

											</ul>

										</div>

										<?php

										$site_url = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';

										$site_url = str_replace( 'www.', '', $site_url );

										?>

										<div class="submit-section">

											<p class="submit">

												<input type="submit" name="submit" id="submit" class="wpssle-button wpssle-button-primary" value="Save">

												<?php

												if ( ! empty( $wpssle_token_value ) || ! empty( $wpssle_google_settings_value[0] ) || ! empty( $wpssle_google_settings_value[1] ) ) {

													?>

														<input type="submit" name="reset_settings" id="reset_settings" value = "Reset" class="wpssle-button wpssle-button-primary reset_settings"/>

													<?php } ?>

											</p>

										</div>

									</div>

								</div>

								<div class="generalSetting-section copy-url-table">

									<ul>

										<li>

											<label><?php echo esc_html__( 'Authorized Domain : ', 'wpsse' ); ?></label>

											<div class="copy-url-text"><span id="authorized_domain"><?php echo esc_html( $site_url ); ?></span>

												<span class="copy-icon wpssle-button" id="a_domain" onclick="wpssleCopy('authorized_domain','a_domain');">

													<svg width="15" height="18" viewBox="0 0 15 18" fill="none" xmlns="http://www.w3.org/2000/svg">

														<path d="M2.16667 17.3334C1.70833 17.3334 1.31597 17.1702 0.989583 16.8438C0.663194 16.5174 0.5 16.1251 0.5 15.6667V4.00008H2.16667V15.6667H11.3333V17.3334H2.16667ZM5.5 14.0001C5.04167 14.0001 4.64931 13.8369 4.32292 13.5105C3.99653 13.1841 3.83333 12.7917 3.83333 12.3334V2.33341C3.83333 1.87508 3.99653 1.48272 4.32292 1.15633C4.64931 0.829942 5.04167 0.666748 5.5 0.666748H13C13.4583 0.666748 13.8507 0.829942 14.1771 1.15633C14.5035 1.48272 14.6667 1.87508 14.6667 2.33341V12.3334C14.6667 12.7917 14.5035 13.1841 14.1771 13.5105C13.8507 13.8369 13.4583 14.0001 13 14.0001H5.5ZM5.5 12.3334H13V2.33341H5.5V12.3334Z" fill="#383E46"/>

													</svg>

													<span class="tooltip-text">Copied</span>

												</span>

											</div>

										</li>

										<li>

											<label><?php echo esc_html__( 'Authorised redirect URIs : ', 'wpsse' ); ?></label>

											<div class="copy-url-text">

												<span id="authorized_uri"><?php echo esc_html( admin_url( 'admin.php?page=wpsyncsheets-elementor' ) ); ?></span>

												<span class="copy-icon wpssle-button tooltip-click1" onclick="wpssleCopy('authorized_uri','a_uri');" id="a_uri">

													<svg width="15" height="18" viewBox="0 0 15 18" fill="none" xmlns="http://www.w3.org/2000/svg">

													<path d="M2.16667 17.3334C1.70833 17.3334 1.31597 17.1702 0.989583 16.8438C0.663194 16.5174 0.5 16.1251 0.5 15.6667V4.00008H2.16667V15.6667H11.3333V17.3334H2.16667ZM5.5 14.0001C5.04167 14.0001 4.64931 13.8369 4.32292 13.5105C3.99653 13.1841 3.83333 12.7917 3.83333 12.3334V2.33341C3.83333 1.87508 3.99653 1.48272 4.32292 1.15633C4.64931 0.829942 5.04167 0.666748 5.5 0.666748H13C13.4583 0.666748 13.8507 0.829942 14.1771 1.15633C14.5035 1.48272 14.6667 1.87508 14.6667 2.33341V12.3334C14.6667 12.7917 14.5035 13.1841 14.1771 13.5105C13.8507 13.8369 13.4583 14.0001 13 14.0001H5.5ZM5.5 12.3334H13V2.33341H5.5V12.3334Z" fill="#383E46"/>

													</svg>

													<span class="tooltip-text">Copied</span>

												</span>

											</div>

										</li>

									</ul>

								</div>

							</div>

						</form>

						<?php } ?>

					</div>

				</div>



				<!-- Free Vs Pro html start   -->

				<div id="wpssle-nav-freevspro" class="navtabcontent vertical-tabs">

					<div class="wpssle-navtabcontent">

							<!-- Header Section -->

							<div class="free-pro-header">

								<div class="free-pro-data">

									<div class="free-pro-title">

										<span>Free vs Pro</span>

									</div>

									<p>

										Compare the features to find the best option for your website.

									</p>

								</div>

								<div class="unlock-pro-button-section">

									<a class="underline" href="<?php echo WPSSLE_BUY_PRO_VERSION_URL; ?>" target="_blank" rel="noreferrer">

										Upgrade Now

									</a>

								</div>

							</div>

							<!-- Comparison Table -->

							<div class="pro-free-features">

									<!-- Table Header Row -->

									<div class="dynamic-content">

										<p>General  Features</p>

										<div class="free-title">

											<p>Free</p>

											<p>Pro</p>

										</div>

									</div>

									<!-- Feature: Automatically Create New Google Spreadsheet -->

									<div class="feature-data">

										<p>Automatically Create New Google Spreadsheet</p>

											<div class="feature-icon">

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check">

												<path d="M20 6 9 17l-5-5"></path>

												</svg>

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check">

												<path d="M20 6 9 17l-5-5"></path>

												</svg>

											</div>

									</div>

									<!-- Feature: Automatcailly Create New Google Sheets -->

									<div class="feature-data">

										<p>Automatcailly Create New Google Sheets</p>

											<div class="feature-icon">

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check">

												<path d="M20 6 9 17l-5-5"></path>

												</svg>

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check">

												<path d="M20 6 9 17l-5-5"></path>

												</svg>

											</div>

									</div>

									<!-- Feature: Freeze Headers (First Row of the Google Spreadsheet) -->

									<div class="feature-data">

										<p>Freeze Headers (First Row of the Google Spreadsheet)</p>

											<div class="feature-icon">

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check">

												<path d="M20 6 9 17l-5-5"></path>

												</svg>

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check">

												<path d="M20 6 9 17l-5-5"></path>

												</svg>

											</div>

									</div>

									<!-- Feature: Download Excel & CSV -->

									<div class="feature-data">

										<p>Download Excel & CSV</p>

											<div class="feature-icon">

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x">

												<path d="M18 6 6 18"></path>

												<path d="m6 6 12 12"></path>

												</svg>

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check">

												<path d="M20 6 9 17l-5-5"></path>

												</svg>

											</div>

									</div>

									<!-- Feature: Automatically Clear Google Spreadsheet -->

									<div class="feature-data">

										<p>Automatically Clear Google Spreadsheet</p>

											<div class="feature-icon">

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x">

												<path d="M18 6 6 18"></path>

												<path d="m6 6 12 12"></path>

												</svg>

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check">

												<path d="M20 6 9 17l-5-5"></path>

												</svg>

											</div>

									</div>

									<!-- Feature: Automatic Field Mapping -->

									<div class="feature-data">

										<p>Automatic Field Mapping</p>

											<div class="feature-icon">

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x">

												<path d="M18 6 6 18"></path>

												<path d="m6 6 12 12"></path>

												</svg>

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check">

												<path d="M20 6 9 17l-5-5"></path>

												</svg>

											</div>

									</div>

									<!-- Feature: Enable/Disable Sheet Headers -->

									<div class="feature-data">

										<p>Enable/Disable Sheet Headers</p>

											<div class="feature-icon">

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x">

												<path d="M18 6 6 18"></path>

												<path d="m6 6 12 12"></path>

												</svg>

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check">

												<path d="M20 6 9 17l-5-5"></path>

												</svg>

											</div>

									</div>

									<!-- Feature: Submission Date & Time -->

									<div class="feature-data">

										<p>Submission Date & Time</p>

											<div class="feature-icon">

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x">

												<path d="M18 6 6 18"></path>

												<path d="m6 6 12 12"></path>

												</svg>

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check">

												<path d="M20 6 9 17l-5-5"></path>

												</svg>

											</div>

									</div>

									<!-- Feature: Update Sheet Headers Name (Spreadsheet Columns) -->

									<div class="feature-data">

										<p>Update Sheet Headers Name (Spreadsheet Columns)</p>

											<div class="feature-icon">

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x">

												<path d="M18 6 6 18"></path>

												<path d="m6 6 12 12"></path>

												</svg>

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check">

												<path d="M20 6 9 17l-5-5"></path>

												</svg>

											</div>

									</div>

									<!-- Feature: Sorting Sheet Headers (Spreadsheet Columns) -->

									<div class="feature-data">

										<p>Sorting Sheet Headers (Spreadsheet Columns)</p>

											<div class="feature-icon">

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x">

												<path d="M18 6 6 18"></path>

												<path d="m6 6 12 12"></path>

												</svg>

												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check">

												<path d="M20 6 9 17l-5-5"></path>

												</svg>

											</div>

									</div>

							</div>

					</div>

				</div>

				<?php

				if ( ! empty( $wpssle_error ) || ! empty( $wpssle_error_general ) || ! \WPSSLE_Dependencies::wpssle_is_elementor_plugin_active() ) {

					$error_msg = '';

					if ( ! \WPSSLE_Dependencies::wpssle_is_elementor_plugin_active() ) {

						$error_msg = 'WPSyncSheets Lite For Elementor plugin requires <a href="' . esc_url( 'https://wordpress.org/plugins/wpsyncsheets-elementor/' ) . '" target= "_blank">Elementor</a> plugin to be active!';

						?>

					<input type="hidden" id="error-message" value="<?php echo esc_attr( $error_msg ); ?>">

						<?php

					} else {

						if ( ! empty( $wpssle_error_general ) ) {

							$error_msg = wp_strip_all_tags( $wpssle_error_general );

						} else {

							$error_msg = wp_strip_all_tags( $wpssle_error );

						}

						if ( $error_msg ) {

							?>

						<input type="hidden" id="error-message" value="<?php echo esc_attr( $error_msg ); ?>">

							<?php

						}
					}

					?>

				<?php } elseif ( isset( $_POST['submit'] ) ) { ?>

				<input type="hidden" id="success-message" value="Settings are saved successfully.">

					<?php

				}

				$token_error_val = 0;

				if ( $wpssle_token_error ) {

					$token_error_val = 1;

				}

				?>

				<input type="hidden" id="token-error" value="<?php echo esc_attr( $token_error_val ); ?>">

				<script type="text/javascript">

					jQuery.noConflict();

					jQuery(".wpssle-header-section .whatsNew-toggle, .wpssle-header-section .close-block").click(function(){

						jQuery(".wpssle-header-section .wpssle-header-right").toggleClass('active');

						jQuery(".wpssle-header-section .whatsNew-toggle").toggleClass('active');

					});

				</script>

				<div class="clear"></div>

			</div>

			<div class="clear"></div>



			<!-- Unlock Pro Features -->

			<div class="wpssle-tabs-main wpssle-pro-main">

				<div class="upgrade-pro-navtabcontent">

					<div class="wpssle-navtabcontent">

						<div class="generalSetting-section">

							<div class="box-border">

								<div class="unlock-pro-content">

									<div class="unlock-pro-title">

										<svg width="64" height="56" viewBox="0 0 64 56" fill="none" xmlns="http://www.w3.org/2000/svg">

											<path d="M20.9521 4.49219C26.2038 1.9958 32.146 1.35206 37.8105 2.66504C43.4752 3.97804 48.5287 7.17034 52.1465 11.7227C55.3478 15.7509 57.2568 20.6337 57.6543 25.7334H54.2324C53.8432 21.4057 52.198 17.2692 49.4766 13.8447C46.3383 9.89585 41.9548 7.12637 37.041 5.9873C32.1271 4.84831 26.9717 5.40664 22.416 7.57227C20.0832 8.68124 17.6708 10.5132 15.5957 12.5684C14.1879 13.9626 12.8819 15.5127 11.833 17.0889L9.59082 15.082C12.1905 10.477 16.1542 6.77301 20.9521 4.49219Z" fill="#4169E1" stroke="#4169E1" stroke-width="4"/>

											<path d="M9.48975 29.7333C9.8653 33.9608 11.4252 38.012 14.0103 41.412C17.0007 45.3451 21.1975 48.1897 25.9585 49.5106C30.7195 50.8315 35.7822 50.5565 40.3716 48.7264C42.7389 47.7824 45.2135 46.1131 47.3716 44.2225C48.8135 42.9593 50.1674 41.5489 51.2935 40.1122L53.2524 42.2411C50.3731 46.4591 46.2973 49.7365 41.5327 51.6366C36.307 53.7205 30.5417 54.0342 25.1206 52.5302C19.6996 51.0261 14.9211 47.7867 11.5161 43.3085C8.51618 39.3629 6.72919 34.6468 6.34619 29.7333H9.48975Z" fill="#4169E1" stroke="#4169E1" stroke-width="4"/>

											<path d="M47.0158 27.1168L55.4635 36.9778L64.0003 27.0222H58.6144H52.6613H46.9336L47.0158 27.1168Z" fill="#4169E1"/>

											<path d="M16.9846 31.1943L8.53702 21.3333L0.000234604 31.2888L5.38608 31.2888L11.3392 31.2888L17.0669 31.2888L16.9846 31.1943Z" fill="#4169E1"/>

										</svg>

										<span>Unlock Pro Features</span>

									</div>

									<h1>Supercharge Your Data Sync With WPSyncSheets Pro!</h1>

								</div>

									<p class="text">Sync Elementor form data with Google Sheets in real-time — effortlessly and accurately.</p>

								<ul class="pro-features-content">

									<li>

										<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-brand-primary-600">

										<path d="M20 6 9 17l-5-5"></path></svg><span>Automatically Clear Google Spreadsheet</span>

									</li>

									<li>

										<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-brand-primary-600">

										<path d="M20 6 9 17l-5-5"></path></svg><span>Submission Date & Time</span>

									</li>

									<li>

										<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-brand-primary-600">

										<path d="M20 6 9 17l-5-5"></path></svg><span>Automatic Field Mapping</span>

									</li>

									<li>

										<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-brand-primary-600">

										<path d="M20 6 9 17l-5-5"></path></svg><span>Sorting Sheet Headers (Spreadsheet Columns)</span>

									</li>

									<li>

										<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-brand-primary-600">

										<path d="M20 6 9 17l-5-5"></path></svg><span>And More…</span>

									</li>

								</ul>

								<div class="unlock-pro-button-section">

									<a class="underline" href="<?php echo WPSSLE_BUY_PRO_VERSION_URL; ?>" target="_blank" rel="noreferrer">

										Upgrade Now

									</a>

									<a class="no-underline" href="javascript:void(0)" onclick="wpssleNavTabfreevspro(event, 'wpssle-nav-freevspro')">

										Free vs Pro

									</a>

								</div>

							</div>

							<div class="pro-feature-img">

								<img src="<?php echo esc_url( WPSSLE_URL . 'assets/images/elementor-pro.png' ); ?>" />

							</div>

						</div>	

					</div>

				</div>

			</div>

		</div>

		<?php
	}

	/**

	 * Get changelog.
	 *
	 * @param string $plugin_path plugin path.
	 */
	public static function wpssle_get_plugin_changelog( $plugin_path ) {

		// Path to the readme.txt file.

		$readme_file = $plugin_path . '/readme.txt';

		// Check if the file exists.

		if ( ! file_exists( $readme_file ) ) {

			return '';

		}

		// phpcs:ignore

		$content = file_get_contents( $readme_file ); // Read the file content.

		if ( ! $content || is_wp_error( $content ) ) {

			return '';

		}

		// Regular expression to extract the Changelog section.

		$pattern = '/==\s*Changelog\s*==\s*(.*?)(==|$)/is';

		// Match the pattern.

		if ( preg_match( $pattern, $content, $matches ) ) {

			// Extracted Changelog section.

			$changelog_content = trim( $matches[1] );

			// Regular expression to parse individual versions.

			$version_pattern = '/=+\s*(\d+\.\d+(\.\d+)?\s*(\(.+?\))?)\s*=+\s*(.*?)(?==+\s*\d+\.\d+(\.\d+)?\s*(\(.+?\))?\s*=+|$)/is';

			preg_match_all( $version_pattern, $changelog_content, $version_matches, PREG_SET_ORDER );

			$html_output = '';

			foreach ( $version_matches as $version_match ) {

				$version = $version_match[1];

				$changes = trim( $version_match[4] );

				// Convert changes to list items.

				$change_items = preg_split( '/\r\n|\r|\n/', $changes );

				$change_items = array_filter( array_map( 'trim', $change_items ) );

				$html_output .= "<h5><strong>Version $version</strong></h5>\n<ol>\n";

				foreach ( $change_items as $item ) {

					// Remove leading '*' and trim the item.

					$item = ltrim( $item, '* ' );

					if ( ! empty( $item ) ) {

						$html_output .= '<li>' . htmlspecialchars( $item ) . "</li>\n";

					}
				}

				$html_output .= "</ol>\n<hr class=\"wp-block-separator has-css-opacity\">\n";

			}

			// Remove any unintended <br> tags.

			return str_replace( array( '<br>', '<br/>', '<br />' ), '', $html_output );

		} else {

			return '';

		}
	}

	/**

	 * Prepare Google Spreadsheet list.
	 *
	 * @return array $sheetarray Spreadsheet List.
	 */
	public static function wpssle_list_googlespreedsheet() {

		/* Build choices array. */

		$sheetarray = array(

			'' => esc_html__( 'Select Google Spreeadsheet', 'wpsse' ),

		);

		$sheetarray = self::$instance_api->get_spreadsheet_listing( $sheetarray );

		return $sheetarray;
	}

	/**

	 * Check posted data before processing.
	 *
	 * @param array $wpssle_data Posted Elementor Data.
	 */
	public static function wpssle_ajax_register_action( $wpssle_data ) {

		//phpcs:ignore

		if ( ! isset( $_REQUEST['actions'] ) ) {

			return;

		}

		//phpcs:ignore

		$wpssle_data = json_decode( sanitize_text_field( wp_unslash( $_REQUEST['actions'] ) ), true );

		if ( isset( $wpssle_data['save_builder'] ) ) {

			$wpssle_google_settings = self::$instance_api->wpssle_option( 'wpsse_google_settings' );

			if ( ! empty( $wpssle_google_settings ) ) {

				$wpssle_data = Plugin::elementor()->db->iterate_data(
					$wpssle_data,
					function ( $wpssle_element ) use ( &$do_update ) {

						if ( isset( $wpssle_element['widgetType'] ) && 'form' === (string) $wpssle_element['widgetType'] ) {

							global $wpssle_header_list, $wpssle_exclude_headertype, $wpssle_spreadsheetid;

							$wpssle_exclude_headertype = array( 'honeypot', 'recaptcha', 'recaptcha_v3', 'html' );

							if ( isset( $wpssle_element['settings'] ) && isset( $wpssle_element['settings']['submit_actions'] ) && in_array( 'WPSyncSheets', $wpssle_element['settings']['submit_actions'], true ) ) {

								$wpssle_settings = $wpssle_element['settings'] ?? array();

								$wpssle_header_list = array();

								$wpssle_spreadsheetid = $wpssle_settings['spreadsheetid'] ?? '';

								$wpssle_sheetname = $wpssle_settings['sheet_name'] ?? '';

								$wpssle_sheetheaders = $wpssle_element['settings']['sheet_headers'] ?? array();

								if ( empty( $wpssle_spreadsheetid ) ) {

									$es_error = new WP_Error( esc_html__( '- Please select spreadsheet : WPSyncSheets', 'wpsse' ), '', '' );

									wp_send_json_error( $es_error );

								}

								if ( 'new' === (string) $wpssle_spreadsheetid && ! isset( $wpssle_settings['new_spreadsheet_name'] ) ) {

									$es_error = new WP_Error( esc_html__( '- Please enter spreadsheet name : WPSyncSheets', 'wpsse' ), '', '' );

									wp_send_json_error( $es_error );

								}

								if ( empty( $wpssle_sheetname ) ) {

									$es_error = new WP_Error( esc_html__( '- Please enter sheet name : WPSyncSheets', 'wpsse' ), '', '' );

									wp_send_json_error( $es_error );

								}

								if ( empty( $wpssle_sheetheaders ) && isset( $wpssle_sheetheaders ) && ! is_array( $wpssle_sheetheaders ) ) {

										$es_error = new WP_Error( esc_html__( '- Please select sheet headers : WPSyncSheets', 'wpsse' ), '', '' );

										wp_send_json_error( $es_error );

								}
							}
						}
					}
				);

			}
		}
	}

	/**

	 * Reset Google API Settings
	 */
	public static function wpssle_reset_settings() {

		if ( ! current_user_can( 'edit_wpsyncsheets_elementor_lite_main_settings' ) ) {

			echo esc_html__( 'You do not have permission to access this page.', 'wpsse' );

			die();

		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'save_api_settings' ) ) {

			echo esc_html__( 'Sorry, your nonce did not verifyy.', 'wpsse' );

			wp_die();

		}

		try {

			$wpssle_google_settings = self::$instance_api->wpssle_option( 'wpsse_google_settings' );

			$settings = array();

			foreach ( $wpssle_google_settings as $key => $value ) {

				$settings[ $key ] = '';

			}

			self::$instance_api->wpssle_update_option( 'wpsse_google_settings', $settings );

			self::$instance_api->wpssle_update_option( 'wpsse_google_accessToken', '' );

		} catch ( Exception $e ) {

			return $e->getMessage(); }

		echo 'successful';

		wp_die();
	}

	/**

	 * Change the row format of spreadsheet.
	 */
	public static function get_row_format() {

		$params = array( 'valueInputOption' => 'RAW' );

		return $params;
	}

	/**

	 * Handles AJAX request to install and activate a WordPress.org plugin.
	 */
	public static function wpssle_install_and_activate_plugin() {

		if ( ! current_user_can( 'install_plugins' ) ) {

			wp_send_json_error( 'Permission denied.' );

		}

		$plugin_slug = isset( $_POST['plugin_slug'] ) ? sanitize_text_field( $_POST['plugin_slug'] ) : '';

		if ( empty( $plugin_slug ) ) {

			wp_send_json_error( 'Plugin slug missing.' );

		}

		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		include_once ABSPATH . 'wp-admin/includes/file.php';

		include_once ABSPATH . 'wp-admin/includes/misc.php';

		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';

		if ( $_POST['plugin_active'] != true ) {

			$upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin() );

			$result = $upgrader->install( "https://downloads.wordpress.org/plugin/{$plugin_slug}.latest-stable.zip" );

			if ( is_wp_error( $result ) ) {

				wp_send_json_error( 'Plugin installation failed: ' . $result->get_error_message() );

			}
		}

		// You might need to dynamically get the correct plugin file.

		$plugins = get_plugins( "/{$plugin_slug}" );

		if ( empty( $plugins ) ) {

			wp_send_json_error( 'Could not find installed plugin.' );

		}

		// Get the first plugin file found

		$plugin_file = key( $plugins );

		$activation = activate_plugin( "{$plugin_slug}/{$plugin_file}" );

		if ( is_wp_error( $activation ) ) {

			wp_send_json_error( 'Activation failed: ' . $activation->get_error_message() );

		}

		wp_send_json_success( 'Plugin installed and activated successfully!' );
	}
}

WPSSLE_Plugin_Setting::wpssle_initilization();