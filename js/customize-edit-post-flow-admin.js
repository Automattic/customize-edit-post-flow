(function( wp, $ ) {
	var api = wp.customize;

	api.bind( 'ready', bindIt );

	function bindIt() {
		api.previewer.bind( 'edit-post', (url) => {
			window.location = url + '&return_uuid=' + api.settings.changeset.uuid;
		} );
	}


})( this.wp, this.jQuery );