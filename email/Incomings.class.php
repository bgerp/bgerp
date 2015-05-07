<?php 


/**
 * Входящи писма
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Incomings extends core_Master
{
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = TRUE;
    
    
    /**
     * Текста бутона за създаване на имейли
     */
    var $emailButtonText = 'Отговор';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'email_Messages';
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Входящи имейли';
    
    
    /**
     * @todo Чака за документация...
     */
    var $singleTitle = 'Входящ имейл';
    

    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    var $fetchFieldsBeforeDelete = 'id';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой има право да го чете?
     */
    var $canSingle = 'powerUser';
    
     
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'email_Wrapper, doc_DocumentPlg, 
    				plg_RowTools, plg_Printing, email_plg_Document, 
    				doc_EmailCreatePlg, plg_Sorting, bgerp_plg_Blank,
    				plg_AutoFilter';
    
    
    /**
     * Сортиране по подразбиране по низходяща дата
     */
    var $defaultSorting = 'date=down';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'email/tpl/SingleLayoutMessages.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/email.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Msg";
    
    
    /**
     * Първоначално състояние на документа
     */
    var $firstState = 'closed';
    
    
    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'subject';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,subject,createdOn=Дата,fromEml=От,toBox=До,accId,routeBy,uid,country';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'subject, fromEml, fromName, textPart, files';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('accId', 'key(mvc=email_Accounts,select=email, allowEmpty)', 'caption=Имейл акаунт, autoFilter');
        $this->FLD("subject", "varchar", "caption=Тема");
        $this->FLD("fromEml", "email", 'caption=От->Имейл');
        $this->FLD("fromName", "varchar", 'caption=От->Име');
        
        // Първия наш имейл от MIME-хедъра "To:"
        $this->FLD("toEml", "email(link=no)", 'caption=До->Имейл');
        
        // Наша пощенска кутия (email_Inboxes) до която е адресирано писмото.
        // Това поле се взема предвид при рутиране и създаване на правила за рутиране.
        $this->FLD("toBox", "email(link=no)", 'caption=До->Кутия');
        
        $this->FLD("headers", "blob(serialize,compress)", 'caption=Хедъри');
        $this->FLD("textPart", "richtext(hndToLink=no, nickToLink=no)", 'caption=Текстова част');
        $this->FLD("spam", "int", 'caption=Спам');
        $this->FLD("lg", "varchar", 'caption=Език');
        $this->FLD("date", "datetime(format=smartTime)", 'caption=Дата');
        $this->FLD('hash', 'varchar(32)', 'caption=Keш');
        $this->FLD('country', 'key(mvc=drdata_countries, select=commonName, selectBg=commonNameBg, allowEmpty)', 'caption=Държава, autoFilter');
        $this->FLD('fromIp', 'ip', 'caption=IP');
        $this->FLD('files', 'keylist(mvc=fileman_Files)', 'caption=Файлове, input=none');
        $this->FLD('emlFile', 'key(mvc=fileman_Files)', 'caption=eml файл, input=none');
        $this->FLD('htmlFile', 'key(mvc=fileman_Files)', 'caption=html файл, input=none');
        $this->FLD('uid', 'int', 'caption=Imap UID');
        
        $this->FLD('routeBy', 'enum(thread, preroute, from, fromTo, domain, toBox, country)', 'caption=Рутиране');
        
        $this->setDbUnique('hash');
    }
    
    
    /**
     * Взема записите от пощенската кутия и ги вкарва в модела
     *
     *
     * @return string $logMsg - Съобщение с броя на новите имейли
     */
    function fetchAllAccounts()
    {
    	$conf = core_Packs::getConfig('email');
    	
        // Задаваме максималната използваема памет
        ini_set('memory_limit', $conf->EMAIL_MAX_ALLOWED_MEMORY);

        // Максималната продължителност за теглене на писма
        $maxFetchingTime = $conf->EMAIL_MAX_FETCHING_TIME;
    
        // Даваме достатъчно време за изпълнението на PHP скрипта
        set_time_limit($maxFetchingTime + 120);
            
        // До коя секунда в бъдещето максимално да се теглят писма?
        $deadline = time() + $maxFetchingTime;
        
        // Вземаме последователно сметките, подредени по случаен начин
        $accQuery = email_Accounts::getQuery();
        $accQuery->XPR('order', 'double', 'RAND()');
        $accQuery->orderBy('#order');

        while (($accRec = $accQuery->fetch("#state = 'active'")) && ($deadline > time())) {
            self::fetchAccount($accRec, $deadline, $maxFetchingTime);
        }
    }


    /**
     * Извлича писмата от посочената сметка
     */
    function fetchAccount($accRec, $deadline, $maxFetchingTime)
    { 
        // Заключваме тегленето от тази пощенска кутия
        $lockKey = 'Inbox:' . $accRec->id;
                   
        if(!core_Locks::get($lockKey, $maxFetchingTime, 1)) {
            $this->log("Кутията {$accRec->email} е заключена от друг процес", NULL, 7);

            return;
        }

        // Нулираме броячите за различните получени писма
        $statusSum = array();
 
        // Връзка по IMAP към сървъра на посочената сметка
        $imapConn = cls::get('email_Imap', array('accRec' => $accRec)); 

        // Логването и генериране на съобщение при грешка е винаги в контролерната част
        if ($imapConn->connect() === FALSE) {
            $errMsg = "Грешка на <b>\"{$accRec->user} ({$accRec->server})\"</b>:  " . $imapConn->getLastError() . "";
            $this->log($errMsg, NULL, 14);
            $htmlRes .= $errMsg;
            
            return;
        }

        // Получаваме броя на писмата в INBOX папката
        $numMsg = $imapConn->getStatistic('messages');
            
        $firstUnreadMsgNo = $this->getFirstUnreadMsgNo($imapConn, $numMsg);
 
        $startTime = time();

        if($firstUnreadMsgNo > 0) {
            
            // Правим цикъл по всички съобщения в пощенската кутия
            // Цикълът може да прекъсне, ако надвишим максималното време за сваляне на писма
            for ($i = $firstUnreadMsgNo; $i <= $numMsg && ($deadline > time()); $i++) {
                    
                try {
                    $status = $this->fetchEmail($imapConn, $i);
                } catch (core_Exception_Expect $exp) {
                    $status = 'fetching error';
                }

                if(($i % 100) == 1 || ( ($i - $firstUnreadMsgNo) < 100)) {
                    $logMsg = "Fetching message {$i} from {$accRec->email}: {$status}";
                    $this->log($logMsg, NULL, 7);
                }
                
                // Изтриване на писмото, ако ако сметката е настроена така
                if ($accRec->deleteAfterRetrieval == 'yes') {
                    $imapConn->delete($i);
                    $statusSum['delete']++;
                    $doExpunge = TRUE;
                }

                $statusSum[$status]++;
            }
            
            // Изтриваме предварително маркираните писма
            if($doExpunge) {
                $imapConn->expunge();
            }
            
        }
        
        // Затваряме IMAP връзката
        $imapConn->close();
            
        // Махаме заключването от кутията
        core_Locks::release($lockKey);
        
        // Общото времето за процесиране на емейл-сметката
        $duration = time() - $startTime;
        
        // Генерираме и записваме лог съобщение
        $msg = "{$accRec->email}: ($duration s); Total: {$numMsg}";
        
        $newStatusArr = array();
        
        // Обхождаме всички статуси
        foreach((array)$statusSum as $status => $cnt) {
            
            // В зависимост от типа на статуса
            switch ($status) {
                case 'incoming':
                    $newStatusArr['new'] = $cnt;
                break;
                
                default:
                    $newStatusArr[$status] = $cnt;
                break;
            }
        }
        
        // Обхождаме новия масив
        foreach ((array)$newStatusArr as $statusKey => $statusCnt) {
            
            // Първата буква да главна
            $statusKey = ucfirst(strtolower($statusKey));
            
            // Добавяме към съотбщението
            $msg .= ", {$statusKey}: {$statusCnt}";
        }
        
        // Показваме стринга
        echo "<h3> $msg </h3>";

        $this->log($msg, NULL, 7);
    }


    /**
     * Извлича посоченото писмо от отворената връзка
     */
    function fetchEmail($imapConn, $msgNo)
    {
        try {
            // Извличаме хедърите и проверяваме за дублиране
            $headers = $imapConn->getHeaders($msgNo);
            
            if(email_Fingerprints::isDown($headers)) {
                
                return 'duplicated';
            }
            
            // Кой е UID на писмото?
            $uid = $imapConn->getUid($msgNo);
 
            // Записа на имейл сметката, от където се тегли
            $accId = $imapConn->accRec->id;
            
            // Извличаме цялото писмо
            $rawEmail = $imapConn->getEml($msgNo);

            // Създава MIMЕ обект
            $mime = cls::get('email_Mime');
            
            try {
                // Парсира съдържанието на писмото
                $mime->parseAll($rawEmail);
                
                // Вземаме хедърите, този път от самото писмо
                $headers = $mime->getHeadersStr();
    
                // Отново правим проверка дали писмото е сваляно
                if(email_Fingerprints::isDown($headers)) {
                    
                    return 'duplicated';
                }
                
                $conf = core_Packs::getConfig('email');
                
                // Очакваме текстовата част да е под допустимия максимум
                expect(mb_strlen($mime->textPart) < $conf->EMAIL_MAX_TEXT_LEN);
                
             } catch(core_exception_Expect $exp) {
                // Не-парсируемо
                email_Unparsable::add($rawEmail, $accId, $uid);
                $status = 'misformatted';
            }
            
            // Ако писмото не е с лошо форматиране
            if ($status != 'misformatted') {
                // Пробваме дали това не е служебно писмо
                // Ако не е служебно, пробваме дали не е SPAM
                // Ако не е нищо от горните, записваме писмото в този модел
                if(email_Returned::process($mime, $accId, $uid)) {
                    $status = 'returned';
                } elseif(email_Receipts::process($mime, $accId, $uid)) {
                    $status = 'receipt';
                } elseif(email_Spam::process($mime, $accId, $uid)) {
                    $status = 'spam';
                } elseif(self::process($mime, $accId, $uid)) {
                    $status = 'incoming';
                }
            }
        } catch (core_exception_Expect $exp) {
            // Обща грешка
            $status = 'error';
        }
        
        // Записваме в отпечатъка на това писмо, както и статуса му на сваляне
        if(in_array($status, array('returned', 'receipt', 'spam', 'incoming', 'misformatted'))) {
            // Записваме статуса на сваленото писмо (service, misformatted, normal);
            email_Fingerprints::setStatus($headers, $status, $accId, $uid);
        }

        return $status;
    }

    
    /**
     * Подготвя, записва и рутира зададеното писмо
     */
    function process($mime, $accId, $uid)
    {   
        $mime->saveFiles();

        $rec = new stdClass();
        
        // Декодираме и запазваме събджекта на писмото
        $rec->subject = $mime->getSubject();
        
        // Извличаме информация за изпращача
        $rec->fromName = $mime->getFromName();
        $rec->fromEml = $mime->getFromEmail();
        
        // Опитва се да намари IP адреса на изпращача
        $rec->fromIp = $mime->getSenderIp();
        
        // Извличаме информация за получателя (към кого е насочено писмото)
        $rec->toEml = $mime->getToEmail();
        
        // Намира вътрешната пощенска кутия, към която е насочено писмото
        $rec->toBox = email_Inboxes::getToBox($mime, $accId);
        
        // Пробваме да определим езика на който е написана текстовата част
        $rec->lg = $mime->getLg();
        
        // Определяме датата на писмото
        $rec->date = $mime->getSendingTime();
        
        // Опитваме се да определим държавата на изпращача
        $rec->country = $mime->getCountry();
        
        // Задаваме прикачените файлове като keylist
        $rec->files = $mime->getFiles();
        
        // Задаваме първата html част като .html файл
        $rec->htmlFile = $mime->getHtmlFile();
        
        // Записваме текста на писмото, като [hash].eml файл
        $rec->emlFile =  $mime->getEmlFile();
        
        // Задаваме текстовата част
        $rec->textPart = $mime->textPart;
        
        // Запазване на допълнителни MIME-хедъри за нуждите на рутирането
        $rec->inReplyTo   = $mime->getHeader('In-Reply-To');
        
        // От коя сметка е получено писмото
        $rec->accId = $accId;
        $rec->uid   = $uid;
        
        // Добавяме хедърите
        $headersStr = $mime->getHeadersStr();
        
        // Преобразуваме в масив с хедъри и сериализираме
        $rec->headers = $mime->parseHeaders($headersStr);
        
        // Записваме (и автоматично рутираме) писмото
        $saved = email_Incomings::save($rec);

        return $saved;
    }


    /**
     * Връща поредния номер на първото не-четено писмо
     */
    protected function getFirstUnreadMsgNo($imapConn, $maxMsgNo)
    {
        // Няма никакви съобщения за сваляне?
        if(!($maxMsgNo > 0)) {
            
            return NULL;
        }
        
        if($imapConn->accRec->protocol == 'imap') {
            $query = email_Fingerprints::getQuery();
            $query->XPR('maxUid', 'int', 'max(#uid)');
            $query->show('maxUid');
            $maxRec = $query->fetch("#accountId = {$imapConn->accRec->id}");
        }

        $maxReadMsgNo = 0;

        if($maxRec->maxUid) {
            $maxReadMsgNo = $imapConn->getMsgNo($maxRec->maxUid);
        }

        if($maxReadMsgNo === 0) {
            // Горен указател
            $t = $maxMsgNo; 
            
            $i = 1;
            
            // Долен указател
            $b = max(1, $maxMsgNo - $i);
            
            $isDownT = $this->isDownloaded($imapConn, $t);

            // Дали всички съобщения са прочетени?
            if($isDownT) {
                return NULL;
            }

            $isDownB = $this->isDownloaded($imapConn, $b);

            do {
                // Ако и двете не са свалени; Изпълнява се няколко пъти последователно в началото
                if(!$isDownB && !$isDownT) {
                    if($t == $b) {

                        return $t;
                    }
                    $t = $b;
                    $i = $i * 2;
                    $b = max(1, $maxMsgNo - $i);
                    $isDownB = $this->isDownloaded($imapConn, $b);
                } elseif($isDownB && !$isDownT) {
                    // Условие, при което $t е първото не-свалено писмо
                    if($t - $b == 1) {

                        return $t;
                    }
                    $m = round(($t + $b) / 2);
                    $isDownM = $this->isDownloaded($imapConn, $m);
                    if($isDownM) {
                        $b = $m;
                    } else {
                        $t = $m;
                    }
                }

                $change = ($t != $tLast || $b != $bLast);

                $tLast = $t;

                $bLast = $b;
                
            } while($change);

        } else {
           
            if(($maxReadMsgNo === FALSE) || ($maxReadMsgNo >= $maxMsgNo)) {
                $maxReadMsgNo = NULL;
            } else {
                $maxReadMsgNo++;
            }

            return $maxReadMsgNo;
        }
    }

    
    /**
     * Дали посоченото писмо е сваляно до сега?
     */
    protected function isDownloaded($imapConn, $msgNum)
    {
        static $isDown = array();
        $this->log("Check Down: $msgNum  ", NULL, 7);
        $accId = $imapConn->accRec->id;

        // Номерата почват от 1
        if($msgNum < 1) {
            $this->log("TRUE: $msgNum < 1", NULL, 7);

            return TRUE;
        }
        
        if(!isset($isDown[$accId][$msgNum])) {

            $headers = $imapConn->getHeaders($msgNum);

            // Ако няма хедъри, значи има грешка
            if(!$headers) {
                $this->log("[{$accId}][{$msgNum}] - missing headers", NULL, 7);

                return TRUE;
            }

            $isDown[$accId][$msgNum] = email_Fingerprints::isDown($headers);
        }
        
        $this->log("Result: $msgNum  " . $isDown[$accId][$msgNum], NULL, 7);

        return $isDown[$accId][$msgNum];
    }
    
    
 	/**
     * Изпълнява се след подготовката на формата за филтриране
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
       
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $form->showFields = 'country, accId';
        
        
        $form->input('country, accId', 'silent');

        if($form->rec->country) {
            $data->query->where(array("#country= '[#1#]'", $form->rec->country));
        } 
        
        if($form->rec->accId) { 
            $data->query->where(array("#accId= '[#1#]'", $form->rec->accId));
        }
    }


    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields)
    {
        if (!is_object($rec) && is_numeric($rec)) {
            $rec = $mvc->fetch($rec);
        }
        
        $rec->textPart = trim($rec->textPart);

        if(empty($rec->toEml)) {
            $rec->toEml = $rec->toBox;
        }
    }
    
    
    /**
     * Преобразува containerId в машинен вид
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields)
    {
        if(!$rec->subject) {
            $row->subject .= '[' . tr('Липсва заглавие') . ']';
        }
        
        if($fields['-single']) {
            if ($rec->files) {
                
                $vals = keylist::toArray($rec->files);
                
                if($rec->htmlFile) {
                    unset($vals[$rec->htmlFile]);
                }

                if (count($vals)) {
                    $row->files = '';
                    
                    foreach ($vals as $keyD) {
                        $row->files .= fileman_Files::getLinkById($keyD);
                    }
                } else {
                    $row->files = '';
                }
            }
            
            static::calcAllToAndCc($rec);
            
            $row->allTo = self::getVerbalEmail($rec->allTo);
            $row->allCc = self::getVerbalEmail($rec->allCc);
        }
        
        if(!$rec->toBox) {
            $row->toBox = $row->toEml;
        }
        
        if($rec->fromIp) {
            $row->fromIp = type_Ip::decorateIp($rec->fromIp, $rec->createdOn);
        }
        
        $row->fromName = str_replace(' чрез ', ' ' . tr('чрез') . ' ', $row->fromName);
        
        if(trim($row->fromName) && (strtolower(trim($rec->fromName)) != strtolower(trim($rec->fromEml)))) {
            $row->fromEml = $row->fromEml . ' (' . trim($row->fromName) . ')';
        }
                
        if($fields['-list']) {
           // $row->textPart = mb_Substr($row->textPart, 0, 100);
        }
    }
    
    
    /**
     * Връща вербалното предствяна на имейла
     * 
     * @param array $emailsArr
     * 
     * @return string
     */
    protected static function getVerbalEmail($emailsArr)
    {
        // Масив само с имейлите
        $allEmailToArr = array();
        foreach ((array)$emailsArr as $emailArr) {
            $allEmailToArr[] = $emailArr['address'];
        }
        
        // Премахваме нашите имейлите
        $otherAllEmailToArr = email_Inboxes::removeOurEmails($allEmailToArr);
        
        $cRec = email_Accounts::getCorporateAcc();
        $allCorpEmails = array();
        if ($cRec) {
            $allCorpEmails = email_Inboxes::getAllInboxes($cRec->id);
        }
        
        // Отбелязваме, кои имейли са външни
        if ($otherAllEmailToArr) {
            foreach ((array)$emailsArr as $key => $emailArr) {
                if (!$emailArr['address']) continue;
                
                if (array_search($emailArr['address'], $otherAllEmailToArr) !== FALSE) {
                    $emailsArr[$key]['isExternal'] = TRUE;
                } else {
                    $fromDomain = type_Email::domain($emailArr['address']);
                    
                    // Ако няма такъв корпоративен имейл
                    if ($allCorpEmails && !$allCorpEmails[trim($emailArr['address'])]) {
                        $emailsArr[$key]['isWrong'] = TRUE;
                    }
                }
            }
        }
        
        // Вземаме вербалното представяне на имейлите
        $emailRow = email_Mime::emailListToVerbal($emailsArr);
        
        return $emailRow;
    }
    
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $rec
     */
    protected static function calcAllToAndCc($rec)
    {
        // Ако няма хедъри
        if (!$rec->headers) {
            
            expect($rec->emlFile);
            
            // Манипулатора на eml файла
            $fh =  fileman_Files::fetchField($rec->emlFile, 'fileHnd');
            
            // Съдържаниетое
            $rawEmail = fileman_Files::getContent($fh); 
            
            // Инстанция на класа
            $mime = cls::get('email_Mime');
            
            // Парсираме имейла
            $mime->parseAll($rawEmail);
            
            // Вземаме хедърите
            $headersArr = $mime->parts[1]->headersArr;
            
            // Ако няма хедъри, записваме ги
            $nRec = new stdClass();
            $nRec->id = $rec->id;
            $nRec->headers = $headersArr;
            
            $eInc = cls::get('email_Incomings');

            $eInc->save_($nRec, 'headers');
        } else {
            
            // Хедърите ги преобразуваме в масив
            $headersArr = $rec->headers;
        }

        // Парсираме To хедъра
        $allTo  = email_Mime::getHeadersFromArr($headersArr, 'to', '*');
        $toParser = new email_Rfc822Addr();
        $rec->allTo = array();
        $toParser->ParseAddressList($allTo, $rec->allTo);
        
        // Парсираме cc хедъра
        $allCc = email_Mime::getHeadersFromArr($headersArr, 'cc', '*');
        $ccParser = new email_Rfc822Addr();
        $rec->allCc = array();
        $ccParser->ParseAddressList($allCc, $rec->allCc);
     }
    
 
    /**
     * Да сваля имейлите по - крон
     */
    function cron_DownloadEmails()
    {
        $mailInfo = $this->fetchAllAccounts();
        
        return $mailInfo;
    }
    
    
    /**
     * Cron екшън за опресняване на публичните домейни
     */
    function cron_UpdatePublicDomains()
    {
        $domains = static::scanForPublicDomains();
        
        $out = "<li>Открити " . count($domains) . " домейн(а) ... </li>";
        
        $stats = drdata_Domains::resetPublicDomains($domains);
        
        $out .= "<li>Добавени {$stats['added']}, изтрити {$stats['removed']} домейн(а)</li>";
        
        if ($stats['addErrors']) {
            $out .= "<li class=\"error\">Проблем при добавянето на {$stats['addErrors']} домейн(а)!</li>";
        }
        
        if ($stats['removeErrors']) {
            $out .= "<li class=\"error\">Проблем при изтриването на {$stats['removeErrors']} домейн(а)!</li>";
        }
        
        $out .= ""
        . "<h4>Опресняване на публичните домейни<h4>"
        . "<ul>"
        .    $out
        . "</ul>";
        
        return $out;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_UpdatePublicDomains()
    {
        requireRole('admin');
        
        return static::cron_UpdatePublicDomains();
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $rec = new stdClass();
        $rec->systemId = 'DownloadEmails';
        $rec->description = 'Сваляне на имейли в модела';
        $rec->controller = $mvc->className;
        $rec->action = 'DownloadEmails';
        $rec->period = 2;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 100;
        $res .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'UpdatePublicDomains';
        $rec->description = 'Обновяване на публичните домейни';
        $rec->controller = $mvc->className;
        $rec->action = 'UpdatePublicDomains';
        $rec->period = 1440; // 24h
        $rec->offset = rand(120, 180); // от 2 до 3h
        $rec->delay = 0;
        $rec->timeLimit = 100;
        $res .= core_Cron::addOnce($rec);
    }
    
    
    /****************************************************************************************
     *                                                                                      *
     *  ИМПЛЕМЕНТАЦИЯ НА @link doc_DocumentIntf                                             *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $subject = $this->getVerbal($rec, 'subject');
        
        if(!trim($subject)) {
            $subject = '[' . tr('Липсва заглавие') . ']';
        }
        
        $row = new stdClass();
        $row->title = $subject;
        
        if(trim($rec->fromName)) {
            $rec->fromName = str_replace(' чрез ', ' ' . tr('чрез') . ' ', $rec->fromName);
            $row->author = $this->getVerbal($rec, 'fromName');
        } else {
            $row->author = "<small>{$rec->fromEml}</small>";
        }
        
        $row->authorEmail = $rec->fromEml;
        
        $row->state = $rec->state;
        
        $row->recTitle = $rec->subject;
        
        return $row;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function isSpam($rec)
    {
        
        /**
         * @TODO
         */
        
        return FALSE;
    }
    
    
    /**
     * Рутиране на писмо още преди записването му.
     *
     * Тук писмата се рутират при възможност директно в нишката, за която са предназначени.
     * Ако това рутиране пропадне, задейства се метода @see doc_DocumentPlg::on_AfterRoute() и
     * той изпраща писмото в специална папка за несортирани писма. От там по-късно писмата биват
     * рутирани @link email_Router.
     *
     * @param stdClass $rec запис на модела email_Incomings
     */
    public function route_(&$rec)
    {
        // Винаги рутираме по номер на тред
        if(email_Router::doRuleThread($rec)) {
            
            // Добавяме начина на рутиране
            $rec->routeBy = 'thread';
            
            return;
        }
        
        // Първо рутираме по ръчно зададените правила
        if(email_Filters::preroute($rec)) {
            
            // Добавяме начина на рутиране
            $rec->routeBy = 'preroute';
            
            return;
        }
        
        if ($rec->accId) {
            // Извличаме записа на сметката, от която е изтеглено това писмо
            $accRec = email_Accounts::fetch($rec->accId);
        }

        // Ако сметката е с рутиране
        if($accRec && ($accRec->applyRouting == 'yes')) {
        
            // Ако `boxTo` е обща кутия, прилагаме последователно `From`, `Domain`, `Country`
            if($accRec->email == $rec->toBox && $accRec->type != 'single') {
                
                // Ако папката е с рутиране и boxTo е обща кутия, прилагаме `From`
                if(email_Router::doRuleFrom($rec)) {
                    
                    // Добавяме начина на рутиране
                    $rec->routeBy = 'from';
                    
                    return;
                }
                
                // Рутиране по домейn
                if(email_Router::doRuleDomain($rec)) {
                    
                    // Добавяме начина на рутиране
                    $rec->routeBy = 'domain';
                    
                    return;
                }
                
                // Рутиране по място (държава)
                if(email_Router::doRuleCountry($rec)) {
                    
                    // Добавяме начина на рутиране
                    $rec->routeBy = 'country';
                    
                    return;
                }
                
            } else {
                
                // Ако `boxTo` е частна кутия, то прилагаме `FromTo`
                if(email_Router::doRuleFromTo($rec)) {
                    
                    // Добавяме начина на рутиране
                    $rec->routeBy = 'fromTo';
                    
                    return;
                }
            }
        }
        
        // Накрая безусловно вкарваме в кутията на `toBox`
        email_Router::doRuleToBox($rec); 
        
        // Добавяме начина на рутиране
        $rec->routeBy = 'toBox';
        
        expect($rec->folderId);
    }


    static function isCommonToBox($rec)
    {
        $accRec = email_Accounts::fetch($rec->accId);
        
        $isCommon = ($accRec->email == $rec->toBox && $accRec->type != 'single');

        return $isCommon;
    }
    
    
    
    /**
     * Преди вкарване на запис в модела
     */
    static function on_BeforeSave($mvc, $id, &$rec) {
        //При сваляне на имейл-а, състоянието е затворено
        
        if (!$rec->id) {
            $rec->state = 'closed';
            $rec->_isNew = TRUE;
        }
    }


    /**
     * Извиква се след вкарване на запис в таблицата на модела
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        static::needFields($rec, 'fromEml, toBox, date, containerId,threadId, accId');
        
        if($rec->containerId && $rec->folderId && $rec->fromEml && $rec->toBox) {
            if ($rec->state == 'rejected') {
                $mvc->removeRouterRules($rec);
            } elseif (($rec->routeBy != 'thread') && ($rec->routeBy != 'preroute')) {
                // Ако рутираме по нишка или потребителски филтър да не се създават правила
                $mvc->makeRouterRules($rec);
            }
        }
    }
    
    
    /**
     * След изтриване на записи на модела
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param core_Query $query
     */
    static function on_AfterDelete($mvc, &$res, $query)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            $mvc->removeRouterRules($rec);
        }
    }
    
    
    /**
     * Зарежда при нужда полета на зададен запис от модела.
     *
     * @param stdClass $rec запис на модела; трябва да има зададен поне първ. ключ ($rec->id)
     * @param mixed $fields полетата, които са нужни; ако ги няма в записа - зарежда ги от БД
     *
     * @TODO това е метод от нивото на fetch, така че може да се изнесе в класа core_Mvc
     */
    static function needFields($rec, $fields)
    {
        expect($rec->id);
        
        $fields = arr::make($fields);
        $missing = array();
        
        foreach ($fields as $f) {
            if (!isset($rec->{$f})) {
                $missing[$f] = $f;
            }
        }
        
        if (count($missing) > 0) {
            $savedRec = static::fetch($rec->id, $missing);
            
            foreach ($missing as $f) {
                $rec->{$f} = $savedRec->{$f};
            }
        }
        
        return $rec;
    }
    
    
    /**
     * Създава правила за рутиране на базата на това писмо
     *
     * За обновяване на правилата след всеки запис на писмо се използва този метод.
     *
     * @param stdClass $rec
     */
    static function makeRouterRules($rec)
    {
        static::makeFromToRule($rec);
        static::makeFromRule($rec);
        static::makeDomainRule($rec);
    }
    
    
    /**
     * Премахва всички правила за рутиране, създадени поради това писмо.
     *
     * В добавка създава правила на базата на последните 3 писма от същия изпращач.
     *
     * @param stdClass $rec
     */
    static function removeRouterRules($rec)
    {
        // Премахване на правилата
        email_Router::removeRules('document', $rec->containerId);
        
        //
        // Създаване на правила на базата на последните 3 писма от същия изпращач
        //
        
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->where("#fromEml = '{$rec->fromEml}' AND #state != 'rejected'");
        $query->orderBy('createdOn', 'DESC');
        $query->limit(3);     // 3 писма
        while ($mrec = $query->fetch()) {
            static::makeRouterRules($mrec);
        }
    }
    
    
    /**
     * Създаване на правило от тип `FromTo` - само ако получателя не е общ.
     *
     * @param stdClass $rec
     * @param int $priority
     */
    static function makeFromToRule($rec)
    {
        if (!static::isCommonToBox($rec)) {
            $key = email_Router::getRoutingKey($rec->fromEml, $rec->toBox, email_Router::RuleFromTo);
            
            // Най-висок приоритет, нарастващ с времето
            $priority = email_Router::dateToPriority($rec->date, 'high', 'asc');
            
            email_Router::saveRule(
                (object)array(
                    'type'       => email_Router::RuleFromTo,
                    'key'        => $key,
                    'priority'   => $priority,
                    'objectType' => 'document',
                    'objectId'   => $rec->containerId
                )
            );
        }
    }
    
    
    /**
     * Създаване на правило от тип `From` - винаги
     *
     * @param stdClass $rec
     * @param int $priority
     */
    static function makeFromRule($rec)
    {
        // Най-висок приоритет, нарастващ с времето
        $priority = email_Router::dateToPriority($rec->date, 'high', 'asc');
        
        email_Router::saveRule(
            (object)array(
                'type'       => email_Router::RuleFrom,
                'key'        => email_Router::getRoutingKey($rec->fromEml, NULL, email_Router::RuleFrom),
                'priority'   => $priority,
                'objectType' => 'document',
                'objectId'   => $rec->containerId
            )
        );
    }
    
    
    /**
     * Създаване на правило от тип `Domain` - ако изпращача не е от пуб. домейн и получателя е общ.
     *
     * @param stdClass $rec
     * @param int $priority
     */
    static function makeDomainRule($rec)
    {
        if (static::isCommonToBox($rec) && ($key = email_Router::getRoutingKey($rec->fromEml, NULL, email_Router::RuleDomain))) {
            
            // До тук: получателя е общ и домейна не е публичен (иначе нямаше да има ключ).
            
            // Остава да проверим дали папката е на визитка. Иначе казано, дали корицата на
            // папката поддържа интерфейс `crm_ContragentAccRegIntf`
            
            if ($coverClassId = doc_Folders::fetchField($rec->folderId, 'coverClass')) {
                $isContragent = cls::haveInterface('crm_ContragentAccRegIntf', $coverClassId);
            }
            
            if ($isContragent) {
                // Всички условия за добавяне на `Domain` правилото са налични.
                
                // Най-висок приоритет, нарастващ с времето
                $priority = email_Router::dateToPriority($rec->date, 'high', 'asc');
                
                email_Router::saveRule(
                    (object)array(
                        'type'       => email_Router::RuleDomain,
                        'key'        => $key,
                        'priority'   => $priority,
                        'objectType' => 'document',
                        'objectId'   => $rec->containerId
                    )
                );
            }
        }
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща данните за адресата
     */
    static function getContragentData($id)
    {
        //Данните за имейл-а
        $msg = email_Incomings::fetch($id);
        
        $addrParse = cls::get('drdata_Address');
        
        Mode::push('text', 'plain');
        Mode::push('ClearFormat', TRUE);
        $rt = new type_Richtext();
        $textPart = $rt->toVerbal($msg->textPart);
        Mode::pop('ClearFormat');
        Mode::pop('text');
        
        $contragentData = $addrParse->extractContact($textPart);
        
        // Ако няма хедъри
        // За съвместимост със стар код
        if (!$msg->headers) {
            
            // Манипулатора на eml файла
            $fh =  fileman_Files::fetchField($msg->emlFile, 'fileHnd');
            
            // Съдържаниетое
            $rawEmail = fileman_Files::getContent($fh); 
            
            // Инстанция на класа
            $mime = cls::get('email_Mime');
            
            // Парсираме имейла
            $mime->parseAll($rawEmail);
            
            // Вземаме хедърите
            $headersArr = $mime->parts[1]->headersArr;
            
            // Ако няма хедъри, записваме ги
            $nRec = new stdClass();
            $nRec->id = $msg->id;
            $nRec->headers = $headersArr;
            
            $eInc = cls::get('email_Incomings');

            $eInc->save_($nRec, 'headers');
        } else {
            
            // Хедърите ги преобразуваме в масив
            $headersArr = $msg->headers;
        }
        
        // Вземамем всички reply-to имейли от хедърите
        $contragentData->replyToEmail = email_Mime::getHeadersFromArr($headersArr, 'reply-to', '*');
        
        // Вземамем всички cc имейли от хедърите
        $contragentData->ccEmail = email_Mime::getHeadersFromArr($headersArr, 'cc', '*');
        
        // Вземамем всички tp имейли от хедърите
        $contragentData->toEmail = email_Mime::getHeadersFromArr($headersArr, 'to', '*');   

        // Вземаме само имейла на изпращача
        $contragentData->email = email_Mime::getAllEmailsFromStr($msg->fromEml);
        
        // Държавата
        $contragentData->countryId = $msg->country;
        
        // Името на класа
        $coverClass = strtolower(doc_Folders::fetchCoverClassName($msg->folderId));
        
        // Ако е корицата е контрагент
        if ($coverClass == 'crm_companies' || $coverClass == 'crm_persons') {
            
            // Вземаме id на ковъра
            $coverId = doc_Folders::fetchCoverId($msg->folderId);   

            // Вземаме контрагент данните на ковъра
            $coverContragent = $coverClass::getContragentData($coverId);
            
            // Груповите имейли
            $contragentData->coverGroupEmails = $coverContragent->groupEmails;
        }

        // Добавяме всички имейли в масив
        $allEmailsArr = array();
        $allEmailsArr['email'] = $contragentData->email;
        $allEmailsArr['replyToEmail'] = $contragentData->replyToEmail;
        $allEmailsArr['toEmail'] = $contragentData->toEmail;
        $allEmailsArr['ccEmail'] = $contragentData->ccEmail;
        $allEmailsArr['buzEmail'] = $contragentData->coverGroupEmails;
        
        // Обхождаме масива
        foreach ($allEmailsArr as $email) {
            
            // Ако няма запис прескачаме
            if (!trim($email)) continue;
            
            // Ако има запис, добавяме към стринга
            $allEmails .= ($allEmails) ? ', ' . $email : $email;
        }
        
        // Вземаме груповите имейли
        $contragentData->groupEmails = email_Mime::getAllEmailsFromStr($allEmails, TRUE);

        // Добавяме toEml и toBox
        $contragentData->toEml = $msg->toEml;
        $contragentData->toBox = $msg->toBox;
        
        return $contragentData;
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото наимей по подразбиране
     */
    static function getDefaultEmailBody($id, $forward)
    {
        $mvc = cls::get('email_Incomings');
        
        $text = email_Outgoings::prepareDefaultEmailBodyText($mvc, $id, 'date', $forward);
        
        return $text;
    }
    
    
    /**
     * Намира всички домейни, от които има изпратени писма, намиращи се в различни фирмени папки
     *
     * @return array масив с ключове - домейни (и стойности TRUE)
     */
    static function scanForPublicDomains()
    {
        // Извличаме ид на корица на фирмените папки
        $crmCompaniesClassId = core_Classes::getId('crm_Companies');
        $crmPersonsClassId = core_Classes::getId('crm_Persons');
        
        // Построяваме заявка, извличаща всички писма, които са във фирмена папка.
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->EXT('coverClass', 'doc_Folders', 'externalKey=folderId');
        $query->where("#coverClass = {$crmCompaniesClassId}");
        $query->orWhere("#coverClass = {$crmPersonsClassId}");
        $query->show('fromEml, folderId, coverClass');
        
        $domains = array();
        $results  = array();
        
        while ($rec = $query->fetch()) {
            
            $fromDomain = type_Email::domain($rec->fromEml);
            $domains[$rec->coverClass][$fromDomain][$rec->folderId] = TRUE;
            
            if (count($domains[$rec->coverClass][$fromDomain]) > 1) {
                // От $fromDomain има поне 2 писма, които са в различни фирмени папки
                $results[$fromDomain] = TRUE;
            }
        }
        
        return $results;
    }
    
    
    /**
     * Реализация  на интерфейсния метод ::getThreadState()
     */
    static function getThreadState($id)
    {
        return 'opened';
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Update()
    {
        requireRole('admin');
        
        set_time_limit(3600);
        $query = self::getQuery();
        
        $i = 0;
        
        while($rec = $query->fetch()) {
            $i++;
            
            if($i % 100 == 1) {
                $this->log("Update email $i", NULL, 7);
            }
            self::save($rec);
        }
    }

    
    /**
     * Добавя бутони
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        // Ако имаме права за single
        if ($mvc->haveRightFor('single', $data->rec)) {
            
            if ($data->rec->emlFile) {
                
                // Име на бутона
                if ($data->rec->htmlFile) {
                    $buttonName = 'Изглед';
                } else {
                    $buttonName = 'Детайли';
                }
                
                // Добавяме бутон за разглеждане не EML файла
                $data->toolbar->addBtn($buttonName, array(
                        'fileman_Files',
                        'single',
                        'id' => fileman_Files::fetchField($data->rec->emlFile, 'fileHnd'),
                    ),NULL,
                array('order'=>'21', 'ef_icon'=>'img/16/file_extension_eml.png', 'title'=>'Преглед на различните части на имейла'));    
            }
            
            // Ако е оттеглен, да не се препраща
            if ($data->rec->state != 'rejected') {
                
                // Добавяме бутон за препращане на имейла
                $data->toolbar->addBtn('Препращане', array(
                        'email_Outgoings',
                        'forward',
                        $data->rec->containerId,
                        'ret_url' => TRUE,
                    ), NULL, array('order'=>'20', 'row'=>'2', 'ef_icon'=>'img/16/email_forward.png', 'title'=>'Препращане на имейла')
                );
            }
        }
    }
    
    
    /**
     * Връща EML файл при генериране на възможности разширения за прикачване
     */
    function on_BeforeGetTypeConvertingsByClass($mvc, &$res, $id)
    {
        //Превръщаме $res в масив
        $res = (array)$res;
        
        // Вземаме манипулатора на файла
        $name = $mvc->getHandle($id);
        
        //Името на файла е с големи букви, както са документите
        $name = strtoupper($name) . '.eml';
        
        // Ако размера е над допустимите за изпращане, да не се добавя автоматично
        $attach = 'on';
        if ($mvc->checkSizeForAttach($id) === FALSE) {
            $attach = 'off';
        }
        
        //Задаваме полето за избор, да е избран по подразбиране
        $res[$name] = $attach;
    }
    
    
    /**
     * Проверява дали документа може да се праща по имейл
     * В зависимост от големината на EML файла
     * 
     * @param unknown_type $mvc
     * @param unknown_type $res
     * @param unknown_type $id
     */
    function on_BeforeCheckSizeForAttach($mvc, &$res, $id)
    {
        // Записа
        $rec = $mvc->fetch($id);
        $emlFile = $rec->emlFile;
        
        if (!$emlFile) return ;
        
        // Записа за EML файла
        $fRec = fileman_Files::fetch($emlFile);
        
        if (!$fRec || !$fRec->dataId) return ;
        
        // Данните за файла
        $data = fileman_Data::fetch($fRec->dataId);
        
        $sizeArr = array();
        $sizeArr[$fRec->fileHnd] = $data->fileLen;
        
        // Проверавяме дали размера е в допустимите граници
        $res = $mvc->checkMaxAttachedSize($sizeArr);
    }
    
    
    /**
     * Добавяме манупулаторите на файловете с разширение .eml
     * 
     * @param core_Mvc $mvc
     * @param array $res масив с манипулатор на файл (@see fileman)
     * @param int $id първичен ключ на документа
     * @param string $type формат, в който да се генерира съдържанието на док.
     * @param string $fileName име на файл, в който да се запише резултата
     */
    static function on_BeforeConvertTo($mvc, &$res, $id, $type, $fileName = NULL)
    {
        // Преобразуваме в масив
        $res = (array)$res;
        
        switch (strtolower($type)) {
            case 'eml':
        
                // Вземаме id' то на EML файла
                $emlFileId = $mvc->fetchField($id, 'emlFile');
                
                // Манипулатора на файла
                $fh = fileman_Files::fetchField($emlFileId, 'fileHnd');
                
                // Добавяме в масива
                if ($fh) {
                    $res[$fh] = $fh;
                } 
                  
            break;
        }
    }

    function on_BeforeGetDocumentSize($mvc, &$res, $id, $type)
    {
        switch (strtolower($type)) {
            case 'eml':
        
                // Вземаме id' то на EML файла
                $emlFileId = $mvc->fetchField($id, 'emlFile');
                
                // Манипулатора на файла
                $dataId = fileman_Files::fetchField($emlFileId, 'dataId');
                
                $res = fileman_Data::fetchField($dataId, 'fileLen');
                  
            break;
        }
    }
    
	
	/**
	 * Връща прикачените файлове
     * 
     * @param object $rec - Запис
     */
    function getLinkedFiles($rec)
    {
        // Ако не е обект
        if (!is_object($rec)) {
             
            // Вземаме записите за файла
            $rec = $this->fetch($rec);    
        }
         
        // Превръщаме в масив
        $filesArr = keylist::toArray($rec->files);
         
         // Ако има HTML файл
         if ($rec->htmlFile) {
             
             // Добавяме го към файловете
             $filesArr[$rec->htmlFile] = $rec->htmlFile;
         }
         
         // Добавяме EML файла, към файловете
         $filesArr[$rec->emlFile] = $rec->emlFile;
         
         // Обхождаме всички файлове
         foreach ($filesArr as $fileId) {
             
            // Вземаме записите за файловете
            $fRec = fileman_Files::fetch($fileId);
             
            // Създаваме масив с прикачените файлове
            $fhArr[$fRec->fileHnd] = $fRec->name;
        }
         
        return $fhArr;
    }

     
    /**
     * Връща иконата на документа
     */
    function getIcon_($id)
    {
        $rec = self::fetch($id);

        $files = keylist::toArray($rec->files);
 
        if($rec->htmlFile) {
            unset($files[$rec->htmlFile]);
        }
 
        if(count($files)) {
             
            return "img/16/email-attach.png";
        }
    }
    
    
    /**
     * Разширява query-то в doc_DocumentPlg, като добавя и имейла от който е получен
     * 
     * @param email_Incomings $mvc
     * @param core_Query $query
     * @param integer $folderId
     * @param array $params
     * 
     * @return core_Query
     */
    public static function on_AfterGetSameFirstDocumentsQuery($mvc, &$query, $folderId, $params=array())
    {
        if (!$query) {
            $query = $mvc->getQuery();
        }
        
        if ($params['fromEml']) {
            $query->where(array("LOWER(#fromEml) = '[#1#]'", mb_strtolower($params['fromEml'])));
        }
        
        return $query;
    }
    
    
    /**
     * 
     * 
     * @param integer $id
     */
    public function getLangFromRec($id)
    {
        if (!$id) return ;
        
        $rec = $this->fetch($id);
        
        return $rec->lg;
    }
}
