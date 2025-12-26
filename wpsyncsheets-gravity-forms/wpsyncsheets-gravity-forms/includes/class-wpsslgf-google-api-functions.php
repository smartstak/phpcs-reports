<?php
/**
 * Main WPSyncSheetsGravityForms\WPSSLGF_Google_API namespace.
 *
 * @since 1.0.0
 * @package wpsyncsheets-gravity-forms
 */

namespace WPSyncSheetsGravityForms;

/**
 * Google API Method Class
 *
 * @since 1.0.0
 */
class WPSSLGF_Google_API_Functions extends \WPSSLGF_Google_API {

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
	public function wpsslgf_load_library() {
		if ( ! class_exists( 'ComposerAutoloaderInita672e4231f706419bd66ef535c4ab40e' ) ) {
			require_once WPSSLGF_DIR . 'lib/vendor/autoload.php';
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
		$wpsslgf_google_settings_value = self::wpsslgf_option( 'wpssg_google_settings' );
		$clientid                      = isset( $wpsslgf_google_settings_value[0] ) ? $wpsslgf_google_settings_value[0] : '';
		$clientsecert                  = isset( $wpsslgf_google_settings_value[1] ) ? $wpsslgf_google_settings_value[1] : '';
		$auth_token                    = isset( $wpsslgf_google_settings_value[2] ) ? $wpsslgf_google_settings_value[2] : '';
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
	 */
	public static function wpsslgf_option( $key = '', $type = '' ) {
		$wpsslgf_old_keys     = self::wpsslgf_old_option_keys();
		$wpsslgf_oldmeta_keys = array_keys( $wpsslgf_old_keys );
		if ( in_array( $key, $wpsslgf_oldmeta_keys, true ) ) {
			self::wpsslgf_database_update();
		}
		$value = parent::wpsslgf_option( $key, $type );
		return $value;
	}

	/**
	 * Update meta value.
	 *
	 * @param object $key plugin meta key.
	 * @param string $value plugin meta value.
	 */
	public static function wpsslgf_update_option( $key = '', $value = '' ) {
		$wpsslgf_oldmeta_keys = array_keys( self::wpsslgf_old_option_keys() );
		if ( in_array( $key, $wpsslgf_oldmeta_keys, true ) ) {
			self::wpsslgf_database_update();
		}
		$value = parent::wpsslgf_update_option( $key, $value );
		return $value;
	}
	/**
	 * Update database.
	 */
	public static function wpsslgf_database_update() {
		$wpsslgf_is_updated = self::wpsslgf_option( 'wpssg_database_updated' );
		$wpsslgf_old_keys   = self::wpsslgf_old_option_keys();
		global $wpdb;
		if ( ! $wpsslgf_is_updated ) {
			$table_name = $wpdb->prefix;
			$table_name = $table_name . 'options';
			foreach ( $wpsslgf_old_keys as $newkey => $oldkey ) {
				// @codingStandardsIgnoreStart
				$sql    = $wpdb->prepare( "UPDATE `$table_name` SET `option_name`=%s WHERE `option_name`=%s", $newkey, $oldkey ); // db call ok.
				$result = $wpdb->get_results( $sql );
				// @codingStandardsIgnoreEnd
			}
			self::wpsslgf_update_option( 'wpssg_database_updated', 1 );
		}
	}
	/**
	 * Return old option keys.
	 *
	 * @return array
	 */
	public static function wpsslgf_old_option_keys() {
		return array(
			'wpssg_google_settings'    => 'gravitysheets_google_settings',
			'wpssg_google_accessToken' => 'gravitysheets_google_accessToken',
		);
	}
	/**
	 * Generate token for the user and refresh the token if it's expired.
	 *
	 * @param int $flag for getting error code.
	 * @return array
	 */
	public function getClient( $flag = 0 ) {

		$this->wpsslgf_load_library();
		$wpsslgf_google_settings_value = self::wpsslgf_option( 'wpssg_google_settings' );
		$clientid                      = isset( $wpsslgf_google_settings_value[0] ) ? $wpsslgf_google_settings_value[0] : '';
		$clientsecert                  = isset( $wpsslgf_google_settings_value[1] ) ? $wpsslgf_google_settings_value[1] : '';
		$auth_token                    = isset( $wpsslgf_google_settings_value[2] ) ? $wpsslgf_google_settings_value[2] : '';
		$client                        = new \Google_Client();
		$client->setApplicationName( 'WPSyncSheets Lite For Gravity Forms - Gravity Forms Google Spreadsheet Addon' );
		$client->setScopes( \Google_Service_Sheets::SPREADSHEETS_READONLY );
		$client->setScopes( \Google_Service_Drive::DRIVE_METADATA_READONLY );
		$client->addScope( \Google_Service_Sheets::SPREADSHEETS );
		$client->setClientId( $clientid );
		$client->setClientSecret( $clientsecert );
		$client->setRedirectUri( esc_html( admin_url( 'admin.php?page=wpsyncsheets-gravity-forms' ) ) );
		$client->setAccessType( 'offline' );
		$client->setPrompt('consent');
		// Load previously authorized credentials from a database.
		try {
			if ( rgblank( $auth_token ) ) {
				$auth_url = $client->createAuthUrl();
				return $auth_url;
			}
			$wpsslgf_accesstoken = parent::wpsslgf_option( 'wpssg_google_accessToken' );

			if ( ! empty( $wpsslgf_accesstoken ) ) {
				$accesstoken = json_decode( $wpsslgf_accesstoken, true );
			} else {
				if ( rgblank( $auth_token ) ) {
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
					parent::wpsslgf_update_option( 'wpssg_google_accessToken', wp_json_encode( $accesstoken ) );
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
				parent::wpsslgf_update_option( 'wpssg_google_accessToken', wp_json_encode( $accesstokenupdated ) );
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
		// Print the names and IDs for up to 10 files.
		$optparams = array(
			'fields' => 'nextPageToken, files(id, name, mimeType)',
			'q'      => "mimeType='application/vnd.google-apps.spreadsheet' and trashed = false",
		);

		$results = self::$instance_drive->files->listFiles( $optparams );

		if ( count( $results->getFiles() ) === 0 ) {
			$sheetarray[] = array(
				'label' => esc_html__( 'Create New', 'wpssg' ),
				'value' => 'new',
			);
		} else {
			$wpsslgf_spreadsheets = array_column( $results->getFiles(), 'name', 'id' );
			foreach ( $wpsslgf_spreadsheets as $id => $name ) {
				$sheetarray[] = array(
					'label' => $name,
					'value' => $id,
				);
			}

			$sheetarray[] = array(
				'label' => esc_html__( 'Create New Spreadsheet', 'wpssg' ),
				'value' => 'new',
			);
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
	 * Freeze Row Object
	 *
	 * @param int $sheetid Sheet ID.
	 * @param int $wpsslgf_freeze 0 - Unfreeze Row, 1 - Freeze Row.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function freezeobject( $sheetid = 0, $wpsslgf_freeze = 0 ) {
		$requestbody = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
			array(
				'requests' => array(
					'updateSheetProperties' => array(
						'properties' => array(
							'sheetId'        => $sheetid,
							'gridProperties' => array(
								'frozenRowCount' => $wpsslgf_freeze,
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
