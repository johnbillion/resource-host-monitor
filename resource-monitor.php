<?php
/*
Plugin Name: Resource Host Monitor
Description: Records the host names of third party resources on your site and performs analysis on the hosts for quality control and curiosity purposes.
Version:     0.1.0
Author:      John Blackbourn
Author URI:  https://johnblackbourn.com/
Text Domain: resource-host-monitor
Domain Path: /languages/
License:     GPL v2 or later

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

require_once dirname( __FILE__ ) . '/classes/Plugin.php';

class Resource_Host_Monitor extends Resource_Host_Monitor_Plugin {

	public $storage   = null;
	public $collector = null;
	public $analyzers = null;

	protected function __construct( $file ) {

		# Actions
		add_action( 'plugins_loaded', array( $this, 'action_plugins_loaded' ) );

		parent::__construct( $file );

	}

	public function action_plugins_loaded() {

		// Utils
		require_once dirname( __FILE__ ) . '/classes/Util.php';

		// Report storage
		require_once dirname( __FILE__ ) . '/classes/Storage.php';
		$this->storage = new Resource_Host_Monitor_Storage;

		// Report collector
		require_once dirname( __FILE__ ) . '/classes/Collector.php';
		$this->collector = new Resource_Host_Monitor_Collector;

		// Collection of report analyzers
		require_once dirname( __FILE__ ) . '/classes/Analyzers.php';
		$this->analyzers = Resource_Host_Monitor_Analyzers::init();

		// Report analyzers
		require_once dirname( __FILE__ ) . '/classes/Analyzer.php';
		require_once dirname( __FILE__ ) . '/classes/Analyzer_HTTPS.php';
		require_once dirname( __FILE__ ) . '/classes/Analyzer_SSL_Labs.php';
		$this->analyzers->add( new Resource_Host_Monitor_Analyzer_HTTPS );
		$this->analyzers->add( new Resource_Host_Monitor_Analyzer_SSL_Labs );

	}

	public static function init( $file = null ) {

		static $instance = null;

		if ( ! $instance ) {
			$instance = new Resource_Host_Monitor( $file );
		}

		return $instance;

	}

}

Resource_Host_Monitor::init( __FILE__ );
