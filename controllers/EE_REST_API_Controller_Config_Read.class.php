<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_REST_API_Controller_Config_Read
 *
 * For handling READ requests for config data
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_REST_API_Controller_Config_Read {
	public static function handle_request() {
		$cap = EE_Restriction_Generator_Base::get_default_restrictions_cap();
		if( EE_Capabilities::instance()->current_user_can( $cap, 'read_over_api' ) ){
			return EE_Config::instance();
		}else{
			return new WP_Error( 'cannot_read_config', sprintf( __( 'You do not have the necessary capabilities (%s) to read Event Espresso Configuration data', 'event_espresso' ), $cap ), array( 'status' => 403 ));
		}
	}
}

// End of file EE_REST_API_Controller_Config_Read.class.php