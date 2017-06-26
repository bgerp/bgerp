<?php 


/**
 * Шаблон за писма за масово разпращане
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 *
 * @method string getHandle(integer $id)
 * @method string getVerbalSizesFromArray(array $arr)
 * @method boolean checkMaxAttachedSize(array $attachSizeArr)
 * @method array getFilesSizes(array $sizeArr)
 * @method array getDocumentsSizes(array $docsArr)
 * @method array getAttachments(object $aRec)
 * @method array getPossibleTypeConvertings(object $cRec)
 */
class blast_Emails extends core_Master
{
    
    /**
     * Име на папката по подразбиране при създаване на нови документи от този тип.
     * Ако стойноста е 'FALSE', нови документи от този тип се създават в основната папка на потребителя
     */
    public $defaultFolder = 'Циркулярни имейли';
    
    
    /**
     * Заглавие на таблицата
     */
    public $title = "Циркулярни имейли";
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Циркулярен имейл";
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/emails.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Inf';
    
    
    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    public $rowToolsSingleField = 'subject';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Кой има право да чете?
     */
    protected $canRead = 'ceo, blast';
    
    
    /**
     * Кой има право да променя?
     */
    protected $canEdit = 'ceo, blast';
    
    
    /**
     * Кой има право да клонира?
     */
    public $canClonerec = 'ceo, blast';
    
    
    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'ceo, blast';
    
    
    /**
     * Кой може да го види?
     */
    protected $canView = 'ceo, blast';
    
    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'ceo, blast';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    protected $canSingle = 'ceo, blast';
    
    
    /**
     * Кой може да оттелгя имейла
     */
    protected $canReject = 'ceo, blast';
    
    
    /**
     * Кой може да активира имейла
     */
    protected $canActivate = 'ceo, blast';
    
    
    /**
     * Кой може да обновява списъка с детайлите
     */
    protected $canUpdate = 'ceo, blast';
    
    
    /**
     * Кой може да спира имейла
     */
    protected $canStop = 'ceo, blast';
   
    
    /**
     * Кой може да го изтрие?
     */
    protected $canDelete = 'no_one';
    
    
    /**
     * Кой може да праша информационните съобщения?
     */
    protected $canBlast = 'ceo, blast';
    
    
    /**
     * Кой може да променя активирани записи
     * @see change_Plugin
     */
    protected $canChangerec = 'blast, ceo';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'email_DocumentIntf';
    
    
    /**
     * Плъгините и враперите, които ще се използват
     */
    public $loadList = 'blast_Wrapper, doc_DocumentPlg, plg_RowTools2, bgerp_plg_blank, change_Plugin, plg_Search, plg_Clone';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене
     * @see plg_Search
     */
    public $searchFields = 'subject, body';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    protected $listFields = 'id, subject, srcLink, from, sendPerCall, sendingDay, sendingFrom, sendingTo';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'blast_EmailSend';
    
    
    /**
     * Нов темплейт за показване
     */
    protected $singleLayoutFile = 'blast/tpl/SingleLayoutEmails.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "2.2|Циркулярни";
    
    
    /**
     * id на системата в крона
     */
    protected static $cronSytemId = 'SendEmails';
    
    
    /**
     * Описание на модела
     */
    protected function description()
    {
        $this->FLD('perSrcClassId', 'class(interface=bgerp_PersonalizationSourceIntf)', 'caption=Източник на данни->Клас, silent, input=hidden');
        $this->FLD('perSrcObjectId', 'varchar(16)', 'caption=Списък, mandatory, silent, removeAndRefreshForm=unsubscribe|lg');
        
        $this->FLD('from', 'key(mvc=email_Inboxes, select=email)', 'caption=От, mandatory, changable');
        $this->FLD('subject', 'varchar', 'caption=Относно, width=100%, mandatory, changable');
        $this->FLD('body', 'richtext(rows=15,bucket=Blast)', 'caption=Съобщение,mandatory, changable');
        $this->FLD('unsubscribe', 'richtext(rows=3,bucket=Blast)', 'caption=Отписване, changable', array('attr' => array('id' => 'unsId')));
        $this->FLD('sendPerCall', 'int(min=1, max=100)', 'caption=Изпращания заедно, input=none, mandatory, oldFieldName=sendPerMinute, title=Брой изпращания заедно');
        
        $this->FLD('sendingFrom', 'time(suggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00)', 'caption=Начален час, input=none');
        $this->FLD('sendingTo', 'time(suggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00)', 'caption=Краен час, input=none');
        $this->FLD('sendingDay', 'set(1=Пон, 2=Вто, 3=Сря, 4=Чет, 5=Пет, 6=Съб, 0=Нед)', 'caption=Ден, input=none, columns=7');
        
        $this->FLD('activatedBy', 'key(mvc=core_Users)', 'caption=Активирано от, input=none');
        
        $this->FLD('progress', 'percent(min=0,max=1,decimals=0)', 'caption=Прогрес, input=none, notNull');
        
        //Данни на адресата - антетка
        $this->FLD('recipient', 'varchar', 'caption=Адресат->Фирма,class=contactData, changable');
        $this->FLD('attn', 'varchar', 'caption=Адресат->Име,oldFieldName=attentionOf,class=contactData, changable');
        $this->FLD('email', 'varchar', 'caption=Адресат->Имейл,class=contactData, changable');
        $this->FLD('tel', 'varchar', 'caption=Адресат->Тел.,class=contactData, changable');
        $this->FLD('fax', 'varchar', 'caption=Адресат->Факс,class=contactData, changable');
        $this->FLD('country', 'varchar', 'caption=Адресат->Държава,class=contactData, changable');
        $this->FLD('pcode', 'varchar', 'caption=Адресат->П. код,class=contactData, changable');
        $this->FLD('place', 'varchar', 'caption=Адресат->Град/с,class=contactData, changable');
        $this->FLD('address', 'varchar', 'caption=Адресат->Адрес,class=contactData, changable');
        
        $this->FLD('encoding', 'enum(utf-8=Уникод|* (UTF-8),
                                    cp1251=Windows Cyrillic|* (CP1251),
                                    koi8-r=Rus Cyrillic|* (KOI8-R),
                                    cp2152=Western|* (CP1252),
                                    ascii=Латиница|* (ASCII))', 'caption=Знаци, changable,notNull');
        
        $this->FLD('attachments', 'set(files=Файловете,documents=Документите)', 'caption=Прикачи, changable');
        
        cls::get('core_Lg');
        
        $this->FLD('lg', 'enum(auto=Автоматично, ' . EF_LANGUAGES . ')', 'caption=Език,changable,notNull');
        
        $this->FLD('errMsg', 'varchar', 'caption=Съобщение за грешка, input=none');
        
        $this->FNC('srcLink', 'varchar', 'caption=Списък');
    }
    
    
    /**
     * Създава имейл с посочените данни
     *
     * @param integer $perSrcClassId
     * @param integer $perSrcObjectId
     * @param string $text
     * @param string $subject
     * @param array $otherParams
     *
     * @return integer
     */
    public static function createEmail($perSrcClassId, $perSrcObjectId, $text, $subject, $otherParams = array())
    {
        // Задаваме стойност
        $rec = new stdClass();
        $rec->perSrcClassId = core_Classes::getId($perSrcClassId);
        $rec->perSrcObjectId = $perSrcObjectId;
        $rec->body = $text;
        $rec->subject = $subject;
        $rec->state = 'draft';
        
        expect($rec->perSrcClassId && $rec->perSrcObjectId, $rec);
        
        // Задаваме стойности за останалите полета
        foreach ((array)$otherParams as $fieldName => $value) {
            if ($rec->$fieldName) continue;
            $rec->$fieldName = $value;
        }
        
        // Ако не е зададен имейл на изпращача, да се използва дефолтният му 
        if (!$rec->from) {
            $rec->from = email_Outgoings::getDefaultInboxId();
        }
        
        expect($rec->from, 'Не може да се определи имейл по подразбиране за изпращача');
        
        // Записваме
        $id = self::save($rec);
        
        return $id;
    }
    
    
    /**
     * Активира имейла, като добавя и списъка с имейлите
     *
     * @param integer|object $id
     * @param integer $sendPerCall
     */
    public static function activateEmail($id, $sendPerCall = 5)
    {   
        // Записа
        $rec = self::getRec($id);
        
        self::logWrite('Активиран бласт имейл', $rec->id);
        
        expect($rec, 'Няма такъв запис');
        
        // Обновяваме списъка с имейлите
        $updateCnt = self::updateEmailList($id);
        
        // Активираме имейла
        $rec->state = 'active';
        $rec->activatedBy = core_Users::getCurrent();
        $rec->sendPerCall = $sendPerCall;
        self::save($rec);
        
        return $updateCnt;
    }
    
