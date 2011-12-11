<?php

/**
 * Клас 'blast_Lists' - Списъци за масово разпращане
 * 
 * Към контактите включени в тези списъци могат да се изпращат
 * циркулярни писма, имейли, факсове и групови SMS-и
 *
 * @category   bgERP
 * @package    blast
 * @author     Milen Georgiev
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 3
 * @since      v 0.1
 */
class blast_ListDetails extends core_Detail
{   
    var $loadList = 'blast_Wrapper,plg_RowNumbering,plg_RowTools,plg_Select,expert_Plugin, plg_Created, plg_Sorting';

    var $title    = "Контакти за масово разпращане";

    var $canRead   = 'blast,admin';
    var $canWrite  = 'blast,admin';
    var $canReject = 'blast,admin';
    var $canDelete = 'blast, admin';

    var $singleTitle = 'Контакт за масово разпращане';

    var $masterKey = 'listId';

    var $rowToolsField = 'RowNumb';

    var $listItemsPerPage = 100;

    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        // Информация за папката
        $this->FLD('listId' ,  'key(mvc=blast_Lists,select=title)', 'caption=Списък,mandatory,column=none');

        $this->FLD('data', 'blob', 'caption=Данни,input=none,column=none');
        $this->FLD('key', 'varchar(64)', 'caption=Kлюч,input=none,column=none');

