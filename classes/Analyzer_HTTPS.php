<?php

class Resource_Host_Monitor_Analyzer_HTTPS extends Resource_Host_Monitor_Analyzer {

	public function __construct() {
		parent::__construct();
	}

	public function get_id() {
		return 'https';
	}

	public function analyze( $id ) {

		$storage  = Resource_Host_Monitor::init()->storage;
		$resource = $storage->get( $id );

		if ( ! $resource ) {
			return;
		}

		// @TODO port
		$uri = $resource['protocol'] . '//' . $resource['hostname'];

		if ( self::has_secure_version( $uri ) ) {
			$https = 1;
		} else {
			$https = 0;
		}

		$storage->update_analysis( $id, 'complete', $this, array(
			'https' => $https,
		) );

	}

	protected static function has_secure_version( $uri ) {
		$https_uri = set_url_scheme( $uri, 'https' );
		return Resource_Host_Monitor_Util::is_valid_uri( $https_uri );
	}

	public function listen_insert( $storage_id, array $resource ) {

		$storage = Resource_Host_Monitor::init()->storage;
		$id      = $this->get_id();

		if ( 'https:' === $resource['protocol'] ) {

			// If the resource in an https resource, we'll assume it's ok and skip our server-side check.
			// This will help avoid false negatives for non-public hosts used in dev environments
			$storage->update_analysis( $storage_id, 'complete', $this, array(
				'https' => 1,
			) );

		} else {

			wp_schedule_single_event( time(), "rhm/analyzer/{$id}", array(
				$storage_id
			) );

		}

	}

	public function admin_columns( array $cols ) {
		$cols[ $this->get_id() ] = __( 'HTTPS', 'resource-host-monitor' );
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

		if ( 'complete' === $resource[ $status_key ] ) {

			if ( $resource[ $data_key ]['https'] ) {
				_e( 'Yes', 'resource-host-monitor' );
			} else {
				_e( 'No', 'resource-host-monitor' );
			}

		} else {
			_e( 'Pending', 'resource-host-monitor' );
		}

	}

}