    /**
     * Обновява списъка с имейлите
     *
     * @param integer|object $id
     *
     * @return integer
     */
    protected static function updateEmailList($id)
    {
        // Записа
        $rec = self::getRec($id);
        
        expect($rec, 'Няма такъв запис');
        
        // Инстанция на класа за персонализация
        $srcClsInst = cls::get($rec->perSrcClassId);
        
        // Масива с данните за персонализация
        $personalizationArr = $srcClsInst->getPresonalizationArr($rec->perSrcObjectId);
        
        // Масив с типовете на полетата
        $descArr = $srcClsInst->getPersonalizationDescr($rec->perSrcObjectId);
        
        // Масив с всички имейл полета
        $emailFieldsArr = self::getEmailFields($descArr);
        
        expect($emailFieldsArr, 'Трябва да има поне едно поле за имейли');
        
        // Обновяваме листа и връщаме броя на обновленията
        $updateCnt = blast_EmailSend::updateList($rec->id, $personalizationArr, $emailFieldsArr);
        
        return $updateCnt;
    }
    
    
    /**
     * Връща записа
     *
     * @param integer|object $id
     *
     * @return object
     */
    protected static function getRec($id)
    {
        // Ако е обект, приемаме, че е подаден самия запис
        if (is_object($id)) {
            $rec = $id;
        } else {
            
            expect($id > 0);
            
            // Ако е id, фечваме записа
            $rec = self::fetch($id);
        }
        
        return $rec;
    }
    
    
    /**
     * Проверява дали не трябва да се спира процеса
     * 
     * @return boolean
     */
    protected static function checkTimelimit()
    {
        static $deadLine;
        
        if (!isset($deadLine)) {
            $deadLine = time() + blast_Setup::get('EMAILS_CRON_TIME_LIMIT');
            $deadLine -= 2;
        }
        
        if ($deadLine > time()) return TRUE;
        
        return FALSE;
    }
    
    
    /**
     * Проверява дали има имейли за изпращане, персонализира ги и ги изпраща
     * Вика се от `cron`
     */
    protected function sendEmails()
    {
        // Всички активни или чакащи имейли, на които им е дошло времето за стартиране
        $query = blast_Emails::getQuery();
        
        $query->where("#state = 'active'");
        $query->orWhere("#state = 'waiting'");
        
        $conf = core_Packs::getConfig('blast');
        
        // За да получим минути
        $period = $conf->BLAST_EMAILS_CRON_PERIOD;
        
        //Проверяваме дали имаме запис, който не е затворен и му е дошло времето за активиране
        while ($rec = $query->fetch()) {
            
            // Ако се изпраща от частна мрежа, спираме процеса
            if (core_App::checkCurrentHostIsPrivate()) {
                $this->logErr('Прекъснато изпращане на циркулярни имейли. Прави се опит за изпращане от частна мрежа', $rec->id);
                
                $rec->state = 'stopped';
                $rec->errMsg = '|Спряно разпращане, поради опит за изпращане от частен адрес.';
                
                $this->save($rec, 'state, errMsg');
                $this->touchRec($rec->id);
                
                break;
            }
            
            // Ако е свършило времето
            if (!$this->checkTimelimit()) {
                
                $this->logNotice('Прекъснато изпращане на циркулярни имейли', $rec->id);
                
                break;
            }
            
            $now = dt::now();
            $nextStartTime = self::getNextStartTime($rec, $now);
            
            // Вземаме секундите между сегашното време и времето на стартиране
            $sec = dt::secsBetween($nextStartTime, $now);
            
            if ($rec->state == 'waiting') {
                if (($sec <= 0) || ($sec <= $period)) {
                    $rec->state = 'active';
                    $this->save($rec, 'state');
                    $this->touchRec($rec->id);
                    
                    if (!($sec <= 0)) continue;
                }
            } elseif ($rec->state == 'active') {
                if ($sec > $period) {
                    $rec->state = 'waiting';
                    $this->save($rec, 'state');
                    $this->touchRec($rec->id);
                } elseif ($sec) {
                    continue ;
                }
            }
            
            // Само активните да се изпращат
            if ($rec->state != 'active') continue;
            
            // Вземаме данните за имейлите, до които ще пращаме
            $dataArr = blast_EmailSend::getDataArrForEmailId($rec->id, $rec->sendPerCall);
            
            // Ако няма данни, затваряме 
            if (empty($dataArr)) {
                $rec->state = 'closed';
                $rec->progress = 1;
                $this->save($rec, 'state, progress');
                $this->touchRec($rec->id);
                continue;
            }
            
            // Инстанция на обекта
            $srcClassInst = cls::get($rec->perSrcClassId);
            
            // Масив с полетата и описаниите за съответния обект
            $descArr = $srcClassInst->getPersonalizationDescr($rec->perSrcObjectId);
            
            // Маркираме имейлите, като изпратени
            blast_EmailSend::markAsSent($dataArr);
            
            // Вземаме всички полета, които могат да бъдат имейли
            $emailPlaceArr = self::getEmailFields($descArr);
            
            // Ако няма полета за имейли, няма смисъл да се праща
            if (empty($emailPlaceArr)) continue;
            
            $notSendDataArr = $dataArr;
            
            // Обхождаме всички получени данни
            foreach ((array)$dataArr as $detId => $detArr) {
                
                // Ако е свършило времето
                if (!$this->checkTimelimit()) {
                    
                    // Маркираме неизпратените имейли
                    blast_EmailSend::removeMarkAsSent($notSendDataArr);
                    
                    $this->logNotice('Прекъснато изпращане на циркулярни имейли', $rec->id);
                    
                    break;
                }
                
                $toEmail = '';
                
                // Обединяваме всички възможни имейли
                foreach ($emailPlaceArr as $place => $type) {
                    $emailsStr = $emailsStr ? ', ' . $detArr[$place] : $detArr[$place];
                }
                
                // Вземаме имейлите
                $emailsArr = type_Emails::toArray($emailsStr);
                
                // Първия валиден имейл, който не е в блокорани, да е получателя
                foreach ((array)$emailsArr as $email) {
                    if (blast_BlockedEmails::isBlocked($email)) continue;
                    $toEmail = $email;
                    break;
                }
                
                // Ако няма имейл, нямя до кого да се праща
                if (!$toEmail) continue;
                
                // Клонираме записа
                $cRec = clone $rec;

                // Имейла да се рендира и да се праща с правата на активатора
                core_Users::sudo($sudoUser = $cRec->activatedBy);
                
                // Задаваме екшъна за изпращането
                doclog_Documents::pushAction(
                    array(
                        'containerId' => $cRec->containerId,
                        'threadId' => $cRec->threadId,
                        'action' => doclog_Documents::ACTION_SEND,
                        'data' => (object)array(
                            'sendedBy' => core_Users::getCurrent(),
                            'from' => $cRec->from,
                            'to' => $toEmail,
                            'detId' => $detId,
                        )
                    )
                );
                
                // Вземаме персонализирания имейл за съответните данни
                $body = $this->getEmailBody($cRec, $detArr, TRUE);
                
                // Деескейпваме шаблоните в текстовата част
                $body->text = core_ET::unEscape($body->text);
                
                // Опитваме се да изпратим имейла
                try {
                    //Извикваме функцията за изпращане на имейли
                    $status = email_Sent::sendOne(
                        $cRec->from,
                        $toEmail,
                        $body->subject,
                        $body,
                        array(
                            'encoding' => $cRec->encoding,
                            'no_thread_hnd' => TRUE
                        )
                    );
                } catch (core_exception_Expect $e) {
                    $status = FALSE;
                }
                
                // Флушваме екшъна
                doclog_Documents::flushActions();
                
                // Връщаме стария потребител
                core_Users::exitSudo($sudoUser);
                                
                // Ако имейлът е изпратен успешно, добавяме времето на изпращане
                if ($status) {
                    
                    // Задаваме времето на изпращане и имейла изпращане
                    blast_EmailSend::setTimeAndEmail(array($detId => $toEmail));
                } else {
                    // Ако възникне грешка при изпращане, записваме имейла, като върнат
                    $this->logWarning("Върнато писмо", $rec->id);
                }
                
                unset($notSendDataArr[$detId]);
            }
            
            $rec->progress = blast_EmailSend::getSendingProgress($rec->id);
            $this->save($rec, 'progress');
            $this->touchRec($rec->id);
        }
    }


