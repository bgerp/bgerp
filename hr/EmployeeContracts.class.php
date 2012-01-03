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
    var $loadList = 'plg_Created, plg_RowTools, hr_Wrapper, plg_Printing,
                     acc_plg_Registry, doc_DocumentPlg';
    
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
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/report_user.png'; 

    
    /**
     * Абривиатура
     */
    var $abbr = "TD";

    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('typeId', 'key(mvc=hr_ContractTypes,select=name)', "caption=Тип");
        
        $this->FLD('managerId', 'key(mvc=crm_Persons,select=name)', 'caption=Управител, mandatory');

        // Служител
        $this->FLD('personId', 'key(mvc=crm_Persons,select=name)', 'caption=Служител->Имена, mandatory');
        $this->FLD('education', 'varchar', 'caption=Служител->Образование');
        $this->FLD('specialty', 'varchar', 'caption=Служител->Специалност');
        $this->FLD('diplomId', 'varchar', 'caption=Служител->Диплома №');
        $this->FLD('diplomIssuer', 'varchar', 'caption=Служител->Издадена от');
        $this->FLD('lengthOfService', 'int', 'caption=Служител->Трудов стаж,unit=г.');

        // Работа
        $this->FLD('departmentId', 'key(mvc=hr_Departments,select=name)', 'caption=Работа->Отдел, mandatory');
        $this->FLD('shiftId', 'key(mvc=hr_Shifts,select=name)', 'caption=Работа->Смяна, mandatory');
        $this->FLD('positionId', 'key(mvc=hr_Positions,select=name)', 'caption=Работа->Длъжност, mandatory,oldField=possitionId');
        
        // УСЛОВИЯ
        $this->FLD('startFrom', 'date', "caption=Условия->Начало,mandatory");
        $this->FLD('endOn', 'date', "caption=Условия->Край");
        $this->FLD('term', 'int', "caption=Условия->Срок,unit=месеца");
        $this->FLD('annualLeave', 'int', "caption=Условия->Годишен отпуск,unit=дни");
        $this->FLD('notice', 'int', "caption=Условия->Предизвестие,unit=дни");
        $this->FLD('probation', 'int', "caption=Условия->Изпитателен срок,unit=месеца");
        $this->FLD('descriptions', 'richtext', 'caption=Условия->Допълнителни');
    }
    
    
    /**
     *
     */
    function on_AfterPrepareeditForm($mvc, $data)
    {
        $pQuery = crm_Persons::getQuery();

        cls::load('crm_Companies');

        while($pRec = $pQuery->fetch("#buzCompanyId = " . BGERP_OWN_COMPANY_ID)) {
            $options[$pRec->id] = crm_Persons::getVerbal($pRec, 'name');
        }

        $data->form->setOptions('managerId', $options);
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
     *
     */
    function on_AfterPrepareSingle($mvc, $data, $data)
    {
        $row = $data->row;
        
        $rec = $data->rec;
        
        $row->script = hr_ContractTypes::fetchField($rec->typeId, 'script');
        
        $row->num = $data->rec->id;
        
        $row->employeeRec = crm_Persons::fetch($rec->personId);
        
        $row->employerRec = crm_Companies::fetch(BGERP_OWN_COMPANY_ID);
        
        $row->managerRec = crm_Persons::fetch($rec->managerId);

        $row->positionRec = hr_Positions::fetch($rec->positionId);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_BeforeRenderSingle($mvc, $res, $data)
    {
        $row = $data->row;

        $lsTpl = cls::get('legalscript_Engine', array('script' => $row->script) );

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
    
    
    /****************************************************************************************
     *                                                                                      *
     *  ИМПЛЕМЕНТАЦИЯ НА @link doc_DocumentIntf                                             *
     *                                                                                      *
     ****************************************************************************************/

    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row->title =  tr('Трудов договор на|* ') . $this->getVerbal($rec, 'personId');

        $row->authorId = $rec->createdBy;
        $row->author   = $this->getVerbal($rec, 'createdBy');

        $row->state  = $rec->state;
        $row->createdOn  = $rec->createdOn;

 
        return $row;
    }

}