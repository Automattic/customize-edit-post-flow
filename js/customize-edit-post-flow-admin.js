(function( wp, $ ) {
	var api = wp.customize;

	api.postEditRedirect = function postEditRedirect( url ) {
		if ( api.state( 'saved' ).get() ) {
			redirectWithReturn( url );
		}

		if ( ! api.state( 'changesetStatus' ).get() ) {
			api.state( 'changesetStatus' ).bind( ( newStatus ) => {
				if ( newStatus = 'auto-draft' ) {
					unbindAYS();
					redirectWithReturn( url );
				}
			});
		} else {
			redirectWithReturn( url );
		}
	}

	function addCustomizerReturnUrl( url ) {
		var sep = ( url.indexOf( '?' ) >= 0 ) ? '&' : '?';
		return url + sep + 'customizer_return=' + encodeURIComponent( window.location.href );
	}

	function redirectWithReturn( url ) {
		window.location = addCustomizerReturnUrl( url );
	}

	function unbindAYS() {
		$( window ).off( 'beforeunload.customize-confirm' );
	}

	api.bind( 'ready', () => api.previewer.bind( 'edit-post', api.postEditRedirect ) );

})( this.wp, this.jQuery );