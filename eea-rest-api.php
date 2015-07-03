<?php
/*
  Plugin Name: Event Espresso - REST API (EE4.x+)
  Plugin URI: http://www.eventespresso.com
  Description: The Event Espresso REST API adds NEW stuff to Event Espresso. Compatible with Event Espresso 4.x or higher
  Version: 3.0.0.beta.001
  Author: Event Espresso
  Author URI: http://www.eventespresso.com
  Copyright 2014 Event Espresso (email : support@eventespresso.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA
 *
 * ------------------------------------------------------------------------
 *
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author		Event Espresso
 * @ copyright	(c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link			http://www.eventespresso.com
 * @ version	 	EE4
 *
 * ------------------------------------------------------------------------
 */
define( 'EE_REST_API_VERSION', '3.0.0.beta.001' );
define( 'EE_REST_API_PLUGIN_FILE',  __FILE__ );
function load_espresso_rest_api() {
	if ( class_exists( 'EE_Addon' ) ) {
		if( class_exists( 'WP_JSON_Server' ) ) {
			// rest_api version
			require_once ( plugin_dir_path( __FILE__ ) . 'EE_REST_API.class.php' );
			EE_REST_API::register_addon();
		} else {
			EE_Error::add_error( __( 'The Event Espresso REST API requires the WP REST/JSON API version to be running (the latest stable version of the WP REST/JSON API at the time of writing was 1.2.0)', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );
		}
	}
}
add_action( 'AHEE__EE_System__load_espresso_addons', 'load_espresso_rest_api' );

// End of file espresso_rest_api.php
// Location: wp-content/plugins/eea-rest-api/espresso_rest_api.php
