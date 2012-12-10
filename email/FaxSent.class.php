<?php 


/**
 * Изпращане на факсове
 *
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
    var $canSend = 'fax, admin';
    
    
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
     * 
     */
    function description()
    {
    }
    
    
    /**
     * 
     */
    function act_Send()
    {
        $this->requireRightFor('send');
        
        //Броя на класовете, които имплементират интерфейса email_SentFaxIntf
        $clsCount = core_Classes::getInterfaceCount('email_SentFaxIntf');

        //Ако нито един клас не имплементира интерфейса
        if (!$clsCount) {
            core_Statuses::add('Нямате инсталирана факс услуга.', 'warning');   
            
            redirect(getRetUrl());
        }

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
        
        $lg = email_Outgoings::getLanguage($data->rec->originId, $data->rec->threadId, $data->rec->folderId);

        // Ако формата е успешно изпратена - изпращане, лог, редирект
        if ($data->form->isSubmitted()) {
            
            $Email = cls::get('email_Outgoings');
            
            //Услугата за изпращане на факс
            $service = $data->form->rec->service;
            
            // Инстанция на услугата
            $instance = cls::getInterface('email_SentFaxIntf', $service);
            
            //Вземаме всички избрани файлове
            $data->rec->attachmentsFh = type_Set::toArray($data->form->rec->attachmentsSet);
            
            //Ако имамем прикачени файлове
            if (count($data->rec->attachmentsFh)) {
                
                //Вземаме id'тата на файловете вместо манупулатора име
                $attachments = fileman_Files::getIdFromFh($data->rec->attachmentsFh);

                //Записваме прикачените файлове
                $data->rec->attachments = type_KeyList::fromArray($attachments);
            }
            
            // Генерираме списък с документи, избрани за прикачане
            $docsArr = email_Outgoings::getAttachedDocuments($data->form->rec);
            
            //Всички факс номера
            $faxToArr = static::faxToArray($data->form->rec->faxTo);
            
//            $emailCss = getFileContent('css/email.css'); //TODO
            $success  = $failure = array(); // списъци с изпратени и проблемни получатели
            
            foreach ($faxToArr as $faxTo) {
                log_Documents::pushAction(
                    array(
                        'containerId' => $data->rec->containerId,
                        'action'      => log_Documents::ACTION_FAX, 
                        'data'        => (object)array(
                            'service' => $service,
                            'faxTo'   => $faxTo,
                        )
                    )
                );
                
                // Подготовка на текста на писмото (HTML & plain text)
                $data->rec->__mid = NULL;
//                $data->rec->html = $Email->getEmailHtml($data->rec, $lg, $emailCss); //TODO не е нужно, защото HTML частта се добавя като прикачен файл
                $data->rec->text = $Email->getEmailText($data->rec, $lg);
                
                // Генериране на прикачените документи
                $data->rec->documentsFh = array();
                
                foreach ($docsArr as $attachDoc) {
                    // Използваме интерфейсен метод doc_DocumentIntf::convertTo за да генерираме
                    // файл със съдържанието на документа в желания формат
                    $fhArr = $attachDoc['doc']->convertTo($attachDoc['ext'], $attachDoc['fileName']);
                    
                    $data->rec->documentsFh += $fhArr;
                }
                
                // .. ако имаме прикачени документи ...
                if (count($data->rec->documentsFh)) {
                    //Вземаме id'тата на файловете вместо манипулаторите
                    $documents = fileman_Files::getIdFromFh($data->rec->documentsFh);
                
                    //Записваме прикачените файлове
                    $data->rec->documents = type_KeyList::fromArray($documents);
                }
                
                // ... и накрая - изпращане. 
                $status = $instance->sendFax($data, $faxTo);
                
                if ($status) {
                    // Правим запис в лога
                    $Email->log('Send fax to ' . $faxTo, $data->rec->id);
                    $success[] = $faxTo;
                } else {
                    $Email->log('Unable to send fax to ' . $faxTo, $data->rec->id);
                    $failure[] = $faxTo;
                }
                
                log_Documents::popAction();
            }

            // Създаваме съобщение, в зависимост от състоянието на изпращане
            if (empty($failure)) {
                $msg = 'Успешно изпратено до: ' . implode(', ', $success);
                $statusType = 'notice';
            } else {
                $msg = 'Грешка при изпращане до: ' . implode(', ', $failure);
                $statusType = 'warning';
            }
            
            // Добавяме статус
            core_Statuses::add($msg, $statusType);
            
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
        $preview = new ET("<div style='display:table'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Факс") . "</b></div>[#FAX_HTML#]<pre class=\"document\">[#FAX_TEXT#]</pre></div>");

        $Email = cls::get('email_Outgoings');
        
        // Полето имейл да не се показва при изпращане на факс
        unset($data->rec->email);
        
        //HTML частта на факса
        $faxHtml = $Email->getEmailHtml($data->rec, $lg);
        
        //Текстовата част на факса
        $faxText = $Email->getEmailText($data->rec, $lg);
        
        //Добавяме към шаблона
        $preview->append($faxHtml, 'FAX_HTML');
        $preview->append(core_Type::escape($faxText), 'FAX_TEXT');
        
        //Добавяме изгледа към главния шаблон
        $tpl->append($preview);

        return static::renderWrapping($tpl);
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
        $data->form->setAction(array($mvc, 'send'));
        $data->form->title = 'Изпращане на факс';
        
        $data->form->FNC('service', 'class(interface=email_SentFaxIntf, select=title)', 'input,caption=Услуга');
        $data->form->FNC('faxTo', 'drdata_PhoneType', 'input,caption=До,mandatory,width=785px');
        
        // Добавяме поле за URL за връщане, за да работи бутона "Отказ"
        $data->form->FNC('ret_url', 'varchar', 'input=hidden,silent');
        
        // Подготвяме лентата с инструменти на формата
        $data->form->toolbar->addSbBtn('Изпрати', 'send', 'id=save,class=btn-send');
        
        // Ако има права за ипзващане на имейл
        if (email_Outgoings::haveRightFor('send')) {

            // показваме бутона за изпращане на имейл
            $data->form->toolbar->addBtn('Имейл', array('email_Outgoings', 'send', $id, 'ret_url'=>getRetUrl()), 'class=btn-email-send');    
        }
        
        $data->form->toolbar->addBtn('Отказ', getRetUrl(), array('class' => 'btn-cancel'));

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

        // Трябва да имаме достъп до нишката, за да можем да изпращаме писма от нея
        doc_Threads::requireRightFor('single', $data->rec->threadId);
        
        $Email = cls::get('email_Outgoings');
        
        // Добавяне на предложения на свързаните документи
        $possibleTypeConv = $Email->GetPossibleTypeConvertings($data->form->rec->id);
        
        // HTML частта на текущия файл, да се добави като прикачен файл и да е избран по подразбиране
        $currEmailPdf[$Email->getHandle($data->form->rec->id) . '.pdf'] = 'on';
        
        //Обединяваме двата масива
        $docHandlesArr = array_merge((array)$currEmailPdf, (array)$possibleTypeConv);
        
        if(count($docHandlesArr) > 0) {
            $data->form->FNC('documentsSet', 'set', 'input,caption=Документи,columns=4'); 
            
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
                list($faxNum, $domain) = explode('@', $fax, 2);
                
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
        
        // Обхождаме масива
        foreach ($faxesArr as $faxArr) {
            
            // Създаваме факс номер от кода на държавата + кода на града + самия номер
            $faxNum = "00{$faxArr->countryCode}{$faxArr->areaCode}{$faxArr->number}";
            
            // Добавяме в масива
            $toFaxArr[$faxNum] = $faxNum;
        }
        
        return $toFaxArr;
    }
}