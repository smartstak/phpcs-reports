<?php
/**
 * Gravity Form Feed Settings file.
 *
 * @file class-wpsslgf-feed-settings.php
 * @package   wpsyncsheets-gravity-forms
 * @since 1.0.0
 */

GFForms::include_payment_addon_framework();

/**
 * GFFeedAddOn extended Class.
 *
 * @since 1.0.0
 */
class WPSSLGF_Feed_Settings extends GFFeedAddOn {
	/**
	 * WPSSLGF Version.
	 *
	 * @var $version
	 */
	protected $version = WPSSLGF_VERSION;
	/**
	 * Minimum Gravity Version.
	 *
	 * @var $min_gravityforms_version
	 */
	// phpcs:ignore
	protected $_min_gravityforms_version = '1.9';
	/**
	 * Slug.
	 *
	 * @var $slug
	 */
	// phpcs:ignore
	protected $_slug = 'ggspreadsheetaddon';
	/**
	 * Path.
	 *
	 * @var $path
	 */
	// phpcs:ignore
	protected $_path = 'wpsyncsheets-gravity-forms/wpsyncsheets-lite-gravity-forms.php';
	/**
	 * Path.
	 *
	 * @var $_full_path
	 */
	// phpcs:ignore
	protected $_full_path = __FILE__;
	/**
	 * Plugin Name.
	 *
	 * @var $_title
	 */
	// phpcs:ignore
	protected $_title = 'WPSyncSheets Lite For Gravity Forms';
	/**
	 * Plugin Short Title.
	 *
	 * @var $_short_title
	 */
	// phpcs:ignore
	protected $_short_title = 'WPSyncSheets Lite';
	/**
	 * Sheet setting Doc.
	 *
	 * @var $doc_sheet_setting
	 */
	protected $doc_sheet_setting = 'https://docs.wpsyncsheets.com/wpssg-google-sheets-api-settings/';
	/**
	 * Ticket generate URL.
	 *
	 * @var $submit_ticket
	 */
	protected $submit_ticket = 'https://wordpress.org/support/plugin/wpsyncsheets-gravity-forms/';
	/**
	 * Instance of WPSSLGF_Feed_Settings
	 *
	 * @var $instance
	 */
	private static $instance = null;
	/**
	 * Instance of WPSSLGF_Google_API_Functions
	 *
	 * @var $instance_api
	 */
	private static $instance_api = null;
	/**
	 * Create WPSSLGF_Feed_Settings Instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new WPSSLGF_Feed_Settings();
		}
		return self::$instance;
	}

	/**
	 * Create WPSSLGF_Google_API_Functions Instance.
	 */
	public static function get_clientinstance() {
		if ( null === self::$instance_api ) {
			self::$instance_api = new WPSyncSheetsGravityForms\WPSSLGF_Google_API_Functions();
		}
		return self::$instance_api;
	}


	/**
	 * Plugin starting point. Handles hooks, loading of language files and PayPal delayed payment support.
	 */
	public function init() {
		parent::init();
		add_action( 'gform_after_submission', array( $this, 'wpsslgf_after_submission' ), 10, 2 );
		add_action( 'wp_ajax_wpsslgf_reset_settings', array( $this, 'wpsslgf_reset_settings' ) );
		add_action( 'wp_ajax_wpsslgf_clear_spreadsheet', array( $this, 'wpsslgf_clear_spreadsheet' ) );
		$this->get_clientinstance();
	}

