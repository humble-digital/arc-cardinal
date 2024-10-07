<?php
/**
 * Arc Cardinal Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Arc Cardinal
 * @since 2.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ARC_CARDINAL_VERSION', '2.0.0' );


require_once __DIR__ . '/_core.php';

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'arc-cardinal-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ARC_CARDINAL_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

// Change add to cart text on single product page
add_filter( 'woocommerce_product_single_add_to_cart_text', 'woocommerce_add_to_cart_button_text_single' ); 
function woocommerce_add_to_cart_button_text_single() {
    return __( 'Order a Sample', 'woocommerce' ); 
}

// Change add to cart text on product archives page
add_filter( 'woocommerce_product_add_to_cart_text', 'woocommerce_add_to_cart_button_text_archives' );  
function woocommerce_add_to_cart_button_text_archives() {
    return __( 'Order a Sample', 'woocommerce' );
}
// Disable payment and shipping
add_filter( 'woocommerce_cart_needs_payment', '__return_false' );
//add_filter( 'woocommerce_cart_needs_shipping', '__return_false' );
//add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );

// Redirect at Checkout
add_action( 'template_redirect', 'redirect_non_logged_in_users_to_login' );

function redirect_non_logged_in_users_to_login() {
    if ( ! is_user_logged_in() && ( is_checkout() || is_cart() ) ) {
        wp_redirect( wc_get_page_permalink( 'myaccount' ) );
        exit;
    }
}

// Remove quantity inputs from cart page
add_filter( 'woocommerce_cart_item_quantity', 'disable_quantity_input_on_cart', 10, 3 );
function disable_quantity_input_on_cart( $product_quantity, $cart_item_key, $cart_item ) {
    if ( is_cart() ) {
        return $cart_item['quantity'];
    }
    return $product_quantity;
}

// Prevent adding the same product to the cart more than once
add_filter( 'woocommerce_add_to_cart_validation', 'limit_product_to_one_per_customer', 10, 3 );

function limit_product_to_one_per_customer( $passed, $product_id, $quantity ) {
	$user = wp_get_current_user();
	if ( $user->has_cap( 'sales_representative' ) ||
	     $user->has_cap( 'manage_woocommerce' ) || // For Shop Managers
	     $user->has_cap( 'edit_others_posts' ) ||  // For Editors
	     $user->has_cap( 'administrator' ) ) {
		return $passed;

		if( !isset(ARC_Core::$custom_acf_options['quantity_restrictions']['sales_rep_maximum_total_items_in_cart']) || ARC_Core::$custom_acf_options['quantity_restrictions']['sales_rep_maximum_total_items_in_cart'] == '' ){
			return $passed;
		}

		$tmp = ARC_Core::$custom_acf_options['quantity_restrictions']['sales_rep_maximum_total_items_in_cart'];
		if ( WC()->cart->get_cart_contents_count() >= $tmp ) {
			wc_add_notice( 'You cannot add more than '. $tmp .' items in the cart.', 'error' );
			return false;
		}
	}

	// Get the cart
	$cart = WC()->cart->get_cart();


	// Check if total items in the cart are already 6
	$tmp = (( ARC_Core::$custom_acf_options['quantity_restrictions']['customer_maximum_total_items_in_cart'] ) ?? 6);

	if ( WC()->cart->get_cart_contents_count() >= $tmp ) {
		wc_add_notice( 'You cannot add more than '. $tmp .' items in the cart.', 'error' );
		return false;
	}

	// Item count for the product being added
	$product_count = 0;
	// Loop through the cart to find if the product is already there
	foreach( $cart as $cart_item_key => $values ) {
		$_product = $values['data'];

		// If the same product is in the cart, increment the count
		if( $_product->get_id() == $product_id ) {
			$product_count += $values['quantity'];
		}
	}

	// If the item is already in the cart twice, and trying to add again, deny
	$tmp = (( ARC_Core::$custom_acf_options['quantity_restrictions']['customer_maximum_per_sku'] ) ?? 2 );
	if( $product_count >= $tmp ) {
		wc_add_notice( 'You cannot add more than ' . $tmp . ' of the same item.', 'error' );
		return false;
	}

	return $passed;
}

// Prevent customers from ordering the same product more than once
add_action( 'woocommerce_add_to_cart_validation', 'restrict_one_purchase_per_product', 10, 4 );

function restrict_one_purchase_per_product( $passed, $product_id, $quantity, $variation_id = 0 ) {
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();

		if (  $current_user->has_cap( 'sales_representative' ) ||
		      $current_user->has_cap( 'manage_woocommerce' ) || // For Shop Managers
		      $current_user->has_cap( 'edit_others_posts' ) ||  // For Editors
		      $current_user->has_cap( 'administrator' ) ){
			return $passed;
		}

        $customer_orders = wc_get_orders( array(
            'customer' => $current_user->ID,
            'limit' => -1, // Get all orders
            'status' => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ) // Check only these statuses
        ) );

        foreach ( $customer_orders as $customer_order ) {
            $order = wc_get_order( $customer_order );
            foreach ( $order->get_items() as $item ) {
                if ( $item->get_product_id() == $product_id ) {
                    wc_add_notice( 'You have already ordered this product and cannot order it again.', 'error' );
                    return false;
                }
            }
        }
    }

    return $passed;
}

// Breadcrumbs
add_filter( 'woocommerce_get_breadcrumb', 'remove_home_breadcrumb', 20, 2 );

function remove_home_breadcrumb( $crumbs, $breadcrumb ) {
    // Remove the first breadcrumb item if it's "Home"
    if ( !empty( $crumbs ) && $crumbs[0][0] === 'Home' ) {
        array_shift( $crumbs );
    }
    return $crumbs;
}

add_filter( 'woocommerce_get_breadcrumb', 'remove_text_in_parentheses_from_breadcrumbs', 20, 2 );

function remove_text_in_parentheses_from_breadcrumbs( $crumbs, $breadcrumb ) {
    foreach ( $crumbs as $key => $crumb ) {
        // Remove text within parentheses
        $crumbs[$key][0] = preg_replace( '/\s*\(.*?\)\s*/', '', $crumb[0] );
    }
    return $crumbs;
}

