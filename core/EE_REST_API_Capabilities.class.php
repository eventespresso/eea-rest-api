<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_REST_API_Capabilities
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_REST_API_Capabilities {
	protected static $_access_restrictions;
	/**
	 * Returns an array that describes what capabilities are required to view/edit/create/delete
	 * on certain endpoints.
	 * Basically, top-level keys are model names;
	 * next-level-keys indicate whether teh permissions are with regards to reading,
	 * creating, updating, or deleting those models;
	 * next-level keys are field names, or "*" to indicate a fallback catch-all;
	 * next-level keys are permission names, and their values are an EE_API_Access_Restriction object
	 * which describes how their access is limited if they don't have the associated permission.
	 * @return array
	 */
	public static function get_access_restrictions() {
		if( ! self::$_access_restrictions ) {
			$event_editing_restrictions = array(
				//in order to edit an event you need this permission
				'ee_edit_events' => new EE_API_Access_Entity_Never()
			);
			$restrictions = array(
				'Answer' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Attendee' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Change_Log' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Checkin' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Country' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Currency' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Currency_Payment_Method' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Datetime' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Datetime_Ticket' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Event' => array(
					 WP_JSON_Server::READABLE => array(
						 '*' => array(
							 //if they can't read events (in the admin) only show them ones they can see on the frontend
							 'ee_read_events' => new EE_API_Access_Entity_If( array( 'status' => 'publish', //'Datetime.DTT_EVT_end' => array( '>=', current_time('mysql' ) )
								 ) ),
							 //without 'ee_read_private_events' don't show others' private events
							 'ee_read_private_events' => new EE_API_Access_Entity_If( array( 'NOT*no_others_private_events' => array( 'status' => 'private', 'EVT_wp_user' => array( '!=', get_current_user_id() ) ) ) )
						 ),
						 'EVT_desc_raw' => $event_editing_restrictions//only show them the EVT_desc_raw if they can edit the event

					 )
				),
				'Event_Message_Template' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Event_Question_Group' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Event_Venue' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Extra_Meta' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Line_Item' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Message_Template' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Message_Template_Group' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Payment' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Payment_Method' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Price' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Price_Type' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Question' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Question_Group' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Question_Group_Question' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Question_Option' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Registration' => array(
				WP_JSON_Server::READABLE => array(
					'*' => array(
						//allow full access to anyone
					)
				)
				),
				'State' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Status' => array(
				WP_JSON_Server::READABLE => array(
					'*' => array(
						//anyone can see stati
					)
				)
				),
				'Term' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Term_Relationship' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Term_Taxonomy' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Ticket' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Ticket_Price' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Ticket_Template' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Transaction' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'Venue' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
				'WP_User' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//allow full access to anyone
							)
					)
				),
			);
			foreach( EE_Registry::instance()->non_abstract_db_models as $model_name => $model_classname ) {
				if( ! isset( $restrictions[ $model_name ] ) ) {
					$restrictions[ $model_name ] = array(
						WP_JSON_Server::READABLE => array(
							'*' => array(
								//by default, if they're basically not an admin, they can't read this
								'activate_plugins' => new EE_API_Access_Entity_Never()
							)
						)
					);
				}
			}
			$restrictions =  apply_filters( 'FHEE__EE_Models_Rest_Read_Controller__get_permissions', $restrictions );
			foreach( $restrictions as $model_name => $request_types_handled ) {
				foreach( $request_types_handled as $request_type_handled => $api_fields ){
					foreach( $api_fields as $api_field_name => $permissions_and_access_restrictions ) {
						foreach( $permissions_and_access_restrictions as $capability => $access_restriction ){
							if( ! $access_restriction instanceof EE_API_Access_Restriction ) {
								throw new EE_Error( sprintf( __( 'You must provide an EE_API_Access_Restriction object that describes how to restrict access to users who dont have a particular permission for the model "%s", request type "%s", at the capability "%s".', 'event_espresso' ), $model_name, $request_type_handled, $capability ) );
							}else{
								$access_restriction->set_model_name( $model_name );
							}
						}
					}
				}
			}
			self::$_access_restrictions = $restrictions;
		}
		return self::$_access_restrictions;
	}

	/**
	 * The current user can see at least SOME of these entities. If a field is provided
	 * returns whether the current user can access that field on at least some entities
	 * (tests whether it's possible for them to access any; not whether there actually ARE
	 * some currently in existence)
	 * @param EEM_Base $model
	 * @param type $request_type
	 * @return boolean
	 */
	public static function current_user_has_partial_access_to( $model, $request_type = WP_JSON_Server::READABLE, $field_name = '*' ) {
		$access_restrictions = self::get_access_restrictions();
		if( isset( $access_restrictions[ $model->get_this_model_name() ] ) && isset( $access_restrictions[ $model->get_this_model_name() ][ $request_type ] ) ) {
			if( isset( $access_restrictions[ $model->get_this_model_name() ][ $request_type ][ $field_name ] ) ) {
				$field_to_use = $field_name;
			}elseif( isset( $access_restrictions[ $model->get_this_model_name() ][ $request_type ][ '*' ] ) ){
				$field_to_use = '*';
			}else{
				throw new EE_Error( sprintf( __( 'Could not find default query params for model %s on request type %s because restrictions array setup improperly. There should be an entry for "*" but there is only %s', 'event_espresso' ), $model->get_this_model_name(), $request_type, implode(',', array_keys( $access_restrictions[ $model->get_this_model_name() ][ $request_type ] ) ) ) );
			}
			foreach( $access_restrictions[ $model->get_this_model_name() ][ $request_type ][ $field_to_use ] as $capability => $restriction ) {
				//check that we're not missing a critical capability
				if( ! current_user_can( $capability ) && $restriction instanceof EE_API_Access_Entity_Never ){
					return false;
				}
			}
			return true;
		}else{
			//no restrictions defined for this model or request type. It mustn't be accessible
			return false;
		}
	}
	/**
	 * Gets an array of all the capabilities the current user is missing that affected
	 * the query
	 * @param EEM_Base $model
	 * @param int $request_type one of the consts on WP_JSON_Server
	 * @return array
	 */
	public static function get_missing_permissions( $model, $request_type = WP_JSON_Server::READABLE ) {
		$caps_missing = array();
		$access_restrictions = self::get_access_restrictions();
		if( isset( $access_restrictions[ $model->get_this_model_name() ] ) && isset( $access_restrictions[ $model->get_this_model_name() ][ $request_type ] ) && isset( $access_restrictions[ $model->get_this_model_name() ][ $request_type ][ '*' ] ) ) {
			foreach( $access_restrictions[ $model->get_this_model_name() ][ $request_type ][ '*' ] as $capability => $restriction ) {
				//check that we're not missing a critical capability
				if( ! EE_Registry::instance()->CAP->current_user_can( $capability, 'ee_api_get_missing_permissions' )
 ){
					$caps_missing[] = $capability;
				}
			}
			return $caps_missing;
		}else{
			return array( '**none: no one can access this' );
		}
	}
	/**
	 * Gets a string of all the capabilities the current user is missing that affected
	 * the query
	 * @param EEM_Base $model
	 * @param int $request_type one of the consts on WP_JSON_Server
	 * @return string
	 */
	public static function get_missing_permissions_string( $model, $request_type = WP_JSON_Server::READABLE ) {
		return implode(',', self::get_missing_permissions( $model, $request_type ) );
	}

	/**
	 * Modifies the query according to the user's permissions (and if certain permissions
	 * are missing, we instead impose restrictions on the database query).
	 * If there is a restriction that means we shouldn't return ANYTHING, just return false.
	 * Client code will need to understand what false means.
	 * @param array $query_params @see EEM_Base::get_all
	 * @param EEM_Base $model
	 * @param int $request_type like a const on WP_JSON_Server
	 * @return boolean
	 */
	public static function add_restrictions_onto_query( $query_params, $model, $request_type = WP_JSON_Server::READABLE, $field_name = '*' ) {
		$access_restrictions = self::get_access_restrictions();
		if( isset( $access_restrictions[ $model->get_this_model_name() ] ) && isset( $access_restrictions[ $model->get_this_model_name() ][ $request_type ] ) ) {
			if( isset( $access_restrictions[ $model->get_this_model_name() ][ $request_type ][ $field_name ] ) ) {
				$field_to_use = $field_name;
			}elseif( isset( $access_restrictions[ $model->get_this_model_name() ][ $request_type ][ '*' ] ) ){
				$field_to_use = '*';
			}else{
				throw new EE_Error( sprintf( __( 'Could not find default query params for model %s on request type %s because restrictions array setup improperly. There should be an entry for "*" but there is only %s', 'event_espresso' ), $model->get_this_model_name(), $request_type, implode(',', array_keys( $access_restrictions[ $model->get_this_model_name() ][ $request_type ] ) ) ) );
			}
			foreach( $access_restrictions[ $model->get_this_model_name() ][ $request_type ][ $field_to_use ] as $capability => $restriction ) {
				//check that we're not missing a critical capability
				if( ! EE_Registry::instance()->CAP->current_user_can( $capability, 'ee_api_add_restrictions_onto_query' ) ){
					//missing this permission is a deal-breaker
					if( $restriction instanceof EE_API_Access_Entity_Never ){
						return false;
					}
					if( ! isset( $query_params[0] ) ){
						$query_params[0] = array();
					}
					$query_params[0] = array_replace( $query_params[0], $restriction->get_where_conditions() );
				}
			}
			return $query_params;
		}else{
			//there's no entry, so really no one should be able to access this
			return false;
		}
	}
	/**
	 * Can the current user access all entries in this field?
	 * @param EEM_Base $model
	 * @param int $request_type const on WP_JSON_Server
	 * @param string $field_name entity field name
	 * @return boolean
	 */
	public static function current_user_has_full_access_to( $model, $request_type, $field_name, $entity_id = null ) {
		$access_restrictions = self::get_access_restrictions();
		if( isset( $access_restrictions[ $model->get_this_model_name() ] ) && isset( $access_restrictions[ $model->get_this_model_name() ][ $request_type ] ) ) {
			if( $entity_id ) {
				//great now let's see if it's findable by applying the default query params
				$query_params = array( array( $model->primary_key_name() => $entity_id ) );
				$query_params = self::add_restrictions_onto_query($query_params, $model, $request_type, $field_name );
				if( $query_params !== false && $model->exists( $query_params ) ) {
					return true;
				} else {
					return false;
				}
			}else{
				//no ID provided. So can they access this field on SOME items?
				//eg, can they use this field in querying? yes if they can sometimes read it
				return self::current_user_has_partial_access_to($model, $request_type, $field_name);
			}
//			$query_params = array( array( $model->primary_key_name() => $entity_id ))
			//determine which items in the current query can access

		}else{
			return false;
		}
	}

	/**
	 * Takes a entity that's ready to be returned
	 * @param type $entity
	 * @param EEM_Base $model
	 * @param type $request_type
	 * @return type
	 */
	public static function filter_out_inaccessible_entity_fields( $entity, $model, $request_type = WP_JSON_Server::READABLE ) {
		$entity_filtered = array();
		$access_restrictions = EE_REST_API_Capabilities::get_access_restrictions();
		if( isset( $access_restrictions[ $model->get_this_model_name() ] ) &&
				isset( $access_restrictions[ $model->get_this_model_name() ][ $request_type ] ) ) {
			if( ! isset( $entity[ $model->primary_key_name() ] ) ){
				throw new EE_Error( sprintf( __( 'Entity\'s primary key could not be found in results (could not find a key "%s" among %s when filtering ou inaccessible entity fields for model %s)', 'event_espresso' ), $model->primary_key_name(), implode(',', array_keys( $entity ) ), $model->get_this_model_name() ) );
			}
			$entity_id = $entity[ $model->primary_key_name() ];
			foreach( $entity as $field_name => $value ) {
				if( EE_REST_API_Capabilities::current_user_has_full_access_to( $model, $request_type, $field_name, $entity_id ) || $model->has_relation( $field_name ) ){
					$entity_filtered[ $field_name ] = $value;
				}
			}
			return $entity_filtered;
		}else{
			//there's no entry for this model. that's weird
			return array();
		}
	}

	/**
	 * Resets the access restrictions
	 */
	public static function reset() {
		self::$_access_restrictions = null;
	}
}

