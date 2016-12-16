<?php



/**
 * Мениджър Журнал детайли
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_InventoryNoteDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    public $title = "Детайл на протокола за инвентаризация";
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'batch,ceo,storeMaster';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_AlignDecimals2, plg_RowTools2,plg_RowNumbering';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, packQuantity, batchOut, batchIn';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('noteId', 'key(mvc=batch_InventoryNotes,select=id)', 'caption=Протокол,mandatory,silent,input=hidden');
    	$this->FLD('productId', 'int', 'caption=Артикул,mandatory,silent,removeAndRefreshForm=quantityInPack|packagingId|quantity,tdClass=productCell leftCol wrap');
    	$this->FLD('packagingId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory,smartCenter,input=hidden,tdClass=small-field nowrap');
    	$this->FLD('quantity', 'double(Min=0)', 'caption=Количество,input=none');
    	$this->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
    	$this->FNC('packQuantity', 'double(decimals=2)', 'caption=Количество,input,mandatory');
    
    	$this->FLD('batchOut', 'varchar', 'caption=Партидност->По система');
    	$this->FLD('batchIn', 'varchar', 'caption=Партидност->Установено');
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    public function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
    	if (empty($rec->quantity) || empty($rec->quantityInPack)) {
    		return;
    	}
    
    	$rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = $form->rec;
    	$masterRec = $data->masterRec;
    	
    	// Опции за артикули
    	$productOptions = self::getProductOptions($masterRec->storeId, $masterRec->valior);
    	if(isset($rec->id)){
    		if(!array_key_exists($rec->productId, $productOptions)){
    			$productOptions[$rec->productId] = cat_Products::getTitleById($rec->productId, FALSE);
    		}
    	}
    	
    	if(!count($productOptions)) return followRetUrl(NULL, 'Няма артикули с партидност в склада, с наличност към избрания период', 'warning');
    	$productOptions = isset($rec->id) ? $productOptions : array('' => '') + $productOptions;
    	$form->setOptions('productId', $productOptions);
    	
    	// Ако има артикул
    	if(isset($rec->productId)){
    		
    		// Зареждане на опаковката
    		$packs = cat_products::getPacks($rec->productId);
    		$form->setField('packagingId', 'input');
    		$form->setOptions('packagingId', $packs);
    		$form->setDefault('packagingId', key($packs));
    		
    		// Зареждане на класа на партидата
    		expect($BatchClass = batch_Defs::getBatchDef($rec->productId));
    		$form->setFieldType('batchIn', $BatchClass->getBatchClassType());
    		$form->setFieldType('batchOut', $BatchClass->getBatchClassType());
    		
    		$batches = batch_Items::getBatches($rec->productId, $masterRec->storeId);
    		if(count($batches)){
    			$form->setSuggestions('batchOut', array('' => '') + $batches);
    		}
    		
    		if(isset($rec->id)){
    			if(!empty($rec->batchIn)){
    				$rec->batchIn = $BatchClass->denormalize($rec->batchIn);
    			}
    			
    			if(!empty($rec->batchOut)){
    				$rec->batchOut = $BatchClass->denormalize($rec->batchOut);
    			}
    		}
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
    		$pInfo = cat_Products::getProductInfo($rec->productId);
    		$rec->quantityInPack = ($pInfo->packagings[$rec->packagingId]) ? $pInfo->packagings[$rec->packagingId]->quantity : 1;
    		$rec->quantity = $rec->quantityInPack * $rec->packQuantity; 
    		
    		$BatchClass = batch_Defs::getBatchDef($rec->productId);
    		
    		// Проверка на входящата партида ако има
    		if(!empty($rec->batchIn)){
    			if(!$BatchClass->isValid($rec->batchIn, $rec->quantity, $msg)){
    				$form->setError('batchIn', $msg);
    			}
    		}
    		
    		// Проверка на изходящата партида ако има
    		if(!empty($rec->batchOut)){
    			if(!$BatchClass->isValid($rec->batchOut, $rec->quantity, $msg)){
    				$form->setError('batchOut', $msg);
    			}
    		}
    		
    		// Трябва да има поне една партида
    		if(empty($rec->batchIn) && empty($rec->batchOut)){
    			$form->setError('batchIn,batchOut', 'Трябва да е посочена поне една партида');
    		}
    	}
    }
    
    
    /**
     * Преди запис на документ
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	// Нормализираме полето за партидата
    	$BatchClass = batch_Defs::getBatchDef($rec->productId);
    	$batchFields = array('batchIn', 'batchOut');
    	foreach ($batchFields as $f){
    		if(!empty($rec->{$f})){
    			if($rec->{$f} != $BatchClass->getAutoValueConst()){
    				$rec->{$f} = $BatchClass->normalize($rec->{$f});
    			}
    		} else {
    			$rec->{$f} = NULL;
    		}
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
    	$BatchClass = batch_Defs::getBatchDef($rec->productId);
    	$batchFields = array('batchIn', 'batchOut');
    	
    	// След запис, ако някоя от партидите трябва да получи автоматична стойност да се попълни
    	foreach ($batchFields as $f){
    		if(!empty($rec->{$f})){
    			if($rec->{$f} == $BatchClass->getAutoValueConst()){
    				$rec->{$f} = $BatchClass->getAutoValue($mvc->Master, $rec->noteId);
    				$mvc->save_($rec, $f);
    			}
    		}
    	}
    }
    
    
    /**
     * Намира артикулите с партида с наличност в склада към дадена дата
     * 
     * @param int $storeId    - склад
     * @param date $date      - дата
     * @param int|NULL $limit - лимит
     * @return array $options - опции
     */
    public static function getProductOptions($storeId, $date, $limit = NULL)
    {
    	$options = $productItems = array();
    	
    	// Кои са артикулите с паритиди
    	$products = batch_Defs::getProductsWithDefs();
		
    	// Ако няма не се връщат опции
    	if(!$products) return $options;
		
    	// Извличане на перата на артикулите с дефиниции
		foreach ($products as $p){
			if($itemId = acc_Items::fetchItem('cat_Products', $p)->id){
				$productItems[$itemId] = $p;
			}
		}
		
		// Ако няма поне едно перо не се връщат опции
		if(!$productItems) return $options;
		
		// Търсим артикулите от два месеца назад
		$to = dt::addDays(-1, $date);
		$to = dt::verbal2mysql($to, FALSE);
		
		$from = dt::addMonths(-2, $to);
		$from = dt::verbal2mysql($from, FALSE);
		
		// Изчисляваме баланс за подадения период за склада
		expect($storeItemId = acc_Items::fetchItem('store_Stores', $storeId)->id);
		$Balance = new acc_ActiveShortBalance(array('from' => $from, 'to' => $to, 'accs' => '321', 'cacheBalance' => FALSE, 'item1' => $storeItemId));
		$bRecs = $Balance->getBalance('321');
		$productPositionId = acc_Lists::getPosition('321', 'cat_ProductAccRegIntf');
		
		$count = 0;
		
		// За всеки намерен запис от баланса
		if(is_array($bRecs)){
			foreach ($bRecs as $bRec){
				
				// Ако артикула няма партидност, пропуска се
				$productItem = $bRec->{"ent{$productPositionId}Id"};
				if(!array_key_exists($productItem, $productItems)) continue;
				
				// Ако артикула има партидност, но вече е добавен към опциите пропуска се
				$productId = $productItems[$productItem];
				if(array_key_exists($productId, $options)) continue;
				$options[$productId] = cat_Products::getTitleById($productId, FALSE);
				$count++;
				
				// Ако лимита е достигнат спира се търсенето
				if(isset($limit) && $count == $limit) break;
			}
		}
		
		// Връщане на намерените опции
		return $options;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)){
    		if(batch_InventoryNotes::fetchField($rec->noteId, 'state') != 'draft'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->query->orderBy('id', 'ASC');
    }
    
    
    /**
	 * Преди рендиране на таблицата
	 */
	protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
	{
    	$rows = $data->rows;
    	if(!count($rows)) return;
    	$masterRec = $data->masterData->rec;
    	
    	foreach ($data->rows as $i => &$row) {
    		$rec = &$data->recs[$i];
    		$RichText = cls::get('type_Richtext');
    		
    		if(!empty($rec->batchIn)){
    			unset($notes);
    			batch_Defs::appendBatch($rec->productId, $rec->batchIn, $notes);
    			$row->batchIn = "<small>{$RichText->toVerbal($notes)}</small>";
    		}
    		
    		if(!empty($rec->batchOut)){
    			unset($notes);
    			batch_Defs::appendBatch($rec->productId, $rec->batchOut, $notes);
    			$row->batchOut = "<small>{$RichText->toVerbal($notes)}</small>";
    			
    			$quantity = batch_Items::getQuantity($rec->productId, $rec->batchOut, $masterRec->storeId);
    			if($quantity < $rec->quantity){
    				$row->ROW_ATTR['style'] = 'background-color:rgba(255, 0, 0, 0.3)';
    				$q = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($quantity);
    				$row->packQuantity = ht::createHint($row->packQuantity, "По-голямо от наличното количество|*: {$q}", 'error', FALSE);
    			}
    		}
    		
    		// Показваме подробната информация за опаковката при нужда
    		deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
    		$row->productId = cat_Products::getShortHyperlink($rec->productId);
    	}
    }
}