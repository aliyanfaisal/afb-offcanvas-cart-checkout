(function($){
  if(!window.AFB){ window.AFB = {}; }
	
	console.log("loaded new file ne")
	
  const Cart = {
    open(){
      const $p = $('#afb-cart-panel'); if(!$p.length){ return; }
      $p.attr('aria-hidden','false').addClass('is-open');
      
      // Skip AJAX load since cart is now server-side rendered
      // Only load via AJAX if cart appears empty (no cart rows)
      const $items = $('[data-afb-cart-items]');
      const hasCartRows = $items.find('.afb-cart-row').length > 0;
      const hasEmptyMessage = $items.find('.afb-cart-empty').length > 0;
      
      if (!hasCartRows && !hasEmptyMessage) {
        // Cart container is completely empty, load via AJAX as fallback
        $.post(AFB_AJAX.url, { action:'afb_cart_open', nonce:AFB_AJAX.nonce }, function(res){
          if(res && res.success){
            $('[data-afb-cart-items]').html(res.data.items);
            $('[data-afb-cart-summary]').html(res.data.totals);
          } else if(res && res.data && res.data.message){
            $('[data-afb-cart-items]').html('<div class="afb-cart-empty">'+res.data.message+'</div>');
          }
        });
      }
    },
    close(){ $('#afb-cart-panel').attr('aria-hidden','true').removeClass('is-open'); },
    // Prefetch cart content only if not already server-side rendered
    prefetch(){
      const $items = $('[data-afb-cart-items]');
      const hasContent = $items.find('.afb-cart-row, .afb-cart-empty').length > 0;
      
      // Skip prefetch if cart is already rendered server-side
      if (hasContent) {
        return;
      }
      
      $.post(AFB_AJAX.url, { action:'afb_cart_open', nonce:AFB_AJAX.nonce }, function(res){
        if(res && res.success){
          const itemsHtml = res.data.items || '';
          const totalsHtml = res.data.totals || '';
          // Only update if content differs to avoid flicker
          const $totals = $('[data-afb-cart-summary]');
          if ($items.length && ($items.html() || '') !== itemsHtml) { $items.html(itemsHtml); }
          if ($totals.length && ($totals.html() || '') !== totalsHtml) { $totals.html(totalsHtml); }
        } else if(res && res.data && res.data.message){
          if ($items.length) { $items.html('<div class="afb-cart-empty">'+res.data.message+'</div>'); }
        }
      });
    },
    // Background refresh every minute to keep cart up-to-date silently
    startAutoRefresh(){
      if (this._refreshTimer) { return; }
      this._refreshTimer = setInterval(() => {
        $.post(AFB_AJAX.url, { action:'afb_cart_open', nonce:AFB_AJAX.nonce }, (res) => {
          if(res && res.success){
            const itemsHtml = res.data.items || '';
            const totalsHtml = res.data.totals || '';
            const $items = $('[data-afb-cart-items]');
            const $totals = $('[data-afb-cart-summary]');
            // Update only if changed
            if ($items.length && ($items.html() || '') !== itemsHtml) { $items.html(itemsHtml); }
            if ($totals.length && ($totals.html() || '') !== totalsHtml) { $totals.html(totalsHtml); }
          }
        });
      }, 60000); // 60 seconds
    }
  };
  window.AFB.Cart = Cart;
  $(document).on('click','[data-afb-close]', function(e){ e.preventDefault(); Cart.close(); });
	
  $(document).on('click','.afb-qty-btn', function(e){
    e.preventDefault();
    const $row=$(this).closest('.afb-cart-row'), key=$row.data('cart-key'), delta=Number($(this).data('delta'));
    $.post(AFB_AJAX.url,
		   { 
		action:'afb_cart_update_qty', 
		nonce:AFB_AJAX.nonce, 
		cart_key:key, 
		delta:delta 
	}, 
	function(res){
      if(res && res.success){
		  $('[data-afb-cart-items]').html(res.data.items); $('[data-afb-cart-summary]').html(res.data.totals); 
		  jQuery(document.body).trigger('afb_update_checkout');
	  }
    });
  });
	
	// Prefetch cart on initial page load and start background refresh
  jQuery(function(){
    if (window.AFB && AFB.Cart) {
      try { AFB.Cart.prefetch(); } catch(e) { /* noop */ }
      try { AFB.Cart.startAutoRefresh(); } catch(e) { /* noop */ }
    }
  });
	
  $(document).on('click','#afb-cart-panel .afb-cart-remove', function(e){
    e.preventDefault();
    let key=$(this).closest('.afb-cart-row').data('cart-key');
	  
	  if(!key){
		  key = $(this).data('cart-key')
	  }
	  
    $.post(AFB_AJAX.url,{ action:'afb_cart_remove', nonce:AFB_AJAX.nonce, cart_key:key }, function(res){
      if(res && res.success){ $('[data-afb-cart-items]').html(res.data.items); $('[data-afb-cart-summary]').html(res.data.totals); }
    });
  });
})(jQuery);

