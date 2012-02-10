<?php


/**
 * Мениджър на изпратените писма
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       https://github.com/bgerp/bgerp/issues/108
 */
class email_Sent extends core_Manager
{
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,email_Wrapper';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title    = "Изпратени имейли";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, to, threadId, containerId, threadHnd, receivedOn, receivedIp, returnedOn';
    
    
    /**
     * Кой има право да го прочете?
     */
    var $canRead   = 'admin,email';
    
    
    /**
     * КОМЕНТАР МГ: Никой не трябва да може да добавя или редактира записи.
     *
     * Всичко потребители трябва да могат да изпращат '$canSend' писма
     */
    var $canWrite  = 'no_one';
    
    /**
     * Кой има право да го отхвърли?
     */
    var $canReject = 'no_one';
    
    
    /**
     * Кой има право да изпраща?
     */
    var $canSend = 'admin,email';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('boxFrom', 'key(mvc=email_Inboxes, select=email)', 'caption=От,mandatory');
        $this->FLD('emailTo', 'varchar', 'caption=До,mandatory');
        $this->FLD('subject', 'varchar', 'caption=Относно');
        $this->FLD('options', 'set(no_thread_hnd, attach=Прикачи файловете, ascii=Конвертиране до ASCII)', 'caption=Опции');
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'input=none,caption=Нишка');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'input=hidden,caption=Документ,oldFieldName=threadDocumentId,silent,mandatory');
        $this->FLD('mid', 'varchar', 'input=none,caption=Ключ');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Send()
    {
        $data = new stdClass();
        
        // Създаване и подготвяне на формата
        $this->prepareEditForm($data);
        
        // Подготвяме адреса за връщане, ако потребителя не е логнат.
        // Ресурса, който ще се зареди след логване обикновено е страницата, 
        // от която се извиква екшъна act_Manage
        $retUrl = getRetUrl();
        
        // Определяме, какво действие се опитваме да направим
        $data->cmd = 'Send';
        
        // Очакваме до този момент във формата да няма грешки
        expect(!$data->form->gotErrors(), 'Има грешки в silent полетата на формата', $data->form->errors);
        
        // Дали имаме права за това действие към този запис?
        $this->requireRightFor($data->cmd, $data->form->rec, NULL, $retUrl);
        
        // Зареждаме формата
        $data->form->input();
        
        $rec = &$data->form->rec;
        
        // Генерираме събитие в mvc, след въвеждането на формата, ако е именована
        $this->invoke('AfterInputEditForm', array($data->form));
        
        // Дали имаме права за това действие към този запис?
        $this->requireRightFor($data->cmd, $rec, NULL, $retUrl);
        
        // Ако формата е успешно изпратена - запис, лог, редирект
        if ($data->form->isSubmitted()) {
            
            $tpl = '<div style="padding: 1em;">';
            bp($rec);
            if ($id = $this->send($rec->containerId, $rec->emailTo, $rec->subject, $rec->boxFrom, $rec->options)) {
                $tpl .= "Успешно изпращане до {$rec->emailTo}";
            } else {
                $tpl .= "Проблем при изпращане до {$rec->emailTo}";
            }
            
            $tpl .= ''
            . '<div style="margin-top: 1em;">'
            .    '<input type="button" value="Затваряне" onclick="window.close();" />'
            . '</div>';
            
            $tpl .= '</div>';
        } else {
            // Подготвяме адреса, към който трябва да редиректнем,  
            // при успешно записване на данните от формата
            $this->prepareRetUrl($data);
            
            // Подготвяме тулбара на формата
            $this->prepareEditToolbar($data);
            
            // Получаваме изгледа на формата
            $tpl = $data->form->renderHtml();
            
            $emailDoc = doc_Containers::getDocument($rec->containerId, 'email_DocumentIntf');
            
            if (Mode::is('text', 'plain')) {
                $tpl = $tpl . '<pre style="padding: 1em; background-color: #fff; margin: 0.5em; border: 1px solid #ccc;">' . htmlspecialchars($rec->document) . '</pre>';
            } else {
                $tpl .= $rec->document;
            }
        }
        
        Mode::set('wrapper', 'tpl_BlankPage');
        
        return $tpl;
    }
    
    
    /**
     * Подготвя стойности по подразбиране на формата за изпращане на писмо.
     *
     * Използва интерфейса email_DocumentIntf за да попълни стойностите
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        $rec  = $form->rec;
        
        $form->setAction(array($mvc, 'send'));
        $form->title = 'Изпращане по е-майл';
        
        $optionsType = $form->getField('options')->type;
        unset($optionsType->params['no_thread_hnd']);
        
        expect($containerId = $rec->containerId);
        
        $emailDocument = $this->getEmailDocument($containerId);
        
        $rec->boxFrom = $emailDocument->getDefaultBoxFrom();
        
        if (empty($rec->boxFrom)) {
            // Задаваме по подразбиране inbox-а на текущия потребител.
            $rec->boxFrom = $mvc->getCurrentUserInbox();
        }
        $rec->emailTo  = $emailDocument->getDefaultEmailTo();
        $rec->subject  = $emailDocument->getDefaultSubject($rec->emailTo, $rec->boxFrom);
        $rec->document = $emailDocument->getEmailHtml($rec->emailTo, $rec->boxFrom);
    }
    
    
    /**
     * Изпраща документ от документната система по електронната поща
     *
     * @param int $containerId key(mvc=doc_Container)
     * @param string $emailTo
     * @param string $subject
     * @param string $boxFrom
     * @param array $options масив с опции за изпращане:
     * - no_thread_hnd - не добавя идентификатор на треда от който е изпратено писмото в subject-а
     * - attach - добавя прикачените файлове към писмото. Иначе те са само линкнати в html и txt частта
     * - ascii - конвертира текстовата част до ascii символи
     */
    function send($containerId, $emailTo = NULL, $subject = NULL, $boxFrom = NULL, $options = array())
    {
        $message = $this->prepareMessage($containerId, $emailTo, $subject, $boxFrom, $options);
        
        if ($isSuccess = $this->doSend($message)) {
            $message->options     = $options;
            $message->containerId = $containerId;
            
            email_Log::sent($message);
            
            // Генериране на `From` правило за рутиране
            email_Router::saveRule(
                (object)array(
                    'type'       => email_Router::RuleFrom,
                    'key'        => email_Router::getRoutingKey($emailTo, NULL, email_Router::RuleFrom),
                    'priority'   => email_Router::dateToPriority(dt::now(TRUE), 'mid', 'asc'), // със среден приоритет, нарастващ с времето
                    'objectType' => 'document',
                    'objectId'   => $message->containerId
                )
            );
            
            if ($key = email_Router::getRoutingKey($emailTo, NULL, email_Router::RuleDomain)) {
                // Има ключ за `Domain` правило, значи трябва да се генерира и самото правило,
                // но само при условие, че папката, в която е изпратеното писмо е фирмена папка
                
                if ($folderId = doc_Containers::fetchField($message->containerId, 'folderId')) {
                    $coverClass = doc_Folders::fetchField($folderId);
                }
                
                if ($coverClass) {
                    $isCompanyFolder = (cls::getClassName($coverClass) === 'crm_Companies');
                }
                
                if ($isCompanyFolder) {
                    // Да, писмото се изпраща от фирмена папка - генерираме domain правило
                    email_Router::saveRule(
                        (object)array(
                            'type' => email_Router::RuleDomain,
                            'key' => $key,
                            'priority' => email_Router::dateToPriority(dt::now(TRUE),
                                'mid',
                                'asc'),  // със среден приоритет, нарастващ с времето
                            'objectType' => 'document',
                            'objectId' => $message->containerId));
                }
            }
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
        
        $message->boxFrom = email_Inboxes::fetchField($message->boxFrom, 'email');
        
        $myDomain = BGERP_DEFAULT_EMAIL_DOMAIN;
        
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
     */
    static function generateMid()
    {
        do {
            $mid = str::getRand('Aaaaaaaa');
        } while (static::fetch("#mid = '{$mid}'", 'id'));
        
        return $mid;
    }
    
    
    /**
     * Намира @link email_Inboxes на текущия потребител
     *
     * @return int key(mvc=email_Inboxes)
     * @access private
     */
    function getCurrentUserInbox()
    {
        return email_Inboxes::getCurrentUserInbox();
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
                //Ако няма fileHandler да не го добавя
                if (!$fh) continue;
                
                $name = fileman_Files::fetchByFh($fh, 'name');
                $path = fileman_Files::fetchByFh($fh, 'path');
                $PML->AddAttachment($path, $name);
            }
        }
        
        // Задаване хедър "Return-Path"
        if (isset($message->headers['Return-Path'])) {
            $PML->Sender = $message->headers['Return-Path'];
            unset($message->headers['Return-Path']);
        }
        
        // Ако има още някакви хедъри, добавяме ги
        if (count($message->headers)) {
            foreach ($message->headers as $name => $value) {
                $PML->AddCustomHeader("{$name}:{$value}");
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
     * @todo Чака за документация...
     */
    function getThreadHandle($containerId)
    {
        $threadId = doc_Containers::fetchField($containerId, 'threadId');
        
        return doc_Threads::getHandle($threadId);
    }
}
