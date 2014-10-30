<?php



/**
 * Мениджър на ресурсите свързани с обекти
 *
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mp_ObjectResources extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Ресурси на обекти';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, mp_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,mp';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,mp';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,mp';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,mp';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,mp';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,resourceId,objectId, createdOn,createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Ресурс на обект';
    
    
    /**
     * Активен таб
     */
    public $currentTab = 'Ресурси->Отношения';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('classId', 'class(interface=mp_ResourceSourceIntf)', 'input=hidden,silent');
    	$this->FLD('objectId', 'int', 'input=hidden,caption=Обект,silent');
    	$this->FLD('resourceId', 'key(mvc=mp_Resources,select=title,allowEmpty)', 'caption=Ресурс');
    	
    	// Поставяне на уникални индекси
    	$this->setDbUnique('classId,objectId,resourceId');
    }


    /**
     * Подготвя показването на ресурси
     */
    public function prepareResources(&$data)
    {
    	$data->TabCaption = 'Ресурси';
    	$data->rows = array();
    	 
    	$classId = $data->masterMvc->getClassId();
    	 
    	$query = $this->getQuery();
    	$query->where("#classId = {$classId} AND #objectId = {$data->masterId}");
    	 
    	while($rec = $query->fetch()){
    		$data->rows[$rec->id] = $this->recToVerbal($rec);
    	}
    	 
    	if(!Mode::is('printing')) {
    		if(self::haveRightFor('add', (object)array('classId' => $classId, 'objectId' => $data->masterId))){
    			$data->addUrl = array($this, 'add', 'classId' => $classId, 'objectId' => $data->masterId, 'ret_url' => TRUE);
    		}
    	}
    }
    
    
    /**
     * Рендира показването на ресурси
     */
    public function renderResources(&$data)
    {
    	$tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
    	$classId = $data->masterMvc->getClassId();
    
    	$tpl->append(tr('Ресурси'), 'title');
    	$table = cls::get('core_TableView', array('mvc' => $this));
    
    	$tpl->append($table->get($data->rows, 'resourceId=Ресурс,createdOn=Създадено->На,createdBy=Създадено->На,tools=Пулт'), 'content');
    
    	if($data->addUrl) {
    		$img = "<img src=" . sbf('img/16/add.png') . " width='16' valign=absmiddle  height='16'>";
    		$tpl->append(ht::createLink($img, $data->addUrl, FALSE, 'title=' . tr('Добавяне на нов ресурс')), 'title');
    	}
    
    	return $tpl;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'delete' || $action == 'edit') && isset($rec)){
    		
    		$Class = cls::get($rec->classId);
    		$masterRec = $Class->fetchRec($rec->objectId);
    		
    		// Не може да добавяме запис ако не може към обекта, ако той е оттеглен или ако нямаме достъп до сингъла му
    		if($masterRec->state != 'active' || !$Class->haveRightFor('single', $rec->objectId)){
    			$res = 'no_one';
    		}
    	}
    	 
    	if($action == 'add' && isset($rec)){
    		
    		if(!$Class->canHaveResource($rec->objectId)){
    			$res = 'no_one';
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
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->objectId = cls::get($rec->classId)->getHyperlink($rec->objectId, TRUE);
    	$row->objectId = "<span style='float:left'>{$row->objectId}</span>";
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    }
}