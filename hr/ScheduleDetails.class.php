<?php 

/**
 * Работни цикли - детайли
 *
 *
 * @category  bgerp
 * @package   hr
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class hr_ScheduleDetails extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Работни интервали';
    
    
    /**
     * @todo Чака за документация...
     */
    public $singleTitle = 'Работен интервал';
    
        
    /**
     * Страница от менюто
     */
    public $pageMenu = 'Персонал';
    

    /**
     * @todo Чака за документация...
     */
    public $masterKey = 'scheduleId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_SaveAndNew, plg_PrevAndNext, plg_Rejected, plg_Modified, hr_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'type,start,duration,break,repeat,until';
    
    
    /**
     * @todo Чака за документация...
     */
    public $rowToolsField = 'day';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,hrMaster';
    
    
    /**
     * Кой може да го изтрие?
     *
     */
    public $canDelete = 'ceo,hrMaster';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('scheduleId', 'key(mvc=hr_Schedules,select=name)', 'column=none,oldFieldName=cycleId');
        $this->FLD('type', 'enum(working=Работа,non-working=Почивка)', 'caption=Тип,mandatory,remember');

        $this->FLD('start', 'datetime', 'caption=Начало,mandatory');
        $this->FLD('duration', 'time(min=1,suggestions=00:30|01:00|01:30|02:00|02:30|03:00|03:30|04:00|04:30|05:00|05:30|6:00|6:30|7:00|7:30|8:00|8:30|9:00|9:30|10:00|10:30|11:00|11:30|12:00|24:00,allowEmpty)', 'caption=Продължителност,mandatory,remember');
        $this->FLD('break', 'time(min=1,suggestions=00|0:30|00:45|1:00|00,allowEmpty)', 'caption=в т.ч. Почивка,remember');
        $this->FLD('repeat', 'time(min=1,suggestions=1 ден|2 дни|3 дни|4 дни|5 дни|6 дни|7 дни|8 дни|9 дни|10 дни|2 седмици,allowEmpty)', 'caption=Повторение->Период,remember,autohide');
        $this->FLD('until', 'datetime', 'caption=Повторение->Край,remember,autohide');
    }
    
    
    
    /**
     * Сортиране
     */
    public function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('#start', 'DESC');
    }


        /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        
        if ($form->isSubmitted()) { 
            if(isset($form->rec->until) && $form->rec->until <= $form->rec->start) {
                $form->setError('until', "Краят на периода за повторение, трябва да е след началото на интервала!");
            }
            if(isset($form->rec->repeat) && $form->rec->repeat < $form->rec->duration) {
                $form->setError('repeat', "Повторението не може да е по-кратко от продължителността на самия интервал!");
            }
        }
    }
    
}