// Remove prices from cart page
add_filter( 'woocommerce_cart_item_price', '__return_empty_string' );
add_filter( 'woocommerce_cart_item_subtotal', '__return_empty_string' );
remove_action( 'woocommerce_cart_totals_before_order_total', 'woocommerce_cart_totals_coupon_html', 10 );
remove_action( 'woocommerce_cart_totals_before_order_total', 'woocommerce_cart_totals_shipping_html', 10 );
remove_action( 'woocommerce_cart_totals_after_order_total', 'woocommerce_cart_totals_fee_html', 10 );
remove_action( 'woocommerce_cart_totals_after_order_total', 'woocommerce_cart_totals_order_total_html', 10 );

// Remove prices from checkout page
add_filter( 'woocommerce_checkout_cart_item_quantity', 'remove_price_from_checkout_cart', 10, 3 );
function remove_price_from_checkout_cart( $quantity_html, $cart_item, $cart_item_key ) {
    return preg_replace( '/<span class="woocommerce-Price-amount amount">[^<]*<\/span>/', '', $quantity_html );
}

// Hide cart totals from cart and checkout
add_action( 'wp_head', 'hide_cart_totals' );
function hide_cart_totals() {
    if ( is_cart() || is_checkout() ) {
        echo '<style>
            .cart-subtotal, .order-total, .product-subtotal, .woocommerce-Price-amount, .product-price {
                display: none !important;
            }
        </style>';
    }
}

// ACF HTML Escaping
add_filter( 'wp_kses_allowed_html', 'acf_add_allowed_iframe_tag', 10, 2 );
function acf_add_allowed_iframe_tag( $tags, $context ) {
    if ( $context === 'acf' ) {
        $tags['iframe'] = array(
            'src'             => true,
            'height'          => true,
            'width'           => true,
            'frameborder'     => true,
            'allowfullscreen' => true,
        );
    }

    return $tags;
}

add_shortcode( 'woo_categories', 'display_product_categories_hierarchical' );

function print_category( $product_category, $level = 0 ) {
	$output = '';

	$children = get_term_children( $product_category->term_id, 'product_cat' );

	$cat_url = get_term_link( $product_category->term_id, 'product_cat' );

	// calculate product count for this category and all descendants
	$product_count = calculate_product_count( $product_category->term_id );

	$output .= '<li data-term-id="'. $product_category->term_id .'" data-parent="'. $product_category->parent .'" data-term-slug="'. $product_category->slug .'">
<label class="wpfLiLabel">
<span class="wpfCheckbox"> 
<label aria-label="'. $product_category->name .'" for="wpfTaxonomyInputCheckbox'. $product_category->term_id .'"></label></span>
<span class="wpfDisplay"><span class="wpfValue"><div class="wpfFilterTaxNameWrapper">
<a href="'. esc_url($cat_url) .'">'. $product_category->name .'</a></div></span><span class="wpfCount"> ('. $product_count .')</span></span></label>';

	if( ! empty( $children ) ) {
		$output .= '<ul>';
		foreach( $children as $child ) {
			$child_category = get_term_by( 'id', $child, 'product_cat' );
			if( ! empty($child_category) ) {
				$output .= print_category( $child_category, $level + 1 );
			}
		}
		$output .= '</ul>';
	}

	$output .= '</li>';

	return $output;
}

