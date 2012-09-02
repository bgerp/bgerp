<?php 


/**
 * Шаблон за писма за масово разпращане
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class blast_Emails extends core_Master
{
    
    /**
     * Име на папката по подразбиране при създаване на нови документи от този тип.
     * Ако стойноста е 'FALSE', нови документи от този тип се създават в основната папка на потребителя
     */
    var $defaultFolder = 'Циркулярни имейли';
	
    
    /**
     * Полета, които ще се клонират
     */
    var $cloneFields = 'listId, from, subject, body, recipient, attn, email, tel, fax, country, pcode, place, address, attachments, encoding';
    
    
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
    var $abbr = 'Inf';
    
    
    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'subject';
    
   
    /**
     * Данните на потребилтеля, които ще се заместват
     */
    var $emailData = NULL;
    
    
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
    var $loadList = 'blast_Wrapper, doc_DocumentPlg, plg_RowTools, plg_Printing, bgerp_plg_blank';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, subject, listId, from, sendPerMinute, startOn, recipient, attn, email, tel, fax, country, pcode, place, address';
    
    
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
        $this->FLD('body', 'richtext(rows=15,bucket=Blast)', 'caption=Съобщение,mandatory');
        $this->FLD('sendPerMinute', 'int(min=1, max=10000)', 'caption=Изпращания в минута, input=none, mandatory');
        $this->FLD('startOn', 'datetime', 'caption=Време на започване, input=none');
        
        $this->FLD('activatedBy', 'key(mvc=core_Users)', 'caption=Активирано от, input=none');
        
        //Данни на адресанта - антетка
        $this->FLD('recipient', 'varchar', 'caption=Адресант->Фирма,class=contactData');
        $this->FLD('attn', 'varchar', 'caption=Адресант->Лице,oldFieldName=attentionOf,class=contactData');
        $this->FLD('email', 'varchar', 'caption=Адресант->Имейл,class=contactData');
        $this->FLD('tel', 'varchar', 'caption=Адресант->Тел.,class=contactData');
        $this->FLD('fax', 'varchar', 'caption=Адресант->Факс,class=contactData');
        $this->FLD('country', 'varchar', 'caption=Адресант->Държава,class=contactData');
        $this->FLD('pcode', 'varchar', 'caption=Адресант->П. код,class=contactData');
        $this->FLD('place', 'varchar', 'caption=Адресант->Град/с,class=contactData');
        $this->FLD('address', 'varchar', 'caption=Адресант->Адрес,class=contactData');
        
        $this->FLD('encoding', 'enum(utf-8=Уникод|* (UTF-8),
                                    cp1251=Windows Cyrillic|* (CP1251),
                                    koi8-r=Rus Cyrillic|* (KOI8-R),
                                    cp2152=Western|* (CP1252),
                                    ascii=Латиница|* (ASCII))', 'caption=Знаци');
        
        $this->FLD('attachments', 'set(files=Файловете,documents=Документите)', 'caption=Прикачи');
    }

    
 	/**
     * Добавяне на филтър
     * Сортиране на записите
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
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
     * Добавяме референтния номер на имейл-а
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->handle = $mvc->getHandle($rec->id);
    }
    
    
    /**
     * Добавя съответните бутони в лентата с инструменти, в зависимост от състоянието
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        //Превеждаме енкодинга
        $data->row->encoding = tr($data->row->encoding);
        
        //При рендиране на листовия изглед показваме дали ще се прикачат файловете и/или документите
        $attachArr = type_Set::toArray($data->rec->attachments);
        if ($attachArr['files']) $data->row->Files = tr('Файловете');
        if ($attachArr['documents']) $data->row->Documents = tr('Документите');
   
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
     * Изпълнява се след подготвяне на формата за редактиране
     */
    static function on_AfterPrepareEditForm(&$mvc, &$res, &$data)
    {
        //Добавя в лист само списъци с имейли
        $query = blast_Lists::getQuery();
        $query->where("#keyField = 'email'");
        
        while ($rec = $query->fetch()) {
            $files[$rec->id] = blast_Lists::getVerbal($rec, 'title');
        }
        
        //Ако няма нито един запис, тогава редиректва към страницата за добавяне на списъци.
        if (!$files) {
            
            return new Redirect(array('blast_Lists', 'add'), tr("Нямате добавен списък за имейли. Моля добавете."));
        }
        
        $form = $data->form;

        $form->fields['from']->type->params['folderId'] = $form->rec->folderId;

        if (!$form->rec->id) {
            
            //Ако не създаваме копие
            if (!Request::get('Clone')) {
                
                //Чекбоксовете, да са избрани по подразбиране
                $data->form->setDefault('attachments','files,documents');  
                
                //По подразбиране да е избран текущия имейл на потребителя
                $form->setDefault('from', email_Inboxes::getUserInboxId());  
            }
            
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
        if ((!$rec->id) && (!Request::get('Clone'))) {
            $rec->recipient = '[#company#]';
            $rec->attn = '[#person#]';
            $rec->email = '[#email#]';
            $rec->tel = '[#tel#]';
            $rec->fax = '[#fax#]';
            $rec->country = '[#country#]';
            $rec->pcode = '[#postCode#]';
            $rec->place = '[#city#]';
            $rec->address = '[#address#]';
        }
    }
    
    
	/**
	* Изпълнява се след въвеждането на даните от формата
	* Проверява дали сме въвели несъществуващ шаблон
	*/
    function on_AfterInputEditForm($mvc, &$form)
    {
        //Ако сме субмитнали формата
        if ($form->isSubmitted()) {
            
            //Масив с всички записи
            $rec = (array)$form->rec;
            
            //id' то на листа, от който се вземат данните на потребителя
            $listId = $form->rec->listId;
            
            foreach ($rec as $field) {
                
                //Всички данни ги записваме в една променлива
                $allRecsWithPlaceHolders .= ' ' . $field;    
            }
            
            //Създаваме шаблон
            $tpl = new ET($allRecsWithPlaceHolders);
            
            //Вземаме всички шаблони, които се използват
            $allPlaceHolder = $tpl->getPlaceHolders();
            
            //Вземаме всички полета, които ще се заместват
            $listsRecAllFields = blast_Lists::fetchField($listId, 'allFields');
            
            $allFieldsArr = array();
            
            //Вземаме всички имена на полетата на данните, които ще се заместват
            preg_match_all('/(\s|^)([^=]+)/', $listsRecAllFields, $allFieldsArr);
            
            //Добавяме полетата, които се добавят от системата
            $allFieldsArr[2][] = 'unsubscribe';
            $allFieldsArr[2][] = 'mid';
            $allFieldsArr[2][] = 'otpisvane';
            
            //Създаваме масив с ключ и стойност имената на полетата, които ще се заместват
            foreach ($allFieldsArr[2] as $field) {
                $fieldsArr[$field] = $field;
            }
            
            //Търсим всички полета, които сме въвели, но ги няма в полетата за заместване
            foreach ($allPlaceHolder as $placeHolder) {
                if (!$fieldsArr[$placeHolder]) {
                    $error .= ($error) ? ", {$placeHolder}" : $placeHolder;
                }
            }
            
            //Показваме грешка, ако има шаблони, които сме въвели в повече
            if ($error) {
                $form->setError('*', "|Шаблоните, които сте въвели ги няма в БД|*: {$error}");    
            }
        }
    }
    
    
	/**
     * След рендиране на singleLayout заместваме плейсхолдера
     * с шаблонa за тялото на съобщение в документната система
     */
    function renderSingleLayout_(&$data)
    {
        //Рендираме шаблона
        if (Mode::is('text', 'xhtml')) {
            
            //Ако сме в xhtml (изпращане) режим, рендираме шаблона за изпращане
            $tpl = new ET(tr('|*' . getFileContent('blast/tpl/SingleLayoutBlast.shtml')));
        } elseif (Mode::is('text', 'plain')) {
            
            //Ако сме в текстов режим, рендираме txt
            $tpl = new ET(tr('|*' . getFileContent('blast/tpl/SingleLayoutBlast.txt')));
        } else {
            
            //Ако не сме в нито един от посочените рендираме html
            $tpl = new ET(tr('|*' . getFileContent('blast/tpl/SingleLayoutEmails.shtml'))); 
        }
        
        return $tpl;
    }
    

	/**
     * Екшън за активиране, съгласно правилата на фреймуърка
     */
    function act_Activation()
    {
        //Права за работа с екшън-а
        requireRole('blast, admin');
        
        $id = Request::get('id', 'int');
        
        $retUrl = getRetUrl();
        
        //URL' то където ще се редиректва при отказ
        $retUrl = ($retUrl) ? ($retUrl) : (array($this, 'single', $id));

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
            
            //Кой активира имейла
            $form->rec->activatedBy = core_Users::getCurrent();
                        
            //Ако е въведена коректна дата, тогава използва нея
            //Ако не е въведено нищо, тогава използва сегашната дата
            //Ако е въведена грешна дата показва съобщение за грешка
            if (!$form->rec->startOn) {
                $form->rec->startOn = dt::verbal2mysql();
            }
            
            //Копира всички имеили, на които ще се изпраща имейл-а
            $this->copyEmailsForSending($rec);
            
            //Упдейтва състоянието и данните за имейл-а
            blast_Emails::save($form->rec, 'state,startOn,sendPerMinute,activatedBy');
            
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
        $form->info = new ET ('[#1#]', tr("|*<b>|Писмо<i style='color:blue'>|*: {$subject} / {$date}</i></b>"));

        //Данните на имейла
        $emailRec = blast_Emails::fetch($form->rec->id);

        $query = blast_ListDetails::getQuery();
        $query->where("#listId={$emailRec->listId}");

        //Обхождаме всички данни докато намерим запис, до който имаме достъп 
        while ($listRec = $query->fetch()) {
            
            //Ако имаме права тогава спираме обхождането
            if (blast_ListDetails::haveRightFor('single', $listRec)) break;
        }
        
        //Имейла на първия потребител, до когото имаме достъп
        $email = $listRec->key;
        
        //Ако няма имейл, тогава не рендираме примерния имейл
        if (!$email) {
            
            return $this->renderWrapping($form->renderHtml());
        }
        
        
        //Намираме преполагаемия език на съобщението
        Mode::push('lg', $this->getLanguage($emailRec->body));
        
        // Подготвяме записа с данните на съответния имейл
        $this->prepareRec($emailRec, $email);
                
        // Тялото на съобщението
        $body = $this->getEmailBody($emailRec, $email);
        
        // Връщаме езика по подразбиране
        Mode::pop('lg');
        
        // Получаваме изгледа на формата
        $tpl = $form->renderHtml();

        // Добавяме превю на първия бласт имейл, който ще изпратим
        $preview = new ET("<div style='display:table'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Примерен имейл") . "</b></div>[#BLAST_HTML#]<pre class=\"document\">[#BLAST_TEXT#]</pre></div>");

        //Конвертираме към въведения енкодинг
        if ($emailRec->encoding == 'ascii') {
            $body->html = str::utf2ascii($body->html);
            $body->text = str::utf2ascii($body->text);
        } elseif (!empty($emailRec->encoding) && $emailRec->encoding != 'utf-8') {
            $body->html = iconv('UTF-8', $emailRec->encoding . '//IGNORE', $body->html);
            $body->text = iconv('UTF-8', $emailRec->encoding . '//IGNORE', $body->text);
        }
        
        // Добавяме към шаблона
        $preview->append($body->html, 'BLAST_HTML');
        $preview->append($body->text, 'BLAST_TEXT');

        // Добавяме изгледа към главния шаблон
        $tpl->append($preview);

        return static::renderWrapping($tpl);
        
    }
    
    
    /**
     * Екшън за спиране
     */
    function act_Stop()
    {
        //Права за работа с екшън-а
        requireRole('blast, admin');
        
        //Очакваме да има такъв запис
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));
        
        //Очакваме потребителя да има права за спиране
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
     * Добавяне или премахване на имейл в блокираните
     * 
     * @todo В момента не работи коректно. Гледа за mid в email_Sent
     */
    function act_Unsubscribe()
    {
    	$conf = core_Packs::getConfig('blast');
    	
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
            $tpl->append("<p>" . tr($conf->BGERP_BLAST_NO_MAIL) . "</p>", 'text');
            
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
            
            $tpl->append("<p>" . tr($conf->BGERP_BLAST_SUCCESS_REMOVED) . "</p>", 'text');
        } elseif ($uns == 'add') {
            $act = 'del';
            $click = 'Премахване';
            
            //Премахваме имейл-а от листата на блокираните имейли
            blast_Blocked::delete("#mail='$rec->mail'");
            $tpl->append("<p>" . tr($conf->BGERP_BLAST_SUCCESS_ADD) . "</p>", 'text');
        } else {
            $act = 'del';
            $click = 'Премахване';
            
            //Текста, който ще се показва при първото ни натискане на линка
            $tpl->append("<p>" . tr($conf->BGERP_BLAST_UNSUBSCRIBE) . "</p>", 'text');
        }
        
        //Генерираме бутон за отписване или вписване
        $link = ht::createBtn(tr($click), array($this, 'Unsubscribe', 'mid' => $mid, 'lang' => $lang, 'uns' => $act));
        
        $tpl->append($link, 'text');
        
        return $tpl;
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
        
        // Задаваме достатъчно време, за да се обработи списъка
        set_time_limit($queryList->count()/10);

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
     * Обработва данните и извиква функцията за изпращане на имейлите
     * 
     * @access private
     */
    function sending($rec)
    {
        $id = $rec->id;
        $containerId = $rec->containerId;
        $threadId = $rec->threadId;
        $boxFrom = $rec->from;
        
        $fromEmail = $rec->from;
        
        //Записваме в лога
        blast_Emails::log("Изпращане на бласт имейли с id {$id}.");
        
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
        
        //Изпращаме персонален имейл до всички намерени адреси адреси
        if (count($listMail)) {
            
            //Спираме системния потребител
            core_Users::cancelSystemUser();
                
            //Променяме потребителя за ипзращане, от системен в потребителя, който е активирал имейла
            $activator = core_Users::fetch($rec->activatedBy);
            Mode::push('currentUserRec', $activator);
            
            foreach ($listMail as $emailTo) {

                //Клонираме записа
                $nRec = clone $rec;    
                
                //Намираме преполагаемия език на съобщението
                Mode::push('lg', $this->getLanguage($nRec->body));
                
                //Подготвяме nRec
                $this->prepareRec($nRec, $emailTo);
                
                //Тялото на съобщението
                $body = $this->getEmailBody($nRec, $emailTo, TRUE);
                
                //Връщаме езика по подразбиране
                Mode::pop('lg');
                                
                //Енкодинга
                $options['encoding'] = $nRec->encoding;

                //Темата на имейла
                $subject = $nRec->subject;
                
                //Извикваме функцията за изпращане на имейли
                email_Sent::send($containerId, $threadId, $boxFrom, $emailTo, $subject, $body, $options);
            }
            
            //Стартираме системния потребител
            core_Users::forceSystemUser();
        }
    }
    
    
	/**
     * Подготвяме данните в rec'а
     * 
     * @param object $rec     - Обект с данните
     * @param email  $emailTo - Имейл
     */
    function prepareRec(&$rec, $emailTo)
    {
        //Заглавието на темата
        $subject = $this->getEmailSubject($rec, $emailTo);
        
        //Записваме заглавието, за да може да се използва при оформяне на имейла
        $rec->subject = $subject;
        
        //Заместваме шаблоните в антетката с техните стойности
        $this->replaceHeaderData($rec, $emailTo);
    }
    
    
	/**
     * Връща тялото на съобщението
     * 
     * @param object $rec - Данни за имейла
     * @param email  $emailTo - Имейла на текущия потребител
     * @param bool   $sending - Дали ще изпращаме имейла
     * 
     * @return object $body - Обект с тялото на съобщението
     * 		   string $body->html - HTMl частта
     * 		   string $body->text - Текстовата част
     *         array  $body->attachments - Прикачените файлове
     */
    function getEmailBody($rec, $emailTo, $sending=NULL)
    {
        $body = new stdClass();
                
        //Вземаме HTML частта
        $body->html = $this->getEmailHtml($rec, $emailTo, $sending);
        
        //Вземаме текстовата част
        $body->text = $this->getEmailText($rec, $emailTo, $sending);
                
        $documents = array();
        $attachments = array();
        
        //Дали да прикачим файловете
        if (($rec->attachments) && ($sending)) {
            $attachArr = type_Set::toArray($rec->attachments);
        }
        
        //Ако сме избрали да се добавят документите, като прикачени
        if ($attachArr['documents']) {
            
            //Вземаме манупулаторите на документите
            $documents = $this->getDocuments($rec->id, $rec->body);
        }
        
        //Ако сме избрали да се добавят файловете, като прикачени
        if ($attachArr['files']) {
            
            //Вземаме манупулаторите на файловете
            $attachments = $this->getAttachments($rec->body);
        }
        
        //Манипулаторите на файловете в масив
        $body->attachmentsFh = (array)$attachments;
        $body->documentsFh = (array)$documents;
        
        //id' тата на прикачените файлове с техните
        $body->attachments = type_Keylist::fromArray(fileman_Files::getIdFromFh($attachments));
        $body->documents = type_Keylist::fromArray(fileman_Files::getIdFromFh($documents));

        return $body;
    }
    
    
	/**
     * Връща темата на имейла
     * 
     * @param object $rec - Данни за имейла
     * @param email  $emailTo - Имейла на потребителя
     * 
     * @return string $res
     */
    function getEmailSubject($rec, $emailTo) 
    {
        //Заместваме всички шаблони, с техните стойности от БД
        $res = $this->replaceEmailData($rec->subject, $rec->listId, $emailTo);

        return $res;
    }
    
    
	/**
     * Взема HTML частта на имейл-а
     * 
     * @param object $rec     - Данни за имейла
     * @param email  $emailTo - Имейла на потребителя
     * 
     * @return core_ET $res
     */
    function getEmailHtml($rec, $emailTo, $sending=FALSE)
    {
        //Емулираме xhtml режим
        Mode::push('text', 'xhtml');
         
        $data = new stdClass();

        //Вземаме тялото на имейла и заместваме шаблоните
        $data->rec = $rec;
        
        //Подготвяме данните за имейла
        $this->prepareSingle($data);
        
        $data->row->body = $this->replaceEmailData($data->row->body, $rec->listId, $emailTo, !$sending);

        //Рендираме шаблона
        $res = $this->renderSingle($data);
        
        //Ако изпращаме имейла
        if ($sending) {
            //Добавяме CSS, като inline стилове
            $css = getFileContent('css/wideCommon.css') .
                "\n" . getFileContent('css/wideApplication.css') . "\n" . getFileContent('css/email.css') ;
                
            $res = '<div id="begin">' . $res->getContent() . '<div id="end">';  
            $res =  csstoinline_Emogrifier::convert($res, $css);  
            $res = str::cut($res, '<div id="begin">', '<div id="end">');    
        }
        
        //Изчистваме HTMl коментарите
        $res = email_Outgoings::clearHtmlComments($res);

        //Връщаме стария режим
        Mode::pop('text');
        
        return $res;
    }
    
    
	/**
     * Взема текстовата част на имейл-а
     * 
     * @param object $rec     - Данни за имейла
     * @param email  $emailTo - Имейла на потребителя
     * 
     * @return core_ET $res 
     */
    function getEmailText($rec, $emailTo, $sending=FALSE)
    {
        //Емулираме текстов режим
        Mode::push('text', 'plain');
         
        $data = new stdClass();
        
        //Вземаме тялото на имейла и заместваме шаблоните
        $data->rec = $rec;
        
        //Подготвяме данните за имейла
        $this->prepareSingle($data);
        
        $data->row->body = $this->replaceEmailData($data->row->body, $rec->listId, $emailTo, !$sending);
        
        //Рендираме шаблона
        $res = $this->renderSingle($data);
        
        //Връщаме стария режим
        Mode::pop('text');
        
        return $res;
    }
    
    
	/**
     * Заместваме шаблоните в полетата, които образуват антетката, с техните стойности
     * 
     * $rec object - Обект с данни, в които ще се заместват данните от антетката
     */
    function replaceHeaderData(&$rec, $emailTo) 
    {
        //Масив с всички полета, които образуват антетката
        $headers = array();
        $headers['recipient'] = 'recipient';
        $headers['attn'] = 'attn';
        $headers['email'] = 'email';
        $headers['tel'] = 'tel';
        $headers['fax'] = 'fax';
        $headers['country'] = 'country';
        $headers['pcode'] = 'pcode';
        $headers['place'] = 'place';
        $headers['address'] = 'address';
        
        //Обхождаме всички данни от антетката
        foreach ($headers as $header) {
            
            //Ако нямаме въведена стойност, прескачаме
            if (!$rec->$header) continue;
            
            //Заместваме данните в антетката
            $rec->$header = $this->replaceEmailData($rec->$header, $rec->listId, $emailTo);
        }
    }
    
    
    /**
     * Заместваме всички шаблони, с техните стойности от БД
     * 
     * @param mixed   $res    - шаблона, който ще се замества
     * @param integer $listId - id' то на шаблона на имейла
     * @param email   $email  - Имейла на потребителя
     * 
     * @return mixed $res
     */
    function replaceEmailData($res, $listId, $email, $escape=FALSE)
    {        
        //Записваме текущите данни на потребителя
        $this->setCurrentEmailData($listId, $email);
        
        //Ако има данни, които да се заместват
        if (count($this->emailData[$listId][$email])) {
            
            foreach ($this->emailData[$listId][$email] as $key => $value) {

                if ($escape) {
                    $value = core_Type::escape($value);    
                }
                
                $search = "[#{$key}#]";
                //Заместваме данните
                $res = str_ireplace($search, $value, $res);
            }     
        }
        
        return $res;
    }
    
    
    /**
     * Сетваме всички данни за текущия потребител
     * 
     * @param integer $listId - id' то на текущия потребител
     * @param email   $email - Имейл на потребителя
     */
    function setCurrentEmailData($listId, $email)
    {
        if (!$this->emailData[$listId][$email]) {
            //Вземаме персоналната информация за потребителя
            $recList = blast_ListDetails::fetch(array("#listId=[#1#] AND #key='[#2#]'", $listId, $email));
            
            //Десериализираме данните за потребителя
            $this->emailData[$listId][$email] = unserialize($recList->data);
            
            $mid = '[#mid#]';
            $urlBg = array($this, 'Unsubscribe', 'mid' => $mid, 'lang' => 'bg');
            $urlEn = array($this, 'Unsubscribe', 'mid' => $mid, 'lang' => 'en');
            
            //Създаваме линковете
            $linkBg = ht::createLink('тук', toUrl($urlBg, 'absolute'), NULL, array('target'=>'_blank'));
            $linkEn = ht::createLink('here', toUrl($urlEn, 'absolute'), NULL, array('target'=>'_blank'));
            
            $this->emailData[$listId][$email]['otpisvane'] = $linkBg;
            $this->emailData[$listId][$email]['unsubscribe'] = $linkEn;
        }
    }
    
    
    /**
     * Намира предполагаемия език на текста
     * 
     * @param text $body - Текста, в който ще се търси
     * 
     * @return string $lg - Двубуквеното означение на предполагаемия език
     */
    function getLanguage($body)
    {
        //Масив с всички предполагаеми езици
        $lgRates = lang_Encoding::getLgRates($body);
        
        //Вземаме езика с най - много точки
        $lg = arr::getMaxValueKey($lgRates);
        
        //Ако езика не е bg, връщаме en
        if ($lg != 'bg') {
            $lg = 'en';
        }
        
        return $lg;
    }
    
    
    /**
     * Вземаме всички прикачени документи
     * 
     * @param integer $id - id' то на имейла
     * @param string  $body - Текста, в който ще се търсят документите
     * 
     * @return array $documents - Масив с манипулаторите на прикачените файлове
     */
    function getDocuments($id, $body)
    {
        $docsArr = $this->getPossibleTypeConvertings($id);

        $documents = array();
        
        //Обхождаме всички документи
        foreach ($docsArr as $fileName => $checked) {
            
            //Намираме името и разширението на файла
            if (($dotPos = mb_strrpos($fileName, '.')) !== FALSE) {
                $ext = mb_substr($fileName, $dotPos + 1);
            
                $fn = mb_substr($fileName, 0, $dotPos);    
            } else {
                $fn = $fileName;
            }
            
            //Масив с манипулаторите на конвертиранети файлове
            $documents = array_merge($documents, $this->convertDocumentAsFile($id, $fn, $ext));
        }

        return (array) $documents;
    }
    
    
	/**
     * Прикачените към документ файлове
     *
     * @param string $body - Текста, в който ще се гледат за файлове
     * 
     * @return array $filesFh - Масив с манипулаторите на прикачените документи
     */
    function getAttachments($body)
    {
        //Всички файлове в документа
        $files = fileman_RichTextPlg::getFiles($body);
        
        $filesFh = array();
        
        //Преработваме масива да връща манупулаторите на файловете, в ключа и стойността на масива
        foreach ($files as $fh => $fileName) {
            $filesFh[$fh] = $fh;
        }
        
        return $filesFh;
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
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $res .= "<p><i>Нагласяне на Cron</i></p>";
        
        //Данни за работата на cron
        $rec = new stdClass();
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
    
    
	/**
     * Интерфейсен метод на doc_DocumentIntf
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
        
        return $row;
    }


    /**
     * Връща заглавието по подразбиране без да се заменят placeholder' ите
     */
    function getDefaultSubject($id, $emailTo = NULL, $boxFrom = NULL)
    {
        return NULL;
    }
    
    
}