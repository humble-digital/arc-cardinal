<?php
function import_magento_orders_to_woocommerce($xml_file_path) {
	$order_status_mapping = array(
		'canceled' => 'wc-cancelled',
		'complete' => 'wc-completed',
		'on hold' => 'wc-on-hold',
		'pending' => 'wc-pending',
		'processing' => 'wc-processing'
	);

	if (!file_exists($xml_file_path)) {
		error_log("XML file not found: " . $xml_file_path);
		return;
	}

	// Load the XML file
	$xml = simplexml_load_file($xml_file_path);
	if (!$xml) {
		error_log("Failed to load XML file: " . $xml_file_path);
		return;
	}

	$statuses = [];

	// Loop through each row of the XML file
	foreach ($xml->Worksheet->Table->Row as $row) {
		$data = [];
		foreach ($row->Cell as $cell) {
			$data[] = (string)$cell->Data;
		}

		// Skip header row or rows without an email
		if ($data[0] == 'ID' || empty($data[11])) {
			continue;
		}

		// Extract relevant order information
		$order_data = array(
			'order_id'                   => sanitize_text_field($data[0]),    // Order ID
			'purchase_point'             => sanitize_text_field($data[1]),    // Purchase Point
			'purchase_date'              => sanitize_text_field($data[2]),    // Purchase Date
			'bill_to_name'               => sanitize_text_field($data[3]),    // Bill-to Name
			'ship_to_name'               => sanitize_text_field($data[4]),    // Ship-to Name
			'grand_total_base'           => sanitize_text_field($data[5]),    // Grand Total (Base)
			'grand_total_purchased'      => sanitize_text_field($data[6]),    // Grand Total (Purchased)
			'status'                     => $order_status_mapping[ strtolower(sanitize_text_field($data[7])) ],    // Status
			'billing_address'            => sanitize_text_field($data[8]),    // Billing Address
			'shipping_address'           => sanitize_text_field($data[9]),    // Shipping Address
			'shipping_information'       => sanitize_text_field($data[10]),   // Shipping Information
			'customer_email'             => sanitize_email($data[11]),        // Customer Email
			'customer_group'             => sanitize_text_field($data[12]),   // Customer Group
			'subtotal'                   => sanitize_text_field($data[13]),   // Subtotal
			'shipping_handling'          => sanitize_text_field($data[14]),   // Shipping and Handling
			'customer_name'              => sanitize_text_field($data[15]),   // Customer Name
			'payment_method'             => sanitize_text_field($data[16]),   // Payment Method
			'total_refunded'             => sanitize_text_field($data[17]),   // Total Refunded
			'company_name'               => sanitize_text_field($data[18]),   // Company Name
			'pickup_location_code'       => sanitize_text_field($data[19]),   // Pickup Location Code
			'allocated_sources'          => sanitize_text_field($data[20]),   // Allocated sources
			'sku_list'                   => explode(', ', sanitize_text_field($data[21])), // SKUs
			'qty_list'                   => explode(', ', sanitize_text_field($data[22])), // Quantities
			'braintree_transaction_source' => sanitize_text_field($data[23]), // Braintree Transaction Source
		);

		// Check if an order with the same order_id already exists
		//$existing_order_id = wc_get_orders(['meta_key' => 'magento_order_id', 'meta_value' => $order_data['order_id'], 'return' => 'ids']);
//		if (!empty($existing_order_id)) {
//			error_log("Order already exists: " . $order_data['order_id']);
//			continue; // Skip orders that already exist
//		}

		if ( (int) sanitize_text_field($data[0]) <= (int) $_GET['test_import'] ) {
			continue;
		}

		// Split billing and shipping addresses into components
		$billing_parts = explode(',', $order_data['billing_address']);
		$shipping_parts = explode(',', $order_data['shipping_address']);

		// Assuming the address format provided:
		// [Company Name, Street Number, Street Name, City, State, Postal Code]
		$billing_company = isset($billing_parts[0]) ? trim($billing_parts[0]) : '';
		$billing_street_number = isset($billing_parts[1]) ? trim($billing_parts[1]) : '';
		$billing_street_name = isset($billing_parts[2]) ? trim($billing_parts[2]) : '';
		$billing_city = isset($billing_parts[3]) ? trim($billing_parts[3]) : '';
		$billing_state = isset($billing_parts[4]) ? trim($billing_parts[4]) : '';
		$billing_postcode = isset($billing_parts[5]) ? trim($billing_parts[5]) : '';

		$shipping_company = isset($shipping_parts[0]) ? trim($shipping_parts[0]) : '';
		$shipping_street_number = isset($shipping_parts[1]) ? trim($shipping_parts[1]) : '';
		$shipping_street_name = isset($shipping_parts[2]) ? trim($shipping_parts[2]) : '';
		$shipping_city = isset($shipping_parts[3]) ? trim($shipping_parts[3]) : '';
		$shipping_state = isset($shipping_parts[4]) ? trim($shipping_parts[4]) : '';
		$shipping_postcode = isset($shipping_parts[5]) ? trim($shipping_parts[5]) : '';

		// Check if the user exists by email
		$user = get_user_by('email', $order_data['customer_email']);
		if (!$user) {
			error_log("User not found: " . $order_data['customer_email']);
			continue; // Skip orders without a matching user
		}

		// Create a new WooCommerce order
		$order = wc_create_order(array('customer_id' => $user->ID));

		// Save order_id as a meta field
		$order->add_meta_data('magento_order_id', $order_data['order_id'], true);


		// Set billing address
		$order->set_billing_first_name($order_data['bill_to_name']);
		$order->set_billing_company($billing_company);
		$order->set_billing_address_1($billing_street_number . ' ' . $billing_street_name);
		$order->set_billing_city($billing_city);
		$order->set_billing_state($billing_state);
		$order->set_billing_postcode($billing_postcode);

		// Set shipping address
		$order->set_shipping_first_name($order_data['ship_to_name']);
		$order->set_shipping_company($shipping_company);
		$order->set_shipping_address_1($shipping_street_number . ' ' . $shipping_street_name);
		$order->set_shipping_city($shipping_city);
		$order->set_shipping_state($shipping_state);
		$order->set_shipping_postcode($shipping_postcode);

		// Set additional order data
		$order->set_payment_method($order_data['payment_method']);
		$order->set_shipping_total(floatval($order_data['shipping_handling']));
		$order->set_total(floatval($order_data['grand_total_purchased']));
		$order->set_status(strtolower($order_data['status']));

		// Add products to the order by SKU
		foreach ($order_data['sku_list'] as $index => $sku) {
			$product_id = wc_get_product_id_by_sku($sku);
			if ($product_id) {
				$quantity = isset($order_data['qty_list'][$index]) ? intval($order_data['qty_list'][$index]) : 1;
				$order->add_product(wc_get_product($product_id), $quantity); // Add product and quantity to order
			} else {
				error_log("Product not found for SKU: " . $sku);
			}
		}

		// Set the order date to the "Purchase Date"
		$purchase_date = strtotime($order_data['purchase_date']); // Convert to Unix timestamp
		$order->set_date_created(gmdate('Y-m-d H:i:s', $purchase_date));

		// Add additional fields as order meta
		$additional_fields = ['order_id', 'purchase_point', 'purchase_date', 'grand_total_base',
			'grand_total_purchased', 'billing_address', 'shipping_address',
			'shipping_information', 'customer_group', 'subtotal',
			'shipping_handling', 'total_refunded', 'company_name',
			'pickup_location_code', 'allocated_sources',
			'braintree_transaction_source'];
		foreach ($additional_fields as $field_name) {
			$order->add_meta_data($field_name, $order_data[$field_name]);
		}

		// Calculate totals and save the order
		$order->calculate_totals();
		$order->save();

		// Optionally, log or display messages
		print_r( PHP_EOL . "Order imported/created for user: " . $order_data['customer_email']);
	}
}

add_action('init', function() {
	import_magento_orders_to_woocommerce( __DIR__ . '/orders.xml' );
});