// Recursive function to calculate total product count for a category and its descendants
function calculate_product_count( $term_id ) {
	$count = 0;

	// get term children
	$children = get_term_children( $term_id, 'product_cat' );

	foreach ( $children as $child_id ) {
		$term = get_term_by( 'id', $child_id, 'product_cat' );
		$count += $term->count + calculate_product_count( $term->term_id );
	}

	return $count;
}

function display_product_categories_hierarchical() {
	$output = '';
	$product_categories = get_terms( 'product_cat', array(
		'parent' => 0, // gets top level categories
		'hide_empty' => 0,
	) );

	if( ! empty( $product_categories ) ) {
		$output .= '
<style>
.wpfFilterTitle {
    font-family: "Montserrat";
    font-weight: bold !important;
    color: #222073 !important;
    margin-bottom: 10px !important;
}
</style>
<div class="wpfFilterTitle"><div class="wfpTitle wfpClickable">Product categories</div></div>
 
 <div class="wpfFilterContent"><div class="wpfCheckboxHier">
 <ul class="wpfFilterVerScroll">';
		foreach ( $product_categories as $product_category ) {
			$output .= print_category( $product_category );
		}
		$output .= '</ul></div></div>';
	}

	return $output;
}

/* S&F 
add_filter('wp_title','search_form_title');

function search_form_title($title){
 
 global $searchandfilter;
 
 if ( $searchandfilter->active_sfid() == 7798)
 {
 return 'Search Results';
 }
 else
 {
 return $title;
 }
 
}*/

// Disable both of WooCommmerce included Select2 libraries on the front-end.

add_action( 'wp_enqueue_scripts', function() {
  if ( !is_admin() && class_exists( 'woocommerce' ) ) {
    wp_dequeue_style( 'select2' );
    wp_deregister_style( 'select2' );

    wp_dequeue_script( 'select2');
    wp_deregister_script('select2');

    // See: https://developer.woocommerce.com/2017/08/08/selectwoo-an-accessible-replacement-for-select2/
    wp_dequeue_script( 'selectWoo');
    wp_deregister_script('selectWoo');
  }
}, 100 );

function custom_search_results_per_page( $query ) {
    if ( !is_admin() && $query->is_main_query() && $query->is_search() ) {
        $query->set( 'posts_per_page', 50 ); // Change 10 to the number of results you want per page
    }
}
add_action( 'pre_get_posts', 'custom_search_results_per_page' );

// Hide Profile Options
function hide_personal_options(){
echo "\n" . '<script type="text/javascript">jQuery(document).ready(function($) { $(\'form#your-profile > h3:first\').hide(); $(\'form#your-profile > table:first\').hide(); $(\'form#your-profile\').show(); });</script>' . "\n";
}
add_action('admin_head','hide_personal_options');

/**
 * Allow Shop Managers to edit and promote users with Sales Rep role 
 * using the 'woocommerce_shop_manager_editable_roles' filter.
 *
 * @param array $roles Array of role slugs for users Shop Managers can edit.
 * @return array
 */
function myextension_shop_manager_role_edit_capabilities( $roles ) {
    $roles[] = 'sales_representative';
    return $roles;
}
add_filter( 'woocommerce_shop_manager_editable_roles', 'myextension_shop_manager_role_edit_capabilities' );


// Hide WP All Import for Editors
function hide_pmxi_menu_for_editors_css() {
    if (current_user_can('editor')) {
        echo '<style>
            li#toplevel_page_pmxi-admin-home { display: none !important; }
        </style>';
    }
}
add_action('admin_head', 'hide_pmxi_menu_for_editors_css');

// Add class to body if cart is empty
function add_empty_cart_class( $classes ) {
    // Check if WooCommerce is active
    if ( class_exists( 'WooCommerce' ) ) {
        // Check if cart is empty
        if ( WC()->cart->is_empty() ) {
            $classes[] = 'empty-cart';
        }
    }
    return $classes;
}
add_filter( 'body_class', 'add_empty_cart_class' );

// Remove Default Shop Page Description
add_filter('woocommerce_register_post_type_product', 'remove_shop_default_description');
function remove_shop_default_description($args){
	$args['description'] = '';
	return $args;
}