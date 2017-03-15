<?php



/**
 * Листвани артикули
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Листвани артикули
 */
class cat_Listings extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Листвания на артикули';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Листване на артикули";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, cat_Wrapper, doc_ActivatePlg, plg_Search, doc_DocumentPlg, doc_plg_SelectFolder';
                    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'folderId';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Li";
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'cat_ListingDetails';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title, folderId, createdOn, createdBy';
    
    
    /**
     * Кой може да го промени?
     */
    public $canEdit = 'cat,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'cat,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'cat,ceo';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'cat,ceo';
   
    
    /**
     * Поле за връзка към единичния изглед
     */
    public $rowToolsSingleField = 'title';


	/**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'cat/tpl/SingleLayoutListing.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "3.99|Търговия";
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders,crm_ContragentAccRegIntf';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'mandatory,caption=Заглавие');
    	$this->FLD('type', 'enum(canSell=Продаваеми,canBuy=Купуваеми)', 'mandatory,caption=Артикули,notNull,value=canSell');
    	$this->FLD('isPublic', 'enum(yes=Да,no=Не)', 'mandatory,caption=Публичен,input=none');
    	
    	$this->setDbUnique('title');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = $form->rec;
    	
    	if(isset($rec->id)){
    		if(cat_ListingDetails::fetchField("#listId = {$rec->id}")){
    			$form->setReadOnly('type');
    		}
    	}
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$row = new stdClass();
    	$title = $this->getVerbal($rec, 'title');
    	 
    	$row->title    = tr($this->singleTitle) . " \"{$title}\"";
    	$row->authorId = $rec->createdBy;
    	$row->author   = $this->getVerbal($rec, 'createdBy');
    	$row->recTitle = $row->title;
    	$row->state    = $rec->state;
    
    	return $row;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'activate'){
    		if(empty($rec->id)){
    			$requiredRoles = 'no_one';
    		} else {
    			if(!cat_ListingDetails::fetchField("#listId = {$rec->id}")){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if(isset($rec->folderId)){
    		$Cover = doc_Folders::getCover($rec->folderId);
    		$rec->isPublic = ($Cover->haveInterface('crm_ContragentAccRegIntf')) ? 'no' : 'yes';
    	}
    }
    
    
    /**
     * Кешира и връща всички листвани артикули за клиента
     * 
     * @param int|stdClass $listId  - ид на лист
     * @param int|NULL     $storeId - ид на склад
     * @param int|NULL     $limit   - ограничение
     * @return array
     */
    public static function getAll($listId, $storeId = NULL, $limit = NULL)
    {
    	expect($listRec = cat_Listings::fetchRec($listId));
    
    	$instock = NULL;
    	
    	// Ако е зададен склад
    	if(isset($storeId)){
    		
    		// Намиране на всички налични артикули в склада
    		$pQuery = store_Products::getQuery();
    		$pQuery->where("#storeId = {$storeId}");
    		$pQuery->where("#quantity > 0");
    		$pQuery->show('productId');
    		$instock = arr::extractValuesFromArray($pQuery->fetchAll(), 'productId');
    	}
    	
    	// Ако няма наличен кеш за контрагента, извлича се наново
    	if(!isset(self::$cache[$listRec->id])){
    		self::$cache[$listRec->id] =  array();
    			
    		// Кои са листваните артикули за контрагента
    		$query = cat_ListingDetails::getQuery();
    		$query->where("#listId = {$listRec->id}");
    		
    		if(isset($instock) && is_array($instock)){
    			
    			// Артикулите се подреждат така че наличните в склада да са по-напред
    			$instock = implode(',', $instock);
    			$query->XPR('instock', 'int', "(CASE WHEN #productId IN ($instock) THEN 0 ELSE 1 END)");
    			$query->orderBy('instock', 'ASC');
    		} else {
    			$query->orderBy('id', 'ASC');
    		}
    			
    		// Ако има зададен лимит
    		if(isset($limit)){
    			$query->limit($limit);
    		}
    		
    		// Добавя се всеки запис, групиран според типа
    		while($rec = $query->fetch()){
    			$obj = (object)array('productId' => $rec->productId, 'packagingId' => $rec->packagingId, 'reff' => $rec->reff, 'moq' => $rec->moq, 'multiplicity' => $rec->multiplicity);
    			
    			self::$cache[$listRec->id][$rec->id] = $obj;
    		}
    	}
    
    	// Връщане на кешираните данни
    	return self::$cache[$listRec->id];
    }
    
    
    /**
     * Помощна ф-я връщаща намерения код според артикула и опаковката, ако няма опаковка
     * се връща първия намерен код
     *
     * @param mixed $cClass          - ид на клас
     * @param int $cId               - ид на контрагента
     * @param int $productId         - ид на артикул
     * @param int|NULL $packagingId  - ид на опаковка, NULL ако не е известна
     * @return varchar|NULL          - намерения код или NULL
     */
    public static function getReffByProductId($listId, $productId, $packagingId = NULL)
    {
    	// Извличане на всичките листвани артикули
    	$all = self::getAll($listId);
    	
    	// Намират се записите за търсения артикул
    	$res = array_filter($all, function (&$e) use ($productId, $packagingId) {
    		if(isset($packagingId)){
    			if($e->productId == $productId && $e->packagingId == $packagingId){
    				return TRUE;
    			}
    		} else{
    			if($e->productId == $productId){
    				return TRUE;
    			}
    		}
    
    		return FALSE;
    	});
    
    	// Ако има намерен поне един запис се връща кода
    	$firstFound = $res[key($res)];
    	$reff = (is_object($firstFound)) ? $firstFound->reff : NULL;
    
    	// Връща се намерения код
    	return $reff;
    }
    
    
    /**
     * Подредба на записите
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->showFields = 'search';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
    
    	// Сортиране на записите по num
    	$data->query->orderBy('id');
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене, това са името на
     * документа или папката
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
    	// Тук ще генерираме всички ключови думи
    	$detailsKeywords = '';
    
    	// Заявка към детайлите
    	$query = cat_ListingDetails::getQuery();
    	$query->where("#listId  = '{$rec->id}'");
   
    	while ($dRec = $query->fetch()){
    		
    		// взимаме заглавията на продуктите
    		$productTitle = cat_Products::getTitleById($dRec->productId);
    		$detailsKeywords .= " " . plg_Search::normalizeText($productTitle);
    	}
    	
    	// добавяме новите ключови думи към основните
    	$res = " " . $res . " " . $detailsKeywords;
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
    	$rec = $this->fetchRec($id);
    
    	return $this->save($rec);
    }
    
    
    /**
     * Изпълнява се преди оттеглянето на документа
     */
    public static function on_BeforeReject(core_Mvc $mvc, &$res, $id)
    {
    	$rec = $mvc->fetchRec($id);
    	
    	$id1 = cond_Parameters::fetchIdBySysId('salesList');
    	$id2 = cond_Parameters::fetchIdBySysId('purchaseList');
    	
    	$cQuery = cond_ConditionsToCustomers::getQuery();
    	$cQuery->in('conditionId', array($id1, $id2));
    	$cQuery->where("#value = {$rec->id}");
    	
    	$found = array();
    	while($cRec = $cQuery->fetch()){
    		$found[] = "<b>" . cls::get($cRec->cClass)->getTitleById($cRec->cId) . "</b>";
    	}
    	
    	if(count($found)){
    		$implode = implode(', ', $found);
    		core_Statuses::newStatus('Документа не може да се оттегли, защото е избран като търговско условие за|* ' . $implode, 'warning');
    		
    		return FALSE;
    	}
    	
    	
    }
}