    /**
     * Преди записване на клонирания запис
     *
     * @param core_Mvc $mvc
     * @param object $rec
     * @param object $nRec
     *
     * @see plg_Clone
     */
    function on_BeforeSaveCloneRec($mvc, $rec, $nRec)
    {
        unset($nRec->progress);
        unset($nRec->activatedBy);
    }
    
    
    /**
     * Подготвяме данните в rec'а
     *
     * @param object $rec - Обект с данните
     * @param array $detArr
     */
    protected function prepareRec(&$rec, $detArr)
    {
        // Заместваме данните
        $this->replaceAllData($rec, $detArr);
    }
    
    
    /**
     * Замества плейсхолдърите с тяхната стойност
     *
     * @param object $rec
     * @param array $detArr
     */
    protected function replaceAllData(&$rec, $detArr)
    {
        //Масив с всички полета, които ще се заместят
        $fieldsArr = array();
        $fieldsArr['subject'] = 'subject';
        $fieldsArr['recipient'] = 'recipient';
        $fieldsArr['attn'] = 'attn';
        $fieldsArr['email'] = 'email';
        $fieldsArr['tel'] = 'tel';
        $fieldsArr['fax'] = 'fax';
        $fieldsArr['country'] = 'country';
        $fieldsArr['pcode'] = 'pcode';
        $fieldsArr['place'] = 'place';
        $fieldsArr['address'] = 'address';
        $fieldsArr['body'] = 'body';
        
        //Обхождаме всички данни от антетката
        foreach ($fieldsArr as $header) {
            
            //Ако нямаме въведена стойност, прескачаме
            if (!$rec->$header) continue;
            
            //Заместваме данните в антетката
            $rec->$header = $this->replacePlaces($rec->$header, $detArr);
        }
    }
    
    
    /**
     * Заместваме всички шаблони, с техните стойности
     *
     * @param string $resStr - стринга, който ще се замества
     * @param array $detArr - масив със стойностите за плейсхолдерите
     *
     * @return string
     */
    protected function replacePlaces($resStr, $detArr)
    {
        // Заместваме плейсхолдерите
        $resStr = new ET($resStr);
        $resStr->placeArray($detArr);
        
        return core_ET::unEscape($resStr->getContent());
    }
    
    
    /**
     * Връща URL за отписване
     * 
     * @param integer $id
     * @param string|NULL $lg
     * @param string|NULL $midPlace
     * @param array $otherParams
     * 
     * @return array
     */
    protected static function getUnsubscribeUrl($id, $lg = NULL, $midPlace = NULL, $otherParams = array())
    {
        $url = array('B', 'U', $id);
        
        $preParams = array();
        
        if ($midPlace) {
            $url['m'] = $midPlace;
            $preParams['m'] = 'm';
        }
    
        if ($lg) {
            $url['lg'] = $lg;
            $preParams['lg'] = 'lg';
        }
        
        foreach ($otherParams as $name => $val) {
            $url[$name] = $val;
            $preParams[$name] = $name;
        }
        
        $absolute = FALSE;
        
        if (Mode::is('printing') || !Mode::is('text', 'html')) {
            $absolute = TRUE;
        }
        
        return toUrl($url, $absolute, TRUE, $preParams);
    }
    
    /**
     * Връща тялото на съобщението
     *
     * @param object $rec - Данни за имейла
     * @param array $detArr - масив с id на детайлите
     * @param boolen $sending - Дали ще изпращаме имейла
     *
     * @return object $body - Обект с тялото на съобщението
     * string $body->html - HTMl частта
     * string $body->text - Текстовата част
     * array  $body->attachments - Прикачените файлове
     */
    protected function getEmailBody($rec, $detArr, $sending = FALSE)
    {
        $body = new stdClass();
        
        //Вземаме HTML частта
        $body->html = $this->getEmailHtml($rec, $detArr, $sending);
        
        //Вземаме текстовата част
        $body->text = $this->getEmailText($rec, $detArr);
        
        // Конвертираме към въведения енкодинг
        if ($rec->encoding == 'ascii') {
            $body->html = str::utf2ascii($body->html);
            $body->text = str::utf2ascii($body->text);
        } elseif (!empty($rec->encoding) && $rec->encoding != 'utf-8') {
            $body->html = iconv('UTF-8', $rec->encoding . '//IGNORE', $body->html);
            $body->text = iconv('UTF-8', $rec->encoding . '//IGNORE', $body->text);
        }
        
        $docsArr = array();
        $attFhArr = array();
        
        if ($sending) {
            
            //Дали да прикачим файловете
            if ($rec->attachments) {
                $attachArr = type_Set::toArray($rec->attachments);
            }
            
            //Ако сме избрали да се добавят документите, като прикачени
            if ($attachArr['documents']) {
                
                $nRec = clone $rec;
                
                $this->prepareRec($nRec, $detArr);
                
                //Вземаме манупулаторите на документите
                $docsArr = $this->getDocuments($nRec);
                
                $docsFhArr = array();
                
                foreach ((array)$docsArr as $attachDoc) {
                    try {
                        
                        // Използваме интерфейсен метод doc_DocumentIntf::convertTo за да генерираме
                        // файл със съдържанието на документа в желания формат
                        $fhArr = $attachDoc['doc']->convertTo($attachDoc['ext'], $attachDoc['fileName']);
                    } catch(ErrorException $e) {
                        continue;
                    }
                    $docsFhArr += $fhArr;
                }
            }
            
            //Ако сме избрали да се добавят файловете, като прикачени
            if ($attachArr['files']) {
                
                //Вземаме манупулаторите на файловете
                $attFhArr = $this->getAttachments($rec);
                
                if (count($attFhArr)) {
                    // Манипулаторите да са и в стойноситите им
                    $attFhArr = array_keys($attFhArr);
                    $attFhArr = array_combine($attFhArr, $attFhArr);
                }
            }
            
            //Манипулаторите на файловете в масив
            $body->attachmentsFh = (array)$attFhArr;
            $body->documentsFh = (array)$docsFhArr;
            
            //id' тата на прикачените файлове с техните
            $body->attachments = keylist::fromArray(fileman::fhKeylistToIds($attFhArr));
            $body->documents = keylist::fromArray(fileman::fhKeylistToIds($docsFhArr));
        }
        
        // Други необходими данни за изпращането на имейла
        $body->containerId = $rec->containerId;
        $body->__mid = $rec->__mid;
        $body->subject = $rec->subject;
        
        return $body;
    }
    
    
    /**
     * Взема HTML частта на имейл-а
     *
     * @param object $rec     - Данни за имейла
     * @param array $detArr - Масив с данните
     * @param boolean $sending - Дали се изпраща в момента
     *
     * @return core_ET $res
     */
    protected function getEmailHtml($rec, $detArr, $sending)
    {
        // Опциите за генериране на тялото на имейла
        $options = new stdClass();
        
        // Добавяме обработения rec към опциите
        $options->rec = $rec;
        $options->__detArr = $detArr;
        
        // Вземаме тялото на имейла
        $res = self::getDocumentBody($rec->id, 'xhtml', $options);
        
        if ($res instanceof core_ET) {
            $content = $res->getContent();
        } else {
            $content = $res;
        }
        
        // За да вземем mid'а който се предава на $options
        $rec->__mid = $options->rec->__mid;
        
        // За да вземем subject'а със заменените данни
        $rec->subject = $options->rec->subject;
        
        //Ако изпращаме имейла
        if ($sending) {
            //Добавяме CSS, като inline стилове            
            $css = file_get_contents(sbf('css/common.css', "", TRUE)) .
            "\n" . file_get_contents(sbf('css/Application.css', "", TRUE)) . "\n" . file_get_contents(sbf('css/email.css', "", TRUE));
            
            $content = '<div id="begin">' . $content . '<div id="end">';
            
            // Вземаме пакета
            $conf = core_Packs::getConfig('csstoinline');
            
            // Класа
            $CssToInline = $conf->CSSTOINLINE_CONVERTER_CLASS;
            
            // Инстанция на класа
            $inst = cls::get($CssToInline);
            
            // Стартираме процеса
            $content =  $inst->convert($content, $css);
            
            $content = str::cut($content, '<div id="begin">', '<div id="end">');
        }
        
        //Изчистваме HTMl коментарите
        $content = email_Outgoings::clearHtmlComments($content);
    
        if ($res instanceof core_ET) {
            $res->setContent($content);
        } else {
            $res = $content;
        }
        
        return $res;
    }
    
    
    /**
     * Взема текстовата част на имейл-а
     *
     * @param object $rec - Данни за имейла
     * @param array $detArr - Масив с данните
     *
     * @return core_ET $res
     */
    protected function getEmailText($rec, $detArr)
    {
        // Опциите за генериране на тялото на имейла
        $options = new stdClass();
        
        // Добавяме обработения rec към опциите
        $options->rec = $rec;
        $options->__detArr = $detArr;
        
        // Вземаме тялото на имейла
        $res = self::getDocumentBody($rec->id, 'plain', $options);
        
        // За да вземем mid'а който се предава на $options
        $rec->__mid = $options->rec->__mid;
        
        // За да вземем subject'а със заменените данни
        $rec->subject = $options->rec->subject;
        
        return $res;
    }
    
