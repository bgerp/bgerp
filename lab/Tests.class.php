<?php

/**
 * Мениджър за тестовете
 */
class lab_Tests extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Лабораторни тестове";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_State,
                             plg_Printing, lab_Wrapper, plg_Sorting, fileman_Files,
                             Methods=lab_Methods, TestDetails=lab_TestDetails, Params=lab_Parameters';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,handler,type,batch,origin,
                             assignor,activatedOn=Активиран,lastChangedOn=Посл. промяна,tools=Пулт,state,searchd';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $details = array('lab_TestDetails');
    
    
    /**
     * Права
     */
    var $canWrite = 'lab,admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'lab,admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('handler', 'varchar(64)', 'caption=Наименование,mandatory');
        $this->FLD('type', 'varchar(64)', 'caption=Вид,notSorting');
        $this->FLD('batch', 'varchar(64)', 'caption=Партида,notSorting');
        $this->FLD('madeBy', 'varchar(255)', 'caption=Изпълнител');
        $this->FLD('origin', 'enum(order=Поръчка,research=Разработка,external=Външна)', 'caption=Произход,notSorting');
        $this->FLD('assignor', 'varchar(255)', 'caption=Възложител');
        $this->FLD('note', 'richtext', 'caption=Описание,notSorting');
        $this->FLD('activatedOn', 'datetime', 'caption=Активиран на,input=none,notSorting');
        $this->FLD('lastChangedOn', 'datetime', 'caption=Последна промяна,input=none,notSorting');
        $this->FLD('state', 'enum(draft=Чернова,active=Активен,rejected=Изтрит)', 'caption=Статус,input=none,notSorting');
        $this->FLD('searchd', 'text', 'caption=searchd, input=none, notSorting');
    }
    
    
    /**
     * Сортиране преди извличане на записите
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#activatedOn', 'DESC');
        $data->query->orderBy('#createdOn', 'DESC');
    }
    
    
    /**
     * Изпълнява се преди запис на теста
     * Ако е нов записа, статуса му става 'draft'
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    function on_BeforeSave($mvc,&$id,$rec)
    {
        // Prepare #state
        if (!$rec->state) {
            $rec->state = 'draft';
        }
        
        // Prepare #searchd
        $rec->searchd = $rec->handler . " " . $rec->note;
        $rec->searchd = core_SearchMysql::normalizeText($rec->searchd);
    }
    
    
    /**
     *  Ако записа е със статус 'rejected', не се виждат иконките за изтриване и редактиране в таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if ($rec->state == 'rejected') {
            $cutPos = strrpos($row->tools->content, '<a ');
            $row->tools->content = substr($row->tools->content, 0, $cutPos) . "</div>";
            $cutPos = strrpos($row->tools->content, '<a ');
            $row->tools->content = substr($row->tools->content, 0, $cutPos) . "</div>";
        }
    }
    
    
    /**
     * Шаблон за теста
     * При статус 'draft' имаме бутон 'Активирай'
     * При статус 'active' имаме бутон 'Reject'
     * При статус 'rejected' нямаме бутон за смяна на статуса
     *
     * @param stdClass $data
     * @return core_Et $viewSingle
     */
    function renderSingleLayout_($data)
    {
        // Count active tests
        $query = $this->getQuery();
        
        $countActiveTests = 0;
        
        while($rec = $query->fetch("#state = 'active'")) {
            $countActiveTests++;
        }
        // END Count active tests
        
        
        $TestDetails = cls::get('lab_TestDetails');
        $queryTestDetails = $TestDetails->getQuery();
        
        while($rec = $queryTestDetails->fetch("#testId = {$data->rec->id}")) {
            $resTestDetails = $rec;
        }
        
        if ($data->rec->state == 'draft') {
            if ($resTestDetails != NULL) {
                $data->toolbar->addBtn('Активирай', array('Ctr' => $this,
                    'Act' => 'activateTest',
                    'id' => $data->rec->id,
                    'ret_url' => TRUE));
            }
        } elseif ($data->rec->state == 'active') {
            $data->toolbar->addBtn('Reject', array('Ctr' => $this,
                'Act' => 'rejectTest',
                'id' => $data->rec->id,
                'ret_url' => TRUE));
        }
        
        if ($resTestDetails != NULL && $countActiveTests > 1) {
            $data->toolbar->addBtn('Сравни', array('Ctr' => $this,
                'Act' => 'CompareTwoTests',
                'id' => $data->rec->id,
                'ret_url' => TRUE));
        }
        
        // Подготвяне на детайлите
        if( count($this->details) ) {
            foreach($this->details as $var => $className) {
                $detailsTpl .= "[#Detail{$var}#]";
            }
        }
        
        $viewSingle = cls::get('lab_tpl_ViewSingleLayoutTests', array('data' => $data));
        $viewSingle->replace(new ET($detailsTpl), 'detailsTpl');
        
        return $viewSingle;
    }
    
    
    /**
     * Смяна статута на 'active'
     *
     * @return core_Redirect
     */
    function act_ActivateTest()
    {
        $id = Request::get('id', 'int');
        
        $recForActivation = new stdClass;
        
        $query = $this->getQuery();
        
        while($rec = $query->fetch("#id = {$id}")) {
            $recForActivation = $rec;
        }
        
        $recForActivation->state = 'active';
        $recForActivation->activatedOn = dt::verbal2mysql();
        $this->save($recForActivation);
        
        return new Redirect(array($this, 'single', $id));
    }
    
    
    /**
     * Смяна статута на 'rejected'
     *
     * @return core_Redirect
     */
    function act_RejectTest()
    {
        $id = Request::get('id', 'int');
        
        $recForReject = new stdClass;
        
        $query = $this->getQuery();
        
        while($rec = $query->fetch("#id = {$id}")) {
            $recForReject = $rec;
        }
        
        $recForReject->state = 'rejected';
        $this->save($recForReject);
        
        return new Redirect(array($this, 'single', $id));
    }
    
    
    /**
     * Промяна на поведението при action 'Delete'
     *
     * @see core/core_Manager::act_Delete()
     * @return core_Redirect
     */
    function act_Delete()
    {
        $id = Request::get('id', 'int');
        
        $recForDelete = new stdClass;
        
        $query = $this->getQuery();
        
        while($rec = $query->fetch("#id = {$id}")) {
            $recForDelete = $rec;
        }
        
        if ($recForDelete->state == 'draft') {
            $this->delete($recForDelete->id);
        } elseif ($recForDelete->state == 'active') {
            $recForDelete->state = 'rejected';
            $this->save($recForDelete);
        } elseif ($recForDelete->state == 'rejected') {
            // alert ...
        }
        
        return new Redirect(array($this, 'List'));
    }
    
    
    /**
     * Променя заглавието на формата при редактиране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        if($data->form->rec->id) {
            $data->form->title = "Редактиране на тест \"" . $mvc->getVerbal($data->form->rec, 'handler') . "\"";
        } else {
            $data->form->title = "Създаване на тест";
        }
    	
    }
    
    
    /**
     * Сравнение на два теста
     *
     * @return core_Et $tpl
     */
    function act_CompareTwoTests()
    {
        $cRec = new stdClass;
        
        $form = cls::get('core_form', array('method' => 'GET'));
        $TestDetails = cls::get('lab_TestDetails');
        $Methods = cls::get('lab_Methods');
        $Params = cls::get('lab_Parameters');
        
        // Prepare left test
        $leftTestId = Request::get('id', 'int');
        $leftTestName = $this->fetchField($leftTestId, 'handler');
        
        // Prepare right test
        $queryRight = $this->getQuery();
        
        while($rec = $queryRight->fetch("#id != {$leftTestId} AND state='active'")) {
            $rightTestSelectArr[$rec->id] = $rec->handler;
        }
        // END Prepare right test
        
        // Prepare form
        $form->title = "Сравнение на тест 'No " . $leftTestId . ". " . $leftTestName . "' с друг тест";
        $form->FNC('leftTestId', 'int', 'input=none');
        $form->FNC('rightTestId', 'int', 'caption=Избери тест');
        $form->showFields = 'rightTestId';
        $form->view = 'vertical';
        $form->toolbar->addSbBtn('Сравни');
        $form->setOptions('rightTestId', $rightTestSelectArr);
        // END Prepare form
        
        $cRec = $form->input();
        $formSubmitted = (boolean) count((array) $cRec);
        
        // Ако формата е submit-ната
        if ($formSubmitted) {
            // Left test
            $cRec->leftTestId = $leftTestId;
            $rightTestName = $this->fetchField($cRec->rightTestId, 'handler');
            
            $queryTestDetailsLeft = $TestDetails->getQuery();
            
            while($rec = $queryTestDetailsLeft->fetch("#testId = {$cRec->leftTestId}")) {
                $testDetailsLeft[] = (array) $rec;
            }
            // END Left test
            
            // Right test
            $queryTestDetailsLeft = $TestDetails->getQuery();
            
            while($rec = $queryTestDetailsLeft->fetch("#testId = {$cRec->rightTestId}")) {
                $testDetailsRight[] = (array) $rec;
            }
            // END Right test
            
            // allParamsArr
            $queryAllParams = $Params->getQuery();
            
            while($rec = $queryAllParams->fetch("#id != 0")) {
                $allParamsArr[$rec->id] = $rec->name;
            }
            
            // allMethodsArr
            $queryAllMethods = $Methods->getQuery();
            
            while($rec = $queryAllMethods->fetch("#id != 0")) {
                $allMethodsArr[$rec->id]['methodName'] = $rec->name;
                $allMethodsArr[$rec->id]['paramId'] = $rec->paramId;
                $allMethodsArr[$rec->id]['paramName'] = $allParamsArr[$rec->paramId];
            }
            
            // Prepare $methodsUnion
            {
                foreach ($testDetailsLeft as $lRec) {
                    $methodsLeft[] = $lRec['methodId'];
                }
                
                foreach ($testDetailsRight as $rRec) {
                    $methodsRight[] = $rRec['methodId'];
                }
                
                $methodsUnion = array_unique(array_merge($methodsLeft, $methodsRight));
            }
            
            // END Prepare $methodsUnion
            
            //      
            $counter = 0;
            $tableRow = array();
            $tableData = array();
            
            // Prepare table data for compare two tests
            foreach ($methodsUnion as $methodId) {
                $counter++;
                $tableRow['counter'] = $counter;
                $tableRow['methodName'] = $allMethodsArr[$methodId]['methodName'];
                $tableRow['paramName'] = $allMethodsArr[$methodId]['paramName'];
                
                $tableRow['resultsLeft'] = "---";
                
                foreach($testDetailsLeft as $v) {
                    if ($v['methodId'] == $methodId) {
                        $tableRow['resultsLeft'] = $v['results'];
                    }
                }
                
                $tableRow['resultsRight'] = "---";
                
                foreach($testDetailsRight as $v) {
                    if ($v['methodId'] == $methodId) {
                        $tableRow['resultsRight'] = $v['results'];
                    }
                }
                
                $tableData[] = $tableRow;
            }
            
            $table = cls::get('core_TableView', array('mvc' => $this));
            
            $data->listFields = arr::make($data->listFields, TRUE);
            
            $tpl = $table->get($tableData, "counter=N,methodName=Метод,paramName=Параметър,resultsLeft=Тест No {$cRec->leftTestId},resultsRight=Тест No {$cRec->rightTestId}");
            
            $tpl->prepend("<div style='margin-bottom: 20px;'>
                               <b>Сравнение на тестове</b>
                               <br/>" . $cRec->leftTestId . ". " . $leftTestName . "
                               <br/>" . $cRec->rightTestId . ". " . $rightTestName . "
                           </div>");
            // END Prepare table data for compare two tests
            
            // Prepare html table
            $viewCompareTests .= "<style type='text/css'>
                                  TABLE.listTable td {background: #ffffff;}
                                  TABLE.listTable TR.title td {background: #f6f6f6;}
                                  </style>";
            $viewCompareTests .= "<table class='listTable'>";
            $viewCompareTests .= "<tr>
                                      <td colspan='5' style='text-align: center;'>
                                          <b>Сравнение на тестове</b>
                                          <br/>" . $cRec->leftTestId . ". " . $leftTestName . "
                                          <br/>" . $cRec->rightTestId . ". " . $rightTestName . "
                                      </td>
                                  </tr>";
            $viewCompareTests .= "<tr class='title'>
                                     <td>#</td>
                                     <td>Метод</td>
                                     <td>Параметър</td>
                                     <td>Тест № " . $cRec->leftTestId . "</td>
                                     <td>Тест № " . $cRec->rightTestId . "</td>
                                  </tr>";
            
            foreach ($tableData as $tableRow) {
                $viewCompareTests .= "<tr>
                                          <td>" . $tableRow['counter'] . "</td>
                                          <td>" . $tableRow['methodName'] . "</td>
                                          <td>" . $tableRow['paramName'] . "</td>
                                          <td style='text-align: " . ($tableRow['resultsLeft'] == '---' ? 'center; background: #f0f0f0' : 'right') . ";'>" . nl2br($tableRow['resultsLeft']) . "</td>
                                          <td style='text-align: " . ($tableRow['resultsRight'] == '---' ? 'center; background: #f0f0f0' : 'right') . ";'>" . nl2br($tableRow['resultsRight']) . "</td>
                                      </tr>";
            }
            
            $viewCompareTests .= "</table>";
            // END Prepare html table
            
            return $this->renderWrapping($tpl);
        } else {
            return $this->renderWrapping($form->renderHtml());
        }
    }
    
    
    /**
     * Филтър
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        // Check wether the table has records
        $hasRecords = $this->fetchField("#id != 0", 'id');
        
        if ($hasRecords) {
            $data->listFilter->title = 'Филтър';
            $data->listFilter->view = 'vertical';
            $data->listFilter->toolbar->addSbBtn('Филтрирай');
            $data->listFilter->FNC('dateStartFilter', 'date', 'caption=От дата,placeholder=От дата');
            $data->listFilter->FNC('dateEndFilter', 'date', 'caption=До дата,placeholder=До дата');
            $data->listFilter->FNC('paramIdFilter', 'key(mvc=lab_Parameters,select=name, allowEmpty)', 'caption=Параметри');
            $data->listFilter->FNC('searchString', 'varchar(255)', 'caption=Търсене,placeholder=Търсене');
            $data->listFilter->showFields = 'dateStartFilter, dateEndFilter, paramIdFilter, searchString';
            
            // Активиране на филтъра
            $data->listFilter->rec = $data->listFilter->input();
            
            // Ако филтъра е активиран
            if ($data->listFilter->isSubmitted()) {
                // Prepare $condDateStartFilter
                $condDateStartFilter = NULL;
                
                if ($data->listFilter->rec->dateStartFilter) {
                    $condDateStartFilter = "#activatedOn >= '{$data->listFilter->rec->dateStartFilter}'";
                }
                
                // Prepare $condDateEndFilter
                $condDateEndFilter = NULL;
                
                if ($data->listFilter->rec->dateEndFilter) {
                    $dateEndFilter = $data->listFilter->rec->dateEndFilter;
                    
                    // variant 1
                    // $dateEndFilter = dt::addDays(1, $dateEndFilter);
                    // $condDateEndFilter = "#activatedOn < '{$dateEndFilter}'";
                    
                    // variant 2
                    // $data->listFilter->rec->dateEndFilter = substr($dateEndFilter, 0, 10) . " 23:59:59";
                    // $condDateEndFilter = "#activatedOn <= '{$dateEndFilter}'";
                    
                    // variant 3
                    $condDateEndFilter = "#activatedOn < DATE_ADD(DATE('{$dateEndFilter}'), INTERVAL 1 DAY)";
                }
                
                // Prepare $condTestsFilteredByParams
                $condTestsFilteredByParams = NULL;
                
                // Ако имаме избрани параметри от филтъра:
                // 1. Правим масив с техните id-та
                // 2. Търсим за всяко id на параметър от горния масив, кои методи използват тези параметри
                // 3. Търсим записи от TestDetails къде има поле #menuId, което е сред елементите на масива с избраните методи 
                // 4. От избраните записи от TestDetails правим масив с id-тата на тестовете 
                // 5. Правим заявка, която вади тестовете, чийто id-та са IN (масива с id-та на избраните тестове)   
                if ($data->listFilter->rec->paramIdFilter) {
                    $selectedParamsArr = type_Keylist::toArray($data->listFilter->rec->paramIdFilter);
                    
                    // If some params are selected in the filter 
                    if (count($selectedParamsArr)) {
                        // Prepare array with method Id-s (which methods have the selected params)
                        $methodsArr = array();
                        $condMethods = NULL;
                        
                        // Add SQL to $condMethods (add $methodId for every method which has the selected #paramId)
                        foreach($selectedParamsArr as $v) {
                            $queryMethods = $mvc->Methods->getQuery();
                            $where = "#paramId = {$v}";
                            
                            while ($recMethods = $queryMethods->fetch($where)) {
                                if (!array_key_exists($recMethods->id, $methodsArr)) {
                                    $methodsArr[$recMethods->id] = $recMethods->name;
                                    $condMethods .= "#methodId = {$recMethods->id} OR ";
                                }
                            }
                        }
                        // END Add SQL to $condMethods (add $methodId for every method which has the selected #paramId)
                        
                        // END Prepare array with method Id-s (which methods have the selected params)
                        
                        if (count($methodsArr)) {
                            // Cut ' OR ' from the end of $condMethods string 
                            $condMethods = substr($condMethods, 0, strlen($condMethods) - 4);
                            
                            // Prepare $testsFilteredByParamsList
                            $queryTestDetails = $mvc->TestDetails->getQuery();
                            
                            $testsFilteredByParamsList = "";
                            
                            while ($recTestDetails = $queryTestDetails->fetch($condMethods)) {
                                $testsFilteredByParamsList .= $recTestDetails->testId . ",";
                            }
                            
                            if (strlen($testsFilteredByParamsList)) {
                                // Cut ',' from the end of $testsFilteredByParamsList string 
                                $testsFilteredByParamsList = substr($testsFilteredByParamsList, 0, strlen($testsFilteredByParamsList) - 1);
                                
                                $condTestsFilteredByParams = "#id IN ({$testsFilteredByParamsList})";
                            } else {
                                // Няма тестове, в които да са използвани избраните параметри
                                $condTestsFilteredByParams = "1=2";
                            }
                            // END Prepare $testsFilteredByParamsList
                        } else {
                            // Няма методи, в които да са използвани избраните параметри
                            $condTestsFilteredByParams = "1=3";
                        }
                    }
                    // END If params are selected in the filter    
                }
                // END Prepare $condTestsFilteredByParams
                
                // Prepare $condSearchString
                $condSearchString = NULL;
                
                if ($data->listFilter->rec->searchString) {
                    $searchString = $data->listFilter->rec->searchString;
                    $searchString = core_SearchMysql::normalizeText($searchString);
                    $searchString = trim($searchString);
                    $searchStringArr = explode(" ", $searchString);
                    
                    // Ако има 'думи' в масива
                    if (count($searchStringArr)) {
                        $condSearchString = "#searchd LIKE '%";
                        
                        // Цикъл за всяка 'дума' от масива
                        foreach ($searchStringArr as $word) {
                            $condSearchString .= " {$word}%";
                        }
                        
                        $condSearchString .= "'";
                    }
                }
                // ENDOF Prepare $condSearchString
                
                // Prepare query
                $data->query->where($condDateStartFilter);
                $data->query->where($condDateEndFilter);
                $data->query->where($condTestsFilteredByParams);
                $data->query->where($condSearchString);
            }
            // END Ако филтъра е активиран
            
            // Сортиране на записите по дата на активиране
            $data->query->orderBy('#activatedOn', 'DESC');
            $data->query->orderBy('#createdOn', 'DESC');
        }
    }
}