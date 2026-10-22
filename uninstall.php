<?php
/**
 * Removes the plugin's settings. Deposit details saved on orders are kept,
 * because they are part of the store's order records.
 *
 * @package Fixed_Deposit_For_WooCommerce
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

foreach ( array( 'fdwc_enabled', 'fdwc_type', 'fdwc_amount', 'fdwc_shipping', 'fdwc_min_total', 'fdwc_scope', 'fdwc_categories', 'fdwc_label', 'fdwc_notice' ) as $fdwc_option ) {
	delete_option( $fdwc_option );
}
