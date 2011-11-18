<?php

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
		
		$this->Router = $this->getRouter();
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
		
		$message->from = 'sender@public-domain.com';
		$this->assertFalse($getRuleKey->invoke($this->Router, 'domain', $message));
	}
	
	/**
	 * Тест за рутиране на писмо с налична информация за тред
	 */
	public function testRouteByThread() {
		$message = (object)array(
			'hasThreadInfo' => true
		);
		
		$location = $this->Router->route($message);
		
		$this->assertEquals('Thread', $location->routeRule);
		$this->assertEquals('thread-id', $location->threadId);
	}
	
	/**
	 * Тест за рутиране на писмо от bypass акаунт
	 */
	public function testRouteByBypassAccount()	{
		$message = (object)array(
			'accId' => 'bypass-account',
		);
				
		$location = $this->Router->route($message);
		
		$this->assertEquals('BypassAccount', $location->routeRule);
		$this->assertEquals('bypass-account-folder-id', $location->folderId);
		$this->assertNull($location->threadId);
	}

	/**
	 * Тест за рутиране на писмо, изпратено до основен (generic) получател
	 */
	public function testRouteByRecipientGeneric() {
		$message = (object)array(
			'from'  => 'known-sender@example.com',
			'to'   => 'generic@here.com',
		);
				
		$location = $this->Router->route($message);
		
		$this->assertNotEquals('Recipient', $location->routeRule);
	}

	/**
	 * Тест за рутиране на писмо, изпратено до не-основен (non-generic) получател
	 */
	public function testRouteByRecipientNonGeneric() {
		$message = (object)array(
			'from'  => 'known-sender@example.com',
			'to'   => 'inbox@here.com',
		);
				
		$location = $this->Router->route($message);
		
		$this->assertEquals('Recipient', $location->routeRule);
		$this->assertEquals('inbox-folderId', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за рутиране според адреса на изпращача И адреса на получателя
	 */
	public function testRouteByFromToNonGeneric() {
		$message = (object)array(
			'from' => 'known-sender@example.com',
			'to'   => 'recipient@here.com',
		);
				
		$location = $this->Router->route($message);
		
		$this->assertEquals('FromTo', $location->routeRule);
		$this->assertEquals('fromTo', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за рутиране според адреса на изпращача И адреса на получателя, в случай че 
	 * получателя е основен (generic) адрес
	 */
	public function testRouteByFromToGeneric() {
		$message = (object)array(
			'from' => 'known-sender@example.com',
			'to'   => 'generic@here.com',
		);
				
		$location = $this->Router->route($message);
		
		$this->assertNotEquals('FromTo', $location->routeRule);
	}
	
	/**
	 * Тест за рутиране според адреса на изпращача, в случай на познат изпращач
	 */
	public function testRouteByKnownSender() {
		$message = (object)array(
			'from' => 'known-sender@example.com',
			'to'   => 'unknown-recipient@here.com',
		);
				
		$location = $this->Router->route($message);
		
		$this->assertEquals('Sender', $location->routeRule);
		$this->assertEquals('from', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за рутиране според адреса на изпращача, в случай на непознат изпращач
	 */
	public function testRouteByUnknownSender() {
		$message = (object)array(
			'from' => 'unknown-sender@example.com',
			'to'   => 'unknown-recipient@here.com',
		);
				
		$location = $this->Router->route($message);
		
		$this->assertNotEquals('Sender', $location->routeRule);
	}
	
	/**
	 * Тест за рутиране на непознат изпращач, който има визитка в CRM
	 */
	public function testRouteByKnownCrm() {
		$message = (object)array(
			'from' => 'crm-sender@example.com',
			'to'   => 'unknown-recipient@here.com',
		);
				
		$location = $this->Router->route($message);
		
		$this->assertEquals('Crm', $location->routeRule);
		$this->assertEquals('crm-sender-folder-id', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за рутиране на непознат изпращач, който няма визитка в CRM
	 */
	public function testRouteByUnknownCrm() {
		$message = (object)array(
			'from' => 'no-crm-sender@example.com',
			'to'   => 'unknown-recipient@here.com',
		);
				
		$location = $this->Router->route($message);
		
		$this->assertNotEquals('Crm', $location->routeRule);
	}
	
	/**
	 * Тест за рутиране според мястото, откъдето последно е изпращано до същия изпращач
	 */
	public function testRouteByKnownSent() {
		$message = (object)array(
			'from' => 'new-sender@example.com',
			'to'   => 'unknown-recipient@here.com',
		);
				
		$location = $this->Router->route($message);
		
		$this->assertNotEquals('Crm', $location->routeRule);
	}
	
	/**
	 * Тест за рутиране според домейна на адреса на изпращача
	 */
	public function testRouteByKnownDomain() {
		$message = (object)array(
			'from' => 'unknown-sender@example.com',
			'to'   => 'unknown-recipient@home.com',
		);
		
		$location = $this->Router->route($message);
		
		$this->assertEquals('Domain', $location->routeRule);
		$this->assertEquals('example.comFolderId', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за рутиране на писмо от неизвестен домейн
	 */
	public function testRouteByUnknownDomain() {
		$message = (object)array(
			'from' => 'unknown-sender@unknown.com',
			'to'   => 'unknown-recipient@home.com',
		);
		
		$location = $this->Router->route($message);
		
		$this->assertNotEquals('Domain', $location->routeRule);
	}
	
	/**
	 * Тест за рутиране на писмо от публичен домейн
	 */
	public function testRouteByPublicDomain() {
		$message = (object)array(
			'from' => 'unknown-sender@public-domain.com',
			'to'   => 'unknown-recipient@home.com',
		);
		
		$location = $this->Router->route($message);
		
		$this->assertNotEquals('Domain', $location->routeRule);
	}
	
	/**
	 * Тест за рутиране според държавата (в случай, че е налична)
	 */
	public function testRouteByKnownCountry() {
		$message = (object)array(
			'from' => 'unknown-sender@unknown.com',
			'to'   => 'unknown-recipient@home.com',
			'country' => 'known-country-id'
		);

		$location = $this->Router->route($message);
		
		$this->assertEquals('Country', $location->routeRule);
		$this->assertEquals('known-country-folder-id', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за рутиране при липсваща или невалдна информация за държавата
	 */
	public function testRouteByUnknownCountry() {
		// 1. Тест при наличен, но невалиден ид на държава
		$message = (object)array(
			'from' => 'unknown-sender@unknown.com',
			'to'   => 'unknown-recipient@home.com',
			'country' => 'non-existing-country'
		);
		
		$location = $this->Router->route($message);
		
		$this->assertNotEquals('Country', $location->routeRule);
		
		// 2. Тест при липсващ номер на държава
		unset($message->country);

		$location = $this->Router->route($message);
		$this->assertNotEquals('Country', $location->routeRule);
	}
	
	/**
	 * Тест за рутиране на нерутируеми писма - в папката на акаунта
	 */
	public function testRouteByAccount() {
		$message = (object)array(
			'accId' => 'non-bypass-account',
			'from' => 'unknown-sender@unknown.com',
			'to'   => 'unknown-recipient@home.com',
		);
		
		$location = $this->Router->route($message);
		
		$this->assertEquals('Account', $location->routeRule);
		$this->assertEquals('non-bypass-account-folder-id', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * Тест за fallback рутиране - когато нищо смислено не е помогнало (вероятно поради грешка)
	 */
	public function testRouteByUnsorted() {
		$message = (object)array();
		
		$location = $this->Router->route($message);
		
		$this->assertEquals('Unsorted', $location->routeRule);
		$this->assertEquals('orphan-folder-id', $location->folderId);
		$this->assertNull($location->threadId);
	}
	
	/**
	 * @return email_Router
	 */
	private function getRouter() {
		$mockedMethods = get_class_methods('email_Router_Mock');
		
		$Router = $this->getMock('email_Router', $mockedMethods);
		
		foreach ($mockedMethods as $m) {
			$Router->expects($this->any())
				->method($m)
				->will($this->returnCallback(array('email_Router_Mock', $m)));
				
		}
		
		$Router->description(); // малко хак, за да се дефинират константите
		
		return $Router;
	}
	
}

class email_Router_Mock
{
	static function extractThreadId($rec) {
		return empty($rec->hasThreadInfo) ? NULL : 'thread-id';
	}
	
	static function forceCountryFolder($countryId) {
		return $countryId == 'known-country-id' ? 'known-country-folder-id' : NULL;
	}

	static function isBypassAccount($accId) {
		return $accId == 'bypass-account';
	}
		
	static function forceAccountFolder($accId) {
		static $map = array(
			'bypass-account' => 'bypass-account-folder-id',
			'non-bypass-account' => 'non-bypass-account-folder-id',
		);
		
		return $map[$accId];
	}
		
	static function getRecipientFolder($email) {
		static $map = array(
			'generic@here.com' => 'generic-folderId',
			'inbox@here.com'   => 'inbox-folderId',
		);
		
		return $map[$email];
	}
		
	static function forceOrphanFolder() {
		return 'orphan-folder-id';
	}
		
	static function fetchRule($type, $key) {
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
	
	static function isGenericRecipient($recipient) {
		return $recipient === 'generic@here.com';
	}
	
	static function getCrmFolderId($email) {
		return ($email == 'crm-sender@example.com') ? 'crm-sender-folder-id' : NULL;
	}
	
	static function isPublicDomain($domain) {
		return ($domain == 'public-domain.com');
	}
}