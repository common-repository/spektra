jQuery( document ).ready(function(){
	
    jQuery( 'body' ).on( 'updated_checkout', function() {
		payment_method_init();

		jQuery('input[name="payment_method"]').change(function(){
			payment_method_init();
		});
	});

	
});

function payment_method_init(){
	var paymentMethod = jQuery("input[name='payment_method']:checked").val()
	var checkout_form = jQuery( 'form.woocommerce-checkout' );
	if(paymentMethod == spkParams.paymentMethodID)
    {
		checkout_form.on( 'checkout_place_order', doSpektraCheckout );
	}
	else
	{
		checkout_form.off( 'checkout_place_order', doSpektraCheckout );
	}
} 

var doSpektraCheckout = function(e)
{
	var checkout_form = jQuery( 'form.woocommerce-checkout' );
	var woo = jQuery('.woocommerce');
	var data = checkout_form.serialize();
	woo.addClass('processing').block({message:null,overlayCSS:{background:"#fff",opacity:.6}});
	jQuery.post('/?wc-ajax=spektra_checkout',data)
		.done(function(result){
			let data = result;//JSON.parse(result);
			woo.removeClass('processing').unblock();
			if(data.result == 'success')
			{
				Spektra(data.checkout_id);
			}
			else
			{
				checkout_form.prev('.woocommerce-notices-wrapper').html(data.messages);
				jQuery('html, body').animate({scrollTop: 0}, 2000);
			}
		})
		.fail(function(){
			
		});
	return false;
}