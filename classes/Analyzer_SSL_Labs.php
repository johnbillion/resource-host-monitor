<?php

class Resource_Host_Monitor_Analyzer_SSL_Labs extends Resource_Host_Monitor_Analyzer {

	protected $endpoint = 'https://api.ssllabs.com/api/v2/analyze';

	public function __construct() {

		$id = $this->get_id();

		$this->hook = "rhm/schedule/{$id}";

		add_action( $this->hook, array( $this, 'action_schedule' ) );

		parent::__construct();
	}

	public function get_id() {
		return 'ssl_labs';
	}

	public function action_schedule() {
		// This triggers once a minute while we have pending analysis

		$storage = Resource_Host_Monitor::init()->storage;

		// Find a pending analysis and refresh

		if ( $analyzing = $storage->get_analyzing( $this ) ) {

			$this->analyze( $analyzing['ID'] );

			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, $this->hook );

		} else if ( $next = $storage->get_next_to_analyze( $this ) ) {

			$this->analyze( $next['ID'] );

			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, $this->hook );

		}

	}

	public function analyze( $id ) {

		$storage  = Resource_Host_Monitor::init()->storage;
		$resource = $storage->get( $id );

		if ( ! $resource ) {
			return;
		}

		// @TODO port
		$uri = $resource['protocol'] . '//' . $resource['hostname'];

		if ( ! self::has_secure_version( $uri ) ) {
			$storage->update_analysis( $id, 'invalid', $this );
			return;
		}

		$storage->update_analysis( $id, 'analyzing', $this );

		$json = $this->request( $resource['hostname'] );

		if ( ! $json ) {
			// if an api request fails, what shall we do?
			// currently this just falls through and it'll re-run one minute later
			return;
		}

		$result = json_decode( $json );
		$data   = array(
			'raw' => $json,
		);

		switch ( strtoupper( $result->status ) ) {

			case 'READY':
				$storage->update_analysis( $id, 'complete', $this, $data );
				break;

			case 'ERROR':
				$storage->update_analysis( $id, 'error', $this, $data );
				break;

			case 'DNS':
			case 'IN_PROGRESS':
			default:
				// nothing, we alrady have a status of 'analyzing'
				break;

		}

	}

	public function request( $host, array $params = array(), array $args = array() ) {
		$params   = array_merge( array(
			'host'      => $host,
			'fromCache' => 'on',
			'all'       => 'done',
		), $params );
		$url      = add_query_arg( $params, $this->endpoint );
		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			// @TODO pass through wp_errors instead
			return false;
		}

		$body = trim( wp_remote_retrieve_body( $response ) );

		// check json for validity:
		if ( ! json_decode( $body ) ) {
			return false;
		}

		return $body;

	}

	protected static function has_secure_version( $uri ) {
		$https_uri = set_url_scheme( $uri, 'https' );
		return Resource_Host_Monitor_Util::is_valid_uri( $https_uri );
	}

	public function listen_insert( $storage_id, array $resource ) {

		if ( ! wp_next_scheduled( $this->hook ) ) {
			wp_schedule_single_event( time() + 10, $this->hook );
		}

	}

	public function admin_columns( array $cols ) {
		$cols[ $this->get_id() ] = __( 'SSL Labs', 'resource-host-monitor' );
		return $cols;
	}

	public function admin_column( $col_id, $post_id ) {
		if ( $this->get_id() !== $col_id ) {
			return;
		}

		$storage  = Resource_Host_Monitor::init()->storage;
		$resource = $storage->get( $post_id );

		$status_key = sprintf( 'analyzer_status_%s', $this->get_id() );
		$data_key   = sprintf( 'analyzer_data_%s', $this->get_id() );

		switch ( $resource[ $status_key ] ) {

			case 'complete':
				$results = array();
				$data    = json_decode( $resource[ $data_key ]['raw'] );
				foreach ( $data->endpoints as $result ) {
					$results[] = esc_html( $result->ipAddress . ': ' . $result->grade );
				}
				echo implode( '<br>', $results );
				break;

			case 'invalid':
				_e( 'N/A', 'resource-host-monitor' );
				break;

			case 'analyzing':
				_e( 'Analyzing', 'resource-host-monitor' );
				break;

			case 'pending':
			default:
				_e( 'Pending', 'resource-host-monitor' );
				break;
		}

	}

}

