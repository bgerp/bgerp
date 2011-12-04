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

    // КОМЕНТАР МГ: Никой не трябва да може да добавя или редактира записи.
    // Всичко потребители трябва да могат да изпращат '$canSend' писма
    var $canWrite  = 'admin,email';
    var $canReject = 'admin,email';
    

    function description()
    {
        $this->FLD('boxFrom', 'varchar', 'caption=От,mandatory');

        // КОМЕНТАР МГ: Полето boxFrom би следвало да е key(mvc=email_Inboxes)
        // Полето emailTo би следвало да е тип 'email'

        $this->FLD('emailTo', 'varchar', 'caption=До,mandatory');
        $this->FLD('subject', 'varchar', 'caption=Относно');
        $this->FLD('options', 'set(no_thread_hnd, attach, ascii)', 'caption=Опции');
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'input=none,caption=Нишка');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'input=hidden,caption=Документ,oldFieldName=threadDocumentId,silent,mandatory');
        $this->FLD('receivedOn', 'date', 'input=none,caption=Получено->На');
        $this->FLD('receivedIp', 'varchar', 'input=none,caption=Получено->IP');
        $this->FLD('returnedOn', 'date', 'input=none,caption=Върнато на');
        $this->FLD('mid', 'varchar', 'input=none,caption=Ключ');
    }
    
    
    /**
     * КОМЕНТАР МГ: Не е правилния начин да се използва дефолт екшъна за добавяне/редактиране за целите на изпращане на писмо
     * Изпращането на писмо трябва да има собствен екшън act_Sent, който да се погрижи за:
     * Въвеждане на входните данни, проверка за правата (дали въобще потребителя има достъп до контейнера?) показването на писмото +
     * формата за изпращане, и самото изпращане на писмото + логването. Освен това трябва да работи в друг wrapper (празен, като за поечат)
     * 
     * КОМЕНТАР МГ: Функцията getDefaultBoxFrom() не трябва задължително да връща стойност. Ако не е посочена default сметка,
     * трябва да се използва тази от конфигурацията.
     */
	function on_AfterInputEditForm($mvc, $form)
	{
		$rec = $form->rec;

		expect($containerId = $rec->containerId);
		
		if (!$form->isSubmitted()) {
			$emailDocument = $this->getEmailDocument($containerId);
			$rec->boxFrom = $emailDocument->getDefaultBoxFrom();
			$rec->emailTo = $emailDocument->getDefaultEmailTo();
			$rec->subject = $emailDocument->getDefaultSubject($rec->emailTo, $rec->boxFrom);
			
			return;
		}
		
		if ($this->send($rec->containerId, $rec->emailTo, $rec->subject, $rec->boxFrom, $rec->options)) {
			redirect(getRetUrl());
		}
		
		$form->setError('Проблем с изпращане на писмото');
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
	    	$message->options = serialize($options);
	    	$message->containerId = $containerId;
	    	$message->threadId = doc_Containers::fetchField($containerId, 'threadId');
    	
    		$isSuccess = static::save(
    			$message
    		);
    	}
    	
        return $isSuccess;
    }
    
    
    /**
     * Пребразуване на документ до електронно писмо
     *
     * @param int $containerId key(mvc=doc_Containers)
     * @param string $emailTo 
     * @param string $subject
     * @param string $boxFrom
     * @param array $options @see email_Sent::send()
     * @return stdClass обект с попълни полета според очакванията на @link email_Sent::doSend()
     */
    function prepareMessage($containerId, $emailTo = NULL, $subject = NULL, $boxFrom = NULL, $options = array())
    {
    	$options = arr::make($options, TRUE);
    	
    	$emailDocument = $this->getEmailDocument($containerId);
    	
    	$message = new stdClass();
    	
    	// Генериране на уникален иденфикатор на писмото
    	$message->mid = static::generateMid();
    	
    	$message->emailTo = empty($emailTo) ? $emailDocument->getDefaultEmailTo() : $emailTo; 
    	$message->boxFrom = empty($boxFrom) ? $emailDocument->getDefaultBoxFrom() : $boxFrom; 
    	$message->subject = empty($subject) ? $emailDocument->getDefaultSubject($message->emailTo, $message->boxFrom) : $subject;
    	$message->text  = $emailDocument->getEmailText($message->emailTo, $message->boxFrom);
    	$message->html  = $emailDocument->getEmailHtml($message->emailTo, $message->boxFrom);
    	$message->attachments = empty($options['attach']) ? NULL : $emailDocument->getEmailAttachments();
    	$message->inReplyTo = $emailDocument->getInReplayTo();
    	
    	$myDomain = MAIL_DOMAIN;
    	
    	$message->headers = array(
    		'Return-Path'                 => "returned.{$message->mid}@{$myDomain}", 
    		'X-Confirm-Reading-To'        => "received.{$message->mid}@{$myDomain}", 
    		'Disposition-Notification-To' => "received.{$message->mid}@{$myDomain}", 
    		'Return-Receipt-To'           => "received.{$message->mid}@{$myDomain}", 
    		'Message-Id'                  => "{$message->mid}",
    	);
    	
    	if (empty($options['no_thread_hnd'])) {
    		$handle = $this->getThreadHandle($containerId);
    		$message->headers['X-Bgerp-Thread'] = "{$handle}; origin={$myDomain}";
    		$message->subject = static::decorateSubject($message->subject, $handle);
    	}
    	
    	$message->html = str_replace('[#mid#]', $message->mid, $message->html);
    	$message->text = str_replace('[#mid#]', $message->mid, $message->text);
    	
    	return $message;
    }
    
    
    /**
     * Добавяне на манипулатор на тред в субджекта на писмо
     *
     * @param string $subject
     * @param string $handle
     * @return string
     * 
     * КОМЕНТАР МГ: Има опсаност <$handle>, вече да ги има в Subjecta. Не трябва да се дублира.
     * 
     */
    static protected function decorateSubject($subject, $handle)
    {
    	return "<{$handle}> {$subject}";
    }

    
    /**
     * Гериране на случаен уникален идентификатор на писмо
     * 
     * @return string
     *
     */
    static function generateMid()
    {
    	do {
    		$mid = str::getRand('Aaaaaaaa');
    	} while (static::fetch("#mid = '{$mid}'", 'id'));
    	
    	return $mid;
    }
    
    
    /**
     * Реално изпращане на писмо по електронна поща
     *
     * @param stdClass $message
     * @return bool
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
    	
        // Добавяме атачмънтите, ако има такива
    	if (count($message->attachments)) {
            foreach ($message->attachments as $fh) {
     	        $fRec = fileman_Files::fetchByFh($fh);
    	        $PML->AddAttachment($rec->path, $rec->name);
    		}
    	}
    	
        // Ако има някакви хедъри, добавяме ги
    	if (count($message->headers)) {
    		foreach ($message->headers as $name => $value) {
    			$PML->HeaderLine($name, $value);
    		}
    	}
    	
    	if (!empty($message->inReplyTo)) {
    		$PML->AddReplyTo($message->inReplyTo);
    	}
    	
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
    
    
    /**
     *
     */
    function getThreadHandle($containerId)
    {
    	$threadId = doc_Containers::fetchField($containerId, 'threadId');
    	
    	return doc_Threads::getHandle($threadId);
    }
}