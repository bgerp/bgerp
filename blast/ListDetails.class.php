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
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blast_ListDetails extends doc_Detail
{
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'blast_Wrapper, plg_RowNumbering, plg_RowTools2, plg_Select, expert_Plugin, plg_Created, plg_Sorting, plg_State, plg_PrevAndNext, plg_SaveAndNew';
    
    
    /**
     * Заглавие
     */
    var $title = "Контакти за масово разпращане";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'blast,ceo,admin';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'blast,ceo,admin';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'blast,ceo,admin';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'blast,ceo,admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'blast,ceo,admin';
    
    
    /**
     * Кой може да го възстанови?
     */
    var $canRestore = 'blast,ceo,admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'blast,ceo,admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'blast,ceo,admin';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Контакт за масово разпращане';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'listId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'RowNumb';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 100;
    
    
    /**
     * Стойност по подразбиране на състоянието
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
    function description()
    {
        // Информация за папката
        $this->FLD('listId' , 'key(mvc=blast_Lists,select=title)', 'caption=Списък,mandatory,column=none');
        
        $this->FLD('data', 'blob', 'caption=Данни,input=none,column=none,export');
        $this->FLD('key', 'varchar(64)', 'caption=Kлюч,input=none,column=none');
        
        $this->setDbUnique('listId,key');
    }
    
    
    /**
     * Връща броя на записите
     * 
     * @param integer $listId
     * @param NULL|string $state
     * 
     * @return integer
     */
    public static function getCnt($listId, $state = NULL)
    {
        $query = self::getQuery();
        $query->where("#listId = {$listId}");
        if ($state) {
            $query->where(array("#state = '[#1#]'", $state));
        }
        
        return (int)$query->count();
    }
    
    
    /**
     * Извиква се преди подготовката на колоните
     */
    static function on_BeforePrepareListFields($mvc, &$res, $data)
    {
        $mvc->addFNC($data->masterData->rec->allFields);
        $mvc->setField('id,createdOn,createdBy', 'column=none');
        $mvc->setField('createdOn,createdBy', 'column=50');
        $mvc->setField('state', 'column=49');
    }
    
    
    /**
     * Преди подготвяне на едит формата
     */
    static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
        if($id = Request::get('id', 'int')) {
            expect($rec = $mvc->fetch($id));
            expect($masterRec = $mvc->Master->fetch($rec->listId));
        } elseif($masterKey = Request::get($mvc->masterKey, 'int')) {
            expect($masterRec = $mvc->Master->fetch($masterKey));
        }
        
        expect($masterRec);
        
        $data->masterRec = $masterRec;      // @todo: Да се сложи в core_Detail
        $mvc->addFNC($masterRec->allFields);
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        
        if($bData = $data->form->rec->data) {
            
            $fieldsArr = $mvc->getFncFieldsArr($data->masterRec->allFields);
            
            $bData = unserialize($bData);
            
            foreach($fieldsArr as $name => $caption) {
                $data->form->rec->{$name} = $bData[$name];
            }
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        if(!$form->isSubmitted()) return;
        
        expect($masterRec = $mvc->Master->fetch($form->rec->listId));
        
        $fieldsArr = $mvc->getFncFieldsArr($masterRec->allFields);
        
        $data = array();
        
        foreach($fieldsArr as $name => $caption) {
            $data[$name] = $form->rec->{$name};
        }
        
        $form->rec->data = serialize($data);
        
        $keyField = $masterRec->keyField;
        
        $form->rec->key = str::convertToFixedKey(mb_strtolower(trim($form->rec->{$keyField})));
        
        if($form->rec->id) {
            $idCond = " AND #id != {$form->rec->id}";
        }
        
        if($mvc->fetch(array("#key = '[#1#]' AND #listId = [#2#]" . $idCond, $form->rec->key, $form->rec->listId))) {
            $form->setError($keyField, "В списъка вече има запис със същия ключ");
        }
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
        
        if(!$fieldsArr) {
            $fieldsArr = $mvc->getFncFieldsArr($masterRec->allFields);
        }
        
        $body = unserialize($rec->data);
        
        foreach($fieldsArr as $name => $caption) {
            $rec->{$name} = $body[$name];
            $row->{$name} = $mvc->getVerbal($rec, $name);
        }
        
        if (!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')) {
            if ($rec->state != 'stopped') {
            
                // Бутон за спиране
                $row->state = ht::createBtn('Спиране', array($mvc, 'stop', $rec->id, 'ret_url' => TRUE), FALSE, FALSE,'title=Прекратяване на изпращане към този имейл');
            } else {
            
                // Бутон за активиране
                $row->state = ht::createBtn('Активиране', array($mvc, 'activate', $rec->id, 'ret_url' => TRUE), FALSE, FALSE,'title=Започване на изпращане към този имейл');
            }
        }
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
    	if ($rec && ($action == 'export')) {
    		if (!haveRole('blast,ceo,admin', $userId)) {
    			if ($rec->createdBy != $userId) {
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
     * @todo да се замести в кода по-горе
     */
    protected function getExportFields_()
    {
        // Кои полета ще се показват
        $fields = arr::make("email=Имейл,
    					     company=Компания", TRUE);
    
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
    	header("Content-type: application/csv");
    	header("Content-Disposition: attachment; filename={$fileName}.csv");
    	header("Pragma: no-cache");
    	header("Expires: 0");
    
    	echo $csv;
    
    	shutdown();
    }
    
    
    /**
     * Екшън за спиране
     */
    function act_Stop()
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
    function act_Activate()
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
    function on_AfterGetQuery($mvc, $query)
    {
          $query->orderBy('state');
          $query->orderBy('createdOn', 'DESC');
    }
    
    
    /**
     * Създава функционални полета, от подадения масив
     */
    function addFNC($fields)
    {
        $fieldsArr = $this->getFncFieldsArr($fields);
        
        foreach($fieldsArr as $name => $caption) {
            $attr = ",remember=info";
            
            switch($name) {
                case 'email' :
                    $type = 'email';
                    break;
                case 'fax' :
                    $type = 'drdata_PhoneType';
                    break;
                case 'mobile' :
                    $type = 'drdata_PhoneType';
                    break;
                case 'country' :
                    $type = 'varchar';
                    $attr = ",remember";
                    break;
                case 'date' :
                    $type = 'type_Date';
                    break;
                default :
                $type = 'varchar';
                break;
            }
            
            $this->FNC($name, $type, "caption={$caption},mandatory,input,forceField" . $attr);
        }
    }
    
    
    /**
     * Преобразува стринга в масив, който се използва за създаване на функционални полета
     */
    static function getFncFieldsArr($fields)
    {
        $fields = str_replace(array("\n", "\r\n", "\n\r"), array(',', ',', ','), trim($fields));
        $fieldsArr = arr::make($fields, TRUE);
        
        return $fieldsArr;
    }
    
    
    /**
     * Добавя бутон за импортиране на контакти
     */
    static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->addBtn('Импорт', array($mvc, 'import', 'listId' => $data->masterId, 'ret_url' => TRUE), NULL, array('ef_icon'=>'img/16/table-import-icon.png', 'title'=>'Внасяне на допълнителни данни'));
        
        if($data->recs) {
        	foreach($data->recs as $rec) {
		        if($mvc->haveRightFor('export', $rec)){
		        	$data->toolbar->addBtn('Експорт в CSV', array($mvc, 'export', $rec->id), NULL, 'ef_icon = img/16/file_extension_xls.png, title = Сваляне на записите в CSV формат,row=2');
		        	break;
		        }
        	}
        }
    }
    
    
    /**
     * Импортиране на контактен списък от друго място (визитника или външен източник)
     */
    function exp_Import($exp)
    {   
        core_App::setTimeLimit(50);

        $exp->functions['getcsvcolnames'] = 'blast_ListDetails::getCsvColNames';
        $exp->functions['getfilecontentcsv'] = 'blast_ListDetails::getFileContent';
        $exp->functions['getcsvcolumnscnt'] = 'blast_ListDetails::getCsvColumnsCnt';
        $exp->functions['importcsvfromcontacts'] = 'blast_ListDetails::importCsvFromContacts';
        $exp->functions['importcsvfromlists'] = 'blast_Lists::importCsvFromLists';
        
        $exp->DEF('#listId', 'int', 'fromRequest');
        
        $exp->DEF('#source=Източник', 'enum(csv=Copy&Paste на CSV данни, 
                                           csvFile=Файл със CSV данни,
                                           groupCompanies=Група от "Указател » Фирми",
                                           groupPersons=Група от "Указател » Лица",
                                           blastList=Друг списък от "Разпращане")', 'maxRadio=5,columns=1,mandatory');
        $exp->ASSUME('#source', '"csv"');
        $exp->question("#source", tr("Моля, посочете източника на данните") . ":", TRUE, 'title=' . tr('От къде ще се импортират данните') . '?');
        
        $exp->DEF('#csvData=CSV данни', 'text(1000000)', 'width=100%,mandatory');
        $exp->question("#csvData", tr("Моля, поставете данните") . ":", "#source == 'csv'", 'title=' . tr('Въвеждане на CSV данни за контакти'));
        
        $exp->DEF('#companiesGroup=Група фирми', 'group(base=crm_Companies,keylist=groupList)', 'mandatory');
        $exp->DEF('#personsGroup=Група лица', 'group(base=crm_Persons,keylist=groupList)', 'mandatory');
        
        $exp->question("#companiesGroup", tr("Посочете група от фирми, от която да се импортират контактните данни") . ":", "#source == 'groupCompanies'", 'title=' . tr('Избор на група фирми'));
        $exp->question("#personsGroup", tr("Посочете група от лица, от която да се импортират контактните данни") . ":", "#source == 'groupPersons'", 'title=' . tr('Избор на група лица'));
        
        $exp->rule("#delimiter", "','", "#source == 'groupPersons' || #source == 'groupCompanies'");
        $exp->rule("#enclosure", "'\"'", "#source == 'groupPersons' || #source == 'groupCompanies'");
        $exp->rule("#firstRow", "'columnNames'", "#source == 'groupPersons' || #source == 'groupCompanies'");
        
        $exp->rule("#csvData", "importCsvFromContacts('crm_Companies', #companiesGroup, #listId)");
        $exp->rule("#csvData", "importCsvFromContacts('crm_Persons', #personsGroup, #listId)");
        
        $exp->DEF('#blastList=Списък', 'key(mvc=blast_Lists,select=title)', 'mandatory');
        
        $exp->question("#blastList", tr("Изберете списъка от който да се импортират данните"), "#source == 'blastList'", 'title=' . tr('Импортиране от съществуващ списък'));
        $exp->rule("#csvData", "importCsvFromLists(#blastList)", '#blastList');
        
        $exp->DEF('#csvFile=CSV файл', 'fileman_FileType(bucket=csvContacts)', 'mandatory');
        $exp->question("#csvFile", tr("Въведете файл с контактни данни във CSV формат") . ":", "#source == 'csvFile'", 'title=' . tr('Въвеждане на данните от файл'));
        $exp->rule("#csvData", "getFileContentCsv(#csvFile)");
        
        $exp->rule("#csvColumnsCnt", "count(getCsvColNames(#csvData,#delimiter,#enclosure))");
        $exp->WARNING(tr("Възможен е проблем с формата на CSV данните, защото е открита само една колона"), '#csvColumnsCnt == 2');
        $exp->ERROR(tr("Има проблем с формата на CSV данните") . ". <br>" . tr("Моля проверете дали правилно сте въвели данните и разделителя"), '#csvColumnsCnt < 2');
        
        $exp->DEF('#delimiter=Разделител', 'varchar(1,size=1)', 'mandatory');
        $exp->SUGGESTIONS("#delimiter", array(',' => ',', ';' => ';', ':' => ':', '|' => '|'));
        $exp->ASSUME('#delimiter', '"|"');

        $exp->DEF('#enclosure=Ограждане', 'varchar(1,size=1)', array('value' => '"'), 'mandatory');
        $exp->SUGGESTIONS("#enclosure", array('"' => '"', '\'' => '\''));
        $exp->DEF('#firstRow=Първи ред', 'enum(columnNames=Имена на колони,data=Данни)', 'mandatory');
        
        $exp->question("#delimiter,#enclosure,#firstRow", tr("Посочете формата на CSV данните") . ":", "#csvData", 'title=' . tr('Уточняване на разделителя и ограждането'));
        
        setIfNot($listId, Request::get('listId', 'int'), $exp->getValue('listId'));
        
        // Изискване за права
        $rec = new stdClass();
        $rec->listId = $listId;
        blast_ListDetails::requireRightFor('add', $rec);
        
        $listRec = blast_Lists::fetch($listId);
        $fieldsArr = $this->getFncFieldsArr($listRec->allFields);
        
        foreach($fieldsArr as $name => $caption) {
            $exp->DEF("#col{$name}={$caption}", 'int', 'mandatory');
            $exp->OPTIONS("#col{$name}", "getCsvColNames(#csvData,#delimiter,#enclosure, NULL, FALSE)");
            
            $caption = str_replace(array('"', "'"), array('\\"', "\\'"), $caption);
            $nameEsc = str_replace(array('"', "'"), array('\\"', "\\'"), $name);
            
            $exp->ASSUME("#col{$name}", "getCsvColNames(#csvData,#delimiter,#enclosure,'{$caption}', TRUE, '{$nameEsc}')");
            
            $qFields .= ($qFields ? ',' : '') . "#col{$name}";
        }

        $exp->DEF('#priority=Приоритет', 'enum(update=Новите данни да обновят съществуващите,data=Съществуващите данни да се запазят)', 'mandatory');
        $exp->question("#priority", tr("Какъв да бъде приоритета в случай, че има нов контакт с дублирано съдържание на полето") . " <span class=\"green\">'" . $fieldsArr[$listRec->keyField] . "'</span> ?", TRUE, 'title=' . tr('Приоритет на данните'));
        
        $exp->question($qFields, tr("Въведете съответстващите полета") . ":", TRUE, 'title=' . tr('Съответствие между полетата на източника и списъка'));
        
        $res = $exp->solve("#source,#csvData,#delimiter,#enclosure,#priority,{$qFields}");
        
        if($res == 'SUCCESS') {
            
            $csv = $exp->getValue('#csvData');
            $delimiter = $exp->getValue('#delimiter');
            
            $enclosure = $exp->getValue('#enclosure');
            
            if(!is_array($csv)) {
                $csvRows = explode("\n", trim($csv));
            } else {
                $csvRows = $csv;
            }
            
            // Ако първия ред са имена на колони - махаме ги
            if($exp->getValue('#firstRow') == 'columnNames') {
                unset($csvRows[0]);
            }
            
            $time = round(count($csvRows) / 5) + 10;
            
            core_App::setTimeLimit($time);
            
            $newCnt = $skipCnt = $updateCnt = 0;

            $errLinesArr = array();
       
            if(count($csvRows)) {
                foreach($csvRows as $row) {
                    $rowArr = str_getcsv($row, $delimiter, $enclosure);
                    $rec = new stdClass();
                    
                    foreach($fieldsArr as $name => $caption) {
                        $id = $exp->getValue("#col{$name}");
                        
                        if($id === NULL) continue;
                        $rec->{$name} = trim($rowArr[$id-1]);
                    }
                    
                    $err = $this->normalizeRec($rec); ;
                    $keyField = $listRec->keyField;
                    
                    // Вземаме стойността на ключовото поле;
                    $key = $rec->{$keyField};
                    
                    // Ако ключа е празен, скипваме текущия ред
                    if (empty($key) || count($err)) {
                        $errLinesArr[] = $row;
                        
                        if (empty($key)) {
                            self::logWarning('Грешка при импортиране: Липсва ключове поле за записа - ' . $row, NULL, 1);
                        } else {
                            self::logWarning('Грешка при импортиране: ' . implode(', ', $err) . ' - ' . $row, NULL, 1);
                        }
                        
                        $skipCnt++;
                        continue;
                    }
                    
                    $rec->key = str::convertToFixedKey($key);
                    $rec->listId = $listId;
                    $rec->state = 'active';
                   
                    if($exRec = $this->fetch(array("#listId = {$listId} AND #key = '[#1#]'", $rec->key))) {
                        // Ако имаме съществуващ $exRec със същия ключ, имаме две възможности
                        // 1. Да го обновим с новите данни
                        // 2. Да го пропуснем
                        if($exp->getValue('#priority') == 'update') {
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
                    
                    foreach($fieldsArr as $name => $caption) {
                        setIfNot($data[$name], $rec->{$name}, $exRec->{$name});
                    }
                    
                    $rec->data = serialize($data);
                    
                    // Да се попълват полетата, които се попълват в плъгина, защото не се прекъсва записа
                    setIfNot($rec->createdOn, dt::verbal2Mysql());
                    setIfNot($rec->createdBy, core_Users::getCurrent());
                    
                    $this->save_($rec);
                }

                $exp->message = tr("Добавени са") . " {$newCnt} " . tr("нови записа") . ", " . tr("обновени") . " - {$updateCnt}, " . tr("пропуснати") . " - {$skipCnt}";
                
                // Ако има грешни линни да се добавят в 'csv' файл
                if (!empty($errLinesArr)) {
                    $fh = fileman::absorbStr(implode("\n", $errLinesArr), 'exportCsv', 'listDetailsExpErr.csv');
                    status_Messages::newStatus('|Пропуснатите линии са добавени в|*: ' . fileman::getLinkToSingle($fh));
                }
            } else {
                $exp->message = tr("Липсват данни за добавяне");
            }
        } elseif($res == 'FAIL') {
            $exp->message = tr('Неуспешен опит за импортиране на списък с контакти') . '.';
        }
        
        return $res;
    }
    
    
    /**
     * Нормализира някои полета от входните данни
     */
    function normalizeRec($rec)
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
                if (!type_Email::isValidEmail($email)) continue;
                
                // Сетваме флага
                $haveValidEmail = TRUE;
                
                // Добавяме първия имейл
                $rec->email = $email;
                
                // Прекъсваме
                break;
            }
            
            if(!$haveValidEmail) {
                $err['email'] = "Некоректен имейл адрес";
            }
        }
        
        // Валидираме полето, ако е GSM
        if (trim($rec->mobile)) {
            $Phones = cls::get('drdata_Phones');
            $code = '359';
            $parsedTel = $Phones->parseTel($rec->mobile, $code);
            
            if(!$parsedTel[0]->mobile) {
                $err['mobile'] = "Некоректен мобилен номер";
            }
            $rec->mobile = $parsedTel[0]->countryCode . $parsedTel[0]->areaCode . $parsedTel[0]->number;
        }
        
        // Валидираме полето, ако е GSM
        if (trim($rec->fax)) {
            $Phones = cls::get('drdata_Phones');
            $code = '359';
            $parsedTel = $Phones->parseTel($rec->fax, $code);
            
            if(!$parsedTel[0]) {
                $err['fax'] = "Некоректен факс номер";
            }
            $rec->fax = $parsedTel[0]->countryCode . $parsedTel[0]->areaCode . $parsedTel[0]->number;
        }
        
        // Валидираме полето ако е държава
        
        return $err;
    }
    
    
    /**
     * Зарежда данни от посочен CSV файл, като се опитва да ги конвертира в UTF-8
     */
    static function getFileContent($fh)
    {
        $csv = fileman_Files::getContent($fh);
        $csv = i18n_Charset::convertToUtf8($csv);
        
        return $csv;
    }
    
    
    /**
     * Връща масив с опции - заглавията на колоните
     */
    static function getCsvColNames($csvData, $delimiter, $enclosure, $caption = NULL, $escape = TRUE, $name = NULL)
    {
        if(is_array($csvData)) {
            $rowsOrig = $csvData;
        } else {
            $rowsOrig = explode("\n", $csvData);
        }
        
        foreach($rowsOrig as $r) {
            if(trim($r)) {
                $rows[] = $r;
            }
        }
        
        if(count($rows) === 0) return array();
        
        $rowArr = str_getcsv($rows[0], $delimiter, $enclosure);
        
        if(count($rows) > 1) {
            $rowArr1 = str_getcsv($rows[1], $delimiter, $enclosure);
            
            if(count($rowArr) != count($rowArr1)) {
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
        
        if(!count($rowArr)) return array();
        
        if($caption) {
            $captionC = trim(mb_strtolower($caption));
            $nameC = trim(mb_strtolower($name));
            foreach($rowArr as $id => $val) {
                $valC = trim(mb_strtolower($val));
                
                if (!$valC) continue;
                
                if(strpos($captionC, $valC) !== FALSE || strpos($valC, $captionC)) {
                    return $id + 1;
                }
                if(strpos($nameC, $valC) !== FALSE || strpos($valC, $nameC)) {
                    return $id + 1;
                }
            }
        } else {
            $resArr = arr::combine(array(NULL => ''), $rowArr);
            array_unshift($resArr, "");
            unset($resArr[0]);
            
            return $resArr;
        }
    }
    
    
    /**
     * Импортира CSV от моделите на визитника
     */
    static function importCsvFromContacts($className, $groupId, $listId)
    {
        $listRec = blast_Lists::fetch($listId);
        
        core_Lg::push($listRec->lg);

        $mvc = cls::get($className);
        
        $cQuery = $mvc->getQuery();
        
        $cQuery->where("#state != 'rejected' AND #groupList like '%|{$groupId}|%'");
        
        $csv = array();

        while($cRec = $cQuery->fetch()) {
            $rCsv = '';
            
            foreach($mvc->fields as $field => $dummy) {
                
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
                
                if(!is_scalar($value)) $value = '';
                
                if (preg_match('/\\r|\\n|,|"/', $value)) {
                    $value = '"' . str_replace('"', '""', $value) . '"';
                }
                
                $rCsv .= ($rCsv ? "," : "") . $value;
                
                if(!$haveColumns) {
                    $columns .= ($columns ? "," : "") . ($mvc->fields[$field]->caption ? $mvc->fields[$field]->caption : $filed);
                }
            }
            $haveColumns = TRUE;
            
            $csv[] = $rCsv;
        }
        
        $csv = array_merge(array($columns), (array)$csv);
        
        core_Lg::pop();

        return $csv;
    }
}
