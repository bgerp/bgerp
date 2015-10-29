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
    var $loadList = 'plg_RowTools, acc_Wrapper,Accounts=acc_Accounts,plg_Sorting, plg_Printing, plg_AutoFilter, bgerp_plg_Blank';
    
    
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
    var $listFields = 'id, periodId, fromDate, toDate, lastAlternation, lastCalculate';
    
    
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
        $this->FLD('lastAlternation', 'datetime', 'input=none,caption=Последно->Изменение');
        $this->FLD('lastCalculate', 'datetime', 'input=none,caption=Последно->Изчисляване');
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
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(empty($rec->periodId)){
    		$row->periodId = dt::mysql2verbal($rec->fromDate, 'd', NULL, FALSE) . "-" . dt::mysql2verbal($rec->toDate, 'd F Y', NULL, FALSE);
    	
    		if($fields['-list']){
    			if($mvc->haveRightFor('single', $rec)){
    				$row->periodId = ht::createLink($row->periodId, array($mvc, 'single', $rec->id), NULL, "ef_icon=img/16/table_sum.png, title = Оборотна ведомост {$row->periodId}");
    			}
    		}
    	}
    	
    	if($rec->lastAlternation > $rec->lastCalculate){
    		$row->lastAlternation = "<span class='red'>{$row->lastAlternation}</span>";
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
        
        // Ако показваме по сметка
        if($accId = Request::get('accId', 'int')){
        	$periods = array();
        	$query = $mvc->getQuery();
        	$query->where('#periodId IS NOT NULL');
        	while($bRec = $query->fetch()){
        		$periods[$bRec->periodId] = acc_Periods::fetchField($bRec->periodId, 'title');
        	}
        	
        	// Подготвяме форма за филтриране по период
        	$searchForm = cls::get('core_Form', array('method' => 'GET'));
        	$searchForm->FLD('periodId','key(mvc=acc_Periods,select=title)','input');
        	$searchForm->setOptions('periodId', $periods);
        	$searchForm->FLD('accId','key(mvc=acc_Accounts)','input=hidden,silent');
        	$searchForm->input(NULL, TRUE);
        	$searchForm->rec->periodId = $data->rec->periodId;
        	$searchForm->addAttr('periodId', array('onchange' => "this.form.submit();"));
        	$searchForm->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        	
        	$searchForm->fieldsLayout = new core_ET('[#periodId#]');
        	$searchForm->layout = new core_ET("<form style='display:inline-block;font-size:0.8em' [#FORM_ATTR#]>[#FORM_FIELDS#][#FORM_HIDDEN#]</form>");
        	$searchForm->input();
        	
        	// Ако е събмитната формата редиректваме към същата сметка но за минал период
        	if($searchForm->isSubmitted()){
        		if(isset($searchForm->rec->periodId) && isset($searchForm->rec->accId)){
        			$balanceId = acc_Balances::fetchField("#periodId = {$searchForm->rec->periodId}", 'id');
        	
        			return redirect(array($mvc, 'single', $balanceId, 'accId' => $searchForm->rec->accId));
        		}
        	}
        	
        	$periodRow = $searchForm->renderHtml();
        } else {
        	$periodRow = $data->row->periodId;
        }
        
        // Показваме за кой период е баланса, ако разглеждаме сметка периода е комбобокс и може да се сменя
        $data->title = new ET("<span class='quiet'> " . tr('Оборотна ведомост') . "</span> " . $periodRow);
    }
    
    
    /**
     * След подготовка на тулбара за единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if (!empty($mvc->accountRec)) {
            $data->toolbar->addBtn('Назад', array($mvc, 'single', $data->rec->id), 'ef_icon=img/16/back16.png, title = Върни се обратно');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy('#toDate', 'DESC');
    }


    /**
     * Връща последния баланс, на който крайната дата е преди друга дата и е валиден
     */
    public function getBalanceBefore($date)
    {
        $query = self::getQuery();
        $query->orderBy('#toDate', 'DESC');
        while($rec = $query->fetch("#toDate < '{$date}'")) {
            if(self::isValid($rec)) {

                return $rec;
            }
        }
    }


    /**
     * Маркира балансите, които се засягат от документ с посочения вальор
     *
     * @param string $date дата, към която
     * @return boolean
     */
    public static function alternate($date)
    {
    	static $dateArr = array();
        
        if($dateArr[$date]) {

            return;
        }

        $dateArr[$date] = TRUE;
        
        $now = dt::now();

        $query = self::getQuery();
        
        // Инвалидираме баланса, ако датата е по-малка от края на периода
        while($rec = $query->fetch("#toDate >= '{$date}'")) {
            $rec->lastAlternation = $now;
            self::save($rec, 'lastAlternation');
        }
    }
    
    
    /**
     * Ако е необходимо записва и изчислява баланса за посочения период
     * 
     * @param stdClass Запис на баланс, с попълнени $fromDate, $toDate и $periodId
     * @return boolean Дали е правено преизчисляване
     */
    private function forceCalc($rec)
    {
        // Очакваме начална и крайна дата
        expect(strlen($rec->fromDate) == 10 && strlen($rec->toDate) == 10,  $rec);

        // Ако записа на баланса не за записан, записваме го, за да имаме id
        $exRec = self::fetch("#fromDate = '{$rec->fromDate}' AND #toDate = '{$rec->toDate}'");
        
        if(!$exRec) {
            self::save($rec);
           
        } else {
            $rec = $exRec;
        }

        // Ако не е валиден го преизчисляваме
        if(!self::isValid($rec)) {

            // Днешна дата
            $today = dt::today();
            
            // Ако изчисляваме текущия период, опитваме да преизчислим баланс за предишен работен ден
            if($rec->toDate == dt::getLastDayOfMonth()) {
                if($prevWorkingDay = self::getPrevWorkingDay($today)) {
                	
                    $prevRec = clone($rec);
                    unset($prevRec->id);
                    $prevRec->toDate = $prevWorkingDay;
                    $prevRec->periodId = NULL;
                    self::forceCalc($prevRec);
                    $fromDate = $prevRec->fromDate;
                    $toDate   = $prevRec->toDate;

                    // Намираме и изтриваме всички баланси, които нямат период и не се отнасят за предишния ден
                    $query = self::getQuery();
                    while($delRec = $query->fetch("(#fromDate != '{$fromDate}' OR #toDate != '{$toDate}') AND #periodId IS NULL")) {
                        acc_BalanceDetails::delete("#balanceId = {$delRec->id}");
                        self::delete($delRec->id);
                    }
                }
            }

            self::calc($rec);

            return TRUE;
        }
    }

    
    /**
     * Изчисляване на баланс
     */
    function calc($rec)
    {
    	//$recalcBalance = TRUE;
    	//$count = 1;
        
    	// Вземаме инстанция на детайлите на баланса
    	$bD = cls::get('acc_BalanceDetails');
    	$bD->updatedBalances = array();
    	
    	//while($recalcBalance){
    		
    		//core_Debug::log("RECALC {$rec->id} TRY {$count}");
    		
    		// Зануляваме флага, за да не се преизчисли баланса отново
    		$recalcBalance = FALSE;
    		
    		// Опитваме се да намерим и заредим последния баланс, който може да послужи за основа на този
    		$lastRec = $this->getBalanceBefore($rec->toDate);
    		
    		if($lastRec) {
    			 
    			// Ако има зададен период не е междинен баланса, иначе е
    			$isMiddleBalance = (!empty($lastRec->periodId)) ? FALSE : TRUE;
    			 
    			// Зареждаме баланса
    			$bD->loadBalance($lastRec->id, $isMiddleBalance);
    			$firstDay = dt::addDays(1, $lastRec->toDate);
    			$firstDay = dt::verbal2mysql($firstDay, FALSE);
    		} else {
    			$firstDay = self::TIME_BEGIN;
    		}
    		
    		// Добавяме транзакциите за периода от първия ден, който не е обхваната от базовия баланс, до края на зададения период
    		$recalcBalance = $bD->calcBalanceForPeriod($firstDay, $rec->toDate);
    		
    		// Записваме баланса в таблицата (данните са записани под системно ид за баланс -1)
    		$bD->saveBalance($rec->id);
    		
    		// Изтриваме старите данни за текущия баланс
    		$bD->delete("#balanceId = {$rec->id}");
    		
    		// Ъпдейтваме данните за баланс -1 да са към текущия баланс
    		// Целта е да заместим новите данни със старите само след като новите данни са изчислени до края
    		// За да може ако някой използва данни от таблицата докато не са готови новите да разполага със старите
    		$balanceIdColName = str::phpToMysqlName('balanceId');
    		$bD->db->query("UPDATE {$bD->dbTableName} SET {$balanceIdColName} = {$rec->id} WHERE {$balanceIdColName} = '-1'");
    		
    		// Отбелязваме, кога за последно е калкулиран този баланс
    		$rec->lastCalculate = dt::now();
    		self::save($rec);
    		
    		//$count++;
    	//}
    }
    
    
    /**
     * Рекалкулира баланса
     */
    public function recalc()
    {
    	$lockKey = "RecalcBalances";
    	 
    	// Ако изчисляването е заключено не го изпълняваме
    	if(!core_Locks::get($lockKey, 600, 1)) {
    		$this->logWarning("Изчисляването на баланса е заключено от друг процес");
    		 
    		return;
    	}
    	
    	// Обикаляме всички активни и чакъщи периоди от по-старите, към по-новите
    	// Ако периода се нуждае от прекалкулиране - правим го
    	// Ако прекалкулирането се извършва в текущия период, то изисляваме баланса
    	// до предходния работен ден и селд това до днес
    	
    	
    	$pQuery = acc_Periods::getQuery();
    	$pQuery->orderBy('#end', 'ASC');
    	$pQuery->where("#state != 'closed'");
    	$pQuery->where("#state != 'draft'");
    		 
    	while($pRec = $pQuery->fetch()) {
    			 
    		$rec = new stdClass();
    			 
    		$rec->fromDate = $pRec->start;
    		$rec->toDate = $pRec->end;
    		$rec->periodId = $pRec->id;
    		self::forceCalc($rec);
    	}
    	
    	// Освобождаваме заключването на процеса
    	core_Locks::release($lockKey);
    	
    	// Пораждаме събитие, че баланса е бил преизчислен
    	$data = new stdClass();
    	$this->invoke('AfterRecalcBalances', array($data));
    }
    
    
    /**
     * Презичислява балансите за периодите, в които има промяна ежеминутно
     */
    function cron_Recalc()
    {
    	$this->recalc();
    }


    /**
     * Проверка, дали записът отговаря на валиден баланс
     * 
     * @param stdClass $rec - запис на баланса
     * @return boolean - дали е валиден или не
     */
    public static function isValid($rec)
    {
        if($rec->lastCalculate && ($rec->lastCalculate >= $rec->lastAlternation)) {

            return TRUE;
        }
        
        return FALSE;
    }

    
    /**
     * Намира предходния работен ден в месеца преди посочената дата
     * @todo Да се сложи проверка от календара
     */
    private static function getPrevWorkingDay($date)
    {
        // И имаме по-малък предходен работен ден
        list($y, $m, $d) = explode('-', $date);
        $d = (int) $d;
        for($day = $d - 1; $day > 0; $day--) {
            $wDate = sprintf('%d-%02d-%02d',$y, $m, $day);
            if(!dt::isHoliday($wDate)) {

                return $wDate;
            }
        }
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
        $query->where("#periodId IS NOT NULL");
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
     * ->amount - крайното салдо на сметката, ако няма записи е 0
     * ->recs   - тази част от подадените записи, участвали в образуването на салдото
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
        } else {
        	$rec = static::fetchRec($rec);
        }
        
        if ($accountRec->id && strlen($num) >= 3) {
            if(acc_Balances::haveRightFor('read', $rec) && !Mode::is('text', 'xhtml') && !Mode::is('printing')){
                
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
                            array('acc_BalanceHistory', 'History', 'fromDate' => $rec->fromDate, 'toDate' => $rec->toDate, 'accNum' => $accountRec->num), NULL, $balImg);
                    }
                }
            }
        }
        
        // Връщаме линка
        return $title;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(haveRole('ceo,admin,debug')){
    		$rec = core_Cron::getRecForSystemId('RecalcBalances');
    		$url = array('core_Cron', 'ProcessRun', str::addHash($rec->id), 'forced' => 'yes');
    		
    		$data->toolbar->addBtn('Преизчисляване', $url, 'title=Преизчисляване на баланса,ef_icon=img/16/arrow_refresh.png,target=cronjob');
    	}
    }
}
