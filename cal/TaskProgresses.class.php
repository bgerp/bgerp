<?php


/**
 * Клас 'cal_TaskProgresses'
 * 
 * @title Отчитане изпълнението на задачите
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_TaskProgresses extends core_Detail
{
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'taskId';

     
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools,plg_Created,cal_Wrapper';


    /**
     * Заглавие
     */
    var $title = "Прогрес по задачите";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'createdOn,createdBy,message,progress,workingTime';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/task.png';

    var $canAdd = 'user';

    
    
     
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // id на задачата
        $this->FLD('taskId', 'key(mvc=cal_Tasks,select=title)', 'caption=Задача,input=hidden,silent,column=none');

        // Статус съобщение
        $this->FLD('message',    'varchar(128)', 'caption=Статус,width=100%');
        
        // Каква част от задачата е изпълнена?
        $this->FLD('progress', 'percent(min=0,max=1,decimals=0)',     'caption=Прогрес');
        
        // Колко време е отнело изпълнението?
        $this->FLD('workingTime', 'time',     'caption=Отработено време');
    }


    /**
     *
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {   
        if($data->form->rec->taskId) {
            $masterRec = cal_Tasks::fetch($data->form->rec->taskId);
        }

        $progressArr[''] = '';

        for($i = 0; $i <= 100; $i += 10) {
            if($masterRec->progress > ($i/100)) continue;
            $p = $i . ' %';
            $progressArr[$p] = $p;
        }
        $data->form->setSuggestions('progress', $progressArr);
    }


    /**
     *
     */
    function renderDetail($data)
    {
        if(!count($data->recs)) {
            return NULL;
        }
        
        $tpl = new ET('<style>.portal table.listTable {width:auto !important;}</style><div class="clearfix21 portal" style="margin-bottom:10px;background-color:transparent;">
                            <div class="legend" style="background-color:#fff;font-size:0.9em;padding:2px;color:black;margin-top:-30px;">Прогрес</div>
                                <div class="listRows">
                                [#TABLE#]
                                </div>
                            </div>
                        </div>           
                ');
        $tpl->replace($this->renderListTable($data), 'TABLE');

        return $tpl;
    }
    

    /**
     *
     */
    static function on_AfterSave($mvc, $id, $rec)
    {
        $masterRec = cal_Tasks::fetch($rec->taskId);
        $masterRec->progress = $rec->progress;
        if($rec->progress == 1) {
            $masterRec->state = 'closed';
        }
        cal_Tasks::save($masterRec, 'progress,state');
    }

}