// Checkout opener: close cart and open left panel with Woo checkout
jQuery(document).on('click','.afb-btn--primary[href*="checkout"]', function(e){
  // Intercept to open left slide-in instead of navigating
  e.preventDefault();
  // Close cart
  if (window.AFB && AFB.Cart) { AFB.Cart.close(); }
  // Open checkout
  const $p = jQuery('#afb-checkout-panel');
  if(!$p.length){ window.location.href = this.href; return; }
  $p.attr('aria-hidden','false').addClass('is-open');
});

// Checkout open/close
(function($){
  if(!window.AFB){ window.AFB = {}; }
  window.AFB.Checkout = {
    open: function(){
      var $p = $('#afb-checkout-panel');
      if(!$p.length){ return; }
      $p.attr('aria-hidden','false').addClass('is-open');
      
      // Trigger custom event for checkout opened
      jQuery(document).trigger('afb_checkout_opened');
      
      // Initialize WooCommerce checkout if available
      if (typeof wc_checkout_params !== 'undefined' && jQuery('body').hasClass('woocommerce-checkout')) {
        jQuery('body').trigger('update_checkout');
      }
    },
    close: function(){
      $('#afb-checkout-panel').attr('aria-hidden','true').removeClass('is-open');
    }
  };
  $(document).on('click','[data-afb-open-checkout]', function(e){
    e.preventDefault();
    if(window.AFB && AFB.Cart){ AFB.Cart.close(); }
    if(window.AFB && AFB.Checkout){ AFB.Checkout.open(); }
  });
  $(document).on('click','#afb-checkout-panel [data-afb-close]', function(e){
    e.preventDefault(); if(window.AFB && AFB.Checkout){ AFB.Checkout.close(); }
  });
})(jQuery);



 
// Multi-step checkout flow: auth -> choice -> address -> payment
(function($){
  // Initialize state only if not already exists
  window.AFB_STATE = window.AFB_STATE || { 
    deliveryOption: null, 
    step: null,
    billingAddress: {},
    shippingAddress: {},
    pickupLocation: null
  };

  // Saved references for fields we need to move in/out of DOM
  var afbSavedShippingFieldsWrapper = null;
  var afbSavedAdditionalFields = null;
  var afbSavedInitialized = false;

  function afbEnsureSavedFields() {
    if (afbSavedInitialized) return;

    var $shippingWrapper = jQuery('.woocommerce-shipping-fields__field-wrapper');
    if ($shippingWrapper && $shippingWrapper.length) {
      afbSavedShippingFieldsWrapper = $shippingWrapper.detach();
    }
    var $additionalFields = jQuery('#afb-checkout-panel .woocommerce-additional-fields');
    if ($additionalFields && $additionalFields.length) {
      afbSavedAdditionalFields = $additionalFields.detach();
    }
    afbSavedInitialized = true;
  }

  // Main function to update UI when step changes
  function setStep(step) {
    // Only proceed if step is actually changing
    if (AFB_STATE.step === step) return;
    
    AFB_STATE.step = step;
    
    // Update step panels
    jQuery('.afb-step').addClass('is-hidden');
    jQuery(`.afb-step[data-step-panel="${step}"]`).removeClass('is-hidden');
    
    // Update step indicators
    jQuery('.afb-steps li').removeClass('is-current')
      .filter(`[data-step="${step}"]`).addClass('is-current');
    
    // Update navigation accessibility
    jQuery('.afb-steps li').each(function() {
      const $li = jQuery(this);
      $li.css('pointer-events', 
        $li.index() > jQuery(`.afb-steps li[data-step="${step}"]`).index() ? 'none' : 'auto');
    });
    
    // Special handling for address step
    if (step === 'address' && AFB_STATE.deliveryOption) {
        
      updateFieldsBasedOnDeliveryOption(AFB_STATE.deliveryOption);
        
        //triger update
        if(AFB_STATE.deliveryOption==="multiship"){
            jQuery(document.body).trigger('call_split_cart');
//             jQuery(document.body).trigger('update_checkout');
        }
        else{
            jQuery(document.body).trigger('call_combine_cart');
        }
        
    }
  }

  // Update form fields based on delivery option
  function updateFieldsBasedOnDeliveryOption(option) {
    // Reset all sections first
    jQuery('#shipping-address-fields').addClass('is-hidden');
    jQuery('#ship-to-different-address').prop('checked', false);
      
      
      
      
      
      if(option==="me"){
          jQuery('.afb-payment-step-title .first-step').show()
          jQuery('.afb-payment-step-title .all-steps').hide()
      }
      else{
          jQuery('.afb-payment-step-title .first-step').hide()
          jQuery('.afb-payment-step-title .all-steps').show()
      }
    
    // Hide the entire shipping section for "me" and "pickup" options
    if (option === 'me' || option === 'pickup') {
//       jQuery('.afb-checkout-column:last-child h3.afb-subtitle').text('VOTRE COMMANDE');
      jQuery('.afb-shipping-toggle').addClass('is-hidden');
    } else {
//       jQuery('.afb-checkout-column:last-child h3.afb-subtitle').text('ADRESSE DE LIVRAISON');
      jQuery('.afb-shipping-toggle').removeClass('is-hidden');
    }
      
      
      if(option === 'me' || option === 'other'){
         jQuery('.afb-extra-fields').removeClass('is-hidden')
      }else{
          jQuery('.afb-extra-fields').addClass('is-hidden')
      }
      
      
 
      
      
      // Ensure elements are captured before moving/removing
      afbEnsureSavedFields();
      var $shippingAddressContainer = jQuery('#afb-checkout-panel .shipping_address');
      
      if(option === 'multiship'){
          jQuery('.shipping_address').removeClass('is-hidden').attr("style","display:block !important")
          jQuery('#shipping_tiles').addClass('is-hidden')
          
         

          // Append saved fields into the shipping_address container
          if (afbSavedShippingFieldsWrapper && !$shippingAddressContainer.find('.woocommerce-shipping-fields__field-wrapper').length) {
              $shippingAddressContainer.append(afbSavedShippingFieldsWrapper);
          }
          if (afbSavedAdditionalFields && !$shippingAddressContainer.find('.woocommerce-additional-fields').length) {
              $shippingAddressContainer.append(afbSavedAdditionalFields);
          }
          

           $('.woocommerce-shipping-fields__field-wrapper').addClass('is-hidden')		 
		  


      }else{
          jQuery('.shipping_address').addClass('is-hidden').attr("style","display:none !important")
          jQuery('#shipping_tiles').removeClass('is-hidden')
          
         
          // Detach fields from DOM and keep references for later use
          var $existingShipping = $shippingAddressContainer.find('.woocommerce-shipping-fields__field-wrapper');
          if ($existingShipping.length) {
              afbSavedShippingFieldsWrapper = $existingShipping.detach();
          }
          var $existingAdditional = $shippingAddressContainer.find('.woocommerce-additional-fields');
          if ($existingAdditional.length) {
              afbSavedAdditionalFields = $existingAdditional.detach();
          }

           $('.woocommerce-shipping-fields__field-wrapper').removeClass('is-hidden')
	  
      }
      
      
      
      
      if(option === "multiship" || option === "other"){
          jQuery('#ship-to-different-address').parent().addClass('is-hidden')
          
          let shipping_address_container = document.querySelector('.shipping_address');

            if (shipping_address_container) { 
                shipping_address_container.childNodes.forEach(function(node) {
                 
                    if (
                        node.nodeType === Node.TEXT_NODE && 
                        (
                            node.nodeValue.includes("Voulez-vous expédier à")
                        ||
                            node.nodeValue.includes("מעוניין לשלוח למספר") 
                        ||
                            node.nodeValue.includes("Do you want to ship to multiple") 
                        )
                    ) {
                        node.remove(); // Remove the unwanted text node
                    }
                });
            }
      }
    
    // Show pickup location field for "pickup" option
//     if (option === 'pickup') {
// 	  // Only add if it doesn't already exist
// 	  if ($('.afb-pickup-section').length === 0) {
// 		$('.afb-checkout-column:last-child').prepend(`
// 		  <div class="afb-pickup-section">
// 			<h3 class="afb-subtitle">POINT DE RETRAIT</h3>
// 			<div class="form-group">
// 			  <label class="form-label" for="pickup_location">BOUTIQUE <span class="required">*</span></label>
// 			  <select name="pickup_location" id="pickup_location" class="form-input" required>
// 				<option value="">-- Choisissez une boutique --</option>
// 				<option value="Tel Aviv Store">Tel Aviv Store</option>
// 			  </select>
// 			</div>
// 		  </div>
// 		`);
// 	  }
// 	} else {
// 	  $('.afb-pickup-section').remove();
// 	}
    
	  
	  
		var $pickup_field = $('.afb-pickup-section')
		console.log("$pickup_field", $pickup_field, option)
	  // Updated condition to replace your existing code
		if (option === 'pickup') {
			// Only add if it doesn't already exist
			if ($('.afb-pickup-section').length) {
				
				$pickup_field.removeClass('is-hidden')
				console.log("showing $pickup_field section")
				
				$('.afb-checkout-column:last-child').prepend( $pickup_field );

				// Load stores via AJAX
		  
				$.ajax({
					url: AFB_AJAX.url,
					type: 'POST',
					data: {
						action: 'get_store_users',
						nonce:  AFB_AJAX.nonce, 
					},
					success: function(response) {
						if (response.success && response.data.length > 0) {
							$('#pickup_location option').remove()
							response.data.forEach(function(store) {
								const storeInfo = store.store_city ? `${store.name} - ${store.store_city}` : store.name;
// 								$('#pickup_location').append(`<option value="${store.id}">${storeInfo}</option>`);
    							$('#pickup_location').append(`<option value="${store.id}">${storeInfo}</option>`);
							});
						}
					}
				});
			}
		} else {
// 			$('.afb-pickup-section').remove();
			$pickup_field.addClass('is-hidden')
		}
    switch(option) {
      case 'other':
        $('#ship-to-different-address').prop('checked', true).trigger('change');
		$('input[name="ship_to_multi_address"]').prop('checked', false).trigger('change');
        break;
        
      case 'multiship':
        $('#ship-to-different-address').prop('checked', true).trigger('change');
		$('input[name="ship_to_multi_address"]').prop('checked', true).trigger('change');
        $(document).trigger('afb:multishipEnabled');
        break;
    }
  }

  // Initialize checkout flow
  function startCheckout() {
    const loggedIn = $('#afb-checkout-panel .afb-panel__body').data('loggedin') == 1;
    setStep(loggedIn ? 'choice' : 'auth');
  }

  // Event Handlers ==============================================

  // Delivery option selection
  $(document).on('click', '.afb-choice__item', function(e) {
    e.preventDefault();
    const option = $(this).data('option');
    AFB_STATE.deliveryOption = option;

    jQuery(document.body).trigger('afb:deliveryOptionChanged', option);
    
    // Update hidden field in checkout form
    $('#afb_delivery_option').val(option);
    
	 $(".afb-address-step-title").html( $(this).html() );
    
    // Show/hide split items button based on delivery option
    if (option === 'multiship') {
      $('#afb-split-items-container').show();
    } else {
      $('#afb-split-items-container').hide();
    }
    
    setStep('address');
  });

  // Shipping address toggle
  $(document).on('change', '#ship-to-different-address', function() {
    $('#shipping-address-fields').toggleClass('is-hidden', !$(this).is(':checked'));
  });

  // Saved address selection
  $(document).on('change', '#afb-shipping-address-select', function() {
    if ($(this).val() && typeof afbLoadAddress === 'function') {
      afbLoadAddress($(this).val());
    }
  });

  // Split items button click handler
  $(document).on('click', '#afb-split-items-btn', function(e) {
    e.preventDefault();
    jQuery(document.body).trigger('call_split_cart');
  });

  // Navigation buttons
  $(document).on('click', '[data-next-step], [data-prev-step]', function(e) {
    const step = $(this).data('next-step') || $(this).data('prev-step');
	  
	if(step=="payment"){
		const afb_terms = document.getElementById("afb_terms");
		if(afb_terms && !afb_terms.checked){
			document.querySelector("#terms_alert").style.display = "block"
			e.preventDefault()
			return;
		}
		else{
			document.querySelector("#terms_alert").style.display = "none"
		}
	}
	 
    if (step) setStep(step);
  });

  // Custom place order button validation
  $(document).on('click', '#place_order', function(e) {
    const afb_terms = document.getElementById("afb_terms");
    if(!afb_terms.checked){
      document.querySelector("#terms_alert").style.display = "block"
      e.preventDefault()
      return false;
    }
    else{
      document.querySelector("#terms_alert").style.display = "none"
    }
  });

  // Prevent form submission if terms are not accepted
  $(document).on('submit', 'form.checkout, form.afb-checkout-form', function(e) {
    const afb_terms = document.getElementById("afb_terms");
    if(!afb_terms.checked){
      document.querySelector("#terms_alert").style.display = "block"
      e.preventDefault()
      return false;
    }
    else{
      document.querySelector("#terms_alert").style.display = "none"
    }
  });

  // Order submission
//   $(document).on('click', '#place_order', function(e) {
//     e.preventDefault();
    
//     // Validate pickup location if needed
//     if (AFB_STATE.deliveryOption === 'pickup' && !$('#pickup_location').val()) {
//       alert('Veuillez sélectionner un point de retrait.');
//       return false;
//     }
    
//     // Save pickup location if applicable
//     if (AFB_STATE.deliveryOption === 'pickup') {
//       AFB_STATE.pickupLocation = $('#pickup_location').val();
//     }
    
//     // Prepare form data
//     const formData = {
//       action: 'afb_process_checkout',
//       delivery_option: AFB_STATE.deliveryOption,
//       billing: AFB_STATE.billingAddress,
//       shipping: AFB_STATE.shippingAddress,
//       pickup_location: AFB_STATE.pickupLocation,
//       payment_method: $('input[name="payment_method"]:checked').val(),
//       nonce: AFB_AJAX.nonce
//     };
    
//     // Submit via AJAX
//     $.post(AFB_AJAX.url, formData)
//       .then(response => {
//         if (response.success) {
//           window.location.href = response.redirect;
//         } else {
//           alert(response.data?.message || 'Erreur lors du traitement de votre commande.');
//         }
//       })
//       .catch(() => alert('Erreur de connexion au serveur.'));
//   });

  // Initialize checkout override
  if (window.AFB?.Checkout?.open) {
    const originalOpen = AFB.Checkout.open;
    AFB.Checkout.open = function() {
      originalOpen.call(this);
      startCheckout();
    };
  }

})(jQuery);






