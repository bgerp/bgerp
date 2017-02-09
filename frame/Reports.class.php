<?php






/**
 * Мениджър на отчети от различни източници
 *
 *
 * @category  bgerp
 * @package   frame
 * @author    Milen Georgiev <milen@experta.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class frame_Reports extends core_Embedder
{
    
    
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_RowTools2, frame_Wrapper, doc_plg_Prototype,doc_DocumentPlg, doc_plg_SelectFolder, plg_Search, plg_Printing, doc_plg_HidePrices, bgerp_plg_Blank, doc_EmailCreatePlg';
                      
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Отчет';
    

    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf,email_DocumentIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Отчети";

    
    /**
     * Права за писане
     */
    public $canWrite = 'ceo, report, admin';
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'powerUser';
    
    
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
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canChangestate = 'powerUser';
    
    
	/**
	 * Кой може да добавя?
	 */
	public $canAdd = 'powerUser';
	
	
	/**
	 * Кой може да добавя?
	 */
	public $canExport = 'powerUser';
	
	
    /**
     * Абревиатура
     */
    public $abbr = "Rep";
    
    
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
    public $newBtnGroup = "18.9|Други";


    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'frame/tpl/SingleLayoutReport.shtml';


    /**
     * Свойство, което указва интерфейса на вътрешните обекти
     */
    public $innerObjectInterface = 'frame_ReportSourceIntf';
    
    
    /**
     * Как се казва полето за избор на вътрешния клас
     */
    public $innerClassField = 'source';
    
    
    /**
     * Кои полета да не се клонират
     */
    public $fieldsNotToClone = 'source,filter,data';
    
    
    /**
     * Как се казва полето за данните от формата на драйвъра
     */
    public $innerFormField = 'filter';
    
    
    /**
     * Как се казва полето за записване на вътрешните данни
     */
    public $innerStateField = 'data';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,title=Документ,source,earlyActivationOn,createdOn,createdBy,modifiedOn,modifiedBy';
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders';
    
    
    /**
     * Колко време да се пази кешираното състояние при чернова
     */
    const KEEP_INNER_STATE_IN_DRAFT = 86400;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // Singleton клас - източник на данните
        $this->FLD('source', 'class(interface=frame_ReportSourceIntf, allowEmpty, select=title)', 'caption=Отчет,silent,mandatory,notFilter,refreshForm');

        // Поле за настройките за филтриране на данните, които потребителят е посочил във формата
        $this->FLD('filter', 'blob(1000000, serialize, compress)', 'caption=Филтър,input=none,single=none,column=none');

        // Извлечените данни за отчета. "Снимка" на състоянието на източника.
        $this->FLD('data', 'blob(1000000, serialize, compress)', 'caption=Данни,input=none,single=none,column=none');
 
        // Най-ранната дата когато отчета може да се активира
        $this->FLD('earlyActivationOn', 'datetime(format=smartTime)', 'input=none,caption=Активиране');
    }

    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    public static function on_BeforeSave($mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
    	// При чернова винаги подготвяме вътрешното състояние
    	if($rec->state == 'draft' && $rec->id){
    		if(!$rec->data){
    			$Driver = frame_Reports::getDriver($rec);
    			$rec->data = $Driver->prepareInnerState();
    		}
    	}
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->title = $mvc->getLink($rec->id, 0);
    	
    	if($fields['-single']) {
           
            // Обновяваме данните, ако отчета е в състояние 'draft'
            if($rec->state == 'draft') {
            	
            	// Ако сме минали зададеното време за обновяване на кеша на данните при чернова
                if(dt::addSecs(self::KEEP_INNER_STATE_IN_DRAFT, $rec->modifiedOn) < dt::now()){
                	
                	// Обновяваме записа, така че на ново да се извлече вътрешното състояние
                	unset($rec->data);
                	$mvc->save($rec);
               }
            }

            $now = dt::now();
            if($rec->earlyActivationOn < $now) {
            	unset($row->earlyActivationOn);
            }
           
            if($rec->state == 'active' || $rec->state == 'rejected'){
            	unset($row->earlyActivationOn);
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
    	if(is_null($fields) && ($rec->state == 'draft' || $rec->state == 'waiting')){
    		
    		// Обновяваме датата на кога най-рано може да се активира
    		$Source = $mvc->getDriver($rec);

            $rec->earlyActivationOn = $Source->getEarlyActivation();

    		$rec->state = 'draft';
    		
    		$mvc->save($rec, 'earlyActivationOn,state');
    	}
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
    	$Driver = $mvc->getDriver($rec->id);
    	
    	$Driver->invoke('AfterActivation', array(&$rec->data, &$rec));
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterReject($mvc, &$res, &$rec)
    {
    	$Driver = $mvc->getDriver($rec->id);
    	
    	$Driver->invoke('AfterReject', array(&$rec->data, &$rec));
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterRestore($mvc, &$res, &$rec)
    {
    	$Driver = $mvc->getDriver($rec->id);
    
    	$Driver->invoke('AfterRestore', array(&$rec->data, &$rec));
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     * 
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
	public static function canAddToThread($threadId)
    {
        $threadRec = doc_Threads::fetch($threadId);
        
    	return self::canAddToFolder($threadRec->folderId);
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
     * Връща тялото на имейла генериран от документа
     * 
     * @see email_DocumentIntf
     * @param int $id - ид на документа
     * @param boolean $forward
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = FALSE)
    {
    	$handle = $this->getHandle($id);
    	$tpl = new ET(tr('Моля запознайте се с нашата справка ') . ': #[#handle#]');
    	$tpl->append($handle, 'handle');
    
    	return $tpl->getContent();
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$me = cls::get(get_called_class());
    	
    	try{
    		$Driver = $me->getDriver($rec);
    		if($Driver){
    		    if (isset($rec->filter->to)){
    		        $to = dt::mysql2verbal($rec->filter->to,'d.m.year');
    		        if(isset($rec->data->summary) && isset($rec->filter->orderField)) {
    		            $summ = $rec->data->summary->{$rec->filter->orderField} . " BGN";
    		            $title = "{$Driver->getReportTitle()} ({$to}/{$summ})";
    		        } else {
    		          $title = "{$Driver->getReportTitle()} ({$to})";
    		        }
    		    } else {
    			    $title = "{$Driver->getReportTitle()}";
    		    }
    		}
    	} catch(core_exception_Expect $e){
    		$title = "<span class='red'>" . tr('Проблем при показването') . "</span>";
    	}
    
    	return $title;
    }
    
    
    /**
	 * Скрива полетата, които потребител с ниски права не може да вижда
	 * 
	 * @param stdClass $data
	 */
    public function hidePriceFields($data)
    {
    	$Driver = $this->getDriver($data->rec);
    	$Driver->hidePriceFields();
    }
    
    
    /**
     * Активира всички чакащи отчети, на които текущата дата е след
     * или по време на датата им за най-ранно активиране
     */
    public function cron_ActivateEarlyOn()
    {
    	$now = dt::now();
    	
    	// Намираме всички отчети които са чакащи и им е пресрочена датата на активация
    	$query = $this->getQuery();
    	$query->where("#state = 'waiting'");
    	$query->where("#earlyActivationOn <= '{$now}'");
    	$query->orWhere("#earlyActivationOn IS NULL");
    	
    	// Активираме ги
    	while($rec = $query->fetch()){
    		$this->activate($rec, $now);
    	}
    }
    
    
    /**
     * Екшън който активира отчета или го прави чакащ
     */
    public function act_Activate()
    {
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	
    	// Проверка за права
    	$this->requireRightFor('changestate', $data->rec);
    	
    	// Променяме състоянието на документа
    	$this->activate($rec);
    	
    	// Редирект
    	return new Redirect(array($this, 'single', $id), '|Документа е активиран успешно');
    }
    
    
    /**
     * Екшън който експортира данните
     */
    public function act_Export()
    {
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	 
    	// Проверка за права
    	$this->requireRightFor('export', $rec);
    	 
    	$Driver = $this->getDriver($rec);

    	$csv = $Driver->exportCsv();

    	$fileName = str_replace(' ', '_', Str::utf2ascii($Driver->title));
    	
    	header("Content-type: application/csv");
    	header("Content-Disposition: attachment; filename={$fileName}.csv");
    	header("Pragma: no-cache");
    	header("Expires: 0");
    	
    	echo $csv;

    	shutdown();

    }
    
    
    /**
     * Метод активиращ документа или го прави чакащ
     * 
     * @param stdClass $rec
     * @return void
     */
    private function activate($rec, $when = NULL)
    {
    	if(empty($when)){
    		$when = dt::now();
    	}
    	
    	// Ако няма стойност за най-ранно активиране - извличаме я наново
    	if(empty($rec->earlyActivationOn)){
    		$Driver = $this->getDriver($rec);
    		$rec->earlyActivationOn = $Driver->getEarlyActivation();
    	}
    	
    	// Ако сега сме преди датата за активиране, правим го 'чакащ' иначе директно се 'активира'
    	$rec->state = ($when < $rec->earlyActivationOn) ? 'waiting' : 'active';
    	$this->save($rec, 'state');
    	 
    	// Ако сме го активирали, генерираме събитие, че е бил активиран
    	if($rec->state == 'active'){
    		$this->invoke('AfterActivation', array($rec));
    	}
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if($mvc->haveRightFor('changestate', $data->rec)){
    		$data->toolbar->addBtn('Активиране', array($mvc, 'activate', $data->rec->id), "id=btnActivate,warning=Наистина ли желаете документът да бъде активиран?", 'ef_icon = img/16/lightning.png,title=Активиране на отчета');
    	}
    	
    	if($mvc->haveRightFor('export', $data->rec)){
    		$data->toolbar->addBtn('Експорт в CSV', array($mvc, 'export', $data->rec->id), NULL, 'ef_icon=img/16/file_extension_xls.png, title=Сваляне на записите в CSV формат,row=2');
    	}
    }

    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	// Кой може да променя състоянието на отчета
    	if($action == 'changestate' && isset($rec)){
    		if($rec->state != 'draft'){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	if($action == 'activate'){
    		$requiredRoles = 'no_one';
    	}
    	
    	// Ако отчета е чакащ, може да се редактира
    	if($action == 'edit' && isset($rec)){
    		$state = (!isset($rec->state)) ? $mvc->fetchField($rec->id, 'state') : $rec->state;
    		if($state == 'waiting'){
    			$requiredRoles = $mvc->getRequiredRoles('edit');
    		}
    	}

    	// Ако отчета е активен, може да се експортва
    	if($action == 'export' && isset($rec)){
    		
    		$canExport = FALSE;
    		
    		if ($rec->state !== 'active') {
    		    $requiredRoles = 'no_one';
    		} else {
    		    $Driver = $mvc->getDriver($rec);
    		    
    		    if(!$Driver->canSelectInnerObject()){
    		    
    		        $requiredRoles = 'no_one';
    		    }
    		}
    	}
    	
    	if ($action == 'add') {
    	    
    	    $canAdd = FALSE;
    	    
    		// Извличаме класовете с посочения интерфейс
    		$interfaces = core_Classes::getOptionsByInterface($mvc->innerObjectInterface, 'title');
			foreach ((array)$interfaces as $id => $int){
				if(!cls::load($id, TRUE)) continue;
				
				$Driver = cls::get($id);
				
				// Ако има права за добавяне на поне 1 отчет
				if($Driver->canSelectInnerObject()){
				    
				    $canAdd = TRUE;
				    break;
				}
			}
			
			if (!$canAdd) {
			    $requiredRoles = 'no_one';
			}
    	}
    	
    	if ($rec && (($action == 'changestate') || ($action == 'edit') || ($action == 'export'))) {
    	    if (!haveRole('ceo, report, admin', $userId)) {
    	        if ($rec->createdBy != $userId) {
    	            $requiredRoles = 'no_one';
    	        }
    	    }
    	}
    }
}