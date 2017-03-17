<?php



/**
 * Клас 'blast_Lists' -
 *
 * Списъци за масово разпращане
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Списъци с контакти
 */
class blast_Lists extends core_Master
{
    
    
    /**
     * Име на папката по подразбиране при създаване на нови документи от този тип.
     * Ако стойноста е 'FALSE', нови документи от този тип се създават в основната папка на потребителя
     */
    var $defaultFolder = 'Списъци за разпращане';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'blast_ListDetails';
    
    
    /**
     * Кой има право да клонира?
     */
    public $canClonerec = 'ceo, blast, admin';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'blast_Wrapper,plg_RowTools2,doc_DocumentPlg, plg_Search, 
                     bgerp_plg_Blank, plg_Clone';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Заглавие
     */
    var $title = "Списъци за изпращане на циркулярни имейли, писма, SMS-и, факсове и др.";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Списък с контакти';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'bgerp_PersonalizationSourceIntf, doc_DocumentIntf';
    
    
    /**
     * Кой може да чете?
     */
    var $canRead = 'blast,ceo,admin';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'blast,ceo,admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'blast,ceo,admin';
    
    
    /**
     * Кой може да го възстанови?
     */
    var $canRestore = 'blast,ceo,admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'blast,ceo,admin';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'blast,ceo,admin';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'blast_ListDetails';
	
	
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/address-book.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'Bls';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'blast/tpl/SingleLayoutLists.shtml';
    
    
    /**
     * Поле за търсене
     */
    var $searchFields = 'title, keyField, contactsCnt, folderId, threadId, containerId ';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "2.1|Циркулярни";
    
    
    /**
     * Да се показва антетка
     */
    public $showLetterHead = TRUE;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Информация за папката
        $this->FLD('title' , 'varchar', 'caption=Заглавие,width=100%,mandatory');
        $this->FLD('keyField', 'enum(email=Имейл,mobile=Мобилен,fax=Факс,names=Лице,company=Фирма,uniqId=№)', 'caption=Ключ,width=100%,mandatory,hint=Kлючовото поле за списъка');
        $this->FLD('fields', 'text', 'caption=Полета,width=100%,mandatory,hint=Напишете името на всяко поле на отделен ред,column=none');
        $this->FNC('allFields', 'text', 'column=none,input=none');
        
        $this->FLD('contactsCnt', 'int', 'caption=Записи,input=none');
        
        cls::get('core_Lg');
        
        $this->FLD('lg', 'enum(, ' . EF_LANGUAGES . ')', 'caption=Език,changable,notNull,allowEmpty');
        
        $this->setDbUnique('title');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param int $folderId - id на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        // Името на класа
        $coverClassName = strtolower(doc_Folders::fetchCoverClassName($folderId));
        
