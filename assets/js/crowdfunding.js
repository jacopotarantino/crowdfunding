var Crowdfunding = {};

var delay = (function(){
	var timer = 0;

	return function(callback, ms){
		clearTimeout (timer);
		timer = setTimeout(callback, ms);
	};
})();

Crowdfunding.Campaign = ( function($) {
	var customPriceField,
	    priceOptions,
	    submitButton,
	    currentPrice,
	    startPledgeLevel;

	var formatCurrencySettings = {
		'decimalSymbol'    : atcfSettings.campaign.currency.decimal,
		'digitGroupSymbol' : atcfSettings.campaign.currency.thousands,
		'symbol'           : ''
	}

	function priceOptionsHandler() {
		customPriceField.keyup(function() {
			submitButton.attr( 'disabled', true );

			var price = $( this ).asNumber( formatCurrencySettings );

			delay( function() {
				Crowdfunding.Campaign.setPrice( price );

				if ( currentPrice < startPledgeLevel )
					Crowdfunding.Campaign.setPrice( startPledgeLevel );
			}, 1000);
		});

		priceOptions.click(function(e) {
			var pledgeLevel = $(this),
			    price = pledgeLevel.data( 'price' );

			if ( pledgeLevel.hasClass( 'inactive' ) )
				return;

			Crowdfunding.Campaign.setPrice( price );
		});
	}

	return {
		init : function() {
			customPriceField  = $( '#contribute-modal-wrap #atcf_custom_price' ),
			priceOptions      = $( '#contribute-modal-wrap .atcf-price-option' ),
			submitButton      = $( '#contribute-modal-wrap .edd-add-to-cart' ),
			
			Crowdfunding.Campaign.setBasePrice();
			priceOptionsHandler();
		},

		setPrice : function( price ) {
			customPriceField
				.val( price )
				.formatCurrency( formatCurrencySettings );

			currentPrice = price;

			priceOptions.each( function( index ) {
				var pledgeLevel = parseFloat( $(this).data( 'price' ) );

				if ( ( currentPrice >= pledgeLevel ) && ! $( this ).hasClass( 'inactive' ) )
					$( this ).find( 'input[type="radio"]' ).attr( 'checked', true );
			});

			submitButton.attr( 'disabled', false );
		},

		setBasePrice : function() {
			priceOptions.each( function( index ) {
				if ( ! $( this ).hasClass( 'inactive' ) && null == startPledgeLevel ) {
					startPledgeLevel = parseFloat( $(this).data( 'price' ) );

					Crowdfunding.Campaign.setPrice( startPledgeLevel );
				}
			});
		}
	}
}(jQuery));

Crowdfunding.SubmitCampaign = ( function($) {
	function addReward() {
		var rewardContainer = $( '.atcf-submit-campaign-rewards' );
		var reward          = rewardContainer.find( 'div:last-of-type' );

		$( '.atcf-submit-campaign-add-reward-button' ).click(function(e) {
			e.preventDefault();

			var newReward = reward.clone();
			var count     = rewardContainer.find( '.atcf-submit-campaign-reward' ).length;

			newReward.removeClass( 'static' );
			newReward.find( 'input, select, textarea' ).val( '' );
			newReward.find( 'input, select, textarea' ).each(function() {
				var label = $( this ).prev().attr( 'for' );
				var name  = $( this ).attr( 'name' );
				
				name  = name.replace( /\[(\d+)\]/, '[' + parseInt( count ) + ']');
				label = label.replace( /\[(\d+)\]/, '[' + parseInt( count ) + ']');

				$( this )
					.attr( 'name', name )
					.attr( 'id', name );

				$( this ).prev()
					.attr( 'for', label );

				newReward.insertBefore( $( '.atcf-submit-campaign-add-reward' ) ).show();
			});
		});
	}

	function removeReward() {
		$( 'body' ).on( 'click', '.atcf-submit-campaign-reward-remove a', function(e) {
			e.preventDefault();

			var reward          = $( this ).parents( '.atcf-submit-campaign-reward' );
			var rewardContainer = $( '.atcf-submit-campaign-rewards' );
			var count           = rewardContainer.find( '.atcf-submit-campaign-reward' ).length;

			if ( count == 1 || reward.hasClass( 'static' ) )
				return alert( atcfSettings.submit.i18n.oneReward );

			reward.remove();
		});
	}

	function validate() {
		$( '.atcf-submit-campaign' ).validate({
			errorPlacement: function(error, element) {},
			rules: {
				"title" : {
					required : true
				},
				"goal" : {
					required : true,
					number   : true
				},
				"contact-email" : {
					required : true,
					email    : true
				}
			},
			submitHandler: function(form) {
				form.submit();
			}
		});
	}

	function endDate() {
		$( '.atcf-toggle-neverending' ).click(function(e) {
			e.preventDefault();

			$( 'input[id="length"]' ).attr( 'disabled', ! $( 'input[id="length"]' ).attr( 'disabled' ) );
		});
	}

	return {
		init : function() {
			addReward();
			removeReward();
			validate();
			endDate();

			$( '#norewards' ).click(function() {
				$( '.atcf-submit-campaign-rewards' ).toggle();
			});

			if ( $( '#norewards' ).is( ':checked' ) ) {
				$( '.atcf-submit-campaign-rewards' ).hide();
			}
		}
	}
}(jQuery));

jQuery(document).ready(function($) {
	if ( atcfSettings.pages.is_submission === 1 )
		Crowdfunding.SubmitCampaign.init();

	//if ( atcfSettings.pages.is_campaign === 1 )
		Crowdfunding.Campaign.init();
});