<?php

abstract class Resource_Host_Monitor_Util {

	public static function is_valid_uri( $uri ) {
		$response = wp_remote_get( $uri, array(
			/**
			 * Do a HEAD request for efficiency.
			 */
			'method'      => 'HEAD',

			/**
			 * HEAD requests will not redirect by default. It is important that redirection works in case the
			 * recorded URI is not the final URI. For instance, if the recorded URI is "google.com" when the actual
			 * URI is "www.google.com", we need to make sure the resolution works.
			 */
			'redirection' => 1,
		) );

		/**
		 * If the response is a WP_Error, a TCP connection cannot be made to the URI, suggesting that it is not valid. We
		 * base the validity of the URL on this result.
		 */
		return ( false === is_wp_error( $response ) );
	}

}
