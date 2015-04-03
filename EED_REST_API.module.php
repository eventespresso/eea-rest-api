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
		if( ! $ee_routes ){
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
			$routes = $instance->_register_model_routes();
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
				array( array( 'EED_REST_API', 'handle_request_get_all' ), WP_JSON_Server::READABLE ),
					//@todo: also handle POST, PUT
			);
			$model_routes[ self::ee_api_namespace . Inflector::pluralize_and_lower( $model_name ) . '/(?P<id>\d+)' ] = array(
				array( array( 'EED_REST_API', 'handle_request_get_one' ), WP_JSON_Server::READABLE ),
					//@todo: also handle PUT, DELETE,
			);
			$model = EE_Registry::instance()->load_model( $model_classname );
			foreach ( $model->relation_settings() as $relation_name => $relation_obj ) {
				if ( $relation_obj instanceof EE_Belongs_To_Relation ) {
					$related_model_name_endpoint_part = strtolower( $relation_name );
				} else {
					$related_model_name_endpoint_part = Inflector::pluralize_and_lower( ( $relation_name ) );
				}
				$model_routes[ self::ee_api_namespace . Inflector::pluralize_and_lower( $model_name ) . '/(?P<id>\d+)/' . $related_model_name_endpoint_part ] = array(
					array( array( 'EED_REST_API', 'handle_request_get_related' ), WP_JSON_Server::READABLE )
						//@todo: also handle POST, PUT
				);
			}
		}
		return $model_routes;
	}



	/**
	 * Handles requests to get all (or a filtered subset) of entities for a particular model
	 * @param string $_path
	 * @param array $filter
	 * @param string $include
	 * @return array
	 */
	public static function handle_request_get_all( $_path, $filter = array(), $include = null ) {
		$inflector = new Inflector();
		$regex = '~' . self::ee_api_namespace_for_regex . '(.*)~';
		$success = preg_match( $regex, $_path, $matches );
		if ( is_array( $matches ) && isset( $matches[ 1 ] ) ) {
			$model_name_plural = $matches[ 1 ];
			$model_name_singular = str_replace( ' ', '_', $inflector->humanize( $inflector->singularize( $model_name_plural ), 'all' ) );
			if ( ! EE_Registry::instance()->is_model_name( $model_name_singular ) ) {
				return new WP_Error( 'endpoint_parsing_error', sprintf( __( 'There is no model for endpoint %s. Please contact event espresso support', 'event_espresso' ), $model_name_singular ) );
			}
			$model = EE_Registry::instance()->load_model( $model_name_singular );
			return self::get_entities_from_model( $model, $filter, $include );
		} else {
			return new WP_Error( 'endpoint_parsing_error', __( 'We could not parse the URL. Please contact event espresso support', 'event_espresso' ) );
		}
	}


	/**
	 * Gets a single entity related to the model indicated in the path and its id
	 * @param string $_path
	 * @param string $id
	 * @param string $includee
	 * @return array|WP_Error
	 */
	public static function handle_request_get_one( $_path, $id, $include = null ) {
		$inflector = new Inflector();
		$regex = '~' . self::ee_api_namespace_for_regex . '(.*)/(.*)~';
		$success = preg_match( $regex, $_path, $matches );
		if ( is_array( $matches ) && isset( $matches[ 1 ] ) ) {
			$model_name_plural = $matches[ 1 ];
			$model_name_singular = str_replace( ' ', '_', $inflector->humanize( $inflector->singularize( $model_name_plural ), 'all' ) );
			if ( ! EE_Registry::instance()->is_model_name( $model_name_singular ) ) {
				return new WP_Error( 'endpoint_parsing_error', sprintf( __( 'There is no model for endpoint %s. Please contact event espresso support', 'event_espresso' ), $model_name_singular ) );
			}
			$model = EE_Registry::instance()->load_model( $model_name_singular );
			return self::get_entity_from_model( $model, $id, $include );
		}
	}

	/**
	 *
	 * Gets all the related entities (or if its a belongs-to relation just the one)
	 * to the item with the given id
	 * @param string $_path
	 * @param string $id
	 * @param array $filter
	 * @return array|WP_Error
	 */
	public static function handle_request_get_related( $_path, $id, $filter = array(), $include = null ) {
		$regex = '~' . self::ee_api_namespace_for_regex . '(.*)/(.*)/(.*)~';
		$success = preg_match( $regex, $_path, $matches );
		if ( is_array( $matches ) && isset( $matches[ 1 ] ) && isset( $matches[3] ) ) {
			$main_model_name_plural = $matches[ 1 ];
			$main_model_name_singular = str_replace( ' ', '_', Inflector::humanize( Inflector::singularize( $main_model_name_plural ), 'all' ) );
			if ( ! EE_Registry::instance()->is_model_name( $main_model_name_singular ) ) {
				return new WP_Error( 'endpoint_parsing_error', sprintf( __( 'There is no model for endpoint %s. Please contact event espresso support', 'event_espresso' ), $main_model_name_singular ) );
			}
			$related_model_name_maybe_plural = $matches[ 3 ];
			$related_model_name_singular = str_replace( ' ', '_', Inflector::humanize( Inflector::singularize( $related_model_name_maybe_plural ), 'all' ) );
			if ( ! EE_Registry::instance()->is_model_name( $related_model_name_singular ) ) {
				return new WP_Error( 'endpoint_parsing_error', sprintf( __( 'There is no model for endpoint %s. Please contact event espresso support', 'event_espresso' ), $related_model_name_singular ) );
			}

			$model = EE_Registry::instance()->load_model( $main_model_name_singular );
			$relation_settings = $model->related_settings_for( $related_model_name_singular );
			//duplicate how we find related model objects in EE_Base_Class::get_many_related()
			$filter[ 'where' ][ $main_model_name_singular . '.' . $model->primary_key_name() ] = $id;
			$filter[ 'default_where_conditions' ] = 'none';
			return self::get_entities_from_relation( $relation_settings, $filter, $include );
		}
	}



	/**
	 * Gets a collection for the given model and filters
	 * @param EEM_Base $model
	 * @param array $filter
	 * @param string $include
	 * @return array
	 */
	public static function get_entities_from_model( $model, $filter, $include ) {
		$query_params = self::create_model_query_params( $model, $filter );
		$results = $model->get_all_wpdb_results( $query_params );
		$nice_results = array( );
		foreach ( $results as $result ) {
			$nice_results[ ] = self::create_entity_from_wpdb_result( $model, $result, $include );
		}
		return $nice_results;
	}

	/**
	 * Gets the coollection for given relation object
	 *
	 * The same as EED_REST_API::get_entities_from_model(), except if the relation
	 * is a HABTM relation, in which case it merges any non-foreign-key fields from
	 * the join-model-object into the results
	 * @param EE_Model_Relation_Base $relation
	 * @param array $filter
	 * @param string $include specific fields to include (possibly prefixed by model name) or related models to include
	 * @return array
	 */
	public static function get_entities_from_relation( $relation, $filter, $include ) {
		$query_params = self::create_model_query_params( $relation->get_other_model(), $filter );
		$results = $relation->get_other_model()->get_all_wpdb_results( $query_params );
		$nice_results = array();
		foreach( $results as $result ) {
			$nice_result = self::create_entity_from_wpdb_result( $relation->get_other_model(), $result );
			if( $relation instanceof EE_HABTM_Relation ) {
				//put the unusual stuff (properties from the HABTM relation) first, and make sure
				//if there are conflicts we prefer the properties from the main model
				$join_model_result = self::create_entity_from_wpdb_result( $relation->get_join_model(), $result );
				$joined_result = array_merge( $nice_result, $join_model_result );
				//but keep the meta stuff from the main model
				$joined_result['meta'] = $nice_result['meta'];
				$nice_result = $joined_result;
			}
			$nice_results[] = $nice_result;
		}
		if( $relation instanceof EE_Belongs_To_Relation ){
			return array_shift( $nice_results );
		}else{
			return $nice_results;
		}
	}

	/**
	 * Changes database results into REST API entities
	 * @param EEM_Base $model
	 * @param array $db_row
	 * @param string $include
	 */
	public static function create_entity_from_wpdb_result( $model, $db_row, $include ) {
		$result = $model->_deduce_fields_n_values_from_cols_n_values( $db_row );
		foreach( $result as $field_name => $field_value ) {
			$field_obj = $model->field_settings_for($field_name);
			if( $field_obj instanceof EE_Foreign_Key_Field_Base || $field_obj instanceof EE_Any_Foreign_Model_Name_Field ) {
				unset( $result[ $field_name ] );
			}
			if( $field_obj instanceof EE_Post_Content_Field ){
				$result[ $field_name . '_raw' ] = $field_value;
				$result[ $field_name ] = do_shortcode( $field_value );
			}
		}
		//add links to related data
		$result['meta']['links'] = array(
			'self' => json_url( self::ee_api_namespace . Inflector::pluralize_and_lower( $model->get_this_model_name() ) . '/' . $result[ $model->primary_key_name() ]
		) );

		foreach( $model->relation_settings() as $relation_name => $relation_obj ) {

			if( $relation_obj instanceof EE_Belongs_To_Relation ) {
				$related_model_part = strtolower( $relation_name );
			}else{
				$related_model_part = Inflector::pluralize_and_lower( $relation_name );
			}
			$result['meta']['links'][$related_model_part] = json_url( self::ee_api_namespace . Inflector::pluralize_and_lower( $model->get_this_model_name() ) . '/' . $result[ $model->primary_key_name() ] . '/' . $related_model_part );
		}
		//filter fields if specified
		$includes_for_this_model = self::extract_includes_for_this_model( $include );
		if( ! empty( $includes_for_this_model ) ) {
			$result = array_intersect_key( $result, array_flip( $includes_for_this_model ) );
		}
		$result = apply_filters( 'FHEE__EED_REST_API__deduce_fields_n_values_form_cols_n_values_except_fks', $result, $model );
		return $result;
	}

