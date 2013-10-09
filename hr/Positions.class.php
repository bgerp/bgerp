<?php 


/**
 * Позиции
 * Детайли, които определят в един отдел, какви длъжности 
 * и на какви условия могат да бъдат назначавани
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_Positions extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = "Позиции";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Позиция";
    
        
    /**
     * Плъгини за зареждане
     */
   
    var $loadList = 'plg_RowTools, hr_Wrapper, plg_Printing, plg_Created';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,hr';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,hr';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,hr';
	
	    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'ceo,hr';
    
    var $masterKey = 'departmentId';
    
    var $currentTab = 'Структура';

    var $rowToolsField = '✍';
    
    var $listFields = '✍,professionId,employmentTotal,employmentOccupied';

    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FNC('name', 'varchar', 'caption=Наименование');

        // Към кое звено на организацията е тази позиция
        $this->FLD('departmentId', 'key(mvc=hr_Departments,select=name)', 'caption=Отдел, column=none, mandatory');
        
        // Каква е професията за тази длъжност
        $this->FLD('professionId', 'key(mvc=hr_Professions,select=name)', 'caption=Професия, mandatory');
        
        // Щат
        $this->FLD('employmentTotal', 'double','caption=Служители->Щат');
        $this->FLD('employmentOccupied', 'double','caption=Служители->Запълване');

        // Възнаграждения
        $this->FLD('salaryBase', 'double(decimals=2)','caption=Възнаграждение->Основно');
        $this->FLD('forYearsOfService', 'percent(decimals=2)','caption=Възнаграждение->За стаж');
        $this->FLD('compensations', 'double(decimals=2)','caption=Възнаграждение->За вредности');
        $this->FLD('frequensity', 'enum(mountly=Ежемесечно, weekly=Ежеседмично, daily=Ежедневно)','caption=Възнаграждение->Периодичност');
        $this->FLD('downpayment', 'enum(yes=Да,no=Не)','caption=Възнаграждение->Аванс');

        // Срокове
        $this->FLD('probation', 'int', "caption=Срокове->Изпитателен срок,unit=месеца,width=6em");
        $this->FLD('annualLeave', 'int', "caption=Срокове->Годишен отпуск,unit=дни,width=6em");
        $this->FLD('notice', 'int', "caption=Срокове->Предизвестие,unit=дни,width=6em");

        // Други условия
        $this->FLD('descriptions', 'richtext(bucket=humanResources)', 'caption=Условия->Допълнителни');

    }
    

    function on_CalcName($mvc, $rec)
    {
        if($rec->departmentId) {
            $dRec = hr_Departments::fetch($rec->departmentId);
            hr_Departments::expandRec($dRec);
        }

        if($rec->professionId) {
            $jRec = hr_Professions::fetch($rec->professionId);
        }

        $rec->name = $dRec->name . ' - ' . $jRec->name;

    }

    
    function preparePositions($data)
    {
        $data->TabCaption = tr('Позиции');
        self::prepareDetail($data);
    }
    
    
    function renderPositions($data)
    { 
        $tpl = getTplFromFile('hr/tpl/SingleLayoutPositions.shtml');
        
        return self::renderDetail($data);
    }

    
}