<?php 


/**
 * Максимално време за еднократно фетчване на писма
 */
defIfNot('IMAP_MAX_FETCHING_TIME', 30);


/**
 * Максималната разрешена памет за използване
 */
defIfNot('MAX_ALLOWED_MEMORY', '800M');


/**
 * Входящи писма
 *
 *
 * @category  all
 * @package   email
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Incomings extends core_Master
{
    
    
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
     * Кой има право да чете?
     */
    var $canRead = 'admin, email';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin, email';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, email';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin, email';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой има права за
     */
    var $canEmail = 'admin, email';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'email_Wrapper, doc_DocumentPlg, plg_RowTools, 
         plg_Printing, email_plg_Document, doc_EmailCreatePlg';
    
    
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
    var $abbr = "MSG";
    
    
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
    var $listFields = 'id,subject,date,fromEml=От,toEml=До,accId,boxIndex,country';
    
    
    /**
     * Шаблон за име на папките, където отиват писмата от дадена държава и неподлежащи на
     * по-адекватно сортиране
     */
    const UnsortableCountryFolderName = 'Unsorted - %s';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('accId', 'key(mvc=email_Inboxes,select=email)', 'caption=Акаунт');
        $this->FLD("messageId", "varchar", "caption=Съобщение ID");
        $this->FLD("subject", "varchar", "caption=Тема");
        $this->FLD("fromEml", "email", 'caption=От->Имейл');
        $this->FLD("fromName", "varchar", 'caption=От->Име');
        $this->FLD("toEml", "email", 'caption=До->Имейл');
        $this->FLD("toBox", "email", 'caption=До->Кутия');
        $this->FLD("headers", "text", 'caption=Хедъри');
        $this->FLD("textPart", "richtext", 'caption=Текстова част');
        $this->FLD("spam", "int", 'caption=Спам');
        $this->FLD("lg", "varchar", 'caption=Език');
        $this->FLD("date", "datetime(format=smartTime)", 'caption=Дата');
        $this->FLD('hash', 'varchar(32)', 'caption=Keш');
        $this->FLD('country', 'key(mvc=drdata_countries,select=commonName)', 'caption=Държава');
        $this->FLD('fromIp', 'ip', 'caption=IP');
        $this->FLD('files', 'keylist(mvc=fileman_Files)', 'caption=Файлове, input=none');
        $this->FLD('emlFile', 'key(mvc=fileman_Files)', 'caption=eml файл, input=none');
        $this->FLD('htmlFile', 'key(mvc=fileman_Files)', 'caption=html файл, input=none');
        $this->FLD('boxIndex', 'int', 'caption=Индекс');
        
        $this->setDbUnique('hash');
        
        
        /**
         * @todo Чака за документация...
         */
        defIfNot('UNSORTABLE_COUNTRY_EMAILS', static::UnsortableCountryFolderName);
    }
    
    
    /**
     * Взема записите от пощенската кутия и ги вкарва в модела
     *
     * @param number $oneMailId - Потребителя, за когото ще се проверяват записите.
     * Ако е празен, тогава ще се проверяват за всички.
     * @param boolean $deleteFetched TRUE - изтрива писмото от IMAP при успешно изтегляне
     * @return boolean
     */
    function getMailInfo($oneMailId = FALSE, $deleteFetched = FALSE)
    {
        ini_set('memory_limit', MAX_ALLOWED_MEMORY);
        
        $accQuery = email_Inboxes::getQuery();
        
        while ($accRec = $accQuery->fetch("#state = 'active' AND #type = 'imap'")) {
            
            /* @var $imapConn email_Imap */
            $imapConn = cls::get('email_Imap', array('host' => $accRec->server,
                    'port' => $accRec->port,
                    'user' => $accRec->user,
                    'pass' => $accRec->password,
                    'subHost' => $accRec->subHost,
                    'folder' => "INBOX",
                    'ssl' => $accRec->ssl));
            
            // Логването и генериране на съобщение при грешка е винаги в контролерната част
            if ($imapConn->connect() === FALSE) {
                
                $this->log("Не може да се установи връзка с пощенската кутия на <b>\"{$accRec->user} ({$accRec->server})\"</b>. " .
                    "Грешка: " . $imapConn->getLastError());
                
                $htmlRes .= "\n<li style='color:red'> Възникна грешка при опит да се свържем с пощенската кутия: <b>{$arr['user']}</b>" .
                $imapConn->getLastError() .
                "</li>";
                
                continue;
            }
            
            $htmlRes .= "\n<li> Връзка с пощенската кутия на: <b>\"{$accRec->user} ({$accRec->server})\"</b></li>";
            
            // Получаваме броя на писмата в INBOX папката
            $numMsg = $imapConn->getStatistic('messages');
            
            // До коя секунда в бъдещето максимално да се теглят писма?
            $maxTime = time() + IMAP_MAX_FETCHING_TIME;
            
            // даваме достатъчно време за изпълнението на PHP скрипта
            set_time_limit(IMAP_MAX_FETCHING_TIME + 49);
            
            // Правим цикъл по всички съобщения в пощенската кутия
            // Цикълът може да прекъсне, ако надвишим максималното време за сваляне на писма
            // Реверсивно изтегляне: 
            // Прогресивно извличане: ($i = 504; ($i <= $numMsg) && ($maxTime > time()); $i++)
            for ($i = $numMsg; ($i >= 1) && ($maxTime > time()); $i--) {
                
                $mimeParser = new email_Mime();
                $rec = $this->fetchSingleMessage($i, $imapConn, $mimeParser);
                
                if ($rec->id) {
                    // Писмото вече е било извличано и е записано в БД. $rec съдържа данните му.
                    // Debug::log("Е-имейл MSG_NUM = $i е вече при нас, пропускаме го");
                    $htmlRes .= "\n<li> Skip: {$rec->hash}</li>";
                } elseif(!$rec) {
                    // Възникнала е грешка при извличането на това писмо
                    // Debug::log("Е-имейл MSG_NUM = $i е вече при нас, пропускаме го");
                    $htmlRes .= "\n<li> Error: msg = {$i}</li>";
                } else {
                    // Ново писмо. 
                    $htmlRes .= "\n<li style='color:green'> Get: {$rec->hash}</li>";
                    $rec->accId = $accRec->id;
                    
                    /**
                     * Служебните писма не подлежат на рутинно рутиране. Те се рутират по други
                     * правила.
                     *
                     * Забележка 1: Не вграждаме логиката за рутиране на служебни писма в процеса
                     *              на рутиране, защото той се задейства след запис на писмото
                     *              което означава, че писмото трябва все пак да бъде записано.
                     *
                     *              По този начин запазваме възможността да не записваме
                     *              служебните писма.
                     *
                     * Забележка 2: Въпреки "Забележка 1", все пак може да записваме и служебните
                     *              писма (при условие че подсигурим, че те няма да се рутират
                     *              стандартно). Докато не изтриваме писмата от сървъра след
                     *              сваляне е добра идея да ги записваме в БД, иначе няма как да
                     *              знаем дали вече не са извършени (еднократните) действия
                     *              свързани с обработката на служебно писмо. Т.е. бихме
                     *              добавяли в лога на писмата по един запис за върнато писмо
                     *              (например) всеки път след изтегляне на писмата от сървъра.
                     *
                     *
                     */
                    
                    $this->processServiceMail($rec);  // <- Задава $rec->isServiceMail = TRUE за
                    //    служебните писма
                    
                    if (!$rec->isServiceMail) {
                        // Не записваме (и следователно - не рутираме) сервизната поща. 
                        //Debug::log("Записваме имейл MSG_NUM = $i");
                        
                        // Тук може да решим да не записваме служебните писма (т.е. онези, за които
                        // $rec->isServiceMail === TRUE)
                        $saved = email_Incomings::save($rec);
                        
                        // Добавя грешки, ако са възникнали при парсирането
                        if(count($mimeParser->errors)) {
                            foreach($mimeParser->errors as $err) {
                                $this->log($err . " ({$msgNum})", $rec->id);
                            }
                        }
                    }
                }
                
                if ($deleteFetched) {
                    // $imapConn->delete($i);
                }
            }
            
            $imapConn->expunge();
            
            $imapConn->close();
        }
        
        return $htmlRes;
    }
    
    
    /**
     * Проверява за служебно писмо (т.е. разписка, върнато) и ако е го обработва.
     *
     * Вдига флага $rec->isServiceMail в случай, че $rec съдържа служебно писмо.Обработката на
     * служебни писма включва запис в doc_Log.
     *
     * @param stdClass $rec запис на модел email_Incomings
     * @return boolean TRUE ако писмото е служебно
     */
    function processServiceMail($rec)
    {
        $rec->isServiceMail = FALSE;
        
        if ($mid = $this->isReturnedMail($rec)) {
            // Върнато писмо
            $rec->isServiceMail = email_Sent::returned($mid, $rec->date);
        } elseif ($mid = $this->isReceipt($rec)) {
            // Разписка
            $rec->isServiceMail = email_Sent::received($mid, $rec->date, $rec->fromIp);
        } else {
            // Не служебна поща
        }
        
        return $rec->isServiceMail;
    }
    
    
    /**
     * Проверява дали писмо е върнато.
     *
     * @param stdClass $rec запис на модел email_Incomings
     * @return string MID на писмото, ако наистина е върнато; FALSE в противен случай.
     */
    function isReturnedMail($rec)
    {
        if (!preg_match('/^.+\+returned=([a-z]+)@/i', $rec->toEml, $matches)) {
            return FALSE;
        }
        
        return $matches[1];
    }
    
    
    /**
     * Проверява дали съобщението е разписка за получено писмо
     *
     * @param stdClass $rec запис на модел email_Incomings
     * @return string MID на писмото, ако наистина е разписка; FALSE в противен случай.
     */
    function isReceipt($rec)
    {
        if (!preg_match('/^.+\+received=([a-z]+)@/i', $rec->toEml, $matches)) {
            return FALSE;
        }
        
        return $matches[1];
    }
    
    
    /**
     * Извлича едно писмо от пощенския сървър.
     *
     * Следи и пропуска (не извлича) вече извлечените писма.
     *
     * @param int $msgNum пореден номер на писмото за извличане
     * @param email_Imap $conn обект-връзка с пощенския сървър
     * @param email_Mime $mimeParser инстанция на парсер на MIME съобщения
     * @return stdClass запис на модел email_Incomings
     */
    function fetchSingleMessage($msgNum, $conn, $mimeParser)
    {
        // Debug::log("Започва обработката на е-имейл MSG_NUM = $msgNum");
        
        $headers = $conn->getHeaders($msgNum);
        
        // Ако няма хедъри, значи има грешка
        if(!$headers) return NULL;
        
        $hash    = $mimeParser->getHash($headers);
        
        if ((!$rec = $this->fetch("#hash = '{$hash}'"))) {
            // Писмото не е било извличано до сега. Извличаме го.
            // Debug::log("Сваляне на имейл MSG_NUM = $msgNum");
            $rawEmail = $conn->getEml($msgNum);
            
            // Debug::log("Парсираме и композираме записа за имейл MSG_NUM = $msgNum");
            $rec = $mimeParser->getEmail($rawEmail);
            
            // Ако не е получен запис, значи има грешка
            if(!$rec) return NULL;
            
            // Само за дебъг. Todo - да се махне
            $rec->boxIndex = $msgNum;
            
            // Проверка дали междувременно друг процес не е свалил и записал писмото
            $rec->id = $this->fetchField("#hash = '{$hash}'", 'id');
        }
        
        return $rec;
    }
    
    
    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields)
    {
        $rec->textPart = trim($rec->textPart);
    }
    
    
    /**
     * Преобразува containerId в машинен вид
     */
    function on_AfterRecToVerbal($mvc, &$row, $rec, $fields)
    {
        if(!$rec->subject) {
            $row->subject .= '[' . tr('Липсва заглавие') . ']';
        }
        
        // Показва до събджекта номера на писмото от пощенската кутия
        // $row->subject .= " ($rec->boxIndex)";
        
        if($fields['-single']) {
            if ($rec->files) {
                $vals = type_Keylist::toArray($rec->files);
                
                if (count($vals)) {
                    $row->files = '';
                    
                    foreach ($vals as $keyD) {
                        $row->files .= fileman_Download::getDownloadLinkById($keyD);
                    }
                }
            }
            
            if($rec->emlFile) {
                $row->emlFile = fileman_Download::getDownloadLinkById($rec->emlFile);
            }
            
            if($rec->htmlFile) {
                $row->htmlFile = fileman_Download::getDownloadLinkById($rec->htmlFile);
            }
        }
        
        if(!$rec->toBox) {
            $row->toBox = $row->toEml;
        }
        
        if($rec->fromIp && $rec->country) {
            $row->fromIp .= " ($row->country)";
        }
        
        if(trim($row->fromName) && (strtolower(trim($rec->fromName)) != strtolower(trim($rec->fromEml)))) {
            $row->fromEml = $row->fromEml . ' (' . trim($row->fromName) . ')';
        }
        
        $pattern = '/\s*[0-9a-f_A-F]+.eml\s*/';
        $row->emlFile = preg_replace($pattern, 'EMAIL.eml', $row->emlFile);
        
        $pattern = '/\s*[0-9a-f_A-F]+.html\s*/';
        
        //$row->htmlFile = preg_replace($pattern, 'EMAIL.html', $row->htmlFile);
        
        $row->files .= $row->emlFile . $row->htmlFile;
        
        if($fields['-list']) {
            $row->textPart = mb_Substr($row->textPart, 0, 100);
        }
    }
    
    
    /**
     * Да сваля имейлите
     */
    function act_DownloadEmails()
    {
        requireRole('admin');
        
        $mailInfo = $this->getMailInfo();
        
        return $mailInfo;
    }
    
    
    /**
     * Сваля и изтрива от IMAP свалените имейли.
     */
    function act_DownloadAndDelete()
    {
        $mailInfo = $this->getMailInfo(NULL, TRUE /* изтриване след изтегляне */);
        
        return $mailInfo;
    }
    
    
    /**
     * Да сваля имейлите по - крон
     */
    function cron_DownloadEmails()
    {
        $mailInfo = $this->getMailInfo();
        
        return $mailInfo;
    }
    
    
    /**
     * Cron екшън за опресняване на публичните домейни
     */
    function cron_UpdatePublicDomains()
    {
        $domains = static::scanForPublicDomains();
        
        $out .= "<li>Сканирани " . count($domains) . " домейн(а) ... </li>";
        
        $stats   = drdata_Domains::resetPublicDomains($domains);
        
        $out .= "<li>Добавени {$stats['added']}, изтрити {$stats['removed']} домейн(а)</li>";
        
        if ($stats['addErrors']) {
            $out .= "<li class=\"error\">Проблем при добавянето на {$stats['addErrors']} домейн(а)!</li>";
        }
        
        if ($stats['removeErrors']) {
            $out .= "<li class=\"error\">Проблем при изтриването на {$stats['removeErrors']} домейн(а)!</li>";
        }
        
        $out = ""
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
        return static::cron_UpdatePublicDomains();
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    function on_AfterSetupMVC($mvc, &$res)
    {
        $res .= "<p><i>Нагласяне на Cron</i></p>";
        
        $rec->systemId = 'DownloadEmails';
        $rec->description = 'Сваля и-имейлите в модела';
        $rec->controller = $this->className;
        $rec->action = 'DownloadEmails';
        $rec->period = 2;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 100;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да сваля имейлите в модела.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да сваля имейлите.</li>";
        }
        
        return $res;
    }
    
    /******************************************************************************************
     *
     * ИМПЛЕМЕНТАЦИЯ НА email_DocumentIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * Текстов вид (plain text) на документ при изпращането му по имейл
     *
     * @param int $id ид на документ
     * @param string $emailTo
     * @param string $boxFrom
     * @return string plain text
     */
    public function getEmailText($id, $emailTo = NULL, $boxFrom = NULL)
    {
        return static::fetchField($id, 'textPart');
    }
    
    
    /**
     * Прикачените към документ файлове
     *
     * @param int $id ид на документ
     * @return array
     */
    public function getEmailAttachments($id)
    {
        
        /**
         * @TODO
         */
        return array();
    }
    
    
    /**
     * Какъв да е събджекта на писмото по подразбиране
     *
     * @param int $id ид на документ
     * @param string $emailTo
     * @param string $boxFrom
     * @return string
     */
    public function getDefaultSubject($id, $emailTo = NULL, $boxFrom = NULL)
    {
        return 'FW: ' . static::fetchField($id, 'subject');
    }
    
    
    /**
     * До кой имейл или списък с етрябва да се изпрати писмото
     *
     * @param int $id ид на документ
     */
    public function getDefaultEmailTo($id)
    {
        return '';
    }
    
    
    /**
     * Адреса на изпращач по подразбиране за документите от този тип.
     *
     * @param int $id ид на документ
     * @return int key(mvc=email_Inboxes) пощенска кутия от нашата система
     */
    public function getDefaultBoxFrom($id)
    {
        
        /**
         * @TODO Това вероятно трябва да е inbox-а на текущия потребител.
         */
        return 'me@here.com';
    }
    
    
    /**
     * Писмото (ако има такова), в отговор на което е направен този постинг
     *
     * @param int $id ид на документ
     * @return int key(email_Incomings) NULL ако документа не е изпратен като отговор
     */
    public function getInReplayTo($id)
    {
        
        return NULL;
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
            $row->author = $this->getVerbal($rec, 'fromName');
        } else {
            $row->author = "<small>{$rec->fromEml}</small>";
        }
        
        $row->authorEmail = $rec->fromEml;
        
        $row->state = $rec->state;
        
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
    public function route_($rec)
    {
        // Правилата за рутиране, подредени по приоритет. Първото правило, след което съобщението
        // има нишка и/или папка прекъсва процеса - рутирането е успешно.
        $rules = array(
            'ByThread',
            'ByFromTo',
            'ByFrom',
            'Spam',
            'ByDomain',
            'ByPlace',
            'ByTo',
        );
        
        foreach ($rules as $rule) {
            $ruleMethod = 'route' . $rule;
            
            if (method_exists($this, $ruleMethod)) {
                $this->{$ruleMethod}($rec);
                
                if ($rec->folderId || $rec->threadId) {
                    break;
                }
            }
        }
    }
    
    
    /**
     * Извлича при възможност нишката от наличната информация в писмото
     *
     * Местата, където очакваме информация за манипулатор на тред са:
     *
     * o `In-Reply-To` (MIME хедър)
     * o `Subject`
     *
     * @param stdClass $rec
     */
    function routeByThread($rec)
    {
        $rec->threadId = static::extractThreadFromReplyTo($rec);
        
        if (!$rec->threadId) {
            $rec->threadId = static::extractThreadFromSubject($rec);
        }
        
        if ($rec->threadId) {
            // Премахване на манипулатора на нишката от събджекта
            static::stripThreadHandle($rec);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function routeByFromTo($rec)
    {
        if (!static::isGenericRecipient($rec)) {
            // Това правило не се прилага за "общи" имейли
            $rec->folderId = static::routeByRule($rec, email_Router::RuleFromTo);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function routeByFrom($rec)
    {
        if (static::isGenericRecipient($rec)) {
            // Това правило се прилага само за "общи" имейли
            $rec->folderId = static::routeByRule($rec, email_Router::RuleFrom);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function routeSpam($rec)
    {
        if ($this->isSpam($rec)) {
            $rec->isSpam = TRUE;
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function routeByDomain($rec)
    {
        if (static::isGenericRecipient($rec) && !$rec->isSpam) {
            $rec->folderId = static::routeByRule($rec, email_Router::RuleDomain);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function routeByPlace($rec) {
        if (static::isGenericRecipient($rec) && !$rec->isSpam && $rec->country) {
            $rec->folderId = $this->forceCountryFolder($rec->country /* key(mvc=drdata_Countries) */);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function routeByTo($rec)
    {
        if (empty($rec->toBox)) {
            $email = email_Inboxes::fetchField($rec->accId, 'email');
        } else {
            $email = $rec->toBox;
        }
        
        $rec->folderId = email_Inboxes::forceFolder($email);
        
        expect($rec->folderId);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function routeByRule($rec, $type)
    {
        return email_Router::route($rec->fromEml, $rec->toEml, $type);
    }
    
    /**
     * Извлича нишката от 'In-Reply-To' MIME хедър
     *
     * @param stdClass $rec
     * @return int първичен ключ на нишка или NULL
     */
    protected static function extractThreadFromReplyTo($rec)
    {
        if (!$rec->inReplyTo) {
            return NULL;
        }
        
        if (!($mid = email_util_ThreadHandle::extractMid($rec->inReplyTo))) {
            return NULL;
        }
        
        if (!($sentRec = email_Sent::fetchByMid($mid, 'containerId, threadId'))) {
            return NULL;
        }
        
        $rec->originId = $sentRec->containerId;
        
        return $sentRec->threadId;
    }
    
    /**
     * Извлича нишката от 'Subject'-а
     *
     * @param stdClass $rec
     * @return int първичен ключ на нишка или NULL
     */
    protected static function extractThreadFromSubject($rec)
    {
        $subject = $rec->subject;
        
        // Списък от манипулатори на нишки, за които е сигурно, че не са наши
        $blackList = array();
        
        if ($rec->bgerpSignature) {
            // Възможно е това писмо да идва от друга инстанция на BGERP.
            list($foreignThread, $foreignDomain) = preg_split('/\s*;\s*/', $rec->bgerpSignature, 2);
            
            if ($foreignDomain != BGERP_DEFAULT_EMAIL_DOMAIN) {
                // Да, друга инстанция;
                $blackList[] = $foreignThread;
            }
        }
        
        // Списък от манипулатори на нишка, които може и да са наши
        $whiteList = email_util_ThreadHandle::extract($subject);
        
        // Махаме 'чуждите' манипулатори
        $whiteList = array_diff($whiteList, $blackList);
        
        // Проверяваме останалите последователно 
        foreach ($whiteList as $handle) {
            if ($threadId = static::getThreadByHandle($handle)) {
                break;
            }
        }
        
        return $threadId;
    }
    
    /**
     * Намира тред по хендъл на тред.
     *
     * @param string $handle хендъл на тред
     * @return int key(mvc=doc_Threads) NULL ако няма съответен на хендъла тред
     */
    protected static function getThreadByHandle($handle)
    {
        return doc_Threads::getByHandle($handle);
    }
    
    
    /**
     * Създава при нужда и връща ИД на папката на държава
     *
     * @param int $countryId key(mvc=drdata_Countries)
     * @return int key(mvc=doc_Folders)
     */
    function forceCountryFolder($countryId)
    {
        $folderId = NULL;
        
        /**
         * @TODO: Идея: да направим клас email_Countries (или може би bgerp_Countries) наследник
         * на drdata_Countries и този клас да стане корица на папка. Тогава този метод би
         * изглеждал така:
         *
         * $folderId = email_Countries::forceCoverAndFolder(
         *         (object)array(
         *             'id' => $countryId
         *         )
         * );
         *
         * Това е по-ясно, а и зависимостта от константата UNSORTABLE_COUNTRY_EMAILS отива на
         * 'правилното' място.
         */
        
        $countryName = $this->getCountryName($countryId);
        
        if (!empty($countryName)) {
            $folderId = doc_UnsortedFolders::forceCoverAndFolder(
                (object)array(
                    'name' => sprintf(UNSORTABLE_COUNTRY_EMAILS, $countryName)
                )
            );
        }
        
        return $folderId;
    }
    
    
    /**
     * Връща името на държавата от която е пратен имейл-а
     */
    protected function getCountryName($countryId)
    {
        if ($countryId) {
            $countryName = drdata_Countries::fetchField($countryId, 'commonName');
        }
        
        return $countryName;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function isGenericRecipient($rec)
    {
        return email_Inboxes::isGeneric($rec->toEml);
    }
    
    
    /**
     * Преди вкарване на запис в модела
     */
    function on_BeforeSave($mvc, $id, &$rec) {
        //При сваляне на имейл-а, състоянието е затворено
        
        if (!$rec->id) {
            $rec->state = 'closed';
            $rec->_isNew = TRUE;
        }
    }
    
    /**
     * @todo Чака за документация...
     */
    static function stripThreadHandle($rec)
    {
        expect($rec->threadId);
        
        $threadHandle = doc_Threads::getHandle($rec->threadId);
        
        $rec->subject = email_util_ThreadHandle::strip($rec->subject, $threadHandle);
    }
    
    
    /**
     * Извиква се след вкарване на запис в таблицата на модела
     */
    function on_AfterSave($mvc, $id, $rec)
    {
        static::needFields($rec, 'fromEml, toEml, date, containerId,threadId');
        
        if ($rec->state == 'rejected') {
            $mvc->removeRouterRules($rec);
        } else {
            $mvc->makeRouterRules($rec);
        }
    }
    
    
    /**
     * След изтриване на записи на модела
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param core_Query $query
     */
    function on_AfterDelete($mvc, &$res, $query)
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
        $query->orderBy('date', 'DESC');
        $query->limit(3);    // 3 писма
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
        if (!static::isGenericRecipient($rec)) {
            $key = email_Router::getRoutingKey($rec->fromEml, $rec->toEml, email_Router::RuleFromTo);
            
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
        if (static::isGenericRecipient($rec) && ($key = email_Router::getRoutingKey($rec->fromEml, NULL, email_Router::RuleDomain))) {
            
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
     * Връща данните за адресанта
     */
    static function getContragentData($id)
    {
        //Данните за имейл-а
        $msg = email_Incomings::fetch($id);
        
        $addrParse = cls::get('drdata_Address');
        $ap = $addrParse->extractContact($msg->textPart);
        
        if(count($ap['company'])) {
            $contragentData->company = arr::getMaxValueKey($ap['company']);
            
            if(count($ap['company'] > 1)){
                foreach($ap['company'] as $cName => $prob) {
                    $contragentData->companyArr[$cName] =  $cName;
                }
            }
        }
        
        if(count($ap['tel'])) {
            $contragentData->tel = arr::getMaxValueKey($ap['tel']);
        }
        
        if(count($ap['fax'])) {
            $contragentData->fax = arr::getMaxValueKey($ap['fax']);
        }
        
        $contragentData->email = $msg->fromEml;
        $contragentData->countryId = $msg->country;
        
        return $contragentData;
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото наимей по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        //Вземаме датата от базата от данни
        $rec = email_Incomings::fetch($id, 'date');
        
        //Вербализираме датата
        $date = dt::mysql2verbal($rec->date, 'd-M H:i');
        
        //Създаваме шаблона
        $text = tr('Благодаря за имейл-а от') . " {$date}.\n" ;
        
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
        $crmCompaniesClassId = core_Classes::fetchIdByName('crm_Companies');
        
        // Построяваме заявка, извличаща всички писма, които са във фирмена папка.
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->EXT('coverClass', 'doc_Folders', 'externalKey=folderId');
        $query->where("#coverClass = {$crmCompaniesClassId}");
        $query->show('fromEml, folderId');
        
        $domains = array();
        $result  = array();
        
        while ($rec = $query->fetch()) {
            $fromDomain = type_Email::domain($rec->fromEml);
            $domains[$fromDomain][$rec->folderId] = TRUE;
            
            if (count($domains[$fromDomain]) > 1) {
                // От $fromDomain има поне 2 писма, които са в различни фирмени папки
                $results[$fromDomain] = TRUE;
            }
        }
        
        return $result;
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
    static function getExternalEmails($threadId)
    {
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->where("#threadId = {$threadId}");
        $query->show('fromEml');
        
        $result = array();
        
        while ($rec = $query->fetch()) {
            if($eml = trim($rec->fromEml)) {
                $result[$eml] = $eml;
            }
        }
        
        return $result;
    }
}
