<?php



/**
 * Мениджър на индикатори за заплати
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Индикатори
 */
class hr_Indicators extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Показатели';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Показател';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, hr_Wrapper, plg_Sorting, plg_StyleNumbers';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'admin,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,hrMaster';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,hrMaster';

    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'date, docId=Документ, personId, indicatorId, value';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('date', 'date', 'caption=Дата,mandatory');
        $this->FLD('docId', 'int', 'caption=Документ->№,mandatory,tdClass=leftCol');
        $this->FLD('docClass', 'int', 'caption=Документ->Клас,silent,mandatory');
		$this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител,mandatory');
		$this->FLD('indicatorId', 'key(mvc=hr_IndicatorNames,select=name)', 'caption=Показател,smartCenter,mandatory');
        $this->FLD('sourceClass', 'class(interface=hr_IndicatorsSourceIntf,select=title)', 'caption=Показател->Източник,smartCenter,mandatory');
        $this->FLD('value', 'double(smartRound,decimals=2)', 'caption=Стойност,mandatory');

        $this->setDbUnique('date,docId,docClass,indicatorId,sourceClass,personId');
        $this->setDbIndex('docClass,docId');
        $this->setDbIndex('date');
        $this->setDbIndex('indicatorId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {   
        // Ако имаме права да видим визитката
        if(crm_Persons::haveRightFor('single', $rec->personId)){
            $name = crm_Persons::fetchField("#id = '{$rec->personId}'", 'name');
            $row->personId = ht::createLink($name, array ('crm_Persons', 'single', 'id' => $rec->personId), NULL, 'ef_icon = img/16/vcard.png');
        }
        
        if(cls::load($rec->docClass, TRUE)){
        	$Class = cls::get($rec->docClass);
        	if(cls::existsMethod($Class, 'getLink')){
        		$row->docId = cls::get($rec->docClass)->getLink($rec->docId, 0);
        	} else {
        		$row->docId = cls::get($rec->docClass)->getTitleById($rec->docId, 0);
        	}
        } else {
        	$row->docId = "<span class='red'>" . tr('Проблем при зареждането') . "</span>";
        }
    }
    
    
    /**
     * Изпращане на данните към индикаторите
     */
    public static function cron_Update()
    { 
        $timeline = dt::addSecs(-(hr_Setup::INDICATORS_UPDATE_PERIOD * 60  + 10000));
    	self::recalc($timeline);
    }
    
     
    /**
     * Екшън за преизчисляване на индикаторите след дадена дата
     */
    function act_Recalc()
    {
    	requireRole('ceo,hrMaster');
    	
    	$form = cls::get('core_Form');
    	$form->FLD('timeline', 'datetime', 'caption=Oт кога');
    	$form->input();
    	
    	if($form->isSubmitted()){
    		$rec = $form->rec;
    		
    		$this->logWrite("Преизчисляване на индикаторите след '{$rec->timeline}'");
    		self::recalc($rec->timeline);
    		followRetUrl(NULL, 'Индикаторите са преизчислени');
    	}
    	
    	// Добавяне на бутони
    	$form->title = 'Преизчисляване на индикаторите';
    	$form->toolbar->addSbBtn('Преизчисляване', 'save', 'ef_icon = img/16/arrow_refresh.png, title = Запис на документа');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
    	
    	return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(haveRole('ceo,hrMaster')){
    		$data->toolbar->addBtn('Преизчисляване', array($mvc, 'recalc', 'ret_url' => TRUE), 'title=Преизчисляване на индикаторите,ef_icon=img/16/arrow_refresh.png');
    	}
    }
    
    
    /**
     * Рекалкулиране на индикаторите от определена дата
     * 
     * @param datetime $timeline
     * @return void
     */
    private static function recalc($timeline)
    {
    	$periods = self::saveIndicators($timeline, $persons);
    	
    	// Форсиране на лицата в група 'Служители'
    	if(is_array($persons)){
    		foreach ($persons as $personId){
    			crm_Persons::forceGroup($personId, 'employees');
    		}
    	}
    	
    	if(is_array($persons)){
    		foreach($periods as $id => $rec) {
    			self::calcPeriod($rec);
    		}
    	}
    }
    
    
    /**
     * Събиране на информация от всички класове
     * имащи интерфейс hr_IndicatorsSourceIntf
     * 
     * @param date $timeline
     * @param array $persons - лицата
     * @return array $periods - засегнатите периоди
     */
    public static function saveIndicators($timeline, &$persons = array())
    {  
        // Записите за кои документи, трябва да почистим (id-та в ключовете), 
        // оставяйки определени записи (id-та в масива - стойност)
        $forClean = array();
        
        // Масив със записи на счетоводни периоди, които връщаме
        $periods = array();
        
        // Намираме всички класове съдържащи интерфейса
        $docArr = core_Classes::getOptionsByInterface('hr_IndicatorsSourceIntf');
        
        // Ако нямаме източници - нищо не правим
        if(!is_array($docArr) || !count($docArr)) return $periods;
        
        // Зареждаме всеки един такъв клас
        foreach ($docArr as $class){
            
            $sMvc = cls::get($class);
            
            // Взимаме връщания масив от интерфейсния метод
            $data = $sMvc->getIndicatorValues($timeline);
           
            if (is_array($data) && count($data)) {
           		
                // Даваме време
                core_App::setTimeLimit(count($data) + 10);

                // По id-то на служителя, намираме от договора му
                // в кой отдел и на каква позиция работи
                foreach($data as $id => $rec){
                    
                    $key = $rec->docClass . '::' . $rec->docId;
                    
                    if(!isset($forClean[$key])) {
                        $forClean[$key] = array();
                    }

                    $periodRec = acc_Periods::fetchByDate($rec->date);
                 
                    // Запомняме за кой период е документа
                    $periods[$periodRec->id] = $periodRec;
                    
                    // Оттеглените източници ги записваме само за почистване
                    if($rec->isRejected === TRUE) continue;
                    
                    $rec->sourceClass = core_Classes::getId($class);

                    $exRec = self::fetch(array("#docClass = {$rec->docClass} AND #docId = {$rec->docId} 
                                                AND #personId = {$rec->personId} 
                                                AND #indicatorId = '{$rec->indicatorId}' AND #sourceClass = {$rec->sourceClass}
                                                AND #date = '{$rec->date}'"));
 
                    $persons[$rec->personId] = $rec->personId;
                     
                    if($exRec) {
                        $rec->id = $exRec->id;
                        $forClean[$key][$rec->id] = $rec->id;

                        // Ако съществува идентичен стар запис - прескачаме
                        if($rec->value == $exRec->value) continue;
                    }
           
                    // Ако имаме уникален запис го записваме
                    self::save($rec);
                    $forClean[$key][$rec->id] = $rec->id;
                }
            }
        }
        
        // Почистване на непотвърдените записи
        foreach($forClean as $doc => $ids) { 
            list($docClass, $docId) = explode('::', $doc); 
            $query = self::getQuery();
            $query->where("#docClass = {$docClass} AND #docId = {$docId}");
            if(count($ids)) {
                $query->where("#id NOT IN (" . implode(',', $ids) . ")");
            }
            $query->delete();
        }

        return $periods;
    }
    
    
    /**
     * Калкулира заплащането на всички, които имат трудов договор за посочения период
     */
    private static function calcPeriod($pRec)
    { 
        // Намираме последните, активни договори за назначения, които се засичат с периода
        $ecQuery = hr_EmployeeContracts::getQuery();
        $ecQuery->where("#state = 'active' OR #state = 'closed'");
        $ecQuery->where("#startFrom <= '{$pRec->end}'");
        $ecQuery->where("(#endOn IS NULL) OR (#endOn >= '{$pRec->start}')");
        $ecQuery->orderBy("#dateId", 'DESC');
        
        $ecArr = array();

        while($ecRec = $ecQuery->fetch()) {
            if(!isset($ecArr[$ecRec->personId])) {
                $ecArr[$ecRec->personId] = $ecRec;
            }
        }
        
        $query = self::getQuery();
        $query->where("#date >= '{$pRec->start}' AND #date <= '{$pRec->end}'");
        $query->groupBy("personId");
        while($rec = $query->fetch()) {
            if(!isset($ecArr[$rec->personId])) {
                $ecArr[$rec->personId] = new stdClass();
            }
        }
        
        // Дали да извадим формулата от длъжността
        $replaceFormula = dt::now() < $pRec->end;

        // Подготвяме масив с нулеви стойности
        $names = self::getIndicatorNames();
        $zeroInd = array('BaseSalary' => 0);
        foreach($names as $class => $nArr) {
            foreach($nArr as $n) {
                $zeroInd[$n] = 0;
            }
        }
		
        // За всеки един договор, се опитваме да намерим формулата за заплащането от позицията.
        foreach($ecArr as $personId => $ecRec) {
            $res = (object) array('personId' => $personId,
            					  'periodId' => $pRec->id);
            
            $sum = array();
            
            if(isset($ecRec->positionId)) {
                $posRec = hr_Positions::fetch($ecRec->positionId);
                $salaryBase = (!empty($ecRec->salaryBase)) ? $ecRec->salaryBase : $posRec->salaryBase;
                if(!empty($salaryBase)) {
                    $sum['BaseSalary'] = $salaryBase;
                }
            }
            
            $query = self::getQuery();
            $query->where("#date >= '{$pRec->start}' AND #date <= '{$pRec->end}'");
            $query->where("#personId = {$personId}");
            while($rec = $query->fetch()) {
                $indicator = $names[$rec->sourceClass][$rec->indicatorId];
                $sum[$indicator] += $rec->value;
            }
            
            $prlRec = hr_Payroll::fetch("#personId = {$personId} AND #periodId = {$pRec->id}");
           
            if(empty($prlRec)) {
                $prlRec = new stdClass();
                $prlRec->personId = $personId;
                $prlRec->periodId = $pRec->id;
            }
            
            if($replaceFormula && $ecRec->positionId) {  
                $prlRec->formula = hr_Positions::fetchField($ecRec->positionId, 'formula');
            }
			
            // Ако няма формула. Няма смисъл да се изчислява ведомост
            if(empty($prlRec->formula)){
            	if(isset($prlRec->id)){
            		hr_Payroll::delete($prlRec->id);
            	}
            	
            	continue;
            }
            
            // Изчисляване на заплатата
            $prlRec->salary = NULL;
            if($prlRec->formula) {
                $contex = array();
                foreach($zeroInd as $name => $zero) {
                    if(strpos($prlRec->formula, $name) !== FALSE) {
                        $contex['$' . $name] = $sum[$name] + $zero;
                    }
                }
                
                uksort($contex, "str::sortByLengthReverse");

                // Заместваме променливите и индикаторите
                $expr = strtr($prlRec->formula, $contex);
        		
                if(str::prepareMathExpr($expr) === FALSE) {
                    $prlRec->error = 'Невъзможно изчисление';
                } else {
                    $prlRec->salary = str::calcMathExpr($expr, $success);

                    if($success === FALSE) {
                        $prlRec->error = 'Грешка в калкулацията';
                    }
                }
            } 

            $prlRec->indicators = $sum;
            hr_Payroll::save($prlRec);
        }
    }
    
    
    /**
     * Извличаме имената на идикаторите
     */
    public static function getIndicatorNames()
    {
        // Масив за резултата
        $names = array();

        // Намираме всички класове съдържащи интерфейса
        $docArr = core_Classes::getOptionsByInterface('hr_IndicatorsSourceIntf');
        
        // Ако нямаме източници - нищо не правим
        if(!is_array($docArr) || !count($docArr)) return;

        // Зареждаме всеки един такъв клас
        foreach ($docArr as $class){
            $sourceClass = core_Classes::getId($class);
            if(cls::load($class, TRUE)){
            	$sMvc = cls::get($class);
            	$names[$sourceClass] = $sMvc->getIndicatorNames();
            }
        }

        return $names;
    }
    
    
    /**
     * Подготовка на индикаторите
     * 
     * @param stdClass $data
     */
    public function preparePersonIndicators(&$data)
    {
    	$data->IData = new stdClass();
    	$data->IData->masterMvc = $data->masterMvc;
    	$data->IData->query = self::getQuery();
    	
    	// Позицията от трудовия договор
    	$contractRec = hr_EmployeeContracts::fetch("#state = 'active' AND #personId = {$data->masterId}");
    	
    	$data->IData->render = FALSE;
    	if(Request::get('Tab') != 'PersonsDetails') return;
    	
    	if(!empty($contractRec->positionId)){
    		
    		// Ако има формула за заплата
    		$formula = hr_Positions::fetchField($contractRec->positionId, 'formula');
    		
    		if(!empty($formula)){
    			
    			// Ще се показват само индикаторите участващи във формулата
    			$indicators = self::getIndicatorsInFormula($formula);
    			$indicators = array_keys($indicators);
    			if(count($indicators)){
    				$data->IData->query->in("indicatorId", $indicators);
    				$data->IData->render = TRUE;
    			}
    		}
    	}
    	
    	// Ако няма такива няма да се рендира нищо
    	if($data->IData->render === FALSE) return;
    	
    	// Подготовка на заявката
    	$data->IData->query->where("#personId = {$data->masterId}");
    	$data->IData->query->where("#date >= '{$contractRec->startFrom}'");
    	$data->IData->query->orderBy('date', 'DESC');
    	$data->IData->recs = $data->IData->rows = array();
    	$data->IData->fullQuery = clone $data->IData->query;
    	
    	// Подготивка на формата за търсене
    	$this->prepareListFields($data->IData);
    	$this->prepareListPager($data->IData);
    	$this->prepareListFilter($data->IData);
    	$data->IData->listFilter->method = 'GET';
    	
    	$date = new DateTime();
   		$from = $date->format('Y-m-01');
    	$to = dt::getLastDayOfMonth($from);
    	
    	if($data->IData->pager) {
    		$data->IData->pager->setLimit($data->IData->query);
    	}
    	
    	while($rec = $data->IData->query->fetch()){
    		$data->IData->recs[$rec->id] = $rec;
    		$data->IData->rows[$rec->id] = $this->recToVerbal($rec);
    	}
    	
    	// Сумиране на индикаторите
    	$data->IData->summaryRecs = $data->IData->summaryRows = array();
    	while($sRec = $data->IData->fullQuery->fetch()){
    		if(!array_key_exists($sRec->indicatorId, $data->IData->summaryRecs)){
    			$data->IData->summaryRecs[$sRec->indicatorId] = (object)array('indicatorId' => $sRec->indicatorId, 'value' => 0);
    		}
    		$data->IData->summaryRecs[$sRec->indicatorId]->value += $sRec->value;
    	}
    	
    	// Подготовка на контекста на заплатата
    	$context = array();
    	foreach ($indicators as $iId){
    		$indicatorVerbal = $this->getFieldType('indicatorId')->toVerbal($iId);
    		$value = array_key_exists($iId, $data->IData->summaryRecs) ? $data->IData->summaryRecs[$iId]->value : 0;
    		$context['$' . $indicatorVerbal] = $value;
    		$data->IData->summaryRows[$iId] = (object)array('indicatorId' => $indicatorVerbal , 'value' => core_Type::getByName('double(smartRound)')->toVerbal($value));
    	}
    	
    	if(!empty($contractRec->salaryBase)){
    		$context["$" . 'BaseSalary'] = $contractRec->salaryBase;
    	}
    	
    	// Опит за изчисление на заплатата по формулата
    	$expr = strtr($formula, $context);
    	if(str::prepareMathExpr($expr) === FALSE) {
    		$data->IData->salary = ht::styleIfNegative(tr('Невъзможно изчисление'), -1);
    	} else {
    		$data->IData->salary = str::calcMathExpr($expr, $success);
    		$data->IData->salary = core_type::getByName('double(decimals=2)')->toVerbal($data->IData->salary);
    		$data->IData->salary = ht::styleIfNegative($data->IData->salary, $data->IData->salary);
    		$data->IData->salary .= " " . acc_Periods::getBaseCurrencyCode();
    		$data->IData->salary = ht::createHint($data->IData->salary, $formula, 'notice', TRUE, 'width=12px,height=12px');
    		if($success === FALSE) {
    			$data->IData->salary = ht::styleIfNegative(tr('Грешка в калкулацията'), -1);
    		}
    	}
    }
    
    
    /**
     * Рендиране на индикаторите в корицата на служителите
     * 
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderPersonIndicators($data)
    {
    	if($data->IData->render === FALSE) return new core_ET("");
		$listTableMvc = clone $this;
		$listTableMvc->setField('indicatorId', 'tdClass=leftCol');
    	
    	$tpl = new core_ET(tr("|*<div style='margin-bottom:6px'>[#I_S_TABLE#]</div><div style='text-align:right;'><hr />|Формула|* : <b>[#salary#]</b><hr /></div><div class='inlineForm' style='margin-top:20px'>[#listFilter#][#ListToolbarTop#][#I_TABLE#][#ListToolbarBottom#]</div>"));
    	$tpl->append($this->renderListFilter($data->IData), 'listFilter');
    	
    	// Рендиране на подробната информация на индикаторите
    	unset($data->IData->listFields['personId']);
    	$table = cls::get('core_TableView', array('mvc' => $listTableMvc));
    	$tpl->append($table->get($data->IData->rows, $data->IData->listFields), 'I_TABLE');
    	
    	// Добавяне на пейджера
    	if($data->IData->pager) {
    		$toolbarHtml = $data->IData->pager->getHtml();
    		$tpl->append($toolbarHtml, 'ListToolbarTop');
    		$tpl->append($toolbarHtml, 'ListToolbarBottom');
    	}
    	
    	// Рендиране на сумарната информация за индикаторите
    	$tpl->replace($data->IData->salary, 'salary');
    	$table = cls::get('core_TableView', array('mvc' => $listTableMvc));
    	$tpl->append($table->get($data->IData->summaryRows, 'indicatorId=Име,value=Сума'), 'I_S_TABLE');
    	
    	return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
    	$data->listFilter->setField('personId', 'silent');
    	$data->listFilter->setField('indicatorId', 'silent');
    	
    	$data->listFilter->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));
        $data->listFilter->FLD('period', 'date(select2MinItems=11)', 'caption=Период,silent,placeholder=Всички');
    	$data->listFilter->FLD('document', 'varchar(16)', 'caption=Документ,silent,placeholder=Всички');
    	$data->listFilter->input(NULL, 'silent');
    	
    	$cloneQuery = clone $data->query;
    	$cloneQuery->XPR('minDate', 'date', 'min(#date)');
    	$min = $cloneQuery->fetch()->minDate;
    	
    	$data->listFilter->setOptions('period', array('' => '') + dt::getMonthsBetween($min));
    	$data->listFilter->showFields = 'period,document';
    	$data->query->orderBy('date', "DESC");
    	
    	if(isset($data->masterMvc)){
    		$data->listFilter->FLD('Tab', 'varchar', 'input=hidden');
    		$data->listFilter->setDefault('Tab', 'PersonsDetails');
    		$data->listFilter->setDefault('period', date('Y-m-01'));
    		$data->listFilter->input('period,document,Tab');
    		$data->listFilter->setField('id', 'input=none');
    		$data->listFilter->view = 'horizontal';
    	} else {
    		$data->listFilter->setFieldTypeParams("personId", array('allowEmpty' => 'allowEmpty'));
    		$data->listFilter->setFieldTypeParams("indicatorId", array('allowEmpty' => 'allowEmpty'));
    		$data->listFilter->showFields = 'period,document,personId,indicatorId';
    		$data->listFilter->input('period,document,personId,indicatorId');
    	}

        // В хоризонтален вид
    	$data->listFilter->class = 'simpleForm fleft';
    	
        // Да не може да избира служителя, ако няма права за CEO/HR master
    	if(!haveRole('ceo,hrMaster')){
    		foreach (array('personId') as $fld){
    			$data->listFilter->setReadOnly($fld);
    		}
    	}
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    	
    	
    	// Филтриране на записите
    	if($fRec = $data->listFilter->rec){
    		if(isset($fRec->personId)){
    			$data->query->where("#personId  = '{$fRec->personId}'");
    		}
    		
    		if(isset($fRec->indicatorId)){
    			$data->query->where("#indicatorId = '{$fRec->indicatorId}'");
    		}
    		
    		if(isset($fRec->period)){
    			$to = dt::getLastDayOfMonth($fRec->period);
    			$data->query->where("#date >= '{$fRec->period}' AND #date <= '{$to}'");
    			if(isset($data->fullQuery)){
    				$data->fullQuery->where("#date >= '{$fRec->period}' AND #date <= '{$to}'");
    			}
    		}
    		
    		if(!empty($fRec->document)){
    			if($document = doc_Containers::getDocumentByHandle($fRec->document)){
    				$data->query->where("#docClass = {$document->getClassId()} AND #docId = {$document->that}");
    			}
    		}
    	}
    }
    
    
    /**
     * Връща индикаторите, които са използвани във формула
     * 
     * @param text $formula
     * @return array $res;
     */
    public static function getIndicatorsInFormula($formula)
    {
    	$names = self::getIndicatorNames();
    	$res = array();
    	array_walk_recursive($names, function ($value, $key) use (&$res){$res[$key] = $value;});
    	
    	// Подготвяме масив с нулеви стойности
    	foreach($res as $id => $value) {
    		if(strpos($formula, "$" . $value) === FALSE){
    			unset($res[$id]);
    		}
    	}
    	
    	return $res;
    }
    
    
    /**
     * След подготовка на записите
     */
    protected static function on_AfterPrepareListSummary($mvc, &$res, &$data)
    {
    	$data->listSummary->query->XPR('sum', 'double', 'SUM(#value)');
    	$sum = $data->listSummary->query->fetch()->sum;
    	$sum = (!empty($sum)) ? $sum : 0;
    	$data->listSummary->summary = (object)array('sumRec' => $sum, 'sumRow' => core_Type::getByName('double(decimals=2)')->toVerbal($sum));
    }
    
    
    /**
     * След рендиране на List Summary-то
     */
    protected static function on_AfterRenderListSummary($mvc, &$tpl, $data)
    {
    	if(isset($data->listSummary->summary)){
    		$tpl = new ET(tr('|*' . getFileContent("acc/plg/tpl/Summary.shtml")));
    		$tpl->append(tr('Общо'), 'caption');
    		$tpl->append($data->listSummary->summary->sumRow, 'quantity');
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'list'){
    		
    		// Даване на права до листа само ако има нужните данни в урл-то
    		if(!haveRole('ceo,hrMaster', $userId)){
    			Request::setProtected('force');
    			$isForced = Request::get('force');
    			if(!empty($isForced)){
    				$requiredRoles = 'powerUser';
    			}
    		}
    	}
    }
    
    
    /**
     * Помощна ф-я за събиране на индикаторите в масив
     *
     * @param array $result
     * @param datetime $valior
     * @param int $personId
     * @param int $docId
     * @param int $docClassId
     * @param int $indicatorId
     * @param double $value
     * @param boolean $isRejected
     */
    public static function addIndicatorToArray(&$result, $valior, $personId, $docId, $docClassId, $indicatorId, $value, $isRejected)
    {
    	$key = "{$personId}|{$docClassId}|{$docId}|{$valior}|{$indicatorId}";
    
    	// Ако няма данни, добавят се
    	if(!array_key_exists($key, $result)){
    		$result[$key] = (object)array('date'        => $valior,
										  'personId'    => $personId,
    									  'docId'       => $docId,
    									  'docClass'    => $docClassId,
    									  'indicatorId' => $indicatorId,
    									  'value'       => $value,
    									  'isRejected'  => $isRejected,);
    	} else {
    
    		// Ако има вече се сумират
    		$ref = &$result[$key];
    		$ref->value += $value;
    	}
    }
}