<?php
/**
 * Клас 'trans_Lines'
 *
 * Документ за Транспортни линии
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trans_Lines extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Транспортни линии';


    /**
     * Абревиатура
     */
    public $abbr = 'Tl';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, trans_Wrapper, plg_Sorting, plg_Printing,
                    doc_DocumentPlg, bgerp_plg_Blank, plg_Search, doc_ActivatePlg';

    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'vehicleId';
    
    
    /**
     * По кои полета ще се търси
     */
    public $searchFields = 'title, destination, vehicleId, forwarderId, forwarderPersonId';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, trans';
    
    
    /**
     * Поле за единичен изглед
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, trans';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, trans';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, trans';


    /**
     * Кой има право да пише?
     */
    var $canWrite = 'ceo, trans';


    /**
     * Детайла, на модела
     */
    public $details = 'Shipments=store_ShipmentOrders,Receipts=store_Receipts,Transfers=store_Transfers';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, title, start, folderId, createdOn, createdBy';
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Транспортна линия';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'trans/tpl/SingleLayoutLines.shtml';

    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/lorry_go.png';
    
   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.5|Логистика";
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Заглавие');
    	$this->FLD('start', 'datetime', 'caption=Начало, mandatory');
    	$this->FLD('destination', 'varchar(255)', 'caption=Дестинация,mandatory');
    	$this->FLD('repeat', 'time(suggestions=1 ден|1 седмица|1 месец)', 'caption=Повторение');
    	$this->FLD('state', 'enum(draft=Чернова,active=Активен,rejected=Оттеглен,closed=Затворен)', 'caption=Състояние,input=none');
    	$this->FLD('isRepeated', 'enum(yes=Да,no=Не)', 'caption=Генерирано на повторение,input=none');
    	$this->FLD('vehicleId', 'key(mvc=trans_Vehicles,select=name,allowEmpty)', 'caption=Допълнително->Превозвач');
    	$this->FLD('forwarderId', 'key(mvc=crm_Companies,select=name,group=suppliers,allowEmpty)', 'caption=Допълнително->Транспортна фирма');
    	$this->FLD('forwarderPersonId', 'acc_type_Item(lists=accountablePersons,select=titleLink,allowEmpty)', 'caption=Допълнително->МОЛ');
    	
    	$this->setDbUnique('title');
    }
    
    
	/**
     * Малко манипулации след подготвянето на формата за филтриране
     */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		$data->listFilter->showFields = 'search';
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
		$data->listFilter->input();
		
		$data->query->orderBy("#state");
		$data->query->orderBy("#start", "DESC");
	}


	/**
     * След подготовка на тулбара на единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$changeUrl = array($mvc, 'changeState', $data->rec->id);
    	if($data->rec->state == 'active'){
    		$data->toolbar->addBtn('Затваряне', $changeUrl, 'ef_icon=img/16/lock.png,warning=Искате ли да затворите линията?,title=Затваряне на линията');
    	}
    	
    	if($data->rec->state == 'closed' && $data->rec->start >= dt::today()){
    		$data->toolbar->addBtn('Активиране', $changeUrl, 'ef_icon=img/16/lock_unlock.png,warning=Искате ли да активирате линията?,title=Отваряне на линията');
    	}
    }
    
    
    /**
     * Екшън за отваряне/затваряне на линия
     */
    function act_ChangeState()
    {
    	$this->requireRightFor('write');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	expect($rec->state == 'active' || $rec->state == 'closed');
    	expect($rec->start >= dt::today());
    	
    	$rec->state = ($rec->state == 'active') ? 'closed' : 'active';
    	$this->save($rec);
    	$msg = ($rec->state == 'active') ? tr('Линията е отворена успешно') : tr('Линията е затворена успешно');
    	
    	return Redirect(array($this, 'single', $rec->id), FALSE, $msg);
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
	    	if(!$rec->title){
	    		$rec->title = $mvc->getDefaultTitle($rec);
	    	}
	    	
	    	$rec->isRepeated = 'no';
	    	if($rec->start < dt::now()){
	    		$form->setError('start', 'Не може да се създаде линия в миналото!');
	    	}
    	}
    }
    
    
    /**
     * Дефолт заглавието на линията
     * @param stdClass $rec
     * @return $string
     */
    private function getDefaultTitle($rec)
    {
    	$vehicle = ($rec->vehicleId) ? trans_Vehicles::getTitleById($rec->vehicleId) : NULL;
	    
    	return $rec->start . "/" . $rec->destination . (($vehicle) ? "/" . $vehicle : '');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-single']){
    		$row->header = $mvc->singleTitle . " №<b>{$mvc->getHandle($rec->id)}</b> ({$row->state})";
    	}
    	
    	$attr['class'] = "linkWithIcon";
    	if($rec->vehicleId && trans_Vehicles::haveRightFor('read', $rec->vehicleId)){
    		$attr['style'] = "background-image:url('" . sbf('img/16/tractor.png', "") . "');";
    	 	$row->vehicleId = ht::createLink($row->vehicleId, array('trans_Vehicles', 'single', $rec->vehicleId), NULL, $attr);
    	}
    	
    	if($rec->forwarderId && crm_Companies::haveRightFor('read', $rec->forwarderId)){
    		$attr['style'] = "background-image:url('" . sbf('img/16/office-building.png', "") . "');";
	        $row->forwarderId = ht::createLink($row->forwarderId, array('crm_Companies', 'single', $rec->forwarderId), NULL, $attr);
    	}
    	
    	if($rec->forwarderPersonId && crm_Persons::haveRightFor('read', $rec->forwarderPersonId)){
    		$attr['style'] = "background-image:url('" . sbf('img/16/vcard.png', "") . "');";
    	 	$row->forwarderPersonId = strip_tags($row->forwarderPersonId);
    		$row->forwarderPersonId = ht::createLink($row->forwarderPersonId, array('crm_Persons', 'single', $rec->forwarderPersonId), NULL, $attr);
    	}
    	
    	$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    }
    
    
	/**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        expect($rec = $this->fetch($id));
        
        $row = (object)array(
            'title'    => $rec->title,
            'authorId' => $rec->createdBy,
            'author'   => $this->getVerbal($rec, 'createdBy'),
            'state'    => $rec->state,
            'recTitle' => $rec->title,
        );
        
        return $row;
    }
    
    
	/**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('trans_LinesFolderCoverIntf');
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $folderClass = doc_Folders::fetchCoverClassName($folderId);
    	
        return cls::haveInterface('trans_LinesFolderCoverIntf', $folderClass);
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	$tpl->push('trans/tpl/LineStyles.css', 'CSS');
    }
    
    
	/**
     * Връща само активните транспортни линии
     */
    static function makeArray4Select($fields = NULL, $where = "", $index = 'id', $tpl = NULL)
    {
    	$options = array();
    	$query = static::getQuery();
    	if(strlen($where)){
    		$query->where = $where;
    	}
    	$query->where("state = 'active'");
    	
    	while($rec = $query->fetch()){
    		$options[$rec->id] = static::getTitleById($rec->id);
    	}
    	
    	return $options;
    }
    
    
    /**
     * Дали има свързано подотчетно лице към линията
     * @param int $id - ид на линията
     * @return boolean
     */
    public static function hasForwarderPersonId($id)
    {
    	expect($rec = static::fetch($id));
    	
    	return isset($rec->forwarderPersonId);
    }
    
    
    /**
     * Създава и затваря нови транспортни линии
     */
    function cron_CreateNewLines()
    {
    	$now = dt::now();
    	$query = $this->getQuery();
    	$query2 = clone $query;
    	$query->where("#state = 'active'");
    	$query->where("#start < '{$now}'");
    	
    	// Затварят се всички отворени линии, с начало в миналото
    	while($rec = $query->fetch()){
    		$rec->state = 'closed';
    		$this->save($rec);
    	}
    	
    	
    	// Намират се затворените линии, които не са повторени и
    	// имат повторение и не са повторени
    	$query2->where("#state = 'closed'");
    	$query2->where("#repeat IS NOT NULL");
    	$query2->where("#isRepeated = 'no'");
    	while($rec = $query2->fetch()){
    		
    		// Генерира се новата линия
    		$newRec = $this->getNewLine($rec);
    		$this->save($newRec);
    		
    		// Линията се отбелязва като повторена
    		$rec->isRepeated = 'yes';
    		$this->save($rec);
    	}
    }
    
    
    /**
     * Изпълнява се преди запис
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	// Специално поле, кеото го има само ако се създава от крон
    	if($rec->_createdBy){
    		
    		// doc_DocumentPlg слага за createdBy '-1', така запазваме на
    		// новата линия, за createdBy този, който е създал първата
    		$rec->createdBy = $rec->_createdBy;
    	}
    }
    
    
    /**
     * Създава нова линия възоснова на стара
     * @param stdClass $rec - старата линия
     * @return stdClass $newRec - Новата линия
     */
    private function getNewLine($rec)
    {
    	$newRec = new stdClass();
    	$newRec->destination 	   = $rec->destination;
    	$newRec->repeat            = $rec->repeat;
    	$newRec->_createdBy        = $rec->createdBy;
    	$newRec->folderId          = $rec->folderId;
    	$newRec->vehicleId 		   = $rec->vehicleId;
    	$newRec->forwarderId 	   = $rec->forwarderId;
    	$newRec->forwarderPersonId = $rec->forwarderPersonId;
    	$newRec->isRepeated 	   = 'no';
    	$newRec->start 			   = dt::addSecs($newRec->repeat, $newRec->start);
    	$newRec->title 			   = $this->getDefaultTitle($newRec);
    	$newRec->state 			   = 'active';
    	
    	return $newRec;
    }
    
    
	/**
     * Извиква се след setUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$conf = core_Packs::getConfig('trans');
    	$period = $conf->TRANS_LINES_CRON_INTERVAL / 60;
    	
        $rec = new stdClass();
        $rec->systemId    = "CreateNewLines";
        $rec->description = "Затваря и създава нови транспортни линии";
        $rec->controller  = "trans_Lines";
        $rec->action      = "CreateNewLines";
        $rec->period      = $period;
        $rec->offset 	  = 0;
        $rec->delay 	  = 0;
        $rec->timeLimit   = 100;
        
        $Cron = cls::get('core_Cron');
        if($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да приключва и да създава нови транспортни линии.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да приключва и да създава нови транспортни линии.</li>";
        }
    }
}
