<?php
/**
 * WooCommerce → Settings → Deposit tab.
 *
 * @package Fixed_Deposit_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings tab. WooCommerce renders, saves, and cleans the fields.
 */
final class FDWC_Settings_Page extends WC_Settings_Page {

	/**
	 * Sets up the tab.
	 */
	public function __construct() {
		$this->id    = 'fdwc';
		$this->label = __( 'Deposit', 'fixed-deposit-for-woocommerce' );
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
				'title' => __( 'Deposit at checkout', 'fixed-deposit-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Customers pay a deposit at checkout. The rest of the order appears as a negative line in the cart, checkout, and order, and is saved as the balance due on the order. Collecting the balance is up to you, for example by creating a new order for the balance and sending the customer its payment link.', 'fixed-deposit-for-woocommerce' ),
				'id'    => 'fdwc_options',
			),
			array(
				'title'   => __( 'Enable deposits', 'fixed-deposit-for-woocommerce' ),
				'desc'    => __( 'Take a deposit instead of the full order total', 'fixed-deposit-for-woocommerce' ),
				'id'      => FDWC_Settings::ENABLED,
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'title'   => __( 'Deposit type', 'fixed-deposit-for-woocommerce' ),
				'id'      => FDWC_Settings::TYPE,
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'percent',
				'options' => array(
					'percent' => __( 'Percentage of the order', 'fixed-deposit-for-woocommerce' ),
					'fixed'   => __( 'Fixed amount', 'fixed-deposit-for-woocommerce' ),
				),
			),
			array(
				'title'             => __( 'Deposit amount', 'fixed-deposit-for-woocommerce' ),
				'desc'              => __( 'A percentage (for example 25) or an amount in your store currency.', 'fixed-deposit-for-woocommerce' ),
				'id'                => FDWC_Settings::AMOUNT,
				'type'              => 'number',
				'default'           => '',
				'custom_attributes' => array(
					'min'  => '0',
					'step' => 'any',
				),
			),
			array(
				'title'   => __( 'Shipping', 'fixed-deposit-for-woocommerce' ),
				'id'      => FDWC_Settings::SHIPPING,
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'upfront',
				'options' => array(
					'upfront' => __( 'Charge shipping in full with the deposit', 'fixed-deposit-for-woocommerce' ),
					'split'   => __( 'Include shipping in the order value the deposit is taken from', 'fixed-deposit-for-woocommerce' ),
				),
			),
			array(
				'title'             => __( 'Minimum order value', 'fixed-deposit-for-woocommerce' ),
				'desc'              => __( 'Only take a deposit when the products in the cart (after discounts, including tax) cost at least this much. Leave empty for no minimum.', 'fixed-deposit-for-woocommerce' ),
				'id'                => FDWC_Settings::MIN_TOTAL,
				'type'              => 'number',
				'default'           => '',
				'custom_attributes' => array(
					'min'  => '0',
					'step' => 'any',
				),
			),
			array(
				'title'   => __( 'Apply to', 'fixed-deposit-for-woocommerce' ),
				'id'      => FDWC_Settings::SCOPE,
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'all',
				'options' => array(
					'all'        => __( 'All orders', 'fixed-deposit-for-woocommerce' ),
					'categories' => __( 'Orders with a product from the categories below', 'fixed-deposit-for-woocommerce' ),
				),
			),
			array(
				'title'   => __( 'Categories', 'fixed-deposit-for-woocommerce' ),
				'desc'    => __( 'When any product in the cart is in one of these categories (or their subcategories), the deposit applies to the whole order.', 'fixed-deposit-for-woocommerce' ),
				'id'      => FDWC_Settings::CATEGORIES,
				'type'    => 'multiselect',
				'class'   => 'wc-enhanced-select',
				'default' => array(),
				'options' => $this->category_options(),
			),
			array(
				'title'       => __( 'Balance line label', 'fixed-deposit-for-woocommerce' ),
				'id'          => FDWC_Settings::LABEL,
				'type'        => 'text',
				'default'     => '',
				'placeholder' => FDWC_Settings::default_label(),
			),
			array(
				'title'       => __( 'Cart and checkout notice', 'fixed-deposit-for-woocommerce' ),
				'id'          => FDWC_Settings::NOTICE,
				'type'        => 'textarea',
				'default'     => '',
				'placeholder' => FDWC_Settings::default_notice(),
				'css'         => 'min-height: 80px;',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'fdwc_options',
			),
		);
	}

	/**
	 * Renders the settings and the support box.
	 */
	public function output() {
		parent::output();
		FDWC_Admin::render_support();
	}
}
