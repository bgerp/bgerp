<?php 


/**
 * Смени
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_Departments extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Отдели";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Отдел";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Персонал";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, hr_Wrapper,  plg_Printing,
                    plg_SaveAndNew, WorkingCycles=hr_WorkingCycles,acc_plg_Registry';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,hr';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'admin,hr';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory,width=100%');
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title, allowEmpty)', "caption=Локация,width=100%");
        $this->FLD('employersCnt', 'datetime', "caption=Служители,input=none");
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        $data->form->setOptions('locationId', array('' => '&nbsp;') + crm_Locations::getOwnLocations());
    }
    
    
    /**
     * Извиква се преди подготовката на масивите $data->recs и $data->rows
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        
      //  $data->query->orderBy("#orderSum");
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, $id, $rec)
    {
        if($rec->parentId) {
            $parentRec = $mvc->fetch($rec->parentId);
            
            if($parentRec) {
                $rec->orderId = ($parentRec->orderId + $parentRec->id) * 1000;
            }
        }
    }

}