(function($){

	function takeOverNoticeDismissal() {
		var $el = $( '#customizer-return' );
		if ( ! $el.length ) {
			return;
		}
		// remove the old, in with the new
		$el.find( '.notice-dismiss' ).off( 'click.wp-dismiss-notice' ).click( function( event ) {
			event.preventDefault();
			if ( ! confirm( window._editPostFlowNotice.confirm ) ) {
				return;
			}

			wp.ajax.post( 'post_edit_redirect_delete', {
				nonce: window._editPostFlowNotice.nonce
			}).then( function(){
				fadeAndRemove( $el );
			});
		});
	}

	function fadeAndRemove( $el ) {
		$el.fadeTo( 100, 0, function() {
			$el.slideUp( 100, function() {
				$el.remove();
			});
		});
	}

	$( document ).ready( function(){
		setTimeout( takeOverNoticeDismissal, 10 );
	});

})(jQuery);