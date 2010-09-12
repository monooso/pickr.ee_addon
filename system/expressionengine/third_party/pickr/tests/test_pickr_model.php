<?php

/**
 * Tests for the Pickr model.
 *
 * @package			Pickr
 * @author 			Stephen Lewis <stephen@experienceinternet.co.uk>
 * @copyright 		Experience Internet
 */

require_once PATH_THIRD .'pickr/models/pickr_model' .EXT;

class Test_pickr_model extends Testee_unit_test_case {
	
	/* --------------------------------------------------------------
	 * PRIVATE PROPERTIES
	 * ------------------------------------------------------------ */
	private $_model;
	
	
	/* --------------------------------------------------------------
	 * PUBLIC METHODS
	 * ------------------------------------------------------------ */
	
	/**
	 * Runs before each test.
	 *
	 * @access	public
	 * @return	void
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_ee->load->model('pickr_model');
	}
	
	
	/* --------------------------------------------------------------
	 * TEST METHODS
	 * ------------------------------------------------------------ */
	
	public function test_get_member_flickr_username_a()
	{
		// Shortcuts.
		$db 	=& $this->_ee->db;
		$model	= $this->_ee->pickr_model;
		
		// Dummy values.
		$flickr_username 	= 'wibble';
		$member_id 			= '5';
		$member_field_id	= $model->get_flickr_username_member_field_id();
		
		// Query row.
		$db_row = new StdClass();
		$db_row->$member_field_id = $flickr_username;
		
		// Query result.
		$db_result = $this->_get_mock('query');
		$db_result->expectOnce('row');
		$db_result->setReturnValue('num_rows', 1);
		$db_result->setReturnReference('row', $db_row);
		
		// Database.
		$db->expectOnce('select', array($member_field_id));
		$db->expectOnce('get_where', array('member_data', array('member_id' => $member_id)));
		
		$db->setReturnReference('select', $db);
		$db->setReturnReference('get_where', $db_result);
		
		// Run the tests.
		$this->assertIdentical($model->get_member_flickr_username($member_id), $flickr_username);
	}
	
	
	public function test_get_member_flickr_username_b()
	{
		// Shortcuts.
		$db 	=& $this->_ee->db;
		$model	= $this->_ee->pickr_model;
		
		// Query result.
		$db_result = $this->_get_mock('query');
		$db_result->setReturnValue('num_rows', 0);
		
		// Database.
		$db->setReturnReference('select', $db);
		$db->setReturnReference('get_where', $db_result);
		
		// Run the tests.
		$this->assertIdentical($model->get_member_flickr_username('NULL'), '');
	}
	
}

/* End of file 		: test_pickr_model.php */
/* File location	: third_party/pickr/tests/test_pickr_model.php */