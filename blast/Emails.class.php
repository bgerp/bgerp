<?php 


/**
 * Имейл-а по подразбиране
 */
defIfNot('BGERP_DEFAULT_EMAIL_FROM');


/**
 * Шаблона, който ще се замества с mid
 */
defIfNot('BGERP_EMAILS_MID', '[#mid#]');


/**
 * Текст за отписване от информационните съобщение
 */
defIfNot('BGERP_BLAST_UNSUBSCRIBE', 'Искате ли да премахнете имейл-а си от листата за получаване на информационни съобщения.');


/**
 * Текст, който се показва, ако не може да се намери имейл адреса в системата
 */
defIfNot('BGERP_BLAST_NO_MAIL', 'Не може да се намери имейл адреса Ви.');


/**
 * Teкст, който се показва когато премахнем имейл-а от блокираните
 */
defIfNot('BGERP_BLAST_SUCCESS_ADD', 'Имейлът Ви е добавен в списъка за информационни съобщения. Искате ли да го премахнете.');


/**
 * Текст, който се показва когато добавим имейл-а в списъка на блокираните имейли
 */
defIfNot('BGERP_BLAST_SUCCESS_REMOVED', 'Имейлът Ви е премахнат от списъка за информационни съобщения. Искате ли да добавите имейл-а си в листата.');


