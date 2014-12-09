<?php



/**
 * Мениджър на ресурсите на предприятието
 *
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Ресурси на предприятието
 */
class mp_Resources extends core_Master
{
    
    
	/**
	 * Интерфейси, поддържани от този мениджър
	 */
	public $interfaces = 'mp_ResourceAccRegIntf,acc_RegisterIntf';
	
	
    /**
     * Заглавие
     */
    public $title = 'Ресурси на предприятието';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, plg_Rejected, mp_Wrapper';
    
    
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
    public $canDelete = 'admin,mp';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,mp';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,title,type,createdOn,createdBy,systemId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Поле за еденичен изглед
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Ресурс';
    
    
    /**
     * Шаблон за еденичен изглед
     */
    public $singleLayoutFile = 'mp/tpl/SingleLayoutResource.shtml';
    		
    		
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Наименование,mandatory');
    	$this->FLD('type', 'enum(equipment=Оборудване,labor=Труд,material=Материал)', 'caption=Вид,mandatory,silent');
    	$this->FLD('measureId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Мярка,mandatory');
    	$this->FLD('systemId', 'varchar', 'caption=Системен №,input=none');
    	
    	// Поставяме уникален индекс
    	$this->setDbUnique('title');
    	$this->setDbUnique('systemId');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$file = "mp/csv/Resources.csv";
    	$fields = array(0 => "title", 1 => 'type', '2' => 'systemId');
    	
    	$cntObj = csv_Lib::importOnce($this, $file, $fields);
    	
    	return $cntObj->html;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
    	$rec->createdBy = '-1';
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->FNC('rType', 'enum(all=Всички,equipment=Оборудване,labor=Труд,material=Материал)', 'caption=Тип,placeholder=aa');
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    	$data->listFilter->setDefault('rType', 'all');
    	$data->listFilter->showFields = 'rType';
    	$data->listFilter->view = 'horizontal';
    	
    	$data->listFilter->input();
    	
    	if($type = $data->listFilter->rec->rType){
    		if($type != 'all'){
    			$data->query->where("#type = '{$type}'");
    		}
    	}
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    public static function getItemRec($objectId)
    {
    	$self = cls::get(__CLASS__);
    	$result = NULL;
    
    	if ($rec = $self->fetch($objectId)) {
    		$result = (object)array(
    				'num' => $rec->id,
    				'title' => $rec->title,
    		);
    	}
    
    	return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
    	// @todo!
    }
    
    
    /**
     * Изпълнява се след подготовката на титлата в единичния изглед
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$dQuery = mp_ObjectResources::getQuery();
    	$dQuery->where("#resourceId = {$data->rec->id}");
    	
    	$data->detailRows = $data->detailRecs = array();
    	while($dRec = $dQuery->fetch()){
    		$data->detailRecs[$dRec->id] = $dRec;
    		$data->detailRows[$dRec->id] = mp_ObjectResources::recToVerbal($dRec);
    	}
    }
    
    
    /**
     * След рендиране на еденичния изглед
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$table = cls::get('core_TableView');
    	$detailTpl = $table->get($data->detailRows, 'tools=Пулт,objectId=Обект');
    	$tpl->append($detailTpl, 'DETAILS');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $res
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'delete' || $action == 'reject') && isset($rec)){
    		if(mp_ObjectResources::fetchField("#resourceId = '{$rec->id}'")){
    			$res = 'no_one';
    		}
    	}
    	
    	if(($action == 'edit' || $action == 'delete') && isset($rec)){
    		if($rec->createdBy == '-1'){
    			$res = 'no_one';
    		}
    	}
    }
}