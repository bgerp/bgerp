<?php


/**
 * Клас 'store_InventoryNotes'
 *
 * Мениджър за документ за инвентаризация на склад
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_InventoryNotes extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Протоколи за инвентаризация';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Ivn';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,store';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,store';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,store';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Протокол за инвентаризация';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/shipment.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.8|Логистика";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_Wrapper,doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, plg_Search, doc_ActivatePlg';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'store_InventoryNoteSummary,store_InventoryNoteDetails';
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'store_InventoryNoteSummary';
   
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/InventoryNote/SingleLayout.shtml';
    

    /**
     * Да се забрани ли кеширането на документа
     */
    public $preventCache = TRUE;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior,title=Документ,storeId,folderId,createdOn,createdBy,modifiedOn,modifiedBy';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('valior', 'date', 'caption=Вальор, mandatory');
    	$this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад, mandatory');
    	$this->FLD('folders', 'keylist(mvc=doc_Folders,select=title)', 'caption=Папки');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setDefault('valior', dt::today());
    	
    	$form->setDefault('storeId', doc_Folders::fetchCoverId($form->rec->folderId));
    	$form->setSuggestions('folders', array('' => '') + $mvc->getFolderSuggestions());
    	
    	if(isset($form->rec->id)){
    		$form->setReadOnly('storeId');
    	} else {
    		$form->FLD('charge', 'enum(owner=Не,responsible=Да)', 'caption=Начет МОЛ,maxRadio=2');
    		$form->setDefault('charge', 'owner');
    	}
    }
    
    
    /**
     * Връща подходящи опции за избор на папки
     * 
     * @return array $options - Опции за избор
     */
    private function getFolderSuggestions()
    {
    	$categoryClassId = cat_Categories::getClassId();
    	
    	$query = doc_Folders::getQuery();
    	$contragents = core_Classes::getOptionsByInterface('cat_ProductFolderCoverIntf', 'title');
    	$contragents = array_keys($contragents);
    	$query->in('coverClass', $contragents);
    	$query->where("#state != 'rejected'");
    	doc_Folders::restrictAccess($query);
    	
    	$categories = $contragents = array();
    	while($rec = $query->fetch()){
    		if($rec->coverClass == $categoryClassId){
    			$arr = &$categories;
    		} else {
    			$arr = &$contragents;
    		}
    		
    		$arr[$rec->id] = doc_Folders::getTitleById($rec->id, FALSE);
    	}
    	
    	$categories = array('c' => (object)array('group' => TRUE, 'title' => tr('Категории'))) + $categories;
    	$contragents = array('co' => (object)array('group' => TRUE, 'title' => tr('Контрагенти'))) + $contragents;
    	$options = $categories + $contragents;
    	
    	return $options;
    }
    
    
    /**
     * Можели документа да се добави в посочената папка
     * 
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
    	$folderClass = doc_Folders::fetchCoverClassName($folderId);
    	
    	return ($folderClass == 'store_Stores') ? TRUE : FALSE;
    }
    
    
    /**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
    	expect($rec = $this->fetch($id));
    	$title = $this->getRecTitle($rec);
    
    	$row = (object)array(
    			'title'    => $title,
    			'authorId' => $rec->createdBy,
    			'author'   => $this->getVerbal($rec, 'createdBy'),
    			'state'    => $rec->state,
    			'recTitle' => $title
    	);
    
    	return $row;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(get_called_class());
    	 
    	return tr("|{$self->singleTitle}|* №") . $rec->id;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
    	core_App::setTimeLimit(300);
    	$products = $mvc->getProductsFromBalance($rec);
    	$now = dt::now();
    	
    	foreach ($products as $pRec){
    		$dRec = (object)array('noteId'     => $rec->id,
    							  'folderId'   => $pRec->folderId,
    							  'productId'  => $pRec->productId,
    							  'blQuantity' => $pRec->quantity,
    							  'charge'     => $rec->charge,
    							  'modifiedOn' => $now,
    		);
    	
    		store_InventoryNoteSummary::save($dRec);
    	}
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if($data->rec->state != 'rejected'){
    		if($mvc->haveRightFor('single', $data->rec->id)){
    			$url = array($mvc, 'single', $data->rec->id);
    			$url['Printing'] = 'yes';
    			$url['Blank'] = 'yes';
    			 
    			$data->toolbar->addBtn('Бланка', $url, 'ef_icon = img/16/blueprint.png,title=Разпечатване на бланката,target=_blank');
    		}
    	}
    }
    
    
    /**
     * Преди подготовка на сингъла
     */
    protected static function on_BeforePrepareSingle(core_Mvc $mvc, &$res, $data)
    {
    	if(Request::get('Blank', 'varchar')){
    		Mode::set('blank');
    	}
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$rec = &$data->rec;
    	$row = &$data->row;
    	
    	$ownCompanyData = crm_Companies::fetchOwnCompany();
    	$row->MyCompany = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
    	$row->MyCompany = transliterate(tr($row->MyCompany));
    	$row->MyAddress = cls::get('crm_Companies')->getFullAdress($ownCompanyData->companyId, TRUE)->getContent();
 		
    	$toDate = dt::addDays(-1, $rec->valior);
    	$toDate = dt::verbal2mysql($toDate, FALSE);
    	$row->toDate = $mvc->getFieldType('valior')->toVerbal($toDate);
    	
    	if($storeLocationId = store_Stores::fetchField($data->rec->storeId, 'locationId')){
    		$row->storeAddress = crm_Locations::getAddress($storeLocationId);
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	$tpl->push('store/tpl/css/styles.css', 'CSS');
    	if(!Mode::is('printing') && !Mode::is('text', 'xhtml') && !Mode::is('pdf')){
    		$tpl->push('store/js/InventoryNotes.js', 'JS');
    		jquery_Jquery::run($tpl, "noteActions();");
    	}
    }
    
    
    /**
     * Масив с артикулите срещани в счетоводството
     * 
     * @param stClass $rec
     * @return array
     * 		o productId - ид на артикул
     * 	    o folderId  - в коя папка е артикула
     *  	o quantity  - к-во
     */
    private function getProductsFromBalance($rec)
    {
    	$res = array();
    	
    	// Търсим артикулите от два месеца назад
    	$from = dt::addMonths(-2, $rec->valior);
    	$from = dt::verbal2mysql($from, FALSE);
    	$to = dt::addDays(-1, $rec->valior);
    	$to = dt::verbal2mysql($to, FALSE);
    	
    	// Изчисляваме баланс за подадения период за склада
    	$storeItemId = acc_items::fetchItem('store_Stores', $rec->storeId)->id;
    	$Balance = new acc_ActiveShortBalance(array('from' => $from, 'to' => $to, 'accs' => '321', 'cacheBalance' => FALSE, 'item1' => $storeItemId));
    	$bRecs = $Balance->getBalance('321');
    	
    	$productPositionId = acc_Lists::getPosition('321', 'cat_ProductAccRegIntf');
    	
    	// Подготвяме записите в нормален вид
    	if(is_array($bRecs)){
    		foreach ($bRecs as $bRec){
    			$productId = acc_Items::fetchField($bRec->{"ent{$productPositionId}Id"}, 'objectId');
    			$res[$productId] = (object)array("productId" => $productId,
    											 "folderId"    => cat_Products::fetchField($productId, 'folderId'),
    								   			 "quantity"  => $bRec->blQuantity,);
    		}
    	}
    	
    	// Връщаме намерените артикули
    	return $res;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	$key = self::getCacheKey($rec);
    	core_Cache::remove($mvc->className, $key);
    }
    
    
    /**
     * Връща ключа за кеширане на данните
     * 
     * @param stdClass $rec - запис
     * @return string $key  - уникален ключ
     */
    public static function getCacheKey($rec)
    {
    	// Подготвяме ключа за кеширане
    	$cu = core_Users::getCurrent();
    	$lg = core_Lg::getCurrent();
    	$isNarrow = (Mode::is('screenMode', 'narrow')) ? TRUE : FALSE;
    	$key = "ip{$cu}|{$lg}|{$rec->id}|{$isNarrow}|";
    	
    	// Връщаме готовия ключ
    	return $key;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     * @param array $fields
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($fields['-list'])){
    		$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    		$row->title = $mvc->getLink($rec->id, 0);
    	}
    }
}