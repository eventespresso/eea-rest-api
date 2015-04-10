<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}
/*
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Event Espresso
 * @ copyright	(c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link				http://www.eventespresso.com
 * @ version		$VID:$
 *
 * ------------------------------------------------------------------------
 */

/**
 * Class  EED_REST_API
 *
 * @package			Event Espresso
 * @subpackage		eea-rest-api
 * @author 				Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
class EED_REST_API extends EED_Module {

	const ee_api_namespace = '/ee4/v2/';
	const ee_api_namespace_for_regex = '\/ee4\/v2\/';
	const saved_routes_option_names = 'ee_routes';

	/**
	 * @return EED_REST_API
	 */
	public static function instance() {
		return parent::get_instance( __CLASS__ );
	}



	/**
	 * 	set_hooks - for hooking into EE Core, other modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks() {
		self::set_hooks_both();
	}



	/**
	 * 	set_hooks_admin - for hooking into EE Admin Core, other modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks_admin() {
		self::set_hooks_both();
	}



	public static function set_hooks_both() {
		add_filter( 'json_endpoints', array( 'EED_REST_API', 'register_routes' ) );
	}


	/**
	 * Filters the WP routes to add our EE-related ones. This takes a bit of time
	 * so we actually prefer to only do it when an EE plugin is activated or upgraded
	 * @param array $routes
	 * @return array
	 */
	public static function register_routes( $routes ) {
		$ee_routes = get_option( self::saved_routes_option_names, null );
		if( ! $ee_routes || ( defined('EE_REST_API_DEV') && EE_REST_API_DEV )){
			self::save_ee_routes();
			$ee_routes = get_option( self::saved_routes_option_names, array() );
		}
		$routes = array_merge( $routes, $ee_routes );
		return $routes;
	}

	/**
	 * Calculates all the EE routes and saves it to a wordpress option so we don't
	 * need to calculate it on every request
	 * @return void
	 */
	public static function save_ee_routes() {
		if( EE_Maintenance_Mode::instance()->models_can_query() ){
			$instance = self::instance();
			$routes = array_merge( $instance->_register_config_routes(), $instance->_register_model_routes() );
			update_option( self::saved_routes_option_names, $routes, true );
		}
	}

	/**
	 * Gets all the route information relating to EE models
	 * @return array
	 */
	protected function _register_model_routes() {
		$models_to_register = EE_Registry::instance()->non_abstract_db_models;
		$model_routes = array( );
		foreach ( $models_to_register as $model_name => $model_classname ) {
			//yes we could jsut register one route for ALL models, but then they wouldn't show up in the index
			$model_routes[ self::ee_api_namespace . Inflector::pluralize_and_lower( $model_name ) ] = array(
				array( array( 'EE_Models_Rest_Read_Controller', 'handle_request_get_all' ), WP_JSON_Server::READABLE ),
					//@todo: also handle POST, PUT
			);
			$model_routes[ self::ee_api_namespace . Inflector::pluralize_and_lower( $model_name ) . '/(?P<id>\d+)' ] = array(
				array( array( 'EE_Models_Rest_Read_Controller', 'handle_request_get_one' ), WP_JSON_Server::READABLE ),
					//@todo: also handle PUT, DELETE,
			);
			$model = EE_Registry::instance()->load_model( $model_classname );
			if( $model->is_owned() ) {
					$model_routes[ self::ee_api_namespace . Inflector::pluralize_and_lower( $model_name ) .'/mine' ] = array(
					array( array( 'EE_Models_Rest_Read_Controller', 'handle_request_get_all_mine' ), WP_JSON_Server::READABLE ),
				);
			}
			foreach ( $model->relation_settings() as $relation_name => $relation_obj ) {
				$related_model_name_endpoint_part = EE_Models_Rest_Read_Controller::get_related_entity_name( $relation_name, $relation_obj );
				$model_routes[ self::ee_api_namespace . Inflector::pluralize_and_lower( $model_name ) . '/(?P<id>\d+)/' . $related_model_name_endpoint_part ] = array(
					array( array( 'EE_Models_Rest_Read_Controller', 'handle_request_get_related' ), WP_JSON_Server::READABLE )
						//@todo: also handle POST, PUT
				);
			}
		}
		return $model_routes;
	}

	/**
	 * Gets routes for the config
	 * @return array
	 */
	protected function _register_config_routes() {
		$config_routes[ self::ee_api_namespace . 'config' ] = array(
				array( array( 'EE_Config_Rest_Read_Controller', 'handle_request' ), WP_JSON_Server::READABLE ),
			);
		return $config_routes;
	}



	/**
	 *    config
	 *
	 * @return EE_REST_API_Config
	 */
	public function config() {
		// config settings are setup up individually for EED_Modules via the EE_Configurable class that all modules inherit from, so
		// $this->config();  can be used anywhere to retrieve it's config, and:
		// $this->_update_config( $EE_Config_Base_object ); can be used to supply an updated instance of it's config object
		// to piggy back off of the config setup for the base EE_REST_API class, just use the following (note: updates would have to occur from within that class)
		return EE_Registry::instance()->addons->EE_REST_API->config();
	}



	/**
	 *    run - initial module setup
	 *
	 * @access    public
	 * @param  WP $WP
	 * @return    void
	 */
	public function run( $WP ) {

	}


	
}

// End of file EED_REST_API.module.php
// Location: /wp-content/plugins/eea-rest-api/EED_REST_API.module.php