    /**
     * Намира предполагаемия език на текста
     *
     * @param text $body - Текста, в който ще се търси
     * @param NULL|string $lang - Език
     *
     * @return string $lg - Двубуквеното означение на предполагаемия език
     */
    protected static function getLanguage($body, $lang = NULL)
    {
        // Масив с всички допустими езици за системата
        $langArr = arr::make(EF_LANGUAGES, TRUE);
        
        // Ако подадения език е в допустимите, да се използва
        if ($lang && $langArr[$lang]) {
            $lg = $lang;
        } else {
            // Масив с всички предполагаеми езици
            $lg = i18n_Language::detect($body);
            
            // Ако езика не е допустимите за системата, да е английски
            if (!$langArr[$lg]) {
                $lg = 'en';
            }
        }
        
        return $lg;
    }
    
    
    /**
     * Вземаме всички прикачени документи
     *
     * @param object $rec
     *
     * @return array $documents - Масив с прикачените документи
     */
    function getDocuments($rec)
    {
        $docsArr = $this->getPossibleTypeConvertings($rec);
        $docs = array();
        
        // Обхождаме всички документи
        foreach ($docsArr as $fileName => $checked) {
            
            // Намираме името и разширението на файла
            if (($dotPos = mb_strrpos($fileName, '.')) !== FALSE) {
                $ext = mb_substr($fileName, $dotPos + 1);
                
                $docHandle = mb_substr($fileName, 0, $dotPos);
            } else {
                $docHandle = $fileName;
            }
            
            $doc = doc_Containers::getDocumentByHandle($docHandle);
            expect($doc);
            
            // Масив с манипулаторите на конвертиранети файлове
            $docs[] = compact('doc', 'ext', 'fileName');
        }
        
        return $docs;
    }
    
