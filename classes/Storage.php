<?php

/**
 * @TODO this can become an abstract class (or interface) at some point to abstract storage somewhere else
 */
class Resource_Host_Monitor_Storage {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ), 1 );
	}

	public function register() {
		register_post_type( 'resource_host', array(
			'public'              => false,
			'show_ui'             => true,
			'menu_icon'           => 'dashicons-carrot',
			'map_meta_cap'        => true,
			'capabilities'        => array(
				'create_posts' => 'do_not_allow',
			),
			'labels'              => array(
				'name'               => __( 'Resource Hosts', 'resource-host-monitor' ),
				'singular_name'      => __( 'Resource Host', 'resource-host-monitor' ),
				'menu_name'          => __( 'Resource Hosts', 'resource-host-monitor' ),
				'search_items'       => __( 'Search Resource Hosts', 'resource-host-monitor' ),
				'not_found'          => __( 'No Resource Hosts found', 'resource-host-monitor' ),
				'not_found_in_trash' => __( 'No Resource Hosts found in trash', 'resource-host-monitor' ),
				'all_items'          => __( 'All Resource Hosts', 'resource-host-monitor' ),
			)
		) );
	}

	public function fetch_existing() {
		static $existing;
		if ( ! isset( $existing ) ) {
			$existing = array();
			$posts = get_posts( array(
				'post_type'      => 'resource_host',
				'post_status'    => 'any',
				'posts_per_page' => -1,
			) );
			foreach ( $posts as $post ) {
				$host = $post->post_title;
				$existing[ $host ] = $post;
			}
		}
		return $existing;
	}

	public function exists( array $resource ) {
		$existing = $this->fetch_existing();
		$key = $this->get_key( $resource );
		return isset( $existing[ $key ] );
	}

	public function get_key( array $resource ) {
		$key = $resource['protocol'] . '//' . $resource['hostname'];
		if ( !empty( $resource['port'] ) ) {
			$key .= ':' . $resource['protocol'];
		}
		return $key;
	}

	public function insert( array $resource ) {
		$inserted = wp_insert_post( array(
			'post_type'   => 'resource_host',
			'post_status' => 'publish',
			'post_title'  => $this->get_key( $resource ),
		) );

		if ( $inserted ) {
			$post = get_post( $inserted );

			// @TODO whitelisting, sanitisation
			foreach ( $resource as $key => $value ) {
				add_post_meta( $post->ID, $key, $value );
			}

			$this->existing[ $key ] = $post;

			do_action( 'rhm/storage/insert', $post->ID, $resource );

			return $post;
		} else {
			return false;
		}
	}

	public function get_next_to_analyze( Resource_Host_Monitor_Analyzer $analyzer ) {
		return $this->get_next( array(
			array(
				'key'     => sprintf( 'analyzer_status_%s', $analyzer->get_id() ),
				'compare' => 'NOT EXISTS',
			)
		) );
	}

	public function get_analyzing( Resource_Host_Monitor_Analyzer $analyzer ) {
		return $this->get_next( array(
			array(
				'key'   => sprintf( 'analyzer_status_%s', $analyzer->get_id() ),
				'value' => 'analyzing',
			)
		) );
	}

	private function get_next( array $meta_query ) {

		$posts = get_posts( array(
			'post_type'      => 'resource_host',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'orderby'        => 'post_date',
			'order'          => 'ASC',
			'meta_query'     => $meta_query,
		) );

		if ( empty( $posts ) ) {
			return false;
		} else {
			$post = reset( $posts );
			return $this->get( $post->ID );
		}

	}

	public function get( $id ) {
		if ( ! $post = get_post( $id ) ) {
			return false;
		}

		// @TODO introduce resource class instead of an array

		$collector = Resource_Host_Monitor::init()->collector;
		$analyzers = Resource_Host_Monitor::init()->analyzers;
		$resource  = array(
			'ID' => $post->ID,
		);

		foreach ( $collector->allowed_fields() as $field ) {
			$resource[ $field ] = get_post_meta( $post->ID, $field, true );
		}

		foreach ( $analyzers as $analyzer ) {
			$key = sprintf( 'analyzer_status_%s', $analyzer->get_id() );
			$resource[ $key ] = get_post_meta( $post->ID, $key, true );
			$key = sprintf( 'analyzer_data_%s', $analyzer->get_id() );
			$resource[ $key ] = get_post_meta( $post->ID, $key, true );
		}

		return $resource;

	}

	public function update_analysis( $id, $status, Resource_Host_Monitor_Analyzer $analyzer, array $data = array() ) {
		if ( ! $post = get_post( $id ) ) {
			return false;
		}

		update_post_meta( $post->ID, sprintf( 'analyzer_status_%s', $analyzer->get_id() ), $status );

		if ( ! empty( $data ) ) {
			update_post_meta( $post->ID, sprintf( 'analyzer_data_%s', $analyzer->get_id() ), $data );
		}

	}

}
