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
    public $loadList = 'plg_RowTools2, acc_Wrapper,Accounts=acc_Accounts,plg_Sorting, plg_Printing, bgerp_plg_Blank';


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
     * Кой може ръчно да рекалкулира баланс?
     */
    public $canForcecalc = 'debug';


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
     * Натрупани времена за диагностика на преизчисляването.
     *
     * Ключ е името на етапа, стойността - масив с 'start', 'total' и 'cnt'.
     */
    private static $timings = array();


    /**
     * Пуска хронометър за даден етап от преизчисляването.
     *
     * За разлика от core_Debug таймерите, тези се натрупват винаги и се извеждат
     * в системния лог - при крон дебъг логът на хита е изключен (@see core_Cron::act_ProcessRun).
     *
     * @param string $name
     */
    public static function timerStart($name)
    {
        core_Debug::startTimer($name);
        self::$timings[$name]['start'] = microtime(true);
    }


    /**
     * Спира хронометъра за даден етап и натрупва изминалото време
     *
     * @param string $name
     */
    public static function timerStop($name)
    {
        core_Debug::stopTimer($name);

        if (!isset(self::$timings[$name]['start'])) {

            return;
        }

        self::$timings[$name]['total'] = (self::$timings[$name]['total'] ?? 0) + (microtime(true) - self::$timings[$name]['start']);
        self::$timings[$name]['cnt'] = (self::$timings[$name]['cnt'] ?? 0) + 1;
        self::$timings[$name]['start'] = null;
    }


    /**
     * Записва диагностичен ред за преизчисляването в системния лог с тип 'debug'.
     *
     * Използва се вместо core_Debug::log(), защото при пускане по крон дебъг логът
     * на хита е изключен (@see core_Cron::act_ProcessRun) и там нищо не се вижда.
     *
     * Ако е подадено $elapsed, редът се записва само когато етапът е отнел поне
     * $threshold секунди - иначе ежеминутният крон би наливал стотици записи.
     *
     * @param string     $msg
     * @param float|NULL $elapsed   - времетраене на етапа в секунди
     * @param float      $threshold - под това време етапът се пропуска
     */
    public static function logCalcStep($msg, $elapsed = null, $threshold = 0.5)
    {
        self::markStep($msg);

        if (isset($elapsed)) {
            if ($elapsed < $threshold) {

                return;
            }

            $msg .= ' [' . round($elapsed, 2) . 'с]';
        }

        log_System::add('acc_Balances', $msg, null, 'debug', 1);
    }


    /**
     * Последната достигната точка в преизчисляването.
     *
     * @see markStep()
     */
    private static $lastStep;


    /**
     * Отбелязва докъде е стигнало изпълнението, без да пише в лога.
     *
     * Ако процесът бъде прекратен, shutdown хендлърът извежда последната отметка -
     * така се локализира точният ред без да се наливат записи при всяко пускане.
     *
     * @param string $msg
     */
    public static function markStep($msg)
    {
        self::$lastStep = $msg;
    }


    /**
     * Момент на стартиране на текущия recalc(), или NULL ако не тече такъв.
     *
     * Служи на shutdown хендлъра да разбере, че процесът е приключил аварийно.
     */
    private static $recalcStartedOn;


    /**
     * Извиква се на края на хита. Ако recalc() е започнал, но не е отбелязал край,
     * значи процесът е убит (фатална грешка, изчерпана памет или време) - точно
     * това остава невидимо в лога и заключва крон процеса до изтичане на timeLimit.
     */
    public static function onRecalcShutdown()
    {
        if (!isset(self::$recalcStartedOn)) {

            return;
        }

        $elapsed = round(microtime(true) - self::$recalcStartedOn, 2);
        $err = error_get_last();
        $errStr = $err ? "{$err['type']}: {$err['message']} @ {$err['file']}:{$err['line']}" : 'няма регистрирана PHP грешка';
        $mem = round(memory_get_peak_usage(true) / 1048576, 1);

        // Ако няма нито грешка, нито изключение, остава прекъсната връзка или изричен exit()
        $connArr = array(0 => 'NORMAL', 1 => 'ABORTED', 2 => 'TIMEOUT', 3 => 'ABORTED+TIMEOUT');
        $conn = $connArr[connection_status()] ?? connection_status();

        self::logCalcStep("recalc() ПРЕКЪСНАТ след {$elapsed}с » последна точка: " . (self::$lastStep ?? '-') .
            " » {$errStr} » връзка: {$conn}, ignore_user_abort=" . ini_get('ignore_user_abort') .
            " » пикова памет {$mem}MB (лимит " . ini_get('memory_limit') . ')');
    }


    /**
     * Връща натрупаните времена, подредени от най-бавния етап към най-бързия
     *
     * @param int   $topN     - колко етапа да се включат
     * @param float $minTotal - етапи под това време (сек) се пропускат
     *
     * @return string
     */
    public static function timersReport($topN = 12, $minTotal = 0.05)
    {
        $arr = array();
        foreach (self::$timings as $name => $t) {
            if (($t['total'] ?? 0) >= $minTotal) {
                $arr[$name] = $t;
            }
        }

        if (!countR($arr)) {

            return 'без отчетени времена';
        }

        uasort($arr, function ($a, $b) {

            return $b['total'] <=> $a['total'];
        });

        $parts = array();
        $i = 0;
        foreach ($arr as $name => $t) {
            if ($i++ >= $topN) {
                break;
            }
            $parts[] = $name . '=' . round($t['total'], 2) . 'с/' . $t['cnt'] . 'х';
        }

        return implode(', ', $parts);
    }


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('periodId', 'key(mvc=acc_Periods,select=title)', 'caption=Период,mandatory,autoFilter');
        $this->FLD('fromDate', 'date', 'input=none,caption=Период->от,column=none');
        $this->FLD('toDate', 'date', 'input=none,caption=Период->до,column=none');
        $this->FLD('lastAlternation', 'datetime(format=smartTime, defaultTime)', 'input=none,caption=Последно->Изменение');
        $this->FLD('lastAlternationDocClass', 'class(interface=acc_TransactionSourceIntf)', 'caption=Последно изменение->Документ клас,input=none,column=none');
        $this->FLD('lastAlternationDocId', 'int', 'input=none,column=none,caption=Последно изменение->Документ ID');
        $this->FLD('lastCalculate', 'datetime(format=smartTime, defaultTime)', 'input=none,caption=Последно->Изчисляване');
        $this->FLD('lastCalculateChange', 'enum(yes,no)', 'input=none,caption=Последно->Нови ст-ти');
        $this->setDbIndex('fromDate');
        $this->setDbIndex('toDate');
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

        if ($action == 'forcecalc' && isset($rec)) {
            if (isset($rec->periodId)) {
                $periodState = acc_Periods::fetchField($rec->periodId, 'state');
                if (in_array($periodState, array('closed', 'draft'))) {
                    $requiredRoles = 'no_one';
                }
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

            if (isset($fields['-list'])) {
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
            $row->lastAlternation = ($row->lastAlternation ?? '') . ht::createLink('↗', array($rec->lastAlternationDocClass, 'single', $rec->lastAlternationDocId));
        }

        if ($rec->lastCalculateChange == 'no') {
            $row->lastCalculate = ($row->lastCalculate ?? '') . ' ' . "<span title='При последното изчисляване не е настъпила промяна'>✓</span>";
        }

        if ($rec->lastAlternation > $rec->lastCalculate) {
            $row->lastAlternation = ht::createHint($row->lastAlternation, 'Има промяна след последното изчисление на баланса', 'warning');
        }

        if ($mvc->haveRightFor('forcecalc', $rec)) {
            $row->lastCalculate = ht::createLink('', array($mvc, 'forceCalc', $rec->id, 'debug' => true, 'ret_url' => true), false, 'ef_icon=img/16/bug.png,select=Ръчно рекалкулиране на баланса с дебъг') . "&nbsp;&nbsp;" . ($row->lastCalculate ?? '');
            $row->lastCalculate .= "&nbsp;&nbsp;" . ht::createLink('', array($mvc, 'forceCalc', $rec->id, 'ret_url' => true), false, 'ef_icon=img/32/arrow_refresh.png,select=Ръчно рекалкулиране на баланса');
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
        $date = (empty($date)) ? '0000-00-00' : $date;

        while ($rec = $query->fetch("#toDate < '{$date}'")) {
            if (self::isValid($rec)) {

                return $rec;
            }
        }
    }


    /**
     * Маркира балансите, които се засягат от документ с посочения вальор
     *
     * @param string $date Вальорът на алтерниращият документ
     * @param int $docClassId Класът на алтерниращият документ
     * @param int $docId id на алтерниращият документ
     */
    public static function alternate($date, $docClassId, $docId)
    {
        static $dateArr = array();
        if ($dateArr[$date] ?? null) {

            return;
        }
        $dateArr[$date] = true;

        $now = dt::now();

        // Ако датата е 
        $alternateWindow = acc_setup::get('ALTERNATE_WINDOW');
        if ($alternateWindow) {
            $windowStart = dt::addSecs(-$alternateWindow);
            if ($windowStart > $date) return;
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
     * Екшън форсиращ рекалкулирането на определен баланс
     */
    function act_ForceCalc()
    {
        $this->requireRightFor('forcecalc');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('forcecalc', $rec);
        $debug = Request::get('debug', 'int');

        // Обикновен бутон – без трейс
        if (empty($debug)) {
            $this->doManualForceCalc($rec);
        }

        $form = cls::get('core_Form');
        if (empty($rec->periodId)) {
            $periodId = dt::mysql2verbal($rec->fromDate, 'd', null, false) . '-' . dt::mysql2verbal($rec->toDate, 'd F Y', null, false);
        } else {
            $periodId = acc_Periods::getTitleById($rec->periodId);
        }

        $form->title = 'Преизчисляване на баланса за|* <b>' . $periodId . "</b>";
        $form->FLD('accountId', 'acc_type_Account(allowEmpty)', 'caption=Дебъг проследяване на сметка->Избор');
        $form->input();

        if ($form->isSubmitted()) {
            $accNum = ($form->rec->accountId) ? acc_Accounts::getNumById($form->rec->accountId) : null;

            // Дебъг бутонът е натиснат → трейсваме винаги, независимо дали има сметка
            $this->doManualForceCalc($rec, $accNum, true);
        }

        $form->toolbar->addSbBtn('Преизчисли', 'save', 'ef_icon = img/16/arrow_refresh.png, title = Преизчисляване, class=submitBtn');
        $form->toolbar->addBtn('Назад', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        return $this->renderWrapping($form->renderHtml());
    }


    /**
     * Изпълнява ръчното преизчисляване на баланс
     *
     * @param stdClass    $rec
     * @param string|null $accNum - номер на сметка за дебъг проследяване или NULL
     * @param bool        $trace  - дали да се генерира дебъг трейс (CSV). Не зависи от $accNum
     */
    private function doManualForceCalc($rec, $accNum = null, $trace = false)
    {
        $checkForLock = true;
        $alternateWindow = acc_setup::get('ALTERNATE_WINDOW');
        if ($alternateWindow) {
            $windowStart = dt::addSecs(-1 * $alternateWindow, null, false);
            if ($rec->toDate < $windowStart) {
                $checkForLock = false;
            }
        }

        if ($checkForLock) {
            $lockKey = 'RecalcBalances';
            if (!core_Locks::obtain($lockKey, self::MAX_PERIOD_CALC_TIME, 1)) {
                $this->logNotice('Изчисляването на баланса е заключено от друг процес');
                followRetUrl(null, "|Балансът се изчислява в момента. Опитайте по-късно.", 'warning');
            }
        }

        // Трейсваме само ако изрично е поискано (дебъг бутон)
        if ($trace) {
            acc_BalanceDebugger::clear($accNum ?? '');
            Mode::push('traceBalance', true);
        }

        self::forceCalc($rec, true);
        self::logWrite('Ръчно преизчисляване на баланса', $rec->id);

        if ($trace) {
            Mode::pop('traceBalance');
        }

        if (isset($lockKey)) {
            core_Locks::release($lockKey);
        }

        if ($trace) {
            // download() извиква exit – кодът след тук не се достига
            acc_BalanceDebugger::download($rec, $accNum ?? '');
        } else {
            followRetUrl(null, 'Балансът е преизчислен успешно');
        }
    }


    /**
     * Ако е необходимо записва и изчислява баланса за посочения период
     *
     * @param stdClass $rec - Запис на баланс, с попълнени $fromDate, $toDate и $periodId
     * @param boolean $force - винаги да преизчислява, или само ако е невалиден
     *
     * @return boolean       - Дали е правено преизчисляване
     */
    private static function forceCalc(&$rec, $force = false)
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
        if ($force !== true) {
            $isValid = self::isValid($rec, ($rec->lastCalculateChange ?? null) != 'no' ? 10 : 1);
        } else {
            $isValid = false;
        }

        if ($isValid) {
            self::logCalcStep("forceCalc ПРОПУСНАТ (валиден) {$rec->fromDate}..{$rec->toDate}");

            return;
        }

        $forceCalcStart = microtime(true);

        // Маркерът е безусловен - по него се вижда докъде е стигнал "увиснал" процес
        self::logCalcStep("forceCalc START {$rec->fromDate}..{$rec->toDate} (periodId=" . ($rec->periodId ?? 'null') . ')');

        // Днешна дата
        $today = dt::today();

        // Ако изчисляваме текущия период, опитваме да преизчислим баланс за предишен работен ден
        if ($rec->toDate == dt::getLastDayOfMonth()) {
            if ($prevWorkingDay = self::getPrevWorkingDay($today)) {
                $prevRec = clone($rec);
                unset($prevRec->id);
                $prevRec->toDate = $prevWorkingDay;
                $prevRec->periodId = null;

                $stepStart = microtime(true);
                self::timerStart('BAL_MIDDLE_FORCECALC');
                self::forceCalc($prevRec);
                self::timerStop('BAL_MIDDLE_FORCECALC');
                self::logCalcStep("  междинен баланс до {$prevWorkingDay}", microtime(true) - $stepStart);

                $fromDate = $prevRec->fromDate;
                $toDate = $prevRec->toDate;

                // Намираме и изтриваме всички баланси, които нямат период и не се отнасят за предишния ден
                $stepStart = microtime(true);
                self::timerStart('BAL_DELETE_OLD_MIDDLE');
                $delCnt = 0;
                $query = self::getQuery();
                while ($delRec = $query->fetch("(#fromDate != '{$fromDate}' OR #toDate != '{$toDate}') AND #periodId IS NULL")) {
                    acc_BalanceDetails::delete("#balanceId = {$delRec->id}");
                    self::delete($delRec->id);
                    $delCnt++;
                }
                self::timerStop('BAL_DELETE_OLD_MIDDLE');

                if ($delCnt) {
                    self::logCalcStep("  изтрити {$delCnt} стари междинни баланса", microtime(true) - $stepStart);
                }
            }
        }

        $stepStart = microtime(true);
        self::timerStart('BAL_CALC');
        self::calc($rec);
        self::timerStop('BAL_CALC');
        self::logCalcStep("  calc() #1 {$rec->fromDate}..{$rec->toDate} промяна=" . ($rec->lastCalculateChange ?? '-'), microtime(true) - $stepStart);

        // Преизчисляваме първия баланс, в който има промени още веднъж, за да подаде верни данни на следващите
        static $rc1;

        if (!$rc1 && $rec->lastCalculateChange != 'no') {
            $stepStart = microtime(true);
            self::timerStart('BAL_CALC_RC1');
            self::calc($rec);
            self::timerStop('BAL_CALC_RC1');
            self::logCalcStep("  calc() #2 (rc1) {$rec->fromDate}..{$rec->toDate} промяна=" . ($rec->lastCalculateChange ?? '-'), microtime(true) - $stepStart);
            $rc1 = true;
        }

        self::logCalcStep("forceCalc END {$rec->fromDate}..{$rec->toDate}", microtime(true) - $forceCalcStart);

        return true;
    }


    /**
     * Изчисляване на баланс
     */
    public static function calc($rec)
    {
        $bD                 = cls::get('acc_BalanceDetails');
        $lastRec            = self::getBalanceBefore($rec->toDate);
        $periodCurrencyCode = acc_Periods::getBaseCurrencyCode($rec->toDate);

        if (Mode::is('traceBalance')) {
            acc_BalanceDebugger::log('calc_start', [
                'balance_from'    => $rec->fromDate,
                'balance_to'      => $rec->toDate,
                'period_currency' => $periodCurrencyCode,
            ]);
        }

        if ($lastRec) {
            $isMiddleBalance  = !!empty($lastRec->periodId);
            $lastCurrencyCode = acc_Periods::getBaseCurrencyCode($lastRec->toDate);
            $convertToDate    = ($lastCurrencyCode != $periodCurrencyCode) ? $rec->toDate : null;

            if (Mode::is('traceBalance')) {
                acc_BalanceDebugger::log('prev_balance_found', [
                    'prev_balance_id'   => $lastRec->id,
                    'prev_from'         => $lastRec->fromDate,
                    'prev_to'           => $lastRec->toDate,
                    'prev_period_id'    => $lastRec->periodId,
                    'is_middle_balance' => $isMiddleBalance ? 'да (без период)' : 'не (нормален)',
                    'prev_currency'     => $lastCurrencyCode,
                    'convert_to_date'   => $convertToDate ?? '(няма конвертиране)',
                ]);
            }

            $bD->loadBalance($lastRec->id, $isMiddleBalance, null, null, null, null, null, $convertToDate);
            $firstDay = dt::addDays(1, $lastRec->toDate);
            $firstDay = dt::verbal2mysql($firstDay, false);
        } else {
            if (Mode::is('traceBalance')) {
                acc_BalanceDebugger::log('prev_balance_found', [
                    'prev_balance_id' => null,
                    'note'            => 'Няма предходен баланс – старт от TIME_BEGIN',
                ]);
            }
            $firstDay = self::TIME_BEGIN;
        }

        if (Mode::is('traceBalance')) {
            acc_BalanceDebugger::log('journal_range', [
                'journal_from' => $firstDay,
                'journal_to'   => $rec->toDate,
            ]);
        }

        $isMiddleBalance = !$rec->periodId;
        $bD->calcBalanceForPeriod($firstDay, $rec->toDate, $isMiddleBalance);

        if ($bD->saveBalance($rec->id)) {
            $rec->lastCalculateChange = 'yes';
        } else {
            $rec->lastCalculateChange = 'no';
        }

        if (Mode::is('traceBalance')) {
            acc_BalanceDebugger::log('save_result', [
                'changed' => $rec->lastCalculateChange === 'yes' ? 'да – имаше промяна' : 'не – без промяна',
            ]);
        }

        $rec->lastCalculate = dt::now();
        self::save($rec, 'lastCalculate,lastCalculateChange');
    }


    /**
     * Рекалкулира баланса
     */
    public function recalc()
    {
        $lockKey = 'RecalcBalances';

        $recalcStart = microtime(true);

        self::timerStart('recalcBalance');
        self::logCalcStep('recalc() START' . (core_Cron::getCurrentRec() ? ' (крон)' : ' (ръчно)'));

        // Ако процесът бъде убит (памет/време/фатална грешка), нищо повече не се логва
        // и крон процесът остава заключен - затова отбелязваме аварийния край
        self::$recalcStartedOn = $recalcStart;
        register_shutdown_function(array(__CLASS__, 'onRecalcShutdown'));

        // Ако изчисляването е заключено не го изпълняваме
        self::timerStart('BAL_INITIAL_LOCK');
        $gotLock = core_Locks::obtain($lockKey, self::MAX_PERIOD_CALC_TIME, 1);
        self::timerStop('BAL_INITIAL_LOCK');

        if (!$gotLock) {

            // Показваме и колко още държи лока - така се вижда дали лок от "увиснал"
            // процес блокира следващите крон пускания за цели MAX_PERIOD_CALC_TIME секунди
            $lockRec = core_Locks::fetch(array("#objectId = '[#1#]'", str::convertToFixedKey($lockKey, 32, 4)), null, false);
            $lockInfo = $lockRec ? ('изтича след ' . ($lockRec->lockExpire - time()) . ' сек, потребител ' . $lockRec->user) : 'няма запис за лока';
            self::logCalcStep("recalc() ИЗХОД - заключено от друг процес ({$lockInfo})");
            self::$recalcStartedOn = null;

            return;
        }

        self::logCalcStep('recalc() лок взет');

        $data = new stdClass();
        $data->recalcedBalances = array();
        if ($oldLastBalance = acc_Balances::getLastBalance()) {
            $data->oldLastBalance = clone $oldLastBalance;
        }

        self::logCalcStep('recalc() getLastBalance() OK');

        // Обикаляме всички активни и чакъщи периоди от по-старите, към по-новите
        // Ако периода се нуждае от прекалкулиране - правим го
        // Ако прекалкулирането се извършва в текущия период, то изисляваме баланса
        // до предходния работен ден и селд това до днес

        $pQuery = acc_Periods::getQuery();
        $pQuery->orderBy('#end', 'ASC');
        $pQuery->where("#state != 'closed'");
        $pQuery->where("#state != 'draft'");

        $rc = true;

        // Ако е указана граница за изчисляването се използва
        $windowStart = null;
        $alternateWindow = acc_setup::get('ALTERNATE_WINDOW');
        if ($alternateWindow) {
            $windowStart = dt::addSecs(-$alternateWindow, null, false);
            $pQuery->where("#end >= '{$windowStart}'");
        }

        $periodsCnt = 0;
        $slowest = array('what' => null, 'time' => 0);

        self::logCalcStep('recalc() начало на цикъла по периоди' . ($windowStart ? " (от {$windowStart})" : ' (всички отворени)'));

        // Изключение тук досега оставаше невидимо - хитът приключваше без да мине през
        // core_Locks::release() и без да отбележи край, което заключваше крон процеса
        try {
            self::markStep('преди fetch на период #1');

            while ($pRec = $pQuery->fetch()) {
                $periodsCnt++;
                self::markStep("fetch на период #{$pRec->id} OK");

                $rec = new stdClass();
                $rec->fromDate = $pRec->start;
                $rec->toDate = $pRec->end;
                $rec->periodId = $pRec->id;

                $periodStart = microtime(true);
                self::logCalcStep("Период #{$pRec->id} {$rec->fromDate}..{$rec->toDate} START");

                // Преизчисляваме първия отворен баланс (когато в него има промени) 9+1 пъти, за да подаде верни данни на следващите
                $j = 0;
                do {
                    $lockStart = microtime(true);
                    self::timerStart('BAL_LOOP_LOCK');
                    core_Locks::obtain($lockKey, self::MAX_PERIOD_CALC_TIME);
                    self::timerStop('BAL_LOOP_LOCK');
                    $lockWait = round(microtime(true) - $lockStart, 3);

                    $iterStart = microtime(true);
                    $r = self::forceCalc($rec);
                    $iterTime = round(microtime(true) - $iterStart, 3);

                    self::logCalcStep("  период #{$pRec->id} итерация {$j}: лок {$lockWait}с, промяна=" . ($rec->lastCalculateChange ?? '-') . ', преизчислен=' . ($r ? 'да' : 'не'), $iterTime);

                    if ($iterTime > $slowest['time']) {
                        $slowest = array('what' => "период {$rec->fromDate}..{$rec->toDate} итерация {$j}", 'time' => $iterTime);
                    }

                    if($r){
                        $data->recalcedBalances[$rec->toDate] = $rec;
                    }
                } while ($rec->lastCalculateChange != 'no' && $j++ < 9 && $rc);

                $periodTime = round(microtime(true) - $periodStart, 3);
                $iterCnt = $j + 1;
                self::logCalcStep("Период #{$pRec->id} {$rec->fromDate}..{$rec->toDate} END: {$iterCnt} итерации за {$periodTime}с");

                $rc = false;
                self::markStep('преди fetch на следващ период (след #' . $pRec->id . ')');
            }

            self::markStep('след цикъла по периоди');
        } catch (Throwable $e) {
            self::logCalcStep('recalc() ИЗКЛЮЧЕНИЕ след ' . $periodsCnt . ' периода » ' . get_class($e) . ': ' . $e->getMessage() .
                ' @ ' . $e->getFile() . ':' . $e->getLine());
            self::logCalcStep('recalc() стек » ' . str_replace("\n", ' | ', $e->getTraceAsString()));

            // Освобождаваме лока, преди да върнем изключението нагоре - иначе остава
            // зает до MAX_PERIOD_CALC_TIME и блокира следващите крон пускания
            core_Locks::release($lockKey);
            self::$recalcStartedOn = null;

            throw $e;
        }

        // Заявката връща ту 0, ту 2 периода - показваме какви състояния реално има,
        // за да се види дали периодите се затварят/отварят между пусканията
        if (!$periodsCnt) {
            $sQuery = acc_Periods::getQuery();
            $sQuery->show('state');
            $states = array();
            while ($sRec = $sQuery->fetch()) {
                $states[$sRec->state] = ($states[$sRec->state] ?? 0) + 1;
            }

            $statesStr = array();
            foreach ($states as $state => $cnt) {
                $statesStr[] = "{$state}={$cnt}";
            }

            self::logCalcStep('recalc() 0 периода » състояния в acc_Periods: ' . (countR($statesStr) ? implode(', ', $statesStr) : 'таблицата е празна'));
        }

        // Освобождаваме заключването на процеса
        self::markStep('преди core_Locks::release()');
        core_Locks::release($lockKey);
        self::markStep('след core_Locks::release()');
        self::timerStop('recalcBalance');

        $totalTime = round(microtime(true) - $recalcStart, 3);
        $slowestWhat = $slowest['what'] ?? '-';

        // Обобщението е безусловно - по него се вижда всяко пускане и разбивката по етапи
        self::logCalcStep("recalc() END: {$periodsCnt} периода за {$totalTime} сек." .
            " » най-бавно: {$slowestWhat} ({$slowest['time']}с)" .
            ' » пикова памет ' . round(memory_get_peak_usage(true) / 1048576, 1) . 'MB' .
            ' » етапи: ' . self::timersReport());

        // Стигнахме до край - shutdown хендлърът няма какво да докладва
        self::$recalcStartedOn = null;

        // Пораждаме събитие, че баланса е бил преизчислен
        $data->lastBalance = acc_Balances::getLastBalance();

        $this->invoke('AfterRecalcBalances', array($data));

        self::logCalcStep('recalc() ИЗХОД (след AfterRecalcBalances)');
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
        if(is_array($data->recalcedBalances)){
            $minDate = countR($data->recalcedBalances) ? min(array_keys($data->recalcedBalances)) : key($data->recalcedBalances);
            acc_ProductPricePerPeriods::logDebug("BALANCES '{$minDate}'");
        } else {
            acc_ProductPricePerPeriods::logDebug("BALANCES NONE");
        }
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
            
            return array();
        }
        
        // Извличане на данните от баланса в които участват зададените сметки
        $dQuery = acc_BalanceDetails::getQuery();
        
        // Филтриране на заявката на детайлите
        acc_BalanceDetails::filterQuery($dQuery, $balanceRec->id, $accs, $itemsAll, $items1, $items2, $items3);
        
        // Връщане на всички намерени записи
        return $dQuery->fetchAll();
    }
    
    
    
    /**
     * Ф-я връщаща последния баланс, в който има записи по аналитичната сметка
     *
     * @param mixed $accountSysId - сис ид на сметка
     * @param mixed $itemsAll     - списък от пера, за които може да са на произволна позиция
     * @param mixed $items1       - списък с пера, от които поне един може да е на първа позиция
     * @param mixed $items2       - списък с пера, от които поне един може да е на втора позиция
     * @param mixed $items3       - списък с пера, от които поне един може да е на трета позиция
     *
     * @return null|int           - намерения баланс, ако има такъв
     */
    public static function fetchLastBalanceFor($accountSysId, $itemsAll = null, $items1 = null, $items2 = null, $items3 = null)
    {
        // Извличане на данните от баланса в които участват зададените сметки
        $dQuery = acc_BalanceDetails::getQuery();
        acc_BalanceDetails::filterQuery($dQuery, null, $accountSysId, $itemsAll, $items1, $items2, $items3);
        $dQuery->XPR('maxBalance', 'double', 'MAX(#balanceId)');
        $dQuery->EXT('periodId', 'acc_Balances', "externalName=periodId,externalKey=balanceId");
        $dQuery->where("#periodId IS NOT NULL");
        $dQuery->orderBy('balanceId', 'DESC');
        $lastBalance = $dQuery->fetch()->maxBalance;
        $res = !empty($lastBalance) ? $lastBalance : null;
        
        return $res;
    }
    
    
    /**
     * Връща масив с количествата групирани по размерната номенклатура на сметките
     *
     * @param array       $jRecs   - масив с данни от журнала
     * @param string      $accs    - Масив от сметки на които ще се изчислява крайното салдо
     * @param string|NULL $type    - кредното, дебитното или крайното салдо
     * @param string      $accFrom - сметки с които може да кореспондира
     * @param string|null $toBaseCurrencyDate - към основната валута за коя дата
     * @params array      $ignoreClassIds - записите от кои класове да се игнорират
     * @params array $items - масив с пера, които трябва да са на посочените позиции
     *
     * @return stdClass $res - К-та групирани по размерната номенклатура
     */
    public static function getBlQuantities($jRecs, $accs, $type = null, $accFrom = null, $items = array(), $toBaseCurrencyDate = null, $ignoreClassIds = array())
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
        $toBaseCurrencyDate = $toBaseCurrencyDate ?? dt::today();
        foreach ($jRecs as $rec) {
            
            // Ако има кореспондираща сметка и тя не участва в записа, пропускаме го
            if (countR($corespondingAccArr) && (!in_array($rec->debitAccId, $corespondingAccArr) && !in_array($rec->creditAccId, $corespondingAccArr))) {
                continue;
            }

            if (countR($ignoreClassIds)) {
                if (in_array($rec->docType, $ignoreClassIds)) continue;
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
                    $res[$index]->amount += deals_Helper::getSmartBaseCurrency($rec->amount, dt::getLastDayOfMonth($rec->valior), $toBaseCurrencyDate);
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
                    $res[$index]->amount += $sign * deals_Helper::getSmartBaseCurrency($rec->amount, dt::getLastDayOfMonth($rec->valior), $toBaseCurrencyDate);
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
     * @param string|null $type    - кредитното, дебитното или крайното салдо
     * @param string      $accFrom - сметки с които може да кореспондира
     * @params array      $items - масив с пера, които трябва да са на посочените позиции (0=>Item1, 1=>Item2, 2=>Item3)
     * @params array      $ignoreClassIds - записите от кои класове да се игнорират
     * @param string|null $toBaseCurrencyDate - към основната валута за коя дата
     * @param bool        $useCurrencyField  - да се сумира по валута, а не по сума
     *
     * @return stdClass $res - обект със следната структура:
     *                  ->amount - крайното салдо на сметката, ако няма записи е 0
     *                  ->recs   - тази част от подадените записи, участвали в образуването на салдото
     */
    public static function getBlAmounts($jRecs, $accs, $type = null, $accFrom = null, $items = array(), $ignoreClassIds = array(), $toBaseCurrencyDate = null, $useCurrencyField = false)
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

        $toBaseCurrencyDate = $toBaseCurrencyDate ?? dt::today();
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

            // ВАЖНО: инициализация за всеки запис (ползват се по-долу независимо дали има $items)
            $skipDebit = $skipCredit = false;

            if (countR($ignoreClassIds)) {
                if (in_array($rec->docType, $ignoreClassIds)) continue;
            }

            // Ако има кореспондираща сметка и тя не участва в записа, пропускаме го
            if (countR($corespondingAccArr) && (!in_array($rec->debitAccId, $corespondingAccArr) && !in_array($rec->creditAccId, $corespondingAccArr))) {
                continue;
            }

            // Ако има посочени задължителни пера
            if (countR($items) > 0) {

                // Проверяваме позициите 1..3 (items[0]=>Item1, items[1]=>Item2, items[2]=>Item3)
                foreach (range(0, 2) as $i) {

                    // Ако няма филтър за тази позиция - пропускаме
                    if (!isset($items[$i])) continue;

                    $j = $i + 1;

                    // Дебитна страна
                    if (in_array($rec->debitAccId, $newAccArr) && $skipDebit !== true) {
                        if ($rec->{"debitItem{$j}"} != $items[$i]) {
                            $skipDebit = true;
                        }
                    }

                    // Кредитна страна
                    if (in_array($rec->creditAccId, $newAccArr) && $skipCredit !== true) {
                        if ($rec->{"creditItem{$j}"} != $items[$i]) {
                            $skipCredit = true;
                        }
                    }

                    // Прекъсваме само ако и двете страни вече няма как да минат
                    if ($skipDebit === true && $skipCredit === true) {
                        break;
                    }
                }

                // Ако ще се пропуска и от двете страни, записът не участва в събирането
                if ($skipDebit === true && $skipCredit === true) {
                    continue;
                }
            }

            // Изчисляваме крайното салдо
            if (in_array($rec->debitAccId, $newAccArr)) {
                if ($skipDebit !== true) {
                    if ($type === null || $type == 'debit') {
                        if ($useCurrencyField) {
                            $res->amount += $rec->debitQuantity;
                        } else {
                            $res->amount += deals_Helper::getSmartBaseCurrency($rec->amount, dt::getLastDayOfMonth($rec->valior), $toBaseCurrencyDate);
                        }
                        $add = true;
                    }
                }
            }

            if (in_array($rec->creditAccId, $newAccArr)) {
                if ($skipCredit !== true) {
                    $sign = ($type === null) ? -1 : 1;

                    if ($type === null || $type == 'credit') {
                        if ($useCurrencyField) {
                            $res->amount += $sign * $rec->creditQuantity;
                        } else {
                            $res->amount += $sign * deals_Helper::getSmartBaseCurrency($rec->amount, dt::getLastDayOfMonth($rec->valior), $toBaseCurrencyDate);
                        }
                    }

                    $add = true;
                }
            }

            // Добавяме записа, участвал в образуването на крайното салдо
            if ($add) {
                $res->recs[$rec->id] = $rec;
            }

            $res->amount = round($res->amount, 8);
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
                
                // Ако има номенклатури и вече е изчислен баланс, правим линк към обобщението на сметката
                if (($accountRec->groupId1 || $accountRec->groupId2 || $accountRec->groupId3) && !empty($rec->id)) {
                    $balImg = ($showIcon) ? 'ef_icon=img/16/filter.png,title=Разбивка по пера на сметката' : null;
                    
                    $title = ht::createLink(

                        $title,
                        array('acc_Balances', 'single', $rec->id ?? null, 'accId' => $accountRec->id),
                        
                        null,
                        
                        $balImg
                    
                    );
                } else {
                    
                    // Ако няма номенклатури, линка е към хронологията на сметката
                    if (acc_BalanceDetails::haveRightFor('history', (object) array())) {
                        $balImg = ($showIcon) ? 'ef_icon=img/16/clock_history.png,title=Хронология на сметката' : null;
                        
                        $title = ht::createLink(
                            
                            $title,
                            array('acc_BalanceHistory', 'History', 'fromDate' => $rec->fromDate ?? null, 'toDate' => $rec->toDate ?? null, 'accNum' => $accountRec->num),
                            
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
        if (haveRole('debug')) {
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
