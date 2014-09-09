<?php



/**
 * Клас 'doc_UnsortedFolders' - Корици на папки с несортирани документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_UnsortedFolders extends core_Master
{
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'accda_DaFolderCoverIntf, price_PriceListFolderCoverIntf, trans_LinesFolderCoverIntf, frame_FolderCoverIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,plg_Rejected,doc_Wrapper,plg_State,doc_FolderPlg,plg_RowTools,plg_Search';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    var $autoCreateFolder = 'instant';
    
    
    /**
     * Заглавие
     */
    var $title = "Проекти";
    
    
    /**
     * var $listFields = 'id,title,inCharge=Отговорник,threads=Нишки,last=Последно';
     */
    var $oldClassName = 'email_Unsorted';
    
    
    /**
     * полета от БД по които ще се търси
     */
    var $searchFields = 'name';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Проект';
    
    
    /**
     * Път към картинка 16x16
     */
    var $singleIcon = 'img/16/project-archive.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'doc/tpl/SingleLayoutUnsortedFolder.shtml';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'powerUser';
    
    
    /**
     * Кой може да го види?
     */
    var $canSingle = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin';
    
    
    /**
     * Кой може да го оттегли?
     */
    var $canReject = 'powerUser';
    
    
    /**
     * Кой може да го възстанови?
     */
    var $canRestore = 'powerUser';
    
    
    /**
     * Кой има права Rip
     */
    var $canWrite = 'powerUser';
    
    
    /**
     * Кои полета можем да редактираме, ако записът е системен
     */
    var $protectedSystemFields = 'none';
    
    
    /**  
     * Кой има право да променя системните данни?  
     */  
    var $canEditsysdata = 'admin';
  
    /**
     * масив с цветове
     */
    static  $colors = array( "#610b7d", 
				    	"#1b7d23",
				    	"#4a4e7d",
				    	"#7d6e23", 
				    	"#33757d",
				    	"#211b7d", 
				    	"#72142d",
				    	"#EE82EE",
				    	"#0080d0",
				    	"#FF1493",
				    	"#C71585",
				    	"#0d777d",
				    	"#4B0082",
				    	"#7d1c24",
				    	"#483D8B",
				    	"#7b237d", 
				    	"#8B008B",
	    				"#FFC0CB",
	    				"#cc0000",
	    				"#00cc00",
	    				"#0000cc",
	    				"#cc00cc",
		    			"#3366CC",
		    			"#FF9999",
		    			"#FF3300",
		    			"#9999FF",
		    			"#330033",
		    			"#003300",
		    			"#0000FF",
		    			"#FFFF33",
		    			"#66CDAA",
		    			"#98FB98",
		    			"#4169E1",
		    			"#D2B48C",
		    			"#9ACD32",
		    			"#00FF7F",
		    			"#4169E1",
		    			"#EEE8AA",
		    			"#9370DB",
		    			"#3CB371",
		    			"#FFB6C1",
		    			"#DAA520",
		    			"#483D8B",
		    			"#8B0000",
		    			"#00FFFF",
		    			"#DC143C",
		    			"#8A2BE2",
		    			"#D2B48C",
		    			"#3CB371",
		    			"#AFEEEE",
    	                );

    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        $this->FLD('name' , 'varchar(128)', 'caption=Наименование,mandatory');
        $this->FLD('description' , 'richtext(rows=3)', 'caption=Описание');
        $this->FLD('closeTime' , 'time', 'caption=Автоматично затваряне на нишките след->Време');
        $this->setDbUnique('name');
    }
    
    
    /**
     *
     * След подготовка на тулбара на единичен изглед.
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, $data)
    {  
    	// от текущото Url, намираме папката
    	$currUrl = getCurrentUrl();
    	// правим заявка към таблицата на Задачите
    	$queryTasks = cal_Tasks::getQuery();
    	$queryTasks->where("#folderId = '{$data->rec->folderId}'");
  
    	// ако можем да извлечем данни, то ще определим,
    	// кой е най-подходящия тип на Гант-а и 
    	// ще сложим бутон за него
        while ($recs = $queryTasks->fetch()) {
        	$source[] = $recs;
        }
        
        if ($source) {
	    	// намираме типа на Ганта
		    $ganttType = cal_Tasks::getGanttTimeType($source);
	
	    	$data->toolbar->addBtn('Гант', array(
		                    $mvc,
		                    'Gant',
		                    $data->rec->id,
		                    'View'=>$ganttType
		                ),
		                'ef_icon = img/16/chart-bar-icon-16.png');
        }
    }

    
    /**
     * 
     */
    function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        // Ако е субмитната формата
        if ($data->form && $data->form->isSubmitted()) {
            
            // Променяма да сочи към single'a
            $data->retUrl = toUrl(array($mvc, 'single', $data->form->rec->id));
        }
    }
    
    
    /**
     * Зареждане на Cron задачите за автоматично затваряне на папка след setup на класа
     *
     * @param core_MVC $mvc
     * @param string $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $rec = new stdClass();
        $rec->systemId = "self_closed_unsorted_folders";
        $rec->description = "Автоматично затваряне на папки";
        $rec->controller = "doc_UnsortedFolders";
        $rec->action = "SelfClosed";
        $rec->period = 24 * 60;
        $rec->offset = 17 * 60;
        $res .= core_Cron::addOnce($rec);
    }
    
    
    /**
     * Метод за Cron за зареждане на валутите
     */
    function cron_SelfClosed()
    {   
    	// сегашно време в секунди
    	$now = dt::mysql2timestamp(dt::now());
    	// заявка към текущата база
    	$query = $this->getQuery();
    	// заявка към базата на "нишките"
    	$queryThread = doc_Threads::getQuery();
     	
    	// търсим всички проекти, които не са отхвърлени и имат време за автоматично затваряне
        $query->where("#state != 'rejected' AND #closeTime IS NOT NULL");

        while ($rec = $query->fetch()) {
        	// търсим нишка, която отговаря на тази папка и е отворена
        	$queryThread->where("#folderId = '{$rec->folderId}' AND #state = 'opened'");
        	// и я взимаме
        	while ($recThread = $queryThread->fetch()) {
        		// ако тя последно е модифицирана преди (сега - времето за затваряне)
        		if ($recThread->modifiedOn <= dt::timestamp2mysql($now - $rec->closeTime)){
        		// автоматично я затваряме
        			$recThread->state = 'closed';
                   
        			doc_Threads::save($recThread, 'state');
        		}		
        	}
        }
    }
    
    
    /**
     * Екшън за спиране
     */
    static public function act_Gant()
    {
    	$currUrl = getCurrentUrl();
        
    	$data = self::fetch($currUrl['id']);

    	//Очакваме да има такъв запис
        //expect($id = Request::get('id', 'int'));
        
        //expect($rec = $this->fetch($id));
        
        //Очакваме потребителя да има права за спиране
        //$this->haveRightFor('gant', $rec);
    	//bp(self::prepareGantt($data));
    	$tpl = getTplFromFile('doc/tpl/SingleLayoutUnsortedFolderGantt.shtml');
        //Права за работа с екшън-а
        requireRole('powerUser');

        // слагаме бутони на къстам тулбара
        $btns = ht::createBtn('Редакция', array(
	                    $mvc,
	                    'edit',
	                    $data->id
	                ), NULL, NULL,
	                'ef_icon = img/16/edit-icon.png');
	    $btns .= ht::createBtn('Папка', array(
	                    'doc_Threads',
	                    'list',
	                    'folderId'=>$data->folderId
	                ), NULL, NULL,
	                'ef_icon = img/16/folder-y.png');
	                
        $btns .= ht::createBtn('Корица', array(
	                    $mvc,
	                    'single',
	                    $data->id
	                ), NULL, NULL,
	                'ef_icon = img/16/project-archive.png');
        
	    // иконата за пред името на проекта
	    $icon = sbf("img/24/chart-bar-icon-24.png", '', '');
        
	    // подготвените данни за Гант
 		$res = self::prepareGantt($data);

 		// изчертаваме Гант
	    $chart = gantt_Adapter::render($res);
        
        // Заместваме в шаблона
        $tpl->replace($btns, 'SingleToolbarGantt');
        $tpl->replace('state-'.$data->state, 'STATE_CLASS_GANTT');
  		$tpl->replace("<img alt='' src='{$icon}'>", 'SingleIconGantt');
        $tpl->replace($data->name, 'nameGantt');
        $tpl->replace($chart, 'Gantt');
        
        // Редиректваме
        return static::renderWrapping($tpl);
    }
    
    
    static public function prepareGantt ($folderData)
    {  
            	
    	$idTaskDoc = core_Classes::getId("cal_Tasks");
    	
    	// заявка към базата на "контейнерите"
    	$queryContainers = doc_Containers::getQuery();
    	
    	// заявка към базата на "нишките"
    	$queryTasks = cal_Tasks::getQuery();
     	
    	// търсим всички дали в тази папка има задачи 
        $queryContainers->where("#folderId = '{$folderData->folderId}' AND #docClass = '{$idTaskDoc}'");
        
        while ($recContainers = $queryContainers->fetch()) {
        	$queryTasks->where("#folderId = '{$folderData->folderId}'");
        	$i = 0;
        	// заявка към таблицата на Задачите
        	while ($recTask = $queryTasks->fetch()) {
        		if($recTask->timeStart){
        			// ако няма продължителност на задачата
    	    		if(!$recTask->timeDuration && !$recTask->timeEnd) {
    	    			// продължителността на задачата е края - началото
    	    			$timeDuration = 1800;
    	    		} elseif(!$recTask->timeDuration && $recTask->timeEnd ) {
    	    			$timeDuration = dt::mysql2timestamp($recTask->timeEnd) - dt::mysql2timestamp($recTask->timeStart);
    	    		} else {
    	    			$timeDuration = $recTask->timeDuration;
    	    		}

	        		// Ако имаме права за достъп до сингъла
	    	    	if (cal_Tasks::haveRightFor('single', $recTask)) {
	    	    		// ще се сложи URL
			           	$flagUrl = 'yes';
			        } else {
			        	$flagUrl = FALSE;
			        }
	        		
	        		$rowArr = array ();
	        		$rowArr[$recTask->id] =  $i;
	        		
	        		$resTask[] = array( 
	    			    					'taskId' => $recTask->id,
	    			    					'rowId' =>  $rowArr,
	    		    						'timeline' => array (
	    		    											'0' => array(
	    		                								'duration' => $timeDuration,  
	    		                								'startTime'=> dt::mysql2timestamp($recTask->timeStart))),
	    		    		                
	    			    					'color' => self::$colors[$recTask->id % 50],
	    			    					'hint' => $recTask->id. " : ". $recTask->title,
	    		    						'url' =>  $flagUrl,
	    			    					'progress' => $recTask->progress
	    		    		);
	    		    	    
		    		$resources[] = array("name" => $recTask->title, "id" => $recTask->id);
		    		$recs[$recTask->id] = $recTask;
		    		$forTask = (object) array('recs' => $recs);
		    		$i++;
        		}
        	}
        }

		$others = cal_Tasks::renderGanttTimeType($forTask);
	
		$params = $others->otherParams;
		$header = $others->headerInfo;
        
    	for ($i = 0; $i <= (count($resTask)); $i++){
        	// Проверка дали ще има URL
        	if ($resTask[$i]['url'] == 'yes') {
        		// Слагаме линк
        		$resTask[$i]['url'] = toUrl(array('cal_Tasks', 'single' , $resTask[$i]['taskId']));
        	} else {
        		// няма да има линк
        		unset ($resTask[$i]['url']);
        	}
        }

	    // връщаме един обект от всички масиви
	    return (object) array('tasksData' => $resTask, 'headerInfo' => $header , 'resources' => $resources, 'otherParams' => $params);
    }
}