// AFB Patch: force-skip auth step if logged in flag is set
try{
  var $panel = document.getElementById('afb-checkout-panel');
  if($panel && $panel.getAttribute('data-loggedin') === '1'){
    document.documentElement.classList.add('afb-user-logged-in');
    // jump to step 2 if step-nav exists
    var nav = $panel.querySelector('.afb-steps');
    if(nav){
      var current = nav.querySelector('[data-step="2"]');
      if(current){
        // activate step 2
        $panel.setAttribute('data-current-step','2');
        nav.querySelectorAll('li').forEach(function(li){
          li.classList.remove('current');
        });
        current.parentElement.classList.add('current');
        // show content pane 2
        $panel.querySelectorAll('.afb-step-pane').forEach(function(p){
          p.style.display = (p.getAttribute('data-step') === '2') ? 'block' : 'none';
        });
      }
    }
  }
}catch(e){ console.warn('AFB auth-skip patch:', e); }








/// get and set fields inserted to head via wp_head in root file
 function afbFillCheckoutFields() {
    if (typeof afbUserData === "undefined") return;

    const form = document.querySelector(".afb-checkout-form");
    if (!form) return;

    // Map fallbacks for billing fields
    const fallbacks = {
        billing_first_name: afbUserData.first_name || afbUserData.display_name,
        billing_last_name: afbUserData.last_name || "",
        billing_email: afbUserData.billing_email || afbUserData.user_email,
    };

    Object.entries(afbUserData).forEach(([key, val]) => {
        // If value missing, try fallback
        if (!val && fallbacks[key]) {
            val = fallbacks[key];
        }
        if (!val) return;

        const el = form.querySelector(`#${key}`);
        if (el) {
            el.value = val;
            el.dispatchEvent(new Event("change", { bubbles: true }));
        }
    });
}





// Run when checkout is ready
document.addEventListener("DOMContentLoaded", afbFillCheckoutFields);
 










