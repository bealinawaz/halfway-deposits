<?php
/**
 * Balance requests: a linked "balance order" the customer pays on WooCommerce's own payment page.
 *
 * @package Halfway_Deposits
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates the balance order, emails the payment link, and marks the deposit order when the balance is paid.
 *
 * The deposit order stays as it is. The balance order is a normal WooCommerce order with one
 * untaxed fee line, so it can be paid with any payment method on the standard "pay for order"
 * page, edited before sending (for example to add delivery), and paid again after a failed attempt.
 */
final class HDWC_Balance {

	const META_BALANCE_ORDER = '_hdwc_balance_order_id';
	const META_DEPOSIT_ORDER = '_hdwc_deposit_order_id';
	const META_PAID          = '_hdwc_balance_paid';
	const ITEM_KEY           = '_hdwc_balance_request';

	const ACTION_CREATE = 'hdwc_create_balance';
	const ACTION_SEND   = 'hdwc_send_balance';
	const BULK_ACTION   = 'hdwc_request_balance';

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'status_changed' ), 10, 4 );
		add_filter( 'woocommerce_my_account_my_orders_actions', array( __CLASS__, 'account_actions' ), 10, 2 );
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'customer_box' ) );
		add_filter( 'woocommerce_get_order_item_totals', array( __CLASS__, 'balance_order_totals' ), 20, 2 );
		add_filter( 'woocommerce_get_item_count', array( __CLASS__, 'balance_order_item_count' ), 10, 3 );

		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ), 30, 2 );
			add_action( 'admin_post_' . self::ACTION_CREATE, array( __CLASS__, 'handle_create' ) );
			add_action( 'admin_post_' . self::ACTION_SEND, array( __CLASS__, 'handle_send' ) );
			add_filter( 'woocommerce_order_actions', array( __CLASS__, 'order_actions' ), 10, 2 );
			add_action( 'woocommerce_order_action_' . self::BULK_ACTION, array( __CLASS__, 'order_action' ) );
			foreach ( array( 'woocommerce_page_wc-orders', 'edit-shop_order' ) as $screen ) {
				add_filter( 'bulk_actions-' . $screen, array( __CLASS__, 'bulk_actions' ) );
				add_filter( 'handle_bulk_actions-' . $screen, array( __CLASS__, 'handle_bulk' ), 10, 3 );
			}
			add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
		}
	}

	/*
	 * ------------------------------------------------------------------
	 * State
	 * ------------------------------------------------------------------
	 */

	/**
	 * The balance order linked to a deposit order, or null. Cancelled, deleted or trashed
	 * balance orders don't count, so a new one can be created.
	 *
	 * @param WC_Order $order Deposit order.
	 * @return WC_Order|null
	 */
	public static function balance_order( $order ) {
		$id = absint( $order->get_meta( self::META_BALANCE_ORDER ) );
		if ( ! $id ) {
			return null;
		}
		$balance = wc_get_order( $id );
		if ( ! $balance instanceof WC_Order || in_array( $balance->get_status(), array( 'cancelled', 'trash' ), true ) ) {
			return null;
		}
		return $balance;
	}

	/**
	 * Whether the balance of a deposit order has been paid.
	 *
	 * @param WC_Order $order Deposit order.
	 * @return bool
	 */
	public static function is_paid( $order ) {
		return '' !== (string) $order->get_meta( self::META_PAID );
	}

	/**
	 * Whether a balance order is waiting for the customer's payment.
	 *
	 * @param WC_Order|null $balance Balance order.
	 * @return bool
	 */
	private static function awaiting_payment( $balance ) {
		return $balance instanceof WC_Order && $balance->needs_payment();
	}

	/**
	 * Why a balance can't be requested for an order, or an empty string when it can.
	 *
	 * @param WC_Order $order Deposit order.
	 * @return string
	 */
	public static function cannot_request( $order ) {
		$split = HDWC_Orders::get_split( $order );
		if ( ! $split || $split['balance'] <= 0 ) {
			return __( 'This order has no balance due.', 'halfway-deposits' );
		}
		if ( self::is_paid( $order ) ) {
			return __( 'The balance of this order is already paid.', 'halfway-deposits' );
		}
		if ( self::balance_order( $order ) ) {
			return __( 'A balance order already exists for this order.', 'halfway-deposits' );
		}
		if ( ! $order->is_paid() ) {
			return __( 'Request the balance once the deposit has been paid.', 'halfway-deposits' );
		}
		return '';
	}

	/*
	 * ------------------------------------------------------------------
	 * Creating and sending
	 * ------------------------------------------------------------------
	 */

	/**
	 * Creates the balance order for a deposit order.
	 *
	 * @param WC_Order $order Deposit order.
	 * @return WC_Order|WP_Error
	 */
	public static function create( $order ) {
		$reason = self::cannot_request( $order );
		if ( '' !== $reason ) {
			return new WP_Error( 'hdwc_cannot_request', $reason );
		}
		$split = HDWC_Orders::get_split( $order );

		$balance = wc_create_order(
			array(
				'customer_id' => $order->get_customer_id(),
				'created_via' => 'halfway-deposits',
			)
		);
		if ( is_wp_error( $balance ) ) {
			return $balance;
		}

		$balance->set_currency( $order->get_currency() );
		$balance->set_prices_include_tax( $order->get_prices_include_tax() );
		$balance->set_address( $order->get_address( 'billing' ), 'billing' );
		$balance->set_address( $order->get_address( 'shipping' ), 'shipping' );
		$balance->set_customer_note( '' );

		$item = new WC_Order_Item_Fee();
		/* translators: %s: deposit order number. */
		$item->set_name( sprintf( __( 'Balance for order #%s', 'halfway-deposits' ), $order->get_order_number() ) );
		$item->set_tax_status( 'none' );
		$item->set_tax_class( '' );
		$item->set_amount( wc_format_decimal( $split['balance'] ) );
		$item->set_total( wc_format_decimal( $split['balance'] ) );
		$item->add_meta_data( self::ITEM_KEY, 'yes', true );
		$balance->add_item( $item );

		$balance->update_meta_data( self::META_DEPOSIT_ORDER, $order->get_id() );
		$balance->calculate_totals( false );
		$balance->set_status( 'pending' );
		/* translators: %s: deposit order number. */
		$balance->add_order_note( sprintf( __( 'Balance order for deposit order #%s, created by Halfway.', 'halfway-deposits' ), $order->get_order_number() ), 0, true );
		$balance->save();

		$order->update_meta_data( self::META_BALANCE_ORDER, $balance->get_id() );
		/* translators: %s: balance order number. */
		$order->add_order_note( sprintf( __( 'Balance order #%s created for the balance due.', 'halfway-deposits' ), $balance->get_order_number() ), 0, true );
		$order->save();

		/**
		 * Fires after a balance order is created.
		 *
		 * @param WC_Order $balance Balance order.
		 * @param WC_Order $order   Deposit order.
		 */
		do_action( 'hdwc_balance_order_created', $balance, $order );

		return $balance;
	}

	/**
	 * Emails the payment link for a balance order, using WooCommerce's "Order details" (customer invoice) email.
	 *
	 * @param WC_Order $order Deposit order.
	 * @return true|WP_Error
	 */
	public static function send( $order ) {
		$balance = self::balance_order( $order );
		if ( ! self::awaiting_payment( $balance ) ) {
			return new WP_Error( 'hdwc_nothing_to_send', __( 'There is no unpaid balance order to send.', 'halfway-deposits' ) );
		}
		if ( ! $balance->get_billing_email() ) {
			return new WP_Error( 'hdwc_no_email', __( 'The order has no billing email address. Copy the payment link and send it to the customer yourself.', 'halfway-deposits' ) );
		}

		WC()->payment_gateways();
		WC()->shipping();
		$emails = WC()->mailer()->get_emails();
		if ( empty( $emails['WC_Email_Customer_Invoice'] ) ) {
			return new WP_Error( 'hdwc_no_mailer', __( 'The WooCommerce order details email is not available.', 'halfway-deposits' ) );
		}
		$emails['WC_Email_Customer_Invoice']->trigger( $balance->get_id(), $balance );

		$balance->add_order_note( __( 'Payment link for the balance emailed to the customer.', 'halfway-deposits' ), 0, true );
		/* translators: %s: balance order number. */
		$order->add_order_note( sprintf( __( 'Balance payment link emailed to the customer (order #%s).', 'halfway-deposits' ), $balance->get_order_number() ), 0, true );
		return true;
	}

	/**
	 * Creates the balance order if needed and emails the payment link.
	 *
	 * @param WC_Order $order Deposit order.
	 * @return true|WP_Error
	 */
	public static function request( $order ) {
		if ( ! self::awaiting_payment( self::balance_order( $order ) ) ) {
			$created = self::create( $order );
			if ( is_wp_error( $created ) ) {
				return $created;
			}
		}
		return self::send( $order );
	}

	/*
	 * ------------------------------------------------------------------
	 * Payment
	 * ------------------------------------------------------------------
	 */

	/**
	 * Marks the deposit order when its balance order is paid, and clears it again if the balance order is
	 * refunded or cancelled.
	 *
	 * @param int      $order_id Order ID.
	 * @param string   $from     Old status.
	 * @param string   $to       New status.
	 * @param WC_Order $order    Order.
	 */
	public static function status_changed( $order_id, $from, $to, $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$deposit_id = absint( $order->get_meta( self::META_DEPOSIT_ORDER ) );
		$deposit    = $deposit_id ? wc_get_order( $deposit_id ) : null;
		if ( ! $deposit instanceof WC_Order || absint( $deposit->get_meta( self::META_BALANCE_ORDER ) ) !== $order->get_id() ) {
			return;
		}

		$paid_statuses = wc_get_is_paid_statuses();
		/**
		 * Filters whether paying this balance order settles the balance of the deposit order.
		 *
		 * @since 2.2.2
		 *
		 * @param bool     $settles Whether the balance is settled when this order is paid.
		 * @param WC_Order $deposit Deposit order.
		 * @param WC_Order $order   Balance order.
		 */
		if ( in_array( $to, $paid_statuses, true ) && ! self::is_paid( $deposit ) && apply_filters( 'hdwc_balance_order_settles_deposit', true, $deposit, $order ) ) {
			$deposit->update_meta_data( self::META_PAID, gmdate( 'Y-m-d H:i:s' ) );
			/* translators: %s: balance order number. */
			$deposit->add_order_note( sprintf( __( 'Balance paid in order #%s.', 'halfway-deposits' ), $order->get_order_number() ) );
			$deposit->save();
			$status = HDWC_Settings::status_after_balance();
			if ( '' !== $status && $deposit->get_status() !== $status && $deposit->has_status( wc_get_is_paid_statuses() ) ) {
				$deposit->update_status( $status, __( 'Balance paid.', 'halfway-deposits' ) );
			}

			/**
			 * Fires when the balance of a deposit order is paid.
			 *
			 * @param WC_Order $deposit Deposit order.
			 * @param WC_Order $order   Balance order.
			 */
			do_action( 'hdwc_balance_paid', $deposit, $order );
		} elseif ( in_array( $to, array( 'refunded', 'cancelled' ), true ) && self::is_paid( $deposit ) ) {
			$deposit->delete_meta_data( self::META_PAID );
			/* translators: 1: balance order number, 2: new status. */
			$deposit->add_order_note( sprintf( __( 'Balance order #%1$s is now %2$s, so the balance is due again.', 'halfway-deposits' ), $order->get_order_number(), wc_get_order_status_name( $to ) ) );
			$deposit->save();
		}
	}

	/*
	 * ------------------------------------------------------------------
	 * Customer side
	 * ------------------------------------------------------------------
	 */

	/**
	 * Adds a "Pay balance" button to the deposit order in My Account → Orders.
	 *
	 * @param array    $actions Actions.
	 * @param WC_Order $order   Order.
	 * @return array
	 */
	public static function account_actions( $actions, $order ) {
		$balance = self::balance_order( $order );
		if ( self::awaiting_payment( $balance ) ) {
			$actions['hdwc-pay-balance'] = array(
				'url'  => $balance->get_checkout_payment_url(),
				'name' => __( 'Pay balance', 'halfway-deposits' ),
			);
		}
		return $actions;
	}

	/**
	 * Whether an order is a balance order created by Halfway.
	 *
	 * @param mixed $order Order.
	 * @return bool
	 */
	public static function is_balance_order( $order ) {
		return $order instanceof WC_Order && absint( $order->get_meta( self::META_DEPOSIT_ORDER ) ) > 0;
	}

	/**
	 * Hides the empty "Subtotal: $0.00" row on balance orders, which have only the balance line.
	 *
	 * @param array    $rows  Totals rows.
	 * @param WC_Order $order Order.
	 * @return array
	 */
	public static function balance_order_totals( $rows, $order ) {
		if ( self::is_balance_order( $order ) && isset( $rows['cart_subtotal'] ) && 0.0 === (float) $order->get_subtotal() ) {
			unset( $rows['cart_subtotal'] );
		}
		return $rows;
	}

	/**
	 * Counts the balance line as one item, so My Account doesn't say "for 0 items".
	 *
	 * @param int      $count Item count.
	 * @param string   $type  Item type.
	 * @param WC_Order $order Order.
	 * @return int
	 */
	public static function balance_order_item_count( $count, $type, $order ) {
		return ( 0 === (int) $count && self::is_balance_order( $order ) ) ? 1 : $count;
	}

	/**
	 * Shows the balance status, with a payment button, on the deposit order's details page
	 * (order received page and My Account), and a link back on the balance order.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function customer_box( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$deposit_id = absint( $order->get_meta( self::META_DEPOSIT_ORDER ) );
		if ( $deposit_id ) {
			$deposit = wc_get_order( $deposit_id );
			if ( $deposit instanceof WC_Order ) {
				echo '<p class="hdwc-balance-for">';
				/* translators: %s: deposit order number. */
				echo esc_html( sprintf( __( 'This is the balance payment for order #%s.', 'halfway-deposits' ), $deposit->get_order_number() ) );
				echo '</p>';
			}
			return;
		}

		$split = HDWC_Orders::get_split( $order );
		if ( ! $split ) {
			return;
		}
		$balance = self::balance_order( $order );
		echo '<section class="hdwc-balance-status">';
		if ( self::is_paid( $order ) ) {
			echo '<p>' . esc_html__( 'The balance of this order has been paid. Thank you.', 'halfway-deposits' ) . '</p>';
		} elseif ( self::awaiting_payment( $balance ) ) {
			echo '<p>';
			/* translators: %s: balance amount. */
			echo wp_kses_post( sprintf( __( 'A balance of %s is ready to pay.', 'halfway-deposits' ), wc_price( $balance->get_total(), array( 'currency' => $balance->get_currency() ) ) ) );
			echo ' <a class="button wc-forward" href="' . esc_url( $balance->get_checkout_payment_url() ) . '">' . esc_html__( 'Pay balance', 'halfway-deposits' ) . '</a></p>';
		}
		echo '</section>';
	}

	/*
	 * ------------------------------------------------------------------
	 * Admin
	 * ------------------------------------------------------------------
	 */

	/**
	 * Adds the "Deposit balance" box to the order edit screen of deposit and balance orders.
	 *
	 * @param string           $screen_id Screen ID.
	 * @param WC_Order|WP_Post $order_or_post Order or post.
	 */
	public static function add_meta_box( $screen_id, $order_or_post = null ) {
		if ( ! in_array( $screen_id, array( 'woocommerce_page_wc-orders', 'shop_order' ), true ) ) {
			return;
		}
		$order = $order_or_post instanceof WC_Order ? $order_or_post : ( $order_or_post instanceof WP_Post ? wc_get_order( $order_or_post->ID ) : null );
		if ( ! $order instanceof WC_Order || ( ! HDWC_Orders::get_split( $order ) && ! $order->get_meta( self::META_DEPOSIT_ORDER ) ) ) {
			return;
		}
		add_meta_box( 'hdwc-balance', __( 'Deposit balance', 'halfway-deposits' ), array( __CLASS__, 'render_meta_box' ), $screen_id, 'side', 'high' );
	}

	/**
	 * Link to an admin-post action for an order.
	 *
	 * @param string $action   Action.
	 * @param int    $order_id Order ID.
	 * @param array  $args     Extra query args.
	 * @return string
	 */
	private static function action_url( $action, $order_id, $args = array() ) {
		$url = add_query_arg(
			array_merge(
				array(
					'action'   => $action,
					'order_id' => $order_id,
				),
				$args
			),
			admin_url( 'admin-post.php' )
		);
		return wp_nonce_url( $url, $action . '_' . $order_id );
	}

	/**
	 * Prints the "Deposit balance" box.
	 *
	 * @param WC_Order|WP_Post $order_or_post Order or post.
	 */
	public static function render_meta_box( $order_or_post ) {
		$order = $order_or_post instanceof WC_Order ? $order_or_post : wc_get_order( $order_or_post->ID );
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		echo '<div class="hdwc-balance-box">';

		$deposit_id = absint( $order->get_meta( self::META_DEPOSIT_ORDER ) );
		if ( $deposit_id ) {
			$deposit = wc_get_order( $deposit_id );
			echo '<p>';
			if ( $deposit instanceof WC_Order ) {
				printf(
					/* translators: %s: link to the deposit order. */
					esc_html__( 'Balance payment for deposit order %s.', 'halfway-deposits' ),
					'<a href="' . esc_url( $deposit->get_edit_order_url() ) . '">#' . esc_html( $deposit->get_order_number() ) . '</a>'
				);
			} else {
				esc_html_e( 'Balance payment for a deposit order that no longer exists.', 'halfway-deposits' );
			}
			echo '</p>';
			if ( $order->needs_payment() ) {
				self::print_link_field( $order );
				echo '<p class="description">' . esc_html__( 'You can edit this order before sending the link, for example to add delivery. Click Recalculate after changing it.', 'halfway-deposits' ) . '</p>';
			}
			echo '</div>';
			return;
		}

		$split    = HDWC_Orders::get_split( $order );
		$balance  = self::balance_order( $order );
		$currency = array( 'currency' => $order->get_currency() );

		if ( self::is_paid( $order ) ) {
			echo '<p><mark class="order-status status-completed"><span>' . esc_html__( 'Balance paid', 'halfway-deposits' ) . '</span></mark></p>';
			if ( $balance ) {
				/* translators: %s: link to the balance order. */
				printf( '<p>' . esc_html__( 'Paid in order %s.', 'halfway-deposits' ) . '</p>', '<a href="' . esc_url( $balance->get_edit_order_url() ) . '">#' . esc_html( $balance->get_order_number() ) . '</a>' );
			}
		} elseif ( $balance ) {
			echo '<p>';
			printf(
				/* translators: 1: link to the balance order, 2: amount, 3: order status. */
				esc_html__( 'Balance order %1$s: %2$s, %3$s.', 'halfway-deposits' ),
				'<a href="' . esc_url( $balance->get_edit_order_url() ) . '">#' . esc_html( $balance->get_order_number() ) . '</a>',
				wp_kses_post( wc_price( $balance->get_total(), array( 'currency' => $balance->get_currency() ) ) ),
				esc_html( wc_get_order_status_name( $balance->get_status() ) )
			);
			echo '</p>';
			if ( $balance->needs_payment() ) {
				self::print_link_field( $balance );
				echo '<p><a class="button button-primary" href="' . esc_url( self::action_url( self::ACTION_SEND, $order->get_id() ) ) . '">' . esc_html__( 'Email payment link', 'halfway-deposits' ) . '</a></p>';
			}
		} else {
			$reason = self::cannot_request( $order );
			/* translators: %s: balance amount. */
			echo '<p>' . wp_kses_post( sprintf( __( 'Balance due: %s', 'halfway-deposits' ), wc_price( $split ? $split['balance'] : 0, $currency ) ) ) . '</p>';
			if ( '' !== $reason ) {
				echo '<p class="description">' . esc_html( $reason ) . '</p>';
			} else {
				echo '<p><a class="button button-primary" href="' . esc_url( self::action_url( self::ACTION_CREATE, $order->get_id(), array( 'send' => 1 ) ) ) . '">' . esc_html__( 'Request balance payment', 'halfway-deposits' ) . '</a></p>';
				echo '<p class="description">' . esc_html__( 'Creates a balance order and emails the customer a link to pay it with any of your payment methods.', 'halfway-deposits' ) . '</p>';
				echo '<p><a href="' . esc_url( self::action_url( self::ACTION_CREATE, $order->get_id() ) ) . '">' . esc_html__( 'Create the balance order without emailing', 'halfway-deposits' ) . '</a></p>';
			}
		}
		echo '</div>';
	}

	/**
	 * Prints a read-only payment link field.
	 *
	 * @param WC_Order $balance Balance order.
	 */
	private static function print_link_field( $balance ) {
		$id = 'hdwc-pay-link-' . $balance->get_id();
		echo '<p><label for="' . esc_attr( $id ) . '">' . esc_html__( 'Payment link', 'halfway-deposits' ) . '</label>';
		echo '<input type="text" readonly class="widefat" id="' . esc_attr( $id ) . '" value="' . esc_attr( $balance->get_checkout_payment_url() ) . '" onfocus="this.select()" /></p>';
	}

	/**
	 * The order from an admin-post request, after checking the nonce and permission.
	 *
	 * @param string $action Action.
	 * @return WC_Order
	 */
	private static function request_order( $action ) {
		$order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0;
		check_admin_referer( $action . '_' . $order_id );
		if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to edit orders.', 'halfway-deposits' ), 403 );
		}
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			wp_die( esc_html__( 'Order not found.', 'halfway-deposits' ), 404 );
		}
		return $order;
	}

	/**
	 * Sends the browser back to the order with a result message.
	 *
	 * @param WC_Order      $order  Order to open.
	 * @param string        $result Message key.
	 * @param WP_Error|null $error  Error.
	 */
	private static function redirect( $order, $result, $error = null ) {
		$args = array( 'hdwc_result' => $result );
		if ( $error instanceof WP_Error ) {
			set_transient( 'hdwc_error_' . get_current_user_id(), $error->get_error_message(), 60 );
		}
		wp_safe_redirect( add_query_arg( $args, $order->get_edit_order_url() ) );
		exit;
	}

	/**
	 * Handles "Request balance payment" and "Create the balance order without emailing".
	 */
	public static function handle_create() {
		$order = self::request_order( self::ACTION_CREATE );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checked in request_order().
		$send    = ! empty( $_GET['send'] );
		$created = self::create( $order );
		if ( is_wp_error( $created ) ) {
			self::redirect( $order, 'error', $created );
		}
		if ( ! $send ) {
			self::redirect( $created, 'created' );
		}
		$sent = self::send( $order );
		self::redirect( $order, is_wp_error( $sent ) ? 'error' : 'sent', is_wp_error( $sent ) ? $sent : null );
	}

	/**
	 * Handles "Email payment link".
	 */
	public static function handle_send() {
		$order = self::request_order( self::ACTION_SEND );
		$sent  = self::send( $order );
		self::redirect( $order, is_wp_error( $sent ) ? 'error' : 'sent', is_wp_error( $sent ) ? $sent : null );
	}

	/**
	 * Adds "Request balance payment" to the Order actions box.
	 *
	 * @param array         $actions Actions.
	 * @param WC_Order|null $order   Order.
	 * @return array
	 */
	public static function order_actions( $actions, $order = null ) {
		if ( $order instanceof WC_Order && HDWC_Orders::get_split( $order ) && ! self::is_paid( $order ) && ( self::awaiting_payment( self::balance_order( $order ) ) || '' === self::cannot_request( $order ) ) ) {
			$actions[ self::BULK_ACTION ] = __( 'Request balance payment', 'halfway-deposits' );
		}
		return $actions;
	}

	/**
	 * Runs "Request balance payment" from the Order actions box.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function order_action( $order ) {
		$result = self::request( $order );
		if ( is_wp_error( $result ) ) {
			WC_Admin_Meta_Boxes::add_error( $result->get_error_message() );
		}
	}

	/**
	 * Adds the bulk action to the Orders list.
	 *
	 * @param array $actions Bulk actions.
	 * @return array
	 */
	public static function bulk_actions( $actions ) {
		$actions[ self::BULK_ACTION ] = __( 'Request balance payment', 'halfway-deposits' );
		return $actions;
	}

	/**
	 * Runs the bulk action. Orders without a balance due are skipped.
	 *
	 * @param string $redirect Redirect URL.
	 * @param string $action   Action.
	 * @param array  $ids      Order IDs.
	 * @return string
	 */
	public static function handle_bulk( $redirect, $action, $ids ) {
		if ( self::BULK_ACTION !== $action ) {
			return $redirect;
		}
		$sent    = 0;
		$skipped = 0;
		foreach ( array_map( 'absint', (array) $ids ) as $id ) {
			$order = wc_get_order( $id );
			if ( $order instanceof WC_Order && ! is_wp_error( self::request( $order ) ) ) {
				++$sent;
			} else {
				++$skipped;
			}
		}
		return add_query_arg(
			array(
				'hdwc_bulk_sent'    => $sent,
				'hdwc_bulk_skipped' => $skipped,
			),
			$redirect
		);
	}

	/**
	 * Shows the result of a balance action.
	 */
	public static function admin_notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Only reads result flags for a message.
		if ( isset( $_GET['hdwc_bulk_sent'] ) ) {
			$sent    = absint( wp_unslash( $_GET['hdwc_bulk_sent'] ) );
			$skipped = isset( $_GET['hdwc_bulk_skipped'] ) ? absint( wp_unslash( $_GET['hdwc_bulk_skipped'] ) ) : 0;
			/* translators: %d: number of orders. */
			$message = sprintf( _n( 'Balance payment link sent for %d order.', 'Balance payment links sent for %d orders.', $sent, 'halfway-deposits' ), $sent );
			if ( $skipped ) {
				/* translators: %d: number of orders. */
				$message .= ' ' . sprintf( _n( '%d order was skipped because it has no unpaid balance or no email address.', '%d orders were skipped because they have no unpaid balance or no email address.', $skipped, 'halfway-deposits' ), $skipped );
			}
			printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', $sent ? 'success' : 'warning', esc_html( $message ) );
			return;
		}
		if ( ! isset( $_GET['hdwc_result'] ) ) {
			return;
		}
		$result = sanitize_key( wp_unslash( $_GET['hdwc_result'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$messages = array(
			'sent'    => __( 'The balance payment link was emailed to the customer.', 'halfway-deposits' ),
			'created' => __( 'Balance order created. Edit it if needed, then send the payment link from the Deposit balance box.', 'halfway-deposits' ),
		);
		if ( 'error' === $result ) {
			$error = get_transient( 'hdwc_error_' . get_current_user_id() );
			delete_transient( 'hdwc_error_' . get_current_user_id() );
			printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $error ? $error : __( 'The balance could not be requested.', 'halfway-deposits' ) ) );
		} elseif ( isset( $messages[ $result ] ) ) {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $messages[ $result ] ) );
		}
	}
}
