<?php
/**
 * Plugin Name: Garanție SGR pentru WooCommerce
 * Plugin URL: https://uprise.ro
 * Description: Extensie WooCommerce pentru sistemul garanție-returnare SGR.
 * Version: 2.0.1
 * Author: Eduard V. Doloc
 * Author URI: https://uprise.ro
 * Requires at least: 6.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * WC requires at least: 7.9
 * WC tested up to: 10.0
 * Stable tag: 2.0.1
 * Requires Plugins: woocommerce
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */


defined( 'ABSPATH' ) || exit;

if ( ! defined( 'GARANTIE_SGR_VERSION' ) ) {
	define( 'GARANTIE_SGR_VERSION', '2.0.1' );
}

// HPOS - not needed, but apparently it fails checks!
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );


//search in media for existing sgr image file
function find_image_sgr( $filename ) {
	$filename     = sanitize_file_name( $filename );
	$image_finder = new WP_Query( array(
		'post_type'              => 'attachment',
		'post_status'            => 'inherit',
		'posts_per_page'         => 1,
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'meta_query'             => array(
			array(
				'key'     => '_wp_attached_file',
				'value'   => $filename,
				'compare' => 'LIKE',
			),
		),
	) );

	if ( ! is_wp_error( $image_finder ) && ! empty( $image_finder->posts ) ) {
		return (int) $image_finder->posts[0];
	}

	return false;
}

// check for the sgr product image
function sgr_image_check( $basename ) {
	$basename = sanitize_file_name( $basename );
	$existing = find_image_sgr( $basename );
	if ( $existing ) {
		return $existing;
	}

	$plugin_path = plugin_dir_path( __FILE__ );
	$src_path    = $plugin_path . $basename;
	if ( ! file_exists( $src_path ) ) {
		return false;
	}

	$bits = wp_upload_bits( $basename, null, file_get_contents( $src_path ) );
	if ( $bits['error'] ) {
		return false;
	}

	$filetype   = wp_check_filetype( $bits['file'], null );
	$attachment = array(
		'post_mime_type' => $filetype['type'],
		'post_title'     => sanitize_text_field( pathinfo( $basename, PATHINFO_FILENAME ) ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	);

	$attach_id = wp_insert_attachment( $attachment, $bits['file'] );
	if ( is_wp_error( $attach_id ) || ! $attach_id ) {
		return false;
	}

	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$attach_data = wp_generate_attachment_metadata( $attach_id, $bits['file'] );
	wp_update_attachment_metadata( $attach_id, $attach_data );

	return (int) $attach_id;
}

// create the product if it's missing
function garantie_svg_product_create() {
	if ( ! class_exists( 'WC_Product_Simple' ) ) {
		return false;
	}
	// safeguard for older wc
	if ( ! function_exists( 'wc_get_product_id_by_sku' ) ) {
		return false;
	}

	$sgr_sku    = 'TAXA-SGR';
	$product_id = wc_get_product_id_by_sku( $sgr_sku );

	if ( $product_id ) {
		update_option( 'garantie_sgr_product_id', (int) $product_id );

		return (int) $product_id;
	}

	$product = new WC_Product_Simple();
	$product->set_name( __( 'Taxă SGR', 'garantie-sgr' ) );
	$product->set_sku( $sgr_sku );
	$product->set_regular_price( '0.50' );
	$product->set_price( '0.50' );
	$product->set_tax_status( 'none' );
	$product->set_description( __( 'Taxă garanție SGR', 'garantie-sgr' ) );
	$product->set_catalog_visibility( 'hidden' );
	$product->set_status( 'publish' );
	$product->set_virtual( true );

	$product_id = $product->save();
	if ( $product_id && ! is_wp_error( $product_id ) ) {
		// Backdate to Unix epoch start.
		wp_update_post( array(
			'ID'            => $product_id,
			'post_date'     => '1970-01-01 00:00:00',
			'post_date_gmt' => '1970-01-01 00:00:00',
		) );
		update_option( 'garantie_sgr_product_id', (int) $product_id );

		$attach_id = sgr_image_check( 'produs-sgr.gif' );
		if ( $attach_id ) {
			set_post_thumbnail( $product_id, $attach_id );
		}

		return (int) $product_id;
	}

	return false;
}

// Activation: defer creation until Woo is initialized (next load).
register_activation_hook( __FILE__, function () {
	update_option( 'garantie_sgr_pending_setup', 1 );
} );

// Update path / repairs when users update without re-activating.
// Run only after WooCommerce is fully initialized.
add_action( 'woocommerce_init', function () {
	if ( ! class_exists( 'WC_Product_Simple' ) || ! function_exists( 'wc_get_product_id_by_sku' ) ) {
		return;
	}

	$stored_version = get_option( 'garantie_sgr_version' );
	$stored_id      = (int) get_option( 'garantie_sgr_product_id' );
	$pending_setup  = (int) get_option( 'garantie_sgr_pending_setup', 0 );

	$needs = false;

	// If activation set a pending flag, run now.
	if ( $pending_setup ) {
		$needs = true;
	}

	// On version change or missing product ID, run now.
	if ( $stored_version !== GARANTIE_SGR_VERSION ) {
		$needs = true;
	}
	if ( ! $stored_id || ! get_post( $stored_id ) ) {
		$needs = true;
	}

	if ( $needs ) {
		garantie_svg_product_create();
		update_option( 'garantie_sgr_version', GARANTIE_SGR_VERSION );
		delete_option( 'garantie_sgr_pending_setup' );
	}
}, 20 );

//filters for visibility of the product
add_filter( 'woocommerce_product_is_visible', function ( $visible, $product_id ) {
	$sgr_id = (int) get_option( 'garantie_sgr_product_id' );

	return ( $product_id === $sgr_id ) ? false : $visible;
}, 10, 2 );

//block manual purchase
add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $product_id, $quantity, $variation_id = 0, $variations = array(), $cart_item_data = array() ) {
	$sgr_id = (int) get_option( 'garantie_sgr_product_id' );

	// Allow internal adds (our sync passes a private flag).
	if ( ! empty( $cart_item_data['_garantie_sgr_internal'] ) ) {
		return $passed;
	}

	// Block manual attempts to add the SGR product.
	if ( $sgr_id && (int) $product_id === $sgr_id ) {
		wc_add_notice( esc_html__( 'Acest produs nu poate fi adăugat direct în coș.', 'garantie-sgr' ), 'error' );

		return false;
	}

	return $passed;
}, 10, 6 );

