<?php 


/**
 * Текста, който ще се показва в хедърната част на постингите
 */
defIfNot('BGERP_POSTINGS_HEADER_TEXT', '|*Препратка|');


/**
 * Ръчен постинг в документната система
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Stefan Stefanov <stefan.bg@gmail.com> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class email_Outgoings extends core_Master
{
    
    
    /**
     * Полета, които ще се клонират
     */
    var $cloneFields = 'subject, body, recipient, attn, email, phone, fax, country, pcode, place, address';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * За конвертиране на съществуащи MySQL таблици от предишни версии
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
     * Кой има права за имейли-те?
     */
    var $canEmail = 'admin, email';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'email_Wrapper, doc_DocumentPlg, plg_RowTools, 
        plg_Printing, email_plg_Document, doc_ActivatePlg';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'doc/tpl/SingleLayoutPostings.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/email_edit.png';
        
    
    /**
     * Абривиатура
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
        $this->FLD('body', 'richtext(rows=10,bucket=Postings)', 'caption=Съобщение,mandatory');
        
        //Данни за адресанта
        $this->FLD('recipient', 'varchar', 'caption=Адресант->Фирма');
        $this->FLD('attn', 'varchar', 'caption=Адресант->Лице,oldFieldName=attentionOf');
        $this->FLD('email', 'email', 'caption=Адресант->Имейл');
        $this->FLD('phone', 'varchar', 'caption=Адресант->Тел.');
        $this->FLD('fax', 'varchar', 'caption=Адресант->Факс');
        $this->FLD('country', 'varchar', 'caption=Адресант->Държава');
        $this->FLD('pcode', 'varchar', 'caption=Адресант->П. код');
        $this->FLD('place', 'varchar', 'caption=Адресант->Град/с');
        $this->FLD('address', 'varchar', 'caption=Адресант->Адрес');
    }
    
    
    function act_Send()
    {
        $this->requireRightFor('send');
        
        $data = new stdClass();
        
        // Създаване и подготвяне на формата
        $this->prepareSendForm($data);
        
        // Подготвяме адреса за връщане, ако потребителя не е логнат.
        // Ресурса, който ще се зареди след логване обикновено е страницата, 
        // от която се извиква екшъна act_Manage
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
            
            $status = email_Sent::send($data->rec, $data->form->rec);
            
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
        
        // Подготвяме тулбара на формата
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
        $data->form->setDefault('boxFrom', email_Inboxes::getCurrentUserInbox());
        $data->form->setDefault('emailTo', $data->rec->email);
        $filesArr = $mvc->getAttachments($data->rec);
        if(count($filesArr) == 0) {
            $data->form->setField('attachments', 'input=none');
        } else {
            $data->form->setSuggestions('attachments', $filesArr);
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

                if($deconverted  != $html ) {
                    $form->setWarning('encoding', 'Писмото съдържа символи, които не могат да се конвертират към|* ' . 
                        $form->fields['encoding']->type->toVerbal($rec->encoding));
                }
            }
        }
    }

    
    
    /**
     *  Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
    }
    
    
    function on_AfterSave($mvc, $id, $rec)
    {
    }

    
    /**
     * Връща plain-текста на писмото
     */
    function getEmailText($rec, $lg)
    {
        core_Lg::push($lg);
        Mode::push('text', 'plain');
        
        $tpl = new ET(tr(getFileContent('email/tpl/Email.txt')));
        $row = $this->recToVerbal($rec, 'subject,body,attn,email,country,place,recipient,modifiedOn,handle');
        $row->subject = mb_strtoupper(type_Text::formatTextBlock($row->subject, 76, 0));
        $tpl->placeObject($row);
        

        Mode::pop('text');
        core_Lg::pop();
        
        return $tpl->getContent();
    }


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
        $res = '<div id="begin">' . $res->getContent() . '<div id="end">';
        $res = csstoinline_Emogrifier::convert($res, getFileContent('css/wideCommon.css') . "\n" . getFileContent('css/wideApplication.css'));
        $res = str::cut($res, '<div id="begin">', '<div id="end">');
 
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

        $form->toolbar->addSbBtn('Изпрати', 'sending', array('class' => 'btn-send', 'order'=>'10'));
              
        //Ако добавяме нови данни
        if (!$rec->id) {
            
            //Ако имаме originId и добавяме нов запис
            if ($rec->originId) {
                
                //Ако създаваме копие, връщаме управлението
                if (Request::get('clone')) return;
                
                //Добавяме в полето Относно отговор на съобщението
                $oDoc = doc_Containers::getDocument($rec->originId);
                $oRow = $oDoc->getDocumentRow();
                $rec->subject = 'RE: ' . html_entity_decode($oRow->title);
                
                //Данните на получателя
                $contragentData = doc_Threads::getContragentData($rec->threadId);
                
            } else {
                
                //Ако нямаме originId, а имаме emailto
                if ($emailTo = Request::get('emailto')) {
                    if ($folderId = email_Router::getEmailFolder($emailTo)) {
                        
                        //Ако имаме права за запис в нея
                        if (doc_Folders::haveRightFor('single', $folderId)) {
                            
                            //Променяме папката по подразбиране
                            $rec->folderId = $folderId;  
                            
                            //Данните за избраната папка
                            $folder = doc_Folders::fetch($folderId);
                            
                            //id' то на cover' а на папката
                            $coverClass = $folder->coverClass;
                            
                            //id на данните на ковъра
                            $coverId = $folder->coverId;    
                        }
                    }

                    if ($coverClass) {
                        
                        //Името на класа, в който се намират документите
                        $className = cls::getClassName($coverClass); 
                        
                        //Вземаме данните на потребителя
                        $contragentData = $className::getRecipientData($emailTo, $coverId);     
                    } else {
                        
                        //Вземаме данните от контакти->фирма
                        $contragentData = crm_Companies::getRecipientData($emailTo);
                        
                        //Ако няма контакти за фирма, вземаме данние от контакти->Лица
                        if (!$contragentData) {
                            $contragentData = crm_Persons::getRecipientData($emailTo);
                        }
                    }
                    
                    //Имейла, който сме натиснали
                    $contragentData->email = $emailTo;
                    
                    $fRec = doc_Folders::fetch($rec->folderId);
                    $fRow = doc_Folders::recToVerbal($fRec);
                    $data->form->title = '|*' . $mvc->singleTitle . ' |в|* ' . $fRow->title ;

                }
            }
            
            //Данни необходими за създаване на хедъра на съобщението
            $contragentDataHeader['name'] = $contragentData->attn;
            $contragentDataHeader['salutation'] = $contragentData->salutation;
            
            //Създаваме тялото на постинга
//            $rec->body = $this->createDefaultBody($contragentDataHeader, $rec->originId, $rec->threadId, $rec->folderId);
            
            //Ако сме открили някакви данни за получателя
            if (count((array)$contragentData)) {
                
                //Заместваме данните в полетата с техните стойности
                $rec->recipient = $contragentData->company;
                $rec->attn = $contragentData->attn;
                $rec->phone = $contragentData->phone;
                $rec->fax = $contragentData->fax;
                $rec->country = $contragentData->country;
                $rec->pcode = $contragentData->pcode;
                $rec->place = $contragentData->place;
                $rec->address = $contragentData->address;
                $rec->email = $contragentData->email;
            }
        }
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
        core_Lg::set($lg);
        
        //Хедър на съобщението
        $header = $this->getHeader($HeaderData);
        
        //Текста между заглавието и подписа
        $body = $this->getBody($originId); 
        
        //Футър на съобщението
        $footer = $this->getFooter();
        
        //Текста по подразбиране в "Съобщение"
        $defaultBody = $header . "\n\n" . $body ."\n\n" . $footer;
        
        //След превода връщаме стария език
        core_Lg::set($oldLg);
        
        return $defaultBody;
    }
    
    
    /**
     * Създава хедър към постинга
     */
    function getHeader($data)
    {
        $tpl = new ET(tr(getFileContent("doc/tpl/OutgoingHeader.shtml")));
        
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
        
        //Ако класа имплементира интерфейса "doc_ContragentDataIntf", тогава извикваме метода, който ни връща тялото на имейла
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
        
        $tpl = new ET(tr(getFileContent("doc/tpl/OutgoingFooter.shtml")));
        
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
    function on_AfterPrepareSingle($mvc, $data)
    {
        $data->row->iconStyle = 'background-image:url(' . sbf($mvc->singleIcon) . ');';
        
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
    function on_AfterRenderSingleLayout($mvc, $tpl, &$data)
    { 
        //Полета за адресанта   
        $allData = $data->row->recipient . $data->row->attn . $data->row->email . $data->row->phone .
        $data->row->fax . $data->row->country . $data->row->pcode . $data->row->place . $data->row->address;
        $allData = str::trim($allData);
        
        //Ако нямаме въведени данни за адресанта, тогава не показваме антетката
        if (!$allData) {
            
            //$data->row->subject = NULL;
            $data->row->createdDate = NULL;
            $data->row->handle = NULL;
        }
        
        if (Mode::is('text', 'plain')) {
            $tpl = new ET(tr(getFileContent('doc/tpl/SingleLayoutPostings.txt')));
        } else {
            $tpl = new ET(tr(getFileContent('doc/tpl/SingleLayoutPostings.shtml')));
        }
        
        $tpl->replace(static::getBodyTpl(), 'DOC_BODY');
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
     * Шаблон за тялото на съобщение в документната система.
     *
     * Използва се в този клас, както и в blast_Emails
     *
     * @return ET
     */
    static function getBodyTpl()
    {
        if (Mode::is('text', 'plain')) {
            $tpl = new ET(tr(getFileContent('doc/tpl/SingleLayoutPostingsBody.txt')));
        } else {
            $tpl = new ET(tr(getFileContent('doc/tpl/SingleLayoutPostingsBody.shtml')));
        }
        
        return $tpl;
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
     * До кой е-мейл или списък с е-мейли трябва да се изпрати писмото
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
    function on_AfterSetupMVC($mvc, $res)
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
        
        $contrData->recipient = $posting->recipient;
        $contrData->attn = $posting->attn;
        $contrData->phone = $posting->phone;
        $contrData->fax = $posting->fax;
        $contrData->country = $posting->country;
        $contrData->pcode = $posting->pcode;
        $contrData->place = $posting->place;
        $contrData->address = $posting->address;
        $contrData->email = $posting->email;
        
        return $contrData;
    }
    
    
    /**
     * Добавя бутон за Изпращане в сингъл вюто
     * @param stdClass $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareSingleToolbar($mvc, $res, $data)
    {
        //Добавяме бутона, ако състоянието не е чернова
        if (($data->rec->state != 'draft') && ($data->rec->state != 'rejected')) {
            $retUrl = array($mvc, 'single', $data->rec->id);
            $data->toolbar->addBtn('Изпращане', array('email_Outgoings', 'send', $data->rec->id, 'ret_url'=>$retUrl), 'class=btn-email-send');    
        }
    }
}
