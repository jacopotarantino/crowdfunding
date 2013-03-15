var CrowdFunding = (function($) {
	var $ = jQuery;

	return {
		init : function() {
			this.addReward();
			this.removeReward();
		},

		addReward : function() {
			var rewardContainer = $( '.atcf-submit-campaign-rewards' );
			var reward          = rewardContainer.find( '.atcf-submit-campaign-reward.static' );

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
				});

				newReward.insertBefore( $( '.atcf-submit-campaign-add-reward' ) );
			});
		},

		removeReward : function() {
			$( 'body' ).on( 'click', '.atcf-submit-campaign-reward-remove a', function(e) {
				e.preventDefault();

				var reward          = $( this ).parents( '.atcf-submit-campaign-reward' );
				var rewardContainer = $( '.atcf-submit-campaign-rewards' );
				var count           = rewardContainer.find( '.atcf-submit-campaign-reward' ).length;

				if ( count == 1 || reward.hasClass( 'static' ) )
					return alert( CrowdFundingL10n.oneReward );

				reward.remove();
			});
		}
	}
}(jQuery));

jQuery(document).ready(function($) {
	CrowdFunding.init();
});