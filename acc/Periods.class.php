<?php

/**
 * Стойност по подразбиране на актуалния ДДС (между 0 и 1)
 * Използва се по време на инициализацията на системата, при създаването на първия период
 */
defIfNot('ACC_DEFAULT_VAT_RATE', 0.20);

/**
 * Стойност по подразбиране на базовата валута.
 * Използва се по време на инициализацията на системата, при създаването на първия период
 */
defIfNot('ACC_DEFAULT_CURRENCY_CODE', 'BGN');


/**
 * Мениджира периодите в счетоводната система
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Periods extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Счетоводни периоди";
    

    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Период';


    /**
     * Активен таб на менюто
     */
    var $menuPage = 'Счетоводство:Настройки';
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, acc_WrapperSettings, plg_State, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, title, start=Начало, end, vatPercent, baseCurrencyId, state, reports=Справки, close=Приключване";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,acc';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'admin,acc';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('end', 'date', 'caption=Край,mandatory');
        $this->FLD('state', 'enum(draft=Бъдещ,active=Активен,closed=Приключен)', 'caption=Състояние,input=none');
        $this->FNC('start', 'date', 'caption=Начало', 'dependFromFields=end');
        $this->FNC('title', 'varchar', 'caption=Заглавие,dependFromFields=start|end');
        $this->FLD('params', 'object', 'input=none');
        $this->FNC('vatPercent', 'percent', 'caption=Параметри->ДДС, input, dependFromFields=paramsBlob');
        $this->FNC('baseCurrencyId', 'key(mvc=currency_Currencies, select=code, allowEmpty)', 'caption=Параметри->Валута, input, dependFromFields=paramsBlob');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_CalcStart($mvc, $rec)
    {
        $recPrev = $mvc->fetchPreviousPeriod($rec);
        
        if (isset($recPrev->end)){
            $rec->start = dt::addDays(1, $recPrev->end);
        } else {
            $rec->start = FALSE;
        }
    }
    
    
    /**
     * Изчислява полето 'title'
     */
    static function on_CalcTitle($mvc, $rec) {
    	
    	$conf = core_Packs::getConfig('core');
    	
        $title = array();
        
        if (!isset($rec->start)) {
            $mvc->on_CalcStart($mvc, $rec);
        }
        
        $format = Mode::is('screenMode', 'narrow') ? $conf->EF_DATE_NARROW_FORMAT : $conf->EF_DATE_FORMAT;
        
        if ($rec->start) {
            $title[] = 'от ' . dt::mysql2verbal($rec->start, $format);
        }
        
        if ($rec->end) {
            $title[] = 'до ' . dt::mysql2verbal($rec->end, $format);
        }
        
        $rec->title = implode(' ', $title);
    }
    
    
    public static function on_CalcVatPercent(core_Mvc $mvc, $rec)
    {
        $rec->vatPercent = $rec->params->vatPercent;
    }
    
    
    public static function on_CalcBaseCurrencyId(core_Mvc $mvc, $rec)
    {
        $rec->baseCurrencyId = $rec->params->baseCurrencyId;
    }
    
    
    /**
     * Връща датата на първия ден от текущия месец
     *
     * @return string
     */
    static function getFirstDayOfCurrentMonth()
    {
        $date  = time();
        $month = date('m', $date);
        $year  = date('Y', $date);
        
        $firstDayStamp = mktime(0, 0, 0, $month, 1, $year);
        
        return date('Y-m-d', $firstDayStamp);
    }
    
    
    /**
     * Връща датата на последния ден от текущия месец
     *
     * @return string
     */
    static function getLastDayOfCurrentMonth()
    {
        $now   = time();
        $month = date('m', $now);
        $year  = date('Y', $now);
        
        $lastDayStamp = mktime(0, 0, 0, $month+1, 0, $year);
        
        return date('Y-m-d', $lastDayStamp);
    }
    
    
 
    
    /**
     * Разпечатва резултата от метода getLastDayOfPrevMonth()
     *
     * @return core_Et $tpl
     */
    function act_PrintNumberOfDaysInPrevMonth()
    {
        $tpl = $this->getLastDayOfPrevMonth();
        $tpl = $this->renderWrapping($tpl);
        
        return $tpl;
    }
    
    
    /**
     * Инициализира default периоди при инсталиране
     * Ако няма дефинирани периоди дефинира период, чийто край е последния ден от предходния месец със state='closed'
     * ипериод, който е за текущия месец и е със state='active'.
     *
     * @param acc_Periods $mvc
     * @param string $res
     */
    function loadSetupData()
    {
        if (!$this->fetch("1=1")){
            
            $conf = core_Packs::getConfig('acc');
            
            $startDay = $conf->ACC_FIRST_PERIOD_START;
            if(!$startDay) {
                $startDay =  date("Y-m-1");
            }

            // Запис на един минал, затворен период
            $rec = new stdClass();
            $rec->end = dt::addDays(-1, $startDay);
            $rec->state = "closed";
            $rec->params = new stdClass();
            $rec->params->vatPercent     = ACC_DEFAULT_VAT_RATE;
            $rec->params->baseCurrencyId =
                currency_Currencies::fetchField("#code = '" . ACC_DEFAULT_CURRENCY_CODE . "'", id);
            $this->save($rec);
            $res .= "<li style='color:green'>Създаден е <b>затворен</b> счетоводен период с край <b>" .
                dt::mysql2verbal($rec->end, 'd/m/Y') . "</b>.</li>";
            
            // Запис на активен период за инициализиране със state=active
            $rec = new stdClass();
            $lastDay = date("Y-m-t", strtotime($startDay));
            $rec->end = $lastDay;
            $rec->state = "active";
            $rec->params = new stdClass();
            $rec->params->vatPercent     = ACC_DEFAULT_VAT_RATE;
            $rec->params->baseCurrencyId =
                currency_Currencies::fetchField("#code = '" . ACC_DEFAULT_CURRENCY_CODE . "'", id);
            $this->save($rec);
            
            $res .= "<li style='color:green'>Създаден е <b>активен</b> счетоводен период с начало с начало <b>" .
                dt::mysql2verbal($startDay, 'd/m/Y') . "</b> и край <b>" . dt::mysql2verbal($rec->end, 'd/m/Y') . "</b>.</li>";
        }

        return $res;
    }
    
    
    /**
     * Добавя за записите поле start и бутони 'Справки' и 'Приключи'
     * Поле 'start' - това поле не съществува в модела. Неговата стойност е end за предходния период + 1 ден.
     * Поле 'reports' - в това поле ще има бутон за справки за периода.
     *
     * @param stdCLass $row
     * @param stdCLass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        //        $rec = $mvc->getPeriod($rec->id);
        
        //        $dateType = cls::get('type_Date');
        //
        //        $row->start = $dateType->toVerbal($rec->start);
        
        $row->reports = Ht::createBtn('Справки', array('acc_Reports', 'List', $rec->id));
        
        if ($rec->state == 'active'){
            $row->close = Ht::createBtn('Приключи', array($this, 'ClosePeriod', $rec->id),
                'Наистина ли желаете да приключите периода?');
        }
    }
    
    
    /**
     * Връща запис за посочения период. Ако не е зададено $id, връща активния период
     *
     * @return stdClass $rec
     */
    function getPeriod($id = NULL)
    {
        $rec = new stdClass();
        
        if(!$id) {
            $rec = $this->fetch("#state='active'");
        } else {
            $rec = $this->fetch($id);
        }
        
        if (!$rec) return FALSE;
        
        $recPrev = $this->fetchPreviousPeriod($rec);
        
        if (isset($recPrev->end)){
            $rec->start = dt::addDays(1, $recPrev->end);
        }
        
        return $rec;
    }
    
    
    /**
     * Връща записа за периода предхождащ зададения.
     *
     * @param stdClass $rec запис за периода, чийто предшественик търсим.
     * @return stdClass запис за предходния период или NULL ако няма
     */
    function fetchPreviousPeriod($rec)
    {
        $query = $this->getQuery();
        $query->where("#end < '{$rec->end}'");
        $query->limit(1);
        $query->orderBy('end', 'DESC');
        $recPrev = $query->fetch();
        
        return $recPrev;
    }
    

    /**
     * Данните на най-скоро създадения период
     * 
     * @return stdClass запис на модела acc_Periods
     */
    public static function fetchLastActivePeriod()
    {
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->limit(1);
        $query->orderBy('createdOn', 'DESC');
        
        $rec = $query->fetch();
        
        return $rec;
    }
    
    
    /**
     * Проверява дали края на редактирания период не попада в активния период
     *
     * @param acc_Periods $mvc
     * @param core_Form $form
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
        // проверка дали формата е submit-ната
        if (!$form->isSubmitted()){
            return;
        }
        
        // $form->rec са данните, които идват от потребителя
        
        $activeRec = $mvc->getPeriod();
        
        if ($form->rec->end <= $activeRec->start){
            $form->setError('end', 'Не може да е преди началото на активния период ');
        }
        
        // Ако редактираме период, който не е активен
        // проверяваме дали неговия край не попада в активния период
        if ($activeRec->id != $form->rec->id){
            if ($form->rec->end <= $activeRec->end){
                $form->setError('end', 'Не може да е преди края на активния период ');
            }
        }
    }
    
    
    /**
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public function on_AfterPrepareEditForm(core_Mvc $mvc, $data)
    {
        if (!$data->form->rec->id) {
            // При създаване на нов запис, зарежда форма с разумни ст-сти по подразбиране
            $mvc::populateCreateDefaults($data->form);
        }
    }
    
    
    /**
     * Зарежда форма със стойности по подразбиране
     * 
     * @param core_Form $form
     */
    public static function populateCreateDefaults(core_Form $form)
    {
        $lastPeriodRec = static::fetchLastActivePeriod();
        
        if (!$lastPeriodRec) {
            return;
        }
        
        $form->rec->vatPercent     = $lastPeriodRec->vatPercent;
        $form->rec->baseCurrencyId = $lastPeriodRec->baseCurrencyId;
    }
    
    
    /**
     * Премахва възможността да се редактират периоди със state='closed'
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     * Ако state = 'closed' премахва възможността да се редактира записа.
     *
     * @param acc_Periods $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако не става дума за редактиране или изтриване, нищо не променяме
        if ($action != 'edit' && $action != 'delete'){
            return;
        }
        
        // Забраняваме изтриването и редактирането за всички минали периоди
        if ($rec->state == 'closed'){
            if(isDebug()) {
                $requiredRoles = "admin";
            } else {
                $requiredRoles = "no_one";
            }
        }
        
        // Забраняваме изтриването за текущия период
        if ($rec->state == 'active' && $action == 'delete'){
            if(isDebug()) {
                $requiredRoles = "admin";
            } else {
                $requiredRoles = "no_one";
            }
        }
    }

    
    static function on_BeforeSave($mvc, $id, $rec)
    {
        $paramNames = array('vatPercent', 'baseCurrencyId');
        $params     = array();
        
        foreach ($paramNames as $n) {
            if (property_exists($rec, $n)) {
                $params[$n] = $rec->{$n};
            }
        }
        
        if (!empty($params)) {
            $rec->params = (object)$params;
        }
    }
    
    /**
     * Сортира записите по поле end
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('end', 'DESC');
    }
    
    
    /**
     * Затваря активен период и задава на следващия период да е активен
     * Ако няма следващ го създава
     *
     * @return string $res
     */
    function act_ClosePeriod()
    {
        // Затваряме период
        $id = Request::get('id');
        
        $rec = new stdClass();
        
        $rec = $this->fetch("#id = '{$id}'");
        
        // Очакваме, че затваряме активен период
        expect($rec->state == 'active');
        
        // Новото състояние е 'Затворен';
        $rec->state = "closed";
        
        $this->save($rec);
        
        $res = "|*<li>|Затворен е период с край |*<span style=\"color:red;\">{$rec->end}</span>.</li>";
        
        // Сменяме за следващия период state = active
        $query = $this->getQuery();
        $query->where("#end > '{$rec->end}'");
        $query->limit(1);
        $query->orderBy('end', 'ASC');
        
        $recActiveNew = $query->fetch();
        
        if(!$recActiveNew){
            // Създаваме нов период и го правим активен
            $date = dt::mysql2timestamp($rec->end);
            $nextMonth = date('m', $date) + 1;
            $year = date('Y', $date);
            
            if ($nextMonth == 13){
                $nextMonth = 1;
                $year++;
            }
            
            $timestamp = strtotime("$year-$nextMonth-01");
            $numberOfDaysInNextMonth = date('t', $timestamp);
            
            $recActiveNew = new stdClass();
            $recActiveNew->end = date('Y-m-d', strtotime("$year-$nextMonth-$numberOfDaysInNextMonth"));
            $recActiveNew->state = "active";
            $this->save($recActiveNew);
        } else {
            $recActiveNew->state = "active";
            $this->save($recActiveNew);
        }
        
        $res .= "|*<li>|Активен е период с край|* <span style=\"color:red;\">{$recActiveNew->end}</span>.</li>";
        
        core_Message::redirect($res, 'page_Info', NULL, array('acc_Periods'));
        
        return $res;
    }
    
    
    /**
     * Връща записа на периода, в който попада зададената дата
     *
     * @param string $date
     * @return stdClass запис на период; NULL ако няма такъв период
     */
    public static function fetchByDate($date)
    {
        $query = self::getQuery();
        $query->where("#end >= '{$date}'");
        $query->orderBy('#end', 'ASC');
        $query->limit(1);
        
        $rec = $query->fetch();
        
        return $rec;
    }
}