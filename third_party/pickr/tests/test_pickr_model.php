<?php

/**
 * Tests for the Pickr model.
 *
 * @package			Pickr
 * @author 			Stephen Lewis <stephen@experienceinternet.co.uk>
 * @copyright 		Experience Internet
 */

require_once PATH_THIRD .'pickr/models/pickr_model' .EXT;
require_once PATH_THIRD .'pickr/tests/mocks/mock_pickr_flickr' .EXT;

class Test_pickr_model extends Testee_unit_test_case {

	/* --------------------------------------------------------------
	 * PRIVATE PROPERTIES
	 * ------------------------------------------------------------ */
	
	/**
	 * Pickr model.
	 *
	 * @access	private
	 * @var		Pickr_model
	 */
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
		
		/**
		 * Called from the model constructor, so needs
		 * to be defined here.
		 */
		
		$this->_ee->config->setReturnValue('item', 1, array('site_id'));
		
		// Doing this ensures a fresh model for each test.
		$this->_model = new Pickr_model();
		
		// Mock the API connector object.
		Mock::generate('Mock_pickr_flickr', 'Pickr_flickr');
	}
	
	
	
	/* --------------------------------------------------------------
	 * TEST METHODS
	 * ------------------------------------------------------------ */
	
	public function test_get_flickr_username_member_field_id__pass()
	{
		$config 	= $this->_ee->config;
		$field_id 	= 'm_field_id_10';
		
		/**
		 * TRICKY:
		 * Ideally, we want to use expectOnce here. However, because the model
		 * calls $this->_ee->config->item in the constructor, and expectOnce does
		 * not take into account the arguments passed, doing so will result in a
		 * failed test.
		 */
		
		$config->expect('item', array('flickr_username_member_field'));
		$config->setReturnValue('item', $field_id, array('flickr_username_member_field'));
		
		$this->assertIdentical($this->_model->get_flickr_username_member_field_id(), $field_id);
	}
	
	
	public function test_get_flickr_buddy_icon_member_field_id__pass()
	{
		$config 	= $this->_ee->config;
		$field_id	= 'm_field_id_10';

		$config->expect('item', array('flickr_buddy_icon_member_field'));
		$config->setReturnValue('item', $field_id, array('flickr_buddy_icon_member_field'));

		$this->assertIdentical($this->_model->get_flickr_buddy_icon_member_field_id(), $field_id);
	}
	
	
	public function test_get_member_flickr_username__pass()
	{
		// Shortcuts.
		$db 	= $this->_ee->db;
		$model	= $this->_model;

		// Dummy values.
		$flickr_username 	= 'wibble';
		$member_id 			= '5';
		$member_field_id	= 'm_field_id_10';

		// Configuration items.
		$this->_ee->config->setReturnValue('item', $member_field_id, array('flickr_username_member_field'));

		// Query row.
		$db_row = new StdClass();
		$db_row->$member_field_id = $flickr_username;

		// Query result.
		$db_result = $this->_get_mock('db_query');
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
	
	
	public function test_get_member_flickr_username__unknown_member()
	{
		// Shortcuts.
		$db 	= $this->_ee->db;
		$model	= $this->_model;

		// Query result.
		$db_result = $this->_get_mock('db_query');
		$db_result->setReturnValue('num_rows', 0);

		// Database.
		$db->setReturnReference('select', $db);
		$db->setReturnReference('get_where', $db_result);

		// Run the tests.
		$this->assertIdentical($model->get_member_flickr_username('100'), '');
	}
	
	
	public function test_get_member_flickr_username__invalid_member_id()
	{
		$this->assertIdentical($this->_model->get_member_flickr_username('NULL'), '');
	}


	public function test_save_member_flickr_buddy_icon__pass()
	{
		// Shortcuts.
		$db		= $this->_ee->db;
		$model	= $this->_model;

		// Dummy values.
		$field_id	= $model->get_flickr_buddy_icon_member_field_id();
		$member_id	= '5';
		$icon_url	= 'http://myphoto.com/';

		// Query.
		$data 	= array($field_id => $icon_url);
		$where	= array('member_id' => $member_id);

		$db->expectOnce('update', array('member_data', $data, $where));
		$db->expectOnce('affected_rows');
		$db->setReturnValue('affected_rows', 1);

		// Run the tests.
		$this->assertIdentical($model->save_member_flickr_buddy_icon($member_id, $icon_url), TRUE);
	}
	
	
	public function test_save_member_flickr_buddy_icon__not_saved()
	{
		$db = $this->_ee->db;

		$db->expectOnce('update');
		$db->expectOnce('affected_rows');
		$db->setReturnValue('affected_rows', 0);

		$this->assertIdentical($this->_model->save_member_flickr_buddy_icon('10', ''), FALSE);
	}
	
	
	public function test_save_member_flickr_buddy_icon__invalid_member_id()
	{
		$this->assertIdentical($this->_model->save_member_flickr_buddy_icon('NULL', ''), FALSE);
	}


	public function test_get_flickr_nsid_from_username__pass()
	{
		$model	= $this->_model;
		$conn 	= new Pickr_flickr();		// Mock object. @see __construct.
		
		$flickr_username = 'wibble';
		$flickr_nsid = '12345678@N00';

		$flickr_user = array(
			'stat' => 'ok',
			'user' => array(
				'id'		=> $flickr_nsid,
				'nsid'		=> $flickr_nsid,
				'username'	=> array('_content' => $flickr_username)
			)
		);
		
		$conn->expectOnce('people_find_by_username', array($flickr_username));
		$conn->setReturnReference('people_find_by_username', $flickr_user, array($flickr_username));
		
		// Set model API connector.
		$model->set_api_connector($conn);
		
		// Run the tests.
		$this->assertIdentical($model->get_flickr_nsid_from_username($flickr_username), $flickr_nsid);
	}
	
	
	public function test_get_flickr_nsid_from_username__no_credentials()
	{
		$model		= $this->_model;
		$conn		= new Pickr_flickr();		// Mock object. @see __construct.
		$exception 	= new Pickr_exception('API credentials not set.');

		$conn->throwOn('people_find_by_username', $exception);
		$model->set_api_connector($conn);

		// Run the test.
		$this->expectException($exception);
		$model->get_flickr_nsid_from_username('NULL');
	}
	
	
	public function test_get_flickr_nsid_from_username__api_exception()
	{
		$model		= $this->_model;
		$conn		= new Pickr_flickr();		// Mock object. @see __construct.
		$exception	= new Pickr_api_exception('User not found', '1');

		$conn->throwOn('people_find_by_username', $exception);
		$model->set_api_connector($conn);

		// Run the test.
		$this->expectException($exception);
		$model->get_flickr_nsid_from_username('wibble');
	}
	
	
	public function test_get_flickr_nsid_from_username__no_api_connector()
	{
		/**
		 * NOTE:
		 * This confirms that the 'API connector' check works for all API-related
		 * methods.
		 */

		$this->expectException(new Pickr_exception('API connector not set.'));
		$this->_model->get_flickr_nsid_from_username('wibble');
	}
	
	
	public function test_get_flickr_user_buddy_icon__pass()
	{
		$model	= $this->_model;
		$conn	= new Pickr_flickr();		// Mock object. @see __construct.

		$flickr_iconfarm	= '10';
		$flickr_iconserver	= '20';
		$flickr_nsid 		= '123456';
		$flickr_username 	= 'wibble';

		$flickr_user = array(
			'stat'		=> 'ok',
			'person' 	=> array(
				'id'			=> $flickr_nsid,
				'nsid'			=> $flickr_nsid,
				'ispro'			=> '0',
				'iconserver'	=> $flickr_iconserver,
				'iconfarm'		=> $flickr_iconfarm,
				'path_alias'	=> $flickr_username,
				'username'		=> array('_content' => $flickr_username),
				'realname'		=> array('_content' => 'Ewan the Photo'),
				'mbox_sha1sum'	=> array('_content' => '6e558ded4d3226ef7188b9ff70624fe4a4912622'),
				'location'		=> array('_content' => 'Caerphilly, Wales'),
				'photosurl'		=> array('_content' => 'http://flickr.com/photos/' .$flickr_username),
				'profileurl'	=> array('_content' => 'http://flickr.com/people/' .$flickr_username),
				'mobileurl'		=> array('_content' => 'http://m.flickr.com/photostream.gne?id=1234567'),
				'photos'		=> array(
					'firstdatetaken'	=> '2005-01-01 01:00:00',
					'firstdate'			=> '1234567890',
					'count'				=> '200'
				)
			)
		);

		$buddy_icon = 'http://farm' .$flickr_iconfarm .'.static.flickr.com/' .$flickr_iconserver .'/buddyicons/' .$flickr_nsid .'.jpg';

		$conn->expectOnce('people_get_info', array($flickr_nsid));
		$conn->setReturnReference('people_get_info', $flickr_user, array($flickr_nsid));

		// Set the model API connector.
		$model->set_api_connector($conn);

		// Run the tests.
		$this->assertIdentical($model->get_flickr_user_buddy_icon($flickr_nsid), $buddy_icon);
	}
	
	
	public function test_get_flickr_user_buddy_icon__api_exception()
	{
		$model		= $this->_model;
		$conn		= new Pickr_flickr();
		$exception	= new Pickr_api_exception('User not found', '1');

		$conn->throwOn('people_get_info', $exception);
		$model->set_api_connector($conn);

		// Run the test.
		$this->expectException($exception);
		$model->get_flickr_user_buddy_icon('wibble');
	}
	
	
	public function test_activate_extension()
	{
		$model 	= $this->_model;
		$db 	= $this->_ee->db;

		$data = array(
			'class'		=> $model->get_extension_class(),
			'enabled'	=> 'y',
			'hook'		=> 'member_register_validate_members',
			'method'	=> 'on_member_register_validate_members',
			'settings'	=> '',
			'version'	=> $model->get_package_version()
		);

		$db->expectOnce('insert', array('extensions', $data));

		// Run the test.
		$model->activate_extension();
	}


	public function test_disable_extension()
	{
		$model	= $this->_model;
		$db		= $this->_ee->db;

		$db->expectOnce('delete', array('extensions', array('class' => $model->get_extension_class())));

		// Run the test.
		$model->disable_extension();
	}


	public function test_update_extension__update()
	{
		$model	= $this->_model;
		$db		= $this->_ee->db;

		$actual_version		= '1.0.0';
		$installed_version	= '0.9.0';

		$data = array('version' => $actual_version);
		$where = array('class' => $model->get_extension_class());

		$db->expectOnce('update', array('extensions', $data, $where));

		// Run the test.
		$model->update_extension($installed_version, $actual_version);
	}


	public function test_update_extension__no_update()
	{
		$model	= $this->_model;
		$db		= $this->_ee->db;

		$actual_version		= '1.0.0';
		$installed_version	= '';

		$db->expectNever('update');

		// Run the tests.
		$this->assertIdentical($model->update_extension($installed_version, $actual_version), FALSE);

		$installed_version = $actual_version;
		$this->assertIdentical($model->update_extension($installed_version, $actual_version), FALSE);
	}

}

/* End of file 		: test_pickr_model.php */
/* File location	: third_party/pickr/tests/test_pickr_model.php */