<?php
/**
 * Main ContactsheetsLite\WPSSLC_Google_API namespace.
 *
 * @since 1.0.0
 * @package contactsheets-lite
 */

namespace ContactsheetsLite;

/**
 * WPSSLC Google API Method Class
 *
 * @since 1.0.0
 */
class WPSSLC_Google_API_Functions extends \WPSSLC_Google_API {
	/**
	 * Google Sheet Object
	 *
	 * @var object
	 * @since 1.0.0
	 */
	private static $instance_service = null;
	/**
	 * Google Drive Object
	 *
	 * @var object
	 * @since 1.0.0
	 */
	private static $instance_drive = null;
	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( self::checkcredenatials() ) {
			self::loadobject();
		}
	}
	/**
	 * Load Google API Library.
	 *
	 * @since 1.0.0
	 */
	public function loadobject() {
		self::$instance_service = self::get_client_object();
		self::$instance_drive   = self::get_drive_object();
	}
	/**
	 * Include Google API Library.
	 *
	 * @since 1.0.0
	 */
	public function wpsslc_load_library() {
		if ( ! class_exists( 'ComposerAutoloaderInita672e4231f706419bd66ef535c4ab40e' ) ) {
			require_once WPSSLC_DIR . 'lib/vendor/autoload.php';
		}
	}
	/**
	 * Generate Google Sheet Object.
	 *
	 * @since 1.0.0
	 */
	public function get_client_object() {
		if ( null === self::$instance_service ) {
			$client                 = self::getClient();
			self::$instance_service = new \Google_Service_Sheets( $client );
		}
		return self::$instance_service;
	}
	/**
	 * Regenerate Google Sheet Object.
	 *
	 * @since 1.0.0
	 */
	public function refreshobject() {
		self::$instance_service = null;
		self::get_client_object();
	}
	/**
	 * Regenerate Google Drive Object.
	 *
	 * @since 1.0.0
	 */
	public function get_drive_object() {
		if ( null === self::$instance_drive ) {
			$client               = self::getClient();
			self::$instance_drive = new \Google_Service_Drive( $client );
		}
		return self::$instance_drive;
	}
	/**
	 * Get Google Drive Object.
	 */
	public static function get_object_drive_object() {
		self::$instance_drive = null;
	}
	/**
	 * Check Google Credenatials.
	 *
	 * @since 1.0.0
	 */
	public function checkcredenatials() {

		$wpsslc_google_settings_value = self::wpsslc_option( 'wpssc_google_settings' );
		$clientid                     = isset( $wpsslc_google_settings_value[0] ) ? $wpsslc_google_settings_value[0] : '';
		$clientsecert                 = isset( $wpsslc_google_settings_value[1] ) ? $wpsslc_google_settings_value[1] : '';
		$auth_token                   = isset( $wpsslc_google_settings_value[2] ) ? $wpsslc_google_settings_value[2] : '';
		if ( empty( $clientid ) || empty( $clientsecert ) || empty( $auth_token ) ) {
			return false;
		} else {
			try {
				if ( self::getClient() ) {
					return true;
				} else {
					return false;
				}
			} catch ( Exception $e ) {
				return false;
			}
		}
	}
	/**
	 * Get meta vlaue.
	 *
	 * @param object $key plugin meta key.
	 * @param string $type boolean value.
	 * @param int    $form_id .
	 */
	public static function wpsslc_option( $key = '', $type = '', $form_id = '' ) {
		$value                = '';
		$wpsslc_old_keys      = self::wpsslc_old_option_keys();
		$wpsslc_old_post_keys = self::wpsslc_old_post_option_keys();
		$postmetas            = get_post_meta( $form_id );
		$wpsslc_oldmeta_keys  = array_keys( $wpsslc_old_keys );
		if ( in_array( $key, $wpsslc_oldmeta_keys, true ) ) {
			self::wpsslc_database_update();
		}
		if ( array_key_exists( $key, $wpsslc_old_keys ) ) {
			$value = parent::wpsslc_option( $key, $type );
		}

		if ( ! empty( $form_id ) && array_key_exists( $key, $wpsslc_old_post_keys ) && isset( $postmetas[ $key ] ) ) {
			$value = get_post_meta( $form_id, $key );
		} elseif ( ! empty( $form_id ) && isset( $postmetas[ $wpsslc_old_post_keys[ $key ] ] ) ) {
			$value = get_post_meta( $form_id, $wpsslc_old_post_keys[ $key ] );
		}
		return $value;
	}

	/**
	 * Update meta value.
	 *
	 * @param object $key plugin meta key.
	 * @param string $value plugin meta value.
	 */
	public static function wpsslc_update_option( $key = '', $value = '' ) {
		$wpsslc_old_keys     = self::wpsslc_old_option_keys();
		$wpsslc_oldmeta_keys = array_keys( $wpsslc_old_keys );
		if ( in_array( $key, $wpsslc_oldmeta_keys, true ) ) {
			self::wpsslc_database_update();
		}
		$value = parent::wpsslc_update_option( $key, $value );
		return $value;
	}
	/**
	 * Update database.
	 */
	public static function wpsslc_database_update() {
		$wpsslc_is_updated = self::wpsslc_option( 'wpssc_database_updated' );
		$wpsslc_old_keys   = self::wpsslc_old_option_keys();

		global $wpdb;

		if ( 'yes' !== $wpsslc_is_updated ) {
			$table_name = $wpdb->prefix;
			$table_name = $table_name . 'options';
			foreach ( $wpsslc_old_keys as $newkey => $oldkey ) {
				// @codingStandardsIgnoreStart
				$sql    = $wpdb->prepare( "UPDATE `$table_name` SET `option_name`=%s WHERE `option_name`=%s", $newkey, $oldkey ); // db call ok.
				$result = $wpdb->get_results( $sql );
				// @codingStandardsIgnoreEnd
			}
			self::wpsslc_update_option( 'wpssc_database_updated', 'yes' );
		}
	}

	/**
	 * Return old option keys.
	 *
	 * @return array
	 */
	public static function wpsslc_old_option_keys() {
		return array(
			'wpssc_google_settings'    => 'advc_cf_google_settings',
			'wpssc_google_accessToken' => 'contactsheets_google_accessToken',
		);
	}

	/**
	 * Return old post meta option keys.
	 *
	 * @return array
	 */
	public static function wpsslc_old_post_option_keys() {
		return array(
			'wpssc_cf_settings'    => 'advc_cf_settings',
			'wpssc_active_headers' => 'contactsheets_active_headers',
		);
	}


	/**
	 * Generate token for the user and refresh the token if it's expired.
	 *
	 * @param int $flag for getting error code.
	 * @return array
	 */
	public function getClient( $flag = 0 ) {
		$this->wpsslc_load_library();
		$wpsslc_google_settings_value = self::wpsslc_option( 'wpssc_google_settings' );
		$clientid                     = isset( $wpsslc_google_settings_value[0] ) ? $wpsslc_google_settings_value[0] : '';
		$clientsecert                 = isset( $wpsslc_google_settings_value[1] ) ? $wpsslc_google_settings_value[1] : '';
		$auth_token                   = isset( $wpsslc_google_settings_value[2] ) ? $wpsslc_google_settings_value[2] : '';
		$client                       = new \Google_Client();
		$client->setApplicationName( 'WPSyncSheets Lite For Contact Form 7 - Contact Form 7 Google Spreadsheet Addon' );
		$client->setScopes( \Google_Service_Sheets::SPREADSHEETS_READONLY );
		$client->setScopes( \Google_Service_Drive::DRIVE_METADATA_READONLY );
		$client->addScope( \Google_Service_Sheets::SPREADSHEETS );
		$client->setClientId( $clientid );
		$client->setClientSecret( $clientsecert );
		$client->setRedirectUri( esc_html( admin_url( 'admin.php?page=wpsyncsheets-contact-form-7' ) ) );
		$client->setAccessType( 'offline' );
		$client->setPrompt('consent');
		// Load previously authorized credentials from a database.
		try {
			if ( empty( $auth_token ) ) {
				$auth_url = $client->createAuthUrl();
				return $auth_url;
			}
			$wpsslc_accesstoken = self::wpsslc_option( 'wpssc_google_accessToken' );
			if ( ! empty( $wpsslc_accesstoken ) ) {
				$accesstoken = json_decode( $wpsslc_accesstoken, true );
			} else {
				if ( empty( $auth_token ) ) {
					$auth_url = $client->createAuthUrl();
					return $auth_url;
				} else {
					$authcode = trim( $auth_token );
					// Exchange authorization code for an access token.
					$accesstoken = $client->fetchAccessTokenWithAuthCode( $authcode );
					if(! isset( $accesstoken['refresh_token'] ) || empty( $accesstoken['refresh_token'] ) ){
						$accesstoken['refresh_token'] = $client->getRefreshToken();
					}
					// Store the credentials to disk.
					parent::wpsslc_update_option( 'wpssc_google_accessToken', wp_json_encode( $accesstoken ) );
				}
			}
			// Check for invalid token.
			if ( is_array( $accesstoken ) && isset( $accesstoken['error'] ) && ! empty( $accesstoken['error'] ) ) {
				if ( $flag ) {
					return $accesstoken['error'];
				}
				return false;
			}
			$client->setAccessToken( $accesstoken );
			// Refresh the token if it's expired.
			if ( $client->isAccessTokenExpired() ) {
				// save refresh token to some variable.				
				$refreshtokensaved = ( isset( $accesstoken['refresh_token'] ) && !empty( $accesstoken['refresh_token'] ) ) ? $accesstoken['refresh_token'] : $client->getRefreshToken();
				if( Null === $refreshtokensaved || empty( $refreshtokensaved ) ){
					if ( $flag ) {
						$m = 'Please revoke the token and generate it again.';
						return $m;
					} else {
						return false;
					}
				}
				$newaccesstoken = $client->fetchAccessTokenWithRefreshToken( $refreshtokensaved );

				if ( is_array( $newaccesstoken ) && isset( $newaccesstoken['error'] ) && ! empty( $newaccesstoken['error'] ) ) {
					if ( $flag ) {
						return $newaccesstoken['error'];
					}
					return false;
				}
				
				// pass access token to some variable.
				$accesstokenupdated = $client->getAccessToken();
				if(! isset( $accesstokenupdated['refresh_token'] ) ){
					// append refresh token.
					$accesstokenupdated['refresh_token'] = $refreshtokensaved;
				}
				// Set the new access token.
				parent::wpsslc_update_option( 'wpssc_google_accessToken', wp_json_encode( $accesstokenupdated ) );
				$accesstoken = json_decode( wp_json_encode( $accesstokenupdated ), true );
				$client->setAccessToken( $accesstoken );
			}
		} catch ( Exception $e ) {
			if ( $flag ) {
				return $e->getMessage();
			} else {
				return false;
			}
		}

		return $client;
	}
	/**
	 * Fetch Spreadsheet list from Google Drive.
	 *
	 * @param array $sheetarray Spreadsheet array.
	 * @since 1.0.0
	 *
	 * @return array.
	 */
	public function get_spreadsheet_listing( $sheetarray = array() ) {

		if ( self::checkcredenatials() ) {
			self::get_object_drive_object();
			self::loadobject();
		} else {
			return $sheetarray;
		}

		$opt_params = array(
			'fields' => 'nextPageToken, files(id, name, mimeType)',
			'q'      => "mimeType='application/vnd.google-apps.spreadsheet' and trashed = false",
		);
		$results    = self::$instance_drive->files->listFiles( $opt_params );
		if ( count( $results->getFiles() ) === 0 ) {
		} else {
			foreach ( $results->getFiles() as $file ) {
			$sheetarray[ $file->getId() ] = $file->getName();
			}
		}	
		return $sheetarray;
	}
	/**
	 * Retrieve the list of sheets from the Google Spreadsheet.
	 *
	 * @param string $spreadsheetid Spreadsheet id.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function get_sheet_listing( $spreadsheetid = '' ) {
		self::refreshobject();
		return parent::get_sheets( self::$instance_service, $spreadsheetid );
	}
	/**
	 * Fetch row from Google Sheet.
	 *
	 * @param array $spreadsheetid Spreadsheet ID.
	 * @param array $sheetname Sheet Name.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function get_row_list( $spreadsheetid, $sheetname ) {
		self::refreshobject();
		$param                  = array();
		$param['spreadsheetid'] = trim( $spreadsheetid );
		$param['sheetname']     = trim( $sheetname );
		return parent::get_values( self::$instance_service, $param );
	}
	/**
	 * Create sheet array.
	 *
	 * @param object $response_object google sheet object.
	 * @since 1.0.0
	 *
	 * @return array.
	 */
	public function get_sheet_list( $response_object ) {
		$sheets = array();
		foreach ( $response_object->getSheets() as $key => $value ) {
			$sheets[ $value['properties']['title'] ] = $value['properties']['sheetId'];
		}
		return $sheets;
	}
	/**
	 * Create insertDimension Object.
	 *
	 * @param array $param contains sheetid,startindex,endindex.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function insertdimensionobject( $param = array() ) {
		$requests           = new \Google_Service_Sheets_Request(
			array(
				'insertDimension' => array(
					'range' => array(
						'sheetId'    => $param['sheetid'],
						'dimension'  => 'ROWS',
						'startIndex' => $param['startindex'],
						'endIndex'   => $param['endindex'],
					),
				),
			)
		);
		$batchupdaterequest = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
			array(
				'requests' => $requests,
			)
		);
		return $batchupdaterequest;
	}
	/**
	 * Create moveDimension Object.
	 *
	 * @param array $param contains sheetid,startindex,endindex,destinationIndex.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function moveDimensionrequests( $param = array() ) {
		$requests = new \Google_Service_Sheets_Request(
			array(
				'moveDimension' => array(
					'source'           => array(
						'dimension'  => 'COLUMNS',
						'sheetId'    => $param['sheetid'],
						'startIndex' => $param['startindex'],
						'endIndex'   => $param['endindex'],
					),
					'destinationIndex' => $param['destindex'],
				),
			)
		);
		return $requests;
	}
	/**
	 * Create deleteDimension Object.
	 *
	 * @param array $param contains sheetid,startindex,endindex.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function deleteDimensionrequests( $param = array() ) {
		$requests = new \Google_Service_Sheets_Request(
			array(
				'deleteDimension' => array(
					'range' => array(
						'sheetId'    => $param['sheetid'],
						'dimension'  => 'COLUMNS',
						'startIndex' => $param['startindex'],
						'endIndex'   => $param['endindex'],
					),
				),
			)
		);
		return $requests;
	}
	/**
	 * Create insertDimension Object.
	 *
	 * @param array $param contains sheetid,startindex,endindex.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function insertdimensionrequests( $param = array() ) {
		$requests = new \Google_Service_Sheets_Request(
			array(
				'insertDimension' => array(
					'range' => array(
						'sheetId'    => $param['sheetid'],
						'dimension'  => 'COLUMNS',
						'startIndex' => $param['startindex'],
						'endIndex'   => $param['endindex'],
					),
				),
			)
		);

		return $requests;
	}
	/**
	 * Update batch requests.
	 *
	 * @param array $param contains requests.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function updatebachrequests( $param = array() ) {
		$batchupdaterequest             = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
			array(
				'requests' => $param['requestarray'],
			)
		);
		$requestobject['spreadsheetid'] = $param['spreadsheetid'];
		$requestobject['requestbody']   = $batchupdaterequest;

		return parent::batchupdate( self::$instance_service, $requestobject );
	}
	/**
	 * Freeze Row Object
	 *
	 * @param int $sheetid Sheet ID.
	 * @param int $wpsslc_freeze 0 - Unfreeze Row, 1 - Freeze Row.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function freezeobject( $sheetid = 0, $wpsslc_freeze = 0 ) {
		$requestbody = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
			array(
				'requests' => array(
					'updateSheetProperties' => array(
						'properties' => array(
							'sheetId'        => $sheetid,
							'gridProperties' => array(
								'frozenRowCount' => $wpsslc_freeze,
							),
						),
						'fields'     => 'gridProperties.frozenRowCount',
					),
				),
			)
		);
		return $requestbody;
	}
	/**
	 * Google_Service_Sheets_Spreadsheet Object
	 *
	 * @param string $spreadsheetname Spreadsheet Name.
	 * @param string $sheetname Sheet Name.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function newspreadsheetobject( $spreadsheetname = '', $sheetname = '' ) {
		$requestbody = new \Google_Service_Sheets_Spreadsheet(
			array(
				'properties' => array(
					'title' => $spreadsheetname,
				),
				'sheets'     => array(
					'properties' => array(
						'title' => $sheetname,
					),
				),
			)
		);
		return $requestbody;
	}
	/**
	 * Create new sheet
	 *
	 * @param string $param .
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function newsheetobject( $param = array() ) {
		$batchupdaterequest   = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
			array(
				'requests' => array(
					'addSheet' => array(
						'properties' => array(
							'title' => $param['sheetname'],
						),
					),
				),
			)
		);
		$param['requestbody'] = $batchupdaterequest;
		return parent::batchupdate( self::$instance_service, $param );
	}
	/**
	 * Prepare parameter array.
	 *
	 * @param string $spreadsheetid Spreadsheet Name.
	 * @param string $range Sheet Name.
	 * @param array  $requestbody requestbody param.
	 * @param array  $params array.
	 * @since 1.0.0
	 *
	 * @return array.
	 */
	public function setparamater( $spreadsheetid = '', $range = '', $requestbody = array(), $params = array() ) {
		$param                  = array();
		$param['spreadsheetid'] = $spreadsheetid;
		$param['range']         = $range;
		$param['requestbody']   = $requestbody;
		$param['params']        = $params;
		return $param;
	}
	/**
	 * Create Google_Service_Sheets_ValueRange Object.
	 *
	 * @param array $values_data Values Array.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function valuerangeobject( $values_data = array() ) {
		$requestbody = new \Google_Service_Sheets_ValueRange( array( 'values' => $values_data ) );
		return $requestbody;
	}
	/**
	 * Create Google_Service_Sheets_ClearValuesRequest Object.
	 *
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function clearobject() {
		$requestbody = new \Google_Service_Sheets_ClearValuesRequest();
		return $requestbody;
	}
	/**
	 * Insert new column, Freeze first row to google spreadsheet.
	 *
	 * @param array $param contains spreadsheetid,requestbody.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function formatsheet( $param = array() ) {
		return parent::batchupdate( self::$instance_service, $param );
	}
	/**
	 * Update entry to google sheet.
	 *
	 * @param array $param contains spreadsheetid, range, requestbody, params.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function updateentry( $param = array() ) {
		return parent::update_entry( self::$instance_service, $param );
	}
	/**
	 * Append entry to google sheet.
	 *
	 * @param array $param contains spreadsheetid, range, requestbody, params.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function appendentry( $param = array() ) {
		return parent::append_entry( self::$instance_service, $param );
	}
	/**
	 * Create new spreadsheet in Google Drive.
	 *
	 * @param array $requestbody requestbody object.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function createspreadsheet( $requestbody = array() ) {
		return parent::create_spreadsheet( self::$instance_service, $requestbody );
	}
	/**
	 * Clear Sheet Value.
	 *
	 * @param array $param spreadsheetid,sheetname,requestbody.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function clear( $param = array() ) {
		return parent::clearsheet( self::$instance_service, $param );
	}
}
