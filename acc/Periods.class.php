<?php


/**
 * Задаване начало и край на първия регистриран период
 */
defIfNot('BGERP_FIRST_PERIOD_START', acc_Periods::getFirstDayOfCurrentMonth());


/**
 *  @todo Чака за документация...
 */
defIfNot('BGERP_FIRST_PERIOD_END', acc_Periods::getLastDayOfCurrentMonth());


/**
 * Менаджира периодите в счетоводната система
 */
class acc_Periods extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Периоди";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, acc_WrapperSettings, plg_State, plg_Sorting';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = "id, title, start=Начало, end, state, reports=Справки, close=Приключване";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canWrite = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('end', 'date', 'caption=Край,mandatory');
        $this->FLD('state', 'enum(draft=Бъдещ,active=Активен,closed=Приключен)', 'caption=Състояние,input=none');
        $this->FNC('start', 'date', 'caption=Начало');
        $this->FNC('title', 'varchar');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_CalcStart($mvc, $rec)
    {
        $recPrev = $this->fetchPreviousPeriod($rec);
        
        if (isset($recPrev->end)){
            $rec->start = dt::addDays(1, $recPrev->end);
        } else {
            $rec->start = FALSE;
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_CalcTitle($mvc, $rec) {
        $title = array();
        
        if (!isset($rec->start)) {
            $mvc->on_CalcStart($mvc, $rec);
        }
        
        $format = Mode::is('narrow') ? EF_DATE_NARROW_FORMAT : EF_DATE_FORMAT;
        
        if ($rec->start) {
            $title[] = 'от ' . dt::mysql2verbal($rec->start, $format);
        }
        
        if ($rec->end) {
            $title[] = 'до ' . dt::mysql2verbal($rec->end, $format);
        }
        
        $rec->title = implode(' ', $title);
    }
    
    
    /**
     * Връща датата на първия ден от текущия месец
     *
     * @return string
     */
    function getFirstDayOfCurrentMonth()
    {
        $date = time();
        
        $month = date('m', $date);
        $year = date('Y', $date);
        $timestamp = strtotime("$year-$month-01");
        
        return date( 'Y-m-d', strtotime("$year-$month-01"));
    }
    
    
    /**
     * Връща датата на посления ден от текущия месец
     *
     * @return string
     */
    function getLastDayOfCurrentMonth()
    {
        $date = time();
        
        $month = date('m', $date);
        $year = date('Y', $date);
        $timestamp = strtotime("$year-$month-01");
        $numberOfDaysInCurrentMonth = date('t', $timestamp);
        
        return date( 'Y-m-d', strtotime("$year-$month-$numberOfDaysInCurrentMonth"));
    }
    
    
    /**
     * Връща датата на посления ден от предишния месец
     * Ако е зададен параметъра $date - връща последния ден (датата) от предишния месец спрямо $date.
     * Ако не е зададен параметъра $date - връща последния ден (датата) от предишния месец спрямо днешна дата.
     *
     * @param string $date
     * @return string
     */
    function getLastDayOfPrevMonth($date = NULL)
    {
        if ($date == NULL){
            $date = time();
        } else {
            $date = dt::mysql2timestamp($date);
        }
        
        $prevMonth=date('m', $date)-1;
        $year=date('Y', $date);
        
        if ($prevMonth == 0){
            $prevMonth = 12;
            $year--;
        }
        
        $timestamp = strtotime("$year-$prevMonth-01");
        $numberOfDaysInPrevMonth = date('t',$timestamp);
        
        return date( 'Y-m-d', strtotime("$year-$prevMonth-$numberOfDaysInPrevMonth"));
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
     * и дефинра период, който е за текущия месец и е със state='active'.
     *
     * @param acc_Periods $mvc
     * @param string $res
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
        if (!$this->fetch("1=1")){
            
            // Запис на период за инициализиране със state=closed
            $rec = new stdClass();
            $rec->end = dt::addDays(-1, BGERP_FIRST_PERIOD_START);
            $rec->state = "closed";
            
            $this->save($rec);
            
            $res .= "<li>Дефиниран е <b>затворен</b> период за инициализация на таблицата за периодите с край <span style=\"color:red;\">{$rec->end}</span>.</li>";
            
            // Запис на активен период за инициализиране със state=active
            $rec = new stdClass();
            $rec->end = BGERP_FIRST_PERIOD_END;
            $rec->state = "active";
            
            $this->save($rec);
            
            $res .= "<li>Дефиниран е <b>активен</b> период за инициализация на таблицата за периодите с край <span style=\"color: green;\">{$rec->end}</span>.</li>";
        }
    }
    
    
    /**
     * Добавя за записите поле start и бутони 'Справки' и 'Приключи'
     *  Поле 'start' - това поле не съществува в модела. Неговата стойност е end за предходния период + 1 ден.
     *  Поле 'reports' - в това поле ще има бутон за справки за периода.
     *
     * @param stdCLass $row
     * @param stdCLass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
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
     * Проверява дали края на редактирания период не попада в активния период
     *
     * @param acc_Periods $mvc
     * @param core_Form $form
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
        // проверка дали формата е submit-ната
        if (!$form->isSubmitted()){
            return;
        }
        
        // $form->rec са данните, които идват от потребитела
        
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
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
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
    
    
    /**
     * Сортира записите по поле end
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy('end', 'DESC');
    }
    
    
    /**
     * Затваря активен период и задава на следващия период да е активен
     * Ако няма следващ го създава
     *
     *  @return string $res
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
            $nextMonth=date('m', $date)+1;
            $year=date('Y', $date);
            
            if ($nextMonth == 13){
                $nextMonth = 1;
                $year++;
            }
            
            $timestamp = strtotime("$year-$nextMonth-01");
            $numberOfDaysInNextMonth = date('t',$timestamp);
            
            $recActiveNew = new stdClass();
            $recActiveNew->end = date( 'Y-m-d', strtotime("$year-$nextMonth-$numberOfDaysInNextMonth"));
            $recActiveNew->state = "active";
            $this->save($recActiveNew);
        } else {
            $recActiveNew->state = "active";
            $this->save($recActiveNew);
        }
        
        $res .= "|*<li>|Активен е период с край|* <span style=\"color:red;\">{$recActiveNew->end}</span>.</li>";
        
        core_Message::redirect($res, 'tpl_Info', NULL, array('acc_Periods'));
        
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