    /**
     * Връща масив с полетата, които са инстанции на type_Email или type_Emails
     *
     * @param array $descArr
     *
     * @return array
     */
    protected static function getEmailFields($descArr)
    {
        $fieldsArr = array();
        
        // Обхождаме всички подадени полета и проверяваме дали не са инстанции на type_Email или type_Emails
        foreach ((array)$descArr as $name => $type) {
            $lName = strtolower($name);
            if (($lName == 'email') || ($lName == 'emails') || ($type instanceof type_Email) || ($type instanceof type_Emails)) {
                $fieldsArr[$name] = $type;
            }
        }
        
        return $fieldsArr;
    }
    
    
    /**
     * Екшън за активиране, съгласно правилата на фреймуърка
     */
    function act_Activation()
    {
        // Права за работа с екшън-а
        $this->requireRightFor('activate');
        
        $id = Request::get('id', 'int');
        
        $retUrl = getRetUrl();
        
        // URL' то където ще се редиректва при отказ
        $retUrl = ($retUrl) ? ($retUrl) : (array($this, 'single', $id));
        
        // Вземаме формата към този модел
        $form = $this->getForm();
        
        // Въвеждаме id-то (и евентуално други silent параметри, ако има)
        $form->input(NULL, 'silent');
        
        // Очакваме да има такъв запис
        expect($rec = $this->fetch($form->rec->id));
        
        // Очакваме потребителя да има права за активиране
        $this->requireRightFor('activate', $rec);
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = 'sendPerCall, sendingDay, sendingFrom, sendingTo';
        
        // Въвеждаме съдържанието на полетата
        $form->input($form->showFields);
        
        // Инстанция на избрания клас
        $srcClsInst = cls::get($rec->perSrcClassId);
        
        // Ако формата е изпратена без грешки
        if($form->isSubmitted()) {
            
            if ($form->rec->sendingFrom && $form->rec->sendingTo) {
                if ($form->rec->sendingFrom >= $form->rec->sendingTo) {
                    $form->setError('sendingTo, sendingFrom', 'Началният час трябва да е преди крайния');
                }
            }
            
            $this->checkHost($form, 'sendPerCall');
        }
        
        // Ако формата е изпратена без грешки, то активираме, ... и редиректваме
        if($form->isSubmitted()) {
            
            $form->rec->activatedBy = core_Users::getCurrent();
            
            $nextStartTime = self::getNextStartTime($form->rec);
            
            // Вземаме секундите между сегашното време и времето на стартиране
            $sec = dt::secsBetween($nextStartTime, dt::now());
            
            // Ако са по - малко от 60 секунди
            if ($sec < 60) {
                
                // Активираме
                $form->rec->state = 'active';
            } else {
                
                // Сменя статуса на чакащ
                $form->rec->state = 'waiting';
            }
            
            $form->rec->errMsg = NULL;
            
            // Упдейтва състоянието и данните за имейл-а
            blast_Emails::save($form->rec, 'state,sendPerCall,activatedBy,modifiedBy,modifiedOn, sendingDay, sendingFrom, sendingTo, errMsg');
            
            // Обновяваме списъка с имейлите
            $updateCnt = self::updateEmailList($form->rec->id);
            
            // В зависимост от броя на обновления променяме състоянието
            if ($updateCnt) {
                if ($updateCnt == 1) {
                    $msg = 'Добавен е|* ' . $updateCnt . ' |запис';
                } else {
                    $msg = 'Добавени са|* ' . $updateCnt . ' |записа';
                }
            } else {
                $msg = 'Не са добавени нови записи';
            }
            
            // Добавяме ново съобщени
            status_Messages::newStatus($msg);
            
            // След успешен запис редиректваме
            $link = array('blast_Emails', 'single', $rec->id);
            
            self::logRead('Активиране', $rec->id);
            
            // Редиректваме
            return new Redirect($link, "|Успешно активирахте бласт имейл-а");
        } else {
            
            // Стойности по подразбиране
            $perMin = $rec->sendPerCall ? $rec->sendPerCall : 5;
            $form->setDefault('sendPerCall', $perMin);
            $form->setDefault('sendingDay', $rec->sendingDay);
            $form->setDefault('sendingFrom', $rec->sendingFrom);
            $form->setDefault('sendingTo', $rec->sendingTo);
        }
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', NULL, 'ef_icon = img/16/disk.png, title=Запис на документа');
        $form->toolbar->addBtn('Отказ', $retUrl, NULL, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        // Добавяме титлата на формата
        $form->title = "Стартиране на масово разпращане";
        $subject = $this->getVerbal($rec, 'subject');
        $date = dt::mysql2verbal($rec->createdOn);
        
        // Добавяме във формата информация, за да знаем за кое писмо става дума
        $form->info = new ET ('[#1#]', tr("|*<b>|Писмо|*<i style='color:blue'>: {$subject} / {$date}</i></b>"));
        
        // Вземаме един запис за персонализиране
        $personalizationArr = $srcClsInst->getPresonalizationArr($rec->perSrcObjectId, 1);
        
        // Вземаме елемента
        $detArr = array_pop($personalizationArr);
        
        // Тялото на съобщението
        $body = $this->getEmailBody($rec, $detArr);
        
        // Деескейпваме плейсхолдерите в текстовата част
        $body->text = core_ET::unEscape($body->text);
        
        // Получаваме изгледа на формата
        $tpl = $form->renderHtml();
        
        // Добавяме превю на първия бласт имейл, който ще изпратим
        $preview = new ET("<div class='preview-holder'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Примерен имейл") . "</b></div><div class='scrolling-holder'>[#BLAST_HTML#]<div class='clearfix21'></div><pre class=\"document\">[#BLAST_TEXT#]</pre></div></div>");
        
        // Добавяме към шаблона
        $preview->append($body->html, 'BLAST_HTML');
        $preview->append(core_Type::escape($body->text), 'BLAST_TEXT');
        
        // Добавяме изгледа към главния шаблон
        $tpl->append($preview);
        
        return self::renderWrapping($tpl);
    }
    
    
    /**
     * Помощна функция, за проверка дали се изпраща от частна мрежа и линковете ще са коректни
     * 
     * @param core_Form $form
     * @param string $errField
     */
    protected function checkHost($form, $errField)
    {
        if (core_App::checkCurrentHostIsPrivate()) {
            
            $host = defined('BGERP_ABSOLUTE_HTTP_HOST') ? BGERP_ABSOLUTE_HTTP_HOST : $_SERVER['HTTP_HOST'];
            
            $err = "Внимание|*! |Понеже системата работи на локален адрес|* ({$host}), |то линковете в изходящото писмо няма да са достъпни от други компютри в Интернет|*.";
            
            $form->setWarning($errField, $err);
        }
    }
    
    
    /**
     * Обновява списъка с имейлите
     */
    function act_Update()
    {
        // Права за работа с екшън-а
        $this->requireRightFor('update');
        
        $id = Request::get('id', 'int');
        
        // Очакваме да има такъв запис
        expect($rec = $this->fetch($id));
        
        // URL' то където ще се редиректва при отказ
        $retUrl = getRetUrl();
        $retUrl = ($retUrl) ? ($retUrl) : (array($this, 'single', $id));
        
        // Очакваме потребителя да има права за обновяване на съответния запис
        $this->requireRightFor('update', $rec);
        
        // Обновяваме списъка с имейлите
        $updateCnt = blast_Emails::updateEmailList($rec);
        
        // В зависимост от броя на обновления променяме състоянието
        if ($updateCnt) {
            if ($updateCnt == 1) {
                $updateMsg = '|Добавен е|* ' . $updateCnt . ' |запис';
            } else {
                $updateMsg = '|Добавени са|* ' . $updateCnt . ' |записа';
            }
            
            $rec->progress = blast_EmailSend::getSendingProgress($rec->id);
            
            // Ако състоянието е затворено, активираме имейла
            if ($rec->state == 'closed') {
                $rec->state = 'active';
            }
            
            $this->save($rec);
        } else {
            $updateMsg = '|Няма нови записи за добавяне';
        }
        
        self::logRead('Обновяване на списъка', $rec->id);
        
        return new Redirect($retUrl, $updateMsg);
    }
    
    
    /**
     * Екшън за спиране
     */
    function act_Stop()
    {
        $this->requireRightFor('stop');
        
        //Очакваме да има такъв запис
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));
        
        // Очакваме да има права за записа
        $this->requireRightFor('stop', $rec);
        
        //Очакваме потребителя да има права за спиране
        $this->haveRightFor('stop', $rec);
        
        $link = array('blast_Emails', 'single', $rec->id);
        
        //Променяме статуса на спрян
        $recUpd = new stdClass();
        $recUpd->id = $rec->id;
        $recUpd->state = 'stopped';
        
        blast_Emails::save($recUpd);
		
        self::logRead('Спиране', $rec->id);
        
        // Редиректваме
        return new Redirect($link, "|Успешно спряхте бласт имейл-а");
    }
    
    
    /**
     * Добавяне или премахване на имейл в блокираните
     */
    function act_Unsubscribe()
    {
        $conf = core_Packs::getConfig('blast');
        
        // GET променливите от линка
        $mid = Request::get('m');
        $lang = Request::get('lg');
        $id = Request::get('id', 'int');
        $uns = Request::get("uns");
        
        expect($id);
        
        $rec = $this->fetch($id);
        expect($rec);
        
        $cid = $rec->containerId;
        
        expect($cid);
        
        if (!core_Users::isPowerUser()) {
            expect($mid);
        }
        
        if (!$lang || ($lang == 'auto')) {
            if ($rec->perSrcClassId) {
                $perClsInst = cls::get($rec->perSrcClassId);
            }
            $lang = $perClsInst->getPersonalizationLg($rec->perSrcObjectId);
        }
        
        $allLangArr = arr::make(EF_LANGUAGES);
        
        $pushedLg = FALSE;
        
        if ($allLangArr[$lang]) {
            // Сменяме езика за да може да  се преведат съобщенията
            core_Lg::push($lang);
            
            $pushedLg = TRUE;
        }
        
        // Шаблон
        $tpl = new ET("<div class='unsubscribe'> [#text#][#link#] </div>");
        
        //Проверяваме дали има такъв имейл
        if (!($hRec = doclog_Documents::fetchHistoryFor($cid, $mid))) {
            
            //Съобщение за грешка, ако няма такъв имейл
            $tpl->append("<p>" . tr($conf->BGERP_BLAST_NO_MAIL) . "</p>", 'text');
            
            if ($pushedLg) {
                // Връщаме предишния език
                core_Lg::pop();
            }
            
            return $tpl;
        }
        
        // Имейла на потребителя
        $email = $hRec->data->to;
        
        // Ако имейл-а е в листата на блокираните имейли или сме натиснали бутона за премахване от листата
        if (($uns == 'del') || ((!$uns) && (blast_BlockedEmails::isBlocked($email)))) {
            
            // Какво действие ще правим след натискане на бутона
            $act = 'add';
            
            // Какъв да е текста на бутона
            $click = 'Добави';
            
            // Добавяме имейл-а в листата на блокираните
            if ($uns) {
                
                blast_BlockedEmails::blockEmail($email);
            }
            
            $text = $conf->BGERP_BLAST_SUCCESS_REMOVED;
        } elseif ($uns == 'add') {
            $act = 'del';
            $click = 'Премахване';
            
            // Премахваме имейл-а от листата на блокираните имейли
            blast_BlockedEmails::unBlockEmail($email);
            $text = $conf->BGERP_BLAST_SUCCESS_ADD;
        } else {
            $act = 'del';
            $click = 'Блокиране';
            
            $text = $conf->BGERP_BLAST_UNSUBSCRIBE;
        }
        
        $url = self::getUnsubscribeUrl($id, $lang, $mid, array('uns' => $act));
        
        // Генерираме бутон за отписване или вписване
        $link = ht::createBtn($click, $url);
        
        $text = tr($text);
        
        $text = "<p>" . $text . "<p>";
        
        $text = new ET($text);
        $Email = cls::get('type_Email');
        $text->replace($Email->toVerbal($email), 'email');
        
        $tpl->append($text, 'text');
        $tpl->append($link, 'link');
        
        if ($pushedLg) {
            // Връщаме предишния език
            core_Lg::pop();
        }
        
        return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготвяне на формата за редактиране
     *
     * @param blast_Emails $mvc
     * @param object $res
     * @param object $data
     */
    static function on_AfterPrepareEditForm(&$mvc, &$res, &$data)
    {
        $form = $data->form;
        
        $defPerSrcClassId = NULL;
        $currUserId = core_Users::getCurrent();
        // Ако не е подаден клас да е blast_List
        $listClassId = blast_Lists::getClassId();
        
        if (isset($form->rec->folderId)) {
            $cover = doc_Folders::getCover($form->rec->folderId);
            
            $coverClassName = strtolower($cover->className);
        
            if ($coverClassName != 'doc_unsortedfolders') {
                $defPerSrcClassId = $cover->getInstance()->getClassId();
            }
        }
        
        if (!isset($defPerSrcClassId)) {
            $defPerSrcClassId = $listClassId;
        }
        
        $data->form->setDefault('perSrcClassId', $defPerSrcClassId);
        
        // Инстанция на източника за персонализация
        $perClsInst = cls::get($data->form->rec->perSrcClassId);
        
        // id на обекта на персонализация
        $perSrcObjId = $data->form->rec->perSrcObjectId;
        
        $perOptArr = array();
        
        if (isset($perSrcObjId) && $form->cmd != 'refresh') {
            
            // Очакваме да може да персонализира, ако не се редактира записа
            if (!$form->rec->id) {
                expect($perClsInst->canUsePersonalization($perSrcObjId));
            }
            
            // Заглавието за персонализация
            $perTitle = $perClsInst->getPersonalizationTitle($perSrcObjId, FALSE);
            
            // Да може да се избере само подадения обект
            $perOptArr[$perSrcObjId] = $perTitle;
            $form->setOptions('perSrcObjectId', $perOptArr);
        } else {
            $perOptArr = $perClsInst->getPersonalizationOptionsForId($cover->that);
            
            // Обхождаме всички опции
            foreach ((array)$perOptArr as $id => $name) {
                
                // Проверяваме дали може да се персонализира
                // Тряба да се проверява в getPersonalizationOptions()
                //                    if (!$perClsInst->canUsePersonalization($id)) continue;
                
                // Описание на полетата
                $descArr = $perClsInst->getPersonalizationDescr($id);
                
                // Ако няма полета за имейл
                if (!self::getEmailFields($descArr)) {
                    
                    // Премахваме от опциите
                    unset($perOptArr[$id]);
                }
            }
            
            if (!$perOptArr) {
                
                $msg = '|Няма източник, който да може да се използва за персонализация';
                
                $redirectUrl = array();
                
                if (($defPerSrcClassId != $listClassId) && ($cover && $cover->instance)) {
                    if ($cover->instance->haveRightFor('single', $cover->that)) {
                        $redirectUrl = array($cover->instance, 'single', $cover->that);
                    }
                }
                
                if (empty($redirectUrl) && $perClsInst->haveRightFor('list')) {
                    $redirectUrl = array($perClsInst, 'list');
                }
                
                if (empty($redirectUrl)) {
                    $redirectUrl = getRetUrl();
                }
                
                redirect($redirectUrl, FALSE, $msg);
            }
            
            // Задаваме опциите
            $form->setOptions('perSrcObjectId', $perOptArr);
        }
        
        if (!$form->rec->id) {
            if (!$perSrcObjId) {
                $perSrcObjId = key($perOptArr);
            }
            $defLg = $perClsInst->getPersonalizationLg($perSrcObjId);
            $form->setDefault('perSrcObjectId', $perSrcObjId);
            $form->setDefault('lg', $defLg);
            
            $conf = core_Packs::getConfig('blast');
            
            $unsubscribeText = $conf->BLAST_UNSUBSCRIBE_TEXT_FOOTER;
            
            $bodyLangArr = array();
            $bCnt = 0;
            
            $allLangArr = arr::make(EF_LANGUAGES);
            $currLg = core_Lg::getCurrent();
            
            if (empty($allLangArr)) {
                $allLangArr = arr::make($currLg, TRUE);
            }
            
            if (!$defLg || $defLg == 'auto') {
                $defLg = $currLg;
            }
            
            foreach ($allLangArr as $lang => $verbLang) {
                
                // За всеки език подоготвяме текста
                core_Lg::push($lang);
                $bodyLangArr[$bCnt]['data'] = tr($unsubscribeText);
                $bodyLangArr[$bCnt]['lg'] = $lang;
                core_Lg::pop();
                
                if ($defLg == $lang) {
                    $form->setDefault('unsubscribe', $bodyLangArr[$bCnt]['data']);
                }
                
                $bCnt++;
            }
            
            $jsonData  = json_encode(array('hint' => tr('Смяна на езика'), 'lg' => $defLg, 'data' => $bodyLangArr, 'id' => 'unsId'));
            
            $form->layout = new ET($data->form->renderLayout());
            
            jquery_Jquery::run($form->layout, "prepareLangBtn(" . $jsonData . ");");
        }
        
        try {
            // Само имейлите достъпни до потребителя да се показват
            $emailOption = email_Inboxes::getFromEmailOptions($form->rec->folderId);
        } catch(ErrorException $e) {
            email_Inboxes::redirect();
            $emailOption = array();
        }
        
        $form->setOptions('from', $emailOption);
        
        // Ако създаваме нов, тогава попълва данните за адресата по - подразбиране
        $rec = $data->form->rec;
        
        if ((!$rec->id) && $data->action != 'clone') {
            
            // По подразбиране да е избран текущия имейл на потребителя
            $form->setDefault('from', email_Outgoings::getDefaultInboxId($rec->folderId));
            
            $fieldsArr = array('company' => 'company', 
            				   'person' => 'person', 
            				   'email' => 'email', 
            				   'tel' => 'tel', 
            				   'fax' => 'fax', 
            				   'country' => 'country', 
            				   'pCode' => 'pCode', 
            				   'place' => 'place', 
            				   'address' => 'address');
            
            if ($perSrcObjId) {
                $fieldsArr = $perClsInst->getPersonalizationDescr($perSrcObjId);
            }
            
            $rec->recipient = $fieldsArr['company'] ? '[#company#]' : '';
            $rec->attn = $fieldsArr['person'] ? '[#person#]' : '';
            $rec->email = $fieldsArr['email'] ? '[#email#]' : '';
            $rec->tel = $fieldsArr['tel'] ? '[#tel#]' : '';
            $rec->fax = $fieldsArr['fax'] ? '[#fax#]' : '';
            $rec->country = $fieldsArr['country'] ? '[#country#]' : '';
            $rec->pcode = $fieldsArr['pCode'] ? '[#pCode#]' : '';
            $rec->place = $fieldsArr['place'] ? '[#place#]' : '';
            $rec->address = $fieldsArr['address'] ? '[#address#]' : '';
        }
    }
    
    
    /**
     * Изпълнява се след въвеждането на даните от формата
     *
     * @param blast_Emails $mvc
     * @param core_Form $form
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        
        // Ако сме субмитнали формата
        if ($form->isSubmitted()) {
            
            // Ако ще се прикачат документи или файлове
            // Проверяваме разширенията им
            if ($rec->attachments) {
                
                $attachArr = type_Set::toArray($rec->attachments);
                
                if ($attachArr['documents']) {
                    // Прикачените документи
                    $docsArr = $mvc->getDocuments($rec);
                    $docsSizesArr = $mvc->getDocumentsSizes($docsArr);
                }
                
                if ($attachArr['files']) {
                    // Прикачените файлове
                    $attachmentsArr = $mvc->getAttachments($rec);
                    $filesSizesArr = $mvc->getFilesSizes($attachmentsArr);
                }
                
                // Проверяваме дали размерът им е над допсутимият
                $allAttachmentsArr = array_merge((array)$docsSizesArr, (array)$filesSizesArr);
                
                if (!$mvc->checkMaxAttachedSize($allAttachmentsArr)) {
                    
                    // Вербалният размер на файловете и документите
                    $docAndFilesSizeVerbal = $mvc->getVerbalSizesFromArray($allAttachmentsArr);
                    
                    if ($attachArr['documents'] && $attachArr['files']) {
                        $str = "файлове и документи";
                    } else if ($attachArr['documents']) {
                        $str = "документи";
                    } else {
                        $str = "файлове";
                    }
                    
                    $FileSize = cls::get('fileman_FileSize');
                    $allowedSize = $mvc->getMaxAttachFileSizeLimit();
                    $allowedSize = $FileSize->toVerbal($allowedSize);
                    
                    $errStr = "Размерът на прикачените {$str} е|*: " . $docAndFilesSizeVerbal;
                    $errStr .= "<br>|Допустимият размер е|*: {$allowedSize}";
                    
                    $form->setError('attachments', $errStr);
                }
            }
        }
        
        // Ако сме субмитнали формата
        // Проверява за плейсхолдери, които липсват в източника
        if ($form->isSubmitted()) {
            
            $classInst = cls::get($rec->perSrcClassId);
            
            // Масив с всички записи
            $recArr = (array)$form->rec;
            
            // Вземаме Относно и Съобщение
            $bodyAndSubject = $recArr['body'] . ' ' . $recArr['subject'] . ' ' . $recArr['unsubscribe'];
            
            // Масив с данни от плейсхолдера
            $nRecArr = array();
            $nRecArr['recipient'] = $recArr['recipient'];
            $nRecArr['attn'] = $recArr['attn'];
            $nRecArr['email'] = $recArr['email'];
            $nRecArr['tel'] = $recArr['tel'];
            $nRecArr['fax'] = $recArr['fax'];
            $nRecArr['country'] = $recArr['country'];
            $nRecArr['pcode'] = $recArr['pcode'];
            $nRecArr['place'] = $recArr['place'];
            $nRecArr['address'] = $recArr['address'];
            
            // Обикаляме всички останали стойности в масива
            foreach ($nRecArr as $field) {
                
                // Всички данни ги записваме в една променлива
                $allRecsWithPlaceHolders .= ' ' . $field;
            }
            
            // Създаваме шаблон
            $tpl = new ET($allRecsWithPlaceHolders);
            
            // Вземаме всички шаблони, които се използват
            $allPlaceHolder = $tpl->getPlaceHolders();
            
            // Шаблон на Относно и Съобщение
            $bodyAndSubTpl = new ET($bodyAndSubject);
            
            // Вземаме всички шаблони, които се използват
            $bodyAndSubPlaceHolder = $bodyAndSubTpl->getPlaceHolders();
            
            $fieldsArr = array();
            
            // Полетата и описаниите им, които ще се използва за персонализация
            $onlyAllFieldsArr = $classInst->getPersonalizationDescr($rec->perSrcObjectId);
            
            // Създаваме масив с ключ и стойност имената на полетата, които ще се заместват
            foreach ((array)$onlyAllFieldsArr as $field => $dummy) {
                // Тримваме полето
                $field = trim($field);
                
                // Името в долен регистър
                $field = strtolower($field);
                
                // Добавяме в масива
                $fieldsArr[$field] = $field;
            }
            
            // Премахваме дублиращите се плейсхолдери
            $allPlaceHolder = array_unique($allPlaceHolder);
            
            $warningPlaceHolderArr = array();
            
            // Търсим всички полета, които сме въвели, но ги няма в полетата за заместване
            foreach ($allPlaceHolder as $placeHolder) {
                
                $placeHolderL = strtolower($placeHolder);
                
                // Ако плейсхолдера го няма във листа
                if (!$fieldsArr[$placeHolderL]) {
                    
                    // Добавяме към съобщението за предупреждение
                    $warning .= ($warning) ? ", {$placeHolder}" : $placeHolder;
                    
                    // Стринг на плейсхолдера
                    $placeHolderStr = "[#" . $placeHolder . "#]";
                    
                    // Добавяме го в масива
                    $warningPlaceHolderArr[$placeHolderStr] = $placeHolderStr;
                }
            }
            
            // Премахваме дублиращите се плейсхолдери
            $bodyAndSubPlaceHolder = array_unique($bodyAndSubPlaceHolder);
            
            // Търсим всички полета, които сме въвели, но ги няма в полетата за заместване
            foreach ($bodyAndSubPlaceHolder as $placeHolder) {
                
                $placeHolderL = strtolower($placeHolder);
                
                // Ако плейсхолдера го няма във листа
                if (!$fieldsArr[$placeHolderL]) {
                    
                    // Добавяме към съобщението за грешка
                    $error .= ($error) ? ", {$placeHolder}" : $placeHolder;
                }
            }
            
            // Показваме грешка, ако има шаблони, които сме въвели в повече в Относно и Съощение
            if ($error) {
                $form->setError('*', "|Шаблоните, които сте въвели ги няма в източника|*: {$error}");
            }
            
            // Показваме предупреждение за останалите шаблони
            if ($warning) {
                
                // Сетваме грешката
                $form->setWarning('*', "|Шаблоните, които сте въвели ги няма в източника|*: {$warning}");
                
                // При игнориране на грешката
                if (!$form->gotErrors()) {
                    
                    // Обхождаме масива с стойност
                    foreach ($nRecArr as $field => $val) {
                        
                        // Премахваме всички плейсхолдери, които не се използват
                        $val = str_ireplace((array)$warningPlaceHolderArr, '', $val);
                        
                        // Добавяме към записа
                        $form->rec->{$field} = $val;
                    }
                }
            }
        }
        
        if ($form->isSubmitted()) {
            $mvc->checkHost($form, 'body');
        }
    }
    
    
    /**
     * Връща допустимия размер на прикачените файлове/докумети
     * 
     * @return integer
     */
    public static function getMaxAttachFileSizeLimit_()
    {
        // 1 МБ
        $res = 1048576;
        
        return $res;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, необходимо за това действие
     *
     * @param blast_Emails $mvc
     * @param string $roles
     * @param string $action
     * @param object $rec
     */
    public static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec = NULL, $userId = NULL)
    {
        // Трябва да има права за сингъла на документа, за да може да активира, спира и/или обновява
        if ((($action == 'activate') || ($action == 'stop') || ($action == 'update')) && $rec) {
            if (!$mvc->haveRightFor('single', $rec)) {
                $roles = 'no_one';
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     *
     * @param blast_Emails $mvc
     * @param object $row
     * @param object $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $blue = new color_Object("#2244cc");
        $grey = new color_Object("#bbb");
        
        $progressPx = min(100, round(100 * $rec->progress));
        $progressRemainPx = 100 - $progressPx;
        $row->progressBar = "<div style='white-space: nowrap; display: inline-block;'><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$blue}; width:{$progressPx}px;'> </div><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$grey};width:{$progressRemainPx}px;'></div></div>";
        
        //При рендиране на листовия изглед показваме дали ще се прикачат файловете и/или документите
        $attachArr = type_Set::toArray($rec->attachments);
        
        if ($attachArr['files']) $row->Files = tr('Файловете');
        
        if ($attachArr['documents']) $row->Documents = tr('Документите');
        
        // Манипулатора на документа
        $row->handle = $mvc->getHandle($rec->id);
        
        // Линка към обекта, който се използва за персонализация
        if ($rec->perSrcClassId && isset($rec->perSrcObjectId)) {
            if (cls::load($rec->perSrcClassId, TRUE)) {
                $inst = cls::get($rec->perSrcClassId);
                
                if ($inst->canUsePersonalization($rec->perSrcObjectId)) {
                    $row->srcLink = $inst->getPersonalizationSrcLink($rec->perSrcObjectId);
                }
            }
        }
        
        if ($rec->errMsg) {
            $row->errMsg = tr('|*' . $rec->errMsg);
            $row->errMsg = type_Varchar::escape($row->errMsg);
        }
    }
    
    
    /**
     * Добавя съответните бутони в лентата с инструменти, в зависимост от състоянието
     *
     * @param blast_Emails $mvc
     * @param object $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        $state = $data->rec->state;
        
        if (($state == 'draft') || ($state == 'stopped')) {
            
            // Добавяме бутона Активирай, ако състоянието е чернова или спряно
            
            if ($mvc->haveRightFor('activate', $rec->rec)) {
                $data->toolbar->addBtn('Активиране', array($mvc, 'Activation', $rec->id), 'ef_icon = img/16/lightning.png, title=Активирай документа');
            }
        } else {
            
            // Добавяме бутона Спри, ако състоянието е активно или изчакване
            if (($state == 'waiting') || ($state == 'active')) {
                if ($mvc->haveRightFor('stop', $rec->rec)) {
                    $data->toolbar->addBtn('Спиране', array($mvc, 'Stop', $rec->id), 'ef_icon = img/16/gray-close.png, title=Прекратяване на действието');
                }
            }
            
            // Добавяме бутон за обновяване в, ако състоянието е активно, изчакване или затворено
            if (($state == 'waiting') || ($state == 'active') || ($state == 'closed')) {
                if ($mvc->haveRightFor('update', $rec->rec)) {
                    $data->toolbar->addBtn('Обновяване', array($mvc, 'Update', $rec->id), NULL, array('ef_icon'=>'img/16/update-icon.png', 'row'=>'1', 'title'=>'Добави новите имейли към списъка'));
                }
            }
        }
    }
    
    
    /**
     * Променяме шаблона в зависимост от мода
     *
     * @param blast_Emails $mvc
     * @param core_ET $tpl
     * @param object $data
     */
    function on_BeforeRenderSingleLayout($mvc, &$tpl, $data)
    {
        //Рендираме шаблона
        if (Mode::is('text', 'xhtml')) {
            //Ако сме в xhtml (изпращане) режим, рендираме шаблона за изпращане
            $mvc->singleLayoutFile = 'blast/tpl/SingleLayoutBlast.shtml';
        } elseif (Mode::is('text', 'plain')) {
            //Ако сме в текстов режим, рендираме txt
            $mvc->singleLayoutFile = 'blast/tpl/SingleLayoutBlast.txt';
        } else {
            $mvc->singleLayoutFile = 'blast/tpl/SingleLayoutEmails.shtml';
        }
    }
    
    
    /**
     * След рендиране на синъл обвивката
     *
     * @param blast_Emails $mvc
     * @param core_ET $tpl
     * @param object $data
     */
    function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        // Полета До и Към
        $attn = $data->row->recipient . $data->row->attn;
        $attn = trim($attn);
        
        // Ако нямаме въведени данни До: и Към:, тогава не показваме имейл-а, и го записваме в полето До:
        if (!$attn) {
            $data->row->recipientEmail = $data->row->email;
            unset($data->row->email);
        }
        
        // Полета Град и Адрес
        $addrStr = $data->row->place . $data->row->address;
        $addrStr = trim($addrStr);
        
        // Ако липсва адреса и града
        if (!$addrStr) {
            
            // Не се показва и пощенския код
            unset($data->row->pcode);
            
            // Ако имаме До: и Държава, и нямаме адресни данни, тогава добавяме държавата след фирмата
            if ($data->row->recipient) {
                $data->row->firmCountry = $data->row->country;
            }
            
            // Не се показва и държавата
            unset($data->row->country);
            
            $telFaxStr = $data->row->tel . $data->row->fax;
            $telFaxStr = trim($telFaxStr);
            
            // Имейла е само в дясната част, преместваме в ляво
            if (!$telFaxStr) {
                $data->row->emailLeft = $data->row->email;
                unset($data->row->email);
            }
        }
        
        // Рендираме шаблона
        if (!Mode::is('text', 'xhtml') && !Mode::is('text', 'plain')) {
            
            // Записите
            $rec = $data->rec;
            
            if (!$data->row->sendingDay) {
                $data->row->sendingDay = tr('Всеки ден');
            }
            
            // Ако състоянието е активирано или чернов
            if ($rec->state == 'active' || $rec->state == 'waiting') {
                
                $nextStartTime = self::getNextStartTime($rec, core_Cron::getNextStartTime(self::$cronSytemId));
                
                $data->row->NextStartTime = dt::mysql2verbal($nextStartTime, 'smartTime');
            }
        }
    }
    
    
    /**
     * Връща времето на следващото стартиране
     * 
     * @param object $rec
     * @param NULL|datetime|FALSE $nextStartTime
     * 
     * @return datetime|string
     */
    protected static function getNextStartTime($rec, $nextStartTime = NULL)
    {
        if (!$nextStartTime) {
            $nextStartTime = dt::now();
        }
        
        $nowF = dt::now(FALSE);
        
        $today = dt::mysql2timestamp($nowF);
        
        if ($rec->sendingFrom) {
            $sendingFrom = $today + $rec->sendingFrom;
            $sendingFrom = dt::timestamp2Mysql($sendingFrom);
        }
        
        if ($rec->sendingTo) {
            $sendingTo = $today + $rec->sendingTo;
            $sendingTo = dt::timestamp2Mysql($sendingTo);
        }
        
        $sendingArr = type_Set::toArray($rec->sendingDay);
        
        $dayOfWeek = date('w');
        
        $haveNextStartTime = FALSE;
        
        if (empty($sendingArr) || ($sendingArr[$dayOfWeek])) {
            if ((($nextStartTime >= $sendingFrom) || !$sendingFrom) && (($nextStartTime < $sendingTo) || !$sendingTo)) {
                $haveNextStartTime = TRUE;
            }
        }
        
        if (!$haveNextStartTime) {
            
            $nextStartDay = 7;
            
            foreach ($sendingArr as $sendingDay) {
                if ($sendingDay > $dayOfWeek) {
                    $nextStartDay = $sendingDay;
                    break;
                }
            }
            
            $nextStartTime = dt::addDays($nextStartDay-$dayOfWeek, $nowF);
            
            if ($rec->sendingFrom) {
                $nextStartTime = dt::addSecs($rec->sendingFrom, $nextStartTime);
            }
        }
        
        return $nextStartTime;
    }
    
    
    /**
     * След порготвяне на формата за филтриране
     *
     * @param blast_Emails $mvc
     * @param object $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Да се показва полето за търсене
        $data->listFilter->showFields = 'search';
        
        $data->listFilter->view = 'horizontal';
        
        //Добавяме бутон "Филтрирай"
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Сортиране на записите по състояние и по времето им на започване
        $data->query->orderBy('state', 'ASC');
        $data->query->orderBy('id', 'DESC');
    }
    
    
    /**
     * Преди да подготвим данните за имейла, подготвяме rec
     *
     * @param blast_Emails $mvc
     * @param core_ET $res
     * @param integer $id
     * @param string $mode
     * @param object|NULL $options
     */
    function on_BeforeGetDocumentBody($mvc, &$res, $id, $mode = 'html', $options = NULL)
    {
        // Записите за имейла
        $emailRec = $mvc->fetch($id);
        
        // Очакваме да има такъв запис
        expect($emailRec);
        
        // Намираме преполагаемия език на съобщението
        core_Lg::push(self::getLanguage($emailRec->body, $emailRec->lg));
        
        $detDataArr = array();
        
        if (is_null($options)) {
            $options = new stdClass();
        }
        
        // Опитваме се да извлечен масива с данните
        if ($options->__detArr) {
            
            // Ако е подаден масива с данните
            $detDataArr = $options->__detArr;
        } elseif ($options->detId) {
            
            // Ако е подадено id, вместо масива
            $detDataArr = blast_EmailSend::getDataArr($options->detId);
        }
        
        if (trim($emailRec->unsubscribe)) {
            $unsUrl = self::getUnsubscribeUrl($id, $options->rec->lg, doc_DocumentPlg::getMidPlace());
            
            $emailRec->unsubscribe = str_replace('[unsubscribe]', "[link={$unsUrl}]", $emailRec->unsubscribe);
            $emailRec->unsubscribe = str_replace('[/unsubscribe]', '[/link]', $emailRec->unsubscribe);
            
            $emailRec->body .= "\n\n[hr]\n[small]" . $emailRec->unsubscribe . "[/small]";
        }
        
        // Подготвяме данните за съответния имейл
        $mvc->prepareRec($emailRec, $detDataArr);
        
        // Обединяваме рековете и ги добавяме в опциите
        // За да може да запазим ->mid' от река
        $options->rec = (object)((array)$emailRec + (array)$options->rec);
    }
    
    
    /**
     * HTML или plain text изгледа на документ при изпращане по емайл.
     *
     * Това е реализацията по подразбиране на интерфейсния метод doc_DocumentIntf::getDocumentBody()
     * Използва single view на мениджъра на документа.
     *
     * @param core_Mvc $mvc мениджър на документа
     * @param core_ET $res генерирания текст под формата на core_ET шаблон
     * @param int $id първичния ключ на документа - key(mvc=$mvc)
     * @param string $mode `plain` или `html`
     * @access private
     */
    function on_AfterGetDocumentBody($mvc, &$res, $id, $mode = 'html', $options = NULL)
    {
        // Връщаме езика по подразбиране
        core_Lg::pop();
    }
    
    
    /**
     * Извършва се преди вземането на линк за документа
     *
     * @param blast_Emails $mvc
     * @param array $res
     * @param integer $id
     * @param integer $userId
     * @param object $data
     */
    public static function on_BeforeGetLinkedDocuments($mvc, &$res, $id, $userId = NULL, $data = NULL)
    {
        // id на детайла
        $detId = $data->detId;
        
        if (!$detId) return ;
        
        // Масив с данните
        $detArr = blast_EmailSend::getDataArr($detId);
        
        if (is_object($id)) {
            $rec = $id;
        } else {
            $rec = $mvc->fetch($id);
        }
        
        if ($mvc->fields['body']->type->params['hndToLink'] == 'no') return ;
        
        // Подготвяме записите
        $mvc->prepareRec($rec, $detArr);
        
        core_Users::sudo($userId);
        
        try {
            // Вземаме прикачените документи за този детайл с правата на активиралия потребител
            $attachedDocs = (array)doc_RichTextPlg::getAttachedDocs($rec->body);
        } catch (core_exception_Expect $e) {
            core_Users::exitSudo();
            return ;
        }
        
        core_Users::exitSudo();
        
        // Ако има прикачени документи
        if (count($attachedDocs)) {
            $attachedDocs = array_keys($attachedDocs);
            $attachedDocs = array_combine($attachedDocs, $attachedDocs);
            
            $res = array_merge($attachedDocs, (array)$res);
        }
    }
    
    
    /**
     * Интерфейсен метод
     * Проверка дали нов документ може да бъде добавен в посочената папка, като начало на нишка.
     *
     * @see email_DocumentIntf
     *
     * @param int $folderId - id на папката
     *
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        // Името на класа
        $coverClassName = strtolower(doc_Folders::fetchCoverClassName($folderId));
        
        // Може да се добавя само в проекти и в групи
        if (($coverClassName != 'doc_unsortedfolders') && ($coverClassName != 'crm_groups')) return FALSE;
        
        return TRUE;
    }
    
    
    /**
     * Интерфейсен метод на
     *
     * @see email_DocumentIntf
     *
     * @return object
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        $subject = $this->getVerbal($rec, 'subject');
        
        //Ако заглавието е празно, тогава изписва съответния текст
        if(!trim($subject)) {
            $subject = '[' . tr('Липсва заглавие') . ']';
        }
        
        //Заглавие
        $row->title = $subject;
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        $row->recTitle = $rec->subject;
        
        return $row;
    }
    
    
    /**
     * Функция, която се изпълнява от крона и стартира процеса на изпращане на blast
     */
    function cron_SendEmails()
    {
        $this->sendEmails();
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $conf = core_Packs::getConfig('blast');
        
        // За да получим минути
        $period = round($conf->BLAST_EMAILS_CRON_PERIOD / 60);
        
        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = self::$cronSytemId;
        $rec->description = 'Изпращане на много имейли';
        $rec->controller = $mvc->className;
        $rec->action = 'SendEmails';
        $rec->period = $period;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = $conf->BLAST_EMAILS_CRON_TIME_LIMIT;
        $res .= core_Cron::addOnce($rec);
        
        //Създаваме, кофа, където ще държим всички прикачени файлове на blast имейлите
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('Blast', 'Прикачени файлове в масовите имейли', NULL, '104857600', 'user', 'user');
    }
    
    
    /**
     * Реализация интерфейсния метод ::getUsedDocs()
     * Отбелязва използване на документа в документа за персонализация
     * 
     * @param blast_Emails $mvc
     * @param array $res
     * @param integer $id
     */
    function on_AfterGetUsedDocs($mvc, &$res, $id)
    {
        $rec = $mvc->fetch($id);
        $srcClsInst = cls::get($rec->perSrcClassId);
        if (cls::haveInterface('doc_DocumentIntf', $srcClsInst)) {
            $cid = $srcClsInst->fetchField($rec->perSrcObjectId, 'containerId');
            $res[$cid] = $cid;
        }
    }
}
