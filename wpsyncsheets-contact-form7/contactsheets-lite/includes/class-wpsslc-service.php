<?php
/**
 * WPSSLC_Service Class
 *
 * @package contactsheets-lite
 */

/**
 * WPSSLC_Google_API class.
 *
 * @since 1.0.0
 */
class WPSSLC_Service {
	/**
	 * Instance of class.
	 *
	 * @var $instance Instance variable of class.
	 */
	private static $instance = null;
	/**
	 * Instance of WPSSLC_Google_API_Functions class.
	 *
	 * @var $instance_api Instance variable of WPSSLC_Google_API_Functions class.
	 */
	private static $instance_api = null;
	/**
	 * Instance of Google API service.
	 *
	 * @var $instance_service Instance variable of Google API service.
	 */
	private static $instance_service = null;
	/**
	 * Allowed Tags.
	 *
	 * @var $allowed_tags.
	 */
	private $allowed_tags = array( 'text', 'email', 'url', 'file', 'tel', 'number', 'range', 'date', 'textarea', 'select', 'checkbox', 'radio', 'acceptance', 'quiz' );
	/**
	 * Get an instance of this class.
	 *
	 * @return instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new WPSSLC_Service();
		}
		return self::$instance;
	}
	/**
	 * Get an instance of WPSSLC_Google_API_Functions class.
	 *
	 * @return WPSSLC_Google_API_Functions class instance
	 */
	public static function get_clientinstance() {
		if ( null === self::$instance_api ) {
			self::$instance_api = new ContactsheetsLite\WPSSLC_Google_API_Functions();
		}
		return self::$instance_api;
	}
	/**
	 *  Set things up.
	 *
	 *  @since 1.0
	 */
	public function __construct() {

		// Add new tab to contact form 7 editors panel.
		add_filter( 'wpcf7_editor_panels', array( $this, 'wpsslc_editor_panels' ) );
		add_action( 'wpcf7_after_save', array( $this, 'wpsslc_save_settings' ) );
		add_action( 'wpcf7_before_send_mail', array( $this, 'wpsslc_wpcf7_before_send_mail' ), 10 );
		add_action( 'wpcf7_before_send_mail', array( $this, 'wpsslc_save_to_google_spreadsheets' ), 50, 1 );
		add_action( 'wp_ajax_wpsslc_reset_settings', array( $this, 'wpsslc_reset_settings' ) );
		add_action( 'wp_ajax_wpsslc_clear_sheet', array( $this, 'wpsslc_clear_sheet' ) );
		$this->get_clientinstance();
	}

	/**
	 * Add new tab to contact form 7 editors panel.
	 *
	 * @since 1.0
	 *
	 * @param array $panels .
	 * @return array
	 */
	public function wpsslc_editor_panels( $panels ) {
		$panels['google_sheets'] = array(
			'title'    => __( 'WPSyncSheets Lite Settings', 'wpssc' ),
			'callback' => array( $this, 'wpsslc_editor_panel_google_sheet' ),
		);

		return $panels;
	}

