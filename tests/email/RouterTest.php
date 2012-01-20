<?php
class email_RouterTest extends PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		parent::setUp();
	}
	
	function testExtractSubjectThreadHnds() 
	{
		$this->assertEquals(array(), email_Router::extractSubjectThreadHnds('This is a test without thread handle'));
		$this->assertEquals(array(), email_Router::extractSubjectThreadHnds(''));
		$this->assertEquals(array(), email_Router::extractSubjectThreadHnds('<> Fake <abc> <123> <abc!>'));
		$this->assertEquals(array('Handle'=>'Handle'), email_Router::extractSubjectThreadHnds('<Handle> <>'));
		$this->assertEquals(array('Handle'=>'Handle'), email_Router::extractSubjectThreadHnds('Prefix <Handle> Suffix'));
		$this->assertEquals(array('Handle'=>'Handle'), email_Router::extractSubjectThreadHnds('Prefix <Handle> Suffix <Handle> Lastfix'));
		$this->assertEquals(array('Handle1'=>'Handle1', 'Handle2'=>'Handle2'), email_Router::extractSubjectThreadHnds('Prefix <Handle1> Suffix <Handle2> Lastfix'));
	}
	
	function testForceCountryFolder()
	{
		$Router = $this->getMock('email_Router', array('getCountryName'));
		
		$testCountry = 'Neverland';
		
		$Router->expects($this->any())
			->method('getCountryName')
			->will($this->returnValue($testCountry));
			
		$expected = sprintf(UNSORTABLE_COUNTRY_EMAILS, $testCountry);
		
		$folderId = $Router->forceCountryFolder(9999);
		
		$this->assertNotNull($folderId);
		$this->assertEquals($expected, doc_Folders::fetchField($folderId, 'title'));

		doc_Folders::delete($folderId);
	}
	
	function testForceAccountFolder()
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
		
				
		$folderId = $Router->forceAccountFolder($testAccId);
		
		$this->assertNotNull($folderId);
		$this->assertEquals('test@example.com', doc_Folders::fetchField($folderId, 'title'));

		email_Accounts::delete($testAccId);
		doc_Folders::delete($folderId);
	}
}