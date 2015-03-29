jQuery( function( $ ) {

	if ( ! window.performance ) {
		return;
	}

	if ( 'function' !== typeof window.performance.getEntriesByType ) {
		return;
	}

	var resources = window.performance.getEntriesByType( 'resource' );
	var hosts = {};

	// Populate `hosts` with a list of hosts on the page
	$.each( resources, function( i, resource ) {
		// @TODO check 'data:' resource types
		var url = new URL( resource.name );
		var key = url.protocol + url.hostname + url.port;
		hosts[ key ] = {
			protocol : url.protocol,
			hostname : url.hostname,
			port     : url.port
		};
	} );

	// We're not interested in our own site
	// @TODO handle ports too
	delete hosts[ 'http:' + location.hostname ];
	delete hosts[ 'https:' + location.hostname ];

	var report = {
		url   : location.href,
		hosts : hosts
	};

	$.ajax( rhm.url, {
		'type'        : 'POST',
		'data'        : JSON.stringify( report ),
		'processData' : false,
		'contentType' : 'application/json'
	} );

} );