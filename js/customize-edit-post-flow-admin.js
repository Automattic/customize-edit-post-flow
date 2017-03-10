(function( wp, $ ) {
	var api = wp.customize;

	api.postEditRedirect = function postEditRedirect( url ) {
		// If no un-saved changes, just go.
		if ( api.state( 'saved' ).get() ) {
			window.location = url;
		}

		if ( ! api.state( 'changesetStatus' ).get() ) {
			api.state( 'changesetStatus' ).bind( ( newStatus ) => {
				if ( newStatus = 'auto-draft' ) {
					unbindAYS();
					// in this case maybe we should mock a promise to the state change and use $.when()
					// so we're not issuing sequential XHRs
					doRedirect( url );
				}
			});
		} else {
			doRedirect( url );
		}
	}

	function addCustomizerReturnUrl( url ) {
		var sep = ( url.indexOf( '?' ) >= 0 ) ? '&' : '?';
		return url + sep + 'customizer_return=' + encodeURIComponent( window.location.href );
	}

	function redirectWithReturn( url ) {
		window.location = addCustomizerReturnUrl( url );
	}

	function doRedirect( url ) {
		var after = function() {
			redirectWithReturn( url );
		},
			promise;

		promise = wp.ajax.post( 'post_edit_redirect_save', { 'changeset_flow': api.settings.changeset.uuid } );
		promise.done( after );
	}

	function unbindAYS() {
		$( window ).off( 'beforeunload.customize-confirm' );
	}

	api.bind( 'ready', () => api.previewer.bind( 'edit-post', api.postEditRedirect ) );

})( this.wp, this.jQuery );

// TODOs
//
// 1. remove option on customizer save (and ensure it only gets triggered on a real save, not on a changeset)
// 2. when resuming an already-in-progress changeset, what happens if you go out to edit a post again?