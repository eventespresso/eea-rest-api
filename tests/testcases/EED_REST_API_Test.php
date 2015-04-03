<?php
if ( !defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 *
 * EED_REST_API_Test
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EED_REST_API_Test extends EE_UnitTestCase{
	public function test_extract_includes_for_this_model__basic(){
		$this->assertEquals( array(
			'EVT_ID',
			'EVT_name'
		), EED_REST_API::extract_includes_for_this_model( 'EVT_ID,EVT_name' ) );
	}
	public function test_extract_includes_for_this_model__extra_whitespace() {
		$this->assertEquals( array(
			'EVT_ID',
			'EVT_name',
			'EVT_desc'
		), EED_REST_API::extract_includes_for_this_model( 'EVT_ID , EVT_name , EVT_desc' ) );
	}
	public function test_extract_includes_for_this_model__related_model() {
		$this->assertEquals( array(), EED_REST_API::extract_includes_for_this_model( 'Registration.*' ) );
	}
	public function test_extract_includes_for_this_model__related_model_all() {
		$this->assertEquals( array(
			'*'
		), EED_REST_API::extract_includes_for_this_model( 'Registration.*', 'Registration' ) );
	}
	public function test_extract_includes_for_this_model__related_models_but_searching_for_this_one() {
		$this->assertEquals( array(
		), EED_REST_API::extract_includes_for_this_model( 'Registration.REG_ID, Registration.Attendee.ATT_ID' ) );
	}
	public function test_extract_includes_for_this_model__related_models_but_searching_for_other() {
		$this->assertEquals( array(
			'REG_ID',
			'Attendee.ATT_ID'
		), EED_REST_API::extract_includes_for_this_model( 'Registration.REG_ID, Registration.Attendee.ATT_ID', 'Registration' ) );
	}

	public function test_handle_request_get_one__event_includes() {
		$event = $this->new_model_obj_with_dependencies( 'Event' );
		$result = EED_REST_API::handle_request_get_one( EED_REST_API::ee_api_namespace . 'events/' . $event->ID(), $event->ID(), 'EVT_ID,EVT_name' );
		$this->assertEquals(
			array (
				'EVT_ID' => $event->ID(),
				'EVT_name' => $event->name()
				), $result );
	}
	public function test_handle_request_get_one__event_include_non_model_field() {
		$event = $this->new_model_obj_with_dependencies( 'Event' );
		$result = EED_REST_API::handle_request_get_one( EED_REST_API::ee_api_namespace . 'events/' . $event->ID(), $event->ID(), 'EVT_desc_raw, EVT_desc' );
		$this->assertEquals(
			array (
				'EVT_ID' => $event->ID(),
				'EVT_desc' => $event->get('EVT_desc'),
				'EVT_desc_raw' => $event->get_pretty( 'EVT_desc'),
				), $result );
	}
	public function test_extract_includes_for_this_model__null() {
		$this->assertEquals( array(), EED_REST_API::extract_includes_for_this_model( '*' ) );
	}
	public function test_handle_request_get_one__event() {
		$event = $this->new_model_obj_with_dependencies( 'Event' );
		$result = EED_REST_API::handle_request_get_one( EED_REST_API::ee_api_namespace . 'events/' . $event->ID(), $event->ID() );
		unset( $result[ 'EVT_created' ] );
		unset( $result[ 'EVT_modified' ] );
		unset( $result[ 'EVT_visible_on' ] );
		unset( $result[ 'meta' ] );
		$this->assertEquals(
			array (
				'EVT_ID' => (string)$event->get( 'EVT_ID' ),
				'EVT_name' => '1_EVT_name',
				'EVT_desc' => '1_EVT_desc',
				'EVT_slug' => '1_evt_slug',
				'EVT_short_desc' => '1_EVT_short_desc',
				'EVT_wp_user' => '1',
				'parent' => '0',
				'EVT_order' => '1',
				'status' => 'draft',
				'comment_status' => '1_comment_status',
				'ping_status' => '1_ping_status',
				'EVT_display_desc' => '1',
				'EVT_display_ticket_selector' => '1',
				'EVT_additional_limit' => '1',
				'EVT_default_registration_status' => 'RPP',
				'EVT_member_only' => '0',
				'EVT_phone' => '1_EVT_phone',
				'EVT_allow_overflow' => '0',
				'EVT_external_URL' => '1_EVT_external_URL',
				'EVT_donations' => '0',
				'EVT_desc_raw' => '1_EVT_desc',
			  ),
				$result
				);
	}

	public function test_handle_request_get_one__registration_include_attendee(){
		$r = $this->new_model_obj_with_dependencies( 'Registration' );
		$entity = EED_REST_API::handle_request_get_one( EED_REST_API::ee_api_namespace . 'registrations/' . $r->ID(), $r->ID(), 'Attendee.*');
		$this->assertArrayHasKey( 'attendee', $entity );
	}

	public function test_handle_request_get_one__registration_include_answers_and_questions(){
		$r = $this->new_model_obj_with_dependencies( 'Registration' );
		$a = $this->new_model_obj_with_dependencies( 'Answer', array( 'REG_ID' => $r->ID() ) );
		$entity = EED_REST_API::handle_request_get_one( EED_REST_API::ee_api_namespace . 'registrations/' . $r->ID(), $r->ID(), 'Answer.Question.*');
		$this->assertArrayHasKey( 'answers', $entity );
		$answers = $entity['answers'];
		foreach( $answers as $answer ) {
			$this->assertArrayHasKey( 'question', $answer );
		}
	}

	public function test_handle_request_get_one__registration_include_answers_and_question_bare_min_from_each(){
		$r = $this->new_model_obj_with_dependencies( 'Registration' );
		$a = $this->new_model_obj_with_dependencies( 'Answer', array( 'REG_ID' => $r->ID() ) );
		$entity = EED_REST_API::handle_request_get_one( EED_REST_API::ee_api_namespace . 'registrations/' . $r->ID(), $r->ID(), 'Answer.ATT_ID, Answer.Question.QST_ID');
		$this->assertArrayHasKey( 'answers', $entity );
		$answers = $entity['answers'];
		foreach( $answers as $answer ){
			$this->assertArrayHasKey( 'question', $answer );
		}
	}

}

// End of file EED_REST_API_Test.php