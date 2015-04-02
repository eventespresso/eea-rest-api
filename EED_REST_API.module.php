<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
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

	 public static function register_routes( $routes ){
		$instance = self::instance();
		$routes = array_merge( $routes, $instance->_register_model_routes() );
		return $routes;
	 }

	 protected function _register_model_routes() {
		 $models_to_register = EE_Registry::instance()->non_abstract_db_models;
		 $model_routes = array();
		 $inflector = new Inflector();
		 foreach( $models_to_register as $model_name => $model_classname ){
			 $model_routes['/ee4/' . strtolower( $inflector->pluralize( $model_name ) ) ] = array(
				 array( array( $this, 'handle_request_get_all' ), WP_JSON_Server::READABLE ),
				 //others to go here...
				 );
		 }
		 return $model_routes;
	 }

	 /**
	  * Handles requests to get all (or a filtered subset) of entities for a particular model
	  * @param type $_method
	  * @param type $_path
	  * @param type $_headers
	  * @return array
	  */
	 public function handle_request_get_all( $_path,
			 $filter=array() ) {
		$inflector = new Inflector();
		$regex = '~\/ee4\/(.*)~';
		$success = preg_match( $regex, $_path, $matches );
		if( is_array( $matches ) && isset( $matches[1] )){
			$model_name_plural = $matches[1];
			$model_name_singular = str_replace(' ', '_', $inflector->humanize($inflector->singularize($model_name_plural), 'all' ) );
			if( ! EE_Registry::instance()->is_model_name( $model_name_singular ) ) {
				return new WP_Error('endpoint_parsing_error', sprintf( __( 'There is no model for endpoint %s. Please contact event espresso support', 'event_espresso' ), $model_name_singular ) );
			}
			$model = EE_Registry::instance()->load_model( $model_name_singular );
			return $this->get_entities_from_model( $model, $filter );
		}else{
			return new WP_Error('endpoint_parsing_error', __( 'We could not parse the URL. Please contact event espresso support', 'event_espresso' ) );
		}
	 }

	 /**
	  *
	  * @param EEM_Base $model
	  * @param array $filter
	  * @return array
	  */
	 public function get_entities_from_model( $model, $filter ) {
		$query_params =  $this->create_model_params( $model, $filter );
		$results = $model->get_all_wpdb_results( $query_params );
		$nice_results = array();
		foreach( $results as $result ) {
			$nice_results[] = $model->_deduce_fields_n_values_from_cols_n_values( $result );
		}
		return $nice_results;
	 }

	 /**
	  * Translates API filter get parameter into $query_params array used by EEM_Base::get_all()
	  * @param type $model
	  * @param type $filter
	  * @return array like what EEM_Base::get_all() expects
	  */
	 public function create_model_params( $model, $filter ){
		 $model_query_params = array();
		 if( isset( $filter['where'] ) ) {
			 //@todo: no good for permissions
			 $model_query_params[0] = $filter['where'];
		 }
		 if( isset( $filter[ 'order_by' ] ) ){
			 $model_query_params[ 'order_by' ] = $filter[ 'order_by'];
		 }elseif( isset( $filter[ 'orderby' ] ) ) {
			 $model_query_params[ 'order_by' ] = $filter[ 'orderby' ];
		 }
		 if( isset( $filter[ 'group_by' ] ) ) {
			 $model_query_params[ 'group_by' ] = $filter[ 'group_by' ];
		 }
		 if( isset( $filter[ 'having' ] ) ) {
			 //@todo: no good for permissions
			 $model_query_params[ 'having' ] = $filter[ 'having' ];
		 }
		 if( isset( $filter[ 'order' ] ) ) {
			 $model_query_params[ 'order' ] = $filter[ 'order' ];
		 }
		 if( isset( $filter[ 'default_where_conditions' ] ) ) {
			 $model_query_params[ 'default_where_conditions' ]  = $filter[ 'default_where_conditions' ];
		 }
		 return apply_filters( 'FHEE__EED_REST_API__create_model_params', $model_query_params, $filter, $model );
	 }


	/**
	 *    config
	 *
	 * @return EE_REST_API_Config
	 */
	public function config(){
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
