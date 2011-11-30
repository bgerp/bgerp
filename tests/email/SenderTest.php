<?php
class email_SenderTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var email_Sent
	 */
	protected $Sender;
	
	protected $baseMessage = array(
		'boxFrom' => 'sender@example.com', 
		'emailTo' => 'recipient@example.com', 
		'subject' => 'Test subject',
	);
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		
		$this->Sender = $this->getSender();
	}
	
	function testPrepareMessage()
	{
		$message = $this->Sender->prepareMessage(1);
		
		$this->assertEquals('sender@example.com', $message->boxFrom);
		$this->assertEquals('recipient@example.com', $message->emailTo);
		$this->assertEquals('<handle1> Test subject', $message->subject);
		$this->assertEquals('Html', $message->html);
		$this->assertEquals('Text', $message->text);
		$this->assertArrayHasKey('X-Bgerp-Thread',$message->headers);
		$this->assertEquals('handle1', $message->headers['X-Bgerp-Thread']);
		$this->assertNull($message->attachments);
		$this->assertEquals('replyto@example.com', $message->inReplyTo);
		
		return $message;
	}
	
	function testPrepareMessageTo()
	{
		$message = $this->Sender->prepareMessage(1, 'rcpt@example.com');
		
		$this->assertEquals('rcpt@example.com', $message->emailTo);
	}
	
	function testPrepareMessageSubject()
	{
		$message = $this->Sender->prepareMessage(1, NULL, 'Another subject');
		
		$this->assertEquals('<handle1> Another subject', $message->subject);
	}
	
	function testPrepareMessageFrom()
	{
		$message = $this->Sender->prepareMessage(1, NULL, NULL, 'sndr@example.com');
		
		$this->assertEquals('sndr@example.com', $message->boxFrom);
	}
	
	function testPrepareMessageAttach()
	{
		$message = $this->Sender->prepareMessage(1, NULL, NULL, NULL, array('attach'));
		
		$this->assertEquals(array('attachments'), $message->attachments);
	}
	
	function testPrepareMessageNoHandle()
	{
		$message = $this->Sender->prepareMessage(1, NULL, NULL, NULL, array('no_thread_hnd'));
		
		$this->assertEquals('Test subject', $message->subject);
	}
	
	/**
	 * 
	 * Enter description here ...
	 *
	 * @param stdClass $message
	 * @depends testPrepareMessage
	 */
	function testDoSend($message)
	{
		$mailer = $this->Sender->getMailer();
		
		$mailer->expects($this->once())
			->method('AddAddress')
			->with($this->equalTo($message->emailTo));
		$mailer->expects($this->once())
			->method('SetFrom')
			->with($this->equalTo($message->boxFrom));
		$mailer->expects($this->once())
			->method('IsHTML')
			->with($this->equalTo(!empty($message->html)));
		$mailer->expects($this->once())
			->method('Send');
			
		$this->Sender->doSend($message);
		
		$this->assertNotEmpty($mailer->Body);
	}
	
	function testDoSendMultipart() {
		$mailer = $this->Sender->getMailer();
		$message = (object)(array(
				'text' => 'Text part',
				'html' => 'Html part',
			) + $this->baseMessage);
		
		$mailer->expects($this->once())
			->method('IsHTML')
			->with($this->equalTo(TRUE));
			
		$this->Sender->doSend($message);
		
		$this->assertEquals($message->html, $mailer->Body);
		$this->assertEquals($message->text, $mailer->AltBody);
	}
	
	function testDoSendHtml() {
		$mailer = $this->Sender->getMailer();
		$message = (object)(array(
				'html' => 'Html part',
			) + $this->baseMessage);
		
		$mailer->expects($this->once())
			->method('IsHTML')
			->with($this->equalTo(TRUE));
			
		$this->Sender->doSend($message);
		
		$this->assertEquals($message->html, $mailer->Body);
		$this->assertEmpty($mailer->AltBody);
	}
	
	function testDoSendText() {
		$mailer = $this->Sender->getMailer();
		$message = (object)(array(
				'text' => 'Text part',
			) + $this->baseMessage);
		
		$mailer->expects($this->once())
			->method('IsHTML')
			->with($this->equalTo(FALSE));
			
		$this->Sender->doSend($message);
		
		$this->assertEquals($message->text, $mailer->Body);
		$this->assertEmpty($mailer->AltBody);
	}
	
	
//	function testDoSendReal() {
//		email_Sent_Mock::$mailer = cls::get('phpmailer_Instance');
//		$this->Sender->send(2);
//	}
	
	/**
	 * @return email_Sent
	 */
	private function getSender()
	{
		$mockedMethods = get_class_methods('email_Sent_Mock');
		
		$Sender = $this->getMock('email_Sent', $mockedMethods);
		
		foreach ($mockedMethods as $m) {
			$Sender->expects($this->any())
				->method($m)
				->will($this->returnCallback(array('email_Sent_Mock', $m)));
		}
		
		cls::load('phpmailer_Instance');
		
		email_Sent_Mock::$mailer = PHPUnit_Framework_MockObject_Generator::getMock('PHPMailer');
		
		return $Sender;
	}
}

class email_Sent_Mock
{
	static $mailer;
	
    /**
     * @return  PHPMailerLite
     */
    static function getMailer() 
    {
    	return static::$mailer;
    }
    
    
    /**
     * @param int $containerId
     * @return email_DocumentIntf
     */
    static function getEmailDocument($containerId)
    {
    	return new email_Document_Mock($containerId);
    }
    
    static function getThreadHandle($containerId) {
    	static $map = array(
    		1 => 'handle1',
    		2 => 'handle2',
    	);
    	
    	return $map[$containerId];
    }
}


class email_Document_Mock
{
	static $defaults = array(
		'getDefaultEmailTo' => 'recipient@example.com',
		'getDefaultBoxFrom' => 'sender@example.com',
		'getEmailText' => 'Text',
		'getEmailHtml' => 'Html',
		'getDefaultSubject' => 'Test subject',
		'getEmailAttachments' => array('attachments'),
		'getInReplayTo' => 'replyto@example.com',
	);

	static $map = array(
		1 => array( // за този containerId ...
			array(
				array(), // ... при такива аргументи
				array()  // ... върни този резултат ( + self::$defaults )
			)
		),
		2 => array( // за този containerId ...
			array(
				array(), // ... при такива аргументи
				array(
					'getDefaultEmailTo' => 'bgerptest@gmail.com',
					'getDefaultBoxFrom' => 'team@bgerp.com',
					'getEmailAttachments' => NULL,
					'getInReplayTo' => 'stefan@bgerp.com',
				)  // ... върни този резултат ( + self::$defaults )
			)
		),
	);
	
	var $id;
	
	function __construct($id) {
		$this->id = $id;
	}
	
	function __call($method, $args) {
		$map = static::$map[$this->id];

		$results = array();
		
		foreach ($map as $m) {
			if ($m[0] == $args) {
				$results = isset($m[1]) ? $m[1] : array();
				break;
			}
		}
		
		$results += static::$defaults;
		
		if (isset($results[$method])) {
			return $results[$method];
		}
	}
}