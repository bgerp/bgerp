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
    public $interfaces = 'doc_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, trans_Wrapper, plg_Sorting, plg_Printing, plg_LastUsedKeys,
                    doc_DocumentPlg, bgerp_plg_Blank, doc_plg_BusinessDoc2, doc_ActivatePlg, plg_Search';

    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'vehicleId';
    
    
    /**
     * По кои олета ще се търси
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
     * Детайла, на модела
     */
    public $details = 'Shipments=store_ShipmentOrders,Receipts=store_Receipts';
    
    
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
     * Групиране на документите
     */
    public $newBtnGroup = "4.5|Логистика";
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Заглавие,width=100%');
    	$this->FLD('start', 'dateTime', 'caption=Начало, mandatory');
    	$this->FLD('destination', 'varchar(255)', 'caption=Дестинация,mandatory');
    	$this->FLD('repeat', 'time(suggestions=1 ден|1 седмица|1 месец)', 'caption=Повторение,mandatory');
    	$this->FLD('state', 'enum(draft=Чернова,active=Активен,rejected=Оттеглен,closed=Затворен)', 'caption=Състояние');
    	$this->FLD('isRepeated', 'enum(yes=Да,no=Не)', 'caption=Генерирано на повторение,maxRadio=2,columns=2');
    	$this->FLD('vehicleId', 'key(mvc=trans_Vehicles,select=name,allowEmpty)', 'caption=Допълнително->Превозвач');
    	$this->FLD('forwarderId', 'key(mvc=crm_Companies,select=name,group=suppliers,allowEmpty)', 'caption=Допълнително->Транспортна фирма');
    	$this->FLD('forwarderPersonId', 'key(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Допълнително->Подочетно лице');
    }
    
    
	/**
     * Малко манипулации след подготвянето на формата за филтриране
     */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		$data->listFilter->showFields = 'search';
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
	}


	/**
     * След подготовка на тулбара на единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if($data->rec->state == 'active'){
    		$data->toolbar->addBtn('Затваряне', array($mvc, 'changeState', $data->rec->id), 'ef_icon=img/16/lock.png,warning=Искатели да затворите линията ?');
    	}
    	
    	if($data->rec->state == 'closed'){
    		$data->toolbar->addBtn('Активиране', array($mvc, 'changeState', $data->rec->id), 'ef_icon=img/16/lock_unlock.png,warning=Искатели да активирате линията ?');
    	}
    }
    
    
    /**
     * Екшън за отваряне затваряне на линия
     */
    function act_ChangeState()
    {
    	$this->requireRightFor('write');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	expect($rec->state == 'active' || $rec->state == 'closed');
    	
    	$rec->state = ($rec->state == 'active') ? 'closed' : 'active';
    	$this->save($rec);
    	
    	return Redirect(array($this, 'single', $rec->id));
    }
    
    
	/**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$data->form->setDefault('isRepeated', 'yes');
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
	    	if(!$rec->title){
	    		$vehicle = ($rec->vehicleId) ? trans_Vehicles::getTitleById($rec->vehicleId) : NULL;
	    		$rec->title = $rec->start . "/" . $rec->destination . (($vehicle) ? "/" . $vehicle : '');
	    	}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-single']){
    		$row->header = $mvc->singleTitle . " №<b>{$mvc->getHandle($rec->id)}</b> ({$row->state})";
    	}
    	
    	if($rec->vehicleId && trans_Vehicles::haveRightFor('read', $rec->vehicleId)){
    		$attr['class'] = "linkWithIcon";
	        $attr['style'] = "background-image:url(' . sbf('img/16/tractor.png') . ');";
    	 	$row->vehicleId = ht::createLink($row->vehicleId, array('trans_Vehicles', 'single', $rec->vehicleId));
    	}
    	
    	if($rec->forwarderId && crm_Companies::haveRightFor('read', $rec->forwarderId)){
    	 	$row->forwarderId = ht::createLink($row->forwarderId, array('crm_Companies', 'single', $rec->forwarderId));
    	}
    	
    	if($rec->forwarderPersonId && crm_Persons::haveRightFor('read', $rec->forwarderPersonId)){
    	 	$row->forwarderPersonId = ht::createLink($row->forwarderPersonId, array('crm_Persons', 'single', $rec->forwarderPersonId));
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
            'recTitle' => $this->getRecTitle($rec),
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
}