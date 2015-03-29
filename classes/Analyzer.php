<?php

abstract class Resource_Host_Monitor_Analyzer {

	public function __construct() {

		$id = $this->get_id();

		add_action( 'rhm/storage/insert',                        array( $this, 'listen_insert' ), 10, 2 );
		add_action( 'rhm/storage/update',                        array( $this, 'listen_update' ), 10, 2 );
		add_action( "rhm/analyzer/{$id}",                        array( $this, 'analyze' ) );

		add_filter( 'manage_edit-resource_host_columns',         array( $this, 'admin_columns' ) );
		add_action( 'manage_resource_host_posts_custom_column' , array( $this, 'admin_column' ), 10, 2 );

	}

	abstract public function get_id();

	abstract public function analyze( $id );

	public function listen_insert( $id, array $resource ) {
	}

	public function listen_update( $id, array $resource ) {
	}

	public function admin_columns( array $cols ) {
		return $cols;
	}

	public function admin_column( $col_id, $post_id ) {
	}

}
