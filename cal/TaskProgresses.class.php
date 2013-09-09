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
    public static function on_AfterPrepareEditForm($mvc, $data)
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
    	
        $tpl = new ET('<div class="clearfix21 portal" style="margin-top:20px;background-color:transparent;">
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
    
    
	function on_AfterRenderListTable($mvc, &$res, $data)
    {
        if(!count($data->recs)) {
            return NULL;
        }
        
    	if(Mode::is('screenMode', 'narrow')){
			$res = new ET('  <!--ET_BEGIN COMMENT_LI-->
                                Дата: [#date#] <br /> 
								Потребител: [#person#] <br />
								Описание: [#message#] <br />
								Отработено време: [#workingTime#] <br />
								Прогрес: [#progress#]</br>
								</br>
								<!--ET_END COMMENT_LI-->
                                      
                ');
			
			foreach($data->recs as $rec){
				$date = dt::mysql2verbal($rec->createdOn, "smartTime");
				$person = core_Users::recToVerbal(core_Users::fetchField($rec->createdBy))->nick;
				$time = cls::get(type_Time);
				$workingTime = $time->toVerbal($rec->workingTime);
				$progress = $rec->progress * 100 . "%";
								
				$cTpl = $res->getBlock("COMMENT_LI");
				$cTpl->replace($date, 'date');
				$cTpl->replace($person, 'person');
				$cTpl->replace($rec->message, 'message');
				$cTpl->replace($workingTime, 'workingTime');
				$cTpl->replace($progress, 'progress');
				$cTpl->removeBlocks();
				$cTpl->append2master();
			}
    	}
    }
    

    /**
     *
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        $tRec = cal_Tasks::fetch($rec->taskId, 'workingTime,progress,state, title, threadId, createdBy');

        // Определяне на прогреса
        if(isset($rec->progress)) {
            $tRec->progress = $rec->progress;

            if($rec->progress == 1) {
            	$message = tr("Приключена е задачата \"" . $tRec->title . "\"");
            	$url = array('doc_Containers', 'list', 'threadId' => $tRec->threadId);
            	$customUrl = array('cal_Tasks', 'single',  $tRec->id);
            	$priority = 'normal';
            	bgerp_Notifications::add($message, $url, $tRec->createdBy, $priority, $customUrl);
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