	// # FEED PROCESSING -----------------------------------------------------------------------------------------------
	/**
	 * Process the feed
	 *
	 * @param array $feed The feed object to be processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form The form object currently being processed.
	 *
	 * @return bool|void
	 */
	public function process_feed( $feed, $entry, $form ) {
		// Retrieve the name => value pairs for all fields mapped in the 'mappedFields' field map.
		$field_map = self::$instance->get_field_map_fields( $feed, 'mappedFields' );

		// Loop through the fields from the field map setting building an array of values to be passed to the third-party service.
		$merge_vars = array();
		foreach ( $field_map as $name => $field_id ) {
			// Get the field value for the specified field id.
			$merge_vars[ $name ] = self::$instance->get_field_value( $form, $entry, $field_id );
		}
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function scripts() {

		$scripts = array(
			array(
				'handle'  => 'gform_googlespreadsheet_pluginsettings',
				'deps'    => array( 'jquery' ),
				'src'     => WPSSLGF_URL . 'assets/js/wpsslgf-plugin-settings.js',
				'version' => self::$instance->version,
				'enqueue' => array(
					array(
						'admin_page' => array( 'plugin_settings', 'form_settings' ),
					),
				),
			),
		);
		return array_merge( parent::scripts(), $scripts );
	}

	/**
	 * Return the stylesheets which should be enqueued.
	 *
	 * @return array
	 */
	public function styles() {

		$styles = array(
			array(
				'handle'  => 'gform_googlespreadsheet_form_settings_css',
				'src'     => WPSSLGF_URL . 'assets/css/wpsslgf-form-settings.css',
				'version' => self::$instance->version,
				'enqueue' => array(
					array( 'admin_page' => array( 'form_settings' ) ),
				),
			),
		);
		return array_merge( parent::styles(), $styles );
	}

	/**
	 * Configures the settings which should be rendered on the feed edit page in the Form Settings > Feed Add-On area.
	 *
	 * @return array
	 */
	public function feed_settings_fields() {

		if ( ! self::$instance_api->checkcredenatials() ) {
			$wpsslgf_error = self::$instance_api->getClient( 1 );
			if ( 'Invalid token format' === (string) $wpsslgf_error ) {
				$wpsslgf_error = '<strong class="err-msg">' . esc_html__( 'Error: Invalid Token - ', 'wpssg' ) . '<a href="' . esc_url( admin_url( 'admin.php?page=wpsyncsheets-gravity-forms' ) ) . '">' . esc_html__( 'Click here', 'wpssg' ) . ' </a> ' . esc_html__( 'to check more information.', 'wpssg' );
			}
			return array();
		}
		$feeddata = array();
		// phpcs:disable
		if ( isset( $_GET['id'] ) ) {
			$formdata = GFAPI::get_form( sanitize_text_field( wp_unslash( $_GET['id'] ) ) );
		}
		$check_spreadsheet_header_value = true; 
		if ( isset( $_GET['fid'] ) && 0 !== (int) sanitize_text_field( wp_unslash($_GET['fid']) ) ){
			$feed_id = sanitize_text_field( wp_unslash( $_GET['fid'] ) );
			$form_id = sanitize_text_field( wp_unslash( $_GET['id'] ) );
			$feeddata = GFAPI::get_feed( $feed_id, $form_id );
			if( isset( $feeddata['meta']['spreedsheets'] ) && isset( $feeddata['meta']['sheetselect'] ) ){
				$sheet_data = self::$instance->wpsslgf_check_sheet_exist( $feeddata['meta']['spreedsheets'], $feeddata['meta']['sheetselect'] );
				if ( '' === (string)$sheet_data['spreadsheetid'] || '' === (string)$sheet_data['sheetname']) {
					$feeddata['meta']['spreedsheets'] = $sheet_data['spreadsheetid'];
					$feeddata['meta']['sheetselect'] = $sheet_data['sheetname'];
					$result = self::$instance->update_feed_meta( $feed_id, $feeddata['meta'] );
				}
				if ( '' !== $sheet_data['spreadsheetid'] && '' !== $sheet_data['sheetname'] ) {
					$spreadsheetid = $sheet_data['spreadsheetid'];
					$sheetname     = $sheet_data['sheetname'] . '!A1:ZZ1';
					$response      = self::$instance_api->get_row_list( $spreadsheetid, $sheetname );
					
					if ( empty( $response->getValues() ) ) {
						$check_spreadsheet_header_value = false;
					}
				}
			}
		}
		?>
		<?php
		/* Build base fields array. */
		$base_fields = array(
			'title'  => 'WPSyncSheets Lite For Gravity Forms Feed Settings',
			'fields' => array(
				array(
					'name'       => '',
					'label'      => '',
					'type'       => 'buy_pro',
				),
				array(
					'name'          => 'feedName',
					'label'         => esc_html__( 'Feed Name', 'wpssg' ),
					'type'          => 'text',
					'required'      => true,
					'class'         => 'medium',
					'default_value' => self::$instance->get_default_feed_name(),
					'tooltip'       => '<h6>' . esc_html__( 'Name', 'wpssg' ) . '</h6>' . esc_html__( 'Enter a feed name to uniquely identify this setup.', 'wpssg' ),
				),
				array(
					'label'    => esc_html__( 'Select Spreadsheet', 'wpssg' ),
					'type'     => 'select',
					'name'     => 'spreedsheets',
					'tooltip'  => esc_html__( 'Select spreadsheet associated with your google account', 'wpssg' ),
					'class'    => 'medium',
					'choices'  => self::$instance->wpsslgf_list_googlespreedsheet(),
					'onchange' => "jQuery('select[name=\"_gaddon_setting_sheetselect\"]').val('');jQuery('select[name=\"_gform_setting_sheetselect\"]').val('');jQuery(this).parents('form').submit();",
				),
				array(
					'label'      => esc_html__( 'Google Spreadsheet', 'wpssg' ),
					'type'       => 'new_spreadsheet_fun',
					'name'       => 'newspreedsheets',
					'dependency' => array(
						'field'  => 'spreedsheets',
						'values' => array( 'new' ),
					),
					'tooltip'    => esc_html__( 'Create new Google spreadsheet into your google account', 'wpssg' ),
					'class'      => 'ssheet',
				),
				array(
					'label'      => esc_html__( 'Select Sheet', 'wpssg' ),
					'type'       => 'select',
					'name'       => 'sheetselect',
					'tooltip'    => esc_html__( 'Select sheet as you have created with above selected spreadsheet', 'wpssg' ),
					'class'      => 'medium',
					'dependency' => array( $this, 'wpsslgf_check_new_spreadsheet_value' ),
					'choices'    => self::$instance->wpsslgf_sheet_for_feed_setting(),
					'onchange'   => "jQuery('select[name=\"_gaddon_setting_mappedFields\"]').val('');jQuery(this).parents('form').submit();",
				),
				array(
					'label'      => 'Set Default Headers',
					'type'       => 'headers_button',
					'tooltip'    => esc_html__( 'Set default headers from form fields', 'wpssg' ),
					'dependency' => array( $this, 'wpsslgf_check_spreadsheet_header_value' ),
				),
				array(
					'name'       => '',
					'label'      => '',
					'type'       => 'note_ins',
					'dependency' => array( $this, 'wpsslgf_check_spreadsheet_header_value' ),
				),
			),
		);
		/* Build Other feed fields array. */
		$other_fields = array(
			'title'  => __( 'Other Feed Settings', 'wpssg' ),
			'fields' => array(
				array(
					'name'  => '',
					'label' => '',
					'type'  => 'headers_note',
				),
				array(
					'name'       => 'mappedFields',
					'label'      => esc_html__( 'Map Fields', 'wpssg' ),
					'type'       => 'field_map',
					'field_map'  => self::$instance->wpsslgf_mapfield_for_feed_setting(),
					'merge_tags' => '',
				),
				array(
					'label'   => esc_html__( 'Submission Date', 'wpssg' ),
					'type'    => 'checkbox',
					'name'    => 'submissiondate',
					'tooltip' => esc_html__( 'Submission Date', 'wpssg' ),
					'choices' => array(
						array(
							'label'         => esc_html__( 'Enable', 'wpssg' ),
							'name'          => 'submissiondate',
							'default_value' => 0,
						),
					),
				),
				array(
					'label'   => esc_html__( 'Form ID', 'wpssg' ),
					'type'    => 'checkbox',
					'name'    => 'wpssg_form_id',
					'tooltip' => esc_html__( 'Form ID', 'wpssg' ),
					'choices' => array(
						array(
							'label'         => esc_html__( 'Enable', 'wpssg' ),
							'name'          => 'wpssg_form_id',
							'default_value' => 0,
						),
					),
				),
				array(
					'label'   => esc_html__( 'Form Title', 'wpssg' ),
					'type'    => 'checkbox',
					'name'    => 'wpssg_form_title',
					'tooltip' => esc_html__( 'Form Title', 'wpssg' ),
					'choices' => array(
						array(
							'label'         => esc_html__( 'Enable', 'wpssg' ),
							'name'          => 'wpssg_form_title',
							'default_value' => 0,
						),
					),
				),
				array(
					'label'   => esc_html__( 'Freeze Header', 'wpssg' ),
					'type'    => 'checkbox',
					'name'    => 'freezecheckbox',
					'tooltip' => esc_html__( 'Freeze header row (first row) of sheet', 'wpssg' ),
					'choices' => array(
						array(
							'label'         => '',
							'name'          => 'freezecheckbox',
							'default_value' => 0,
						),
					),
				),
				array(
					'name'           => '',
					'label'          => esc_html__( 'View, Clear & Download Spreadsheet', 'wpssg' ),
					'type'           => 'view_download_spreadsheet_button',
				)
			),
		);
		/* Build conditional logic fields array. */
		$conditional_fields = array();

		if( true === $check_spreadsheet_header_value ){
			return array( $base_fields, $other_fields, $conditional_fields );
		} else {
			return array( $base_fields, $conditional_fields );
		}
	}

	/**
	 * Check Create New spreadsheet value.
	 */
	public function wpsslgf_check_new_spreadsheet_value() {
		/* Get current feed. */
		$feed = self::$instance->get_current_feed();

		/* Get posted settings. */
		$posted_settings = self::$instance->get_posted_settings();

		if ( ! empty( $posted_settings ) ) {
			/* Show if an action is chosen */
			if ( (string) rgar( $posted_settings, 'spreedsheets' ) === 'new' || (string) rgar( $posted_settings, 'spreedsheets' ) === '' ) {
				return false;
			}
		} else {
			/* Show if an action is chosen */
			if ( (string) rgars( $feed, 'meta/spreedsheets' ) === 'new' || (string) rgars( $feed, 'meta/spreedsheets' ) === '' ) {
				return false;
			}
		}
		return true;
	}

	
	/**
	 * Create New Spreadsheet into the user's google account function
	 *
	 * @param string $field Field.
	 * @param bool   $echo Echo or not.
	 */
	public function settings_new_spreadsheet_fun( $field, $echo = true ) {

		$html =
			'<p> ' . esc_html__( 'Spreadsheet Name', 'wpssg' ) . '</p>
			<input type="text" name="newspreadsheetlabel" value="" class="medium gaddon-setting gaddon-text" id="newspreadsheetlabel"><br/>
			<p> ' . esc_html__( 'Sheet Name', 'wpssg' ) . ' </p>
			<input type="text" name="sheetlabel" value="" class="medium gaddon-setting gaddon-text" id="sheetlabel"><br/>
			
			<input type="checkbox" class="gaddon-setting gaddon-checkbox" id="headercheckbox" name="headercheckbox" checked="checked" disabled><span class="headercheckboxspan">' . esc_html__( 'Set deafult headers as per the form fields', 'wpssg' ) . '</span><br/><br/>
			<a href="#" class="button" id="gform_google_new_spreadsheet_button">' . esc_html__( 'Create New Spreadsheet', 'wpssg' ) . '</a>';

		$html .= wp_nonce_field( 'save_general_settings', 'wpsslgf_general_settings' );
		if ( $echo ) {
			$allowed_html = wp_kses_allowed_html( 'post' );
			echo wp_kses( $html, $allowed_html );
		}
		return $html;
	}

	/**
	 * Set instruction for the sheet headers.
	 *
	 * @param string $field Field.
	 * @param bool   $echo Echo or not.
	 */
	public function settings_note_ins( $field, $echo = true ) {

		$html         = '<i>' . esc_html__( 'Note: Click here to set default headers as per the form fields or create manually to start with Entry Id as a first header name.', 'wpssg' ) . '</i>';
		if ( $echo ) {
			$allowed_html = wp_kses_allowed_html( 'post' );
			echo wp_kses( $html, $allowed_html );
		}
		return $html;
	}
	/**
	 * No access Note.
	 *
	 * @param string $field Field.
	 * @param bool   $echo Echo or not.
	 */
	public function settings_no_access_note( $field, $echo = true ) {

		$html         = '<p>' . esc_html__( 'You do not have permission to access this page.', 'wpssg' ) . '</p>';
		if ( $echo ) {
			$allowed_html = wp_kses_allowed_html( 'post' );
			echo wp_kses( $html, $allowed_html );
		}
		return $html;
	}
	/**
	 * Set instruction for the sheet headers.
	 *
	 * @param string $field Field.
	 * @param bool   $echo Echo or not.
	 */
	public function settings_headers_note( $field, $echo = true ) {

		$html = '<i>' . esc_html__( 'Note: Entry Id must have to be the first header column in the sheet.', 'wpssg' ) . '</i>';
		if ( $echo ) {
			$allowed_html = wp_kses_allowed_html( 'post' );
			echo wp_kses( $html, $allowed_html );
		}
		return $html;
	}
	/**
	 * Buy button for WPSyncSheets For Gravity Forms Pro.
	 *
	 * @param string $field Field.
	 * @param bool   $echo Echo or not.
	 */
	public function settings_buy_pro( $field, $echo = true ) {

		$html         = '
		<div class="wpsslgf-feed-prosection">
            <div class="duc-btn1 pro-version">
                <a target="_blank" href="'.esc_url(WPSSLGF_BUY_PRO_VERSION_URL).'" ><span class="dashicons dashicons-admin-links"></span>'.esc_html__( "Upgrade To Pro", "wpssg" ).'</a>
            </div>
		</div>';
		return $html;
	}
	/**
	* Configures the View Spreadsheet button .
	*
	* @return array
	*/
	public function settings_view_download_spreadsheet_button( $field, $echo = true ) {
	
		$html = sprintf(
			'<a href="#" class="button" id="gform_google_view_button" target="_blank">%1$s</a>',
			esc_html__( 'View Spreadsheet', 'wpssg' )
		);
		$html .= sprintf(
			'<a href="#" class="button" id="gform_google_clear_button">%1$s</a>',
			esc_html__( 'Clear Spreadsheet', 'wpssg' )
		);
		$html .=
			'<img src="' . esc_url( admin_url( 'images/spinner.gif' ) ) . '" id="clearloader"><span id="cleartext">' . esc_html__( 'Clearing...', 'wpssg' ) . '</span>';
		
		$html .= sprintf(
			'<a href="#" class="button" id="gform_google_download_button" target="_blank">%1$s</a>',
			esc_html__( 'Download Spreadsheet', 'wpssg' )
		);
		$html .= wp_nonce_field( 'save_general_settings', 'wpsslgf_general_settings' );
		if ( $echo ) {
			$allowed_html = wp_kses_allowed_html( 'post' );
			echo wp_kses( $html, $allowed_html );
		}
		return $html;
	}
	/**
	 * Configures the synchronization button for synchronize old entries to Google Sheet .
	 *
	 * @return array
	 */
	public function settings_headers_button( $field, $echo = true ) {
		$html  = sprintf(
			' <a href="#" class="button" id="gform_google_header_button">%1$s</a><br>',
			esc_html__( 'Click Here', 'wpssg' )
		);
		$html .= wp_nonce_field( 'save_general_settings', 'wpsslgf_general_settings' );
		if ( $echo ) {
			$allowed_html = wp_kses_allowed_html( 'post' );
			echo wp_kses( $html, $allowed_html );
		}
		return $html;
	}
	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @return array
	 */
	public function feed_list_columns() {
		return array(
			'feedName' => esc_html__( 'Name', 'wpssg' ),
		);
	}

	/**
	 * Prevent feeds being listed or created if an authToken isn't blank.
	 *
	 * @return bool
	 */
	public function can_create_feed() {
		// Get the plugin settings.
		if ( ! self::$instance_api->getClient() ) {
			return false;
		}
		$wpsslgf_google_settings = self::$instance_api->wpsslgf_option( 'wpssg_google_settings' );
		// Access a specific setting e.g. an authToken.
		if ( isset( $wpsslgf_google_settings[2] ) && ! empty( $wpsslgf_google_settings[2] ) ) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * Configure Addon Message.
	 */
	public function configure_addon_message() {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wpsyncsheets-gravity-forms' ) ) . '">' . esc_html__( 'WPSynSheets Google API Settings', 'wpssg' ) . '</a>.';
		return esc_html__( 'To get started, please configure your ', 'wpssg' ) . $settings_link;
	}
	/**
	 * Prepare Google Spreedsheet sheet name for feed setting.
	 *
	 * @access public
	 * @return array $choices
	 */
	public function wpsslgf_sheet_for_feed_setting() {

		/* Build choices array. */
		$choices = array(
			array(
				'label' => esc_html__( 'Select a Sheet List', 'wpssg' ),
				'value' => '',
			),
		);

		/* Get feed settings. */
		$settings = self::$instance->is_postback() ? self::$instance->get_posted_settings() : self::$instance->get_feed( self::$instance->get_current_feed_id() );

		$settings = isset( $settings['meta'] ) ? $settings['meta'] : $settings;
		if ( ! isset( $settings['spreedsheets'] ) || '' === (string) $settings['spreedsheets'] || 'new' === (string) $settings['spreedsheets'] ) {
			return $choices;
		}

		$response = self::$instance_api->get_sheet_listing( $settings['spreedsheets'] );

		foreach ( $response->getSheets() as $s ) {
			$sheets[] = $s['properties']['title'];
		}

		/* Add the Google Sheets to the choices array. */
		if ( ! empty( $sheets ) ) {
			$i = 0;
			foreach ( $sheets as $list ) {

				$choices[] = array(
					'label' => $list,
					'value' => $list,
				);
				$i++;
			}
		}
		return $choices;
	}

	/**
	 * Prepare Google Spreedsheet sheet headers for mapping fields to form fields.
	 *
	 * @access public
	 * @return array $choices
	 */
	public function wpsslgf_check_spreadsheet_header_value() {

		$settings = self::$instance->is_postback() ? self::$instance->get_posted_settings() : self::$instance->get_feed( self::$instance->get_current_feed_id() );
		$settings = isset( $settings['meta'] ) ? $settings['meta'] : $settings;

		if ( ! empty( $settings ) && isset( $settings['sheetselect'] ) ) {
			$sheet_data = self::$instance->wpsslgf_check_sheet_exist( $settings['spreedsheets'], $settings['sheetselect'] );
			if ( '' !== $sheet_data['spreadsheetid'] && '' !== $sheet_data['sheetname'] ) {
				$spreadsheetid = $settings['spreedsheets'];
				$sheetname     = $settings['sheetselect'] . '!A1:ZZ1';
				$response      = self::$instance_api->get_row_list( $spreadsheetid, $sheetname );
				$values        = $response->getValues();
				if ( empty( $response['values'] ) ) {
					return true;
				} else {
					return false;
				}
			}
		}
	}
	/**
	 * Check Google Spreedsheet sheet headers for mapping fields to form fields.
	 *
	 * @access public
	 * @return array $choices
	 */
	public function wpsslgf_check_spreadsheet_header_values() {

		$settings = self::$instance->is_postback() ? self::$instance->get_posted_settings() : self::$instance->get_feed( self::$instance->get_current_feed_id() );
		$settings = isset( $settings['meta'] ) ? $settings['meta'] : $settings;

		if ( ! empty( $settings ) && isset( $settings['sheetselect'] ) && isset( $settings['spreedsheets'] ) && ! empty( $settings['spreedsheets'] ) ) {
			$sheet_data = self::$instance->wpsslgf_check_sheet_exist( $settings['spreedsheets'], $settings['sheetselect'] );
			if ( '' !== $sheet_data['spreadsheetid'] && '' !== $sheet_data['sheetname'] ) {
				$spreadsheetid = $settings['spreedsheets'];
				$sheetname     = $settings['sheetselect'] . '!A1:ZZ1';
				$response      = self::$instance_api->get_row_list( $spreadsheetid, $sheetname );
				$values        = $response->getValues();

				if ( empty( $response['values'] ) ) {
					return false;
				} else {
					return true;
				}
			}
		}
	}
	/**
	 * Prepare Google Spreedsheet sheet headers for mapping fields to form fields.
	 *
	 * @access public
	 * @return array $choices
	 */
	public function wpsslgf_mapfield_for_feed_setting() {

		$settings = self::$instance->is_postback() ? self::$instance->get_posted_settings() : self::$instance->get_feed( self::$instance->get_current_feed_id() );
		$settings = isset( $settings['meta'] ) ? $settings['meta'] : $settings;
		//phpcs:ignore
		if ( isset( $_GET['id'] ) ) {
			//phpcs:ignore
			$form_id = (int) sanitize_text_field( wp_unslash( $_GET['id'] ) );
			$form    = RGFormsModel::get_form_meta( $form_id );
		}
		$fields_array = self::$instance->wpsslgf_generate_headers( $form, 'id' );
		if ( ! empty( $settings ) && isset( $settings['sheetselect'] ) ) {
			$sheet_data = self::$instance->wpsslgf_check_sheet_exist( $settings['spreedsheets'], $settings['sheetselect'] );
			if ( '' !== $sheet_data['spreadsheetid'] && '' !== $sheet_data['sheetname'] ) {
				$spreadsheetid = $settings['spreedsheets'];
				$sheetname     = $settings['sheetselect'] . '!A1:ZZ1';
				$response      = self::$instance_api->get_row_list( $spreadsheetid, $sheetname );
				$values        = $response->getValues();
				$choices       = array();
				if ( empty( $response['values'] ) ) {
					return;
				} else {
					$i = 0;
					foreach ( $values as $row ) {
						if ( 0 === (int) $i ) {
							foreach ( $row as $list ) {
								if ( 'Submission Date' === (string) $list || 'Form ID' === (string) $list || 'Form Title' === (string) $list ) {
									continue;
								}
								$choices[] = array(
									'name'          => str_replace( array( ' ', "'" ), '_', strtolower( trim( $list ) ) ),
									'label'         => $list,
									'default_value' => array_search( $list, $fields_array, true ),
								);
							}
							return $choices;
						}
						$i++;
					}
				}
			} else {
				return;
			}
		} else {
			return;
		}
	}

	/**
	 * Prepare Google Spreedsheet sheet headers.
	 *
	 * @param string $spreadsheetid Google Spreadsheet id.
	 * @param string $sheetname Google Sheet Name.
	 * @access public
	 * @return array $row
	 */
	public function wpsslgf_get_mapfields( $spreadsheetid, $sheetname ) {
		$response = self::$instance_api->get_row_list( $spreadsheetid, $sheetname );
		$values   = $response->getValues();
		if ( empty( $values ) ) {
			print "No data found.\n";
		} else {
			$i = 0;
			foreach ( $values as $row ) {
				// Print columns A and E, which correspond to indices 0 and 4.
				if ( 0 === (int) $i ) {
					return $row;
				}
				$i++;
			}
		}
	}
	/** Conver string to readable form.
	 *
	 * @param array $chars Character.
	 * @access public
	 * @return array $row
	 */
	public function wpsslgf_convert_to_readable( $chars ) {
		$encoding = ini_get( 'mbstring.internal_encoding' );
		$str      = preg_replace_callback(
			'/\\\\u([0-9a-fA-F]{4})/u',
			function( $match ) use ( $encoding ) {
				return mb_convert_encoding( pack( 'H*', $match[1] ), $encoding, 'UTF-16BE' );
			},
			$chars
		);
		return $str;
	}
	/**
	 * Insert data into Google Spreadsheet after form submission.
	 *
	 * @param array $entry Entry Array.
	 * @param array $form Form Array.
	 * @access public
	 * @return array $row
	 */
	public function wpsslgf_after_submission( $entry, $form ) {

		if ( ! self::$instance_api->getClient() ) {
			return;
		}

		$form_field_type       = array();
		$formfeed              = self::$instance->get_feeds( $form['id'] );
		$wpsslgf_form_data       = GFAPI::get_form( $form['id'] );
		$form_field_type       = self::$instance->wpsslgf_form_field_type( $form );
		$wpsslgf_google_settings = self::$instance_api->wpsslgf_option( 'wpssg_google_settings' );
		$formfeedcountdata     = count( $formfeed );

		// Access a specific setting e.g. an authToken.
		if ( isset( $wpsslgf_google_settings[2] ) && ! empty( $wpsslgf_google_settings[2] ) ) {
			try {
				for ( $i = 0; $i < $formfeedcountdata; $i++ ) {
					$values_data = array();

					if ( '1' === (string) $formfeed[ $i ]['is_active'] ) {
						$feed = $formfeed[ $i ]['meta'];
						$flag = 1;

						$is_condition_enabled = rgar( $feed, 'feed_condition_conditional_logic' ) === '1';

						$logic = rgars( $feed, 'feed_condition_conditional_logic_object/conditionalLogic' );

						if ( ! empty( $is_condition_enabled ) && ! empty( $logic ) ) {
							$check = GFCommon::evaluate_conditional_logic( $logic, $form, $entry );

							if ( ! $check ) {
								continue;
							}
						}

						if ( isset( $feed['spreedsheets'] ) && isset( $feed['sheetselect'] ) ) {
							$sheet_data = self::$instance->wpsslgf_check_sheet_exist( $feed['spreedsheets'], $feed['sheetselect'] );
							if ( '' === (string) $sheet_data['spreadsheetid'] || '' === (string) $sheet_data['sheetname'] ) {
								continue;
							}

							$formmapfieldsval[ $i ] = self::$instance->wpsslgf_get_mapfields( $feed['spreedsheets'], $feed['sheetselect'] );

							$spreadsheetid         = $feed['spreedsheets'];
							$range                 = $feed['sheetselect'];
							$countformmapfieldsval = count( $formmapfieldsval[ $i ] );

							$val         = self::$instance->wpsslgf_prepared_headers( $formmapfieldsval[ $i ], $feed );
							$values_data = self::$instance->wpsslgf_prepared_values( $form, $val, $entry, $feed );

							$values = array( $values_data );

							$allentry    = self::$instance_api->get_row_list( $spreadsheetid, $range . '!A:A' );
							$data        = $allentry->getValues();
							$requestbody = self::$instance_api->valuerangeobject( $values );
							$params      = self::$instance->get_row_format( $feed );
							$range       = $range . '!A' . ( count( $data ) + 1 );
							$param       = self::$instance_api->setparamater( $spreadsheetid, $range, $requestbody, $params );
							$response    = self::$instance_api->updateentry( $param );
						}
					}
				}
			} catch ( Exception $e ) {
				$allowed_html = wp_kses_allowed_html( 'post' );
				echo wp_kses( 'Message : ' . $e->getMessage(), $allowed_html );
			}
		}
	}
	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @return string
	 */
	public function get_menu_icon() {
		$icon = wp_remote_get( WPSSLGF_URL . 'assets/images/dashicons-wpsyncsheets-black.svg' );
		$body = wp_remote_retrieve_body( $icon );
		return $body;
	}
	/**
	 * Prepare Google Spreadsheet list
	 *
	 * @access public
	 * @return array $sheetarray
	 */
	public function wpsslgf_list_googlespreedsheet() {
		$settings = self::$instance->is_postback() ? self::$instance->get_posted_settings() : self::$instance->get_feed( self::$instance->get_current_feed_id() );
		$settings = isset( $settings['meta'] ) ? $settings['meta'] : $settings;

		/* Build choices array. */
		$sheetarray = array(
			array(
				'label' => esc_html__( 'Select Google Spreeadsheet List', 'wpssg' ),
				'value' => '',
			),
		);
		$sheetarray = self::$instance_api->get_spreadsheet_listing( $sheetarray );
		return $sheetarray;
	}

	/**
	 * Save Feed Settings
	 *
	 * @param int   $feed_id Feed ID.
	 * @param int   $form_id Form ID.
	 * @param array $settings Settings Array.
	 * @access public
	 * @return array $sheetarray
	 */
	public function save_feed_settings( $feed_id, $form_id, $settings ) {
		
		if ( $feed_id ) {
			$result = self::$instance->update_feed_meta( $feed_id, $settings );
			$result = $feed_id;
		} else {
			$result = self::$instance->insert_feed( $form_id, true, $settings );
		}

		$spreadsheetid = isset( $_POST['_gaddon_setting_spreedsheets'] ) ? sanitize_text_field( wp_unslash( $_POST['_gaddon_setting_spreedsheets'] ) ) : '';
		if ( empty( $spreadsheetid ) ) {
			$spreadsheetid = isset( $_POST['_gform_setting_spreedsheets'] ) ? sanitize_text_field( wp_unslash( $_POST['_gform_setting_spreedsheets'] ) ) : '';
		}
		$sheetname = isset( $_POST['_gaddon_setting_sheetselect'] ) ? sanitize_text_field( wp_unslash( $_POST['_gaddon_setting_sheetselect'] ) ) : '';
		if ( empty( $sheetname ) ) {
			$sheetname = isset( $_POST['_gform_setting_sheetselect'] ) ? sanitize_text_field( wp_unslash( $_POST['_gform_setting_sheetselect'] ) ) : '';
		}

		/*
		* Check for create new spreadsheet request
		*
		*/
		if ( isset( $_POST['createnewspreadsheet'] ) ) {

			if ( isset( $_POST['newspreadsheetlabel'] ) && ! empty( $_POST['newspreadsheetlabel'] ) ) {
				$spreadsheetname = sanitize_text_field( wp_unslash( $_POST['newspreadsheetlabel'] ) );
			}

			if ( isset( $_POST['sheetlabel'] ) && ! empty( $_POST['sheetlabel'] ) ) {
				$sheetname = sanitize_text_field( wp_unslash( $_POST['sheetlabel'] ) );
			}
			// TODO: Assign values to desired properties of `requestbody`.
			$requestbody = self::$instance_api->newspreadsheetobject( $spreadsheetname, $sheetname );
			$response    = self::$instance_api->createspreadsheet( $requestbody );
			// phpcs:ignore
			$spreadsheetid = $response->spreadsheetId;
			$range         = trim( $sheetname ) . '!A1';
			$form          = RGFormsModel::get_form_meta( $form_id );

			$fields_array = self::$instance->wpsslgf_generate_headers( $form );

			$values = array( array_values( array_filter( $fields_array ) ) );

			$requestbody = self::$instance_api->valuerangeobject( $values );

			$params   = self::$instance->get_row_format();
			$param    = self::$instance_api->setparamater( $spreadsheetid, $range, $requestbody, $params );
			$response = self::$instance_api->appendentry( $param );

			$settings['spreedsheets'] = $spreadsheetid;
			$settings['sheetselect']  = $sheetname;

			$fid     = $result;
			$result1 = self::$instance->update_feed_meta( $result, $settings );
			// phpcs:ignore
			print( '<script>window.location.href="admin.php?page=gf_edit_forms&view=settings&subview=ggspreadsheetaddon&id=' . $form_id . '&fid=' . $fid . '"</script>' );
		}

		if ( ( isset( $_POST['_gaddon_setting_freezecheckbox'] ) && sanitize_text_field( wp_unslash( $_POST['_gaddon_setting_freezecheckbox'] ) ) !== '' ) || ( isset( $_POST['_gform_setting_freezecheckbox'] ) && sanitize_text_field( wp_unslash( $_POST['_gform_setting_freezecheckbox'] ) ) !== '' ) && 'new' !== $spreadsheetid ) {

			$wpsslgf_freeze = isset( $_POST['_gaddon_setting_freezecheckbox'] ) ? sanitize_text_field( wp_unslash( $_POST['_gaddon_setting_freezecheckbox'] ) ) : '';
			if ( empty( $wpsslgf_freeze ) || 0 === $wpsslgf_freeze ) {
				$wpsslgf_freeze = isset( $_POST['_gform_setting_freezecheckbox'] ) ? sanitize_text_field( wp_unslash( $_POST['_gform_setting_freezecheckbox'] ) ) : '';
			}

			if ( 'on' === (string) $wpsslgf_freeze || 1 === (int) $wpsslgf_freeze ) {
				$wpsslgf_freeze = 1;
			} else {
				$wpsslgf_freeze = 0;
			}
			$response = self::$instance_api->get_sheet_listing( $spreadsheetid );
			foreach ( $response->getSheets() as $key => $value ) {
				if ( (string) $value['properties']['title'] === $sheetname ) {
					$requestbody                    = self::$instance_api->freezeobject( $value['properties']['sheetId'], $wpsslgf_freeze );
					$requestobject                  = array();
					$requestobject['spreadsheetid'] = $spreadsheetid;
					$requestobject['requestbody']   = $requestbody;
					self::$instance_api->formatsheet( $requestobject );
				}
			}
		}
		if ( isset( $_POST['submissiondate'] ) || isset( $_POST['wpssg_form_id'] ) || isset( $_POST['wpssg_form_title'] ) ) {
			$range    = $sheetname . '!A1:ZZ1';
			$response = self::$instance_api->get_row_list( $spreadsheetid, $range );
			$data     = $response->getValues();
			$fields_array   = $data[0];
			$update_header  = false;
			if (isset( $_POST['submissiondate'] ) && ! in_array( 'Submission Date', $data[0], true ) ) {
				$fields_array[] = 'Submission Date';
				$update_header  = true;
			}
			if (isset( $_POST['wpssg_form_id'] ) && ! in_array( 'Form ID', $data[0], true ) ) {
				$fields_array[] = 'Form ID';
				$update_header  = true;
			}
			if (isset( $_POST['wpssg_form_title'] ) && ! in_array( 'Form Title', $data[0], true ) ) {
				$fields_array[] = 'Form Title';
				$update_header  = true;
			}
			if($update_header){
				$range          = $sheetname . '!A1';
				$values         = array( $fields_array );

				$requestbody = self::$instance_api->valuerangeobject( $values );
				$params      = self::$instance->get_row_format();
				$param       = self::$instance_api->setparamater( $spreadsheetid, $range, $requestbody, $params );
				$response    = self::$instance_api->updateentry( $param );
			}
		}
		
		if ( isset( $_POST['headerset'] ) ) {
			$range = $sheetname . '!A1';

			$form         = RGFormsModel::get_form_meta( $form_id );
			$fields_array = self::$instance->wpsslgf_generate_headers( $form );
			$values       = array( array_values( array_filter( $fields_array ) ) );

			$requestbody = self::$instance_api->valuerangeobject( $values );

			$params   = self::$instance->get_row_format();
			$param    = self::$instance_api->setparamater( $spreadsheetid, $range, $requestbody, $params );
			$response = self::$instance_api->updateentry( $param );

			$fid = $result;
			// phpcs:ignore
			print( '<script>window.location.href="admin.php?page=gf_edit_forms&view=settings&subview=ggspreadsheetaddon&id=' . $form_id . '&fid=' . $fid . '"</script>' );
		}
		return $result;
	}

	/**
	 * Enable feed duplication.
	 *
	 * @param int $id ID.
	 * @access public
	 * @return bool
	 */
	public function can_duplicate_feed( $id ) {
		return false;
	}

	/**
	 * Heading row for field map table.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @uses GFAddOn::field_map_title()
	 *
	 * @return string
	 */
	public function field_map_table_header() {
		return '<thead>
			<tr>
				<th>' . esc_html__( 'Sheet Headers', 'wpssg' ) . '</th>
				<th>' . esc_html__( 'Form Field', 'wpssg' ) . '</th>
			</tr>
		</thead>';
	}
	/**
	 * Generate headers.
	 *
	 * @param array  $form Form Array.
	 * @param string $format Return with id.
	 */
	public function wpsslgf_generate_headers( $form, $format = '' ) {
		$fields_array       = array();
		$fields_array['id'] = 'Entry Id';
		if ( is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				if ( 1 === (int) $field['displayOnly'] ) {
					continue;
				}
				if ( 'checkbox' === (string) $field['type'] ) {
					$fields_array[ $field->id ] = GFCommon::get_label( $field, $field->id );
				} elseif ( isset( $field['inputs'] ) && is_array( $field['inputs'] ) && ! empty( $field['inputs'] ) ) {

					foreach ( $field['inputs'] as $input ) {
						if ( ! isset( $input['isHidden'] ) ) {
							$fields_array[ $input['id'] ] = GFCommon::get_label( $field, $input['id'] );
						} elseif ( isset( $input['isHidden'] ) && 1 !== $input['isHidden'] ) {
							$fields_array[ $input['id'] ] = GFCommon::get_label( $field, $input['id'] );
						}
					}
				}

				if ( 'list' === (string) $field['type'] && is_array( $field['choices'] ) && ! empty( $field['choices'] ) ) {
					foreach ( $field['choices'] as $inx => $choice ) {
						if ( ! empty( $choice['text'] ) ) {
							$fields_array[ $field->id . '.' . $inx ] = $choice['text'];
						}
					}
				} elseif ( isset( $field ) && is_object( $field ) && ! isset( $field['inputs'] ) ) {
					$fields_array[ $field->id ] = GFCommon::get_label( $field, $field->id );
				}
			}
		}
		
		if ( 'id' === (string) $format ) {
			return $fields_array;
		} else {
			return array_values( $fields_array );
		}
	}

	/**
	 * Check Field is display only or not.
	 *
	 * @param array $form Form Array.
	 */
	public function wpsslgf_form_field_type( $form ) {
		$fields_array = array();
		if ( is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				if ( 1 === (int) $field['displayOnly'] ) {
					continue;
				}
				$fields_array[ $field->id ] = $field['type'];
			}
		}
		return $fields_array;
	}
	/**
	 * Reset Google API Settings
	 */
	public static function wpsslgf_reset_settings() {
		if ( ! current_user_can( 'edit_wpsyncsheets_gravity_forms_lite_main_settings' ) ) {
			echo esc_html__( 'You do not have permission to access this page.', 'wpssg' );
			die();
		}
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'save_api_settings' ) ) {
			echo esc_html__( 'Sorry, your nonce did not verifyy.', 'wpssg' );
			wp_die();
		}
		try {
			$wpsslgf_google_settings_value = self::$instance_api->wpsslgf_option( 'wpssg_google_settings' );
			$settings                    = array();
			foreach ( $wpsslgf_google_settings_value as $key => $value ) {
				$settings[ $key ] = '';
			}
			self::$instance_api->wpsslgf_update_option( 'wpssg_google_settings', $settings );
			self::$instance_api->wpsslgf_update_option( 'wpssg_google_accessToken', '' );
		} catch ( Exception $e ) {
			$allowed_html = wp_kses_allowed_html( 'post' );
			echo wp_kses( $e->getMessage(), $allowed_html );
		}
		echo 'successful';
		wp_die();
	}
	/**
	 * Change the row format of spreadsheet.
	 *
	 * @param array $settings An array containing the sheet object to change the row format of spreadsheet.
	 */
	public function get_row_format( $settings = array() ) {
		if ( isset( $settings['row_format'] ) && 'userenter' === (string) $settings['row_format'] ) {
			$params = array( 'valueInputOption' => 'USER_ENTERED' );
		} else {
			$params = array( 'valueInputOption' => 'RAW' );
		}
		return $params;
	}
	/**
	 * Prepare Header List.
	 *
	 * @param array $formmapfieldsval An array containing the form fields.
	 * @param array $feed An array form.
	 */
	public function wpsslgf_prepared_headers( $formmapfieldsval, $feed ) {
		$countformmapfieldsval = count( $formmapfieldsval );
		$val                   = array();
		for ( $j = 0; $j < $countformmapfieldsval; $j++ ) {
			if ( 'Submission Date' === (string) $formmapfieldsval[ $j ] ) {
				$val[ $j ] = 'Submission Date';
				continue;
			}
			if ( 'Form ID' === (string) $formmapfieldsval[ $j ] ) {
				$val[ $j ] = 'Form ID';
				continue;
			}
			if ( 'Form Title' === (string) $formmapfieldsval[ $j ] ) {
				$val[ $j ] = 'Form Title';
				continue;
			}
			$fieldname = str_replace( array( ' ', '.' ), '_', strtolower( trim( $formmapfieldsval[ $j ] ) ) );
			if ( isset( $feed[ 'mappedFields_' . $fieldname ] ) ) {
				$val[ $j ] = $feed[ 'mappedFields_' . $fieldname ];
			}
		}
		return $val;
	}
	/**
	 * Prepare Value for insert in Google Spreadsheet.
	 *
	 * @param array $form An array form.
	 * @param array $val An array containing the form fields.
	 * @param array $entry Gravity Form Entry.
	 * @param array $feed Feed Array.
	 *
	 * @return array.
	 */
	public function wpsslgf_prepared_values( $form, $val, $entry, $feed ) {

		$last_key        = '';
		$countval        = count( $val );
		$wpsslgf_form_data = GFAPI::get_form( $form['id'] );
		$form_field_type = self::$instance->wpsslgf_form_field_type( $form );
		$values_data     = array();
		
		for ( $k = 0; $k < $countval; $k++ ) {

			if ( ! isset( $val[ $k ] ) ) {
				$values_data[ $k ] = '';
				continue;
			}
			if ( 'Form ID' === (string) $val[ $k ] && isset( $feed['wpssg_form_id'] ) && $feed['wpssg_form_id'] ) {
				$values_data[ $k ] = isset( $form['id'] ) ? $form['id'] : '';
				continue;
			}
			if ( 'Form Title' === (string) $val[ $k ] && isset( $feed['wpssg_form_title'] ) && $feed['wpssg_form_title'] ) {
				$values_data[ $k ] = isset( $wpsslgf_form_data['title'] ) ? $wpsslgf_form_data['title'] : '';
				continue;
			}
			if ( 'Submission Date' === (string) $val[ $k ] && isset( $feed['submissiondate'] ) && $feed['submissiondate'] ) {
				if ( isset( $feed['submissiondate'] ) && ! empty( $feed['submissiondate'] ) ) {
					$default_format = self::$instance_api->wpsslgf_option( 'date_format' ) . ' ' . self::$instance_api->wpsslgf_option( 'time_format' );
					$date_val          = GFCommon::format_date( $entry['date_created'] , false, $default_format ,false);
					$values_data[ $k ] = $date_val;
					
				} else {
					$values_data[ $k ] = '';
				}
				continue;
			}

			$num        = $val[ $k ];
			$is_explode = 0;
			if ( false !== strpos( $num, '.' ) ) {
				$fid        = explode( '.', $num );
				$num        = $fid[0];
				$is_explode = 1;
			}

			if (  ! empty( $num ) && array_key_exists( $num, $form_field_type ) ) {
				if ( 'checkbox' === (string) $form_field_type[ $num ] && 0 === (int) $is_explode ) {
					$field          = GFFormsModel::get_field( $form, $num );
					$checkbox_value = '';
					foreach ( $field['inputs'] as $choice ) {
						if ( ! empty( $entry[ $choice['id'] ] ) ) {
							$checkbox_value .= $entry[ $choice['id'] ] . ',';
						}
					}
					$values_data[ $k ] = rtrim( $checkbox_value, ',' );
					continue;
				} elseif ( 'list' === (string) $form_field_type[ $num ] ) {
					if ( '' === (string) $entry[ $num ] || null === $entry[ $num ] ) {
						$values_data[ $k ] = '';
					} else {
						$last_key         = $num;
						$wpsslgf_form_field = GFFormsModel::get_field( $wpsslgf_form_data, $num );
						$is_enablecolumns = isset( $wpsslgf_form_field['enableColumns'] ) ? $wpsslgf_form_field['enableColumns'] : 0;
						// phpcs:ignore
						$list_array = array();
						$list_array = maybe_unserialize( $entry[ $num ] );
						if ( isset( $list_array[0] ) && ! empty( $list_array[0] ) && 1 === (int) $is_enablecolumns ) {
							if ( 0 === (int) $is_explode ) {
								$list_row_array = array();
								foreach ( $list_array as $list_rows ) {
									$list_row_array[] = implode( ',', $list_rows );
								}
								$list_row          = implode( '|', $list_row_array );
								$values_data[ $k ] = $list_row;
							} else {
								$listkeys       = array_keys( $list_array[0] );
								$list_row_array = array();

								$list_columns = array();
								foreach ( $wpsslgf_form_field['choices'] as $choice ) {
									$list_columns[] = isset( $choice['value'] ) ? $choice['value'] : '';
								}

								foreach ( $list_columns as $ldata ) {
									$list_row_array[] = in_array( $ldata, $listkeys, true ) ? implode( ',', array_column( $list_array, $ldata ) ) : '';
								}
								if ( false !== strpos( $val[ $k ], '.' ) ) {
									$list_field_id     = explode( '.', $val[ $k ] );
									$values_data[ $k ] = isset( $list_row_array[ $list_field_id[1] ] ) ? $list_row_array[ $list_field_id[1] ] : '';
								}
							}
						} elseif ( ! empty( $list_array ) ) {
							$values_data[ $k ] = implode( ',', $list_array );
						} else {
							$values_data[ $k ] = '';
						}
					}
					continue;
				} elseif ( 'name' === (string) $form_field_type[ $num ] && 0 === (int) $is_explode ) {
					$values_data[ $k ] = $this->get_full_name( $entry, $num );
					continue;
				} elseif ( 'address' === (string) $form_field_type[ $num ] && 0 === (int) $is_explode ) {
					$address_arr = array();
					$address     = '';
					foreach ( $entry as $field_key => $field_val ) {
						if ( 1 === strpos( '"' . $field_key, $num . '.' ) ) {
							$address_arr[ $field_key ] = $field_val;
						}
					}
					$address_arr = array_filter( $address_arr );
					ksort( $address_arr );
					$address           = implode( ',', $address_arr );
					$values_data[ $k ] = $address;
					continue;
				} elseif ( 'fileupload' === (string) $form_field_type[ $num ] && 0 === (int) $is_explode ) {
					if ( isset( $entry[ $val[ $k ] ] ) && ( ! empty( $entry[ $val[ $k ] ] ) ) ) {
						$wpsslgf_url     = json_decode( $entry[ $val[ $k ] ] );
						$wpsslgf_url_val = '';
						if ( null === $wpsslgf_url ) { // check if it was invalid json string.
							$wpsslgf_url_val = $entry[ $val[ $k ] ];
						}

						if ( is_array( $wpsslgf_url ) ) {
							$wpsslgf_url_val = implode( ',', $wpsslgf_url );
						}
						$wpsslgf_url_val     = str_replace( array( '[', ']', '"' ), '', $wpsslgf_url_val );
						$values_data[ $k ] = $wpsslgf_url_val;
					} else {
						$values_data[ $k ] = '';
					}
					continue;
				}
			}
			if ( 'form_title' === (string) $val[ $k ] ) {
				$values_data[ $k ] = isset( $wpsslgf_form_data['title'] ) ? $wpsslgf_form_data['title'] : '';
				continue;
			} 
			if ( ! isset( $entry[ $val[ $k ] ] ) || '' === (string) $entry[ $val[ $k ] ] || null === $entry[ $val[ $k ] ] ) {
				$values_data[ $k ] = '';
				continue;
			} else {
				if ( ( isset( $form_field_type[ $num ] ) && 'date' === (string) $form_field_type[ $num ] ) || 'date_created' === (string) $val[ $k ] ) {
					if ( (bool) strtotime( $entry[ $val[ $k ] ] ) ) {
						$date_format = self::$instance_api->wpsslgf_option( 'date_format' ) . ' ' . self::$instance_api->wpsslgf_option( 'time_format' );
						
						if ( isset( $form_field_type[ $num ] ) && 'date' === (string) $form_field_type[ $num ] ) {
							$values_data[ $k ] = date( $date_format, strtotime( $entry[ $val[ $k ] ] ) );
						} else {
							$values_data[ $k ] = GFCommon::format_date( $entry[ $val[ $k ] ] , false, $date_format ,false );
						}
					}else{
						$values_data[ $k ] = $entry[ $val[ $k ] ];
					}
				} elseif ( isset( $form_field_type[ $val[ $k ] ] ) && 'multiselect' === (string) $form_field_type[ $val[ $k ] ] ) {
					$wpsslgf_multiselect     = json_decode( $entry[ $val[ $k ] ] );
					$wpsslgf_multiselect_val = '';
					if ( null === $wpsslgf_multiselect ) { // check if it was invalid json string.
						$wpsslgf_multiselect_val = $entry[ $val[ $k ] ];
					}
					if ( is_array( $wpsslgf_multiselect ) ) {
						$wpsslgf_multiselect_val = implode( ',', $wpsslgf_multiselect );
					}
					$values_data[ $k ] = str_replace( array( '[', ']', '"', ',' ), array( '', '', '', ', ' ), self::$instance->wpsslgf_convert_to_readable( $wpsslgf_multiselect_val ) );
				} else {
					$values_data[ $k ] = self::$instance->wpsslgf_convert_to_readable( $entry[ $val[ $k ] ] );
				}
			}
		}
		return $values_data;
	}
	/**
	 * Convert to Integer.
	 *
	 * @param string $data Entry ids array.
	 */
	public function wpsslgf_convert_int( $data ) {
		$data = array_map(
			function( $element ) {
				return ( is_numeric( $element ) ? (int) $element : $element );
			},
			$data
		);
		return $data;
	}

	/**
	 * Convert to String.
	 *
	 * @param string $data Entry ids array.
	 */
	public function wpsslgf_convert_string( $data ) {
		$data = array_map(
			function( $element ) {
				return ( is_string( $element ) ? (string) $element : $element );
			},
			$data
		);
		return $data;
	}
	/**
	 * Check spreadsheet and sheet exist or not.
	 *
	 * @param string $spreadsheetid Spreadsheet ID.
	 * @param string $sheetname Sheet Name.
	 */
	public function wpsslgf_check_sheet_exist( $spreadsheetid, $sheetname ) {
		$spreadsheets          = self::$instance->wpsslgf_list_googlespreedsheet();
		$spreadsheet_list      = array_column( $spreadsheets, 'value' );
		$data                  = array();
		$data['spreadsheetid'] = '';
		$data['sheetname']     = '';
		if ( '' !== $spreadsheetid && 'new' !== $spreadsheetid && in_array( $spreadsheetid, $spreadsheet_list, true ) ) {
			$data['spreadsheetid'] = $spreadsheetid;
			$response              = self::$instance_api->get_sheet_listing( $spreadsheetid );
			foreach ( $response->getSheets() as $s ) {
				$sheets[] = $s['properties']['title'];
			}
			if ( ! empty( $sheetname ) && in_array( $sheetname, $sheets, true ) ) {
				$data['sheetname'] = $sheetname;
			}
		}
		return $data;
	}

	/**
	 * Clear the Spreadsheet.
	 */
	public function wpsslgf_clear_spreadsheet() {
		
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'save_general_settings' ) ) {
			echo esc_html__( 'Sorry, your nonce did not verify.', 'wpssg' );
			wp_die();
		}
		if ( isset( $_POST['form_id'] ) ) {
			$form_id = (int) sanitize_text_field( wp_unslash( $_POST['form_id'] ) );
		}
		if ( isset( $_POST['fid'] ) ) {
			$wpsslgf_fid = (int) sanitize_text_field( wp_unslash( $_POST['fid'] ) );
		}

		$syncformfeed    = RGFormsModel::get_form_meta( $form_id );
		$formfeed        = self::$instance->get_feeds( $form_id );
		$form_field_type = self::$instance->wpsslgf_form_field_type( $syncformfeed );

		foreach ( $formfeed as $feedsetting ) {
			if ( (int) $feedsetting['id'] === $wpsslgf_fid ) {
				$settings = $feedsetting['meta'];
			}
		}

		$spreadsheetid = $settings['spreedsheets'];
		$sheetname     = $settings['sheetselect'];
		$total         = self::$instance_api->get_row_list( $spreadsheetid, $sheetname );
		if($total->getValues() && !is_null($total->getValues())) {
			$total_headers = count( $total['values'][0] );
			$last_column   = self::$instance->wpsslgf_get_column_index( $total_headers );
			try {
				$range                  = $sheetname . '!A2:' . $last_column . '100000';
				$requestbody            = self::$instance_api->clearobject();
				$param                  = array();
				$param['spreadsheetid'] = $spreadsheetid;
				$param['sheetname']     = $range;
				$param['requestbody']   = $requestbody;
				$response               = self::$instance_api->clear( $param );
			} catch ( Exception $e ) {
				$allowed_html = wp_kses_allowed_html( 'post' );
				echo wp_kses( 'Message : ' . $e->getMessage(), $allowed_html );
			}
		}

		echo esc_html( 'successful' );
		wp_die();
	}
	/**
	 * Return Character based on gven number
	 *
	 * @param int $number Number.
	 */
	public static function wpsslgf_get_column_index( $number ) {

		if ( $number <= 0 ) {
			return null;
		}

		$temp;
		$letter = '';
		while ( $number > 0 ) {
			$temp   = ( $number - 1 ) % 26;
			$letter = chr( $temp + 65 ) . $letter;
			$number = ( $number - $temp - 1 ) / 26;
		}
		return $letter;
	}
}
