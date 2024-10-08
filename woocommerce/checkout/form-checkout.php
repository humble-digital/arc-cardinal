<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

	<?php if ( $checkout->get_checkout_fields() ) : ?>
		
		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
		
		<div class="col2-set" id="customer_details">
			<div class="col-1">

				<div class="arc-billing-fields">
					<?php do_action( 'woocommerce_checkout_billing' ); ?>
				</div>
				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
				
				
			</div>

			<div class="col-2">
				
			</div> 
			<div class="arc-checkout-extra-sections-notes">
				<div id="arc_fake_custom_checkout_field" style="margin-bottom: 15px;">
					<h3 style="margin-bottom: 10px !important; ">Order notes (optional):</h3>
					<p class="form-row " id="fake_client_note_field" data-priority="">
						
						<span class="woocommerce-input-wrapper">
							<textarea name="fake_client_note" class="input-text " id="fake_client_note" placeholder="Add your notes here" rows="2" cols="5"></textarea></span>
						</p>
				</div>
			</div>
		</div>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

	<?php endif; ?>
	
	<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
	
	<h3 id="order_review_heading"><?php esc_html_e( 'Your order', 'woocommerce' ); ?></h3>
	
	<?php do_action( 'woocommerce_checkout_before_order_review' ); ?> 

	<div id="order_review" class="woocommerce-checkout-review-order">
		<?php do_action( 'woocommerce_checkout_order_review' ); ?>
	</div>

	<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
