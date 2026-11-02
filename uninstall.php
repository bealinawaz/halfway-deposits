<?php
/**
 * Removes the plugin's settings. Deposit details saved on orders are kept,
 * because they are part of the store's order records.
 *
 * @package Halfway_Deposits
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

foreach ( array( 'enabled', 'type', 'amount', 'shipping', 'min_total', 'scope', 'categories', 'label', 'notice' ) as $hdwc_option ) {
	delete_option( 'hdwc_' . $hdwc_option );
	delete_option( 'fdwc_' . $hdwc_option ); // Names used by version 2.0.0 and earlier.
}
delete_option( 'hdwc_migrated' );
