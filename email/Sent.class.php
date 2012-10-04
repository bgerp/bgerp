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
 * @since     v 0.11
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
    var $listFields = 'createdOn=Изпратено->на, createdBy=Изпратено->от, containerId, boxFrom, emailTo, receivedOn, receivedIp, returnedOn, documents=Прикачени->Документи, attachments=Прикачени->Файлове';
    
    
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
     * Домейн на записите в кеша
     *
     * @see core_Cache
     */
    const CACHE_TYPE = 'sentHistory';
    
    /**
     * Масив с всички разширения и съответните им mime типове
     */
    static $mimes = NULL;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('boxFrom', 'key(mvc=email_Inboxes, select=email)', 'caption=От адрес,mandatory');
        $this->FLD('emailTo', 'email', 'caption=До,input=none');
        $this->FLD('encoding', 'enum(utf-8=Уникод|* (UTF-8),
                                    cp1251=Windows Cyrillic|* (CP1251),
                                    koi8-r=Rus Cyrillic|* (KOI8-R),
                                    cp2152=Western|* (CP1252),
                                    ascii=Латиница|* (ASCII))', 'caption=Знаци');
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'input=hidden,mandatory,caption=Нишка');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'input=hidden,caption=Документ,oldFieldName=threadDocumentId,mandatory');
        $this->FLD('attachments', 'keylist(mvc=fileman_files, select=name)', 'caption=Файлове,columns=4,input=none');
        $this->FLD('documents', 'keylist(mvc=fileman_files, select=name)', 'caption=Документи,columns=4,input=none');
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
     * @param int $containerId key(mvc=doc_Containers) кой документ се изпраща
     * @param int $threadId key(mvc=doc_Threads) от коя нишка е документа, който се изпраща
     * @param int $boxFrom key(mvc=email_Inboxes) от коя пощенска кутия се изпраща
     * @param string $emailsTo type_Emails списък от адреси на получатели
     * @param string $subject Поле "Относно: "
     * @param mixed $body Обект или масив със съдържанието на писмото. Полетата му са:
     * ->html string - HTML частта на писмото
     * ->text string текстовата част на писмото
     * ->attachments array масив с прикачените файлове (незадължителен)
     * @param array options Масив с опции. Полетата му са:
     * ->encoding string как да се кодират символите на изходящия имейл
     * ->no_thread_hnd boolean дали в изходящото писмо да има информация за нишката
     * (в събджекта, MIME-хедъри-те и пр.)
     */
    static function send($containerId, $threadId, $boxFrom, $emailsTo, $subject, $body, $options)
    {
        // Конвертиране на събджекта ($subject) и текста на писмото ($body->text и $body->html) 
        // в енкодинга, зададен с $options['encoding']
        if ($options['encoding'] == 'ascii') {
            $body->html = str::utf2ascii($body->html);
            $body->text = str::utf2ascii($body->text);
            $subject    = str::utf2ascii($subject);
        } elseif (!empty($options['encoding']) && $options['encoding'] != 'utf-8') {
            $body->html = iconv('UTF-8', $options['encoding'] . '//IGNORE', $body->html);
            $body->text = iconv('UTF-8', $options['encoding'] . '//IGNORE', $body->text);
            $subject    = iconv('UTF-8', $options['encoding'] . '//IGNORE', $subject);
        }
        
        $messageBase = array(
            'subject' => $subject,
            'html'    => $body->html,
            'text'    => $body->text,
            'attachments' => array_merge((array)$body->attachmentsFh, (array)$body->documentsFh),
            'headers' => array(),
            'emailFrom' => email_Inboxes::fetchField($boxFrom, 'email'),
            'charset'   => $options['encoding'],
        );
        
        if (empty($options['no_thread_hnd'])) {
            $myDomain = BGERP_DEFAULT_EMAIL_DOMAIN;
            $handle = static::getThreadHandle($containerId);
            $messageBase['headers']['X-Bgerp-Thread'] = "{$handle}; origin={$myDomain}";
            $messageBase['subject'] = email_util_ThreadHandle::decorate($messageBase['subject'], $handle);
        }
        
        $sentRec = (object)array(
            'boxFrom' => $boxFrom,
            'threadId' => $threadId,
            'containerId' => $containerId,
            'encoding' => $options['encoding'],
            'attachments' => (is_array($body->attachments)) ? type_Keylist::fromArray($body->attachments) :$body->attachments,
            'documents' => (is_array($body->documents)) ? type_Keylist::fromArray($body->documents) :$body->documents,
        );
        
        $emailsTo = type_Emails::toArray($emailsTo);
        
        $nSent = 0;
        
        foreach ($emailsTo as $sentRec->emailTo) {
            $message = (object)$messageBase;
            
            static::prepareMessage($message, $sentRec, $options['is_fax']);
            
            if (static::doSend($message, $sentRec->emailTo)) {
                $sentRec->id = NULL;
                static::save($sentRec);
                $nSent++;
            }
        }
        
        return $nSent;
    }
    
    
    
    static function sendOne($boxFrom, $emailTo, $subject, $body, $options)
    {
        if ($options['encoding'] == 'ascii') {
            $body->html = str::utf2ascii($body->html);
            $body->text = str::utf2ascii($body->text);
            $subject    = str::utf2ascii($subject);
        } elseif (!empty($options['encoding']) && $options['encoding'] != 'utf-8') {
            $body->html = iconv('UTF-8', $options['encoding'] . '//IGNORE', $body->html);
            $body->text = iconv('UTF-8', $options['encoding'] . '//IGNORE', $body->text);
            $subject    = iconv('UTF-8', $options['encoding'] . '//IGNORE', $subject);
        }
        
        $messageBase = array(
            'subject' => $subject,
            'html'    => $body->html,
            'text'    => $body->text,
            'attachments' => array_merge((array)$body->attachmentsFh, (array)$body->documentsFh),
            'headers' => array(),
            'emailFrom' => email_Inboxes::fetchField($boxFrom, 'email'),
            'charset'   => $options['encoding'],
        );
        
        if (empty($options['no_thread_hnd'])) {
            $myDomain = BGERP_DEFAULT_EMAIL_DOMAIN;
            $handle = static::getThreadHandle($body->containerId);
            $messageBase['headers']['X-Bgerp-Thread'] = "{$handle}; origin={$myDomain}";
            $messageBase['subject'] = email_util_ThreadHandle::decorate($messageBase['subject'], $handle);
        }
        
        $sentRec = (object)array(
            'boxFrom' => $boxFrom,
            'mid'     => $body->__mid,
            'encoding' => $options['encoding'],
            'attachments' => (is_array($body->attachments)) ? type_Keylist::fromArray($body->attachments) :$body->attachments,
            'documents' => (is_array($body->documents)) ? type_Keylist::fromArray($body->documents) :$body->documents,
        );
        
        $message = (object)$messageBase;
     
        static::prepareMessage($message, $sentRec, $options['is_fax']);
    
        return static::doSend($message, $emailTo);
    }
    
    /**
     * Подготвя за изпращане по имейл
     *
     * @param stdClass $outRec @see email_Sent::send()
     * @param stdClass $sentRec @see email_Sent::send()
     * @return stdClass обект с попълнени полета според очакванията на @link email_Sent::doSend()
     */
    protected static function prepareMessage($message, $sentRec, $isFax = NULL)
    {
        $myDomain = BGERP_DEFAULT_EMAIL_DOMAIN;
        
        list($senderName, $senderDomain) = explode('@', $message->emailFrom, 2);
        
        expect(is_array($message->headers));
        
        // Намираме сметка за входящи писма от корпоративен тип, с домейла на имейла
        $corpAccRec = email_Accounts::getCorporateAcc();

        if($corpAccRec->domain == $senderDomain && !$isFax) {
            $message->headers['Return-Path'] = "{$senderName}+returned={$sentRec->mid}@{$senderDomain}";
        }
        
        $message->headers += array(
            
            'X-Confirm-Reading-To'        => "{$senderName}+received={$sentRec->mid}@{$senderDomain}",
            'Disposition-Notification-To' => "{$senderName}+received={$sentRec->mid}@{$senderDomain}",
            'Return-Receipt-To'           => "{$senderName}+received={$sentRec->mid}@{$senderDomain}",
        );
        
        $message->messageId = email_util_ThreadHandle::makeMessageId($sentRec->mid);
        
        // Заместване на уникалния идентификатор на писмото с генерираната тук стойност
        $message->html = str_replace('[#mid#]', $sentRec->mid, $message->html);
        $message->text = str_replace('[#mid#]', $sentRec->mid, $message->text);
        
        return $message;
    }
    
    
    /**
     * на случаен уникален идентификатор на писмо
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
     * @param string $emailFrom
     * @param string $emailTo
     * @return bool
     */
    protected static function doSend($message, $emailTo)
    {
        expect($emailTo);
        expect($message->emailFrom);
        expect($message->subject);
        expect($message->html || $message->text);
        
        /** @var $PML PHPMailer */
        $PML = email_Accounts::getPML($message->emailFrom);
        
        $PML->AddAddress($emailTo);
        $PML->SetFrom($message->emailFrom);
        $PML->Subject   = $message->subject;
        $PML->CharSet   = $message->charset;
        $PML->MessageID = $message->messageId;
        $PML->ClearReplyTos();
        
        if (!empty($message->html)) {
            $PML->Body = $message->html;
            
            //Вкарваме всички статични файлове в съобщението
            self::embedSbfImg($PML);
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
        if (!($rec = static::fetch("#mid = '{$mid}'"))) {
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
     * в историята факта че (по-рано изпратено от нас) писмо не е доставено до получателя си
     *
     * @param string $mid Уникален ключ на писмото, което не е доставено
     * @param string $date дата на върнатото писмо
     * @return boolean TRUE намерено е писмото-оригинал и събитието е отразено;
     */
    public static function returned($mid, $date = NULL)
    {
        if (!($rec = static::fetch("#mid = '{$mid}'"))) {
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
        
        if ($result = static::save($rec)) {
            // Нотификация за връщането на писмото до изпращача му
            bgerp_Notifications::add(
                'Върнати писма', // съобщение 
                array('email_Sent', 'list', 'state'=>'returned'), // URL 
                $rec->createdBy, // получател на нотификацията 
                'alert' // Важност (приоритет)
            );
        }
        
        return $result;
    }
    
    
    /**
     * Извлича запис на модела от зададен MID
     *
     * @param string $mid
     * @param mixed $fields
     * @return int NULL, ако не е намерен такъв MID
     */
    public static function fetchByMid($mid, $fields = NULL)
    {
        return static::fetch(array("#mid = '[#1#]'", $mid), $fields);
    }
    
    
    /**
     * Изпълнява се всеки преди запис
     *
     * @param core_Manager $mvc
     * @param int $id
     * @param stdClass $rec
     */
    static function on_BeforeSave($mvc, $id, $rec)
    {
        // Маркираме записите, които се създават
        $rec->_new = empty($rec->id);
    }
    
    
    /**
     * Изпълнява се след всеки запис в модела
     *
     * @param doc_Log $mvc
     * @param int $id key(mvc=doc_Log)
     * @param stdClass $rec запис на модела, който е бил записан в БД
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        expect($rec->threadId);
        
        // Изчистваме кешираната история на треда, понеже тя току-що е била променена.
        $mvc::removeHistoryFromCache($rec->threadId);
        
        if ($rec->_new) {
            // Ако е новодобавен запис, обновява правилата на рутера
            $mvc::updateRouterRules($rec);
        }
    }
    
    
    /**
     * Изтрива от кеша записана преди история на нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     */
    static function removeHistoryFromCache($threadId)
    {
        $cacheKey = static::getHistoryCacheKey($threadId);
        
        core_Cache::remove(static::CACHE_TYPE, $cacheKey);
    }
    
    
    /**
     * Обновява правилата за рутиране след успешно изпращане до един конкретен имейл
     *
     * Извиква се от @link email_Sent::on_AfterSave()
     *
     * @param stdClass $rec запис на модела email_Sent
     */
    static function updateRouterRules($rec)
    {
        // Генериране на `From` правило за рутиране
        email_Router::saveRule(
            (object)array(
                'type'       => email_Router::RuleFrom,
                'key'        => email_Router::getRoutingKey($rec->emailTo, NULL, email_Router::RuleFrom),
                'priority'   => email_Router::dateToPriority(dt::now(TRUE), 'mid', 'asc'), // със среден приоритет, нарастващ с времето
                'objectType' => 'document',
                'objectId'   => $rec->containerId
            )
        );
        
        if ($key = email_Router::getRoutingKey($rec->emailTo, NULL, email_Router::RuleDomain)) {
            // Има ключ за `Domain` правило, значи трябва да се генерира и самото правило,
            // но само при условие, че папката, в която е изпратеното писмо е фирмена папка
            
            if ($folderId = doc_Containers::fetchField($rec->containerId, 'folderId')) {
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
                            'mid',  // със среден приоритет,
                            'asc'), // нарастващ с времето
                        'objectType' => 'document',
                        'objectId' => $rec->containerId));
            }
        }
    }
    
    /**
     * Ключ, под който се записва историята на нишка в кеша
     *
     * @see core_Cache
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return string
     */
    protected static function getHistoryCacheKey($threadId)
    {
        return $threadId;
    }
    
    
    /**
     * Зарежда историята на нишка. Проверява в кеша, ако я няма - преизчислява записва в кеша.
     *
     * @see core_Cache
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return array историята на нишката, във вида в който я връща @link email_Sent::buildThreadHistory()
     */
    public static function loadHistory($threadId)
    {
        $cacheKey = static::getHistoryCacheKey($threadId);
        
        if (($history = core_Cache::get(static::CACHE_TYPE, $cacheKey)) === FALSE) {
            // Историята на този тред я няма в кеша - подготвяме я и я записваме в кеша
            $history = static::buildThreadHistory($threadId);
            core_Cache::set(static::CACHE_TYPE, $cacheKey, $history, '2 дена');
        }
        
        return $history;
    }
    
    /**
     * Преизчислява историята за изпращанията на контейнерите в нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return array масив с ключ $containerId (на контейнерите от $threadId, за които има запис
     *                  в историята) и стойности - обекти (stdClass) със следната структура:
     *
     *     ->summary => array(
     *         'returned' => {брой връщания}, // след изпращане на документа по имейл
     *         'received' => {брой получавания},
     *         'sent'     => {брой изпращания}, // колко пъти документа е бил изпратен по имейл
     *     )
     *
     *  ->containerId - контейнера, чиято история се съдържа в обекта (за удобство)
     */
    protected static function buildThreadHistory($threadId)
    {
        static::log('Регенериране на историята на нишка', $threadId, 3);
        
        $query = static::getQuery();
        $query->where("#threadId = {$threadId}");
        $query->orderBy('#createdOn');
        
        $data = array();   // Масив с историите на контейнерите в нишката
        while ($rec = $query->fetch()) {
            if (isset($rec->returnedOn)) {
                $data[$rec->containerId]->summary['returned'] += 1;
            }
            
            if (isset($rec->receivedOn)) {
                $data[$rec->containerId]->summary['received'] += 1;
            }
            
            $data[$rec->containerId]->summary['sent'] += 1;
            $data[$rec->containerId]->containerId = $rec->containerId;
        }
        
        return $data;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        if ($containerId = Request::get('containerId', 'key(mvc=doc_Containers)')) {
            unset($data->listFields['containerId']);
            $data->query->where("#containerId = {$containerId}");
            $data->doc = doc_Containers::getDocument($containerId, 'doc_DocumentIntf');
        }
        
        $data->query->orderBy('#createdOn', 'DESC');
    }
    
    
    /**
     * Подготовка на форма за филтър на списъчен изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        if ($data->doc) {
            // Не показваме форма за филтриране ако е избран конкретен документ
            return;
        }
        
        /* @var $data core_Form */
        $data->listFilter->setField('id', 'input=none');
        $data->listFilter->setField('containerId', 'input=none');
        $data->listFilter->setField('threadId', 'input=none');
        $data->listFilter->FNC('users', 'users', 'caption=Потребител,input,silent');
        $data->listFilter->FNC('state', 'enum(*=Всички,received=Само получените,returned=Само върнатите)', 'caption=Състояние,input,silent');
        $data->listFilter->FNC('recipient', 'varchar', 'caption=До,input,silent');
        $data->listFilter->showFields = 'users,state,recipient';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->input(null, 'silent');
    }
    
    
    /**
     * Подредба и филтър на on_BeforePrepareListRecs()
     * Манипулации след подготвянето на основния пакет данни
     * предназначен за рендиране на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        // Филтър по изпращач
        $users = array();
        
        if ($data->listFilter->rec->users) {
            $users = type_Keylist::toArray($data->listFilter->rec->users);
        }
        
        if (empty($users)) {
            // По подразбиране (когато не е зададен потребител) филтрираме списъка по текущия.
            /*
             * @todo stv: Това се оказа наложително. Причината е в типа `type_Users`. Ако искаме да
             * конструираме URL, съдържащо в себе си стойност на полето users (нотификациите
             * имат такава нужда) не е ясно каква стойност да зададем. За това не задаваме
             * никаква, а тук приемаме, че ако няма потребител се подразбира филтър по текущия.
             * Това върши работа за нотификациите.
             * 
             * Да разбера дали има други възможности!
             * 
             */
            
            $users = array(core_Users::getCurrent());
        }
        
        $data->query->where('#createdBy IN (' . implode(', ', $users) . ')');
        
        // Филтър "само получени". Подрежда резултата в обратно хронологичен ред
        if ($data->listFilter->rec->state == 'received') {
            $data->query->where('#receivedOn IS NOT NULL');
            $data->query->orderBy('#receivedOn', 'DESC');
        }
        
        // Филтър "само върнати". Подрежда резултата в обратно хронологичен ред
        if ($data->listFilter->rec->state == 'returned') {
            $data->query->where('#returnedOn IS NOT NULL');
            $data->query->orderBy('#returnedOn', 'DESC');
        }
        
        // Филтър по имейл адрес на получател
        if ($data->listFilter->rec->recipient) {
            $data->query->where(array("#emailTo LIKE '%[#1#]%'", $data->listFilter->rec->recipient));
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareListRows($mvc, $data) {
        if ($data->recs && $data->listFields['containerId']) {
            foreach ($data->recs as $i => $rec) {
                $doc = doc_Containers::getDocument($rec->containerId);
                
                if ($doc->instance->haveRightFor('single', $doc->that)) {
                    $data->rows[$i]->containerId = $doc->getLink();
                } else {
                    // Няма достъп до документа (писмото) - не показваме реда
                    unset($data->rows[$i]);
                }
            }
        }
        
        if ($data->listFilter->rec->state == 'returned') {
            // Изчистваме нотификациите на текущия потребител за върнати писма
            bgerp_Notifications::clear(array('email_Sent', 'list', 'state'=>'returned'), core_users::getCurrent());
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareListTitle($mvc, $data)
    {
        if ($containerId = Request::get('containerId', 'key(mvc=doc_Containers)')) {
            $data->title = "История";
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterRenderListTitle($mvc, &$tpl, $data)
    {
        if ($data->doc) {
            $link = $data->doc->getLink();
            $tpl = '<div class="listTitle">История на ' . $link . '</div>';
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        if ($data->doc) {
            $tpl->append($data->doc->getDocumentBody());
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getExternalEmails($threadId)
    {
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->where("#threadId = {$threadId}");
        $query->show('emailTo');
        
        $result = array();
        
        while ($rec = $query->fetch()) {
            if($eml = trim($rec->emailTo)) {
                $result[$eml] = $eml;
            }
        }
        
        return $result;
    }
    
    
    /**
     * Вкарва всички статични изображения, като cid' ове
     * Приема обект.
     * Прави промените в $PML->Body
     */
    static function embedSbfImg(&$PML)
    {
        //Енкодинг
        $encoding = 'base64';
        
        //Ескейпваме името на директорията. Също така, допълнително ескейпваме и '/'
        $efSbf = preg_quote(EF_SBF, '/');
        
        //Шаблон за намиране на всички статични изображения в img таг
        $patternImg = "/<img[^>]+src=\"([^\">]+[\\\\\/]+" .  $efSbf . "[\\\\\/]+[^\">]+)\"/im";
        
        //Намираме всички статични изображения в img таг
        preg_match_all($patternImg, $PML->Body, $matchesImg);
        
        //Шаблон за намиране на всички статични изображения в background
        $patternBg = "/background[-image]*:[\s]*url[\s]*\(\"([^\)\"]+[\\\\\/]+" .  $efSbf . "[\\\\\/]+[^\)\"]+)\"/im";
        
        //Намираме всички статични изображения в background
        preg_match_all($patternBg, $PML->Body, $matchesBg);
        
        //Ако и двета масива съществуват, обединяваме ги
        if ((count($matchesImg[1])) && (count($matchesBg[1]))) {
            foreach ($matchesBg[1] as $key => $value) {
                $matchesImg[0][] = $matchesBg[0][$key];
                $matchesImg[1][] = $matchesBg[1][$key];
            }
            $matches = $matchesImg;
        }
        
        //Ако не сме открили съвпадения за background използваме img
        if ((count($matchesImg[1])) && (!count($matchesBg[1]))) {
            $matches = $matchesImg;
        }
        
        //Ако не сме открили съвпадения за img използваме background
        if ((!count($matchesImg[1])) && (count($matchesBg[1]))) {
            $matches = $matchesBg;
        }
        
        //Ако сме открили съвпадение
        if (count($matches[1])) {
                        
            //Обхождаме всички открите изображения
            foreach ($matches[1] as $imgPath) {
                                
                //Превръщаме абсолютния линк в реален, за да може да работи phpmailer' а
                $imgFile = self::absoluteUrlToReal($imgPath);
                
                //Масив с данните за линка
                $imgPathInfo = pathinfo($imgPath);
                
                //Името на файла
                $filename = $imgPathInfo['basename'];
                
                //Последната точка в името на файла
                $dotPos = mb_strrpos($filename, ".");
                
                //Добавяме стойността на брояча между името и разширението на cid'а за да е уникално
                $cidName = mb_substr($filename, 0, $dotPos) . $i . mb_substr($filename, $dotPos);
                
                //cid' а, с който ще заместваме
                $cidPath = "cid:" . $cidName;
                
                //Вземаме mimeType' а на файла
                $mimeType = fileman_Mimes::getMimeByExt($imgPathInfo['extension']);
                
                //Шаблона, за намиране на URL' то на файла
                $pattern = "/" . preg_quote($imgPath, '/') . "/im";
                
                //Заместваме URL' то на файла със съответния cid
                $PML->Body = preg_replace($pattern, $cidPath, $PML->Body, 1);
                
                //Ембедваме изображението
                $PML->AddEmbeddedImage($imgFile, $cidName, $filename, $encoding, $mimeType);
                
                //Брояч
                $i++;
            }
        }
    }
    
    
    /**
     * Превръша абсолютново URL в линк в системата
     */
    static function absoluteUrlToReal($link)
    {
        //sbf директорията
        $sbfPath = str_ireplace(EF_INDEX_PATH, '', EF_SBF_PATH);
        
        //Намираме позицията където се среща sbf директорията
        $spfPos = mb_stripos($link, $sbfPath);
        
        //Ако сме открили съвпадание
        if ($spfPos !== FALSE) {
            //Пътя на файла след sbf директорията
            $sbfPart = mb_substr($link, $spfPos + mb_strlen($sbfPath));
            
            //Връщаме вътрешното URL на файла в системата
            $realLink = EF_SBF_PATH . $sbfPart;
            
            return $realLink;
        }
    }
}
