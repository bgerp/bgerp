<?php



/**
 * Мениджър на баланси
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
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
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('periodId', 'key(mvc=acc_Periods,select=title)', 'caption=Период,mandatory,autoFilter');
        $this->FLD('fromDate', 'date', 'input=none,caption=Период->от,column=none');
        $this->FLD('toDate', 'date', 'input=none,caption=Период->до,column=none');
        $this->FLD('state', 'enum(draft=Горещ,active=Активен,rejected=Изтрит)', 'caption=Тип,input=none');
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
     * След подготовка на записите за листовия изглед
     */
    public static function on_AfterPrepareListRows($mvc, $data)
    {
        if (empty($data->rows)) {
            return;
        }
        
        foreach ($data->rows as $i=>$row) {
            $data->rows[$i]->periodId = ht::createLink(
                $row->periodId, array($mvc, 'single', $data->recs[$i]->id)
            );
        }
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
            $data->toolbar->addBtn('Обобщена ' . $data->row->periodId, array($mvc, 'single', $data->rec->id));
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
     * Извиква се след изчислението на края на баланса, извлича информацията
     * за моментното състояние на склада
     */
    private function extractStoreData()
    {
    	// Извличане на данните за склада от баланса
    	$all = $this->prepareStoreData();
    	
    	// Синхронизиране на складовите продукти с тези от баланса
    	store_Products::sync($all);
    }
    
    
    /**
     * Извлича информацията нужна за ъпдейт на склада
     */
    private function prepareStoredata()
    {
    	$all = array();
    	$query = static::getQuery();
    	$query->orderBy('#lastCalculate', 'DESC');
    	$balanceRec = $query->fetch();
    	
    	// Извличане на сметките по които ще се ситематизират данните
    	$conf = core_Packs::getConfig('store');
    	$storeAccs = keylist::toArray($conf->STORE_ACC_ACCOUNTS);
    	
    	// Филриране да се показват само записите от зададените сметки
    	$dQuery = acc_BalanceDetails::getQuery();
    	foreach ($storeAccs as $sysId){
    		$dQuery->orWhere("#accountId = {$sysId}");
    	}
    	
    	$dQuery->where("#balanceId = {$balanceRec->id}");
    	
    	while($rec = $dQuery->fetch()){
    		if($rec->ent1Id){
    			
    			// Перо 'Склад'
	    		$storeItem = acc_Items::fetch($rec->ent1Id);
	    		
	    		// Перо 'Артикул'
	    		$pItem = acc_Items::fetch($rec->ent2Id);
	    		
	    		// Съмаризиране на информацията за артикул / склад
	    		$index = $storeItem->objectId . "|" . $pItem->classId . "|" . $pItem->objectId;
	    		if(empty($all[$index])){
	    			
	    			// Ако няма такъв продукт в масива, се записва
	    			$all[$index] = $rec->blQuantity;
	    		} else {
	    			
	    			// Ако го има добавяме количеството на записа
	    			$all[$index] += $rec->blQuantity;
	    		}
    		}
    	}
    	
    	// Връщане на групираните крайни суми
    	return $all;
    }
    
    
    public function act_Test()
    {
    	$this->cron_Recalc();
    	$this->extractStoreData();
    	
    	return followRetUrl();
    }
    
    /**
     * Изчисляване на баланс
     */
    function calc($rec)
    {
        // Вземаме инстанция на детаилите на баланса
        $bD = cls::get('acc_BalanceDetails');

        // Опитваме се да намерим и заредим последния баланс, който може да послужи за основа на този
        $query = self::getQuery();
        $query->orderBy('#toDate', 'DESC');
        $query->limit(1);
        $lastRec = $query->fetch("#toDate < '{$rec->fromDate}'");
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
    }
}