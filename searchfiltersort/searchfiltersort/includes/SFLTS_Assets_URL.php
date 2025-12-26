<?php
/**
 * Class for Assets URL method
 *
 * @package searchfiltersort
 */

namespace SearchFilterSort;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Final class of WPSSLWP_Assets_Url
 */
final class SFLTS_Assets_URL {

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
			defined( 'SFLTS_ENVIRONMENT' ) &&
			SFLTS_ENVIRONMENT === 'production'
		);

		$dev_file  = trailingslashit( SFLTS_PLUGIN_PATH ) . trailingslashit( $dev_path ) . $filename . '.' . $type;
		$prod_file = trailingslashit( SFLTS_PLUGIN_PATH ) . trailingslashit( $prod_path ) . $filename . '.' . $type;

		if ( $is_production && file_exists( $prod_file ) ) {
			return SFLTS_PLUGIN_URL . trailingslashit( $prod_path ) . $filename . '.' . $type;
		}
		return SFLTS_PLUGIN_URL . trailingslashit( $dev_path ) . $filename . '.' . $type;
	}
}
