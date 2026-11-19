<?php
/**
 * Settings tab registration and plugin links.
 *
 * @package Halfway_Deposits
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin hooks.
 */
final class HDWC_Admin {

	const TAB         = 'hdwc';
	const AUTHOR_URL  = 'https://bealinawaz.com/';
	const CONTACT_URL = 'https://bealinawaz.com/contact-me/';
	const DONATE_URL  = 'https://www.buymeacoffee.com/bealinawaz';

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( HDWC_FILE ), array( __CLASS__, 'action_links' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'row_meta' ), 10, 2 );
	}

	/**
	 * Adds the Deposit tab to WooCommerce → Settings.
	 *
	 * @param array $pages Settings pages.
	 * @return array
	 */
	public static function add_settings_page( $pages ) {
		require_once __DIR__ . '/class-hdwc-settings-page.php';
		$pages[] = new HDWC_Settings_Page();
		return $pages;
	}

	/**
	 * Settings screen URL.
	 *
	 * @return string
	 */
	public static function settings_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=' . self::TAB );
	}

	/**
	 * Loads the admin styles on the Deposit tab.
	 *
	 * @param string $hook Current admin page.
	 */
	public static function enqueue( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only reads which settings tab is open.
		if ( 'woocommerce_page_wc-settings' !== $hook || ! isset( $_GET['tab'] ) || self::TAB !== sanitize_key( wp_unslash( $_GET['tab'] ) ) ) {
			return;
		}
		wp_enqueue_style( 'hdwc-admin', plugin_dir_url( HDWC_FILE ) . 'assets/admin.css', array(), HDWC_VERSION );
	}

	/**
	 * Page of the Halfway Pro add-on on the author's site.
	 */
	const PRO_URL = 'https://bealinawaz.com/wordpress-plugins/pro/halfway/';

	/**
	 * Whether the Halfway Pro add-on is active. Checked when rendering, because Pro loads after the free plugin.
	 *
	 * @return bool
	 */
	public static function pro_active() {
		return defined( 'HDWC_PRO_VERSION' );
	}

	/**
	 * One short note about the Pro add-on on the Deposit settings tab, only while Pro isn't active.
	 */
	public static function render_pro_box() {
		if ( self::pro_active() ) {
			return;
		}
		?>
		<div class="hdwc-pro">
			<h2><?php esc_html_e( 'Need more? Halfway Pro', 'halfway-deposits' ); ?></h2>
			<ul class="ul-disc">
				<li><?php esc_html_e( 'Balance due dates for the store, a product or a single order, with reminder emails before, on and after the due date.', 'halfway-deposits' ); ?></li>
				<li><?php esc_html_e( 'Payment plans that split the balance into several payments, each with its own payment link and reminders.', 'halfway-deposits' ); ?></li>
				<li><?php esc_html_e( 'Deposit rules by product, customer role, billing country and payment method, with a minimum and maximum deposit.', 'halfway-deposits' ); ?></li>
			</ul>
			<p><a class="button" href="<?php echo esc_url( self::PRO_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'See Halfway Pro', 'halfway-deposits' ); ?></a></p>
		</div>
		<?php
	}

	/**
	 * Prints the support box below the settings.
	 */
	public static function render_support() {
		?>
		<div class="hdwc-support">
			<h2><?php esc_html_e( 'Need help or a custom version?', 'halfway-deposits' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %s: link to the author's website. */
					esc_html__( 'Halfway – Deposits & Partial Payments for WooCommerce is free and built by Ali Nawaz, a WordPress and WooCommerce developer at %s. Need the balance charged automatically with your payment gateway, or another custom change?', 'halfway-deposits' ),
					'<a href="' . esc_url( self::AUTHOR_URL ) . '" target="_blank" rel="noopener">bealinawaz.com</a>'
				);
				?>
			</p>
			<p class="hdwc-support__actions">
				<a class="button button-primary" href="<?php echo esc_url( self::CONTACT_URL ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Hire me for custom work', 'halfway-deposits' ); ?></a>
				<a class="button" href="<?php echo esc_url( self::AUTHOR_URL ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Visit bealinawaz.com', 'halfway-deposits' ); ?></a>
				<a class="button" href="<?php echo esc_url( self::DONATE_URL ); ?>" target="_blank" rel="noopener">&#9749; <?php esc_html_e( 'Buy me a coffee', 'halfway-deposits' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Adds a Settings link on the Plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( self::settings_url() ) . '">' . esc_html__( 'Settings', 'halfway-deposits' ) . '</a>' );
		return $links;
	}

	/**
	 * Adds custom development and donation links under the plugin description.
	 *
	 * @param array  $links Plugin meta links.
	 * @param string $file  Plugin file.
	 * @return array
	 */
	public static function row_meta( $links, $file ) {
		if ( plugin_basename( HDWC_FILE ) !== $file ) {
			return $links;
		}
		if ( ! self::pro_active() ) {
			$links[] = '<a href="' . esc_url( self::PRO_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Halfway Pro', 'halfway-deposits' ) . '</a>';
		}
		$links[] = '<a href="' . esc_url( self::CONTACT_URL ) . '" target="_blank" rel="noopener">' . esc_html__( 'Custom development', 'halfway-deposits' ) . '</a>';
		$links[] = '<a href="' . esc_url( self::DONATE_URL ) . '" target="_blank" rel="noopener">' . esc_html__( 'Buy me a coffee', 'halfway-deposits' ) . '</a>';
		return $links;
	}
}
