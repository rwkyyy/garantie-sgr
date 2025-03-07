<?php
/**
 * Plugin Name: Garanție SGR pentru WooCommerce
 * Plugin URL: https://uprise.ro
 * Description: Extensie WooCommerce pentru sistemul garanție SGR.
 * Version: 1.0
 * Author: Eduard V. Doloc
 * Author URI: https://uprise.ro
 * Requires at least: 6.0
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * WC requires at least: 7.9
 * WC tested up to: 9.6
 * Stable tag: 1.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

// HPOS - not needed, but apparently it fails checks!
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

if ( ! class_exists( 'Garantie_SGR' ) ) {
	class Garantie_SGR {
		private static $instance = null;

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function __construct() {


			// Init hooks
			add_action( 'admin_init', [ $this, 'init_admin' ] );
			add_action( 'woocommerce_init', [ $this, 'init_woocommerce' ] );


			// Add admin menu
			add_action( 'admin_menu', [ $this, 'add_admin_menu_sgr' ], 10 );
		}


		public function init_admin() {
			// Add product tab
			add_filter( 'woocommerce_product_data_tabs', [ $this, 'add_product_data_tab' ] );
			add_action( 'woocommerce_product_data_panels', [ $this, 'add_product_data_fields' ] );
			add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_data_fields' ] );


		}

		public function init_woocommerce() {
			// Frontend display
			add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'display_warranty_message' ] );

			// Cart handling
			add_action( 'woocommerce_cart_calculate_fees', [ $this, 'add_warranty_fee' ] );
		}

		public function woocommerce_missing_notice() {
			?>
            <div class="error">
                <p><?php esc_html_e( 'SGR Warranty requires WooCommerce to be installed and active.', 'garantie-sgr' ); ?></p>
            </div>
			<?php
		}

		// Product Data Tab
		public function add_product_data_tab( $tabs ) {
			$tabs['sgr'] = [
				'label'  => esc_html__( 'SGR', 'garantie-sgr' ),
				'target' => 'sgr_warranty_data',
				'class'  => [ 'show_if_simple', 'show_if_variable' ],
			];

			return $tabs;
		}

		public function add_product_data_fields() {
			?>
            <div id="sgr_warranty_data" class="panel woocommerce_options_panel">
				<?php
				wp_nonce_field( 'sgr_save_product_data', '_sgr_nonce' ); // Add nonce field
				woocommerce_wp_checkbox( [
					'id'          => '_enable_sgr',
					'label'       => esc_html__( 'SGR', 'garantie-sgr' ),
					'description' => esc_html__( 'Dorești ca acest produs să aibă SGR?', 'garantie-sgr' )
				] );
				?>
            </div>
			<?php
		}

		public function save_product_data_fields( $post_id ) {
			// Verify nonce
			if ( ! isset( $_POST['_sgr_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_sgr_nonce'] ) ), 'sgr_save_product_data' ) ) {
				return;
			}

			// Ensure the user has permission
			if ( ! current_user_can( 'edit_product', $post_id ) ) {
				return;
			}

			// Sanitize and save data
			$enable_sgr = isset( $_POST['_enable_sgr'] ) ? 'yes' : 'no';
			update_post_meta( $post_id, '_enable_sgr', sanitize_text_field( $enable_sgr ) );
		}

		// Admin Menu
		public function add_admin_menu_sgr() {
			if ( ! class_exists( 'WooCommerce' ) ) {
				return; // Exit if WooCommerce is not active
			}

			// Add submenu page to Products menu
			add_submenu_page(
				'edit.php?post_type=product',       // Parent slug for Products menu
				esc_html__( 'SGR', 'garantie-sgr' ), // Page title
				esc_html__( 'SGR', 'garantie-sgr' ), // Menu title
				'manage_woocommerce',               // Capability required
				'sgr-products',                     // Menu slug
				[ $this, 'render_sgr_products_page' ] // Callback function
			);
		}

		public function render_sgr_products_page() {
			// Verify user permissions
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'Verifică drepturile de utilizator!', 'garantie-sgr' ) );
			}

			// Optimized query using get_posts() instead of WP_Query
			$args = [
				'post_type'      => 'product',
				'posts_per_page' => - 1,
				'meta_key'       => '_enable_sgr',
				'meta_value'     => 'yes',
				'fields'         => 'ids', // Only fetch product IDs for better performance
			];

			$product_ids = get_posts( $args ); // Returns an array of product IDs
			?>
            <div class="wrap">
                <h1 class="wp-heading-inline"><?php esc_html_e( 'Produse cu SGR activ', 'garantie-sgr' ); ?></h1>

				<?php if ( ! empty( $product_ids ) ) : ?>
                    <table class="wp-list-table widefat fixed striped posts">
                        <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e( 'Produs', 'garantie-sgr' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'SKU', 'garantie-sgr' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Preț', 'garantie-sgr' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Acțiuni', 'garantie-sgr' ); ?></th>
                        </tr>
                        </thead>
                        <tbody>
						<?php foreach ( $product_ids as $product_id ) :
							$product = wc_get_product( $product_id );
							?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo esc_url( get_edit_post_link( $product_id ) ); ?>"
                                           class="row-title">
											<?php echo esc_html( $product->get_name() ); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td><?php echo esc_html( $product->get_sku() ?: '-' ); ?></td>
                                <td><?php echo wp_kses_post( $product->get_price_html() ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( get_edit_post_link( $product_id ) ); ?>"
                                       class="button button-small">
										<?php esc_html_e( 'Modificare', 'garantie-sgr' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>"
                                       class="button button-small" target="_blank">
										<?php esc_html_e( 'Vezi', 'garantie-sgr' ); ?>
                                    </a>
                                </td>
                            </tr>
						<?php endforeach; ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th scope="col"><?php esc_html_e( 'Produs', 'garantie-sgr' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'SKU', 'garantie-sgr' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Preț', 'garantie-sgr' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Acțiuni', 'garantie-sgr' ); ?></th>
                        </tr>
                        </tfoot>
                    </table>
				<?php else : ?>
                    <div class="notice notice-warning">
                        <p><?php esc_html_e( 'Nu există produse cu SGR activat.', 'garantie-sgr' ); ?></p>
                    </div>
				<?php endif; ?>
            </div>
			<?php
		}


		// Frontend Display
		public function display_warranty_message() {
			global $product;

			if ( ! $product || get_post_meta( $product->get_id(), '_enable_sgr', true ) !== 'yes' ) {
				return;
			}

			echo '<div class="woocommerce-info">';
			echo esc_html__( 'Garanție SGR (+0.50 lei)', 'garantie-sgr' );
			echo '</div>';
		}

		// Cart Handling
		public function add_warranty_fee( $cart ) {
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				return;
			}

			$fee_total    = 0;
			$warranty_fee = 0.50;

			foreach ( $cart->get_cart() as $cart_item ) {
				$product_id = $cart_item['product_id'];
				if ( get_post_meta( $product_id, '_enable_sgr', true ) === 'yes' ) {
					$fee_total += $warranty_fee * $cart_item['quantity'];
				}
			}

			if ( $fee_total > 0 ) {
				$cart->add_fee( esc_html__( 'SGR', 'garantie-sgr' ), $fee_total );
			}
		}
	}

	// Initialize the plugin
	function Garantie_SGR() {
		return Garantie_SGR::instance();
	}

	Garantie_SGR();
}
