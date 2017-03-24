<?php



/**
 * Нов мениджър за справки
 *
 *
 * @category  bgerp
 * @package   frame2
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class frame2_Reports extends embed_Manager
{
    
    
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'frame2_ReportIntf';
	
	
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_RowTools2, frame_Wrapper, doc_plg_Prototype, doc_DocumentPlg, doc_plg_SelectFolder, plg_Search, plg_Printing, bgerp_plg_Blank, doc_SharablePlg';
                      
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Справка';
    

    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Справки";

    
    /**
     * Права за писане
     */
    public $canWrite = 'ceo, report, admin';
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'ceo, report, admin';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'ceo, report, admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, report, admin';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, report, admin';
    
    
	/**
	 * Кой може да добавя?
	 */
	public $canAdd = 'powerUser';
	
	
    /**
     * Абревиатура
     */
    public $abbr = "Rpt";
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    public $singleIcon = 'img/16/report.png';


    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "18.91|Други";


    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'frame2/tpl/SingleLayoutReport.shtml';
    
    
    /**
     * Може ли да се редактират активирани документи
     */
    public $canEditActivated = TRUE;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,title=Документ,updateDays,updateTime,lastRefreshed,createdOn,createdBy,modifiedOn,modifiedBy';
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Кеш на обновените отчети
     * 
     * @var array
     */
    protected $refreshReports = array();
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Заглавие');
    	$this->FLD('updateDays', 'set(mon=Понеделник,tue=Вторник,wed=Сряда,thu=Четвъртък,fri=Петък,sat=Събота,sun=Неделя)', 'caption=Обновяване->Дни');
    	$this->FLD('updateTime', 'set(8:00=8:00,9:00=9:00,11:11=11:11)', 'caption=Обновяване->Час');
    	$this->FLD('sharedUsers', 'userList(roles=powerUser)', 'caption=Нотифициране при обновяване->Потребители,mandatory');
    	$this->FLD('notificationText', 'varchar', 'caption=Нотифициране при обновяване->Текст');
    	$this->FLD('maxKeepHistory', 'int', 'caption=Допълнително->Брой запазени промени');
    	$this->FLD('data', 'blob(serialize, compress)', 'input=none');
    	$this->FLD('lastRefreshed', 'datetime', 'caption=Актуален към,input=none');
    }
    
    
    /**
     * Извиква се след подготовката на формата
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setDefault('sharedUsers', keylist::addKey('', core_Users::getCurrent()));
    	$form->setDefault('notificationText', "Има нови неща в отчет [#handle#]");
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = $form->rec;
    	
    	if($form->isSubmitted()){
    		if(empty($rec->title)){
    			if($Driver = $mvc->getDriver($rec)){
    				$rec->title = $Driver->getTitle($rec);
    			}
    		}
    		
    		if(empty($rec->title)){
    			$form->setError('title', 'Задайте име на справката');
    		}
    		
    		$rec->isEdited = TRUE;
    	}
    }
    
    
    public static function sendNotification($rec)
    {
    	$userArr = keylist::toArray($rec->sharedUsers);
    	$msg = new core_ET($rec->notificationText);
    	
    	if($Driver = self::getDriver($rec)){
    		$params = $Driver->getNotificationParams($rec);
    		if(is_array($params)){
    			$msg->placeArray($params);
    		}
    	}
    	
    	doc_Containers::notifyToSubscribedUsers($rec->containerId, $msg->getContent());
    }
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$title = self::getVerbal($rec, 'title');
    	
    	return "{$title} №{$rec->id}";
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	 
    	$row = new stdClass();
    	$row->title    = $this->getRecTitle($rec);
    	$row->authorId = $rec->createdBy;
    	$row->author   = $this->getVerbal($rec, 'createdBy');
    	$row->state    = $rec->state;
    	$row->recTitle = $row->title;
    
    	return $row;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	
    	if($mvc->haveRightFor('edit', $rec)){
    		//$data->toolbar->addBtn('Обнови', array($mvc, 'refresh', $rec->id), 'ef_icon=img/16/bug.png,title=Дебъг информация,row=2');
    	}
    }
    
    
    /**
     * След рендиране на еденичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$rec = $data->rec;
    	
    	// Рендиране на данните
    	if($Driver = $mvc->getDriver($rec)){
    		$tpl->append($Driver->renderData($rec), 'DRIVER_DATA');
    	}
    }
    
    
    /**
     * Метод опресняващ отчета
     * 
     * @param stdClass $rec - ид на отчет
     * @param boolean $save - да се запишат ли промените в модела или не
     */
    public static function refresh(&$rec, $save = TRUE)
    {
    	// Ако има драйвер
    	if($Driver = self::getDriver($rec)){
    		$me = self::getSingleton();
    		
    		// Опресняват се данните му
    		$rec->data = $Driver->prepareData($rec);
    		$rec->lastRefreshed = dt::now();
    		
    		// Записване в опашката че отчета е бил опреснен
    		$me->refreshReports[$rec->id] = $rec;
    		core_Statuses::newStatus('Данните са преизчислени');
    		
    		// Запис на променените полета
    		if($save === TRUE){
    			$me->save_($rec, 'data,lastRefreshed');
    		}
    	}
    }
    
    
    /**
     * След изпълнение на скрипта, обновява записите, които са за ъпдейт
     */
    public static function on_Shutdown($mvc)
    {
    	// Ако е имало опреснени отчети
    	if(count($mvc->refreshReports)){
    		foreach ($mvc->refreshReports as $rec) {
    			if($Driver = $mvc->getDriver($rec)){
    				
    				// Проверява се трябва ли да бъде изпратена нова нотификация до споделените
    				if($Driver->canSendNotification($rec)){
    					
    					// Ако да то се нотифицират всички споделени потребители
    					self::sendNotification($rec);
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if($rec->isEdited === TRUE){
    		self::refresh($rec, FALSE);
    	}
    	
    	if($rec->state == 'draft'){
    		$rec->state = 'active';
    	}
    }
}