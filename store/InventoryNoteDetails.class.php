<?php


/**
 * Клас 'store_InventoryNoteDetails'
 *
 * Детайли на мениджър на детайлите на протоколите за инвентаризация (@see store_InventoryNotes)
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_InventoryNoteDetails extends doc_Detail
{
	
	
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола за инвентаризация';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'артикул за опис';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'store_Wrapper, plg_AlignDecimals2, plg_RowTools2, plg_PrevAndNext, plg_SaveAndNew, plg_Modified,plg_Created,plg_Sorting,plg_Search';
    
    
    /**
     * Кой има достъп до листовия изглед?
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, storeMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, storeMaster';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, storeMaster';
    
        
    /**
     * Активен таб
     */
    public $currentTab = 'Документи->Инвентаризация';
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'noteId, productId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId=Мярка,packQuantity=Установено,modifiedOn,modifiedBy';
    
    
    /**
     * По подразбиране колко резултата да показва на страница
     */
    public $listItemsPerPage = 40;
    
    
    /**
     * Работен кеш
     */
    protected $cache = array();
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'productId,packagingId';
    
    
    /**
     * Име на полето за търсене
     */
    public $searchInputField = 'searchDetail';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=store_InventoryNotes)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory,silent,removeAndRefreshForm=packagingId|quantity|quantityInPack|packQuantity|batch');
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory,smartCenter,tdClass=small-field nowrap,removeAndRefreshForm=quantity|quantityInPack|packQuantity|batch,remember,silent');
        $this->FLD('quantity', 'double', 'caption=Количество,input=none');
        $this->FLD('quantityInPack', 'double(decimals=2)', 'input=hidden,column=none');
        $this->FNC('packQuantity', 'double(decimals=2,min=0)', 'caption=Количество,input');
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    protected static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
    	if (!isset($rec->quantity) || !isset($rec->quantityInPack)) return;
    
    	$rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Подготовка на Детайлите
     */
    function prepareDetail_($data)
    {
    	$data->TabCaption = 'Въвеждане';
    	$data->Tab = 'top';
    	 
    	$tab = Request::get($data->masterData->tabTopParam, 'varchar');
    	if($tab == get_called_class()){
    		parent::prepareDetail_($data);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)){
    		$state = store_InventoryNotes::fetchField($rec->noteId, 'state');
    		if($state != 'draft'){
    			$requiredRoles = 'no_one';
    		} else {
    			if(!store_InventoryNotes::haveRightFor('edit', $rec->noteId)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareEditForm_($data)
    {
    	$data = parent::prepareEditForm_($data);
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	// Кеш на предишния запис
    	$lastId = Mode::get("InventoryNoteLastSavedRow{$rec->noteId}");
    	if($lastId && $lastId != $rec->id){
    		if($lastRec = $this->fetch($lastId)){
    			$row = $this->recToVerbal($lastRec);
    			$info = new core_ET(tr("|*<b>|Предишно|*</b>: {$row->productId} {$row->packQuantity} {$row->packagingId} [#tools#]"));
    			$info->replace($row->_rowTools->renderHtml(2), 'tools');
    			$form->info = $info;
    		}
    	}
    	
    	$form->FNC('keepProduct', 'enum(yes=Да,no=Не)', 'caption=Помни артикула,after=packQuantity,input,maxRadio=2,remember');
    	$permanentName = cls::getClassName($this) . "_keepProduct";
    	$permanentName = (Mode::get($permanentName)) ? Mode::get($permanentName) : 'no';
    	$form->setDefault('keepProduct', $permanentName);
    	
    	$products = cat_Products::getByProperty('canStore');
    	$form->setOptions('productId', array('' => '') + $products);
    	
    	$defProduct = Mode::get("InventoryNoteNextProduct{$rec->noteId}");
    	$form->setDefault('productId', $defProduct);
    	
    	// Рендиране на опаковките
    	if(isset($rec->productId)){
    		$packs = cat_Products::getPacks($rec->productId);
    		$form->setOptions('packagingId', $packs);
    		$form->setDefault('packagingId', key($packs));
    	} else {
    		$form->setField('packagingId', 'input=none');
    	}
    	
    	return $data;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = $form->rec;
    	
    	if($form->notMandatoryQ !== TRUE){
    		$form->setField('packQuantity', 'mandatory');
    	}
    	
    	if($form->isSubmitted()){
    		
    		// Проверка на к-то
    		if(!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)){
    			$form->setError('packQuantity', $warning);
    		}
    		
    		$productInfo = cat_Products::getProductInfo($rec->productId);
    		$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
    		$rec->quantity = (isset($rec->packQuantity)) ? $rec->packQuantity * $rec->quantityInPack : NULL;
    	}
    	
    	$mvc->invoke('AfterAfterInputEditForm', array($form));
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
    	// Подсигуряване че запис и нов го има дори и при редакция
    	if(isset($data->form->rec->id)) {
    		$data->form->toolbar->addSbBtn('Запис и Нов', 'save_n_new', NULL, array('id'=>'saveAndNew', 'order'=>'1', 'ef_icon'=>'img/16/save_and_new.png', 'title'=>'Запиши документа и създай нов'));
    	}
    }
    
    
    /**
     * Логика за определяне къде да се пренасочва потребителския интерфейс.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareRetUrl($mvc, $data)
    {
    	if(!isset($data->form) || !$data->form->isSubmitted()) return;
    	
    	if($data->form->cmd == 'save_n_new'){
    		$rec = $data->form->rec;
    		
    		// Ако не е избрано за пазене на артикула, се цикли в следващия в списъка
    		if($rec->keepProduct != 'yes'){
    			$cache = store_InventoryNotes::fetchField($rec->noteId, 'cache');
    			$cache = (array)json_decode($cache);
    			
    			$keys = array_values($cache);
    			$i = array_search($rec->productId, $keys);
    			$key = $i + 1;
    			
    			$newProductId = (isset($keys[$key])) ? $keys[$key] : NULL;
    			Mode::setPermanent("InventoryNoteNextProduct{$rec->noteId}", $newProductId);
    		} else {
    			Mode::setPermanent("InventoryNoteNextProduct{$rec->noteId}", $rec->productId);
    		}
    		
    		unset($data->retUrl['id']);
    		unset($data->retUrl['packagingId']);
    	}
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if(is_null($rec->quantity)){
    		$mvc->delete($rec->id);
    	}
    	
    	$summeryId = store_InventoryNoteSummary::force($rec->noteId, $rec->productId);
    	store_InventoryNoteSummary::recalc($summeryId);
    	
    	Mode::setPermanent("InventoryNoteLastSavedRow{$rec->noteId}", $rec->id);
    	$mvc->cache[$rec->noteId] = $rec->noteId;
    }
    
    
    /**
     * След изтриване на запис
     */
    public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
    	foreach ($query->getDeletedRecs() as $id => $rec) {
    		$summeryId = store_InventoryNoteSummary::force($rec->noteId, $rec->productId);
    		store_InventoryNoteSummary::recalc($summeryId);
    		
    		$mvc->cache[$rec->noteId] = $rec->noteId;
    		Mode::setPermanent("InventoryNoteLastSavedRow{$rec->noteId}", NULL);
    	}
    }
    
    
    /**
     * Изчиства записите, заопашени за запис
     */
    public static function on_Shutdown($mvc)
    {
    	if(count($mvc->cache)){
    		foreach ($mvc->cache as $noteId) {
    			store_InventoryNotes::invalidateCache($noteId);
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
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->productId = cat_Products::getShortHyperlink($rec->productId);
    	deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	if($data->masterData->rec->state == 'rejected') return;
    	
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png,title=Филтриране на данните');
    	$data->listFilter->FLD('threadId', 'key(mvc=doc_Threads)', 'input=hidden');
    	$data->listFilter->FLD("{$data->masterData->tabTopParam}", 'varchar', 'input=hidden');
    	$data->listFilter->setDefault("{$data->masterData->tabTopParam}", get_called_class());
    	$data->listFilter->setDefault('threadId', $data->masterData->rec->threadId);
    	$data->listFilter->showFields = $mvc->searchInputField;
    	$data->listFilter->view = 'horizontal';
    	
    	$data->query->orderby('id', 'DESC');
    }
}