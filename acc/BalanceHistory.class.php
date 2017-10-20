<?php



/**
 * Помощен мениджър за показване на хронологията
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_BalanceHistory extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Хронологична счетоводна справка';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'Balance=acc_BalanceDetails, acc_Wrapper, plg_Printing';
    
    
    /**
     * Тук ще се зарежда баланса
     */
    public $Balance;
    
    
    /**
     * Брой записи от историята на страница
     */
    public $listHistoryItemsPerPage = 40;
    
    
    /**
     * Показване на историята на една аналитична сметка
     */
    public function act_History()
    {
        acc_BalanceDetails::requireRightFor('history');
        $this->currentTab = 'Хронология';
        
        expect($accNum = Request::get('accNum', 'int'));
        expect($accId = acc_Accounts::fetchField("#num = '{$accNum}'", 'id'));
        
        $from = Request::get('fromDate', 'Date');
        $to = Request::get('toDate', 'Date');
        
        $ent1 = Request::get('ent1Id', 'int');
        
        if($ent1){
            expect(acc_Items::fetch($ent1));
        }
        
        $ent2 = Request::get('ent2Id', 'int');
        
        if($ent2){
            expect(acc_Items::fetch($ent2));
        }
        
        $ent3 = Request::get('ent3Id', 'int');
        
        if($ent3){
            expect(acc_Items::fetch($ent3));
        }
        
        $this->title = 'Хронологична справка';
        
        // Подготвяне на данните
        $data = new stdClass();
        
        $balanceRec = $this->getBalanceBetween($from, $to);
        
        $data->rec = new stdClass();
        $data->rec->accountId = $accId;
        $data->rec->ent1Id = $ent1;
        $data->rec->ent2Id = $ent2;
        $data->rec->ent3Id = $ent3;
        $data->rec->accountNum = $accNum;
        
        acc_BalanceDetails::requireRightFor('history', $data->rec);
        
        $data->balanceRec = $balanceRec;
        $data->fromDate = $from;
        $data->toDate = $to;
        $data->isGrouped = 'yes';
        
        $this->prepareSingleToolbar($data);
        
        // Подготовка на филтъра
        $this->prepareHistoryFilter($data);
        
        // Подготовка на историята
        $this->prepareHistory($data);
        $data->layoutClass = 'singleView';
        
        // Подготовка на странициране и вербалното представяне
        $this->prepareRows($data);
        
        // Рендиране на историята
        $tpl = $this->renderHistory($data);
        $tpl->removeBlock('toDate');
        $tpl = $this->renderWrapping($tpl);
        
        // Записваме, че потребителя е разглеждал този списък
        $this->logRead("Разглеждане на хронология на сметка");
        
        // Връщаме шаблона
        return $tpl;
    }
    
    
    /**
     * Подготвя страницирането
     */
    public function prepareRows(&$data)
    {
    	// Преизчисляваме пейджъра с новия брой на записите
        $conf = core_Packs::getConfig('acc');
        
        if(!Mode::is('printing')) {
                
           
            $Pager = cls::get('core_Pager', array('itemsPerPage' => $this->listHistoryItemsPerPage));
            $Pager->itemsCount = count($data->recs);
            $Pager->calc();
            $data->pager = $Pager;
            
            $start = $data->pager->rangeStart;
            $end = $data->pager->rangeEnd - 1;
            
            if(count($data->recs)){
            	$data->recs = array_reverse($data->recs, TRUE);
            }
            
            // Махаме тези записи които не са в диапазона на страницирането
            $count = 0;
            
            if(count($data->recs)){
            	foreach ($data->recs as $id => $dRec){
            		if(!($count >= $start && $count <= $end)){
            			unset($data->recs[$id]);
            		}
            		$count++;
            	}
            }
            
            if($data->pager->page == 1){
            	// Добавяне на последния ред
            	if(count($data->recs)){
            		array_unshift($data->recs, $data->lastRec);
            	} else {
            		$data->recs = array($data->lastRec);
            	}
            }
            
            // Ако сме на единствената страница или последната, показваме началното салдо
            if($data->pager->page == $data->pager->pagesCount || $data->pager->pagesCount == 0){
            	$data->recs[] = $data->zeroRec;
            }
        } else {
            // Подготвя средното салдо
            if(!count($data->allRecs)){
                $data->allRecs = array();
            }

            $data->recs = array('zero' => $data->zeroRec) + $data->allRecs + array('last' => $data->lastRec);
        }
        
        // Подготвя средното салдо
        if(!count($data->allRecs)){
        	$data->allRecs = array();
        }
        $data->allRecs =  array('zero' => $data->zeroRec) + $data->allRecs + array('last' => $data->lastRec);
       
        $this->prepareMiddleBalance($data);
        
        // За всеки запис, обръщаме го във вербален вид
        if(count($data->recs)){
        	foreach ($data->recs as $jRec){
        		$data->rows[] = $this->getVerbalHistoryRow($jRec);
        	}
        }
    }
    
    
    /**
     * Подготвя единичния тулбар
     */
    private function prepareSingleToolbar($data)
    {
        if(!Mode::is('printing')){
            $data->toolbar = cls::get('core_Toolbar');
            
            if(acc_BalanceDetails::haveRightFor('history', $data->rec)){
                
                // Бутон за отпечатване
                $printUrl = getCurrentUrl();
                $printUrl['Printing'] = 'yes';
                $data->toolbar->addBtn('Печат', $printUrl, 'id=btnPrint,target=_blank,row=2', 'ef_icon = img/16/printer.png,title=Печат на страницата');
            }
            
            if(acc_Balances::haveRightFor('read')){
                if(empty($data->balanceRec->id)){
                    $data->toolbar->addBtn('Назад', NULL, 'id=btnOverview,error=Невалиден период', 'ef_icon=img/16/back16.png');
                } else {
                    if(empty($data->rec->ent1Id) && empty($data->rec->ent2Id) && empty($data->rec->ent3Id)){
                        $data->toolbar->addBtn('Назад', array($this->Balance->Master, 'single', $data->balanceRec->id), FALSE, 'ef_icon=img/16/back16.png', "title=Обобщена оборотна ведомост,ef_icon=img/16/back16.png");
                    } else {
                        $data->toolbar->addBtn('Назад', array($this->Balance->Master, 'single', $data->balanceRec->id, 'accId' => $data->rec->accountId), FALSE, 'ef_icon=img/16/back16.png', "title=Обобщена оборотна ведомост");
                    }
                }
            }
        }
    }
    
    
    /**
     * Намира балансите между определени дати
     */
    public function getBalanceBetween($from, $to)
    {
        $bQuery = acc_Balances::getQuery();
        $bQuery->where("#fromDate >= '{$from}' && #toDate <= '{$to}'");
        $bQuery->orderBy('id', 'ASC');
        
        if($balanceId = $bQuery->fetch()->id){
            
            return acc_Balances::fetch($balanceId);
        }
        
        return FALSE;
    }
    
    
    /**
     * Взима балансовите периоди
     */
    public static function getBalancePeriods()
    {
        // За начална и крайна дата, слагаме по подразбиране, датите на периодите
        // за които има изчислени оборотни ведомости
        $balanceQuery = acc_Balances::getQuery();
        $balanceQuery->where("#periodId IS NOT NULL");
        $balanceQuery->orderBy("#fromDate", "DESC");
        
        Mode::push('text', 'plain');
        $today = dt::mysql2verbal(dt::addDays(0), 'd.m.Y');
        $yesterday = dt::mysql2verbal(dt::addDays(-1), 'd.m.Y');
        $daybefore = dt::mysql2verbal(dt::addDays(-2), 'd.m.Y');
        $options = array();
        

        // Какви париоди трябва да можем да избираме:
        // Днес
        // Вчера
        // Тази седмици
        // Предишната седмица
        // Последните 7 дни
        // Последните 14 дни
        // Последните 30 дни
        // От текущия месец - 6 месеца назад
        // Тази година
        // Миналата година

        $options[$today . '|' .$today] = 'Днес';
        $options[$yesterday . '|' . $yesterday] = 'Вчера';
        $options[$daybefore . '|' . $daybefore] = 'Завчера';
        
     
        
        while($bRec = $balanceQuery->fetch()){
            $bRow = acc_Balances::recToVerbal($bRec, 'periodId,id,fromDate,toDate,-single');
            $options[$bRow->fromDate . '|' . $bRow->toDate] = $bRow->periodId;
        }
        
        Mode::pop('text');
        
        return $options;
    }
    
    
    /**
     * Подготвя филтъра на историята на перата
     */
    private function prepareHistoryFilter(&$data)
    {
        $data->listFilter = cls::get('core_Form');
        $data->listFilter->method = 'GET';
        $filter = &$data->listFilter;
        $filter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $filter->class = 'simpleForm';
        
        self::addPeriodFields($filter);

        $filter->FNC('accNum', 'int', 'input=hidden');
        $filter->FNC('isGrouped', 'enum(yes=Да,no=Не)', 'input,caption=Групиране');
        $filter->showFields = 'selectPeriod,toDate,fromDate,isGrouped';
        $data->accountInfo = acc_Accounts::getAccountInfo($data->rec->accountId);
        
        foreach (array(3, 2, 1) as $i){
            
        	$ent = $data->rec->{"ent{$i}Id"};
        	if(is_object($data->accountInfo->groups[$i])){
        		$listRec = $data->accountInfo->groups[$i]->rec;
        		$filter->FNC("ent{$i}Id", "acc_type_Item(lists={$listRec->num},select=titleLink,showAll,allowEmpty)", "input,class=w75,caption={$listRec->name}");
        		$filter->showFields = "ent{$i}Id,{$filter->showFields}";
        	} else {
        		$filter->FNC("ent{$i}Id", 'int', 'input=hidden');
        	}
        }
        
        $filter->setDefault('isGrouped', 'yes');
        $filter->setDefault('accNum', $data->rec->accountNum);
        $filter->setDefault('ent1Id', $data->rec->ent1Id);
        $filter->setDefault('ent2Id', $data->rec->ent2Id);
        $filter->setDefault('ent3Id', $data->rec->ent3Id);
                

        $filter->setDefault('fromDate', $data->fromDate);
        $filter->setDefault('toDate', $data->toDate);
        
        // Активиране на филтъра
        $filter->input();
        
        if($filter->isSubmitted()){
            if($filter->rec->fromDate > $filter->rec->toDate){
                $filter->setError('fromDate,toDate', 'Началната дата е по-голяма от крайната');
            }
        }
        
        // Ако има изпратени данни
        if($filter->rec){
        	if($filter->rec->isGrouped){
        		$data->isGrouped = $filter->rec->isGrouped;
        	}
        	
            if($filter->rec->from){
                $data->fromDate = $filter->rec->from;
            }
            
            if($filter->rec->to){
                $data->toDate = $filter->rec->to;
            }
        }
    }
    

    /**
     * Добавя към филтъра полета за избор на период
     */
    public static  function addPeriodFields($filter, $fromDate='fromDate', $toDate='toDate')
    {
        $filter->FLD('selectPeriod', 'autofillMenu', 'input,placeholder=Край,caption=Период');
        $filter->FLD($toDate, 'date(width=6)', 'caption=-,input,inlineTo=selectPeriod,placeholder=Край');
        $filter->FLD($fromDate, 'date(width=6)', 'inlineTo=selectPeriod,input,placeholder=Начало', array('caption' => ' '));
        $toDateField = $filter->getField('selectPeriod');
        $toDateField->type->setMenu(self::getBalancePeriods(), "{$fromDate}|{$toDate}");
    }
    

    /**
     * Подготовка на историята за перара
     *
     * @param stdClass $data
     */
    public function prepareHistory(&$data)
    {
        $rec = &$data->rec;
        
        // Подготвяне на данните на записа
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        
        // Подготовка на вербалното представяне
        $row = new stdClass();
        $row->accountId = acc_Accounts::getTitleById($rec->accountId);
        $row->fromDate = $Date->toVerbal($data->fromDate);
        $row->toDate = $Date->toVerbal($data->toDate);
        $accountRec = acc_Accounts::fetch($rec->accountId);
        
        foreach(range(1, 3) as $i){
            if ($accountRec->{"groupId{$i}"} && $rec->{"ent{$i}Id"}) {
                $row->{"ent{$i}Id"} = acc_Items::getVerbal($rec->{"ent{$i}Id"}, 'titleLink');
            }
        }
        
        $data->row = $row;
        
        // Подготовка на пейджъра
        $this->listItemsPerPage = $this->listHistoryItemsPerPage;
        $this->prepareListPager($data);
        
        // Извличане на всички записи към избрания период за посочените пера
        $accSysId = acc_Accounts::fetchField($rec->accountId, 'systemId');
        
        // Извличаме хронологията за перата
        $isGrouped = ($data->isGrouped !== 'yes') ? FALSE : TRUE;
        $balHistory = acc_ActiveShortBalance::getBalanceHystory($accSysId, $data->fromDate, $data->toDate, $rec->ent1Id, $rec->ent2Id, $rec->ent3Id, $isGrouped, FALSE);
        $data->recs = $balHistory['history'];
        
        $rec->baseAmount = $balHistory['summary']['baseAmount'];
        $rec->baseQuantity = $balHistory['summary']['baseQuantity'];
        $row->baseAmount = $Double->toVerbal($rec->baseAmount);
        $row->baseQuantity = $Double->toVerbal($rec->baseQuantity);
       
        if(round($rec->baseAmount, 4) < 0){
            $row->baseAmount = "<span style='color:red'>{$row->baseAmount}</span>";
        }
        
        if(round($rec->baseQuantity, 4) < 0){
            $row->baseQuantity = "<span style='color:red'>{$row->baseQuantity}</span>";
        }
        
        // Нулевия ред е винаги началното салдо
        $zeroRec = array('docId' => "Начален баланс",
            'valior'      => $data->fromDate,
            'debitAmount' => 0,
            'debitQuantity' => 0,
            'creditQuantity' => 0,
            'creditAmount' => 0,
            'blAmount'   => $rec->baseAmount,
            'blQuantity' => $rec->baseQuantity,
            'ROW_ATTR'   => array('style' => 'background-color:#eee;font-weight:bold'));
       
        $data->allRecs = $data->recs;
        
        if($data->orderField){
        	arr::order($data->recs, $data->orderField, strtoupper($data->orderBy));
        }
        
        // Крайното салдо е изчисленото крайно салдо на сметката
        $rec->blAmount = $balHistory['summary']['blAmount'];
        $rec->blQuantity = $balHistory['summary']['blQuantity'];
        $row->blAmount = $Double->toVerbal($rec->blAmount);
        $row->blQuantity = $Double->toVerbal($rec->blQuantity);
        
        // Последния ред е крайното салдо
        $lastRec = array('docId'          => "Краен баланс",
			             'valior'         => $data->toDate,
			             'blAmount'       => $rec->blAmount,
			             'blQuantity'     => $rec->blQuantity,
			             'debitAmount'    => $balHistory['summary']['debitAmount'],
			             'debitQuantity'  => $balHistory['summary']['debitQuantity'],
			             'creditQuantity' => $balHistory['summary']['creditQuantity'],
			             'creditAmount'   => $balHistory['summary']['creditAmount'],
			             'ROW_ATTR'       => array('style' => 'background-color:#eee;font-weight:bold'));
        
        $data->zeroRec = $zeroRec;
        $data->lastRec = $lastRec;
    }
    
    
    /**
     * Подготовка на вербалното представяне на един ред от историята
     */
    public function getVerbalHistoryRow($rec)
    {
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	
    	$Date = cls::get('type_Date');
    	
    	$arr = array();
        $arr['valior'] = $Date->toVerbal($rec['valior']);
        
        // Ако има отрицателна сума показва се в червено
        foreach (array('debitAmount', 'debitQuantity', 'creditAmount', 'creditQuantity', 'blQuantity', 'blAmount') as $fld){
            
            $arr[$fld] = $Double->toVerbal($rec[$fld]);
            
            if(round($rec[$fld], 6) < 0){
                $arr[$fld] = "<span style='color:red'>{$arr[$fld]}</span>";
            }
        }
        
        try{
        	$Class = cls::get($rec['docType']);
        	$arr['docId'] = (!Mode::isReadOnly()) ? $Class->getShortHyperLink($rec['docId']) : "#" . $Class->getHandle($rec['docId']);
        	$arr['reason'] = $Class->getContoReason($rec['docId'], $rec['reasonCode']);
        } catch(core_exception_Expect $e){
        	if(is_numeric($rec['docId'])){
        		$arr['docId'] = "<span style='color:red'>" . tr("Проблем при показването") . "</span>";
        	} else {
        		$arr['docId'] = $rec['docId'];
        	}
        }
        
        if($rec['ROW_ATTR']){
            $arr['ROW_ATTR'] = $rec['ROW_ATTR'];
        }
        
        return (object)$arr;
    }
    
    
    /**
     * Изчислява средното салдо
     */
    private function prepareMiddleBalance(&$data)
    {
        $recs = $data->allRecs;
       
        // Ако в формата има грешки,
        if(!empty($data->listFilter)){
        	if($data->listFilter->gotErrors()) return;
        }
        
        $tmpArray = array();
        $quantity = $amount = 0;
        
        // Създаваме масив с ключ вальора на документа, така имаме списък с
        // последните записи за всяка дата
        if(count($recs)){
            foreach ($recs as $rec){
                
                // Ако няма друг запис за тази дата добавяме го
                if(empty($tmpArray[$rec['valior']])){
                    $tmpArray[$rec['valior']] = $rec;
                } else {
                    
                    // Ако има запис и текущия е с по ново ид, заместваме съществуващия,
                    // така имаме последните записи за всяка дата
                    if($rec['id'] > $tmpArray[$rec['valior']]['id']){
                        $tmpArray[$rec['valior']] = $rec;
                    }
                }
            }
        }
        
        // Нулираме му ключовете за по-лесно обхождане
        $tmpArray = array_values($tmpArray);
       
        if(count($tmpArray)){
            
            // За всеки запис
            foreach ($tmpArray as $id => $arr){
                
                // Ако не е последния елемент
                if($id != count($tmpArray)-1){
                    
                    // Взимаме дните между следващата дата и текущата от записа
                    $value = dt::daysBetween($tmpArray[$id + 1]['valior'], $arr['valior']);
                } else {
                    
                    // Ако сме на последната дата
                    $value = 1;
                }
                
                // Умножяваме съответните количества по дните разлика
                $quantity += $value * $arr['blQuantity'];
                $amount += $value * $arr['blAmount'];
            }
        }
        
        // Колко са дните в избрания период
        $daysInPeriod = dt::daysBetween($data->toDate, $data->fromDate) + 1;
        
        // Средното салдо е събраната сума върху дните в периода
        $data->rec->midQuantity = $quantity / $daysInPeriod;
        $data->rec->midAmount = $amount / $daysInPeriod;
        
        // Вербално представяне на средното салдо
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $data->row->midQuantity = $Double->toVerbal($data->rec->midQuantity);
        $data->row->midAmount = $Double->toVerbal($data->rec->midAmount);
    }
    
    
    /**
     * Рендиране на историята
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
   	public function renderHistory(&$data)
    {
        // Взимаме шаблона за историята
        $tpl = getTplFromFile('acc/tpl/SingleLayoutBalanceHistory.shtml');
        if(isset($data->layoutClass)){
        	$tpl->replace($data->layoutClass, 'singleClass');
        }
        
        if($data->toolbar){
            $tpl->append($data->toolbar->renderHtml(), 'HystoryToolbar');
        } else {
            $tpl->replace($data->row->fromDate, 'fromDate');
            $tpl->replace($data->row->toDate, 'toDate');
        }
        
        if($data->isReport !== TRUE){
        	unset($data->row->ent1Id,$data->row->ent2Id,$data->row->ent3Id);
        }
        
        // Проверка дали всички к-ва равнят на сумите
        $equalBl = TRUE;
        
        if(count($data->rows)){
            foreach ($data->rows as $row){
                if(trim($row->blQuantity) != trim($row->blAmount)){
                    $equalBl = FALSE;
                }
            }
        }
        
        // Подготвяме таблицата с данните извлечени от журнала
        $table = cls::get('core_TableView', array('mvc' => $this->Balance));
        $data->listFields = array('valior'         => 'Вальор',
					              'docId'          => 'Документ',
					              'reason'         => 'Забележки',
					              'debitQuantity'  => 'Дебит->К-во',
					              'debitAmount'    => 'Дебит->Сума',
					              'creditQuantity' => 'Кредит->К-во',
					              'creditAmount'   => 'Кредит->Сума',
					              'blQuantity'     => 'Остатък->К-во',
					              'blAmount'       => 'Остатък->Сума',
        );
        
        // Ако равнят не показваме количествата
        if($equalBl){
            unset($data->listFields['debitQuantity'], $data->listFields['creditQuantity'], $data->listFields['blQuantity']);
            $data->listFields['debitAmount']  = 'Сума->Дебит';
            $data->listFields['creditAmount'] = 'Сума->Кредит';
            $data->listFields['blAmount']     = 'Сума->Остатък';
        }
        
        // Ако сумите на крайното салдо са отрицателни - оцветяваме ги
        $details = $table->get($data->rows, $data->listFields);
        
        foreach (array('blQuantity', 'blAmount', 'midQuantity', 'midAmount') as $fld){
            if($data->rec->{$fld} < 0){
                $data->row->{$fld} = "<span style='color:red'>{$data->row->{$fld}}</span>";
            }
        }
        
        $tpl->placeObject($data->row);
        
        // Добавяне в края на таблицата, данните от журнала
        $tpl->replace($details, 'DETAILS');
        
        // Рендиране на филтъра
        $tpl->append($this->renderListFilter($data), 'listFilter');
        
        if(!Mode::is('printing')) {
            // Рендиране на пейджъра
            if($data->pager){ 
            	$tpl->append($data->pager->getHtml(), 'PAGER');
            }
        }
        
        
        // Връщаме шаблона
        return $tpl;
    }
}