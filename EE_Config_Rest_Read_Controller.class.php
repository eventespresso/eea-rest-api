<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_Config_Rest_Read_Controller
 *
 * For handling READ requests for config data
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_Config_Rest_Read_Controller {
	public static function handle_request() {
		return EE_Config::instance();
	}
}

// End of file EE_Config_Rest_Read_Controller.class.php