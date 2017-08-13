<?php 


/**
 * Ръчен постинг в документната система
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Stefan Stefanov <stefan.bg@gmail.com> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Outgoings extends core_Master
{
    

    /**
     * Шаблон (ET) за заглавие на перо
     */
    public $recTitleTpl = '[#subject#]';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = TRUE;
    
    
    /**
     * Име на папката по подразбиране при създаване на нови документи от този тип.
     * Ако стойноста е 'FALSE', нови документи от този тип се създават в основната папка на потребителя
     */
    var $defaultFolder = FALSE;
    
    
    /**
     * Кой има право да клонира?
     */
    public $canClonerec = 'powerUser';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'doc_Postings';
    
    
    /**
     * Заглавие
     */
    var $title = "Изходящи имейли";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Имейл";
    
    
    /**
     * Кой има право да го чете?
     */
    var $canSingle = 'powerUser';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'powerUser';
    
    
    /**
     * Кой може да изпраща имейли?
     */
    var $canSend = 'powerUser';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой има права за
     */
    var $canEmail = 'powerUser';
    
    
    /**
     * Кой може да затваря имейла
     */
    var $canClose = 'user';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'email_Wrapper, doc_DocumentPlg, plg_RowTools2, 
        plg_Printing, email_plg_Document, doc_ActivatePlg, 
        bgerp_plg_Blank,  plg_Search, recently_Plugin, plg_Clone, change_Plugin';


    /**
     * Кой може да променя активирани записи
     */
    var $canChangerec = 'powerUser';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'email/tpl/SingleLayoutOutgoings.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/email_edit.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'Eml';
    
    
    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'subject';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,subject,recipient,attn,email,createdOn,createdBy';
    
    
    /**
     * Поле за търсене
     */
    var $searchFields = 'subject, body, email, emailCc, recipient, attn, tel, fax, country, pcode, place, address, folderId';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "1.2|Общи";
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = '*';
    
    
    /**
     * Масив с грешки, които ще се показват на потребителя при възникването им
     */
    protected static $errShowNotifyStr = array('recipients failed');
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('subject', 'varchar', 'caption=Относно,mandatory,width=100%,reduceText,changable,tdClass=emailListTitle');
        $this->FLD('body', 'richtext(rows=15,bucket=Postings)', 'caption=Съобщение,mandatory,changable');
        
        $this->FLD('waiting', 'time', 'input=none, caption=Изчакване');
        $this->FLD('lastSendedOn', 'datetime(format=smartTime)', 'input=none, caption=Изпратено->на');
        $this->FLD('lastSendedBy', 'key(mvc=core_Users)', 'caption=Изпратено->От, notNull, input=none');
        $this->FLD('forward', 'enum(,no=Не, yes=Да)', 'caption=Препращане, input=hidden, allowEmpty');
        
        //Данни за адресата
        $this->FLD('email', 'emails', 'caption=Адресат->Имейл, width=100%, silent,changable');
        $this->FLD('emailCc', 'emails', 'caption=Адресат->Копие до,  width=100%,changable');
        $this->FLD('recipient', 'varchar', 'caption=Адресат->Фирма,class=contactData,changable');
        $this->FLD('attn', 'varchar', 'caption=Адресат->Име,oldFieldName=attentionOf,class=contactData,changable');
        $this->FLD('tel', 'varchar', 'caption=Адресат->Тел.,oldFieldName=phone,class=contactData,changable');
        $this->FLD('fax', 'drdata_PhoneType', 'caption=Адресат->Факс,class=contactData, silent,changable');
        $this->FLD('country', 'varchar', 'caption=Адресат->Държава,class=contactData,changable');
        $this->FLD('pcode', 'varchar', 'caption=Адресат->П. код,class=pCode,changable');
        $this->FLD('place', 'varchar', 'caption=Адресат->Град/с,class=contactData,changable');
        $this->FLD('address', 'varchar', 'caption=Адресат->Адрес,class=contactData,changable');

        $this->setDbIndex('createdOn');
    }
    
    
    /**
     * Филтрира само собсвеноръчно създадените изходящи имейли
     */
    function on_AfterPrepareListFilter($mvc, &$data)
    {
        if(!haveRole('ceo')) {
            $cu = core_Users::getCurrent();
            $data->query->where("#createdBy = {$cu}");
        }
        
        $data->query->orderBy('#createdOn', 'DESC');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Send()
    {
        $this->requireRightFor('send');
        
        $data = new stdClass();
        
        // Създаване и подготвяне на формата
        $this->prepareSendForm($data);
        
        // Подготвяме адреса за връщане, ако потребителя не е логнат.
        // Ресурса, който ще се зареди след логване обикновено е страницата, 
        // от която се извиква екшън-а act_Manage
        $retUrl = getRetUrl();
        
        // Очакваме до този момент във формата да няма грешки
        expect(!$data->form->gotErrors(), 'Има грешки в silent полетата на формата', $data->form->errors);
        
        // Зареждаме формата
        $data->form->input();
        
        // Проверка за коректност на входните данни
        $this->invoke('AfterInputSendForm', array($data->form));
        
        // Дали имаме права за това действие към този запис?
        $this->requireRightFor('send', $data->rec, NULL, $retUrl);
        
        $lg = email_Outgoings::getLanguage($data->rec->originId, $data->rec->threadId, $data->rec->folderId, $data->rec->body);
        
        // Ако формата е успешно изпратена - изпращане, лог, редирект
        if ($data->form->isSubmitted()) {
            
            static::send($data->rec, $data->form->rec, $lg);
            
            // Подготвяме адреса, към който трябва да редиректнем,  
            // при успешно записване на данните от формата
            $data->form->rec->id = $data->rec->id;
            $this->prepareRetUrl($data);
            
            // $msg е съобщение за статуса на изпращането
            return new Redirect($data->retUrl);
        } else {
            // Подготвяме адреса, към който трябва да редиректнем,  
            // при успешно записване на данните от формата
            $this->prepareRetUrl($data);
        }
        
        // Получаваме изгледа на формата
        $tpl = $data->form->renderHtml();
        
        // Добавяме превю на имейла, който ще изпратим
        $preview = new ET("<div class='preview-holder'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Изходящ имейл") . "</b></div><div class='scrolling-holder'>[#EMAIL_HTML#]<pre class=\"document\" style=\"width:95%; white-space: pre-wrap;\">[#EMAIL_TEXT#]</pre></div></div>");
        
        $preview->append($this->getEmailHtml($data->rec, $lg) , 'EMAIL_HTML');
        $preview->append(core_Type::escape(core_ET::unEscape($this->getEmailText($data->rec, $lg))) , 'EMAIL_TEXT');
        
        $tpl->append($preview);
        
        return static::renderWrapping($tpl);
    }
    
    
    /**
     * Проверява дали трябва да се изпраща по-късно
     * 
     * @param object $rec
     * @param object $options
     * @param string $lg
     * 
     * @return boolean
     */
    public static function checkAndAddForLateSending($rec, $options, $lg, $className = 'email_Outgoings')
    {
        if ($options->delay) {
            $delay = $options->delay;
            // Нулираме закъснението, за да не сработи при отложеното изпращане
            $options->delay = NULL;
            if (email_SendOnTime::add($className, $rec->id, array('rec' => $rec, 'options' => $options, 'lg' => $lg), $delay)) {
                status_Messages::newStatus('|Добавено в списъка за отложено изпращане');
                self::logWrite('Добавяне за отложено изпращане', $rec->id);
                
                $rec->modifiedOn = dt::now();
                
                $saveStr = 'modifiedOn';
                
                if ($options->waiting) {
                    $rec->waiting = $options->waiting + dt::secsBetween($delay, dt::now());
                    $saveStr .= ',waiting';
                }
                
                $saveStr .= ',state';
                
                $rec->state = 'pending';
                
                email_Outgoings::save($rec, $saveStr);
            } else {
                status_Messages::newStatus('|Грешка при добавяне в списъка за отложено изпращане', 'error');
                self::logErr('Грешка при добавяне за отложено изпращане', $rec->id);
            }
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Изпраща имейла
     * 
     * @param object $rec
     * @param object $options
     * @param string $lg
     */
    public static function send($rec, $options, $lg)
    {
        if (self::checkAndAddForLateSending($rec, $options, $lg)) return ;
        
        //Вземаме всички избрани файлове
        $rec->attachmentsFh = type_Set::toArray($options->attachmentsSet);
        
        //Ако имамем прикачени файлове
        if (count($rec->attachmentsFh)) {
            
            //Вземаме id'тата на файловете вместо манупулатора име
            $attachments = fileman::fhKeylistToIds($rec->attachmentsFh);
            
            //Записваме прикачените файлове
            $rec->attachments = keylist::fromArray($attachments);
        }
        
        // Генерираме списък с документи, избрани за прикачане
        $docsArr = static::getAttachedDocuments($options);
        
        // Имейлите от адресат
        $rEmails = $rec->email;
        
        // Имейлите от получател
        $oEmails = $options->emailsTo;
        
        $groupEmailsArr = array();
        $groupEmailsArr['cc'][0] = $options->emailsCc;
        
        // Ако не сме променили имейлите
        if (trim($rEmails) == trim($oEmails)) {
            
            // Всики имейли са в една група
            $groupEmailsArr['to'][0] = $oEmails;
        } else {
            
            // Масив с имейлите от адресата
            $rEmailsArr = type_Emails::toArray($rEmails);
            
            // Масив с имейлите от получателя
            $oEmailsArr = type_Emails::toArray($oEmails);
            
            // Събираме в група всички имейли, които се ги има и в двата масива
            $intersectArr = array_intersect($oEmailsArr, $rEmailsArr);
            
            // Вземаме имейлите, които ги няма в адресата, но ги има в получатели
            $diffArr = array_diff($oEmailsArr, $rEmailsArr);
            
            // Добавяме имейлите, които са в адресат и в получател
            // Те ще се изпращат заедно с CC
            if ($intersectArr) {
                $groupEmailsArr['to'][0] = type_Emails::fromArray($intersectArr);
            }
            
            // Обхождаме всички имейли, които ги няма в адресат, но ги има в получател
            foreach ($diffArr as $diff) {
                
                // Добавяме ги в масива, те ще се изпращат самостоятелно
                $groupEmailsArr['to'][] = $diff;
            }
        }
        
        // CSS' а за имейли
        $emailCss = file_get_contents(sbf('css/email.css', "", TRUE));
        
        // списъци с изпратени и проблеми получатели
        $success  = $failure = array();
        
        // Ако е отговор на имейл опитваме се да извлечем In-Reply-To
        if ($rec->originId) {
            $originDoc = doc_Containers::getDocument($rec->originId);
            if ($originDoc->instance instanceof email_Incomings) {
                $iRec = $originDoc->fetch();
                $messageIdArr = (array)$iRec->headers['message-id'];
                $messageId = reset($messageIdArr);
                if ($messageId) {
                    $rec->__inReplyTo = trim($messageId, '<>');
                }
            }
        }
        
        // Обхождаме масива с всички групи имейли
        foreach ($groupEmailsArr['to'] as $key => $emailTo) {
            
            // Вземаме имейлите от cc
            $emailsCc = $groupEmailsArr['cc'][$key];
            
            // Конфигурацията на пакета
            $conf = core_Packs::getConfig('email');
            
            // Проверяваме дали същия имейл е изпращан преди
            $isSendedBefore = doclog_Documents::isSended($rec->containerId, $conf->EMAIL_RESENDING_TIME, $emailTo, $emailsCc);
            
            // Ако е изпращан преди
            if ($isSendedBefore) {
                
                // В събджекта добавяме текста
                $rec->_resending = 'Повторно изпращане';
            } else {
                
                // Ако не е изпращане преди
                $rec->_resending = NULL;
            }
            
            // Данни за съответния екшън
            $action = array(
                'containerId' => $rec->containerId,
                'action'      => doclog_Documents::ACTION_SEND,
                'data'        => (object)array(
                    'from' => $options->boxFrom,
                    'to'   => $emailTo,
                )
            );
            
            // Ако има CC
            if ($emailsCc) {
                
                // Добавяме към екшъна
                $action['data']->cc = $emailsCc;
            }
            
            // Добавяме изпращача
            $action['data']->sendedBy = core_Users::getCurrent();
            
            // Генериране на прикачените документи
            $rec->documentsFh = array();
            
            try {
                $convertStatus = TRUE;
                
                foreach ($docsArr as $attachDoc) {
                    // Използваме интерфейсен метод doc_DocumentIntf::convertTo за да генерираме
                    // файл със съдържанието на документа в желания формат
                    $fhArr = $attachDoc['doc']->convertTo($attachDoc['ext'], $attachDoc['fileName']);
                    
                    $rec->documentsFh += $fhArr;
                }
                
                // .. ако имаме прикачени документи ...
                if (count($rec->documentsFh)) {
                    
                    //Вземаме id'тата на файловете вместо манипулаторите
                    $documents = fileman::fhKeylistToIds($rec->documentsFh);
                    
                    //Записваме прикачените файлове
                    $rec->documents = keylist::fromArray($documents);
                }
            } catch (core_exception_Expect $e) {
                self::logErr("Грешка при генериране на файловете", $rec->id);
                reportException($e);
                $convertStatus = FALSE;
            }
            
            if ($convertStatus) {
                // Пушваме екшъна
                doclog_Documents::pushAction($action);
                
                // Подготовка на текста на писмото (HTML & plain text)
                $rec->__mid = NULL;
                $rec->html = static::getEmailHtml($rec, $lg, $emailCss);
                $rec->text = static::getEmailText($rec, $lg);
                $rec->text = core_ET::unEscape($rec->text);
                
                try {
                    // ... и накрая - изпращане.
                    $status = email_Sent::sendOne(
                        $options->boxFrom,
                        $emailTo,
                        $rec->subject,
                        $rec,
                        array(
                            'encoding' => $options->encoding
                        ),
                        $emailsCc,
                        $error
                    );
                } catch (core_exception_Expect $e) {
                    self::logErr("Грешка при изпращане", $rec->id);
                    reportException($e);
                    $status = FALSE;
                }
                
                // Записваме историята
                doclog_Documents::flushActions();
                
                // Ако възникне грешка при изпращане
                if (!$status) {
                    
                    // Записваме имейла, като върнат
                    doclog_Documents::returned($rec->__mid);
                }
            }
            
            // Стринга с имейлите, до които е изпратено
            $allEmailsToStr = ($emailsCc) ? "{$emailTo}, $emailsCc" : $emailTo;
            
            // Ако е изпратен успешно
            if ($status) {
                
                // Добавяме кутията от която се изпраща, като имейл по подразбиране за папката
                if ($rec->folderId) {
                    $currUserId = core_Users::getCurrent();
                    if ($currUserId > 0) {
                        $valArr = array();
                        $valArr['defaultEmail'] = $options->boxFrom;
                        $key = doc_Folders::getSettingsKey($rec->folderId);
                        core_Settings::setValues($key, $valArr, core_Users::getCurrent(), TRUE);
                    } 
                }
                
                // Правим запис в лога
                self::logWrite('Изпращане' , $rec->id);
                
                // Добавяме в масива
                $success[] = $allEmailsToStr;
            } else {
                
                $errType = 'err';
                foreach (email_Sent::$logErrToWarningArr as $v) {
                    if (stripos($error, $v)) {
                
                        $errType = 'warning';
                
                        break;
                    }
                }
                
                $errStr = 'Грешка при изпращане: ' . $error;
                if ($errType == 'warning') {
                    static::logWarning($errStr, $rec->id);
                } else {
                    static::logErr($errStr, $rec->id);
                }
                
                $failure[] = $allEmailsToStr;
            }
        }
        
        // Ако има успешно изпращане
        if ($success) {
            $successEmailsStr = implode(', ', $success);
            $msg = '|Успешно изпратено до|*: ' . $successEmailsStr;
            $statusType = 'notice';
            
            // Добавяме статус
            status_Messages::newStatus($msg, $statusType);
            
            // Инстанция на изходящи имейли
            $inst = cls::get('email_Outgoings');
            
            // Нулираме флага, защото имейла вече е изпратен
            // Проверява се в on_AfterSave
            $inst->flagSendIt = FALSE;
            
            $nRec = new stdClass();
            $nRec->id = $rec->id;
            
            $saveArray = array();
            $saveArray['id'] = 'id';
            $saveArray['modifiedOn'] = 'modifiedOn';
            $saveArray['modifiedBy'] = 'modifiedBy';
            
            // Ако имейла е активен или чернова и не е въведено време за изчакване
            if (!$options->waiting && ($rec->state == 'active' || $rec->state == 'draft' || $rec->state == 'pending')) {
                
                // Сменяме състоянието на затворено
                $nRec->state = 'closed';
                $saveArray['state'] = 'state';
            }
            
            // Ако ще се изчаква
            if ($options->waiting) {
                
                // Добавяме времето на изчкаваме и състоянието
                $nRec->waiting = $options->waiting;
                $nRec->state = 'waiting';
                $saveArray['state'] = 'state';
                $saveArray['waiting'] = 'waiting';
            }
            
            // От кого и кога е изпратено последно
            $nRec->lastSendedOn = dt::now();
            $nRec->lastSendedBy = core_Users::getCurrent();
            $saveArray['lastSendedOn'] = 'lastSendedOn';
            $saveArray['lastSendedBy'] = 'lastSendedBy';
            
            // Записваме
            $inst->save($nRec, $saveArray);
        }
        
        // Добавя FROM правила за всички имейли, за които няма никакви правила
        if ($successEmailsStr) {
            $successArr = type_Emails::toArray($successEmailsStr);
            
            $priority = email_Router::dateToPriority(dt::now(), 'low', 'desc');
            
            foreach ($successArr as $successEmail) {
                $recObj = (object)array(
                        'type' => email_Router::RuleFrom,
                        'key' => email_Router::getRoutingKey($successEmail, NULL, email_Router::RuleFrom),
                        'priority' => $priority,
                        'objectType' => 'document',
                        'objectId' => $rec->containerId
                    );
                
                // Създаване на `From` правило
                email_Router::saveRule($recObj, FALSE);
            }
        }
        
        // Ако има провалено изпращане
        if ($failure) {
            $msg = '|Грешка при изпращане до|*: ' . implode(', ', $failure);
            
            if ($error) {
                foreach (self::$errShowNotifyStr as $v) {
                    if (stripos($error, $v)) {
                        $msg .= '<br>' . $error;
                        break;
                    }
                }
            }
            
            $statusType = 'error';
            
            // Добавяме статус
            status_Messages::newStatus($msg, $statusType);
        }
    }
    
    
    /**
     * @param object $rec
     */
    static function getAttachedDocuments($rec)
    {
        $docs     = array();
        $docNames = type_Set::toArray($rec->documentsSet);
        
        //Обхождаме избрани документи
        foreach ($docNames as $fileName) {
            
            //Намираме името и разширението на файла
            if (($dotPos = mb_strrpos($fileName, '.')) !== FALSE) {
                $ext       = mb_substr($fileName, $dotPos + 1);
                $docHandle = mb_substr($fileName, 0, $dotPos);
            } else {
                $docHandle = $fileName;
            }
            
            // $docHandle -> $doc
            $doc = doc_Containers::getDocumentByHandle($docHandle);
            expect($doc);
            
            $docs[] = compact('doc', 'ext', 'fileName');
        }
        
        return $docs;
    }
    
    
    /**
     * Подготовка на формата за изпращане
     * Самата форма се взема от email_Send
     */
    function prepareSendForm_($data)
    {
        // Вземаме празна форма
        $form = cls::get('core_Form');
        
        // Задаваме екшъна
        $form->setAction(array($this, 'send'));
        
        // Подготвяме титлата
        $form->title = 'Изпращане на имейл';
        
        // Добавяме функционални полета
        $form->FNC('id', 'int', 'input=hidden, silent');
        $form->FLD('boxFrom', 'key(mvc=email_Inboxes, select=email)', 'caption=От адрес,mandatory');
        $form->FLD('encoding', 'enum(utf-8=Уникод|* (UTF-8),
                                    cp1251=Windows Cyrillic|* (CP1251),
                                    koi8-r=Rus Cyrillic|* (KOI8-R),
                                    cp1252=Western|* (CP1252),
                                    ascii=Латиница|* (ASCII))', 'caption=Знаци, formOrder=4');
        $form->FLD('attachments', 'keylist(mvc=fileman_files, select=name)', 'caption=Файлове,columns=4,input=none');
        $form->FLD('documents', 'keylist(mvc=fileman_files, select=name)', 'caption=Документи,columns=4,input=none');
        $form->FNC('emailsTo', 'emails', 'input,caption=До,mandatory,class=long-input,formOrder=2', array('attr' => array('data-role' => 'list')));
        $form->FNC('emailsCc', 'emails', 'input,caption=Копие до,class=long-input,formOrder=3', array('attr' => array('data-role' => 'list')));
        $form->FNC('waiting', 'time(suggestions=1 ден|3 дни|1 седмица|2 седмици, allowEmpty)', 'caption=Известяване при липса на отговор->След,hint=Време за известряване при липса на отговор след изпращане,input,formOrder=8');
        $form->FNC('delay', 'datetime', 'caption=Отложено изпращане->Изпращане на,hint=Време за отлагане на изпращането,input,formOrder=9,autohide');
                
        // Подготвяме лентата с инструменти на формата
        $form->toolbar->addSbBtn('Изпрати', 'send', NULL, array('id'=>'save', 'ef_icon'=>'img/16/move.png', 'title'=>'Изпращане на имейла'));
        
        $id = Request::get('id', 'int');
        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = array('email_Outgoings', 'single', $id);
        }
        
        // Ако има права за ипзващне на факс
        if (email_FaxSent::haveRightFor('send')) {
            // Добавяме бутона за факс
            $form->toolbar->addBtn('Факс', array('email_FaxSent', 'send', $id, 'ret_url' => $retUrl), 'ef_icon = img/16/fax.png', 'title=Изпращане на имейла като факс');
        }
        
        // Добавяме бутона отказ
        $form->toolbar->addBtn('Отказ', $retUrl, NULL, array('ef_icon'=>'img/16/close-red.png', 'title'=>'Спиране на изпращането'));
        
        // Вкарваме silent полетата
        $form->input(NULL, 'silent');
        
        // Добавяме всички данни
        $data->form = $form;
        
        return $data;
    }
    
    
    /**
     * Извиква се след подготовката на формата за изпращане
     */
    static function on_AfterPrepareSendForm($mvc, $data)
    {
        expect($data->rec = $mvc->fetch($data->form->rec->id));
        
        // Трябва да имаме достъп до нишката, за да можем да изпращаме писма от нея
        doc_Threads::requireRightFor('single', $data->rec->threadId);
        
        $data->form->getFieldType('boxFrom')->params['folderId'] = $data->rec->folderId;
        
        $data->form->setDefault('containerId', $data->rec->containerId);
        $data->form->setDefault('threadId', $data->rec->threadId);
        
        // Масив, който ще съдърща прикачените файлове
        $filesArr = array();
        
        $attachmenstArr = $mvc->getAttachments($data->rec);
        
        expect(is_array($attachmenstArr), $attachmenstArr, $data->rec);
        
        // Добавяне на предложения за прикачени файлове
        $filesArr += $attachmenstArr;
        
        // Добавяне на предложения на свързаните документи
        $docHandlesArr = $mvc->GetPossibleTypeConvertings($data->form->rec->id);
        
        if(count($docHandlesArr) > 0) {
            $data->form->FNC('documentsSet', 'set', 'input,caption=Документи,columns=4,formOrder=6');
            
            $suggestion = array();
            $setDef = array();
            
            //Вземаме всички документи
            foreach ($docHandlesArr as $name => $checked) {
                
                // Масив, с информация за документа
                $documentInfoArr = doc_RichTextPlg::getFileInfo($name);
                
                if (!$documentInfoArr['className']) continue;
                
                $rec = $documentInfoArr['className']::fetchByHandle($documentInfoArr);
                
                // Вземаме прикачените файлове от линковете към други документи в имейла
                $filesArr += (array)$documentInfoArr['className']::getAttachments($rec->id);
                
                //Проверяваме дали документа да се избира по подразбиране
                if ($checked == 'on') {
                    //Стойността да е избрана по подразбиране
                    $setDef[$name] = $name;
                }
                
                //Всички стойности, които да се покажат
                $suggestion[$name] = $name;
            }
            
            // Задаваме на формата да се покажат полетата
            $data->form->setSuggestions('documentsSet', $suggestion);
            
            // Задаваме, кои полета да са избрани по подразбиране
            $data->form->setDefault('documentsSet', $setDef);
        }
        
        // Ако има прикачени файлове
        if(count($filesArr) > 0) {
            
            // Задаваме на формата да се покажат полетата
            $data->form->FNC('attachmentsSet', 'set', 'input,caption=Файлове,formOrder=7,maxCaptionLen=25');
            $data->form->setSuggestions('attachmentsSet', $filesArr);
        }
        
        // Ако има originId
        if (($data->rec->originId) && ($data->rec->forward != 'yes')) {
            
            // Контрагент данните от контейнера
            $contrData = doc_Containers::getContragentData($data->rec->originId);
        } else {
            
            // Контрагент данните от нишката
            $contrData = doc_Threads::getContragentData($data->rec->threadId);
        }
        
        // Масив с всички имейли в До
        $emailsToArr = type_Emails::toArray($data->rec->email);
        
        // Масив с всички имейли в Cc
        $emailsCcArr = type_Emails::toArray($data->rec->emailCc);
        
        // Всички групови имейли
        $groupEmailsArr = type_Emails::toArray($contrData->groupEmails);
        
        // Добавяме и имейлите до които е изпратено в същата нишка
        $sendedToEmails = self::getSentToEmails(NULL, $data->rec->threadId);
        if ($sendedToEmails) {
            $sendedToEmailsArr = type_Emails::toArray($sendedToEmails);
            $groupEmailsArr = array_merge($groupEmailsArr, $sendedToEmailsArr);
        }
        
        // Премахваме нашите имейли
        $groupEmailsArr = email_Inboxes::removeOurEmails($groupEmailsArr);
        
        // Премахваме имейлите, които ги има записани в полето Имейл
        $groupEmailsArr = array_diff((array)$groupEmailsArr, (array)$emailsToArr);
        
        // Премахваме имейлите, които ги има записани в полето Копие
        $groupEmailsArr = array_diff((array)$groupEmailsArr, (array)$emailsCcArr);
        
        // Ако има имейл
        if (count($groupEmailsArr)) {
            
            // Ключовете да са равни на стойностите
            $groupEmailsArr = array_combine($groupEmailsArr, $groupEmailsArr);
        }
        
        // Добавяне на предложения за имейл адреси, до които да бъде изпратено писмото
        if (count($groupEmailsArr)) {
            $data->form->setSuggestions('emailsTo', array('' => '') + $groupEmailsArr);
            $data->form->setSuggestions('emailsCc', array('' => '') + $groupEmailsArr);
        }
        
        // По подразбиране кои да са избрани
        $data->form->setDefault('emailsTo', $data->rec->email);
        $data->form->setDefault('emailsCc', $data->rec->emailCc);
        
        // Стойността на полето От, дефинирано в персонализацията на профилите
        $defaultBoxFromId = self::getDefaultInboxId($data->rec->folderId);
        
        if (!$defaultBoxFromId) {
            email_Inboxes::redirect();
        }
        
        $data->form->setDefault('boxFrom', $defaultBoxFromId);
        
        // Ако имам папка
        if ($data->rec->folderId) {
            
            // Времето за изчакване да се вземе от последно изпратения имейл от паката, в което е използвано изчакване
            $waitingTime = $mvc->getLastWaitingTime($data->rec->folderId, $data->rec->email);
            
            $data->form->setDefault('waiting', $waitingTime);
        }
    }
    
    
    /**
     * Връща кутията по-подразбиране за съответния потребител
     * Ако е зададено в конфигурацията ще използваме нея
     * Ако няма зададен имейл, ще върне първия достъпен
     *
     * @param integer $folderId
     * @param integer $userId
     *
     * @return integer
     */
    static function getDefaultInboxId($folderId = NULL, $userId = NULL)
    {
        // Имейла от конфигурацията
        $conf = core_Packs::getConfig('email');
        $defaultSentBox = $conf->EMAIL_DEFAULT_SENT_INBOX;
        
        try {
            // Всички достъпни имейл кутии
            $emailOptions = email_Inboxes::getFromEmailOptions($folderId, $userId);
        }  catch (core_exception_Expect $e) {
            $emailOptions = array();
        }
        
        // Ако не е зададена кутия в конфигурацията, използваме първия имейл от масива
        if (!$defaultSentBox) {
            
            if ($emailOptions) {
                reset($emailOptions);
                $boxId = key($emailOptions);
            }
        } else {
            
            // Ако има кутия за изпращане и конфигурацията и е в допустимите, използваме нея
            if ($emailOptions[$defaultSentBox]) {
                $boxId = $defaultSentBox;
            } elseif(!is_numeric($defaultSentBox)) {
                
                // Ако е подаден, като стринг
                $boxId = array_search($defaultSentBox, $emailOptions);
            }
        }
        
        return $boxId;
    }
    
    
    /**
     * Връща последното изпозлвано време за изчакване за съответния имейл в папката
     *
     * @param integer $foldeId
     * @param string $email
     */
    static function getLastWaitingTime($foldeId, $email)
    {
        $query = static::getQuery();
        
        // Само от съответната папка
        $query->where(array("#folderId = '[#1#]'", $foldeId));
        
        // Ако е подаден имейл да се използва
        if ($email) {
            $query->where(array("#email = '[#1#]'", $email));
        }
        
        // Които имат време за изчакване
        $query->where('#waiting IS NOT NULL');
        
        // В съответните състояние
        $query->where("#state = 'waiting'");
        $query->orWhere("#state = 'wakeup'");
        $query->orWhere("#state = 'closed'");
        
        // Подредени по последно изпратени
        $query->orderBy('lastSendedOn', 'DESC');
        $query->limit(1);
        
        $rec = $query->fetch();
        
        if ($rec) {
            
            return $rec->waiting;
        }
    }
    
    
    /**
     * Проверка на входните параметри от формата за изпращане
     */
    static function on_AfterInputSendForm($mvc, $form)
    {
        if($form->isSubmitted()) {
            $rec = $form->rec;
            
            if($form->rec->encoding != 'utf8' && $form->rec->encoding != 'lat') {
                $html = (string) $rec->html;
                $converted = iconv('UTF-8', $rec->encoding, $html);
                $deconverted = iconv($rec->encoding, 'UTF-8', $converted);
                
                if($deconverted  != $html) {
                    $form->setWarning('encoding', 'Писмото съдържа символи, които не могат да се конвертират към|* ' .
                        $form->getFieldType('encoding')->toVerbal($rec->encoding));
                }
            }
            
            if ($rec->delay && $rec->delay < dt::now()) {
                $form->setError('delay', 'Отложеното изпращане може да е само в бъдеще');
            }
            
            // Вземаме записа
            $eRec = static::fetch($form->rec->id);
            
            // Ако има originId
            if ($eRec->originId) {
                
                // Вземаме документа от originId
                $oRec = doc_Containers::getDocument($eRec->originId);
                
                // Ако е входящ имейл
                if ($oRec->instance instanceof email_Incomings){
                    
                    // Вземаме записа
                    $iRec = email_Incomings::fetch($oRec->that);
                    
                    // Вземаме no-reply хедърите
                    $noReplayEmails = email_Mime::getHeadersFromArr($iRec->headers, 'no-reply', '*');
                    
                    // Вземаме само имейлите
                    $noReplayEmails = email_Mime::getAllEmailsFromStr($noReplayEmails, TRUE);
                    
                    // Превръщаме в масив
                    $noReplayEmailsArr = arr::make($noReplayEmails);
                    
                    // Ако има масив
                    if (count($noReplayEmailsArr)) {
                        
                        // Вземаме имейлите ДО
                        $emailsToArr = arr::make($form->rec->emailsTo, TRUE);
                        
                        // Вземаме имейлите CC
                        $emailsCcArr = arr::make($form->rec->emailsCc, TRUE);
                        
                        $toWarningArr = array();
                        $ccWarningArr = array();
                        
                        // Обхождаме масива с no-reply имейлите
                        foreach ($noReplayEmailsArr as $noReplayEmail) {
                            
                            // Ако имейла е в До
                            if ($emailsToArr[$noReplayEmail]) {
                                
                                // Добавяме в масива 
                                $toWarningArr[$noReplayEmail] = $noReplayEmail;
                            }
                            
                            // Ако имейла е в CC
                            if ($emailsCcArr[$noReplayEmail]) {
                                
                                // Добавяме в масива
                                $ccWarningArr[$noReplayEmail] = $noReplayEmail;
                            }
                        }
                        
                        // Съобщението
                        $msg = "Адреси, които не очакват отговор (no-reply)|*: ";
                        
                        // Ако има предупреждение До
                        if ($toWarningArr) {
                            
                            // Сетваме предупреждение
                            $form->setWarning('emailsTo', $msg . type_Emails::escape(implode(', ', $toWarningArr)));
                        }
                        
                        // Ако има предупреждение Cc
                        if ($ccWarningArr) {
                            
                            // Сетваме предупреждение
                            $form->setWarning('emailsCc', $msg . type_Emails::escape(implode(', ', $ccWarningArr)));
                        }
                    }
                }
            }
            
            // Ако ще се прикачат документи или файлове
            if (trim($rec->documentsSet) || trim($rec->attachmentsSet)) {
                
                // Прикачените документи
                $checkedDocs = static::getAttachedDocuments($rec);
                $docsSizesArr = $mvc->getDocumentsSizes($checkedDocs);
                
                // Прикачените файлове
                $attachmentsArr = type_Set::toArray($rec->attachmentsSet);
                $filesSizesArr = $mvc->getFilesSizes($attachmentsArr);
                
                // Проверяваме дали размерът им е над допсутимият
                $allAttachmentsArr = array_merge((array)$docsSizesArr, (array)$filesSizesArr);
                
                if (!$mvc->checkMaxAttachedSize($allAttachmentsArr)) {
                    
                    // Вербалният размер на файловете и документите
                    $docAndFilesSizeVerbal = $mvc->getVerbalSizesFromArray($allAttachmentsArr);
                    
                    if ($rec->documentsSet && $rec->attachmentsSet) {
                        $str = "файлове и документи";
                    } else if ($rec->documentsSet) {
                        $str = "документи";
                    } else {
                        $str = "файлове";
                    }
                    
                    $form->setError('attachmentsSet, documentsSet', "Размерът на прикачените {$str} е|*: " . $docAndFilesSizeVerbal);
                }
            }
            
            $mvc->checkHost($form, 'boxFrom');
        }
        
        // Проверяваме дали може да се изпраща до този имейл
        if ($form->isSubmitted()) {
            $mvc->checkForSending($form, array('emailsTo', 'emailsCc'));
        }
    }
    
    
    /**
     * Проверява се дали може да се изпраща имейл до съответните домейни
     * 
     * @param core_Form $form
     * @param array $fieldsArr
     */
    protected static function checkForSending($form, $fieldsArr = array('email'))
    {
        $stopSendTo = email_Setup::get('STOP_SEND_TO');
        
        if ($stopSendTo === FALSE) return;
        
        $stopSendToArr = type_Set::toArray($stopSendTo);
        
        $corpAcc = email_Accounts::getCorporateDomainsArr();
        
        // Подготвяме регулярния израз
        foreach ($stopSendToArr as &$stopStr) {
            $r = '__' . rand() . '__';
            $stopStr = str_replace('*', $r, $stopStr);
            $stopStr = preg_quote($stopStr, '/');
            $stopStr = str_replace($r, '.*', $stopStr);
        }
        
        $corporateDomains = email_Accounts::getCorporateDomainsArr();
        
        $rec = $form->rec;
        foreach ($fieldsArr as $fieldName) {
            $fValStr = $rec->{$fieldName};
            $fValArr = type_Emails::toArray($fValStr);
            
            foreach ($fValArr as $fVal) {
                $fVal = trim($fVal);
                if (!$fVal) continue;
                
                $fVal = mb_strtolower($fVal);
                
                $haveErr = FALSE;
                $errMsg = 'Не е позволено изпращането до този имейл|*: ' . type_Varchar::escape($fVal);
                
                foreach ($stopSendToArr as $stopReg) {
                    
                    $stopReg = "/{$stopReg}/i";
                    if (preg_match($stopReg, $fVal)) {
                        $haveErr = TRUE;
                    }
                }
                
                // Проверяваме и дали това не е опит за изпращане към вътрешен потребител
                list($nick,$domain) = explode('@', $fVal);
                
                if ($corporateDomains[$domain]) {
                    $haveErr = TRUE;
                    
                    $errMsg .=  "<br>|";
                    
                    // Ако е потребител в системата
                    if (core_Users::fetchField(array("LOWER(#nick) = '[#1#]' AND #state != 'rejected'", $nick))) {
                        $errMsg .=  "Вместо това използвайте коментар със споделяне към|* " . '@' . $nick;
                    } else {
                        $errMsg .= "Имейла е към корпоративната ви сметка";
                    }
                }
                
                if ($haveErr) {
                    $form->setError($fieldName, $errMsg);
                    
                    break;
                }
            }
        }
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
            
            if ($form->isSubmitted()) {
                $this->logWarning('Изпращане на писмо с линкове към частна мрежа', $form->rec->id);
            }
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $mvc->flagSendIt = ($form->cmd == 'sending');
            $mvc->flagSendItFax = ($form->cmd == 'sendingFax');
            
            if ($mvc->flagSendIt || $mvc->flagSendItFax) {
                $form->rec->state = 'active';
                
                $mvc->invoke('Activation', array($form->rec));
                
                // Ако се създава факс и се изпраща директно
                if ($mvc->flagSendItFax) {
                    
                    // Ако има услуга за изпращане на факс
                    if (email_FaxSent::haveRightFor('send', $form->rec)) {
                        
                        // Ако няма въведен факс номер
                        if (!trim($form->rec->fax)) {
                            
                            if (stripos($rec->email, '@fax.man')) {
                                //Ако изпращаме имейла и полето за имейл е празно, показва съобщение за грешка
                                $form->setError('fax', "За да изпратите факс, трябва да попълните полето|* <b>|Адресат|*->|Факс|*</b>.");
                            }
                        }
                    } else {
                        
                        // 
                        $form->setError('fax', "Нямате зададена услуга за изпращане на факс");
                    }
                } else if (!trim($form->rec->email)) {
                    //Ако изпращаме имейла и полето за имейл е празно, показва съобщение за грешка
                    $form->setError('email', "За да изпратите имейла, трябва да попълните полето|* <b>|Адресат|*->|Имейл|*</b>.");
                }
                
                $mvc->checkHost($form, 'subject');
            }
            
            // Проверяваме дали може да се изпраща до този имейл
            if ($form->isSubmitted()) {
                $mvc->checkForSending($form, array('email', 'emailCc'));
            }
            
            if (trim($form->rec->body) && (preg_match_all(type_Richtext::QUOTE_PATTERN, $form->rec->body, $matches))) {
                
                $quotOtherArr = array();
                
                foreach ((array)$matches[2] as $hnd) {
                    $hnd = trim($hnd);
                    if (!$hnd) continue;
                    
                    if ($fileInfo = doc_RichTextPlg::getFileInfo($hnd)) {
                        
                        if (!$fileInfo['id']) continue;
                        
                        if (!cls::load($fileInfo['className'], TRUE)) continue;
                        
                        $cls = cls::get($fileInfo['className']);
                        
                        $hRec = $cls->fetch($fileInfo['id']);
                        
                        if (($form->rec->theadId && (($form->rec->theadId != $hRec->threadId))) || 
                            ($form->rec->folderId && (($form->rec->folderId != $hRec->folderId)))) {
                                
                            $quotOtherArr[$hnd] = $hnd;
                        }
                    }
                }
                
                if (!empty($quotOtherArr)) {
                    $docStr = count($quotOtherArr) == 1 ? 'документ' : 'документи';
                    $form->setWarning('body', "Цитирате {$docStr} от друга нишка|*: " . implode(', ', $quotOtherArr));
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след запис на имейла, като дава възможност за моменталното му изпращане
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        if ($mvc->flagSendIt || $mvc->flagSendItFax) {
            
            $options = array();
            
            // Масив с всички документи
            $docHandlesArr = $mvc->GetPossibleTypeConvertings($rec->id);
            
            $docsArr = array();
            
            // Обхождаме документите
            foreach ($docHandlesArr as $name => $checked) {
                
                // Проверяваме дали документа да се избира по подразбиране
                if ($checked == 'on') {
                    
                    // Добавяме в масива
                    $docsArr[$name] = $name;
                }
            }
            
            // Ако има прикачени файлове по подаразбиране
            if (count($docsArr)) {
                
                // Инстанция на класа
                $typeSet = cls::get('type_Set');
                
                // Файловете, които ще се прикачат
                $docsSet = $typeSet->fromVerbal($docsArr);
                
                // Добавяме прикачените документи в опциите
                $options['documentsSet'] = $docsSet;
            }
            
            $lg = email_Outgoings::getLanguage($rec->originId, $rec->threadId, $rec->folderId, $rec->body);
            
            $boxFromId = static::getDefaultInboxId($rec->folderId);
            
            $options['boxFrom'] = $boxFromId;
            $options['encoding'] = 'utf-8';
            $options['emailsTo'] = $rec->email;
            $options['emailsCc'] = $rec->emailCc;
            
            // Ако ще се праща по имейл
            if ($mvc->flagSendIt) {
                
                // Изпращаме по имейл
                static::send($rec, (object)$options, $lg);
            } else if ($mvc->flagSendItFax) {
                
                // Услуга за изпращане
                $options['service'] = email_FaxSent::getAutoSendIntf();
                
                // Факсовете, до които да се прати
                $options['faxTo'] = $rec->fax;
                
                $emailArr = type_Emails::toArray($rec->email);
                
                foreach ($emailArr as $email) {
                    if (stripos($email, '@fax.man')) {
                        list($faxNum) = explode('@', $email, 2);
                        $options['faxTo'] .= ', ' . $faxNum;
                    }
                }
                $options['faxTo'] = ltrim($options['faxTo'], ', ');
                
                // Изпращаме факса
                email_FaxSent::send($rec, (object)$options, $lg);
            }
        }
        
        // Ако има id
        if ($rec->id) {
            
            // Вземаме целия запис
            $nRec = $mvc->fetch($rec->id);
        } else {
            
            // Клонираме
            $nRec = clone($rec);
        }
        
        if(!Mode::is('isMigrate')) {
            // Обръщението да се записва за
            $salUserId = email_Salutations::getUserId($nRec->containerId);
          
            if ($salUserId === FALSE || ($salUserId == core_Users::getCurrent())) {
                // Записваме обръщението в модела
                email_Salutations::add($nRec);
            }
        }
        
        // Ако препащме имейла
        if (($rec->forward == 'yes') && $rec->originId) {
            
            // Записваме в лога, че имейла, който е създаден е препратен
            doclog_Documents::forward($rec);
        }
    }
    
    
    /**
     * Връща plain-текста на писмото
     */
    static function getEmailText($oRec, $lg)
    {
        core_Lg::push($lg);
        
        $textTpl = static::getDocumentBody($oRec->id, 'plain', (object)array('rec' => $oRec));
        $text    = $textTpl->getContent();
        
        core_Lg::pop();
        
        return $text;
    }
    
    
    /**
     * 
     * 
     * @param email_Outgoings $mvc
     * @param core_Et $tpl
     * @param object $data
     */
    function on_BeforeRenderSingle($mvc, &$tpl, $data)
    {
        if ($data->lg && (Mode::is('printing') || Mode::is('text', 'xhtml'))) {
            core_Lg::push($data->lg);
        }
    }
    
    
    /**
     * 
     * 
     * @param email_Outgoings $mvc
     * @param core_Et $tpl
     * @param object $data
     */
    function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if ($data->lg && (Mode::is('printing') || Mode::is('text', 'xhtml'))) {
            core_Lg::pop($data->lg);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getEmailHtml($rec, $lg, $css = '')
    {
        core_Lg::push($lg);
        
        // Използваме интерфейсния метод doc_DocumentIntf::getDocumentBody() за да рендираме
        // тялото на документа (изходящия имейл)
        $res = static::getDocumentBody($rec->id, 'xhtml', (object)array('rec' => $rec));
        
        if ($res instanceof core_ET) {
            $content = $res->getContent();
        } else {
            $content = $res;
        }
        
        // Правим инлайн css, само ако са зададени стилове $css
        // Причината е, че Emogrifier не работи правилно, като конвертира html entities към 
        // символи (страничен ефект).
        //
        // @TODO Да се сигнализират създателите му
        //
        if($css) {
            //Създаваме HTML частта на документа и превръщаме всички стилове в inline
            //Вземаме всичките css стилове
            
            $css = file_get_contents(sbf('css/common.css', "", TRUE)) .
            "\n" . file_get_contents(sbf('css/Application.css', "", TRUE)) . "\n" . $css ;
            
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
        
        //Изчистваме HTML коментарите
        $content = self::clearHtmlComments($content);
        
        core_Lg::pop();
        
        if ($res instanceof core_ET) {
            $res->setContent($content);
        } else {
            $res = $content;
        }
        
        return $res;
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Да се цитират документа, ако не се редактира
        if (!$data->form->rec->id) {
            $data->form->fields['body']->type->params['appendQuote'] = 3;
        }
        
        $form = $data->form;
        $rec = $form->rec;
        
        // Ако се препраща
        $isForwarding = (boolean) Request::get('forward');
        $isCloning = (boolean) ($data->action == 'clone');
        $isEditing = (boolean) $rec->id;
        
        $faxTo = Request::get('faxto');
        $emailTo = Request::get('emailto');
        
        $emailTo = str_replace(email_ToLinkPlg::AT_ESCAPE, '@', $emailTo);
        $emailTo = str_replace('mailto:', '', $emailTo);
        
        $orderVal = 10.000091;
        
        // Бутон за изпращане
        if ($faxTo || stripos($emailTo, '@fax.man') || (!$rec->email && $rec->fax) || stripos($rec->email, '@fax.man')) {
            
            $mvc->singleTitle = "Факс";
            
            $btnParamsArr = array('order' => $orderVal, 'ef_icon' => 'img/16/fax2.png', 'title' => 'Изпращане на имейла по факс');
            
            if (!email_FaxSent::haveRightFor('send')) {
                $btnParamsArr['error'] = 'Не е настроена сметка за изпращане';
            }
            
            $form->toolbar->addSbBtn('Изпрати', 'sendingFax', NULL, $btnParamsArr);
        } else {
            
            $btnParamsArr = array('order' => $orderVal,'ef_icon' => 'img/16/move.png', 'title' => 'Изпращане на имейла');
            
            $defaultBoxFromId = self::getDefaultInboxId($rec->folderId);
            if (!isset($defaultBoxFromId)) {
                $btnParamsArr['error'] = 'Не е настроена сметка за изпращане';
            }
            
            $form->toolbar->addSbBtn('Изпрати', 'sending', NULL, $btnParamsArr);
        }
        
        $pContragentData = NULL;
        
        // Ако сме дошли на формата чрез натискане на имейл или се препраща
        if ($emailTo) {
            
            if (type_Email::isValidEmail($emailTo)) {
                if (!$isForwarding) {
                    // Опитваме се да вземаме папката
                    if (!$folderId = self::getAccessedEmailFolder($emailTo)) {
                        
                        if ($personId = crm_Profiles::getProfile()->id) {
                            // Ако нищо не сработи вземаме папката на текущия потребител
                            $folderId = crm_Persons::forceCoverAndFolder($personId);
                        } else {
                            // Трябва да има потребителски профил
                            expect(FALSE, 'Няма потребителски профил');
                        }
                    } else {
                        
                        // Когато се използва бизнес имейл на лице - за да му се вземат личните данните
                        $buzPersFolderId = crm_Persons::getFolderFromBuzEmail($emailTo);
                        if ($buzPersFolderId && ($buzPersFolderId == $folderId)) {
                            $personEmail = mb_strtolower($emailTo);
                            $personRec = crm_Persons::fetch(array("LOWER(#buzEmail) LIKE '%[#1#]%'", $personEmail));
                            $pContragentData = crm_Persons::getContragentData($personRec->id);
                        }
                    }
                    $rec->folderId = $folderId;
                    unset($rec->threadId);
                }
            } else {
                //Ако не е валидемимейал, добавяме статус съобщения, че не е валиден имейл
                status_Messages::newStatus("|Невалиден имейл|*: {$emailTo}", 'warning');
            }
        }
        
        if (!$isEditing && !$isCloning) {
            
            // Ако се препраща в нова нишка
            if (($isForwarding) && ($folderId = Request::get('folderId', 'int'))) {
                $rec->folderId = $folderId;
                unset($rec->threadId);
            }
            
            // Ако е отговор, на някой документ
            if (!$isForwarding) {
                
                if (!$rec->threadId && $rec->originId) {
                    $rec->threadId = doc_Containers::fetchField($rec->originId, 'threadId');
                }
                
                if (!$rec->folderId && $rec->threadId) {
                    $rec->folderId = doc_Threads::fetchField($rec->threadId, 'folderId');
                }
                
                $emailLg = email_Outgoings::getLanguage($rec->originId, $rec->threadId, $rec->folderId);
                $rec->forward = 'no';
            } else {
                $emailLg = email_Outgoings::getLanguage(FALSE, FALSE, $rec->folderId);
                $rec->forward = 'yes';
            }
            
            if ($rec->originId) {
                $oDoc = doc_Containers::getDocument($rec->originId);
            }
            
            core_Lg::push($emailLg);
            
            if ($rec->originId && $oDoc->haveInterface('email_DocumentIntf')) {
                $rec->subject = $oDoc->getDefaultEmailSubject($isForwarding);
            }
            
            $hintStr = tr('Смяна на езика');
            
            // Данните на адресата
            $contragentData = self::prepareContragentData($rec, $isForwarding);
            if ($contragentData && $pContragentData && $pContragentData->person) {
                $contragentData->person = $contragentData->person ? $contragentData->person : $pContragentData->person;
            }
            self::setContragentDataToRec($contragentData, $rec);
            
            if ($emailTo) {
                $rec->email = $emailTo;
            }
            
            if ($faxTo) {
                $rec->fax = $faxTo;
            }
            
            // Подготвяме тялото на имейла и преводите
            $bodyLangArr = array();
            $bCnt = 0;
            //Създаваме тялото на постинга
            $rec->body = $bodyLangArr[$bCnt]['data'] = $mvc->createDefaultBody($contragentData, $rec, $isForwarding);
            $bodyLangArr[$bCnt]['lg'] = $emailLg;
            
            $allLangArr = arr::make(EF_LANGUAGES);
            
            if ($allLangArr) {
                foreach ($allLangArr as $lang => $verbLang) {
                    
                    if ($lang == $emailLg) continue;
                    $bCnt++;
                    // За всеки език подоготвяме текста
                    core_Lg::push($lang);
                    $bodyLangArr[$bCnt]['data'] = $mvc->createDefaultBody($contragentData, $rec, $isForwarding);
                    $bodyLangArr[$bCnt]['lg'] = $lang;
                    core_Lg::pop();
                }
            }
            
            $data->__bodyLgArr = array('hint' => $hintStr, 'lg' => $emailLg, 'data' => $bodyLangArr);
            $data->form->layout = new ET($data->form->renderLayout());
            
            jquery_Jquery::run($form->layout, "prepareLangBtn(" . json_encode($data->__bodyLgArr) . ");");
            
            core_Lg::pop();
            
            $recEmailsArr = type_Emails::toArray($rec->email);
            
            // Ако не отговаряме на конкретен имейл, премахваме нашите имейли
            if (!$emailTo) {
                $recEmailsArr = email_Inboxes::removeOurEmails($recEmailsArr);
            }
            
            if ($contragentData->replyToEmail) {
                $removeFromGroup = $recEmailsArr;
            } else {
                if ($recEmailsArr && $recEmailsArr[0]) {
                    $removeFromGroup = array($recEmailsArr[0]);
                    $recEmailsArr = $removeFromGroup;
                } else {
                    $removeFromGroup = array();
                }
            }
            
            // Ако има имейли в Cc и е избрано да се попълват ги добавяме в полето
            if ($contragentData->ccEmail) {
                
                $autoFillCnt = email_Setup::get('AUTO_FILL_EMAILS_FROM_CC');
                if ($autoFillCnt == 'no' || !$autoFillCnt) {
                    $autoFillCnt = 0;
                } elseif ($autoFillCnt == 'yes') {
                    $autoFillCnt = 1; 
                }
                
                if ($autoFillCnt) {
                    
                    $ccEmails = $rec->emailCc;
                    $ccEmails .= $ccEmails ? ', ' : '';
                    $ccEmails .= $contragentData->ccEmail;
                
                    $ccEmailsArr = type_Emails::toArray($ccEmails);
                    
                    $ccEmailsArr = array_combine($ccEmailsArr, $ccEmailsArr);
                    $ccEmailsArr = email_Inboxes::removeOurEmails($ccEmailsArr);
                    
                    // Ако имейлите в копие са над лимита, не ги добавяме автоматично в полето
                    if (count($ccEmailsArr) <= $autoFillCnt) {
                        $rec->emailCc = type_Emails::fromArray($ccEmailsArr);
                        $removeFromGroup = array_merge($removeFromGroup, $ccEmailsArr);
                    }
                }
            }
        } else {
            if ($isCloning) {
                $oId = $rec->originId ? $rec->originId : $rec->containerId;
                $emailLg = email_Outgoings::getLanguage($oId, $rec->threadId, $rec->folderId);
            } else {
                $emailLg = email_Outgoings::getLanguage($rec->containerId, $rec->threadId, $rec->folderId);
            }
            
            core_Lg::push($emailLg);
            if ($rec->threadId) {
                $contragentData = doc_Threads::getContragentData($rec->threadId);
            } elseif ($rec->folderId) {
                $contragentData = doc_Folders::getContragentData($rec->folderId);
            }
            core_Lg::pop();
            
            $recEmailsArr = type_Emails::toArray($rec->email);
            $removeFromGroup = $recEmailsArr;
        }
        
        $rec->email = type_Emails::fromArray($recEmailsArr);
        
        $groupEmailsArr = array();
        if ($contragentData->groupEmails) {
            $groupEmailsArr = type_Emails::toArray($contragentData->groupEmails);
        }
        
        // Подготвяме груповите имейли
        $sentToEmails = self::getSentToEmails(NULL, $rec->threadId);
        if ($sentToEmails) {
            $sentToEmailsArr = type_Emails::toArray($sentToEmails);
            $groupEmailsArr = array_merge($groupEmailsArr, $sentToEmailsArr);
        }
        
        $groupEmailsArr = array_diff($groupEmailsArr, $removeFromGroup);
        $groupEmailsArr = email_Inboxes::removeOurEmails($groupEmailsArr);
        
        if ($groupEmailsArr) {
            
            $groupEmailsArr = array_combine($groupEmailsArr, $groupEmailsArr);
            
            // Имейлите по подразбиране
            $data->form->setSuggestions('email', array('' => '') + $groupEmailsArr);
            $data->form->setSuggestions('emailCc', array('' => '') + $groupEmailsArr);
            $data->form->addAttr('email', array('data-role' => 'list'));
            $data->form->addAttr('emailCc', array('data-role' => 'list'));
        }
        
        // Ако има открит език
        if ($emailLg) {
            
            $langAttrArr = array("lang" => $emailLg, "spellcheck" => "true");
            
            // Добавяме атрибути към тялото и заглавието
            $data->form->addAttr('body', $langAttrArr);
            $data->form->addAttr('subject', $langAttrArr);
        }
    }
    
    
    /**
     * Прави опит да определи контрагент данните и връща резултат за тях
     * 
     * @param stdObject $rec
     * @param boolean $isForwarding
     * 
     * @return NULL|stdObject
     */
    protected static function prepareContragentData($rec, $isForwarding=FALSE)
    {
        $contragentData = NULL;
        
        if (!$isForwarding) {
            
            if ($rec->threadId) {
                $contragentData = doc_Threads::getContragentData($rec->threadId);
            }
            
            if ($rec->originId) {
                $oDoc = doc_Containers::getDocument($rec->originId);
                $oContragentData = $oDoc->getContragentData();
                
                if ($oContragentData->person) {
                    $contragentData->person = ($contragentData->person) ? $contragentData->person : $oContragentData->person;
                }
                
                if ($oContragentData->replyToEmail) {
                    $contragentData->replyToEmail = ($contragentData->replyToEmail) ? $contragentData->replyToEmail : $oContragentData->replyToEmail;
                }
                
                // Добавяме имейла от originId на мястото да другия имейл
                if ($oContragentData->email) {
                    if ($contragentData->email) {
                        $contragentData->groupEmails .= ($contragentData->groupEmails) ? ', ' : '';
                        $contragentData->groupEmails .= $contragentData->email;
                    }
                    $contragentData->email = $oContragentData->email;
                }
                
                // Имейлите от полето копие
                if ($oContragentData->ccEmail) {
                    $contragentData->ccEmail .= ($contragentData->ccEmail) ? ', ': '';
                    $contragentData->ccEmail .= $oContragentData->ccEmail;
                }
                
                if ($oContragentData->groupEmails) {
                    $contragentData->groupEmails .= ($contragentData->groupEmails) ? ', ' : '';
                    $contragentData->groupEmails .= $oContragentData->groupEmails;
                }
            }
        }
        
        if (!$contragentData) {
            $contragentData = doc_Folders::getContragentData($rec->folderId);
        } else {
            // Ако е в папка на котрагент, може да се използват името на фирмата, лицето и държавата от там
            $cover = doc_Folders::getCover($rec->folderId);
            if (($cover->instance instanceof crm_Companies) || ($cover->instance instanceof crm_Persons)) {
            
                $use = TRUE;
            
                $contrData = $cover->getContragentData();
            
                $contrData->groupEmails = mb_strtolower($contrData->groupEmails);
            
                $emailsArr = type_Emails::toArray($contrData->groupEmails);
            
                if ($rec->originId) {
                    $oDoc = doc_Containers::getDocument($rec->originId);
                    $oRec = $oDoc->fetch();
                    $fromEml = $oRec->fromEml;
                    $fromEml = trim($fromEml);
                    $fromEml = mb_strtolower($fromEml);
            
                    if (!$fromEml || !in_array($fromEml, $emailsArr)) {
                        $use = FALSE;
                    }
                }
            
                if ($use) {
                    $contragentData->country = $contrData->country;
                    $contragentData->countryId = $contrData->countryId;
                    if ($contrData->person) {
                        $contragentData->person = $contrData->person;
                    }
                    $contragentData->company = $contrData->company;
                    $contragentData->companyId = $contrData->companyId;
                }
            }
        }
        
        return $contragentData;
    }
    
    
    /**
     * Задава стойности на контрагент данните
     * 
     * @param stdObject $contragentData
     * @param stdObject $rec
     */
    protected static function setContragentDataToRec($contragentData, &$rec)
    {
        if (!$contragentData) return ;
        
        crm_Companies::removeOwnCompanyData($contragentData);
        
        $rec->recipient = $contragentData->company;
        $rec->attn      = $contragentData->person;
        $rec->country   = $contragentData->country;
        $rec->pcode     = $contragentData->pCode;
        $rec->place     = $contragentData->place;
        
        $rec->tel = $contragentData->tel ? $contragentData->tel : $contragentData->pMobile;
        if (!$rec->tel) {
            $rec->tel = $contragentData->pTel;
        }
        
        $rec->fax = $contragentData->fax ? $contragentData->fax : $contragentData->pFax;
        
        $rec->address = $contragentData->address ? $contragentData->address : $contragentData->pAddress;
        
        if (trim($contragentData->replyToEmail)) {
            $rec->email = email_Mime::getAllEmailsFromStr($contragentData->replyToEmail);
        } else {
            $rec->email = $contragentData->email ? $contragentData->email : $contragentData->pEmail;
        }
    }
    
    
    /**
     * Създава тялото на постинга
     */
    function createDefaultBody($contragentData, $rec, $forward = FALSE)
    {
        $contragentDataHeader = (array)$contragentData;
            
        //Данни необходими за създаване на хедър-а на съобщението
        $contragentDataHeader['name'] = $contragentData->person;
        
        // Ако има обръщение
        if($contragentData->salutationRec) {
            if($contragentData->salutationRec == 'mrs' || $contragentData->salutationRec == 'miss') {
                $contragentDataHeader['hello'] = tr("Уважаема");
            } else {
                $contragentDataHeader['hello'] = tr("Уважаеми");
            }
        }
        
        if (!$contragentDataHeader['hello']) {
            if($contragentData->person) {
                $contragentDataHeader['hello'] = tr('Здравейте') . ',';
                if (core_Lg::getCurrent() == 'bg') {
                    $contragentDataHeader['lastChar'] = '!';
                }
            } else {
                $contragentDataHeader['hello'] = tr('Уважаеми колеги');
            }
        }
        
        //Хедър на съобщението
        $header = $this->getHeader($contragentDataHeader, $rec);
        
        //Текста между заглавието и подписа
        $body = $this->getBody($rec->originId, $forward);
        
        //Футър на съобщението
        $footer = $this->getFooter($contragentDataHeader['countryId']);
        
        //Текста по подразбиране в "Съобщение"
        $defaultBody = $header . "\n\n" . $body . "\n\n" . $footer;
        
        return $defaultBody;
    }
    
    
    /**
     * Създава хедър към постинга
     *
     * @param array $headerDataArr
     * @param object $rec
     */
    function getHeader($headerDataArr, $rec)
    {
        $cu = core_Users::getCurrent();
        
        $salutation = NULL;
        
        if ($cu > 0) {
            // Вземаме обръщението
            $salutation = email_Salutations::get($rec->folderId, $rec->threadId, $rec->email, $cu);
        }
        
        if (!$salutation && ($cu > 0)) {
            $salutation = email_Salutations::get($rec->folderId, $rec->threadId, $rec->email);
        }
        
        // Ако обръщението не съвпадата с текущия език, да се остави да се определи от системата
        if ($salutation) {
            $isCyrillic = FALSE;
            
            if (strlen($salutation) != mb_strlen($salutation)) {
                $isCyrillic = TRUE;
            }
            
            $currLg = core_Lg::getCurrent();
            
            if (array_search($currLg, array('bg', 'ru', 'md', 'sr')) === FALSE) {
                if ($isCyrillic) {
                    $salutation = '';
                }
            } else {
                if (!$isCyrillic) {
                    $salutation = '';
                }
            }
        }
        
        // Ако сме открили обръщение използваме него
        if ($salutation) return $salutation;
        
        $conf = core_Packs::getConfig('email');
        
        $headerStr = core_Packs::getConfigValue($conf, 'EMAIL_OUTGOING_HEADER_TEXT');
        
        // Шаблона за хедъра
        $tpl = new ET($headerStr);
        
        // Заместваме плейсхолдерите
        $tpl->placeArray($headerDataArr);
        
        // Вземаме съдържанието
        $content = $tpl->getContent();
        
        // Премахваме ненужните празни стойности
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);
        
        // Ако последният символ не е пунктуация, добавяме запетая накрая
        $lastChar = mb_substr($content, -1);
        
        setIfNot($headerDataArr['lastChar'], ',');
        
        if (!core_String::isPunctuation($lastChar)) {
            $content .= $headerDataArr['lastChar'];
        }
        
        return $content;
    }
    
    
    /**
     * Създава текста по подразбиране
     *
     * @param integer $originId
     * @param boolean $forward
     */
    function getBody($originId, $forward = FALSE)
    {
        if (!$originId) return ;
        
        //Вземаме класа, за който се създава съответния имейл
        $document = doc_Containers::getDocument($originId);
        
        //Ако класа имплементира интерфейса "doc_ContragentDataIntf", тогава извикваме метода, който ни връща тялото на имейл-а
        if (cls::haveInterface('email_DocumentIntf', $document->className)) {
            $intf = cls::getInterface('email_DocumentIntf', $document->className);
            $body = $intf->class->getDefaultEmailBody($document->that, $forward);
        }
        
        return $body;
    }
    
    
    /**
     * Създава футър към постинга в зависимост от типа на съобщението
     *
     * @param integer $contragentCountryId
     */
    public static function getFooter($contragentCountryId = NULL, $userId = NULL)
    {
        if(!$userId) {
            $userId = core_Users::getCurrent();
        }

        $conf = core_Packs::getConfig('email');
        
        // Профила на текущият потребител
        $personRec = crm_Profiles::getProfile($userId);
        
        // Ако потребителя няма фирма
        if (!($companyId = $personRec->buzCompanyId)) {
            
            // Вземаме фирмата по подразбиране
            $companyId = crm_Setup::BGERP_OWN_COMPANY_ID;
        }
        
        // Вземаме данните за нашата фирма
        $companyRec = crm_Companies::fetch($companyId);
        
        $footerData = array();
        
        // Името на компанията
        $footerData['company'] = transliterate(tr($companyRec->name));
        
        // Името на потребителя
        $footerData['name'] = transliterate($personRec->name);
        
        // Телефон
        $footerData['tel'] = ($personRec->buzTel) ? ($personRec->buzTel) : $companyRec->tel;
        
        // Факс
        $footerData['fax'] = ($personRec->buzFax) ? ($personRec->buzFax) : $companyRec->fax;
        
        // Имейл
        $footerData['email'] = ($personRec->buzEmail) ? ($personRec->buzEmail) : $companyRec->email;
        
        // Длъжност
        $footerData['position'] = tr($personRec->buzPosition);
        
        // Ако няма въведен адрес на бизнеса на потребителя
        if ($personRec->buzAddress) {
            
            // Адреса
            $footerData['street'] = transliterate(tr($personRec->buzAddress));
        } else {
            // Определяме адреса от фирмата
            $footerData['pCode'] = transliterate($companyRec->pCode);
            $footerData['city'] = transliterate(tr($companyRec->place));
            $footerData['street'] = transliterate(tr($companyRec->address));
            
            if ($footerData['pCode']) {
                $footerData['pCodeAndCity'] = $footerData['pCode'] . ' ';
            }
            
            $footerData['pCodeAndCity'] .= ' ' . $footerData['city'];
            
            // Ако няма държава на контрагента
            if (!$contragentCountryId) {
                
                // Езиците в съответната държава
                $companyCountryLang = drdata_Countries::fetchField($companyRec->country, 'languages');
                $companyCountryLangArr = arr::make($companyCountryLang, TRUE);
                
                // Вземаме езика
                $lg = core_Lg::getCurrent();
                
                // Ако текущия език не е на държавата
                if (!$companyCountryLangArr[$lg]) {
                    
                    $getCountry = TRUE;
                }
            } elseif($companyRec->country != $contragentCountryId) {
                
                // Ако контрагента не е от държавата на фирмата
                
                $getCountry = TRUE;
            }
            
            // Ако ще се добавя държавата
            if ($getCountry) {
                $footerData['country'] = crm_Companies::getVerbal($companyRec, 'country');
            }
        }
        
        // Страницата
        $footerData['website'] = $companyRec->website;
        
        // Зареждаме шаблона
        $tpl = new ET(core_Packs::getConfigValue($conf, 'EMAIL_OUTGOING_FOOTER_TEXT'));

        // Променливи, нужни за определяне дали в реда е бил заместен плейсхолдер
        $tplClone = clone $tpl;
        $tplWithPlaceholders = $tplClone->getContent(NULL, "CONTENT", FALSE, FALSE);
        $tplWithPlaceholdersArr = explode("\n", $tplWithPlaceholders);
        $tplWithoutPlaceholders = $tplClone->getContent();
        $tplWithoutPlaceholdersArr = explode("\n", $tplWithoutPlaceholders);
        
        // Заместваме плейсхолдерите
        $tpl->placeArray($footerData);
        
        $content = $tpl->getContent();
        
        // Премахва празните редове, в които няма никаква стойност
        // Премахва и редовете, в които е имало плейсхолдер, но не е бил заместен
        $contentArr = explode("\n", $content);
        
        foreach ((array)$contentArr as $key => $line) {
            
            // Ако е празен ред
            if (!$line) continue;
            
            // Ако е имало плейсхолдер, който е заместен и има друг стринг в реда, премхаваме целия ред
            if (($tplWithPlaceholdersArr[$key] != $line) && ($tplWithoutPlaceholdersArr[$key] == $line)) continue;
            
            $nContent .= ($nContent) ? "\n" . $line : $line;
        }
        
        return $nContent;
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     * 
     * @see email_DocumentIntf
     * @param int $id - ид на документа
     * @param boolean $forward
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = FALSE)
    {
        // Ако препращаме
        if ($forward) {
            
            $mvc = cls::get('email_Outgoings');
        
            $text = email_Outgoings::prepareDefaultEmailBodyText($mvc, $id, 'createdOn', $forward);
        }
        
        return $text;
    }
    
    
    /**
     * Подготвя текст за тялото на имейла при отговор и препращане
     * 
     * @param core_Mvc $class
     * @param integer $id
     * @param string $dateField
     * @param boolean $forward
     * 
     * @return core_ET
     */
    public static function prepareDefaultEmailBodyText($class, $id, $dateField = 'date', $forward = FALSE)
    {
        //Вземаме датата от базата от данни
        $date = $class->fetchField($id, $dateField);
        
        if ($forward) {
            $key = 'EMAIL_FORWARDING_DEFAULT_EMAIL_BODY_FORWARDING';
        } else {
            $key = 'EMAIL_INCOMINGS_DEFAULT_EMAIL_BODY';
        }
        
        $text = core_Packs::getConfigValue('email', $key);
        
        $textTpl = new ET($text);
        
        $placeArr = $textTpl->getPlaceholders();
        
        $valArr = array();
        
        foreach ((array)$placeArr as $placeHolder) {
            
            $placeHolderU = strtoupper($placeHolder);
            
            switch ($placeHolderU) {
                case 'DATETIME':
                    $valArr[$placeHolder] = dt::mysql2verbal($date, 'd-M H:i', NULL, FALSE);
                break;
                
                case 'DATE':
                    $valArr[$placeHolder] = dt::mysql2verbal($date, 'd-M', NULL, FALSE);
                break;
                
                case 'MSG':
                    // Манипулатора на документа
                    $valArr[$placeHolder] = '#' . $class->getHandle($id);
                break;
                
                default:
                    ;
                break;
            }
        }
        
        $textTpl->placeArray($valArr);
        
        return $textTpl;
        
    }
    
    
    /**
     * Подготвя иконата за единичния изглед
     */
    static function on_AfterPrepareSingle($mvc, $data)
    {
        if (!Mode::isReadOnly()) {
        
            // Изчистваме нотификацията за събуждане
            $url = array($mvc, 'single', $data->rec->id, 'wakeUp' => TRUE);
            bgerp_Notifications::clear($url);
        }
        
        if($data->rec->recipient || $data->rec->attn || $data->rec->email) {
            $data->row->headerType = tr('Писмо');
        } elseif($data->rec->originId) {
            $data->row->headerType = tr('Отговор');
        } else {
            $threadRec = doc_Threads::fetch($data->rec->threadId);
            
            if($threadRec->firstContainerId == $data->rec->containerId) {
                $data->row->headerType = tr('Съобщение');
            } else {
                $data->row->headerType = tr('Съобщение');
            }
        }
        
        $data->lg = email_Outgoings::getLanguage($data->rec->originId, $data->rec->threadId, $data->rec->folderId, $data->rec->body);
        
        if (!Mode::is('text', 'xhtml') && $data->rec->waiting && ($data->rec->state == 'waiting' || $data->rec->state == 'active' || $data->rec->state == 'pending')) {
            $notifyDate = dt::addSecs($data->rec->waiting, $data->rec->lastSendedOn);
            $data->row->notifyDate = dt::mysql2verbal($notifyDate, 'smartTime');
            $notifyUserId = $data->rec->lastSendedBy ? $data->rec->lastSendedBy : $data->rec->modifiedBy;
            if (!$notifyUserId) {
                $notifyUserId = $data->rec->activatedBy;
            }
            $data->row->notifyUser = crm_Profiles::createLink($notifyUserId);
            
            if ($mvc->haveRightFor('close', $data->rec)) {
                $data->row->removeNotify = ht::createLink('', array($mvc, 'close', $data->rec->id, 'ret_url'=>TRUE), tr('Сигурни ли сте, че искате да спрете изчакването') . '?',
                                                            array('ef_icon' => 'img/12/close.png', 'class' => 'smallLinkWithWithIcon', 'title' => 'Премахване на изчакването за отговор'));
            }
        }
        
        if (!Mode::is('text', 'xhtml')) {
            $sendArr = email_SendOnTime::getPendingRows($data->rec->id);
            
            if ($sendArr) {
                $data->row->sendLater = new ET();
                foreach ($sendArr as $row) {
                    $sendTpl = getTplFromFile('email/tpl/SendOnTimeText.shtml');
                    $sendTpl->placeObject($row);
                    $sendTpl->removePlaces();
                    $sendTpl->removeBlocks();
                    $data->row->sendLater->append($sendTpl);
                }
            }
        }
    }
    
    
    /**
     * След рендиране на singleLayout заместваме плейсхолдера
     * с шаблонa за тялото на съобщение в документната система
     */
    function renderSingleLayout_(&$data)
    {
        if ($data->lg && (Mode::is('printing') || Mode::is('text', 'xhtml'))) {
            core_Lg::push($data->lg);
        }
        
        //Полета До и Към
        $attn = $data->row->recipient . $data->row->attn;
        $attn = trim($attn);
        
        //Ако нямаме въведени данни До: и Към:, тогава не показваме имейл-а, и го записваме в полето До:
        if (!$attn) {
            $data->row->recipientEmail = $data->row->email;
            $data->row->emailCcLeft = $data->row->emailCc;
            unset($data->row->email);
            unset($data->row->emailCc);
        }
        
        //Полета Град и Адрес
        $addr = $data->row->place . $data->row->address;
        $addr = trim($addr);
        
        //Ако липсва адреса и града
        if (!$addr) {
            //Не се показва и пощенския код
            unset($data->row->pcode);
            
            //Ако имаме До: и Държава, и нямаме адресни данни, тогава добавяме държавата след фирмата
            if ($data->row->recipient) {
                $data->row->firmCountry = $data->row->country;
            }
            
            //Не се показва и държавата
            unset($data->row->country);
            
            $telFax = $data->row->tel . $data->row->fax;
            $telFax = trim($telFax);
            
            //Имейла е само в дясната част, преместваме в ляво
            if (!$telFax) {
                $data->row->emailLeft = $data->row->email;
                setIfNot($data->row->emailCcLeft, $data->row->emailCc);
                unset($data->row->email);
                unset($data->row->emailCc);
            }
        }
        
        // Определяме лейаута според режима на рендиране
        
        switch (true)
        {
            case Mode::is('text', 'plain') :
            $tpl = 'email/tpl/SingleLayoutOutgoings.txt';
            break;
            
            case (Mode::is('printing') || Mode::is('text', 'xhtml')) :
            $tpl = 'email/tpl/SingleLayoutSendOutgoings.shtml';
            break;
            
            default :
            $tpl = 'email/tpl/SingleLayoutOutgoings.shtml';
        }
        
        $tpl = getTplFromFile($tpl);
        
        if ($data->lg && (Mode::is('printing') || Mode::is('text', 'xhtml'))) {
            core_Lg::pop();
        }
        
        return $tpl;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     * @param array $fields
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $row->handle = $mvc->getHandle($rec->id);
        
        if ($fields['-single']) {
            $row->singleTitle = tr('Изходящ имейл');
            
            $isAbsolute = (boolean) Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf');
            
            if (!$isAbsolute && core_Users::isPowerUser()) {
                $sendEmailsArr = doclog_Documents::getSendEmails($rec->containerId);
                
                $emailsTld = type_Emails::getCountryFromTld($sendEmailsArr, 'letterCode2');
                
                $viewIp = doclog_Documents::getViewIp($rec->containerId);
                
                $badIpArr = email_Incomings::getBadIpArr($viewIp, $rec->folderId, $emailsTld);
                
                if (!empty($badIpArr)) {
                    $row->IpErrorString = tr('Писмото е видяно от потребител в рискова зона|* - ');
                    $countryName = '';
                    foreach ($badIpArr as $ip => $countryCode) {
                        $row->IpErrorString .= ($countryName) ? ', ' : '';
                        $countryName = drdata_Countries::getCountryName($countryCode, core_Lg::getCurrent());
                        $row->IpErrorString .= $countryName;
                    }
                    
                    $row->IpErrorString = email_Incomings::addErrToEmailStr($row->IpErrorString, '', 'error');
                }
            }
        }
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $subject = $this->getVerbal($rec, 'subject');
        
        $row = new stdClass();
        $row->title = $subject;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->authorId = $rec->createdBy;
        $row->state = $rec->state;
        $row->recTitle = $rec->subject;
        
        return $row;
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        // Инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('Postings', 'Прикачени файлове в постингите', NULL, '300 MB', 'user', 'user');
        
        // Данни за работата на Крон
        $rec = new stdClass();
        $rec->systemId = 'processWaitingEmails';
        $rec->description = 'Нотифициране за чакащи имейли';
        $rec->controller = $mvc->className;
        $rec->action = 'processWaitingEmails';
        $rec->period = 60;
        $rec->offset = mt_rand(0,40);
        $rec->delay = 0;
        $rec->timeLimit = 250;
        $res .= core_Cron::addOnce($rec);
    }
    
    
    /**
     * Стартира се по крон
     */
    static function cron_ProcessWaitingEmails()
    {
        // Нотифицира за чакащи имейли
        static::processWaitingEmails();
    }
    
    
    /**
     * Намира изпратени и чакащи имейли, на които им е минал срока.
     * Ако има входящ имейл след последното изпращане на имейла, се затваря
     * Ако няма входящ имейл и срок е минал, изпраща се нотификация до последния изпращач
     */
    static function processWaitingEmails()
    {
        $cnt = 0;
        
        // Класа на входящите документи
        $incomingClassId = core_Classes::getId('email_Incomings');
        
        // Вземаме всички чакащи или събудени имейли
        $query = static::getQuery();
        $query->where("#state = 'waiting'");
        $query->orWhere("#state = 'wakeup'");
        
        while ($rec = $query->fetch()) {
            
            // Дали да се записва
            $flagSave = FALSE;
            
            $nRec = new stdClass();
            $nRec->id = $rec->id;
            $saveFiedsArr = array();
            $saveFiedsArr['id'] = 'id';
            
            // Ако има входящ имейл след последното изпращане в нишката
            if (doc_Containers::haveDocsAfter($rec->threadId, $rec->lastSendedOn, $incomingClassId)) {
                
                // Ако имейла е бил събуден
                if ($rec->state == 'wakeup') {
                    
                    // Премахваме нотификацията
                    $urlArr = array('doc_Search', 'state' => 'wakeup');
                    bgerp_Notifications::clear($urlArr, $rec->lastSendedBy);
                    
                    $urlArr = array(get_called_class(), 'single', $rec->id, 'wakeUp' => TRUE);
                    bgerp_Notifications::clear($urlArr, $rec->lastSendedBy);
                }
                
                // Затваряме
                $nRec->state = 'closed';
                $saveFiedsArr['state'] = 'state';
                $flagSave = TRUE;
            } else {
                
                // Ако вече е бил събуден, не го пипаме
                if ($rec->state == 'wakeup') continue;
                
                // Ако е минало максимално зададеното време
                $now = dt::now();
                $pendingTo = dt::addSecs($rec->waiting, $rec->lastSendedOn);
                
                if (($now > $pendingTo) || !$rec->lastSendedOn) {
                    
                    // "Събуждаме" имейла и добавяме нотификация
                    $nRec->state = 'wakeup';
                    $saveFiedsArr['state'] = 'state';
                    static::addWaitingEmailNotification($rec->lastSendedBy, $rec);
                    $flagSave = TRUE;
                    
                    self::logWrite('Събуждане', $rec->id);
                }
            }
            
            // Ако е вдигнат флага, записваме
            if ($flagSave) {
                if (static::save($nRec, $saveFiedsArr)) {
                    self::touchRec($nRec->id);
                    $cnt++;
                }
            }
        }
        
        return $cnt;
    }
    
    
    /**
     * Добавяме нотификация на съответния потребител за чакащ имейл
     *
     * @param integer|NULL $userId
     * @param stdObject|NULL $rec
     */
    static function addWaitingEmailNotification($userId = NULL, $rec = NULL)
    {
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        if ($userId <= 0) return ;
        
        if ($rec && self::haveRightFor('single', $rec, $userId)) {
            $msg = '|Липсва отговор на|* "' . $rec->subject . '"';
            $urlArr = array(get_called_class(), 'single', $rec->id, 'wakeUp' => TRUE);
        } else {
            $msg = "|Липсва отговор на изпратен имейл";
            $urlArr = array('doc_Search', 'state' => 'wakeup');
        }
        
        // Добавяме нотификацията
        bgerp_Notifications::add($msg, $urlArr, $userId, 'normal');
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща данните за адресата
     */
    static function getContragentData($id)
    {
        $posting = email_Outgoings::fetch($id);
        
        $contrData = new stdClass();
        $contrData->company = $posting->recipient;
        $contrData->person = $posting->attn;
        $contrData->tel = $posting->tel;
        $contrData->fax = $posting->fax;
        $contrData->country = $posting->country;
        $contrData->pCode = $posting->pcode;
        $contrData->place = $posting->place;
        $contrData->address = $posting->address;
        $contrData->email = $posting->email;
        $contrData->emailCc = $posting->emailCc;
        
        // Ако има папка
        if ($posting->folderId) {
            
            // Вземаме корицата на папката
            $cover = doc_Folders::getCover($posting->folderId);
            
            // Ако корицата има съответния интерфейс
            if (cls::haveInterface('doc_ContragentDataIntf', $cover->className)) {
                
                // Вземаме груповите имейли
                $contrData->groupEmails = $cover->getContragentData($rec->docId)->groupEmails;
            }
        }
        
        // Ако има originId
        if ($posting->originId && $posting->forward != 'yes') {
            
            // Вземаме контрагент данните на оригиналния документ (когато клонираме изходящ имейл)
            $originContr = doc_Containers::getContragentData($posting->originId);
            
            // Ако има групови имейли
            if ($originContr->groupEmails) {
                
                // Добавяме ги
                $contrData->groupEmails .= ($contrData->groupEmails) ? "$contrData->groupEmails, $originContr->groupEmails" : $originContr->groupEmails;
            }
        }
        
        // Добавяме към груповите имейли и имейлите до които им е пращано
        if ($posting->containerId) {
                
            $sendedGroupEmails = self::getSentToEmails($posting->containerId);
            
            if ($sendedGroupEmails) {
                $contrData->groupEmails .= ($contrData->groupEmails) ? ", " . $sendedGroupEmails : $sendedGroupEmails;
            }
        }
        
        return $contrData;
    }
    
    
    /**
     * Връща всички имейли до които им е изпратен имейл от съответната нишка или контейнер
     * 
     * @param integer $containerId
     * @param integer $threadId
     * 
     * @return string
     */
    protected static function getSentToEmails($containerId = NULL, $threadId = NULL)
    {
        $sendedTo = '';
        if (!$containerId && !$threadId) return $sendedTo;
        $lRecsArr = doclog_Documents::getRecs($containerId, doclog_Documents::ACTION_SEND, $threadId);
        
        if ($lRecsArr) {
            
            foreach ($lRecsArr as $lRec) {
                if (!$lRec->data->to) continue;
            
            
                $sendedTo .= ($sendedTo) ? ", " . $lRec->data->to : $lRec->data->to;
            }
        }
        
        return $sendedTo;
    }
    
    
    /**
     * Добавя бутон за Изпращане в единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        //Добавяме бутона, ако състоянието не е чернова или отхвърлена, и ако имаме права за изпращане
        if (($data->rec->state != 'draft') && ($data->rec->state != 'rejected')) {
            
            // Подготвяме ret_url' то
            $retUrl = array('email_Outgoings', 'single', $data->rec->id);
            
            // Разделяме имейла на факсове и имейли
            $faxAndEmailsArr = static::explodeEmailsAndFax($data->rec->email);
            
            // Броя на факсовете
            $faxCount = count($faxAndEmailsArr['fax']);
            $emailCount = count($faxAndEmailsArr['email']);
            
            // Ако има факс номер и имаме права за изпращане на факс
            if ((email_FaxSent::haveRightFor('send') && (($faxCount) || ($data->rec->fax && !$emailCount)))) {
                
                // Бутона за изпращане да сочи към екшъна за изпращане на факсове
                $data->toolbar->addBtn('Изпращане', array('email_FaxSent', 'send', $data->rec->id, 'ret_url'=>$retUrl), 'ef_icon = img/16/email_go.png', 'title=Изпращане на имейла');
            } else {
                
                // Ако няма факс номер и имаме права за изпращане на имейл
                if (email_Outgoings::haveRightFor('email')) {
                    
                    // Добавяме бутон за изпращане на имейл
                    $data->toolbar->addBtn('Изпращане', array('email_Outgoings', 'send', $data->rec->id, 'ret_url'=>$retUrl), 'ef_icon = img/16/email_go.png', 'title=Изпращане на имейла');
                }
            }
            
            if ($mvc->haveRightFor('add')) {
                // Добавяме бутон за препращане на имейла
                $data->toolbar->addBtn('Препращане', array(
                        'email_Outgoings',
                        'forward',
                        $data->rec->containerId,
                        'ret_url' => TRUE,
                    ), array('order'=>'19', 'row'=>'2', 'ef_icon'=>'img/16/email_forward.png', 'title'=>'Препращане на имейла')
                );
            }
        }
        
        if ($mvc->haveRightFor('close', $data->rec)) {
            $data->toolbar->addBtn('Затваряне', array($mvc, 'close', $data->rec->id, 'ret_url'=>TRUE), array('ef_icon'=>'img/16/gray-close.png', 'title'=>'Спиране на изпращането'));
        }
    }
    
    
    /**
     * Затваря състоянието на имейла
     */
    function act_Close()
    {
        $id = Request::get('id', 'int');
        
        $rec = $this->fetch($id);
        
        expect($rec);
        
        $this->requireRightFor('close', $rec);
        
        $rec->state = 'closed';
        
        if ($this->save($rec)) {
            $msg = '|Успешно затворен имейл';
            $type = 'notice';
            $this->logWrite('Затваряне на имейла', $id);
        } else {
            $msg = '|Грешка при затваряне на имейла';
            $type = 'warning';
        }
        
        $retUrl = array($this, 'single', $id);
        
        return new Redirect($retUrl, $msg, $type);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако ще се затваря
        if ($action == 'close' && $rec) {
            
            // Ако не чакащо или събудено състояние, да не може да се затваря
            if (($rec->state != 'waiting') && ($rec->state != 'wakeup') && ($rec->state != 'pending')) {
                $requiredRoles = 'no_one';
            } else if (!haveRole('admin, ceo')) {
                
                // Ако няма роля admin или ceo
                // Ако не е изпратен от текущия потребител, да не може да се затваря
                if ($rec->lastSendedBy != $userId) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if (($action == 'activate') && ($rec->state == 'pending')) {
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * Разделя подадения стринг от имейли на масив с факсове и имейли
     *
     * @param string $emails - Стринг от имейли (и факсове)
     *
     * @return array $arr - Масив с имейли и факсове
     * @return arry $arr['fax'] - Масив с всчики факс номера
     * @return arry $arr['email'] - Масив с всчики имейли
     */
    static function explodeEmailsAndFax($emails)
    {
        // Превръщаме всички имейли на масив
        $emailsArr = type_Emails::toArray($emails);
        
        $arr = array();
        
        // Обхождаме масива
        foreach ($emailsArr as $email) {
            
            // Вземаме домейн частта на всеки имейл
            $domain = mb_strtolower(type_Email::domain($email));
            
            // Ако домейн частта показва, че е факс
            if ($domain == 'fax.man') {
                
                // Добавяме в масива с факовсе
                $arr['fax'][$email] = $email;
            } else {
                
                // Добавяме в масива с имейли
                $arr['email'][$email] = $email;
            }
        }
        
        return $arr;
    }
    
    
    /**
     * Намира предполагаемия езика на който трябва да отговорим
     *
     * 1. Ако е отговор, гледаме езика на origin'а
     * 2. В нишката - Първо от обръщенията (ако корицата е папка на контрагент), после от езика на първия документ
     * 3. В папката - Първо от обръщенията (ако корицата е папка на контрагент), после от държавата на визитката
     * 4.1 Ако няма открит -> Текущия език
     * 4.2 Ако има и не "добър", следователно е английски
     *
     * @param int $originId - id' то на контейнера
     * @param int $threadId - id' то на нишката
     * @param int $folderId - id' то на папката
     * @param string $body - текста на писмото
     *
     * @return string $lg - Двубуквеното означение на предполагаемия език на имейла
     */
    static function getLanguage($originId, $threadId, $folderId, $body=NULL)
    {
        // Търсим езика в контейнера
        $lg = doc_Containers::getLanguage($originId);
        
        // Ако не сме открили езика
        if (!$lg) {
            
            // Търсим езика в нишката
            $lg = doc_Threads::getLanguage($threadId);
        }
        
        // Ако не сме открили езика
        if (!$lg) {
            
            // Търсим езика в папката
            $lg = doc_Folders::getLanguage($folderId);
        }
        
        // Ако не сме открили езика
        if (!$lg) {
            
            // Вземаме езика на текущия интерфейс
            $lg = core_Lg::getCurrent();
        } else {
            
            // Ако езика не е един от позволените
            if (!core_Lg::isGoodLg($lg)) {
                
                // Използваме английски
                $lg = 'en';
            }
        }
        
        if ($body) {
            if (in_array($lg, array('bg', 'ru', 'md', 'sr'))) {
                if (strlen($body) == mb_strlen($body)) {
                    $lg = 'en';
                }
            }
        }
        
        return $lg;
    }
    
    
    /**
     * Изчиства всики HTML коментари
     */
    static function clearHtmlComments($html)
    {
        //Шаблон за намиране на html коментари
        //Коментарите са:
        //<!-- Hello -->
        //<!-- Hello -- -- Hello-->
        //<!---->
        //<!------ Hello -->
        //<!>
        $pattern = '/(\<!\>)|(\<![-]{2}[^\>]*[-]{2}\>)/i';
        
        //Премахваме всички коментари
        $html = preg_replace($pattern, '', $html);
        
        return $html;
    }
    
    
    /**
     * Екшън за препращане на имейли
     */
    function act_Forward()
    {
        // id'то на контейнера
        $cid = Request::get('id', 'int');
        
        // Записите на контейнер
        $cRec = doc_Containers::fetch($cid);
        
        // Инстанция на класа
        $class = cls::get($cRec->docClass);
        
        // id на записа
        $id = $cRec->docId;
        
        // Вземаме записа
        $rec = $class::fetch($id);
        
        // Оттеглените имейли, да не може да се препращат
        expect($rec->state != 'rejected', 'Не може да се препраща оттеглен имейл.');
        
        // Проверяваме за права
        $class::requireRightFor('single', $rec);
        
        $data = new stdClass();
        
        // Вземаме формата
        $data->form = static::getForm();
        
        $form = &$data->form;
        
        // Обхождаме всички да не се показват
        foreach($form->fields as &$field) {
            $field->input = 'none';
        }
        
        // Добавяме функционални полета
        $form->FNC('personId', 'key(mvc=crm_Persons, select=name, allowEmpty)', 'input,silent,caption=Папка->Лице,width=100%');
        $form->FNC('companyId', 'key(mvc=crm_Companies, select=name, allowEmpty)', 'input,silent,caption=Папка->Фирма,width=100%');
        
        $form->FNC('userEmail', 'email', 'input=input,silent,caption=Имейл->Адрес,width=100%,recently');
        
        // Заявка за извличане на потребителите
        $personsQuery = crm_Persons::getQuery();
        
        // Да извлече само достъпните
        crm_Persons::applyAccessQuery($personsQuery);
        
        $personsArr = array();
        
        // Обхождаме всички откити резултати
        while ($personsRec = $personsQuery->fetch()) {
            
            // Добавяме в масива
            $personsArr[$personsRec->id] = $personsRec->name;
        }
        
        // Ако има открити стойности
        if (count($personsArr)) {
            
            // Добавяме ги в комбобокса
            $form->setOptions('personId', $personsArr);
        } else {
            
            // Добавяме празен стринг, за да не се покажат всичките записи
            $form->setOptions('personId', array('' => ''));
        }
        
        // Заявка за извличане на фирмите
        $companyQuery = crm_Companies::getQuery();
        
        // Да извлече само достъпните
        crm_Companies::applyAccessQuery($companyQuery);
        
        // Обхождаме всички откити резултати
        while ($companiesRec = $companyQuery->fetch()) {
            
            // Добавяме в масива
            $companiesArr[$companiesRec->id] = $companiesRec->name;
        }
        
        // Ако има открити стойности
        if (count($companiesArr)) {
            
            // Добавяме ги в комбобокса
            $form->setOptions('companyId', $companiesArr);
        } else {
            
            // Добавяме празен стринг, за да не се покажат всичките записи
            $form->setOptions('companyId', array('' => ''));
        }
        
        $form->input();
        
        // Проверка за грешки
        if($form->isSubmitted()) {
            // Намира броя на избраните
            $count = (int)isset($form->rec->personId) + (int)isset($form->rec->companyId) + (int)isset($form->rec->userEmail);
            
            if($count != 1) {
                $form->setError('#', 'Трябва да изберете само една от трите възможности');
            }
        }
        
        // URL' то където ще се редиректва
        $retUrl = getRetUrl();
        
        // Ако няма ret_url, създаваме го
        $retUrl = ($retUrl) ? $retUrl : toUrl(array($class, 'single', $id));
        
        // Ако формата е субмитната
        if ($form->isSubmitted()) {
            
            // Ако сме избрали потребител
            if (isset($form->rec->personId)) {
                
                // Инстанция на класа
                $Persons = cls::get('crm_Persons');
                
                // Папката
                $folderId = $Persons->forceCoverAndFolder($form->rec->personId);
            }
            
            // Ако сме избрали фирмата
            if (isset($form->rec->companyId)) {
                
                // Инстанция на класа
                $Companies = cls::get('crm_Companies');
                
                // Папката
                $folderId = $Companies->forceCoverAndFolder($form->rec->companyId);
            }
            
            // Ако сме въвели имейл
            if (isset($form->rec->userEmail)) {
                
                // Вземаме папката на имейла
                $folderId = static::getForwardEmailFolder($form->rec->userEmail);
            }
            
            // Ако не сме открили папка или нямаме права в нея
            if (!$folderId || !doc_Folders::haveRightFor('single', $folderId)) {
                
                // Изтриваме папката
                unset($folderId);
            }
            
            // Препращаме към формата за създаване на имейл
            redirect(toUrl(array(
                        'email_Outgoings',
                        'add',
                        'originId' => $rec->containerId,
                        'folderId' => $folderId,
                        'emailto' => $form->rec->userEmail,
                        'forward' => 'forward',
                        'ret_url' => $retUrl,
                    )));
        }
        
        // Подготвяме лентата с инструменти на формата
        $form->toolbar->addSbBtn('Избор', 'default', NULL, array('ef_icon'=>'img/16/disk.png', 'title'=>'Създаване на имейл'));
        $form->toolbar->addBtn('Отказ', $retUrl, NULL, array('ef_icon'=>'img/16/close-red.png', 'title'=>'Спиране на създаване на имейл'));
        
        // Потготвяме заглавието на формата
        $form->title = 'Препращане на имейл';
        
        // Получаваме изгледа на формата
        $tpl = $form->renderHtml();
        
        // Опаковаме изгледа
        $tpl = static::renderWrapping($tpl, $data);
        
        return $tpl;
    }
    
    
    /**
     * Функция, която прихваща след активирането на документа
     */
    public static function on_BeforeActivation($mvc, &$rec)
    {
        $rec->__activation = TRUE;
    }
    
    
    /**
     * Връща имейла, до който имаме достъп
     *
     * Начин за определяна не папката:
     * 1. Ако е на фирма
     * 2. Ако е бизнес имейл на лице свързано с фирма
     * 3. Ако е на лице
     * 4. Къде би се рутирал имейла (само папка на контрагент)
     * 5. Ако има корпоративен акаунт:
     * 5.1 Кутия на потребителя
     * 5.2 Кутия на която е inCharge от съответния корпоративен акаунт
     * 6. Последната кутия на която сме inCharge
     *
     * @param email $email - Имейл
     *
     * @return doc_Folders $folderId - id на папка
     */
    static function getAccessedEmailFolder($email)
    {
        // Имейла в долния регистър
        $email = mb_strtolower($email);
        
        // Папката на фирмата
        $folderId = crm_Companies::getFolderFromEmail($email);
        
        // Ако има папка връщаме
        if ($folderId) return $folderId;
        
        // Папката от бизнес имейла на фирмата
        $folderId = crm_Persons::getFolderFromBuzEmail($email);
        
        // Ако има папка връщаме
        if ($folderId) return $folderId;
        
        // Личната папка
        $folderId = crm_Persons::getFolderFromEmail($email);
        
        // Ако има папка връщаме
        if ($folderId) return $folderId;
        
        // Вземаме предполагаемата папка
        $folderId = email_Router::getEmailFolder($email);
        
        // Ако може да се определи папка
        if ($folderId && doc_Folders::haveRightFor('single', $folderId)) {
            
            // Вземаем името на cover
            $coverClassName = strtolower(doc_Folders::fetchCoverClassName($folderId));
            
            // Ако корицата е на контрагент
            if (($coverClassName == 'crm_persons') || ($coverClassName == 'crm_companies')) {
                
                return $folderId;
            }
        }
        
        // Вземаме корпоративната сметка
        $corpAccRec = email_Accounts::getCorporateAcc();
        
        $currUserId = core_Users::getCurrent();
        
        // Ако имаме корпоративен акаунт
        if ($corpAccId = $corpAccRec->id) {
            
            // Корпоративния имейла на потребиеля
            $currUserCorpEmail = mb_strtolower(email_Inboxes::getUserEmail());
            
            // Вземаме папката
            $folderId = email_Inboxes::fetchField(array("LOWER(#email) = '[#1#]' AND #state = 'active' AND #accountId = '{$corpAccId}'", $currUserCorpEmail), 'folderId');
            
            // Ако има папка и имаме права в нея
            if ($folderId && email_Inboxes::haveRightFor('single', $folderId)) {
                
                return $folderId;
            }
            
            // Ако нямаме корпоративен имейл
            // Вземаме последния имейл на който сме inCharge
            $queryCorp = email_Inboxes::getQuery();
            $queryCorp->where("#inCharge = '{$currUserId}' AND #accountId = '{$corpAccId}' AND #state = 'active'");
            $queryCorp->orderBy('createdOn', 'DESC');
            $queryCorp->limit(1);
            $emailCorpAcc = $queryCorp->fetch();
            $folderId = $emailCorpAcc->folderId;
            
            // Ако има папка и имаме права
            if ($folderId && email_Inboxes::haveRightFor('single', $folderId)) {
                
                return $folderId;
            }
        }
        
        // Ако няма корпоративна сметка
        // Вземаме последния имейл на който имаме права за inCharge
        $queryEmail = email_Inboxes::getQuery();
        $queryEmail->where("#inCharge = '{$currUserId}' AND #state = 'active'");
        $queryEmail->orderBy('createdOn', 'DESC');
        $queryEmail->limit(1);
        $emailAcc = $queryEmail->fetch();
        $folderId = $emailAcc->folderId;
        
        // Ако има папка и имаме права
        if ($folderId && email_Inboxes::haveRightFor('single', $folderId)) {
            
            return $folderId;
        }
        
        // Ако не може да се определи по никакъв начин
        return FALSE;
    }
    
    
    /**
     * Връща папката от имейла при препращане
     *
     * @param email $email - Имейла, към който ще препращаме
     *
     * Начин за определяна не папката:
     * 1. Ако е на фирма
     * 2. Ако е бизнес имейл на лице свързано с фирма
     * 3. Ако е на лице
     *
     * @return doc_Folders $folderId - id на папка
     */
    static function getForwardEmailFolder($email)
    {
        // Имейла в долния регистър
        $email = mb_strtolower($email);
        
        // Папката на фирмата
        $folderId = crm_Companies::getFolderFromEmail($email);
        
        // Ако има папка връщаме
        if ($folderId) return $folderId;
        
        // Папката от бизнес имейла на фирмата
        $folderId = crm_Persons::getFolderFromBuzEmail($email);
        
        // Ако има папка връщаме
        if ($folderId) return $folderId;
        
        // Личната папка
        $folderId = crm_Persons::getFolderFromEmail($email);
        
        // Ако има папка връщаме
        if ($folderId) return $folderId;
        
        // Ако не може да се определи по никакъв начин
        return FALSE;
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
        // Премахваме ненужните полета
        unset($nRec->waiting);
        unset($nRec->lastSendedOn);
        unset($nRec->lastSendedBy);
    }
    
    
    /**
     * Проверява дали може да се променя записа в зависимост от състоянието на документа
     * 
     * @see change_Plugin
     * @param core_Mvc $mvc
     * @param boolean $res
     * @param string $state
     */
    public static function on_AfterCanChangeRec($mvc, &$res, $rec)
    {
        // Затворените имейли също могат да се променят
        // Само черновите имейли няма да могат да се променят, но това се задава в change_Plugin
        if ($res !== FALSE && $rec->state == 'closed') {
            
            $res = TRUE;
        }
    }
	
	
    /**
     * 
     * 
     * @param email_Outgoings $mvc
     * @param mixed $res
     * @param int|object $id първичен ключ или запис на $mvc
     */
    public static function on_AfterReject($mvc, &$res, $id)
    {
        if (is_object($id)) {
            $rId = $id->id;
        } else {
            $rId = $id;
        }
        
        if ($rId) {
            $sQuery = email_SendOnTime::getQuery();
            $sQuery->where(array("#objectId = [#1#] AND #state = 'waiting'", $rId));
            
            while ($rec = $sQuery->fetch()) {
                email_SendOnTime::stopSending($rec);
            }
        }
    }
}
