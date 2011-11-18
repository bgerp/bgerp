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
		);
		
		// Промяна на достъпа до email_Router::getRuleKey()
		$getRuleKey = new ReflectionMethod(
			'email_Router', 'getRuleKey'
        );
        $getRuleKey->setAccessible(TRUE);
		
		$this->assertEquals('known-sender@example.com|known-recipient@here.com', $getRuleKey->invoke($this->Router, 'fromTo', $message));
		$this->assertEquals('known-sender@example.com', $getRuleKey->invoke($this->Router, 'from', $message));
		$this->assertEquals('known-sender@example.com', $getRuleKey->invoke($this->Router, 'sent', $message));
		$this->assertEquals('example.com', $getRuleKey->invoke($this->Router, 'domain', $message));
	}
	
	/**
	 * Тест за рутиране на писмо от bypass акаунт
	 */
	public function testRouteByBypassAccount()	{
		$Router = $this->getRouterMockedRules();

		$message = (object)array(
			'accId' => 'bypass-account',
		);
				
		$location = $Router->route($message);
		
		$this->assertEquals('BypassAccount', $location->routeRule);
		$this->assertEquals('bypass-account-folderId', $location->folderId);
		$this->assertNull($location->threadId);
	}

	/**
	 * Тест за рутиране на писмо, изпратено до основен (generic) получател
	 */
	public function testRouteByRecipientGeneric() {
		$Router = $this->getRouterMockedRules();

		$message = (object)array(
			'from'  => 'known-sender@example.com',
			'to'   => 'generic@here.com',
		);
				
		$location = $Router->route($message);
		
		$this->assertNotEquals('Recipient', $location->routeRule);
	}

	/**
	 * Тест за рутиране на писмо, изпратено до не-основен (non-generic) получател
	 */
	public function testRouteByRecipientNonGeneric() {
		$Router = $this->getRouterMockedRules();

		$message = (object)array(
			'from'  => 'known-sender@example.com',
			'to'   => 'inbox@here.com',
		);
				
		$location = $Router->route($message);
		
		$this->assertEquals('Recipient', $location->routeRule);
		$this->assertEquals('inbox-folderId', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за рутиране според адреса на изпращача И адреса на получателя
	 */
	public function testRouteByFromToNonGeneric() {
		$Router = $this->getRouterMockedRules();

		$message = (object)array(
			'from' => 'known-sender@example.com',
			'to'   => 'recipient@here.com',
		);
				
		$location = $Router->route($message);
		
		$this->assertEquals('FromTo', $location->routeRule);
		$this->assertEquals('fromTo', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за рутиране според адреса на изпращача И адреса на получателя, в случай че 
	 * получателя е основен (generic) адрес
	 */
	public function testRouteByFromToGeneric() {
		$Router = $this->getRouterMockedRules();

		$message = (object)array(
			'from' => 'known-sender@example.com',
			'to'   => 'generic@here.com',
		);
				
		$location = $Router->route($message);
		
		$this->assertNotEquals('FromTo', $location->routeRule);
	}
	
	/**
	 * Тест за рутиране според адреса на изпращача, в случай на познат изпращач
	 */
	public function testRouteByKnownSender() {
		$Router = $this->getRouterMockedRules();

		$message = (object)array(
			'from' => 'known-sender@example.com',
			'to'   => 'unknown-recipient@here.com',
		);
				
		$location = $Router->route($message);
		
		$this->assertEquals('Sender', $location->routeRule);
		$this->assertEquals('from', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за рутиране според адреса на изпращача, в случай на непознат изпращач
	 */
	public function testRouteByUnknownSender() {
		$Router = $this->getRouterMockedRules();

		$message = (object)array(
			'from' => 'unknown-sender@example.com',
			'to'   => 'unknown-recipient@here.com',
		);
				
		$location = $Router->route($message);
		
		$this->assertNotEquals('Sender', $location->routeRule);
	}
	
	/**
	 * Тест за рутиране на непознат изпращач, който има визитка в CRM
	 */
	public function testRouteByKnownCrm() {
		$Router = $this->getRouterMockedRules();

		$message = (object)array(
			'from' => 'crm-sender@example.com',
			'to'   => 'unknown-recipient@here.com',
		);
				
		$location = $Router->route($message);
		
		$this->assertEquals('Crm', $location->routeRule);
		$this->assertEquals('crm-sender-folder-id', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за рутиране на непознат изпращач, който няма визитка в CRM
	 */
	public function testRouteByUnknownCrm() {
		$Router = $this->getRouterMockedRules();

		$message = (object)array(
			'from' => 'no-crm-sender@example.com',
			'to'   => 'unknown-recipient@here.com',
		);
				
		$location = $Router->route($message);
		
		$this->assertNotEquals('Crm', $location->routeRule);
	}
	
	/**
	 * Тест за рутиране според мястото, откъдето последно е изпращано до същия изпращач
	 */
	public function testRouteByKnownSent() {
		$Router = $this->getRouterMockedRules();

		$message = (object)array(
			'from' => 'new-sender@example.com',
			'to'   => 'unknown-recipient@here.com',
		);
				
		$location = $Router->route($message);
		
		$this->assertNotEquals('Crm', $location->routeRule);
	}
	
	/**
	 * Тест за рутиране според домейна на адреса на изпращача
	 */
	public function testRouteByKnownDomain() {
		$Router = $this->getRouterMockedRules();
		
		$message = (object)array(
			'from' => 'unknown-sender@example.com',
			'to'   => 'unknown-recipient@home.com',
		);
		

		$location = $Router->route($message);
		
		$this->assertEquals('Domain', $location->routeRule);
		$this->assertEquals('example.comFolderId', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за рутиране на писмо от неизвестен домейн
	 */
	public function testRouteByUnknownDomain() {
		$Router = $this->getRouterMockedRules();
		
		$message = (object)array(
			'from' => 'unknown-sender@unknown.com',
			'to'   => 'unknown-recipient@home.com',
		);
		
		$location = $Router->route($message);
		
		$this->assertNotEquals('Domain', $location->routeRule);
	}
	
	/**
	 * Тест за рутиране според държавата (в случай, че е налична)
	 */
	public function testRouteByKnownCountry() {
		$Router = $this->getRouterMockedRules();
		
		$message = (object)array(
			'from' => 'unknown-sender@unknown.com',
			'to'   => 'unknown-recipient@home.com',
			'country' => 'known-country-id'
		);

		$location = $Router->route($message);
		
		$this->assertEquals('Country', $location->routeRule);
		$this->assertEquals('known-country-folder-id', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за рутиране при липсваща или невалдна информация за държавата
	 */
	public function testRouteByUnknownCountry() {
		$Router = $this->getRouterMockedRules();
		
		// 1. Тест при наличен, но невалиден ид на държава
		$message = (object)array(
			'from' => 'unknown-sender@unknown.com',
			'to'   => 'unknown-recipient@home.com',
			'country' => 'non-existing-country'
		);
		
		$location = $Router->route($message);
		
		$this->assertNotEquals('Country', $location->routeRule);
		
		// 2. Тест при липсващ номер на държава
		unset($message->country);

		$location = $Router->route($message);
		$this->assertNotEquals('Country', $location->routeRule);
	}
	
	/**
	 * Тест за рутиране на нерутируеми писма - в папката на акаунта
	 */
	public function testRouteByAccount() {
		$Router = $this->getRouterMockedRules();
		
		$message = (object)array(
			'accId' => 'non-bypass-account',
			'from' => 'unknown-sender@unknown.com',
			'to'   => 'unknown-recipient@home.com',
		);
		
		$location = $Router->route($message);
		
		$this->assertEquals('Account', $location->routeRule);
		$this->assertEquals('non-bypass-account-folder-id', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * @return email_Router
	 */
	private function getRouterMockedRules() {
		$Router = $this->getMock('email_Router', 
			array(
				'fetchRule', 
				'forceCountryFolder',
				'isBypassAccount',
				'forceAccountFolder',
				'isGenericRecipient',
				'getRecipientFolder',
				'getCrmFolderId',
				'getAccountFolderId',
				'forceOrphanFolder',
			)
		);
		
		$Router->expects($this->any())
			->method('fetchRule')
			->will($this->returnCallback(array($this, 'fetchRule')));
			
		$Router->expects($this->any())
			->method('forceCountryFolder')
			->will($this->returnValueMap(
				array(
					array('known-country-id', 'known-country-folder-id'),
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
		
		$Router->expects($this->any())
			->method('isGenericRecipient')
			->will($this->returnCallback(array($this, 'isGenericRecipient')));
			
		$Router->expects($this->any())
			->method('getRecipientFolder')
			->will($this->returnValueMap(
				array(
					array('generic@here.com', 'generic-folderId'),
					array('inbox@here.com', 'inbox-folderId'),
				)
			));
			
		$Router->expects($this->any())
			->method('getCrmFolderId')
			->will($this->returnCallback(array($this, 'getCrmFolderId')));
			
		$Router->expects($this->any())
			->method('getAccountFolderId')
			->will($this->returnCallback(array($this, 'getAccountFolderId')));
			
		$Router->expects($this->any())
			->method('forceOrphanFolder')
			->will($this->returnValue('orphan-folder-id'));
			
		return $Router;
	}
	
	function fetchRule($type, $key) {
		static $rules = array(
			1 => array(
				'type' => 'fromTo', 
				'key' => 'known-sender@example.com|recipient@here.com', 
				'folderId' => 'fromTo'
			),
			array(
				'type' => 'from', 
				'key' => 'known-sender@example.com', 
				'folderId' => 'from'
			),
			array(
				'type' => 'sent', 
				'key' => 'new-sender@example.com', 
				'folderId' => 'sent'
			),
			array(
				'type' => 'domain', 
				'key' => 'example.com', 
				'folderId' => 'example.comFolderId'
			),
			array(
				'type' => 'fromTo', 
				'key' => 'known-sender@example.com|generic@here.com', 
				'folderId' => 'generic-folder-id'
			),
		);
		
		foreach ($rules as $rule) {
			if ($type == $rule['type'] && $key == $rule['key']) {
				return (object)$rule;
			}
		}
		
		return FALSE;
	}
	
	function isGenericRecipient($recipient) {
		return $recipient === 'generic@here.com';
	}
	
	function getCrmFolderId($email) {
		return ($email == 'crm-sender@example.com') ? 'crm-sender-folder-id' : NULL;
	}
	
	function getAccountFolderId($accId) {
		return ($accId == 'non-bypass-account') ? 'non-bypass-account-folder-id' : NULL;
	}
}