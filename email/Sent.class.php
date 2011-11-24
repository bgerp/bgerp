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
        $this->FLD('to' , 'varchar', 'caption=Изпратен до');
        $this->FLD('threadId' , 'key(mvc=doc_Threads)', 'caption=Нишка');
        $this->FLD('containerId' , 'key(mvc=doc_Containers)', 'caption=Документ,oldFieldName=threadDocumentId');
        $this->FLD('threadHnd' , 'varchar', 'caption=Манипулатор');
        $this->FLD('receivedOn' , 'date', 'caption=Получено->На');
        $this->FLD('receivedIp' , 'varchar', 'caption=Получено->IP');
        $this->FLD('returnedOn' , 'date', 'caption=Върнато на');
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
    	$options = arr::make($options, TRUE);
    	
    	/* @var $emailDocument email_DocumentIntf */
    	$emailDocument = doc_Containers::getDocument($containerId, 'email_DocumentIntf');
    	
    	if (!isset($emailTo)) {
    		$emailTo = $emailDocument->getDefaultEmailTo();
    	}
    	if (!isset($boxFrom)) {
    		$boxFrom = $emailDocument->getDefaultBoxFrom();
    	}
    	if (!isset($subject)) {
    		$subject = $emailDocument->getDefaultSubject($emailTo, $boxFrom);
    	}
    	
    	if (empty($options['no_thread_hnd'])) {
    		$subject = $this->decorateSubject($subject, $containerId);
    	}
    	
    	if (!empty($options['attach'])) $attachments = $emlDoc->getAttachments();
    	$this->doSend($message);
    	
    }
    
    protected function doSend()
    {
    	/* var $PML PHPMailerLite */
    	$PML = cls::get('phpmailer_Instance');
    	
    }
    
}