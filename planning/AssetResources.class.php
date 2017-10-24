<?php



/**
 * Мениджър на Оборудвания
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_AssetResources extends core_Master
{
    
	
	/**
     * Заглавие
     */
    public $title = 'Оборудване';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, planning_Wrapper, plg_State2, plg_Search';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, planningMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, planningMaster';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, planningMaster';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, planning';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'fullName,groupId,departments,quantity=К-во,createdOn,createdBy,state';

    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'fullName';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Оборудване';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'protocolId';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleAssetResource.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'name, code, groupId, departments, protocolId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('name', 'varchar', 'caption=Име,mandatory');
    	$this->FLD('groupId', 'key(mvc=planning_AssetGroups,select=name,allowEmpty)', 'caption=Група,mandatory,silent');
    	$this->FLD('code', 'varchar(16)', 'caption=Код,mandatory');
    	$this->FLD('protocolId', 'key(mvc=accda_Da,select=id)', 'caption=Протокол за пускане в експлоатация,silent,input=hidden');
    	$this->FLD('departments', 'keylist(mvc=hr_Departments,select=name,makeLinks)', 'caption=Структура');
    	$this->FLD('quantity', 'int', 'caption=Kоличество,notNull,value=1');
    	$this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
    	$this->FNC('fullName', 'varchar', 'caption=Име');
    	
    	$this->setDbUnique('code');
    	$this->setDbUnique('protocolId');
    }
    
    
    /**
     * След изчисление на пълното име
     */
    protected static function on_CalcFullName($mvc, &$rec)
    {
    	$rec->fullName = "{$rec->name} ($rec->code)";
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	
    	if(isset($form->rec->protocolId)){
    		$daTitle = accda_Da::fetchField($form->rec->protocolId, 'title');
    		$form->setDefault('name', $daTitle);
    		$form->info = tr('От') . " " . accda_Da::getHyperLink($form->rec->protocolId, TRUE);
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->STATE_CLASS = "state-{$rec->state}";
    	if(isset($rec->protocolId)){
    		$row->protocolId = accda_Da::getHyperlink($rec->protocolId, TRUE);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec)){
    		if(isset($rec->protocolId)){
    			$state = accda_Da::fetchField($rec->protocolId, 'state');
    			if($state != 'active'){
    				$requiredRoles = 'no_one';
    			} else {
    				if($mvc->fetch("#protocolId = {$rec->protocolId}")){
    					$requiredRoles = 'no_one';
    				}
    			}
    		}
    	}
    	
    	if($action == 'delete' && isset($rec)){
    		if(isset($rec->lastUsedOn)){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->showFields = 'search,groupId';
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    
    	if($data->listFilter->rec->groupId){
    		$data->query->where("#groupId = {$data->listFilter->rec->groupId}");
    	}
    }
    
    
    /**
     * Избор на наличното оборудване в подадената папка
     * 
     * @param int $folderId - папка
     * @return array $res   - налично оборудване
     */
    public static function getAvailableAssets($folderId)
    {
    	$departmentId = hr_Departments::fetchField("#folderId = {$folderId}", 'id');
    	
    	$res = array();
    	$query = self::getQuery();
    	$query->where("#departments IS NULL || #departments LIKE '%|{$departmentId}|%'");
    	while($rec = $query->fetch()){
    		$res[$rec->id] = $rec->fullName;
    	}
    	
    	return $res;
    }
}