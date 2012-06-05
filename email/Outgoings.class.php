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
     * Полета, които ще се клонират
     */
    var $cloneFields = 'subject, body, recipient, attn, email, tel, fax, country, pcode, place, address';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
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
    var $singleTitle = "Изходящ имейл";
    
    
    /**
     * Кой има право да го чете?
     */
    var $canSingle = 'admin, email, user';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'admin, email, user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, email, user';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin, email, user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, email, user';
    
    
    /**
     * Кой може да изпраща имейли?
     */
    var $canSend = 'admin, email, user';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой има права за
     */
    var $canEmail = 'admin, email, user';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'email_Wrapper, doc_DocumentPlg, plg_RowTools, 
        plg_Printing, email_plg_Document, doc_ActivatePlg, 
        bgerp_plg_Blank,  plg_Search';
    
    
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
    var $searchFields = 'subject, recipient, attn, email, folderId, threadId, containerId';
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('subject', 'varchar', 'caption=Относно,mandatory,width=100%');
        $this->FLD('body', 'richtext(rows=15,bucket=Postings)', 'caption=Съобщение,mandatory');
        
        //Данни за адресанта
        $this->FLD('recipient', 'varchar', 'caption=Адресант->Фирма');
        $this->FLD('attn', 'varchar', 'caption=Адресант->Лице,oldFieldName=attentionOf');
        $this->FLD('email', 'emails', 'caption=Адресант->Имейл');
        $this->FLD('tel', 'varchar', 'caption=Адресант->Тел.,oldFieldName=phone');
        $this->FLD('fax', 'varchar', 'caption=Адресант->Факс');
        $this->FLD('country', 'varchar', 'caption=Адресант->Държава');
        $this->FLD('pcode', 'varchar', 'caption=Адресант->П. код');
        $this->FLD('place', 'varchar', 'caption=Адресант->Град/с');
        $this->FLD('address', 'varchar', 'caption=Адресант->Адрес');
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

        $lg = email_Outgoings::getLanguage($data->rec->originId, $data->rec->threadId, $data->rec->folderId);

        // Ако формата е успешно изпратена - изпращане, лог, редирект
        if ($data->form->isSubmitted()) {
            
            $data->rec->html = $this->getEmailHtml($data->rec, $lg, getFileContent('css/email.css'));
            
            $data->rec->text = $this->getEmailText($data->rec, $lg);
                        
            //Вземаме всички избрани файлове
            $attachmentsFh = type_Set::toArray($data->form->rec->attachmentsSet);
            
            //Ако имамем прикачени файлове
            if (count($attachmentsFh)) {
                
                //Вземаме id'тата на файловете вместо манупулатора име
                $attachments = fileman_Files::getIdFromFh($attachmentsFh);

                //Преобразуваме в keylist тип
                $keyAtt = type_KeyList::fromArray($attachments);
                
                //Записваме прикачените файлове
                $data->rec->attachments = $keyAtt;
                
                //Записваме манупулотирите на прикачените файлове
                $data->rec->attachmentsFh = (array)$attachmentsFh;
            }
            
            $documentsFh = array();
            
            //Прикачваме избраните документи
            $docsArr = type_Set::toArray($data->form->rec->documentsSet);
            
            //Обхождаме избрани документи
            foreach ($docsArr as $fileName) {
                
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
                
                // Използваме интерфейсен метод doc_DocumentIntf::convertTo за да генерираме
                // файл със съдържанието на документа в желания формат
                $fh = $doc->convertTo($ext, $fileName);
                
                if (!empty($fh)) {
                    $documentsFh[$fh] = $fh;
                }
            }
            
            //Ако имамем прикачени документи
            if (count($documentsFh)) {
                
                //Вземаме id'тата на файловете вместо манупулатора име
                $documents = fileman_Files::getIdFromFh($documentsFh);

                //Преобразуваме в keylist тип
                $keyAtt = type_KeyList::fromArray($documents);

                //Записваме прикачените файлове
                $data->rec->documents = $keyAtt;
                
                //Записваме манупулотирите на прикачените документи
                $data->rec->documentsFh = (array)$documentsFh;
            }

            $status = email_Sent::send(
                $data->form->rec->containerId,
                $data->form->rec->threadId,
                $data->form->rec->boxFrom,
                $data->form->rec->emailsTo,
                $data->rec->subject,
                $data->rec,
                array(
                    'encoding' => $data->form->rec->encoding
                )
            );
            
            $msg = $status ? 'Изпратено' : 'ГРЕШКА при изпращане на писмото';
            
            // Правим запис в лога
            $this->log('Send', $data->rec->id);
            
            // Подготвяме адреса, към който трябва да редиректнем,  
            // при успешно записване на данните от формата
            $data->form->rec->id = $data->rec->id;
            $this->prepareRetUrl($data);
            
            // $msg е съобщение за статуса на изпращането
            return new Redirect($data->retUrl, $msg);
        } else {
            // Подготвяме адреса, към който трябва да редиректнем,  
            // при успешно записване на данните от формата
            $this->prepareRetUrl($data);
        }
        
        // Получаваме изгледа на формата
        $tpl = $data->form->renderHtml();
        
        // Добавяме превю на имейла, който ще изпратим
        $preview = new ET("<div style='display:table'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Изходящ имейл") . "</b></div>[#EMAIL_HTML#]<pre class=\"document\">[#EMAIL_TEXT#]</pre></div>");
       
        $preview->append($this->getEmailHtml($data->rec, $lg) , 'EMAIL_HTML');
        $preview->append(core_Type::escape($this->getEmailText($data->rec, $lg)) , 'EMAIL_TEXT');
        
        $tpl->append($preview);

        return static::renderWrapping($tpl);
    }
    
    
    /**
     * Подготовка на формата за изпращане
     * Самата форма се взема от email_Send
     */
    function prepareSendForm_($data)
    {
        $data->form = email_Sent::getForm();
        $data->form->setAction(array($mvc, 'send'));
        $data->form->title = 'Изпращане на имейл';
        
        $data->form->FNC(
            'emailsTo',
            'emails',
            'input,caption=До,mandatory,width=785px,formOrder=1',
            array(
                'attr' => array(
                    'data-role' => 'list'
                ),
            )
        );
        
        // Добавяме поле за URL за връщане, за да работи бутона "Отказ"
        $data->form->FNC('ret_url', 'varchar', 'input=hidden,silent');
        
        // Подготвяме лентата с инструменти на формата
        $data->form->toolbar->addSbBtn('Изпрати', 'send', 'id=save,class=btn-send');
        $data->form->toolbar->addBtn('Отказ', getRetUrl(), array('class' => 'btn-cancel'));
        
        $data->form->input(NULL, 'silent');
        
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
        
        $data->form->setDefault('containerId', $data->rec->containerId);
        $data->form->setDefault('threadId', $data->rec->threadId);
        $data->form->setDefault('boxFrom', email_Inboxes::getUserEmailId());
        
        // Добавяне на предложения на свързаните документи
        $docHandlesArr = $mvc->GetPossibleTypeConvertings($data->form->rec->id);
        
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
        $filesArr = $mvc->getAttachments($data->rec);
        if(count($filesArr) > 0) {
            $data->form->FNC('attachmentsSet', 'set', 'input,caption=Файлове,columns=4');
            $data->form->setSuggestions('attachmentsSet', $filesArr);   
        }
        $data->form->setDefault('emailsTo', $data->rec->email);
        

        // Добавяне на предложения за имейл адреси, до които да бъде изпратено писмото
        $toSuggestions = doc_Threads::getExternalEmails($data->rec->threadId);
        unset($toSuggestions[$data->rec->email]);
        if (count($toSuggestions)) {
            $data->form->setSuggestions('emailsTo', array('' => '') + $toSuggestions);
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
                        $form->fields['encoding']->type->toVerbal($rec->encoding));
                }
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
            
            if ($mvc->flagSendIt) {
                $form->rec->state = 'active';
                
                //Ако изпращаме имейла и полето за имейл е празно, показва съобщение за грешка
                if (!trim($form->rec->email)) {
                    $form->setError('email', "За да изпратите имейла, трябва да попълните полето <b>Адресант->Имейл</b>.");    
                }
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterSave($mvc, $id, $rec)
    {
        if ($mvc->flagSendIt) {
            $lg = email_Outgoings::getLanguage($data->rec->originId, $data->rec->threadId, $data->rec->folderId);
            $body = (object)array(
                'html' => $mvc->getEmailHtml($rec, $lg, getFileContent('css/email.css')),
                'text' => $mvc->getEmailText($rec, $lg),
                //Ако изпращаме имейла директно от формата, документите и файловете не се прикачват
            );
            
            $mvc->sendStatus = email_Sent::send(
                $rec->containerId,
                $rec->threadId,
                email_Inboxes::getUserEmailId(),
                $rec->email,
                $rec->subject,
                $body,
                array(
                    'encoding' => 'utf-8'
                )
            );
        }
    }
    
    
    /**
     * Връща plain-текста на писмото
     */
    function getEmailText($oRec, $lg)
    {
        core_Lg::push($lg);
        
        $textTpl = static::getDocumentBody($oRec->id, 'plain');
        $text    = html_entity_decode($textTpl->getContent());
        
        core_Lg::pop();
        
        return $text;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getEmailHtml($rec, $lg, $css = '')
    {
        core_Lg::push($lg);

        // Използваме интерфейсния метод doc_DocumentIntf::getDocumentBody() за да рендираме
        // тялото на документа (изходящия имейл)
        $res = static::getDocumentBody($rec->id, 'xhtml');
        
        // Правим инлайн css, само ако са зададени стилове $css
        // Причината е, че Emogrifier не работи правилно, като конвертира html entities към 
        // символи (страничен ефект).
        //
        // @TODO Да се сигнализират създателите му
        //
        if($css) {
            //Създаваме HTML частта на документа и превръщаме всички стилове в inline
            //Вземаме всичките css стилове
            $css = getFileContent('css/wideCommon.css') .
                "\n" . getFileContent('css/wideApplication.css') . "\n" . $css ;
                
            $res = '<div id="begin">' . $res->getContent() . '<div id="end">';  
            $res =  csstoinline_Emogrifier::convert($res, $css);  
            $res = str::cut($res, '<div id="begin">', '<div id="end">');
        }
            
        //Изчистваме HTML коментарите
        $res = self::clearHtmlComments($res);
        
        core_Lg::pop();
        
        return $res;
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec = $data->form->rec;
        $form = $data->form;
        
        //Добавяме бутона изпрати
        $form->toolbar->addSbBtn('Изпрати', 'sending', array('class' => 'btn-send', 'order'=>'10'));
        
        //Ако субмитнем формата, кода не се изпълнява
        if ($form->isSubmitted()) return;
        
        //Ако редактираме записа или го клонираме, няма да се изпълни нататък
        if (($rec->id) || (Request::get('Clone'))) return;
        
        //Зареждаме нужните променливи от $data->form->rec
        $originId = $rec->originId;
        $threadId = $rec->threadId;
        $folderId = $rec->folderId;
        $emailTo = Request::get('emailto');
        
        //Определяме треда от originId
        if($originId && !$threadId) {
            $threadId = doc_Containers::fetchField($originId, 'threadId');
        }
        
        //Определяме папката от треда
        if($threadId && !$folderId) {
            $folderId = doc_Threads::fetchField($threadId, 'folderId');
        }
        
        //Ако сме дошли на формата чрез натискане на имейл
        if ($emailTo) {
            $folderId = email_Router::getEmailFolder($emailTo);
        }
        
        //Ако писмото е отговор на друго, тогава по подразбиране попълваме полето относно
        if ($originId) {
            //Добавяме в полето Относно отговор на съобщението
            $oDoc = doc_Containers::getDocument($originId);
            $oRow = $oDoc->getDocumentRow();
            $rec->subject = 'RE: ' . html_entity_decode($oRow->title);
            $oContragentData = $oDoc->getContragentData();
        }
        
        //Определяме езика на който трябва да е имейла
        $lg = email_Outgoings::getLanguage($originId, $threadId, $folderId);
        
        //Сетваме езика, който сме определили за превод на съобщението
        core_Lg::push($lg);
        
        //Ако сме в треда, вземаме данните на получателя
        if ($threadId) {
            //Данните на получателя от треда
            $contragentData = doc_Threads::getContragentData($threadId);
        }
        
        //Ако създаваме нов тред, определяме данните на контрагента от ковъра на папката
        if (!$threadId && $folderId) {
            $contragentData = doc_Folders::getContragentData($folderId);
        }
        
        //Ако сме открили някакви данни за получателя
        if ($contragentData) {
            
            //Заместваме данните в полетата с техните стойности. Първо се заместват данните за потребителя
            $rec->recipient = tr($contragentData->company);
            $rec->attn      = tr($contragentData->name);
            $rec->country   = tr($contragentData->country);
            $rec->pcode     = $contragentData->pcode;
            $rec->place     = tr($contragentData->place);
            
            //Телефонен номер. Ако има се взема от компанията, aко няма, от мобилния. В краен случай от персоналния (домашен).
            ($contragentData->tel) ? ($rec->tel = $contragentData->tel) : ($rec->tel = $contragentData->pMobile);
            
            if (!$rec->tel) $rec->tel = $contragentData->pTel;
            
            //Факс. Прави опит да вземе факса на компанията. Ако няма тогава взема персоналния.
            $rec->fax = $contragentData->fax ? $contragentData->fax : $contragentData->pFax;
            
            //Адрес. Прави опит да вземе адреса на компанията. Ако няма тогава взема персоналния.
            $rec->address = tr($contragentData->address ? $contragentData->address : $contragentData->pAddress);
            
            //Имейл. Прави опит да вземе имейл-а на компанията. Ако няма тогава взема персоналния.
            $rec->email = $contragentData->email ? $contragentData->email : $contragentData->pEmail;
        }
        
        // Ако отговаряме на конкретен е-имейл, винаги имейл адреса го вземаме от него
        if($oContragentData->email) {
            $rec->email = $oContragentData->email;
        }
        
        //Ако сме натиснали конкретен имейл, винаги вземаме имейл адреса от Request
        if ($emailTo) {
            $rec->email = $emailTo;
        }
        
        //Данни необходими за създаване на хедър-а на съобщението
        $contragentDataHeader['name'] = $contragentData->name;
        $contragentDataHeader['salutation'] = $contragentData->salutation;
        
        //Създаваме тялото на постинга
        $rec->body = $mvc->createDefaultBody($contragentDataHeader, $originId);
        
        //След превода връщаме стария език
        core_Lg::pop();
        
        //Добавяме новите стойности на $rec
        $rec->threadId = $threadId;
        $rec->folderId = $folderId;
    }
    
    
    /**
     * Създава тялото на постинга
     */
    function createDefaultBody($HeaderData, $originId)
    {
        //Хедър на съобщението
        $header = $this->getHeader($HeaderData);
        
        //Текста между заглавието и подписа
        $body = $this->getBody($originId);
        
        //Футър на съобщението
        $footer = $this->getFooter();
        
        //Текста по подразбиране в "Съобщение"
        $defaultBody = $header . "\n\n" . $body . "\n\n" . $footer;
        
        return $defaultBody;
    }
    
    
    /**
     * Създава хедър към постинга
     */
    function getHeader($data)
    {
        $tpl = new ET(tr(getFileContent("email/tpl/OutgoingHeader.shtml")));
        
        //Заместваме шаблоните
        $tpl->replace(tr($data['salutation']), 'salutation');
        $tpl->replace(tr($data['name']), 'name');
        
        return $tpl->getContent();
    }
    
    
    /**
     * Създава текста по подразбиране
     */
    function getBody($originId)
    {
        if (!$originId) return ;
        
        //Вземаме класа, за който се създава съответния имейл
        $document = doc_Containers::getDocument($originId);
        
        //Името на класа
        $className = $document->className;
        
        //Ако класа имплементира интерфейса "doc_ContragentDataIntf", тогава извикваме метода, който ни връща тялото на имейл-а
        if (cls::haveInterface('doc_ContragentDataIntf', $className)) {
            $body = $className::getDefaultEmailBody($document->that);
        }
        
        return $body;
    }
    
    
    /**
     * Създава футър към постинга в зависимост от типа на съобщението
     */
    function getFooter()
    {
        //Вземаме езика
        $lg = core_Lg::getCurrent();
        
        //Зареждаме класа, за да имаме достъп до променливите
        cls::load('crm_Companies');
        
        $companyId = BGERP_OWN_COMPANY_ID;
        
        //Вземаме данните за нашата фирма
        $myCompany = crm_Companies::fetch($companyId);
        
        $userName = core_Users::getCurrent('names');
        
        $country = crm_Companies::getVerbal($myCompany, 'country');
        
        //Ако езика е на български и държавата е България, да не се показва държавата
        if ((strtolower($lg) == 'bg') && (strtolower($country) == 'bulgaria')) {
            
            unset($country);
        }
        
        $tpl = new ET(tr(getFileContent("email/tpl/OutgoingFooter.shtml")));
        
        //Заместваме шаблоните
        $tpl->replace(tr($userName), 'name');
        $tpl->replace(tr($myCompany->name), 'company');
        $tpl->replace($myCompany->tel, 'tel');
        $tpl->replace($myCompany->fax, 'fax');
        $tpl->replace($myCompany->email, 'email');
        $tpl->replace($myCompany->website, 'website');
        $tpl->replace(tr($country), 'country');
        $tpl->replace($myCompany->pCode, 'pCode');
        $tpl->replace(tr($myCompany->place), 'city');
        $tpl->replace(tr($myCompany->address), 'street');
        
        return $tpl->getContent();
    }
    
    
    /**
     * Подготвя иконата за единичния изглед
     */
    static function on_AfterPrepareSingle($mvc, $data)
    {
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
    }
    
    
    /**
     * След рендиране на singleLayout заместваме плейсхолдера
     * с шаблонa за тялото на съобщение в документната система
     */
    function renderSingleLayout_(&$data)
    {
        if (Mode::is('text', 'xhtml')) {
            //Полета До и Към
            $attn = $data->row->recipient . $data->row->attn;
            $attn = trim($attn);
            
            //Ако нямаме въведени данни До: и Към:, тогава не показваме имейл-а, и го записваме в полето До:
            if (!$attn) {
                $data->row->recipientEmail = $data->row->email;
                unset($data->row->email);
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
                    unset($data->row->email);
                }
            }        
        }
        
        // Определяме лейаута според режима на рендиране
        
        switch (Mode::get('text')) 
        {
            case 'xhtml':
                $tpl = 'email/tpl/SingleLayoutSendOutgoings.shtml';
                break;
                
            case 'plain':
                $tpl = 'email/tpl/SingleLayoutOutgoings.txt';
                break;
                
            default:
                $tpl = 'email/tpl/SingleLayoutOutgoings.shtml';
                
        }
        
        $tpl = new ET(tr('|*' . getFileContent($tpl)));
        
        return $tpl;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->handle = $mvc->getHandle($rec->id);
    }
    
    
    /**
     * Прикачените към документ файлове
     *
     * @param mixed $rec int - ид на документ или stdClass - запис на модела
     * @return array
     */
    public function getAttachments($rec)
    {
        if (!is_object($rec)) {
            $rec = self::fetch($rec);
        }
        
        $files = fileman_RichTextPlg::getFiles($rec->body);
        
        return $files;
    }
    
    /******************************************************************************************
     *
     * ИМПЛЕМЕНТАЦИЯ НА email_DocumentIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * Какъв да е събджекта на писмото по подразбиране
     *
     * @param int $id ид на документ
     * @param string $emailTo
     * @param string $boxFrom
     * @return string
     *
     * @TODO това ще е полето subject на doc_Posting, когато то бъде добавено.
     */
    public function getDefaultSubject($id, $emailTo = NULL, $boxFrom = NULL)
    {
        return static::fetchField($id, 'subject');
    }
    
    
    /**
     * До кой е-имейл или списък с етрябва да се изпрати писмото
     *
     * @param int $id ид на документ
     */
    public function getDefaultEmailTo($id)
    {
        return static::fetchField($id, 'email');
    }
    
    
    /**
     * Адреса на изпращач по подразбиране за документите от този тип.
     *
     * @param int $id ид на документ
     * @return int key(mvc=email_Inboxes) пощенска кутия от нашата система
     */
    public function getDefaultBoxFrom($id)
    {
        // Няма смислена стойност по подразбиране
        return NULL;
    }
    
    
    /**
     * Писмото (ако има такова), в отговор на което е направен този постинг
     *
     * @param int $id ид на документ
     * @return int key(email_Incomings) NULL ако документа не е изпратен като отговор
     */
    public function getInReplayTo($id)
    {
        
        /**
         * @TODO
         */
        return NULL;
    }
    
    
    /**
     * @todo Чака за документация...
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
        
        return $row;
    }


    /**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('Postings', 'Прикачени файлове в постингите', NULL, '300 MB', 'user', 'user');
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща данните за адресанта
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
        $contrData->pcode = $posting->pcode;
        $contrData->place = $posting->place;
        $contrData->address = $posting->address;
        $contrData->email = $posting->email;
        
        return $contrData;
    }
    
    
    /**
     * Добавя бутон за Изпращане в единичен изглед
     * @param stdClass $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        //Добавяме бутона, ако състоянието не е чернова или отхвърлена, и ако имаме права за изпращане
        if (($data->rec->state != 'draft') && ($data->rec->state != 'rejected')) {
            if ($mvc->haveRightFor('email')) {
                $retUrl = array($mvc, 'single', $data->rec->id);
                $data->toolbar->addBtn('Изпращане', array('email_Outgoings', 'send', $data->rec->id, 'ret_url'=>$retUrl), 'class=btn-email-send');    
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getExternalEmails($threadId)
    {
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->where("#threadId = {$threadId}");
        $query->show('email');
        
        $result = array();
        
        while ($rec = $query->fetch()) {
            if($eml = trim($rec->email)) {
                $result[$eml] = $eml;
            }
        }
        
        return $result;
    }
    
    
    /**
     * Намира предполагаемия езика на който трябва да отговорим
     *
     * @param int $originId - id' то на контейнера
     * @param int $threadId - id' то на нишката
     * @param int $folderId  -id' то на папката
     *
     * @return string $lg - Двубуквеното означение на предполагаемия език на имейла
     */
    static function getLanguage($originId, $threadId, $folderId)
    {
        //Търсим езика в контейнера
        $lg = doc_Containers::getLanguage($originId);
        
        //Ако не сме открили езика
        if (!$lg) {
            //Търсим езика в нишката
            $lg = doc_Threads::getLanguage($threadId);
        }
        
        //Ако не сме открили езика
        if (!$lg) {
            //Търсим езика в папката
            $lg = doc_Folders::getLanguage($folderId);
        }
        
        //Ако не сме открили езика
        if (!$lg) {
            //Вземаме езика на текущия интерфейс
            $lg = core_Lg::getCurrent();
        }
        
        //Ако езика не е bg, използваме en
        if ($lg != 'bg') {
            $lg = 'en';
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
}
