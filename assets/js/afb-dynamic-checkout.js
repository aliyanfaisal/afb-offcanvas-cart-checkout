jQuery(document).ready(function($) {
    console.log("i m checkout.js")
    // Check if vars are loadedc
    if (typeof afb_checkout_vars === 'undefined') {
        console.error('AFB Checkout: afb_checkout_vars not loaded!');
        return;
    }
    
    // Get nonce from localized script
    var afb_ajax_nonce = afb_checkout_vars.nonce;
    var afb_ajax_url = afb_checkout_vars.ajax_url;
    
    // Flag to prevent multiple simultaneous updates
    var updating = false;
    
    /**
     * Update order review function
     */
    function updateOrderReview() {
        console.log('AFB: updateOrderReview called');
        if (updating) {
			console.log("UPDATE ON ORDER REVIEW FOUND ")
			$(document.body).trigger('afb_update_multi_shipping');
			return; 
		}
        
        updating = true;
        
        // Show loading state
        $('.afb-order-loading').show();
        $('.afb-order-review').addClass('updating');
        
        $.ajax({
            type: 'POST',
            url: afb_ajax_url,
            data: {
                action: 'afb_update_order_review',
                nonce: afb_ajax_nonce
            },
            success: function(response) {
                if (response.success) {
                    // Find the loading indicator and replace everything after it
                    var $orderReview = $('.afb-order-review');
                    var $loading = $('.afb-order-loading');
                    
                    // Remove everything after the loading indicator (header + subtitle + loading)
                    $loading.nextAll().remove();
                    
                    // Add the new content after the loading indicator
                    $loading.after(response.data.content);
                    
                    // Trigger WooCommerce fragments update
//                     $(document.body).trigger('updated_wc_div');
                    
                    // Update any cart counters on the page
                    if (response.data.cart_count !== undefined) {
                        $('.cart-count, .cart-contents-count').text(response.data.cart_count);
                    }
                    
                    // Trigger custom event for other scripts
                    $(document.body).trigger('afb_order_review_updated', [response.data]);
                    
                    console.log('AFB: Order review updated successfully');
                } else {
                    console.error('AFB: Update failed', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('AFB: Order review update failed:', error);
            },
            complete: function() {
                // Hide loading state
                $('.afb-order-loading').hide();
                $('.afb-order-review').removeClass('updating');
                updating = false;
            }
        });
    }
    
    /**
     * Event Listeners
     */
    
    // Listen for WooCommerce checkout update events
    $(document.body).on('update_checkout', function() {
        console.log('AFB: update_checkout event received');
        setTimeout(()=>{
			updateOrderReview()
		},300);
    });
    
    // Listen for custom AFB update event
    $(document.body).on('afb_update_checkout', function() {
        console.log('AFB: afb_update_checkout event received');
		
		 
			 updateOrderReview();
	 
       
    });
    
    // Listen for cart changes
    $(document.body).on('added_to_cart removed_from_cart', function() {
        console.log('AFB: cart change event received');
        updateOrderReview();
    });
    
    // Make updateOrderReview globally accessible
    window.afbUpdateCheckout = function() {
        console.log('AFB: Manual update triggered');
        updateOrderReview();
    };
    
    // Alternative global function
    window.afb_update_order_review = updateOrderReview;
    
    // Handle quantity changes via +/- buttons
    $(document).on('click', '.afb-quantity-plus, .afb-quantity-minus', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $input = $button.siblings('.afb-quantity-input');
        var cartKey = $input.data('cart-key');
        var currentQty = parseInt($input.val()) || 1;
        var newQty;
        
        if ($button.hasClass('afb-quantity-plus')) {
            var maxQty = parseInt($input.attr('max')) || 999;
            newQty = Math.min(currentQty + 1, maxQty);
        } else {
            newQty = Math.max(currentQty - 1, 0);
        }
		
		
		if( $(this).hasClass("afb-quantity-plus") ){
			newQty = currentQty + 1;
		}
		else if($(this).hasClass("afb-quantity-minus")){
			newQty = currentQty - 1;
		}
        
        $input.val(newQty);
        updateCartQuantity(cartKey, newQty);
    });
    
    // Handle direct input changes
    $(document).on('change', '.afb-quantity-input', function() {
        var $input = $(this);
        var cartKey = $input.data('cart-key');
        var newQty = parseInt($input.val()) || 0;
        var maxQty = parseInt($input.attr('max')) || 999;
        
        // Validate quantity
        newQty = Math.max(0, Math.min(newQty, maxQty));
        $input.val(newQty);
        
        updateCartQuantity(cartKey, newQty);
    });
    
    // Handle remove item
    $(document).on('click', '#afb-checkout-panel .afb-cart-remove', function(e) {
        e.preventDefault();
        
        var cartKey = $(this).data('cart-key');
        var $item = $(this).closest('.afb-order-item');
        
        // Add removing class for visual feedback
        $item.addClass('removing');
        
        $.ajax({
            type: 'POST',
            url: afb_ajax_url,
            data: {
                action: 'afb_remove_cart_item',
                cart_key: cartKey,
                nonce: afb_ajax_nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update the entire dynamic content
                    var $orderReview = $('.afb-order-review');
                    var $loading = $('.afb-order-loading');
                    
                    // Remove everything after the loading indicator
                    $loading.nextAll().remove();
                    
                    // Add the new content after the loading indicator
                    $loading.after(response.data.content);
                    
                    // Update cart fragments
                    if (response.data.fragments) {
                        $.each(response.data.fragments, function(key, value) {
                            $(key).replaceWith(value);
                        });
                    }
                    
                    // Update cart counter
                    if (response.data.cart_count !== undefined) {
                        $('.cart-count, .cart-contents-count').text(response.data.cart_count);
                    }
                    
                    // Trigger events
                    $(document.body).trigger('removed_from_cart', [response.data.fragments, response.data.cart_hash, $item]);
                    $(document.body).trigger('afb_item_removed', [cartKey]);
                     
                } else {
                    console.error('AFB: Remove failed', response);
                }
            },
            error: function() {
                $item.removeClass('removing');
                alert(afb_checkout_vars.i18n.error_removing || 'Erreur lors de la suppression de l\'article');
            }
        });
    });
    
    /**
     * Function to update cart quantity
     */
    function updateCartQuantity(cartKey, quantity) {
        if (updating) return;
        
        updating = true;
        
        var $item = $('[data-cart-key="' + cartKey + '"]').closest('.afb-order-item');
        $item.addClass('updating');
        
        $.ajax({
            type: 'POST',
            url: afb_ajax_url,
            data: {
                action: 'afb_update_cart_quantity',
                cart_key: cartKey,
                quantity: quantity,
                nonce: afb_ajax_nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update the entire dynamic content
                    var $orderReview = $('.afb-order-review');
                    var $loading = $('.afb-order-loading');
                    
                    // Remove everything after the loading indicator
                    $loading.nextAll().remove();
                    
                    // Add the new content after the loading indicator
                    $loading.after(response.data.content);
                    
                    // Update cart fragments
                    if (response.data.fragments) {
                        $.each(response.data.fragments, function(key, value) {
                            $(key).replaceWith(value);
                        });
                    }
                    
                    // Update cart counter
                    if (response.data.cart_count !== undefined) {
                        $('.cart-count, .cart-contents-count').text(response.data.cart_count);
                    }
                    
                    // Trigger events
                    $(document.body).trigger('wc_cart_item_quantity_changed', [cartKey, quantity]);
                    $(document.body).trigger('afb_quantity_updated', [cartKey, quantity]);

					jQuery(document.body).trigger('update_checkout');
            //         if(AFB_STATE.deliveryOption==="multiship"){
            //             jQuery(document.body).trigger('call_split_cart');
            // // 			jQuery(document.body).trigger('update_checkout');
            //         }
            //         else{
            //             jQuery(document.body).trigger('call_combine_cart');
            //         }
					
// 					if(AFB?.splited){
					   $('.pdct-qty-'+cartKey).val(quantity)
// 					 }
					
                    
                    console.log('AFB: Quantity updated successfully');
                } else {
                    console.error('AFB: Quantity update failed', response);
                }
            },
            error: function() {
                // Revert quantity on error
                var $input = $('[data-cart-key="' + cartKey + '"]');
                $input.val($input.data('original-value') || 1);
                alert(afb_checkout_vars.i18n.error_updating || 'Erreur lors de la mise à jour de la quantité');
            },
            complete: function() {
                $item.removeClass('updating');
                updating = false;
            }
        });
    }
    
    /**
     * Utility Functions
     */
    
    // Store original values for error recovery
    $(document).on('focus', '.afb-quantity-input', function() {
        $(this).data('original-value', $(this).val());
    });
    
    // Debounced input handler for better performance
    var quantityUpdateTimeout;
    $(document).on('input', '.afb-quantity-input', function() {
        clearTimeout(quantityUpdateTimeout);
        var $input = $(this);
        
        quantityUpdateTimeout = setTimeout(function() {
            $input.trigger('change');
        }, 500);
    });
    
    // Initialize on page load
    if (typeof wc_checkout_params !== 'undefined') {
        $(document.body).trigger('init_checkout');
    }
	
	
	
	
	
	
	
	
	
	
	

	
	/// SPLIT CART ITEMS
	function splitCartItems() { 
         
        $.ajax({
            url: afb_ajax_url,  
            type: 'POST',
            data: {
                action: 'split_cart_items',
                nonce: afb_ajax_nonce,
				delivery_option: AFB_STATE.deliveryOption,
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) { 
                    
					jQuery(document.body).trigger('update_checkout');
                    // Reload the page to show updated cart
//                     location.reload();
                } 
            },
            error: function(xhr, status, error) { 
                console.error('AJAX Error:', error); 
            }
        });
    }
	
	
	jQuery('body').on('call_split_cart', function() {
		console.log("calliing synccc call_split_cart")
		splitCartItems()
// 		
 		setTimeout(()=>{
// 			  autoClickShipToDiffElements()
		  }, 400)
		
	});
	
	
	
	
	
	
	
	
	
	
	
	jQuery('body').on('call_combine_cart', function() {
		console.log("calliing synccc call_combine_cart")
		combineCartItems()
// 		jQuery(document.body).trigger('update_checkout');

	});
	
	function combineCartItems() { 
        
        // Make AJAX request
        $.ajax({
            url: afb_ajax_url, 
            type: 'POST',
            data: {
                action: 'combine_cart_items',
                nonce: afb_ajax_nonce ,
				delivery_option: AFB_STATE.deliveryOption,
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Success - show message and reload page/cart
                     jQuery(document.body).trigger('update_checkout');
                } else {
                    // Error from server
                    console.error('Error: ' + response.message); 
                }
            },
            error: function(xhr, status, error) {
                // AJAX error
                console.error('AJAX Error:', error); 
            }
        });
    }
});













