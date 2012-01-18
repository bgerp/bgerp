<?php 


/**
 * Ръчен постинг в документната система
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Postings extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf';
    
    
    
    /**
     * Заглавие
     */
    var $title = "Постинги";
    
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Коментар";
    
    
    
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
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';
    
    
    var $canEmail = 'admin, email';
    
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'doc_Wrapper, doc_DocumentPlg, plg_RowTools, 
        plg_Printing, email_plg_Document, doc_ActivatePlg';
    
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'doc/tpl/SingleLayoutPostings.shtml';
    
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/doc_text_image.png';
    
    var $currentTab = 'doc_Containers';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('subject', 'varchar', 'caption=Относно,mandatory,width=100%');
        $this->FLD('body', 'richtext(rows=10,bucket=Postings)', 'caption=Съобщение,mandatory');
        $this->FLD('recipient', 'varchar', 'caption=Адресант->Фирма');
        $this->FLD('attn', 'varchar', 'caption=Адресант->Лице,oldFieldName=attentionOf');
        $this->FLD('email', 'email', 'caption=Адресант->Имейл');
        $this->FLD('phone', 'varchar', 'caption=Адресант->Тел.');
        $this->FLD('fax', 'varchar', 'caption=Адресант->Факс');
        $this->FLD('country', 'varchar', 'caption=Адресант->Държава');
        $this->FLD('pcode', 'varchar', 'caption=Адресант->П. код');
        $this->FLD('place', 'varchar', 'caption=Адресант->Град/с');
        $this->FLD('address', 'varchar', 'caption=Адресант->Адрес');
        $this->FLD('sharedUsers', 'keylist(mvc=core_Users,select=nick)', 'caption=Споделяне->Потребители');
    }
    
        
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec = $data->form->rec;
           
        //Ако имаме originId
        if ($rec->originId) {
            
            //Добавяме в полето Относно отговор на съобщението
            $oDoc = doc_Containers::getDocument($rec->originId);
            $oRow = $oDoc->getDocumentRow();
            $rec->subject = 'RE: ' . $oRow->title;
            
            //Взема документа, от който е постинга
            $document = doc_Containers::getDocument($rec->originId);

            //Вземаме данните за потребителя
            $recepientData = $document->getContragentData($rec->originId);
            
            if ($recepientData) {
                $rec->recipient = $recepientData->name;
                $rec->attn = $recepientData->attn;
                $rec->phone = $recepientData->tel;
                $rec->fax = $recepientData->fax;
                $rec->country = crm_Companies::getVerbal($recepientData, 'country');
                $rec->pcode = $recepientData->pCode;
                $rec->place = $recepientData->place;
                $rec->address = $recepientData->address;
                $rec->email = $recepientData->email;
            }
        } else {
            $emailTo = Request::get('emailto');
    
            //Проверяваме дали е валиден имейл
            if (type_Email::isValidEmail($emailTo)) {
                //Вземаме данните от визитката
                $query = crm_Companies::getQuery();
                $query->where("#email LIKE '%{$emailTo}%'");
                $query->orderBy('createdOn');
                
                while (($company = $query->fetch()) && (!$find)) {
                    //Ако има права за single
                    if(!crm_Companies::haveRightFor('single', $company)) {
                        
                        continue;    
                    }
                    
                    $pattern = '/[\s,:;\\\[\]\(\)\>\<]/';
                    $values = preg_split( $pattern, $company->email, NULL, PREG_SPLIT_NO_EMPTY);
    
                    //Проверяваме дали същия емайл го има въведено в модела
                    if (count($values)) {
                        foreach ($values as $val) {
                            if ($val == $emailTo) {
                                $rec->recipient = $company->name;
                                //$rec->attn = $company->; //TODO няма поле за име?
                                $rec->phone = $company->tel;
                                $rec->fax = $company->fax;
                                $rec->country = crm_Companies::getVerbal($company, 'country');
                                $rec->pcode = $company->pCode;
                                $rec->place = $company->place;
                                $rec->address = $company->address;
                                
                                //Форсираме папката
                                $rec->folderId = crm_Companies::forceCoverAndFolder($company);
    
                                $find = TRUE;
                                
                                break;
                            }
                        }
                    }
                }
                
                $rec->email = $emailTo;
            }
        }
    }
    
    
    /**
     * Преди вкарване на записите в модела
     */
    function on_BeforeSave($mvc, $id, &$rec)
    {
        if ((stripos($rec->body, '[#sign#]') !== FALSE) || (stripos($rec->body, '[#podpis#]') !== FALSE)) {
            $footer = $this->getFooter();
            $rec->body = str_ireplace('[#sign#]', $footer, $rec->body);
            $rec->body = str_ireplace('[#podpis#]', $footer, $rec->body);
        }
    }
    
    
    /**
     * Добавя футър към постинга
     */
    function getFooter()
    {
        //Зареждаме текущия език
        $lang = core_Lg::getCurrent();
        
        //Зареждаме класа, за да имаме достъп до променливите
        cls::load('crm_Companies');
        
        $companyId = BGERP_OWN_COMPANY_ID;
        
        //Вземаме данните за нашата фирма
        $myCompany = crm_Companies::fetch($companyId);
        
        $userName = core_Users::getCurrent('names');
        
        //Ако езика е на български да не се показва държавата
        if (strtolower($lang) != 'bg') {
           $country = crm_Companies::getVerbal($myCompany, 'country');
        }
               
        $tpl = new ET(tr(getFileContent("doc/tpl/GreetingPostings.shtml")));
        
        //Заместваме шаблоните
        $tpl->replace($userName, 'name');
        $tpl->replace($country, 'country');
        $tpl->replace($myCompany->pCode, 'pCode');
        $tpl->replace($myCompany->place, 'city');
        $tpl->replace($myCompany->address, 'street');
        $tpl->replace($myCompany->name, 'company');
        $tpl->replace($myCompany->tel, 'tel');
        $tpl->replace($myCompany->fax, 'fax');
        $tpl->replace($myCompany->email, 'email');
        $tpl->replace($myCompany->website, 'website');
        
        $footer = $this->clearEmptyLines($tpl->getContent());
        
        return $footer;
    }
    
        
    /**
     * Изчиства празните линии
     */
    function clearEmptyLines($content)
    {
        $arrContent = explode("\n", $content);
        
        if (is_array($arrContent)) {
            foreach ($arrContent as $value) {
                if (!str::trim($value)) continue;
                
                $clearContent .= $value . "\r\n";
            }
        }
        
        $clearContent = str::trim($clearContent);
        
        return $clearContent;
    }
    
    
    /**
     * Подготвя иконата за единичния изглед
     */
    function on_AfterPrepareSingle($mvc, $data)
    {
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
            //Темата е на мястото на singleTitle
            $data->row->singleTitle = $data->row->subject;
            
            $data->row->subject = NULL;
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
    
    /******************************************************************************************
     *
     * ИМПЛЕМЕНТАЦИЯ НА email_DocumentIntf
     * 
     ******************************************************************************************/
    
    
    
    /**
     * Прикачените към документ файлове
     *
     * @param int $id ид на документ
     * @return array
     */
    public function getEmailAttachments($id)
    {
        
        /**
         * @TODO
         */
        return array();
    }
    
    
    
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
     * @return int key(email_Messages) NULL ако документа не е изпратен като отговор
     */
    public function getInReplayTo($id)
    {
        
        /**
         * @TODO
         */
        return NULL;
    }
    
    
    
    
    /**
     * ИМПЛЕМЕНТАЦИЯ НА @link doc_DocumentIntf
     */
    public function getHandle($id)
    {
        return 'T' . $id;
    }
    
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
    
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    function on_AfterSetupMVC($mvc, $res)
    {
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('Postings', 'Прикачени файлове в постингите', NULL, '300 MB', 'user', 'user');
    }
}