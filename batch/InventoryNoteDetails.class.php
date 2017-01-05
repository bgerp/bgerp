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
    public $loadList = 'plg_RowTools2,plg_RowNumbering';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, batch=Партида, fromSystem=Количества->По система, final=Количества->Установено, diff=Количества->Разлика';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('noteId', 'key(mvc=batch_InventoryNotes,select=id)', 'caption=Протокол,mandatory,silent,input=hidden');
    	$this->FLD('productId', 'int', 'caption=Артикул,mandatory,silent,removeAndRefreshForm=quantityInPack|packagingId|quantity|batch|newBatch,tdClass=productCell leftCol wrap');
    	$this->FLD('packagingId', 'key(mvc=cat_UoM, select=name)', 'silent,caption=Мярка,mandatory,smartCenter,input=hidden,tdClass=small-field nowrap,removeAndRefreshForm');
    	$this->FLD('quantityInPack', 'double(minDecimals=0)', 'input=none,column=none');
    	$this->FLD('data', 'blob(serialize, compress)', 'input=none');
    	$this->setDbUnique('noteId,productId');
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
    	if(isset($rec->id)){
    		$productOptions = array();
    		$productOptions[$rec->productId] = cat_Products::getTitleById($rec->productId, FALSE);
    	} else {
    		$productOptions = self::getProductOptions($masterRec->storeId, $masterRec->valior);
    		
    		$query = self::getQuery();
    		$query->where("#noteId = {$masterRec->id}");
    		$query->show('productId');
    		$productIds = arr::extractValuesFromArray($query->fetchAll(), 'productId');
    		$productOptions = array_diff_key($productOptions, $productIds);
    	}
    	
    	if(!count($productOptions)) return followRetUrl(NULL, 'Няма артикули с партидност в склада, с наличност към избрания период', 'warning');
    	$options = isset($rec->id) ? $productOptions : ((count($productOptions) != 1) ? array('' => '') + $productOptions : $productOptions);
    	$form->setOptions('productId', $options);
    	
    	if(count($productOptions) == 1){
    		$form->setDefault('productId', key($productOptions));
    	}
    	
    	// Ако има артикул
    	if(isset($rec->productId)){
    		
    		// Зареждане на опаковката
    		$packs = cat_products::getPacks($rec->productId);
    		$form->setField('packagingId', 'input');
    		$form->setOptions('packagingId', $packs);
    		$form->setDefault('packagingId', key($packs));
    		$packName = cat_UoM::getShortName($rec->packagingId);
    		
    		// Зареждане на класа на партидата
    		expect($Def = batch_Defs::getBatchDef($rec->productId));
    		
    		$quantities = batch_Items::getBatchQuantitiesInStore($rec->productId, $masterRec->storeId, $masterRec->valior);
    		$rec->data = (is_array($rec->data)) ? $rec->data : array();
    		$quantities += $rec->data;
    		
    		$form->counts = array();
    		
    		if(is_array($quantities)){
    			$count = 1;
    			foreach ($quantities as $batch => $quantity){
    				$form->counts[] = $count;
					$verbal = strip_tags($Def->toVerbal($batch));
					$form->FLD("quantity{$count}", "double", "caption=Установени партиди->{$verbal},unit={$packName}");
					if(array_key_exists($batch, $rec->data)){
						$form->setDefault("quantity{$count}", $rec->data["{$batch}"] / $rec->quantityInPack);
					}
					$form->FLD("batch{$count}", 'varchar', "input=hidden");
					$form->setDefault("batch{$count}", $batch);
					$count++;
				}
    		}
    		
    		// Добавяне на поле за нова партида
    		$autohide = count($quantities) ? 'autohide' : '';
    		$caption = ($Def->getFieldCaption()) ? $Def->getFieldCaption() : 'Партида';
    		$form->FLD('newBatch', 'varchar', "caption=Установена нова партиди->{$caption},placeholder={$Def->placeholder},{$autohide}");
    		$form->setFieldType('newBatch', $Def->getBatchClassType());
    		
    		// Ако е сериен номер полето за к-во се скрива
    		if(!($Def instanceof batch_definitions_Serial)){
    			$form->FLD('newBatchQuantity', 'double(min=0)', "caption=Установена нова партиди->К-во,placeholder={$Def->placeholder},unit={$packName},{$autohide}");
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
    		
    		$BatchClass = batch_Defs::getBatchDef($rec->productId);
    		
    		$data = array();
    		foreach ($form->counts as $count){
    			if(isset($rec->{"quantity{$count}"})){
    				$data["{$rec->{"batch{$count}"}}"] = $rec->{"quantity{$count}"};
    			}
    		}
    		
    		// Ако има нова партида, проверява се
    		if(!empty($rec->newBatch)){
    			$normalized = $BatchClass->normalize($rec->newBatch);
    			$batches = $BatchClass->makeArray($normalized);
    			
    			// Трябва да е валидна
    			if(!$BatchClass->isValid($rec->newBatch, count($batches), $msg)){
    				$form->setError('newBatch', $msg);
    			}
    			
    			foreach ($batches as $b1 => $b2){
    				if(!array_key_exists($b1, $data)){
    					$data["{$b1}"] = ($BatchClass instanceof batch_definitions_Serial) ? 1 : ($rec->newBatchQuantity / count($batches));
    				} else {
    					$form->setError('newBatch', 'Опитвате се да създадете същестуваща партида');
    				}
    			}
    		}
    		
    		foreach ($rec->data as &$q1){
    			$q1 *= $rec->quantityInPack;
    		}
    		
    		$rec->data = $data;
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
    	$products = batch_Items::getProductsWithDefs();
    	
    	// Ако няма не се връщат опции
    	if(!$products) return $options;
    	$products = array_combine(array_keys($products), array_keys($products));
    	
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
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, &$data)
    {
    	$rows = &$data->rows;
    	if(!count($rows)) return;
    	
    	$masterRec = $data->masterData->rec;
    	$newRows = array();
    	
    	$makeLink = !Mode::isReadOnly() && haveRole('powerUser');
    	if(!haveRole('batch,ceo')){
    		Request::setProtected('batch');
    	}
    	
    	foreach ($data->rows as $id => $row){
    		$rec = $data->recs[$id];
    		
    		deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
    		$row->productId = cat_Products::getShortHyperlink($rec->productId);
    		
    		$newRows[] = $row;
    		
    		$quantities = batch_Items::getBatchQuantitiesInStore($rec->productId, $masterRec->storeId, $masterRec->valior);
    		$BatchClass = batch_Defs::getBatchDef($rec->productId);
    		
    		foreach ($rec->data as $batch => $q){
    			$Double = cls::get('type_Double', array('params' => array('smartRound' => TRUE)));
    			
    			$diff = round($q - $quantities["{$batch}"], 2);
    			
    			$diff = $Double->toVerbal($diff);
    			$systemQuantity = $Double->toVerbal($quantities["{$batch}"]);
    			$finalQuantity = $Double->toVerbal($q);
    			
    			if($diff < 0){
    				$diff = "<span class='red'>{$diff}</span>";
    			} elseif($diff > 0){
    				$diff = "<span style='color:green'>+{$diff}</span>";
    			} else {
    				$diff = "<span class='quiet'>{$diff}</span>";
    			}
    			
    			$systemQuantity = ($quantities["{$batch}"] < 0) ? "<span class='red'>{$systemQuantity}</span>" : $systemQuantity;
    			$finalQuantity = ($q < 0) ? "<span class='red'>{$finalQuantity}</span>" : $finalQuantity;
    			
    			$batchV = $BatchClass->toVerbal($batch);
    			if($makeLink){
    				$batchV = ht::createLink($batchV, array('batch_Movements', 'list', 'batch' => $batch));
    			}
    			
    			$newRow = (object)array('productId'   => "<span class='quiet'>" . cat_Products::getTitleById($rec->productId) . "<span>",
    						            'batch'       => $batchV,
    						            'packagingId' => "<span class='quiet'>" . $row->packagingId . "</span>",
    						            'fromSystem'  => $systemQuantity,
    						            'final'       => $finalQuantity, 
    					                'diff'        => $diff
    			);
    			
    			$newRows[] = $newRow;
    		}
    	}
    	
    	$data->rows = $newRows;
    	
    	Request::removeProtected('batch');
    }
    
    
    /**
	 * Преди рендиране на таблицата
	 */
	protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
	{
		$data->listTableMvc->FLD('batch', 'varchar', 'smartCenter');
		$data->listTableMvc->FLD('fromSystem', 'double');
		$data->listTableMvc->FLD('final', 'double');
		$data->listTableMvc->FLD('diff', 'double');
    }
}