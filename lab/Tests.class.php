<?php

/**
 * Мениджър за тестовете
 *
 *
 * @category  bgerp
 * @package   lab
 * @author    Milen Georgiev <milen@download.bg>
 *            Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
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

    var $canChangestate = 'ceo,lab,masterLab';

    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools2,doc_ActivatePlg,plg_Clone,doc_DocumentPlg,plg_Printing,
                     lab_Wrapper, plg_Sorting,plg_Search, bgerp_plg_Blank, doc_plg_SelectFolder,planning_plg_StateManager';

    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title, vendorNote,providerNote,batch,type,provider,sharedUsers';
    

    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title,type,batch,activatedOn=Активиран';

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
    public $canAdd = 'ceo,lab,masterLab';

    /**
     * Роли, които могат да записват
     */
    var $canWrite = 'lab,ceo,masterLab';

    /**
     * Кой има право да чете?
     */
    var $canRead = 'lab,ceo,masterLab';

    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'lab,ceo,masterLab';

    /**
     * Кой може да го разглежда?
     */
    var $canList = 'lab,ceo,masterLab';

    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'lab,ceo,masterLab';

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
        $this->FLD('referention', 'set()', 'caption=Референтен');
        $this->FLD('type', 'varchar(64)', 'caption=Заглавие,after=referention,notSorting');
        $this->FLD('provider', 'varchar(64)', 'caption=Доставчик,notSorting');
        $this->FLD('batch', 'varchar(64)', 'caption=Партида,notSorting');
        
        $this->FLD('vendorNote', 'richtext(bucket=Notes)', 'caption=Допълнителна информация->От Възложителя,notSorting');
        $this->FLD('providerNote', 'richtext(bucket=Notes)', 'caption=Допълнителна информация->От Лаборанта,notSorting');
        $this->FLD('parameters', 'keylist(mvc=lab_Parameters,select=name)', 
            'caption=Параметри,notSorting,after=bringing');
        $this->FLD('bringing', 'enum(vendor=Възложителя ще я изпрати,performer=Лаборанта да я намери)', 
            "caption=Образец,maxRadio=2,columns=2,after=batch");
        $this->FLD('sharedUsers', 'userList(roles=powerUser,allowEmpty)', 'caption=Нотифициране->Потребители');
        $this->FLD('activatedOn', 'datetime', 'caption=Активиран на,input=none,notSorting');
        $this->FLD('lastChangedOn', 'datetime', 'caption=Последна промяна,input=none,notSorting');
        $this->FLD('state', 'enum(draft=Чернова,active=Активен,rejected=Изтрит,pending=Зaявка,stopped=Спрян,closed=Приключен,wakeup=Събуден)',
            'caption=Статус,input=none,notSorting');
      
        
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
        
        if (! core_Users::haveRole('masterLab,ceo')) {
            
            $form->setField('referention', 'input=none');
        }
    }

    public function on_CalcTitle($mvc, $rec)
    {
        $rec->title = 'xxx' . $rec->id;
        
        $testTitle = $rec->type . '/' . $rec->provider . '/' . $rec->batch;
        
        if (is_numeric($rec->referention)) {
            
            $testTitle .= ' -РЕФЕРЕНТЕН';
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
    public static function on_BeforeSave($mvc, $id, $rec) //
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
       
        if ($data->rec->id && $data->rec->state == 'active') {
            
            $handle = $mvc->getHandle($data->rec->id);
            
            $msg = 'Лабораторен тест ' . $handle . ' е активиран';
            
            $url = array(
                'lab_Tests',
                'single',
                $data->rec->id
            );
            
            $userId = $data->rec->createdBy;
            
            bgerp_Notifications::add($msg, $url, $userId, $rec->priority);
        }
        
        $compTest = Mode::get('testCompare_' . $mvc->getHandle($data->rec->id));
       
        
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
        
       
        foreach ($parameters as $param) {
            
            $parameter = lab_Parameters::getTitleById($param);
            if (lab_TestDetails::haveRightFor('add')) {
                $parametersStr .= ht::createLink($parameter, 
                    
                    array(
                        'lab_TestDetails',
                        'add',
                        'testId' => $data->rec->id,
                        'ret_url' => TRUE,
                        'paramName' => $param
                    )) . "<br>";
            }
        }
        
        $data->row->ParametersStr = $parametersStr;
    }

    /**
     * Добавя бутоните в лентата с инструменти на единичния изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
       
        
       // $data->toolbar->removeBtn('btnClose');
        
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
     *Сравнение на два теста
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
            
            $rightTestSelectArr[$rec->id] = $this->getHandle($rec->id) . "-" . $rec->title;
        }
        
        // END repare right test
        
        // Prepare form
        $form->title = "Сравнение на тест|* 'No " . $leftTestId . ". " . $leftTestName . "' |с друг тест|*";
        // $form->FNC('leftTestId', 'int', 'input=none');
        $form->FNC('rightTestId', 'int', 'caption=Избери тест, mandatory, input');
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');
        $form->setOptions('rightTestId', $rightTestSelectArr);
        
        // END Prepare form
        
        $cRec = $form->input();
       
        
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
    static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {

        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->FNC('dateStart', 'date', 'caption=От,placeholder=От');
        
        $data->listFilter->showFields = 'dateStart';
        
        $data->listFilter->FNC('dateEnd', 'date', 'caption=До,placeholder=До');
        
        $data->listFilter->showFields .= ',dateEnd';
        
        $data->listFilter->FNC('paramIdFilter', 'varchar',
            'caption=Параметри,placeholder=Параметър');
        
        $paramsForChois = self::suggestionsParams();
        
        $data->listFilter->setOptions('paramIdFilter',array(''=>' ')+$paramsForChois);
        
        $data->listFilter->showFields .= ',paramIdFilter';
        
        $data->listFilter->showFields .= ',search';
        
        $data->listFilter->input();
        
        $data->query->where("#state != 'rejected'");
        
        if ($data->listFilter->isSubmitted()) {
        
            if ($data->listFilter->rec->dateStart) {
                
                $data->query->where(array("#activatedOn > '[#1#]'", $data->listFilter->rec->dateStart));
                
            }
            
            if ($data->listFilter->rec->dateEnd) {
                
                $data->query->where(array("#activatedOn < '[#1#]'", $data->listFilter->rec->dateEnd));
                
            }
            
            // Сортиране на записите по дата на активиране
            $data->query->orderBy('#activatedOn', 'DESC');
            
            $data->query->orderBy('#createdOn', 'DESC');
            
            if ($data->listFilter->rec->paramIdFilter) {
                
                list ( $paramsCheckId,$paramName,$methodCheckId) = explode ( '.', $data->listFilter->rec->paramIdFilter);
               
                $data->query->EXT('paramValue', 'lab_TestDetails', 'externalName=value,remoteKey=testId');

                $data->query->EXT('paramName', 'lab_TestDetails', 'externalName=paramName,remoteKey=testId');
                
                $data->query->EXT('methodId', 'lab_TestDetails', 'externalName=methodId,remoteKey=testId');
                
            	$data->query->where(array("#paramName = '[#1#]'", $data->listFilter->rec->paramIdFilter));
                	
            	$data->query->where(array("#methodId = '[#1#]'", $methodCheckId));

                $data->query->orderBy('paramValue', 'DESC');
                
                $data->listFields = arr::make($data->listFields,TRUE);
                
                $mvc->FNC('paramValue', 'double(2)');
                
                $data->listFields['paramValue'] ='Стойност'/* type_Varchar::escape(lab_Parameters::fetchField($data->listFilter->rec->paramIdFilter,'name'))*/;
                 
            }
           
        }
        
        return ;
        
    }

    
    public static function on_AfterRecToVerbal($mvc,$row,$rec,$listFields)
    {
        
        $Double = cls::get('type_Double', array('params' => array('decimals' => 2, 'smartRound' => 'smartRound', 'smartCenter' => 'smartCenter')));
        
        $row->paramValue = $Double->toVerbal($rec->paramValue);
     
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
                
                if ($rec->state == 'draft' || ! $haveOtherTests) {
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
        if (! count($userArr))
            return;
        
        $handle = (lab_Tests::getHandle($rec->id));
        $user = core_Users::getTitleById(core_Users::getCurrent());
        $text = self::$defaultNotificationText . $handle;
        if ($rec->bringing == 'performer') {
            $text .= '.  Трябва да вземете мострата от ' . "{$user}";
        } else {
            
            $text .= '.  Мострата ще Ви бъде доставена';
        }
        $msg = new core_ET($text);
        
        $url = array(
            'lab_Tests',
            'single',
            $rec->id
        );
        $msg = $msg->getContent();
        
        // На всеки от абонираните потребители се изпраща нотификацията за промяна на документа
        foreach ($userArr as $userId) {
            bgerp_Notifications::add($msg, $url, $userId, $rec->priority);
        }
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);
    
        return $this->save($rec, 'modifiedOn,modifiedBy,searchKeywords');
    }
    
    
    
    /**
     * След извличане на ключовите думи
     */
    function on_AfterGetSearchKeywords($mvc, &$searchKeywords, $rec)
    {
        $rec = $mvc->fetchRec($rec);
         
        if (!isset($searchKeywords)) {
            $searchKeywords = plg_Search::getKeywords($mvc, $rec);
        }
    
        if ($rec->id) {
    

            $dQuery = lab_TestDetails::getQuery();
            $dQuery->where("#testId = {$rec->id}");
            while($dRec = $dQuery->fetch()){
                $str1 = lab_TestDetails::getVerbal($dRec, 'paramName');
                $str2 = lab_TestDetails::getVerbal($dRec, 'methodId');
                $str3 = lab_TestDetails::getVerbal($dRec, 'value');
                $str4 = lab_TestDetails::getVerbal($dRec, 'comment');
                $searchKeywords .= " " . plg_Search::normalizeText($str1 . ' ' . $str2 . ' ' . $str3 . ' ' . $str4) . ' ';
            }
        }
 
    }
    
    static function suggestionsParams()
    {
        $metQuery = lab_Methods::getQuery();
        
        while ($methods = $metQuery->fetch()){
            
            $paramKey = $methods->paramId.'.'.type_Varchar::escape(lab_Parameters::fetchField($methods->paramId,'name').'.'.$methods->id);
            $paramsArr[$paramKey] = type_Varchar::escape(lab_Parameters::fetchField($methods->paramId,'name').'.'.$methods->abbreviatedName);
            
            
        }
        
       return $paramsArr;
    }
    
}

