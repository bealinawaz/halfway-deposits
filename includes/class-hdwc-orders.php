<?php
/**
 * Saves the deposit split on orders and shows it to customers and the store.
 *
 * @package Halfway_Deposits
 */

defined( 'ABSPATH' ) || exit;

/**
 * Order meta and order totals rows.
 */
final class HDWC_Orders {

	const META_ORDER_VALUE = '_hdwc_order_value';
	const META_DEPOSIT     = '_hdwc_deposit';
	const META_BALANCE     = '_hdwc_balance_due';

	/**
	 * Registers hooks.
	 */
	public static function init() {
		// Classic checkout.
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'save_split' ) );
		// Block checkout (Store API). Runs each time the draft order is updated from the cart.
		add_action( 'woocommerce_store_api_checkout_update_order_meta', array( __CLASS__, 'save_split' ) );

		add_filter( 'woocommerce_get_order_item_totals', array( __CLASS__, 'totals_rows' ), 10, 2 );
		add_action( 'woocommerce_admin_order_totals_after_total', array( __CLASS__, 'admin_rows' ) );
	}

	/**
	 * Saves the full order value, the deposit, and the balance from the cart onto the order.
	 * Uses WooCommerce's order CRUD, so it works with both order storage types.
	 *
	 * @param WC_Order $order Order being created.
	 */
	public static function save_split( $order ) {
		if ( ! $order instanceof WC_Order || ! WC()->cart ) {
			return;
		}
		$fees = WC()->cart->get_fees();
		if ( ! isset( $fees[ HDWC_Deposit::FEE_ID ] ) ) {
			$order->delete_meta_data( self::META_ORDER_VALUE );
			$order->delete_meta_data( self::META_DEPOSIT );
			$order->delete_meta_data( self::META_BALANCE );
			return;
		}
		$decimals = wc_get_price_decimals();
		$balance  = round( abs( (float) $fees[ HDWC_Deposit::FEE_ID ]->total ), $decimals );
		$deposit  = round( (float) WC()->cart->get_total( 'edit' ), $decimals );

		$order->update_meta_data( self::META_ORDER_VALUE, wc_format_decimal( $deposit + $balance, $decimals ) );
		$order->update_meta_data( self::META_DEPOSIT, wc_format_decimal( $deposit, $decimals ) );
		$order->update_meta_data( self::META_BALANCE, wc_format_decimal( $balance, $decimals ) );
	}

	/**
	 * The saved split for an order, or null.
	 *
	 * @param WC_Order $order Order.
	 * @return array|null
	 */
	public static function get_split( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return null;
		}
		if ( '' !== (string) $order->get_meta( self::META_BALANCE ) ) {
			return array(
				'order_value' => (float) $order->get_meta( self::META_ORDER_VALUE ),
				'deposit'     => (float) $order->get_meta( self::META_DEPOSIT ),
				'balance'     => (float) $order->get_meta( self::META_BALANCE ),
			);
		}
		// Orders placed with version 2.0.0 and earlier saved the split under the old "_fdwc_" keys.
		if ( '' !== (string) $order->get_meta( '_fdwc_balance_due' ) ) {
			return array(
				'order_value' => (float) $order->get_meta( '_fdwc_order_value' ),
				'deposit'     => (float) $order->get_meta( '_fdwc_deposit' ),
				'balance'     => (float) $order->get_meta( '_fdwc_balance_due' ),
			);
		}
		return null;
	}

	/**
	 * Label for the deposit row, depending on whether the order has been paid.
	 *
	 * @param WC_Order $order Order.
	 * @return string
	 */
	private static function deposit_label( $order ) {
		return $order->is_paid() ? __( 'Deposit paid:', 'halfway-deposits' ) : __( 'Deposit due now:', 'halfway-deposits' );
	}

	/**
	 * Adds order value, deposit, and balance rows after the order total. Used on the
	 * order received page, My Account order details, and order emails.
	 *
	 * @param array    $rows  Totals rows.
	 * @param WC_Order $order Order.
	 * @return array
	 */
	public static function totals_rows( $rows, $order ) {
		$split = self::get_split( $order );
		if ( ! $split ) {
			return $rows;
		}
		$currency = array( 'currency' => $order->get_currency() );
		$extra    = array(
			'hdwc_order_value' => array(
				'label' => __( 'Order value:', 'halfway-deposits' ),
				'value' => wc_price( $split['order_value'], $currency ),
			),
			'hdwc_deposit'     => array(
				'label' => self::deposit_label( $order ),
				'value' => wc_price( $split['deposit'], $currency ),
			),
			'hdwc_balance_due' => array(
				'label' => __( 'Balance due:', 'halfway-deposits' ),
				'value' => wc_price( $split['balance'], $currency ),
			),
		);

		$position = array_search( 'order_total', array_keys( $rows ), true );
		if ( false === $position ) {
			return $rows + $extra;
		}
		return array_slice( $rows, 0, $position + 1, true ) + $extra + array_slice( $rows, $position + 1, null, true );
	}

	/**
	 * Adds the same rows to the totals on the order edit screen.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function admin_rows( $order_id ) {
		$order = wc_get_order( $order_id );
		$split = self::get_split( $order );
		if ( ! $split ) {
			return;
		}
		$currency = array( 'currency' => $order->get_currency() );
		$rows     = array(
			array( __( 'Order value:', 'halfway-deposits' ), $split['order_value'] ),
			array( self::deposit_label( $order ), $split['deposit'] ),
			array( __( 'Balance due:', 'halfway-deposits' ), $split['balance'] ),
		);
		foreach ( $rows as $row ) {
			?>
			<tr class="hdwc-admin-total">
				<td class="label"><?php echo esc_html( $row[0] ); ?></td>
				<td width="1%"></td>
				<td class="total"><?php echo wp_kses_post( wc_price( $row[1], $currency ) ); ?></td>
			</tr>
			<?php
		}
	}
}
