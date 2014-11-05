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
	public $interfaces = 'mp_ResourceAccRegIntf';
	
	
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
    public $listFields = 'tools=Пулт,title,createdOn,createdBy';
    
    
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
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Наименование,mandatory');
    	$this->FLD('type', 'enum(equipment=Оборудване,labor=Труд,material=Материал)', 'caption=Вид,mandatory,silent');
    	
    	// Поставяме уникален индекс
    	$this->setDbUnique('title');
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
    		$data->toolbar->removeBtn('btnAdd');
    		
    		$type = Request::get('type', 'enum(equipment=Оборудване,labor=Труд,material=Материал)');
    		$data->toolbar->addBtn('Нов запис', array($mvc, 'add', "type" => $type, 'ret_url' => TRUE), "id=btnAdd,order=10", 'ef_icon = img/16/star_2.png');
    	}
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$type = Request::get('type', 'enum(equipment,labor,material)');
    	$typeVerbal = $mvc->getFieldType('type')->toVerbal($type);
    	$mvc->currentTab = "Ресурси->{$typeVerbal}";
    	
    	$data->query->where("#type = '{$type}'");
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
}