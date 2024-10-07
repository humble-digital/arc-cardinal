<?php
function import_shipments_to_woocommerce() {
	$xml_file_path = __DIR__ . '/shipments.xml';

	if (!file_exists($xml_file_path)) {
		print_r( "<br>" . "XML file not found: " . $xml_file_path);
		return;
	}

	// Load the XML file
	$xml = simplexml_load_file($xml_file_path);
	if (!$xml) {
		print_r( "<br>" . "Failed to load XML file: " . $xml_file_path);
		return;
	}

	// Loop through each row of the XML file
	foreach ($xml->Worksheet->Table->Row as $i => $row) {
		$data = [];
		foreach ($row->Cell as $cell) {
			$data[] = (string)$cell->Data;
		}

		// Skip header row or rows without an Order #
		if ($data[0] == 'Shipment' || empty($data[2])) {
			continue;
		}

		print_r("<br>" . sanitize_text_field($data[0]));

		// Extract relevant shipment information
		$shipment_data = array(
			'shipment_id'          => sanitize_text_field($data[0]),  // Shipment ID
			'ship_date'            => sanitize_text_field($data[1]),  // Ship Date
			'magento_order_id'     => sanitize_text_field($data[2]),  // Order # (Magento Order ID)
			'order_date'           => sanitize_text_field($data[3]),  // Order Date
			'ship_to_name'         => sanitize_text_field($data[4]),  // Ship-to Name
			'total_quantity'       => sanitize_text_field($data[5]),  // Total Quantity
			'order_status'         => sanitize_text_field($data[6]),  // Order Status
			'purchased_from'       => sanitize_text_field($data[7]),  // Purchased From
			'customer_name'        => sanitize_text_field($data[8]),  // Customer Name
			'customer_email'       => sanitize_email($data[9]),       // Email
			'customer_group'       => sanitize_text_field($data[10]), // Customer Group
			'billing_address'      => sanitize_text_field($data[11]), // Billing Address
			'shipping_address'     => sanitize_text_field($data[12]), // Shipping Address
			'payment_method'       => sanitize_text_field($data[13]), // Payment Method
			'shipping_information' => sanitize_text_field($data[14]), // Shipping Information
		);

		// Find WooCommerce order by Magento Order ID meta key
		$order_query = new WC_Order_Query(array(
			'limit' => 1,
			'meta_key' => 'magento_order_id',
			'meta_value' => $shipment_data['magento_order_id'],
		));
		$orders = $order_query->get_orders();

		if (empty($orders)) {
			print_r( "<br>" . "Order not found for Magento Order ID: " . $shipment_data['magento_order_id']);
			continue; // Skip if order is not found
		}

		$order = $orders[0]; // Get the first (and only) order from the query

		// Convert the ship date to the format WooCommerce uses (Y-m-d H:i:s)
		$ship_date = gmdate('Y-m-d H:i:s', strtotime($shipment_data['ship_date']));

		// Update WooCommerce order with shipment details
		$order->update_meta_data('_shipment_id', $shipment_data['shipment_id']);
		$order->update_meta_data('_ship_date', $ship_date);
		$order->update_meta_data('_total_quantity_shipped', floatval($shipment_data['total_quantity']));
		$order->update_meta_data('_shipping_information', $shipment_data['shipping_information']);
		$order->update_meta_data('_ship_to_name', $shipment_data['ship_to_name']);
		$order->update_meta_data('_shipping_address', $shipment_data['shipping_address']);

		// Save the changes to the order
		$order->save();

//		print_r( $orders[0] );
		print_r( "<br>" . "Shipment imported/updated for Magento Order ID: " . $shipment_data['magento_order_id']);

//		if($i > 50){
//			die;
//		}
	}
}

// Hook into WooCommerce initialization
add_action('init', 'import_shipments_to_woocommerce');
