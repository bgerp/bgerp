<?php
class email_UserInboxPlgTest extends framework_TestCase
{
	protected function setUp()
	{
	    parent::setUp();
	}
	
	public function testCreateUser()
	{
	    try {
	    core_Users::save(
	        (object)array(
	            'nick' => 'tester1',
	            'password' => 'password1',
	        )
	    );
	    } catch (Exception $e) {
	        print_r($e);
	        exit;
	    }
	}
}