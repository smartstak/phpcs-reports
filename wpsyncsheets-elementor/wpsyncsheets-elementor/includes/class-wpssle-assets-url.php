<?php
/**
 * Class for Assets URL method
 *
 * @package wpsyncsheets-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Final class of WPSSLE_Assets_Url
 */
final class WPSSLE_Assets_URL {

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
			defined( 'WPSSLE_ENVIRONMENT' ) &&
			WPSSLE_ENVIRONMENT === 'production'
		);

		$dev_file  = trailingslashit( WPSSLE_PATH ) . trailingslashit( $dev_path ) . $filename . '.' . $type;
		$prod_file = trailingslashit( WPSSLE_PATH ) . trailingslashit( $prod_path ) . $filename . '.' . $type;

		if ( $is_production && file_exists( $prod_file ) ) {
			return WPSSLE_URL . trailingslashit( $prod_path ) . $filename . '.' . $type;
		}
		return WPSSLE_URL . trailingslashit( $dev_path ) . $filename . '.' . $type;
	}
}
