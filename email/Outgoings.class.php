<?php 


/**
 * Текста, който ще се показва в хедърната част на постингите
 */
defIfNot('BGERP_POSTINGS_HEADER_TEXT', '|*Препратка|');


/**
 * Ръчен постинг в документната система
 *
 *
 * @category  all
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
    var $canRead = 'admin, email';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'admin, email';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, email';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin, email';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, email';
    
    
    /**
     * Кой може да изпраща имейли?
     */
    var $canSend = 'admin, email';
    
    
    /**
     * Кой има право да изтрива?
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
        plg_Printing, email_plg_Document, doc_ActivatePlg, bgerp_plg_Blank';
    
    
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
    var $abbr = 'EML';
    
    
    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'subject';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,subject,recipient,attn,email,createdOn,createdBy';
    
    
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
        $this->FLD('email', 'email', 'caption=Адресант->Имейл');
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
        
        // Ако формата е успешно изпратена - изпращане, лог, редирект
        if ($data->form->isSubmitted()) {
            
            //Създаваме HTML частта на документа и превръщаме всички стилове в inline
            //Вземаме всичките css стилове
            $css = getFileContent('css/wideCommon.css') .
            "\n" . getFileContent('css/wideApplication.css') . "\n" . getFileContent('css/email.css');
            
            $res = $data->rec->html;
            $res = '<div id="begin">' . $res->getContent() . '<div id="end">';
            $res = csstoinline_Emogrifier::convert($res, $css);
            $res = str::cut($res, '<div id="begin">', '<div id="end">');
            
            $data->rec->html = $res;
            
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
    function on_AfterPrepareSendForm($mvc, $data)
    {
        
        expect($data->rec = $mvc->fetch($data->form->rec->id));
        
        // Трябва да имаме достъп до нишката, за да можем да изпращаме писма от нея
        doc_Threads::requireRightFor('single', $data->rec->threadId);
        
        $data->rec->text = $mvc->getEmailText($data->rec, 'bg');
        $data->form->rec->html = $data->rec->html = $mvc->getEmailHtml($data->rec, 'bg');
        
        $data->form->setDefault('containerId', $data->rec->containerId);
        $data->form->setDefault('threadId', $data->rec->threadId);
        $data->form->setDefault('boxFrom', email_Inboxes::getUserEmailId());
        
        $filesArr = $mvc->getAttachments($data->rec);
        
        //Името на PDF документа
        $handle = email_Outgoings::getHandle($data->rec->id);
        
        //Създаваме pdf документа
        $pdfArr = doc_PdfCreator::convert($data->rec->html, $handle);
        $filesArr[$pdfArr] = 'email.pdf';
        
        if(count($filesArr) == 0) {
            $data->form->setField('attachments', 'input=none');
        } else {
            $data->form->setSuggestions('attachments', $filesArr);
        }
        $data->form->setDefault('emailsTo', $data->rec->email);
        
        $toSuggestions = doc_Threads::getExternalEmails($data->rec->threadId);
        
        if (isset($toSuggestions[$data->rec->email])) {
            unset($toSuggestions[$data->rec->email]);
        }
        
        if (count($toSuggestions)) {
            $data->form->setSuggestions('emailsTo', array('' => '') + $toSuggestions);
        }
        
        $data->form->layout = $data->form->renderLayout();
        $tpl = new ET("<div style='display:table'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Изходящ имейл") . "</b></div>[#DOCUMENT#]</div>");
        
        $tpl->append($data->rec->html, 'DOCUMENT');
        $tpl->append('<pre class="document">' . htmlspecialchars($data->rec->text) . '</pre>', 'DOCUMENT');
        
        $data->form->layout->append($tpl);
    }
    
    
    /**
     * Проверка на входните параметри от формата за изпращане
     */
    function on_AfterInputSendForm($mvc, $form)
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
    function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $mvc->flagSendIt = ($form->cmd == 'sending');
            
            if ($mvc->flagSendIt) {
                $form->rec->state = 'active';
            }
        }
    }
    
    /**
     * @todo Чака за документация...
     */
    function on_AfterSave($mvc, $id, $rec)
    {
        if ($mvc->flagSendIt) {
            $body = (object)array(
                'html' => $mvc->getDocumentBody($rec->id, 'html'),
                'text' => $mvc->getDocumentBody($rec->id, 'plain'),
                'attachments' => $mvc->getAttachments($rec),
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
        Mode::push('text', 'plain');
        
        $rec = clone($oRec);
        $row->rec =  type_Text::formatTextBlock($row->rec, 76, 0) ;
        
        $tpl = new ET(tr(getFileContent('email/tpl/SingleLayoutOutgoings.txt')));
        $row = $this->recToVerbal($rec, 'subject,body,attn,email,country,place,recipient,modifiedOn,handle');
        $tpl->placeObject($row);
        
        Mode::pop('text');
        core_Lg::pop();
        
        return html_entity_decode($tpl->getContent());
    }
    
    /**
     * @todo Чака за документация...
     */
    function getEmailHtml($rec, $lg)
    {
        // Създаваме обекта $data
        $data = new stdClass();
        
        // Трябва да има $rec за това $id
        expect($data->rec = $rec);
        
        core_Lg::push($lg);
        
        // Емулираме режим 'printing', за да махнем singleToolbar при рендирането на документа
        Mode::push('printing', TRUE);
        
        // Задаваме `text` режим според $mode. singleView-то на $mvc трябва да бъде генерирано
        // във формата, указан от `text` режима (plain или html)
        Mode::push('text', 'xhtml');
        
        // Подготвяме данните за единичния изглед
        $this->prepareSingle($data);
        
        // Рендираме изгледа
        $res = $this->renderSingle($data);
        
        //Извикваме рендирането на обвивката
        //        $res = $this->renderWrapping($res);
        
        // Връщаме старата стойност на 'printing'
        Mode::pop('text');
        Mode::pop('printing');
        core_Lg::pop();
        
        return $res;
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec = $data->form->rec;
        $form = $data->form;
        
        //Добавяме бутона изпрати
        $form->toolbar->addSbBtn('Изпрати', 'sending', array('class' => 'btn-send', 'order'=>'10'));
        
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
        
        //Изискваме да има права на треда
        if ($threadId) {
            doc_Threads::requireRightFor('single', $threadId);
        }
        
        //Ако няма folderId или нямаме права за запис в папката, тогава използваме имейл-а на текущия потребител
        if ((!$folderId) || (!doc_Folders::haveRightFor('single', $folderId))) {
            $user->email = email_Inboxes::getUserEmail();
            $folderId = email_Inboxes::forceCoverAndFolder($user);
        }
        
        //Ако писмото е отговор на друго, тогава по подразбиране попълваме полето относно
        if ($originId) {
            //Добавяме в полето Относно отговор на съобщението
            $oDoc = doc_Containers::getDocument($originId);
            $oRow = $oDoc->getDocumentRow();
            $rec->subject = 'RE: ' . html_entity_decode($oRow->title);
            $oContragentData = $oDoc->getContragentData();
        }
        
        //Попълваме заглавието
        if ($folderId) {
            $fRec = doc_Folders::fetch($folderId);
            $fRow = doc_Folders::recToVerbal($fRec);
            $data->form->title = '|*' . $mvc->singleTitle . ' |в|* ' . $fRow->title;
        }
        
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
            $rec->recipient = $contragentData->company;
            $rec->attn      = $contragentData->name;
            $rec->country   = $contragentData->country;
            $rec->pcode     = $contragentData->pcode;
            $rec->place     = $contragentData->place;
            
            //Телефонен номер. Ако има се взема от компанията, aко няма, от мобилния. В краен случай от персоналния (домашен).
            ($contragentData->tel) ? ($rec->tel = $contragentData->tel) : ($rec->tel = $contragentData->pMobile);
            
            if (!$rec->tel) $rec->tel = $contragentData->pTel;
            
            //Факс. Прави опит да вземе факса на компанията. Ако няма тогава взема персоналния.
            $rec->fax = $contragentData->fax ? $contragentData->fax : $contragentData->pFax;
            
            //Адрес. Прави опит да вземе адреса на компанията. Ако няма тогава взема персоналния.
            $rec->address = $contragentData->address ? $contragentData->address : $contragentData->pAddress;
            
            //Имейл. Прави опит да вземе имейл-а на компанията. Ако няма тогава взема персоналния.
            $rec->email = $contragentData->email ? $contragentData->email : $contragentData->pEmail;
        }
        
        // Ако отговаряме на конкретен е-имейл, винаги имейл адреса го вземаме от него
        if($oContragentData->email) {
            $rec->email = $oContragentData->email;
        }
        
        //Данни необходими за създаване на хедър-а на съобщението
        $contragentDataHeader['name'] = $contragentData->name;
        $contragentDataHeader['salutation'] = $contragentData->salutation;
        
        //Създаваме тялото на постинга
        $rec->body = $this->createDefaultBody($contragentDataHeader, $originId, $threadId, $folderId);
        
        //Добавяме новите стойности на $rec
        $rec->threadId = $threadId;
        $rec->folderId = $folderId;
    }
    
    
    /**
     * Създава тялото на постинга
     */
    function createDefaultBody($HeaderData, $originId, $threadId, $folderId)
    {
        //Текущия език на интерфейса
        $oldLg = core_Lg::getCurrent();
        
        //Езика, на който искаме да се превежда
        $lg = doc_Folders::getLanguage($threadId, $folderId);
        
        //Сетваме езика, който сме определили за превод на съобщението
        core_Lg::push($lg);
        
        //Хедър на съобщението
        $header = $this->getHeader($HeaderData);
        
        //Текста между заглавието и подписа
        $body = $this->getBody($originId);
        
        //Футър на съобщението
        $footer = $this->getFooter();
        
        //Текста по подразбиране в "Съобщение"
        $defaultBody = $header . "\n\n" . $body . "\n\n" . $footer;
        
        //След превода връщаме стария език
        core_Lg::pop();
        
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
        
        return trim($tpl->getContent());
    }
    
    
    /**
     * Подготвя иконата за единичния изглед
     */
    function on_AfterPrepareSingle($mvc, $data)
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
    function renderSingleLayout_($data)
    {
        //Полета До и Към
        $attn = $data->row->recipient . $data->row->attn;
        $attn = str::trim($attn);
        
        //Ако нямаме въведени данни До: и Към:, тогава не показваме имейл-а, и го записваме в полето До:
        if (!$attn) {
            $data->row->recipientEmail = $data->row->email;
            unset($data->row->email);
        }
        
        //Полета Град и Адрес
        $addr = $data->row->place . $data->row->address;
        $addr = str::trim($addr);
        
        //Ако липсва адреса и града
        if (!$addr) {
            //Не се показва и пощенския код
            unset($data->row->pcode);
            
            //Ако имаме До: и Държава, и нямаме адресни данни, тогава добавяме фирмата след фирмата
            if ($data->row->recipient) {
                $data->row->firmCountry = $data->row->country;
            }
            
            //Не се показва и държавата
            unset($data->row->country);
        }
        
        //Рендираме шаблона
        if (Mode::is('text', 'xhtml') || Mode::is('printing')) {
            //Ако сме в xhtml (изпращане) режим, рендираме шаблона за изпращане
            $tpl = new ET(tr(getFileContent('email/tpl/SingleLayoutSendOutgoings.shtml')));
        } elseif (Mode::is('text', 'plain')) {
            //Ако сме в текстов режим, рендираме txt
            $tpl = new ET(tr(getFileContent('email/tpl/SingleLayoutOutgoings.txt')));
        } else {
            //Ако не сме в нито един от посоченитеРендираме html
            $tpl = new ET(tr(getFileContent('email/tpl/SingleLayoutOutgoings.shtml')));
        }
        
        return $tpl;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
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
        $pdfs = doc_RichTextPlg::getPdfs($rec->body);
        
        $attachments = ((is_array($pdfs) ? (array_merge($files, $pdfs)) : $files));
                
        return $attachments;
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
        
        $row->title = $subject;
        
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        $row->authorId = $rec->createdBy;
        
        $row->state = $rec->state;
        
        return $row;
    }
    
    //    /**
    //     * Потребителите, с които е споделен този документ
    //     *
    //     * @return string keylist(mvc=core_Users)
    //     * @see doc_DocumentIntf::getShared()
    //     */
    //    function getShared($id)
    //    {
    ////        return static::fetchField($id, 'sharedUsers');
    //    }
    
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    function on_AfterSetupMVC($mvc, &$res)
    {
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('Postings', 'Прикачени файлове в постингите', NULL, '300 MB', 'user', 'user');
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща данните за адресанта
     */
    function getContragentData($id)
    {
        $posting = email_Outgoings::fetch($id);
        
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
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        //Добавяме бутона, ако състоянието не е чернова
        if (($data->rec->state != 'draft') && ($data->rec->state != 'rejected')) {
            $retUrl = array($mvc, 'single', $data->rec->id);
            $data->toolbar->addBtn('Изпращане', array('email_Outgoings', 'send', $data->rec->id, 'ret_url'=>$retUrl), 'class=btn-email-send');
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
}