        // Ако не е папка проект или контрагент, не може да се добави
        if (($coverClassName != 'doc_unsortedfolders')) return FALSE;
    }
    
    
    /**
     * Прибавя ключовото поле към другите за да получи всичко
     */
    static function on_CalcAllFields($mvc, $rec)
    {
        $rec->allFields = $rec->keyField . '=' . $mvc->getFieldType('keyField')->options[$rec->keyField] . "\n" . $mvc->clearFields($rec->fields);
    }
    
    
    /**
     * Изчиства празния ред.
     * Премахва едноредовите коментари.
     */
    function clearFields($rec)
    {
        $delimiter = '[#newLine#]';
        
        //Заместваме празните редове
        $fields = str_ireplace(array("\n", "\r\n", "\n\r"), $delimiter, $rec);
        $fieldsArr = explode($delimiter, $fields);
        
        //Премахва редове, които започват с #
        foreach ($fieldsArr as $value) {
            
            //Премахваме празните интервали
            $value = trim($value);
            
            //Проверяваме дали е коментар
            if ((strpos($value, '#') !== 0) && (strlen($value))) {
                
                //Разделяме стринга на части
                $valueArr = explode("=", $value, 2);
                
                //Вземаме името на полето
                $fieldName = $valueArr[0];
                
                //Превръщаме името на полето в малки букви
                $fieldName = strtolower($fieldName);
                
                //Премахваме празните интервали в края и в началото в името на полето
                $fieldName = trim($fieldName);
                
                //Заместваме всички стойности различни от латински букви и цифри в долна черта
                $fieldName = preg_replace("/[^a-z0-9]/", "_", $fieldName);
                
                //Премахваме празните интервали в края и в началото в заглавието на полето
                $caption = trim($valueArr[1]);
                
                //Ескейпваме заглавието
                //                $caption = htmlspecialchars($caption, ENT_COMPAT | ENT_HTML401, 'UTF-8');
                //                $caption = core_Type::escape($caption);
                
                //Ескейпваме непозволените символи в заглавието
                //                $caption = str_replace(array('=', '\'', '$', '|'), array('&#61;', '&#39;', '&#36;', '&#124;'), $caption);
                
                //Изчистваме заглавието на полето и го съединяваме със заглавието
                $newValue = $fieldName . '=' . $caption;
                
                //Създаваме нова променлива, в която ще се съхраняват всички полета
                ($newFields) ? ($newFields .= "\n" . $newValue) : $newFields = $newValue;
            }
        }
        
        return $newFields;
    }
    
    
    /**
     * Поддържа точна информацията за записите в детайла
     */
    protected static function on_AfterUpdateDetail(core_Master $mvc, $id, core_Manager $detailMvc)
    {
        $rec = $mvc->fetch($id);
        $dQuery = $detailMvc->getQuery();
        $dQuery->where("#listId = $id");
        $rec->contactsCnt = $dQuery->count();
        
        // Определяме състоянието на база на количеството записи (контакти)
        if($rec->state == 'draft' && $rec->contactsCnt > 0) {
            $rec->state = 'closed';
        } elseif ($rec->state == 'closed' && $rec->contactsCnt == 0) {
            $rec->state = 'draft';
        }
        
        $mvc->save($rec);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, необходимо за това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec)
    {
        if(($action == 'edit' || $action == 'delete') && $rec->state != 'draft' && isset($rec->state)) {
            $roles = 'no_one';
        }
    }
    
    
    /**
     * Добавя помощен шаблон за попълване на полетата
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        if (!$data->form->rec->fields) {
            $template = new ET (getFileContent("blast/tpl/ListsEditFormTemplates.txt"));
            $data->form->rec->fields = $template->getContent();
        }
        
        if (!$data->form->rec->id) {
            $data->form->setDefault('lg', core_Lg::getCurrent());
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        if ($data->rec->keyField == 'email' && blast_Emails::haveRightFor('add') && $data->rec->state != 'draft' && $data->rec->state != 'rejected') {
            
            Request::setProtected(array('perSrcObjectId', 'perSrcClassId'));
        
            $data->toolbar->addBtn('Циркулярен имейл', array('blast_Emails', 'add', 'perSrcClassId' => core_Classes::getId($mvc), 'perSrcObjectId' => $data->rec->id, 'ret_url' => TRUE), 'id=btnEmails','ef_icon = img/16/emails.png,title=Създаване на циркулярен имейл');
        }
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = $this->getVerbal($rec, 'title');
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        $row->recTitle = $rec->title;
        
        return $row;
    }
    
    
    /**
     * Връща CSV представяне на данните в списъка
     */
    static function importCsvFromLists($listId)
    {
        $rec = self::fetch($listId);
        $fieldsArr = blast_ListDetails::getFncFieldsArr($rec->allFields);
        
        $csv = '';
        
        self::addCsvRow($csv, $fieldsArr);
        
        $dQuery = blast_ListDetails::getQuery();
        $dQuery->where("#listId = {$rec->id}");
        
        $listDetails = cls::get('blast_ListDetails');
        $listDetails->addFNC($rec->allFields);
        
        while($r = $dQuery->fetch()) {
            $data = unserialize($r->data);
            
            $row = array();
            
            foreach($fieldsArr as $key => $caption) {
                $row[$key] = $data[$key];
            }
            
            self::addCsvRow($csv, $row);
        }
        
        return $csv;
    }
    
    
    /**
     * Добавя един ред в CSV структура
     */
    static function addCsvRow(&$csv, $row)
    {
        $div = '';
        
        foreach($row as $value) {
            
            // escape
            if (preg_match('/\\r|\\n|,|"/', $value)) {
                $value = '"' . str_replace('"', '""', $value) . '"';
            }
            
            $csv .= $div . $value;
            
            $div = ',';
        }
        
        $csv .= "\n";
        
        return $csv;
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
        $cnt = blast_ListDetails::getCnt($rec->id);
        
        $Int = cls::get('type_Int');
        $row->DetailsCnt = $Int->toVerbal($cnt);
    }
    
    
    /**
     * Преобразува стринга с полета в масив с инстанции на класовете
     *
     * @param string $fields
     *
     * @return array
     */
    protected static function getFieldsArr($fields)
    {
        $fields = trim($fields);
        
        $fields = str_replace(array("\n", "\r\n", "\n\r"), array(',', ',', ','), $fields);
        
        // Преобразуваме в масив
        $fieldsArr = arr::make($fields, TRUE);
        
        // Обхождаме масива и за всеки плейсхолдер, добавяме съответния му тип
        foreach ($fieldsArr as $name => $caption) {
            $name = strtolower($name);
            $paramArr = array('caption' => $caption);
            
            switch ($name) {
                
                case 'email' :
                    $type = 'type_Email';
                    break;
                
                case 'emails' :
                    $type = 'type_Emails';
                    break;
                
                case 'vat' :
                    $type = 'drdata_VatType';
                    break;
                
                case 'fax' :
                case 'mobile' :
                case 'tel' :
                case 'phone' :
                    $type = 'drdata_PhoneType';
                    break;
                
                case 'country' :
                    $type = 'type_Varchar';
                    $paramArr['remember'] = 'remember';
                    break;
                
                default :
                $type = 'type_Varchar';
                break;
            }
            $fieldsArr[$name] = cls::get($type, $paramArr);
        }
        
        return $fieldsArr;
    }
    
    
    /**
     * Връща масив с ключове имената на плейсхолдърите и съдържание - типовете им
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param integer $id
     *
     * @return array
     */
    public function getPersonalizationDescr($id)
    {
        $fieldsArr = array();
        $rec = $this->fetch($id);
        
        if (!$rec) return $fieldsArr;
        
        // Масив с ключове плейсхолдерите и стойности класовете им
        $fieldsArr = $this->getFieldsArr($rec->allFields);
        
        return $fieldsArr;
    }
    
    
    /**
     * Връща масив с ключове - уникални id-та и ключове - масиви с данни от типа place => value
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param integer $id
     * @param integer $limit
     *
     * @return array
     */
    public function getPresonalizationArr($id, $limit = 0)
    {
        $resArr = array();
        
        // Всички списъци, които не са спредни или оттеглени
        $detailQuery = blast_ListDetails::getQuery();
        $detailQuery->where("#listId = '{$id}'");
        $detailQuery->where("#state != 'stopped'");
        $detailQuery->where("#state != 'rejected'");
        
        if ($limit) {
            $detailQuery->limit($limit);
        }
        
        $cnt = 0;
        
        while ($rec = $detailQuery->fetch()) {
            $resArr[$rec->id] = unserialize($rec->data);
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща вербално представяне на заглавието на дадения източник за персонализирани данни
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param integer|object $id
     * @param boolean $verbal
     *
     * @return string
     */
    public function getPersonalizationTitle($id, $verbal = TRUE)
    {
        if (is_object($id)) {
            $rec = $id;
        } else {
            $rec = $this->fetch($id);
        }
        
        // Ако трябва да е вебална стойност
        if ($verbal) {
            $title = $this->getVerbal($rec, 'title');
        } else {
            $title = $rec->title;
        }
        
        return $title;
    }
    
    
    /**
     * Дали потребителя може да използва дадения източник на персонализация
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param integer $id
     * @param integer $userId
     *
     * @return boolean
     */
    public function canUsePersonalization($id, $userId = NULL)
    {
        // Всеки който има права до сингъла на записа, може да го използва
        if (($id > 0) && ($this->haveRightFor('single', $id, $userId))) return TRUE;
        
        return FALSE;
    }
    
    
    /**
     * Връща масив за SELECT с всички възможни източници за персонализация от даден клас, които са достъпни за посочения потребител
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param integer $userId
     *
     * @return array
     */
    public function getPersonalizationOptions($userId = NULL)
    {
        $resArr = array();
        
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        //Добавя в лист само списъци с имейли
        $query = $this->getQuery();
        $query->where("#state != 'rejected'");
        $query->orderBy("createdOn", 'DESC');
        
        // Обхождаме откритите резултати
        while ($rec = $query->fetch()) {
            
            // Ако няма права за персонализиране, да не се връща
            if (!$this->canUsePersonalization($rec->id, $userId)) continue;
            
            // Добавяме в масива
            $resArr[$rec->id] = $this->getPersonalizationTitle($rec, FALSE);
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща масив за SELECT с всички възможни източници за персонализация от даден клас,
     * за съответния запис,
     * които са достъпни за посочения потребител
     * 
     * @param integer $id
     * 
     * @return array
     */
    public function getPersonalizationOptionsForId($id)
    {
        $resArr = $this->getPersonalizationOptions();
        
        return $resArr;
    }
    
    
    /**
     * Връща линк, който сочи към източника за персонализация
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param integer $id
     *
     * @return core_ET
     */
    public function getPersonalizationSrcLink($id)
    {
        // Създаваме линк към сингъла листа
        $title = $this->getPersonalizationTitle($id, TRUE);
        $link = ht::createLink($title, array($this, 'single', $id));
        
        return $link;
    }
    
    
    /**
     * Връща езика за източника на персонализация
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param integer $id
     *
     * @return string
     */
    public function getPersonalizationLg($id)
    {
        $rec = $this->fetch($id);
        
        return $rec->lg;
    }
    
    
    /**
     * Добавя допълнителни полетата в антетката
     * 
     * @param core_Master $mvc
     * @param NULL|array $res
     * @param object $rec
     * @param object $row
     */
    public static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
        $resArr = arr::make($resArr);
        
        $allFieldsArr = array('title' => 'Заглавие',
        						'keyField' => 'Ключово поле',
        						'allFields' => 'Всички полета',
        						'DetailsCnt' => 'Брой абонати',
        						'lg' => 'Език',
        						'lastUsedOn' => 'Последна употреба'
                            );
        foreach ($allFieldsArr as $fieldName => $val) {
            if ($row->{$fieldName}) {
                $resArr[$fieldName] =  array('name' => tr($val), 'val' =>"[#{$fieldName}#]");
            }
        }
        
        $resArr['created'] =  array('name' => tr('Създаване'), 'val' =>"[#createdBy#], [#createdOn#]");
    }
    
    
    /**
     * Кои полета да са скрити във вътрешното показване
     * 
     * @param core_Master $mvc
     * @param NULL|array $res
     * @param object $rec
     * @param object $row
     */
    public static function on_AfterGetHideArrForLetterHead($mvc, &$res, $rec, $row)
    {
        $res = arr::make($res);
        
        $res['external']['created'] = TRUE;
    }
}
