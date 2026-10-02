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
    public $listFields = 'id, periodId, fromDate, toDate, lastAlternation, lastCalculate, calcDuration';


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
     * Над колко секунди изчисление се записва статистика в лога
     */
    const STATS_LOG_MIN_TIME = 5;


    /**
     * Брой извиквания на calc() в текущия хит
     */
    public static $calcCount = 0;


    /**
     * Под тази разлика сумите се смятат за непроменени (валутните курсове са до 5 знака)
     */
    const CHANGE_THRESHOLD = 0.00001;


    /**
     * Под този сбор от промените на сумите в журнала (стотинка) стратегиите се смятат за уравновесени
     *
     * Заедно с CHANGE_THRESHOLD за всеки ред - иначе стотици редове с шум в 8-10 знак държат итерациите
     */
    const JOURNAL_SUM_THRESHOLD = 0.01;


    /**
     * До колко пъти се смята един баланс, докато стратегиите се уравновесят
     */
    const MAX_ITERATIONS = 10;


    /**
     * В колко поредни пускания се прави допълнителен опит за довършване на стабилизацията
     *
     * Ограничава само опитите въпреки isValid() - невалиден баланс (нов документ и т.н.) се смята винаги
     */
    const MAX_PENDING_RUNS = 3;


    /**
     * Колко реда от детайлите на изтрит междинен баланс се трият с една заявка
     */
    const STALE_DELETE_CHUNK = 20000;


    /**
     * До колко секунди в едно пускане се започват нови порции триене на детайли
     */
    const STALE_DELETE_MAX_TIME = 30;


    /**
     * Ключ в core_Permanent на изтритите междинни баланси, чиито детайли още не са изтрити
     */
    const STALE_QUEUE_KEY = 'accBalanceStaleDetails';


    /**
     * Продължителност на последното смятане - за преценка дали има време за още едно
     */
    public static $lastPassTime = 0;


    /**
     * Дали времето на cron задачата е свършило - останалите баланси се смятат при следващото пускане
     */
    public static $outOfTime = false;


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

        // Времето е в отпечатъка на баланса (core_Permanent), за да не е нужна нова колона
        $this->FNC('calcDuration', 'varchar', 'input=none,caption=Последно->Време');
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

        // Времето на последното пълно изчисление (всички итерации) и броят смятания, ако са повече от едно
        if (isset($fields['-list']) && isset($rec->id)) {
            $fingerprint = self::getFingerprint($rec->id);
            if (isset($fingerprint['calcDuration'])) {
                $Double = core_Type::getByName('double(decimals=1)');
                $row->calcDuration = $Double->toVerbal($fingerprint['calcDuration']) . ' ' . tr('сек.');
                if (($fingerprint['calcPasses'] ?? 1) > 1) {
                    $row->calcDuration .= ' ×' . $fingerprint['calcPasses'];
                }

                // Стрелка спрямо предишното пълно изчисление - само при разлика от поне 10%
                $prev = $fingerprint['prevCalcDuration'] ?? null;
                if (!empty($prev)) {
                    $ratio = $fingerprint['calcDuration'] / $prev;
                    $title = tr('Предишно изчисление') . ': ' . $Double->toVerbal($prev) . ' ' . tr('сек.');
                    if ($ratio >= 1.1) {
                        $row->calcDuration .= " <span class='red' title='" . ht::escapeAttr($title) . "'>↑</span>";
                    } elseif ($ratio <= 0.9) {
                        $row->calcDuration .= " <span class='green' title='" . ht::escapeAttr($title) . "'>↓</span>";
                    }
                }
            }
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

            if ($isValid && isset($rec->id)) {
                $fingerprint = self::getFingerprint($rec->id);

                // Недовършената стабилизация получава нов опит, макар балансът да е валиден
                if (!empty($fingerprint['pendingRuns']) && $fingerprint['pendingRuns'] < self::MAX_PENDING_RUNS) {
                    $isValid = false;
                }
            }
        } else {
            $isValid = false;
        }

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
                    // Детайлите им се трият на части в deleteStaleDetails(), извън смятането
                    $query = self::getQuery();
                    while ($delRec = $query->fetch("(#fromDate != '{$fromDate}' OR #toDate != '{$toDate}') AND #periodId IS NULL")) {
                        if (!self::updateStaleQueue($delRec->id, true)) {
                            continue;
                        }
                        self::delete($delRec->id);
                        core_Permanent::remove("accBalanceFingerprint_{$delRec->id}");
                        self::logNotice("Изтрит междинен баланс #{$delRec->id} {$delRec->fromDate} - {$delRec->toDate}, редовете му се трият на части", null, 3);
                    }
                }
            }

            // Времето е свършило при междинния баланс - месецът е при следващото пускане
            if (self::isOutOfTime()) {

                return false;
            }

            self::calcUntilStable($rec, $force === true);

            return true;
        }
    }


    /**
     * Смята баланса отново, докато стратегиите се уравновесят
     *
     * Стратегиите се хранят от сумите в журнала - щом смятането не ги промени, следващо ще даде същото.
     * Така и следващите баланси по веригата получават вече крайните цени.
     * Уравновесени са, когато никой ред не се мести с CHANGE_THRESHOLD и сборът е под JOURNAL_SUM_THRESHOLD,
     * или когато сменените суми не захранват стратегия и не са от цена по подразбиране.
     */
    private static function calcUntilStable($rec, $force = false)
    {
        $diffs = array();
        $maxDiffs = array();
        $prevDiff = null;
        $calcStart = microtime(true);

        for ($i = 1; ; $i++) {
            $passStart = microtime(true);
            self::calc($rec, $i == 1 && !$force);
            if (empty($rec->calcSkipped)) {
                self::$lastPassTime = microtime(true) - $passStart;
            }

            $diff = $rec->journalAmountDiff ?? 0;
            $maxDiff = $rec->journalAmountMaxDiff ?? 0;
            $diffs[] = (float) sprintf('%.2g', $diff);
            $maxDiffs[] = (float) sprintf('%.2g', $maxDiff);

            if ($maxDiff < self::CHANGE_THRESHOLD && $diff < self::JOURNAL_SUM_THRESHOLD) {
                $stop = 'stable';
                break;
            }

            // Променените суми не захранват стратегия и няма сума от getDefaultCost - следващото смятане би дало същото
            if (empty($rec->journalNeedsRepeat)) {
                $stop = 'no feed';
                break;
            }
            if ($i >= self::MAX_ITERATIONS) {
                $stop = 'max';
                break;
            }

            // Сумите не намаляват - стратегиите се люлеят
            if (isset($prevDiff) && $diff >= $prevDiff) {
                $stop = 'no progress';
                break;
            }

            if (self::isOutOfTime()) {
                $stop = 'time';
                break;
            }

            $prevDiff = $diff;
            core_Locks::obtain('RecalcBalances', self::MAX_PERIOD_CALC_TIME);
        }

        // Неуравновесен баланс се преизчислява и в следващите 10 минути (@see isValid)
        $calcChange = in_array($stop, array('stable', 'no feed')) ? 'no' : 'yes';
        if ($rec->lastCalculateChange != $calcChange) {
            $rec->lastCalculateChange = $calcChange;
            self::save($rec, 'lastCalculateChange');
        }

        // Прекъснатото (време, лимит) получава нов опит при следващите пускания, люлеенето - не.
        // Нов документ или сменен предходен баланс започват опитите отначало.
        $fingerprint = self::getFingerprint($rec->id);
        if (($fingerprint['pendingKey'] ?? null) !== ($rec->externalKey ?? null)) {
            unset($fingerprint['pendingRuns']);
        }
        $fingerprint['pendingKey'] = $rec->externalKey ?? null;

        // Пропуснатото смятане не сменя показаното време на последното пълно изчисление
        if (empty($rec->calcSkipped)) {
            if (isset($fingerprint['calcDuration'])) {
                $fingerprint['prevCalcDuration'] = $fingerprint['calcDuration'];
            }
            $fingerprint['calcDuration'] = round(microtime(true) - $calcStart, 1);
            $fingerprint['calcPasses'] = countR($diffs);
        }

        if (in_array($stop, array('time', 'max'))) {
            $fingerprint['pendingRuns'] = ($fingerprint['pendingRuns'] ?? 0) + 1;
            if ($fingerprint['pendingRuns'] == self::MAX_PENDING_RUNS) {
                self::logNotice("Стабилизацията на {$rec->fromDate} - {$rec->toDate} спира след {$fingerprint['pendingRuns']} пускания", $rec->id, 3);
            }
        } else {
            unset($fingerprint['pendingRuns']);
        }
        self::setFingerprint($rec->id, $fingerprint);

        $rec->calcIterations = $diffs;
        $rec->calcStop = $stop;
        if (countR($diffs) > 1 || $stop != 'stable') {
            self::logNotice("Итерации на {$rec->fromDate} - {$rec->toDate}: " . countR($diffs) . ' (промяна на сумите в журнала ' . implode(', ', $diffs) . '; най-голяма на ред ' . implode(', ', $maxDiffs) . "), край: {$stop}", $rec->id, 3);
        }
    }


    /**
     * Дали в лимита на cron задачата не остава време за още едно смятане
     *
     * Тогава изчисляването спира и следващото пускане продължава от недовършения баланс
     */
    private static function isOutOfTime()
    {
        $timeLeft = core_Cron::getTimeLeft();
        if ($timeLeft !== false && ($timeLeft <= 0 || $timeLeft < self::$lastPassTime * 1.2)) {
            self::$outOfTime = true;
        }

        return self::$outOfTime;
    }


    /**
     * Изчисляване на баланс
     *
     * @param stdClass $rec            - запис на баланса
     * @param bool     $useFingerprint - да не се смята, ако входът е същият като при последното изчисление
     */
    public static function calc($rec, $useFingerprint = false)
    {
        $calcStart          = microtime(true);
        $bD                 = cls::get('acc_BalanceDetails');
        $bD->calcStats      = array();
        self::$calcCount++;

        // Пикът не се нулира, защото core_Cron го записва за цялата задача
        $peakBefore = memory_get_peak_usage(true);
        $bD->calcStats['phpPrecision'] = ini_get('precision');

        // Използвана памет преди баланса - показва дали нещо остава от предходно изчисление
        $bD->calcStats['usedBeforeMB'] = round(memory_get_usage(false) / 1048576);
        $rec->calcSkipped   = false;
        $rec->journalAmountDiff = 0;
        $rec->journalAmountMaxDiff = 0;
        $rec->journalNeedsRepeat = false;
        $convertToDate      = null;
        $lastRec            = self::getBalanceBefore($rec->toDate);
        $periodCurrencyCode = acc_Periods::getBaseCurrencyCode($rec->toDate);

        // От кой баланс тръгва смятането - междинен или с период
        $bD->calcStats['baseBalance'] = $lastRec ? "{$lastRec->id}:{$lastRec->toDate}" . (empty($lastRec->periodId) ? ':middle' : '') : '-';

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

        // Документите, осчетоводени след прочитането на журнала, не влизат в това изчисление
        $journalReadOn = dt::now();
        $isMiddleBalance = !$rec->periodId;
        $journal = $bD->fetchJournal($firstDay, $rec->toDate, $isMiddleBalance);

        $fingerprint = self::getFingerprint($rec->id);
        $journalHash = $bD->getJournalHash($journal);
        $inputHash = self::getInputHash($rec, $lastRec, $firstDay, $convertToDate, $journalHash);

        // Външните промени: документ в периода или сменен предходен баланс (обновяването на журнала от смятането не сменя lastAlternation)
        $rec->externalKey = ($rec->lastAlternation ?? '') . '|' . ($lastRec ? $lastRec->id . ':' . (self::getFingerprint($lastRec->id)['dataToken'] ?? '') : '');

        if ($useFingerprint && !empty($rec->lastCalculate) && $inputHash === ($fingerprint['inputHash'] ?? null)) {
            $rec->calcSkipped = true;
            $rec->lastCalculate = $journalReadOn;
            $rec->lastCalculateChange = 'no';
            self::save($rec, 'lastCalculate,lastCalculateChange');
            self::logNotice("Пропуснато изчисление на {$rec->fromDate} - {$rec->toDate}: входът е същият", $rec->id, 3);

            return;
        }

        if ($lastRec) {
            $bD->loadBalance($lastRec->id, empty($lastRec->periodId), null, null, null, null, null, $convertToDate);
        }

        $bD->calcBalanceForPeriod($firstDay, $rec->toDate, $isMiddleBalance, $journal);
        $rec->journalAmountDiff = $bD->calcStats['journalAmountDiff'] ?? 0;
        $rec->journalAmountMaxDiff = $bD->calcStats['journalAmountMaxDiff'] ?? 0;
        $rec->journalNeedsRepeat = !empty($bD->calcStats['journalFeedChanged']) || !empty($bD->calcStats['journalDefaultCost']);

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

        // Само реалната промяна сменя маркера на данните - записите под прага не карат следващите баланси да се смятат
        if ($rec->lastCalculateChange == 'yes' || empty($fingerprint['dataToken'])) {
            $fingerprint['dataToken'] = str::getRand('****************');
        }

        // Обновеният журнал е вход за отпечатъка, само ако повторно смятане би дало същото
        // и при повторно четене се различава само по записаното от смятането
        if (!empty($bD->calcStats['journalUpdated'])) {
            $inputHash = null;
            if (empty($rec->journalNeedsRepeat)) {
                $journalHash = $bD->getUpdatedJournalHash($journalHash, $firstDay, $rec->toDate, $isMiddleBalance);
                if (isset($journalHash)) {
                    $inputHash = self::getInputHash($rec, $lastRec, $firstDay, $convertToDate, $journalHash);
                }
            }
        }
        unset($journal);
        $fingerprint['inputHash'] = $inputHash;
        self::setFingerprint($rec->id, $fingerprint);

        $rec->lastCalculate = $journalReadOn;
        self::save($rec, 'lastCalculate,lastCalculateChange');

        $totalTime = round(microtime(true) - $calcStart, 2);
        $peakAfter = memory_get_peak_usage(true);
        $peakMemory = round($peakAfter / 1048576);

        // Ако пикът не е вдигнат от този баланс, неговият е неизвестен, но не по-голям
        $peakText = ($peakAfter > $peakBefore) ? "peak {$peakMemory}MB" : "peak <={$peakMemory}MB (от по-ранно изчисление)";
        if ($totalTime >= self::STATS_LOG_MIN_TIME) {
            self::logNotice("Статистика на баланс {$rec->fromDate} - {$rec->toDate}: total {$totalTime}s, {$peakText}, change={$rec->lastCalculateChange}; " . $bD->getCalcStatsLine(), $rec->id, 3);
        }
    }


    /**
     * Отпечатък на всичко, от което зависи изчислението на баланса
     *
     * Цената по подразбиране (getDefaultCost) не влиза: ползва се само за ред без сума, а след смятането
     * сумата вече е в журнала. Дата не влиза: при неприключени периоди от години насам
     * тя би направила пълно и всяко следващо изчисление по веригата, макар данните да не са се сменили.
     */
    private static function getInputHash($rec, $lastRec, $firstDay, $convertToDate, $journalHash)
    {
        static $accountsHash;
        if (!isset($accountsHash)) {
            $aQuery = acc_Accounts::getQuery();
            $aQuery->show('id,num,type,strategy');
            $accounts = array();
            while ($aRec = $aQuery->fetch()) {
                $accounts[] = "{$aRec->id}:{$aRec->num}:{$aRec->type}:{$aRec->strategy}";
            }
            $accountsHash = md5(implode(',', $accounts));
        }

        // Преобразуването между валутите: кодовете и приложеният курс
        $currency = '';
        if (isset($convertToDate)) {
            $currency = acc_Periods::getBaseCurrencyCode($lastRec->toDate) . '>' . acc_Periods::getBaseCurrencyCode($convertToDate) . ':' . deals_Helper::getSmartBaseCurrency(1000000, $lastRec->toDate, $convertToDate);
        }

        $parts = array(
            $rec->fromDate,
            $rec->toDate,
            $firstDay,
            $lastRec ? $lastRec->id : '',
            $lastRec ? (self::getFingerprint($lastRec->id)['dataToken'] ?? '') : '',
            $convertToDate ?? '',
            $currency,
            acc_Setup::get('FEED_STRATEGY_WITH_NEGATIVE_QUANTITY'),
            $accountsHash,
            $journalHash,
        );

        return md5(implode('|', $parts));
    }


    /**
     * Маркер на данните и отпечатък на входа от последното изчисление на баланса
     *
     * @return array ['dataToken' => string, 'inputHash' => string|null, 'pendingRuns' => int, 'pendingKey' => string, 'calcDuration' => float, 'prevCalcDuration' => float, 'calcPasses' => int]
     */
    private static function getFingerprint($balanceId)
    {
        $res = core_Permanent::get("accBalanceFingerprint_{$balanceId}");

        return is_array($res) ? $res : array();
    }


    /**
     * Записва маркера и отпечатъка на баланса (изтичат, ако балансът не се смята 90 дни)
     */
    private static function setFingerprint($balanceId, $fingerprint)
    {
        core_Permanent::set("accBalanceFingerprint_{$balanceId}", $fingerprint, 60 * 24 * 90);
    }


    /**
     * Рекалкулира баланса
     */
    public function recalc()
    {
        $lockKey = 'RecalcBalances';

        // Ако изчисляването е заключено не го изпълняваме
        if (!core_Locks::obtain($lockKey, self::MAX_PERIOD_CALC_TIME, 1)) {
            $this->logNotice('Изчисляването на баланса е заключено от друг процес');

            return;
        }

        $data = new stdClass();
        $data->recalcedBalances = array();
        if ($oldLastBalance = acc_Balances::getLastBalance()) {
            $data->oldLastBalance = clone $oldLastBalance;
        }

        // Обикаляме всички активни и чакъщи периоди от по-старите, към по-новите
        // Ако периода се нуждае от прекалкулиране - правим го
        // Ако прекалкулирането се извършва в текущия период, то изисляваме баланса
        // до предходния работен ден и селд това до днес

        $pQuery = acc_Periods::getQuery();
        $pQuery->orderBy('#end', 'ASC');
        $pQuery->where("#state != 'closed'");
        $pQuery->where("#state != 'draft'");

        self::$outOfTime = false;

        // Ако е указана граница за изчисляването се използва
        $windowStart = null;
        $alternateWindow = acc_setup::get('ALTERNATE_WINDOW');
        if ($alternateWindow) {
            $windowStart = dt::addSecs(-$alternateWindow, null, false);
            $pQuery->where("#end >= '{$windowStart}'");
        }

        while ($pRec = $pQuery->fetch()) {
            $rec = new stdClass();
            $rec->fromDate = $pRec->start;
            $rec->toDate = $pRec->end;
            $rec->periodId = $pRec->id;

            // Без време за още едно смятане - следващото пускане продължава оттук
            if (self::isOutOfTime()) {
                $this->logNotice("Изчисляването продължава при следващото пускане от {$rec->fromDate} - {$rec->toDate}", null, 3);
                break;
            }

            $periodStart = microtime(true);
            $calcCountBefore = self::$calcCount;
            core_Locks::obtain($lockKey, self::MAX_PERIOD_CALC_TIME);
            if (self::forceCalc($rec)) {
                $data->recalcedBalances[$rec->toDate] = $rec;
            }
            $unfinished = self::$outOfTime;

            $periodTime = round(microtime(true) - $periodStart, 2);
            $calcCalls = self::$calcCount - $calcCountBefore;
            if ($calcCalls > 1 || $periodTime >= self::STATS_LOG_MIN_TIME) {
                $iterations = $rec->calcIterations ?? array();
                $this->logNotice("Преизчисляване на {$rec->fromDate} - {$rec->toDate}: " . countR($iterations) . ' итерации (' . implode(', ', $iterations) . '), край: ' . ($rec->calcStop ?? '-') . ", calc() извиквания {$calcCalls} за {$periodTime}s", $rec->id, 3);
            }

            // Времето е свършило при смятането на този период - следващото пускане продължава от него
            if ($unfinished) {
                $this->logNotice("Изчисляването продължава при следващото пускане от {$rec->fromDate} - {$rec->toDate}", null, 3);
                break;
            }
        }

        // Освобождаваме заключването на процеса
        core_Locks::release($lockKey);
        core_Debug::stopTimer('recalcBalance');

        // Пораждаме събитие, че баланса е бил преизчислен
        $data->lastBalance = acc_Balances::getLastBalance();

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
     * Трие редовете на изтритите междинни баланси - отделно, за да не държи задачата за преизчисляване
     */
    public function cron_DeleteStaleDetails()
    {
        self::deleteStaleDetails();
    }


    /**
     * Добавя или маха изтрит междинен баланс от опашката за триене на детайлите
     *
     * @param int  $balanceId - ид на баланса
     * @param bool $add       - true добавя, false маха
     *
     * @return bool - дали опашката е обновена
     */
    private static function updateStaleQueue($balanceId, $add)
    {
        $lockKey = 'accBalanceStaleQueue';
        if (!core_Locks::obtain($lockKey, 60, 10, 10)) {

            return false;
        }

        $queue = core_Permanent::get(self::STALE_QUEUE_KEY);
        $queue = is_array($queue) ? $queue : array();
        if ($add) {
            $queue[$balanceId] = $balanceId;
        } else {
            unset($queue[$balanceId]);
        }

        if (countR($queue)) {
            core_Permanent::set(self::STALE_QUEUE_KEY, $queue, core_Permanent::FOREVER_VALUE);
        } else {
            core_Permanent::remove(self::STALE_QUEUE_KEY);
        }
        core_Locks::release($lockKey);

        return true;
    }


    /**
     * Трие на части детайлите на изтритите междинни баланси
     *
     * Започнатата заявка не се прекъсва - бюджетът ограничава само започването на следващите
     */
    public static function deleteStaleDetails()
    {
        $queue = core_Permanent::get(self::STALE_QUEUE_KEY);
        if (!is_array($queue) || !countR($queue)) {

            return;
        }

        $lockKey = 'RecalcBalancesCleanup';
        if (!core_Locks::obtain($lockKey, self::MAX_PERIOD_CALC_TIME, 1)) {

            return;
        }

        $start = microtime(true);
        $chunkTime = 0;
        $deleted = 0;
        $done = array();
        foreach ($queue as $balanceId) {

            // Заглавието още съществува - сривът е бил преди изтриването му и то ще се изтрие отново
            if (self::fetch($balanceId, 'id', false)) {
                continue;
            }

            while (true) {
                $elapsed = microtime(true) - $start;
                $timeLeft = core_Cron::getTimeLeft();
                if ($elapsed + $chunkTime > self::STALE_DELETE_MAX_TIME || ($timeLeft !== false && $timeLeft < $chunkTime * 1.5 + 5)) {
                    break 2;
                }

                $chunkStart = microtime(true);
                $cnt = acc_BalanceDetails::delete("#balanceId = {$balanceId}", self::STALE_DELETE_CHUNK);
                $chunkTime = microtime(true) - $chunkStart;
                $deleted += $cnt;

                if ($cnt < self::STALE_DELETE_CHUNK && !acc_BalanceDetails::fetch("#balanceId = {$balanceId}", 'id', false)) {
                    if (self::updateStaleQueue($balanceId, false)) {
                        $done[] = $balanceId;
                    }
                    break;
                }
            }
        }
        core_Locks::release($lockKey);

        if ($deleted || countR($done)) {
            $left = array_diff($queue, $done);
            $time = round(microtime(true) - $start, 2);
            $leftStr = countR($left) ? '#' . implode(', #', $left) : 'няма';
            self::logNotice("Триене на редовете на изтрити междинни баланси: {$deleted} реда за {$time}s (последна порция " . round($chunkTime, 2) . "s), довършени: " . (countR($done) ? '#' . implode(', #', $done) : 'няма') . ", остават: {$leftStr}", null, 3);
        }
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
        
        // Вземаме предния баланс. Ако той е с по-ново време на изчисление, задължително изчисляваме и този
        $query = self::getQuery();
        $query->limit(1);
        $query->where("#toDate < '{$rec->fromDate}'");
        $query->orderBy('toDate', 'DESC');
        $lastRec = $query->fetch();
        
        if ($lastRec && ($lastRec->lastCalculate > $rec->lastCalculate)) {
            
            return false;
        }
        
        // Ако нямаме никакви записи за периода, значи всичко е ОК
        if (empty($rec->lastAlternation)) {
            
            return true;
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
