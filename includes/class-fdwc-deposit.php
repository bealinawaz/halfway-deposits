<?php
/**
 * Cart calculation: turns the order into a deposit plus a balance line.
 *
 * @package Fixed_Deposit_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds a negative, non-taxable fee so the amount paid at checkout equals the deposit.
 *
 * Taxes on the products (and on shipping) are charged in full with the deposit; the
 * balance line carries no tax. WooCommerce normally spreads tax over negative fees,
 * so the plugin removes the tax from its own line in the cart and on the order.
 */
final class FDWC_Deposit {

	const FEE_ID   = 'fdwc_balance';
	const ITEM_KEY = '_fdwc_balance_line';

	/**
	 * Split calculated for the cart in this request, or null when no deposit applies.
	 *
	 * @var array|null
	 */
	private static $split = null;

	/**
	 * Correction added to the balance after WooCommerce rounds the totals.
	 *
	 * @var float
	 */
	private static $adjust = 0.0;

	/**
	 * Whether the cart is being recalculated to apply the rounding correction.
	 *
	 * @var bool
	 */
	private static $correcting = false;

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'add_balance_fee' ), 1000 );
		add_filter( 'woocommerce_cart_totals_get_fees_from_cart_taxes', array( __CLASS__, 'untax_cart_fee' ), 10, 2 );
		add_action( 'woocommerce_after_calculate_totals', array( __CLASS__, 'correct_rounding' ), 1000 );
		add_action( 'woocommerce_checkout_create_order_fee_item', array( __CLASS__, 'mark_order_fee' ), 10, 3 );
		add_action( 'woocommerce_order_item_fee_after_calculate_taxes', array( __CLASS__, 'untax_order_fee' ) );

		add_action( 'woocommerce_before_cart_totals', array( __CLASS__, 'print_notice' ) );
		add_action( 'woocommerce_review_order_before_payment', array( __CLASS__, 'print_notice' ) );
		add_filter( 'render_block_woocommerce/cart', array( __CLASS__, 'prepend_block_notice' ) );
		add_filter( 'render_block_woocommerce/checkout', array( __CLASS__, 'prepend_block_notice' ) );
	}

	/**
	 * The current deposit split, or null.
	 *
	 * @return array|null Keys: order_value, deposit, balance, payable.
	 */
	public static function split() {
		return self::$split;
	}

	/**
	 * Whether any cart item belongs to a selected category or one of its subcategories.
	 * The deposit applies to the whole order when at least one item matches.
	 *
	 * @param WC_Cart $cart Cart.
	 * @return bool
	 */
	private static function cart_qualifies( $cart ) {
		if ( $cart->is_empty() ) {
			return false;
		}
		if ( ! FDWC_Settings::limited_to_categories() ) {
			return true;
		}
		$selected = FDWC_Settings::categories();
		if ( ! $selected ) {
			return false;
		}
		foreach ( $selected as $term_id ) {
			$children = get_term_children( $term_id, 'product_cat' );
			if ( is_array( $children ) ) {
				$selected = array_merge( $selected, $children );
			}
		}
		foreach ( $cart->get_cart() as $item ) {
			$product_id = ! empty( $item['product_id'] ) ? (int) $item['product_id'] : 0;
			if ( $product_id && array_intersect( $selected, wc_get_product_term_ids( $product_id, 'product_cat' ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Adds the balance line. Runs late so fees from other plugins are already in the cart.
	 *
	 * @param WC_Cart $cart Cart.
	 */
	public static function add_balance_fee( $cart ) {
		self::$split = null;

		if ( ( is_admin() && ! wp_doing_ajax() ) || ! FDWC_Settings::enabled() || ! self::cart_qualifies( $cart ) ) {
			return;
		}

		$decimals = wc_get_price_decimals();
		$products = (float) $cart->get_cart_contents_total() + (float) $cart->get_cart_contents_tax();
		$shipping = (float) $cart->get_shipping_total() + (float) $cart->get_shipping_tax();
		$fees     = 0.0;
		foreach ( $cart->fees_api()->get_fees() as $id => $fee ) {
			if ( self::FEE_ID === $id ) {
				continue;
			}
			$fees += (float) $fee->amount;
			if ( $fee->taxable && wc_tax_enabled() ) {
				$fees += (float) array_sum( WC_Tax::calc_tax( (float) $fee->amount, WC_Tax::get_rates( $fee->tax_class, $cart->get_customer() ), false ) );
			}
		}

		$min_total = FDWC_Settings::min_total();
		if ( $min_total > 0 && $products < $min_total ) {
			return;
		}

		$order_value = round( $products + $shipping + $fees, $decimals );
		$base        = 'split' === FDWC_Settings::shipping() ? $order_value : round( $products + $fees, $decimals );
		$deposit     = 'percent' === FDWC_Settings::type() ? $base * FDWC_Settings::amount() / 100 : FDWC_Settings::amount();
		$deposit     = round( $deposit, $decimals );

		// Nothing to defer when the order is already at or below the deposit.
		if ( $deposit <= 0 || $base <= $deposit ) {
			return;
		}

		$balance = round( $base - $deposit + self::$adjust, $decimals );
		if ( $balance <= 0 ) {
			return;
		}

		$cart->fees_api()->add_fee(
			array(
				'id'      => self::FEE_ID,
				'name'    => FDWC_Settings::label(),
				'amount'  => -1 * $balance,
				'taxable' => false,
			)
		);

		self::$split = array(
			'order_value' => $order_value,
			'deposit'     => $deposit,
			'balance'     => round( $base - $deposit, $decimals ),
			'payable'     => round( $order_value - ( $base - $deposit ), $decimals ),
		);
	}

	/**
	 * Keeps the balance line free of tax in the cart.
	 *
	 * @param array  $taxes Fee taxes.
	 * @param object $fee   Fee data from WC_Cart_Totals.
	 * @return array
	 */
	public static function untax_cart_fee( $taxes, $fee ) {
		if ( isset( $fee->object->id ) && self::FEE_ID === $fee->object->id ) {
			return array();
		}
		return $taxes;
	}

	/**
	 * Recalculates once if WooCommerce's rounding left the total a cent away from the deposit.
	 *
	 * @param WC_Cart $cart Cart.
	 */
	public static function correct_rounding( $cart ) {
		if ( self::$correcting || null === self::$split ) {
			return;
		}
		$difference = round( (float) $cart->get_total( 'edit' ) - self::$split['payable'], wc_get_price_decimals() );
		if ( 0.0 === (float) $difference || abs( $difference ) > 1 ) {
			return;
		}
		self::$correcting = true;
		self::$adjust     = $difference;
		$cart->calculate_totals();
		self::$adjust     = 0.0;
		self::$correcting = false;
	}

	/**
	 * Marks the balance line on the order, so it can be found again later.
	 *
	 * @param WC_Order_Item_Fee $item    Order fee item.
	 * @param string            $fee_key Cart fee ID.
	 * @param object            $fee     Cart fee.
	 */
	public static function mark_order_fee( $item, $fee_key, $fee ) {
		if ( self::FEE_ID === $fee_key || ( isset( $fee->id ) && self::FEE_ID === $fee->id ) ) {
			$item->add_meta_data( self::ITEM_KEY, 'yes', true );
			$item->set_tax_status( 'none' );
		}
	}

	/**
	 * Keeps the balance line free of tax when an order is recalculated, for example by the
	 * block checkout or the Recalculate button on the order screen.
	 *
	 * @param WC_Order_Item_Fee $item Order fee item.
	 */
	public static function untax_order_fee( $item ) {
		if ( 'yes' === $item->get_meta( self::ITEM_KEY ) ) {
			$item->set_taxes( false );
		}
	}

	/**
	 * Whether a deposit applies to the current cart, calculating totals if needed.
	 *
	 * @return bool
	 */
	private static function cart_has_deposit() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || ! FDWC_Settings::enabled() ) {
			return false;
		}
		if ( null === self::$split && ! did_action( 'woocommerce_after_calculate_totals' ) ) {
			WC()->cart->calculate_totals();
		}
		return null !== self::$split;
	}

	/**
	 * Prints the deposit notice in the classic cart totals and checkout order review.
	 * Both areas are refreshed by WooCommerce when the cart changes.
	 */
	public static function print_notice() {
		if ( ! self::cart_has_deposit() ) {
			return;
		}
		echo '<div class="woocommerce-info fdwc-notice" role="status">' . esc_html( FDWC_Settings::notice() ) . '</div>';
	}

	/**
	 * Adds the deposit notice above the block cart and checkout. The balance line
	 * itself updates live; the notice reflects the cart when the page loaded.
	 *
	 * @param string $content Block HTML.
	 * @return string
	 */
	public static function prepend_block_notice( $content ) {
		if ( is_admin() || wp_is_json_request() || ! self::cart_has_deposit() ) {
			return $content;
		}
		$notice = '<div class="wc-block-components-notice-banner is-info fdwc-notice" role="status"><div class="wc-block-components-notice-banner__content">' . esc_html( FDWC_Settings::notice() ) . '</div></div>';
		return $notice . $content;
	}
}
