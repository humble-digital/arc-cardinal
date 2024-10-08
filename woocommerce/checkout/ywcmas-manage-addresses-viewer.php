<?php
/**
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package YITH\MultipleShippingAddresses\Templates
 */

if ( empty( $address_id ) || '_no-address' === $address_id ) {
	$user_addresses = yith_wcmas_get_user_default_and_custom_addresses( get_current_user_id() );

	if ( get_user_meta( get_current_user_id(), 'yith_wcmas_default_address', true ) ) {
		$address_id = get_user_meta( get_current_user_id(), 'yith_wcmas_default_address', true );
	} elseif ( is_array( $user_addresses ) && ! empty( $user_addresses ) ) {
		$address_id = key( $user_addresses );
	} else {
		$address_id = '';
	}
}
?>
<div class="ywcmas_manage_addresses_viewer">
	<?php
	$params_add = array(
		'ajax'   => 'true',
		'action' => 'ywcmas_shipping_address_form',
	);
	$url        = add_query_arg( $params_add, admin_url( 'admin-ajax.php' ) );
	?>
	<div>
		<a class="button ywcmas_shipping_address_button_new" data-rel="prettyPhoto"
			href="<?php echo esc_attr( $url ); ?>"><?php esc_html_e( 'Add new shipping address', 'yith-multiple-shipping-addresses-for-woocommerce' ); ?></a>
	</div>
	<div class="ywcmas_single_address ywcmas_address_box" id="<?php echo esc_attr( $address_id ); ?>">
		<?php if ( $address_id ) : ?>
			<?php $address = yith_wcmas_get_user_address_by_id( $address_id ); ?>
			<?php if ( $address && is_array( $address ) ) : ?>
				<h5>
				<?php
				switch ( $address_id ) {
					case YITH_WCMAS_BILLING_ADDRESS_ID:
						echo esc_html__( 'Billing Address', 'yith-multiple-shipping-addresses-for-woocommerce' );
						break;
					case YITH_WCMAS_DEFAULT_SHIPPING_ADDRESS_ID:
						echo esc_html__( 'Default Shipping Address', 'yith-multiple-shipping-addresses-for-woocommerce' );
						break;
					default:
						echo esc_html( $address_id );
				}
				?>
					</h5>
				<address>
					<?php
					$prefix  = YITH_WCMAS_BILLING_ADDRESS_ID === $address_id ? 'billing_' : 'shipping_';
					$address = array(
						'first_name' => ! empty( $address[ $prefix . 'first_name' ] ) ? $address[ $prefix . 'first_name' ] : '',
						'last_name'  => ! empty( $address[ $prefix . 'last_name' ] ) ? $address[ $prefix . 'last_name' ] : '',
						'company'    => ! empty( $address[ $prefix . 'company' ] ) ? $address[ $prefix . 'company' ] : '',
						'address_1'  => ! empty( $address[ $prefix . 'address_1' ] ) ? $address[ $prefix . 'address_1' ] : '',
						'address_2'  => ! empty( $address[ $prefix . 'address_2' ] ) ? $address[ $prefix . 'address_2' ] : '',
						'city'       => ! empty( $address[ $prefix . 'city' ] ) ? $address[ $prefix . 'city' ] : '',
						'state'      => ! empty( $address[ $prefix . 'state' ] ) ? $address[ $prefix . 'state' ] : '',
						'postcode'   => ! empty( $address[ $prefix . 'postcode' ] ) ? $address[ $prefix . 'postcode' ] : '',
						'country'    => ! empty( $address[ $prefix . 'country' ] ) ? $address[ $prefix . 'country' ] : '',
					);

					$formatted_address = WC()->countries->get_formatted_address( $address );

					if ( ! $formatted_address ) {

						esc_html_e( 'You have not set up this type of address yet.', 'yith-multiple-shipping-addresses-for-woocommerce' );
					} else {
						echo $formatted_address; // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</address>
			<?php else : ?>
				<address><?php esc_html_e( 'Address does not exist', 'yith-multiple-shipping-addresses-for-woocommerce' ); ?></address>
			<?php endif; ?>
		<?php else : ?>
			<address><?php esc_html_e( 'Please, set a new address first', 'yith-multiple-shipping-addresses-for-woocommerce' ); ?></address>
		<?php endif; ?>
	</div>
	<div>
		<select class="ywcmas_addresses_manager_address_select"><?php yith_wcmas_print_addresses_select_options( $address_id, get_current_user_id() ); ?></select>
	</div>
	<?php if ( $address_id ) : ?>
		<div>
			<input class="ywcmas_manage_addresses_viewer_id_selected" type="hidden" value="<?php echo esc_attr( $address_id ); ?>">
			<?php
			$params_edit   = array(
				'ajax'       => 'true',
				'action'     => 'ywcmas_shipping_address_form',
				'address_id' => $address_id,
			);
			$url_edit      = add_query_arg( $params_edit, admin_url( 'admin-ajax.php' ) );
			$params_delete = array(
				'ajax'       => 'true',
				'action'     => 'ywcmas_delete_shipping_address_window',
				'address_id' => $address_id,
			);
			$url_delete    = add_query_arg( $params_delete, admin_url( 'admin-ajax.php' ) );

			$params_add_new = array(
				'ajax'       => 'true',
				'action'     => 'ywcmas_shipping_address_form',				 
			);
			$url_add_new    = add_query_arg( $params_add_new, admin_url( 'admin-ajax.php' ) );
			?>
			<a class="ywcmas_shipping_address_button_edit" data-rel="prettyPhoto"
				href="<?php echo esc_attr( $url_edit ); ?>"><?php esc_html_e( 'Edit', 'yith-multiple-shipping-addresses-for-woocommerce' ); ?></a>
			<a class="ywcmas_shipping_address_button_delete" data-rel="prettyPhoto"
				href="<?php echo esc_attr( $url_delete ); ?>"><?php esc_html_e( 'Delete', 'yith-multiple-shipping-addresses-for-woocommerce' ); ?></a>

			<a class="button ywcmas_shipping_address_button_new arc-always-show" data-rel="prettyPhoto" data-href="https://arccardinal.staging.tempurl.host/wp-admin/admin-ajax.php?ajax=true&amp;action=ywcmas_shipping_address_form" href="<?php echo esc_attr( $url_add_new ); ?>">
				<?php esc_html_e( 'Add new shipping address',  'yith-multiple-shipping-addresses-for-woocommerce' )?></a>
		</div>
	<?php endif; ?>
</div>