/**
 * Used in the API's EE_Models_Rest_Read_Controller::get_permissions() array
 * to indicate that if if a certain permission is missing, these additional WHERE conditions should be added
 */
abstract class EE_API_Access_Restriction{
	protected $_model_name;
	public function set_model_name( $model_name ){
		$this->_model_name = $model_name;
	}
	/**
	 *
	 * @return EEM_Base
	 */
	protected function _get_model(){
		return EE_Registry::instance()->load_model( $this->_model_name );
	}
	/**
	 * Returns an array like EEM_Base::get_all() 's index of 0, ie
	 * only the WHERE conditions in the query. These are conditions that are
	 * added onto queries when the user is missing the associated permission
	 * @return array
	 */
	abstract public function get_where_conditions();
}
/**
 * If the user doesn't have the indicated permission, they should only have access
 * to model objects that meet this criteria
 */
class EE_API_Access_Entity_If extends EE_API_Access_Restriction{
	protected $_where_conditions = array();

	public function __construct( $where_conditions = array() ){
		$this->_where_conditions = $where_conditions;
	}
	public function get_where_conditions() {
		return $this->_where_conditions;
	}
}
/**
 * If the user doesn't ahve the indicated permission, they should only be able
 * to access model objects that belong to them.
 */
class EE_API_Access_Entity_If_Owner extends EE_API_Access_Restriction{
	public function get_where_conditions() {
		$full_query_params = $this->_get_model()->alter_query_params_to_only_include_mine();
		return $full_query_params[0];
	}
}
/**
 * Ie if the user doesn't have the indicated permission, they shouldn't be able
 * to access anything
 */
class EE_API_Access_Entity_Never extends EE_API_Access_Restriction{
	//mostly marker and unfortunately logic is in client code
	//but this should indicate to NOT bothe running a query because nothing should be returned
	public function get_where_conditions() {
		$model = $this->_get_model();
		if( $model->has_primary_key_field() ) {
			return array( $model->primary_key_name() => -1 );
		}else{
			$fk_field = $model->get_a_field_of_type( 'EE_Foreign_Key_Field_Base' );
			return array( $fk_field->get_name() => -1 );
		}
	}
}
// End of file EE_REST_API_Capabilities.class.php