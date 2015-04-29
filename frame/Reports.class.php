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
    public $loadList = 'plg_RowTools, frame_Wrapper, doc_DocumentPlg, plg_Search, plg_Printing, doc_plg_HidePrices, bgerp_plg_Blank';
                      
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Отчет';
    

    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf';
   
    
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
     * Абревиатура
     */
    public $abbr = "Rep";
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    public $singleIcon = 'img/16/report.png';


    /**
     * Групиране на документите
     */
    public $newBtnGroup = "18.9|Други";


    /**
     * Файл с шаблон за единичен изглед на статия
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
     * Как се казва полето за данните от формата на драйвъра
     */
    public $innerFormField = 'filter';
    
    
    /**
     * Как се казва полето за записване на вътрешните данни
     */
    public $innerStateField = 'data';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // Singleton клас - източник на данните
        $this->FLD('source', 'class(interface=frame_ReportSourceIntf, allowEmpty, select=title)', 'caption=Вид,silent,mandatory,notFilter,refreshForm');

        // Поле за настройките за филтриране на данните, които потребителят е посочил във формата
        $this->FLD('filter', 'blob(1000000, serialize, compress)', 'caption=Филтър,input=none,single=none,column=none');

        // Извлечените данни за отчета. "Снимка" на състоянието на източника.
        $this->FLD('data', 'blob(1000000, serialize, compress)', 'caption=Данни,input=none,single=none,column=none');
 
        // Най-ранната дата когато отчета може да се активира
        $this->FLD('earlyActivationOn', 'datetime(format=smartTime)', 'input=none,caption=Активиране');
    }

    
    /**
     *  Обработки по вербалното представяне на данните
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
		if($fields['-single']) {
           
            // Обновяваме данните, ако отчета е в състояние 'draft'
            if($rec->state == 'draft') {
               //bp($rec, $mvc->getDriver($rec));
            	$Source = $mvc->getDriver($rec);
            	$rec->data = $Source->prepareInnerState();
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
    	if(is_null($fields) && ($rec->state == 'draft' || $rec->state == 'pending')){
    		
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
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
    	$folderCover = doc_Folders::getCover($folderId);
       
       return ($folderCover->haveInterface('frame_FolderCoverIntf')) ? TRUE : FALSE;
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
        $folderCover = doc_Folders::getCover($threadRec->folderId);
        
    	return ($folderCover->haveInterface('frame_FolderCoverIntf')) ? TRUE : FALSE;
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
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$me = cls::get(get_called_class());
    	
    	try{
    		$Driver = $me->getDriver($rec);
    		if($Driver){
    			$title = $me->singleTitle . " '{$Driver->getReportTitle()}' №{$rec->id}";
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
    	$query->where("#state = 'pending'");
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
    	redirect(array($this, 'single', $id), 'Документа е активиран успешно');
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
    	$rec->state = ($when < $rec->earlyActivationOn) ? 'pending' : 'active';
    	$this->save($rec, 'state');
    	 
    	// Ако сме го активирали, генерираме събитие че е бил активиран
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
    		$data->toolbar->addBtn('Активиране', array($mvc, 'activate', $data->rec->id), "id=btnActivate,warning=Наистина ли желаете документа да бъде активиран?", 'ef_icon = img/16/lightning.png,title=Активиране на отчета');
    	}
    	
    	if($mvc->haveRightFor('export', $data->rec)){
    		$data->toolbar->addBtn('Експорт в CSV', array($mvc, 'export', $data->rec->id), NULL, 'ef_icon = img/16/file_extension_xls.png, title = Сваляне на записите в CSV формат');
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
    		if($state == 'pending'){
    			$requiredRoles = $mvc->getRequiredRoles('edit');
    		}
    	}

    	// Ако отчета е активен, може да се експортва
    	if($action == 'export' && isset($rec)){
    		$state = (!isset($rec->state)) ? $mvc->fetchField($rec->id, 'state') : $rec->state;
    		if($state != 'active'){
    			$requiredRoles = 'no_one';
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