/**
 * Шаблон за писма за масово разпращане
 *
 *
 * @category  all
 * @package   blast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blast_Emails extends core_Master
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Циркулярни имейли";
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Циркулярен имейл";
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/emails.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'INF';
    
    
    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'subject';
    
    
    /**
     * Данните за съобщението, за съответния потребител
     */
    var $currentUserData;
    
    
    /**
     * Шаблона, без да е заместен с данните за потребителя
     */
    var $templateData = NULL;
    
    
    /**
     * Данните за заместване на placeHolder' ите
     */
    var $listData;
    
    
    /**
     * Текстовата част на имейл-а
     */
    var $text = NULL;
    
    
    /**
     * HTML частта на имейл-а
     */
    var $html = NULL;
    
    
    /**
     * имейл-а, към когото се праща шаблона с неговите данни
     */
    var $mail = NULL;
    
    
    /**
     * id на текущия имейл
     */
    var $emailsId = NULL;
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, blast';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, blast';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, blast';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin, blast';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, blast';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой може да праша информационните съобщения?
     */
    var $canBlast = 'admin, blast';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'email_DocumentIntf';
    
    
    /**
     * Плъгините и враперите, които ще се използват
     */
    var $loadList = 'blast_Wrapper, doc_DocumentPlg, plg_RowTools, plg_Printing';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, subject, listId, from, sendPerMinute, startOn, recipient, attn, email, phone, fax, country, pcode, place, address';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'blast_ListSend';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'blast/tpl/SingleLayoutEmails.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('listId', 'key(mvc=blast_Lists, select=title)', 'caption=Лист');
        $this->FLD('from', 'key(mvc=email_Inboxes, select=email)', 'caption=От');
        $this->FLD('subject', 'varchar', 'caption=Относно, width=100%, mandatory');
        $this->FLD('textPart', 'richtext(bucket=Blast)', 'caption=Tекстова част, width=100%, height=200px');
        $this->FLD('htmlPart', 'html', 'caption=HTML част, width=100%, height=200px');
        $this->FLD('sendPerMinute', 'int(min=1, max=10000)', 'caption=Изпращания в минута, input=none, mandatory');
        $this->FLD('startOn', 'datetime', 'caption=Време на започване, input=none');
        
        $this->FLD('recipient', 'varchar', 'caption=Адресант->Фирма');
        $this->FLD('attn', 'varchar', 'caption=Адресант->Лице,oldFieldName=attentionOf');
        $this->FLD('email', 'varchar', 'caption=Адресант->Имейл');
        $this->FLD('phone', 'varchar', 'caption=Адресант->Тел.');
        $this->FLD('fax', 'varchar', 'caption=Адресант->Факс');
        $this->FLD('country', 'varchar', 'caption=Адресант->Държава');
        $this->FLD('pcode', 'varchar', 'caption=Адресант->П. код');
        $this->FLD('place', 'varchar', 'caption=Адресант->Град/с');
        $this->FLD('address', 'varchar', 'caption=Адресант->Адрес');
    }
    
    
    /**
     * Връща стойността от модела в зависимост oт id' то и полето
     * @access private
     */
    function getData($id, $mail, $field)
    {
        if (!$this->currentUserData[$id]) {
            $this->emailsId = $id;
            $this->setData();
        }
        
        if ($mail === FALSE) {
            
            return $this->currentUserData[$id][$field];
        }
        
        if ($this->mail != $mail) {
            $this->mail = $mail;
            $this->setListData();
            
            $this->currentUserData[$id] = $this->templateData[$id];
            
            $this->replace();
        }
        
        return $this->currentUserData[$id][$field];
    }
    
    
    /**
     * Взема данните за имейл-а, ако не са взети
     * @access private
     */
    function setData()
    {
        $id = $this->emailsId;
        $rec = blast_Emails::fetch(array("#id=[#1#]", $id));
        
        $this->currentUserData[$id] = get_object_vars($rec);
        
        $this->currentUserData[$id]['modifiedOn'] = dt::mysql2verbal($rec->modifiedOn, 'd-m-Y');
        
        $this->templateData[$id] = $this->currentUserData[$id];
    }
    
    
    /**
     * Взема данните на потребителя, до когото ще се изпрати имейл-а
     * @access private
     */
    function setListData()
    {
        $id = $this->emailsId;
        
        //Премахваме старите данни, защото вече работим с нов акаунт
        unset($this->listData);
        unset($this->text);
        unset($this->html);
        
        //Вземаме персоналната информация за потребителя
        $recList = blast_ListDetails::fetch(array("#listId=[#1#] AND #key='[#2#]'", $this->currentUserData[$id]['listId'], $this->mail));
        $this->listData = unserialize($recList->data);
        
        $urlBg = array($this, 'Unsubscribe', 'mid' => BGERP_EMAILS_MID, 'lang' => 'bg');
        $urlEn = array($this, 'Unsubscribe', 'mid' => BGERP_EMAILS_MID, 'lang' => 'en');
        
        //Създаваме линковете
        $linkBg = ht::createLink('тук', toUrl($urlBg, 'absolute'), NULL, array('target'=>'_blank'));
        $linkEn = ht::createLink('here', toUrl($urlEn, 'absolute'), NULL, array('target'=>'_blank'));
        
        $this->listData['otpisvane'] = $linkBg;
        $this->listData['unsubscribe'] = $linkEn;
    }
    
    
    /**
     * Замества плейсхолдерите със съответните стойност
     * @access private
     */
    function replace()
    {
        $id = $this->emailsId;
        
        //Заместваме всички плейсхолдери със съответната стойност, ако в изпратеното поле има такива
        //След това ги записваме в масива $this->currentUserData[$id]
        if (count($this->listData)) {
            foreach ($this->listData as $key => $value) {
                foreach ($this->currentUserData[$id] as $udKey => $udValue) {
                    $this->currentUserData[$id][$udKey] = str_ireplace('[#' . $key . '#]', $value, $udValue);
                }
            }
        }
    }
    
    
    /**
     * Взема текстовата част на имейл-а
     */
    function getEmailText($id, $emailTo = NULL, $boxFrom = NULL)
    {
        if (!$this->text) {
            $Rich = cls::get('type_Richtext');
            
            $this->text = $this->getData($id, $emailTo, 'textPart');
            
            //Ако липсва текстовата част, тогава вземаме HTML частта, като такава
            if (!$this->checkTextPart($this->text)) {
                //Ако липсва текстовата част, тогава вземаме html частта за текстова
                $this->getEmailHtml($id, $emailTo, $boxFrom);
                $this->textFromHtml();
            }
            
            //Изчистваме richtext' а, и го преобразуваме в чист текстов вид
            $this->text = $Rich->richtext2text($this->text);
            
            //Създава хедърната част
            $this->text = $this->createHeader('text');
            
            //Заместваме URL кодирания текст, за да може после да се замести плейсхолдера със стойността
            $rep = urlencode(BGERP_EMAILS_MID);
            $repWith = BGERP_EMAILS_MID;
            $this->text = str_ireplace($rep, $repWith, $this->text);
        }
        
        return $this->text;
    }
    
    
    /**
     * Взема HTML частта на имейл-а
     */
    function getEmailHtml($id, $emailTo = NULL, $boxFrom = NULL)
    {
        if (!$this->html) {
            $this->html = $this->getData($id, $emailTo, 'htmlPart');
            
            if (!$this->checkHtmlPart($this->html)) {
                //Ако липсва HTML частта, тогава вземаме текстовата, като HTML
                $this->getEmailText($id, $emailTo, $boxFrom);
                $this->htmlFromText();
            }
            
            //Създава хедърната част
            $this->html = $this->createHeader('html');
            
            //При санитаризиране на html текста, се санитаризира и първия елемент на pleceholdera
            //Заместваме го с оригиналната стойност за да работи коректно и да показва линка
            if (strpos(BGERP_EMAILS_MID, '[') === 0) {
                $rep = substr_replace(BGERP_EMAILS_MID, '&#91;', 0, 1);
                $repWith = BGERP_EMAILS_MID;
                $this->html = str_ireplace($rep, $repWith, $this->html);
            }
            
            //Заместваме URL кодирания текст, за да може после да се замести плейсхолдера със стойността
            $rep = urlencode(BGERP_EMAILS_MID);
            $repWith = BGERP_EMAILS_MID;
            $this->html = str_ireplace($rep, $repWith, $this->html);
        }
        
        return $this->html;
    }
    
    
    /**
     * Добавя антетка към HTML и текстовата част
     */
    function createHeader($type)
    {
        $id = $this->emailsId;
        
        //Очаква данните да са сетнати
        expect($this->currentUserData[$id]);
        
        //Записваме стария Mode, за да можем да го върнем, след края на операцията
        $oldMode = Mode::get('text');
        
        //Проверяваме какъв е подадения тип и спрямо него променяме Mode.
        if ($type == 'text') {
            Mode::set('text', 'plain');
        } else {
            Mode::set('text', 'html');
        }
        
        //Вземаме шаблона за тялото на съобщението
        $tpl = email_Outgoings::getBodyTpl();
        
        //Заместваме всички полета в шаблона с данните за съответния потребител
        
        foreach ($this->currentUserData[$id] as $key => $value) {
            $tpl->replace($value, $key);
        }
        
        $tpl->replace($this->$type, 'body');
        
        //Връщаме стария mode на text
        Mode::set('text', $oldMode);
        
        return $tpl->getContent();
    }
    
    
    /**
     * Проверява за надеждността на HTML частта
     * @access private
     */
    function checkHtmlPart($html)
    {
        if (!str::trim(strip_tags($html))) {
            
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
     * Проверява за надеждността на текстовата част
     * @access private
     */
    function checkTextPart($text)
    {
        if (!str::trim($text)) {
            
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
     * Преобразува текстовата част в HTML
     *
     * @access private
     */
    function htmlFromText()
    {
        $Rich = cls::get('type_Richtext');
        $this->html = $Rich->toHtml($this->text)->content;
    }
    
    
    /**
     * Преобразува HTMl частта в текстова
     *
     * @access private
     */
    function textFromHtml()
    {
        $this->text = strip_tags($this->html);
    }
    
    
    /**
     * Взема прикрепените файлове
     */
    function getEmailAttachments($id)
    {
        //TODO ?
        
        return NULL;
    }
    
    
    /**
     * Връща заглавието по подразбиране без да се заменят placeholder' ите
     */
    function getDefaultSubject($id, $emailTo = NULL, $boxFrom = NULL)
    {
        $subject = $this->getData($id, $emailTo, 'subject');
        
        return $subject;
    }
    
    
    /**
     * До кой имейл или списък ще се изпраща
     */
    function getDefaultEmailTo($id)
    {
        
        return NULL;
    }
    
    
    /**
     * Връща id' то на пощенската кутия от нашата система
     */
    function getDefaultBoxFrom($id)
    {
        //Ако няма въведен изпращач, тогава използваме конфигурационната константа по default
        return BGERP_DEFAULT_EMAIL_FROM;
    }
    
    
    /**
     * msgId на писмото на което в отговор е направен този постинг
     */
    function getInReplayTo($id)
    {
        
        return NULL;
    }
    
    
    /**
     * Извиква се след въвеждането на данните
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()){
            //Проверяваме дали имаме текстова или HTML част. Задължително е да имаме поне едно от двете
            if (!$this->checkTextPart($form->rec->textPart)) {
                if (!$this->checkHtmlPart($form->rec->htmlPart)) {
                    $form->setError('textPart, htmlPart', 'Текстовата част и/или HTML частта трябва да се попълнят.');
                }
            }
        }
    }
    
    
    /**
     * Добавя съответните бутони в лентата с инструменти, в зависимост от състоянието
     */
    function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $id = $data->rec->id;
        $state = $data->rec->state;
        
        if (($state == 'draft') || ($state == 'stopped')) {
            //Добавяме бутона Активирай, ако състоянието е чернова или спряно
            $data->toolbar->addBtn('Активиране', array($mvc, 'Activation', $id), 'class=btn-activation');
        } elseif (($state == 'waiting') || ($state == 'active')) {
            //Добавяме бутона Спри, ако състоянието е активно или изчакване
            $data->toolbar->addBtn('Спиране', array($mvc, 'Stop', $id), 'class=btn-cancel');
        }
    }
    
    
    /**
     * Екшън за активиране, съгласно правилата на фреймуърка
     */
    function act_Activation()
    {
        //Права за работа с екшън-а
        requireRole('blast, admin');
        
        //URL' то където ще се редиректва при отказ
        $retUrl = getRetUrl() ? getRetUrl() : array($this);
        
        // Вземаме формата към този модел
        $form = $this->getForm();
        
        // Въвеждаме id-то (и евентуално други silent параметри, ако има)
        $form->input(NULL, 'silent');
        
        // Очакваме да има такъв запис
        expect($rec = $this->fetch($form->rec->id));
        
        // Очакваме потребителя да има права за активиране
        $this->haveRightFor('activation', $rec);
        
        // Въвеждаме съдържанието на полетата
        $form->input('sendPerMinute, startOn');
        
        // Ако формата е изпратена без грешки, то активираме, ... и редиректваме
        if($form->isSubmitted()) {
            
            //Сменя статуса на чакащ
            $form->rec->state = 'waiting';
            
            //Ако е въведена коректна дата, тогава използва нея
            //Ако не е въведено нищо, тогава използва сегашната дата
            //Ако е въведена грешна дата показва съобщение за грешка
            if (!$form->rec->startOn) {
                $form->rec->startOn = dt::verbal2mysql();
            }
            
            //Копира всички имеили, на които ще се изпраща имейл-а
            $this->copyEmailsForSending($rec);
            
            //Упдейтва състоянието и данните за имейл-а
            blast_Emails::save($form->rec, 'state,startOn,sendPerMinute');
            
            //След успешен запис редиректваме
            $link = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
            
            return new Redirect($link, tr("Успешно активирахте бласт имейл-а"));
        }
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = 'sendPerMinute, startOn';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', $retUrl, array('class' => 'btn-cancel'));
        
        // Добавяме титлата на формата
        $form->title = tr("Стартиране на масово разпращане");
        $subject = $this->getVerbal($rec, 'subject');
        $date = dt::mysql2verbal($rec->createdOn);
        
        // Добавяме във формата информация, за да знаем за кое писмо става дума
        $form->info = tr("|*<b>|Писмо<i style='color:blue'>|*: {$subject} / {$date}</i></b>");
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Екшън за спиране
     */
    function act_Stop()
    {
        //Права за работа с екшън-а
        requireRole('blast, admin');
        
        // Очакваме да има такъв запис
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));
        
        // Очакваме потребителя да има права за спиране
        $this->haveRightFor('stop', $rec);
        
        $link = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
        
        //Променяме статуса на спрян
        $recUpd = new stdClass();
        $recUpd->id = $rec->id;
        $recUpd->state = 'stopped';
        
        blast_Emails::save($recUpd);
        
        return new Redirect($link, tr("Успешно спряхте бласт имейл-а."));
    }
    
    
    /**
     * Записваме всички имейли в модела за изпращане, откъдето по - късно ще ги изпраща
     */
    function copyEmailsForSending($rec)
    {
        //Вземаме всички пощенски кутии, които са блокирани
        $queryBlocked = blast_Blocked::getQuery();
        
        while ($recBlocked = $queryBlocked->fetch()) {
            $listBlocked[$recBlocked->mail] = TRUE;
        }
        
        $queryList = blast_ListDetails::getQuery();
        $queryList->where("#listId = '$rec->listId'");
        
        //Записваме всички имейли в модела за изпращане, откъдето по - късно ще ги вземем за изпращане
        while ($recList = $queryList->fetch()) {
            //Ако имейл-а е в блокирани, тогава не се добавя в системата
            if ($listBlocked[$recList->key]) continue;
            
            $recListSend = new stdClass();
            $recListSend->listDetailId = $recList->id;
            $recListSend->emailId = $rec->id;
            
            blast_ListSend::save($recListSend, NULL, 'IGNORE');
        }
    }
    
    
    /**
     * Добавяне на филтър
     * Сортиране на записите
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        //Добавя филтър за търсене по "Тема" и "Време на започване"
        $data->listFilter->FNC('filter', 'varchar', 'caption=Търсене,input, width=100%, 
                hint=Търсене по "Тема" и "Време на започване"');
        
        $data->listFilter->showFields = 'filter';
        
        $data->listFilter->view = 'horizontal';
        
        //Добавяме бутон "Филтрирай"
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        $filterInput = trim($data->listFilter->input()->filter);
        
        if($filterInput) {
            $data->query->where(array("#startOn LIKE '%[#1#]%' OR #subject LIKE '%[#1#]%'", $filterInput));
        }
        
        // Сортиране на записите по състояние и по времето им на започване
        $data->query->orderBy('state', 'ASC');
        $data->query->orderBy('startOn', 'DESC');
    }
    
    
    /**
     * Получава управлението от cron' а и проверява дали има съобщения за изпращане
     */
    function checkForSending()
    {
        $query = blast_Emails::getQuery();
        $now = (dt::verbal2mysql());
        $query->where("#startOn <= '$now'");
        $query->where("#state != 'closed' AND #state != 'stopped' AND #state != 'draft'");
        
        //Проверяваме дали имаме запис, който не е затворен и му е дошло времето за активиране
        while ($rec = $query->fetch()) {
            switch ($rec->state) {
                
                //Ако е на изчакване, тогава стартираме процеса
                case 'waiting' :
                    //променяме статуса на имейл-а на активен
                    $recNew = new stdClass();
                    $recNew->id = $rec->id;
                    $recNew->state = 'active';
                    blast_Emails::save($recNew);
                    
                    //Стартираме процеса на изпращане
                    $this->sending($rec);
                    
                    break;
                    
                    //Ако процеса е активен, тогава продължава с изпращането на имейли до следващите получатели
                case 'active' :
                    $this->sending($rec);
                    break;
                    
                    //За всички останали
                default :
                //Да не прави нищо
                break;
            }
        }
    }
    
    
    /**
     * Обработва данните и извиква функцията за изпращане на имейлите
     */
    function sending($rec)
    {
        //Записваме в лога
        blast_Emails::log("Изпращане на бласт имейли с id {$rec->id}.");
        
        $containerId = $rec->containerId;
        $fromEmail = $rec->from;
        
        //Вземаме ($rec->sendPerMinute) имейли, на които не са пратени имейли
        $query = blast_ListSend::getQuery();
        $query->where("#emailId = '$rec->id'");
        $query->where("#sended IS NULL");
        $query->limit($rec->sendPerMinute);
        
        //Ако няма повече пощенски кутии, на които не са пратени имейли сменяме статуса на затворен
        if (!$query->count()) {
            $recNew = new stdClass();
            $recNew->id = $rec->id;
            $recNew->state = 'closed';
            blast_Emails::save($recNew);
            
            return ;
        }
        
        //обновяваме времето на изпращане на всички имейли, които сме взели.
        while ($recListSend = $query->fetch()) {
            $listMail[] = blast_ListDetails::fetchField($recListSend->listDetailId, 'key');
            $recListSendNew = new stdClass();
            $recListSendNew->id = $recListSend->id;
            $recListSendNew->sended = dt::verbal2mysql();
            blast_ListSend::save($recListSendNew);
        }
        
        if (count($listMail)) {
            foreach ($listMail as $toEmail) {
                //Извикваме функцията, която ще изпраща имейлите
                
                $options = array(
                    'no_thread_hnd' => 'no_thread_hnd',
                    'attach' => 'attach'
                );
                
                //Извикваме метода за изпращане на имейли
                $Sent = cls::get('email_Sent');
                $Sent->send($containerId, $toEmail, NULL, $fromEmail, $options);
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготвяне на формата за редактиране
     */
    function on_AfterPrepareEditForm(&$mvc, &$res, &$data)
    {
        //Добавя в лист само списъци на с имейли
        $query = blast_Lists::getQuery();
        $query->where("#keyField = 'email'");
        
        while ($rec = $query->fetch()) {
            $files[$rec->id] = $rec->title;
        }
        
        //Ако няма нито един запис, тогава редиректва към страницата за добавяне на списъци.
        if (!$files) {
            
            return new Redirect(array('blast_Lists', 'add'), tr("Нямате добавен списък за имейли. Моля добавете."));
        }
        
        $form = $data->form;
        
        if (!$form->rec->id) {
            //Слага state = draft по default при нов запис
            $form->setDefault('state', 'draft');
            
            //Ако добавяме нов показваме всички списъци
            $form->setOptions('listId', $files, $form->rec->id);
        } else {
            //Ако редактираме, показваме списъка, който го редактираме
            $file[$form->rec->listId] = $files[$form->rec->listId];
            $form->setOptions('listId', $file, $form->rec->id);
        }
        
        //Ако създаваме нов, тогава попълва данните за адресанта по - подразбиране
        $rec = $data->form->rec;
        
        if (!$rec->id) {
            $rec->recipient = '[#company#]';
            $rec->attn = '[#person#]';
            $rec->email = '[#email#]';
            $rec->phone = '[#tel#]';
            $rec->fax = '[#fax#]';
            $rec->country = '[#country#]';
            $rec->pcode = '[#postCode#]';
            $rec->place = '[#city#]';
            $rec->address = '[#address#]';
        }
    }
    
    
    /**
     * Функция, която се изпълнява от крона и стартира процеса на изпращане на blast
     */
    function cron_SendEmails()
    {
        $this->checkForSending();
        
        return 'Изпращането приключи';
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
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
        
        return $row;
    }
    
    
    /**
     * Добавяне или премахване на имейл в блокираните
     */
    function act_Unsubscribe()
    {
        //GET променливите от линка
        $mid = Request::get("mid");
        $lang = Request::get("lang");
        $uns = Request::get("uns");
        
        //Сменяме езика за да може да  се преведат съобщенията
        core_Lg::set($lang);
        
        //Шаблон
        $tpl = new ET("<div class='unsubscribe'> [#text#] </div>");
        
        //Проверяваме дали има такъв имейл
        if (!($rec->mail = email_Sent::fetchField("#mid='$mid'", 'emailTo'))) {
            
            //Съобщение за грешка, ако няма такъв имейл
            $tpl->append("<p>" . tr(BGERP_BLAST_NO_MAIL) . "</p>", 'text');
            
            return $tpl;
        }
        
        //Ако имейл-а е в листата на блокираните имейли или сме натиснали бутона за премахване от листата
        if (($uns == 'del') || ((!$uns) && (blast_Blocked::fetch("#mail='$rec->mail'")))) {
            
            //Какво действие ще правим след натискане на бутона
            $act = 'add';
            
            //Какъв да е текста на бутона
            $click = 'Добави';
            
            //Премахва имейл-а от листата на блокираните
            if ($uns) {
                blast_Blocked::save($rec, NULL, 'IGNORE');
            }
            
            $tpl->append("<p>" . tr(BGERP_BLAST_SUCCESS_REMOVED) . "</p>", 'text');
        } elseif ($uns == 'add') {
            $act = 'del';
            $click = 'Премахване';
            
            //Премахваме имейл-а от листата на блокираните имейли
            blast_Blocked::delete("#mail='$rec->mail'");
            $tpl->append("<p>" . tr(BGERP_BLAST_SUCCESS_ADD) . "</p>", 'text');
        } else {
            $act = 'del';
            $click = 'Премахване';
            
            //Текста, който ще се показва при първото ни натискане на линка
            $tpl->append("<p>" . tr(BGERP_BLAST_UNSUBSCRIBE) . "</p>", 'text');
        }
        
        //Генерираме бутон за отписване или вписване
        $link = ht::createBtn(tr($click), array($this, 'Unsubscribe', 'mid' => $mid, 'lang' => $lang, 'uns' => $act));
        
        $tpl->append($link, 'text');
        
        return $tpl;
    }
    
    
    /**
     * След рендиране на singleLayout заместваме плейсхолдера
     * с шаблонa за тялото на съобщение в документната система
     */
    function on_AfterRenderSingleLayout($mvc, $tpl)
    {
        //Ако мода е текст, тогава извикваме друг шаблон
        if (Mode::is('text', 'plain')) {
            $tpl = new ET(tr(getFileContent('blast/tpl/SingleLayoutEmails.txt')));
        }
        
        $tpl->replace(email_Outgoings::getBodyTpl(), 'DOC_BODY');
    }
    
    
    /**
     * Добавяме референтния номер на имейл-а
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->handle = $mvc->getHandle($rec->id);
    }
    
    
    /**
     * След подготвяне на single изглед
     */
    function on_AfterPrepareSingle($mvc, &$data)
    {
        //Създаваме и заместваме полето body от текстовата и HTML частта
        $data->row->body = new ET();
        $data->row->body->append($data->row->textPart . "\n\n" . $data->row->htmlPart);
        
        //Създаваме и заместваме полето body от текстовата и HTML частта
        $data->row->attentionOf = new ET();
        $data->row->attentionOf->append($data->row->attn);
        
        if (Mode::is('text', 'plain')) {
            // Форматиране на данните в $data->row за показване в plain text режим
            $width = 80;
            $leftLabelWidth = 19;
            $rightLabelWidth = 11;
            $columnWidth = $width / 2;
            
            $row = $data->row;
            
            // Лява колона на антетката
            foreach (array('modifiedOn', 'subject', 'recipient', 'attentionOf', 'refNo') as $f) {
                $row->{$f} = strip_tags($row->{$f});
                $row->{$f} = type_Text::formatTextBlock($row->{$f}, $columnWidth - $leftLabelWidth, $leftLabelWidth);
            }
            
            // Дясна колона на антетката
            foreach (array('email', 'phone', 'fax', 'address') as $f) {
                $row->{$f} = strip_tags($row->{$f});
                $row->{$f} = type_Text::formatTextBlock($row->{$f}, $columnWidth - $rightLabelWidth, $columnWidth + $rightLabelWidth);
            }
            
            $row->body = type_Text::formatTextBlock($row->body, $width, 0);
            $row->hr = str_repeat('-', $width);
        }
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    function on_AfterSetupMVC($mvc, $res)
    {
        $res .= "<p><i>Нагласяне на Cron</i></p>";
        
        //Данни за работата на cron
        $rec->systemId = 'SendEmails';
        $rec->description = 'Изпращане на много имейли';
        $rec->controller = $mvc->className;
        $rec->action = 'SendEmails';
        $rec->period = 10;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 500;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да изпраща много имейли.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да изпраща имейли.</li>";
        }
        
        //Създаваме, кофа, където ще държим всички прикачени файлове на blast имейлите
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('Blast', 'Прикачени файлове в масовите имейли', NULL, '104857600', 'user', 'user');
    }
}