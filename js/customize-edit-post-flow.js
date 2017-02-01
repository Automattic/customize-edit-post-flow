(function( wp, $ ) {
	var api = wp.customize;

	function init() {
		removeUnpreviewable();
		followEditLinks();
	}

	function followEditLinks() {
		$( 'body' ).on( 'click', '.post-edit-link', function( event ) {
			api.preview.send( 'edit-post', event.target.href );
		} );
	}

	function removeUnpreviewable() {
		$( '.post-edit-link.customize-unpreviewable' ).removeClass( 'customize-unpreviewable' );
	}

	api.bind( 'preview-ready', () => setTimeout( init, 100) );
})( this.wp, this.jQuery );