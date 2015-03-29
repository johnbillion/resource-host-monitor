<?php

if ( ! class_exists( 'Resource_Host_Monitor_Analyzers' ) ) {
class Resource_Host_Monitor_Analyzers implements IteratorAggregate {

	private $items = array();

	public function getIterator() {
		return new ArrayIterator( $this->items );
	}

	public static function add( Resource_Host_Monitor_Analyzer $item ) {
		$collection = self::init();
		$collection->items[ $item->get_id() ] = $item;
	}

	public static function get( $id ) {
		$collection = self::init();
		if ( isset( $collection->items[ $id ] ) ) {
			return $collection->items[ $id ];
		}
		return false;
	}

	public static function init() {
		static $instance;

		if ( !$instance ) {
			$instance = new Resource_Host_Monitor_Analyzers;
		}

		return $instance;

	}

}
}
