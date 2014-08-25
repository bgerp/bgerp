<?php



/**
 * Мениджър на баланси
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Balances extends core_Master
{
    /**
     * Константа за начало на счетоводното време
     */
    const TIME_BEGIN = '1970-01-01 02:00:00';
    
    
    /**
     * Заглавие
     */
    var $title = "Оборотни ведомости";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, acc_Wrapper,Accounts=acc_Accounts,plg_Sorting, plg_Printing, plg_AutoFilter';
                    
    
    /**
     * Детайла, на модела
     */
    var $details = 'acc_BalanceDetails';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Оборотна ведомост';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,acc';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,acc';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,acc';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'no_one';
    

    /**
     * Кой може да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * @var acc_Accounts
     */
    var $Accounts;
    

    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'acc/tpl/SingleLayoutBalance.shtml';
    
    
    /**
     * Поле за единичен изглед
     */
    public $rowToolsSingleField = 'periodId';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    var $listFields = 'id, periodId, lastCalculate';
    

    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/table_sum.png';


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('periodId', 'key(mvc=acc_Periods,select=title)', 'caption=Период,mandatory,autoFilter');
        $this->FLD('fromDate', 'date', 'input=none,caption=Период->от,column=none');
        $this->FLD('toDate', 'date', 'input=none,caption=Период->до,column=none');
        $this->FLD('lastCalculate', 'datetime', 'input=none,caption=Последно изчисляване');
    }
    
    
    /**
     * Предефиниране на единичния изглед
     */
    function act_Single()
    {
        if ($accountId = Request::get('accId', 'int')) {
            $this->accountRec = $this->Accounts->fetch($accountId);
        }
        
        return parent::act_Single();
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action)
    {
        if ($mvc->accountRec) {
            if ($action == 'edit' || $action == 'delete') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на титлата в единичния изглед
     */
    static function on_AfterPrepareSingleTitle($mvc, $data)
    {
        if ($mvc->accountRec) {
            $data->row->accountId = acc_Accounts::getRecTitle($mvc->accountRec);
        } else {
            $data->row->accountId = 'Обобщена';
        }

        $data->title = new ET('<span class="quiet">Оборотна ведомост</span> ' . $data->row->periodId);
    }
    
    
    /**
     * След подготовка на тулбара за единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if (!empty($mvc->accountRec)) {
            $data->toolbar->addBtn('Обобщена', array($mvc, 'single', $data->rec->id));
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {
        $Periods = &cls::get('acc_Periods');
        $periodRec = $Periods->fetch($rec->periodId);
        
        $rec->baseBalanceId = $mvc->getBaseBalanceId($periodRec);
        $rec->fromDate = $periodRec->start;
        $rec->toDate = $periodRec->end;
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_AfterSave($mvc, &$id, $rec)
    {
        $mvc->calc($rec);
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy('#toDate', 'DESC');
    }
    

    /**
     * Връща ид-то на базовия баланс
     * @param $periodRec - запис на период
     * @return int $id - ид на базовия баланс
     */
    private function getBaseBalanceId($periodRec)
    {
        $balanceId = NULL;
        
        $Periods = &cls::get('acc_Periods');
        
        if ($prevPeriodRec = $Periods->fetchPreviousPeriod($periodRec)) {
            $balanceId = $this->fetchField("#periodId = {$prevPeriodRec->id}", 'id');
        }
        
        return $balanceId;
    }
    
    
    /**
     * Връща последния баланс, на който крайната дата е преди друга дата
     */
    public function getBalanceBefore($date)
    {
    	$query = self::getQuery();
        $query->orderBy('#toDate', 'DESC');
        $query->limit(1);
        
        return $query->fetch("#toDate < '{$date}'");
    }
    
    
    /**
     * Изчисляване на баланс
     */
    function calc($rec)
    {
        // Вземаме инстанция на детаилите на баланса
        $bD = cls::get('acc_BalanceDetails');

        // Опитваме се да намерим и заредим последния баланс, който може да послужи за основа на този
        $lastRec = $this->getBalanceBefore($rec->fromDate);
        if($lastRec) {
            $bD->loadBalance($lastRec->id);
            $firstDay = dt::addDays(1, $lastRec->toDate);
        } else {
            $firstDay = self::TIME_BEGIN;
        }
        
        // Добавяме транзакциите за периода от първия ден, който не е обхваната от базовия баланс, до края на зададения период
        $bD->calcBalanceForPeriod($firstDay, $rec->toDate);

        // Изтриваме всички детайли за дадения баланс
        $bD->delete("#balanceId = {$rec->id}");
		
        // Записваме баланса в таблицата
        $bD->saveBalance($rec->id);
    }
  

    /**
     * Презичислява балансите за периодите, в които има промяна ежеминутно
     */
    function cron_Recalc()
    {
        // Взема всички периоди (без closed и draft) от най-стария, към най-новия
        // За всеки период, ако има стойност в lastEntry:
        //  - взема съответстващия му баланс (мастера)
        //  - ако няма такъв баланс, то той се изчислява
        //  - ако има такъв баланс, то той се изчислява, само ако неговото поле lastCalc <= lastEntry
        // след преизчисляване на баланс, полето lastCalc се попълва с времето, когато е започнало неговото изчисляване
        // продължава се със слеващия баланс
    	
        $pQuery = acc_Periods::getQuery();
        $pQuery->orderBy('#end', 'ASC');
        $pQuery->where("#state != 'closed'");
        $pQuery->where("#state != 'draft'");
        $lastEntry = self::TIME_BEGIN;
        while($pRec = $pQuery->fetch()) {
            
            $lastEntry = max($lastEntry, $pRec->lastEntry);
           
            if($lastEntry > '1970-01-01 10:00:00') {

                $rec = self::fetch("#periodId = {$pRec->id}");
 
                if(!$rec || ($rec->lastCalculate <= $lastEntry)) {
                    
                    if(!$rec) {
                        $rec = new stdClass(); 
                        $rec->periodId = $pRec->id;
                    }
                   
                    $rec->lastCalculate = dt::verbal2mysql();
                    
                    self::save($rec);
                }
            }
        }
        
        // Пораждаме събитие, че баланса е бил преизчислен
        $data = new stdClass();
        $this->invoke('AfterRecalcBalances', array($data));
    }
    
    
    /**
     * След изчисляване на баланса синхронизира складовите наличности
     */
    public static function on_AfterRecalcBalances(acc_Balances $mvc, &$data)
    {
    	acc_Journal::clearDrafts();
    }
    
    
    /**
     * Връща последно калкулирания баланс
     */
    public static function getLastBalance()
    {
    	$query = static::getQuery();
    	
    	// Подреждаме ги по последно калкулиране и по начална дата в обратен ред
    	$query->orderBy('#lastCalculate,#fromDate', 'DESC');
    	
    	return $query->fetch();
    }
    
    
    /**
     * Ф-я връщаща записи от последния баланс отговарящ ма следните условия
     * 
     * @param mixed $accs     - списък от систем ид-та на сметките
     * @param mixed $itemsAll - списък от пера, за които може да са на произволна позиция
     * @param mixed $items1   - списък с пера, от които поне един може да е на първа позиция
     * @param mixed $items2   - списък с пера, от които поне един може да е на втора позиция
     * @param mixed $items3   - списък с пера, от които поне един може да е на трета позиция
     * @return array          - масив със всички извлечени записи
     */
    public static function fetchCurrent($accs, $itemsAll = NULL, $items1 = NULL, $items2 = NULL, $items3 = NULL)
    {
    	// Кой е последния баланс
    	$balanceRec = static::getLastBalance();
    	
    	// Ако няма запис на последния баланс не се връща нищо
    	if(empty($balanceRec)) return FALSE;
    	
    	// Извличане на данните от баланса в които участват зададените сметки
    	$dQuery = acc_BalanceDetails::getQuery();
    	
    	// Филтриране на заявката на детайлите
    	acc_BalanceDetails::filterQuery($dQuery, $balanceRec->id, $accs, $itemsAll, $items1, $items2, $items3);
	    
    	// Връщане на всички намерени записи
	    return $dQuery->fetchAll();
    }
    
    
    /**
     * Връща крайното салдо на дадена сметка, според подадени записи
     * 
     * @param array $jRecs - масив с данни от журнала
     * @param string $accsd - Масив от сметки на които ще се изчислява крайното салдо
     * @param enum(debit,credit,NULL) $type - кредното, дебитното или крайното салдо
     * @param string $accSysIdFrom - сметка с която кореспондира първата
     * 
     * @return stdClass $res - обект със следната структура:
     * 			->amount - крайното салдо на сметката, ако няма записи е 0
     * 			->recs   - тази част от подадените записи, участвали в образуването на салдото
     */
    public static function getBlAmounts($jRecs, $accs, $type = NULL, $accSysIdFrom = NULL)
    {
    	$res = new stdClass();
    	$res->amount = 0;
    	
    	// Ако няма записи, връщаме празен масив
    	if(!count($jRecs)) return $res;
    	
    	if($type){
    		expect(in_array($type, array('debit', 'credit')));
    	}
    	
    	$newAccArr = array();
    	$accArr = arr::make($accs);
    	expect(count($accArr));
    	
    	// Намираме ид-та на сметките
    	foreach ($accArr as $accSysId){
    		expect($accId = acc_Accounts::getRecBySystemId($accSysId)->id);
    		$newAccArr[] = $accId;
    	}
    	
    	if(isset($accSysIdFrom)){
    		expect($accIdFrom = acc_Accounts::getRecBySystemId($accSysIdFrom)->id);
    	}
    	
    	// За всеки запис
    	foreach ($jRecs as $rec){
    		$add = FALSE;
    		
    		// Ако има кореспондираща сметка и тя не участва в записа, пропускаме го
    		if(isset($accIdFrom) && ($rec->debitAccId != $accIdFrom && $rec->creditAccId != $accIdFrom)) continue;
    		
    		// Изчисляваме крайното салдо
    		if(in_array($rec->debitAccId, $newAccArr)) {
    			if($type === NULL || $type == 'debit'){
    				$res->amount += $rec->amount;
    				$add = TRUE;
    			}
    		}
    		
    		if(in_array($rec->creditAccId, $newAccArr)) {
    			$sign = ($type === NULL) ? -1 : 1;
    			
    			if($type === NULL || $type == 'credit'){
    				$res->amount += $sign * $rec->amount;
    			}
    			
    			$add = TRUE;
    		}
    		
    		// Добавяме записа, участвал в образуването на крайното салдо
    		if($add){
    			$res->recs[$rec->id] = $rec;
    		}
    		
    		$res->amount = round($res->amount, 6);
    	}
    	
    	// Връщане на резултата
    	return $res;
    }
    
    
    /**
     * Ф-я връщаща името на сметка като линк към баланса
     * 
     * @param int $accountId - ид на сметката
     * @param $rec - запис на баланс, ако е NULL взима последния баланс
     * @param $showNum - дали да се показва Номера на сметката до името й
     * @param $showIcon - дали да се показва иконка
     * 
     * @return html $title - името на сметката като линк (ако имаме права)
     */
    public static function getAccountLink($accountId, $rec = NULL, $showNum = TRUE, $showIcon = FALSE)
    {
    	expect($accountRec = acc_Accounts::fetchRec($accountId));
    	$title = acc_Accounts::getVerbal($accountRec, 'title');
    	$num = acc_Accounts::getVerbal($accountRec, 'num');
    	
    	// Ако трябва да се показва num-а го показваме до името на сметката
    	if($showNum){
    		$title = $num . ". " . $title;
    	}
    	
    	// Ако не е подаден баланс, взимаме последния
    	if(!$rec){
    		$rec = static::getLastBalance();
    	}
    	 
    	if ($accountRec->id && strlen($num) >= 3) {
    		if(acc_Balances::haveRightFor('read', $rec)){
    			
    			// Ако има номенклатури, правим линк към обобщението на сметката
    			if ($accountRec->groupId1 || $accountRec->groupId2 || $accountRec->groupId3) {
    				$balImg = ($showIcon) ? array('class' => 'linkWithIcon', 'style' => 'background-image:url(' . sbf('img/16/filter.png') . ');') : NULL;
    				
    				$title = ht::createLink($title,
    						array('acc_Balances', 'single', $rec->id, 'accId' => $accountRec->id), NULL, $balImg);
    			} else{
    				
    				// Ако няма номенклатури, линка е към хронологията на сметката
    				if(acc_BalanceDetails::haveRightFor('history', (object)array())){
    					$balImg = ($showIcon) ? array('class' => 'linkWithIcon', 'style' => 'background-image:url(' . sbf('img/16/clock_history.png') . ');') : NULL;
    					
    					$title = ht::createLink($title,
    							array('acc_HistoryReport', 'History', 'fromDate' => $rec->fromDate, 'toDate' => $rec->toDate, 'accNum' => $accountRec->num), NULL, $balImg);
    				}
    			}
    		}
    	}
    	 
    	// Връщаме линка
    	return $title;
    }
}