<?php
/**
 * Клас 'sales_SalesDetails'
 *
 * Детайли на мениджър на документи за продажба на продукти (@see sales_Sales)
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_SalesDetails extends deals_DealDetail
{
    
    
    /**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Детайли на продажби';


    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'saleId';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'plg_RowTools, plg_Created, sales_Wrapper, plg_RowNumbering, plg_SaveAndNew,
                        plg_AlignDecimals2, plg_Sorting, deals_plg_ImportDealDetailProduct, doc_plg_HidePrices, LastPricePolicy=sales_SalesLastPricePolicy,cat_plg_CreateProductFromDocument';
    
    
    /**
     * Активен таб на менюто
     * 
     * @var string
     */
    public $menuPage = 'Търговия:Продажби';
    
    
    /**
     * Кой има право да чете?
     * 
     * @var string|array
     */
    public $canRead = 'ceo, sales';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'ceo, sales';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'ceo, sales';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'ceo, sales';
    
    
    /**
     * Брой записи на страница
     * 
     * @var integer
     */
    public $listItemsPerPage;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, packQuantity, packPrice, discount, amount, quantityInPack';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';


    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Какви мета данни да изискват продуктите, които да се показват
     */
    public $metaProducts = 'canSell';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('saleId', 'key(mvc=sales_Sales)', 'column=none,notNull,silent,hidden,mandatory');
        
        parent::getDealDetailFields($this);
        
        $this->FLD('term', 'time(uom=days,suggestions=1 ден|5 дни|7 дни|10 дни|15 дни|20 дни|30 дни)', 'caption=Срок,after=tolerance,input=none');
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	if(isset($rec->productId)){
    		$pInfo = cat_Products::getProductInfo($rec->productId);
    		$masterStore = $mvc->Master->fetch($rec->{$mvc->masterKey})->shipmentStoreId;
    		
    		if(isset($masterStore) && isset($pInfo->meta['canStore'])){
    			$storeInfo = deals_Helper::checkProductQuantityInStore($rec->productId, $rec->packagingId, $rec->packQuantity, $masterStore);
    			$form->info = $storeInfo->formInfo;
    		}
    	}
    	
    	parent::inputDocForm($mvc, $form);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	$rows = &$data->rows;
    	
    	if(!count($data->recs)) return;
    	 
    	foreach ($rows as $id => $row){
    		$rec = $data->recs[$id];
    		$pInfo = cat_Products::getProductInfo($rec->productId);
    			
    		if($storeId = $data->masterData->rec->shipmentStoreId){
    			if(isset($pInfo->meta['canStore']) && $data->masterData->rec->state == 'draft'){
    				$warning = deals_Helper::getQuantityHint($rec->productId, $storeId, $rec->quantity);
    				if(strlen($warning)){
    					$row->packQuantity = ht::createHint($row->packQuantity, $warning, 'warning', FALSE);
    				}
    			}
    		}
    		
    		if($rec->price < cat_Products::getSelfValue($rec->productId, NULL, $rec->quantity)){
    			$row->packPrice = ht::createHint($row->packPrice, 'Цената е под себестойността', 'warning', FALSE);
    		}
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$rec = &$data->form->rec;
    	$form = &$data->form;
    	
    	if(isset($rec->productId)){
    		
    		$term = cat_Products::getParams($rec->productId, 'term');
    		if(!empty($term)){
    			$form->setField('term', 'input');
    			if(empty($rec->id)){
    				$form->setDefault('term', $term);
    			}
    			
    			$termVerbal = $mvc->getFieldType('term')->toVerbal($term);
    			$form->setSuggestions('term', array('' => '', $termVerbal => $termVerbal));
    		}
    	}
    }
    
    
    /**
     * Приготвя информация за нестандартните артикули и техните задания
     * 
     * @param stdClass $rec
     * @param stdClass $masterRec
     * @return void|stdClass
     */
    public static function prepareJobInfo($rec, $masterRec)
    {
    	$row = new stdClass();
    	$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
    	$row->quantity = $row->quantityFromTasks = $row->quantityProduced = 0;
    	$jobRec = NULL;
    	
    	$pRec = cat_Products::fetch($rec->productId);
    	
    	// Имаме ли активно задание по тази продажба
    	$jobRec = planning_Jobs::fetch("#productId = {$rec->productId} AND #saleId = {$masterRec->id} AND (#state = 'active' || #state = 'stopped' || #state = 'wakeup')");
    	
    	// Ако няма търсим, имаме ли активно задание
    	if(!$jobRec){
    		$jobRec = planning_Jobs::fetch("#productId = {$rec->productId} AND (#state = 'active' || #state = 'stopped' || #state = 'wakeup')");
    	}
    	
    	// Ако и такова няма намираме последното задание-чернова към тази продажба
    	if(!$jobRec){
    		$jQuery = planning_Jobs::getQuery();
    		$jQuery->where("#productId = {$rec->productId} AND #saleId = {$masterRec->id} AND #state = 'draft'");
    		$jQuery->orderBy("id", 'DESC');
    		$jobRec = planning_Jobs::fetch("#productId = {$rec->productId} AND #state = 'draft'");
    	}
    	
    	if(!empty($jobRec)){
    		$Double = cls::get('type_Double', (object)array('params' => array('smartRound' => TRUE)));
    		$row->quantity = $Double->toVerbal($jobRec->quantity);
    		$row->quantityFromTasks = $Double->toVerbal(planning_TaskActions::getQuantityForJob($jobRec->id, 'product'));
    		$row->quantityProduced = $Double->toVerbal($jobRec->quantityProduced);
    		
    		if(!Mode::is('text', 'xhtml') && !Mode::is('printing')){
    			$row->dueDate = cls::get('type_Date')->toVerbal($jobRec->dueDate);
    			$row->dueDate = ht::createLink($row->dueDate, array('cal_Calendar', 'day', 'from' => $row->dueDate, 'Task' => 'true'), NULL, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
    		}
    		
    		$row->jobId = "#" . planning_Jobs::getHandle($jobRec->id);
    		$row->jobId = ht::createLink($row->jobId, planning_Jobs::getSingleUrlArray($jobRec->id), FALSE, 'ef_icon=img/16/clipboard_text.png');
    		$row->ROW_ATTR['class'] = "state-{$jobRec->state}";
    	
    		return $row;
    	}
    }
}