	/**
	 * Set Google sheet settings with contact form
	 *
	 * @since 1.0
	 *
	 * @param object $post The post object to be processed.
	 */
	public function wpsslc_save_settings( $post ) {

		if ( ! isset( $_POST['wpsslc_sheet_settings'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpsslc_sheet_settings'] ) ), 'save_sheet_settings' ) ) {
			return;
		}
		if ( ! isset( $_POST['wpsslc_all_settings'] ) ) {
			update_post_meta( $post->id(), 'wpssc_all_settings', 'no' );
			return;
		} else {
			update_post_meta( $post->id(), 'wpssc_all_settings', sanitize_text_field( wp_unslash( $_POST['wpsslc_all_settings'] ) ) );
		}
		$post_data       = self::get_form_data( $post->id() );
		$form_fields     = self::single_array( $post_data );
		$wpsslc_cf_sheet = array();
		$active_header   = array();
		$newsheet        = '';

		array_push( $form_fields, 'IP-address' );
		array_push( $form_fields, 'page-URL' );
		array_push( $form_fields, 'submission-date');
		array_push( $form_fields, 'submission-time');

		if ( 'new' === sanitize_text_field( wp_unslash( $_POST['spreadsheetselection'] ) ) ) {
			$newsheet                           = 'new';
			$wpsslc_cf_sheet['spreadsheetname'] = isset( $_POST['new_spreadsheetname'] ) ? sanitize_text_field( wp_unslash( $_POST['new_spreadsheetname'] ) ) : '';
			$wpsslc_cf_sheet['sheetname']       = isset( $_POST['new_sheetname'] ) ? sanitize_text_field( wp_unslash( $_POST['new_sheetname'] ) ) : '';

			$requestbody = self::$instance_api->newspreadsheetobject( $wpsslc_cf_sheet['spreadsheetname'], $wpsslc_cf_sheet['sheetname'] );

			$response                           = self::$instance_api->createspreadsheet( $requestbody );
			$spreadsheetname                    = $response['spreadsheetId'];
			$wpsslc_cf_sheet['spreadsheetname'] = $response['spreadsheetId'];
			$_POST['wpsslc']['spreadsheetname'] = $response['spreadsheetId'];
			$_POST['wpsslc']['sheetname']       = $wpsslc_cf_sheet['sheetname'];
		}

		$mapping                            = array();
		$wpsslc_cf_sheet['spreadsheetname'] = isset( $_POST['wpsslc']['spreadsheetname'] ) ? sanitize_text_field( wp_unslash( $_POST['wpsslc']['spreadsheetname'] ) ) : '';
	
		if ( isset( $_POST['header_value_0'] ) ) {

			$spreadsheetname = $wpsslc_cf_sheet['spreadsheetname'];
			$sheetname       = $_POST['new_sheetname'];
			$wpsslc_existingsheets = array();
			$response              = self::$instance_api->get_sheet_listing( $spreadsheetname );
			$wpsslc_existingsheets = self::$instance_api->get_sheet_list( $response );
			$range                 = trim( $sheetname ) . '!A1';
			//$wpsslc_sheetid        = $wpsslc_existingsheets[ $sheetname ];
			$wpsslc_sheetid = $wpsslc_existingsheets[$sheetname] ?? null;

			// Create new sheet within the existing Google Spreadsheet
			if ( ! array_key_exists( $_POST['new_sheetname'], $wpsslc_existingsheets ) ) {
				$wpsslc_cf_sheet['sheetname'] = $_POST['new_sheetname'];
				$wpsslc_sheetname = $_POST['new_sheetname'];
				$param                  = array();
				$param['spreadsheetid'] = $wpsslc_cf_sheet['spreadsheetname'];
				$param['sheetname']     = $_POST['new_sheetname'];
				$wpsslwp_response       = self::$instance_api->newsheetobject( $param );
				$wpsslwp_range          = trim( $_POST['new_sheetname'] ) . '!A1';
			}
			else {
				$wpsslc_cf_sheet['sheetname'] = isset( $_POST['new_sheetname'] ) ? sanitize_text_field( wp_unslash( $_POST['new_sheetname'] ) ) : '';
			}

			$headers       = array();
			$active_header = array();

			$previous_active_headers     = self::$instance_api->wpsslc_option( 'wpssc_active_headers', '', $post->id() );
			$wpsslc_neworder             = array();
			$wpsslc_total_mapping_fields = isset( $_POST['wpsslc_total_mapping_fields'] ) ? sanitize_text_field( wp_unslash( $_POST['wpsslc_total_mapping_fields'] ) ) : count( $form_fields );
			for ( $i = 0; $i < $wpsslc_total_mapping_fields; $i++ ) {
				if ( isset( $_POST[ 'header_value_' . $i ] ) ) {
					$header_shortcode = isset( $_POST[ 'header_shortcode_' . $i ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'header_shortcode_' . $i ] ) ) : '';
					if ( 'IP Address' === $header_shortcode || 'Page URL' === $header_shortcode ) {
						$header_shortcode = strtolower( str_replace( ' ', '-', $header_shortcode ) );
					}

					if ( 'Submission Date' === $header_shortcode || 'Submission Time' === $header_shortcode ) {
						$header_shortcode = strtolower( str_replace( ' ', '-', $header_shortcode ) );
					}

					if ( isset( $_POST[ 'active_header_value_' . $i ] ) ) {
						$wpsslc_neworder[]                  = $header_shortcode;
						$headers[]                          = sanitize_text_field( wp_unslash( $_POST[ 'header_value_' . $i ] ) );
						$active_header[ $header_shortcode ] = 1;
					}
					$mapping[ $header_shortcode ] = sanitize_text_field( wp_unslash( $_POST[ 'header_value_' . $i ] ) );
				}
			}

			if ( isset( $previous_active_headers[0] ) ) {
				$wpsslc_old_header_order = array_keys( $previous_active_headers[0] );
			} else {
				$wpsslc_old_header_order = array();
			}

			$wpsslc_column = array_diff( $wpsslc_old_header_order, $wpsslc_neworder );

			if ( ! empty( $wpsslc_column ) ) {
				$wpsslc_column = array_reverse( $wpsslc_column, true );
				foreach ( $wpsslc_column as $columnindex => $columnval ) {
					unset( $wpsslc_old_header_order[ $columnindex ] );
					$wpsslc_old_header_order = array_values( $wpsslc_old_header_order );
					if ( array_key_exists( $sheetname, $wpsslc_existingsheets ) ) {

						$param                = array();
						$param['sheetid']     = $wpsslc_sheetid;
						$param['startindex']  = $columnindex;
						$param['endindex']    = $columnindex + 1;
						$deleterequestarray[] = self::$instance_api->deleteDimensionrequests( $param );
					}
				}
			}
			try {
				if ( ! empty( $deleterequestarray ) ) {
					$param                  = array();
					$param['spreadsheetid'] = $spreadsheetname;
					$param['requestarray']  = $deleterequestarray;

					$wpsslc_response = self::$instance_api->updatebachrequests( $param );
				}
			} catch ( Exception $e ) {
				echo esc_html( 'Message: ' . $e->getMessage() );
			}
			if ( $wpsslc_old_header_order !== $wpsslc_neworder ) {
				foreach ( $wpsslc_neworder as $key => $hname ) {
					$wpsslc_startindex = array_search( $hname, $wpsslc_old_header_order, true );

					if ( false !== $wpsslc_startindex && ( isset( $wpsslc_old_header_order[ $key ] ) && $wpsslc_old_header_order[ $key ] !== $hname ) ) {
						unset( $wpsslc_old_header_order[ $wpsslc_startindex ] );
						$wpsslc_old_header_order = array_merge( array_slice( $wpsslc_old_header_order, 0, $key ), array( 0 => $hname ), array_slice( $wpsslc_old_header_order, $key, count( $wpsslc_old_header_order ) - $key ) );
						$wpsslc_endindex         = $wpsslc_startindex + 1;
						$wpsslc_destindex        = $key;

						if ( array_key_exists( $sheetname, $wpsslc_existingsheets ) ) {

							$param               = array();
							$param['sheetid']    = $wpsslc_sheetid;
							$param['startindex'] = $wpsslc_startindex;
							$param['endindex']   = $wpsslc_endindex;
							$param['destindex']  = $wpsslc_destindex;

							$requestarray[] = self::$instance_api->moveDimensionrequests( $param );
						}
					} elseif ( false === $wpsslc_startindex ) {

						$wpsslc_old_header_order = array_merge( array_slice( $wpsslc_old_header_order, 0, $key ), array( 0 => $hname ), array_slice( $wpsslc_old_header_order, $key, count( $wpsslc_old_header_order ) - $key ) );

						if ( array_key_exists( $sheetname, $wpsslc_existingsheets ) ) {
							$param               = array();
							$param['sheetid']    = $wpsslc_sheetid;
							$param['startindex'] = $key;
							$param['endindex']   = $key + 1;
							$requestarray[]      = self::$instance_api->insertdimensionrequests( $param );
						}
					}
				}

				if ( ! empty( $requestarray ) ) {
					$param                  = array();
					$param['spreadsheetid'] = $spreadsheetname;
					$param['requestarray']  = $requestarray;
					$wpsslc_response        = self::$instance_api->updatebachrequests( $param );
				}
			}

			$range       = trim( $sheetname ) . '!A1';
			$values      = array( $headers );
			$requestbody = self::$instance_api->valuerangeobject( $values );
			$params      = array(
				'valueInputOption' => 'RAW',
			);

			$param    = self::$instance_api->setparamater( $spreadsheetname, $range, $requestbody, $params );
			$response = self::$instance_api->updateentry( $param );

		}
		update_post_meta( $post->id(), 'wpssc_cf_settings', $wpsslc_cf_sheet );
		update_post_meta( $post->id(), 'field_mapping_settings', $mapping );
		update_post_meta( $post->id(), 'wpssc_active_headers', $active_header );

		if ( isset( $_POST['freeze_header'] ) ) {
			$freeze = 1;
		} else {
			$freeze = 0;
		}

		if ( ! empty( $wpsslc_cf_sheet['spreadsheetname'] ) && ! empty( $wpsslc_cf_sheet['spreadsheetname'] ) ) {
			$response = self::$instance_api->get_sheet_listing( $wpsslc_cf_sheet['spreadsheetname'] );

			foreach ( $response->getSheets() as $key => $value ) {
				if ( $wpsslc_cf_sheet['sheetname'] === (string) $value['properties']['title'] ) {
					$requestbody                    = self::$instance_api->freezeobject( $value['properties']['sheetId'], $freeze );
					$requestobject                  = array();
					$requestobject['spreadsheetid'] = $wpsslc_cf_sheet['spreadsheetname'];
					$requestobject['requestbody']   = $requestbody;
					self::$instance_api->formatsheet( $requestobject );
				}
			}
		}
		if ( isset( $_POST['freeze_header'] ) ) {
			update_post_meta( $post->id(), 'freeze_header', sanitize_text_field( wp_unslash( $_POST['freeze_header'] ) ) );
		} else {
			update_post_meta( $post->id(), 'freeze_header', '' );
		}

	}
	/**
	 * Function - Run before sending mail.
	 *
	 * @since 1.0
	 */
	public function wpsslc_wpcf7_before_send_mail() {
		self::$instance_api->getClient();
	}
	/**
	 * Function - To send contact form data to google spreadsheet.
	 *
	 * @param object $form .
	 * @since 1.0
	 */
	public function wpsslc_save_to_google_spreadsheets( $form ) {
		// get form data.
		$form_id = $form->id();
		if ( 'no' === (string) get_post_meta( $form_id, 'wpssc_all_settings', true ) ) {
			return;
		}

		if ( ! self::$instance_api->checkcredenatials() ) {
			return;
		}
		if ( ! self::$instance_api->getClient() ) {
			return;
		}
		$submission = WPCF7_Submission::get_instance();

		$form_data      = self::$instance_api->wpsslc_option( 'wpssc_cf_settings', '', $form_id );
		$mapping_data   = get_post_meta( $form_id, 'field_mapping_settings' );
		$active_headers = self::$instance_api->wpsslc_option( 'wpssc_active_headers', '', $form_id );
		$data           = array();
		self::check_mapping_fields( $mapping_data );
		self::check_active_headers( $active_headers );
		$sheetarray = self::wpsslc_list_googlespreedsheet();
		if ( isset( $form_data[0]['spreadsheetname'] ) && ! empty( $form_data[0]['spreadsheetname'] ) && ! array_key_exists( $form_data[0]['spreadsheetname'], $sheetarray ) ) {
			$form_data[0]['spreadsheetname'] = '';
			$form_data[0]['sheetname']       = '';
		} elseif ( isset( $form_data[0]['spreadsheetname'] ) && ! empty( $form_data[0]['spreadsheetname'] ) ) {
			$sheetname = self::sheet_for_form_setting( $form_data );
			if ( isset( $form_data[0]['sheetname'] ) && ! empty( $form_data[0]['sheetname'] ) && ! in_array( $form_data[0]['sheetname'], $sheetname, true ) ) {
				$form_data[0]['spreadsheetname'] = '';
				$form_data[0]['sheetname']       = '';
			}
		}
		// if contact form sheet name and tab name is not empty than send data to spreedsheet.
		if ( $submission && ( ! empty( $form_data[0]['spreadsheetname'] ) ) && ( ! empty( $form_data[0]['sheetname'] ) ) && ! empty( array_filter( $mapping_data ) ) ) {
			$posted_data = $submission->get_posted_data();
			$file_data   = $submission->uploaded_files();
			$field_data  = self::get_form_data( $form_id );

			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			global $input_quiz_array;
			$input_quiz_array = array();
			array_walk_recursive(
				$field_data,
				function( $value, $key ) {
					global $input_quiz_array;
					if ( 'quiz' === (string) $key ) {
						$input_quiz_array[] = $value;
					}
				}
			);

			foreach ( $mapping_data[0] as $key => $val ) {
				if ( $active_headers ) {
					if ( ! array_key_exists( $key, $active_headers[0] ) ) {
						continue;
					}
				}
				if ( 'IP-address' === (string) $key ) {

					if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && ! empty( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) ) ) ) {
						$data[] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
					} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && ! empty( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) {
						$data[] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
					} else {
						$data[] = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
					}
					continue;
				}
				if ( 'page-URL' === (string) $key ) {
					$data[] = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
					continue;
				}

				if ( 'submission-date' === (string) $key ) {
					$data[] = date_i18n( get_option( 'date_format' ) );
					continue;
				}
				if ( 'submission-time' === (string) $key ) {
					$data[] = date_i18n( get_option( 'time_format' ) );
					continue;
				}

				if ( in_array( $key, $input_quiz_array, true ) ) {
					// @codingStandardsIgnoreStart.
					if ( isset( $_POST[ $key ] ) ) {
						$data[] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
					// @codingStandardsIgnoreEnd.
					} else {
						$data[] = '';
					}
					continue;
				}

				if ( ! isset( $posted_data[ $key ] ) ) {
					$data[] = '';
					continue;
				}

				if ( is_array( $posted_data[ $key ] ) ) {
					$data[] .= implode( ',', $posted_data[ $key ] );
				} else {
					$field_type = self::search_forkey( $key, $field_data );
					if ( 'file' === (string) $field_type ) {
						if ( ! empty( $file_data[ $key ] ) ) {
							$data[] = basename( $file_data[ $key ][0] );
						} else {
							$data[] = '';
						}
						continue;
					}
					if ( 'acceptance' === (string) $field_type ) {
						if ( 1 === (int) $posted_data[ $key ] ) {
							$data[] = 'Yes';
						} else {
							$data[] = 'No';
						}
						continue;
					}
					$data[] = $posted_data[ $key ];
				}
			}
			

