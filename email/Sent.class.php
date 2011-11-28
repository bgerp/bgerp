<?php
/**
 * Мениджър на изпратените писма
 * 
 * @category   BGERP
 * @package    email
 * @author	   Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 * @see https://github.com/bgerp/bgerp/issues/108
 */
class email_Sent extends core_Manager
{   
    var $loadList = 'plg_Created,email_Wrapper';

    var $title    = "Изпратени писма";

    var $listFields = 'id, to, threadId, containerId, threadHnd, receivedOn, receivedIp, returnedOn';

    var $canRead   = 'admin,email';
    var $canWrite  = 'admin,email';
    var $canReject = 'admin,email';
    

    function description()
    {
        $this->FLD('boxFrom' , 'varchar', 'caption=Изпратен от');
        $this->FLD('emailTo' , 'varchar', 'caption=Изпратен до');
        $this->FLD('subject' , 'varchar', 'caption=Относно');
        $this->FLD('options' , 'varchar', 'caption=Опции');
        $this->FLD('threadId' , 'key(mvc=doc_Threads)', 'caption=Нишка');
        $this->FLD('containerId' , 'key(mvc=doc_Containers)', 'caption=Документ,oldFieldName=threadDocumentId');
        $this->FLD('receivedOn' , 'date', 'caption=Получено->На');
        $this->FLD('receivedIp' , 'varchar', 'caption=Получено->IP');
        $this->FLD('returnedOn' , 'date', 'caption=Върнато на');
        $this->FLD('mid' , 'varchar', 'caption=Ключ');
    }
    
    
    /**
     * Изпраща документ от документната система по електронната поща
     *
     * @param int $containerId key(mvc=doc_Container)
     * @param string $emailTo
     * @param string $subject
     * @param string $boxFrom
     * @param array $options масив с опции за изпращане:
     * 	- no_thread_hnd - не добавя идентификатор на треда от който е изпратено писмото в subject-а
     * 	- attach - добавя прикачените файлове към писмото. Иначе те са само линкнати в html и txt частта
	 *  - ascii - конвертира текстовата част до ascii символи
     * 
     */
    function send($containerId, $emailTo = NULL, $subject = NULL, $boxFrom = NULL, $options = array())
    {
    	$message = $this->prepareMessage($containerId, $emailTo, $subject, $boxFrom, $options);
    	
    	if ($isSuccess = $this->doSend($message)) {
    		$message->options = serialize($message->options);
    		
    		$isSuccess = $this->save(
    			$message
    		);
    	}
    	
    	return $isSuccess;
    }
    
    
    function prepareMessage($containerId, $emailTo = NULL, $subject = NULL, $boxFrom = NULL, $options = array())
    {
    	$options = arr::make($options, TRUE);
    	
    	$emailDocument = $this->getEmailDocument($containerId);
    	
    	$message = new stdClass();
    	
    	$message->emailTo = empty($emailTo) ? $emailDocument->getDefaultEmailTo() : $emailTo; 
    	$message->boxFrom = empty($boxFrom) ? $emailDocument->getDefaultBoxFrom() : $boxFrom; 
    	$message->subject = empty($subject) ? $emailDocument->getDefaultSubject($message->emailTo, $message->boxFrom) : $subject;
    	$message->text  = $emailDocument->getEmailText($message->emailTo, $message->boxFrom);
    	$message->html  = $emailDocument->getEmailHtml($message->emailTo, $message->boxFrom);
    	$message->attachments = empty($options['attach']) ? NULL : $emailDocument->getEmailAttachments();
    	$message->inReplyTo = $emailDocument->getInReplayTo();
    	
    	if (empty($options['no_thread_hnd'])) {
    		$handle = $this->getThreadHandle($containerId);
    		$message->headers['X-Bgerp-Thread'] = $handle;
    		$message->subject = static::decorateSubject($message->subject, $handle);
    	}
    	
    	$message->mid = static::generateMid();
    	
    	$message->html = str_replace('[#mid#]', $message->mid, $message->html);
    	$message->text = str_replace('[#mid#]', $message->mid, $message->text);
    	
    	$message->options = $options;

    	return $message;
    }
    
    
    static protected function decorateSubject($subject, $handle) {
    	return "<{$handle}> {$subject}";
    }

    
    static function generateMid() {
    	do {
    		$mid = str::getUniqId();
    	} while (static::fetch("#mid = '{$mid}'", 'id'));
    	
    	return $mid;
    }
    
    /**
     * Реално изпращане на писмо по електронна поща
     *
     * @param stdClass $message
     */
    function doSend($message)
    {
    	expect($message->emailTo);
    	expect($message->boxFrom);
    	expect($message->subject);
    	
    	/** @var $PML PHPMailer */
    	$PML = $this->getMailer();
    	
    	$PML->AddAddress($message->emailTo);
    	$PML->SetFrom($message->boxFrom);
    	$PML->Subject = $message->subject;
    	
    	if (!empty($message->html)) {
    		$PML->Body = $message->html;
    		$PML->IsHTML(TRUE);
    	}
    	
    	if (!empty($message->text)) {
    		if (empty($message->html)) {
    			$PML->Body = $message->text;
    			$PML->IsHTML(FALSE);
    		} else {
    			$PML->AltBody = $message->text;
    		}
    	}
    	
    	if (count($message->attachments)) {
    		foreach ($message->attachments as $attachment) {
    			/**
    			 * @TODO: Определяне на $path, $name
    			 * 
    			 * $attachment => FH (file handle)
    			 * 
    			 * fileman_Files::fetchByFh() => $rec(path, name);
    			 */
    			//$PML->AddAttachment($path, $name);
    		}
    	}
    	
    	if (count($message->headers)) {
    		foreach ($message->headers as $name=>$value) {
    			$PML->HeaderLine($name, $value);
    		}
    	}
    	
    	if (!empty($message->inReplyTo)) {
    		$PML->AddReplyTo($message->inReplyTo);
    	}
    	
    	var_dump($PML);
    	exit;
    	
    	return $PML->Send();
    }
    
    /**
     * @return  PHPMailer
     */
    function getMailer()
    {
    	return cls::get('phpmailer_Instance');
    }
    
    
    /**
     * @param int $containerId
     * @return email_DocumentIntf
     */
    function getEmailDocument($containerId)
    {
    	return doc_Containers::getDocument($containerId, 'email_DocumentIntf');
    }
    
    function getThreadHandle($containerId)
    {
    	return doc_Threads::getHandle($containerId);
    }
    
}