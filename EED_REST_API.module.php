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

	const ee_api_namespace = '/ee/v';
	const ee_api_namespace_for_regex = '\/ee\/v([^/]*)\/';
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
		add_filter( 'json_index', array( 'EE_REST_API_Controller_Model_Meta', 'filter_ee_metadata_into_index' ) );
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
			$routes = array_merge( $instance->_register_config_routes(),  $instance->_register_meta_routes(), $instance->_register_model_routes() );
			update_option( self::saved_routes_option_names, $routes, true );
		}
	}

	/**
	 * Gets all the route information relating to EE models
	 * @return array
	 */
	protected function _register_model_routes() {
		EE_Registry::instance()->load_helper( 'Inflector' );
		$models_to_register = apply_filters( 'FHEE__EED_REST_API___register_model_routes', EE_Registry::instance()->non_abstract_db_models );
		//let's not bother having endpoints for extra metas
		unset($models_to_register['Extra_Meta']);
		$model_routes = array( );
		foreach( self::versions_served() as $version => $hidden_endpoint ) {
			if( $hidden_endpoint ) {
				$bitmask = WP_JSON_Server::READABLE | WP_JSON_Server::HIDDEN_ENDPOINT;
			}else{
				$bitmask = WP_JSON_Server::READABLE;
			}
			foreach ( $models_to_register as $model_name => $model_classname ) {
				//yes we could jsut register one route for ALL models, but then they wouldn't show up in the index
				$model_routes[ self::ee_api_namespace . $version . '/' . EEH_Inflector::pluralize_and_lower( $model_name ) ] = array(
					array( array( 'EE_REST_API_Controller_Model_Read', 'handle_request_get_all' ), $bitmask ),
						//@todo: also handle POST, PUT
				);
				$model_routes[ self::ee_api_namespace . $version . '/' .EEH_Inflector::pluralize_and_lower( $model_name ) . '/(?P<id>\d+)' ] = array(
					array( array( 'EE_REST_API_Controller_Model_Read', 'handle_request_get_one' ), $bitmask ),
						//@todo: also handle PUT, DELETE,
				);
				$model = EE_Registry::instance()->load_model( $model_classname );
				foreach ( $model->relation_settings() as $relation_name => $relation_obj ) {
					$related_model_name_endpoint_part = EE_REST_API_Controller_Model_Read::get_related_entity_name( $relation_name, $relation_obj );
					$model_routes[ self::ee_api_namespace . $version . '/' . EEH_Inflector::pluralize_and_lower( $model_name ) . '/(?P<id>\d+)/' . $related_model_name_endpoint_part ] = array(
						array( array( 'EE_REST_API_Controller_Model_Read', 'handle_request_get_related' ), $bitmask )
							//@todo: also handle POST, PUT
					);
				}
			}
		}

		return $model_routes;
	}

	/**
	 * Gets routes for the config
	 * @return array
	 */
	protected function _register_config_routes() {
		foreach( self::versions_served() as $version => $hidden_endpoint ) {
			if( $hidden_endpoint ) {
				$bitmask = WP_JSON_Server::READABLE | WP_JSON_Server::HIDDEN_ENDPOINT;

			}else{
				$bitmask = WP_JSON_Server::READABLE;
			}

			$config_routes[ self::ee_api_namespace . $version . '/config' ] = array(
					array( array( 'EE_REST_API_Controller_Config_Read', 'handle_request' ), $bitmask ),
				);
		}
		return $config_routes;
	}

	/**
	 * Gets the meta info routes
	 * @return array
	 */
	protected function _register_meta_routes() {
		foreach( self::versions_served() as $version => $hidden_endpoint ) {
			if( $hidden_endpoint ) {
				$bitmask = WP_JSON_Server::READABLE | WP_JSON_Server::HIDDEN_ENDPOINT;
			}else{
				$bitmask = WP_JSON_Server::READABLE;
			}
			$meta_routes[ self::ee_api_namespace . $version . '/resources' ] = array(
				array( array( 'EE_REST_API_Controller_Model_Meta', 'handle_request_models_meta' ), $bitmask )
			);
		}
		return $meta_routes;
	}

	/**
	 * Returns an array describing which versions of core support serving requests for.
	 * Keys are core versions' major and minor version, and values are the
	 * LOWEST requested version they can serve. Eg, 4.7 can serve requests for 4.6-like
	 * data by just removing a few models and fields from the responses. However, 4.15 might remove
	 * the answers table entirely, in which case it would be very difficult for
	 * it to serve 4.6-style responses.
	 * Versions of core that are missing from this array are unknowns.
	 * previous ver
	 * @return array
	 */
	public static function version_compatibilities() {
		return  apply_filters( 'FHEE__EED_REST_API__version_compatibilities',
				array(
					'4.6' => '4.6',
					'4.7' => '4.6',
					'4.8' => '4.6'
				));
	}

	/**
	 * Using EED_REST_API::version_compatibilities(), determines what version of
	 * EE the API can serve requests for. Eg, if we are on 4.15 of core, and
	 * we can serve reqeusts from 4.12 or later, this will return array( '4.12', '4.13', '4.14', '4.15' ).
	 * We also indicate whether or not this version should be put in the index or not
	 * @return array keys are API version numbers (just major and minor numbers), and values
	 * are whether or not they should be hidden
	 */
	public static function versions_served() {
		$version_compatibilities = EED_REST_API::version_compatibilities();
		$versions_served = array();
		if( $version_compatibilities[ EED_REST_API::core_version() ] ) {
			$lowest_compatible_version = $version_compatibilities[ EED_REST_API::core_version() ];
			//for each version of core we have ever served:
			foreach( array_keys( EED_REST_API::version_compatibilities() ) as $possibly_served_version ) {
				//if it's not above the current core version, and it's compatible with the current version of core
				if( $possibly_served_version < EED_REST_API::core_version() && $possibly_served_version >= $lowest_compatible_version ) {
					$versions_served[ $possibly_served_version ] = true;
				}elseif( $possibly_served_version == EED_REST_API::core_version() ) {
					$versions_served[ $possibly_served_version ] = false;
				}
			}
		}
		return $versions_served;
	}



	/**
	 * Gets the major and minor version of EE core's version string
	 * @return string
	 */
	public static function core_version() {
		return apply_filters( 'FHEE__EED_REST_API__core_version', implode('.', array_slice( explode( '.', espresso_version() ), 0, 2 ) ) );
	}



	/**
	 *    config
	 *
	 * @return EE_REST_API_Config
	 */
	public function config() {
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
