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
        $this->FLD('emailTo', 'emails', 'caption=До,mandatory, width=785px');
        $this->FLD('encoding', 'enum(utf-8=Уникод|* (UTF-8),
                                    cp1251=Win Cyrillic|* (CP1251),
                                    koi8-r=Rus Cyrillic|* (KOI8-R),
                                    cp2152=Western|* (CP1252),
                                    asscii=Латиница|* (ASCII))', 'caption=Знаци');
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'input=hidden,mandatory,caption=Нишка');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'input=hidden,caption=Документ,oldFieldName=threadDocumentId,mandatory');
        $this->FLD('attachments', 'set()', 'caption=Прикачи,columns=4');
        $this->FLD('mid', 'varchar', 'input=none,caption=Ключ');

        // дата на получаване на писмото (NULL ако няма информация дали е получено)
        $this->FLD('receivedOn', 'datetime(format=smartTime)', 'input=none,caption=Получено->На');
        
        // IP от което е получено писмото (NULL ако няма информация от къде е получено)
        $this->FLD('receivedIp', 'ip', 'input=none,caption=Получено->IP');
        
        // дата на връщане на писмото (в случай, че не е получено)
        $this->FLD('returnedOn', 'datetime(format=smartTime)', 'input=none,caption=Върнато на');
    }

    
    /**
     * Изпраща имейл
     *
     * @param stdClass $outRec Запис на модела @link email_Outgoings, с добавени полетата
     * 
     *  o text string текстова част (незадължителна, но поне една от text или html частите е задължителна)
     *  o html string HTML част (незадължителна, но поне една от text или html частите е задължителна)
     *  
     * @param stdClass $sentRec Запис на модела @link email_Sent 
     * 	[no_thread_hnd] - не вкарва в писмото информация за нишката му
     * 	
     */
    static function send_($outRec, $sentRec)
    {
        $message = static::prepareMessage($outRec, $sentRec);
        
        if ($isSuccess = static::doSend($message)) {

            /**
             * TODO: Да прехвърля този блок в on_AfterSend()
             */
            
            /*
             * Запис в историята на изпращанията (email_Sent)
             */ 
            $sentRec->id = NULL;
            static::save($sentRec);
            
            /*
             * Създаване на нови правила за рутиране
             */
            
            // Генериране на `From` правило за рутиране
            email_Router::saveRule(
                (object)array(
                    'type'       => email_Router::RuleFrom,
                    'key'        => email_Router::getRoutingKey($message->emailTo, NULL, email_Router::RuleFrom),
                    'priority'   => email_Router::dateToPriority(dt::now(TRUE), 'mid', 'asc'), // със среден приоритет, нарастващ с времето
                    'objectType' => 'document',
                    'objectId'   => $message->containerId
                )
            );
            
            if ($key = email_Router::getRoutingKey($message->emailTo, NULL, email_Router::RuleDomain)) {
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
     * Подготвя за изпращане по имейл
     *
     * @param stdClass $outRec @see email_Sent::send()
     * @param stdClass $sentRec @see email_Sent::send()
     * @return stdClass обект с попълнени полета според очакванията на @link email_Sent::doSend()
     */
    static function prepareMessage($outRec, $sentRec)
    {
        $message = $outRec;
   
        // Генериране на уникален иденфикатор на писмото
        $sentRec->mid = static::generateMid();
        
        // Извличане на имейл адреса на кутията, от която изпращаме
        $message->emailFrom = email_Inboxes::fetchField($sentRec->boxFrom, 'email');
        
        $message->emailTo   = $sentRec->emailTo;
        
        $myDomain = BGERP_DEFAULT_EMAIL_DOMAIN;
        
        // Подготовка на MIME-хедъри
        list($senderName,) = explode('@', $message->emailFrom, 2);
        $message->headers = array(
            'Return-Path'                 => "{$senderName}+returned={$sentRec->mid}@{$myDomain}",
            'X-Confirm-Reading-To'        => "{$senderName}+received={$sentRec->mid}@{$myDomain}",
            'Disposition-Notification-To' => "{$senderName}+received={$sentRec->mid}@{$myDomain}",
            'Return-Receipt-To'           => "{$senderName}+received={$sentRec->mid}@{$myDomain}",
        );

        $message->messageId = "<{$sentRec->mid}@{$myDomain}.mid>";
       
        if (empty($sentRec->no_thread_hnd)) {
            $handle = static::getThreadHandle($message->containerId);
            $message->headers['X-Bgerp-Thread'] = "{$handle}; origin={$myDomain}";
            $message->subject = static::decorateSubject($message->subject, $handle);
        }
        
        // Заместване на уникалния идентификатор на писмото с генерираната тук стойност
        $message->html = str_replace('[#mid#]', $sentRec->mid, $message->html);
        $message->text = str_replace('[#mid#]', $sentRec->mid, $message->text);
        
        // Добавяне на прикачените файлове
        $message->attachments = arr::make($sentRec->attachments);
         
        // Конвертиране на $message->text и $message->html в енкодинга, зададен с
        // $sentRec->encoding 
        if($sentRec->encoding == 'ascii') {
            $message->html    = str::utf2ascii($message->html);
            $message->text    = str::utf2ascii($message->text);
            $message->subject = str::utf2ascii($message->subject);
        } elseif($sentRec->encoding != 'utf-8') {
            $message->html    = iconv('UTF-8', $sentRec->encoding . '//IGNORE', $message->html);
            $message->text    = iconv('UTF-8', $sentRec->encoding . '//IGNORE', $message->text);
            $message->subject = iconv('UTF-8', $sentRec->encoding . '//IGNORE', $message->subject);
        } 

        $message->charset = $sentRec->encoding;

        return $message;
    }
    
    /**
     * Добавяне на манипулатор на тред в събджекта на писмо.
     * 
     * Манипулатора не се добавя ако вече присъства в събджекта. 
     *
     * @param string $subject
     * @param string $handle
     * @return string
     *
     */
    static protected function decorateSubject($subject, $handle)
    {
        // Добавяме манипулатора само ако го няма
        if (!in_array($handle, email_Incomings::extractSubjectThreadHnds($subject))) {
            $subject = "<{$handle}> {$subject}";
        }
        
        return $subject;
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
     * Реално изпращане на писмо по електронна поща
     *
     * @param stdClass $message
     * @return bool
     */
    function doSend($message)
    {
        expect($message->emailTo);
        expect($message->emailFrom);
        expect($message->subject);
        expect($message->html || $message->text);
        
        /** @var $PML PHPMailer */
        $PML = static::getMailer();
        
        $PML->AddAddress($message->emailTo);
        $PML->SetFrom($message->emailFrom);
        $PML->Subject   = $message->subject;
        $PML->CharSet   = $message->charset;
        $PML->MessageID = $message->messageId;
        $PML->ClearReplyTos();

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
    static function getMailer()
    {
        return cls::get('phpmailer_Instance');
    }
        
    
    /**
     * @todo Чака за документация...
     */
    static function getThreadHandle($containerId)
    {
        $threadId = doc_Containers::fetchField($containerId, 'threadId');
        
        return doc_Threads::getHandle($threadId);
    }
    
    
    /**
     * Отразява в историята факта, че (по-рано изпратено от нас) писмо е видяно от получателя си
     *
     * @param string $mid Уникален ключ на писмото, за което е получена обратна разписка
     * @param string $date Дата на изпращане на обратната разписка (NULL - днешна дата)
     * @param string $ip IP адрес, от който е изпратена разписката
     * @return boolean TRUE - обратната разписка е обработена нормално и FALSE противен случай
     */
    public static function received($mid, $date = NULL, $ip = NULL)
    {
        if ( !($rec = static::fetch("#mid = '{$mid}'")) ) {
            // Няма следа от оригиналното писмо - игнорираме обратната разписката
            return FALSE;
        }
        

        if (!empty($rec->receivedOn) && $rec->ip == $ip) {
            // Получаването на писмото (от това IP) вече е било отразено в историята; не правим 
            // нищо, но връщаме TRUE - сигнал, че разписката е обработена нормално.
            return TRUE;
        }
                
        if (!isset($date)) {
            $date = dt::now();
        }
        
        $rec->receivedOn = $date;
        $rec->receivedIp = $ip;
        
        return static::save($rec);
    } 
    
    
    /**
     * Отрязава в историята факта че (по-рано изпратено от нас) писмо не е доставено до получателя си
     *
     * @param string $mid Уникален ключ на писмото, което не е доставено
     * @param string $date дата на върнатото писмо
     * @return boolean TRUE намерено е писмото-оригинал и събитието е отразено; 
     */
    public static function returned($mid, $date = NULL)
    {
        if ( !($rec = static::fetch("#mid = '{$mid}'")) ) {
            // Няма следа от оригиналното писмо. 
            return FALSE;
        }

        if (!empty($rec->returnedOn)) {
            // Връщането на писмото вече е било отразено в историята; не правим нищо
            return TRUE;
        }
        
        if (!isset($date)) {
            $date = dt::now();
        }
        
        $rec->returnedOn = $date;
        
        return static::save($rec);
    }
    

}
