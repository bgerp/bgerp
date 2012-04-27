<?php 


/**
 * Домейн на услугата имейл към факс
 */
defIfNot('BGERP_FAX_DOMEIN', 'fax.domein.com'); //TODO


/**
 * Създаване и изпращане на факсове
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Faxes extends core_Master
{
    
    
    /**
     * Полета, които ще се клонират
     */
    var $cloneFields = 'subject, body, recipient, attn, email, tel, fax, country, pcode, place, address';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, doc_ContragentDataIntf';
    
        
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
    var $canSingle = 'admin, user';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'admin, user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, user';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin, user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, user';
    
    
    /**
     * Кой може да изпраща факс?
     */
    var $canSend = 'admin, user';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой има права за
     */
    var $canFax = 'admin, user';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'doc_Wrapper, doc_DocumentPlg, plg_RowTools, 
        plg_Printing, doc_ActivatePlg, bgerp_plg_Blank';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'doc/tpl/SingleLayoutFax.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/fax.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'FAX';
    
    
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
        $this->FLD('email', 'emails', 'caption=Адресант->Имейл');
        $this->FLD('tel', 'varchar', 'caption=Адресант->Тел.,oldFieldName=phone');
        $this->FLD('fax', 'varchar', 'caption=Адресант->Факс');
        $this->FLD('country', 'varchar', 'caption=Адресант->Държава');
        $this->FLD('pcode', 'varchar', 'caption=Адресант->П. код');
        $this->FLD('place', 'varchar', 'caption=Адресант->Град/с');
        $this->FLD('address', 'varchar', 'caption=Адресант->Адрес');
    }
    
    
    /**
     * Екшън за изпращане на факс
     * 
     * @return core_ET static::renderWrapping($tpl)
     */
    function act_Send()
    {
        expect(BGERP_FAX_DOMEIN, 'Не е дефинирана константата.');
        
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
        
        $lg = doc_Faxes::getLanguage($data->rec->containerId, NULL, NULL);

        // Ако формата е успешно изпратена - изпращане, лог, редирект
        if ($data->form->isSubmitted()) {
            
            $data->rec->text = $this->getFaxText($data->rec, $lg);
            
            //Всички документи, които са избрани
            $docsArr = type_Set::toArray($data->form->rec->documents);
            
            //Емулираме текстов режим, за да вземем текстовата част
            Mode::push('text', 'plain');
            
            $docsArrCnt = count($docsArr);
                        
            //Обхождаме избрани документи
            foreach ($docsArr as $docName) {

                //Вземаме тялото на документа
                $document = doc_DocumentPlg::getDocumentBody($docName);
                
                //Ако има такъв документ
                if ($document) {
                    
                    //Създаваме променлива за разделяне на два документа
                    if (!$line) {
                        $line = "\n\n" . str_repeat('-', 70) . "\n\n";
                    }
                    
                    //Вземаме документите
                    $docsStr .= ($docsStr) ? $line . $document->getContent() : $document->getContent();    
                }
            }
            
            $data->rec->text = $data->rec->text->getContent();
            
            if ($line) {
                //Добавяме към текстовата част на факса всички избрани документи
                $data->rec->text .= $line .tr("Документи:") . $line . $docsStr;
            }
            
            //Връщаме старата стойност на текстовия режим
            Mode::pop('text');
            
            bp($data->rec->text);
            //TODO
//
//            $status = email_Sent::send(
//                $data->form->rec->containerId,
//                $data->form->rec->threadId,
//                $data->form->rec->boxFrom,
//                $data->form->rec->emailsTo,
//                $data->rec->subject,
//                $data->rec,
//                array(
//                    'encoding' => $data->form->rec->encoding
//                )
//            );
//            
//            $msg = $status ? 'Изпратено' : 'ГРЕШКА при изпращане на писмото';
//            
//            // Правим запис в лога
//            $this->log('Send', $data->rec->id);
//            
//            // Подготвяме адреса, към който трябва да редиректнем,  
//            // при успешно записване на данните от формата
//            $data->form->rec->id = $data->rec->id;
//            $this->prepareRetUrl($data);
//            
//            // $msg е съобщение за статуса на изпращането
//            return new Redirect($data->retUrl, $msg);
        } else {
            // Подготвяме адреса, към който трябва да редиректнем,  
            // при успешно записване на данните от формата
            $this->prepareRetUrl($data);
        }
                
        // Получаваме изгледа на формата
        $tpl = $data->form->renderHtml();
        
        // Добавяме превю на факса, който ще изпратим
        $preview = new ET("<div style='display:table'>
            					<div style='margin-top:20px; margin-bottom:-10px; padding:5px;'>
            						<b>" . tr("Факс") . "</b>
        						</div>
        						
        						<pre class=\"document\">[#FAX_TEXT#]</pre>
    							
        						[#DOCS#]
    							
    						</div>");
        
        //Текстовата част на факса
        $faxText = $this->getFaxText($data->rec, $lg);
        
        //Добавяме към шаблона
        $preview->append($faxText, 'FAX_TEXT');
        
        //Документите във факса
        $docHandlesArr = doc_RichTextPlg::getAttachedDocs($faxText);
        
        //Ако има документи
        if (count($docHandlesArr)) {
            
            //Емулираме текстов режим, за да вземем текстовата част
            Mode::push('text', 'plain');
            
            foreach ($docHandlesArr as $docName) {
                
                //Шаблона за документи
                $docs = new ET("
                	<div style='margin-top:20px; margin-bottom:-10px; padding:5px;'>
						<b>" . tr("Документ") . ": [#DOC_NAME#]". "</b>
					
						</div>
					
					<pre class=\"document\">[#DOC_TEXT#]</pre>
                ");
                
                //Вземаме тялото на документа
                $document = doc_DocumentPlg::getDocumentBody($docName);
                
                //Заместваме шаблона
                $docs->replace($document, 'DOC_TEXT');
                $docs->replace($docName, 'DOC_NAME');
                
                //Добавяме шаблона с документа, към шаблона с изгледа на факса
                $preview->append($docs, 'DOCS');
            }
            
            //Връща предишния режим
            Mode::pop('text');
        }
        
        //Добавяме изгледа към главния шаблон
        $tpl->append($preview);

        return static::renderWrapping($tpl);
    }
    
    
	/**
     * След рендиране на singleLayout заместваме плейсхолдера
     * с шаблонa за тялото на съобщение в документната система
     * 
     * @param stdClass $data - Обект с данните
     * 
     * @return core_ET $tpl - Шаблон
     */
    function renderSingleLayout_(&$data)
    {
        if (!Mode::is('text', 'plain')) {
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
                
                //Ако имаме До: и Държава, и нямаме адресни данни, тогава добавяме държавата след фирмата
                if ($data->row->recipient) {
                    $data->row->firmCountry = $data->row->country;
                }
                
                //Не се показва и държавата
                unset($data->row->country);
                
                $telFax = $data->row->tel . $data->row->fax;
                $telFax = str::trim($telFax);
                
                //Имейла е само в дясната част, преместваме в ляво
                if (!$telFax) {
                    $data->row->emailLeft = $data->row->email;
                    unset($data->row->email);
                }
            }        
        }
        
        //Рендираме шаблона
        if (!Mode::is('text', 'plain')) {
            //Ако сме в xhtml (изпращане) режим, рендираме шаблона за изпращане
            $tpl = new ET(tr('|*' . getFileContent('doc/tpl/SingleLayoutFax.shtml')));
        } else {
            //Ако не сме в нито един от посоченитеРендираме html
            $tpl = new ET(tr('|*' . getFileContent('doc/tpl/SingleLayoutFax.txt')));
        }
        
        return $tpl;
    }
    
    
   /**
     * Подготовка на формата за изпращане
     * Самата форма се взема от email_Sent
     * 
     * @param stdClass $data - Данните за формата
     * 
     * @return stdClass $data - Променените данни на формата
     */
    function prepareSendForm_($data)
    {
        $data->form = email_Sent::getForm();
        $data->form->setAction(array($mvc, 'send'));
        $data->form->title = 'Изпращане на факс';
        
        //Добавяме поле за факса на получателя
        $data->form->FNC(
            'faxTo',
            'drdata_PhoneType',
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
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     * 
	 * @param core_Manager $mvc  - 
     * @param stdClass     $data - Данните от формата
     */
    static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec = $data->form->rec;
        $form = $data->form;
        
        //Ако субмитнем формата, кода не се изпълнява
        if ($form->isSubmitted()) return;
        
        //Добавяме бутона изпрати
        $form->toolbar->addSbBtn('Изпрати', 'sending', array('class' => 'btn-send', 'order'=>'10'));
        
        //Ако редактираме записа или го клонираме, няма да се изпълни нататък
        if (($rec->id) || (Request::get('Clone'))) return;
        
        //Зареждаме нужните променливи от $data->form->rec
        $originId = $rec->originId;
        $threadId = $rec->threadId;
        $folderId = $rec->folderId;        
        
        //Определяме треда от originId
        if($originId && !$threadId) {
            $threadId = doc_Containers::fetchField($originId, 'threadId');
        }
        
        //Определяме папката от треда
        if($threadId && !$folderId) {
            $folderId = doc_Threads::fetchField($threadId, 'folderId');
        }
        
        //Ако е отговор на, тогава по подразбиране попълваме полето относно
        if ($originId) {
            //Добавяме в полето Относно отговор на съобщението
            $oDoc = doc_Containers::getDocument($originId);
            $oRow = $oDoc->getDocumentRow();
            $rec->subject = 'За: ' . html_entity_decode($oRow->title);
        }

        //Определяме езика на който трябва да е факса
        $lg = doc_Faxes::getLanguage($originId, $threadId, $folderId);
        
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
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc - 
     * @param stdClass     $row - Това ще се покаже
     * @param stdClass     $rec - Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->handle = $mvc->getHandle($rec->id);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
	 * @param core_Manager $mvc  - 
     * @param stdClass $form - Данните от формата
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $mvc->flagSendIt = ($form->cmd == 'sending');
            
            //Ако изпращаме директно факса от формата, тогава го активираме
            if ($mvc->flagSendIt) {
                $form->rec->state = 'active';
            }
        }
    }
    
    
    /**
     * Добавя бутон за изпращане на факса
     * 
	 * @param core_Manager $mvc  - 
	 * @param stdClass     $res  - 
     * @param stdClass     $form - Данните от формата
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        //Ако сме въвели домейн за изпращане на факс
        if (BGERP_FAX_DOMEIN) {
            //Бутон за изпращане на факс
            if (($data->rec->state != 'draft') && ($data->rec->state != 'rejected')) {
                
                if ($mvc->haveRightFor('fax')) {
                    $retUrl = array($mvc, 'single', $data->rec->id);
                    
                    // Бутон за изпращане на факс
                    $data->toolbar->addBtn('Изпращане', array(    
                                $mvc,
                                'send',
                                $data->rec->id,
                                'ret_url'=>$retUrl
                            ),
                        'class=btn-fax'
                    );
                }  
            }    
        }
    }
    
    
	/**
     * Извиква се след подготовката на формата за изпращане
     * 
     * @param core_Manager $mvc  - 
     * @param stdClass     $data - Данните от формата
     */
    static function on_AfterPrepareSendForm($mvc, &$data)
    {
        expect($data->rec = $mvc->fetch($data->form->rec->id));

        // Трябва да имаме достъп до нишката, за да можем да изпращаме писма от нея
        doc_Threads::requireRightFor('single', $data->rec->threadId);
        
        $data->form->setDefault('containerId', $data->rec->containerId);
        $data->form->setDefault('threadId', $data->rec->threadId);
        
        //Факса, от който ще се изпрати
        $data->form->setOptions('boxFrom', array(1=>self::getMyFaxDomein())); //TODO @fixme
                
        //Документите във факса
        $docHandlesArr = doc_RichTextPlg::getAttachedDocs($data->rec->body);
                
        if(count($docHandlesArr) == 0) {
            
            //Ако нямаме нито един документ, не се показва полето за документи
            $data->form->setField('documents', 'input=none');    
        } else {
            
            //Задаваме на формата да се покажат полетата
            $data->form->setSuggestions('documents', $docHandlesArr);
        }
        
        $data->form->setDefault('faxTo', $data->rec->fax);
        
        //Полето за прикачени файлове да не се показва
        $data->form->setField('attachments', 'input=none');   
    }
    
    
    /**
     * Проверка на входните параметри от формата за изпращане
     * 
	 * @param core_Manager $mvc  - 
     * @param stdClass     $form - Данните от формата
     */
    static function on_AfterInputSendForm($mvc, $form)
    {
        if($form->isSubmitted()) {
            $rec = $form->rec;
            
            //Ако енкодинга е различен от UTF8 и LAT
            if($form->rec->encoding != 'utf8' && $form->rec->encoding != 'lat') {
                
                //Оригиналния текст
                $text = (string) $rec->text;
                
                //Конвертирания текст
                $converted = iconv('UTF-8', $rec->encoding, $text);
                
                //Деконвертирания текст
                $deconverted = iconv($rec->encoding, 'UTF-8', $converted);
                
                //Проверяваме дали деконвертирания стринг съвпада с оригиналния
                if($deconverted  != $text) {
                    $form->setWarning('encoding', 'Факсът съдържа символи, които не могат да се конвертират към|* ' .
                        $form->fields['encoding']->type->toVerbal($rec->encoding));
                }
            }
        }
    }

    
	/**
     * След записване на данни в БД
     * 
     * @param core_Manager $mvc - 
     * @param integer      $id  - id' то на записа
     * @param stdClass     $rec - Обект със записаните стойности
     */
    static function on_AfterSave($mvc, $id, $rec)
    {
        //Ако сме изпратили факса директно от формата
        if ($mvc->flagSendIt) {
            
            //Езика
            $lg = doc_Faxes::getLanguage($data->rec->containerId, NULL, NULL);
            
            //Тялото на факса
            $body = (object)array(
                'text' => $mvc->getFaxText($rec, $lg)
            );
            
            //TODO
//            $mvc->sendStatus = email_Sent::send(
//                $rec->containerId,
//                $rec->threadId,
//                email_Inboxes::getUserEmailId(),//TODO
//                $rec->email,//TODO
//                $rec->subject,
//                $body,
//                array(
//                    'encoding' => 'utf-8'
//                )
//            );
        }
    }
    
    
    /**
     * Връща текста на факса
     * 
     * @param stdClass $rec - Обект с данните за факса
     * @param string $lg  - Езика на факса
     * 
     * @return core_ET $res - Шаблон на текста
     */
    function getFaxText($rec, $lg)
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
        Mode::push('text', 'plain');
        
        // Подготвяме данните за единичния изглед
        $this->prepareSingle($data);
        
        // Рендираме изгледа
        $res = $this->renderSingle($data);
                
        // Връщаме старата стойност на 'printing'
        Mode::pop('text');
        Mode::pop('printing');
        core_Lg::pop();
        
        return $res;
    }
    
    
	/**
     * Създава тялото на факса
     * 
     * @param array   $headerData - Данните на контрагента
     * @param integer $originId   - id' то на контейнера
     * 
     * @return $string $defaulBody - Тялото на факса, по подразбиране
     */
    function createDefaultBody($headerData, $originId)
    {
        //Хедър на съобщението
        $header = $this->getHeader($headerData);
        
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
     * 
     * @param array $data - Данните на контрагента
     * 
     * @return string $tpl->getContent() - Стинг
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
     * 
     * @param integer $originId - id' то на документа
     * 
     * @return string $body
     */
    function getBody($originId)
    {
        if (!$originId) return ;
        
        //Вземаме класа, за който се създава съответния факс
        $document = doc_Containers::getDocument($originId);
        
        //Името на класа
        $className = $document->className;
        
        //Ако класа имплементира интерфейса "doc_ContragentDataIntf", тогава извикваме метода, който ни връща тялото на факса-а
        if (cls::haveInterface('doc_ContragentDataIntf', $className)) {
            $body = $className::getDefaultEmailBody($document->that);
        }
        
        return $body;
    }
    
    
    /**
     * Създава футър към постинга в зависимост от типа на съобщението
     * 
     * @return string $tpl->getContent() - Стринг
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
     * Намира предполагаемия езика на който трябва да отговорим
     *
     * @param int $originId - id' то на контейнера
     * @param int $threadId - id' то на нишката
     * @param int $folderId  -id' то на папката
     *
     * @return string $lg - Двубуквеното означение на предполагаемия език на факса
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
     * Връща факса на фирмата
     * 
     * @return string $fax - Факса на фирмата
     */
    static function getMyFaxDomein()
    {
        //Очаква да има домейн за факс
        expect(BGERP_FAX_DOMEIN, 'Не е дефинирана константата.');
        
        //Вземаме id' то на нашата фирма
        core_cls::get('crm_Companies');
        $companyId = BGERP_OWN_COMPANY_ID;
        
        //Вземаме факса на нашата фирма
        $myCompanyFax = crm_Companies::fetchField($companyId, 'fax');
        
        //Очакваме да имаме въведен номер на факс
        expect($myCompanyFax, 'Няма добавен факс номер на компанията Ви.');
        
        //Образуваме домейна на факса
        $fax = $myCompanyFax . '@' . BGERP_FAX_DOMEIN;
        
        return $fax;
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща данните за адресанта
     * 
     * @param integer $id - id' то на записа
     * 
     * @return stdClass $contrData - Обект с данните на контрагента
     */
    static function getContragentData($id)
    {
        $posting = doc_Faxes::fetch($id);
        
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
	 * Връща данните на създателя на записа
	 * 
	 * @param integet $id - id' то на записа
	 * 
	 * @return stdClass $row - Обект със стойности
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
     * 
     * @param core_Manager $mvc - 
     * @param string       $res - Стринга, който ще се показва
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        //Инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('Fax', 'Прикачени файлове във факсовете', NULL, '300 MB', 'user', 'user');
    }
}