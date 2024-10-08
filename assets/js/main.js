(function ($) {
	var theEl, place_order, coverButton,  shippToDif, defMShip, myShPage, response, toDiff, toMulti, arcCheckoutCanSubmit, 
	arcCheckoutMainDropdown,
 arcCheckoutProdDropdown, alladdrDr, shipping_address_select, primaryDropdown , secondaryDropdowns , selectedValue, html, acfShipInfo, addr, differentAddressCheckbox;
 	var map = {};
	var list = 0;
	var prodId = 0, lastProd = 0 ;

	var msQtyInputVal = 0;
	var msQtyInputItems = 0;
	var msQtyInputItemIndex = 0;
	var msQtyInputItemTotalVal = 0;
	var msQtyInputOtherItems = 0;
	var msQtyInputCurrentQrt = 0;
	var canChange = -1; 
	var updateCount = 0;
	var acr_main_addr_selected = 1; 
	var acr_main_addr = '';

	function autoCheckDifAddr() {
		shippToDif = $('#ship-to-different-address-checkbox');
		if(!shippToDif.prop('checked')){
			$('.woocommerce-billing-fields').addClass('arc-hide');
			shippToDif.click();

		}
	}

	function fixpageDefails(){
		defMShip = $('#ywcmas_manage_addresses_cb');
		if(!defMShip.prop('checked')){
			//$('#arc-ship-to-multiple-different-address-checkbox').prop('checked', true); 
			defMShip.prop('checked', true);
		}
		
	}
	fixpageDefails();
	function addDefaultOptionForsShippindDropdown(lType) {
		theEl = jQuery('.ywcmas_addresses_manager_address_select');
		if(theEl.length){ // && theEl.html().indexOf('Select another address') == -1
			//theEl.prepend('<option value="0" '+lType+'>Select another address</option>');
			theEl.change();
		}
		 
		
		console.log('addDefaultOptionForsShippindDropdown', theEl); 
		
	}
	function afterLoad() {
		$('body').addClass('arc-ywcmas-loaded');
	}
	place_order = $('#place_order');
	if(place_order.length){
		place_order.prop('disabled', true);
	}
	//autoCheckDifAddr(); 
	setTimeout(function () {
		afterLoad();
		//addDefaultOptionForsShippindDropdown('selected');
	}, 3000); 
	setTimeout(function () {
		addDefaultOptionForsShippindDropdown('selected');
	}, 4000); 
	setTimeout(function () {
		if(place_order.length){
			place_order.prop('disabled', false);
			console.log('enable-submit');
		}
	}, 6000); 
	$(document).on('change', '#fake_client_note', function () {
		//$('#order_comments').val(this.value);
		$('#client_note').val(this.value);
		//order_comments
	}); 
	myShPage = $('.ywcmas_custom_addresses_top');
	if(myShPage.length){
		myShPage.after('<a href="#" id="arc-add-new-address">Add New Address</a> ');
	}
	$(document).on('click', '#arc-add-new-address, #acr-open-new-add-popup', function (e) {
		e.preventDefault();
		$('.ywcmas_shipping_address_button_new').click();
	})
	$( document ).on( "ajaxComplete", function(event, xhr, settings ) {
            console.log('ajaxComplete', event, xhr, settings );
        
            response = xhr.responseJSON;
            if(typeof settings.url != 'undefined' && settings.url == '/?wc-ajax=update_order_review'){
           		//addDefaultOptionForsShippindDropdown(''); 	
           		mapProdAndAddr();
           		addPlaceholderCoverButton();
           		validateCheckoutDropdownBeforeSubmit();
           		//recoverDropdownVal();
           		//fixDropdownAdd();
           		if(typeof xhr.responseJSON != 'undefined' && typeof response.fragments.arc_cart_count != 'undefined'){
           			jQuery('.ast-icon-shopping-cart').attr('data-cart-total', response.fragments.arc_cart_count);
           		}
            } else if(typeof settings.url != 'undefined' && settings.url.indexOf('action=ywcmas_shipping_address_form') > -1){
            	if(settings.url.indexOf('action=ywcmas_shipping_address_form&address_id') == -1){
            		setTimeout(function () {
            			$('.woocommerce-address-fields .input-text').val('');	
            			jQuery('.woocommerce-address-fields #shipping_country').val('').change();
            		}, 50);
            		
            	}
            	
            } else if(typeof settings.data != 'undefined' && settings.data.indexOf('action=ywcmas_update_multi_shipping_data') > -1){
            	mapProdAndAddr();
            }
           
    })
	/*$(document).on('click', '#ship-to-different-address-checkbox', function () {
		if(this.checked){
			$('.woocommerce-billing-fields').addClass('arc-hide');
		} else {
			$('.woocommerce-billing-fields').removeClass('arc-hide');
		}
		$('body').toggleClass('arc-multi-shipping');
		$('#ywcmas_manage_addresses_cb').click();
	});*/

	function checkoutSextionsAdjustment() {
		toDiff = $('#arc-ship-to-different-address-checkbox');
		toMulti = $('#arc-ship-to-multiple-different-address-checkbox');
		//var manage_addresses_cb = $('#ywcmas_manage_addresses_cb');
		if(toDiff.prop('checked') || toMulti.prop('checked')){
			$('.col-1').hide();
			/*if(!manage_addresses_cb.prop('checked')){
				manage_addresses_cb.click();	
			}*/
		} else{
			$('.col-1').show();
		}
		if(toDiff.prop('checked')){
			if(toMulti.prop('checked')){
				$('body').removeClass('arc-multi-shipping');
			} else {
				$('body').addClass('arc-multi-shipping');
			}
			/*if(!manage_addresses_cb.prop('checked')){
					manage_addresses_cb.click();	
			}*/
		} else if(toMulti.prop('checked')){
				$('body').removeClass('arc-multi-shipping');
			} else {
				/*if(manage_addresses_cb.prop('checked')){
					manage_addresses_cb.click();	
				}*/
				$('body').addClass('arc-multi-shipping');
			}
		

	}
	checkoutSextionsAdjustment();
	$(document).on('click', '#arc-ship-to-different-address-checkbox, #arc-ship-to-multiple-different-address-checkbox', function () {
		checkoutSextionsAdjustment();
		if(this.id == 'arc-ship-to-multiple-different-address-checkbox'){
			if(this.checked){
				$('.ywcmas_multiple_addresses_manager').addClass('arc-hide-title');
				$('.ywcmas_manage_addresses_viewer_container .ywcmas_manage_addresses_viewer').hide();
			} else {
				$('.ywcmas_manage_addresses_viewer_container .ywcmas_manage_addresses_viewer').show();
				$('.ywcmas_multiple_addresses_manager').removeClass('arc-hide-title');
			}
		}
	});

	$(document).on('click', '.order-by-sku .button', function () {
		$('#sku-input').val('');
	})
	place_order = $('#place_order'); 
	
	
	function addPlaceholderCoverButton() {
		coverButton = $('#arc-place_order-cover'); 
		console.log('coverButton', coverButton.length, place_order.length);
		place_order = $('#place_order');
		if(coverButton.length == 0){
			if(place_order.length){
				place_order.before('<a href="#" id="arc-place_order-cover"></a>');
				console.log('coverButton-add', coverButton.length, place_order.length);
			}
		}
		$('.ywcmas_addresses_manager_address_select option[value="billing_address"], .ywcmas_addresses_manager_address_select option[value="default_shipping_address"]').remove();
		$('.ywcmas_addresses_manager_table_shipping_address_select option[value="billing_address"], .ywcmas_addresses_manager_table_shipping_address_select option[value="default_shipping_address"]').remove();
	}
	addPlaceholderCoverButton();
	arcCheckoutCanSubmit = 0;
	$(document).on('click', '#arc-place_order-cover', function (e) {
		theEl = $(this); 
		e.preventDefault();
		if(arcCheckoutCanSubmit == 0){		
			document.cookie = "acr_main_addr_selected_v1=0; expires=Thu, 18 Dec 3013 12:00:00 UTC; path=/";	
			$('.ywcmas_addresses_manager_table_shipping_address_select').change(); 
			arcCheckoutCanSubmit = 1;			
		} else {
			$('#place_order').click();
		}
	}); 
	
	//var arcCheckoutMainDropdown, arcCheckoutProdDropdown;
	function validateCheckoutDropdownBeforeSubmit(){
		if(arcCheckoutCanSubmit == 1){
			arcCheckoutMainDropdown = $('.ywcmas_addresses_manager_address_select');
			//.ywcmas_addresses_manager_address_select option[value="default_shipping_address"], 
			$('.ywcmas_addresses_manager_address_select option[value="billing_address"], .ywcmas_addresses_manager_address_select option[value="default_shipping_address"]').remove();
			$('.arc-has-error-msg').remove();
			if(arcCheckoutMainDropdown.html().indexOf('selected') == -1){
				arcCheckoutMainDropdown.after('<span class="arc-has-error-msg">Please select a valid address</span>');
				arcCheckoutCanSubmit = 0; 
				console.log('main-db', arcCheckoutMainDropdown.attr('class'));
			} else {
				arcCheckoutMainDropdown.removeClass('arc-has-error');
				
			}
			$('.ywcmas_addresses_manager_table_shipping_address_select option[value="billing_address"], .ywcmas_addresses_manager_table_shipping_address_select option[value="default_shipping_address"]').remove();
			arcCheckoutProdDropdown = $('.ywcmas_addresses_manager_table_shipping_address_select');
			arcCheckoutProdDropdown.each(function (e, i) {
					if(i.innerHTML.indexOf('selected') == -1){
						arcCheckoutCanSubmit = 0; 
						if(i.innerHTML.indexOf('Select an address') == -1){
							$(i).prepend('<option value="0">Select an address</option>');
							i.value = 0;
						}
						
						$(i).after('<span class="arc-has-error-msg">Please select a valid address</span>');
					} else {
						
					}
				});
				console.log('otherdrop', arcCheckoutCanSubmit)
				setTimeout(function () {
					if(arcCheckoutCanSubmit == 1){
						$('#place_order').click();
					} else {
						
						acr_main_addr_selected = 0; 
						differentAddressCheckbox = $('#arc-ship-to-multiple-different-address-checkbox');
						if(!differentAddressCheckbox.prop('checked')){
							differentAddressCheckbox.click(); 
						}
					}
				}, 100);
		}
	}
	$(document).on('input', '.input-text.qty', function () {
		if((this.value - 0) > arc_max_purchases){
			this.value = arc_max_purchases;
		}
	});

	function MSFieldsMaxQtyValidation(el) {
		msQtyInputItems = $('.ywcmas_addresses_manager_table_qty[data-cart_id="'+el.data('cart_id')+'"]');
		msQtyInputItemIndex = el.data('item_index');
		console.log('MSFieldsMaxQtyValidation', msQtyInputItemIndex, msQtyInputItems)
		if(msQtyInputItems.length == 1){
			if((el.val() - 0) > arc_max_purchases){
				//this.value = arc_max_purchases;
				el.val(arc_max_purchases);
			}
		} else if(msQtyInputItems.length > 1){
			msQtyInputItemTotalVal = 0;
			msQtyInputOtherItems = 0;
			msQtyInputItems.each(function (e, i) {
				msQtyInputItemTotalVal += (i.value - 0);
				console.log('-item_index' ,i.getAttribute('data-item_index'), el.data('item_index'));
				if(i.getAttribute('data-item_index') != el.data('item_index')){
					msQtyInputOtherItems += (i.value - 0);
				}
			});
			if(msQtyInputItemTotalVal > arc_max_purchases){
				//msQtyInputCurrentQrt = ($('.ywcmas_addresses_manager_table_current_qty[data-item_index="'+el.data('item_index')+'"]').val() - 0); 
				//msQtyInputVal = arc_max_purchases - msQtyInputCurrentQrt;
				msQtyInputVal = arc_max_purchases - msQtyInputOtherItems;
				//arc_max_purchases - (arc_max_purchases - msQtyInputCurrentQrt);
				if(msQtyInputCurrentQrt > msQtyInputVal){ 
					//msQtyInputVal = msQtyInputCurrentQrt; //wrtong
				}
				el.val(msQtyInputVal);
				console.log('MSFieldsMaxQtyValidation-new-val', el.val(),  msQtyInputOtherItems);
			}
			console.log('MSFieldsMaxQtyValidation-mm', msQtyInputItemTotalVal , arc_max_purchases);
		}
	}
	
	$(document).on('input', '.ywcmas_addresses_manager_table_qty', function () {
		MSFieldsMaxQtyValidation($(this));
		/*if((this.value - 0) > arc_max_purchases){
			this.value = arc_max_purchases;
		}*/
		/*if(msQtyInputVal){
			this.value = msQtyInputVal;
		}*/
	});

	
	function fixDropdownAdd() {

		if(canChange != -1){
			return false;
		}
		canChange = 0;
		alladdrDr = $('.ywcmas_addresses_manager_table_shipping_address_select');
		theEl = this;
		for (var i = 0; i < alladdrDr.length; i++) {
			setTimeout(function (el) {
				el.value = theEl.value;
				$(el).change();
				console.log('update_loop', el);
				updateCount++;
				if(updateCount>=alladdrDr.length){
					 canChange = -1;
					 updateCount = 0; 
				}
			}, 800, alladdrDr[i]);
			
		}
	}
	/*$(document).on('change', '.ywcmas_addresses_manager_address_select', function () {
		$('.ywcmas_addresses_manager_table_shipping_address_select').val(this.value).change();
		//fixDropdownAdd()
		/*setTimeout(function () {
			fixDropdownAdd();
		}, 1000);*/
		/*alladdrDr.each(function (e, i) {
			//$(i).val(theEl.value).change();
			i.value = theEl.value; 
			i.change();
		}) */
		
	//});
	
	document.cookie = "acr_main_addr_selected_v1=1; expires=Thu, 18 Dec 3013 12:00:00 UTC; path=/";
	$(document).on('click', '.ywcmas_addresses_manager_address_select', function () {
		document.cookie = "acr_main_addr_selected_v1=1; expires=Thu, 18 Dec 3013 12:00:00 UTC; path=/";
		acr_main_addr_selected = 1; 
	});
	$(document).on('click', '.ywcmas_addresses_manager_table_shipping_address_select', function () {
		document.cookie = "acr_main_addr_selected_v1=0; expires=Thu, 18 Dec 3013 12:00:00 UTC; path=/";
		acr_main_addr_selected = 0; 
	});
	$(document).on('change', '.ywcmas_addresses_manager_address_select', function () {
		shipping_address_select = $('.ywcmas_addresses_manager_table_shipping_address_select'); 
		document.cookie = "acr_main_addr="+this.value+"; expires=Thu, 18 Dec 3013 12:00:00 UTC; path=/";
		acr_main_addr = this.value;
		shipping_address_select.val(this.value);
		setTimeout(function () {
			shipping_address_select.change();
		}, 500); 
		/*setTimeout(function () {
			document.cookie = "acr_main_addr=0; expires=Thu, 18 Dec 3013 12:00:00 UTC";
		}, 6000);*/
		
	});
	//var primaryDropdow,secondaryDropdowns , selectedValue; 
	function recoverDropdownVal() {

		  primaryDropdown = document.querySelector('.ywcmas_addresses_manager_address_select');

	// Select all secondary dropdowns using their class name
	  secondaryDropdowns = document.querySelectorAll('.ywcmas_addresses_manager_table_shipping_address_select');

	// Listen for changes on the primary dropdown
	primaryDropdown.addEventListener('change', function() {
	    // Get the selected value of the primary dropdown
	      selectedValue = primaryDropdown.value;

	    // Update all secondary dropdowns to match the primary dropdown's value
	    secondaryDropdowns.forEach(dropdown => {
	        dropdown.value = selectedValue;
	    });
	});
	}
	

	function mapProdAndAddr() {
		 map = {};
		 list = $('.ywcmas_addresses_manager_table_shipping_selection_row');
		 prodId = 0; 
		 lastProd = 0;
		 addr = '';
		$('.arc-wc-ms-foelds').remove();

		document.cookie = "acr_main_addr="+$('.ywcmas_addresses_manager_address_select').val()+"; expires=Thu, 18 Dec 3013 12:00:00 UTC";
		html = "";
		list.each(function (e, i) {
			console.log('each-loop', e, i);
			prodId = $(i).find('.ywcmas_addresses_manager_table_product_id');
			if(prodId.length){
				lastProd = prodId.val();
			} else {
				prodId = lastProd; 
			}
			addr = $(i).find('.ywcmas_addresses_manager_table_shipping_address_select').val();
			if(typeof map[addr] == 'undefined'){
				map[addr] = {};
			}
			
			map[addr][e] = {
				qty : $(i).find('.ywcmas_addresses_manager_table_qty').val(),
				addr : addr,
				cart_id : $(i).find('.ywcmas_addresses_manager_table_item_cart_id').val(),
				//prodId : prodId,
			} 
			html += "<input type='hidden' class='arc-wc-ms-foelds' name='arc_ship_info["+addr+"]["+e+"][qty]' value='"+$(i).find('.ywcmas_addresses_manager_table_qty').val()+"'/>";
			html += "<input type='hidden' class='arc-wc-ms-foelds' name='arc_ship_info["+addr+"]["+e+"][addr]' value='"+addr+"'/>";
			html += "<input type='hidden' class='arc-wc-ms-foelds' name='arc_ship_info["+addr+"]["+e+"][cart_id]' value='"+$(i).find('.ywcmas_addresses_manager_table_item_cart_id').val()+"'/>";
		}); 
		acfShipInfo = $('#arc_ship_info');
		if(acfShipInfo.length == 0){
			$('form.woocommerce-checkout').append(html);
			//('<input type="hidden" name="arc_ship_info" id="arc_ship_info"/>');
			acfShipInfo = $('#arc_ship_info');
		}
		acfShipInfo.val(JSON.stringify(map));
		console.log('map-addr', map);
	}

	/*$(document).on('click', '#arc-ship-to-different-address-checkbox', function () {
		var manage_addresses_cb = $('#ywcmas_manage_addresses_cb');
		if(this.checked){
			$('body').addClass('arc-multi-shipping');
			$('.woocommerce-billing-fields').addClass('arc-hide');
			if(!manage_addresses_cb.prop('checked')){
				manage_addresses_cb.click();	
			}
			$('.col-1').hide();
			setTimeout(function () {
				//$('#ship-to-different-address-checkbox').click();
				//$('.woocommerce-shipping-fields, .shipping_address').slideDown();
			}, 1000);
		} else {
			$('.col-1').show();
			$('.woocommerce-billing-fields').removeClass('arc-hide');
			$('body').removeClass('arc-multi-shipping');
			if(manage_addresses_cb.prop('checked')){
				manage_addresses_cb.click();	
			}
			setTimeout(function () {
				//$('#ship-to-different-address-checkbox').click();
				//$('.woocommerce-shipping-fields, .shipping_address').slideUp();
			}, 1000);
		}
		
		
	});*/
	/*$(document).on('click', '#arc-ship-to-multiple-different-address-checkbox', function () {
		var manage_addresses_cb = $('#ywcmas_manage_addresses_cb');
		if(this.checked){
			$('body').removeClass('arc-multi-shipping');	
			$('.col-1').hide();
			if(!manage_addresses_cb.prop('checked')){
				manage_addresses_cb.click();	
			}
		} else{
			$('.col-1').show();
			//$('.woocommerce-billing-fields').removeClass('arc-hide');
			$('body').removeClass('arc-multi-shipping');
			if(manage_addresses_cb.prop('checked')){
				manage_addresses_cb.click();	
			}
		}
		
		
	}); */
	/*******my acccount*********/
	/*var urMa = $('.user-registration-MyAccount-content'); 
	if(urMa.length){
		//.elementor-widget-woocommerce-my-account .e-my-account-tab .woocommerce
		urMa.after('<div id="arc-user-ui" class="user-registration-page user-registration-account user-registration-orders woocommerce-account woocommerce-page woocommerce-orders"><div class="elementor-widget-woocommerce-my-account"><div class="e-my-account-tab"><div class="woocommerce"><div id="arc-user-registration-MyAccount-content"></div></div></div></div>');
	}

	$('.user-registration-MyAccount-navigation a').click(function (e) {
		
		var theEl = $(this);
		var arcMA = $('#arc-user-ui');
		var arcMAContent = $('#arc-user-registration-MyAccount-content');
		if(theEl.hasClass('urcma-user-logout')){

		} else if(theEl.hasClass('urcma-dashboard')){
			arcMA.hide();
			urMa.show();
			e.preventDefault();
		} else {
			e.preventDefault();
			$('.user-registration-MyAccount-navigation-link').removeClass('is-active');
			theEl.parent('.user-registration-MyAccount-navigation-link').addClass('is-active');
			$.post(wc_add_to_cart_params.ajax_url, {action:'arc_urma_load_content', link:theEl.attr('href')}, function (d) {
				urMa.hide();
				arcMA.show();
				arcMAContent.html(d);
			});
		}
	})*/
})(jQuery);