// Exclude SGR from coupons (any type)
add_filter( 'woocommerce_coupon_is_valid_for_product', function ( $valid, $product, $coupon ) {
	$sgr_id = (int) get_option( 'garantie_sgr_product_id' );
	if ( $sgr_id && $product && (int) $product->get_id() === $sgr_id ) {
		return false;
	}

	return $valid;
}, 10, 3 );

//Make main logic pluggable so others can change if needed ;)
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
			add_filter( 'woocommerce_product_data_tabs', [ $this, 'add_product_data_tab' ] );
			add_action( 'woocommerce_product_data_panels', [ $this, 'add_product_data_fields' ] );
			add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_data_fields' ] );
		}

		public function init_woocommerce() {
			add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'display_warranty_message' ] );
			add_action( 'woocommerce_cart_loaded_from_session', function ( $cart ) {
				$this->sync_sgr_cart( $cart );
			}, 20 );
			add_action( 'woocommerce_after_cart_item_quantity_update', function ( $cart_item_key, $quantity, $old_quantity, $cart ) {
				$this->sync_sgr_cart( $cart );
			}, 10, 4 );
			add_action( 'woocommerce_remove_cart_item', function ( $cart_key, $cart ) {
				$this->sync_sgr_cart( $cart );
			}, 10, 2 );
			add_action( 'woocommerce_add_to_cart', function () {
				if ( WC()->cart ) {
					$this->sync_sgr_cart( WC()->cart );
				}
			} );
		}

		/**
		 * Calculate how many SGR items are needed based on SGR-enabled products in cart.
		 *
		 * @param WC_Cart $cart
		 *
		 * @return int
		 */
		private function get_required_sgr_qty( $cart ) {
			$qty = 0;
			if ( ! $cart || ! method_exists( $cart, 'get_cart' ) ) {
				return 0;
			}

			foreach ( $cart->get_cart() as $item ) {
				if ( empty( $item['product_id'] ) ) {
					continue;
				}
				$product_id = (int) $item['product_id'];
				if ( get_post_meta( $product_id, '_enable_sgr', true ) === 'yes' ) {
					$qty += (int) $item['quantity'];
				}
			}

			return $qty;
		}


		//Ensure the SGR virtual product is present in the cart in the correct quantity.
		public function sync_sgr_cart( $cart ) {
			static $processing = false;
			if ( $processing || ! $cart || ! method_exists( $cart, 'get_cart' ) ) {
				return;
			}
			$processing = true;

			$sgr_id = (int) get_option( 'garantie_sgr_product_id' );
			if ( ! $sgr_id ) {
				$maybe = wc_get_product_id_by_sku( 'TAXA-SGR' );
				if ( $maybe ) {
					update_option( 'garantie_sgr_product_id', (int) $maybe );
					$sgr_id = (int) $maybe;
				}
				if ( ! $sgr_id ) {
					$processing = false;

					return;
				}
			}

			foreach ( $cart->get_cart() as $cart_item_key => $item ) {
				if ( (int) $item['product_id'] === $sgr_id ) {
					$cart->remove_cart_item( $cart_item_key );
				}
			}

			$needed_qty = $this->get_required_sgr_qty( $cart );
			if ( $needed_qty > 0 ) {
				$cart->add_to_cart( $sgr_id, $needed_qty, 0, array(), array( '_garantie_sgr_internal' => 1 ) );
			}
			$processing = false;
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
				return;
			}

			// Add submenu page to Products menu
			add_submenu_page(
				'edit.php?post_type=product',
				esc_html__( 'SGR', 'garantie-sgr' ),
				esc_html__( 'SGR', 'garantie-sgr' ),
				'manage_woocommerce',
				'sgr-products',
				[ $this, 'render_sgr_products_page' ]
			);
		}

		public function render_sgr_products_page() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'Verifică drepturile de utilizator!', 'garantie-sgr' ) );
			}

			$args = [
				'post_type'              => 'product',
				'posts_per_page'         => - 1,
				'meta_key'               => '_enable_sgr',
				'meta_value'             => 'yes',
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			];

			$product_ids = get_posts( $args );
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


		// Frontend for products
		public function display_warranty_message() {
			global $product;

			if ( ! $product || get_post_meta( $product->get_id(), '_enable_sgr', true ) !== 'yes' ) {
				return;
			}

			echo '<div class="woocommerce-info">';
			echo esc_html__( 'Garanție SGR (+0.50 lei)', 'garantie-sgr' );
			echo '</div>';
		}

	}

	function Garantie_SGR() {
		return Garantie_SGR::instance();
	}

	Garantie_SGR();
}
