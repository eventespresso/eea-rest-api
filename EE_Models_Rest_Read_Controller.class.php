<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_Models_Rest_Read_Controller
 *
 * Handles requests relating to GETting model information
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_Models_Rest_Read_Controller {
	/**
	 * Handles requests to get all (or a filtered subset) of entities for a particular model
	 * @param string $_path
	 * @param array $filter The query parameters to be passed onto the EE models system.
	 * Using syntax like "/wp-json/ee4/v2/events?filter[where][EVT_name][]=like&filter[where][EVT_name][]=%25monkey%25 to create a query params array like "array(array('EVT_name' => array('LIKE','%monkey%'))", which
	 * will create SQL like "WHERE EVT_name LIKE '%monkey%'"
	 * @param string $include string indicating which fields to include in the response, including fields
	 * on related entities. Eg, when querying for events, an include string like
	 * "...&include=EVT_name,EVT_desc,Datetime, Datetime.Ticket.TKT_ID, Datetime.Ticket.TKT_name, Datetime.Ticket.TKT_price" instructs us to only include the event's name and description, each related datetime, and
	 * each related datetime's ticket's name and price. Eg json would be:
	 * '{"EVT_ID":12,"EVT_name":"star wars party","EVT_desc":"so cool...","datetimes":[{"DTT_ID":123,...,
	 * "tickets":[{"TKT_ID":234,"TKT_name":"student rate","TKT_price":32.0},...]}]}', ie, events with all
	 * their associated datetimes (including ones that are trashed) embedded in the json object, and each
	 * datetime also has each associated ticket embedded in its json object.
	 * @return array
	 */
	public static function handle_request_get_all( $_path, $filter = array(), $include = '*' ) {
		$inflector = new Inflector();
		$regex = '~' . EED_REST_API::ee_api_namespace_for_regex . '(.*)~';
		$success = preg_match( $regex, $_path, $matches );
		if ( is_array( $matches ) && isset( $matches[ 1 ] ) ) {
			$model_name_plural = $matches[ 1 ];
			$model_name_singular = str_replace( ' ', '_', $inflector->humanize( $inflector->singularize( $model_name_plural ), 'all' ) );
			if ( ! EE_Registry::instance()->is_model_name( $model_name_singular ) ) {
				return new WP_Error( 'endpoint_parsing_error', sprintf( __( 'There is no model for endpoint %s. Please contact event espresso support', 'event_espresso' ), $model_name_singular ) );
			}
			$model = EE_Registry::instance()->load_model( $model_name_singular );
			if( EE_REST_API_Capabilities::current_user_can_access_any( $model_name_singular, WP_JSON_Server::READABLE ) ){
				return self::get_entities_from_model( $model, $filter, $include );
			}else{
				return new WP_Error( sprintf( 'json_%s_cannot_list', $model_name_plural ), sprintf( __( 'Sorry, you are not allowed to list %s. Missing permissions: %s' ), $model_name_plural, EE_REST_API_Capabilities::get_missing_permissions_string( $model_name_singular, WP_JSON_Server::READABLE ) ), array( 'status' => 403 ) );
			}
		} else {
			return new WP_Error( 'endpoint_parsing_error', __( 'We could not parse the URL. Please contact event espresso support', 'event_espresso' ) );
		}
	}

	/**
	 * Handles requests to get all mine (or a filtered subset) of entities for a particular model
	 * @param string $_path
	 * @param array $filter @see EE_MOdels_Rest_Read_Controller:handle_request_get_all
	 * @param string $include @see EE_MOdels_Rest_Read_Controller:handle_request_get_all
	 * @return array
	 */
	public static function handle_request_get_all_mine( $_path, $filter = array(), $include = '*' ) {
		$inflector = new Inflector();
		$regex = '~' . EED_REST_API::ee_api_namespace_for_regex . '(.*)/mine~';
		$success = preg_match( $regex, $_path, $matches );
		if ( is_array( $matches ) && isset( $matches[ 1 ] ) ) {
			$model_name_plural = $matches[ 1 ];
			$model_name_singular = str_replace( ' ', '_', $inflector->humanize( $inflector->singularize( $model_name_plural ), 'all' ) );
			if ( ! EE_Registry::instance()->is_model_name( $model_name_singular ) ) {
				return new WP_Error( 'endpoint_parsing_error', sprintf( __( 'There is no model for endpoint %s. Please contact event espresso support', 'event_espresso' ), $model_name_singular ) );
			}
			$model = EE_Registry::instance()->load_model( $model_name_singular );
			if( ! $model->is_owned() ) {
				return new WP_Error( 'endpoint_not_supported', sprintf( __( 'This endpoint is misconfigured, please contact Event Espresso Support', 'event_espresso' ) ) );
			}
			if( EE_REST_API_Capabilities::current_user_can_access_any( $model_name_singular, WP_JSON_Server::READABLE ) ){
				$filter['mine'] = 1;
				return self::get_entities_from_model( $model, $filter, $include );
			}else{
				return new WP_Error( sprintf( 'json_%s_cannot_list', $model_name_plural ), sprintf( __( 'Sorry, you are not allowed to list %s. Missing permissions: %s' ), $model_name_plural, EE_REST_API_Capabilities::get_missing_permissions_string( $model_name_singular, WP_JSON_Server::READABLE ) ), array( 'status' => 403 ) );
			}
		} else {
			return new WP_Error( 'endpoint_parsing_error', __( 'We could not parse the URL. Please contact event espresso support', 'event_espresso' ) );
		}
	}

	/**
	 * Gets a single entity related to the model indicated in the path and its id
	 * @param string $_path
	 * @param string $id ID of the thing to be retrieved
	 * @param string $include @see EE_MOdels_Rest_Read_Controller:handle_request_get_all
	 * @return array|WP_Error
	 */
	public static function handle_request_get_one( $_path, $id, $include = '*' ) {
		$inflector = new Inflector();
		$regex = '~' . EED_REST_API::ee_api_namespace_for_regex . '(.*)/(.*)~';
		$success = preg_match( $regex, $_path, $matches );
		if ( $success && is_array( $matches ) && isset( $matches[ 1 ] ) ) {
			$model_name_plural = $matches[ 1 ];
			$model_name_singular = str_replace( ' ', '_', $inflector->humanize( $inflector->singularize( $model_name_plural ), 'all' ) );
			if ( ! EE_Registry::instance()->is_model_name( $model_name_singular ) ) {
				return new WP_Error( 'endpoint_parsing_error', sprintf( __( 'There is no model for endpoint %s. Please contact event espresso support', 'event_espresso' ), $model_name_singular ) );
			}
			$model = EE_Registry::instance()->load_model( $model_name_singular );
			return self::get_entity_from_model( $model, $id, $include );
		}else{
			return new WP_Error( 'endpoint_parsing_error', __( 'We could not parse the URL. Please contact event espresso support', 'event_espresso' ) );
		}
	}

	/**
	 *
	 * Gets all the related entities (or if its a belongs-to relation just the one)
	 * to the item with the given id
	 * @param string $_path
	 * @param string $id
	 * @param array $filter @see EE_MOdels_Rest_Read_Controller:handle_request_get_all
	 * @param string $include @see EE_MOdels_Rest_Read_Controller:handle_request_get_all
	 * @return array|WP_Error
	 */
	public static function handle_request_get_related( $_path, $id, $filter = array(), $include = '*' ) {
		$regex = '~' . EED_REST_API::ee_api_namespace_for_regex . '(.*)/(.*)/(.*)~';
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
			if( EE_REST_API_Capabilities::current_user_can_access_any( $related_model_name_singular, WP_JSON_Server::READABLE ) ){
				return self::get_entities_from_relation( $id, $relation_settings, $filter, $include );
			}else{
				return new WP_Error( sprintf( 'json_%s_cannot_list', $related_model_name_maybe_plural ), sprintf( __( 'Sorry, you are not allowed to list %s related to %s. Missing permissions: %s' ), $related_model_name_maybe_plural, $main_model_name_plural, EE_REST_API_Capabilities::get_missing_permissions_string( $related_model_name_singular, WP_JSON_Server::READABLE ) ), array( 'status' => 403 ) );
			}
		}
	}



	/**
	 * Gets a collection for the given model and filters
	 * @param EEM_Base $model
	 * @param array $filter @see EE_MOdels_Rest_Read_Controller:handle_request_get_all
	 * @param string $include @see EE_MOdels_Rest_Read_Controller:handle_request_get_all
	 * @return array
	 */
	public static function get_entities_from_model( $model, $filter, $include ) {
		$query_params = self::create_model_query_params( $model, $filter );
		if( $query_params === false ) {
			//don't even bother querying
			$results = array();
		} else {
			$results = $model->get_all_wpdb_results( $query_params );
		}
		$nice_results = array( );
		foreach ( $results as $result ) {
			$nice_results[ ] = self::create_entity_from_wpdb_result( $model, $result, $include );
		}
		return $nice_results;
	}

	/**
	 * Gets the coollection for given relation object
	 *
	 * The same as EE_Models_Rest_Read_Controller::get_entities_from_model(), except if the relation
	 * is a HABTM relation, in which case it merges any non-foreign-key fields from
	 * the join-model-object into the results
	 * @param string $id the ID of the thing we are fetching related stuff from
	 * @param EE_Model_Relation_Base $relation
	 * @param array $filter @see EE_MOdels_Rest_Read_Controller:handle_request_get_all
	 * @param string $include @see EE_MOdels_Rest_Read_Controller:handle_request_get_all
	 * @return array
	 */
	public static function get_entities_from_relation( $id,  $relation, $filter, $include ) {
		$query_params = self::create_model_query_params( $relation->get_other_model(), $filter );
		if( $query_params === false ){
			//a restriction indicated it wanted nothing to be returned, so dont even query
			$results = array();
		}else{
			$query_params[0][ $relation->get_this_model()->get_this_model_name() . '.' . $relation->get_this_model()->primary_key_name() ] = $id;
			$query_params[ 'default_where_conditions' ] = 'none';
			$results = $relation->get_other_model()->get_all_wpdb_results( $query_params );
		}
		$nice_results = array();
		foreach( $results as $result ) {
			$nice_result = self::create_entity_from_wpdb_result( $relation->get_other_model(), $result, $include );
			if( $relation instanceof EE_HABTM_Relation ) {
				//put the unusual stuff (properties from the HABTM relation) first, and make sure
				//if there are conflicts we prefer the properties from the main model
				$join_model_result = self::create_entity_from_wpdb_result( $relation->get_join_model(), $result, $include );
				$joined_result = array_merge( $nice_result, $join_model_result );
				//but keep the meta stuff from the main model
				if( isset( $nice_result['meta'] ) ){
					$joined_result['meta'] = $nice_result['meta'];
				}
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
	 * @param array $db_row like results from $wpdb->get_results()
	 * @param string $include @see EE_MOdels_Rest_Read_Controller:handle_request_get_all
	 */
	public static function create_entity_from_wpdb_result( $model, $db_row, $include ) {
		$result = $model->_deduce_fields_n_values_from_cols_n_values( $db_row );
		foreach( $result as $field_name => $raw_field_value ) {
			$field_obj = $model->field_settings_for($field_name);
			$field_value = $field_obj->prepare_for_set_from_db( $raw_field_value );
			if( $field_obj instanceof EE_Foreign_Key_Field_Base || $field_obj instanceof EE_Any_Foreign_Model_Name_Field ) {
				unset( $result[ $field_name ] );
			}elseif( $field_obj instanceof EE_Post_Content_Field ){
				$result[ $field_name . '_raw' ] = $field_obj->prepare_for_get( $field_value );
				$result[ $field_name ] = $field_obj->prepare_for_pretty_echoing( $field_value );
			}elseif( $field_obj instanceof EE_Enum_Integer_Field ||
					$field_obj instanceof EE_Enum_Text_Field ||
					$field_obj instanceof EE_Money_Field ) {
				$result[ $field_name ] = $field_obj->prepare_for_get( $field_value );
				$result[ $field_name . '_pretty' ] = $field_obj->prepare_for_pretty_echoing( $field_value );
			}elseif( $field_obj instanceof EE_Datetime_Field ){
				$result[ $field_name ] = json_mysql_to_rfc3339( $raw_field_value );
			}else{
				$result[ $field_name ] = $field_obj->prepare_for_get( $field_value );
			}
		}
		//add links to related data
		$result['meta']['links'] = array(
			'self' => json_url( EED_REST_API::ee_api_namespace . Inflector::pluralize_and_lower( $model->get_this_model_name() ) . '/' . $result[ $model->primary_key_name() ]
		) );

		//filter fields if specified
		$includes_for_this_model = self::extract_includes_for_this_model( $include );
		if( ! empty( $includes_for_this_model ) ) {
			if( $model->has_primary_key_field() ) {
				//always include the primary key
				$includes_for_this_model[] = $model->primary_key_name();
			}
			$result = array_intersect_key( $result, array_flip( $includes_for_this_model ) );
		}
		//add meta links and possibly include related models
		foreach( $model->relation_settings() as $relation_name => $relation_obj ) {
			$related_model_part = self::get_related_entity_name( $relation_name, $relation_obj );
			if( empty( $includes_for_this_model ) || isset( $includes_for_this_model['meta'] ) ) {
				$result['meta']['links'][$related_model_part] = json_url( EED_REST_API::ee_api_namespace . Inflector::pluralize_and_lower( $model->get_this_model_name() ) . '/' . $result[ $model->primary_key_name() ] . '/' . $related_model_part );
			}
			$related_fields_to_include = self::extract_includes_for_this_model( $include, $relation_name );
			if( $related_fields_to_include ) {
				$result[ $related_model_part ] = self::get_entities_from_relation( $result[ $model->primary_key_name() ], $relation_obj, array(), implode(',',self::extract_includes_for_this_model( $include, $relation_name ) ) );
			}
		}
		$result = apply_filters( 'FHEE__EE_Models_Rest_Read_Controller__deduce_fields_n_values_form_cols_n_values_except_fks', $result, $model );
		return $result;
	}

	/**
	 * Gets the correct lowercase name for the relation in the API according
	 * to the relation's type
	 * @param string $relation_name
	 * @param EE_Model_Relation_Base $relation_obj
	 * @return string
	 */
	public static function get_related_entity_name( $relation_name, $relation_obj ){
		if( $relation_obj instanceof EE_Belongs_To_Relation ) {
			return strtolower( $relation_name );
		}else{
			return Inflector::pluralize_and_lower( $relation_name );
		}
	}

//	public function


	/**
	 * Gets the one model object with the specified id for the specified model
	 * @param EEM_Base $model
	 * @param string $id ID of the entity we want to retrieve
	 * @param string $include @see EE_MOdels_Rest_Read_Controller:handle_request_get_all
	 * @return array
	 */
	public static function get_entity_from_model( $model, $id, $include ) {
		$query_params = array( array( $model->primary_key_name() => $id ),'limit' => 1 );
		if( $model instanceof EEM_Soft_Delete_Base ){
			$query_params = $model->alter_query_params_so_deleted_and_undeleted_items_included($query_params);
		}
		$restricted_query_params = EE_REST_API_Capabilities::add_restrictions_onto_query( $query_params, $model->get_this_model_name(), WP_JSON_Server::READABLE );
		$model_rows = $model->get_all_wpdb_results( $restricted_query_params );
		if ( ! empty ( $model_rows ) ) {
			return self::create_entity_from_wpdb_result( $model, array_shift( $model_rows ), $include );
		} else {
			//ok let's test to see if we WOULD have found it, had we not had restrictions from missing capabilities
			$lowercase_model_name = strtolower( $model->get_this_model_name() );
			$model_rows_found_sans_restrictions = $model->get_all_wpdb_results( $query_params );
			if( ! empty( $model_rows_found_sans_restrictions ) ) {
				//you got shafted- it existed but we didn't want to tell you!
				return new WP_Error( 'json_user_cannot_read', sprintf( __( 'Sorry, you cannot read this %s', 'event_espresso' ), strtolower( $model->get_this_model_name() ) ) );
			}else{
				//it's not you. It just doesn't exist
				return new WP_Error( sprintf( 'json_%s_invalid_id', $lowercase_model_name ), sprintf( __( 'Invalid %s ID.', 'event_espresso' ), $lowercase_model_name ), array( 'status' => 404 ) );
			}
		}
	}



	/**
	 * Translates API filter get parameter into $query_params array used by EEM_Base::get_all()
	 * @param EEM_Base $model
	 * @param array $filter from $_GET['filter'] parameter @see EE_MOdels_Rest_Read_Controller:handle_request_get_all
	 * @return array like what EEM_Base::get_all() expects or FALSE to indicate
	 * that absolutely no results should be returned
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
		if ( isset( $filter[ 'mine' ] ) ){
			$model_query_params = $model->alter_query_params_to_only_include_mine( $model_query_params );
		}
		$model_query_params = EE_REST_API_Capabilities::add_restrictions_onto_query( $model_query_params, $model->get_this_model_name(), WP_JSON_Server::READABLE );
		return apply_filters( 'FHEE__EE_Models_Rest_Read_Controller__create_model_query_params', $model_query_params, $filter, $model );
	}

	/**
	 * Parses the $include_string so we fetch all the field names relating to THIS model
	 * (ie have NO period in them), or for the provided model (ie start with the model
	 * name and then a period).
	 * @param string $include_string @see EE_MOdels_Rest_Read_Controller:handle_request_get_all
	 * @param string $model_name
	 * @return array of fields for this model. If $model_name is provided, then
	 * the fields for that model, with the model's name removed from each.
	 */
	public static function extract_includes_for_this_model( $include_string, $model_name = null ) {
		if( $include_string === '*' ) {
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
				}elseif( $field_to_include == $model_name ){
					$extracted_fields_to_include[] = '*';
				}
			}
		}else{
			//look for ones with no period
			foreach( $includes as $field_to_include ) {
				$field_to_include = trim( $field_to_include );
				if( strpos($field_to_include, '.' ) === FALSE && ! EE_Registry::instance()->is_model_name( $field_to_include ) ) {
					$extracted_fields_to_include[] = $field_to_include;
				}
			}
		}
		return $extracted_fields_to_include;

	}
}


// End of file EE_Models_Rest_Read_Controller.class.php