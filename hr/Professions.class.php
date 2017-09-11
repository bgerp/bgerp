<?php 


/**
 * Длъжности
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_Professions extends core_Master
{
    
    /**
     * Заглавие
     */
    var $title = "Професии в организацията";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Професия";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools2, hr_Wrapper,  plg_Printing,
                        plg_SaveAndNew, WorkingCycles=hr_WorkingCycles';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,hrMaster';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,hrMaster';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,hrMaster';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'ceo,hrMaster';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'hr/tpl/SingleLayoutProfessions.shtml';
    
    
    /**
     * Единична икона
     */
    var $singleIcon = 'img/16/construction-work-icon.png';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    

    /**
     * Предишно име на класа
     */
    var $oldClassname = 'hr_Positions';
    

    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory');
        
        $this->FLD('nkpd', 'key(mvc=bglocal_NKPD, select=title)', 'caption=НКПД, hint=Номер по НКПД');
               
        $this->FLD('descriptions', 'richtext(bucket=humanResources)', 'caption=@Характеристика, ');
        
        $this->FLD('employmentOccupied', 'datetime', "caption=Назначения,input=none");
        
        $this->setDbUnique('name');
    }
    
    static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {

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
    	if($action == 'delete'){
	    	if ($rec->id) {
	        	
	    		$inUse = hr_EmployeeContracts::fetch("#positionId = '{$rec->id}'");
	    		
	    		if($inUse){
	    			$requiredRoles = 'no_one';
	    		}
    	     }
         }
    }
}