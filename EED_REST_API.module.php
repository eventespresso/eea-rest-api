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
	 * @var 		bool
	 * @access 	public
	 */
	public static $shortcode_active = FALSE;



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
		 // ajax hooks
		 add_action( 'wp_ajax_get_rest_api', array( 'EED_REST_API', 'get_rest_api' ));
		 add_action( 'wp_ajax_nopriv_get_rest_api', array( 'EED_REST_API', 'get_rest_api' ));
		 self::set_hooks_both();
	 }

	 public static function set_hooks_both() {
		 add_filter( 'json_endpoints', array( 'EED_REST_API', 'register_routes' ) );
	 }

	 public static function register_routes( $routes ){
		$instance = self::instance();
		$routes = array_merge( $routes, $instance->_register_model_routes() );
//		$routes['/myplugin/mytypeitems'] = array(
//			array( array( $this, 'get_posts'), WP_JSON_Server::READABLE ),
//			array( array( $this, 'new_post'), WP_JSON_Server::CREATABLE | WP_JSON_Server::ACCEPT_JSON ),
//		);
//		$routes['/myplugin/mytypeitems/(?P<id>\d+)'] = array(
//			array( array( $this, 'get_post'), WP_JSON_Server::READABLE ),
//			array( array( $this, 'edit_post'), WP_JSON_Server::EDITABLE | WP_JSON_Server::ACCEPT_JSON ),
//			array( array( $this, 'delete_post'), WP_JSON_Server::DELETABLE ),
//		);
		return $routes;
	 }

	 protected function _register_model_routes() {
		 $models_to_register = EE_Registry::instance()->non_abstract_db_models;
		 $model_routes = array();
		 $inflector = new Inflector();
		 foreach( $models_to_register as $model_name => $model_classname ){
			 $model_routes['/ee4/' . strtolower( $inflector->pluralize( $model_name ) ) ] = array(
				 array( array( $this, 'get' ), WP_JSON_Server::READABLE ),
				 //others to go here...
				 );
		 }
		 return $model_routes;
	 }

	 /**
	  *
	  * @param type $_method
	  * @param type $_path
	  * @param type $_headers
	  * @return array
	  */
	 public function get( $_path,
			 $filter=array() ) {
		$inflector = new Inflector();
		$regex = '~\/ee4\/(.*)~';
		$success = preg_match( $regex, $_path, $matches );
		if( is_array( $matches ) && isset( $matches[1] )){
			$model_name_plural = $matches[1];
			$model_name_singular = ucwords( $inflector->singularize($model_name_plural) );
			if( ! EE_Registry::instance()->is_model_name( $model_name_singular ) ) {
				return new WP_Error('endpoint_parsing_error', __( 'We could not parse the URL. Please contact event espresso support', 'event_espresso' ) );
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
		 add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ));
	 }






	/**
	 * 	enqueue_scripts - Load the scripts and css
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function enqueue_scripts() {
		//Check to see if the rest_api css file exists in the '/uploads/espresso/' directory
		if ( is_readable( EVENT_ESPRESSO_UPLOAD_DIR . "css/rest_api.css")) {
			//This is the url to the css file if available
			wp_register_style( 'espresso_rest_api', EVENT_ESPRESSO_UPLOAD_URL . 'css/espresso_rest_api.css' );
		} else {
			// EE rest_api style
			wp_register_style( 'espresso_rest_api', EE_REST_API_URL . 'css/espresso_rest_api.css' );
		}
		// rest_api script
		wp_register_script( 'espresso_rest_api', EE_REST_API_URL . 'scripts/espresso_rest_api.js', array( 'jquery' ), EE_REST_API_VERSION, TRUE );

		// is the shortcode or widget in play?
		if ( EED_REST_API::$shortcode_active ) {
			wp_enqueue_style( 'espresso_rest_api' );
			wp_enqueue_script( 'espresso_rest_api' );
		}
	}




	/**
	 *		@ override magic methods
	 *		@ return void
	 */
	public function __set($a,$b) { return FALSE; }
	public function __get($a) { return FALSE; }
	public function __isset($a) { return FALSE; }
	public function __unset($a) { return FALSE; }
	public function __clone() { return FALSE; }
	public function __wakeup() { return FALSE; }
	public function __destruct() { return FALSE; }

 }
// End of file EED_REST_API.module.php
// Location: /wp-content/plugins/eea-rest-api/EED_REST_API.module.php
