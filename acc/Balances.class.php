<?php


/**
 * Мениджър на баланси
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
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
    public $title = 'Оборотни ведомости';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, acc_Wrapper,Accounts=acc_Accounts,plg_Sorting, plg_Printing, bgerp_plg_Blank';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'acc_BalanceDetails';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Оборотна ведомост';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,acc';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,acc';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,acc';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * @var acc_Accounts
     */
    public $Accounts;
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'acc/tpl/SingleLayoutBalance.shtml';
    
    
    /**
     * Поле за единичен изглед
     */
    public $rowToolsSingleField = 'periodId';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'id, periodId, fromDate, toDate, lastAlternation, lastCalculate';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/table_sum.png';
    
    
    /**
     * Текущата сметка
     */
    public $accountRec;
    
    
    /**
     * Максимално допустимо време в секунди за изчисляване на баланс на период
     */
    const MAX_PERIOD_CALC_TIME = 600;
    
    
    /**
     * Ключ за заключване по време на записването
     */
    const saveLockKey = 'Save_Balance_In_Progress';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('periodId', 'key(mvc=acc_Periods,select=title)', 'caption=Период,mandatory,autoFilter');
        $this->FLD('fromDate', 'date', 'input=none,caption=Период->от,column=none');
        $this->FLD('toDate', 'date', 'input=none,caption=Период->до,column=none');
        $this->FLD('lastAlternation', 'datetime(format=smartTime)', 'input=none,caption=Последно->Изменение');
        $this->FLD('lastAlternationDocClass', 'class(interface=acc_TransactionSourceIntf)', 'caption=Последно изменение->Документ клас,input=none,column=none');
        $this->FLD('lastAlternationDocId', 'int', 'input=none,column=none,caption=Последно изменение->Документ ID');
        $this->FLD('lastCalculate', 'datetime(format=smartTime)', 'input=none,caption=Последно->Изчисляване');
        $this->FLD('lastCalculateChange', 'enum(yes,no)', 'input=none,caption=Последно->Нови ст-ти');
    }
    
    
    /**
     * Предефиниране на единичния изглед
     */
    public function act_Single()
    {
        if ($accountId = Request::get('accId', 'int')) {
            $this->accountRec = $this->Accounts->fetch($accountId);
        }
        
        return parent::act_Single();
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $user = null)
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
        if (empty($rec->periodId)) {
            $row->periodId = dt::mysql2verbal($rec->fromDate, 'd', null, false) . '-' . dt::mysql2verbal($rec->toDate, 'd F Y', null, false);
            
            if ($fields['-list']) {
                if ($mvc->haveRightFor('single', $rec)) {
                    $row->periodId = ht::createLink($row->periodId, array($mvc, 'single', $rec->id), null, "ef_icon=img/16/table_sum.png, title = Оборотна ведомост|* {$row->periodId}");
                }
            }
        } else {
            $periodState = acc_Periods::fetchField($rec->periodId, 'state');
            $row->ROW_ATTR['class'] = "state-{$periodState}";
        }
        
        // Добавяме връзка към последния алтерниращ документ
        if ($rec->lastAlternationDocClass && $rec->lastAlternationDocId) {
            $row->lastAlternation .= ht::createLink('↗', array($rec->lastAlternationDocClass, 'single', $rec->lastAlternationDocId));
        }
        
        if ($rec->lastCalculateChange == 'no') {
            $row->lastCalculate .= ' ' . "<span title='При последното изчисляване не е настъпила промяна'>✓</span>";
        }
        
        if ($rec->lastAlternation > $rec->lastCalculate) {
            $row->lastAlternation = ht::createHint($row->lastAlternation, 'Има промяна след последното изчисление на баланса', 'warning');
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на титлата в единичния изглед
     */
    public static function on_AfterPrepareSingleTitle($mvc, $data)
    {
        if ($mvc->accountRec) {
            $data->row->accountId = acc_Accounts::getRecTitle($mvc->accountRec);
        } else {
            $data->row->accountId = 'Обобщена';
        }
        
        // Ако показваме по сметка
        if (Request::get('accId', 'int')) {
            $periods = self::getSelectOptions('DESC', false, true);
            $value = toUrl(array($mvc, 'single', $data->rec->id));
            $periodRow = ht::createSmartSelect($periods, 'periodId', $value, array('class' => 'filterBalanceId'));
        } else {
            $periodRow = $data->row->periodId;
        }
        
        // Показваме за кой период е баланса, ако разглеждаме сметка периода е комбобокс и може да се сменя
        $data->title = new ET("<span class='quiet'> " . tr('Оборотна ведомост') . '</span> ' . $periodRow);
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
    public function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy('#toDate', 'DESC');
    }
    
    
    /**
     * Връща последния баланс, на който крайната дата е преди друга дата и е валиден
     */
    public static function getBalanceBefore($date)
    {
        $query = self::getQuery();
        $query->orderBy('#toDate', 'DESC');
        while ($rec = $query->fetch("#toDate < '{$date}'")) {
            if (self::isValid($rec)) {
                
                return $rec;
            }
        }
    }
    
    
    /**
     * Маркира балансите, които се засягат от документ с посочения вальор
     *
     * @param string $date       Вальорът на алтерниращият документ
     * @param int    $docClassId Класът на алтерниращият документ
     * @param int    $docId      id на алтерниращият документ
     */
    public static function alternate($date, $docClassId, $docId)
    {
        static $dateArr = array();
        if ($dateArr[$date]) {
            
            return;
        }
        $dateArr[$date] = true;
        
        $now = dt::now();
        
        // Ако датата е 
        $alternateWindow = acc_setup::get('ALTERNATE_WINDOW');
        if($alternateWindow) {
            $windowStart = dt::addSecs(-$alternateWindow);
            if($windowStart > $date) return;
        }

        $query = self::getQuery();
        $query->where("#toDate >= '{$date}'");
        
        // Инвалидираме баланса, ако датата е по-малка от края на периода
        while ($rec = $query->fetch()) {
            $rec->lastAlternation = $now;
            $rec->lastAlternationDocClass = $docClassId;
            $rec->lastAlternationDocId = $docId;
            self::save($rec, 'lastAlternation,lastAlternationDocClass,lastAlternationDocId');
        }
    }
    
    
    /**
     * Ако е необходимо записва и изчислява баланса за посочения период
     *
     * @param stdClass Запис на баланс, с попълнени $fromDate, $toDate и $periodId
     *
     * @return bool Дали е правено преизчисляване
     */
    private static function forceCalc(&$rec)
    {
        // Очакваме начална и крайна дата
        expect(strlen($rec->fromDate) == 10 && strlen($rec->toDate) == 10, $rec);
        
        // Ако записа на баланса не за записан, записваме го, за да имаме id
        $exRec = self::fetch("#fromDate = '{$rec->fromDate}' AND #toDate = '{$rec->toDate}'");
        
        if (!$exRec) {
            self::save($rec);
        } else {
            $rec = $exRec;
        }
        
        // Ако не е валиден го преизчисляваме, като всяка от
        // десетте минути след преизчисляването - пак го преизчисляваме
        $isValid = self::isValid($rec, $rec->lastCalculateChange != 'no' ? 10 : 1);
        
        if (!$isValid) {
            
            // Днешна дата
            $today = dt::today();
            
            // Ако изчисляваме текущия период, опитваме да преизчислим баланс за предишен работен ден
            if ($rec->toDate == dt::getLastDayOfMonth()) {
                if ($prevWorkingDay = self::getPrevWorkingDay($today)) {
                    $prevRec = clone($rec);
                    unset($prevRec->id);
                    $prevRec->toDate = $prevWorkingDay;
                    $prevRec->periodId = null;
                    self::forceCalc($prevRec);
                    $fromDate = $prevRec->fromDate;
                    $toDate = $prevRec->toDate;
                    
                    // Намираме и изтриваме всички баланси, които нямат период и не се отнасят за предишния ден
                    $query = self::getQuery();
                    while ($delRec = $query->fetch("(#fromDate != '{$fromDate}' OR #toDate != '{$toDate}') AND #periodId IS NULL")) {
                        acc_BalanceDetails::delete("#balanceId = {$delRec->id}");
                        self::delete($delRec->id);
                    }
                }
            }
            
            self::calc($rec);
            
            // Преизчисляваме първия баланс, в който има промени още веднъж, за да подаде верни данни на следващите
            static $rc1;
            
            self::logDebug("After Calc: {$rec->lastCalculateChange}; rc1 = {$rc1}");
            
            if (!$rc1 && $rec->lastCalculateChange != 'no') {
                self::calc($rec);
                $rc1 = true;
                
                self::logDebug("After Calc2: {$rec->lastCalculateChange}; rc1 = {$rc1}");
            }
            
            return true;
        }
    }
    
    
    /**
     * Изчисляване на баланс
     */
    public static function calc($rec)
    {
        // Вземаме инстанция на детайлите на баланса
        $bD = cls::get('acc_BalanceDetails');
        
        // Опитваме се да намерим и заредим последния баланс, който може да послужи за основа на този
        $lastRec = self::getBalanceBefore($rec->toDate);
        
        if ($lastRec) {
            
            // Ако има зададен период не е междинен баланса, иначе е
            $isMiddleBalance = (!empty($lastRec->periodId)) ? false : true;
            
            // Зареждаме баланса
            $bD->loadBalance($lastRec->id, $isMiddleBalance);
            $firstDay = dt::addDays(1, $lastRec->toDate);
            $firstDay = dt::verbal2mysql($firstDay, false);
        } else {
            $firstDay = self::TIME_BEGIN;
        }
        
        // Добавяме транзакциите за периода от първия ден, който не е обхваната от базовия баланс, до края на зададения период
        $isMiddleBalance = ($rec->periodId) ? false : true;
        $bD->calcBalanceForPeriod($firstDay, $rec->toDate, $isMiddleBalance);
        
        // Записваме баланса в таблицата (данните са записани под системно ид за баланс -1)
        if ($bD->saveBalance($rec->id)) {
            $rec->lastCalculateChange = 'yes';
        } else {
            $rec->lastCalculateChange = 'no';
        }
        
        // Отбелязваме, кога за последно е калкулиран този баланс
        $rec->lastCalculate = dt::now();
        self::save($rec, 'lastCalculate,lastCalculateChange');
    }
    
    
    /**
     * Рекалкулира баланса
     */
    public function recalc()
    {
        $lockKey = 'RecalcBalances';
        
        // Ако изчисляването е заключено не го изпълняваме
        if (!core_Locks::get($lockKey, self::MAX_PERIOD_CALC_TIME, 1)) {
            $this->logNotice('Изчисляването на баланса е заключено от друг процес');
            
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
        
        $rc = true;
        
        while ($pRec = $pQuery->fetch()) {
            $rec = new stdClass();
            $rec->fromDate = $pRec->start;
            $rec->toDate = $pRec->end;
            $rec->periodId = $pRec->id;
            
            // Преизчисляваме първия отворен баланс (когато в него има промени) 9+1 пъти, за да подаде верни данни на следващите
            $j = 0;
            do {
                core_Locks::get($lockKey, self::MAX_PERIOD_CALC_TIME);
                self::forceCalc($rec);
                self::logDebug("After forceCalc: {$rec->lastCalculateChange}; j = {$j}; rc = {$rc}");
            } while ($rec->lastCalculateChange != 'no' && $j++ < 9 && $rc);
            $rc = false;
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
    public function cron_Recalc()
    {
        $this->recalc();
    }
    
    
    /**
     * Проверка, дали записът отговаря на валиден баланс
     *
     * @param stdClass $rec - запис на баланса
     *
     * @return bool - дали е валиден или не
     */
    public static function isValid($rec, $calcMinutesAfter = 0)
    {
        // Ако балансът никога не е калкулиран, значи не е валиден
        if (empty($rec->lastCalculate)) {
            
            return false;
        }
        
        // Ако нямаме никакви записи за периода, значи всичко е ОК
        if (empty($rec->lastAlternation)) {
            
            return true;
        }
        
        // Вземаме предния баланс. Ако той е с по-ново време на изчисление, задължително изчисляваме и този
        $query = self::getQuery();
        $query->limit(1);
        $query->where("#fromDate < '{$rec->fromDate}'");
        $query->orderBy('fromDate', 'DESC');
        $lastRec = $query->fetch();
        
        if ($lastRec && ($lastRec->lastCalculate > $rec->lastCalculate)) {
            
            return false;
        }
        
        // Ако последното изчисляване е $calcMinutesAfter и повече след последната промяна на журнала за периода, значи баланса е валиден
        if (dt::secsBetween($rec->lastCalculate, $rec->lastAlternation) > $calcMinutesAfter * 60) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Намира предходния работен ден в месеца преди посочената дата
     *
     * @todo Да се сложи проверка от календара
     */
    private static function getPrevWorkingDay($date)
    {
        // И имаме по-малък предходен работен ден
        list($y, $m, $d) = explode('-', $date);
        $d = (int) $d;
        for ($day = $d - 1; $day > 0; $day--) {
            $wDate = sprintf('%d-%02d-%02d', $y, $m, $day);
            if (!dt::isHoliday($wDate)) {
                
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
     * Връща последния баланс
     *
     * @return stdClass
     */
    public static function getLastBalance()
    {
        $query = static::getQuery();
        
        // Подреждаме ги по последно калкулиране и по начална дата в обратен ред
        $query->where('#periodId IS NOT NULL');
        $query->orderBy('#toDate', 'DESC');
        
        $today = dt::today();
        $query->where("#fromDate <= '{$today}' AND #toDate >= '{$today}'");
        
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
     *
     * @return array - масив със всички извлечени записи
     */
    public static function fetchCurrent($accs, $itemsAll = null, $items1 = null, $items2 = null, $items3 = null)
    {
        // Кой е последния баланс
        $balanceRec = static::getLastBalance();
        
        // Ако няма запис на последния баланс не се връща нищо
        if (empty($balanceRec)) {
            
            return false;
        }
        
        // Извличане на данните от баланса в които участват зададените сметки
        $dQuery = acc_BalanceDetails::getQuery();
        
        // Филтриране на заявката на детайлите
        acc_BalanceDetails::filterQuery($dQuery, $balanceRec->id, $accs, $itemsAll, $items1, $items2, $items3);
        
        // Връщане на всички намерени записи
        return $dQuery->fetchAll();
    }
    
    
    /**
     * Връща масив с количествата групирани по размерната номенклатура на сметките
     *
     * @param array       $jRecs   - масив с данни от журнала
     * @param string      $accs    - Масив от сметки на които ще се изчислява крайното салдо
     * @param string|NULL $type    - кредното, дебитното или крайното салдо
     * @param string      $accFrom - сметки с които може да кореспондира
     * @params array $items - масив с пера, които трябва да са на посочените позиции
     *
     * @return stdClass $res - К-та групирани по размерната номенклатура
     */
    public static function getBlQuantities($jRecs, $accs, $type = null, $accFrom = null, $items = array())
    {
        $res = array();
        
        // Ако няма записи, връщаме празен масив
        if (!countR($jRecs)) {
            
            return $res;
        }
        
        if ($type) {
            expect(in_array($type, array('debit', 'credit')));
        }
        
        $newAccArr = $corespondingAccArr = array();
        $accArr = arr::make($accs);
        $fromArr = arr::make($accFrom);
        expect(countR($accArr));
        
        // Намираме ид-та на сметките
        foreach ($accArr as $accSysId) {
            expect($accId = acc_Accounts::getRecBySystemId($accSysId)->id);
            $newAccArr[] = $accId;
        }
        
        foreach ($fromArr as $accSysId1) {
            expect($accId = acc_Accounts::getRecBySystemId($accSysId1)->id);
            $corespondingAccArr[] = $accId;
        }
        
        // За всеки запис
        foreach ($jRecs as $rec) {
            
            // Ако има кореспондираща сметка и тя не участва в записа, пропускаме го
            if (countR($corespondingAccArr) && (!in_array($rec->debitAccId, $corespondingAccArr) && !in_array($rec->creditAccId, $corespondingAccArr))) {
                continue;
            }
            
            // Ако има посочени задължителни пера
            if (countR($items) > 0) {
                $skip = false;
                
                // За всяко
                foreach (range(0, 2) as $i) {
                    
                    // Ако е сетнато
                    if (!empty($items[$i])) {
                        $j = $i + 1;
                        
                        // И дебитната сметка е от търсените
                        if (in_array($rec->debitAccId, $newAccArr)) {
                            
                            // И съответното перо не е като търсеното
                            if ($rec->{"debitItem{$j}"} != $items[$i]) {
                                
                                // Ще се пропуска записа
                                $skip = true;
                                break;
                            }
                            
                            // И кредитната сметка е от търсените
                        } elseif (in_array($rec->creditAccId, $newAccArr)) {
                            
                            // И съответното перо не е като търсеното
                            if ($rec->{"creditItem{$j}"} != $items[$i]) {
                                
                                // Ще се пропуска записа
                                $skip = true;
                                break;
                            }
                        }
                    }
                }
                
                // Ако ще се пропуска, записа не участва в събирането
                if ($skip === true) {
                    continue;
                }
            }
            
            // Изчисляваме крайното салдо
            if (in_array($rec->debitAccId, $newAccArr)) {
                if ($type === null || $type == 'debit') {
                    $index = null;
                    foreach (range(3, 1) as $i) {
                        if (isset($rec->{"debitItem{$i}"})) {
                            $index = $rec->{"debitItem{$i}"};
                            break;
                        }
                    }
                    if (!array_key_exists($index, $res)) {
                        $res[$index] = (object) array('quantity' => 0, 'amount' => 0);
                    }
                    
                    $res[$index]->quantity += $rec->debitQuantity;
                    $res[$index]->amount += $rec->amount;
                }
            }
            
            if (in_array($rec->creditAccId, $newAccArr)) {
                $sign = ($type === null) ? -1 : 1;
                
                if ($type === null || $type == 'credit') {
                    $index = null;
                    foreach (range(3, 1) as $i) {
                        if (isset($rec->{"creditItem{$i}"})) {
                            $index = $rec->{"creditItem{$i}"};
                            break;
                        }
                    }
                    
                    if (!array_key_exists($index, $res)) {
                        $res[$index] = (object) array('quantity' => 0, 'amount' => 0);
                    }
                    
                    $res[$index]->quantity += $sign * $rec->creditQuantity;
                    $res[$index]->amount += $sign * $rec->amount;
                }
            }
        }
        
        // Връщане на резултата
        return $res;
    }
    
    
    /**
     * Връща крайното салдо на дадена сметка, според подадени записи
     *
     * @param array       $jRecs   - масив с данни от журнала
     * @param string      $accs    - Масив от сметки на които ще се изчислява крайното салдо
     * @param string|NULL $type    - кредното, дебитното или крайното салдо
     * @param string      $accFrom - сметки с които може да кореспондира
     * @params array $items - масив с пера, които трябва да са на посочените позиции
     *
     * @return stdClass $res - обект със следната структура:
     *                  ->amount - крайното салдо на сметката, ако няма записи е 0
     *                  ->recs   - тази част от подадените записи, участвали в образуването на салдото
     */
    public static function getBlAmounts($jRecs, $accs, $type = null, $accFrom = null, $items = array())
    {
        $res = new stdClass();
        $res->amount = 0;
        
        // Ако няма записи, връщаме празен масив
        if (!countR($jRecs)) {
            
            return $res;
        }
        
        if ($type) {
            expect(in_array($type, array('debit', 'credit')));
        }
        
        $newAccArr = $corespondingAccArr = array();
        $accArr = arr::make($accs);
        $fromArr = arr::make($accFrom);
        expect(countR($accArr));
        
        // Намираме ид-та на сметките
        foreach ($accArr as $accSysId) {
            expect($accId = acc_Accounts::getRecBySystemId($accSysId)->id);
            $newAccArr[] = $accId;
        }
        
        foreach ($fromArr as $accSysId1) {
            expect($accId = acc_Accounts::getRecBySystemId($accSysId1)->id);
            $corespondingAccArr[] = $accId;
        }
        
        
        // За всеки запис
        foreach ($jRecs as $rec) {
            $add = false;
            
            // Ако има кореспондираща сметка и тя не участва в записа, пропускаме го
            if (countR($corespondingAccArr) && (!in_array($rec->debitAccId, $corespondingAccArr) && !in_array($rec->creditAccId, $corespondingAccArr))) {
                continue;
            }
            
            // Ако има посочени задължителни пера
            if (countR($items) > 0) {
                $skipDebit = $skipCredit = false;
                
                // За всяко
                foreach (range(0, 2) as $i) {
                    
                    // Ако е сетнато
                    if (!empty($items[$i])) {
                        $j = $i + 1;
                        
                        // И дебитната сметка е от търсените
                        if (in_array($rec->debitAccId, $newAccArr)) {
                            
                            // И съответното перо не е като търсеното
                            if ($rec->{"debitItem{$j}"} != $items[$i]) {
                                
                                // Ще се пропуска записа
                                $skipDebit = true;
                                break;
                            }
                        }
                        
                        // И кредитната сметка е от търсените
                        if (in_array($rec->creditAccId, $newAccArr)) {
                            
                            // И съответното перо не е като търсеното
                            if ($rec->{"creditItem{$j}"} != $items[$i]) {
                                
                                // Ще се пропуска записа
                                $skipCredit = true;
                                break;
                            }
                        }
                    }
                }
                
                // Ако ще се пропуска, записа не участва в събирането
                if ($skipDebit === true && $skipCredit === true) {
                    continue;
                }
            }
            
            // Изчисляваме крайното салдо
            if (in_array($rec->debitAccId, $newAccArr)) {
                if ($skipDebit !== true) {
                    if ($type === null || $type == 'debit') {
                        $res->amount += $rec->amount;
                        $add = true;
                    }
                }
            }
            
            if (in_array($rec->creditAccId, $newAccArr)) {
                if ($skipCredit !== true) {
                    $sign = ($type === null) ? -1 : 1;
                    
                    if ($type === null || $type == 'credit') {
                        $res->amount += $sign * $rec->amount;
                    }
                    
                    $add = true;
                }
            }
            
            // Добавяме записа, участвал в образуването на крайното салдо
            if ($add) {
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
     * @return string $title - името на сметката като линк (ако имаме права)
     */
    public static function getAccountLink($accountId, $rec = null, $showNum = true, $showIcon = false)
    {
        expect($accountRec = acc_Accounts::fetchRec($accountId));
        $title = acc_Accounts::getVerbal($accountRec, 'title');
        $num = acc_Accounts::getVerbal($accountRec, 'num');
        
        // Ако трябва да се показва num-а го показваме до името на сметката
        if ($showNum) {
            $title = $num . '. ' . $title;
        }
        
        // Ако не е подаден баланс, взимаме последния
        if (!$rec) {
            $rec = static::getLastBalance();
        } else {
            $rec = static::fetchRec($rec);
        }
        
        if ($accountRec->id && strlen($num) >= 3) {
            if (acc_Balances::haveRightFor('read', $rec) && !Mode::isReadOnly()) {
                
                // Ако има номенклатури, правим линк към обобщението на сметката
                if ($accountRec->groupId1 || $accountRec->groupId2 || $accountRec->groupId3) {
                    $balImg = ($showIcon) ? 'ef_icon=img/16/filter.png,title=Разбивка по пера на сметката' : null;
                    
                    $title = ht::createLink(
                        
                        $title,
                        array('acc_Balances', 'single', $rec->id, 'accId' => $accountRec->id),
                        
                        null,
                        
                        $balImg
                    
                    );
                } else {
                    
                    // Ако няма номенклатури, линка е към хронологията на сметката
                    if (acc_BalanceDetails::haveRightFor('history', (object) array())) {
                        $balImg = ($showIcon) ? 'ef_icon=img/16/clock_history.png,title=Хронология на сметката' : null;
                        
                        $title = ht::createLink(
                            
                            $title,
                            array('acc_BalanceHistory', 'History', 'fromDate' => $rec->fromDate, 'toDate' => $rec->toDate, 'accNum' => $accountRec->num),
                            
                            null,
                            
                            $balImg
                        
                        );
                    }
                }
            }
        }
        
        // Връщаме линка
        return $title;
    }
    
    
    /**
     * Връща урл-то към крон процеса за преизчисляване на баланса
     *
     * @return array $url
     */
    public static function getRecalcCronUrl()
    {
        $cronRec = core_Cron::getRecForSystemId('RecalcBalances');
        $url = array('core_Cron', 'ProcessRun', str::addHash($cronRec->id), 'forced' => 'yes');
        
        return $url;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (haveRole('ceo,admin,debug')) {
            $url = self::getRecalcCronUrl();
            $data->toolbar->addBtn('Преизчисляване', $url, 'title=Преизчисляване на баланса,ef_icon=img/16/arrow_refresh.png,target=cronjob');
        }
    }
    
    
    /**
     * Опции с балансите за избор
     * 
     * @param string $order       - подредба
     * @param boolean $skipClosed - пропусни затворените
     * @param boolean $linkKeys   - дали ключа да е линк към сингъла на баланса
     * 
     * @return array              - $balances
     */
    public static function getSelectOptions($order = 'DESC', $skipClosed = true, $linkKeys = false)
    {
        $balances = array();
        $query = acc_Balances::getQuery();
        $query->EXT('state', 'acc_Periods', 'externalName=state,externalKey=periodId');
        if ($skipClosed === true) {
            $query->where("#state != 'closed'");
        }
        
        $query->orderBy('id', $order);
        while ($rec = $query->fetch()) {
            $key = ($linkKeys !== true) ? $rec->id : toUrl(array(__CLASS__, 'single', $rec->id));
            $balances[$key] = acc_Periods::getTitleById($rec->periodId, false);
        }
        
        return $balances;
    }
    
    
    /**
     * Помощна функция подготвяща опции за начало и край на период със всички периоди в системата
     * както и вербални опции като : Днес, Вчера, Завчера
     *
     * @return stdClass $res
     *                  $res->fromOptions - опции за начало
     *                  $res->toOptions - опции за край на период
     */
    public static function getPeriodOptions()
    {
        // За начална и крайна дата, слагаме по подразбиране, датите на периодите
        // за които има изчислени оборотни ведомости
        $balanceQuery = self::getQuery();
        $balanceQuery->where('#periodId IS NOT NULL');
        $balanceQuery->orderBy('#fromDate', 'DESC');
        
        $yesterday = dt::verbal2mysql(dt::addDays(-1, dt::today()), false);
        $daybefore = dt::verbal2mysql(dt::addDays(-2, dt::today()), false);
        $optionsFrom = $optionsTo = array();
        $optionsFrom[dt::today()] = 'Днес';
        $optionsFrom[$yesterday] = 'Вчера';
        $optionsFrom[$daybefore] = 'Завчера';
        $optionsTo[dt::today()] = 'Днес';
        $optionsTo[$yesterday] = 'Вчера';
        $optionsTo[$daybefore] = 'Завчера';
        
        while ($bRec = $balanceQuery->fetch()) {
            $bRow = self::recToVerbal($bRec, 'periodId,id,fromDate,toDate,-single');
            $optionsFrom[$bRec->fromDate] = $bRow->periodId . " ({$bRow->fromDate})";
            $optionsTo[$bRec->toDate] = $bRow->periodId . " ({$bRow->toDate})";
        }
        
        return (object) array('fromOptions' => $optionsFrom, 'toOptions' => $optionsTo);
    }
}
