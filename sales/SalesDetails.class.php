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
                        plg_AlignDecimals2, doc_plg_HidePrices, LastPricePolicy=sales_SalesLastPricePolicy';
    
    
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
    public $listFields = 'productId, packagingId, uomId, packQuantity, packPrice, discount, amount';
    
        
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
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	$masterStore = $mvc->Master->fetch($rec->{$mvc->masterKey})->shipmentStoreId;
    	
    	if(isset($rec->productId)){
    		$pInfo = cls::get($rec->classId)->getProductInfo($rec->productId, $rec->packagingId);
    		if(isset($masterStore) && isset($pInfo->meta['canStore'])){
    			
    			$storeInfo = deals_Helper::getProductQuantityInStoreInfo($rec->productId, $rec->classId, $masterStore);
    			$form->info = $storeInfo->formInfo;
    		}
    	}
    	
    	if ($form->isSubmitted()){
    		$quantityInPack = ($pInfo->packagingRec) ? $pInfo->packagingRec->quantity : 1;
    		
    		// Показваме предупреждение ако наличното в склада е по-голямо от експедираното
    		if(isset($storeInfo)){
    			if($rec->packQuantity > ($storeInfo->quantity / $quantityInPack)){
    				$form->setWarning('packQuantity', 'Въведеното количество е по-голямо от наличното в склада');
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
    			
    		if($rec->price < cls::get($rec->classId)->getSelfValue($rec->productId)){
    			$row->packPrice = "<span class='row-negative' title = '" . tr('Цената е под себестойност') . "'>{$row->packPrice}</span>";
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
    	$row->productId = cls::get($rec->classId)->getTitleById($rec->productId);
    	$row->productId = ht::createLinkRef($row->productId, array($rec->classId, 'single', $rec->productId));
    	
    	if($masterRec->state == 'active') {
    		
    		// Проверяваме имали задание
    		if($jobRec = planning_Jobs::fetch("#productId = {$rec->productId} AND (#state = 'active' || #state = 'draft')", 'id,state,dueDate')){
    		
    			// Ако е чернова, и можем да го редактираме добавяме бутон за редакция
    			if($jobRec->state == 'draft'){
    				if(planning_Jobs::haveRightFor('activate', $jobRec)){
    					$row->jobId = ht::createBtn('Редакция', array('planning_Jobs', 'edit', $jobRec->id), FALSE, FALSE, 'title=Редактиране на ново задание за артикула,ef_icon=img/16/edit.png');
    				}
    			}
    		
    			if(!$row->jobId){
    				// Ако има такова, добавяме линк към сингъла му
    				$row->jobId = "#" . planning_Jobs::getHandle($jobRec->id);
    				if(planning_Jobs::haveRightFor('single', $jobRec)){
    					$row->jobId = ht::createLink($row->jobId, array('planning_Jobs', 'single', $jobRec->id), FALSE, 'ef_icon=img/16/clipboard_text.png');
    				}
    				$row->jobId .= " ( " . planning_Jobs::getVerbal($jobRec, 'dueDate') . " )";
    			}
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
