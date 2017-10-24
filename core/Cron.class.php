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
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Cron extends core_Manager
{
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Периодични процеси';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = 'Периодичeн процес';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id,title=Описание,parameters=Параметри,last=Последно,state";
    
    
    /**
     * Списък с плъгини, които се прикачат при конструиране на мениджъра
     */
    var $loadList = "plg_Created,plg_Modified,plg_SystemWrapper,plg_RowTools,plg_RefreshRows,plg_State2,plg_Search";
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin';
	
	
	/**
	 * Кой може да променя състояниет?
	 * @see plg_State2
	 */
	var $canChangestate = 'admin';
    
    
    /**  
	 * Кой има право да променя системните данни?  
	 */  
	var $canEditsysdata = 'admin';
	
    
    /**  
	 * Кой има право да редактира?  
	 */  
	var $canEdit = 'admin';
	
    
    /**  
	 * Кой има право да добавя?  
	 */  
	var $canAdd = 'no_one';
    
    
    /**
     * Време за опресняване информацията при лист на събитията
     */
    var $refreshRowsTime = 5000;
    

    /**
     * Максимално време в Unix time, до което може да се изпълнява процеса
     */
     static $timeDeadline = 0;


    /**
     * $systemId na последния стартиран процес
     */
    static $lastSystemId;


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'systemId,description,controller,action';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
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

        $this->setDbUnique('systemId,offset,delay');
		
        $this->dbEngine = 'InnoDB';
    }
    
    
    /**
     * Връща максималното време за изпълнение
     *
     * @param $systemId
     *
     * @return int - време в секунди
     */
    public static function getTimeLimit($systemId = NULL)
    {
        if(!$systemId) {
            $systemId = self::$lastSystemId;
        }
        
        if($systemId) {
    	    $rec = self::getRecForSystemId($systemId);
    	
    	    return $rec->timeLimit;
        }

        return FALSE;
    }
    

    /**
     * Връща секундите, които оставят до края на прозореца за изпълнение
     */
    public static function getTimeLeft()
    {
        if(self::$timeDeadline) {

            return max(self::$timeDeadline - time(), 0);
        }

        return FALSE;
    }


    /**
     * Връща времето на последно стартиране на процес
     * 
     * @param $systemId
     * 
     * @return NULL|datetime
     */
    public static function getLastStartTime($systemId = NULL)
    {
        $query = self::getQuery();
        $query->limit(1);
        
        if ($systemId) {
            $query->where(array("#systemId = '[#1#]'", $systemId));
        } else {
            $query->where("#lastStart IS NOT NULL");
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
     * @return integer
     */
    public static function getPeriod($systemId)
    {
        $rec = self::getRecForSystemId($systemId);
        
        if ($rec === FALSE) return ;
        
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
    	
    	$data->query->orderBy('period'); 
        $data->query->orderBy('offset');
        $data->query->orderBy('systemId');
    }
    
    
    /**
     * 
     */
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
        $data->toolbar->addBtn('Логове на Cron', array(
                'log_System',
                'class' => $mvc->className
            ),
            'ef_icon = img/16/action_log.png');
    }
    
    
    /**
     * Този метод се задейства през интервал от 1 минута от OS
     */
    function act_Cron()
    {
        $whitelist = array(
            '127.0.0.1',
            '::1'
        );

        if(!in_array($_SERVER['REMOTE_ADDR'], $whitelist)){
            // requireRole('debug,admin');
        }
        
        // Ако в момента се извършва инсталация - да не се изпълняват процесите
        core_SystemLock::stopIfBlocked();

        header('Cache-Control: no-cache, no-store');
        
        // Отключваме всички процеси, които са в състояние заключено, а от последното
        // им стартиране е изминало повече време от Време-лимита-а
        $query = $this->getQuery();
        $query->where("#state = 'locked'");
        $now = dt::verbal2mysql();
        $query->where("DATE_ADD(#lastStart, INTERVAL #timeLimit SECOND) < '{$now}'");
        
        while ($rec = $query->fetch()) {
            $rec->state = 'free';
            $this->save($rec, 'state');
            $this->logWarning("Отключен процес, започнал в " . $rec->lastStart, $rec->id, 7);
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
        
        while ($rec = $query->fetch("#state != 'stopped'")) {
            
            // Кога е бил последно стартиран този процес?
            $lastStarting = $rec->lastStart;
            
            // В коя минута е трябвало за последен път да се стартира този процес?
            $lastSchedule = dt::timestamp2mysql((floor(($currentMinute - $rec->offset) / $rec->period) * $rec->period + $rec->offset) * 60 -  date('Z'));
            $now = dt::timestamp2mysql($currentMinute   * 60 -  date('Z'));

            // Колко минути остават до следващото стартиране
            $remainMinutes = floor(($currentMinute - $rec->offset) / $rec->period ) * $rec->period + $rec->period + $rec->offset - $currentMinute;
            
            if( (($currentMinute % $rec->period) == $rec->offset) || ($rec->period > 60 && $lastSchedule > $lastStarting && $rec->period/2 < $remainMinutes)) {
               
                $i++;
                fopen(toUrl(array(
                            'Act' => 'ProcessRun',
                            'id' => str::addHash($rec->id)
                        ), 'absolute-force'), 'r');
            
            }
        }

        $Os = cls::get('core_Os');
        $apacheProc = $Os->countApacheProc();
        $this->logInfo("Има ({$apacheProc}) стартирани процеси на Apache", NULL, 7);

        $this->logThenStop("Стартирани са {$i} процеса", NULL, 'info');
    }
    
    
    /**
     * Екшън за стартиране на единичен процес
     */
    function act_ProcessRun()
    {
        // Форсираме системния потребител
        core_Users::forceSystemUser();
        
        // Затваряме връзката създадена от httpTimer, ако извикването не е форсирано
        if(!$forced = Request::get('forced')) {
            header("Connection: close");
            ob_start();
            session_write_close();
            header("Content-Length: 0");
            
            if(function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } else {
                @ob_end_flush();
                flush();
            }
        } else {
            header ('Content-type: text/html; charset=utf-8');
        }
        
        // Декриптираме входния параметър. Чрез предаването на id-to на процеса, който
        // трябва да се стартира в защитен вид, ние се предпазваме от евентуална външна намеса
        $id = str::checkHash(Request::get('id'));
        
        if (!$id || !is_numeric($id)) {
            $cryptId = Request::get('id');
            $this->logThenStop("Некоректно id за криптиране: {$cryptId}", NULL, 'err');
        }
        
        log_Browsers::stopGenerating();
        
        // Вземаме информация за процеса
        $rec = $this->fetch($id);
        
        if (!$rec) {
            $this->logThenStop("Липсва запис", $id, 'err');
        }
        
        // Дали процесът не е заключен?
        if ($rec->state == 'locked' && !$forced) {
            $this->logThenStop("Процесът е заключен", $id, 'warning');
        }
        
        // Дали този процес не е стартиран след началото на текущата минута
        $nowMinute = date("Y-m-d H:i:00", time());
        if ($nowMinute <= $rec->lastStart && !$forced) {
            $this->logThenStop("Процесът е стартиран повторно по крон в една и съща минута", $id, 'notice');
        }
        
        // Заключваме процеса и му записваме текущото време за време на последното стартиране
        $rec->state = 'locked';
        $rec->lastStart = dt::verbal2mysql();
        $rec->lastDone = NULL;
        $this->save($rec, 'state,lastStart,lastDone');
        
        // Изчакваме преди началото на процеса, ако е зададено 
        if ($rec->delay > 0) {
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
                self::logInfo("Стартиран процес: " . $rec->action, $rec->id, 3);
                
                // Ако е зададено максимално време за изпълнение, 
                // задаваме го към PHP , като добавяме 5 секунди
                if ($rec->timeLimit) {
                    core_App::setTimeLimit($rec->timeLimit + 5);
                    self::$timeDeadline =time() + $rec->timeLimit;
                    self::$lastSystemId = $rec->systemId;
                }
                
                $startingMicroTime = $this->getMicrotime();
                $content = $handlerObject->$act();
                
                if (!Request::get('forced')) {
                    ob_clean();
                }
                
                // Ако извикания метод е генерирал резултат, то го добавяме
                // подходящо форматиран към лога
                if ($content) {
                    $content = "<p><i>$content</i></p>";
                    if (Request::get('forced')) {
                        echo $content;
                    }
                }
                
                $workingTime = round($this->getMicrotime() - $startingMicroTime, 2);
                
                self::logInfo("Процесът '{$rec->action}' е изпълнен успешно за {$workingTime} секунди", $rec->id, 3);
            } else {
                $this->unlockProcess($rec);
                $this->logThenStop("Няма такъв екшън в класа", $rec->id, 'err');
                echo(core_Debug::getLog());
                shutdown();
            }
        } else {
            $this->unlockProcess($rec);
            $this->logThenStop("Няма такъв клас", $rec->id, 'err');
            echo(core_Debug::getLog());
            shutdown();
        }
        
        // Отключваме процеса и му записваме текущото време за време на последното приключване
        $this->unlockProcess($rec);
        echo(core_Debug::getLog());
        shutdown();
    }
    
    
    /**
     * Записва в лога и спира
     */
    function logThenStop($msg, $id = NULL, $type = 'info')
    {
        $lifeDays = 7;
        if ($type == 'info') {
            $lifeDays = 3;
        }
        
        log_System::add(get_called_class(), $msg, $id, $type, $lifeDays);
        if(haveRole('admin,debug')) {
            echo(core_Debug::getLog());
        }
        shutdown();
    }
    
    
    /**
     * Отключва заключен процес
     */
    function unlockProcess($rec)
    {
        if (!$rec || !$rec->id) return ;
        $rec = $this->fetch($rec->id);
        
        if ($rec->state == 'locked') {
            $rec->state = 'free';
            $rec->lastDone = dt::verbal2mysql();
            $this->save($rec, 'state,lastDone');
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
        $row->lastStart = dt::mysql2verbal($rec->lastStart, "d.m.y  H:i:s");
        $row->lastDone = dt::mysql2verbal($rec->lastDone, "d.m.y  H:i:s");
        
        $row->description = $mvc->getVerbal($rec, 'description');
        $row->controller = $mvc->getVerbal($rec, 'controller');
        $row->action = $mvc->getVerbal($rec, 'action');
        $row->period = $mvc->getVerbal($rec, 'period');
        $row->offset = $mvc->getVerbal($rec, 'offset');
        $row->delay = $mvc->getVerbal($rec, 'delay');
        $row->timeLimit = $mvc->getVerbal($rec, 'timeLimit');
        $row->systemId = $mvc->getVerbal($rec, 'systemId');
        
        if($rec->lastStart) {
            $row->last = "<p>От: <b>{$row->lastStart}</b>";
        }
        
        if($rec->lastDone) {
            $row->last .= "<p>До: <b>{$row->lastDone}</b>";
        }
        
        $url = toUrl(array(
                'Act' => 'ProcessRun',
                'id' => str::addHash($rec->id),
                'forced' => 'yes'
            ), 'absolute');
        
        $row->systemId = ht::createLink("<b>{$row->systemId}</b>", $url, NULL, array('target' => 'cronjob'));
        
        $row->title = "<p>" . $row->systemId . "</p><p><i>{$row->description}</i></p>";
        
        $row->parameters = "<p style='color:green'><b>\${$row->controller}->{$row->action}</b><p>" .
        "Всеки <b>{$row->period}</b> + <b>{$row->offset}</b>";
        
        if($rec->delay) {
            $row->parameters .= ", Зак.: <b>{$row->delay}</b> s";
        }
        
        if($rec->timeLimit) {
            $row->parameters .= ", Лимит: <b>{$row->timeLimit}</b> s";
        }
        
        $now = dt::mysql2timestamp(dt::verbal2mysql());
        
        if($rec->state == 'locked' ||
            ($rec->lastStart && $rec->state == 'free' && (($now - $mvc->refreshRowsTime / 1000-2) < dt::mysql2timestamp($rec->lastStart)))) {
            
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
    static function getMicrotime()
    {
        list($usec, $sec) = explode(" ", microtime());
        
        return ((float) $usec + (float) $sec);
    }
    
    
    /**
     * Добавя запис, като гледа да няма запис със същото systemId
     * 
     * return boolean
     */
    static function addOnce($rec, $force = FALSE)
    {
        
        if(is_array($rec)) {
            $rec = (object) $rec;
        }
        
        expect($rec->systemId);
        expect($rec->description);
        expect($rec->controller);
        expect($rec->action);
        
        // Периода трябва да е по-голям от една минута
        expect($rec->period >= 1);
        
        // Офсета трябва да е по-голям от нула и да е по-малък от периода
        if(!isset($rec->offset)) {
            if($rec->period > 1) {
                $rec->offset = rand(0, $rec->period-1);
            } else {
                $rec->offset = 0;
            }
        }

        $rec->offset = max(0, $rec->offset);
        expect($rec->period > $rec->offset);
 
        // Търсим дали има съществуващ запис със същото id
        $exRec = self::fetch(array("#systemId = '[#1#]'", $rec->systemId ));

        
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
                if ( $systemDataChanged || $rec->period != $exRec->period ||
                      floor($rec->delay) != floor($exRec->delay) ||
                      $rec->timeLimit != $exRec->timeLimit
                    ) {
                    $mustSave = TRUE;
                    if($exRec->offset < $rec->period) {
                        $rec->offset = $exRec->offset;
                    }
                    $msg = "<li class=\"debug-update\">Обновено разписание за {$description}</li>";
                } else { // ако няма промени го пропускаме
                    $mustSave = FALSE;
                    $msg = "<li class=\"debug-info\">Съществуващо разписание за {$description}</li>";
                }
            } elseif ($systemDataChanged) {
                $mustSave = TRUE;
                unset($rec->period);
                unset($rec->offset);
                unset($rec->delay);
                unset($rec->timeLimit);
                $msg = "<li class=\"debug-notice\">Запазени потребителски настройки на разписание за {$description}</li>";
            }
        } else {
            $mustSave = TRUE;
            $msg = "<li class=\"debug-new\">Добавено разписание за {$description}</li>";
        }
 

        if($mustSave) {
            if(!self::save($rec)) {
                $msg = "<li class=\"debug-error\">Грешка при нагласяне на разписание за {$description}</li>";
            }
        }

        return $msg;
    }
    
    
    /**
     * Рутинен метод, премахва задачите, свързани с класове от посочения пакет
     */
    static function deinstallPack($pack)
    {
        $query = self::getQuery();
        $preffix = $pack . "_";
        $query->delete(array("#controller LIKE '[#1#]%'", $preffix));
    }
    

    /**
     * Премахва методите, за които не съществуват входни точки
     */
    static function cleanRecords()
    {
        $query = self::getQuery();

        while($rec = $query->fetch()) {
            if(cls::load($rec->controller, TRUE)) {
                $ctr = cls::get($rec->controller);
                if(method_exists($ctr, 'cron_' . $rec->action)) {
                    continue;
                }
            }
            
            $class = cls::getClassName($rec->controller);

            self::delete($rec->id);

            $res .= ($res ? ', ' : '') . "{$class}::{$rec->action}";
        }

        if($res) {

            return "<li style='color:green;'>Премахнати бяха липсващите входни точки за Cron: {$res}</li>";
        }
    }
    
    
    /**
     * Връща времето на следващото стартиране на крона
     * 
     * @param string $systemId
     * 
     * @return date|NULL|FALSE $nextStartTime
     */
    static function getNextStartTime($systemId)
    {
        // Вземаме записитеи за тази ситема
        $rec = core_Cron::fetch("#systemId = '{$systemId}'");
        
        // Ако е спрян или няма период
        if ($rec->state == 'stopped' || !$rec->period) {
            
            // Връщаме FALSE
            return FALSE;
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
        if ($nextStartTime < $now) return NULL;
        
        return $nextStartTime;
    }
    
    
    /**
     * 
     * 
     * @param integer $id
     * @param boolean $escape
     */
    public static function getTitleForId_($id, $escaped = TRUE)
    {
        if (!$id) return parent::getTitleById($id, $escaped);
        
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
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($rec && ($action == 'edit')) {
            if (!$rec->modifiedBy || $rec->modifiedBy == '-1') {
                $requiredRoles = 'no_one';
            }
        }
    }
}
