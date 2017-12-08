<?php 


/**
 * Изпращане на факсове
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_FaxSent extends core_Manager
{

    
    /**
     * Заглавие
     */
    var $title = "Факс";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Факс";
    
    
    /**
     * Кой има право да го чете?
     */
    var $canSingle = 'no_one';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'no_one';
    
    
    /**
     * Кой може да изпраща факс?
     */
    var $canSend = 'fax, admin, ceo';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой има права за
     */
    var $canFax = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'email_Wrapper';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        
    }
    
    
    /**
     * Връща имейла на факс номера
     * 
     * @param string $number
     * 
     * @return string
     */
    static function getFaxMail($number)
    {
        $faxMail = $number . '@fax.man';
        
        return $faxMail;
    }
    
    
    /**
     * URL, което ще се използва за създаване на факс
     * 
     * @param string $fax
     * 
     * @return array - Масив s URL-то, което ще се използва за създаване на факс
     */
    static function getAddFaxUrl($fax)
    {
        $urlArr = array('email_Outgoings', 'add', 'faxto' => $fax);
        
        return $urlArr;
    }
    
    
    /**
     * Екшън за изпращане на факс
     */
    function act_Send()
    {
        // Проверяваме дали има права за изпращане
        $this->requireRightFor('send');
        
        // Създаваме обект за данни
        $data = new stdClass();
        
        // Създаване и подготвяне на формата
        $this->prepareSendForm($data);
        
        // Очакваме до този момент във формата да няма грешки
        expect(!$data->form->gotErrors(), 'Има грешки в silent полетата на формата', $data->form->errors);
        
        // Подготвяме адреса за връщане, ако потребителя не е логнат.
        // Ресурса, който ще се зареди след логване обикновено е страницата, 
        // от която се извиква екшън-а act_Manage
        $retUrl = getRetUrl();
        
        // Ако няма URL
        if (!$retUrl) {
            
            // Създаваме го
            $retUrl = array('email_Outgoings', 'single', $data->form->rec->id);
        }
        
        // Зареждаме формата
        $data->form->input();
        
        // Проверка за коректност на входните данни
        $this->invoke('AfterInputSendForm', array($data->form));

        // Дали имаме права за това действие към този запис?
        $this->requireRightFor('send', $data->rec, NULL, $retUrl);
        
        $lg = email_Outgoings::getLanguage($data->rec->originId, $data->rec->threadId, $data->rec->folderId, $data->rec->body);
        
        // Полето имейл да не се показва при изпращане на факс
        unset($data->rec->email);
        
        // Инстанция на класа
        $Email = cls::get('email_Outgoings');
        
        //HTML частта на факса
        $faxHtml = $Email->getEmailHtml($data->rec, $lg);
        
        //Текстовата част на факса
        $faxText = core_ET::unEscape($Email->getEmailText($data->rec, $lg));
        
        // Ако формата е успешно изпратена - изпращане, лог, редирект
        if ($data->form->isSubmitted()) {
            
            static::send($data->rec, $data->form->rec, $lg);
            
            // Подготвяме адреса, към който трябва да редиректнем,  
            // при успешно записване на данните от формата
            $data->form->rec->id = $data->rec->id;
            $this->prepareRetUrl($data);
            
            return new Redirect($data->retUrl);
            
        } else {
            // Подготвяме адреса, към който трябва да редиректнем,  
            // при успешно записване на данните от формата
            $this->prepareRetUrl($data);
        }
                
        // Получаваме изгледа на формата
        $tpl = $data->form->renderHtml();
        
        // Добавяме превю на факса, който ще изпратим
        $preview = new ET("<div class='preview-holder'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Факс") . "</b></div><div class='scrolling-holder'>[#FAX_HTML#]<pre class=\"document\">[#FAX_TEXT#]</pre></div></div>");
        
        //Добавяме към шаблона
        $preview->append($faxHtml, 'FAX_HTML');
        $preview->append(core_Type::escape($faxText), 'FAX_TEXT');
        
        //Добавяме изгледа към главния шаблон
        $tpl->append($preview);

        return static::renderWrapping($tpl);
    }
    
    
    /**
     * Изпраща факс
     * 
     * @param object $rec
     * @param object $options
     * @param string $lg
     */
    public static function send($rec, $options, $lg)
    {
        if (email_Outgoings::checkAndAddForLateSending($rec, $options, $lg)) return ;
        
        // Инстанция на класа
        $Email = cls::get('email_Outgoings');
        
        //Услугата за изпращане на факс
        $service = $options->service;
        
        // Инстанция на услугата
        $instance = cls::getInterface('email_SentFaxIntf', $service);
        
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
        $docsArr = email_Outgoings::getAttachedDocuments($options);
        
        //Всички факс номера
        $faxToArr = static::faxToArray($options->faxTo);
        
        // Списъци с изпратени и проблемни получатели
        $success  = $failure = array();
        
        // Инстанция на doclog_Documents за да работи on_Shutdown
        cls::get('doclog_Documents');
        
        // Обхождаме масива
        foreach ($faxToArr as $faxToA) {
            
            // Вземаме факс номера
            $faxTo = $faxToA['faxNum'];
            
            // Оригиналния факс номер
            $originalFaxTo = $faxToA['original'];
            
            // Пушваме екшъна
            doclog_Documents::pushAction(
                array(
                    'containerId' => $rec->containerId,
                    'threadId'    => $rec->threadId,
                    'action'      => doclog_Documents::ACTION_FAX, 
                    'data'        => (object)array(
                        'service' => $service,
                        'faxTo'   => $originalFaxTo,
                        'sendedBy'   => core_Users::getCurrent(),
                    )
                )
            );
            
            // Подготовка на текста на писмото (HTML & plain text)
            $rec->__mid = NULL;
            
            //Текстовата част на факса
            $faxText = core_ET::unEscape($Email->getEmailText($rec, $lg));
            
            $rec->text = $faxText;
            
            // Генериране на прикачените документи
            $rec->documentsFh = array();
            
            foreach ($docsArr as $attachDoc) {
                try {
                    // Използваме интерфейсен метод doc_DocumentIntf::convertTo за да генерираме
                    // файл със съдържанието на документа в желания формат
                    $fhArr = $attachDoc['doc']->convertTo($attachDoc['ext'], $attachDoc['fileName']);
                } catch (core_exception_Expect $e) {
                    $failure[] = $faxTo;
                }
                $rec->documentsFh += $fhArr;
            }
            
            // .. ако имаме прикачени документи ...
            if (count($rec->documentsFh)) {
                //Вземаме id'тата на файловете вместо манипулаторите
                $documents = fileman::fhKeylistToIds($rec->documentsFh);
            
                //Записваме прикачените файлове
                $rec->documents = keylist::fromArray($documents);
            }
            
            // ... и накрая - изпращане. 
            $status = $instance->sendFax($rec, $faxTo);
            
            if ($status) {
                
                // Ако е инсталиран пакета
                if (core_Packs::isInstalled('callcenter')) {
                    callcenter_Fax::saveSend($originalFaxTo, $rec->containerId);
                }
                
                // Правим запис в лога
                $Email->logWrite('Изпратен факс', $rec->id);
                $success[] = $faxTo;
            } else {
                $Email->logErr('Грешка при изпращане на факс', $rec->id);
                $failure[] = $faxTo;
            }
        }

        // Създаваме съобщение, в зависимост от състоянието на изпращане
        if (empty($failure)) {
            $msg = '|Успешно изпратено до|*: ' . implode(', ', $success);
            $statusType = 'notice';
        } else {
            $msg = '|Грешка при изпращане до|*: ' . implode(', ', $failure);
            $statusType = 'warning';
        }
        
        // Добавяме статус
        status_Messages::newStatus($msg, $statusType);
    }
    
    
    /**
     * Връща интерфейс, който ще се ползва за изпращане на факс
     * 
     * @return integer
     */
    public static function getAutoSendIntf()
    {
        $optionsArr = core_Classes::getOptionsByInterface('email_SentFaxIntf');
        
        reset($optionsArr);
        
        return key($optionsArr);
    }
    
    
    /**
     * Подготовка на формата за изпращане
     * 
     * @param stdClass $data - Данните за формата
     * 
     * @return stdClass $data - Променените данни на формата
     */
    function prepareSendForm_($data)
    {
        $id = Request::get('id', 'int');
        
        $data->form = $this->getForm();
        $data->form->setAction(array($this, 'send'));
        $data->form->title = 'Изпращане на факс';
        
        $data->form->FNC('service', 'class(interface=email_SentFaxIntf, select=title)', 'input,caption=Услуга, mandatory');
        $data->form->FNC('faxTo', 'drdata_PhoneType', 'input,caption=До,mandatory,width=785px,hint=Номер на факс');
        $data->form->FNC('delay', 'time(suggestions=1 мин|5 мин|8 часа|1 ден, allowEmpty)', 'caption=Отложено изпращане на факса->Отлагане,hint=Време за отлагане на изпращането,input,formOrder=8');
        
        // Добавяме поле за URL за връщане, за да работи бутона "Отказ"
        $data->form->FNC('ret_url', 'varchar(1024)', 'input=hidden,silent');
        
        // Подготвяме лентата с инструменти на формата
        $data->form->toolbar->addSbBtn('Изпрати', 'send', NULL, array('id'=>'save', 'ef_icon'=>'img/16/move.png', 'title'=>'Изпращане на факса'));
        
        // Ако има права за ипзващане на имейл
        if (email_Outgoings::haveRightFor('send')) {

            // Показваме бутона за изпращане на имейл
            $data->form->toolbar->addBtn('Имейл', array('email_Outgoings', 'send', $id, 'ret_url'=>getRetUrl()), 'ef_icon = img/16/email_go.png', 'title=Обратно към имейла');    
        }
        
        $data->form->toolbar->addBtn('Отказ', getRetUrl(),  'ef_icon = img/16/close-red.png', 'title=Прекратяване на изпращането');

        $data->form->input(NULL, 'silent');

        return $data;
    }

    
	/**
     * Извиква се след подготовката на формата за изпращане
     * 
     * @param core_Manager $mvc  - 
     * @param stdClass     $data - Данните от формата
     */
    function on_AfterPrepareSendForm($mvc, &$data)
    {
        expect($data->rec = email_Outgoings::fetch($data->form->rec->id));

        $Email = cls::get('email_Outgoings');
        
        // Добавяне на предложения на свързаните документи
        $possibleTypeConv = $Email->GetPossibleTypeConvertings($data->form->rec->id);
        
        $currEmailPdf = array();
        
        // HTML частта на текущия файл, да се добави като прикачен файл и да е избран по подразбиране
        $currEmailPdf[$Email->getHandle($data->form->rec->id) . '.pdf'] = 'on';
        
        //Обединяваме двата масива
        $docHandlesArr = array_merge((array)$currEmailPdf, (array)$possibleTypeConv);
        
        if(count($docHandlesArr) > 0) {
            $data->form->FNC('documentsSet', 'set', 'input,caption=Документи,columns=4'); 
            
            $suggestion = array();
            $setDef = array();
            
            //Вземаме всички документи
            foreach ($docHandlesArr as $name => $checked) {
                
                //Проверяваме дали документа да се избира по подразбиране
                if ($checked == 'on') {
                    //Стойността да е избрана по подразбиране
                    $setDef[$name] = $name;
                }
                
                //Всички стойности, които да се покажат
                $suggestion[$name] = $name;
            }
            
            //Задаваме на формата да се покажат полетата
            $data->form->setSuggestions('documentsSet', $suggestion);
            
            //Задаваме, кои полета да са избрани по подразбиране
            $data->form->setDefault('documentsSet', $setDef); 
        }
        
        // Добавяне на предложения за прикачени файлове
        $filesArr = $Email->getAttachments($data->rec);
        
        if(count($filesArr) > 0) {
            $data->form->FNC('attachmentsSet', 'set', 'input,caption=Файлове,columns=4');
            $data->form->setSuggestions('attachmentsSet', $filesArr);   
        }
        
        // Масив с всички факсове и имейли
        $faxesArr = email_Outgoings::explodeEmailsAndFax($data->rec->email);
        
        // Добавяме в стринга с факсове, факс номера от полето 'Факс'
        $faxNums = $data->rec->fax;
        
        // Ако има имейли, които са факс номера
        if (count($faxesArr['fax'])) {
            
            // Обхождаме ги
            foreach ($faxesArr['fax'] as $fax) {
                
                // Разделяме домейн частта от номера
                list($faxNum) = explode('@', $fax, 2);
                
                // Добавяме към стринга
                $faxNums .= ($faxNums) ? ", {$faxNum}" : $faxNum;
            }
        }
              
        //Задаваме факс номера по подразбиране да се вземат от факса на контрагента
        $data->form->setDefault('faxTo', $faxNums);
    }
    
    
 	/**
     * Изпълнява се след подготвяне на факс формата
     * 
     * Проверява дали сме въвели коректен факс
     */
    function on_AfterInputSendForm($mvc, &$form)
    {
        // Ако формата е изпратена успешно
        if ($form->isSubmitted()) {
            
            // Ако не може да се намеи нито един факс номер
            if (!count(drdata_PhoneType::toArray($form->rec->faxTo))) {
                
                // Добавяме съобщение за грешка
                $form->setError('faxTo', "Не сте въвели валиден факс номер.");
            }
        }
    }
    
    
    /**
     * Преобразува подадения стринг от факсове в масив
     * 
     * @param string $faxTo - Стринг от факсове
     * 
     * @return array $toFaxArr - Масив с факсове
     */
    static function faxToArray($faxTo)
    {   
        // Преобразуваме стринга в масив с факс номера
        $faxesArr = drdata_PhoneType::toArray($faxTo);
        
        $toFaxArr = array();
        
        // Обхождаме масива
        foreach ($faxesArr as $key => $faxArr) {
            
            // Създаваме факс номер от кода на държавата + кода на града + самия номер
            $faxNum = "00{$faxArr->countryCode}{$faxArr->areaCode}{$faxArr->number}";
            
            // Добавяме в масива
            $toFaxArr[$key]['faxNum'] = $faxNum;
            
            // Добавяме оригиналния номер
            $toFaxArr[$key]['original'] = $faxArr->original;
        }
        
        return $toFaxArr;
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
        // Ако изпращаме
        if ($action == 'send') {
            
            // Ако няма клас, който да имплементира интерфейса email_SentFaxIntf
            if (!core_Classes::getInterfaceCount('email_SentFaxIntf')) {
                
                // Никой да не може да променя
                $requiredRoles = 'no_one';
            }
            
            // Ако все още има права и има запис
            if (!$requiredRoles != 'no_one' && $rec->threadId) {
                
                // Ако няма права за сингъла към нишката
                if (!doc_Threads::haveRightFor('single', $rec->threadId)) {
                    
                    // Да не може да се изпраща
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
}
