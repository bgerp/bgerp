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
}