			$values      = array( $data );
			$requestbody = self::$instance_api->valuerangeobject( $values );
			$params      = array(
				'valueInputOption' => 'USER_ENTERED',
			);
			$param       = self::$instance_api->setparamater( $form_data[0]['spreadsheetname'], $form_data[0]['sheetname'], $requestbody, $params );

			$response = self::$instance_api->appendentry( $param );

		}
	}

	/**
	 * Google sheet settings page
	 *
	 * @since 1.0
	 */

	public function wpsslc_editor_panel_google_sheet() {
		?>
		<div id="informationdivwpsslc" class="postbox wpsslc-upgrade-pro-informationdiv">
			<div class="inside">
				<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../assets/images/logo.svg' ); ?>">
				<ul>
					<li><?php echo esc_html( __( 'Edit Sheet Headers', 'wpssc' ) ); ?></li>
					<li><?php echo esc_html( __( 'Sorting Sheet Headers', 'wpssc' ) ); ?></li>
					<li><?php echo esc_html( __( 'User Agent & Submission Time Sheet Headers', 'wpssc' ) ); ?></li>
					<li><?php echo esc_html( __( 'Special Mail Tags Options', 'wpssc' ) ); ?></li>
					<li><?php echo esc_html( __( '6+ Third Party Plugins Compatibility', 'wpssc' ) ); ?></li>
				</ul>
				<a class="button button-primary" target="_blank" href="<?php echo esc_url( WPSSLC_PRO_VERSION_URL ); ?>">
					<span class="dashicons dashicons-admin-links"></span>
					<?php echo esc_html( __( 'Upgrade To Pro', 'wpssc' ) ); ?>
				</a>
			</div>
		</div>
		<?php
		if ( ! current_user_can( 'edit_wpsyncsheets_contact_form_7_lite_form_settings' ) ) {
			echo '<p>' . esc_html__( 'You do not have permission to access this page.', 'wpssc' ) . '</p>';
		} else {
			// @codingStandardsIgnoreStart.
			if ( isset( $_GET['post'] ) ) {

				$form_id = sanitize_text_field( wp_unslash( $_GET['post'] ) );
				// @codingStandardsIgnoreEnd.
				$form_data                    = self::$instance_api->wpsslc_option( 'wpssc_cf_settings', '', $form_id );
				$wpsslc_google_settings_value = self::$instance_api->wpsslc_option( 'wpssc_google_settings' );

				$wpsslc_error = '';

				if ( ! empty( $wpsslc_google_settings_value[2] ) ) {
					if ( ! self::$instance_api->getClient() ) {
						$wpsslc_error = self::$instance_api->getClient( 1 );
						if ( 'Invalid token format' === (string) $wpsslc_error ) {

							$wpsslc_error = '<strong class="err-msg">' . esc_html__( 'Error: Invalid Token - ', 'wpssc' ) . '<a href="' . esc_url( admin_url( 'admin.php?page=wpsyncsheets-contact-form-7' ) ) . '">' . esc_html__( 'Click here</a> to check more information.', 'wpssc' ) . '';

						} else {

							$wpsslc_error = '<strong class="err-msg">Error: ' . $wpsslc_error . '</strong>';

						}
					}
				} else {
					echo '<strong class="err-msg">' . esc_html__( 'Please genearate authentication code from', 'wpssc' ) . '</strong>';
					?>
					<?php echo esc_html__( 'Google API Settings', 'wpssc' ); ?><strong>
						<a href='<?php echo esc_url( 'admin.php?page=wpsyncsheets-contact-form-7' ); ?>'> <?php echo esc_html__( 'Click here', 'wpssc' ); ?></a></strong>
					<?php
					return;
				}
				if ( ! empty( $wpsslc_error ) ) {
					$allowed_html = wp_kses_allowed_html( 'post' );
					echo wp_kses( $wpsslc_error, $allowed_html );
					return;
				}

				$sheetarray             = self::wpsslc_list_googlespreedsheet();
				$wpsslc_spreadsheetname = '';
				$wpsslc_sheetname       = '';
				if ( isset( $form_data[0] ) && isset( $form_data[0]['spreadsheetname'] ) ) {
					$wpsslc_spreadsheetname = $form_data[0]['spreadsheetname'];
				}
				if ( isset( $form_data[0] ) && isset( $form_data[0]['sheetname'] ) ) {
					$wpsslc_sheetname = $form_data[0]['sheetname'];
				}
				if ( ! empty( $wpsslc_spreadsheetname ) && ! array_key_exists( $wpsslc_spreadsheetname, $sheetarray ) ) {
					$wpsslc_spreadsheetname = '';
					$wpsslc_sheetname       = '';
				}
				$sheetname = array();
				if ( 'new' !== (string) $wpsslc_spreadsheetname && '' !== (string) $wpsslc_spreadsheetname ) {
					$sheetname = self::sheet_for_form_setting( $form_data );
				}
				if ( isset( $sheetname ) ) {
					if ( in_array( $wpsslc_sheetname, $sheetname, true ) ) {
						$checkheaders = self::wpsslc_check_spreadsheet_header_value( $form_id );
					} else {
						$wpsslc_sheetname = '';
					}
				}
				?>
				<form method="post" id="googlesetting">
					<?php wp_nonce_field( 'save_sheet_settings', 'wpsslc_sheet_settings' ); ?>
					<div class="gs-fields">

						<div class="wpsslc_all_settings">
							<div class="generalSetting-left">
								<div class="contactforms-panel-field">
									<label><?php echo esc_html( __( 'WPSyncSheets Settings', 'wpssc' ) ); ?></label>
								</div>
								<p><?php echo esc_html__( 'Enable this option to automatically create customized spreadsheets and sheets for efficient entry management & seamless functionality.', 'wpssc' ); ?></p>
							</div>
							<div class="generalSetting-right">
							<label>
								<input type="checkbox" name="wpsslc_all_settings" id="wpsslc_all_settings" value="yes" 
								<?php
								$wpsslc_all_settings = get_post_meta( $form_id, 'wpssc_all_settings', true );
								if ( 'yes' === (string) $wpsslc_all_settings ) {
									echo 'checked=checked';
								}
								?>
								><span class="checkbox-switch"></span>  							
							</label>
						    </div>
						</div>

						<div class="all_settings_div">
								<div class="generalSetting-section googleSpreadsheet-section entry_spreadsheet_row">
									<div class="generalSetting-left">
										<div class="google-settings-panel">
											<div class="contactforms-panel-field">
												<label><?php echo esc_html( __( 'Select Google Spreadsheet', 'wpssc' ) ); ?></label>
											</div>
											<p><?php echo esc_html__( 'Your chosen Google Spreadsheet automatically generates a sheet with customized headers based on the below-mentioned settings. Whenever a new entry is placed, WPSyncSheets creates a new row to accommodate it with the spreadsheet.', 'wpsswp' ); ?></p>
											
											<div class="createanew-radio">
												<div class="createanew-radio-box">
													<input type="radio" name="spreadsheetselection" value="new" id="createanew">
													<label for="createanew">Create New Spreadsheet</label>
												</div>
												<div class="createanew-radio-box">
													<input type="radio" name="spreadsheetselection" value="existing" id="existing" checked="checked">
													<label for="existing">Select Existing Spreadsheet</label>
												</div>
											</div>

											<select name="wpsslc[spreadsheetname]" id="spreadsheetid" >
												<?php
												if ( ! empty( $sheetarray ) ) {
													foreach ( $sheetarray as $key => $val ) {
														if ( isset( $wpsslc_spreadsheetname ) && $wpsslc_spreadsheetname === $key ) {
															?>
															<option value="<?php echo esc_attr( $key ); ?>" selected><?php echo esc_html( $val ); ?></option>
															<?php
														} else {
															?>
															<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $val ); ?></option>
															<?php
														}
													}
												}
												?>
											</select>

											<?php if ( isset( $wpsslc_spreadsheetname ) && ! empty( $wpsslc_spreadsheetname ) ) { ?>
												<span class="sheet-btns"> 
													<a id="view_spreadsheet" target="_blank" href="<?php echo esc_url( 'https://docs.google.com/spreadsheets/d/' . $wpsslc_spreadsheetname ); ?>" class="wpss-button wpss-tooltio-link view_spreadsheet">
													<svg width="19" height="14" viewBox="0 0 19 14" fill="none" xmlns="http://www.w3.org/2000/svg"> 
														<path d="M9.49967 10.8334C10.5413 10.8334 11.4268 10.4688 12.1559 9.73962C12.8851 9.01046 13.2497 8.12504 13.2497 7.08337C13.2497 6.04171 12.8851 5.15629 12.1559 4.42712C11.4268 3.69796 10.5413 3.33337 9.49967 3.33337C8.45801 3.33337 7.57259 3.69796 6.84342 4.42712C6.11426 5.15629 5.74967 6.04171 5.74967 7.08337C5.74967 8.12504 6.11426 9.01046 6.84342 9.73962C7.57259 10.4688 8.45801 10.8334 9.49967 10.8334ZM9.49967 9.33337C8.87467 9.33337 8.34343 9.11462 7.90593 8.67712C7.46843 8.23962 7.24967 7.70837 7.24967 7.08337C7.24967 6.45837 7.46843 5.92712 7.90593 5.48962C8.34343 5.05212 8.87467 4.83337 9.49967 4.83337C10.1247 4.83337 10.6559 5.05212 11.0934 5.48962C11.5309 5.92712 11.7497 6.45837 11.7497 7.08337C11.7497 7.70837 11.5309 8.23962 11.0934 8.67712C10.6559 9.11462 10.1247 9.33337 9.49967 9.33337ZM9.49967 13.3334C7.4719 13.3334 5.62467 12.7674 3.95801 11.6355C2.29134 10.5035 1.08301 8.98615 0.333008 7.08337C1.08301 5.1806 2.29134 3.66324 3.95801 2.53129C5.62467 1.39935 7.4719 0.833374 9.49967 0.833374C11.5275 0.833374 13.3747 1.39935 15.0413 2.53129C16.708 3.66324 17.9163 5.1806 18.6663 7.08337C17.9163 8.98615 16.708 10.5035 15.0413 11.6355C13.3747 12.7674 11.5275 13.3334 9.49967 13.3334ZM9.49967 11.6667C11.0691 11.6667 12.5101 11.2535 13.8226 10.4271C15.1351 9.60073 16.1386 8.48615 16.833 7.08337C16.1386 5.6806 15.1351 4.56601 13.8226 3.73962C12.5101 2.91323 11.0691 2.50004 9.49967 2.50004C7.93023 2.50004 6.48926 2.91323 5.17676 3.73962C3.86426 4.56601 2.86079 5.6806 2.16634 7.08337C2.86079 8.48615 3.86426 9.60073 5.17676 10.4271C6.48926 11.2535 7.93023 11.6667 9.49967 11.6667Z" fill="#383E46"></path> </svg>
													<span class="tooltip-text">View Spreadsheet</span>
													</a>
													
													<a id="clear_spreadsheet" data-form-id="<?php echo esc_attr( $form_id ); ?>" href="javascript:void(0)" class="wpss-button wpss-tooltio-link wpsyncsheets-contactforms-clear-button" data-form-id="47359">
														<svg width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg"> 
														<path d="M8.66674 8.66671H10.3334V2.83337C10.3334 2.59726 10.2535 2.39935 10.0938 2.23962C9.9341 2.0799 9.73619 2.00004 9.50008 2.00004C9.26397 2.00004 9.06605 2.0799 8.90633 2.23962C8.7466 2.39935 8.66674 2.59726 8.66674 2.83337V8.66671ZM3.66674 12H15.3334V10.3334H3.66674V12ZM2.45841 17H4.50008V15.3334C4.50008 15.0973 4.57994 14.8993 4.73966 14.7396C4.89938 14.5799 5.0973 14.5 5.33341 14.5C5.56952 14.5 5.76744 14.5799 5.92716 14.7396C6.08688 14.8993 6.16674 15.0973 6.16674 15.3334V17H8.66674V15.3334C8.66674 15.0973 8.7466 14.8993 8.90633 14.7396C9.06605 14.5799 9.26397 14.5 9.50008 14.5C9.73619 14.5 9.9341 14.5799 10.0938 14.7396C10.2535 14.8993 10.3334 15.0973 10.3334 15.3334V17H12.8334V15.3334C12.8334 15.0973 12.9133 14.8993 13.073 14.7396C13.2327 14.5799 13.4306 14.5 13.6667 14.5C13.9029 14.5 14.1008 14.5799 14.2605 14.7396C14.4202 14.8993 14.5001 15.0973 14.5001 15.3334V17H16.5417L15.7084 13.6667H3.29174L2.45841 17ZM16.5417 18.6667H2.45841C1.91674 18.6667 1.47924 18.4514 1.14591 18.0209C0.812577 17.5903 0.715354 17.1112 0.854243 16.5834L2.00008 12V10.3334C2.00008 9.87504 2.16327 9.48268 2.48966 9.15629C2.81605 8.8299 3.20841 8.66671 3.66674 8.66671H7.00008V2.83337C7.00008 2.13893 7.24313 1.54865 7.72924 1.06254C8.21535 0.57643 8.80563 0.333374 9.50008 0.333374C10.1945 0.333374 10.7848 0.57643 11.2709 1.06254C11.757 1.54865 12.0001 2.13893 12.0001 2.83337V8.66671H15.3334C15.7917 8.66671 16.1841 8.8299 16.5105 9.15629C16.8369 9.48268 17.0001 9.87504 17.0001 10.3334V12L18.1459 16.5834C18.3265 17.1112 18.2466 17.5903 17.9063 18.0209C17.566 18.4514 17.1112 18.6667 16.5417 18.6667Z" fill="#383E46"></path> </svg>
													<span class="tooltip-text">Clear Spreadsheet</span> 
													</a><img src="<?php dirname( __FILE__ ); ?>images/spinner.gif" id="clearloader">

													<a id="down_spreadsheet" target="_blank" href="<?php echo esc_url( 'https://docs.google.com/spreadsheets/d/' . $wpsslc_spreadsheetname . '/export' ); ?>" class="wpss-button wpss-tooltio-link down_spreadsheet">
														<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="none" stroke="#383E46" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 3.33c2.98 1.72 5 4.96 5 8.66 0 5.52-4.48 10-10 10 -5.53 0-10-4.48-10-10 0-3.71 2.01-6.94 5-8.67m1 8.66l4 4m0 0l4-4m-4 4v-14"></path></svg>
														<span class="tooltip-text">
															Download Spreadsheet</span>
													</a> 
												</span>
											<?php } ?>

										</div>
										<?php /*
										if ( isset( $wpsslc_spreadsheetname ) && 'new' !== (string) $wpsslc_spreadsheetname && '' !== (string) $wpsslc_spreadsheetname ) {
											?>
										<div class="custom-sheet">
											<input type="hidden" id="prev_selected_sheet" value="<?php echo esc_attr( $wpsslc_spreadsheetname ); ?>" />
												
												<select name="wpsslc[sheetname]" id="sheetname" >
												<?php
												if ( ! empty( $sheetname ) ) {
													?>
														<option value=""><?php echo esc_html( __( 'Select Sheet', 'wpssc' ) ); ?></option>
														<?php
														foreach ( $sheetname as $key => $val ) {
															if ( isset( $wpsslc_sheetname ) && $wpsslc_sheetname === $val ) {
																?>
																<option value="<?php echo esc_attr( $val ); ?>" selected><?php echo esc_html( $val ); ?></option>
																<?php
															} else {
																?>
														<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $val ); ?></option>
																<?php
															}
														}
												}
												?>
												</select>
											</div>
											<?php } */ 
											?>
											<div class="custom-new-sheet">
												<input type="text" name="new_spreadsheetname" id="new_spreadsheetname" placeholder="Enter Spreadsheet name">
											</div>
											<div class="custom-new-sheet">
												<input type="text" name="new_sheetname" id="new_sheetname" placeholder="Enter Sheet name" value="<?php echo $wpsslc_sheetname; ?>">
											</div>
									</div>
								</div>
															
								<div class="generalSetting-section sheetHeaders-section wpss_spreadsheet_row">

											<div class="mapfields">
												<h4><?php echo esc_html__( 'Sheet Headers', 'wpssc' ); ?></h4>
												<p><?php echo esc_html__( 'If you disable any sheet headers, they will be automatically removed from the current spreadsheet. You can always re-enable the headers you need, save the settings, and update the spreadsheet with the latest data. Clear the existing spreadsheet and click the "Click to Sync" button to initiate the sync process.', 'wpssc' ); ?></p>
												<?php
												// phpcs:ignore 
												$post_data = isset( $_GET['post'] ) ? self::get_form_data( $_GET['post'] ) : array(); 
												?>
											</div>
											<div class="selectall-btnset">
												<button type="button" class="wpsslc-button wpsslc-button-primary" id="selectall"><?php echo esc_html__( 'Select All', 'wpssc' ); ?></button>                
												<button type="button" class="wpsslc-button wpsslc-button-secondary" id="selectnone"><?php echo esc_html__( 'Select None', 'wpssc' ); ?></button>
											</div>
											
											<?php
												$form_fields    = self::single_array( $post_data );
												$mapping_fields = get_post_meta( $form_id, 'field_mapping_settings' );
												$active_headers = get_post_meta( $form_id, 'wpssc_active_headers' );

												$freeze       = get_post_meta( $form_id, 'freeze_header' );
												$new_headers  = array();


												if ( isset( $mapping_fields[0] ) ) {
													foreach ( $mapping_fields[0] as $key => $value ) {
														if ( 'IP-address' === (string) $key || 'page-URL' === (string) $key ) {
															continue;
														}
														if ( 'submission-date' === (string) $key || 'submission-time' === (string) $key ) {
															continue;
														}
														if ( ! in_array( $key, $form_fields, true ) ) {
															if ( isset( $active_headers[0] ) && array_key_exists( $key, $active_headers[0] ) ) {
																continue;
															}
															unset( $mapping_fields[0][ $key ] );
															continue;
														}
														if ( isset( $active_headers[0] ) && false === array_key_exists( $key, $active_headers[0] ) ) {
															$field_name                = ucwords( preg_replace( '/[^A-Za-z0-9\-]/', ' ', $key ) );
															$field_name                = ucwords( str_replace( '-', ' ', $field_name ) );
															$mapping_fields[0][ $key ] = $field_name;
														}
													}
												}

												array_push( $form_fields, 'IP-address' );
												array_push( $form_fields, 'page-URL' );
												array_push( $form_fields, 'submission-date' );
												array_push( $form_fields, 'submission-time' );

												if ( isset( $mapping_fields[0] ) && ! empty( $mapping_fields[0] ) ) {
													self::check_mapping_fields( $mapping_fields );
													if ( $active_headers ) {
														self::check_active_headers( $active_headers );
													}
													$j                    = 0;
																									

												$saved_fields = array_keys( $mapping_fields[0] );

												$newaddedfield       = array_values( array_diff( $form_fields, $saved_fields ) );
												$newaddedfield_count = count( $newaddedfield );

												for ( $i = 0; $i < $newaddedfield_count; $i++ ) {
													$field                                     = ucwords( preg_replace( '/[^A-Za-z0-9\-]/', ' ', $newaddedfield[ $i ] ) );
													$field                                     = ucwords( str_replace( '-', ' ', $field ) );
													$mapping_fields[0][ $newaddedfield[ $i ] ] = $field;
												}

												$total_mapping_fields = count( $mapping_fields[0] );
												echo '<input type="hidden" name=wpsslc_total_mapping_fields value="' . esc_html( $total_mapping_fields ) . '">';
												echo '<ul class="sheet-headers ui-sortable" id="sortable">';


													foreach ( $mapping_fields[0] as $shorcode => $header_title ) {

														$is_active = '';
														if ( $active_headers ) {
															if ( array_key_exists( $shorcode, $active_headers[0] ) ) {
																$is_active = 'checked';
															}
														} else {
															$is_active = 'checked'; }

														
											
														?>
													<li class="ui-state-default ui-sortable-handle">
														<span class="sheet-left">
																<label for="c_<?php echo esc_attr( $j ); ?>"><span class="contacttextfield"><?php echo esc_html( $header_title ); ?></span>
																
																<span class="ui-icon ui-icon-pencil wpss-tooltio-link disabled-pro-version">
																	<span class="pencil-icon">
																		<svg width="12" height="12" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
																		<path d="M1.33333 11.6668H2.26667L8.01667 5.91677L7.08333 4.98343L1.33333 10.7334V11.6668ZM10.8667 4.9501L8.03333 2.1501L8.96667 1.21677C9.22222 0.961213 9.53611 0.833435 9.90833 0.833435C10.2806 0.833435 10.5944 0.961213 10.85 1.21677L11.7833 2.1501C12.0389 2.40566 12.1722 2.71399 12.1833 3.0751C12.1944 3.43621 12.0722 3.74455 11.8167 4.0001L10.8667 4.9501ZM9.9 5.93343L2.83333 13.0001H0V10.1668L7.06667 3.1001L9.9 5.93343Z" fill="#64748B"/>
																		</svg>
																	</span>
																	<span class="tooltip-text edit-tooltip">Upgrade To Pro</span>
																</span>
																<input type="checkbox" class="wpsslc_active" id="c_<?php echo esc_attr( $j ); ?>" name="active_header_value_<?php echo esc_attr( $j ); ?>" value="1" <?php echo esc_attr( $is_active ); ?> >
															<span class="checkbox-switch-new"></span>
															<input type="hidden" class="header_shortcode" name="header_shortcode_<?php echo esc_attr( $j ); ?>" value="<?php echo esc_attr( $shorcode ); ?>"/>
															<input type="hidden" class="header_value" name="header_value_<?php echo esc_attr( $j ); ?>" value="<?php echo esc_attr( $header_title ); ?>"/>
															</label>
														</span>

														<span class="sheet-right">
															
															<span class="ui-icon ui-icon-caret-2-n-s wpss-tooltio-link disabled-pro-version">
																<svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
																<mask id="mask0_384_3228" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="17" height="16">
																<rect x="0.5" width="16" height="16" fill="#D9D9D9"/>
																</mask>
																<g mask="url(#mask0_384_3228)">
																<path d="M5.95875 13.67C5.55759 13.67 5.21418 13.5272 4.92851 13.2415C4.64284 12.9558 4.5 12.6124 4.5 12.2113C4.5 11.8101 4.64284 11.4667 4.92851 11.181C5.21418 10.8953 5.55759 10.7525 5.95875 10.7525C6.35991 10.7525 6.70332 10.8953 6.98899 11.181C7.27466 11.4667 7.4175 11.8101 7.4175 12.2113C7.4175 12.6124 7.27466 12.9558 6.98899 13.2415C6.70332 13.5272 6.35991 13.67 5.95875 13.67ZM10.335 13.67C9.93384 13.67 9.59043 13.5272 9.30476 13.2415C9.01909 12.9558 8.87625 12.6124 8.87625 12.2113C8.87625 11.8101 9.01909 11.4667 9.30476 11.181C9.59043 10.8953 9.93384 10.7525 10.335 10.7525C10.7362 10.7525 11.0796 10.8953 11.3652 11.181C11.6509 11.4667 11.7937 11.8101 11.7937 12.2113C11.7937 12.6124 11.6509 12.9558 11.3652 13.2415C11.0796 13.5272 10.7362 13.67 10.335 13.67ZM5.95875 9.29375C5.55759 9.29375 5.21418 9.15091 4.92851 8.86524C4.64284 8.57957 4.5 8.23616 4.5 7.835C4.5 7.43384 4.64284 7.09043 4.92851 6.80476C5.21418 6.51909 5.55759 6.37625 5.95875 6.37625C6.35991 6.37625 6.70332 6.51909 6.98899 6.80476C7.27466 7.09043 7.4175 7.43384 7.4175 7.835C7.4175 8.23616 7.27466 8.57957 6.98899 8.86524C6.70332 9.15091 6.35991 9.29375 5.95875 9.29375ZM10.335 9.29375C9.93384 9.29375 9.59043 9.15091 9.30476 8.86524C9.01909 8.57957 8.87625 8.23616 8.87625 7.835C8.87625 7.43384 9.01909 7.09043 9.30476 6.80476C9.59043 6.51909 9.93384 6.37625 10.335 6.37625C10.7362 6.37625 11.0796 6.51909 11.3652 6.80476C11.6509 7.09043 11.7937 7.43384 11.7937 7.835C11.7937 8.23616 11.6509 8.57957 11.3652 8.86524C11.0796 9.15091 10.7362 9.29375 10.335 9.29375ZM5.95875 4.9175C5.55759 4.9175 5.21418 4.77466 4.92851 4.48899C4.64284 4.20332 4.5 3.85991 4.5 3.45875C4.5 3.05759 4.64284 2.71418 4.92851 2.42851C5.21418 2.14284 5.55759 2 5.95875 2C6.35991 2 6.70332 2.14284 6.98899 2.42851C7.27466 2.71418 7.4175 3.05759 7.4175 3.45875C7.4175 3.85991 7.27466 4.20332 6.98899 4.48899C6.70332 4.77466 6.35991 4.9175 5.95875 4.9175ZM10.335 4.9175C9.93384 4.9175 9.59043 4.77466 9.30476 4.48899C9.01909 4.20332 8.87625 3.85991 8.87625 3.45875C8.87625 3.05759 9.01909 2.71418 9.30476 2.42851C9.59043 2.14284 9.93384 2 10.335 2C10.7362 2 11.0796 2.14284 11.3652 2.42851C11.6509 2.71418 11.7937 3.05759 11.7937 3.45875C11.7937 3.85991 11.6509 4.20332 11.3652 4.48899C11.0796 4.77466 10.7362 4.9175 10.335 4.9175Z" fill="#64748B"/>
																</g>
																</svg>
																<span class="tooltip-text">Upgrade To Pro</span>
															</span>
														</span>
														<span class="fieldsmap">
															<?php
															echo esc_html__( 'Map Field : ', 'wpssc' ) . esc_html( $shorcode );
															?>
														</span>
													</li>
												<?php $j++; } ?> </ul>
												<?php } else { ?>
												<!-- <ul class="sheet-headers"> -->
												<ul class="sheet-headers ui-sortable" id="sortable">
													<?php
													$is_active         = 'checked';
													$form_fields_count = count( $form_fields );
													for ( $i = 0; $i < $form_fields_count; $i++ ) {
														$field = ucwords( preg_replace( '/[^A-Za-z0-9\-]/', ' ', $form_fields[ $i ] ) );
														$field = ucwords( str_replace( '-', ' ', $field ) );
														?>
													<li class="ui-state-default ui-sortable-handle">
														<span class="sheet-left">
															<label for="c_<?php echo esc_attr( $i ); ?>">
															<span class="contacttextfield"><?php echo esc_html( $field ); ?></span>
														
															<span class="pencil-icon">
																<svg width="12" height="12" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
																<path d="M1.33333 11.6668H2.26667L8.01667 5.91677L7.08333 4.98343L1.33333 10.7334V11.6668ZM10.8667 4.9501L8.03333 2.1501L8.96667 1.21677C9.22222 0.961213 9.53611 0.833435 9.90833 0.833435C10.2806 0.833435 10.5944 0.961213 10.85 1.21677L11.7833 2.1501C12.0389 2.40566 12.1722 2.71399 12.1833 3.0751C12.1944 3.43621 12.0722 3.74455 11.8167 4.0001L10.8667 4.9501ZM9.9 5.93343L2.83333 13.0001H0V10.1668L7.06667 3.1001L9.9 5.93343Z" fill="#64748B"/>
																</svg>
															</span>
															<input type="checkbox" class="wpsslc_active" id="c_<?php echo esc_attr( $i ); ?>" name="active_header_value_<?php echo esc_attr( $i ); ?>" value="1" <?php echo esc_attr( $is_active ); ?> >
															<span class="checkbox-switch-new"></span>
															<input type="hidden" class="header_shortcode" name="header_shortcode_<?php echo esc_attr( $i ); ?>" value="<?php echo esc_attr( $form_fields[ $i ] ); ?>"/>
															<input type="hidden" class="header_value" name="header_value_<?php echo esc_attr( $i ); ?>" value="<?php echo esc_attr( $field ); ?>"/>
																</label>
														</span>

														<span class="sheet-right">
															
															<span class="ui-icon ui-icon-caret-2-n-s wpss-tooltio-link disabled-pro-version">
																<svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
																<mask id="mask0_384_3228" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="17" height="16">
																<rect x="0.5" width="16" height="16" fill="#D9D9D9"/>
																</mask>
																<g mask="url(#mask0_384_3228)">
																<path d="M5.95875 13.67C5.55759 13.67 5.21418 13.5272 4.92851 13.2415C4.64284 12.9558 4.5 12.6124 4.5 12.2113C4.5 11.8101 4.64284 11.4667 4.92851 11.181C5.21418 10.8953 5.55759 10.7525 5.95875 10.7525C6.35991 10.7525 6.70332 10.8953 6.98899 11.181C7.27466 11.4667 7.4175 11.8101 7.4175 12.2113C7.4175 12.6124 7.27466 12.9558 6.98899 13.2415C6.70332 13.5272 6.35991 13.67 5.95875 13.67ZM10.335 13.67C9.93384 13.67 9.59043 13.5272 9.30476 13.2415C9.01909 12.9558 8.87625 12.6124 8.87625 12.2113C8.87625 11.8101 9.01909 11.4667 9.30476 11.181C9.59043 10.8953 9.93384 10.7525 10.335 10.7525C10.7362 10.7525 11.0796 10.8953 11.3652 11.181C11.6509 11.4667 11.7937 11.8101 11.7937 12.2113C11.7937 12.6124 11.6509 12.9558 11.3652 13.2415C11.0796 13.5272 10.7362 13.67 10.335 13.67ZM5.95875 9.29375C5.55759 9.29375 5.21418 9.15091 4.92851 8.86524C4.64284 8.57957 4.5 8.23616 4.5 7.835C4.5 7.43384 4.64284 7.09043 4.92851 6.80476C5.21418 6.51909 5.55759 6.37625 5.95875 6.37625C6.35991 6.37625 6.70332 6.51909 6.98899 6.80476C7.27466 7.09043 7.4175 7.43384 7.4175 7.835C7.4175 8.23616 7.27466 8.57957 6.98899 8.86524C6.70332 9.15091 6.35991 9.29375 5.95875 9.29375ZM10.335 9.29375C9.93384 9.29375 9.59043 9.15091 9.30476 8.86524C9.01909 8.57957 8.87625 8.23616 8.87625 7.835C8.87625 7.43384 9.01909 7.09043 9.30476 6.80476C9.59043 6.51909 9.93384 6.37625 10.335 6.37625C10.7362 6.37625 11.0796 6.51909 11.3652 6.80476C11.6509 7.09043 11.7937 7.43384 11.7937 7.835C11.7937 8.23616 11.6509 8.57957 11.3652 8.86524C11.0796 9.15091 10.7362 9.29375 10.335 9.29375ZM5.95875 4.9175C5.55759 4.9175 5.21418 4.77466 4.92851 4.48899C4.64284 4.20332 4.5 3.85991 4.5 3.45875C4.5 3.05759 4.64284 2.71418 4.92851 2.42851C5.21418 2.14284 5.55759 2 5.95875 2C6.35991 2 6.70332 2.14284 6.98899 2.42851C7.27466 2.71418 7.4175 3.05759 7.4175 3.45875C7.4175 3.85991 7.27466 4.20332 6.98899 4.48899C6.70332 4.77466 6.35991 4.9175 5.95875 4.9175ZM10.335 4.9175C9.93384 4.9175 9.59043 4.77466 9.30476 4.48899C9.01909 4.20332 8.87625 3.85991 8.87625 3.45875C8.87625 3.05759 9.01909 2.71418 9.30476 2.42851C9.59043 2.14284 9.93384 2 10.335 2C10.7362 2 11.0796 2.14284 11.3652 2.42851C11.6509 2.71418 11.7937 3.05759 11.7937 3.45875C11.7937 3.85991 11.6509 4.20332 11.3652 4.48899C11.0796 4.77466 10.7362 4.9175 10.335 4.9175Z" fill="#64748B"/>
																</g>
																</svg>
																<span class="tooltip-text">Upgrade To Pro</span>
															</span>
														</span>
														<span class="fieldsmap">
															<?php
															echo esc_html__( 'Map Field : ', 'wpssc' ) . esc_html( $form_fields[ $i ] );
															?>
														</span>
													</li>
													<?php } ?>
												</ul>
													<?php
												}
												?>
								</div>
											
								<div class="generalSetting-section-freeze-header-row freeze_header">
									<div class="generalSetting-left">
										<div class="contactforms-panel-field">
											<label><?php echo esc_html__( 'Freeze Header', 'wpssc' ); ?></label>
										</div>
										<p><?php echo esc_html__( 'By enabling this feature, the first row containing the header (or title) information will remain fixed at the top of the sheet even while scrolling down, providing easy access to essential details.', 'wpsswp' ); ?></p>
									</div>
									<div class="generalSetting-right">
										<label>
											<input type="checkbox" name="freeze_header" value="yes" 
											<?php
											if ( isset( $freeze[0] ) && 'yes' === (string) $freeze[0] ) {
												echo 'checked=checked';}
											?>
											><span class="checkbox-switch"></span>  							
										</label>
									</div>
								</div>

								<div class="generalSetting-section">
									<div class="generalSetting-left">
										<div class="contactforms-panel-field">
											<label><?php echo esc_html__( 'Row Input Format Option', 'wpssc' ); ?><span class="wpss-tooltio-link tooltip-right">
													<span class="tooltip-text"><a target="_blank" href="<?php echo esc_url( WPSSLC_PRO_VERSION_URL ); ?>" class="upgrade-class-link">Upgrade To Pro</a></span>
												</span></label>
										</div>
										<p><?php echo esc_html__( 'This option allows you to specify how the input data should be interpreted. For further information, refer to the provided Link for more details, ', 'wpssc' ); ?><a href="https://developers.google.com/sheets/api/reference/rest/v4/ValueInputOption" target="_blank">click here.</a></p>
									</div>
								</div>
						</div>
					</div>
				</form>
				<?php
			} else {
				echo esc_html__( 'Please save your newly created form for WPSyncSheets Lite For Contact Form 7 settings.', 'wpssc' );
			}
		}
	}



	/**
	 * Prepare Google Spreadsheet list
	 *
	 * @access public
	 * @return array $sheetarray
	 */
	public static function wpsslc_list_googlespreedsheet() {
		$sheetarray = array(
			'' => __( 'Select Google Spreeadsheet List', 'wpssc' ),
		);
		$sheetarray = self::$instance_api->get_spreadsheet_listing( $sheetarray );
		return $sheetarray;
	}

	/**
	 * Prepare Google Spreedsheet sheet name for feed setting.
	 *
	 * @access public
	 * @param array $data selected spreadsheet data .
	 * @return array $choices
	 */
	public function sheet_for_form_setting( $data ) {

		/* Build choices array. */
		$choices = 'Select a Sheet List';
		/* Get feed settings. */
		$settings = isset( $data[0]['spreadsheetname'] ) ? $data[0]['spreadsheetname'] : '';
		if ( '' === $data[0]['spreadsheetname'] || 'new' === $data[0]['spreadsheetname'] ) {
			return $choices;
		}
		// get worksheet name.
		$response = self::$instance_api->get_sheet_listing( $data[0]['spreadsheetname'] );
		foreach ( $response->getSheets() as $s ) {
			$sheets[] = $s['properties']['title'];
		}
		return $sheets;
	}

	/**
	 * Get form data.
	 *
	 * @access public
	 * @param object $form .
	 * @return array $assoc_arr
	 */
	public function get_form_data( $form ) {
		$assoc_arr = array();
		$meta      = get_post_meta( $form, '_form', true );

		$fields = self::get_fields( $meta );
		foreach ( $fields as $field ) {

			$single = self::get_field_assoc( $field );
			if ( $single ) {
				$assoc_arr[] = $single;
			}
		}
		return $assoc_arr;
	}

	/**
	 * Get form fields.
	 *
	 * @access public
	 * @param string $meta .
	 * @return array $arr
	 */
	public function get_fields( $meta ) {
		$regexp = '/\[.*\]/';
		$arr    = array();

		if ( preg_match_all( $regexp, $meta, $arr ) === false ) {
			return false;
		}
		return $arr[0];
	}

	/**
	 * Get associative form fields.
	 *
	 * @access public
	 * @param string $content .
	 * @return array
	 */
	public function get_field_assoc( $content ) {
		$regexp_type = '/(?<=\[)[^\s\*]*/';
		$regexp_name = '/(?<=\s)[^\s\]]*/';

		$arr_type = array();
		$arr_name = array();
		if ( false === preg_match( $regexp_type, $content, $arr_type ) ) {
			return false;
		}
		if ( ! in_array( $arr_type[0], $this->allowed_tags, true ) ) {
			return false;
		}
		if ( false === preg_match( $regexp_name, $content, $arr_name ) ) {
			return false;
		}

		return array( $arr_type[0] => $arr_name[0] );
	}

	/**
	 * Serch for key in given array using given value
	 *
	 * @access public
	 * @param string $value is to search in arrray.
	 * @param array  $array_val .
	 * @return string $key or null
	 */
	public function search_forkey( $value, $array_val ) {
		foreach ( $array_val as $key => $val ) {
			$key = array_search( $value, $val, true );
			if ( $key ) {
				return $key;
			}
		}
		return null;
	}

	/**
	 * Check Mapping fields
	 *
	 * @param array $mapping_fields .
	 */
	public function check_mapping_fields( $mapping_fields ) {
		if ( $mapping_fields && is_array( $mapping_fields ) ) {
			if ( ! array_key_exists( 'IP-address', $mapping_fields[0] ) ) {
				$mapping_fields[0]['IP-address'] = 'IP Address';
			}
			if ( ! array_key_exists( 'page-URL', $mapping_fields[0] ) ) {
				$mapping_fields[0]['page-URL'] = 'Page URL';
			}
			if ( ! array_key_exists( 'submission-date', $mapping_fields[0] ) ) {
				$mapping_fields[0]['submission-date'] = 'Submission Date';
			}
			if ( ! array_key_exists( 'submission-time', $mapping_fields[0] ) ) {
				$mapping_fields[0]['submission-time'] = 'Submission Time';
			}
		}
	}

	/**
	 * Check active headers
	 *
	 * @param array $active_headers .
	 */
	public function check_active_headers( $active_headers ) {
		if ( $active_headers && is_array( $active_headers ) ) {
			if ( ! array_key_exists( 'IP-address', $active_headers[0] ) ) {
				$active_headers[0]['IP-address'] = '1';
			}
			if ( ! array_key_exists( 'page-URL', $active_headers[0] ) ) {
				$active_headers[0]['page-URL'] = '1';
			}
			if ( ! array_key_exists( 'submission-date', $active_headers[0] ) ) {
				$active_headers[0]['submission-date'] = '1';
			}
			if ( ! array_key_exists( 'submission-time', $active_headers[0] ) ) {
				$active_headers[0]['submission-time'] = '1';
			}
		}
	}

	/**
	 * Create simple index array
	 *
	 * @access public
	 * @param array $arr .
	 * @return array $new_arr
	 */
	public static function single_array( $arr ) {
		$new_arr = array();
		foreach ( $arr as $key ) {
			if ( is_array( $key ) ) {
				$arr1 = self::single_array( $key );
				foreach ( $arr1 as $k ) {
					$new_arr[] = $k;
				}
			} else {
				$new_arr[] = $key;
			}
		}
		return $new_arr;
	}
	/**
	 * Prepare Google Spreedsheet sheet headers for mapping fields to form fields.
	 *
	 * @access public
	 * @param int $form_id .
	 * @param int $param .
	 */
	public static function wpsslc_check_spreadsheet_header_value( $form_id, $param = 0 ) {

		$form_data = self::$instance_api->wpsslc_option( 'wpssc_cf_settings', '', $form_id );
		if ( ! $form_data || ! isset( $form_data[0]['spreadsheetname'] ) || ! isset( $form_data[0]['sheetname'] ) ) {
			return true;
		}

		if ( ( ! empty( $form_data[0]['spreadsheetname'] ) ) && ( ! empty( $form_data[0]['sheetname'] ) ) ) {

			$spreadsheetid = $form_data[0]['spreadsheetname'];
			$spredsheet    = $form_data[0]['sheetname'] . '!A1:Z1';

			$response = self::$instance_api->get_row_list( $spreadsheetid, $spredsheet );
			$values   = $response->getValues();

			if ( 1 === (int) $param ) {
				return $response['values'];
			}
			if ( empty( $response['values'] ) ) {
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * Reset Google API Settings
	 */
	public static function wpsslc_reset_settings() {
		if ( ! current_user_can( 'edit_wpsyncsheets_contact_form_7_lite_main_settings' ) ) {
			echo esc_html__( 'You do not have permission to access this page.', 'wpssc' );
			die();
		}
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'save_api_settings' ) ) {
			echo esc_html__( 'Sorry, your nonce did not verifyy.', 'wpssc' );
			wp_die();
		}
		try {
			$wpsslc_google_settings_value = self::$instance_api->wpsslc_option( 'wpssc_google_settings' );
			$settings                     = array();
			foreach ( $wpsslc_google_settings_value as $key => $value ) {
				$settings[ $key ] = '';
			}
			self::$instance_api->wpsslc_update_option( 'wpssc_google_settings', $settings );
			self::$instance_api->wpsslc_update_option( 'wpssc_google_accessToken', '' );
		} catch ( Exception $e ) {
			echo esc_html( 'Message: ' . $e->getMessage() );
		}
		echo esc_html( 'successful' );
		wp_die();
	}
	/**
	 * Clear spreadsheet
	 *
	 * @access public
	 */
	public static function wpsslc_clear_sheet() {

		$wpsslc_error = '';
		$form_id      = isset( $_POST['form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) : '';
		if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpnonce'] ) ), 'save_sheet_settings' ) || empty( $form_id ) ) {
			echo esc_html__( 'Sorry, your nonce did not verify.', 'wpssc' );
			wp_die();
		}

		$form_data             = self::$instance_api->wpsslc_option( 'wpssc_cf_settings', '', $form_id );
		$wpsslc_active_headers = self::$instance_api->wpsslc_option( 'wpssc_active_headers', '', $form_id );

		$wpsslc_spreadsheetid = $form_data[0]['spreadsheetname'];
		$sheetname            = $form_data[0]['sheetname'];

		$total_headers = count( $wpsslc_active_headers[0] );
		$r             = self::$instance_api->get_row_list( $wpsslc_spreadsheetid, $sheetname );

		foreach ( $r['values'][0] as $data_value ) {
			if ( 'Submission Date' === (string) $data_value ) {
				$total_headers++;
				continue;
			}
		}

		foreach ( $r['values'][0] as $data_value ) {
			if ( 'Submission Time' === (string) $data_value ) {
				$total_headers++;
				continue;
			}
		}

		$last_column = self::get_column_index( $total_headers );
		try {
			$range                  = $sheetname . '!A2:' . $last_column . '100000';
			$requestbody            = self::$instance_api->clearobject();
			$param                  = array();
			$param['spreadsheetid'] = $wpsslc_spreadsheetid;
			$param['sheetname']     = $range;
			$param['requestbody']   = $requestbody;
			$response               = self::$instance_api->clear( $param );
			echo esc_html( 'successful' );
		} catch ( Exception $e ) {
			echo esc_html( 'Message: ' . $e->getMessage() );
		}
		wp_die();
	}
	/**
	 * Get column index of sheet
	 *
	 * @access public
	 * @param int $number .
	 * @return string $letter
	 */
	public static function get_column_index( $number ) {
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
$instance = new WPSSLC_Service();
