<?php 

/**
 * Смени
 */
class hr_EmployeeContracts extends core_Master
{
    /**
     * Интерфайси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf,hr_ContractAccRegIntf';

    /**
     *  @todo Чака за документация...
     */
    var $title = "Трудови Договори";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $singleTitle = "Трудов договор";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Персонал";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, hr_Wrapper, plg_Printing, Types=hr_ContractTypes,
                     plg_SaveAndNew, WorkingCycles=hr_WorkingCycles, Shifts=hr_Shifts,acc_plg_Registry,
                     Persons=crm_Persons, Companies=crm_Companies, Positions=hr_Positions, Departments=hr_Departments';
    
    var $cssClass = 'document';
    
    /**
     * Права
     */
    var $canRead = 'admin,hr';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canWrite = 'admin,hr';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('typeId', 'key(mvc=hr_ContractTypes,select=name)', "caption=Тип");
        $this->FLD('personId', 'key(mvc=crm_Persons,select=name)', 'caption=Служител, mandatory');
        $this->FLD('positionId', 'key(mvc=hr_Positions,select=name)', 'caption=Длъжност, mandatory,oldField=possitionId');
        $this->FLD('departmentId', 'key(mvc=hr_Departments,select=name)', 'caption=Отдел, mandatory');
        $this->FLD('shiftId', 'key(mvc=hr_Shifts,select=name)', 'caption=Смяна, mandatory');
        
        $this->FLD('descriptions', 'richtext', 'caption=Допълнително');
        
        $this->FLD('startFrom', 'date', "caption=Начало");
        $this->FLD('endOn', 'date', "caption=Край");
        $this->FLD('term', 'int', "caption=Срок,unit=месеца");
        
        $this->FLD('annualLeave', 'int', "caption=Годишен отпуск,unit=дни");
        
        $this->FLD('notice', 'int', "caption=Предизвестие,unit=дни");
        
        $this->FLD('probation', 'int', "caption=Изпитателен срок,unit=месеца");
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_BeforePrepareEditForm($mvc, $data)
    {
        if(!$mvc->Types->fetch('1=1')) {
            core_Message::redirect("Моля въведете поне един тип договор", 'tpl_Error', NULL, array('hr_ContractTypes'));
        }
        
        if(!$mvc->Persons->fetch('1=1')) {
            core_Message::redirect("Моля въведете поне една визитка на служител", 'tpl_Error', NULL, array('crm_Persons'));
        }
        
        if(!$mvc->Positions->fetch('1=1')) {
            core_Message::redirect("Моля въведете поне една длъжност", 'tpl_Error', NULL, array('hr_Positions'));
        }
        
        if(!$mvc->Departments->fetch('1=1')) {
            core_Message::redirect("Моля въведете поне един отдел", 'tpl_Error', NULL, array('hr_Departments'));
        }
        
        if(!$mvc->Shifts->fetch('1=1')) {
            core_Message::redirect("Моля въведете поне една смяна", 'tpl_Error', NULL, array('hr_Shifts'));
        }
    }
    
    
    /**
     *  Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->personId = ht::createLink($row->personId, array('crm_Persons', 'Single', $rec->personId));
        
        $row->positionId = ht::createLink($row->positionId, array('hr_Positions', 'Single', $rec->positionId));
        
        $row->departmentId = ht::createLink($row->departmentId, array('hr_Departments', 'Single', $rec->departmentId));
        
        $row->shiftId = ht::createLink($row->shiftId, array('hr_Shifts', 'Single', $rec->shiftId));
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_BeforeRenderSingle($mvc, $res, $data)
    {
        // bp($data->row);
        $row = clone($data->row);
        $rec = $data->rec;
        
        $script = $mvc->Types->fetchField($rec->typeId, 'script');
        
        $lsTpl = cls::get('legalscript_Engine', array('script' => $script) );
        
        $row->num = $data->rec->id;
        
        $row->employeeRec = $this->Persons->fetch($rec->personId);
        
        $row->employerRec = $this->Companies->fetch(BGERP_OWN_COMPANY_ID);
        
        $row->positionRec = $mvc->Positions->fetch($rec->positionId);
        
        $contract = $lsTpl->render($row);
        
        $res = new ET("[#toolbar#]
        <div class='document' style='max-width:800px;'>[#contract#]</div> <div style='clear:both;'></div>
        
        ");
        
        $res->replace($contract, 'contract');
        
        $res->replace($mvc->renderSingleToolbar($data), 'toolbar');
        
        return FALSE;
    }


    /**
     * Връща заглавието и мярката на перото за продукта
     *
     * Част от интерфейса: intf_Register
     */
    function getItemRec($objectId)
    {
         $result = null;
        
        if ($rec = self::fetch($objectId)) {
            $result = (object)array(
                'title' => $this->getVerbal($rec, 'personId') . " [" . $this->getVerbal($rec, 'startFrom') . ']',
                'num'    => $rec->id,
                'features' => 'foobar' // @todo!
            ); 
        }
        
        return $result;
    }

    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }

}