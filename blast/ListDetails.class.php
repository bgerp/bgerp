<?php


/**
 * Клас 'blast_Lists' - Списъци за масово разпращане
 *
 * Към контактите включени в тези списъци могат да се изпращат
 * циркулярни писма, имейли, факсове и групови SMS-и
 *
 *
 * @category  bgerp
 * @package   blast
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class blast_ListDetails extends doc_Detail
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'blast_Wrapper, plg_RowNumbering, plg_RowTools2, plg_Select, expert_Plugin, plg_Created, plg_Sorting, plg_State, plg_PrevAndNext, plg_SaveAndNew';
    
    
    /**
     * Заглавие
     */
    public $title = 'Контакти за масово разпращане';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'blast,ceo,admin';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'blast,ceo,admin';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'blast,ceo,admin';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'blast,ceo,admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    public $canReject = 'blast,ceo,admin';
    
    
    /**
     * Кой може да го възстанови?
     */
    public $canRestore = 'blast,ceo,admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'blast,ceo,admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'blast,ceo,admin';
    
    
    /**
     * Кой може да екпортира?
     */
    public $canExport = 'blast,ceo,admin';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Контакт за масово разпращане';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'listId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 100;
    
    
    /**
     * Стойност по подразбиране на състоянието
     *
     * @see plg_State
     */
    public $defaultState = 'active';
    
    
    /**
     * Предлог в формата за добавяне/редактиране
     */
    public $formTitlePreposition = 'в';
    
    
    /**
     * Описание на полетата на модела
     */
    public function description()
    {
        // Информация за папката
        $this->FLD('listId', 'key(mvc=blast_Lists,select=title)', 'caption=Списък,mandatory,column=none');
        
        $this->FLD('data', 'blob', 'caption=Данни,input=none,column=none,export');
        $this->FLD('key', 'varchar(64)', 'caption=Ключ,input=none,column=none');
        
        $this->setDbUnique('listId,key');
    }
    
    
    /**
     * Връща броя на записите
     *
     * @param int         $listId
     * @param NULL|string $state
     *
     * @return int
     */
    public static function getCnt($listId, $state = null)
    {
        $query = self::getQuery();
        $query->where("#listId = {$listId}");
        if ($state) {
            $query->where(array("#state = '[#1#]'", $state));
        }
        
        return (int) $query->count();
    }
    
    
    /**
     * Извиква се преди подготовката на колоните
     */
    public static function on_BeforePrepareListFields($mvc, &$res, $data)
    {
        $mvc->addFNC($data->masterData->rec->allFields);
        $mvc->setField('id,createdOn,createdBy', 'column=none');
        $mvc->setField('createdOn,createdBy', 'column=50');
        $mvc->setField('state', 'column=49');
    }
    
    
    /**
     * След порготвяне на формата за филтриране
     *
     * @param blast_Emails $mvc
     * @param object       $data
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('createdOn', 'DESC');
        $data->query->orderBy('id', 'DESC');
    }
    
    
    /**
     * Преди подготвяне на едит формата
     */
    public static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
        if ($id = Request::get('id', 'int')) {
            expect($rec = $mvc->fetch($id));
            expect($masterRec = $mvc->Master->fetch($rec->listId));
        } elseif ($masterKey = Request::get($mvc->masterKey, 'int')) {
            expect($masterRec = $mvc->Master->fetch($masterKey));
        }
        
        expect($masterRec);
        
        $data->masterRec = $masterRec;      // @todo: Да се сложи в core_Detail
        $mvc->addFNC($masterRec->allFields);
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        if ($bData = $data->form->rec->data) {
            $fieldsArr = $mvc->getFncFieldsArr($data->masterRec->allFields);
            
            $bData = unserialize($bData);
            
            foreach ($fieldsArr as $name => $caption) {
                $data->form->rec->{$name} = $bData[$name];
            }
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        if (!$form->isSubmitted()) {
            
            return;
        }
        
        expect($masterRec = $mvc->Master->fetch($form->rec->listId));
        
        $fieldsArr = $mvc->getFncFieldsArr($masterRec->allFields);
        
        $data = array();
        
        foreach ($fieldsArr as $name => $caption) {
            $data[$name] = $form->rec->{$name};
        }
        
        $form->rec->data = serialize($data);
        
        $keyField = $masterRec->keyField;
        
        $form->rec->key = str::convertToFixedKey(mb_strtolower(trim($form->rec->{$keyField})));
        
        if ($form->rec->id) {
            $idCond = " AND #id != {$form->rec->id}";
        }
        
        if ($mvc->fetch(array("#key = '[#1#]' AND #listId = [#2#]" . $idCond, $form->rec->key, $form->rec->listId))) {
            $form->setError($keyField, 'В списъка вече има запис със същия ключ');
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass     $row Това ще се покаже
     * @param stdClass     $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $masterRec = $mvc->Master->fetch($rec->listId);
        $keyField = $masterRec->keyField;
        
        if ($keyField == 'email') {
            $emailState = blast_BlockedEmails::getState($rec->key);
            
            if ($emailState == 'error') {
                $row->ROW_ATTR['class'] .= ' state-error-email';
            } elseif ($emailState == 'blocked') {
                $row->ROW_ATTR['class'] .= ' state-blocked-email';
            }
        }
        
        static $fieldsArr;
        
        if (!$fieldsArr) {
            $fieldsArr = $mvc->getFncFieldsArr($masterRec->allFields);
        }
        
        $body = unserialize($rec->data);
        
        foreach ($fieldsArr as $name => $caption) {
            $rec->{$name} = $body[$name];
            $row->{$name} = $mvc->getVerbal($rec, $name);
        }
        
        if (!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')) {
            if ($rec->state != 'stopped') {
                
                // Бутон за спиране
                $row->state = ht::createBtn('Спиране', array($mvc, 'stop', $rec->id, 'ret_url' => true), false, false, 'title=Прекратяване на изпращане към този имейл');
            } else {
                
                // Бутон за активиране
                $row->state = ht::createBtn('Активиране', array($mvc, 'activate', $rec->id, 'ret_url' => true), false, false, 'title=Започване на изпращане към този имейл');
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($rec && ($action == 'export')) {
            if ($rec->createdBy != $userId) {
                if (!blast_Lists::haveRightFor('single', $rec->listId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     *
     * @todo да се замести в кода по-горе
     */
    protected function getExportFields_()
    {
        // Кои полета ще се показват
        $fields = arr::make('email=Имейл,
    					     company=Компания', true);
        
        return $fields;
    }
    
    
    /**
     * Екшън който експортира данните
     */
    public function act_Export()
    {
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        
        // Проверка за права
        $this->requireRightFor('export', $rec);
        
        // взимаме от базата целия списък отговарящ на този бюлетин
        $query = self::getQuery();
        $query->where("#listId = '{$rec->listId}'");
        
        $allFields = blast_Lists::fetch($rec->listId, 'allFields');
        
        $fieldSet = cls::get('blast_ListDetails');
        $fieldSet->addFNC($allFields->allFields);
        
        $listFields = blast_ListDetails::getFncFieldsArr($allFields->allFields);
        
        while ($fRec = $query->fetch()) {
            $data[] = (object) unserialize($fRec->data);
        }
        
        $csv = csv_Lib::createCsv($data, $fieldSet, $listFields);
        
        $listTitle = blast_Lists::fetchField("#id = '{$rec->listId}'", 'title');
        
        // името на файла на кирилица
        $fileName = basename($this->title);
        $fileName = str_replace(' ', '_', Str::utf2ascii($this->title));
        
        $fileName = fileman_Files::normalizeFileName($listTitle);
        
        // правим CSV-то
        header('Content-type: application/csv');
        header("Content-Disposition: attachment; filename={$fileName}.csv");
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $csv;
        
        shutdown();
    }
    
    
    /**
     * Екшън за спиране
     */
    public function act_Stop()
    {
        // id' то на записа
        $id = Request::get('id', 'int');
        
        expect($id);
        
        // Очакваме да има такъв запис
        $rec = $this->fetch($id);
        expect($rec, 'Няма такъв запис.');
        
        // Очакваме да имаме права за записа
        $this->requireRightFor('single', $rec);
        
        // Смяняме състоянието на спряно
        $nRec = new stdClass();
        $nRec->id = $id;
        $nRec->state = 'stopped';
        $this->save($nRec);
        
        return new Redirect(getRetUrl());
    }
    
    
    /**
     * Екшън за активиране
     */
    public function act_Activate()
    {
        // id' то на записа
        $id = Request::get('id', 'int');
        
        expect($id);
        
        // Очакваме да има такъв запис
        $rec = $this->fetch($id);
        expect($rec, 'Няма такъв запис.');
        
        // Очакваме да имаме права за записа
        $this->requireRightFor('single', $rec);
        
        // Смяняме състоянието на спряно
        $nRec = new stdClass();
        $nRec->id = $id;
        $nRec->state = 'active';
        $this->save($nRec);
        
        return new Redirect(getRetUrl());
    }
    
    
    /**
     * След като се поготви заявката за модела
     */
    public function on_AfterGetQuery($mvc, $query)
    {
        $query->orderBy('state');
        $query->orderBy('createdOn', 'DESC');
    }
    
    
    /**
     * Създава функционални полета, от подадения масив
     */
    public function addFNC($fields)
    {
        $fieldsArr = $this->getFncFieldsArr($fields);
        
        foreach ($fieldsArr as $name => $caption) {
            $attr = ',remember=info';
            
            switch ($name) {
                case 'email':
                    $type = 'email';
                    break;
                case 'fax':
                    $type = 'drdata_PhoneType';
                    break;
                case 'mobile':
                    $type = 'drdata_PhoneType';
                    break;
                case 'country':
                    $type = 'varchar';
                    $attr = ',remember';
                    break;
                case 'date':
                    $type = 'type_Date';
                    break;
                default:
                $type = 'varchar';
                break;
            }
            
            $this->FNC($name, $type, "caption={$caption},mandatory,input,forceField" . $attr);
        }
    }
    
    
    /**
     * Преобразува стринга в масив, който се използва за създаване на функционални полета
     */
    public static function getFncFieldsArr($fields)
    {
        $fields = str_replace(array("\n", "\r\n", "\n\r"), array(',', ',', ','), trim($fields));
        $fieldsArr = arr::make($fields, true);
        
        return $fieldsArr;
    }
    
    
    /**
     * Добавя бутон за импортиране на контакти
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->addBtn('Импорт', array($mvc, 'import', 'listId' => $data->masterId, 'ret_url' => true), null, array('ef_icon' => 'img/16/table-import-icon.png', 'title' => 'Внасяне на допълнителни данни'));
        
        if ($data->recs) {
            foreach ($data->recs as $rec) {
                if ($mvc->haveRightFor('export', $rec)) {
                    $data->toolbar->addBtn('Експорт в CSV', array($mvc, 'export', $rec->id), null, 'ef_icon = img/16/file_extension_xls.png, title = Сваляне на записите в CSV формат,row=2');
                    break;
                }
            }
        }
    }
    
    
    /**
     * Импортиране на контактен списък от друго място (визитника или външен източник)
     */
    public function exp_Import($exp)
    {
        core_App::setTimeLimit(50);
        
        $exp->functions['getcsvcolnames'] = 'blast_ListDetails::getCsvColNames';
        $exp->functions['getcountriesfromgroup'] = 'blast_ListDetails::getCountriesFromGroup';
        $exp->functions['getfilecontentcsv'] = 'blast_ListDetails::getFileContent';
        $exp->functions['getcsvcolumnscnt'] = 'blast_ListDetails::getCsvColumnsCnt';
        $exp->functions['importcsvfromcontacts'] = 'blast_ListDetails::importCsvFromContacts';
        $exp->functions['importcsvfromdocuments'] = 'blast_ListDetails::importCsvFromDocuments';
        $exp->functions['importcsvfromlists'] = 'blast_Lists::importCsvFromLists';
        $exp->functions['csvanalize'] = 'blast_ListDetails::csvAnalize';

        $exp->DEF('#listId', 'int', 'fromRequest');
        
        $exp->DEF('#source=Източник', 'enum(csv=Copy&Paste на CSV данни, 
                                           csvFile=Файл със CSV данни,
                                           groupCompanies=Група от "Указател » Фирми",
                                           groupPersons=Група от "Указател » Лица",
                                           blastList=Друг списък от "Разпращане",
                                           document=От документи)', 'maxRadio=6,columns=1,mandatory');
        $exp->ASSUME('#source', '"csv"');
        $exp->question('#source', tr('Моля, посочете източника на данните') . ':', true, 'title=' . tr('От къде ще се импортират данните') . '?');
        
        $exp->DEF('#csvData=CSV данни', 'text(1000000)', 'width=100%,mandatory');
        $exp->question('#csvData', tr('Моля, поставете данните') . ':', "#source == 'csv'", 'title=' . tr('Въвеждане на CSV данни за контакти'));
        
        $exp->DEF('#companiesGroup=Група фирми', 'group(base=crm_Companies,keylist=groupList)', 'mandatory');
        $exp->DEF('#personsGroup=Група лица', 'group(base=crm_Persons,keylist=groupList)', 'mandatory');
        $exp->DEF('#inChargeUsers=Отговорници', 'userList', 'notNull');
        
        $exp->question('#companiesGroup,#inChargeUsers', tr('Посочете група от фирми, от която да се импортират контактните данни') . ':', "#source == 'groupCompanies'", 'title=' . tr('Избор на група фирми'));
        $exp->question('#personsGroup,#inChargeUsers', tr('Посочете група от лица, от която да се импортират контактните данни') . ':', "#source == 'groupPersons'", 'title=' . tr('Избор на група лица'));
        
        $exp->DEF('#countriesInclude=Държава->Само тези', 'keylist(mvc=drdata_Countries, select=commonName, selectBg=commonNameBg, allowEmpty)', 'placeholder=Всички, notNull');
        $exp->SUGGESTIONS('#countriesInclude', 'getCountriesFromGroup(#companiesGroup)');
        $exp->SUGGESTIONS('#countriesInclude', 'getCountriesFromGroup(#personsGroup, "crm_Persons")');
        
        $exp->DEF('#countriesExclude=Държава->Без тези', 'keylist(mvc=drdata_Countries, select=commonName, selectBg=commonNameBg, allowEmpty)', 'placeholder=Няма, notNull');
        $exp->SUGGESTIONS('#countriesExclude', 'getCountriesFromGroup(#companiesGroup)');
        $exp->SUGGESTIONS('#countriesExclude', 'getCountriesFromGroup(#personsGroup, "crm_Persons")');
        
        $exp->DEF('#documentType=Вид', 'enum(,sales=Продажби, quotations=Оферти, inquiries=Запитвания)', 'placeholder=Всички, notNull');
        $exp->DEF('#catGroups=Продуктови групи', 'keylist(mvc=cat_Groups,select=name, allowEmpty)', 'placeholder=Всички, notNull');
        $exp->DEF('#contragentType=Вид контрагент', 'enum(,crm_Companies=Фирми,crm_Persons=Лица)', 'placeholder=Всички, notNull');
        $exp->DEF('#docFrom=Период->От', 'date', 'notNull');
        $exp->DEF('#docTo=Период->До', 'date', 'notNull');
        
        $exp->question('#countriesInclude,#countriesExclude', tr('Филтър по държави') . ':', "#source == 'groupCompanies' || #source == 'groupPersons'", 'title=' . tr('Филтър по държави'));
        
        $exp->question('#documentType,#catGroups,#countriesInclude,#countriesExclude,#contragentType,#docFrom,#docTo', tr('Избор на вид документ') . ':', "#source == 'document'", 'title=' . tr('Избор на вид документ'));
        
        $exp->rule('#delimiter', "','", "#source == 'groupPersons' || #source == 'groupCompanies' || #source == 'document' || #source == 'blastList'");
        $exp->rule('#enclosure', "'\"'", "#source == 'groupPersons' || #source == 'groupCompanies' || #source == 'document' || #source == 'blastList'");
        $exp->rule('#firstRow', "'columnNames'", "#source == 'groupPersons' || #source == 'groupCompanies' || #source == 'document' || #source == 'blastList'");
        
        $exp->rule('#csvData', "importCsvFromContacts('crm_Companies', #companiesGroup, #listId, #countriesInclude, #countriesExclude, #inChargeUsers)");
        $exp->rule('#csvData', "importCsvFromContacts('crm_Persons', #personsGroup, #listId, #countriesInclude, #countriesExclude, #inChargeUsers)");
        
        $exp->rule('#csvData', 'importCsvFromDocuments(#documentType,#catGroups,#listId,#countriesInclude,#countriesExclude,#contragentType,#docFrom,#docTo)');
        
        $exp->DEF('#blastList=Списък', 'key(mvc=blast_Lists,select=title)', 'mandatory');
        
        $exp->question('#blastList', tr('Изберете списъка от който да се импортират данните'), "#source == 'blastList'", 'title=' . tr('Импортиране от съществуващ списък'));
        $exp->rule('#csvData', 'importCsvFromLists(#blastList)', '#blastList');
        
        $exp->DEF('#csvFile=CSV файл', 'fileman_FileType(bucket=csvContacts)', 'mandatory');
        $exp->question('#csvFile', tr('Въведете файл с контактни данни във CSV формат') . ':', "#source == 'csvFile'", 'title=' . tr('Въвеждане на данните от файл'));
        $exp->rule('#csvData', 'getFileContentCsv(#csvFile)');
        
        $exp->rule('#csvColumnsCnt', 'count(getCsvColNames(#csvData,#delimiter,#enclosure))');
        $exp->ERROR(tr('В CSV-източника са открити по-малко колони от колкото са необходими за този списък'), '(#csvColumnsCnt-1) != #listColumns');
        $exp->ERROR(tr('Има проблем с формата на CSV данните') . '. <br>' . tr('Моля проверете дали правилно сте въвели данните и разделителя'), '#csvColumnsCnt < 2');
        
        $exp->rule('#csvAnalize', 'csvanalize(#csvData)', "is_string(#csvData)");
        $exp->DEF('#delimiter=Разделител', 'varchar(,size=1)');
        $exp->DEF('#delimiterAsk=Разделител', 'varchar(,size=5)', 'mandatory');
        $exp->SUGGESTIONS('#delimiterAsk', array('' => '', ',' => ',', ';' => ';', ':' => ':', '|' => '|', '[tab]' => '[tab]'));
        $exp->ASSUME('#delimiterAsk', '#csvAnalize[1] == "' . "\t" . '" ? "[tab]" : #csvAnalize[1]');
        $exp->rule('#delimiter', "#delimiterAsk == '[tab]' ? '" . "\t" . "' : #delimiterAsk");

        $exp->DEF('#enclosure=Ограждане', 'varchar(1,size=1)', array('value' => '"'), 'mandatory');
        $exp->SUGGESTIONS('#enclosure', array('"' => '"', '\'' => '\''));
        $exp->ASSUME('#enclosure', '#csvAnalize[2]');

        $exp->DEF('#firstRow=Първи ред', 'enum(columnNames=Имена на колони,data=Данни)', 'mandatory');
        $exp->ASSUME('#firstRow', '#csvAnalize[3]');

        $exp->question('#delimiterAsk,#enclosure,#firstRow', tr('Посочете формата на CSV данните') . ':', '#csvData', 'title=' . tr('Уточняване на разделителя и ограждането'));
        
        setIfNot($listId, Request::get('listId', 'int'), $exp->getValue('listId'));
        
        // Изискване за права
        $rec = new stdClass();
        $rec->listId = $listId;
        blast_ListDetails::requireRightFor('add', $rec);
        
        $listRec = blast_Lists::fetch($listId);
        $fieldsArr = $this->getFncFieldsArr($listRec->allFields);
   
        foreach ($fieldsArr as $name => $caption) {
            $exp->DEF("#col{$name}={$caption}", 'int', 'mandatory');
            $exp->OPTIONS("#col{$name}", 'getCsvColNames(#csvData,#delimiter,#enclosure, NULL, FALSE)');
            
            $caption = str_replace(array('"', "'"), array('\\"', "\\'"), $caption);
            $nameEsc = str_replace(array('"', "'"), array('\\"', "\\'"), $name);
            
            $exp->ASSUME("#col{$name}", "getCsvColNames(#csvData,#delimiter,#enclosure,'{$caption}', TRUE, '{$nameEsc}')");
            
            $qFields .= ($qFields ? ',' : '') . "#col{$name}";
        }

        $exp->rule('#listColumns', count($fieldsArr));

        
        $exp->DEF('#priority=Приоритет', 'enum(data=Съществуващите данни да се запазят,update=Новите данни да обновят съществуващите)', 'mandatory');
        $exp->rule('#priority', '"data"', $listRec->contactsCnt ? "0" : "1");

        $exp->question('#priority', tr('Какъв да бъде приоритета в случай, че има нов контакт с дублирано съдържание на полето') . " <span class=\"green\">'" . $fieldsArr[$listRec->keyField] . "'</span> ?", true, 'title=' . tr('Приоритет на данните'));
        
        $exp->question($qFields, tr('Въведете съответстващите полета') . ':', true, 'title=' . tr('Съответствие между полетата на източника и списъка'));
        
        $res = $exp->solve("#source,#csvData,#delimiter,#enclosure,#priority,{$qFields}");
        
        if ($res == 'SUCCESS') {
            $csv = $exp->getValue('#csvData');
            $delimiter = $exp->getValue('#delimiter');
            
            $enclosure = $exp->getValue('#enclosure');
            
            if (!is_array($csv)) {
                $csvRows = explode("\n", trim($csv));
            } else {
                $csvRows = $csv;
            }
            
            // Ако първия ред са имена на колони - махаме ги
            if ($exp->getValue('#firstRow') == 'columnNames') {
                unset($csvRows[0]);
            }
            
            $time = round(countR($csvRows) / 5) + 10;
            
            core_App::setTimeLimit($time);
            
            $newCnt = $skipCnt = $updateCnt = 0;
            
            $errLinesArr = array();
            
            if (countR($csvRows)) {
                foreach ($csvRows as $row) {
                    $rowArr = str_getcsv($row, $delimiter, $enclosure);
                    $rec = new stdClass();
                    
                    foreach ($fieldsArr as $name => $caption) {
                        $id = $exp->getValue("#col{$name}");
                        
                        if ($id === null) {
                            continue;
                        }
                        $rec->{$name} = trim($rowArr[$id - 1]);
                    }
                    
                    $err = $this->normalizeRec($rec);
                    $keyField = $listRec->keyField;
                    
                    // Вземаме стойността на ключовото поле;
                    $key = $rec->{$keyField};
                    
                    // Ако ключа е празен, скипваме текущия ред
                    if (empty($key) || countR($err)) {
                        $errLinesArr[] = $row;
                        
                        if (empty($key)) {
                            self::logWarning('Грешка при импортиране: Липсва ключове поле за записа - ' . $row, null, 1);
                        } else {
                            self::logWarning('Грешка при импортиране: ' . implode(', ', $err) . ' - ' . $row, null, 1);
                        }
                        
                        $skipCnt++;
                        continue;
                    }
                    
                    $rec->key = str::convertToFixedKey($key);
                    $rec->listId = $listId;
                    $rec->state = 'active';
                    
                    if ($exRec = $this->fetch(array("#listId = {$listId} AND #key = '[#1#]'", $rec->key))) {
                        // Ако имаме съществуващ $exRec със същия ключ, имаме две възможности
                        // 1. Да го обновим с новите данни
                        // 2. Да го пропуснем
                        if ($exp->getValue('#priority') == 'update') {
                            $rec->id = $exRec->id;
                            $updateCnt++;
                            $rec->state = $exRec->state;
                        } else {
                            $skipCnt++;
                            continue;
                        }
                    } else {
                        $newCnt++;
                    }
                    
                    // Подготвяме $rec->data
                    $data = array();
                    
                    foreach ($fieldsArr as $name => $caption) {
                        setIfNot($data[$name], $rec->{$name}, $exRec->{$name});
                    }
                    
                    $rec->data = serialize($data);
                    
                    // Да се попълват полетата, които се попълват в плъгина, защото не се прекъсва записа
                    setIfNot($rec->createdOn, dt::verbal2Mysql());
                    setIfNot($rec->createdBy, core_Users::getCurrent());
                    
                    $this->save_($rec);
                }
                
                $exp->message = tr('Добавени са') . " {$newCnt} " . tr('нови записа') . ', ' . tr('обновени') . " - {$updateCnt}, " . tr('пропуснати') . " - {$skipCnt}";
                
                // Ако има грешни линни да се добавят в 'csv' файл
                if (!empty($errLinesArr)) {
                    $fh = fileman::absorbStr(implode("\n", $errLinesArr), 'exportCsv', 'listDetailsExpErr.csv');
                    status_Messages::newStatus('|Пропуснатите линии са добавени в|*: ' . fileman::getLinkToSingle($fh));
                }
            } else {
                $exp->message = tr('Липсват данни за добавяне');
            }
        } elseif ($res == 'FAIL') {
            $exp->message = tr('Неуспешен опит за импортиране на списък с контакти') . '.';
        }
        
        return $res;
    }


    /**
     * Анализ на csv данни за откриване на разделител, оградител и първи ред
     */
    public static function csvAnalize($data)
    {
        return csv_Lib::analyze($data);

    }
    
    
    /**
     * Нормализира някои полета от входните данни
     */
    public function normalizeRec($rec)
    {
        $err = array();
        
        // Валидираме полето, ако е имейл
        if (trim($rec->email)) {
            $rec->email = strtolower($rec->email);
            
            // Масив с всички имейли
            $emailArr = type_Emails::toArray($rec->email);
            
            // Обхождаме масива
            foreach ($emailArr as $email) {
                
                // Ако не е валиден имейл, прескачаме
                if (!type_Email::isValidEmail($email)) {
                    continue;
                }
                
                // Сетваме флага
                $haveValidEmail = true;
                
                // Добавяме първия имейл
                $rec->email = $email;
                
                // Прекъсваме
                break;
            }
            
            if (!$haveValidEmail) {
                $err['email'] = 'Некоректен имейл адрес';
            }
        }
        
        // Валидираме полето, ако е GSM
        if (trim($rec->mobile)) {
            $Phones = cls::get('drdata_Phones');
            $code = '359';
            $parsedTel = $Phones->parseTel($rec->mobile, $code);
            
            if (!$parsedTel[0]->mobile) {
                $err['mobile'] = 'Некоректен мобилен номер';
            }
            $rec->mobile = $parsedTel[0]->countryCode . $parsedTel[0]->areaCode . $parsedTel[0]->number;
        }
        
        // Валидираме полето, ако е GSM
        if (trim($rec->fax)) {
            $Phones = cls::get('drdata_Phones');
            $code = '359';
            $parsedTel = $Phones->parseTel($rec->fax, $code);
            
            if (!$parsedTel[0]) {
                $err['fax'] = 'Некоректен факс номер';
            }
            $rec->fax = $parsedTel[0]->countryCode . $parsedTel[0]->areaCode . $parsedTel[0]->number;
        }
        
        // Валидираме полето ако е държава
        
        return $err;
    }
    
    
    /**
     * Зарежда данни от посочен CSV файл, като се опитва да ги конвертира в UTF-8
     */
    public static function getFileContent($fh)
    {
        $csv = fileman_Files::getContent($fh);
        $csv = i18n_Charset::convertToUtf8($csv);
        
        return $csv;
    }


    /**
     * Извежда списък с всички под-нива на дадената група, включително и нея
     */
    private static function expandTree($groupId)
    {
        $Groups = cls::get('crm_Groups');
        $gQuery = $Groups->getQuery();
        $res[$groupId] = $groupId;
        $flag = true;
        $gRecs = $gQuery->fetchAll();
        while($flag) {
            $flag = false;
           
            foreach($gRecs as $r) {
                if(isset($res[$r->parentId])) {
                    if(!isset($res[$r->id])) {
                        $res[$r->id] = $r->id;
                        $flag = true;
                    }
                }
            }
        }
        $res = keylist::fromArray($res);

        return $res;
    }
    
    
    /**
     * Връща масив с всички държави използвани в съотвения клас и група
     *
     * @param int    $groupId
     * @param string $class
     *
     * @return array
     */
    public static function getCountriesFromGroup($groupId, $class = 'crm_Companies')
    {
        static $resArr = array();
        
        $hash = $groupId . '|' . $class;
        
        if (isset($resArr[$hash])) {
            
            return $resArr[$hash];
        }
         
        $cQuery = $class::getQuery();
        $groupId = self::expandTree($groupId);

        $cQuery->likeKeylist('groupList', $groupId);
        
        $cQuery->groupBy('country');
        
        $cQuery->orderBy('country', 'ASC');
        
        $cQuery->show('country');
        
        $cRecArr = array();
        while ($cRec = $cQuery->fetch()) {
            $cRecArr[$cRec->country] = $cRec->country;
        }
        
        $resArr[$hash] = array();
        
        if (!empty($cRecArr)) {
            $resArr[$hash] = drdata_Countries::getOptionsArr($cRecArr);
        }
        
        return $resArr[$hash];
    }
    
    
    /**
     * Връща масив с опции - заглавията на колоните
     */
    public static function getCsvColNames($csvData, $delimiter, $enclosure, $caption = null, $escape = true, $name = null)
    {
        if (is_array($csvData)) {
            $rowsOrig = $csvData;
        } else {
            $rowsOrig = explode("\n", $csvData);
        }
        
        foreach ($rowsOrig as $r) {
            if (trim($r)) {
                $rows[] = $r;
            }
        }
        
        if (countR($rows) === 0) {
            
            return array();
        }
        
        $rowArr = str_getcsv($rows[0], $delimiter, $enclosure);
        
        if (countR($rows) > 1) {
            $rowArr1 = str_getcsv($rows[1], $delimiter, $enclosure);
            
            if (countR($rowArr) != countR($rowArr1)) {
                
                return array();
            }
        }
        
        //Ескейпваме стойностите
        foreach ($rowArr as $key => $value) {
            if ($escape) {
                $rowArr[$key] = core_Type::escape($value);
            } else {
                $rowArr[$key] = $value;
            }
        }
        
        if (!countR($rowArr)) {
            
            return array();
        }
        
        if ($caption) {
            $captionC = trim(mb_strtolower($caption));
            $nameC = trim(mb_strtolower($name));
            foreach ($rowArr as $id => $val) {
                $valC = trim(mb_strtolower($val));
                
                if (!$valC) {
                    continue;
                }
                
                if (strpos($captionC, $valC) !== false || strpos($valC, $captionC) !== false) {
                    
                    return $id + 1;
                }
                if (strpos($nameC, $valC) !== false || strpos($valC, $nameC) !== false) {
                    
                    return $id + 1;
                }
 
                if (type_Email::isValidEmail($valC) &&  $nameC == 'email') {
                    
                    return $id + 1;
                }
            }
        } else {
            $resArr = arr::combine(array(null => ''), $rowArr);
            array_unshift($resArr, '');
            unset($resArr[0]);
            
            return $resArr;
        }
    }
    
    
    /**
     * Връща списъка за импортиране
     *
     * @param string|array $documentType
     * @param string       $groupIds
     * @param int          $listId
     * @param string       $countriesInclude
     * @param string       $countriesExlude
     * @param string       $contragentType
     * @param string       $docFrom
     * @param string       $docTo
     *
     * @return array
     */
    public static function importCsvFromDocuments($documentType, $groupIds, $listId, $countriesInclude, $countriesExlude, $contragentType, $docFrom, $docTo)
    {
        core_App::setTimeLimit(600);
        
        $listRec = blast_Lists::fetch($listId);
        core_Lg::push($listRec->lg);
        
        // Ако е празна стойност, тогава се връщат всички
        $documentTypeArr = arr::make($documentType);
        if (empty($documentType)) {
            $documentTypeArr[] = 'sales';
            $documentTypeArr[] = 'quotations';
            $documentTypeArr[] = 'inquiries';
        }
        
        $csvArr = array();
        if (!empty($documentTypeArr)) {
            $csvArr[] = tr('Имейл') . ',' . tr('Име') . ',' . tr('Държава');
        }
        
        $allEmailArr = array();
        
        foreach ($documentTypeArr as $docType) {
            $getFromNextEmail = false;
            if ($docType == 'sales' || $docType == 'quotations') {
                $getFromNextEmail = true;
                
                $docDetails = ($docType == 'sales') ? 'sales_SalesDetails' : 'sales_QuotationsDetails';
                $masterClass = ($docType == 'sales') ? 'sales_Sales' : 'sales_Quotations';
            }
            
            // Ако данните ще се определят от следващия изпратен имейл или папката
            if ($getFromNextEmail) {
                $docDetailsInst = cls::get($docDetails);
                
                $query = $docDetailsInst->getQuery();
                
                $query->groupBy($docDetailsInst->masterKey);
                
                // Филтрираме по група
                if ($groupIds) {
                    $query->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
                    $query->likeKeylist('groups', $groupIds);
                }
                
                if ($countriesInclude || $countriesExlude || $contragentType) {
                    $query->EXT('contragentClassId', $masterClass, "externalName=contragentClassId,externalKey={$docDetailsInst->masterKey}");
                    if ($countriesInclude || $countriesExlude) {
                        $query->EXT('contragentId', $masterClass, "externalName=contragentId,externalKey={$docDetailsInst->masterKey}");
                    }
                }
                
                // Филтрираме по вид контрагент
                if ($contragentType) {
                    $query->where(array("#contragentClassId = '[#1#]'", $clsId = $contragentType::getClassId()));
                }
                
                // Филтрираме по държави
                if ($countriesInclude || $countriesExlude) {
                    if ($docType == 'quotations') {
                        $query->EXT('contragentCountryId', $masterClass, "externalName=contragentCountryId,externalKey={$docDetailsInst->masterKey}");
                        
                        if ($countriesInclude) {
                            $query->in('contragentCountryId', type_Keylist::toArray($countriesInclude));
                        }
                        
                        // Премахваме тези държави от списъка
                        if ($countriesExlude) {
                            $query->notIn('contragentCountryId', type_Keylist::toArray($countriesExlude));
                        }
                    }
                }
                
                // Филтрираме по дата
                if ($docFrom || $docTo) {
                    $query->EXT('masterCreatedOn', $masterClass, "externalName=createdOn,externalKey={$docDetailsInst->masterKey}");
                    
                    if ($docFrom) {
                        $query->where(array("#masterCreatedOn >= '[#1#]'", $docFrom . ' 00:00:00'));
                    }
                    
                    if ($docTo) {
                        $query->where(array("#masterCreatedOn <= '[#1#]'", $docTo . ' 23:59:59'));
                    }
                }
                if ($docType == 'quotations') {
                    $query->EXT('email', $masterClass, "externalName=email,externalKey={$docDetailsInst->masterKey}");
                }
                
                $query->EXT('containerId', $masterClass, "externalName=containerId,externalKey={$docDetailsInst->masterKey}");
                $query->EXT('folderId', $masterClass, "externalName=folderId,externalKey={$docDetailsInst->masterKey}");
                
                while ($rec = $query->fetch()) {
                    $name = '';
                    
                    // Ако е продажба, филтрирам по държава, ако е зададено, защото не е направено със заявката
                    if (($docType == 'sales') && (($countriesInclude || $countriesExlude))) {
                        $contragentCountry = cls::get($rec->contragentClassId)->fetchField($rec->contragentId, 'country');
                        
                        if ($countriesInclude) {
                            if (!$contragentCountry) {
                                continue;
                            }
                            if (!type_Keylist::isIn($contragentCountry, $countriesInclude)) {
                                continue;
                            }
                        }
                        
                        if ($countriesExlude) {
                            if (type_Keylist::isIn($contragentCountry, $countriesExlude)) {
                                continue;
                            }
                        }
                    }
                    
                    $fRec = doc_Folders::fetch($rec->folderId);
                    $cInstRec = null;
                    if (($fRec->coverClass) && ($fRec->coverId)) {
                        $cInst = cls::get($fRec->coverClass);
                        $cInstRec = $cInst->fetch($fRec->coverId);
                    }
                    
                    // Ако няма имейл в записа, вземаме следващия до когото е изпратен
                    $email = $rec->email;
                    if (!$email) {
                        if ($rec->containerId) {
                            $cRec = doc_Containers::fetch($rec->containerId);
                            
                            $cQuery = doc_Containers::getQuery();
                            $cQuery->where(array("#threadId = '[#1#]'", $cRec->threadId));
                            $cQuery->where(array("#createdOn >= '[#1#]'", $cRec->createdOn));
                            $cQuery->where(array("#docClass = '[#1#]'", email_Outgoings::getClassId()));
                            $cQuery->where("#state != 'rejected' && #state != 'draft'");
                            while ($eCRec = $cQuery->fetch()) {
                                $sendEmailsArr = doclog_Documents::getSendEmails($eCRec->id);
                                if (empty($sendEmailsArr)) {
                                    continue;
                                }
                                
                                $email = trim($sendEmailsArr[0]);
                                if ($email) {
                                    $eCDoc = doc_Containers::getDocument($eCRec->id);
                                    if ($eCDoc) {
                                        $eCDocRec = $eCDoc->fetch();
                                        
                                        if ($eCDocRec) {
                                            if ($contragentType) {
                                                if ($contragentType == 'crm_Persons') {
                                                    $name = $eCDocRec->attn;
                                                } else {
                                                    $name = $eCDocRec->recipient;
                                                }
                                            } else {
                                                $name = $eCDocRec->attn ? $eCDocRec->attn : $eCDocRec->recipient;
                                            }
                                        }
                                    }
                                    
                                    break;
                                }
                            }
                        }
                    }
                    
                    // Ако все ощя няма имейл, използваме имейла от корицата
                    if (!$email) {
                        if ($cInstRec) {
                            $emails = $cInstRec->buzEmail;
                            $emails .= $emails ? ',' : '';
                            $emails = $cInstRec->email;
                            $emailsArr = type_Emails::toArray($emails);
                            $email = trim($emailsArr[0]);
                        }
                    }
                    
                    if (!$email) {
                        continue ;
                    }
                    
                    if ($allEmailArr[$email]) {
                        continue;
                    }
                    
                    $allEmailArr[$email] = $email;
                    
                    $countryName = '';
                    if ($cInstRec && $cInstRec->country) {
                        $countryName = $cInst->getVerbal($cInstRec, 'country');
                    }
                    
                    if (!$name) {
                        $name = $cInstRec->name;
                    }
                    
                    $csvArr[] = $email . ',' . $name . ',' . $countryName;
                }
            } else {
                
                // Ако е запитване
                
                $query = marketing_Inquiries2::getQuery();
                
                $query->where('#email IS NOT NULL');
                $query->where("#email != ''");
                
                // Ако се филтрира по дата
                if ($docFrom || $docTo) {
                    if ($docFrom) {
                        $query->where(array("#createdOn >= '[#1#]'", $docFrom . ' 00:00:00'));
                    }
                    
                    if ($docTo) {
                        $query->where(array("#createdOn <= '[#1#]'", $docTo . ' 23:59:59'));
                    }
                }
                
                // Ако се филтрира по типа на контрагента
                if ($contragentType) {
                    $query->EXT('coverClass', 'doc_Folders', 'externalName=coverClass,externalKey=folderId');
                    $query->where(array("#coverClass = '[#1#]'", $clsId = $contragentType::getClassId()));
                }
                
                // Ако се филтрира по държави
                if ($countriesInclude || $countriesExlude) {
                    if ($countriesInclude) {
                        $query->in('country', type_Keylist::toArray($countriesInclude));
                    }
                    
                    if ($countriesExlude) {
                        $query->notIn('country', type_Keylist::toArray($countriesExlude));
                    }
                }
                
                // Ако има зададена група, филтрираме по нея
                $catGroupsWhere = '';
                if ($groupIds) {
                    $groupIdsArr = type_Keylist::toArray($groupIds);
                    if (!empty($groupIdsArr)) {
                        $catGroupsWhere = "";
                        foreach ($groupIdsArr as $gId) {
                            $catGroupsWhere .= ($catGroupsWhere ? ' OR ' : '') . "LOCATE('|{$gId}|', #groups)";
                        }
                    }
                }
                while ($rec = $query->fetch()) {
                    $email = trim($rec->email);
                    
                    if (!$email) {
                        continue ;
                    }
                    
                    if ($allEmailArr[$email]) {
                        continue;
                    }
                    
                    // Гледаме дали е в някоя група от зададените
                    if ($catGroupsWhere) {
                        if (!cat_Products::fetch("(#originId = '{$rec->containerId}') AND ({$catGroupsWhere})")) continue;
                    }
                    
                    $allEmailArr[$email] = $email;
                    
                    $name = '';
                    if ($contragentType) {
                        if ($contragentType == 'crm_Persons') {
                            $name = $rec->personNames;
                        } else {
                            $name = $rec->company;
                        }
                    } else {
                        $name = $rec->company ? $rec->company : $rec->personNames;
                    }
                    
                    $countryName = '';
                    if ($rec->country) {
                        $countryName = marketing_Inquiries2::getVerbal($rec, 'country');
                    }
                    
                    $csvArr[] = $email . ',' . $name . ',' . $countryName;
                }
            }
        }
        
        core_Lg::pop();
        
        return $csvArr;
    }
    
    
    /**
     * Импортира CSV от моделите на визитника
     */
    public static function importCsvFromContacts($className, $groupId, $listId, $countriesInclude, $countriesExlude, $inChargeUsers)
    {
        core_App::setTimeLimit(240);
        $listRec = blast_Lists::fetch($listId);
        
        core_Lg::push($listRec->lg);
        
        $mvc = cls::get($className);
        
        $cQuery = $mvc->getQuery();
        $groupId = self::expandTree($groupId);
        $cQuery->where("#state != 'rejected'");
        $cQuery->likeKeylist('groupList', $groupId);

        if ($inChargeUsers) {
            $cQuery->in('inCharge', $inChargeUsers);
        }
        
        // Филтрираме само по-тези държави
        if ($countriesInclude) {
            $cQuery->in('country', type_Keylist::toArray($countriesInclude));
        }
        
        // Премахваме тези държави от списъка
        if ($countriesExlude) {
            $cQuery->notIn('country', type_Keylist::toArray($countriesExlude));
        }
        
        $csv = array();
        $columns = '';
        $haveColumns = false;
        
        while ($cRec = $cQuery->fetch()) {
            $rCsv = '';
            
            foreach ($mvc->fields as $field => $dummy) {
                $type = $mvc->getFieldType($field);
                
                if (($type instanceof type_Key) || ($type instanceof type_Key2)) {
                    $value = $mvc->getVerbal($cRec, $field);
                } elseif ($type instanceof type_Varchar) {
                    $value = $cRec->{$field};
                } elseif ($type instanceof type_Int) {
                    $value = $cRec->{$field};
                } elseif ($type instanceof type_Double) {
                    $value = $cRec->{$field};
                } elseif ($type instanceof type_Date) {
                    $value = dt::mysql2verbal($cRec->{$field});
                } else {
                    $value = '';
                }
                
                if (!is_scalar($value)) {
                    $value = '';
                }
                
                if (preg_match('/\\r|\\n|,|"/', $value)) {
                    $value = '"' . str_replace('"', '""', $value) . '"';
                }
                
                $rCsv .= ($rCsv ? ',' : '') . $value;
                
                if (!$haveColumns) {
                    $columns .= ($columns ? ',' : '') . ($mvc->fields[$field]->caption ? $mvc->fields[$field]->caption : $field);
                }
            }
            $haveColumns = true;
            
            $csv[] = $rCsv;
        }
        
        $csv = array_merge(array($columns), (array) $csv);
        
        core_Lg::pop();
        
        return $csv;
    }
}
