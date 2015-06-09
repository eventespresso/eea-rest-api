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
				'ee_edit_events' => new EE_Return_None_Where_Conditions()
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
							 'ee_read_events' => new EE_Default_Where_Conditions( array( 'status' => 'publish', //'Datetime.DTT_EVT_end' => array( '>=', current_time('mysql' ) )
								 ) ),
							 //without 'ee_read_private_events' don't show others' private events
							 'ee_read_private_events' => new EE_Default_Where_Conditions( array( 'NOT*no_others_private_events' => array( 'status' => 'private', 'EVT_wp_user' => array( '!=', get_current_user_id() ) ) ) )
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
							//don't show event categories if they can't see those
							'ee_manage_event_categories' => new EE_Default_Where_Conditions( array( 'Term_Taxonomy.taxonomy*no_event_cats' => array( '!=', 'espresso_event_categories' ) ) ),
							//dont' show venue categories if they can't see those
							'ee_manage_venue_categories' => new EE_Default_Where_Conditions( array( 'Term_Taxonomy.taxonomy*no_venue_cats' => array( '!=', 'espresso_venue_categories' ) ) ),
							)
					)
				),
				'Term_Relationship' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//don't show event categories if they can't see those
							'ee_manage_event_categories' => new EE_Default_Where_Conditions( array( 'Event.EVT_ID' => array( 'IS_NULL' ) ) ),
							//dont' show venue categories if they can't see those
							'ee_manage_venue_categories' => new EE_Default_Where_Conditions( array( 'Venue.VNU_ID' => array( 'IS_NULL' ) ) ),
							)
					)
				),
				'Term_Taxonomy' => array(
					WP_JSON_Server::READABLE => array(
						'*' => array(
							//don't show event categories if they can't see those
							'ee_manage_event_categories' => new EE_Default_Where_Conditions( array( 'Event.EVT_ID' => array( 'IS_NULL' ) ) ),
							//dont' show venue categories if they can't see those
							'ee_manage_venue_categories' => new EE_Default_Where_Conditions( array( 'Venue.VNU_ID' => array( 'IS_NULL' ) ) ),
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
								'activate_plugins' => new EE_Return_None_Where_Conditions()
							)
						)
					);
				}
			}
			$restrictions =  apply_filters( 'FHEE__EE_REST_API_Controller_Model_Read__get_permissions', $restrictions );
			foreach( $restrictions as $model_name => $request_types_handled ) {
				foreach( $request_types_handled as $request_type_handled => $api_fields ){
					foreach( $api_fields as $api_field_name => $permissions_and_access_restrictions ) {
						foreach( $permissions_and_access_restrictions as $capability => $access_restriction ){
							if( ! $access_restriction instanceof EE_Default_Where_Conditions ) {
								throw new EE_Error( sprintf( __( 'You must provide an EE_Default_Where_Conditions object that describes how to restrict access to users who dont have a particular permission for the model "%s", request type "%s", at the capability "%s".', 'event_espresso' ), $model_name, $request_type_handled, $capability ) );
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
	 * The current user can see at least SOME of these entities.
	 * @param EEM_Base $model
	 * @param string $model_context one of the return values from EEM_Base::valid_cap_contexts()
	 * @return boolean
	 */
	public static function current_user_has_partial_access_to( $model, $model_context = EEM_Base::caps_read ) {
		if( apply_filters( 'FHEE__EE_REST_API_Capabilities__current_user_has_partial_access_to__override_begin', false, $model, $model ) ) {
			return true;
		}
		foreach( $model->caps_missing( $model_context ) as $capability_name => $restriction_obj ) {
			if( $restriction_obj instanceof EE_Return_None_Where_Conditions ){
				return false;
			}
		}
		if( apply_filters( 'FHEE__EE_REST_API_Capabilities__current_user_has_partial_access_to__override_end', false, $model, $model ) ) {
			return false;
		}
		return true;
	}
	/**
	 * Gets an array of all the capabilities the current user is missing that affected
	 * the query
	 * @param EEM_Base $model
	 * @param int $request_type one of the consts on WP_JSON_Server
	 * @return array
	 */
	public static function get_missing_permissions( $model, $request_type = EEM_Base::caps_read ) {
		return $model->caps_missing( $request_type );
	}
	/**
	 * Gets a string of all the capabilities the current user is missing that affected
	 * the query
	 * @param EEM_Base $model
	 * @param int $model_context one of the return values from EEM_Base::valid_cap_contexts()
	 * @return string
	 */
	public static function get_missing_permissions_string( $model, $model_context = EEM_Base::caps_read ) {
		return implode(',', array_keys( self::get_missing_permissions( $model, $model_context ) ) );
	}

	/**
	 * Can the current user access all entries in this field?
	 * @param EEM_Base $model
	 * @param int $request_type const on WP_JSON_Server
	 * @param string $field_name entity field name
	 * @return boolean
	 */
	public static function current_user_has_full_access_to( $model, $request_type, $field_name, $entity_id = null ) {
		return true;
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
	 * Takes a entity that's ready to be returned and removes fields which the user shouldn't be able to access.
	 * @param array $entity
	 * @param EEM_Base $model
	 * @param string $request_type one of the return values from EEM_Base::valid_cap_contexts()
	 * @return array ready for converting into json
	 */
	public static function filter_out_inaccessible_entity_fields( $entity,  $model, $request_type = EEM_Base::caps_read ) {
		//we only care to do this for frontend reads and when the user can't edit the item
		if(  $request_type !== EEM_Base::caps_read ||
				$model->exists( array(
					array( $model->primary_key_name() => $entity[ $model->primary_key_name() ] ),
					'default_where_conditions' => 'none',
					'caps' => EEM_Base::caps_edit ) ) ) {
			return $entity;
		}
		foreach( $model->field_settings() as $field_name => $field_obj ){
			if( $field_obj instanceof EE_Post_Content_Field && isset( $entity[ $field_name . '_raw' ] )) {
				unset( $entity[ $field_name . '_raw' ] );
			}
		}
		//theoretically we may want to filter out specific fields for specific models

		return apply_filters( 'FHEE__EE_REST_API_Capabilities__filter_out_inaccessible_entity_fields', $entity, $model, $request_type );
	}


	/**
	 * Resets the access restrictions
	 */
	public static function reset() {
		self::$_access_restrictions = null;
	}
}

// End of file EE_REST_API_Capabilities.class.php