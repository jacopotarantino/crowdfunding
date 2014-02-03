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

	var formatCurrencySettings = {};

	function priceOptionsHandler() {
		customPriceField.keyup(function() {
			var price = $( this ).asNumber( formatCurrencySettings );

			delay( function() {
				if ( price < startPledgeLevel )
					Crowdfunding.Campaign.findPrice( startPledgeLevel );
				else
					Crowdfunding.Campaign.findPrice( price );
			}, 1000);
		});

		priceOptions.click(function(e) {
			var pledgeLevel = $(this),
			    price       = Crowdfunding.Campaign.parsePrice( $(this) );

			if ( pledgeLevel.hasClass( 'inactive' ) )
				return;

			$(this).find( 'input[type="radio"]' ).attr( 'checked', true );

			customPriceField
				.val( price )
				.formatCurrency( formatCurrencySettings );
		});
	}

	return {
		init : function() {
			formatCurrencySettings = {
				'decimalSymbol'    : atcfSettings.campaign.currency.decimal,
				'digitGroupSymbol' : atcfSettings.campaign.currency.thousands,
				'symbol'           : ''
			}

			currentPrice      = 0;
			customPriceField  = $( '#contribute-modal-wrap #atcf_custom_price' );
			priceOptions      = $( '#contribute-modal-wrap .atcf-price-option' );
			submitButton      = $( '#contribute-modal-wrap a.edd-add-to-cart' );

			Crowdfunding.Campaign.setBasePrice();
			priceOptionsHandler();
		},

		findPrice : function( price ) {
			var foundPrice  = {
				price : 0,
				el    : null
			};

			customPriceField
				.val( price )
				.formatCurrency( formatCurrencySettings );

			currentPrice = price;

			priceOptions.each( function( index ) {
				var price       = price = Crowdfunding.Campaign.parsePrice( $(this) );
				var pledgeLevel = parseFloat( price );

				if ( ( currentPrice >= pledgeLevel ) && ! $( this ).hasClass( 'inactive' ) ) {
					var is_greater = pledgeLevel > foundPrice.price;

					if ( is_greater ) {
						foundPrice = {
							price : pledgeLevel,
							el    : $(this)
						}
					}
				}
			});

			foundPrice.el.find( 'input[type="radio"]' ).attr( 'checked', true );
		},

		setBasePrice : function() {
			var basePrice = {
				price : 1000000000, // something crazy
				el    : null
			}

			priceOptions.each( function( index ) {
				if ( ! $( this ).hasClass( 'inactive' ) ) {
					var price = Crowdfunding.Campaign.parsePrice( $(this) );

					if ( parseFloat( price ) < parseFloat( basePrice.price ) ) {
						basePrice = {
							price : price,
							el     : $( this )
						}
					}
				}
			});

			startPledgeLevel = parseFloat( basePrice.price );

			if ( null != basePrice.el )
				basePrice.el.find( 'input[type="radio"]' ).attr( 'checked', true );

			if ( atcfSettings.campaign.isDonations != 1 ) {
				customPriceField
					.val( startPledgeLevel )
					.formatCurrency( formatCurrencySettings );
			}
		},

		parsePrice : function( el ) {
			var price = el.data( 'price' );

			price = price.split( '-' );
			price = price[0];

			return price;
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
			newReward.find( 'input:not([type=hidden]), select, textarea' ).val( '' );
			newReward.find( 'input, select, textarea' ).each(function() {
				var label = $( this ).prev().attr( 'for' );
				var name  = $( this ).attr( 'name' );

				name  = name.replace( /\[(\d+)\]/, '[' + parseInt( count ) + ']');

				if ( label ) {
					label = label.replace( /\[(\d+)\]/, '[' + parseInt( count ) + ']');
				}

				$( this )
					.attr( 'name', name )
					.attr( 'id', name )
					.attr( 'readonly', false );

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

	function endDate() {
		$( '.atcf-toggle-neverending' ).click(function(e) {
			e.preventDefault();

			$( 'input[id="length"]' ).attr( 'disabled', ! $( 'input[id="length"]' ).attr( 'disabled' ) );
		});

		if ( $( '.atcf-toggle-neverending' ).hasClass( 'active' ) )
			$( '.atcf-toggle-neverending' ).trigger( 'click' );
	}

	return {
		init : function() {
			addReward();
			removeReward();
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
	if ( atcfSettings.pages.is_submission )
		Crowdfunding.SubmitCampaign.init();

	if ( atcfSettings.pages.is_campaign )
		Crowdfunding.Campaign.init();
});