        $this->setDbUnique('listId,key');
    }


    /**
     *
     */
    function on_AfterPrepareDetailQuery($mvc, $res, $data)
    {
    	//Коментиран е за да работи плъгина plg_Sorting
        //$data->query->orderBy("#key");
    }
    
    
    /**
     *
     */
    function on_BeforePrepareListFields($mvc, $res, $data)
    {
        $mvc->addFNC($data->masterData->rec->allFields);
        $mvc->setField('id', 'column=none');
    }
    
    /**
     *
     */
    function on_BeforePrepareEditForm($mvc, $res, $data)
    {
        if($id = Request::get('id', 'int')) {
            expect($rec = $mvc->fetch($id));
            expect($masterRec = $mvc->Master->fetch($rec->listId));
        } elseif($masterKey = Request::get($mvc->masterKey, 'int')) {
            expect($masterRec = $mvc->Master->fetch($masterKey));
        }
 
        expect($masterRec);

        $data->masterRec = $masterRec; // @todo: Да се сложи в core_Detail

        $mvc->addFNC($masterRec->allFields);
        
    }




    /**
     *
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {

        if($bData = $data->form->rec->data) {

            $fieldsArr = $mvc->getFncFieldsArr($data->masterRec->allFields);
            
            $bData  =  unserialize($bData);
 
            foreach($fieldsArr as $name => $caption) {
                $data->form->rec->{$name} = $bData[$name];
            }

        }

    }


    /**
     *
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        if(!$form->isSubmitted()) return;
        
        expect($masterRec = $mvc->Master->fetch($form->rec->listId));

        $fieldsArr = $this->getFncFieldsArr($masterRec->allFields);

        foreach($fieldsArr as $name => $caption) {
            $data[$name] = $form->rec->{$name};
        }
 
        $form->rec->data =  serialize($data);
        
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
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        static $fieldsArr;

        if(!$fieldsArr) {
            expect($masterRec = $mvc->Master->fetch($rec->listId));
            $fieldsArr = $this->getFncFieldsArr($masterRec->allFields);
        }
        
        $body  =  unserialize($rec->data);
 
        foreach($fieldsArr as $name => $caption) {
            $rec->{$name} = $body[$name];
            $row->{$name} = $mvc->getVerbal($rec, $name);
        }
    }


    /**
     *
     */
    function addFNC($fields) 
    {
        $fieldsArr = $this->getFncFieldsArr($fields);
        foreach($fieldsArr as $name => $caption) {
            $attr = ",remember=info"; 
            switch($name) {
                case  'email': 
                    $type = 'email'; 
                    break;
                case    'fax': 
                    $type = 'drdata_PhoneType'; 
                    break;
                case 'mobile': 
                    $type = 'drdata_PhoneType'; 
                    break;
                case 'country': 
                    $type = 'varchar'; 
                    $attr = ",remember"; 
                    break;
                default: 
                    $type = 'varchar'; 
                    break;
            }

            $this->FNC($name, $type, "caption={$caption},mandatory,input" . $attr);
        }
    }


    /**
     *
     */
    function getFncFieldsArr($fields)
    {
        $fields = str_replace(array("\n", "\r\n", "\n\r"), array(',', ',', ','), $fields);
        $fieldsArr = arr::make($fields, TRUE);
        
        return $fieldsArr;
    }
   
    
    /**
     *
     */
    function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec)
    {
        if($action == 'edit' || $action == 'add') {
              $roles = 'blast,admin';
        }
    }


    /**
     * Добавя бутон за импортиране на контакти
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        $data->toolbar->addBtn('Импорт', array($mvc, 'import', 'listId' => $data->masterId, 'ret_url' => TRUE), NULL, 'class=btn-import');
    }
    

    /**
     * Импортиране на контактен списък от друго място (визитника или външен източник)
     */
    function exp_Import($exp)
    {   
        $exp->functions['getcsvcolnames'] = 'blast_ListDetails::getCsvColNames';
        $exp->functions['getfilecontent'] = 'fileman_Files::getContent';
        $exp->functions['getcsvcolumnscnt'] = 'blast_ListDetails::getCsvColumnsCnt';
        $exp->functions['importcsvfromcontacts'] = 'blast_ListDetails::importCsvFromContacts';

        $exp->DEF('#listId', 'int', 'fromRequest');

        $exp->DEF('#source=Източник', 'enum(csv=Copy&Paste на CSV данни, 
                                           csvFile=Файл със CSV данни,
                                           groupCompanies=Група от "Контакти » Фирми",
                                           groupPersons=Група от "Контакти » Лица",
                                           blastList=Друг списък от "Разпращане")', 'maxRadio=5,columns=1,value=csv,mandatory');
        $exp->question("#source", "Моля, посочете източника на данните:", TRUE, 'title=От къде ще се импортират данните?');

        $exp->DEF('#csvData=CSV данни', 'text(1000000)', 'width=100%,mandatory');
        $exp->question("#csvData", "Моля, поставете данните:", "#source == 'csv'", 'title=Въвеждане на CSV данни за контакти');
        
        $exp->DEF('#companiesGroup=Група фирми', 'group(base=crm_Companies,keylist=groupList)', 'mandatory');
        $exp->DEF('#personsGroup=Група лица', 'group(base=crm_Persons,keylist=groupList)', 'mandatory');
        
        $exp->question("#companiesGroup", "Посочете група от фирми, от която да се импортират контактните данни:", "#source == 'groupCompanies'", 'title=Избор на група фирми');
        $exp->question("#personsGroup", "Посочете група от лица, от която да се импортират контактните данни:", "#source == 'groupPersons'", 'title=Избор на група лица');
        
        $exp->rule("#delimiter", "','", "#source == 'groupPersons' || #source == 'groupCompanies'");
        $exp->rule("#enclosure", "'\"'", "#source == 'groupPersons' || #source == 'groupCompanies'");
        $exp->rule("#firstRow", "'columnNames'", "#source == 'groupPersons' || #source == 'groupCompanies'");
        
        $exp->rule("#csvData", "importCsvFromContacts('crm_Companies', #companiesGroup)");
        $exp->rule("#csvData", "importCsvFromContacts('crm_Persons', #personsGroup)");

        $exp->DEF('#csvFile=CSV файл', 'fileman_FileType(bucket=csvContacts)', 'mandatory');
        $exp->question("#csvFile", "Въведете файл с контактни данни във CSV формат:", "#source == 'csvFile'", 'title=Въвеждане на данните от файл');
        $exp->rule("#csvData", "getFileContent(#csvFile)");
        
        $exp->rule("#csvColumnsCnt", "count(getCsvColNames(#csvData,#delimiter,#enclosure))");
        $exp->WARNING("Възможен е проблем с формата на CSV данните, защото е открита само една колона", '#csvColumnsCnt == 2');
        $exp->ERROR("Има проблем с формата на CSV данните. <br>Моля проверете дали правилно сте въвели данните и разделителя", '#csvColumnsCnt < 2');


        $exp->DEF('#delimiter=Разделител', 'varchar(1,size=1)', 'value=&comma;', 'mandatory');
        $exp->SUGGESTIONS("#delimiter", array(',' => ',', ';' => ';', ':' => ':', '|' => '|'));
        $exp->DEF('#enclosure=Ограждане', 'varchar(1,size=1)', 'value=&quot;,mandatory');
        $exp->SUGGESTIONS("#enclosure", array('"' => '"', '\'' => '\''));
        $exp->DEF('#firstRow=Първи ред', 'enum(columnNames=Имена на колони,data=Данни)', 'mandatory');
 
        $exp->question("#delimiter,#enclosure,#firstRow", "Посочете формата на CSV данните:", "#csvData", 'title=Уточняване на разделителя и ограждането');
        

        setIfNot($listId, Request::get('listId', 'int'), $exp->getValue('listId'));
 
        blast_Lists::requireRightFor('edit', $listId);
        $listRec = blast_Lists::fetch($listId);
        $fieldsArr = $this->getFncFieldsArr($listRec->allFields);


        foreach($fieldsArr as $name => $caption) {
            $exp->DEF("#col{$name}={$caption}", 'int', 'mandatory');
            $exp->OPTIONS("#col{$name}", "getCsvColNames(#csvData,#delimiter,#enclosure)");

            $qFields .= ($qFields ? ',' : '') . "#col{$name}";
        }  
        $exp->DEF('#priority=Приоритет', 'enum(update=Новите данни да обновят съществуващите,data=Съществуващите данни да се запазят)', 'mandatory');
        $exp->question("#priority", "Какъв да бъде приоритета в случай, че има нов контакт с дублирано съдържание на полето <font color=green>'" . $fieldsArr[$listRec->keyField] . "'</font> ?", TRUE, 'title=Приоритет на данните');

        $exp->question($qFields, "Въведете съответстващите полета:", TRUE, 'title=Съответствие между полетата на източника и списъка');

        $res = $exp->solve("#source,#csvData,#delimiter,#enclosure,#priority,{$qFields}");

        if($res == 'SUCCESS') {
               
            $csv = trim($exp->getValue('#csvData'));
            $delimiter = $exp->getValue('#delimiter');
            $enclosure = $exp->getValue('#enclosure');
            $csvRows = explode("\n", $csv);
            
            // Ако първия ред са имена на колони - махаме ги
            if($exp->getValue('#firstRow') == 'columnNames') {
                unset($csvRows[0]);
            }

            // Приемамаме, че сървъра може да импортва по минимум 20 записа в секунда
            set_time_limit( round(count($csvRows)/20) + 10 );
            
            $newCnt = $skipCnt = $updateCnt = 0;

            if(count($csvRows)) { 
                foreach($csvRows as $row) {
                    $rowArr = str_getcsv($row, $delimiter, $enclosure);
                    $rec = new stdClass();
                    
                    foreach($fieldsArr as $name => $caption) {
                        $id = $exp->getValue("#col{$name}");
                        if($id == -1) continue;
                        $rec->{$name} = trim($rowArr[$id]);
                    }

                    $err = $this->normalizeRec($rec);

                    $keyField = $listRec->keyField;
                    
                    // Вземаме стойността на ключовото поле;
                    $key = $rec->{$keyField};

                    // Ако ключа е празен, скипваме текущия ред
                    if(empty($key) || count($err)) {
                        $skipCnt++;
                        continue;
                    }
                    

                    $rec->key = str::convertToFixedKey($key);
                    $rec->listId = $listId;
                    if($exRec = $this->fetch(array("#listId = {$listId} AND #key = '[#1#]'", $rec->key))) {
                        // Ако имаме съществуващ $exRec със същия ключ, имаме две възможности
                        // 1. Да го обновим с новите данни
                        // 2. Да го пропуснем
                        if($exp->getValue('#priority') == 'update') {
                            $rec->id = $exRec->id;
                            $updateCnt++;
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
                    $rec->data =  serialize($data);


                    $this->save($rec);
                }
                $exp->message = "Добавени са {$newCnt} нови записа, обновени - {$updateCnt}, пропуснати - {$skipCnt}";
            } else {
                $exp->message = "Липсват данни за добавяне";
            }

        } elseif($res == 'FAIL') {
            $exp->message = 'Неуспешен опит за импортиране на списък с контакти.';
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
        if($rec->email) {
            $rec->email = strtolower($rec->email);
            if(!type_Email::isValidEmail($rec->email)) {
                $err['email'] = "Некоректен е-меил адрес";
            }
        }
        
        // Валидираме полето, ако е GSM
        if ($rec->mobile) {
            $Phones = cls::get('drdata_Phones');
            $code = '359';
            $parsedTel = $Phones->parseTel($rec->mobile, $code);  
            if(!$parsedTel[0]->mobile) {
                $err['mobile'] = "Некоректен мобилен номер";
            }
            $rec->mobile = $parsedTel[0]->countryCode . $parsedTel[0]->areaCode . $parsedTel[0]->number;
        }
        
        // Валидираме полето, ако е GSM
        if ($rec->fax) {
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
     *
     */
    function getCsvColNames($csvData, $delimiter, $enclosure)
    {
        $rows = explode("\n", $csvData);

        $rowArr = str_getcsv($rows[0], $delimiter, $enclosure);
        $rowArr1 = str_getcsv($rows[1], $delimiter, $enclosure);
        
        if(count($rowArr) != count($rowArr1)) {
            return array();
        }

        return arr::combine(array('-1' => ''), $rowArr);
    }



    /**
     * Импортира CSV от моделите на визитника
     */
    function importCsvFromContacts($className, $groupId)
    {
        $mvc = cls::get($className);

        $cQuery = $mvc->getQuery();

        $cQuery->where("#state != 'rejected' AND #groupList like '%|{$groupId}|%'");

        while($cRec = $cQuery->fetch()) {
            $rCsv = '';
            foreach($mvc->fields as $field => $dummy) {
                
                $type = $mvc->fields[$field]->type;
                    
                if ($type instanceof type_Key) {
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

			    if (preg_match( '/\\r|\\n|,|"/', $value )) {
				    $value = '"' . str_replace('"', '""', $value) . '"';
			    }
						
				$rCsv .= ($rCsv ? "," : "") . $value;

                if(!$haveColumns) {
                    $columns .= ($columns ? "," : "") . ($mvc->fields[$field]->caption ? $mvc->fields[$field]->caption : $filed);
                }
            }
            $haveColumns = TRUE;

            $csv .= $rCsv . "\n";
        }

        $csv = $columns . "\n" . $csv;

        return $csv;
    } 
}