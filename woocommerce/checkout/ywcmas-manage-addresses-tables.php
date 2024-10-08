<?php
/**
 * Print tables based on multi shipping data array. One table for each different item in WC cart.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package YITH\MultipleShippingAddresses\Templates
 */

$cart          = WC()->cart->cart_contents;
$product_title = esc_html__( 'Product', 'yith-multiple-shipping-addresses-for-woocommerce' );
$qty_title     = esc_html__( 'Quantity', 'yith-multiple-shipping-addresses-for-woocommerce' );
$ship_title    = esc_html__( 'Ship to', 'yith-multiple-shipping-addresses-for-woocommerce' );

$first_table = true;
$row_index = 0; 
?>
<p>Ship to multiple addresses by selecting a shipping address for each item, or <a href="<?php echo get_permalink( get_option('woocommerce_myaccount_page_id') ); ?>/edit-address/" target="_blank">add a new address</a> to your address book.</p>
<?php if ( $multi_shipping_data ) : ?>
	<?php foreach ( $multi_shipping_data as $item_id => $item ) : ?>
		<?php
		if ( ! isset( $cart[ $item_id ] ) ) {
			continue;}
		?>
		<?php $product = wc_get_product( ! empty( $cart[ $item_id ]['variation_id'] ) ? $cart[ $item_id ]['variation_id'] : $cart[ $item_id ]['product_id'] ); ?>
		<?php
		if ( ! $product ) {
			continue;
		}
		
		?>
		<?php $first = true; ?>
		<table class="ywcmas_addresses_manager_table shop_table_responsive">
			<thead>
			<th class="ywcmas_addresses_manager_table_product_th"><?php echo $first_table ? esc_html( $product_title ) : ''; ?></th>
			<th class="ywcmas_addresses_manager_table_qty_th"><?php echo $first_table ? esc_html( $qty_title ) : ''; ?></th>
			</thead>
			<tbody>
			<?php // Now iterate over the shipping selectors of the current cart item. ?>
			<?php foreach ( $item as $shipping_selector_id => $shipping_selector ) : ?>
				<?php
				if ( ! isset( $shipping_selector['qty'] ) || ! isset( $shipping_selector['shipping'] ) ) {
					continue;
				}
				$row_index++;
				?>
				<tr class="ywcmas_addresses_manager_table_shipping_selection_row">
					<?php if ( $first ) : ?>
						<td class="ywcmas_addresses_manager_table_product_name_td" data-title="<?php echo esc_attr( $product_title ); ?>">
							<input class="ywcmas_addresses_manager_table_product_id" type="hidden" value="<?php echo esc_attr( $product->get_id() ); ?>">
							<input class="ywcmas_addresses_manager_table_item_id" type="hidden" value="<?php echo esc_attr( $item_id ); ?>">
							<span class="ywcmas_addresses_manager_table_img" title="<?php echo esc_html( $product->get_name() ); ?>"><?php echo $product->get_image( 'shop_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<span title="<?php echo esc_html( $product->get_name() ); ?>">SKU:<strong><?php echo esc_html( $product->get_sku() ); ?></strong></span>
							<div class="ywcmas_addresses_manager_table_item_data"><?php echo wp_kses_post( wc_get_formatted_cart_item_data( $cart[ $item_id ], false ) ); ?></div>
						</td>
					<?php else : ?>
						<td class="ywcmas_addresses_manager_table_product_name_td_empty"></td>
					<?php endif; ?>
					<td class="ywcmas_addresses_manager_table_qty_td" data-title="<?php echo esc_attr( $qty_title ); ?>">
						<div class="ywcmas_addresses_manager_table_qty_container">
							<?php if ( count( $item ) > 1 ) : ?>
								<div class="ywcmas_addresses_manager_table_remove">
									<div class="ywcmas_addresses_manager_table_remove_button">×</div>
								</div>
							<?php endif; ?>
							<div class="ywcmas_qty">
								<input class="ywcmas_addresses_manager_table_qty ywcmas_ms-diff-addr" data-item_index="<?php echo esc_attr( $row_index ); ?>" data-cart_id="<?php echo esc_attr( $item_id ); ?>" type="number" value="<?php echo esc_attr( $shipping_selector['qty'] ); ?>" min="1">
								<input class="ywcmas_addresses_manager_table_current_qty" data-item_index="<?php echo esc_attr( $row_index ); ?>" data-cart_id="<?php echo esc_attr( $item_id ); ?>" type="hidden" value="<?php echo esc_attr( $shipping_selector['qty'] ); ?>">
								<input class="ywcmas_addresses_manager_table_item_cart_id" type="hidden" value="<?php echo esc_attr( $item_id ); ?>">
								<input class="ywcmas_addresses_manager_table_shipping_selector_id" type="hidden" value="<?php echo esc_attr( $shipping_selector_id ); ?>">
								<a class="ywcmas_addresses_manager_table_update_qty_button" href="#"><?php esc_html_e( 'Update', 'yith-multiple-shipping-addresses-for-woocommerce' ); ?></a>
							</div>
							<?php 
							$extraclass = '';
							if(!empty($_COOKIE['acr_main_addr']) AND !empty($_COOKIE['acr_main_addr_selected_v1'])){
								$shipping_selector['shipping'] = $_COOKIE['acr_main_addr'];
								$extraclass = $_COOKIE['acr_main_addr'];
							}
							?>
							<div class="ywcmas_select extra-<?php echo $extraclass; ?>">
								
								<select class="ywcmas_addresses_manager_table_shipping_address_select"><?php 
								/*if(!empty($_POST['new_shipping_address'])){
									$shipping_selector['shipping'] = $_POST['new_shipping_address'];
								}*/
								/*if(!empty($_COOKIE['acr_main_addr']) AND !empty($_COOKIE['acr_main_addr_selected'])){
									$shipping_selector['shipping'] = $_COOKIE['acr_main_addr'];
								}*/

								yith_wcmas_print_addresses_select_options( $shipping_selector['shipping'], get_current_user_id(), true ); ?></select>
							</div>
						</div>
					</td>
				</tr>
				<?php $first = false; ?>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
			<tr>
				<td class="ywcmas_addresses_manager_table_foot"></td>
				<td class="ywcmas_addresses_manager_table_foot">
					<?php $different_addresses_limit = get_option( 'ywcmas_different_addresses_limit', '10' ); ?>
					<div class="ywcmas_more_addresses">
						<span class="ywcmas_excluded_item"><?php esc_html_e( 'This item can be shipped to one address only', 'yith-multiple-shipping-addresses-for-woocommerce' ); ?></span>
						<?php /* translators: %s is the number of  addresses you can send. */ ?>
						<span class="ywcmas_no_more_shipping_selectors_alert"><?php printf( esc_html__( 'You cannot ship to more than %d different addresses', 'yith-multiple-shipping-addresses-for-woocommerce' ), esc_html( $different_addresses_limit ) ); ?></span>
						<span class="ywcmas_increase_qty_alert"><?php esc_html_e( 'Increase the quantity to ship this item to other addresses', 'yith-multiple-shipping-addresses-for-woocommerce' ); ?></span>
						<input class="ywcmas_different_addresses_limit" type="hidden" value="<?php echo esc_attr( $different_addresses_limit ); ?>">
						<a class="ywcmas_new_shipping_selector_button"><?php esc_html_e( 'Ship this item to other addresses', 'yith-multiple-shipping-addresses-for-woocommerce' ); ?></a>
					</div>
				</td>
			</tr>
			</tfoot>
		</table>
		<?php $first_table = false; ?>
	<?php endforeach; ?>
<?php endif; ?>