// custom function to click split items in cart

function autoClickShipToDiffElements() {
    let intervalId;
	
	 AFB.splited = false
	console.log("AFB.splited ", AFB?.splited )
				
				
	let updateCheckoutHandlers = [];

		// Pause function
		function pauseUpdateCheckout() {
		  // Clone handlers BEFORE unbinding
		  updateCheckoutHandlers = [...(jQuery._data(document.body, "events")?.update_checkout || [])];
		  jQuery(document.body).off('update_checkout');
		}

		// Resume function
		function resumeUpdateCheckout() {
		  updateCheckoutHandlers.forEach(h => {
			// Rebind using original parameters
			jQuery(document.body).on(
			  h.namespace ? `update_checkout.${h.namespace}` : 'update_checkout',
			  h.selector || null,
			  h.handler
			);
		  });
		}

		 
		pauseUpdateCheckout();


	
    function clickNextElement() {
        if(AFB_STATE.deliveryOption != "multiship") return;
		
        const elements = jQuery('a.ship_to_diff_adr.add-dif-ship-adr.link_enabled_class');
        
        if (elements.length === 0) {
            console.log('No more elements with link_enabled_class found. Stopping.');
	
			setTimeout(()=>{
				 AFB.splited = true
				console.log("SETTTTTING AFB.splited as true")

				resumeUpdateCheckout();
  				jQuery(document.body).trigger('update_checkout');

			}, 1800)
			
            clearInterval(intervalId);
            return;
        }
        
        // Click the first element found
        const element = jQuery(elements[0]);
        console.log(`Clicking element with link_enabled_class (${elements.length} total remaining)`);
        element.click();
    }
    
    
    intervalId = setInterval(clickNextElement, 600);
    
     
    clickNextElement();
     
    return intervalId;
}