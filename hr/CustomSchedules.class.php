<?php 


/**
 * Персонални работни цикли
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Gabriela Petrova <gab4eto@gmial.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_CustomSchedules extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Персонални работни графици";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Персонален работен график";
    
    
    /**
     * Страница от менюто
     */
    var $pageMenu = "Персонал";
    
    
    /**
     * @todo Чака за документация...
     */
    //var $details = 'hr_CycleDetails';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, hr_Wrapper,  plg_Printing';
    
    
    /**
     * Единична икона
     */
    //var $singleIcon = 'img/16/timespan.png';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    //var $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,hr,trz';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo,hr,trz';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,hr,trz';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'ceo,hr,trz';
    
    /**
     * @todo Чака за документация...
     */
    //var $singleFields = 'id,name,cycleDuration,info';
    
    
    /**
     * Шаблон за единичния изглед
     */
    //var $singleLayoutFile = 'hr/tpl/SingleLayoutWorkingCycles.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('date', 'date', 'caption=Дата, width=100%,mandatory');
        $this->FLD('typeDate', 'enum(working=работен,
                                     nonworking=почивен,
                                     leave=отпуска,
                                     traveling=командировка,
                                     sicDay=болничен,)', 'caption=Вид,width=100%,allowEmpty');
        $this->FLD('departmenId', 'key(mvc=hr_Departments, select=name,allowEmpty)', 'caption=Структура, width=50px');
        $this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees,allowEmpty)', 'caption=Служител,width=100%');
        $this->FLD('start', 'time(suggestions=00:00|01:00|02:00|03:00|04:00|05:00|06:00|07:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|19:00|20:00|21:00|22:00|23:00,format=H:M,allowEmpty)', 'caption=Работен ден->Начало');
        $this->FLD('duration', 'time(suggestions=00|6:00|6:30|7:00|7:30|8:00|8:30|9:00|9:30|10:00|10:30|11:00|11:30|12:00,allowEmpty)', 'caption=Работен ден->Времетраене');
        $this->FLD('break',    'time(suggestions=00|0:30|00:45|1:00|00,allowEmpty)', 'caption=Работен ден->в т.ч. Почивка');   
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        if ($form->isSubmitted()){
            if (isset($rec->departmenId) && isset($rec->personId)) {
                $form->setError('departmenId', "Не  може да изберете едновременно структура и служител");
            }
        }
    }

}
