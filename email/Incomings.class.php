<?php 

/**
 * Входящи писма
 *
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class email_Incomings extends core_Master
{
    /**
     * Масив с IP-та, които се приемат за рискови и контрагента, ако не е от същата държава
     * Трбва да дава предупреждение за измама
     *
     * GH - Ghana
     * NG - Nigeria
     * VN - Viet Nam
     * SN - Senegal
     * SL - Sierra Leone
     */
    public static $riskIpArr = array('GH', 'NG', 'VN', 'SN', 'SL');

    
    /**
     * Максимален брой файлове от имейл, които да се сканират за баркод
     */
    protected static $maxScanFileCnt = 10;
    
    
    /**
     * Максимален брой баркодове, които да се проверяват
     */
    protected static $maxScanBarcodeCnt = 10;
    
    
    /**
     * Дали се очаква в документа да има други документи
     */
    public $expectDocs = false;
    
    
    /**
     * Шаблон (ET) за заглавие на перо
     */
    public $recTitleTpl = '[#subject#]';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = true;
    
    
    /**
     * Текста бутона за създаване на имейли
     */
    public $emailButtonText = 'Отговор';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'email_Messages';
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Входящи имейли';
    
    
    /**
     * @todo Чака за документация...
     */
    public $singleTitle = 'Входящ имейл';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    public $fetchFieldsBeforeDelete = 'id, containerId, fromEml';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'email_Wrapper, doc_DocumentPlg, 
    				plg_RowTools2, plg_Printing, email_plg_Document, 
    				doc_EmailCreatePlg, plg_Sorting, bgerp_plg_Blank';
    
    
    /**
     * Сортиране по подразбиране по низходяща дата
     */
    public $defaultSorting = 'date=down';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'email/tpl/SingleLayoutMessages.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    public $singleIcon = 'img/16/email.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Msg';
    
    
    /**
     * Първоначално състояние на документа
     */
    public $firstState = 'closed';
    
    
    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    public $rowToolsSingleField = 'subject';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,subject,createdOn=Дата,fromEml=От,toBox=До,accId,routeBy,uid,country';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'subject, fromEml, fromName, textPart, files';
    
    
    /**
     * Дали да може да се изтрива документа от документната система
     *
     * @see doc_Threads
     */
    public $deleteThreadAndDoc = true;
    

    /**
     * На участъци от по колко записа да се бекъпва?
     */
    public $backupMaxRows = 50000;
    
    
    /**
     * Кои полета да определят рзличността при backup
     */
    public $backupDiffFields = 'modifiedOn';
    

    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('accId', 'key(mvc=email_Accounts,select=email, allowEmpty)', 'caption=Имейл акаунт, autoFilter');
        $this->FLD('subject', 'varchar(utf8mb4=utf8)', 'caption=Тема, tdClass=emailListTitle');
        $this->FLD('fromEml', 'email', 'caption=От->Имейл');
        $this->FLD('fromName', 'varchar', 'caption=От->Име');
        
        // Първия наш имейл от MIME-хедъра "To:"
        $this->FLD('toEml', 'email(link=no)', 'caption=До->Имейл');
        
        // Наша пощенска кутия (email_Inboxes) до която е адресирано писмото.
        // Това поле се взема предвид при рутиране и създаване на правила за рутиране.
        $this->FLD('toBox', 'email(link=no)', 'caption=До->Кутия');
        
        $this->FLD('headers', 'blob(serialize,compress)', 'caption=Хедъри');
        $this->FLD('textPart', 'richtext(hndToLink=no, nickToLink=no,bucket=Postings,oembed=none)', 'caption=Текстова част');
        $this->FLD('spam', 'int', 'caption=Спам');
        $this->FLD('lg', 'varchar', 'caption=Език');
        $this->FLD('date', 'datetime(format=smartTime)', 'caption=Дата');
        $this->FLD('hash', 'varchar(32)', 'caption=Кеш');
        $this->FLD('country', 'key(mvc=drdata_countries, select=commonName, selectBg=commonNameBg, allowEmpty)', 'caption=Държава, autoFilter');
        $this->FLD('fromIp', 'ip', 'caption=IP');
        $this->FLD('files', 'keylist(mvc=fileman_Files)', 'caption=Файлове, input=none');
        $this->FLD('emlFile', 'key(mvc=fileman_Files)', 'caption=eml файл, input=none');
        $this->FLD('htmlFile', 'key(mvc=fileman_Files)', 'caption=html файл, input=none');
        $this->FLD('uid', 'int', 'caption=Imap UID');
        
        $this->FLD('routeBy', 'enum(thread, file, preroute, from, fromTo, domain, toBox, country)', 'caption=Рутиране');
        
        $this->FLD('userInboxes', 'keylist(mvc=email_Inboxes, select=email)', 'caption=Имейли на потребители');
        
        $this->FLD('toAndCc', 'blob(serialize,compress)', 'caption=Имейл до');
        
        $this->FLD('spamScore', 'double', 'caption=Спам рейтинг');
        
        $this->setDbUnique('hash');
        $this->setDbIndex('fromEml');
        
        $this->setDbIndex('modifiedOn');
    }
    
    
    /**
     * Взема записите от пощенската кутия и ги вкарва в модела
     *
     *
     * @return string $logMsg - Съобщение с броя на новите имейли
     */
    public function fetchAllAccounts($time = 0)
    {
        $conf = core_Packs::getConfig('email');
        
        $FileSize = cls::get('fileman_FileSize');
        $memoryLimit = $FileSize->fromVerbal(ini_get('memory_limit'));
        if ($conf->EMAIL_MAX_ALLOWED_MEMORY > $memoryLimit) {
            // Задаваме максималната използваема памет
            ini_set('memory_limit', $conf->EMAIL_MAX_ALLOWED_MEMORY);
        }
        
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
            if (Request::get('forced') != 'yes' && $time > 0) {
                if (!$accRec->fetchingPeriod) {
                    $accRec->fetchingPeriod = 60;
                }
                $period = round($accRec->fetchingPeriod / 30) * 30;
                if ($period > 0 && ($time % $period) > 0) {
                    continue;
                }
            }
            
            self::fetchAccount($accRec, $deadline, $maxFetchingTime);
        }
    }
    
    
    /**
     * Извлича писмата от посочената сметка
     */
    public function fetchAccount($accRec, $deadline, $maxFetchingTime)
    {
        // Заключваме тегленето от тази пощенска кутия
        $lockKey = 'Inbox:' . $accRec->id;
        
        if (!core_Locks::get($lockKey, $maxFetchingTime, 1)) {
            email_Accounts::logWarning('Кутията е заключена от друг процес', $accRec->id, 7);
            
            return;
        }
        
        // Връзка по IMAP към сървъра на посочената сметка
        $imapConn = cls::get('email_Imap', array('accRec' => $accRec));

        // Логването и генериране на съобщение при грешка е винаги в контролерната част
        if ($imapConn->connect() === false) {
            $imapLastErr = $imapConn->getLastError();
            $errMsg = 'Грешка при свързване';
            email_Accounts::logWarning("{$errMsg}: {$imapLastErr}", $accRec->id, 14);
            
            return;
        }
        
        // Получаваме броя на писмата в INBOX папката
        $numMsg = $imapConn->getStatistic('messages');

        // Масив за броячите за различните получени писма
        $statusSum = array('Total' => $numMsg);
        
        // Номер на първото не-свалено писмо
        $firstUnreadMsgNo = $this->getFirstUnreadMsgNo($imapConn, $numMsg);

        if (!isset($firstUnreadMsgNo) && $imapConn->accRec->protocol == 'pop3') {
            $firstUnreadMsgNo = $this->getFirstUnreadMsgNo($imapConn, 1);
        }

        $startTime = time();
        
        $doExpunge = false;

        if ($firstUnreadMsgNo > 0) {
            $statusSum['first'] = $firstUnreadMsgNo;
            
            // Правим цикъл по всички съобщения в пощенската кутия
            // Цикълът може да прекъсне, ако надвишим максималното време за сваляне на писма
            for ($i = $firstUnreadMsgNo; $i <= $numMsg && (($deadline - 5 > time()) || ($i == $firstUnreadMsgNo)); $i++) {
                $status = self::processMsg($i, $imapConn, $accRec, $doExpunge);
                $statusSum[$status]++;
            }
        }
        
        // Максимално големият номер за проверка
        $maxMsgNo = max($firstUnreadMsgNo, $numMsg);
        
        
        // Изтриване на старите имейли
        $cacheKey = 'ndt_' . $accRec->email;
        $nextDeleteTime = core_Cache::get('email_Incomings', $cacheKey);
        $now = dt::now();
        
        if ($numMsg > 0 && $accRec->deleteAfterPeriod > 0 && (!$nextDeleteTime || $nextDeleteTime <= $now)) {
            $nextDeleteTime = dt::addSecs($accRec->deleteAfterPeriod);
            for ($msgNo = 1; $msgNo < $maxMsgNo && ($deadline - 1 > time()); $msgNo++) {
                $headers = $imapConn->getHeaders($msgNo);
                
                $fRec = email_Fingerprints::fetchByHeaders($headers);
                
                if (!$fRec) {
                    // Ако писмото липсва в хеша на свалетите писма - правим нов опит за свалянето му
                    $status = $this->processMsg($msgNo, $imapConn, $accRec, $doExpunge);
                    $statusSum[$status]++;
                    
                    continue;
                }
                
                $deleteTime = dt::addSecs($accRec->deleteAfterPeriod, $fRec->downloadedOn);
                
                if ($deleteTime < $now) {
                    $imapConn->delete($msgNo);
                    email_Accounts::logInfo("Изтриване {$msgNo} от {$maxMsgNo}", $accRec->id);
                    $statusSum['delete']++;
                    $doExpunge = true;
                } else {
                    $nextDeleteTime = min($deleteTime, $nextDeleteTime);
                }
            }
            
            // Колко минути да се съхранява в кеша информацията за следващото време за изтриване?
            $keepMinutes = (dt::mysql2Timestamp($nextDeleteTime) - dt::mysql2Timestamp(dt::now())) / 60;
            
            log_System::add('email_Incomings', 'Зададено следващо изтриване на писма след ' . $keepMinutes . ' min за ' . $accRec->email);
            if ($keepMinutes > 1) {
                core_Cache::set('email_Incomings', $cacheKey, $nextDeleteTime, $keepMinutes, array('email_Accounts'));
            }
        }
        
        // Изтриваме предварително маркираните писма
        if ($doExpunge) {
            $imapConn->expunge();
        }
        
        // Затваряме IMAP връзката
        $imapConn->close();
        
        // Махаме заключването от кутията
        core_Locks::release($lockKey);
        
        // Общото времето за процесиране на емейл-сметката
        $duration = time() - $startTime;
        
        // Генерираме и записваме лог съобщение
        $logMsg = "{$accRec->email} (${duration} s)";
        
        // Обхождаме всички статуси
        foreach ($statusSum as $status => $cnt) {
            $status = ucfirst($status);
            $logMsg .= "; {$status}={$cnt}";
        }
        
        // Показваме стринга
        echo "<h3> ${logMsg} </h3>";
        
        email_Accounts::logInfo($logMsg, $accRec->id);
    }
    
    
    /**
     * Извлича посоченото съобщение по POP3 или IMAP протокол
     */
    public function processMsg($i, $imapConn, $accRec, $doExpunge)
    {
        try {
            $status = $this->fetchEmail($imapConn, $i);
        } catch (core_Exception_Expect $exp) {
            $status = 'fetching error';
        }
        
        //if(($i % 100) == 1 || ( ($i - $firstUnreadMsgNo) < 100)) {
        //    email_Accounts::logInfo("Fetching message {$i}", $accRec->id);
        //}
        
        if ($status != 'error' && $status != 'fetching error') {
            // Изтриване на писмото, ако сметката е настроена така
            if ($accRec->deleteAfterPeriod === '0') {
                $imapConn->delete($i);
                
                $doExpunge = true;
            }
            
            // Дали да отмаркираме съобщението, че е прочетено?
            if ($accRec->imapFlag == 'unseen') {
                $imapConn->unmarkSeen($i);
                
                // email_Accounts::logInfo("Отмаркиране $i", $accRec->id);
                $doExpunge = true;
            } elseif ($accRec->imapFlag == 'seen') {
                $imapConn->markSeen($i);
                
                // email_Accounts::logInfo("Маркиране $i", $accRec->id);
                $doExpunge = true;
            }
        }
        
        return $status;
    }
    
    
    /**
     * Извлича посоченото писмо от отворената връзка
     */
    public function fetchEmail($imapConn, $msgNo)
    {
        try {
            // Извличаме хедърите и проверяваме за дублиране
            $headers = $imapConn->getHeaders($msgNo);
            
            if (email_Fingerprints::fetchByHeaders($headers)) {
                
                return 'duplicated';
            }
            
            // Кой е UID на писмото?
            $uid = $imapConn->getUid($msgNo);
            
            // Записа на имейл сметката, от където се тегли
            $accId = $imapConn->accRec->id;
            
            // Извличаме цялото писмо
            $rawEmail = $imapConn->getEml($msgNo);
            
            // Създава MIME обект
            $mime = cls::get('email_Mime');
            
            try {
                // Парсира съдържанието на писмото
                $mime->parseAll($rawEmail);
                
                // Вземаме хедърите, този път от самото писмо
                $headersMime = $mime->getHeadersStr();
                
                // Отново правим проверка дали писмото е сваляно
                if ($fRec = email_Fingerprints::fetchByHeaders($headersMime)) {
                    
                    // Записваме статуса на сваленото писмо (service, misformatted, normal);
                    email_Fingerprints::setStatus($headers, $fRec->status, $accId, $uid);
                    
                    return 'duplicated';
                }
                
                $conf = core_Packs::getConfig('email');
                
                // Очакваме текстовата част да е под допустимия максимум
                expect(mb_strlen($mime->textPart) < $conf->EMAIL_MAX_TEXT_LEN);
            } catch (core_exception_Expect $exp) {
                // Не-парсируемо
                email_Unparsable::add($rawEmail, $accId, $uid);
                $status = 'misformatted';
            }
            
            // Ако писмото не е с лошо форматиране
            if ($status != 'misformatted') {
                // Пробваме дали това не е служебно писмо
                // Ако не е служебно, пробваме дали не е SPAM
                // Ако не е нищо от горните, записваме писмото в този модел
                
                $clsArr = core_Classes::getOptionsByInterface('email_AutomaticIntf');
                
                $clsInstArr = array();
                foreach ($clsArr as $clsName) {
                    $clsInstArr[$clsName] = cls::getInterface('email_AutomaticIntf', $clsName);
                    $arrWeight[$clsName] = (int)$clsInstArr[$clsName]->class->weight;
                }

                if (!empty($arrWeight)) {
                    arsort($arrWeight);
                    foreach ($arrWeight as $clsName => $dummy) {
                        try {
                            $status = $clsInstArr[$clsName]->process($mime, $accId, $uid);
                        } catch (core_exception_Expect $exp) {
                            reportException($exp);
                            continue;
                        }
                        
                        if (isset($status)) {
                            break;
                        }
                    }
                }

                if (!isset($status) || is_array($status)) {
                    $this->process($mime, $accId, $uid, $status['preroute'], $status['spam']);
                    $status = 'incoming';
                }
            }
        } catch (core_exception_Expect $exp) {
            // Обща грешка
            $status = 'error';
            reportException($exp);
        }
        
        $status = strtolower($status);
        
        // Записваме в отпечатъка на това писмо, както и статуса му на сваляне
        if (!in_array($status, array('error'))) {
            // Записваме статуса на сваленото писмо (service, misformatted, normal);
            email_Fingerprints::setStatus($headers, $status, $accId, $uid);
        }

        return $status;
    }
    
    
    /**
     * Подготвя, записва и рутира зададеното писмо
     */
    public function process($mime, $accId, $uid, $prerouteRecArr = array(), $spamDataArr = array())
    {
        $mime->saveFiles();
        
        $rec = new stdClass();
        
        // Декодираме и запазваме събджекта на писмото
        $rec->subject = $mime->getSubject();
        
        $rec->subject = str::limitLen($rec->subject, 245, 20, '.....');
        
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
        $rec->emlFile = $mime->getEmlFile();
        
        // Задаваме текстовата част
        $rec->textPart = $mime->textPart;
        
        // Запазване на допълнителни MIME-хедъри за нуждите на рутирането
        $rec->inReplyTo = $mime->getHeader('In-Reply-To');
        
        // От коя сметка е получено писмото
        $rec->accId = $accId;
        $rec->uid = $uid;
        
        // Добавяме хедърите
        $headersStr = $mime->getHeadersStr();
        
        // Преобразуваме в масив с хедъри и сериализираме
        $rec->headers = $mime->parseHeaders($headersStr);

        if (!$spamDataArr && $spamDataArr['checkSpam'] !== false) {
            $rec->spamScore = email_Spam::getSpamScore($rec->headers, true, $mime, $rec);
        } else {
            $rec->spamScore = 0;
        }

        if ($prerouteRecArr) {
            $rec->_prerouteRecArr = $prerouteRecArr;
        }

        // Записваме (и автоматично рутираме) писмото
        $saved = $this->save($rec);
        
        return $saved;
    }
    
    
    /**
     * Връща поредния номер на първото не-четено писмо
     *
     * @param email_Imap $imapConn Обект с отворена IMAP/POP3 връзка
     * @param int        $maxMsgNo Брой на съобщенията в кутията
     */
    protected function getFirstUnreadMsgNo($imapConn, $maxMsgNo)
    {
        // Няма никакви съобщения за сваляне?
        if (!($maxMsgNo > 0)) {
            
            return;
        }
        
        if ($imapConn->accRec->protocol == 'imap') {
            $query = email_Fingerprints::getQuery();
            $query->XPR('maxUid', 'int', 'max(#uid)');
            $query->show('maxUid');
            $maxRec = $query->fetch("#accountId = {$imapConn->accRec->id}");
        }
        
        $maxReadMsgNo = 0;
        
        if ($maxRec->maxUid) {
            $maxReadMsgNo = $imapConn->getMsgNo($maxRec->maxUid);
        }
        
        if ($maxReadMsgNo === 0) {
            // Горен указател
            $t = $maxMsgNo;

            $i = 1;
            
            // Долен указател
            $b = max(1, $maxMsgNo - $i);
            
            $isDownT = $this->isDownloaded($imapConn, $t);

            // Дали всички съобщения са прочетени?
            if ($isDownT) {
                
                return;
            }
            
            $isDownB = $this->isDownloaded($imapConn, $b);
            
            do {
                // Ако и двете не са свалени; Изпълнява се няколко пъти последователно в началото
                if (!$isDownB && !$isDownT) {
                    if ($t == $b) {
                        
                        return $t;
                    }
                    $t = $b;
                    $i = $i * 2;
                    $b = max(1, $maxMsgNo - $i);
                    $isDownB = $this->isDownloaded($imapConn, $b);
                } elseif ($isDownB && !$isDownT) {
                    // Условие, при което $t е първото не-свалено писмо
                    if ($t - $b == 1) {
                        
                        return $t;
                    }
                    $m = round(($t + $b) / 2);
                    $isDownM = $this->isDownloaded($imapConn, $m);
                    if ($isDownM) {
                        $b = $m;
                    } else {
                        $t = $m;
                    }
                }
                
                $change = ($t != $tLast || $b != $bLast);
                
                $tLast = $t;
                
                $bLast = $b;
            } while ($change);
        } else {
            if (($maxReadMsgNo === false) || ($maxReadMsgNo >= $maxMsgNo)) {
                $maxReadMsgNo = null;
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
        $accId = $imapConn->accRec->id;
        
        email_Accounts::logInfo("Check Down: ${msgNum}", $accId);
        
        // Номерата почват от 1
        if ($msgNum < 1) {
            email_Accounts::logInfo("TRUE: ${msgNum} < 1", $accId);
            
            return true;
        }
        
        if (!isset($isDown[$accId][$msgNum])) {
            $headers = $imapConn->getHeaders($msgNum);

            // Ако няма хедъри, значи има грешка
            if (!$headers) {
                email_Accounts::logWarning("[{$msgNum}] - missing headers", $accId, 7);
                
                return true;
            }
            
            $isDown[$accId][$msgNum] = email_Fingerprints::fetchByHeaders($headers) ? true : false;
        }
        
        email_Accounts::logInfo("Result: ${msgNum}  " . $isDown[$accId][$msgNum], $accId);
        
        return $isDown[$accId][$msgNum];
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    public function on_AfterPrepareListFilter($mvc, $data)
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
        
        if ($form->rec->country) {
            $data->query->where(array("#country= '[#1#]'", $form->rec->country));
        }
        
        if ($form->rec->accId) {
            $data->query->where(array("#accId= '[#1#]'", $form->rec->accId));
        }
    }
    
    
    /**
     * Променяме шаблона в зависимост от мода
     *
     * @param email_Incomings $mvc
     * @param core_ET         $tpl
     * @param object          $data
     */
    public function on_BeforeRenderSingleLayout($mvc, &$tpl, $data)
    {
        if (Mode::is('externalThreadView')) {
            $mvc->singleLayoutFile = Mode::get('screenMode') == 'wide' ? 'email/tpl/ExternalThreadViewSingleIncomings.shtml' : 'email/tpl/ExternalThreadViewSingleIncomingsNarrow.shtml';
            
            $data->row->ExternalThreadViewAvatar = avatar_Plugin::getImg(null, $data->rec->fromEml);
        }
    }
    
    
    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    public static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields)
    {
        if (!is_object($rec) && is_numeric($rec)) {
            $rec = $mvc->fetch($rec);
        }
        
        $rec->textPart = trim($rec->textPart);
        
        if (empty($rec->toEml)) {
            $rec->toEml = $rec->toBox;
        }
    }
    
    
    /**
     * Преобразува containerId в машинен вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields)
    {
        $haveErr = false;
        if (!$rec->subject) {
            $row->subject .= '[' . tr('Липсва заглавие') . ']';
        }
        
        if ($rec->headers) {
            $xResentFrom = email_Mime::getHeadersFromArr($rec->headers, 'X-ResentFrom');
            
            if ($xResentFrom && ($xEmailStr = email_Mime::getAllEmailsFromStr($xResentFrom))) {
                $tEmails = cls::get('type_Emails');
                $row->fromEml .= ' ' . tr('чрез') . ' ' . $tEmails->toVerbal($xEmailStr);
            }
        }
        
        if ($fields['-single']) {
            if ($rec->files) {
                $vals = keylist::toArray($rec->files);
                
                if ($rec->htmlFile) {
                    unset($vals[$rec->htmlFile]);
                }
                
                if (countR($vals)) {
                    $ourImgArr = core_Permanent::get('ourImgEmailArr');
                    $row->files = '';
                    
                    foreach ($vals as $keyD) {
                        $fRec = fileman::fetch($keyD);
                        
                        if ($ourImgArr[$fRec->dataId]) {
                            continue;
                        }
                        
                        $row->files .= fileman_Files::getLink($fRec->fileHnd);
                    }
                } else {
                    $row->files = '';
                }
            }
            
            self::calcAllToAndCc($rec);
            
            $errEmailInNameStr = 'Имейлът в името не съвпада с оригиналния|*.';
            
            // Проверяваме да няма подадени "грешн" имейли в name частта, които да объркат потребителите
            $row->AllTo = self::getVerbalEmail($rec->AllTo);
            
            if ($rec->AllTo && $rec->headers) {
                if (!self::checkNamesInEmails($rec->AllTo)) {
                    $row->AllTo = self::addErrToEmailStr($row->AllTo, $errEmailInNameStr, 'error');
                    $haveErr = true;
                }
                
                $mvc->addClosestEmailWarning($rec->AllTo, $row->AllTo);
            }
            
            $row->AllCc = self::getVerbalEmail($rec->AllCc);
            if ($rec->AllCc && $rec->headers) {
                if (!self::checkNamesInEmails($rec->AllCc)) {
                    $row->AllCc = self::addErrToEmailStr($row->AllCc, $errEmailInNameStr, 'error');
                    $haveErr = true;
                }
                
                $mvc->addClosestEmailWarning($rec->AllCc, $row->AllCc);
            }
            
            if (trim($rec->fromEml) && $rec->headers) {
                if (!self::checkNamesInEmails(array(array('address' => $rec->fromEml, 'name' => $rec->fromName)))) {
                    $row->fromEml = self::addErrToEmailStr($row->fromEml, $errEmailInNameStr, 'error');
                    $haveErr = true;
                }
            }
            
            // Ако имейлът не съвпада с този на Return-Path, добавяме предупреждение
            if ($rec->headers) {
                $returnPath = email_Mime::getHeadersFromArr($rec->headers, 'Return-Path');
                $returnPathEmails = type_Email::extractEmails($returnPath);
                if (!self::checkEmailIsExist($rec->fromEml, $returnPathEmails, false, true)) {
                    $returnPathEmailsUniq = array_unique($returnPathEmails);
                    $rEmailsStr = type_Emails::fromArray($returnPathEmailsUniq);
                    $rEmailsStr = type_Varchar::escape($rEmailsStr);
                    $w = 'тези';
                    if (countR($returnPathEmailsUniq) == 1) {
                        $w = 'този';
                    }
                    
                    $row->fromEml = self::addErrToEmailStr($row->fromEml, "Имейлът не съвпада с {$w} в|* Return-Path: " . $rEmailsStr, 'warning');
                    $haveErr = true;
                }
            }
            
            if (trim($rec->fromEml) && $rec->headers) {
                $firstCid = doc_Threads::getFirstContainerId($rec->threadId);
                
                // Проверка дали с този имейл има кореспонденция или е в контрагент данните на потребителя/фирмата
                if (($firstCid != $rec->containerId) && !self::checkEmailIsFromGoodList($rec->fromEml, $rec->threadId, $rec->folderId)) {
                    $row->fromEml = self::addErrToEmailStr($row->fromEml, 'В тази нишка няма кореспонденция с този имейл и не е в списъка с имейлите на контрагента|*.', 'error');
                    $haveErr = true;
                }
            }
            
            // Ако IP-то на изпращача е от рискова зона
            // Показваме предупреждение след имейла
            if ($rec->fromIp) {
                $badIpArr = $mvc->getBadIpArr(array($rec->fromIp), $rec->folderId);
                
                if (!empty($badIpArr)) {
                    $countryCode = $badIpArr[$rec->fromIp];
                    
                    $badIp = true;
                    
                    // Ако домейна е от същата дърава
                    if ($countryCode) {
                        if (($dotPos = strrpos($rec->fromEml, '.')) !== false) {
                            $tld = substr($rec->fromEml, $dotPos + 1);
                            
                            if (strtolower($tld) == strtolower($countryCode)) {
                                $badIp = false;
                            }
                        }
                    }
                    
                    // Ако в текста се съдържа държавата - на системния език или en
                    if ($badIp) {
                        $countryLocal = drdata_Countries::getCountryName($countryCode, core_Lg::getDefaultLang());
                        
                        if (mb_stripos($rec->textPart, $countryLocal) === false) {
                            $countryEn = drdata_Countries::getCountryName($countryCode, 'en');
                            
                            if (stripos($rec->textPart, $countryEn) !== false) {
                                $badIp = false;
                            }
                        } else {
                            $badIp = false;
                        }
                    }
                    
                    if ($badIp) {
                        $errIpCountryName = ' - ' . drdata_Countries::getCountryName($countryCode, core_Lg::getCurrent());
                        
                        $row->fromEml = self::addErrToEmailStr($row->fromEml, "Писмото е от IP в рискова зона|*{$errIpCountryName}!", 'error');
                        $haveErr = true;
                    }
                }
            }
        }
        
        if (!$rec->toBox) {
            $row->toBox = $row->toEml;
        }
        
        if ($rec->fromIp) {
            $row->fromIp = type_Ip::decorateIp($rec->fromIp, $rec->createdOn);
        }
        
        $row->fromName = str_replace(' чрез ', ' ' . tr('чрез') . ' ', $row->fromName);
        
        if (trim($row->fromName) && (strtolower(trim($rec->fromName)) != strtolower(trim($rec->fromEml)))) {
            if ($row->fromEml instanceof core_ET) {
                $row->fromEml->append(' (' . trim($row->fromName) . ')');
            } else {
                $row->fromEml .= ' (' . trim($row->fromName) . ')';
            }
        }
        
        
        if ($haveErr) {
            if ($row->fromEml instanceof core_ET) {
                $row->fromEml->prepend('<span class="textWithIcons">');
                $row->fromEml->append('</span>');
            } else {
                $row->fromEml = '<span class="textWithIcons">' . trim($row->fromName) . '</span>';
            }
        } else {
            
            // Показваме съответната икона в зависимост от СПАМ рейтинга
            $hardSpamRating = email_Setup::get('HARD_SPAM_SCORE');
            $rejectSpamRating = email_Setup::get('REJECT_SPAM_SCORE');
            if (isset($rec->spamScore) && (($rec->spamScore >= $hardSpamRating) || ($rec->spamScore >= $rejectSpamRating))) {
                $img = '/img/24/spam-warning.png';
                
                if ($rec->spamScore >= $hardSpamRating) {
                    $img = '/img/24/spam.png';
                }
                
                $row->fromEml = ht::createHint($row->fromEml, "Висок СПАМ рейтинг|*: {$rec->spamScore}", $img);
                
                if ($row->fromEml instanceof core_ET) {
                    $row->fromEml->prepend('<span class="textWithIcons">');
                    $row->fromEml->append('</span>');
                } else {
                    $row->fromEml = '<span class="textWithIcons">' . trim($row->fromName) . '</span>';
                }
            }
        }
        
        // Показваме източника на първия документ в нишката
        if ($rec->originId && $rec->threadId) {
            $fCid = doc_Threads::fetchField($rec->threadId, 'firstContainerId');
            
            if ($fCid == $rec->containerId) {
                $row->inReplyToOrigin = doc_Containers::getLinkForSingle($rec->originId);
            }
        }
        
        if (Mode::is('text', 'xhtml')) {
            unset($row->ip);
        }
    }
    
    
    /**
     * От подадения масив с IP адреси връща само лошите (от рискова зона)
     * Изключват се IP-та от държавите със същата корица и подадените в масива за изключения
     *
     * @param array    $ipArr
     * @param NULL|int $folderId
     * @param array    $skipCountryArr
     */
    public static function getBadIpArr($ipArr, $folderId = null, $skipCountryArr = array())
    {
        $resArr = array();
        
        foreach ($ipArr as $ip) {
            if (!trim($ip)) {
                continue ;
            }
            
            $ipCoutryCode = drdata_IpToCountry::get($ip);
            
            if (!in_array($ipCoutryCode, self::$riskIpArr)) {
                continue ;
            }
            
            if (isset($folderId)) {
                $cData = doc_Folders::getContragentData($folderId);
                
                // Ако папката е от рисковите държави
                // Ip-то не се добавя към рисковите
                if (isset($cData, $cData->countryId)) {
                    $coutryCode = drdata_Countries::fetchField((int) $cData->countryId, 'letterCode2');
                    if ($coutryCode == $ipCoutryCode) {
                        continue ;
                    }
                } else {
                    if ($ipCoutryCode) {
                        $fRec = doc_Folders::fetch($folderId);
                        if ($fRec->coverClass && $fRec->coverId && cls::load($fRec->coverClass, true)) {
                            $cInst = cls::get($fRec->coverClass);
                            if ($cInst instanceof doc_UnsortedFolders) {
                                $cRec = $cInst->fetch($fRec->coverId);
                                
                                $country = drdata_Countries::getCountryName($ipCoutryCode);
                                $unsortedName = sprintf(email_Setup::get('UNSORTABLE_COUNTRY'), $country);
                                $unsortedName = trim($unsortedName);
                                $unsortedName = mb_strtolower($unsortedName);
                                $fRec->title = trim($fRec->title);
                                $fRec->title = mb_strtolower($fRec->title);
                                if ($unsortedName == $fRec->title) {
                                    continue;
                                }
                                
                                core_Lg::push('en');
                                $countryEn = drdata_Countries::getCountryName($ipCoutryCode, 'en');
                                $unsortedNameEn = sprintf(email_Setup::get('UNSORTABLE_COUNTRY'), $countryEn);
                                $unsortedNameEn = trim($unsortedNameEn);
                                $unsortedNameEn = mb_strtolower($unsortedNameEn);
                                core_Lg::pop();
                                if ($unsortedNameEn == $fRec->title) {
                                    continue;
                                }
                            }
                        }
                    }
                }
            }
            
            if (!empty($skipCountryArr) && isset($skipCountryArr[$ipCoutryCode])) {
                continue ;
            }
            
            $resArr[$ip] = $ipCoutryCode;
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща стринг с най-близкия имейл, на който отговаря
     *
     * @param array $emailsArr
     *
     * @return string
     */
    protected static function addClosestEmailWarning($emailsArr, &$body)
    {
        $allEmailToArr = array();
        
        foreach ((array) $emailsArr as $emailArr) {
            $email = trim($emailArr['address']);
            $email = strtolower($email);
            
            $allEmailToArr[$email] = $email;
        }
        
        $closestEmail = email_Inboxes::getClosest($allEmailToArr, false, false);
        
        if (is_string($body)) {
            $isString = true;
        }
        
        if ($closestEmail) {
            if (!$allEmailToArr[$closestEmail]) {
                $res = ht::createHint($body, tr('Имейлът е пренасочен към') . ' ' . type_Varchar::escape($closestEmail), 'warning');
                if ($isString) {
                    $body = (string) $res;
                } else {
                    $body = $res;
                }
            }
        }
    }
    
    
    /**
     * Проверява дали има имейл и дали съвпада с оригиналния имейл в name частта
     *
     * @param array $emailsArr
     *
     * @return bool
     */
    protected static function checkNamesInEmails($emailsArr)
    {
        if (!$emailsArr) {
            
            return true;
        }
        
        foreach ($emailsArr as $emailArr) {
            if (!$emailArr['name']) {
                continue;
            }
            $pEmailsFromName = type_Email::extractEmails($emailArr['name']);
            if (!$pEmailsFromName) {
                continue;
            }
            
            if (!self::checkEmailIsExist($emailArr['address'], $pEmailsFromName)) {
                
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Добавя иконка за грешка пред стринга
     *
     * @param string $emailStr
     * @param string $errStr
     * @param string $type
     *
     * @return string
     */
    public static function addErrToEmailStr($emailStr, $errStr = '', $type = 'warning')
    {
        $hint = 'Възможен проблем|*!';
        
        
        if ($type != 'warning') {
            $hint = 'Възможност за измама|*! |Проверете по още един канал данните при превод на пари|*.';
            $type = '/img/24/danger.png';
        } else {
            $type = '/img/24/warning.png';
        }
        
        $hint .= ' |' . $errStr;
        
        return  ht::createHint($emailStr, $hint, $type);
    }
    
    
    /**
     * Проверява дали имейла е в добър списък
     * Дали от нишката има изпращане към този имейл
     * Дали папката е на контрагент и имейла го има в списъка
     *
     * @param string $email
     * @param int    $threadId
     * @param int    $folderId
     *
     * @return bool
     */
    protected static function checkEmailIsFromGoodList($email, $threadId, $folderId)
    {
        static $threadEmailsArr = array();
        static $checkedEmailsArr = array();
        static $contrDataEmailsArr = array();
        
        // Всички изпратени имейли в нишката
        if (!isset($threadEmailsArr[$threadId])) {
            $threadEmailsArr[$threadId] = array();
            $emailRecsArr = doclog_Documents::getRecs(null, doclog_Documents::ACTION_SEND, $threadId);
            
            foreach ($emailRecsArr as $emailRecArr) {
                $toArr = type_Emails::toArray($emailRecArr->data->to);
                $ccArr = type_Emails::toArray($emailRecArr->data->cc);
                $allArr = array_merge((array) $toArr, (array) $ccArr);
                
                foreach ($allArr as $emailStr) {
                    $threadEmailsArr[$threadId][$emailStr] = $emailStr;
                }
            }
        }
        
        $email = trim($email);
        $email = strtolower($email);
        
        if (!isset($checkedEmailsArr[$threadId][$email])) {
            // Дали е в изпратените имейли
            $checked = self::checkEmailIsExist($email, $threadEmailsArr[$threadId], true);
            
            if (!$checked) {
                // Ако папката е на котрагент, проверява в техните имейли
                if ($folderId) {
                    $cover = doc_Folders::getCover($folderId);
                    if (($cover->instance instanceof crm_Companies) || ($cover->instance instanceof crm_Persons)) {
                        if (!isset($contrDataEmailsArr[$folderId])) {
                            $contrData = $cover->getContragentData();
                            $contrDataEmailsArr[$folderId] = type_Emails::toArray($contrData->groupEmails);
                        }
                        $checkedEmailsArr[$threadId][$email] = self::checkEmailIsExist($email, $contrDataEmailsArr[$folderId], true);
                    }
                }
            } else {
                $checkedEmailsArr[$threadId][$email] = true;
            }
        }
        
        if (!isset($checkedEmailsArr[$threadId][$email])) {
            $checkedEmailsArr[$threadId][$email] = !(boolean) $threadEmailsArr[$threadId];
        }
        
        return $checkedEmailsArr[$threadId][$email];
    }
    
    
    /**
     * Проверява дали имейла може да е еднакъв с подадения масив
     * Публичните трябва да съвпадат точно
     * При останалите - домейна трябва да съвпада
     *
     * @param string $email
     * @param array  $emailsArr
     * @param bool   $emailsArr
     * @param bool   $removeSubdomains
     *
     * @return bool
     */
    public static function checkEmailIsExist($email, $emailsArr, $mandatory = false, $removeSubdomains = false)
    {
        if (!$emailsArr) {
            if ($mandatory) {
                
                return false;
            }
            
            return true;
        }
        
        $email = strtolower($email);
        $email = type_Email::removeBadPart($email);
        $domain = type_Email::domain($email);
        
        $isPublic = false;
        
        if (drdata_Domains::isPublic($domain)) {
            $isPublic = true;
        }
        
        foreach ($emailsArr as $emailCheck) {
            if ($isPublic) {
                $emailCheck = strtolower($emailCheck);
                $emailCheck = type_Email::removeBadPart($emailCheck);
                if ($emailCheck == $email) {
                    
                    return true;
                }
            } else {
                $cDomain = type_Email::domain($emailCheck);
                $cDomain = strtolower($cDomain);
                
                // Правим проверка без да сравняваме поддомейните
                if ($removeSubdomains) {
                    $cDomain = core_Url::parseUrl($cDomain);
                    $cDomain = $cDomain['domain'];
                    
                    $domain = core_Url::parseUrl($domain);
                    $domain = $domain['domain'];
                }
                
                if ($domain == $cDomain) {
                    
                    return true;
                }
            }
        }
        
        return false;
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
        foreach ((array) $emailsArr as $emailArr) {
            $allEmailToArr[] = $emailArr['address'];
        }
        
        // Премахваме нашите имейлите
        $otherAllEmailToArr = email_Inboxes::removeOurEmails($allEmailToArr);
        
        $cRec = email_Accounts::getCorporateAcc();
        $allEmailsArr = email_Inboxes::getAllInboxes();
        
        // Отбелязваме, кои имейли са външни
        if ($otherAllEmailToArr) {
            foreach ((array) $emailsArr as $key => $emailArr) {
                if (!$emailArr['address']) {
                    continue;
                }
                
                if (array_search($emailArr['address'], $otherAllEmailToArr) !== false) {
                    $emailsArr[$key]['isExternal'] = true;
                } else {
                    $trimEmail = trim($emailArr['address']);
                    $trimEmail = strtolower($trimEmail);
                    
                    // Ако няма такъв корпоративен имейл
                    if (!empty($allEmailsArr) && !$allEmailsArr[$trimEmail]) {
                        $emailsArr[$key]['isWrong'] = true;
                    }
                }
            }
        }
        
        // Вземаме вербалното представяне на имейлите
        $emailRow = email_Mime::emailListToVerbal($emailsArr);
        
        return $emailRow;
    }
    
    
    /**
     * Пресмята стойностите за AllTo и AllCc - всички получатели на имейла
     *
     * @param stdClass $rec
     * @param bool     $saveIfNotExist
     */
    public static function calcAllToAndCc($rec, $saveIfNotExist = true)
    {
        if (isset($rec->AllTo) || isset($rec->AllCc)) {
            
            return ;
        }
        
        if ($rec->toAndCc) {
            $rec->AllTo = $rec->toAndCc['allTo'];
            $rec->AllCc = $rec->toAndCc['allCc'];
            
            return ;
        }
        
        // Ако няма хедъри
        if (!$rec->headers && $rec->emlFile) {
            
            // Манипулатора на eml файла
            $fh = fileman_Files::fetchField($rec->emlFile, 'fileHnd');
            
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
        $allTo = email_Mime::getHeadersFromArr($headersArr, 'to', '*');
        $toParser = new email_Rfc822Addr();
        $rec->AllTo = array();
        $toParser->ParseAddressList($allTo, $rec->AllTo);
        
        // Парсираме cc хедъра
        $allCc = email_Mime::getHeadersFromArr($headersArr, 'cc', '*');
        
        $ccParser = new email_Rfc822Addr();
        $rec->AllCc = array();
        $ccParser->ParseAddressList($allCc, $rec->AllCc);
        
        $rec->toAndCc = array('allTo' => $rec->AllTo, 'allCc' => $rec->AllCc);
        
        if ($rec->id && $saveIfNotExist) {
            $inst = cls::get(get_called_class());
            $inst->save_($rec, 'toAndCc');
        }
    }
    
    
    /**
     * Да сваля имейлите по - крон
     */
    public function cron_DownloadEmails()
    {
        if (defined('DEV_SERVER') &&  (DEV_SERVER === true)) {

            return 'Спряно сваляне на имейли';
        }

        // Закръгляме текущите секунди към най-близкото делящо се на 30 число
        $time = round(time() / 30) * 30;
        
        $mailInfo = $this->fetchAllAccounts($time);
        
        return $mailInfo;
    }
    
    
    /**
     * Cron екшън за опресняване на публичните домейни
     */
    public function cron_UpdatePublicDomains()
    {
        $domains = static::scanForPublicDomains();
        
        $out = '<li>Открити ' . countR($domains) . ' домейн(а) ... </li>';
        
        $stats = drdata_Domains::resetPublicDomains($domains);
        
        $out .= "<li>Добавени {$stats['added']}, изтрити {$stats['removed']} домейн(а)</li>";
        
        if ($stats['addErrors']) {
            $out .= "<li class=\"error\">Проблем при добавянето на {$stats['addErrors']} домейн(а)!</li>";
        }
        
        if ($stats['removeErrors']) {
            $out .= "<li class=\"error\">Проблем при изтриването на {$stats['removeErrors']} домейн(а)!</li>";
        }
        
        $out .= ''
        . '<h4>Опресняване на публичните домейни<h4>'
        . '<ul>'
        .    $out
        . '</ul>';
        
        return $out;
    }
    
    
    /**
     * Крон процес за извличане на телофонни номера от имейлите
     */
    public function cron_ExtractPhoneNumbers()
    {
        $period = core_Cron::getRecForSystemId('ExtractPhoneNumbers')->period;
        $period = $period ? $period : 60;
        $period *= 60;
        
        $query = self::getQuery();
        $before = dt::subtractSecs($period);
        $query->where(array("#modifiedOn >= '[#1#]'", $before));
        
        $query->EXT('coverClass', 'doc_Folders', 'externalName=coverClass,externalKey=folderId');
        $query->where(array("#coverClass = '[#1#]'", $clsId = crm_Companies::getClassId()));
        $query->orWhere(array("#coverClass = '[#1#]'", $clsId = crm_Persons::getClassId()));
        
        $query->EXT('coverId', 'doc_Folders', 'externalName=coverId,externalKey=folderId');
        $query->where("#coverId IS NOT NULL");
        
        $query->orderBy('modifiedOn', 'DESC');
        
        try {
            while ($rec = $query->fetch()) {
                if (!$rec->coverClass || !$rec->coverId) {
                    continue;
                }
                
                $cData = $this->getContragentData($rec->id);
                
                $mob = $cData->mob ? $cData->mob : null;
                $tel = $cData->tel ? $cData->tel : null;
                $fax = $cData->fax ? $cData->fax : null;
                
                if (!$mob && !$tel && !$fax) {
                    continue;
                }

                self::logInfo("Добавени номера от имейл: tel: {$tel}, mob:{$mob}, fax: {$fax}", $rec->id, 70);

                $inst = cls::get($rec->coverClass);
                $iRec = $inst->fetch($rec->coverId);
                $inst->addAddtionalNumber($iRec, $mob, $tel, $fax);
            }
        } catch (Exception $e) {
            reportException($e);
        } catch (Throwable $t) {
            reportException($t);
        }
    }
    
    
    /**
     * Обучаване на SPAS за HAM и SPAM
     *
     * Правила за обучение:
     * От последния час, оттеглени/възстановени от потребител и няма друг документ в нишката
     * В папки с корица на Е-Кутия и несортирани - за оттеглените
     * Ако е пратен до имейл, който не е в системата
     */
    public function cron_TrainSpas()
    {
        // Ако пакета не е истанлиран
        if (!core_Packs::isInstalled('spas')) {
            
            return ;
        }
        
        $period = core_Cron::getRecForSystemId('trainSpas')->period;
        $period = $period ? $period : 60;
        $period *= 60;

        $query = self::getQuery();
        $before = dt::subtractSecs($period);
        $query->where(array("#modifiedOn >= '[#1#]'", $before));
        $query->EXT('docCnt', 'doc_Threads', 'externalName=allDocCnt,remoteKey=firstContainerId, externalFieldName=containerId');
        $query->where('#docCnt <= 1');
        $query->where("#emlFile != ''");
        $query->where('#emlFile IS NOT NULL');

        $allBoxesArr = email_Inboxes::getAllEmailsArr(false);
        $allBoxesArrNew = array();
        foreach ((array) $allBoxesArr as $email) {
            $email = strtolower($email);
            $allBoxesArrNew[$email] = $email;
        }

        while ($rec = $query->fetch()) {
            if (!$rec->emlFile) {
                continue;
            }
            
            // Ако е оттеглен, проверяваме броя на документите
            if (($rec->state == 'rejected') && $rec->docCnt == 0) {
                $cQuery = doc_Containers::getQuery();
                $cQuery->where(array("#threadId = '[#1#]'", $rec->threadId));
                $cQuery->limit(2);
                $cQuery->show('threadId');
                if ($cQuery->count() > 1) {
                    continue;
                }
            }

            $haveEmail = true;
            if (!$rec->userInboxes) {
                $haveEmail = false;
                foreach ((array) $rec->toAndCc['allTo'] as $emailAddArr) {
                    $email = strtolower(trim($emailAddArr['address']));
                    if ($allBoxesArrNew[$email]) {
                        $haveEmail = true;
                        break;
                    }
                }
                
                if (!$haveEmail) {
                    foreach ((array) $rec->toAndCc['allCc'] as $emailAddArr) {
                        $email = strtolower(trim($emailAddArr['address']));
                        if ($allBoxesArrNew[$email]) {
                            $haveEmail = true;
                            break;
                        }
                    }
                }
            } else {
                // Оттеглните имейлите се проверяват само в Е-кутии и Несортирани
                if ($rec->state == 'rejected') {
                    $cover = doc_Folders::getCover($rec->folderId);
                    if (!($cover->instance instanceof email_Inboxes) && !($cover->instance instanceof doc_UnsortedFolders)) {
                        continue;
                    }
                }
            }
            
            if ($haveEmail) {
                if ($rec->modifiedBy <= 0) {
                    continue;
                }
                
                if (!(($rec->state == 'rejected' && $rec->brState) || ($rec->brState == 'rejected'))) {
                    continue;
                }
            }

            $type = spas_Client::LEARN_HAM;
            $typeStr = 'НЕ Е СПАМ (от възстановен имейл)';
            
            if (!$haveEmail || ($rec->state == 'rejected')) {
                $type = spas_Client::LEARN_SPAM;
                
                if (!$haveEmail) {
                    $typeStr = 'СПАМ (липсваща имейл кутия)';
                } else {
                    $typeStr = 'СПАМ (от оттеглен имейл)';
                }
            }
            
            $fh = fileman_Files::fetchField($rec->emlFile, 'fileHnd');
            
            if (!$fh) {
                continue;
            }
            
            $rawEmail = fileman_Files::getContent($fh);

            try {
                $sa = spas_Test::getSa();
                
                $res = $sa->learn($rawEmail, $type);
                
                if ($res) {
                    $resStr = 'ОК';
                } else {
                    $resStr = 'Проблем';
                }
                
                email_Incomings::logNotice("Резултат от обучение за {$typeStr} - " . $resStr, $rec->id);
            } catch (spas_client_Exception $e) {
                reportException($e);
                email_Incomings::logErr('Грешка при обучение на SPAS: ' . $e->getMessage());
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function act_UpdatePublicDomains()
    {
        requireRole('admin');
        
        return static::cron_UpdatePublicDomains();
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        $rec = new stdClass();
        $rec->systemId = 'DownloadEmails';
        $rec->description = 'Сваляне на имейли в модела';
        $rec->controller = $mvc->className;
        $rec->action = 'DownloadEmails';
        $rec->period = 1;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 100;
        $res .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'DownloadEmails2';
        $rec->description = 'Сваляне на имейли в модела2';
        $rec->controller = $mvc->className;
        $rec->action = 'DownloadEmails';
        $rec->period = 1;
        $rec->offset = 0;
        $rec->delay = 30;
        $rec->timeLimit = 100;
        $res .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'UpdatePublicDomains';
        $rec->description = 'Обновяване на публичните домейни';
        $rec->controller = $mvc->className;
        $rec->action = 'UpdatePublicDomains';
        $rec->period = 1440; // 24h
        $rec->offset = rand(120, 180); // от 2 до 3h
        $rec->isRandOffset = true;
        $rec->delay = 0;
        $rec->timeLimit = 100;
        $res .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'trainSpas';
        $rec->description = 'Обучение на SPAS';
        $rec->controller = $mvc->className;
        $rec->action = 'trainSpas';
        $rec->period = 60;
        $rec->offset = rand(0, 59);
        $rec->isRandOffset = true;
        $rec->delay = 0;
        $rec->timeLimit = 250;
        $res .= core_Cron::addOnce($rec);
        
        
        $rec = new stdClass();
        $rec->systemId = 'ExtractPhoneNumbers';
        $rec->description = 'Извличане на телефонни номер';
        $rec->controller = $mvc->className;
        $rec->action = 'ExtractPhoneNumbers';
        $rec->period = 60;
        $rec->offset = rand(0, 59);
        $rec->isRandOffset = true;
        $rec->delay = 0;
        $rec->timeLimit = 250;
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
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        
        $subject = $this->getVerbal($rec, 'subject');
        
        if (!trim($subject)) {
            $subject = '[' . tr('Липсва заглавие') . ']';
        }
        
        $row = new stdClass();
        $row->title = $subject;
        
        if (trim($rec->fromName)) {
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
    public static function isSpam($rec)
    {
        /**
         * @TODO
         */
        
        return false;
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
        // Репортване, ако имаме данни за нишката
        if ($rec->threadId || $rec->folderId) {
            if (!Mode::is('isMigrate') && !Mode::is('MassImporting')) {
                wp($rec);
            }
            
            return;
        }
        
        // Винаги рутираме по номер на тред
        if (email_Router::doRuleThread($rec)) {
            $rec->routeBy = 'thread';
        }
        
        // Рутиране по файлове
        if (!$rec->threadId && self::doRuleFile($rec)) {
            $rec->routeBy = 'file';
        }
        
        $originId = $rec->originId;
        
        if (!$originId) {
            
            // Ако не е зададено originId - тогава е на последния документ
            if ($rec->threadId) {
                $cQuery = doc_Containers::getQuery();
                $cQuery->where(array("#threadId = '[#1#]'", $rec->threadId));
                $cQuery->orderBy('#createdOn', 'DESC');
                $cQuery->orderBy('#id', 'DESC');
                $cQuery->limit(1);
                $cQuery->show('id');
                
                $originId = $cQuery->fetch()->id;
            }
        }
        $rArr = array('folderId' => null, 'threadId' => null, 'routeBy' => null, 'originId' => $originId);
        
        // Проверяваме дали може да се рутира тук
        email_Router::checkRouteRules($rec, $rArr);
        
        // Входящите имейли да не влизат в оттеглени нишки, в които има документи за контиране
        if ($rec->threadId && ($rec->routeBy == 'file' || $rec->routeBy == 'thread')) {
            $tRec = doc_Threads::fetch($rec->threadId);
            
            if ($tRec->state == 'rejected') {
                $query = doc_Containers::getQuery();
                $query->where(array("#threadId = '[#1#]'", $rec->threadId));
                $query->orderBy('createdOn', 'ASC');
                
                while ($cRec = $query->fetch()) {
                    if (($cRec->docClass) && (cls::load($cRec->docClass, true)) && cls::haveInterface('acc_TransactionSourceIntf', $cRec->docClass)) {
                        unset($rec->threadId);
                        unset($rec->folderId);
                        unset($rec->routeBy);
                        
                        break;
                    }
                }
            }
            
            if ($rec->routeBy && $rec->threadId) {
                
                return ;
            }
        }

        // Първо рутираме по ръчно зададените правила
        if ($rec->_prerouteRecArr) {

            foreach ($rec->_prerouteRecArr as $fName => $fVal) {
                $rec->{$fName} = $fVal;
            }

            // Добавяме начина на рутиране
            $rec->routeBy = 'preroute';

            if ($rec->_prerouteRecArr['folderId']) {

                return ;
            }
        }

        if ($rec->accId) {
            // Извличаме записа на сметката, от която е изтеглено това писмо
            $accRec = email_Accounts::fetch($rec->accId);
        }
        
        // Ако имейлът на сметката има домейн за миграция - новата сметка се използва
        $accEml = email_Inboxes::replaceDomains($accRec->email);
        if ($accEml != $accRec->email) {
            $newAccRec = email_Accounts::fetch(array("#email = '[#1#]'", $accEml));
            if ($newAccRec) {
                $accRec = $newAccRec;
            }
        }
        
        // Ако сметката е с рутиране
        if ($accRec && ($accRec->applyRouting == 'yes')) {
            
            // Ако `boxTo` е обща кутия, прилагаме последователно `From`, `Domain`, `Country`
            if (($accRec->email == $rec->toBox || $accRec->email == $rec->toEml) && $accRec->type != 'single') {
                
                // Ако папката е с рутиране и boxTo е обща кутия, прилагаме `From`
                if (email_Router::doRuleFrom($rec)) {
                    
                    // Добавяме начина на рутиране
                    $rec->routeBy = 'from';
                    
                    // Проверяваме дали може да се рутира тук
                    if (email_Router::checkRouteRules($rec, $rArr)) {
                        
                        return;
                    }
                }
                
                // Рутиране по домейн
                if (email_Router::doRuleDomain($rec)) {
                    
                    // Добавяме начина на рутиране
                    $rec->routeBy = 'domain';
                    
                    if (email_Router::checkRouteRules($rec, $rArr)) {
                        
                        return;
                    }
                }
                
                // Рутиране по място (държава)
                if (email_Router::doRuleCountry($rec)) {
                    
                    // Добавяме начина на рутиране
                    $rec->routeBy = 'country';
                    
                    if (email_Router::checkRouteRules($rec, $rArr)) {
                        // Автоматично оттегляне на имейлите, които са СПАМ
                        self::checkSpamLevelAndReject($rec);
                        
                        return;
                    }
                }
            } else {
                
                // Ако `boxTo` е частна кутия, то прилагаме `FromTo`
                if (email_Router::doRuleFromTo($rec)) {
                    
                    // Добавяме начина на рутиране
                    $rec->routeBy = 'fromTo';
                    
                    if (email_Router::checkRouteRules($rec, $rArr)) {
                        if ($rec->folderId) {
                            $coverClass = doc_Folders::getCover($rec->folderId);
                            
                            // Ако ще се рутира към пощенска кутия или проект
                            if ($coverClass) {
                                if ($coverClass->instance instanceof email_Inboxes || $coverClass->instance instanceof doc_UnsortedFolders) {
                                    self::checkSpamLevelAndReject($rec, 0.5);
                                }
                            }
                        }
                        
                        return;
                    }
                }
            }
        }
        
        // Автоматично оттегляне на имейлите, които са СПАМ
        self::checkSpamLevelAndReject($rec);
        
        // Накрая безусловно вкарваме в кутията на `toBox`
        email_Router::doRuleToBox($rec);
        
        // Добавяме начина на рутиране
        $rec->routeBy = 'toBox';
        
        expect($rec->folderId, $rec);
    }
    
    
    /**
     *
     *
     * @param stdClass $rec
     * @param float    $tolerance
     */
    protected static function checkSpamLevelAndReject($rec, $tolerance = 0)
    {
        if ($rec->state == 'rejected') {
            
            return ;
        }

        if (Mode::is('forceDownload')) {

            return ;
        }

        $score = $rec->spamScore;
        if (!isset($score)) {
            $score = email_Spam::getSpamScore($rec->headers, false, null, $rec);
        }
        
        $spamScore = email_Setup::get('REJECT_SPAM_SCORE');
        
        $spamScore = $spamScore + ($spamScore * $tolerance);
        
        if (isset($score) && ($score >= $spamScore)) {
            $rec->state = 'rejected';
            self::logNotice("Автоматично оттеглен имейл ({$rec->subject}) със СПАМ рейтинг = '{$score}'", $rec->id);
        }
        
        if ($rec->state != 'rejected') {
            // Проверка на имейла за файл с вирус
            $files = $rec->files;
            $files = type_Keylist::addKey($files, $rec->emlFile);
            $files = type_Keylist::addKey($files, $rec->htmlFile);
            
            $filesArr = type_Keylist::toArray($files);
            if (!empty($filesArr)) {
                $fQuery = fileman_Files::getQuery();
                $fQuery->orWhereArr('id', $filesArr);
                $fQuery->where('#dangerRate IS NOT NULL');
                $fQuery->where('#dangerRate >= 0.001');
                
                if ($fQuery->count()) {
                    $rec->state = 'rejected';
                    self::logNotice("Автоматично оттеглен имейл ({$rec->subject}) с вирусен файл", $rec->id);
                }
            }
        }
    }
    
    
    /**
     * Рутира по файлове
     *
     * @param stdClass $rec
     */
    protected static function doRuleFile($rec)
    {
        if ($rec->files && ($filesArr = type_Keylist::toArray($rec->files))) {
            try {
                $fCnt = 0;
                $ratingsArr = array();
                foreach ($filesArr as $fileId) {
                    
                    // Ако сме достигнали максималния брой на файлове, които да се сканират
                    if ($fCnt > self::$maxScanFileCnt) {
                        break;
                    }
                    
                    $fRec = fileman_Files::fetch((int) $fileId);
                    $ext = fileman_Files::getExt($fRec->name);
                    
                    if (!$ext) {
                        continue;
                    }
                    $ext = mb_strtolower($ext);
                    
                    $allowedExt = mb_strtolower(email_Setup::get('ALLOWED_EXT_FOR_BARCOCE'));
                    $allowedExtArr = arr::make($allowedExt, true);
                    
                    if (!$allowedExtArr[$ext]) {
                        continue;
                    }
                    
                    // Ако е под или над допустимия размер за обработка - прескачаме
                    if (email_Setup::get('MIN_FILELEN_FOR_BARCOCE') > $fRec->fileLen) {
                        continue;
                    }
                    if (email_Setup::get('MAX_FILELEN_FOR_BARCOCE') < $fRec->fileLen) {
                        continue;
                    }
                    
                    try {
                        $fCnt++;
                        $barcodesArr = zbar_Reader::getBarcodesFromFile($fRec->fileHnd);
                    } catch (fileman_Exception $e) {
                        continue;
                    }
                    
                    $barcodeCnt = 0;
                    
                    // Опитваме се да определеим баркода за документ от нашата система
                    foreach ($barcodesArr as $bCode) {
                        if ($barcodeCnt > self::$maxScanBarcodeCnt) {
                            break;
                        }
                        
                        if (!$bCode->code) {
                            continue;
                        }
                        
                        $barcodeCnt++;
                        
                        $cId = doclog_Documents::getDocumentCidFromURL($bCode->code);
                        
                        if (!$cId) {
                            continue;
                        }
                        
                        $cRec = doc_Containers::fetch($cId);
                        
                        if (!$cRec) {
                            continue;
                        }
                        
                        if (!isset($ratingsArr[$cRec->id])) {
                            $ratingsArr[$cRec->id] = self::getDocRating($cRec);
                        }
                    }
                }
                
                if (!empty($ratingsArr)) {
                    arsort($ratingsArr);
                    
                    foreach ($ratingsArr as $bestCidId => $rating) {
                        if (!$bestCidId) {
                            continue;
                        }
                        
                        $cRec = doc_Containers::fetch($bestCidId);
                        
                        $rec->threadId = $cRec->threadId;
                        
                        if ($rec->threadId) {
                            if ($rec->folderId = doc_Threads::fetchField($rec->threadId, 'folderId')) {
                                $coverClass = doc_Folders::getCover($rec->folderId);
                                
                                // Ако ще се рутира към пощенска кутия или папка на контрагент
                                if ($coverClass) {
                                    if ($coverClass->instance instanceof email_Inboxes ||
                                        $coverClass->instance instanceof crm_Companies ||
                                        $coverClass->instance instanceof crm_Persons) {
                                        break;
                                    }
                                }
                            }
                        }
                        
                        // Ако се стигне до тук, значи нишката не отговаря на условията
                        unset($rec->threadId);
                        unset($rec->folderId);
                    }
                }
                
                if (!empty($ratingsArr) && $rec->folderId) {
                    
                    return $rec->folderId;
                }
            } catch (ErrorException $e) {
                reportException($e);
            }
        }
    }
    
    
    /**
     * Определя рейтинга на документа
     * Последно модифицираните са с най-голям рейтинг, а оттеглените с най-нисък
     *
     * @param int|stdClass $cId
     *
     * @return NULL|int
     */
    protected static function getDocRating($cId)
    {
        if (!$cId) {
            
            return ;
        }
        
        $cRec = doc_Containers::fetchRec($cId);
        
        if (!$cRec) {
            
            return ;
        }
        
        $rating = 0;
        
        if ($cRec->modifiedOn) {
            $rating = dt::mysql2timestamp($cRec->modifiedOn);
        }
        
        if ($rating && ($cRec->state == 'rejected')) {
            $rating /= 1000;
        }
        
        return $rating;
    }
    
    
    /**
     *
     *
     * @param stdClass $rec
     *
     * return boolean
     */
    public static function isCommonToBox($rec)
    {
        expect($rec->accId, $rec);
        
        $accRec = email_Accounts::fetch($rec->accId);
        
        $isCommon = (($accRec->email == $rec->toBox || $accRec->email == $rec->toEml) && $accRec->type != 'single');
        
        return $isCommon;
    }
    
    
    /**
     * Извиква се след вкарване на запис в таблицата на модела
     *
     * @param email_Incomings $mvc
     * @param int|NULL        $id
     * @param stdClass        $rec
     * @param mixed           $saveFileds
     */
    public static function on_BeforeSave($mvc, &$id, $rec, $saveFileds = null)
    {
        $mvc->calcAllToAndCc($rec, false);
        
        $mvc->updateUserInboxes($rec, false);
    }
    
    
    /**
     * Извиква се след вкарване на запис в таблицата на модела
     *
     * @param email_Incomings $mvc
     * @param int|NULL        $id
     * @param stdClass        $rec
     * @param mixed           $saveFileds
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFileds = null)
    {
        static::needFields($rec, 'fromEml, toBox, date, containerId,threadId, accId');
        
        if ($rec->containerId && $rec->folderId && $rec->fromEml && $rec->toBox) {
            if ($rec->state == 'rejected') {
                $mvc->removeRouterRules($rec);
            } elseif (($rec->routeBy != 'thread') && ($rec->routeBy != 'preroute') && ($rec->routeBy != 'file')) {
                // Ако рутираме по нишка или потребителски филтър или файл да не се създават правила
                $mvc->makeRouterRules($rec);
            }
        }
        
        // Ако се е прекъснало нормалното рутиране по нишка
        // Бием нотификация на създателя на документа
        if ($rec->originId) {
            $cRec = doc_Containers::fetch($rec->originId);
            
            if (($cRec->createdBy > 0) && $rec->containerId && email_Incomings::haveRightFor('single', $rec, $cRec->createdBy)) {
                $newCRec = doc_Containers::fetch($rec->containerId);
                doc_Containers::addNotifications(array($cRec->createdBy => $cRec->createdBy), $mvc, $newCRec, 'добави', false);
            }
        }
    }
    
    
    /**
     * Добавя id-тата на имейлите към акаунтите
     *
     * @param stdClass $rec
     * @param bool     $forceSave
     *
     * @return int|FALSE
     */
    public function updateUserInboxes($rec, $forceSave = true)
    {
        if (!$rec) {
            
            return ;
        }
        
        $rec->userInboxes = '';
        
        self::calcAllToAndCc($rec, $forceSave);
        
        $allEmailsArr = array_merge($rec->AllTo, $rec->AllCc);
        
        foreach ($allEmailsArr as $allTo) {
            $email = $allTo['address'];
            $email = trim($email);
            $emailArr[$email] = $email;
        }
        
        if ($emailArr) {
            $emailIdArr = email_Inboxes::getEmailsRecField($emailArr);
            
            if ($emailIdArr) {
                $emailIdArr = array_values($emailIdArr);
                $emailIdArr = arr::make($emailIdArr, true);
                
                $rec->userInboxes = type_Keylist::fromArray($emailIdArr);
            }
        }
        
        if ($rec->id && $forceSave) {
            
            return $this->save_($rec, 'userInboxes');
        }
    }
    
    
    /**
     * След изтриване на записи на модела
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param core_Query $query
     */
    public static function on_AfterDelete($mvc, &$res, $query)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            $mvc->removeRouterRules($rec);
        }
    }
    
    
    /**
     * Зарежда при нужда полета на зададен запис от модела.
     *
     * @param stdClass $rec    запис на модела; трябва да има зададен поне първ. ключ ($rec->id)
     * @param mixed    $fields полетата, които са нужни; ако ги няма в записа - зарежда ги от БД
     *
     * @TODO това е метод от нивото на fetch, така че може да се изнесе в класа core_Mvc
     */
    public static function needFields($rec, $fields)
    {
        expect($rec->id);
        
        $fields = arr::make($fields);
        $missing = array();
        
        foreach ($fields as $f) {
            if (!isset($rec->{$f})) {
                $missing[$f] = $f;
            }
        }
        
        if (countR($missing) > 0) {
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
    public static function makeRouterRules($rec)
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
    public static function removeRouterRules($rec)
    {
        // Премахване на правилата
        email_Router::removeRules('document', $rec->containerId);
        
        //
        // Създаване на правила на базата на последните 3 писма от същия изпращач
        //
        
        /* @var $query core_Query */
        if ($rec->fromEml) {
            $query = static::getQuery();
            $query->where("#fromEml = '{$rec->fromEml}' AND #state != 'rejected' AND #accId > 0");
            $query->orderBy('createdOn', 'DESC');
            $query->limit(3);     // 3 писма
            while ($mrec = $query->fetch()) {
                static::makeRouterRules($mrec);
            }
        }
    }
    
    
    /**
     * Създаване на правило от тип `FromTo` - само ако получателя не е общ.
     *
     * @param stdClass $rec
     */
    public static function makeFromToRule($rec)
    {
        if (!static::isCommonToBox($rec)) {
            $key = email_Router::getRoutingKey($rec->fromEml, $rec->toBox, email_Router::RuleFromTo);
            
            // Най-висок приоритет, нарастващ с времето
            $priority = email_Router::dateToPriority($rec->date, 'high', 'asc');
            
            email_Router::saveRule(
                (object) array(
                    'type' => email_Router::RuleFromTo,
                    'key' => $key,
                    'priority' => $priority,
                    'objectType' => 'document',
                    'objectId' => $rec->containerId
                )
            );
        }
    }
    
    
    /**
     * Създаване на правило от тип `From` - винаги
     *
     * @param stdClass $rec
     */
    public static function makeFromRule($rec)
    {
        // Най-висок приоритет, нарастващ с времето
        $priority = email_Router::dateToPriority($rec->date, 'high', 'asc');
        
        email_Router::saveRule(
            (object) array(
                'type' => email_Router::RuleFrom,
                'key' => email_Router::getRoutingKey($rec->fromEml, null, email_Router::RuleFrom),
                'priority' => $priority,
                'objectType' => 'document',
                'objectId' => $rec->containerId
            )
        );
    }
    
    
    /**
     * Създаване на правило от тип `Domain` - ако изпращача не е от пуб. домейн и получателя е общ.
     *
     * @param stdClass $rec
     */
    public static function makeDomainRule($rec)
    {
        if (static::isCommonToBox($rec) && ($key = email_Router::getRoutingKey($rec->fromEml, null, email_Router::RuleDomain))) {
            
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
                    (object) array(
                        'type' => email_Router::RuleDomain,
                        'key' => $key,
                        'priority' => $priority,
                        'objectType' => 'document',
                        'objectId' => $rec->containerId
                    )
                );
            }
        }
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща данните за адресата
     */
    public static function getContragentData($id)
    {
        //Данните за имейл-а
        $msg = email_Incomings::fetch($id);
        
        $addrParse = cls::get('drdata_Address');
        
        Mode::push('text', 'plain');
        Mode::push('ClearFormat', true);
        $rt = new type_Richtext();
        $textPart = $rt->toVerbal($msg->textPart);
        Mode::pop('ClearFormat');
        Mode::pop('text');
        
        $footer = email_Outgoings::getFooter();
        
        $avoid = array('html') + array_filter(explode("\n", str_replace(array('Тел.:', 'Факс:', 'Tel.:', 'Fax:'), array('', '', '', ''), trim($footer))));
        
        $contragentData = $addrParse->extractContact($textPart, array('email' => $msg->fromEml, 'lg' => $msg->lg, 'country' => $msg->country), $avoid);
        
        $headersArr = array();
        
        // Ако няма хедъри
        // За съвместимост със стар код
        if (!$msg->headers) {
            if ($msg->emlFile) {
                // Манипулатора на eml файла
                $fh = fileman_Files::fetchField($msg->emlFile, 'fileHnd');
                
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
            }
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
        if (!$contragentData->countryId) {
            $contragentData->countryId = $msg->country;
        }
        
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
            if (!trim($email)) {
                continue;
            }
            
            // Ако има запис, добавяме към стринга
            $allEmails .= ($allEmails) ? ', ' . $email : $email;
        }
        
        // Вземаме груповите имейли
        $contragentData->groupEmails = email_Mime::getAllEmailsFromStr($allEmails, true);
        
        // Добавяме toEml и toBox
        $contragentData->toEml = $msg->toEml;
        $contragentData->toBox = $msg->toBox;
        
        return $contragentData;
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     *
     * @see email_DocumentIntf
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = false)
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
    public static function scanForPublicDomains()
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
        $results = array();
        
        while ($rec = $query->fetch()) {
            $fromDomain = type_Email::domain($rec->fromEml);
            $domains[$rec->coverClass][$fromDomain][$rec->folderId] = true;
            
            if (countR($domains[$rec->coverClass][$fromDomain]) > 1) {
                // От $fromDomain има поне 2 писма, които са в различни фирмени папки
                $results[$fromDomain] = true;
            }
        }
        
        return $results;
    }
    
    
    /**
     * Реализация  на интерфейсния метод ::getThreadState()
     */
    public static function getThreadState($id)
    {
        return 'opened';
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function act_Update()
    {
        requireRole('admin');
        
        set_time_limit(3600);
        $query = self::getQuery();
        
        $i = 0;
        
        while ($rec = $query->fetch()) {
            $i++;
            
            if ($i % 100 == 1) {
                email_Incomings::logInfo('Update email - ' . $i);
            }
            self::save($rec);
        }
    }
    
    
    /**
     * Добавя бутони
     */
    public function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        // Ако имаме права за single
        if ($mvc->haveRightFor('single', $data->rec)) {
            if (($data->rec->emlFile) && fileman_Files::haveRightFor('single', $data->rec->emlFile)) {
                
                // Име на бутона
                if ($data->rec->htmlFile) {
                    $buttonName = 'Изглед';
                } else {
                    $buttonName = 'Детайли';
                }
                
                // Добавяме бутон за разглеждане не EML файла
                $data->toolbar->addBtn(
                    $buttonName,
                    array(
                        'fileman_Files',
                        'single',
                        'id' => fileman_Files::fetchField($data->rec->emlFile, 'fileHnd'),
                    ),
                    null,
                array('order' => '21', 'ef_icon' => 'img/16/file_extension_eml.png', 'title' => 'Преглед на различните части на имейла')
                );
            }
            
            // Ако е оттеглен, да не се препраща
            if ($data->rec->state != 'rejected' && email_Outgoings::haveRightFor('add')) {
                
                // Добавяме бутон за препращане на имейла
                $data->toolbar->addBtn(
                    'Препращане',
                    array(
                        'email_Outgoings',
                        'forward',
                        $data->rec->containerId,
                        'ret_url' => true,
                    ),
                    null,
                    array('order' => '19', 'row' => '2', 'ef_icon' => 'img/16/email_forward.png', 'title' => 'Препращане на имейла')
                );
            }
            
            if ($data->rec->state != 'rejected') {
                $rec = $data->rec;
                
                $cover = doc_Folders::getCover($rec->folderId);
                $quatation = false;
                if ($cover->haveInterface('crm_ContragentAccRegIntf')) {
                    if (sales_Quotations::haveRightFor('add', (object) array('folderId' => $rec->folderId, 'threadId' => $rec->threadId))) {
                        $data->toolbar->addBtn('Оферта', array('sales_Quotations', 'add', 'originId' => $rec->containerId), 'ef_icon=img/16/quotation.png,title=Създаване на оферта по това запитване');
                        $quatation = true;
                    }
                }
                
                // Създаване на нов артикул от входящ имейл
                if (!$quatation && cat_Products::haveRightFor('add', (object) array('folderId' => $rec->folderId, 'threadId' => $rec->threadId))) {
                    $innerClass = null;
                    
                    $Products = cls::get('cat_Products');
                    
                    // Намира се последния избиран драйвер в папката
                    $lastDriver = cond_plg_DefaultValues::getFromLastDocument($Products, $rec->folderId, 'innerClass');
                    if (!$lastDriver) {
                        $lastDriver = cat_GeneralProductDriver::getClassId();
                    }
                    
                    // Ако може да бъде избран
                    if (!empty($lastDriver)) {
                        if (cls::load($lastDriver, true)) {
                            if (cls::get($lastDriver)->canSelectDriver()) {
                                $innerClass = $lastDriver;
                            }
                        }
                    }
                    
                    $url = array('cat_Products', 'add', 'innerClass' => $innerClass, 'foreignId' => $rec->containerId, 'ret_url' => true);
                    if ($cover->haveInterface('crm_ContragentAccRegIntf')) {
                        $url['folderId'] = $rec->folderId;
                        $url['threadId'] = $rec->threadId;
                    }
                    
                    $data->toolbar->addBtn('Артикул', $url, 'ef_icon=img/16/wooden-box.png,title=Създаване на артикул по това запитване');
                }
            }
        }

        if (email_ServiceRules::haveRightFor('add')) {

            $url = array('email_ServiceRules', 'add', 'email' => $rec->fromEml, 'subject' => $rec->subject, 'ret_url' => true);

            $data->toolbar->addBtn('Правило', $url, 'ef_icon=img/16/page_lightning-new.png, title=Създаване на правило, row=2, order=19');
        }
    }
    
    
    /**
     * Връща EML файл при генериране на възможности разширения за прикачване
     */
    public function on_BeforeGetTypeConvertingsByClass($mvc, &$res, $id)
    {
        //Превръщаме $res в масив
        $res = (array) $res;
        
        // Вземаме манипулатора на файла
        $name = $mvc->getHandle($id);
        
        //Името на файла е с големи букви, както са документите
        $name = strtoupper($name) . '.eml';
        
        // Ако размера е над допустимите за изпращане, да не се добавя автоматично
        $attach = 'on';
        if ($mvc->checkSizeForAttach($id) === false) {
            $attach = 'off';
        }
        
        //Задаваме полето за избор, да е избран по подразбиране
        $res[$name] = $attach;
    }
    
    
    /**
     * Проверява дали документа може да се праща по имейл
     * В зависимост от големината на EML файла
     *
     * @param email_Incomings $mvc
     * @param mixed           $res
     * @param int             $id
     */
    public function on_BeforeCheckSizeForAttach($mvc, &$res, $id)
    {
        // Записа
        $rec = $mvc->fetch($id);
        $emlFile = $rec->emlFile;
        
        if (!$emlFile) {
            
            return ;
        }
        
        // Записа за EML файла
        $fRec = fileman_Files::fetch($emlFile);
        
        if (!$fRec || !$fRec->dataId) {
            
            return ;
        }
        
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
     * @param array    $res      масив с манипулатор на файл (@see fileman)
     * @param int      $id       първичен ключ на документа
     * @param string   $type     формат, в който да се генерира съдържанието на док.
     * @param string   $fileName име на файл, в който да се запише резултата
     */
    public static function on_BeforeConvertTo($mvc, &$res, $id, $type, $fileName = null)
    {
        // Преобразуваме в масив
        $res = (array) $res;
        
        switch (strtolower($type)) {
            case 'eml':
                
                $emlFileId = null;
                $fh = null;
                
                if ($id) {
                    // Вземаме id' то на EML файла
                    $emlFileId = $mvc->fetchField($id, 'emlFile');
                }
                
                if ($emlFileId) {
                    // Манипулатора на файла
                    $fh = fileman_Files::fetchField($emlFileId, 'fileHnd');
                }
                
                // Добавяме в масива
                if ($fh) {
                    $res[$fh] = $fh;
                }
            
            break;
        }
    }
    
    
    /**
     *
     *
     * @param email_Incomings $mvc
     * @param NULL|int        $res
     * @param int             $id
     * @param string          $type
     */
    public function on_BeforeGetDocumentSize($mvc, &$res, $id, $type)
    {
        switch (strtolower($type)) {
            case 'eml':
                
                $emlFileId = null;
                $dataId = null;
                
                if ($id) {
                    // Вземаме id' то на EML файла
                    $emlFileId = $mvc->fetchField($id, 'emlFile');
                }
                
                if ($emlFileId) {
                    // Манипулатора на файла
                    $dataId = fileman_Files::fetchField($emlFileId, 'dataId');
                }
                
                if ($dataId) {
                    $res = fileman_Data::fetchField($dataId, 'fileLen');
                }
            break;
        }
    }
    
    
    /**
     * Връща прикачените файлове
     *
     * @param object $rec - Запис
     */
    public function getLinkedFiles($rec)
    {
        // Ако не е обект
        if (!is_object($rec)) {
            
            // Вземаме записите за файла
            $rec = $this->fetch($rec);
        }

        if (!property_exists($rec, 'files') && $rec->id) {
            $cRec = $this->fetch($rec->id);
        } else {
            $cRec = clone $rec;
        }

        // Превръщаме в масив
        $filesArr = keylist::toArray($cRec->files);
        
        // Ако има HTML файл, добавяме го към файловете
        if ($cRec->htmlFile) {
            $filesArr[$cRec->htmlFile] = $cRec->htmlFile;
        }
        
        // Ако има, добавяме EML файла, към файловете
        if ($cRec->emlFile) {
            $filesArr[$cRec->emlFile] = $cRec->emlFile;
        }
        
        $fhArr = array();
        
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
     *
     * @param int|null $id
     *
     * @return string|null
     */
    public function getIcon_($id = null)
    {
        $rec = self::fetch($id);
        
        $files = keylist::toArray($rec->files);
        
        if ($rec->htmlFile) {
            unset($files[$rec->htmlFile]);
        }
        
        if (countR($files)) {
            
            return 'img/16/email-attach.png';
        }
    }
    
    
    /**
     * Разширява query-то в doc_DocumentPlg, като добавя и имейла от който е получен
     *
     * @param email_Incomings $mvc
     * @param core_Query      $query
     * @param int             $folderId
     * @param array           $params
     *
     * @return core_Query
     */
    public static function on_AfterGetSameFirstDocumentsQuery($mvc, &$query, $folderId, $params = array())
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
     * @param int $id
     */
    public function getLangFromRec($id)
    {
        if (!$id) {
            
            return ;
        }
        
        $rec = $this->fetch($id);
        
        return $rec->lg;
    }
    
    
    /**
     * Намираме потребители, които да се нотифицират допълнително за документа
     * Извън споделени/абонирани в нишката
     *
     * @param stdClass $rec
     *
     * @return array
     */
    public function getUsersArrForNotifyInDoc($rec)
    {
        if (!isset($rec->userInboxes)) {
            $this->updateUserInboxes($rec);
        }
        
        $userInboxes = type_Keylist::toArray($rec->userInboxes);
        
        $usersArr = email_Inboxes::getInChargeForInboxes($userInboxes);
        
        return $usersArr;
    }
}
