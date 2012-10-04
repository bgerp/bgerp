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
       
        // Каква част от задачата е изпълнена?
        $this->FLD('progress', 'percent(min=0,max=1,decimals=0)',     'caption=Прогрес');

        // Колко време е отнело изпълнението?
        $this->FLD('workingTime', 'time(suggestions=10 мин.|30 мин.|60 мин.|2 часа|3 часа|5 часа|10 часа)',     'caption=Отработено време');
        
        // Статус съобщение
        $this->FLD('message',    'richtext(rows=5)', 'caption=Съобщение,width=300px');
    }


    /**
     * 
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {   
        expect($data->form->rec->taskId);

        $masterRec = cal_Tasks::fetch($data->form->rec->taskId);

        $data->form->title = "|Прогрес по|* \"" . type_Varchar::escape($masterRec->title) . "\"";
    

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
        
        $tpl = new ET('<style>.portal table.listTable {width:auto !important;}</style><div class="clearfix21 portal" style="margin-top:20px;background-color:transparent;">
                            <div class="legend" style="background-color:#ffc;font-size:0.9em;padding:2px;color:black;margin-top:-30px;">Прогрес</div>
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
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        $tRec = cal_Tasks::fetch($rec->taskId, 'workingTime,progress,state');
        
        // Определяне на прогреса
        if(isset($rec->progress)) {
            $tRec->progress = $rec->progress;

            if($rec->progress == 1) {
                $tRec->state = 'closed';
            }
        }
        
        // Определяне на отработеното време
        if(isset($rec->workingTime)) {
            $query = self::getQuery();
            $query->where("#taskId = {$tRec->id}");
            $query->XPR('workingTimeSum', 'int', 'sum(#workingTime)');
            $rec = $query->fetch();
            $tRec->workingTime = $rec->workingTimeSum;
        }

        cal_Tasks::save($tRec);
    }

}