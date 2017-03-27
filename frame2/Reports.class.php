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
	 * Какви интерфейси поддържа този мениджър
	 */
	public $interfaces = 'doc_DocumentIntf';
	
	
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'frame2_ReportIntf';
	
	
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_RowTools2, frame_Wrapper, doc_plg_Prototype, doc_DocumentPlg, doc_plg_SelectFolder, plg_Search, plg_Printing, bgerp_plg_Blank, doc_SharablePlg';
                      
    
    /**
     * Да се показва антетка
     */
    public $showLetterHead = TRUE;
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Справка';
   
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Справки";

    
    /**
     * Да се забрани ли кеширането на документа
     */
    public $preventCache = TRUE;
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'ceo, report, admin';
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'ceo, report, admin';
    
    
    /**
     * Права за писане
     */
    public $canExport = 'ceo, report, admin';
    
    
    /**
     * Права за писане
     */
    public $canRefresh = 'ceo, report, admin';
    
    
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
	public $canAdd = 'ceo, report, admin';
	
	
	/**
	 * Детайла, на модела
	 */
	public $details = 'frame2_ReportVersions';
	
	
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
    public $listFields = 'title=Документ,updateDays,updateTime,lastRefreshed,createdOn,createdBy,modifiedOn,modifiedBy';
    
    
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
     */
    protected $refreshReports = array();
    
    
    /**
     * Максимален брон на пазене на версии
     */
    const MAX_VERSION_HISTORT_COUNT = 10;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Заглавие');
    	$this->FLD('updateDays', 'set(mon=Понеделник,tue=Вторник,wed=Сряда,thu=Четвъртък,fri=Петък,sat=Събота,sun=Неделя)', 'caption=Обновяване->Дни');
    	$this->FLD('updateTime', 'set(8:00=8:00,9:00=9:00,11:11=11:11)', 'caption=Обновяване->Час');
    	$this->FLD('notificationText', 'varchar', 'caption=Нотифициране при обновяване->Текст,mandatory');
    	$this->FLD('sharedUsers', 'userList(roles=powerUser)', 'caption=Нотифициране при обновяване->Потребители,mandatory');
    	$this->FLD('maxKeepHistory', 'int(Min=0)', 'caption=Запазване на предишни състояния->Версии,autohide,placeholder=Неограничено');
    	$this->FLD('data', 'blob(serialize, compress)', 'input=none');
    	$this->FLD('lastRefreshed', 'datetime', 'caption=Последно актуализиране,input=none');
    }
    
    
    /**
     * Извиква се след подготовката на формата
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setDefault('sharedUsers', keylist::addKey('', core_Users::getCurrent()));
    	$form->setDefault('notificationText', "|*[#handle#] |има актуална версия от|* '[#lastRefreshed#]'");
    	$form->setField('maxKeepHistory', array('placeholder' => self::MAX_VERSION_HISTORT_COUNT));
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = $form->rec;
    		$Driver = $mvc->getDriver($rec);
    		
    		// Ако има драйвер
    		if($Driver){
    			
    			// и няма заглавие на отчета, прави се опит да се вземе от драйвера
    			if(empty($rec->title)){
    				$rec->title = $Driver->getTitle($rec);
    			}
    			
    			// Ако отчета е за фиксирана дата и има опит за обновяване по разписание дава се грешка
    			if(!empty($rec->updateTime) || !empty($rec->updateDays)){
    				if(!$Driver->canBeRefreshedOnTime($rec)){
    					$form->setError('updateDays,updateTime', 'Отчета е за фиксирана дата/период и не може да бъде опресняван по разписание');
    				}
    			}
    			
    			$refresh = TRUE;
    			if(isset($rec->id)){
    				$refresh = FALSE;
    				
    				// Ако записа бива редактиран и няма променени полета от драйвера не се преизчислява
    				$oldRec = self::fetch($rec->id);
    				$fields = $mvc->getDriverFields($Driver);
    				foreach ($fields as $name => $caption){
    					if($oldRec->{$name} !== $rec->{$name}){
    						$refresh = TRUE;
    						break;
    					}
    				}
    			}
    			
    			// Флаг че датата трябва да се рефрешне
    			if($refresh === TRUE){
    				$rec->refreshData = TRUE;
    			}
    		}
    		
    		// Трябва да има заглавие
    		if(empty($rec->title)){
    			$form->setError('title', 'Задайте име на справката');
    		}
    	}
    }
    
    
    /**
     * Изпращане на нотификации на споделените потребители
     * 
     * @param stdClass $rec
     * @return void
     */
    public static function sendNotification($rec)
    {
    	$userArr = keylist::toArray($rec->sharedUsers);
    	$msg = new core_ET($rec->notificationText);
    	
    	// Заместване на параметрите
    	if($Driver = self::getDriver($rec)){
    		$params = $Driver->getNotificationParams($rec);
    		if(is_array($params)){
    			$msg->placeArray($params);
    		}
    	}
    	
    	// Изпращане на нотификациите
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
    	
    	// Добавен бутон за ръчно обновяване
    	if($mvc->haveRightFor('refresh', $rec)){
    		$data->toolbar->addBtn('Обнови', array($mvc, 'refresh', $rec->id, 'ret_url' => TRUE), 'ef_icon=img/16/arrow_refresh.png,title=Обновяване на отчета');
    	}
    	
    	if($mvc->haveRightFor('export', $rec)){
    		$data->toolbar->addBtn('Експорт в CSV', array($mvc, 'export', $rec->id), NULL, 'ef_icon=img/16/file_extension_xls.png, title=Сваляне на записите в CSV формат,row=1');
    	}
    }
    
    
    /**
     * Рефрешване на отчета
     */
    function act_Refresh()
    {
    	$this->requireRightFor('refresh');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('refresh', $rec);
    	
    	self::refresh($rec, $save = TRUE);
    	$versionArr = Mode::get(frame2_ReportVersions::PERMANENT_SAVE_NAME);
    	unset($versionArr[$rec->id]);
    	Mode::setPermanent(frame2_ReportVersions::PERMANENT_SAVE_NAME, $versionArr);
    	
    	return followRetUrl();
    }
    
    
    /**
     * След рендиране на еденичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$rec = $data->rec;
    	
    	// Рендиране на данните
    	if($Driver = $mvc->getDriver($rec)){
    		$rec->data = $Driver->prepareData($rec);
    		$tpl->append($Driver->renderData($rec), 'DRIVER_DATA');
    	}
    }
    
    
    /**
     * Метод опресняващ отчета
     * 
     * @param stdClass $rec - ид на отчет
     */
    public static function refresh(&$rec)
    {
    	$rec = self::fetchRec($rec);
    	
    	// Ако има драйвер
    	if($Driver = self::getDriver($rec)){
    		$me = self::getSingleton();
    		
    		// Опресняват се данните му
    		$rec->data = $Driver->prepareData($rec);
    		$rec->lastRefreshed = dt::now();
    		
    		// Запис на променените полета
    		$me->save_($rec, 'data,lastRefreshed');
    		
    		// Записване в опашката че отчета е бил опреснен
    		$me->refreshReports[$rec->id] = $rec;
    		if(frame2_ReportVersions::log($rec->id, $rec)){
    			if(core_Users::getCurrent() != core_Users::SYSTEM_USER){
    				core_Statuses::newStatus('Справката е актуализирана');
    			}
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
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if($rec->refreshData === TRUE){
    		self::refresh($rec);
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
    	if($rec->state == 'draft'){
    		$rec->state = 'active';
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'refresh' && isset($rec)){
    		if($Driver = $mvc->getDriver($rec)){
    			if(!$Driver->canBeRefreshedOnTime($rec)){
    				$requiredRoles = 'no_one';
    			}
    		}
    		
    		if($rec->state == 'rejected'){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	if($action == 'export' && isset($rec)){
    		if(!$mvc->haveRightFor('single', $rec)){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Добавя допълнителни полетата в антетката
     *
     * @param core_Master $mvc
     * @param NULL|array $res
     * @param object $rec
     * @param object $row
     */
    public static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
    	$resArr = arr::make($resArr);
    	$resArr['title'] = array('name' => tr('Заглавие'), 'val' => $row->title);
    	
    	if(!empty($rec->updateDays) || !empty($rec->updateTime)){
    		$resArr['update'] = array('name' => tr('Актуализиране'), 'val' => tr("|*<div><!--ET_BEGIN updateDays--><span style='font-weight:normal'>|Дни|*</span>: [#updateDays#]<!--ET_END updateDays-->
        																		 <!--ET_BEGIN updateTime--><br><span style='font-weight:normal'>|Часове|*</span>: [#updateTime#]<!--ET_END updateTime-->"));										 
    	}
    	
    	if(isset($rec->lastRefreshed)){
    		$resArr['lastRefreshed'] = array('name' => tr('Актуален към'), 'val' => $row->lastRefreshed);
    	}
    	
    	$resArr['notify'] = array('name' => tr('Известия'), 'row' => 2, 'val' => tr("|*[#sharedUsers#]"));
    }
    
    
    /**
     * Коя е последната избрана версия от потребителя
     * 
     * @param int $id - ид
     * @return int    - ид на последната версия
     */
    public static function getSelectedVersionId($id)
    {
    	$versionArr = Mode::get(frame2_ReportVersions::PERMANENT_SAVE_NAME);
    	
    	return $versionArr[$id];
    }
    
    
    /**
     * Преди подготовка на сингъла
     */
    public static function on_BeforePrepareSingle(core_Mvc $mvc, &$res, $data)
    {
    	// Ако има избрана версия записа се подменя преди да се е подготвил
    	if($versionId = self::getSelectedVersionId($data->rec->id)){
    		if($versionRec = frame2_ReportVersions::fetchField($versionId, 'oldRec')){
    			$data->rec = $versionRec;
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($fields['-single'])){
    		// Ако има избрана версия
    		$selectedVersionid = self::getSelectedVersionId($rec->id);
    		if(isset($selectedVersionid) && !Mode::isReadOnly()){
    			
    			// И тя е по-стара от последната
    			$latestVersionId = frame2_ReportVersions::getLatestVersionId($rec->id);
    			if($selectedVersionid < $latestVersionId){
    				
    				// Показва се информация
    				if(frame2_ReportVersions::haveRightFor('checkout', $latestVersionId)){
    					$checkoutUrl = array('frame2_ReportVersions', 'checkout', $latestVersionId, 'ret_url' => $mvc->getSingleUrlArray($rec->id));
    					$row->checkoutBtn = ht::createLink('Избор', $checkoutUrl, FALSE);
    					$row->checkoutDate = frame2_ReportVersions::getVerbal($latestVersionId, 'createdOn');
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Екшън който експортира данните
     */
    public function act_Export()
    {
		// Проверка за права
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('export', $rec);
    
    	if($versionId = self::getSelectedVersionId($data->rec->id)){
    		if($versionRec = frame2_ReportVersions::fetchField($versionId, 'oldRec')){
    			$rec = $versionRec;
    		}
    	}
    	
    	$Driver = $this->getDriver($rec);
   	    
    	$csvExportRows = $Driver->getCsvExportRows($rec);
    	$fields = $Driver->getCsvExportFieldset($rec);
    	
    	$csv = csv_Lib::createCsv($csvExportRows, $fields);
    	$csv .= "\n" . $rCsv;
    	
    	$fileName = str_replace(' ', '_', Str::utf2ascii($Driver->title));
    	 
    	header("Content-type: application/csv");
    	header("Content-Disposition: attachment; filename={$fileName}.csv");
    	header("Pragma: no-cache");
    	header("Expires: 0");
    	 
    	echo $csv;
    
    	shutdown();
    }
}