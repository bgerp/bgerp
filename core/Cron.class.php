<?php


/**
 * Клас 'core_Cron' - Стартиране на процеси по часовник
 *
 * Поддържа механизъм за периодично (по часовник) извикване на методи
 * в системата, които са регистрирани в таблицата на този клас и името
 * им започва с приставката 'cron_'
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Cron extends core_Manager
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Периодични процеси';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Периодичен процес';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,title=Описание,parameters=Параметри,last=Последно, max=Максимални стойности, state';
    
    
    /**
     * Списък с плъгини, които се прикачат при конструиране на мениджъра
     */
    public $loadList = 'plg_Created,plg_Modified,plg_SystemWrapper,plg_RowTools,plg_RefreshRows,plg_State2,plg_Search';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Кой може да променя състояниет?
     *
     * @see plg_State2
     */
    public $canChangestate = 'admin';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'admin';
    
    
    /**
     * Кой има право да редактира?
     */
    public $canEdit = 'admin';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Време за опресняване информацията при лист на събитията
     */
    public $refreshRowsTime = 5000;
    
    
    /**
     * Спиране на подреждането по състоянието
     */
    public $state2PreventOrderingByState = true;
    
    
    /**
     * Записа на последния стартиран процес
     */
    public $currentRec;
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'systemId,description,controller,action';
    
    
    /**
     * Дали за този модел ще се прави репликация на SQL заявките
     */
    public $doReplication = false;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('systemId', 'varchar', 'caption=Системен ID,notNull');
        $this->FLD('description', 'varchar', 'caption=Описание');
        $this->FLD('controller', 'varchar(64)', 'caption=Контролер');
        $this->FLD('action', 'varchar(32)', 'caption=Функция');
        $this->FLD('period', 'minutes(Min=0, max=600000)', 'caption=Период (мин)');
        $this->FLD('offset', 'minutes(min=0, max=600000)', 'caption=Отместване (мин)');
        $this->FLD('delay', 'int(min=0, Max=60)', 'caption=Закъснение (s)');
        $this->FLD('timeLimit', 'int(min=0, max=10000)', 'caption=Време-лимит (s)');
        $this->FLD('state', 'enum(free=Свободно,locked=Заключено,stopped=Спряно)', 'caption=Състояние,1input=none');
        $this->FLD('lastStart', 'datetime', 'caption=Последно->Стартиране,input=none');
        $this->FLD('lastDone', 'datetime', 'caption=Последно->Приключване,input=none');
        $this->FLD('lastMaxUsedMemory', 'fileman_FileSize', 'caption=Последно->Използвана памет,input=none');
        $this->FLD('data', 'blob(compress, serialize)', 'caption=Данни,input=none');
        
        $this->setDbUnique('systemId,offset,delay');
        
        $this->dbEngine = 'InnoDB';
    }
    
    
    /**
     * Връща записа на текъщия крон процес
     * Ако в текущия хит не е по крон процес, връща NULL
     *
     * @return NULL|stdClass
     */
    public static function getCurrentRec()
    {
        $me = cls::get('core_Cron');
        $rec = $me->currentRec;
        
        return $rec;
    }
    
    
    /**
     * Връща секундите, които оставят до края на прозореца за изпълнение
     */
    public static function getTimeLeft()
    {
        $rec = self::getCurrentRec();
        
        if ($rec) {
            $deadline = dt::mysql2timestamp($rec->lastStart) + max($rec->timeLimit, 30);
            
            return max($deadline - time(), 0);
        }
        
        return false;
    }
    
    
    /**
     * Връща времето на последно стартиране на процес
     *
     * @param $systemId
     *
     * @return NULL|datetime
     */
    public static function getLastStartTime($systemId = null)
    {
        $query = self::getQuery();
        $query->limit(1);
        
        if ($systemId) {
            $query->where(array("#systemId = '[#1#]'", $systemId));
        } else {
            $query->where('#lastStart IS NOT NULL');
            $query->orderBy('lastStart', 'DESC');
        }
        
        $rec = $query->fetch();
        
        if ($rec) {
            
            return $rec->lastStart;
        }
    }
    
    
    /**
     * Връща периода на стартиране на процеса в секунду
     *
     * @param string $systemId
     *
     * @return int
     */
    public static function getPeriod($systemId)
    {
        $rec = self::getRecForSystemId($systemId);
        
        if ($rec === false) {
            
            return ;
        }
        
        $period = ($rec->period * 60) + ($rec->offset * 60);
        
        return $period;
    }
    
    
    /**
     *
     *
     * @param string $systemId
     *
     * @return FALSE|object
     */
    public static function getRecForSystemId($systemId)
    {
        $rec = self::fetch(array("#systemId = '[#1#]'", $systemId));
        
        return $rec;
    }
    
    
    /**
     * Преди извличането на записите за листовия изглед
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->query->XPR('oState', 'int', "IF(#state != 'stopped', 1, 0)");
        $data->query->orderBy('oState', 'DESC');
        
        $data->query->orderBy('period');
        $data->query->orderBy('offset');
        $data->query->orderBy('systemId');
    }
    
    
    public static function on_AfterPrepareListRows($mvc, $data)
    {
        // Изчистваме нотификацията
        bgerp_Notifications::clear(array('core_Cron'));
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        $data->toolbar->addBtn(
            'Логове на Cron',
            array(
                'log_System',
                'search' => $mvc->className
            ),
            'ef_icon = img/16/action_log.png'
        );
    }
    
    
    /**
     * Този метод се задейства през интервал от 1 минута от OS
     */
    public function act_Cron()
    {
        if (!Request::get('forced')) {
            core_App::flushAndClose(false);
            // Подтиска използването на сесията на сесията.
            core_Session::$mute = true;
        }
        
        $whitelist = array(
            '127.0.0.1',
            '::1'
        );
        
        if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
            // requireRole('debug,admin');
        }
        
        // Ако в момента се извършва инсталация - да не се изпълняват процесите
        core_SystemLock::stopIfBlocked();
        
        // Отключваме всички процеси, които са в състояние заключено, а от последното
        // им стартиране е изминало повече време от Време-лимита-а
        $query = $this->getQuery();
        $query->where("#state = 'locked'");
        $now = dt::verbal2mysql();
        $query->where("DATE_ADD(#lastStart, INTERVAL #timeLimit SECOND) < '{$now}'");
        
        while ($rec = $query->fetch()) {
            $rec->state = 'free';
            $this->save($rec, 'state');
            $this->logWarning('Отключен процес, започнал в ' . $rec->lastStart, $rec->id, 7);
        }
        
        // Коя е текущата секунда?
        $timeStamp = time();
        
        // Добавяме отместването във времето за timezone
        $timeStamp += date('Z');
        
        /// Намираме коя е текущата минута
        $currentMinute = floor($timeStamp / 60);
        
        // Определяме всички процеси, които трябва да се стартират през тази минута
        // и ги стартираме наред
        $query = $this->getQuery();
        $i = 0;
        
        $mCnt = 5;
        
        while ($rec = $query->fetch("#state != 'stopped'")) {
            set_time_limit(120);
            
            // Кога е бил последно стартиран този процес?
            $lastStarting = $rec->lastStart;
            
            // В коя минута е трябвало за последен път да се стартира този процес?
            $lastSchedule = dt::timestamp2mysql((floor(($currentMinute - $rec->offset) / $rec->period) * $rec->period + $rec->offset) * 60 - date('Z'));
            $now = dt::timestamp2mysql($currentMinute * 60 - date('Z'));
            
            // Колко минути остават до следващото стартиране
            $remainMinutes = floor(($currentMinute - $rec->offset) / $rec->period) * $rec->period + $rec->period + $rec->offset - $currentMinute;
            
            $maxRemain = 60;
            
            if ((($currentMinute % $rec->period) == $rec->offset) || ($rec->period > $maxRemain && $lastSchedule > $lastStarting && $maxRemain < $remainMinutes)) {
                if (($maxRemain < $remainMinutes) && (($currentMinute % $rec->period) != $rec->offset)) {
                    if ($mCnt-- <= 0) {
                        continue;
                    }
                    
                    self::logNotice('Форсирано пускане на пропуснат процес', $rec->id);
                }
                
                $i++;
                $url = toUrl(array('Act' => 'ProcessRun','id' => str::addHash($rec->id)), 'absolute-force');
                core_Url::start($url);
            }
        }
        
        $this->logThenStop("Стартирани са {$i} процеса", null, 'info');
    }
    
    
    /**
     * Екшън за стартиране на единичен процес
     */
    public function act_ProcessRun()
    {
        // Подтиска използването на сесията на сесията.
        core_Session::$mute = true;

        $this->logInfo('Процес:::: ' . Request::get('id'));
        
        // Затваряме връзката създадена от httpTimer, ако извикването не е форсирано
        if (!($forced = Request::get('forced'))) {
            core_App::flushAndClose(false);
        } else {
            header('Content-type: text/html; charset=utf-8');
        }
        
        // Форсираме системния потребител
        core_Users::forceSystemUser();
        
        // Декриптираме входния параметър. Чрез предаването на id-to на процеса, който
        // трябва да се стартира в защитен вид, ние се предпазваме от евентуална външна намеса
        $id = str::checkHash(Request::get('id'));
        
        if (!$id || !is_numeric($id)) {
            $cryptId = Request::get('id');
            $this->logThenStop("Некоректно id за криптиране: {$cryptId}", null, 'err');
        }
        
        log_Browsers::stopGenerating();
        
        // Да не правим лог в Debug за хита, ако се вика по крон
        if (!Request::get('forced')) {
            Debug::$isLogging = false;
        }
        
        
        // Вземаме информация за процеса
        $rec = $this->fetch($id);
        
        if (!$rec) {
            $this->logThenStop('Липсва запис', $id, 'err');
        }
        
        
        // Дали процесът не е заключен?
        if ($rec->state == 'locked' && !$forced) {
            $this->logThenStop('Процесът е заключен', $id, 'warning');
        }
        
        // Дали този процес не е стартиран след началото на текущата минута
        $nowMinute = date('Y-m-d H:i:00', time());
        if ($nowMinute <= $rec->lastStart && !$forced) {
            $this->logThenStop('Процесът е стартиран повторно по крон в една и съща минута', $id, 'notice');
        }
        
        // Заключваме процеса и му записваме текущото време за време на последното стартиране
        $rec->state = 'locked';
        $rec->lastStart = dt::verbal2mysql();
        $rec->lastDone = null;
        $rec->lastMaxUsedMemory = null;
        $this->save($rec, 'state,lastStart,lastDone,lastMaxUsedMemory');
        $this->currentRec = clone($rec);
        
        // Изчакваме преди началото на процеса, ако е зададено
        if ($rec->delay > 0 && !$forced) {
            core_App::setTimeLimit(30 + $rec->delay);
            sleep($rec->delay);
            Debug::log("Sleep {$rec->delay} sec. in " . __CLASS__);
        }
        
        // Стартираме процеса
        $act = 'cron_' . $rec->action;
        
        $class = cls::getClassName($rec->controller);
        
        $handlerObject = & cls::get($class);
        
        if (is_a($handlerObject, $class)) {
            if (method_exists($handlerObject, $act)) {
                self::logInfo('Стартиран процес: ' . $rec->action, $rec->id, 3);
                
                // Ако е зададено максимално време за изпълнение,
                // задаваме го към PHP , като добавяме 5 секунди
                if ($rec->timeLimit) {
                    core_App::setTimeLimit($rec->timeLimit + 20);
                }
                
                $startingMicroTime = $this->getMicrotime();
                $content = $handlerObject->$act();
                
                if (!Request::get('forced')) {
                    ob_clean();
                }
                
                // Ако извикания метод е генерирал резултат, то го добавяме
                // подходящо форматиран към лога
                if ($content) {
                    $content = "<p><i>${content}</i></p>";
                    if (Request::get('forced')) {
                        echo $content;
                    }
                }
                
                $workingTime = round($this->getMicrotime() - $startingMicroTime, 2);
                
                self::logInfo("Процесът '{$rec->action}' е изпълнен успешно за {$workingTime} секунди", $rec->id, 3);
            } else {
                $this->unlockProcess($rec);
                $this->logThenStop('Няма такъв екшън в класа', $rec->id, 'err');
            }
        } else {
            $this->unlockProcess($rec);
            $this->logThenStop('Няма такъв клас', $rec->id, 'err');
        }
        
        // Отключваме процеса и му записваме текущото време за време на последното приключване
        $this->unlockProcess($rec);
        $this->logThenStop();
    }
    
    
    /**
     * Записва в лога и спира
     */
    public function logThenStop($msg = '', $id = null, $type = 'info')
    {
        // Ако имаме съобщение - записваме го в лога
        if (strlen($msg)) {
            $lifeDays = 7;
            if ($type == 'info') {
                $lifeDays = 3;
            }
            log_System::add(get_called_class(), $msg, $id, $type, $lifeDays);
        }
        
        // Ако извикването е от браузър - отпечатваме резултата
        if (Request::get('forced')) {
            echo(core_Debug::getLog());
        }
        
        shutdown();
    }
    
    
    /**
     * Отключва заключен процес
     */
    public function unlockProcess($rec)
    {
        if (!$rec || !$rec->id) {
            
            return ;
        }
        $rec = $this->fetch($rec->id);
        
        if ($rec->state == 'locked') {
            $rec->state = 'free';
            $rec->lastDone = dt::verbal2mysql();
            $rec->lastMaxUsedMemory = memory_get_peak_usage(true);
            
            $saveArr = array('state' => 'state', 'lastDone' => 'lastDone', 'lastMaxUsedMemory' => 'lastMaxUsedMemory');
            
            $mPeriod = max(7 * $rec->period, 3 * 1440);
            $mPeriod *= 60;
            
            $data = &$rec->data;
            
            if (($rec->lastMaxUsedMemory >= $data['maxUsedMemory']) || (dt::subtractSecs($mPeriod, $rec->lastDone) > $data['maxUsedMemoryTime'])) {
                $data['maxUsedMemory'] = $rec->lastMaxUsedMemory;
                $data['maxUsedMemoryTime'] = $rec->lastDone;
                $saveArr['data'] = 'data';
            }
            
            $duration = dt::secsBetween($rec->lastDone, $rec->lastStart);
            $duration -= $rec->delay;
            if (($duration >= $data['maxDuration']) || (dt::subtractSecs($mPeriod, $rec->lastDone) > $data['maxDurationTime'])) {
                $data['maxDuration'] = $duration;
                $data['maxDurationTime'] = $rec->lastDone;
                $saveArr['data'] = 'data';
            }
            
            $this->save($rec, $saveArr);
        }
    }
    
    
    /**
     * Изпълнява се след поготовка на формата за редактиране
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $data->form->setOptions('state', array('free' => 'Свободно',
            'stopped' => 'Спряно'
        ));
    }
    
    
    /**
     * Изпълнява се при всяко преобразуване на запис към вербални стойности
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // За по-голяма точност, показваме и секундите
        $row->lastStart = dt::mysql2verbal($rec->lastStart, 'd.m.y  H:i:s');
        $row->lastDone = dt::mysql2verbal($rec->lastDone, 'd.m.y  H:i:s');
        
        $row->description = $mvc->getVerbal($rec, 'description');
        $row->controller = $mvc->getVerbal($rec, 'controller');
        $row->action = $mvc->getVerbal($rec, 'action');
        $row->period = $mvc->getVerbal($rec, 'period');
        $row->offset = $mvc->getVerbal($rec, 'offset');
        $row->delay = $mvc->getVerbal($rec, 'delay');
        $row->timeLimit = $mvc->getVerbal($rec, 'timeLimit');
        $row->systemId = $mvc->getVerbal($rec, 'systemId');
        $row->lastMaxUsedMemory = $mvc->getVerbal($rec, 'lastMaxUsedMemory');
        
        if ($rec->lastStart) {
            $duration = null;
            if ($rec->lastDone) {
                $duration = dt::secsBetween($rec->lastDone, $rec->lastStart);
                
                $duration -= $rec->delay;
                
                $duration = " ({$duration}s)";
            }
            
            $row->last = '<p>' . tr('Начало') . ": <b>{$row->lastStart}</b>" . $duration . '</p>';
        }
        
        if ($rec->lastMaxUsedMemory) {
            $row->last .= '<p>' . tr('Памет') . ": <b>{$row->lastMaxUsedMemory}</b></p>";
        }
        
        if ($rec->data['maxUsedMemory']) {
            $fType = cls::get('fileman_FileSize');
            
            $row->max .= '<p>' . tr('Памет') . ': <b>' . $fType->toVerbal($rec->data['maxUsedMemory']) . '</b> - ' . dt::mysql2verbal($rec->data['maxUsedMemoryTime'], 'smartTime') . '</p>';
        }
        
        if ($rec->data['maxDuration']) {
            $tTime = cls::get('type_Time');
            
            $row->max .= '<p>' . tr('Прод.') . ': <b>' . $tTime->toVerbal($rec->data['maxDuration']) . '</b> - ' . dt::mysql2verbal($rec->data['maxDurationTime'], 'smartTime') . '</p>';
        }
        
        $url = toUrl(array(
            'Act' => 'ProcessRun',
            'id' => str::addHash($rec->id),
            'forced' => 'yes'
        ), 'absolute');
        
        $row->systemId = ht::createLink("<b>{$row->systemId}</b>", $url, null, array('target' => 'cronjob'));
        
        $row->title = '<p>' . $row->systemId . "</p><p><i>{$row->description}</i></p>";
        
        $row->parameters = "<p style='color:green'><b>\${$row->controller}->{$row->action}</b><p>" .
        tr('Всеки') . " <b>{$row->period}</b> + <b>{$row->offset}</b>";
        
        if ($rec->delay) {
            $row->parameters .= ', ' . tr('Зак.') . ": <b>{$row->delay}</b> s";
        }
        
        if ($rec->timeLimit) {
            $row->parameters .= ', ' . tr('Лимит') . ": <b>{$row->timeLimit}</b> s";
        }
        
        $now = dt::mysql2timestamp(dt::verbal2mysql());
        
        if ($rec->state == 'locked' ||
            ($rec->lastStart && $rec->state == 'free' && (($now - $mvc->refreshRowsTime / 1000 - 2) < dt::mysql2timestamp($rec->lastStart)))) {
            $row->ROW_ATTR['style'] .= 'background-color:#ffa;';
        } elseif ($rec->state == 'free') {
            $row->ROW_ATTR['style'] .= 'background-color:#cfc;';
        } elseif ($rec->state == 'stopped') {
            $row->ROW_ATTR['style'] .= 'background-color:#aaa;';
        }
    }
    
    
    /**
     * Връща timestamp в микро секунди, като рационално число
     */
    public static function getMicrotime()
    {
        list($usec, $sec) = explode(' ', microtime());
        
        return ((float) $usec + (float) $sec);
    }
    
    
    /**
     * Добавя запис, като гледа да няма запис със същото systemId
     *
     * return boolean
     */
    public static function addOnce($rec, $force = false)
    {
        if (is_array($rec)) {
            $rec = (object) $rec;
        }
        
        expect($rec->systemId);
        expect($rec->description);
        expect($rec->controller);
        expect($rec->action);
        
        // Периода трябва да е по-голям от една минута
        expect($rec->period >= 1);
        
        // Офсета трябва да е по-голям от нула и да е по-малък от периода
        if (!isset($rec->offset)) {
            if ($rec->period > 1) {
                $rec->offset = rand(0, $rec->period - 1);
            } else {
                $rec->offset = 0;
            }
        }
        
        $rec->offset = max(0, $rec->offset);
        expect($rec->period > $rec->offset);
        
        // Търсим дали има съществуващ запис със същото id
        $exRec = self::fetch(array("#systemId = '[#1#]'", $rec->systemId));
        
        if (!$exRec && isset($rec->exSystemId)) {
            $exRec = self::fetch(array("#systemId = '[#1#]'", $rec->exSystemId));
        }
        
        // Записваме, че записът е създаден от системния потребител
        setIfNot($rec->createdBy, -1);
        
        // Ако няма зададено преди това състояние - то е празно
        setIfNot($rec->state, 'free');
        
        // По подразбиране 50 секунди времелимит за извършване на операцията
        setIfNot($rec->timeLimit, 50);
        
        // Описанието с малки букви
        $description = mb_strtolower(mb_substr($rec->description, 0, 1)) . mb_substr($rec->description, 1);
        
        // Ако има стар запис и е редактиран от потребител
        // - обновяваме записа с изключение на състоянието, отместването, периода и времелимит-а
        if ($exRec) {
            // Имаме стар запис
            $rec->id = $exRec->id;
            $systemDataChanged = ($rec->systemId != $exRec->systemId ||
                                  $rec->description != $exRec->description ||
                                  $rec->controller != $exRec->controller ||
                                  $rec->action != $exRec->action);
            if ($exRec->modifiedBy == -1 || !$exRec->modifiedBy) {
                // Ако не е редактиран и има промени го обновяваме
                if ($systemDataChanged || $rec->period != $exRec->period ||
                      floor($rec->delay) != floor($exRec->delay) ||
                      $rec->timeLimit != $exRec->timeLimit
                    ) {
                    $mustSave = true;
                    $msg = "<li class=\"debug-update\">Обновено разписание за {$description}</li>";
                } else { // ако няма промени го пропускаме
                    $mustSave = false;
                    $msg = "<li class=\"debug-info\">Съществуващо разписание за {$description}</li>";
                }
            } elseif ($systemDataChanged) {
                $mustSave = true;
                unset($rec->period);
                unset($rec->offset);
                unset($rec->delay);
                unset($rec->timeLimit);
                $msg = "<li class=\"debug-notice\">Запазени потребителски настройки на разписание за {$description}</li>";
            }
        } else {
            $mustSave = true;
            $msg = "<li class=\"debug-new\">Добавено разписание за {$description}</li>";
        }
        
        
        if ($mustSave) {
            if (!self::save($rec)) {
                $msg = "<li class=\"debug-error\">Грешка при нагласяне на разписание за {$description}</li>";
            }
        }
        
        return $msg;
    }
    
    
    /**
     * Рутинен метод, премахва задачите, свързани с класове от посочения пакет
     */
    public static function deinstallPack($pack)
    {
        $res = '';
        $query = self::getQuery();
        $preffix = $pack . '_';
        $rowCnt = $query->delete(array("#controller LIKE '[#1#]%'", $preffix));
        if ($rowCnt) {
            $res .= "<li class='debug-notice'>Бяха премахнати {$rowCnt} нагласения на Cron</li>";
        }
        
        return $res;
    }
    
    
    /**
     * Премахва методите, за които не съществуват входни точки
     */
    public static function cleanRecords()
    {
        $query = self::getQuery();
        
        while ($rec = $query->fetch()) {
            if (cls::load($rec->controller, true)) {
                $ctr = cls::get($rec->controller);
                if (method_exists($ctr, 'cron_' . $rec->action)) {
                    continue;
                }
            }
            
            $class = cls::getClassName($rec->controller);
            
            self::delete($rec->id);
            
            $res .= ($res ? ', ' : '') . "{$class}::{$rec->action}";
        }
        
        if ($res) {
            
            return "<li style='color:brown;'>Премахнати бяха липсващите входни точки за Cron: {$res}</li>";
        }
    }
    
    
    /**
     * Връща времето на следващото стартиране на крона
     *
     * @param string $systemId
     *
     * @return datetime|NULL|FALSE $nextStartTime
     */
    public static function getNextStartTime($systemId)
    {
        // Вземаме записитеи за тази ситема
        $rec = core_Cron::fetch("#systemId = '{$systemId}'");
        
        // Ако е спрян или няма период
        if ($rec->state == 'stopped' || !$rec->period) {
            
            // Връщаме FALSE
            return false;
        }
        
        // Текущото време
        $now = dt::now();
        
        // Ако няма време на последно стартиране
        if (!($startTime = $rec->lastStart)) {
            
            // Използваме текущото време
            $startTime = $now;
        }
        
        // Добавяме секундите
        $nextStartTime = dt::addSecs($rec->period * 60, $startTime);
        
        // Ако е преди текущото време, връщаме NULL
        if ($nextStartTime < $now) {
            
            return;
        }
        
        return $nextStartTime;
    }
    
    
    /**
     *
     *
     * @param int  $id
     * @param bool $escape
     */
    public static function getTitleForId_($id, $escaped = true)
    {
        if (!$id) {
            
            return parent::getTitleById($id, $escaped);
        }
        
        $rec = self::fetch($id);
        
        return $rec->systemId;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Cron $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            if ($form->rec->offset >= $form->rec->period) {
                $form->setError('offset', 'Отместването трябва да е по-малко от периода');
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($rec && ($action == 'edit')) {
            if (!$rec->modifiedBy || $rec->modifiedBy == '-1') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Самообраняващ процес
     * Ако открие, че Крон е пропуснал да изпълни процеси в последната минута,
     * то предизвиква периодично запалването му
     */
    public function act_Watchdog()
    {
        set_time_limit(90);
        core_App::flushAndClose(false);
        
        // Подтиска използването на сесията на сесията.
        core_Session::$mute = true;

        // Пробваме да вземем lock за този процес, за 65 секунди
        while (core_Locks::get('core_Cron::Watchdog', 80)) {
            set_time_limit(120);
            
            // Изчакваме да стане 10-тата секунда от минутата
            $rest = (70 - (time() % 60)) % 60;
            if ($rest > 0) {
                $this->logInfo('Спи ' . $rest . ' сек.');
                sleep($rest);
                $this->logInfo('Събуждане');
            }
            
            // Ако има пуснати процеси, преди по-малко или равно на 10 секунди,
            // излизаме, защото някой друг се грижи
            $lastStart = self::getLastStartTime();
            if (!$lastStart) {
                $lastStart = '2000-01-01';
            }
            $lastStartBefore = time() - dt::mysql2timestamp($lastStart);
            if ($lastStartBefore <= 10) {
                $okTrays++;
                $this->logInfo('Пропускаме, защото има скорошни пускания');
                if ($okTrays > 3) {
                    $this->logInfo('3 пропускания - свършваме');
                    core_App::shutdown(false);
                }
            } else {
                $okTrays = 0;
                
                // Самостартираме крон
                $url = toUrl(array('core_Cron', 'cron'), 'absolute-force');
                core_Url::start($url);
            }
            
            // Изчакваме още 2 секунди
            sleep(2);
        }
        
        $this->logInfo('Излиза, защото не може да вземе лок');
        core_App::shutdown(false);
    }
}
