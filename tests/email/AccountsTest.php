<?php
class email_AccountsTest extends PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		parent::setUp();
	}
	
	
	function testForceCoverAndFolder()
	{
		$Router = cls::get('email_Router');
		
		$testAccId = email_Accounts::save(
			(object)array(
				'eMail' => 'test@example.com'
			)
		);
		
		if (!$testAccId) {
			$this->markTestIncomplete('Невъзможно създаване на тестов акаунт');
			return;
		}
		
		$folderId = email_Accounts::forceCoverAndFolder(
			(object)array(
				'id' => $testAccId
			)
		);
		
		$this->assertNotNull($folderId);
		$this->assertEquals('test@example.com', doc_Folders::fetchField($folderId, 'title'));
		
		email_Accounts::delete($testAccId);
		doc_Folders::delete($folderId);
	}
}