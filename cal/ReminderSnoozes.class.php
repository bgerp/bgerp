<?php


/**
 * Клас 'cal_ReminderSnoozes'
 * 
 * @title Отлагане на напомняне
 *
 *
 * @category  bgerp
 * @package   cal
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_ReminderSnoozes extends core_Detail
{
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'remId';

     
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools,plg_Created,cal_Wrapper';


    /**
     * Заглавие
     */
    public $title = "Отлагане на напомняне";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'createdOn,createdBy,message,timeStart';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/reminders.png.png';

    
    /**
     * Име за единичния изглед
     */
    public $singleTitle = 'отлагане';
    
    
    /**
     * 
     * @var unknown
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Активен таб на менюто
     */
    public $currentTab = 'Напомняния';
   
         
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        // id на напомянето
        $this->FLD('remId', 'key(mvc=cal_Reminders,select=title)', 'caption=Напомняне,input=hidden,silent,column=none');
        
        // Начало на новото напомнянето
        $this->FLD('timeStart', 'time(suggestions=1 час|3 часа|5 часа|8 часа|10 часа|1 ден|2 дена|3 дена|4 дена|5 денa|6 дена|7 дена)', 'caption=Време->Начало, silent,changable');

        // Статус съобщение
        $this->FLD('message',    'richtext(rows=5, bucket=calReminders)', 'caption=Съобщение');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {   
    	expect($data->form->rec->remId);

        $masterRec = cal_Reminders::fetch($data->form->rec->remId);
        
        if ($masterRec->timeStart) {
        	$data->form->setDefault('timeStart', "1 ден");
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
    	expect($form->rec->remId);
    	
    	$masterRec = cal_Reminders::fetch($form->rec->remId);
    	
    	// ако формата е събмитната
    	if ($form->isSubmitted()){
        	if ($masterRec->timeStart > $form->rec->timeStart) {
        		$form->setWarning('timeStart', "|Въвели сте дата по-малка от началната. Сигурни ли сте, че искате да продължите?");
        	} elseif ($masterRec->timeStart == $form->rec->timeStart) {
        		$form->setWarning('timeStart', "|Въвели сте дата равен на предишната. Сигурни ли сте, че искате да продължите?");
        	}
    	}
    }


    /**
     * Изпълнява се след опаковане на детайла от мениджъра
     * 
     * @param stdClass $data
     */
    function renderDetail($data)
    {
        if(!count($data->recs)) {
            return NULL;
        }
    	
        $tpl = new ET('<div class="clearfix21 portal" style="margin-top:20px;background-color:transparent;">
	                            <div class="legend" style="background-color:#ffc;font-size:0.9em;padding:2px;color:black">Отлагане</div>
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
     * Ако няма записи не вади таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_BeforeRenderListTable($mvc, &$res, $data)
    {

    }
    
    
    /**
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_AfterRenderListTable($mvc, &$res, $data)
	{
        if(!count($data->recs)) {
            return NULL;
        }
        
    	if(Mode::is('screenMode', 'narrow')){
			$res = new ET(' <table class="listTable snooz-table"> 
									<!--ET_BEGIN COMMENT_LI-->
										<tr>
	                               			<td>
												<span class="nowrap">[#createdOn#]</span>&nbsp;	
												[#createdBy#]&nbsp; 
												[#timeStart#]&nbsp;
											</td>
											<td>
												[#message#]
											</td>
										</tr>
										
									<!--ET_END COMMENT_LI-->
								</table>
                ');
			
			foreach($data->recs as $rec){
				
				$row = $mvc->recToVerbal($rec);
				
				$cTpl = $res->getBlock("COMMENT_LI");
				$cTpl->placeObject($row);
				$cTpl->removeBlocks();
				$cTpl->append2master();
			}
    	}
    }

    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        $remRec = cal_Reminders::fetch($rec->remId, 'timeStart, state, title, threadId, createdBy');
        $now = dt::now();
        
        // Определяне на времето за отлагане
        if(isset($rec->timeStart)) {
            
            $time = dt::mysql2timestamp($remRec->timeStart) + $rec->timeStart;
            $remRec->timeStart = dt::timestamp2Mysql($time);
            $remRec->state = 'active';
        }
       
        cal_Reminders::save($remRec);
    }
}