//	public function


	/**
	 * Gets the one model object with the specified id for the specified model
	 * @param EEM_Base $model
	 * @param string $id
	 * @param string $include
	 * @return array
	 */
	public static function get_entity_from_model( $model, $id, $include ) {
		$query_params = array( array( $model->primary_key_name() => $id ),'limit' => 1 );
		if( $model instanceof EEM_Soft_Delete_Base ){
			$query_params = $model->alter_query_params_so_deleted_and_undeleted_items_included($query_params);
		}
		$model_rows = $model->get_all_wpdb_results( $query_params );
		if ( ! empty ( $model_rows ) ) {
			return self::create_entity_from_wpdb_result( $model, array_shift( $model_rows ), $include );
		} else {
			return new WP_Error( 'json_ee_object_invalid_id', __( 'Invalid model object ID.' ), array( 'status' => 404 ) );
		}
	}



	/**
	 * Translates API filter get parameter into $query_params array used by EEM_Base::get_all()
	 * @param type $model
	 * @param type $filter
	 * @return array like what EEM_Base::get_all() expects
	 */
	public static function create_model_query_params( $model, $filter ) {
		$model_query_params = array( );
		if ( isset( $filter[ 'where' ] ) ) {
			//@todo: no good for permissions
			$model_query_params[ 0 ] = $filter[ 'where' ];
		}
		if ( isset( $filter[ 'order_by' ] ) ) {
			$model_query_params[ 'order_by' ] = $filter[ 'order_by' ];
		} elseif ( isset( $filter[ 'orderby' ] ) ) {
			$model_query_params[ 'order_by' ] = $filter[ 'orderby' ];
		}
		if ( isset( $filter[ 'group_by' ] ) ) {
			$model_query_params[ 'group_by' ] = $filter[ 'group_by' ];
		}
		if ( isset( $filter[ 'having' ] ) ) {
			//@todo: no good for permissions
			$model_query_params[ 'having' ] = $filter[ 'having' ];
		}
		if ( isset( $filter[ 'order' ] ) ) {
			$model_query_params[ 'order' ] = $filter[ 'order' ];
		}
		if ( isset( $filter[ 'default_where_conditions' ] ) ) {
			$model_query_params[ 'default_where_conditions' ] = $filter[ 'default_where_conditions' ];
		}
		return apply_filters( 'FHEE__EED_REST_API__create_model_query_params', $model_query_params, $filter, $model );
	}

	/**
	 * Parses the $include_string so we fetch all the field names relating to THIS model
	 * (ie have NO period in them), or for the provided model (ie start with the model
	 * name and then a period)
	 * @param type $include_string
	 * @param type $model_name
	 * @return array of fields for this model. If $model_name is provided, then
	 * the fields for that model, with the model's name removed from each
	 */
	public static function extract_includes_for_this_model( $include_string, $model_name = null ) {
		if( $include_string === null ) {
			return array();
		}
		$includes = explode( ',', $include_string );
		$extracted_fields_to_include = array();
		if( $model_name ){
			foreach( $includes as $field_to_include ) {
				$field_to_include = trim( $field_to_include );
				if( strpos( $field_to_include, $model_name . '.' ) === 0 ) {
					//found the model name at the exact start
					$field_sans_model_name = str_replace( $model_name . '.', '', $field_to_include );
					$extracted_fields_to_include[] = $field_sans_model_name;
				}
			}
		}else{
			//look for ones with no period
			foreach( $includes as $field_to_include ) {
				$field_to_include = trim( $field_to_include );
				if( strpos($field_to_include, '.' ) === FALSE ) {
					$extracted_fields_to_include[] = $field_to_include;
				}
			}
		}
		return $extracted_fields_to_include;

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
