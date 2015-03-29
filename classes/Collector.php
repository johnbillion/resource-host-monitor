<?php

if ( ! defined( 'RDM_SAMPLE_MODE' ) ) {
	define( 'RDM_SAMPLE_MODE', false );
}

class Resource_Host_Monitor_Collector {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_collector' ) );
		add_action( 'init',               array( $this, 'handle_report_uri' ) );
	}

	public function enqueue_collector() {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$plugin = Resource_Host_Monitor::init();

		wp_enqueue_script(
			'rhm_collector',
			$plugin->plugin_url( 'js/collector.js' ),
			array(
				'jquery',
			),
			$plugin->plugin_ver( 'js/collector.js' ),
			true
		);

		wp_localize_script( 'rhm_collector', 'rhm', array(
			'url' => add_query_arg( array(
				'_rhm'  => 'collect',
				'nonce' => wp_create_nonce( sprintf( 'rhm_collect_%d', get_current_user_id() ) ),
			), home_url() ),
		) );
	}

	/**
	 * Handle routing of the beacon request.
	 *
	 * This function identifies the beacon request and sets into motion the actions to record the beacon data.
	 *
	 * @since  1.0.0.
	 *
	 * @return void
	 */
	public function handle_report_uri() {
		// Check to make sure the a beacon request has been made
		if ( ! isset( $_GET['_rhm'] ) || 'collect' !== $_GET['_rhm'] ) {
			return;
		}

		if ( ! headers_sent() ) {
			status_header( 204 );
		}

		// Authenticate the request for either sampling mode or auth mode
		if ( true === RDM_SAMPLE_MODE && ! is_user_logged_in() ) {
			/**
			 * To accept every RDM_SAMPLE_FREQUENCY percent of requests in sample mode, pull a random number between
			 * 1 and the percentage of requests we should accept. If that number is 1, accept the request. This is a
			 * simple method to only allow a certain number of requests.
			 */
			$max_range     = ceil( 100 / (float) RDM_SAMPLE_FREQUENCY );
			$random_number = rand( 1, $max_range );

			if ( 1 !== $random_number ) {
				exit;
			}
		} else {
			// If you can turn on the plugin, the beacon should work for you
			if ( ! current_user_can( 'activate_plugins' ) ) {
				exit;
			}
		}

		// Verify the nonce is set
		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], sprintf( 'rhm_collect_%d', get_current_user_id() ) ) ) {
			exit;
		}

		// Grab the contents of the request
		$contents = json_decode( file_get_contents( 'php://input' ), true );

		if ( empty( $contents ) ) {
			exit;
		}

		$this->store_reports( $contents );

		exit;

	}

	public function store_reports( array $reports ) {

		// Make sure the expected data is sent with the request
		if ( ! isset( $reports['hosts'] ) ) {
			return;
		}

		$clean_data = array();
		$clean_data = array_map( array( $this, 'sanitize' ), $reports['hosts'] );
		$clean_data = array_filter( $clean_data );
		// $clean_data = array_unique( $clean_data );

		if ( empty( $clean_data ) ) {
			return;
		}

		$storage = Resource_Host_Monitor::init()->storage;

		foreach ( $clean_data as $host ) {
			if ( ! $storage->exists( $host ) ) {
				$storage->insert( $host );
			}
		}

	}

	public function sanitize( array $resource ) {
		return $resource;
	}

	public function allowed_fields() {
		return array(
			'protocol',
			'hostname',
			'port',
		);
	}

}
