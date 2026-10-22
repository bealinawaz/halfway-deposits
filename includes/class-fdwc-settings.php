<?php
/**
 * Deposit settings.
 *
 * @package Fixed_Deposit_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads and validates the settings saved on WooCommerce → Settings → Deposit.
 */
final class FDWC_Settings {

	const ENABLED    = 'fdwc_enabled';
	const TYPE       = 'fdwc_type';
	const AMOUNT     = 'fdwc_amount';
	const SHIPPING   = 'fdwc_shipping';
	const MIN_TOTAL  = 'fdwc_min_total';
	const SCOPE      = 'fdwc_scope';
	const CATEGORIES = 'fdwc_categories';
	const LABEL      = 'fdwc_label';
	const NOTICE     = 'fdwc_notice';

	/**
	 * All option names, for uninstall and tests.
	 *
	 * @return string[]
	 */
	public static function option_names() {
		return array( self::ENABLED, self::TYPE, self::AMOUNT, self::SHIPPING, self::MIN_TOTAL, self::SCOPE, self::CATEGORIES, self::LABEL, self::NOTICE );
	}

	/**
	 * Whether deposits are turned on with a usable amount.
	 *
	 * @return bool
	 */
	public static function enabled() {
		return 'yes' === get_option( self::ENABLED, 'no' ) && self::amount() > 0;
	}

	/**
	 * Deposit type: "percent" or "fixed".
	 *
	 * @return string
	 */
	public static function type() {
		return 'fixed' === get_option( self::TYPE, 'percent' ) ? 'fixed' : 'percent';
	}

	/**
	 * Deposit amount: a percentage from 0 to 100, or a fixed amount in the store currency.
	 *
	 * @return float
	 */
	public static function amount() {
		$amount = max( 0.0, (float) wc_format_decimal( get_option( self::AMOUNT, '' ) ) );
		return 'percent' === self::type() ? min( 100.0, $amount ) : $amount;
	}

	/**
	 * How shipping is handled: "upfront" charges it in full with the deposit,
	 * "split" includes it in the order value the deposit is taken from.
	 *
	 * @return string
	 */
	public static function shipping() {
		return 'split' === get_option( self::SHIPPING, 'upfront' ) ? 'split' : 'upfront';
	}

	/**
	 * Minimum order value (products after discounts, including tax) for a deposit. 0 means none.
	 *
	 * @return float
	 */
	public static function min_total() {
		return max( 0.0, (float) wc_format_decimal( get_option( self::MIN_TOTAL, '' ) ) );
	}

	/**
	 * Selected product category IDs, or an empty array when deposits apply to all products.
	 *
	 * @return int[]
	 */
	public static function categories() {
		if ( 'categories' !== get_option( self::SCOPE, 'all' ) ) {
			return array();
		}
		$ids = get_option( self::CATEGORIES, array() );
		return is_array( $ids ) ? array_values( array_filter( array_map( 'absint', $ids ) ) ) : array();
	}

	/**
	 * Whether deposits are limited to categories.
	 *
	 * @return bool
	 */
	public static function limited_to_categories() {
		return 'categories' === get_option( self::SCOPE, 'all' );
	}

	/**
	 * Label of the balance line in the cart, checkout, and order.
	 *
	 * @return string
	 */
	public static function label() {
		$label = trim( (string) get_option( self::LABEL, '' ) );
		return '' !== $label ? $label : self::default_label();
	}

	/**
	 * Default balance line label.
	 *
	 * @return string
	 */
	public static function default_label() {
		return __( 'Balance due later', 'fixed-deposit-for-woocommerce' );
	}

	/**
	 * Notice shown in the cart and checkout while a deposit applies.
	 *
	 * @return string
	 */
	public static function notice() {
		$notice = trim( (string) get_option( self::NOTICE, '' ) );
		return '' !== $notice ? $notice : self::default_notice();
	}

	/**
	 * Default cart and checkout notice.
	 *
	 * @return string
	 */
	public static function default_notice() {
		return __( 'Only a deposit is due today. The remaining balance is shown in the order totals, and we will send you an invoice for it later.', 'fixed-deposit-for-woocommerce' );
	}
}
