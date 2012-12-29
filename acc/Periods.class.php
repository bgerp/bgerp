<?php



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
 * 
 * Текущ период = период в който поада днешната дата
 * Активен период = период в състояние 'active'. Може да има само един активен период
 * Неприключен период - период в състояние "draft" или "active"
 * Приключен период - период в състояние "closed"
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
    var $listFields = "id, title, start=Начало, end, vatRate, baseCurrencyId, lastEntry, reports=Справки, close=Приключване";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,acc';
    
    
    /**
     * Кой може да пише?
     */
    var $canEdit = 'admin,acc';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой може да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('end', 'date(format=d.m.Y)', 'caption=Край,mandatory');
        $this->FLD('state', 'enum(draft=Бъдещ,active=Активен,closed=Приключен)', 'caption=Състояние,input=none');
        $this->FNC('start', 'date(format=d.m.Y)', 'caption=Начало', 'dependFromFields=end');
        $this->FNC('title', 'varchar', 'caption=Заглавие,dependFromFields=start|end');
        $this->FLD('lastEntry', 'datetime', 'caption=Последен запис');
        $this->FLD('vatRate', 'percent', 'caption=Параметри->%ДДС,oldFieldName=vatPercent');
        $this->FLD('baseCurrencyId', 'key(mvc=currency_Currencies, select=code, allowEmpty)', 'caption=Параметри->Валута');
    }


    /**
     * Изчислява полето 'start' - начало на периода
     */
    static function on_CalcStart($mvc, $rec)
    {
        $rec->start = dt::mysql2verbal($rec->end, 'Y-m-01');
    }
    
    
    /**
     * Изчислява полето 'title' - заглавие на периода
     */
    static function on_CalcTitle($mvc, $rec)
    {
        $rec->title = dt::mysql2verbal($rec->end, "M-Y");
    }
    
    
    /**
     * Сортира записите по поле end
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
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
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if($mvc->haveRightFor('reports', $rec)) {
            $row->reports = Ht::createBtn('Справки', array('acc_Reports', 'List', $rec->id), NULL, NULL, 'ef_icon=img/16/report.png');
        }
        
        if($mvc->haveRightFor('close', $rec)) {
            if ($rec->state == 'active'){
                $row->close = Ht::createBtn('Приключване', array($this, 'Close', $rec->id), 'Наистина ли желаете да приключите периода?', NULL, 'ef_icon=img/16/lock.png');
            }
        }
    }
    
    
    /**
     * Връща запис за периода, към който се отнася датата. 
     * Ако не е зададена $date, връща текущия период
     *
     * @return stdClass $rec
     */
    static function fetchByDate($date = NULL)
    {
        $lastDayOfMonth = dt::getLastdayOfMonth($date);
         
        $rec = self::fetch("#end = '{$lastDayOfMonth}'");

        return $rec;
    }


    /**
     * Връща записа за периода предхождащ зададения.
     *
     * @param stdClass $rec запис за периода, чийто предшественик търсим.
     * @return stdClass запис за предходния период или NULL ако няма
     */
    static function fetchPreviousPeriod($rec)
    {
        $query = self::getQuery();
        $query->where("#end < '{$rec->end}'");
        $query->limit(1);
        $query->orderBy('end', 'DESC');
        $recPrev = $query->fetch();
        
        return $recPrev;
    }

    /**
     * Проверява датата в указаното поле на формата дали е в отворен период
     * и записва във формата съобщение за грешка или предупреждение
     * грешка или предупреждение няма, ако датата е от началото на активния, 
     * до края на насотящия период
     */
    static function checkDocumentDate($form, $field = 'date')
    {
        if(!$form->isSubmitted()) {
            return;
        }

        $date = $form->rec->{$field};
        
        if(!$date) {

            return;
        }

        $rec = self::forceActive();
 
        if($rec->start >= $date) {
            $form->setError($field, "Датата е преди активния счетоводен период| ($rec->title)");
            
            return;
        }
        
        $rec = self::fetchByDate($date);

        if(!$rec) {
            $form->setError($field, "Датата е в несъществуващ счетоводен период");
            
            return;
        }

        if($date > dt::getLastDayOfMonth()) {
            $form->setWarning($field, "Датата е в бъдещ счетоводен период");
            
            return;
        }

        return TRUE;
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
                
        $activeRec = $mvc->forceActive();
        
        if ($form->rec->end <= $activeRec->start){
            $form->setError('end', 'Не може да е преди началото на активния период ');
        }
        
        // Ако редактираме период, който не е активен проверяваме 
        // дали неговия край не попада в активния период
        if ($activeRec->id != $form->rec->id){
            if ($form->rec->end <= $activeRec->end){
                $form->setError('end', 'Не може да е преди края на активния период ');
            }
        }
    }


    /**
     * Връща посочения период или го създава, като създава и периодите преди него
     */
    function forcePeriod($date)
    {
        $end = dt::getLastDayOfMonth($date);

        $rec = self::fetch("#end = '{$end}'");

        if($rec) return $rec;

        // Определяме, кога е последният ден на началния период
        $query = self::getQuery();
        $query->orderBy('#end', 'ASC');
        $query->limit(1);
        $firstRec = $query->fetch();
        if(!$firstRec) {
            $firstRec = new stdClass();
            $firstRec->end = ACC_FIRST_PERIOD_START ? dt::getLastDayOfMonth(ACC_FIRST_PERIOD_START) : dt::getLastDayOfMonth(NULL, -1);
        }

        // Ако датата е преди началния период, връщаме началния
        if($end < $firstRec->end) {

            return self::forcePeriod($firstRec->end);
        }
        
        // Конфигурационни данни на пакета 'acc'
        $conf = core_Packs::getConfig('acc');
        
        // Връзка към сингълтон инстанса
        $me = cls::get('acc_Periods');

        // Ако датата е точно началния период, създаваме го, ало липсва и го връщаме
        if($end == $firstRec->end) {
            if(!$firstRec->id) {
                $firstRec->vatRate = $conf->ACC_DEFAULT_VAT_RATE;
                $firstRec->baseCurrencyId =  currency_Currencies::getIdByCode();
                self::save($firstRec);
                $firstRec = self::fetch($firstRec->id); // За титлата
                $me->actLog .= "<li style='color:green;'>Създаден е начален период $firstRec->title</li>";
            }

            return $firsRec;
        }

        // Ако периода е след началния, то:
        
        // 1. вземаме предишния период
        $prevEnd = dt::getLastDayOfMonth($date, -1);
        $prevRec = self::forcePeriod($prevEnd);
        
        // 2. създаваме търсения период на база на началния
        $rec = new stdCLass();
        $rec->end = $end;

        // Периодите се създават в състояние драфт
        $rec->state = 'draft';
        
        // Вземаме последните
        setIfnot($rec->vatRate, $prevRec->vatRate, ACC_DEFAULT_VAT_RATE);

        if($prevRec->baseCurrencyId) {
            $rec->baseCurrencyId = $prevRec->baseCurrencyId;
        } else {
            $rec->baseCurrencyId =  currency_Currencies::getIdByCode();
        }

        self::save($rec);
        $rec = self::fetch($rec->id);

        $me->actLog .= "<li style='color:green;'>Създаден е период $rec->title</li>";

        return $rec;
    }


    /**
     * Връща активния период. Създава такъв, ако няма
     */
    static function forceActive()
    {
        if(!($rec = self::fetch("#state = 'active'"))) {

            $me = cls::get('acc_Periods');

            $query = self::getQuery();
            $query->where("#state != 'closed'");
            $query->orderBy('#end', 'ASC');
            $query->limit(1);
            
            $rec = $query->fetch();

            $rec->state = 'active';

            self::save($rec, 'state');
            
            $me->actLog .= "<li style='color:green;'>Зададен е активен период $rec->end</li>";
        }

        return $rec;
    }

    
    /**
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public function on_AfterPrepareEditForm(core_Mvc $mvc, $data)
    {
        if ($data->form->rec->id) {
            $data->form->setReadOnly('end');
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
    static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if(!$rec) {
            return;
        }

        // Забраняваме всички модификации за всички минали периоди
        if ($action == 'edit'){
            if($rec->state == 'closed') {
                $requiredRoles = "no_one";
            }
        }
        
        // Последния ден на текущия период
        $curPerEnd = dt::getLastDayOfMonth(dt::verbal2mysql());
        
        // Забраняваме изтриването за текущия период
        if($action == 'delete') {
            if ($rec->end <= $curPerEnd){
                $requiredRoles = "no_one";
            }
        }
        
        // Период може да се затваря само ако е изтекъл
        if($action == 'close') {
            if($rec->end >= $curPerEnd || $rec->state != 'active') {
                 $requiredRoles = "no_one";
            }
        }
    }

  
    
    /**
     * Затваря активен период и задава на следващия период да е активен
     * Ако няма следващ го създава
     *
     * @return string $res
     */
    function act_Close()
    {
        $this->requireRightFor('close');

        // Затваряме период
        $id = Request::get('id');
        
        $rec = new stdClass();
        
        $rec = $this->fetch("#id = '{$id}'");
        
        // Очакваме, че затваряме активен период
        $this->requireRightFor('close', $rec);
        
        // Новото състояние е 'Затворен';
        $rec->state = "closed";
        
        $this->save($rec);
        
        $res = "Затворен е период |*<span style=\"color:red;\">{$rec->title}</span>";
        
        // Отваря следващия период. Създава го, ако не съществува
        $nextRec = $this->forcePeriod(dt::addDays(1, $rec->end));
        
        $activeRec = $this->forceActive();
        
        $res .= "<br>Активен е период |* <span style=\"color:red;\">{$activeRec->title}</span>";
        
        $res = new Redirect(array('acc_Periods'), tr($res));
        
        return $res;
    }


    /**
     * Инициализира начални счетоводни периоди при инсталиране
     * Ако няма дефинирани периоди дефинира период, чийто край е последния ден от предходния 
     * месец със state='closed' и период, който е за текущия месец и е със state='active'
     */
    function loadSetupData()
    {
        $conf = core_Packs::getConfig('acc');

        $firstPeriodStart = $conf->ACC_FIRST_PERIOD_START ? $conf->ACC_FIRST_PERIOD_START : dt::verbal2mysql();

        $this->forcePeriod($firstPeriodStart);

        $this->forceActive();

        $Cron = cls::get('core_Cron');
        
        $rec = new stdClass();
        $rec->systemId = "Create Periods";
        $rec->description = "Създава нови счетоводни периоди";
        $rec->controller = "acc_Periods";
        $rec->action = "createFuturePeriods";
        $rec->period = 24*60*60;
        $rec->offset = 3777;
        
        $Cron->addOnce($rec);


        return $this->actLog;
    }

    // Създава бъдещи (3 месеца напред) счетоводни периоди
    function cron_CreateFuturePeriods()
    {
        $this->forcePeriod(dt::getLastDayOfMonth(NULL, 3));
        $this->forceActive();
    }
   
 
}