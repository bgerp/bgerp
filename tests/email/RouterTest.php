<?php
//class email_MessagesMock
//{
//	/**
//	 * @var PHPUnit_Framework_TestCase
//	 */
//	static $case;
//	
//	static $moves = array();
//	
//	static function getQuery() { 
//		$fakeRecs = array(
//			1 => (object)array(
//				'id'       => 1,
//				'folderId' => 1,
//				'threadId' => 2
//			),
//			2 => (object)array(
//				'id'       => 1,
//				'folderId' => 5,
//				'threadId' => 15
//			),
//		);
//		
//		$query = self::$case->getMock('core_Query', array('fetch'));
//		
//		$query->expects(self::$case->any())
//			->method('fetch')
//			->will(
//				call_user_func_array(array(self::$case, 'onConsecutiveCalls'), $fakeRecs)
//			);
//		
//		return $query;
//	}
//	
//	static function getUnsortedFolder() { return 1; }
//
//	static function move($rec, $location) { 
//		self::$moves[] = array($rec, $location); 
//	}
//}
//
//class_alias('email_MessagesMock', 'email_Messages');

class email_RouterTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var email_Router
	 */
	protected $Router;
	
	private $email_Messages = array(
		array(
			'from' => 'a@example.com',
			'to'   => 'b@example.com',
			'domain' => 'example.com',
			'country' => 1
		)
	);
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		
//		email_MessagesMock::$case = $this;

		$this->Router = cls::get('email_Router');
	}
	
	/**
	 * Дефинирани ли са коректно константите.
	 *
	 */
	public function testDefines() {
		$this->assertTrue(defined('UNSORTABLE_EMAILS'), 'Не е дефинирана константа UNSORTABLE_EMAILS');
		$this->assertTrue(defined('UNSORTABLE_COUNTRY_EMAILS'), 'Не е дефинирана константа UNSORTABLE_COUNTRY_EMAILS');
	}
	
	public function testGetRuleKey() {
		$message = (object)array(
			'from' => 'known-sender@example.com',
			'to'   => 'known-recipient@here.com',
			'country' => 26, // - България - key(mvc=drdata_Countries)
		);
		$this->assertEquals('known-sender@example.com|known-recipient@here.com', $this->Router->getRuleKey('fromTo', $message));
		$this->assertEquals('known-sender@example.com', $this->Router->getRuleKey('from', $message));
		$this->assertEquals('example.com', $this->Router->getRuleKey('domain', $message));
	}
	
	/**
	 * Тест за рутиране на писмо от bypass акаунт
	 */
	public function testRouteByBypassAccount()	{
		$Router = $this->getRouterMockedRules();

		$message = (object)array(
			'accId' => 'bypass-account',
			'from'  => 'known-sender@example.com',
			'to'   => 'known-recipient@here.com',
			'country' => 26, // - България - key(mvc=drdata_Countries)
		);
				
		$location = $Router->route($message);
		
		$this->assertEquals('bypass-account-folderId', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за рутиране според адреса на изпращача И адреса на получателя
	 */
	public function testRouteByFromTo() {
		$Router = $this->getRouterMockedRules();

		$message = (object)array(
			'from' => 'known-sender@example.com',
			'to'   => 'known-recipient@here.com',
			'domain' => 'example.com',
			'country' => 1
		);
				
		$location = $Router->route($message);
		
		$this->assertEquals('fromTo', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за рутиране според адреса на изпращача
	 */
	public function testRouteByFrom() {
		$Router = $this->getRouterMockedRules();

		$message = (object)array(
			'from' => 'known-sender@example.com',
			'to'   => 'unknown-recipient@here.com',
			'domain' => 'example.com',
			'country' => 1
		);
				
		$location = $Router->route($message);
		
		$this->assertEquals('from', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за рутиране според домейна на адреса на изпращача
	 */
	public function testRouteByDomain() {
		$Router = $this->getRouterMockedRules();
		
		$message = (object)array(
			'from' => 'unknown-sender@example.com',
			'to'   => 'unknown-recipient@home.com',
		);
		

		$location = $Router->route($message);
		
		$this->assertEquals('example.comFolderId', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за рутиране според държавата
	 */
	public function testRouteByCountry() {
		$Router = $this->getRouterMockedRules();
		
		$message = (object)array(
			'from' => 'unknown-sender@unknown-domain.com',
			'to'   => 'unknown-recipient@home.com',
			'country' => 26
		);
		

		$location = $Router->route($message);
		
		$this->assertEquals('BulgariaFolderId', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	private function getRouterMockedRules() {
		$Router = $this->getMock('email_Router', 
			array(
				'fetchRule', 
				'forceCountryFolder',
				'isBypassAccount',
				'forceAccountFolder',
			)
		);

		$Router->expects($this->any())
			->method('fetchRule')
			->will($this->returnCallback(array($this, 'fetchRule')));
			
		$Router->expects($this->any())
			->method('forceCountryFolder')
			->will($this->returnValueMap(
				array(
					array(1,  'Country1FolderId'),
					array(26, 'BulgariaFolderId'),
				)
			));
		
		$Router->expects($this->any())
			->method('isBypassAccount')
			->will($this->returnValueMap(
				array(
					array('bypass-account',  TRUE),
					array('non-bypass-account', FALSE),
				)
			));
		
		$Router->expects($this->any())
			->method('forceAccountFolder')
			->will($this->returnValueMap(
				array(
					array('bypass-account', 'bypass-account-folderId'),
					array('non-bypass-account', 'non-bypass-account-folderId'),
				)
			));
		
		return $Router;
	}
	
	function fetchRule($type, $key) {
		static $rules = array(
			1 => array(
				'type' => 'fromTo', 
				'key' => 'known-sender@example.com|known-recipient@here.com', 
				'folderId' => 'fromTo'
			),
			2 => array(
				'type' => 'from', 
				'key' => 'known-sender@example.com', 
				'folderId' => 'from'
			),
			3 => array(
				'type' => 'sent', 
				'key' => 'known-recipient@here.com', 
				'folderId' => 'sent'
			),
			3 => array(
				'type' => 'domain', 
				'key' => 'example.com', 
				'folderId' => 'example.comFolderId'
			),
		);
		
		foreach ($rules as $rule) {
			if ($type == $rule['type'] && $key == $rule['key']) {
				return (object)$rule;
			}
		}
		
		return FALSE;
	}
	
}