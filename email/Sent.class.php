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
    var $listFields = 'createdOn=Изпратено->на, createdBy=Изпратено->от, containerId, boxFrom, emailTo, receivedOn, receivedIp, returnedOn';
    
    
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
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('boxFrom', 'key(mvc=email_Inboxes, select=email)', 'caption=От адрес,mandatory');
        $this->FLD('emailTo', 'email', 'caption=До,input=none');
        $this->FLD('encoding', 'enum(utf-8=Уникод|* (UTF-8),
                                    cp1251=Win Cyrillic|* (CP1251),
                                    koi8-r=Rus Cyrillic|* (KOI8-R),
                                    cp2152=Western|* (CP1252),
                                    ascii=Латиница|* (ASCII))', 'caption=Знаци');
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
     * @param int $containerId key(mvc=doc_Containers) кой документ се изпраща
     * @param int $threadId key(mvc=doc_Threads) от коя нишка е документа, който се изпраща
     * @param int $boxFrom key(mvc=email_Inboxes) от коя пощенска кутия се изпраща
     * @param string $emailsTo type_Emails списък от адреси на получатели
     * @param string $subject Поле "Относно: "
     * @param mixed $body Обект или масив със съдържанието на писмото. Полетата му са:
     * 	->html string - HTML частта на писмото
     * 	->text string текстовата част на писмото
     *  ->attachments array масив с прикачените файлове (незадължителен)
     * @param array options Масив с опции. Полетата му са:
     * 	->encoding string как да се кодират символите на изходящия имейл
     *  ->no_thread_hnd boolean дали в изходящото писмо да има информация за нишката 
     *  						(в събджекта, MIME-хедърите и пр.) 
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
            'attachments' => $body->attachments,
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
        );
        
        $emailsTo = type_Emails::toArray($emailsTo);
        
        $nSent = 0;
        
        foreach ($emailsTo as $sentRec->emailTo) {
            $message = (object)$messageBase;
            
            static::prepareMessage($message, $sentRec);
            
            if (static::doSend($message, $sentRec->emailTo)) {
                $sentRec->id = NULL;
                static::save($sentRec);
                $nSent++;
            }
        }
        
        return $nSent;
    }
    
    
    /**
     * Подготвя за изпращане по имейл
     *
     * @param stdClass $outRec @see email_Sent::send()
     * @param stdClass $sentRec @see email_Sent::send()
     * @return stdClass обект с попълнени полета според очакванията на @link email_Sent::doSend()
     */
    protected static function prepareMessage($message, $sentRec)
    {
        // Генериране на уникален иденфикатор на писмото
        $sentRec->mid = static::generateMid();
        
        $myDomain = BGERP_DEFAULT_EMAIL_DOMAIN;
        
        list($senderName,) = explode('@', $message->emailFrom, 2);
        
        expect(is_array($message->headers));
        
        $message->headers += array(
            'Return-Path'                 => "{$senderName}+returned={$sentRec->mid}@{$myDomain}",
            'X-Confirm-Reading-To'        => "{$senderName}+received={$sentRec->mid}@{$myDomain}",
            'Disposition-Notification-To' => "{$senderName}+received={$sentRec->mid}@{$myDomain}",
            'Return-Receipt-To'           => "{$senderName}+received={$sentRec->mid}@{$myDomain}",
        );

        $message->messageId = email_util_ThreadHandle::makeMessageId($sentRec->mid);
        
        // Заместване на уникалния идентификатор на писмото с генерираната тук стойност
        $message->html = str_replace('[#mid#]', $sentRec->mid, $message->html);
        $message->text = str_replace('[#mid#]', $sentRec->mid, $message->text);
        
        return $message;
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
        $PML = static::getMailer();
        
        $PML->AddAddress($emailTo);
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
    
    
    /**
     * 
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
    function on_BeforeSave($mvc, $id, $rec)
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
    function on_AfterSave($mvc, $id, $rec)
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
     * 				 в историята) и стойности - обекти (stdClass) със следната структура:
     * 
     * 	->summary => array(
     * 		'returned' => {брой връщания}, // след изпращане на документа по имейл
     * 		'received' => {брой получавания},
     * 		'sent'     => {брой изпращания}, // колко пъти документа е бил изпратен по имейл
     * 	)
     * 
     *  ->containerId - контейнера, чиято история се съдържа в обекта (за удобство)
     */
    protected static function buildThreadHistory($threadId)
    {
        static::log('Регенериране на историята на нишка', $threadId, 3);
        
        $query = static::getQuery();
        $query->where("#threadId = {$threadId}");
        $query->orderBy('#createdOn');
        
        $data = array(); // Масив с историите на контейнерите в нишката
        
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
    
    
    function on_AfterPrepareListFields($mvc, $data)
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
    function on_AfterPrepareListFilter($mvc, $data)
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
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        // Филтър по изпращач
        if ($data->listFilter->rec->users && $users = type_Keylist::toArray($data->listFilter->rec->users)) {
            $data->query->where('#createdBy IN (' . implode(', ', $users) . ')');
        }
        
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
    
    
    function on_AfterPrepareListRows($mvc, $data) {
        if ($data->recs && $data->listFields['containerId']) {
            foreach ($data->recs as $i=>$rec) {
                $doc = doc_Containers::getDocument($rec->containerId);
                $data->rows[$i]->containerId = $doc->getLink();
            }
        }
    }
    
    
    function on_AfterPrepareListTitle($mvc, $data)
    {
        if ($containerId = Request::get('containerId', 'key(mvc=doc_Containers)')) {
            $data->title = "История";
        }
    }
    
    
    function on_AfterRenderListTitle($mvc, $tpl, $data)
    {
        if ($data->doc) {
            $link = $data->doc->getLink();
            $tpl = '<div class="listTitle">История на ' . $link . '</div>';
        }
    }
    
    
    function on_AfterRenderListTable($mvc, $tpl, $data)
    {
        if ($data->doc) {
            $tpl->append($data->doc->getDocumentBody());
        }
    }


    static function getExternalEmails($threadId)
    {
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->where("#threadId = {$threadId}");
        $query->show('emailTo');
        
        $result = array();
        
        while ($rec = $query->fetch()) {
            $result[$rec->emailTo] = $rec->emailTo;
        }

        return $result;
    }
    
}
