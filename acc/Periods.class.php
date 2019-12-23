<?php


/**
 * Мениджира периодите в счетоводната система
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 *
 * Текущ период = период в който попада днешната дата
 * Активен период = период в състояние 'active'. Може да има само един активен период
 * Чакащ период - период в състояния 'pending' който е след активния период и преди текущия (ако двата не съвпадат)
 * Бъдещ период - период, който започва след изтичането на текущия
 * Приключен период - период в състояние "closed"
 */
class acc_Periods extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Счетоводни периоди';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Период';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, acc_WrapperSettings, plg_State, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, title, start=Начало, end, vatRate, baseCurrencyId, state, close=Приключване';
    
    
    /**
     * Кой може да пише?
     */
    public $canEdit = 'ceo,acc,admin';
    
    
    /**
     * Кой може да пише?
     */
    public $canClose = 'ceo,accMaster,admin';
    
    
    /**
     * Кой може да редактира системните данни
     */
    public $canEditsysdata = 'ceo,accMaster,admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,acc,admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'admin,ceo,admin';
    
    
    /**
     * Лог
     */
    public $actLog;
    
    
    /**
     * Записа на първия активен период
     */
    private $firstActive;
    
    
    /**
     * Записа на последно затворен период
     */
    private $lastClosed;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('end', 'date(format=d.m.Y)', 'caption=Край,mandatory');
        $this->FLD('state', 'enum(draft=Бъдещ,active=Активен,closed=Приключен,pending=Чакащ)', 'caption=Състояние,input=none');
        $this->FNC('start', 'date(format=d.m.Y)', 'caption=Начало', 'dependFromFields=end');
        $this->FNC('title', 'varchar', 'caption=Заглавие,dependFromFields=start|end');
        $this->FLD('vatRate', 'percent', 'caption=Параметри->ДДС,oldFieldName=vatPercent');
        $this->FLD('baseCurrencyId', 'key(mvc=currency_Currencies, select=code, allowEmpty)', 'caption=Параметри->Валута,width=5em');
    }
    
    
    /**
     * Изчислява полето 'start' - начало на периода
     */
    protected static function on_CalcStart($mvc, $rec)
    {
        $rec->start = dt::mysql2verbal($rec->end, 'Y-m-01');
    }
    
    
    /**
     * Изчислява полето 'title' - заглавие на периода
     */
    protected static function on_CalcTitle($mvc, $rec)
    {
        $rec->title = dt::mysql2verbal($rec->end, 'F Y', null, false);
    }
    
    
    /**
     * Сортира записите по поле end
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('end', 'DESC');
    }
    
    
    /**
     * Добавя за записите поле start и бутони 'Справки' и 'Приключи'
     * Поле 'start' - това поле не съществува в модела. Неговата стойност е end за предходния период + 1 ден.
     * Поле 'reports' - в това поле ще има бутон за справки за периода.
     *
     * @param stdCLass $row
     * @param stdCLass $rec
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Дали може да затворим периода
        if ($mvc->haveRightFor('close', $rec)) {
            
            // Проверяваме имали записи в баланса за този период
            if ($accId = acc_Balances::fetchField("#periodId = {$rec->id}", 'id')) {
                if (acc_BalanceDetails::fetchField("#balanceId = {$accId}")) {
                    
                    // Проверяваме имали контиран приключващ документ за периода
                    if (acc_ClosePeriods::fetchField("#periodId = {$rec->id} AND #state = 'active'")) {
                        
                        // Ако има, периода може да се приключи
                        $row->close = ht::createBtn('Приключване', array($mvc, 'Close', $rec->id, 'ret_url' => true), 'Наистина ли желаете да приключите периода?', null, 'ef_icon=img/16/lock.png,title=Приключване на периода');
                    } else {
                        
                        // Ако няма не може докато не бъде контиран такъв
                        $row->close = ht::createErrBtn('Приключване', 'Не може да се приключи, докато не се контира документ за приключване на периода');
                    }
                } else {
                    
                    // Ако няма записи, то периода може спокойно да се приключи
                    $row->close = ht::createBtn('Приключване', array($mvc, 'Close', $rec->id, 'ret_url' => true), 'Наистина ли желаете да приключите периода?', null, 'ef_icon=img/16/lock.png,title=Приключване на периода');
                }
            }
        }
        
        if ($repId = acc_Balances::fetchField("#periodId = {$rec->id}", 'id')) {
            $row->title = ht::createLink($row->title, array('acc_Balances', 'Single', $repId), null, "ef_icon=img/16/table_sum.png, title = Оборотна ведомост|* {$row->title}");
        }
        
        $curPerEnd = static::getPeriodEnd();
        
        if ($rec->end == $curPerEnd) {
            $row->id = ht::createElement('img', array('src' => sbf('img/16/control_play.png', ''), 'style' => 'display:inline-block; float: left; margin-right:5px', 'title' => 'Текущ период')) . $row->id;
        }
        
        if ($rec->state == 'closed') {
            if ($docId = acc_ClosePeriods::fetchField("#periodId = {$rec->id} AND #state = 'active'", 'id')) {
                $row->close = acc_ClosePeriods::getLink($docId, 0);
            }
        }
    }
    
    
    /**
     * Връща запис за периода, към който се отнася датата.
     * Ако не е зададена $date, връща текущия период
     *
     * @return stdClass $rec
     */
    public static function fetchByDate($date = null)
    {
        static $periods = array();
        
        $lastDayOfMonth = dt::getLastdayOfMonth($date);
        
        if (!$periods[$lastDayOfMonth]) {
            $periods[$lastDayOfMonth] = self::fetch("#end = '{$lastDayOfMonth}'");
        }
        
        return $periods[$lastDayOfMonth];
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        // Форсираме перо за месеца и годината на периода
        static::forceYearItem($rec->end);
    }
    
    
    /**
     * Форсира пера за месеца и годината на дадена дата
     *
     * @param datetime $date - дата
     *
     * @return stdClass -> year - ид на перото на годината
     */
    public static function forceYearItem($date)
    {
        // Коя е годината
        $year = dt::mysql2verbal($date, 'Y');
        
        // Ако има перо за тази година го връщаме, ако няма създаваме ново
        $yearItem = acc_Items::forceSystemItem($year, $year, 'year');
        
        // Връщаме ид-то на перата на годината и месеца
        return (object) array('year' => $yearItem->id);
    }
    
    
    /**
     * Връща посочения период или го създава, като създава и периодите преди него
     */
    public function forcePeriod($date)
    {
        $end = dt::getLastDayOfMonth($date);
        
        $rec = self::fetch("#end = '{$end}'");
        
        if ($rec) {
            
            return $rec;
        }
        
        // Определяме, кога е последният ден на началния период
        $query = self::getQuery();
        $query->orderBy('#end', 'ASC');
        $query->limit(1);
        $firstRec = $query->fetch();
        
        if (!$firstRec) {
            $firstRec = new stdClass();
            
            if (defined('ACC_FIRST_PERIOD_START') && ACC_FIRST_PERIOD_START) {
                
                // Проверяваме дали ACC_FIRST_PERIOD_START е във валиден формат за дата
                $dateArr = date_parse(ACC_FIRST_PERIOD_START);
                
                if (checkdate($dateArr['month'], $dateArr['day'], $dateArr['year'])) {
                    
                    // Ако е валидна дата, за първи запис е посочения месец
                    $firstRec->end = dt::getLastDayOfMonth(dt::verbal2mysql(ACC_FIRST_PERIOD_START));
                } else {
                    
                    // При грешна дата се създава предходния месец на текущия
                    $firstRec->end = dt::getLastDayOfMonth(null, -1);
                }
            } else {
                $firstRec->end = dt::getLastDayOfMonth(null, -1);
            }
        }
        
        // Ако датата е преди началния период, връщаме началния
        if ($end < $firstRec->end) {
            
            return self::forcePeriod($firstRec->end);
        }
        
        // Конфигурационни данни на пакета 'acc'
        $conf = core_Packs::getConfig('acc');
        
        // Връзка към сингълтон инстанса
        $me = cls::get('acc_Periods');
        
        // Ако датата е точно началния период, създаваме го, ако липсва и го връщаме
        if ($end == $firstRec->end) {
            if (!$firstRec->id) {
                $firstRec->vatRate = $conf->ACC_DEFAULT_VAT_RATE;
                $firstRec->baseCurrencyId = currency_Currencies::getIdByCode($conf->BASE_CURRENCY_CODE);
                self::save($firstRec);
                $firstRec = self::fetch($firstRec->id);  // За титлата
                $me->actLog .= "<li style='color:green;'>Създаден е начален период {$firstRec->title}</li>";
            }
            
            return $firstRec;
        }
        
        // Ако периода е след началния, то:
        
        // 1. вземаме предишния период
        $prevEnd = dt::getLastDayOfMonth($date, -1);
        $prevRec = self::forcePeriod($prevEnd);
        
        // 2. създаваме търсения период на база на началния
        $rec = new stdCLass();
        $rec->end = $end;
        
        // Периодите се създават в състояние драфт
        $curPerEnd = static::getPeriodEnd();
        
        if ($rec->end > $curPerEnd) {
            $rec->state = 'draft';
        } else {
            $rec->state = 'pending';
        }
        
        // Вземаме последните
        setIfnot($rec->vatRate, $prevRec->vatRate, ACC_DEFAULT_VAT_RATE);
        
        if ($prevRec->baseCurrencyId) {
            $rec->baseCurrencyId = $prevRec->baseCurrencyId;
        } else {
            $rec->baseCurrencyId = currency_Currencies::getIdByCode($conf->BASE_CURRENCY_CODE);
        }
        
        self::save($rec);
        
        $rec = self::fetch($rec->id);
        
        $me->actLog .= "<li style='color:green;'>Създаден е период {$rec->title}</li>";
        
        return $rec;
    }
    
    
    /**
     * Връща активния период. Създава такъв, ако няма
     */
    public static function forceActive()
    {
        if (!($rec = self::fetch("#state = 'active'"))) {
            $me = cls::get('acc_Periods');
            
            $query = self::getQuery();
            $query->where("#state != 'closed'");
            $query->orderBy('#end', 'ASC');
            $query->limit(1);
            
            $rec = $query->fetch();
            
            $rec->state = 'active';
            
            self::save($rec, 'state');
            
            $me->actLog .= "<li style='color:green;'>Зададен е активен период {$rec->end}</li>";
        }
        
        return $rec;
    }
    
    
    /**
     * Връща последния затворен период
     *
     *
     * @return stdClass|false
     */
    public static function getLastClosed()
    {
        $query = self::getQuery();
        if (null === ($rec = $query->mvc->lastClosed)) {
            $query->limit(1);
            $query->orderBy('#end=DESC');
            $rec = $query->fetch("#state = 'closed'");
        }
        
        return $rec;
    }
    
    
    /**
     * Връща последния затворен период
     *
     *
     * @return stdClass|false
     */
    public static function getFirstActive()
    {
        $query = self::getQuery();
        if (null === ($rec = $query->mvc->firstActive)) {
            $query->limit(1);
            $query->orderBy('#end=ASC');
            $rec = $query->fetch("#state = 'active'");
        }
        
        return $rec;
    }
    
    
    /**
     * Проверява датата в указаното поле на формата дали е в отворен период
     * и записва във формата съобщение за грешка или предупреждение
     * грешка или предупреждение няма, ако датата е от началото на активния,
     * до края на насотящия период
     *
     * @param datetime $dateToCheck - Дата която да се сравни
     *
     * @return string|FALSE - грешката или FALSE ако няма
     */
    public static function checkDocumentDate($dateToCheck)
    {
        if (!$dateToCheck) {
            
            return;
        }
        
        $rec = self::getFirstActive();
        
        if ($rec && ($rec->start > $dateToCheck)) {
            
            return "Датата е преди първия активен период|* <b>{$rec->title}</b>";
        }
        
        $rec = self::fetchByDate($dateToCheck);
        if (!$rec) {
            
            return 'Датата е в несъществуващ счетоводен период';
        }
        
        if ($dateToCheck > dt::getLastDayOfMonth()) {
            
            return 'Датата е в бъдещ счетоводен период';
        }
        
        return false;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        if (!isset($rec->id)) {
            $query = self::getQuery();
            $query->orderBy('#end=ASC');
            $query->limit(1);
            $fPeriod = $query->fetch();
            if ($fPeriod) {
                $rec->end = dt::addDays(-1, $fPeriod->start);
                $form->setDefault('end', $rec->end);
                $form->setReadonly('end');
            }
        } else {
            $data->form->setReadOnly('end');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        if (!$rec->id) {
            $rec->state = 'active';
        }
    }
    
    
    /**
     * Премахва възможността да се редактират периоди със state='closed'
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     * Ако state = 'closed' премахва възможността да се редактира записа.
     *
     * @param acc_Periods   $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param stdClass|NULL $rec
     * @param int|NULL      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Последния ден на текущия период
        $curPerEnd = static::getPeriodEnd();
        
        // Забраняваме всички модификации за всички минали периоди
        if ($action == 'edit' && is_object($rec)) {
            if ($rec->end <= $curPerEnd) {
                $requiredRoles = 'no_one';
            }
        }
        
        // Период може да се затваря само ако е изтекъл
        if ($action == 'close' && is_object($rec) && $rec->id) {
            $rec = self::fetch($rec->id);
            
            if ($rec->end >= $curPerEnd || $rec->state != 'active') {
                $requiredRoles = 'no_one';
            }
            
            // Никой не може да затваря невалиден баланс
            $balRec = acc_Balances::fetch("#periodId = {$rec->id}");
            if (!acc_Balances::isValid($balRec)) {
                $requiredRoles = 'no_one';
            }
        }
        
        // Могат ръчно да се добавят периоди, само, ако няма нито един приключил
        if ($action == 'add') {
            if (self::fetch("#state = 'closed'")) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Затваря активен период и задава на следващия период да е активен
     * Ако няма следващ го създава
     *
     * @return string $res
     */
    public function act_Close()
    {
        $this->requireRightFor('close');
        
        // Затваряме период
        $id = Request::get('id', 'int');
        
        $rec = $this->fetch("#id = '{$id}'");
        
        // Очакваме, че затваряме активен период
        $this->requireRightFor('close', $rec);
        
        // Новото състояние е 'Затворен';
        $rec->state = 'closed';
        
        $this->save($rec);
        
        $res = "|Затворен е период|* <span style=\"color:red;\">{$rec->title}</span>";
        
        // Отваря следващия период. Създава го, ако не съществува
        $this->forcePeriod(dt::addDays(1, $rec->end));
        
        $activeRec = $this->forceActive();
        
        $res .= "<br>|Активен е период|* <span style=\"color:red;\">{$activeRec->title}</span>";
        
        // Записваме, че потребителя е разглеждал този списък
        $this->logWrite('Затваряне на период', $id);
        
        return followRetUrl(null, $res);
    }
    
    
    /**
     * Инициализира начални счетоводни периоди при инсталиране
     * Ако няма дефинирани периоди дефинира период, чийто край е последния ден от предходния
     * месец със state='closed' и период, който е за текущия месец и е със state='active'
     */
    public function loadSetupData2()
    {
        // Форсира създаването на периоди от текущия месец до ACC_FIRST_PERIOD_START
        $this->forcePeriod(dt::verbal2mysql());
        
        $this->updateExistingPeriodsState();
        
        return $this->actLog;
    }
    
    
    /**
     * Обновява състоянията на съществуващите чернови периоди
     */
    protected function updateExistingPeriodsState()
    {
        $curPerEnd = static::getPeriodEnd();
        $activeRec = $this->forceActive();
        
        $query = $this->getQuery();
        $query->where("#end > '{$activeRec->end}' AND #end <= '{$curPerEnd}'");
        
        // Ако сме достигнали указания ден за активиране на следващия бъдещ период
        $daysBefore = acc_Setup::get('DAYS_BEFORE_MAKE_PERIOD_PENDING');
        
        if ($daysBefore) {
            if (dt::now() >= dt::addSecs(-1 * $daysBefore, $curPerEnd)) {
                
                // Опитваме се да намерим пърия бъдещ период с начало, ден след края на предходния
                $nQuery = acc_Periods::getQuery();
                $nQuery->where("#state = 'draft'");
                $nQuery->orderBy('id', 'ASC');
                
                $nextDay = dt::addDays(1, $curPerEnd);
                $nextDay = dt::verbal2mysql($nextDay, false);
                $draftId = null;
                while ($draftRec = $nQuery->fetch()) {
                    if ($draftRec->start == $nextDay) {
                        $draftId = $draftRec->id;
                        break;
                    }
                }
                
                // Ако е намерен такъв период, добавяме го в заявката, така че да стане чакащ
                if (isset($draftId)) {
                    $query->orWhere("#id = {$draftId}");
                }
            }
        }
        
        while ($rec = $query->fetch()) {
            $rec->state = 'pending';
            $this->save($rec);
        }
    }
    
    
    /**
     * Създава бъдещи (3 месеца напред) счетоводни периоди
     */
    public function cron_CreateFuturePeriods()
    {
        $this->forcePeriod(dt::getLastDayOfMonth(null, 3));
        $this->updateExistingPeriodsState();
    }
    
    
    /**
     * Връща първичния ключ (id) на базовата валута към определена дата
     *
     * @param string $date Ако е NULL - текущата дата
     *
     * @return int key(mvc=currency_Currencies)
     */
    public static function getBaseCurrencyId($date = null)
    {
        $periodRec = static::fetchByDate($date);
        
        if (!($baseCurrencyId = $periodRec->baseCurrencyId)) {
            $conf = core_Packs::getConfig('acc');
            $baseCurrencyId = currency_Currencies::getIdByCode($conf->BASE_CURRENCY_CODE);
        }
        
        return $baseCurrencyId;
    }
    
    
    /**
     * Връща кода на базовата валута към определена дата
     *
     * @param string $date Ако е NULL - текущата дата
     *
     * @return string трибуквен ISO код на валута
     */
    public static function getBaseCurrencyCode($date = null)
    {
        return currency_Currencies::getCodeById(static::getBaseCurrencyId($date));
    }
    
    
    /**
     * Връща края на даден период
     *
     * @param datetime $date - дата от период, NULL  ако е текущия
     *
     * @return datetime - крайната дата на периода (ако съществува)
     */
    public static function getPeriodEnd($date = null)
    {
        return acc_Periods::fetchByDate($date)->end;
    }
    
    
    /**
     * Помощна функция подготвяща датите за сравняване
     *
     * @param string $from
     * @param string $to
     * @param string $displacement със стойности "months|year"
     *
     * @return stdClass $res
     *                  $res->from - начало на сравнявания период
     *                  $res->to - край на сравнявания период
     */
    public static function comparePeriod($from, $to, $displacement = null)
    {
        switch ($displacement) {
            
            case 'months':
                
                $dFrom = date('d', dt::mysql2timestamp($from));
                $date1 = new DateTime(dt::addDays(1, $to));
                $date2 = new DateTime($from);
                $interval = date_diff($date1, $date2);
                $months = $interval->m;
                $days = $interval->days;
                
                if ($dFrom == '01' && $to == dt::getLastDayOfMonth($to) && $interval->y == 0) {
                    $toCompare = dt::getLastDayOfMonth($from, -1);
                    
                    $first = date('Y-m-01', dt::mysql2timestamp($toCompare));
                    $dToCompare = date('d', dt::mysql2timestamp($toCompare));
                    $mToCompare = date('m', dt::mysql2timestamp($toCompare));
                    
                    if ($months == 1) {
                        if ($interval->d == 0 || $interval->d == 1) {
                            $fromCompare1 = $first;
                        } elseif ($interval->d == 2) {
                            $fromCompare1 = dt::addMonths(-$months + 1, $first);
                        } else {
                            $fromCompare1 = dt::addMonths(-$months, $first);
                        }
                    } else {
                        $fromCompare1 = dt::addMonths(-$months + 1, $first);
                    }
                    
                    if ($dToCompare == '28' && $mToCompare == '02') {
                        $fromCompare1 = dt::addMonths(-$months + 1, $toCompare);
                    }
                    
                    $fromCompare = date('Y-m-01', dt::mysql2timestamp($fromCompare1));
                } else {
                    $toCompare = strstr(dt::addDays(-1, $from), ' ', true);
                    $fromCompare = strstr(dt::addDays(-($days - 1), $toCompare), ' ', true);
                }
                
                break;
            
            case 'year':
                
                $toCompare = date('Y-m-d', strtotime('-12 months', dt::mysql2timestamp($to)));
                $fromCompare = date('Y-m-d', strtotime('-12 months', dt::mysql2timestamp($from)));
                
                break;
            
            default:
                
                $fromCompare = $from;
                $toCompare = $to;
        }
        
        return (object) array('from' => $fromCompare, 'to' => $toCompare);
    }
    
    
    /**
     * Дали датата е в затворен счетоводен период
     *
     * @param datetime $date - дата
     *
     * @return bool - Затворен ли е периода в който е датата
     */
    public static function isClosed($date)
    {
        // В кой период е датата
        $period = self::fetchByDate($date);
        
        // Проверка дали периода е затворен
        return $period->state == 'closed';
    }
    
    
    /**
     * Връща всички периоди, с изчислен баланс
     *
     * @param bool     $descending - възходящ или низходящ ред
     * @param int|NULL $limit      - лимит
     *
     * @return array $periods     - периодите с баланс
     */
    public static function getCalcedPeriods($descending = false, $limit = null)
    {
        $periods = array();
        $bQuery = acc_Balances::getQuery();
        $bQuery->where('#periodId IS NOT NULL');
        
        $orderBy = ($descending === true) ? 'DESC' : 'ASC';
        $bQuery->orderBy('#fromDate', $orderBy);
        $bQuery->show('periodId');
        $bQuery->groupBy('periodId');
        if (isset($limit)) {
            $bQuery->limit($limit);
        }
        
        while ($bRec = $bQuery->fetch()) {
            $b = acc_Balances::recToVerbal($bRec, 'periodId');
            $periods[$bRec->periodId] = $b->periodId;
        }
        
        return $periods;
    }
    
    
    /**
     * Премахва от резултатите скритите от менютата за избор
     */
    protected static function on_AfterMakeArray4Select($mvc, &$res, $fields = null, &$where = '', $index = 'id')
    {
        if (is_array($res)) {
            krsort($res);
        }
    }
}
