<?php

/**
 * Мениджър за тестовете
 *
 *
 * @category  bgerp
 * @package   lab
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class lab_Tests extends core_Master
{

    /**
     * Заглавие
     */
    var $title = 'Лабораторни тестове';
    
    /**
     * Дефолтен текст за нотификация
     */
    protected static $defaultNotificationText = "Имате заявен лабораторен тест";
   // protected static $defaultNotificationText = "|*[#handle#] |има актуална версия от|* '[#lastRefreshed#]'";

    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools2,doc_ActivatePlg,plg_Clone,doc_DocumentPlg,plg_Printing,
                     lab_Wrapper, plg_Sorting, bgerp_plg_Blank, doc_plg_SelectFolder,planning_plg_StateManager';

    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;

    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title,type,batch,origin,
                       assignor,activatedOn=Активиран,lastChangedOn=Последно';

    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';

    /**
     * Детайла, на модела
     */
    var $details = 'lab_TestDetails';

    /**
     * Кой може да активира задачата
     */
    public $canActivate = 'ceo,lab,masterLab';

    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canEdit = 'ceo,lab,masterLab';

    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = 'ceo,lab';

    /**
     * Роли, които могат да записват
     */
    var $canWrite = 'lab,ceo';

    /**
     * Кой има право да чете?
     */
    var $canRead = 'lab,ceo';

    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'lab,ceo';

    /**
     * Кой може да го разглежда?
     */
    var $canList = 'lab,ceo';

    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'lab,ceo';

    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Лабораторен тест';

    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/ruler.png';

    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'lab/tpl/SingleLayoutTests.shtml';

    /**
     * Абревиатура
     */
    var $abbr = "Lab";

    /**
     * Групиране на документите
     */
    var $newBtnGroup = "18.1|Други";

    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,lab,masterLab';

    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders';

    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'lab_TestDetails';

    
    public $canCompare = 'ceo, lab, masterLab';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'title';

    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('referention', 'set()','caption=Референтен');
        $this->FLD('type', 'varchar(64)', 'caption=Образец,after=referention,notSorting');
        $this->FLD('provider', 'varchar(64)', 'caption=Доставчик,notSorting');
        $this->FLD('batch', 'varchar(64)', 'caption=Партида,notSorting');
        
        $this->FLD('note', 'richtext(bucket=Notes)', 'caption=Описание,notSorting');
        $this->FLD('parameters', 'keylist(mvc=lab_Parameters,select=name)', 'caption=Параметри,notSorting,after=bringing');
        $this->FLD('bringing', 'enum(vendor=Възложителя,performer=Изпълнителя)', "caption=Мострата се доставя от,maxRadio=2,columns=2,after=batch");
        $this->FLD('sharedUsers', 'userList(roles=powerUser)', 'caption=Нотифициране->Потребители,mandatory');
        $this->FLD('activatedOn', 'datetime', 'caption=Активиран на,input=none,notSorting');
        $this->FLD('lastChangedOn', 'datetime', 'caption=Последна промяна,input=none,notSorting');
        $this->FLD('state', 'enum(draft=Чернова,active=Активен,rejected=Изтрит,pending=Зявка)', 
            'caption=Статус,input=none,notSorting');
        $this->FLD('searchd', 'text', 'caption=searchd, input=none, notSorting');
        
        $this->FNC('title', 'varchar(128)', 'caption=Наименование,input=none,oldFieldName=handler');

    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *            $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        if (!lab_TestDetails::haveRightFor('add')) {
        
            $form->setField('referention', 'input=none');
        }
    }
    
    public function on_CalcTitle($mvc, $rec)
    {
        $rec->title = 'xxx' . $rec->id;
        
        $testTitle = $rec->type.'/'.$rec->provider.'/'.$rec->batch;
        
         
        if (is_numeric($rec->referention)){
        
            $testTitle.=' -РЕФЕРЕНТЕН';
        
        }
        $rec->title = $testTitle;
    }

    public static function on_AfterInputeditForm($mvc, &$form)
    {
        
        $rec = $form->rec;

        
        if ($rec->foreignId) {

        $firstDocument = doc_Threads::getFirstDocument(doc_Containers::fetch($rec->foreignId)->threadId);
        
        $handle = $firstDocument->getHandle();
        
       $form->setDefault('batch', "{$handle}");
        
        }
         $form->setDefault('bringing', 'vendor');

      
    }

    /**
     * Преди запис в модела
     */
    public static function on_BeforeSave($mvc, $id, $rec)//
    {
      
        if ($rec->foreignId) {
            
            $rec->originId = $rec->foreignId;
        }
        
        
    }
    
    public static function on_AfterSavePendingDocument($mvc, &$rec)
    {
    	
    	
        self::sendNotification($rec);
        
    }
   
  
    
    
    static function on_AfterPrepareSingle($mvc, &$res, $data)
    {

        $compTest = Mode::get('testCompare_' . $mvc->getHandle($data->rec->id));
       // bp($compTest);
       
        
        if ($compTest) {
            $cRec = $mvc->fetch($compTest);
            $data->row->RefHandle = $mvc->getHandle($compTest);
            $data->row->RefTitle = $mvc->getVerbal($cRec, 'title');
            $data->row->RefType = $mvc->getVerbal($cRec, 'type');
            $data->row->RefProvider = $mvc->getVerbal($cRec, 'provider');
            $data->row->RefBatch = $mvc->getVerbal($cRec, 'batch');
           
        }
        $parameters = array();
        
        $parameters = keylist::toArray($data->rec->parameters);
      
       // bp($data->rec->id);
        foreach ($parameters as $param){
            
      
            $parameter = lab_Parameters::getTitleById($param);
            if (lab_TestDetails::haveRightFor('add')) {
                $parametersStr.=ht::createLink($parameter,
              
                    array(
                        'lab_TestDetails',
                        'add',
                        'testId' => $data->rec->id,
                        'ret_url' => TRUE,
                        'paramName'=>$param
                    ))."<br>";
            }
            
        }
        
        
           
        $data->row->ParametersStr = $parametersStr;
        
    }
    
    
    

    /**
     * Добавя бутоните в лентата с инструменти на единичния изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
    	
    	
        if ($mvc->haveRightFor('compare', $data->rec)) {
            $url = array(
                $mvc,
                'compareTwoTests',
                $data->rec->id,
                'ret_url' => TRUE
            );
            $data->toolbar->addBtn('Сравняване', $url, 
                'id=compare,class=btn-compare,title=Сравняване на два теста,ef_icon=img/16/report.png');
        }
    }

    /**
     * pendingSavedСравнение на два теста
     *
     * @return core_Et $tpl
     */
    function act_CompareTwoTests()
    {
      $this->requireRightFor('compare');
        $cRec = new stdClass();
        
        $leftTestId = Request::get('id', 'int');
        $lRec = $this->fetch($leftTestId);
        expect($lRec);
        
        $this->requireRightFor('compare', $lRec);
        
        $form = cls::get('core_Form');
        
       
        $TestDetails = cls::get('lab_TestDetails');
        $Methods = cls::get('lab_Methods');
        $Params = cls::get('lab_Parameters');
        
        // Prepare left test
        
        $leftTestName = $this->getVerbal($lRec, 'title');
        
        // Prepare right test
        $queryRight = $this->getQuery();
        
        while ($rec = $queryRight->fetch("#id != {$leftTestId} AND state='active'")) {
            
           
          
            $rightTestSelectArr[$rec->id] =$this->getHandle($rec->id)."-".$rec->title;
        }
       
        // END repare right test
        
        // Prepare form
        $form->title = "Сравнение на тест|* 'No " . $leftTestId . ". " . $leftTestName . "' |с друг тест|*";
//         $form->FNC('leftTestId', 'int', 'input=none');
        $form->FNC('rightTestId', 'int', 'caption=Избери тест, mandatory, input');
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');
        $form->setOptions('rightTestId', $rightTestSelectArr);
       
        // END Prepare form
        
       
      
        $cRec = $form->input();
        
     
     //   bp($this->fetch($cRec->rightTestId));
        
        
//         $formSubmitted = (boolean) count((array) $cRec);
        
        // Ако формата е submit-ната
        if ($form->isSubmitted(``)) {
            // Left test
            $cRec->leftTestId = $leftTestId;
            $rightTestName = $this->fetchField($cRec->rightTestId, 'title');
            
            $rRec = $this->fetch($form->rec->rightTestId);
            expect($rRec);
            
            $this->requireRightFor('compare', $rRec);
           
            Mode::setPermanent('testCompare_' . $this->getHandle($lRec->id), $rRec->id);
            
            
            return new Redirect(getRetUrl());
            
          //////////////////////////////////////////////////////////////////////
          
            
            $queryTestDetailsLeft = $TestDetails->getQuery();
            
            while ($rec = $queryTestDetailsLeft->fetch("#testId = {$cRec->leftTestId}")) {
                $testDetailsLeft[] = (array) $rec;
            }
            
            // END Left test
            
            // Right test
            $queryTestDetailsLeft = $TestDetails->getQuery();
            
            while ($rec = $queryTestDetailsLeft->fetch("#testId = {$cRec->rightTestId}")) {
            	
            	
            	
                $testDetailsRight[] = (array) $rec;
            }
            
            // END Right test
            
            // allParamsArr
            $queryAllParams = $Params->getQuery();
            
            while ($rec = $queryAllParams->fetch("#id != 0")) {
                $allParamsArr[$rec->id] = $rec->name;
            }
            
            // allMethodsArr
            $queryAllMethods = $Methods->getQuery();
            
            while ($rec = $queryAllMethods->fetch("#id != 0")) {
                $allMethodsArr[$rec->id]['methodName'] = $rec->name;
                $allMethodsArr[$rec->id]['paramId'] = $rec->paramId;
                $allMethodsArr[$rec->id]['paramName'] = $allParamsArr[$rec->paramId];
            }//
            
            $methodsLeft = $methodsRight = array();
            if (count($testDetailsLeft)) {
                foreach ($testDetailsLeft as $lRec) {
                    $methodsLeft[] = $lRec['methodId'];
                }
            }
            
            if (count($methodsRight)) {
                foreach ($testDetailsRight as $rRec) {
                    $methodsRight[] = $rRec['methodId'];
                }
            }
            
            $methodsUnion = array_unique(array_merge($methodsLeft, $methodsRight));
            
            // END Prepare $methodsUnion
            
            //
            $counter = 0;
            $tableRow = array();
            $tableData = array();
            
            // Prepare table data for compare two tests
            foreach ($methodsUnion as $methodId) {
                $counter ++;
                $tableRow['counter'] = $counter;
                $tableRow['methodName'] = $allMethodsArr[$methodId]['methodName'];
                $tableRow['paramName'] = $allMethodsArr[$methodId]['paramName'];
                
                $tableRow['resultsLeft'] = "---";
                
                if (count($testDetailsLeft)) {
                    foreach ($testDetailsLeft as $v) {
                        if ($v['methodId'] == $methodId) {
                            $tableRow['resultsLeft'] = $v['value'];
                        }
                    }
                }
                
                $tableRow['resultsRight'] = "---";
                
                if (count($testDetailsRight)) {
                    foreach ($testDetailsRight as $v) {
                        if ($v['methodId'] == $methodId) {
                            $tableRow['resultsRight'] = $v['value'];
                        }
                    }
                }
                
                $tableData[] = $tableRow;
            }
            
            $table = cls::get('core_TableView', array(
                'mvc' => $this
            ));
            
            $data = new stdClass();
            $data->listFields = arr::make($this->listFields, TRUE);
            
          
            
            
            $tpl = $table->get($tableData, 
                "counter=N,methodName=Метод,paramName=Параметър,resultsLeft=Тест No {$cRec->leftTestId},resultsRight=Тест No {$cRec->rightTestId}");
            
            $tpl->prepend(
                "<div style='margin-bottom: 20px;'>
                               <b>Сравнение на тестове</b>
                               <br/>" . $cRec->leftTestId . ". " .
                     $leftTestName . "
                               <br/>" . $cRec->rightTestId . ". " .
                     $rightTestName . "
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
                                          <br/>" . $cRec->leftTestId .
                 ". " . $leftTestName . "
                                          <br/>" . $cRec->rightTestId .
                 ". " . $rightTestName . "
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
                                          <td>" . $tableRow['paramName'] .
                     "</td>
                                          <td style='text-align: " .
                     ($tableRow['resultsLeft'] == '---' ? 'center; background: #f0f0f0' : 'right') . ";'>" .
                     nl2br($tableRow['resultsLeft']) .
                     "</td>
                                          <td style='text-align: " .
                     ($tableRow['resultsRight'] == '---' ? 'center; background: #f0f0f0' : 'right') . ";'>" .
                     nl2br($tableRow['resultsRight']) . "</td>
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
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        
      
        // Check wether the table has records
        $hasRecords = $mvc->fetchField("#id != 0", 'id');
        
        if ($hasRecords) {
            $data->listFilter->title = 'Филтър';
            $data->listFilter->view = 'horizontal';
            $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
            $data->listFilter->FNC('dateStartFilter', 'date', 'caption=От,placeholder=От');
            $data->listFilter->FNC('dateEndFilter', 'date', 'caption=До,placeholder=До');
            $data->listFilter->FNC('paramIdFilter', 'key(mvc=lab_Parameters,select=name, allowEmpty)', 
                'caption=Параметри,refreshForm');
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
                    $selectedParamsArr = keylist::toArray($data->listFilter->rec->paramIdFilter);
                    
                    // If some params are selected in the filter
                    if (count($selectedParamsArr)) {
                        // Prepare array with method Id-s (which methods have the selected params)
                        $methodsArr = array();
                        $condMethods = NULL;
                        
                        // Add SQL to $condMethods (add $methodId for every method which has the selected #paramId)
                        foreach ($selectedParamsArr as $v) {
                            $queryMethods = $mvc->Methods->getQuery();
                            $where = "#paramId = {$v}";
                            
                            while ($recMethods = $queryMethods->fetch($where)) {
                                if (! array_key_exists($recMethods->id, $methodsArr)) {
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
                                $testsFilteredByParamsList = substr($testsFilteredByParamsList, 0, 
                                    strlen($testsFilteredByParamsList) - 1);
                                
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
        
        $data->query->orderBy('#activatedOn', 'DESC');
        $data->query->orderBy('#createdOn', 'DESC');
    }

    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	
    	
        if ($action == 'activate') {
              
            if (is_object($rec) && $rec->id) {
                
                $haveDetail = is_object(lab_TestDetails::fetch("#testId = {$rec->id}"));
            } else {
                $haveDetail = FALSE;
            }
            
            if (! $rec->id || $rec->state != 'pending' || ! $haveDetail) {
                $requiredRoles = 'no_one';
                
                return;
            }
        }
        
        if (is_object($rec)) {
            
            if ($action == 'compare') {
                
                $haveOtherTests = is_object(lab_Tests::fetch("#id != {$rec->id}"));
                
                if ($rec->state != 'active' || ! $haveOtherTests) {
                    $requiredRoles = 'no_one';
                    
                    return;
                }
            }
        }
    }

    /**
     * Интерфейсен метод на doc_DocumentIntf
     */
    function getDocumentRow($id)
    {
        if (! $id)
            return;
        
        $rec = $this->fetch($id);
        $title = $this->singleTitle . " " . $rec->title;
        
        $row = new stdClass();
        $row->title = $title;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->authorId = $rec->createdBy;
        $row->recTitle = $title;
        
        return $row;
    }
    
    /**
     * Изпращане на нотификации на споделените потребители
     *
     * @param stdClass $rec
     * @return void
     */
    public static function sendNotification($rec)
    {
        // Ако няма избрани потребители за нотифициране, не се прави нищо
        $userArr = keylist::toArray($rec->sharedUsers);
        if(!count($userArr)) return;
         
        $text = (!empty($rec->notificationText)) ? $rec->notificationText : self::$defaultNotificationText;
        $msg = new core_ET($text);
         
        // Заместване на параметрите в текста на нотификацията
//         if($Driver = self::getDriver($rec)){
//             $params = $Driver->getNotificationParams($rec);
//             if(is_array($params)){
//                 $msg->placeArray($params);
//             }
//         }
         
        $url = array('lab_Test', 'single', $rec->id);
        $msg = $msg->getContent();
         
        // На всеки от абонираните потребители се изпраща нотификацията за промяна на документа
        foreach ($userArr as $userId){
            bgerp_Notifications::add($msg, $url, $userId, $rec->priority);
        }
    }
    
   
    
}

