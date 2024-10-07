<?php

class ARC_Core {
	public static $custom_acf_options = [];

	/**
	 * Init
	 */
	public static function init() {
		// Limitations for 1 or 6 items lifetime
		add_action( 'init', [ self::class, 'add_sales_representative_role' ] );
		add_action( 'init', [ self::class, 'retrieve_acf_options' ] );
		add_filter( 'woocommerce_add_to_cart_validation', [ self::class, 'limit_lifetime_purchases' ], 20, 3 );
		add_action('woocommerce_add_to_cart', [ self::class, 'check_product_in_cart'], 20, 6);
		add_filter( 'woocommerce_product_get_stock_status', [ self::class, 'set_all_products_in_stock' ], 20, 3 );
		// Reorder button in orders list https://arc-cardinal.tempurl.host/my-account/orders/
		add_filter( 'woocommerce_my_account_my_orders_actions', [ self::class, 'wc_reorder_order_action_button' ], 50, 2 );
		add_action( 'wp_ajax_reorder_order', [ self::class, 'wc_handle_reorder_order' ] );
		// Show the list of the products on view order page like https://arc-cardinal.tempurl.host/my-account/view-order/2402/
		add_action( 'woocommerce_view_order', [ self::class, 'display_product_table' ] );
		add_filter( 'thwma_update_shipping_address_section', [ self::class, 'disable_multi_shipping_in_email'], 10, 1 );
		// Change menu im My Account
		add_action( 'init', [ self::class, 'add_endpoints' ] );
		add_filter( 'woocommerce_account_menu_items', [ self::class, 'new_menu_items' ] );
		add_action( 'woocommerce_account_order-by-sku_endpoint', [ self::class, 'order_by_sku_content' ] );
		add_action( 'woocommerce_account_my-wish-list_endpoint', [ self::class, 'my_wish_list_content' ] );
		add_action( 'woocommerce_account_us-privacy-settings_endpoint', [ self::class, 'us_privacy_settings_content' ] );
		add_action( 'woocommerce_account_privacy-settings_endpoint', [ self::class, 'privacy_settings_content' ] );
		/*add_action( 'woocommerce_account_newsletter-subscriptions_endpoint', [ self::class, 'newsletter_subscriptions_content' ] );*/
		// Add footer scripts
		add_action( 'wp_footer', [ self::class, 'add_footer_scripts' ] );
		// CSV import / bulk add to cart
		add_action( 'wp_ajax_arc_bulk_add_to_cart', [ self::class, 'arc_bulk_add_to_cart' ] );
		add_action( 'wp_ajax_nopriv_arc_bulk_add_to_cart', [ self::class, 'arc_bulk_add_to_cart' ] );
		// Add additional link in cart
		add_action( 'woocommerce_proceed_to_checkout', [ self::class, 'arc_custom_continue_shopping_button' ], 9999 );
		// Add notes to orders as well as search in My Account
		add_action( 'woocommerce_after_checkout_billing_form', [ self::class, 'arc_custom_checkout_field' ], 9999 );
		add_action( 'woocommerce_checkout_order_processed', [ self::class, 'arc_custom_checkout_field_update_order_meta' ], 10, 2 );
		add_filter( 'woocommerce_my_account_my_orders_columns', [ self::class, 'arc_custom_user_orders_columns' ] );
		add_filter( 'woocommerce_my_account_my_orders_query', [ self::class, 'filter_orders_by_search' ], 10, 1 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', [ self::class, 'display_admin_order_meta' ], 10, 1 );
		add_shortcode( 'arc_order_search_form', [ self::class, 'arc_order_search_form_shortcode' ] );
		add_action( 'woocommerce_before_account_orders', [ self::class, 'my_custom_content_above_orders_table' ] );
		// Setting for categories to limit purchases count per product in category
		add_action( 'product_cat_edit_form_fields', [ self::class, 'woocommerce_edit_category_fields' ], 10, 2 );
		add_action( 'edited_product_cat', [ self::class, 'woocommerce_save_category_fields' ], 10, 3 );
		// Remove Subtotal column from checkout
		add_filter( 'woocommerce_update_order_review_fragments', [ self::class, 'filter_update_order_review_fragments' ] );
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
		add_action( 'woocommerce_checkout_order_review', [ self::class, 'custom_order_review' ], 10 );
		// A filter to modify the order table columns on the account page
		add_filter( 'woocommerce_account_orders_columns', [ self::class, 'custom_orders_table_columns' ] );
		// Filter to change data output in 'order-total' column
		add_filter( 'woocommerce_my_account_my_orders_column_order-total', [ self::class, 'custom_order_total_column_content' ], 10, 1 );
		add_action( 'woocommerce_my_account_my_orders_column_client-note', [ self::class, 'arc_custom_user_orders_column_content' ] );
		// Custom addresses
		add_action( 'woocommerce_after_edit_account_address_form', [ self::class, 'add_content_after_my_address' ] );
		// Woo: “New order” emails should only come from Customers and not other User Roles
		add_filter( 'woocommerce_email_recipient_new_order', [ self::class, 'custom_new_order_email_recipient' ], 10, 2 );
		// Filtered Page title should always take Category title
		add_filter( 'get_the_archive_title', [ self::class, 'change_products_page_title' ] );
        // Set min/max for inputs
		add_filter( 'woocommerce_quantity_input_args', [ self::class, 'custom_quantity_input_args'], 10, 2 );
	}

	public static function retrieve_acf_options() {
		self::$custom_acf_options = get_fields( 'arc-cardinal-order-settings' );
	}

	/**
	 * @return string
	 */
    public static function disable_multi_shipping_in_email() {
	    return '';
    }

	/**
	 * @param $args
	 * @param $product
	 *
	 * @return mixed
	 */
    public static function custom_quantity_input_args( $args, $product ) {

	    $user            = wp_get_current_user();
	    $purchases       = get_user_meta( $user->ID, 'lifetime_purchases', true );
	    $product_cat_ids = wc_get_product_cat_ids( $product->get_id() );

	    $max_purchases = (( ARC_Core::$custom_acf_options['quantity_restrictions']['customer_maximum_per_sku'] ) ?? 2 );
	    if ( $user->has_cap( 'sales_representative' ) ||
	         $user->has_cap( 'manage_woocommerce' ) || // For Shop Managers
	         $user->has_cap( 'edit_others_posts' ) ||  // For Editors
	         $user->has_cap( 'administrator' ) ) {
		    foreach ( $product_cat_ids as $cat_id ) {
			    $cat_limit = get_term_meta( $cat_id, '_sales_rep_sku_limit', true );
			    if ( ! is_numeric( $cat_limit ) ) {
				    $cat_limit = ( self::$custom_acf_options['quantity_restrictions']['sales_rep_maximum_quantity_per_sku'] ) ?? 32;
			    }
			    if ( ! empty( $cat_limit ) ) {
				    $max_purchases = $cat_limit;
				    break;
			    }
		    }
	    }

	    $args['input_value'] = 1; // Start from this value (default = 1)
	    $args['max_value'] = max($max_purchases, 1); // Maximum value
	    $args['min_value'] = 1; // Minimum value
	    $args['step'] = 1; // Increment or decrement by this value (default = 1)

	    return $args;
    }

	/**
	 * Add new Role
	 */
	public static function add_sales_representative_role() {
		add_role( 'sales_representative', 'Sales Representative', array(
			'read' => true,
		) );
	}

	public static function set_all_products_in_stock( $status, $product ) {
		return $status;
		//return 'instock';
	}

	/**
	 * @param $cart_item_key
	 * @param $product_id
	 * @param $quantity
	 * @param $variation_id
	 * @param $variation
	 * @param $cart_item_data
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function check_product_in_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		if (did_action('woocommerce_add_to_cart') === 1) {
			$cart_contents = WC()->cart->cart_contents;

			foreach ($cart_contents as $key => $values) {
				if ($product_id == $values['product_id'] && $key != $cart_item_key) {
					$new_quantity = intval($cart_contents[$key]['quantity']) + $quantity;
					WC()->cart->set_quantity($key, $new_quantity);
					WC()->cart->remove_cart_item($cart_item_key);
				}
			}
		}
	}

	/**
	 * Limit the lifetime purchases for a user.
	 *
	 * @param bool $passed
	 * @param int $product_id
	 * @param int $quantity
	 *
	 * @return bool
	 **/
	public static function limit_lifetime_purchases( $passed, $product_id, $quantity ) {
		$user            = wp_get_current_user();
		$purchases       = get_user_meta( $user->ID, 'lifetime_purchases', true );
		$product_cat_ids = wc_get_product_cat_ids( $product_id );

		$max_purchases = ( self::$custom_acf_options['quantity_restrictions']['customer_maximum_total_items_in_cart'] ) ?? 6;
		if ( $user->has_cap( 'sales_representative' ) ||
		     $user->has_cap( 'manage_woocommerce' ) || // For Shop Managers
		     $user->has_cap( 'edit_others_posts' ) ||  // For Editors
		     $user->has_cap( 'administrator' ) ) {
			foreach ( $product_cat_ids as $cat_id ) {
				$cat_limit = get_term_meta( $cat_id, '_sales_rep_sku_limit', true );
				if ( ! is_numeric( $cat_limit ) ) {
					$cat_limit = ( self::$custom_acf_options['quantity_restrictions']['sales_rep_maximum_quantity_per_sku'] ) ?? 32;
				}
				if ( ! empty( $cat_limit ) ) {
					$max_purchases = $cat_limit;
					break;
				}
			}
		}

		if ( isset( $purchases[ $product_id ] ) && $purchases[ $product_id ] >= $max_purchases ) {
			wc_add_notice( sprintf( __( 'You have reached your lifetime samples ordering limit for this product.' ) ), 'error' );
			$passed = false;
		} elseif ( $quantity > $max_purchases ) {
			wc_add_notice( sprintf( __( 'You cannot order more than %s samples of this product at once.' ), $max_purchases ), 'error' );
			$passed = false;
		}

		return $passed;
	}

	/**
	 * Edit the category fields for WooCommerce.
	 *
	 * @param object $term The category term object.
	 * @param string $taxonomy The category taxonomy.
	 *
	 * @return void
	 */
	public static function woocommerce_edit_category_fields( $term, $taxonomy ) {
		$value = get_term_meta( $term->term_id, '_sales_rep_sku_limit', true );
		?>
        <tr class="form-field">
        <th scope="row" valign="top"><label><?php _e( 'Sales Reps purchase limit per SKU' ); ?></label></th>
        <td>
            <input type="number" name="_sales_rep_sku_limit" value="<?php echo esc_attr( $value ) ? esc_attr( $value ) : ( ( self::$custom_acf_options['quantity_restrictions']['sales_rep_maximum_quantity_per_sku'] ) ?? 32 ); ?>">
        </td>
        </tr><?php
	}

	public static function woocommerce_save_category_fields( $term_id, $tt_id = '', $taxonomy = '' ) {
		$value = filter_input( INPUT_POST, '_sales_rep_sku_limit' );
		update_term_meta( $term_id, '_sales_rep_sku_limit', $value );
	}

	/**
	 * @param $actions
	 * @param $order
	 *
	 * @return mixed
	 */
	public static function wc_reorder_order_action_button( $actions, $order ) {
		$actions['reorder'] = array(
			'url'  => wp_nonce_url( admin_url( 'admin-ajax.php?action=reorder_order&order_id=' . $order->get_id() ), 'reorder_order_nonce' ),
			'name' => 'Reorder'
		);

		return $actions;
	}

	/**
	 * Handle the AJAX request to reorder the items
	 */
	public static function wc_handle_reorder_order() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'reorder_order_nonce' ) ) {
			die( 'Security check failed' );
		}

		if ( ! isset( $_GET['order_id'] ) ) {
			die( 'Order ID not provided' );
		}

		$order = wc_get_order( $_GET['order_id'] );
		if ( ! $order ) {
			die( 'Invalid order ID' );
		}

		// Empty the cart
		WC()->cart->empty_cart();

		// Add each item in the order to the cart
		foreach ( $order->get_items() as $item ) {
			WC()->cart->add_to_cart( $item->get_product_id(), $item->get_quantity() );
		}

		// Redirect to the cart page
		wp_redirect( wc_get_cart_url() );
		exit;
	}

	public static function display_product_table( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		?>
        <h2><?php _e( 'Products', 'textdomain' ); ?></h2>
        <table class="shop_table shop_table_responsive">
            <thead>
            <tr>
                <th class="product-add"></th>
                <th class="product-name"><?php _e( 'Product Name', 'textdomain' ); ?></th>
                <th class="product-sku"><?php _e( 'SKU', 'textdomain' ); ?></th>
                <th class="product-quantity"><?php _e( 'Quantity', 'textdomain' ); ?></th>
            </tr>
            </thead>
            <tbody>
			<?php

			foreach ( $order->get_items() as $item ) {
				$product    = $item->get_product();
				$product_id = $item->get_product_id();
				?>
                <tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
                    <td class="product-add">
						<?php
						echo '<a href="' . esc_url( $product->add_to_cart_url() ) . '" rel="nofollow" data-product_id="' . esc_attr( $product->get_id() ) . '" data-quantity="' . esc_attr( $item->get_quantity() ) . '" class="button product_type_simple add_to_cart_button ajax_add_to_cart">' . esc_html( $product->single_add_to_cart_text() ) . '</a>';
						?>
                    </td>
                    <td class="product-name">
						<?php echo $product->get_name(); ?>
                    </td>
                    <td class="product-sku">
						<?php echo $product->get_sku(); ?>
                    </td>
                    <td class="product-quantity">
						<?php
						//TODO: Update logic according to your configuration or fields for 'Quantity Ordered' and 'Quantity Shipped'
						$quantity_ordered = $item->get_quantity();
						$tracking_info    = $order->get_meta( '_tracking_info', true );
						$quantity_shipped = empty( $tracking_info ) ? 0 : $item->get_quantity(); //TODO: sum / count( $tracking_info );

						printf( __( 'Ordered: %d<br/>Shipped: %d', 'textdomain' ), $quantity_ordered, $quantity_shipped );
						?>
                    </td>
                </tr>
				<?php
			}
			?>
            </tbody>
        </table>
		<?php
	}

	public static function new_menu_items( $items ) {
		unset( $items['dashboard'] );
		unset( $items['orders'] );
		unset( $items['edit-address'] );
		unset( $items['edit-account'] );

		$new_items = array(
			'dashboard'                => 'My Account',
			'orders'                   => 'My Orders',
			'order-by-sku'             => 'Order by SKU',
			'edit-address'             => 'Address Book',
			'edit-account'             => 'Account Information',
			/*'newsletter-subscriptions' => 'Newsletter Subscriptions',*/
		);

		return $new_items + $items;
	}

	public static function add_endpoints() {
		flush_rewrite_rules();
		add_rewrite_endpoint( 'order-by-sku', EP_ROOT | EP_PAGES );
		add_rewrite_endpoint( 'newsletter-subscriptions', EP_ROOT | EP_PAGES );
		add_rewrite_endpoint( 'my-invitations', EP_ROOT | EP_PAGES );
	}

	public static function order_by_sku_content() {
		include __DIR__ . '/_' . __FUNCTION__ . '.php';
	}

	public static function newsletter_subscriptions_content() {
		echo "<h2>Newsletter Subscriptions</h2>";
	}

	/**
	 * Adds items to the cart in bulk.
	 *
	 * This method retrieves a list of items in JSON format from the request body.
	 * It iterates over each item and adds it to the cart if the product SKU exists.
	 *
	 * @return void
	 */
	public static function arc_bulk_add_to_cart() {
		$items = $_REQUEST['items'];
		foreach ( $items as $item ) {
			$product_id = wc_get_product_id_by_sku( $item['sku'] );
			if ( $product_id ) {
				WC()->cart->add_to_cart( $product_id, $item['qty'] );
			}
		}
		wp_send_json_success();
		exit;
	}

	/**
	 * Generates a custom "Continue Shopping" button.
	 *
	 * @return void
	 */
	public static function arc_custom_continue_shopping_button() {
		$shop_link = get_permalink( wc_get_page_id( 'shop' ) );
		echo "<a href='{$shop_link}' class='checkout-button button alt wc-forward add-more-btn'>Add More Products</a>";
	}

	public static function add_footer_scripts() {
		echo '<script type="text/javascript">
    jQuery(document).ready(function($) {
        var checkbox = $("#ship-to-different-address-checkbox");
        if (checkbox.is(":checked")) {
            checkbox.prop("checked", false);
        }
    });
    </script>';
	}

	// Add client note field to checkout page
	public static function arc_custom_checkout_field() {
		echo '<style>.select { min-height: 3.5em; height: 3.5em; } label[for="billing_address_2"] { visibility: hidden !important; } </style>';
		echo '<div id="arc_custom_checkout_field" style="margin-bottom: 15px;"><h3 style="margin-bottom: 10px !important; ">' . __( 'Order notes (optional):' ) . '</h3>';

		woocommerce_form_field( 'client_note', array(
			'type'        => 'textarea',
			'class'       => array( '' ),
			'label'       => __( 'Add your note' ),
			'placeholder' => __( 'Add your notes here' ),
		), WC()->customer->get_meta( 'client_note', true ) );

		echo '</div>';
	}

	public static function filter_orders_by_search( $args ) {
        // Subquery for searching by product name or SKU in order items
		global $wpdb;

        if ( isset( $_GET['orders_search'] ) && $_GET['orders_search'] ) {
			$search = sanitize_text_field( trim( $_GET['orders_search'] ) );

			$current_user_id = get_current_user_id(); // get the ID of currently logged in user

	        if ( is_numeric( $search ) ) {
		        // Searching by Order ID
		        $order_id_belongs_to_user = $wpdb->get_var(
			        $wpdb->prepare("
                    SELECT ID FROM {$wpdb->prefix}posts
                    WHERE ID = %d AND post_author = %d AND post_type = 'shop_order_placehold'
                ", $search, $current_user_id )
		        );

		        // If the order belongs to current user, add the ID to args
		        if ( !empty($order_id_belongs_to_user)) {
			        $args['post__in'] = [ $search ];
		        }

	        } else {
				$args['limit'] = -1;



				$product_ids = $wpdb->get_col( $wpdb->prepare( "
                SELECT p.ID FROM {$wpdb->prefix}posts AS p
                INNER JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
                WHERE (pm.meta_key = '_sku' AND pm.meta_value LIKE '%%%s%%')
            ", $search ) );

				$order_ids = $wpdb->get_col( "
                SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items
                WHERE order_item_type = 'line_item'
                AND order_id IN (
                    SELECT ID FROM {$wpdb->prefix}posts
                    WHERE post_author = $current_user_id AND post_type = 'shop_order_placehold'
                )
                AND order_item_id IN (
                    SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta
                    WHERE meta_key = '_product_id'
                    AND meta_value IN (" . implode( ',', array_map( 'intval', $product_ids ) ) . ")
                )
            ");
                
				// Searching by billing or shipping details in wp_ho_wc_order_addresses
				$address_orders = $wpdb->get_col( $wpdb->prepare( "
                SELECT order_id FROM {$wpdb->prefix}wc_order_addresses
                WHERE order_id IN (
                    SELECT ID FROM {$wpdb->prefix}posts
                    WHERE post_author = $current_user_id AND post_type = 'shop_order_placehold'
                )
                AND (`first_name` LIKE '%%%s%%'
                OR `last_name` LIKE '%%%s%%'
                OR `address_1` LIKE '%%%s%%'
                OR `address_2` LIKE '%%%s%%'
                OR `city` LIKE '%%%s%%'
                OR `postcode` LIKE '%%%s%%'
                OR `state` LIKE '%%%s%%'
                OR `phone` LIKE '%%%s%%'
                OR `email` LIKE '%%%s%%')
            ", $search, $search, $search, $search, $search, $search, $search));

				$client_note_orders = $wpdb->get_col( $wpdb->prepare( "
                SELECT order_id FROM {$wpdb->prefix}wc_orders_meta
                WHERE meta_key = 'client_note' AND meta_value LIKE '%%%s%%'
            ", $search));

				if ( !empty( $address_orders ) ) {
					$order_ids   = array_merge($order_ids, $address_orders);
				}

				if ( !empty( $client_note_orders ) ) {
					$order_ids = array_merge($order_ids, $client_note_orders);
				}

				if ( !empty( $order_ids ) ) {
					$args['post__in']   = $order_ids;
					$args['meta_query'] = [];
				}
			}
		}

		if ( trim($search) && empty( $args['post__in'] ) ) {
			// Set 'post__in' to array with non-existent post ID, to ensure no posts are returned.
			$args['post__in'] = [ 0 ];
		}

		return $args;
	}

	/**
	 * Save client note field value to order meta
	 *
	 * @param $order_id
	 * @param $data
	 *
	 * @return void
	 */
	public static function arc_custom_checkout_field_update_order_meta( $order_id, $data ) {
		if ( ! empty( $_POST['client_note'] ) ) {
			$order = wc_get_order( $order_id );
			$order->update_meta_data( 'client_note', sanitize_text_field( $_POST['client_note'] ) );
			$order->save();
		}
	}

	/**
	 * @param $columns
	 *
	 * @return mixed
	 */
	public static function arc_custom_user_orders_columns( $columns ) {
		$columns['client-note'] = __( 'Client Note' );

		return $columns;
	}

	/**
	 * Display the admin order meta.
	 *
	 * @param object $order The order object.
	 *
	 * @return void
	 */
	public static function display_admin_order_meta( $order ) {
		$note = $order->get_meta( 'client_note' );
		echo '<p><strong>' . __( 'Client Note' ) . ':</strong> ' . ( $note ? $note : '-' ) . '</p>';
	}

	/**
	 * Display the order search form as a shortcode.
	 *
	 * @return string The HTML markup of the order search form.
	 */
	public static function arc_order_search_form_shortcode() {
		ob_start();

		if ( is_wc_endpoint_url( 'orders' ) ) {
			?>
            <style>
                .woocommerce-orders-table__header-client-note {
                    max-width: 200px;
                    width: 200px;
                }
            </style>
            <form role="search" method="get" class="woocommerce-EditAccountForm" action="">
                <label class="screen-reader-text" for="woocommerce-order-search-field"><?php _e( 'Search orders:', 'woocommerce' ) ?></label> <input type="search" id="woocommerce-order-search-field" class="woocommerce-Input input-text" style="max-width:200px;border: 1px solid #dedede;" placeholder="<?php echo esc_attr_x( 'Search orders&hellip;', 'placeholder', 'woocommerce' ) ?>" value="<?php echo esc_attr( isset( $_REQUEST['orders_search'] ) ? $_REQUEST['orders_search'] : '' ); ?>" name="orders_search"/> <input type="submit" class="button" value="<?php echo esc_attr_x( 'Search', 'submit button', 'woocommerce' ) ?>"/><br><br>
            </form>
			<?php
		}

		return ob_get_clean();
	}

	/**
	 * @return void
	 */
	public static function my_custom_content_above_orders_table() {
		echo do_shortcode( '[arc_order_search_form]' );
	}

	/**
	 * Display the custom order review on the checkout page.
	 *
	 * This function retrieves and displays the review order template.
	 *
	 * @return void
	 */
	public static function custom_order_review() {
		include( trailingslashit( get_stylesheet_directory() ) . 'woocommerce/checkout/review-order.php' );
	}

	/**
	 * Update order review fragments.
	 *
	 * @param array $fragments The fragments to update.
	 *
	 * @return array The updated fragments.
	 */
	public static function filter_update_order_review_fragments( $fragments ) {
		ob_start();
		self::custom_order_review();
		$fragments['.woocommerce-checkout-review-order-table'] = ob_get_clean();

		return $fragments;
	}

	/**
	 * @param $columns
	 *
	 * @return mixed
	 */
	public static function custom_orders_table_columns( $columns ) {
		if ( isset( $columns['order-total'] ) ) {
			$columns['order-total'] = 'Items';
		}

		return $columns;
	}

	/**
	 * Calculates*/
	public static function custom_order_total_column_content( $order ) {
		$item_count = $order->get_item_count();
		echo $item_count . ' item' . ( $item_count > 1 ? 's' : '' );
	}

	/**
	 * Retrieve the content for the custom user orders column.
	 *
	 * @param object $order The WooCommerce order object.
	 *
	 * @return void
	 */
	public static function arc_custom_user_orders_column_content( $order ) {
		$note = $order->get_meta( 'client_note', true );

		if ( ! empty( $note ) ) {
			echo esc_html( $note );
		} else {
			echo __( 'No notes found', 'text-domain' );
		}
	}

	/**
	 * Adds custom content after "My Address" section on the My Account page.
	 *
	 * @return void
	 */
	public static function add_content_after_my_address() {
		echo '<div class="my-custom-content">';

		require_once __DIR__ . "/woocommerce/myaccount/my-address.php";
	}

	/**
	 * Custom new order email recipient
	 *
	 * Filter the email recipient for new order notifications.
	 *
	 * @param string $recipient The recipient email address.
	 * @param WC_Order $order The order object.
	 *
	 * @return string   The filtered email recipient.
	 */
	public static function custom_new_order_email_recipient( $recipient, $order ) {
		if ( ! $order instanceof WC_Order ) {
			return $recipient;
		}  // In case of no order exit early

		// Getting the customer user ID from the order
		$user_id = $order->get_user_id();

		// If the user ID exists
		if ( isset( $user_id ) ) {
			// Getting the user data
			$user = get_userdata( $user_id );

			// If the user has role of 'sales_representative', remove admin from the recipient
			if ( in_array( 'sales_representative', $user->roles ) ) {
				$recipient = '';
			}
		}

		return $recipient;
	}

	/**
	 * Change the products page title depending on the current product category
	 *
	 * @param string $page_title The original page title
	 *
	 * @return string The modified page title
	 */
	public static function change_products_page_title( $page_title ) {
        // https://arccardinal.com/our-products/?_sft_product_cat=glassware+beer&debug
        return $page_title;

		// Check if this is a WooCommerce archive page
		if ( isset($_GET['_sft_product_cat']) || is_product_category() ) {
			$cur_cat    = get_queried_object();
			$page_title = $cur_cat->name;
		}

		if ( isset( $_GET['debug'] ) ) {
            die('123');
			return '123';
		}

		return $page_title;
	}
}

ARC_Core::init();
//
//add_filter( 'wp_mail', 'disabling_emails', 9999, 1 );
//function disabling_emails( $args ) {
//	if ( isset( $args['to'] ) ) {
//		unset ( $args['to'] );
//	}
//
//	return $args;
//}

if ( isset( $_GET['test_import'] ) ) {
	// require_once __DIR__.'/_import.php';
	// require_once __DIR__ . '/_import_orders.php';
	//	require_once __DIR__ . '/_import_shipments.php';
}

add_filter( 'woocommerce_my_account_my_orders_columns', function ( $columns ) {
	$new_columns = array();

	foreach ( $columns as $key => $name ) {
		$new_columns[ $key ] = $name;

		// Add it after order status column
		if ( 'order-status' === $key ) {
			$new_columns['magento_id'] = __( 'Magento ID', 'textdomain' );
		}
	}

	return $new_columns;
} );

/*
function save_new_address_after_checkout( $order_id ) {
	$order = wc_get_order( $order_id );
	$user_id = $order->get_user_id(); // Get the user ID associated with the order.

	// Assuming you have the new address data in $new_address as an associative array
	//  (e.g., ['shipping_first_name' => '...', 'shipping_last_name' => '...', ...])
	$new_address = $order->get_address( 'shipping' );
	// Call the 'save_address_to_user' function to store the address
	\Themehigh\WoocommerceMultipleAddressesPro\includes\utils\THWMA_Utils::save_address_to_user( $user_id, $new_address, 'shipping' );

	$new_address = $order->get_address( 'billing' );
	// Call the 'save_address_to_user' function to store the address
	\Themehigh\WoocommerceMultipleAddressesPro\includes\utils\THWMA_Utils::save_address_to_user( $user_id, $new_address, 'billing' );
}

//$current_user = wp_get_current_user();
//if ( $current_user->user_email == 'oleg.shumar@gmail.com' ) {
	add_action( 'woocommerce_checkout_order_processed', 'save_new_address_after_checkout' );
//}
*/