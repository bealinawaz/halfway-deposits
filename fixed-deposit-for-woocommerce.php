<?php
/**
 * Plugin Name:          Fixed Deposit for WooCommerce – Take a Set Deposit at Checkout
 * Plugin URI:           https://github.com/bealinawaz/fixed-deposit-for-woocommerce
 * Description:          Charge a fixed amount or a percentage of the order as a deposit at checkout. The rest of the order is recorded as a balance you invoice later.
 * Version:              2.0.0
 * Requires at least:    6.2
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               Ali Nawaz
 * Author URI:           https://bealinawaz.com
 * License:              GPLv2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          fixed-deposit-for-woocommerce
 * WC requires at least: 7.1
 * WC tested up to:      10.3
 *
 * @package Fixed_Deposit_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'FDWC_VERSION', '2.0.0' );
define( 'FDWC_FILE', __FILE__ );

require_once __DIR__ . '/includes/class-fdwc-settings.php';
require_once __DIR__ . '/includes/class-fdwc-deposit.php';
require_once __DIR__ . '/includes/class-fdwc-orders.php';

// Orders are read and written through WooCommerce's CRUD API, and the deposit is a regular cart fee,
// which the block-based cart and checkout support through the Store API.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', FDWC_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', FDWC_FILE, true );
		}
	}
);

FDWC_Deposit::init();
FDWC_Orders::init();

if ( is_admin() ) {
	require_once __DIR__ . '/includes/class-fdwc-admin.php';
	FDWC_Admin::init();
}
