<?php
/**
 * Клас 'sales_SalesDetails'
 *
 * Детайли на мениджър на документи за продажба на продукти (@see sales_Sales)
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
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
                        plg_AlignDecimals2, plg_Sorting, deals_plg_ImportDealDetailProduct, doc_plg_HidePrices, LastPricePolicy=sales_SalesLastPricePolicy';
    
    
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
    		$pInfo = cls::get($rec->classId)->getProductInfo($rec->productId);
    		$masterStore = $mvc->Master->fetch($rec->{$mvc->masterKey})->shipmentStoreId;
    		
    		if(isset($masterStore) && isset($pInfo->meta['canStore'])){
    			$storeInfo = deals_Helper::checkProductQuantityInStore($rec->productId, $rec->packagingId, $rec->packQuantity, $masterStore);
    			$form->info = $storeInfo->formInfo;
    			
    			if ($form->isSubmitted()){
    				if(isset($storeInfo->warning)){
    					$form->setWarning('packQuantity', $storeInfo->warning);
    				}
    			}
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
    		$pInfo = cls::get($rec->classId)->getProductInfo($rec->productId);
    			
    		if($storeId = $data->masterData->rec->shipmentStoreId){
    			if(isset($pInfo->meta['canStore'])){
    				$quantityInStore = store_Products::fetchField("#productId = {$rec->productId} AND #classId = {$rec->classId} AND #storeId = {$storeId}", 'quantity');
    				$diff = ($data->masterData->rec->state == 'active') ? $quantityInStore : $quantityInStore - $rec->quantity;
    					
    				if($diff < 0){
    					$row->packQuantity = "<span class='row-negative' title = '" . tr('Количеството в скалда е отрицателно') . "'>{$row->packQuantity}</span>";
    				}
    			}
    		}
    		
    		if($rec->price < cls::get($rec->classId)->getSelfValue($rec->productId, NULL, $rec->quantity)){
    			$row->packPrice = "<span class='row-negative' title = '" . tr('Цената е под себестойност') . "'>{$row->packPrice}</span>";
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
    		
    		$params = cls::get($rec->classId)->getParams($rec->productId);
    		if(!empty($params['term'])){
    			
    			$form->setField('term', 'input');
    			if(empty($rec->id)){
    				$form->setDefault('term', $params['term']);
    			}
    			
    			$termVerbal = $mvc->getFieldType('term')->toVerbal($params['term']);
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
    	$pRec = cls::get($rec->classId)->fetch($rec->productId, 'isPublic,containerId');
    	if($pRec->isPublic === 'yes') return;
    	$pInfo = cls::get($rec->classId)->getProductInfo($rec->productId);
    	if(!isset($pInfo->meta['canManifacture'])) return;
    	
    	$row = new stdClass();
    	
    	// Кой е артикула
    	$row->productId = cls::get($rec->classId)->getShortHyperLink($rec->productId);
    	
    	if($masterRec->state == 'active') {
    		
    		// Проверяваме имали задание
    		if($jobRec = planning_Jobs::fetch("#productId = {$rec->productId} AND (#state != 'draft' && #state != 'rejected')", 'id,state,dueDate')){
    		
    			// Ако има такова, добавяме линк към сингъла му
    			$row->jobId = "#" . planning_Jobs::getHandle($jobRec->id);
    			if(planning_Jobs::haveRightFor('single', $jobRec)){
    				$row->jobId = ht::createLink($row->jobId, array('planning_Jobs', 'single', $jobRec->id), FALSE, 'ef_icon=img/16/clipboard_text.png');
    			}
    			$row->jobId .= " ( " . planning_Jobs::getVerbal($jobRec, 'dueDate') . " )";
    		
    		} else {
    			// Ако няма задание, добавяме бутон за създаване на ново задание
    			if(planning_Jobs::haveRightFor('add', (object)array('productId' => $pRec->id))){
    				$jobUrl = array('planning_Jobs', 'add', 'productId' => $pRec->id, 'quantity' => $rec->quantity, 'saleId' => $masterRec->id, 'ret_url' => TRUE);
    				$row->jobId = ht::createBtn('Нов', $jobUrl, FALSE, FALSE, 'title=Създаване на ново задание за артикула,ef_icon=img/16/clipboard_text.png');
    			}
    		}
    	}
    	
    	return $row;
    }
}
