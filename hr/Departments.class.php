<?php 

/**
 * Смени
 */
class hr_Departments extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Отдели";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $singleTitle = "Отдел";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Персонал";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, hr_Wrapper,  plg_Printing,
                    plg_SaveAndNew, WorkingCycles=hr_WorkingCycles,acc_plg_Registry';
    
    
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
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory');
        $this->FLD('parentId', 'key(mvc=hr_Departments, select=name, allowEmpty)', "caption=В състава на,input=none");
        $this->FLD('orderId', 'varchar(100)', "caption=Подредба, input=none,1column=none");
        $this->FLD('employersCnt', 'datetime', "caption=Служители,input=none");
        $this->XPR('orderSum', 'varchar', 'CONCAT(#id, #orderId)');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     *  Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        if($mvc->fetch('1=1')) {
            $data->form->setField('parentId', 'input');
        }
    }
    
    
    /**
     *  Извиква се преди подготовката на масивите $data->recs и $data->rows
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        
        $data->query->orderBy("#orderSum");
    }
    
    
    /**
     *  Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave($mvc, $id, $rec)
    {
        if($rec->parentId) {
            $parentRec = $mvc->fetch($rec->parentId);
            
            if($parentRec) {
                $rec->orderId = ($parentRec->orderId + $parentRec->id) * 1000;
            }
        }
    }
}