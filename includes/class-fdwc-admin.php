<?php
/**
 * Settings tab registration and plugin links.
 *
 * @package Fixed_Deposit_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin hooks.
 */
final class FDWC_Admin {

	const TAB         = 'fdwc';
	const AUTHOR_URL  = 'https://bealinawaz.com/';
	const CONTACT_URL = 'https://bealinawaz.com/contact-me/';
	const DONATE_URL  = 'https://www.buymeacoffee.com/bealinawaz';

	/**
	 * Registers hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( FDWC_FILE ), array( __CLASS__, 'action_links' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'row_meta' ), 10, 2 );
	}

	/**
	 * Adds the Deposit tab to WooCommerce → Settings.
	 *
	 * @param array $pages Settings pages.
	 * @return array
	 */
	public static function add_settings_page( $pages ) {
		require_once __DIR__ . '/class-fdwc-settings-page.php';
		$pages[] = new FDWC_Settings_Page();
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
		wp_enqueue_style( 'fdwc-admin', plugin_dir_url( FDWC_FILE ) . 'assets/admin.css', array(), FDWC_VERSION );
	}

	/**
	 * Prints the support box below the settings.
	 */
	public static function render_support() {
		?>
		<div class="fdwc-support">
			<h2><?php esc_html_e( 'Need help or a custom version?', 'fixed-deposit-for-woocommerce' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %s: link to the author's website. */
					esc_html__( 'Fixed Deposit for WooCommerce is free and built by Ali Nawaz, a WordPress and WooCommerce developer at %s. Need automatic balance payments, payment plans, or per-product deposit rules?', 'fixed-deposit-for-woocommerce' ),
					'<a href="' . esc_url( self::AUTHOR_URL ) . '" target="_blank" rel="noopener">bealinawaz.com</a>'
				);
				?>
			</p>
			<p class="fdwc-support__actions">
				<a class="button button-primary" href="<?php echo esc_url( self::CONTACT_URL ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Hire me for custom work', 'fixed-deposit-for-woocommerce' ); ?></a>
				<a class="button" href="<?php echo esc_url( self::AUTHOR_URL ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Visit bealinawaz.com', 'fixed-deposit-for-woocommerce' ); ?></a>
				<a class="button" href="<?php echo esc_url( self::DONATE_URL ); ?>" target="_blank" rel="noopener">&#9749; <?php esc_html_e( 'Buy me a coffee', 'fixed-deposit-for-woocommerce' ); ?></a>
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
		array_unshift( $links, '<a href="' . esc_url( self::settings_url() ) . '">' . esc_html__( 'Settings', 'fixed-deposit-for-woocommerce' ) . '</a>' );
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
		if ( plugin_basename( FDWC_FILE ) !== $file ) {
			return $links;
		}
		$links[] = '<a href="' . esc_url( self::CONTACT_URL ) . '" target="_blank" rel="noopener">' . esc_html__( 'Custom development', 'fixed-deposit-for-woocommerce' ) . '</a>';
		$links[] = '<a href="' . esc_url( self::DONATE_URL ) . '" target="_blank" rel="noopener">' . esc_html__( 'Buy me a coffee', 'fixed-deposit-for-woocommerce' ) . '</a>';
		return $links;
	}
}
