<?php



/**
 * Меню
 *
 *
 * @category  bgerp
 * @package   catering
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class catering_Menu extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Дневни менюта';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Дневно меню';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, catering_Wrapper, plg_Sorting,
                     plg_Printing, Companies=catering_Companies, CrmCompanies=crm_Companies';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,date, repeatDay, companyName';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'catering_MenuDetails';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, catering';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, catering';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, catering';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, catering';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'catering/tpl/SingleLayoutMenu.shtml';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        // Prepare day input
        $string = new type_Varchar();
        $string->suggestions = arr::make(tr('Всеки понеделник|*,
                                             |Всеки вторник|*, 
                                             |Всяка сряда|*,
                                             |Всеки четвъртък|*,
                                             |Всеки петък|*,
                                             |Всяка събота|*,
                                             |Всеки ден'), true);
        $string->load('calendarpicker_Plugin');
        $this->FNC('day', $string, 'caption=Ден, input');
        
        // END Prepare day input
        
        $this->FLD('date', 'date', 'caption=За дата, allowEmpty=true, input=none');
        $this->FLD('repeatDay', 'enum(0.OnlyOnThisDate=За дата,
                                      1.Mon=Всеки понеделник, 
                                      2.Tue=Всеки вторник, 
                                      3.Wed=Всяка сряда, 
                                      4.Thu=Всеки четвъртък, 
                                      5.Fri=Всеки петък,
                                      6.Sat=Всяка събота,
                                      99.AllDays=Всеки ден)', 'caption=Повторение, input=none');
        $this->FLD('companyId', 'key(mvc=catering_Companies, select=companyId)', 'caption=Фирма');
        $this->FNC('companyName', 'varchar(255)', 'caption=Фирма');
        
        $this->setDbUnique('date, repeatDay, companyId');
    }
    
    
    /**
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    public static function on_AfterRenderListTable($mvc, &$res, $data)
    {
        //$res->append("<div></div>");
    }
    
    
    /**
     * Филтър за деня на менюто
     * Ако е избран конкретен ден, то показваме за всяка фирма:
     * 1. Ястия за всеки ден
     * 2. Ястия за всеки ден от седмицата, който съвпада с избрания ден (напр. за всеки вторник)
     * 3. Ястия, които са само за текущата дата (напр. само за 2011-06-11)
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Check wether the table has records
        $hasRecords = $mvc->fetchField('#id != 0', 'id');
        
        if ($hasRecords) {
            $data->listFilter->title = 'Изберете дата';
            $data->listFilter->view = 'vertical';
            $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
            $data->listFilter->FNC('dateFilter', 'date', 'caption=Дата');
            $data->listFilter->showFields = 'dateFilter';
            
            // Активиране на филтъра
            $data->filter = $data->listFilter->input();
            
            // Ако филтъра е задействан
            if ($data->filter->dateFilter) {
                $selectedWeekDay = $mvc->getRepeatDay($data->filter->dateFilter);
                
                // Избиране всички записи, които са за:
                // 1. Всички дни
                // 2. За всеки ден от седмицата, които съвпада с избрания (напр. за всеки вторник)
                // 3. За конкретната дата, която съвпада с избраната (напр. за 2011-06-11)
                $data->query->where("#date = '{$data->filter->dateFilter}'
                                     OR (#date IS NULL AND #repeatDay ='{$selectedWeekDay}'
                                     OR (#date IS NULL AND #repeatDay = '99.AllDays'))");
                
                // Сортираме по фирма, по 'repeatDay'
                $data->query->orderBy('date', 'DESC');
                $data->query->orderBy('repeatDay', 'ASC');
                $data->query->orderBy('companyId', 'ASC');
            }
            
            // END Ако филтъра е задействан
            
            // Ако не е задействан филтъра
            else {
                $data->query->where('1=1');
                $data->query->orderBy('date', 'DESC');
                $data->query->orderBy('repeatDay', 'ASC');
                $data->query->orderBy('companyId', 'ASC');
            }
            
            // END Ако не е задействан филтъра
        }
    }
    
    
    /**
     * Връща 'repeatDay' за подадена входна дата
     *
     * @param string $date
     * @return string $selectedWeekDay
     */
    public function getRepeatDay($date)
    {
        $date = substr($date, 0, 10);
        
        list($year, $month, $day) = explode('-', $date);
        $timestamp = mktime(0, 0, 0, $month, $day, $year);
        $selectedWeekDay = date('D', $timestamp);
        
        // Променяме $selectedWeekDay да съответства на полето 'repeatDay' от модела
        switch ($selectedWeekDay) {
            case 'Mon':
                $selectedWeekDay = '1.' . $selectedWeekDay;
                break;
            case 'Tue':
                $selectedWeekDay = '2.' . $selectedWeekDay;
                break;
            case 'Wed':
                $selectedWeekDay = '3.' . $selectedWeekDay;
                break;
            case 'Thu':
                $selectedWeekDay = '4.' . $selectedWeekDay;
                break;
            case 'Fri':
                $selectedWeekDay = '5.' . $selectedWeekDay;
                break;
            case 'Sat':
                $selectedWeekDay = '6.' . $selectedWeekDay;
                break;
        }
        
        // END Променяме $selectedWeekDay да съответства на полето 'repeatDay' от модела
        
        return $selectedWeekDay;
    }
    
    
    /**
     * Prepare 'companyName'
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // 'companyName'
        $companyId = $mvc->Companies->fetchField("#id = '{$rec->companyId}'", 'companyId');
        $companyName = $mvc->CrmCompanies->fetchField("#id = '{$companyId}'", 'name');
        $row->companyName = $companyName;
    }
    
    
    /**
     * По подразбиране нов запис е със state 'active'
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        if ($data->form->rec->id) {
            // Ако редактираме запис
            if ($data->form->rec->date === null) {
                $data->form->setDefault('day', $mvc->getVerbal($data->form->rec, 'repeatDay'));
            } else {
                $data->form->setDefault('day', $mvc->getVerbal($data->form->rec, 'date'));
            }
            
            // END Ако редактираме запис
        } else {
            // Ако добавяме запис
            $data->form->setDefault('state', 'active');
        }
    }
    
    
    /**
     * При нов запис, ако повторението не е само за конкретна дата, то полето 'date' е NULL
     * Проверка, ако повторението е за конкретна дата, дали датата е въведена
     *
     * @param core_Mvc $mvc
     * @param int      $id
     * @param stdClass $rec
     */
    public static function on_BeforeSave($mvc, &$id, $rec)
    {
        if (!$rec->day) {
            redirect(array('catering_Menu', 'edit'), false, '|Няма въведени данни в полето "Ден"');
        }
        
        switch ($rec->day) {
            case 'Всеки понеделник':
                $rec->repeatDay = '1.Mon';
                $rec->date = null;
                break;
            case 'Всеки вторник':
                $rec->repeatDay = '2.Tue';
                $rec->date = null;
                break;
            case 'Всяка сряда':
                $rec->repeatDay = '3.Wed';
                $rec->date = null;
                break;
            case 'Всеки четвъртък':
                $rec->repeatDay = '4.Thu';
                $rec->date = null;
                break;
            case 'Всеки петък':
                $rec->repeatDay = '5.Fri';
                $rec->date = null;
                break;
            case 'Всяка събота':
                $rec->repeatDay = '6.Sat';
                $rec->date = null;
                break;
            case 'Всеки ден':
                $rec->repeatDay = '99.AllDays';
                $rec->date = null;
                break;
            default:
            $rec->day = substr($rec->day, 0, 10);
            $regexCond = '/^[0-3]{1}[0-9]{1}[-]{1}(01|02|03|04|05|06|07|08|09|10|11|12){1}[-]{1}(20){1}[0-9]{2}/';
            
            expect(preg_match($regexCond, $rec->day));

            $rec->repeatDay = '0.OnlyOnThisDate';
            $rec->date = dt::verbal2mysql($rec->day);
        }
    }
}
