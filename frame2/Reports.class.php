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
    public $loadList = 'plg_RowTools2, frame_Wrapper, doc_plg_Prototype, doc_DocumentPlg, doc_plg_SelectFolder, plg_Search, plg_Printing, bgerp_plg_Blank, doc_SharablePlg, plg_Clone';
                      
    
    /**
     * Кой има право да клонира?
     */
    public $canClonerec = 'powerUser';
    
    
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
    public $canWrite = 'powerUser';
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Права за писане
     */
    public $canExport = 'powerUser';
    
    
    /**
     * Права за писане
     */
    public $canRefresh = 'powerUser';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, report, admin';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'powerUser';
    
    
	/**
	 * Кой може да добавя?
	 */
	public $canAdd = 'powerUser';
	
	
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
    public $newBtnGroup = FALSE;


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
     * Кеш на обновените отчети
     */
    protected $setNewUpdateTimes = array();
    
    
    /**
     * Максимален брон на пазене на версии
     */
    const MAX_VERSION_HISTORT_COUNT = 10;
    
    
    /**
     * Дефолтен текст за нотификация
     */
    protected static $defaultNotificationText = "|*[#handle#] |има актуална версия от|* '[#lastRefreshed#]'";
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'lastRefreshed';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Заглавие');
    	$this->FLD('updateDays', 'set(monday=Понеделник,tuesday=Вторник,wednesday=Сряда,thursday=Четвъртък,friday=Петък,saturday=Събота,sunday=Неделя)', 'caption=Обновяване и известяване->Дни,autohide');
    	$this->FLD('updateTime', 'set(08:00,09:00,10:00,11:00,12:00,13:00,14:00,15:00,16:00,17:00,18:00,19:00,20:00)', 'caption=Обновяване и известяване->Час,autohide');
    	$this->FLD('notificationText', 'varchar', 'caption=Обновяване и известяване->Текст,autohide');
    	$this->FLD('sharedUsers', 'userList(roles=powerUser)', 'caption=Обновяване и известяване->Потребители,autohide');
    	$this->FLD('maxKeepHistory', 'int(Min=0)', 'caption=Запазване на предишни състояния->Версии,autohide,placeholder=Неограничено');
    	$this->FLD('data', 'blob(serialize, compress,size=20000000)', 'input=none');
    	$this->FLD('lastRefreshed', 'datetime', 'caption=Последно актуализиране,input=none');
    }
    
    
    /**
     * Извиква се след подготовката на формата
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setField('notificationText', array('placeholder' => self::$defaultNotificationText));
    	$form->setField('maxKeepHistory', array('placeholder' => self::MAX_VERSION_HISTORT_COUNT));
    
    	if($Driver = self::getDriver($form->rec)){
    		$dates = $Driver->getNextRefreshDates($form->rec);
    		if((is_array($dates) && count($dates)) || $dates === FALSE){
    			$form->setField('updateDays', 'input=none');
    			$form->setField('updateTime', 'input=none');
    		}
    	}
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
    			
    			$refresh = TRUE;
    			if(isset($rec->id)){
    				$refresh = FALSE;
    				$oldRec = self::fetch($rec->id);
    			
    				// Ако записа бива редактиран и няма променени полета от драйвера не се преизчислява
    				$fields = $mvc->getDriverFields($Driver);
    				foreach ($fields as $name => $caption){
    					if($oldRec->{$name} !== $rec->{$name}){
    						$refresh = TRUE;
    						break;
    					}
    				}
    			
    				// Ако е променен броя на версиите ъпдейт
    				if($rec->maxKeepHistory != $oldRec->maxKeepHistory){
    					$rec->updateVersionHistory = TRUE;
    				}
    				
    				// Ако преди е имало обновяване, но сега няма ще се премахнат зададените обновявания
    				$oldUpdateTime = (!empty($oldRec->updateDays) || !empty($oldRec->updateTime));
    				if($oldUpdateTime && (empty($rec->updateDays) && empty($rec->updateTime))){
    					$rec->removeSetUpdateTimes = TRUE;
    				}
    				
    				// Ако са променени данните за обновяване ъпдейтват се
    				if($rec->removeSetUpdateTimes !== TRUE){
    					if($oldRec->updateDays != $rec->updateDays || $oldRec->updateTime != $rec->updateTime){
    						$rec->updateRefreshTimes = TRUE;
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
    		
    		if((isset($rec->updateDays) || isset($rec->updateTime)) && empty($rec->sharedUsers)){
    			$form->setError('sharedUsers', 'Не са посочени потребители за известяване при обновяване');
    		}
    		
    		frame2_ReportVersions::unSelectVersion($rec->id);
    	}
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
    	$data->form->toolbar->renameBtn('save', 'Запис');
    }
    
    
    /**
     * Изпращане на нотификации на споделените потребители
     * 
     * @param stdClass $rec
     * @return void
     */
    public static function sendNotification($rec)
    {
    	// Ако няма избрани потребители за нотифициране, не се прави нищо
    	$userArr = keylist::toArray($rec->sharedUsers);
    	if(!count($userArr)) return;
    	
    	$text = (!empty($rec->notificationText)) ? $rec->notificationText : self::$defaultNotificationText;
    	$msg = new core_ET($text);
    	
    	// Заместване на параметрите в текста на нотификацията
    	if($Driver = self::getDriver($rec)){
    		$params = $Driver->getNotificationParams($rec);
    		if(is_array($params)){
    			$msg->placeArray($params);
    		}
    	}
    	
    	$url = array('frame2_Reports', 'single', $rec->id);
    	$msg = $msg->getContent();
    	
    	// На всеки от абонираните потребители се изпраща нотификацията за промяна на документа
    	foreach ($userArr as $userId){
    		bgerp_Notifications::add($msg, $url, $userId);
    	}
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
    		$data->toolbar->addBtn('Експорт в CSV', array($mvc, 'export', $rec->id, 'ret_url' => TRUE), NULL, 'ef_icon=img/16/file_extension_xls.png, title=Сваляне на записите в CSV формат,row=2');
    	}
    	
    	$url = array($mvc, 'single', $rec->id);
    	$icon = 'img/16/checked.png';
    	if(!Request::get('vId', 'int')){
    		$url['vId'] = $rec->id;
    		$icon = 'img/16/checkbox_no.png';
    	}
    	
    	$vCount = frame2_ReportVersions::count("#reportId = {$rec->id}");
    	if($vCount > 1){
    		$data->toolbar->addBtn("Версии ({$vCount})", $url, NULL, "ef_icon={$icon}, title=Показване на предишни версии,row=1");
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
    	frame2_ReportVersions::unSelectVersion($rec->id);
    	
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
    		$tpl->replace($Driver->renderData($rec)->getContent(), 'DRIVER_DATA');
    	} else {
    		$tpl->replace("<span class='red'><b>" . tr('Несъществуващ драйвер') . "</b></span>", 'DRIVER_DATA');
    	}
    	
    	// Връщане на оригиналния рек ако е пушнат
    	if(isset($data->originalRec)){
    		$rec = $data->originalRec;
    	}
    }
    
    
    /**
     * Метод опресняващ отчета по разписания
     *
     * @param stdClass $data - дата
     */
    public static function callback_refreshOnTime($data)
    {
    	try{
    		expect($rec = self::fetch($data->id));
    		if($rec->state == 'rejected') return;
    		self::refresh($rec);
    	} catch(core_exception_Expect $e){
    		reportException($e);
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
    		$me = cls::get(get_called_class());
    		
    		// Опресняват се данните му
    		$rec->data = $Driver->prepareData($rec);
    		$rec->lastRefreshed = dt::now();
    		
    		// Запис на променените полета
    		$me->save_($rec, 'data,lastRefreshed');
    		
    		// Записване в опашката че отчета е бил опреснен
    		if(frame2_ReportVersions::log($rec->id, $rec)){
    			$me->refreshReports[$rec->id] = $rec;
    			if(core_Users::getCurrent() != core_Users::SYSTEM_USER){
    				core_Statuses::newStatus('Справката е актуализирана');
    			}
    		}
    		
    		// Mаркиране че отчера реяжва да се обнови
    		$me->setNewUpdateTimes[$rec->id] = $rec;
    	}
    }
    
    
    /**
     * След изпълнение на скрипта, обновява записите, които са за ъпдейт
     */
    public static function on_Shutdown($mvc)
    {
    	// Ако е имало опреснени отчети
    	if(is_array($mvc->refreshReports)){
    		foreach ($mvc->refreshReports as $rec) {
    			if($Driver = $mvc->getDriver($rec)){
    				
    				// Проверява се трябва ли да бъде изпратена нова нотификация до споделените
    				if($Driver->canSendNotificationOnRefresh($rec)){
    					
    					// Ако да то се нотифицират всички споделени потребители
    					self::sendNotification($rec);
    				}
    			}
    		}
    	}
    	
    	// Задаване на нови времена за обновяване
    	if(is_array($mvc->setNewUpdateTimes)){
    		foreach ($mvc->setNewUpdateTimes as $rec) {
    			self::setAutoRefresh($rec->id);
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
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if($rec->refreshData === TRUE){
    		self::refresh($rec);
    	}
    	
    	// Ако е променен броя на поддържаните версии, ъпдейтват се
    	if($rec->updateVersionHistory === TRUE){
    		frame2_ReportVersions::keepInCheck($rec->id);
    	}
    	
    	// Айи ще се махнат зададените времена за обновяване, махат се
    	if($rec->removeSetUpdateTimes === TRUE){
    		self::removeAllSetUpdateTimes($rec->id);
    	}
    	
    	// Ако ще се ъпдейтват времената за обновяване
    	if($rec->updateRefreshTimes === TRUE){
    		$mvc->setNewUpdateTimes[$rec->id] = $rec;
    	}
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if($rec->state == 'draft'){
    		$rec->state = 'active';
    	} elseif($rec->state == 'rejected'){
    		$rec->removeSetUpdateTimes = TRUE;
    	} elseif($rec->state == 'active' && $rec->brState == 'rejected'){
    		$rec->updateRefreshTimes = TRUE;
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'refresh' && isset($rec)){
    		if($Driver = $mvc->getDriver($rec)){
    			$dates = $Driver->getNextRefreshDates($rec);
    			if($dates === FALSE){
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
    	
    	// Документа може да бъде създаван ако потребителя може да избере поне един драйвер
    	if($action == 'add'){
    		$options = self::getAvailableDriverOptions($userId);
    		if(!count($options)){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	// За модификация, потребителя трябва да има права и за драйвера
    	if(in_array($action, array('edit', 'write', 'refresh', 'export', 'clonerec')) && isset($rec->driverClass)){
    		if($Driver = $mvc->getDriver($rec)){
    			if(!$Driver->canSelectDriver($userId)){
    				$requiredRoles = 'no_one';
    			}
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
    protected static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
    	$resArr = arr::make($resArr);
    	$resArr['title'] = array('name' => tr('Заглавие'), 'val' => $row->title);
    	
    	if(!empty($rec->updateDays) || !empty($rec->updateTime) || !empty($row->nextUpdate)){
    		$resArr['update'] = array('name' => tr('Актуализиране'), 'val' => tr("|*<!--ET_BEGIN updateDays--><div><span style='font-weight:normal'>|Дни|*</span>: [#updateDays#]<br><!--ET_END updateDays-->
        																		 <!--ET_BEGIN updateTime--><span style='font-weight:normal'>|Часове|*</span>: [#updateTime#]<!--ET_END updateTime--><!--ET_BEGIN nextUpdate--><div><span style='font-weight:normal'>|Следващо|*</span> [#nextUpdate#]</div><!--ET_END nextUpdate-->"));										 
    	}
    	
    	if(isset($rec->lastRefreshed)){
    		$resArr['lastRefreshed'] = array('name' => tr('Актуален към'), 'val' => $row->lastRefreshed);
    	}
    	
    	if(isset($rec->sharedUsers)){
    		$resArr['notify'] = array('name' => tr('Известия'), 'row' => 2, 'val' => tr("|*[#sharedUsers#]"));
    	}
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
    protected static function on_BeforePrepareSingle(core_Mvc $mvc, &$res, $data)
    {
    	// Ако има избрана версия записа се подменя преди да се е подготвил
    	if($versionId = self::getSelectedVersionId($data->rec->id)){
    		if($versionRec = frame2_ReportVersions::fetchField($versionId, 'oldRec')){
    			$data->originalRec = clone $data->rec;
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
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
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
    					$row->checkoutBtn = ht::createLink('Избор', $checkoutUrl, FALSE, array('ef_icon' => 'img/16/tick-circle-frame.png', 'title' => 'Към последната версия'));
    					$row->checkoutDate = frame2_ReportVersions::getVerbal($latestVersionId, 'createdOn');
    				}
    			}
    		}
    		
    		$callOn = $mvc->getNextRefreshTime($rec);
    		if(!empty($callOn)){
    			$row->nextUpdate = core_Type::getByName('datetime(format=smartTime)')->toVerbal($callOn);
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
    
    	// Ако е избрана версия експортира се тя
    	if($versionId = self::getSelectedVersionId($id)){
    		if($versionRec = frame2_ReportVersions::fetchField($versionId, 'oldRec')){
    			$rec = $versionRec;
    		}
    	}
    	
    	// Подготовка на данните
    	$csvExportRows = $fields = array();
    	if($Driver = $this->getDriver($rec)){
    		$csvExportRows = $Driver->getCsvExportRows($rec);
    		$fields = $Driver->getCsvExportFieldset($rec);
    	}
    	
    	// Проверка има ли данни за експорт
    	if(!count($csvExportRows)) followRetUrl(NULL, 'Няма данни за експортиране');
    	
    	// Създаване на csv-то
    	$csv = csv_Lib::createCsv($csvExportRows, $fields);
    	$csv .= "\n" . $rCsv;
    	
    	$fileName = str_replace(' ', '_', str::utf2ascii($rec->title));
    	 
    	header("Content-type: application/csv");
    	header("Content-Disposition: attachment; filename={$fileName}({$rec->id}).csv");
    	header("Pragma: no-cache");
    	header("Expires: 0");
    	echo $csv;
    	shutdown();
    }
    
    
    // Премахване на зададените времена за обновяване
    public static function removeAllSetUpdateTimes($id)
    {
    	foreach (range(0, 2) as $i){
    		$data = new stdClass();
    		$data->id = (string)$id;
    		$data->index = (string)$i;
    		core_CallOnTime::remove(get_called_class(), 'refreshOnTime', $data);
    	}
    }
    
    
    /**
     * Задаване на автоматично време за изпълнение
     * 
     * @param int $id
     * @return void
     */
    public static function setAutoRefresh($id)
    {
    	$rec = self::fetchRec($id);
    	$dates = NULL;
    	
    	if($Driver = self::getDriver($rec)){
    		$dates = $Driver->getNextRefreshDates($rec);
    	}
    	
    	// Намира следващите три времена за обновяване
    	if(empty($dates)){
    		$dates = self::getNextRefreshDates($rec);
    	}
    	
    	// Обхождане от 0 до 2
    	foreach (range(0, 2) as $i){
    		$data = new stdClass();
    		$data->id = (string)$id;
    		$data->index = (string)$i;
    		if(!isset($dates[$i])) continue;
    		
    		core_CallOnTime::setOnce(get_called_class(), 'refreshOnTime', $data, $dates[$i]);
    	}
    	
    	if(haveRole('debug')){
    		status_Messages::newStatus("Зададени времена за обновяване");
    	}
    }
    
    
    /**
     * Следващото обновяване на отчета
     * 
     * @param stdClass $rec
     * @return datetime|NULL
     */
    private function getNextRefreshTime($rec)
    {
    	foreach (range(0, 2) as $i){
    		$callOn = core_CallOnTime::getNextCallTime(get_called_class(), 'refreshOnTime', (object)array('id' => (string)$rec->id, 'index' => (string)$i));
    		if(!empty($callOn)) return $callOn;
    	}
    }
    
    
    /**
     * Връща следващите три дати, когато да се актуализира справката
     * 
     * @param stdClass $rec - запис
     * @return array        - масив с три дати
     */
    private static function getNextRefreshDates($rec)
    {
    	// Ако няма зададени времена, няма да има дати за обновяване
    	if(empty($rec->updateDays) && empty($rec->updateTime)) return array();
    	
    	$fromDate = $rec->lastRefreshed;
    	$dayKeys = array(1 => 'monday', 2 => 'tuesday' , 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 7 => 'sunday');
    	$date = new DateTime($fromDate);
    	
    	// Кой ден от седмицата е (1 за Понеделник до 7 за Неделя)
    	$todayKey = $date->format('N');
    	$days = type_Set::toArray($rec->updateDays);
    	$daysArr = array();
    	
    	// Ако има зададени дати
    	if(count($days)){
    		$orderArr = $after = $before = array();
    		
    		// Подреждат се дните, които са след текущия ден
    		foreach ($days as $d){
    			$k = array_search($d, $dayKeys);
    			if($k > $todayKey && $k <= 7){
    				$after[$k] = $d;
    			} elseif($k <= $todayKey && $k >= 1){
    				$before[$k] = $d;
    			}
    		}
    		 
    		ksort($after);
    		ksort($before);
    		 
    		// Връща се масив с подредените относително дни
    		$orderArr = array_merge($after, $before);
    		$count = count($orderArr);
    		
    		// Подсигуряване, че масива има три дена (ако е зададен само един, се повтарят)
    		if(count($orderArr) == 1){
    			$orderArr = array_merge($orderArr, $orderArr, $orderArr);
    		} elseif($count == 2){
    			$orderArr = array_merge($orderArr, array($orderArr[key($orderArr)]));
    		}
    		 
    		// Генериране на следващите три дена за изпълняване
    		foreach ($orderArr as $d1){
    			$date->modify("next {$d1}");
    			$nextDate = $date->format('Y-m-d');
    			$daysArr[] = $nextDate;
    		}
    	} else {
    		
    		// Ако няма зададени дни, взимат се най-близките три дена
    		$daysArr[] = $date->format('Y-m-d');
    		$date->modify("next day");
    		$daysArr[] = $date->format('Y-m-d');
    		$date->modify("next day");
    		$daysArr[] = $date->format('Y-m-d');
    		$date->modify("next day");
    		$daysArr[] = $date->format('Y-m-d');
    	}
    	
    	// Намират се зададените времена, ако няма това е началото на работния ден
    	$timesArr = type_Set::toArray($rec->updateTime);
    	if(!count($timesArr)){
    		$startTime = bgerp_Setup::get('START_OF_WORKING_DAY');
    		$timesArr[$startTime] = $startTime;
    	}
    	
    	// Времената се добавят към датите
    	$now = dt::now();
    	$res = array();
    	foreach ($daysArr as $d){
    		foreach ($timesArr as $time){
    			$dt = "{$d} {$time}";
    			if($dt < $now) continue;
    			$res[] = $dt;
    		}
    	}
    	
    	// Сортират се
    	sort($res);
    	
    	// Връщат се най близките 3 дати
    	return array($res[0], $res[1], $res[2]);
    }
}
