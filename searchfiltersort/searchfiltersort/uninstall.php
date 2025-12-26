<?php
/**
 * Uninstall script
 *
 * Deletes plugin options on uninstall.
 *
 * @package searchfiltersort
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'sflts_settings' );
