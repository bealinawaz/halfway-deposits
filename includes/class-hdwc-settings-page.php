<?php
/**
 * WooCommerce → Settings → Deposit tab.
 *
 * @package Halfway_Deposits
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings tab. WooCommerce renders, saves, and cleans the fields.
 */
final class HDWC_Settings_Page extends WC_Settings_Page {

	/**
	 * Sets up the tab.
	 */
	public function __construct() {
		$this->id    = 'hdwc';
		$this->label = __( 'Deposit', 'halfway-deposits' );
		parent::__construct();
	}

	/**
	 * Product category choices.
	 *
	 * @return array
	 */
	private function category_options() {
		$options = array();
		$terms   = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->term_id ] = $term->name;
			}
		}
		return $options;
	}

	/**
	 * Settings fields.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {
		return array(
			array(
				'title' => __( 'Deposit at checkout', 'halfway-deposits' ),
				'type'  => 'title',
				'desc'  => __( 'Customers pay a deposit at checkout. The rest of the order appears as a negative line in the cart, checkout, and order, and is saved as the balance due on the order. When you are ready for the rest, click Request balance payment on the order: the customer gets an email with a link to pay the balance.', 'halfway-deposits' ),
				'id'    => 'hdwc_options',
			),
			array(
				'title'   => __( 'Enable deposits', 'halfway-deposits' ),
				'desc'    => __( 'Take a deposit instead of the full order total', 'halfway-deposits' ),
				'id'      => HDWC_Settings::ENABLED,
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'title'   => __( 'Deposit type', 'halfway-deposits' ),
				'id'      => HDWC_Settings::TYPE,
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'percent',
				'options' => array(
					'percent' => __( 'Percentage of the order', 'halfway-deposits' ),
					'fixed'   => __( 'Fixed amount', 'halfway-deposits' ),
				),
			),
			array(
				'title'             => __( 'Deposit amount', 'halfway-deposits' ),
				'desc'              => __( 'A percentage (for example 25) or an amount in your store currency.', 'halfway-deposits' ),
				'id'                => HDWC_Settings::AMOUNT,
				'type'              => 'number',
				'default'           => '',
				'custom_attributes' => array(
					'min'  => '0',
					'step' => 'any',
				),
			),
			array(
				'title'   => __( 'Shipping', 'halfway-deposits' ),
				'id'      => HDWC_Settings::SHIPPING,
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'upfront',
				'options' => array(
					'upfront' => __( 'Charge shipping in full with the deposit', 'halfway-deposits' ),
					'split'   => __( 'Include shipping in the order value the deposit is taken from', 'halfway-deposits' ),
				),
			),
			array(
				'title'             => __( 'Minimum order value', 'halfway-deposits' ),
				'desc'              => __( 'Only take a deposit when the products in the cart (after discounts, including tax) cost at least this much. Leave empty for no minimum.', 'halfway-deposits' ),
				'id'                => HDWC_Settings::MIN_TOTAL,
				'type'              => 'number',
				'default'           => '',
				'custom_attributes' => array(
					'min'  => '0',
					'step' => 'any',
				),
			),
			array(
				'title'   => __( 'Apply to', 'halfway-deposits' ),
				'id'      => HDWC_Settings::SCOPE,
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'all',
				'options' => array(
					'all'        => __( 'All orders', 'halfway-deposits' ),
					'categories' => __( 'Orders with a product from the categories below', 'halfway-deposits' ),
				),
			),
			array(
				'title'   => __( 'Categories', 'halfway-deposits' ),
				'desc'    => __( 'When any product in the cart is in one of these categories (or their subcategories), the deposit applies to the whole order.', 'halfway-deposits' ),
				'id'      => HDWC_Settings::CATEGORIES,
				'type'    => 'multiselect',
				'class'   => 'wc-enhanced-select',
				'default' => array(),
				'options' => $this->category_options(),
			),
			array(
				'title'       => __( 'Balance line label', 'halfway-deposits' ),
				'id'          => HDWC_Settings::LABEL,
				'type'        => 'text',
				'default'     => '',
				'placeholder' => HDWC_Settings::default_label(),
			),
			array(
				'title'       => __( 'Cart and checkout notice', 'halfway-deposits' ),
				'id'          => HDWC_Settings::NOTICE,
				'type'        => 'textarea',
				'default'     => '',
				'placeholder' => HDWC_Settings::default_notice(),
				'css'         => 'min-height: 80px;',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'hdwc_options',
			),
			array(
				'title' => __( 'Balance payment', 'halfway-deposits' ),
				'type'  => 'title',
				'desc'  => __( 'Open an order that has a balance due and click "Request balance payment" in the Deposit balance box, or select several orders and use the bulk action. Halfway creates a balance order and emails the customer WooCommerce\'s "Order details" email with a link to pay it with any of your payment methods. Customers also see a "Pay balance" button in My Account.', 'halfway-deposits' ),
				'id'    => 'hdwc_balance_options',
			),
			array(
				'title'   => __( 'When the balance is paid', 'halfway-deposits' ),
				'id'      => HDWC_Settings::AFTER_PAID,
				'type'    => 'select',
				'default' => '',
				'desc'    => __( 'The balance order is always marked as paid. This setting decides what happens to the original deposit order.', 'halfway-deposits' ),
				'options' => array(
					''          => __( 'Keep the deposit order status', 'halfway-deposits' ),
					'completed' => __( 'Mark the deposit order as Completed', 'halfway-deposits' ),
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'hdwc_balance_options',
			),
		);
	}

	/**
	 * Renders the settings and the support box.
	 */
	public function output() {
		parent::output();
		HDWC_Admin::render_pro_box();
		HDWC_Admin::render_support();
	}
}
