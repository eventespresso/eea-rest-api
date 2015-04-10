<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EE_Models_Rest_Read_Controller_Test
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_Models_Rest_Read_Controller_Test extends EE_UnitTestCase{
	public function test_extract_includes_for_this_model__basic(){
		$this->assertEquals( array(
			'EVT_ID',
			'EVT_name'
		), EE_Models_Rest_Read_Controller::extract_includes_for_this_model( 'EVT_ID,EVT_name' ) );
	}
	public function test_extract_includes_for_this_model__extra_whitespace() {
		$this->assertEquals( array(
			'EVT_ID',
			'EVT_name',
			'EVT_desc'
		), EE_Models_Rest_Read_Controller::extract_includes_for_this_model( 'EVT_ID , EVT_name , EVT_desc' ) );
	}
	public function test_extract_includes_for_this_model__related_model() {
		$this->assertEquals( array(), EE_Models_Rest_Read_Controller::extract_includes_for_this_model( 'Registration.*' ) );
	}
	public function test_extract_includes_for_this_model__related_model_all() {
		$this->assertEquals( array(
			'*'
		), EE_Models_Rest_Read_Controller::extract_includes_for_this_model( 'Registration.*', 'Registration' ) );
	}
	public function test_extract_includes_for_this_model__related_models_but_searching_for_this_one() {
		$this->assertEquals( array(
		), EE_Models_Rest_Read_Controller::extract_includes_for_this_model( 'Registration.REG_ID, Registration.Attendee.ATT_ID' ) );
	}
	public function test_extract_includes_for_this_model__related_models_but_searching_for_other() {
		$this->assertEquals( array(
			'REG_ID',
			'Attendee.ATT_ID'
		), EE_Models_Rest_Read_Controller::extract_includes_for_this_model( 'Registration.REG_ID, Registration.Attendee.ATT_ID', 'Registration' ) );
	}

	public function test_handle_request_get_one__event_includes() {
		$event = $this->new_model_obj_with_dependencies( 'Event', array( 'status' => 'publish' ) );
		$response = EE_Models_Rest_Read_Controller::handle_request_get_one( EED_REST_API::ee_api_namespace . 'events/' . $event->ID(), $event->ID(), 'EVT_ID,EVT_name' );
		$result = $response->get_data();
		$this->assertEquals(
			array (
				'EVT_ID' => $event->ID(),
				'EVT_name' => $event->name()
				), $result );
	}
	public function test_handle_request_get_one__event_include_non_model_field() {
		$this->set_current_user_to_new();
		$event = $this->new_model_obj_with_dependencies( 'Event' );
		$response = EE_Models_Rest_Read_Controller::handle_request_get_one( EED_REST_API::ee_api_namespace . 'events/' . $event->ID(), $event->ID(), 'EVT_desc_raw, EVT_desc' );
		$result = $response->get_data();
		$this->assertEquals(
			array (
				'EVT_ID' => $event->ID(),
				'EVT_desc' => $event->get('EVT_desc'),
				'EVT_desc_raw' => $event->get_pretty( 'EVT_desc'),
				), $result );
	}
	public function test_extract_includes_for_this_model__null() {
		$this->assertEquals( array(), EE_Models_Rest_Read_Controller::extract_includes_for_this_model( '*' ) );
	}
	public function test_handle_request_get_one__event() {
		$this->set_current_user_to_new();
		$event = $this->new_model_obj_with_dependencies( 'Event' );
		$response = EE_Models_Rest_Read_Controller::handle_request_get_one( EED_REST_API::ee_api_namespace . 'events/' . $event->ID(), $event->ID() );
		$result = $response->get_data();
		$this->assertTrue( is_array( $result ) );
		unset( $result[ 'EVT_created' ] );
		unset( $result[ 'EVT_modified' ] );
		unset( $result[ 'EVT_visible_on' ] );
		unset( $result[ 'meta' ] );
		$this->assertEquals(
			array (
				'EVT_ID' => $event->get( 'EVT_ID' ),
				'EVT_name' => $event->get( 'EVT_name' ) ,
				'EVT_desc' => $event->get( 'EVT_desc' ) ,
				'EVT_slug' => $event->get( 'EVT_slug' ) ,
				'EVT_short_desc' => $event->get( 'EVT_short_desc' ) ,
				'parent' => $event->get( 'parent' ) ,
				'EVT_order' => $event->get( 'EVT_order' ) ,
				'status' => $event->get_pretty( 'status' ) ,
				'comment_status' => $event->get( 'comment_status' ) ,
				'ping_status' => $event->get( 'ping_status' ) ,
				'EVT_display_desc' => $event->get( 'EVT_display_desc' ) ,
				'EVT_display_ticket_selector' => $event->get( 'EVT_display_ticket_selector' ) ,
				'EVT_additional_limit' => $event->get( 'EVT_additional_limit' ) ,
				'EVT_default_registration_status' => $event->get_pretty( 'EVT_default_registration_status' ) ,
				'EVT_member_only' => $event->get( 'EVT_member_only' ) ,
				'EVT_phone' => $event->get( 'EVT_phone' ) ,
				'EVT_allow_overflow' => $event->get( 'EVT_allow_overflow' ) ,
				'EVT_external_URL' => $event->get( 'EVT_external_URL' ) ,
				'EVT_donations' => $event->get( 'EVT_donations' ) ,
				'EVT_desc_raw' => $event->get_pretty( 'EVT_desc' ) ,
				'status_raw' => $event->get( 'status' ) ,
				'EVT_default_registration_status_raw' => $event->get( 'EVT_default_registration_status' ) ,
			  ),
				$result
				);
	}


	public function test_handle_request_get_one__registration_include_attendee(){
		$this->set_current_user_to_new();
		$r = $this->new_model_obj_with_dependencies( 'Registration' );
		$response = EE_Models_Rest_Read_Controller::handle_request_get_one( EED_REST_API::ee_api_namespace . 'registrations/' . $r->ID(), $r->ID(), 'Attendee.*');
		$entity = $response->get_data();
		$this->assertArrayHasKey( 'attendee', $entity );
	}

	public function test_handle_request_get_one__registration_include_answers_and_questions(){
		$this->set_current_user_to_new();
		$r = $this->new_model_obj_with_dependencies( 'Registration' );
		$a = $this->new_model_obj_with_dependencies( 'Answer', array( 'REG_ID' => $r->ID() ) );
		$response = EE_Models_Rest_Read_Controller::handle_request_get_one( EED_REST_API::ee_api_namespace . 'registrations/' . $r->ID(), $r->ID(), 'Answer.Question.*');
		$entity = $response->get_data();
		$this->assertArrayHasKey( 'answers', $entity );
		$answers = $entity['answers'];
		foreach( $answers as $answer ) {
			$this->assertArrayHasKey( 'question', $answer );
		}
	}

	public function test_handle_request_get_one__registration_include_answers_and_question_bare_min_from_each(){
		$this->set_current_user_to_new();
		$r = $this->new_model_obj_with_dependencies( 'Registration' );
		$a = $this->new_model_obj_with_dependencies( 'Answer', array( 'REG_ID' => $r->ID() ) );
		$response = EE_Models_Rest_Read_Controller::handle_request_get_one( EED_REST_API::ee_api_namespace . 'registrations/' . $r->ID(), $r->ID(), 'Answer.ATT_ID, Answer.Question.QST_ID');
		$entity = $response->get_data();
		$this->assertArrayHasKey( 'answers', $entity );
		$answers = $entity['answers'];
		foreach( $answers as $answer ){
			$this->assertArrayHasKey( 'question', $answer );
		}
	}

	public function test_handle_request_get_one__doesnt_exist(){
		$e = $this->new_model_obj_with_dependencies('Event');
		$non_existent_id = $e->ID() + 100;
		$response = EE_Models_Rest_Read_Controller::handle_request_get_one( EED_REST_API::ee_api_namespace . 'events/' . $non_existent_id, $non_existent_id );
		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'json_event_invalid_id', $response->get_error_code() );
	}
	public function test_handle_request_get_one__cannot_accesss(){
		$e = $this->new_model_obj_with_dependencies('Event', array( 'status' => 'draft' ) );
		$response = EE_Models_Rest_Read_Controller::handle_request_get_one( EED_REST_API::ee_api_namespace . 'events/' . $e->ID(), $e->ID() );
		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'json_user_cannot_read', $response->get_error_code() );
	}

	public function test_handle_request_get_all__not_logged_in(){
		$r = $this->new_model_obj_with_dependencies('Registration');
		$response = EE_Models_Rest_Read_Controller::handle_request_get_all( EED_REST_API::ee_api_namespace . 'registrations' );
		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'json_registrations_cannot_list', $response->get_error_code() );
	}

	public function test_handle_request_get_all_mine__not_logged_in(){
		$r = $this->new_model_obj_with_dependencies('Registration');
		$response = EE_Models_Rest_Read_Controller::handle_request_get_all_mine( EED_REST_API::ee_api_namespace . 'registrations/mine' );
		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'json_registrations_cannot_list', $response->get_error_code() );
	}
	public function test_handle_request_get_related__not_logged_in(){
		$r = $this->new_model_obj_with_dependencies('Registration');
		$response = EE_Models_Rest_Read_Controller::handle_request_get_related( EED_REST_API::ee_api_namespace . 'registrations/' . $r->ID() . '/attendee', $r->ID() );
		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'json_attendee_cannot_list', $response->get_error_code() );
	}

	/**
	* @param string $role
	* @return \WP_User
	*/
	public function get_wp_user_mock( $role = 'administrator' ) {
	   /** @type WP_User $user */
	   $user = $this->factory->user->create_and_get();
	   $user->add_role( $role );
	   return $user;
	}

	/**
	 * Creates a new wp user with the specified role and makes them the new current user
	 * @global type $current_user
	 * @param type $role
	 * @return WP_User
	 */
	public function set_current_user_to_new( $role = 'administrator' ){
		global $current_user;
		$current_user = $this->get_wp_user_mock( $role );
		return $current_user;
	}
}

// End of file EE_Models_Rest_Read_Controller_Test.php