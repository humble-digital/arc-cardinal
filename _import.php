<?php
function import_magento_users_to_woocommerce($xml_file_path) {
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

	// Register a custom WooCommerce role if needed
	if (!get_role('customer')) {
		add_role('customer', __('Customer'), array(
			'read'         => true,
			'edit_posts'   => false,
			'delete_posts' => false,
		));
	}

	// Loop through each row of the XML file
	foreach ($xml->Worksheet->Table->Row as $row) {
		$data = [];
		foreach ($row->Cell as $cell) {
			$data[] = (string)$cell->Data;
		}

		// Skip header row or rows without data
		if ($data[0] == 'ID' || empty($data[2])) {
			continue;
		}

		// Map XML data to WooCommerce customer data
		$user_data = array(
			'display_name'      => sanitize_text_field($data[1]),  // Name
			'user_email'        => sanitize_email($data[2]),       // Email
			'first_name'        => sanitize_text_field($data[22]), // Billing Firstname
			'last_name'         => sanitize_text_field($data[23]), // Billing Lastname
			'role'              => 'customer',
			'user_login'        => sanitize_user($data[2]),        // Use email as username
		);

		// Map additional fields to user meta fields
		$user_meta = array(
			'magento_user_id'                   => sanitize_text_field($data[0]),
			'billing_phone'                     => sanitize_text_field($data[4]),  // Phone
			'billing_postcode'                  => sanitize_text_field($data[5]),  // ZIP
			'billing_country'                   => sanitize_text_field($data[6]),  // Country
			'billing_state'                     => sanitize_text_field($data[7]),  // State/Province
			'billing_address_1'                 => sanitize_text_field($data[17]), // Street Address
			'billing_city'                      => sanitize_text_field($data[18]), // City
			'shipping_address_1'                => sanitize_text_field($data[13]), // Shipping Address
			'shipping_city'                     => sanitize_text_field($data[18]), // City
			'shipping_postcode'                 => sanitize_text_field($data[5]),  // ZIP
			'shipping_country'                  => sanitize_text_field($data[6]),  // Country
			'shipping_state'                    => sanitize_text_field($data[7]),  // State/Province
			'billing_company'                   => sanitize_text_field($data[21]), // Company
			'billing_first_name'                => sanitize_text_field($data[22]), // Billing Firstname
			'billing_last_name'                 => sanitize_text_field($data[23]), // Billing Lastname
			'gender'                            => sanitize_text_field($data[16]), // Gender
			'date_of_birth'                     => sanitize_text_field($data[14]), // Date of Birth
			'vat_number'                        => sanitize_text_field($data[20]), // VAT Number
			'customer_since'                    => sanitize_text_field($data[8]),  // Customer Since
			'account_created_in'                => sanitize_text_field($data[11]), // Account Created in
			'confirmed_email'                   => sanitize_text_field($data[10]), // Confirmed email
			'account_lock'                      => sanitize_text_field($data[24]), // Account Lock
			'status'                            => sanitize_text_field($data[25]), // Status
			'customer_type'                     => sanitize_text_field($data[26]), // Customer Type
			'sales_representative'              => sanitize_text_field($data[28]), // Sales Representative
			'do_not_sell_or_share_info'         => sanitize_text_field($data[29]), // Don't Sell or Share My Personal Information
			'business_category'                 => sanitize_text_field($data[30]), // Business Category
			'distributor'                       => sanitize_text_field($data[31]), // Distributor
			'get_sample_products'               => sanitize_text_field($data[32]), // Get sample products
			'how_did_you_hear_about_us'         => sanitize_text_field($data[33]), // How did you hear about us
			'job_title'                         => sanitize_text_field($data[34]), // Job Title
			'purchasing_budget'                 => sanitize_text_field($data[35]), // Purchasing budget
			'arc_cardinal_sales_representative' => sanitize_text_field($data[36]), // Name of Arc Cardinal Sales Representative
			'tableware_needs'                   => sanitize_text_field($data[37]), // Tell Us About Your Tableware Needs
			'purchase_in_next_six_months'       => sanitize_text_field($data[38]), // Will you be making purchase in the next six months
			'sap_id'                            => sanitize_text_field($data[39]), // SAP ID
		);

		// Check if the user already exists by email
		if (email_exists($user_data['user_email'])) {
			continue;
			$user_id = email_exists($user_data['user_email']);
			$user_data['ID'] = $user_id;

			// Update existing user
			wp_update_user($user_data);

			// Update user meta data
			foreach ($user_meta as $meta_key => $meta_value) {
				update_user_meta($user_id, $meta_key, $meta_value);
			}
		} else {
			// Create new user
			$user_id = wp_insert_user($user_data);

			if( !is_int($user_id) ){
				print_r($data[0]);
				print_r($user_id);die;
			}

			// Add user meta data
			foreach ($user_meta as $meta_key => $meta_value) {
				update_user_meta($user_id, $meta_key, $meta_value);
			}
		}

		// Optionally, log or display messages
		print_r( "<br>" . "User imported/updated: " . $user_data['user_email'] . ' ID: ' . $user_id);
	}
}

// Example usage: Call this function with the path to the XML file
import_magento_users_to_woocommerce(__DIR__.'/customers.xml');