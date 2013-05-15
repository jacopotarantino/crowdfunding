var CrowdFundingAdmin = (function($) {
	var $ = jQuery;

	return {
		init : function() {
			var checks = $( '#edd_variable_pricing, #edd_price_options_mode' );

			if ( ! $( '#edd_variable_pricing' ).is( ':checked' ) )
				checks.trigger( 'change' )
		
			checks
				.attr( 'checked', true )
				.hide();

			$( 'label[for="edd_variable_pricing"], label[for="edd_price_options_mode"]' ).hide();
		}
	}
}(jQuery));

jQuery(document).ready(function($) {
	CrowdFundingAdmin.init();
});