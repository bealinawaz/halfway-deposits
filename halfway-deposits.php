<?php
/**
 * Plugin Name:          Halfway – Deposits & Partial Payments for WooCommerce
 * Plugin URI:           https://github.com/bealinawaz/halfway-deposits
 * Description:          Take a deposit or partial payment at checkout: a fixed amount or a percentage of the order. The rest is recorded on the order as the balance due.
 * Version:              2.1.1
 * Requires at least:    6.2
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               Ali Nawaz
 * Author URI:           https://bealinawaz.com
 * License:              GPLv2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          halfway-deposits
 * WC requires at least: 7.1
 * WC tested up to:      11.2
 *
 * @package Halfway_Deposits
 */

defined( 'ABSPATH' ) || exit;

define( 'HDWC_VERSION', '2.1.1' );
define( 'HDWC_FILE', __FILE__ );

require_once __DIR__ . '/includes/class-hdwc-settings.php';
require_once __DIR__ . '/includes/class-hdwc-deposit.php';
require_once __DIR__ . '/includes/class-hdwc-orders.php';

// Orders are read and written through WooCommerce's CRUD API, and the deposit is a regular cart fee,
// which the block-based cart and checkout support through the Store API.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', HDWC_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', HDWC_FILE, true );
		}
	}
);

HDWC_Settings::migrate_legacy_options();
HDWC_Deposit::init();
HDWC_Orders::init();

if ( is_admin() ) {
	require_once __DIR__ . '/includes/class-hdwc-admin.php';
	HDWC_Admin::init();
}
