<?php
class email_UserInboxPlgTest extends framework_TestCase
{
	protected function setUp()
	{
	    parent::setUp();
	    
	    core_Users::delete('1=1');
	    doc_Folders::delete('#id > 1');
	    email_Inboxes::delete('#id > 1');
	}
	
	public function testCreateUser($userData = NULL)
	{
	    if (!isset($userData)) {
	        $userData = array(
	            'nick' => 'tester',
	            'password' => 'password',
	        ); 
	    }
	    
	    $userId = core_Users::save((object)$userData);
	    
	    $this->assertTrue(is_numeric($userId));
	    
	    $userEmail = email_Inboxes::getUserEmail($userId);
	    
	    $this->assertEquals($userData['nick'] . '@' . BGERP_DEFAULT_EMAIL_DOMAIN, $userEmail);
	    
	    $inboxRec = email_Inboxes::fetch("#email = '{$userEmail}'");
	    
	    $expected = array(
	        'email' => $userData['nick'] . '@' . BGERP_DEFAULT_EMAIL_DOMAIN,
	        'type'  => 'internal',
	        'state' => 'active',
	        'applyRouting' => 'no',
	        'inCharge' => $userId,
	        'access' => 'private',
	        'shared' => NULL
        );

	    $this->assertArrayMatch($expected, $inboxRec);
	    
	    return $userId;
	}
	
	/**
	 * Създава нов потребител, изтрива го и го създава пак със същия nick
	 * 
	 */
	public function testReCreateNick()
	{
	    $userId = $this->testCreateUser();
	    
	    core_Users::delete("#id = {$userId}");
	    
	    try {
	        $this->testCreateUser();
	    } catch (core_exception_Expect $e) {
	        $this->assertEquals('Моля въведете друг Ник. Папката е заета от друг потребител.', $e->args(1));
	        return;
	    }
	    
	    $this->fail('Очакваше се прекъсване.');
	}
}