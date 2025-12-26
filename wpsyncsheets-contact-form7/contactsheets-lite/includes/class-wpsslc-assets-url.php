<?php
/**
 * Class for Assets URL method
 *
 * @package contactsheets-lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Final class of WPSSLC_Assets_Url
 */
final class WPSSLC_Assets_URL {

	/**
	 * Assets URL builder method
	 *
	 * @param string $type js|css.
	 * @param string $filename  File name without extension.
	 * @param string $dev_path  Development relative path.
	 * @param string $prod_path Production relative path.
	 */
	public static function assets_url( string $type, string $filename, string $dev_path, string $prod_path ): string {

		$is_production = (
			defined( 'WPSSLC_ENVIRONMENT' ) &&
			WPSSLC_ENVIRONMENT === 'production'
		);

		$dev_file  = trailingslashit( WPSSLC_PATH ) . trailingslashit( $dev_path ) . $filename . '.' . $type;
		$prod_file = trailingslashit( WPSSLC_PATH ) . trailingslashit( $prod_path ) . $filename . '.' . $type;

		if ( $is_production && file_exists( $prod_file ) ) {
			return WPSSLC_URL . trailingslashit( $prod_path ) . $filename . '.' . $type;
		}
		return WPSSLC_URL . trailingslashit( $dev_path ) . $filename . '.' . $type;
	}
}
