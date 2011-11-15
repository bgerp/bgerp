<?php
class email_MessagesMock
{
	/**
	 * @var PHPUnit_Framework_TestCase
	 */
	static $case;
	
	static $moves = array();
	
	static function getQuery() { 
		$fakeRecs = array(
			1 => (object)array(
				'id'       => 1,
				'folderId' => 1,
				'threadId' => 2
			),
			2 => (object)array(
				'id'       => 1,
				'folderId' => 5,
				'threadId' => 15
			),
		);
		
		$query = self::$case->getMock('core_Query', array('fetch'));
		
		$query->expects(self::$case->any())
			->method('fetch')
			->will(
				call_user_func_array(array(self::$case, 'onConsecutiveCalls'), $fakeRecs)
			);
		
		return $query;
	}
	
	static function getUnsortedFolder() { return 1; }

	static function move($rec, $location) { 
		self::$moves[] = array($rec, $location); 
	}
}

class email_RouterTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		
		email_MessagesMock::$case = $this;
		class_alias('email_MessagesMock', 'email_Messages');

		$this->Router = cls::get('email_Router');
	}

	/**
	 * Tests catpr_Pricelists->description()
	 */
	public function testRouteAll()
	{
        
		$this->Router->routeAll();
		
		print_r(email_Messages::$moves);
	}

	
}