<?php



/**
 * Регистър за разпределяне на разходи
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_BatchesInDocuments extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Партиди в документи';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'batch_Wrapper';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Партида';
    
    
    /**
     * Кой може да променя?
     */
    public $canWrite = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'debug';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'id,date,containerId=Документ,productId=Артикул,packagingId=Опаковка,quantityInPack=К-во в опаковка,quantity=Количество,batch=Партида,operation=Операция,storeId=Склад';
	
	
	/**
	 * Описание на модела (таблицата)
	 */
	public static $cache = array();
	
	
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('detailClassId', 'class(interface=core_ManagerIntf)', 'caption=Детайл,mandatory,silent,input=hidden,remember');
    	$this->FLD('detailRecId', 'int', 'caption=Ред от детайл,mandatory,silent,input=hidden,remember');
    	$this->FLD('productId', 'key(mvc=cat_Products)', 'caption=Артикул,mandatory,silent,input=hidden,remember');
    	$this->FLD('packagingId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory,smartCenter,input=hidden,tdClass=small-field nowrap');
    	$this->FLD('quantity', 'double(decimals=4)', 'caption=Количество,input=none');
    	$this->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
    	$this->FLD('date', 'date', 'mandatory,caption=Дата,silent,input=hidden');
    	$this->FLD('containerId', 'key(mvc=doc_Containers)', 'mandatory,caption=Ориджин,silent,input=hidden');
    	$this->FLD('batch', 'text', 'input=none,caption=Партида,after=productId,forceField');
    	$this->FLD('operation', 'enum(in=Влиза, out=Излиза, stay=Стои)', 'mandatory,caption=Операция');
    	$this->FLD('storeId', 'key(mvc=store_Stores)', 'caption=Склад');
    	
    	$this->setDbIndex('detailClassId,detailRecId');
    	$this->setDbIndex('productId');
    }
	
	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		try{
			$row->containerId = doc_Containers::getDocument($rec->containerId)->getLink(0);
		} catch(core_exception_Expect $e){
			$row->containerId = "<span class='color:red'>" . tr('Проблем при показването') . "</span>";
		}
		
		$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
	 *
	 * @param core_Mvc $mvc
	 * @param string $requiredRoles
	 * @param string $action
	 * @param stdClass $rec
	 * @param int $userId
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'modify' && isset($rec)){
			if(!isset($rec->detailClassId) || !isset($rec->detailRecId)){
				$requiredRoles = 'no_one';
			} else {
				$requiredRoles = cls::get($rec->detailClassId)->getRolesToModfifyBatches($rec->detailRecId);
			}
		}
	}
	
	
	/**
	 * Рендиране на партидите на даде обект
	 * 
	 * @param mixed $detailClassId - клас на обект
	 * @param id $detailRecId      - ид на обект
	 * @param id $storeId          - ид на склад
	 * @return core_ET $tpl        - шаблона с рендирането
	 */ 
	public static function renderBatches($detailClassId, $detailRecId, $storeId)
	{
		$detailClassId = cls::get($detailClassId)->getClassId();
		$rInfo = cls::get($detailClassId)->getRowInfo($detailRecId);
		if(!count($rInfo->operation)) return;
		$operation = key($rInfo->operation);
		
		$query = self::getQuery();
		$query->where("#detailClassId = {$detailClassId} AND #detailRecId = {$detailRecId} AND #operation = '{$operation}'");
		$query->orderBy('id', 'ASC');
		$batchDef = batch_Defs::getBatchDef($rInfo->productId);
		
		$file = ($batchDef instanceof batch_definitions_Serial) ? 'batch/tpl/BatchInfoBlockSerial.shtml' : 'batch/tpl/BatchInfoBlock.shtml';
		$tpl = getTplFromFile($file);
		
		$count = 0;
		$total = $rInfo->quantity;
		$totalCount = $query->count() - 1;
		
		while($rec = $query->fetch()){
			
			$batch = batch_Movements::getLinkArr($rec->productId, $rec->batch);
			if(is_array($batch)){
				foreach ($batch as $key => &$b){
					if($msg = self::checkBatchRow($detailClassId, $detailRecId, $key, $rec->quantity)){
						$b = ht::createHint($b, $msg, 'warning');
						$b = $b->getContent();
					}
				}
			}
			
			$string = '';
			$block = clone $tpl->getBlock('BLOCK');
			$total -= $rec->quantity;
			$total = round($total, 5);
			
			$caption = $batchDef->getFieldCaption();
			$label = (!empty($caption)) ? tr($caption) . ":" : 'lot:';
			
			// Вербализацията на к-то ако е нужно
			if(count($batch) == 1 && (!($batchDef instanceof batch_definitions_Serial))){
				$quantity = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($rec->quantity / $rInfo->quantityInPack);
				$quantity .= " " . tr(cat_UoM::getShortName($rInfo->packagingId));
				$block->append($quantity, "quantity");
			}

			$batch = implode(', ', $batch);
			
			if($batchDef instanceof batch_definitions_Serial){
				$label = ($count == 0) ? "{$label} " : "";
				$end = ($count == $totalCount) ? "" : ",";
				$string = "{$label}{$batch}{$end}";
			} else {
				$string = "{$label} {$batch}" . "<br>";
			}
			
			$block->append($string, "batch");
			$block->removePlaces();
			$block->append2Master();
			$count++;
		}
		
		// Ако има остатък
		if($total > 0 || $total < 0){
			
			// Показва се като 'Без партида'
			$block = clone $tpl->getBlock('NO_BATCH');
			if($total > 0){
				$batch = "<i style=''>" . tr('Без партида') . "</i>";
				$quantity = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($total / $rInfo->quantityInPack);
				$quantity .= " " . tr(cat_UoM::getShortName($rInfo->packagingId));
			} else {
				$batch = "<i style='color:red'>" . tr('Несъответствие') . "</i>";
				$batch = ht::createHint($batch, 'К-то на разпределените партиди е повече от това на реда', 'error');
				$quantity = '';
				$block->append('border:1px dotted red;', 'BATCH_STYLE');
			}
			
			$block->append($batch, 'nobatch');
			$block->append($quantity, "nobatchquantity");
			$block->removePlaces();
			$block->append2Master();
		}
		
		$tpl->removePlaces();
		
		return $tpl;
	}
	
	
	/**
	 * Проверка на реда дали има проблеми с партидата
	 * 
	 * @param mixed $detailClassId
	 * @param int $detailRecId
	 * @param string $batch
	 * @param string $quantity
	 * @return FALSE|string
	 */
	public static function checkBatchRow($detailClassId, $detailRecId, $batch, $quantity)
	{
		$Class = cls::get($detailClassId);
		$rInfo = $Class->getRowInfo($detailRecId);
		if(empty($rInfo->operation[key($rInfo->operation)])) return FALSE;
		
		// Ако операцията е изходяща 
		if($rInfo->operation == 'out' && $rInfo->state == 'draft'){
			$storeQuantity = batch_Items::getQuantity($rInfo->productId, $batch, $rInfo->operation['out']);
			if($quantity > $storeQuantity) {
				return 'Недостатъчно количество в склада';
			}
		}
		
		$def = batch_Defs::getBatchDef($rInfo->productId);
		
		// Ако е сериен номер проверка дали не се повтаря
		if($def instanceof batch_definitions_Serial){
			if($Class instanceof core_Detail){
				$rec = $Class->fetch($detailRecId);
				$key = $Class->getClassId() . "|{$rec->{$Class->masterKey}}";
				if(!array_key_exists($key, self::$cache)){
					$siblingsQuery = $Class->getQuery();
					$siblingsQuery->where("#{$Class->masterKey} = {$rec->{$Class->masterKey}}");
					$siblingsQuery->show('id');
					self::$cache[$key] = arr::extractValuesFromArray($siblingsQuery->fetchAll(), 'id');
				}
			}
			
			$query = self::getQuery();
			$query->where("#detailClassId = {$detailClassId}");
			$query->in("detailRecId", self::$cache[$key]);
			$query->show('batch,productId');
			$query->groupBy('batch');
			if($detailRecId){
				$query->where("#detailRecId != {$detailRecId}");
			}
			
			$oSerials = $def->makeArray($batch);
			
			// За всеки
			while($oRec = $query->fetch()){
				$serials = batch_Defs::getBatchArray($oRec->productId, $oRec->batch);
					
				// Проверяваме имали дублирани
				$intersectArr = array_intersect($oSerials, $serials);
				$intersect = count($intersectArr);
					
				// Ако има казваме, кои се повтарят
				// един сериен номер не може да е на повече от един ред
				if($intersect){
					$imploded = implode(',', $intersectArr);
					if($intersect == 1){
						return "|Серийният номер|*: {$imploded}| се повтаря в документа|*";
					} else {
						return "|Серийните номера|*: {$imploded}| се повтарят в документа|*";
					}
				}
			}
		}
	}
	
	
	/**
	 * Екшън за модифициране на партидите
	 */
	public function act_Modify()
	{
		expect($detailClassId = Request::get('detailClassId', 'class'));
		expect($detailRecId = Request::get('detailRecId', 'int'));
		expect($storeId = Request::get('storeId', 'key(mvc=store_Stores)'));
		
		// Проверка на права
		$this->requireRightFor('modify', (object)array('detailClassId' => $detailClassId, 'detailRecId' => $detailRecId));
		$Detail = cls::get($detailClassId);
		$recInfo = $Detail->getRowInfo($detailRecId);
		$recInfo->detailClassId = $detailClassId;
		$recInfo->detailRecId = $detailRecId;
		$storeId = $recInfo->operation[key($recInfo->operation)];
		
		// Кои са наличните партиди към момента
		$batches = batch_Items::getBatchQuantitiesInStore($recInfo->productId, $storeId, $recInfo->date);
		foreach ($batches as $i => $v){
			$itemState = batch_Items::fetchField("#productId = {$recInfo->productId} AND #storeId = {$storeId} AND #batch = '{$i}'", 'state');
			if($itemState == 'closed'){
				unset($batches[$i]);
			}
		}
		
		// Кои са въведените партиди от документа
		$foundBatches = array();
		$dQuery = self::getQuery();
		$dQuery->where("#detailClassId = {$detailClassId} AND #detailRecId = {$detailRecId}");
		while ($dRec = $dQuery->fetch()){
		    $foundBatches[$dRec->batch] = $dRec->quantity;
		    if(!array_key_exists($dRec->batch, $batches)){
				$batches[$dRec->batch] = $dRec->quantity;
			}
		}
		
		// Филтриране на партидите
		$Detail->filterBatches($detailRecId, $batches);
		$packName = cat_UoM::getShortName($recInfo->packagingId);
		
		$link = doc_Containers::getDocument($recInfo->containerId)->getLink(0);
		
		// Подготовка на формата
		$form = cls::get('core_Form');
		$form->title = "Задаване на партидности в|* " . $link;
		$form->info = new core_ET(tr("Артикул|*:[#productId#]<br>|Склад|*: [#storeId#]<br>|Количество за разпределяне|*: <b>[#quantity#]</b>"));
		$form->info->replace(cat_Products::getHyperlink($recInfo->productId, TRUE), 'productId');
		$form->info->replace(store_Stores::getHyperlink($storeId, TRUE), 'storeId');
		$form->info->replace($packName, 'packName');
		$form->info->append(cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($recInfo->quantity / $recInfo->quantityInPack), 'quantity');
		
		$Def = batch_Defs::getBatchDef($recInfo->productId);
		$suggestions = array();
		
		if($Def instanceof batch_definitions_Serial){
			
			// Полетата излизат като списък
			$suggestions = '';
			foreach ($batches as $b => $q){
				$bArray = $Def->makeArray($b);
				foreach ($bArray as $b1){
					$verbal = strip_tags($Def->toVerbal($b1));
					$suggestions .= "{$b1}={$verbal},";
				}
			}
			$suggestions = trim($suggestions, ',');
			if(!empty($suggestions)){
				$form->FLD('serials', "set({$suggestions})", 'caption=Партиди,maxRadio=2,class=batch-quantity-fields');
			}
			
			if(count($foundBatches)){
				$foundArr = array();
				foreach ($foundBatches as $f => $q){
					$fArray = $Def->makeArray($f);
					foreach ($fArray as $b2){
						$foundArr[$b2] = $b2;
					}
				}
				
				$defaultBatches = $form->getFieldType('serials')->fromVerbal($foundArr);
				$form->setDefault('serials', $defaultBatches);
			}
		} else {
			$i = $j = 0;
			$tableRec = $exTableRec = array();
			$batchesCount = count($batches);
			foreach ($batches as $batch => $quantityInStore){
				$vBatch = $Def->toVerbal($batch);
				$suggestions[] = strip_tags($vBatch);
				$tableRec['batch'][$i] = $vBatch;
				if(array_key_exists($batch, $foundBatches)){
					$tableRec['quantity'][$i] = $foundBatches[$batch] / $recInfo->quantityInPack;
					$exTableRec['batch'][$j] = $vBatch;
					$exTableRec['quantity'][$j] = $foundBatches[$batch];
					$j++;
				} else {
					$tableRec['quantity'][$i] = "";
				}
				$i++;
			}
			
			if($batchesCount > batch_Setup::get('COUNT_IN_EDIT_WINDOW')){
				$tableRec = $exTableRec;
			}
		}
		
		// Добавяне на поле за нова партида
		$caption = ($Def->getFieldCaption()) ? $Def->getFieldCaption() : 'Партида';
		$columns = ($Def instanceof batch_definitions_Serial) ? 'batch' : 'batch|quantity';
		$captions = ($Def instanceof batch_definitions_Serial) ? 'Номер' : 'Номер|Количество';
		$noCaptions = ($Def instanceof batch_definitions_Serial) ? 'noCaptions' : '';
		
		$form->FLD('newArray', "table(columns={$columns},batch_ro=readonly,captions={$captions},{$noCaptions},validate=batch_BatchesInDocuments::validateNewBatches)", "caption=Нови партиди->{$caption},placeholder={$Def->placeholder}");
		
		$form->setFieldTypeParams('newArray', array('batch_sgt' => $suggestions));
		$form->setFieldTypeParams('newArray', array('batchDefinition' => $Def));
		$form->setDefault('newArray', $tableRec);
		
		// Какви са наличните партиди
		$Def = batch_Defs::getBatchDef($recInfo->productId);
		$batchCount = count($batches);
		
		$form->input();
		$saveBatches = array();
		
		// След събмит
		if($form->isSubmitted()){
			$r = $form->rec;
			
			$update = $delete = $fields = $error = $error2 = $errorFields = array();
			$total = 0;
			
			if(!empty($r->newArray)){
				$newBatchArray = array();
				$newBatches = (array)@json_decode($r->newArray);
				$bCount = count($newBatches['batch']);
				
				for($i = 0; $i <= $bCount - 1; $i++){
					if(empty($newBatches['batch'][$i])) continue;
					$batch = $Def->normalize($newBatches['batch'][$i]);
					
					$Double = core_Type::getByName('double');
					if($Def instanceof batch_definitions_Serial){
					    $newBatches['quantity'][$i] = 1;
					}
					
					if(!empty($newBatches['quantity'][$i])){
						$quantity = $Double->fromVerbal($newBatches['quantity'][$i]);
						if($quantity){
							$total += $quantity;
						}
							
						$quantity = ($Def instanceof batch_definitions_Serial) ? 1 : $quantity;
						$saveBatches[$batch] = $quantity  * $recInfo->quantityInPack;
						
						// Проверка на к-то
						if(!deals_Helper::checkQuantity($recInfo->packagingId, $quantity, $warning)){
							$form->setError("newArray", $warning);
						}
					} else {
						$delete[] = $newBatches['batch'][$i];
					}
				}
			}
			
			if($Def instanceof batch_definitions_Serial){
				$batches = type_Set::toArray($r->serials);
				if(count($batches) > $recInfo->quantity){
					if($form->cmd != 'updateQuantity'){
						$form->setError('serials', "Серийните номера са повече от цялото количество");
					}
				}
					
				foreach ($batches as $b){
					$saveBatches[$b] = 1 / $recInfo->quantityInPack;
					$total += 1;
				}
				$fields[] = "serials";
					
				if(is_array($foundBatches)){
					foreach ($foundBatches as $fb => $q){
						if(!array_key_exists($fb, $batches)){
							$delete[] = $fb;
							unset($saveBatches[$fb]);
						}
					}
				}
			}
			
			if($form->cmd != 'updateQuantity'){
					
				// Не може да е разпределено по-голямо количество от допустимото
				if($total > ($recInfo->quantity / ($recInfo->quantityInPack))){
					$form->setError('newArray', 'Общото количество е над допустимото');
				}
			}
			
			if(!$form->gotErrors()){
			
				if($form->cmd == 'auto'){
				    $old = (count($foundBatches)) ? $foundBatches : array();
					$saveBatches = $Def->allocateQuantityToBatches($recInfo->quantity, $storeId, $recInfo->date);
					$intersect = array_diff_key($old, $saveBatches);
					$delete = (count($intersect)) ? array_keys($intersect) : array();
				}
				
				// Ъпдейт/добавяне на записите, които трябва
				if(count($saveBatches)){
					self::saveBatches($detailClassId, $detailRecId, $saveBatches);
				}
				
				// Изтриване
				if(count($delete)){
					foreach ($delete as $b){
						self::delete("#detailClassId = {$recInfo->detailClassId} AND #detailRecId = {$recInfo->detailRecId} AND #productId = {$recInfo->productId} AND #batch = '{$b}'");
					}
				}
				
				// Предизвиква се обновяване на документа
				$dRec = cls::get($detailClassId)->fetch($detailRecId);
				
				if($form->cmd == 'updateQuantity' && !empty($total)){
					$dRec->quantity = $total * $recInfo->quantityInPack;
				}
				
				cls::get($detailClassId)->save($dRec);
				
				return followRetUrl();
			}
		}
		
		// Добавяне на бутони
		$form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
		
		$attr = arr::make('warning=К-то ще бъде разпределено автоматично по наличните партиди,ef_icon = img/16/arrow_refresh.png, title = Автоматично разпределяне на количеството');
		$attr['onclick'] = "$(this.form).find('.batch-quantity-fields').val('');";
		$form->toolbar->addSbBtn('Това е количеството', 'updateQuantity', "ef_icon = img/16/disk.png,title = Обновяване на количеството");
		$operation = key($recInfo->operation);
		if($operation == 'out'){
			$form->toolbar->addSbBtn('Автоматично', 'auto', $attr);
		}
		
		$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
		$tpl = $this->renderWrapping($form->renderHtml());
		core_Form::preventDoubleSubmission($tpl, $form);
		
		// Рендиране на формата
		return $tpl;
	}
	
	
	/**
	 * Валидира партидите
	 */
	public static function validateNewBatches($tableData, $Type)
	{
		$res = array();
		$Def = $Type->params['batchDefinition'];
		$tableData = (array)$tableData;
		$isSerial = $Def instanceof batch_definitions_Serial;
		$DefType = $Def->getBatchClassType();
		
		$error = $errorFields = array();
		$batches = $tableData['batch'];
		if(empty($tableData)) return;
		
		$bArray = array();
		foreach ($batches as $key => $batch)
		{
			if(!empty($batch)){
				if($isSerial){
					if(empty($tableData['quantity'][$key])){
						$tableData['quantity'][$key] = 1;
					}
				}
				
				if(!$Def->isValid($batch, $tableData['quantity'][$key], $msg)){
					$error[]= "<b>{$batch}</b>:|* {$msg}";
					$errorFields['batch'][$key] = "<b>{$batch}</b>:|* {$msg}";
				}
				
				if(array_key_exists($batch, $bArray)){
					$error[]= "Повтаряща се партида";
					$errorFields['batch'][$key] = "Повтаряща се партида";
				} else {
					$bArray[$batch] = $batch;
				}
			}
		}
		
		if(is_array($tableData['quantity'])){
			foreach ($tableData['quantity'] as $key => $quantity)
			{
				if(!empty($quantity)){
					if(empty($tableData['batch'][$key])){
						$error[] = "Попълнено количество без да има партида";
						$errorFields['quantity'][$key] = "Попълнено количество без да има партида";
						$errorFields['batch'][$key] = "Попълнено количество без да има партида";
					}
			
					$Max = ($isSerial) ? 'max=1' : '';
					$Double = core_Type::getByName("double(min=0,{$Max})");
					$qVal = $Double->isValid($quantity);
					
					if(!empty($qVal['error'])){
						$error[] = "Количеството " . mb_strtolower($qVal['error']);
						$errorFields['quantity'][$key] = "Количеството " . mb_strtolower($qVal['error']);
					}
					
					$q2 = $Double->fromVerbal($quantity);
					if(!$q2){
						$error[] = "Невалидно количество";
						$errorFields['quantity'][$key] = "Невалидно количество";
					}
				}
			}
		}
		
		if(count($error)){
			$error = implode("<li>", $error);
			$res['error'] = $error;
		}
		
		if(count($errorFields)){
			$res['errorFields'] = $errorFields;
		}
		
		return $res;
	}
	
	
	/**
	 * Връща ид-то съответстващо на записа
	 * 
	 * @param int $detailClassId - ид на клас
	 * @param int $detailRecId   - ид на запис
	 * @param int $productId     - ид на артикул
	 * @param string $batch      - партида
	 * @param string $operation  - операция
	 */
	public static function getId($detailClassId, $detailRecId, $productId, $batch, $operation)
	{
		$detailClassId = cls::get($detailClassId)->getClassId();
		$where = "#detailClassId = {$detailClassId} AND #detailRecId = {$detailRecId} AND #productId = {$productId} AND #operation = '{$operation}'";
		if(!empty($batch)){
			$where .= " AND #batch = '{$batch}'";
		}
		
		return self::fetchField($where);
	}
	
	
	/**
	 * Записва масив с партиди и техните количества на ред
	 * 
	 * @param mixed $detailClassId
	 * @param int $detailRecId
	 * @param array $batchesArr
	 * @param boolean $sync 
	 * @return void
	 */
	public static function saveBatches($detailClassId, $detailRecId, $batchesArr, $sync = FALSE)
	{
		if(!is_array($batchesArr)) return;
		$recInfo = cls::get($detailClassId)->getRowInfo($detailRecId);
		$recInfo->detailClassId = cls::get($detailClassId)->getClassId();
		$recInfo->detailRecId = $detailRecId;
		
		// Подготвяне на редовете за обновяване
		$update = array();
		foreach ($batchesArr as $b => $q){
			
			foreach ($recInfo->operation as $operation => $storeId){
				$obj = clone $recInfo;
				$obj->operation = $operation;
				$obj->storeId = $storeId;
				$obj->quantity = $q;
				$obj->batch = $b;
				
				$b = ($sync === TRUE) ? NULL : $obj->batch;
				if($id = self::getId($obj->detailClassId, $obj->detailRecId, $obj->productId, $b, $operation)){
					$obj->id = $id;
				}
				
				$update[] = $obj;
			}
		}
		
		// Запис
		if(count($update)){
			cls::get(get_called_class())->saveArray($update);
		}
	}
	
	
	/**
	 * Подготовка на филтър формата
	 */
	protected static function on_AfterPrepareListFilter($mvc, &$data)
	{
		$data->listFilter->view = 'horizontal';
		$data->listFilter->FLD('document', 'varchar(128)', 'silent,caption=Документ,placeholder=Хендлър');
		$data->listFilter->showFields = 'document';
		
		$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
		$data->listFilter->input();
		
		if($fRec = $data->listFilter->rec){
			if(isset($fRec->document)){
				$document = doc_Containers::getDocumentByHandle($fRec->document);
				if(is_object($document)){
					$data->query->where("#containerId = {$document->fetchField('containerId')}");
				}
			}
		}
	}
	
	
	/**
	 * Помощна ф-я за показване на партидите във фактура
	 * 
	 * @param int $productId
	 * @param text $batches
	 * @return NULL|string
	 */
	public static function displayBatchesForInvoice($productId, $batches)
	{
		$batches = explode(',', $batches);
		if(!count($batches)) return NULL;
		$res = array();
		
		foreach ($batches as $key => $b){
			$batch = batch_Defs::getBatchArray($productId, $b);
			if(count($batch)){
				foreach ($batch as $k => &$b){
					if(!Mode::isReadOnly() && haveRole('powerUser')){
						if(!haveRole('batch,ceo')){
							Request::setProtected('batch');
						}
						$b = ht::createLink($b, array('batch_Movements', 'list', 'batch' => $k));
						$b = $b->getContent();
					}
					
					$res[] = $b;
				}
			}
		}
		
		$res = implode(",", $res);
		
		return $res;
	}
}
