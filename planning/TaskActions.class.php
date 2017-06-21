<?php


/**
 * Клас 'planning_TaskActions' - Операции със задачи
 *
 * 
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_TaskActions extends core_Manager
{
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Регистър на прогреса по производствените операции';
	
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,planning';
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canWrite = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'planning_Wrapper, plg_AlignDecimals2, plg_Search, plg_Created, plg_Modified, plg_Sorting';
	
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,type,action,serial,productId,taskId,jobId,quantity, quantityInPack,employees,fixedAsset,modifiedOn,modifiedBy';
    		
    		
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'productId,taskId,jobId,serial,employees,fixedAsset';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 40;
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'serial,employees,fixedAsset';
    
    
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('type', 'enum(input=Влагане,product=Произвеждане,waste=Отпадък,start=Пускане,scrap=Брак)', 'input=none,mandatory,caption=Действие');
		$this->FLD('action', 'enum(reject=Оттегляне,add=Добавяне,restore=Възстановяване,edit=Редакция)');
		$this->FLD('productId', 'key(mvc=cat_Products)', 'input=none,mandatory,caption=Артикул');
		$this->FLD('taskId', 'key(mvc=planning_Tasks)', 'input=none,mandatory,caption=Задача');
		$this->FLD('jobId', 'key(mvc=planning_Jobs)', 'input=none,mandatory,caption=Задание');
		$this->FLD('quantity', 'double', 'input=none,mandatory,caption=Количество');
		$this->FLD("packagingId", 'key(mvc=cat_UoM,select=shortName)');
		$this->FLD("quantityInPack", 'double');
		
		$this->FLD('serial', 'varchar(32)', 'input=none,mandatory,caption=С. номер,smartCenter');
		$this->FLD('employees', 'keylist(mvc=crm_Persons,select=id)', 'input=none,mandatory,caption=Служители');
		$this->FLD('fixedAsset', 'key(mvc=planning_AssetResources,select=code)', 'input=none,mandatory,caption=Обордуване');
	
		$this->setDbIndex('taskId');
		$this->setDbIndex('taskId,type');
		$this->setDbIndex('productId');
	}
	
	
	/**
	 * Преди рендиране на таблицата
	 */
	protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
	{
		unset($data->listFields['action']);
		$rows = &$data->rows;
		if(!count($rows)) return;
		
		foreach ($rows as $id => $row){
			$rec = $data->recs[$id];
				
			$class = ($rec->type == 'input') ? 'row-added' : (($rec->type == 'product') ? 'state-active' : (($rec->type == 'start') ? 'state-stopped' : 'row-removed'));
			if($rec->action == 'reject' || $rec->action == 'restore' || $rec->action == 'edit'){
				$row->type = "{$row->type} <small>({$row->action})</small>";
				if($rec->action == 'restore'){
					$class = 'state-restore';
				} elseif($rec->action == 'edit') {
					$class = 'state-closed';
				} else {
					$class = '';
				}
			}
			
			if($class != ''){
				$row->ROW_ATTR['class'] = $class;
			} else {
				$row->ROW_ATTR['style'] = 'background-color:rgba(204,102,102,.6)';
			}
			
			if($rec->type == 'scrap' && $rec->action == 'add'){
				$row->ROW_ATTR['style'] = 'background-color:white';
			}
			
			$row->productId = cat_Products::getShortHyperlink($rec->productId);
			$row->jobId = planning_Jobs::getLink($rec->jobId, 0);
			$row->taskId = planning_Tasks::getLink($rec->taskId, 0);
			$row->quantity .= " " . cat_UoM::getShortName($rec->packagingId);
			
			if(isset($rec->employees)){
				$row->employees = planning_drivers_ProductionTaskDetails::getVerbalEmployees($rec->employees);
			}
		}
	}
	
	
	/**
	 * Записва действие по задача
	 * 
	 * @param int $taskId                           - ид на задача
	 * @param int $productId                        - ид на артикул
	 * @param add|reject|restore $action            - вид на действието
	 * @param product|input|waste|start|scrap $type - вид на действието
	 * @param int $packagingId                      - опаковка
	 * @param int $quantity                         - количество
	 * @param int $serial                           - сериен номер
	 * @param text $employees                       - работници
	 * @param text $fixedAsset                      - оборудване
	 * @return int
	 */
	public static function add($taskId, $productId, $action, $type, $packagingId, $quantity, $quantityInPack, $serial, $employees, $fixedAsset)
	{
		if(!$productId) return;
		
		$taskOriginId = planning_Tasks::fetchField($taskId, 'originId');
		$jobId = doc_Containers::getDocument($taskOriginId)->that;
		
		$rec = (object)array('taskId'         => $taskId,
				             'productId'      => $productId,
							 'action'         => $action,
				             'type'           => $type,
				             'quantity'       => $quantity,
							 'serial'         => $serial,
							 'employees'      => $employees,
							 'fixedAsset'     => $fixedAsset,
							 'quantityInPack' => $quantityInPack,
							 'packagingId'    => $packagingId,
							 'jobId'          => $jobId);
		
		return self::save($rec);
	}
	
	
	/**
	 * Връща количеството произведено по задачи по дадено задание
	 * 
	 * @param int $jobId
	 * @param product|input|waste $type
	 * @return double $quantity
	 */
	public static function getQuantityForJob($jobId, $type)
	{
		expect(in_array($type, array('product', 'input', 'waste', 'start')));
		expect($jobRec = planning_Jobs::fetch($jobId));
		
		$query = self::getQuery();
		$query->EXT('taskState', 'planning_Tasks', 'externalName=state,externalKey=taskId');
		$query->where("#taskState != 'rejected'");
		$query->where("#type = '{$type}'");
		$query->where("#jobId = {$jobId}");
		$query->where("#productId = {$jobRec->productId}");
		$query->show('quantity,action,quantityInPack');
		
		$quantity = 0;
		while($rec = $query->fetch()){
			$sign = ($rec->action == 'reject') ? -1 : 1;
			$q = isset($rec->quantityInPack) ? ($rec->quantity * $rec->quantityInPack) : $rec->quantity;
			
			$quantity += $sign * $q;
		}
		
		if($quantity < 0){
			$quantity = 0;
		}
		
		return $quantity;
	}
	
	
	/**
	 * Подготовка на филтър формата
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareListFilter($mvc, &$data)
	{
		// Добавяне на полета към филтъра
		$data->listFilter->class = 'simpleForm';
		$data->listFilter->FNC('filterType', 'enum(all=Действия,input=Влагане,product=Произвеждане,waste=Отпадък,start=Пускане,reject=Оттегляне,restore=Възстановяване,scrap=Бракуване)', 'caption=Действие');
		$data->listFilter->FNC('assets', 'keylist(mvc=planning_AssetResources,select=code,allowEmpty)', 'caption=Оборудване');
		
		$employees = crm_ext_Employees::getEmployeesWithCode();
		$data->listFilter->showFields = 'search,filterType,assets';
		
		// Ако има служители с кодове, възможност да се избират
		if(count($employees)){
			$data->listFilter->FNC('filterEmployees', 'keylist(mvc=crm_Persons,select=id,allowEmpty)', 'caption=Служители');
			$data->listFilter->setSuggestions('filterEmployees', array('' => '') + $employees);
			$data->listFilter->showFields .= ",filterEmployees";
		} 
		
		$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
		$data->listFilter->setDefault('action', 'all');
		$data->listFilter->input();
		
		// Ако филтъра е събмитнат
		if($filter = $data->listFilter->rec){
			
			// Филтър по действие
			if(isset($filter->filterType) && $filter->filterType != 'all'){
				if($filter->filterType != 'reject' && $filter->filterType != 'restore'){
					$data->query->where("#type = '{$filter->filterType}'");
					$data->query->where("#action = 'add'");
				} else {
					$data->query->where("#action = '{$filter->filterType}'");
				}
			}
			
			// Филтър по служители
			if(isset($filter->filterEmployees)){
				$data->query->likeKeylist('employees', $filter->filterEmployees);
			}
			
			// Филтър по оборудване
			if(isset($filter->assets)){
				$data->query->likeKeylist('fixedAsset', $filter->assets);
			}
		}
		
		$data->query->orderBy('modifiedOn,id', "DESC");
	}
}