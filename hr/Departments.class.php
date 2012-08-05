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
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory');
        $this->FLD('parentId', 'key(mvc=hr_Departments, select=name, allowEmpty)', "caption=В състава на,input=none");
        $this->FLD('orderId', 'varchar(100)', "caption=Подредба, input=none,1column=none");
        $this->FLD('employersCnt', 'datetime', "caption=Служители,input=none");
        $this->XPR('orderSum', 'varchar', 'CONCAT(#id, #orderId)');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        if($mvc->fetch('1=1')) {
            $data->form->setField('parentId', 'input');
        }
    }
    
    
    /**
     * Извиква се преди подготовката на масивите $data->recs и $data->rows
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        
        $data->query->orderBy("#orderSum");
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

    function act_DateTest()
    {
        $d = date_parse('Sat, 4 Aug 2012 14:50:37 +0300');

        $time = mktime($d['hour'], $d['minute'], $d['second'], $d['month'], $d['day'] , $d['year']);
            
        if($d['is_localtime']) {
            $time = $time + $d['zone'] * 60 + (date("O") / 100 * 60 * 60);
        }
            
        $res = dt::timestamp2Mysql($time);
 
        $html .= "<li> $res";
        $html .= "<li>" . date_offset_get(new DateTime) / 60;
        $html .= "<li>" . date("O") / 100 * 60 * 60);
        
        return $html;
    }

}