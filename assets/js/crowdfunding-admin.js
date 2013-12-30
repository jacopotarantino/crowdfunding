var CrowdFundingAdmin = (function($) {
	var $ = jQuery;

	function noRewards() {
		var tohide = $( '#edd_price_fields, #edd_download_files, #edd_download_files + p, #edd_download_files + p + label' );

		$( '#campaign_norewards' ).click(function(e) {
			tohide.toggle();
		});

		if ( $( '#campaign_norewards' ).is( ':checked' ) ) {
			tohide.hide();
		}
	}

	return {
		init : function() {
			var checks = $( '#edd_variable_pricing, #edd_price_options_mode' );

			if ( ! $( '#edd_variable_pricing' ).is( ':checked' ) )
				checks.trigger( 'change' )
		
			checks
				.attr( 'checked', true )
				.hide();

			$( 'label[for="edd_variable_pricing"], label[for="edd_price_options_mode"]' ).hide();

			noRewards();
		}
	}
}(jQuery));

jQuery(document).ready(function($) {
	CrowdFundingAdmin.init();
});