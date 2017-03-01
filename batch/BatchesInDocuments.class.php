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
		$tpl = getTplFromFile('batch/tpl/BatchInfoBlock.shtml');
		$detailClassId = cls::get($detailClassId)->getClassId();
		$rInfo = cls::get($detailClassId)->getRowInfo($detailRecId);
		if(!count($rInfo->operation)) return;
		$operation = key($rInfo->operation);
		
		$query = self::getQuery();
		$query->where("#detailClassId = {$detailClassId} AND #detailRecId = {$detailRecId} AND #operation = '{$operation}'");
		$query->orderBy('id', "DESC");
		$batchDef = batch_Defs::getBatchDef($rInfo->productId);
		
		$count = 0;
		$total = $rInfo->quantity;
		
		while($rec = $query->fetch()){
			
			// Партидите стават линкове
			$batch = batch_Defs::getBatchArray($rec->productId, $rec->batch);
			foreach ($batch as $key => &$b){
				if(!Mode::isReadOnly() && haveRole('powerUser')){
					if(!haveRole('batch,ceo')){
						Request::setProtected('batch');
					}
					$b = ht::createLink($b, array('batch_Movements', 'list', 'batch' => $key));
				}
				
				// Проверка на реда
				if($msg = self::checkBatchRow($detailClassId, $detailRecId, $key, $rec->quantity)){
					$b = ht::createHint($b, $msg, 'warning');
				}
			}
			
			$string = '';
			$block = clone $tpl->getBlock('BLOCK');
			$total -= $rec->quantity;
			$total = round($total, 5);
			
			$caption = $batchDef->getFieldCaption();
			$label = (!empty($caption)) ? $caption . ":" : 'lot:';
			
			// Вербализацията на к-то ако е нужно
			if(count($batch) == 1 && (!($batchDef instanceof batch_definitions_Serial))){
				$quantity = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($rec->quantity / $rInfo->quantityInPack);
				$quantity .= " " . cat_UoM::getShortName($rInfo->packagingId);
				$block->append($quantity, "quantity");
			}

			$batch = implode(', ', $batch);
			$string = "{$label} {$batch}" . "<br>";
				
			$block->append($string, "batch");
			$block->removePlaces();
			$block->append2Master();
			$count++;
		}
		
		// Ако има остатък
		if($total > 0 || $total < 0){
			
			// Показва се като 'Без партида'
			$block = clone $tpl->getBlock('BLOCK');
			if($total > 0){
				$batch = "<i style=''>" . tr('Без партида') . "</i>";
				$quantity = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($total / $rInfo->quantityInPack);
				$quantity .= " " . cat_UoM::getShortName($rInfo->packagingId);
			} else {
				$batch = "<i style='color:red'>" . tr('Несъответствие') . "</i>";
				$batch = ht::createHint($batch, 'К-то на разпределените партиди е повече от това на реда', 'error');
				$quantity = '';
				$block->append('border:1px dotted red;', 'BATCH_STYLE');
			}
			
			$block->append($batch, 'batch');
			$block->append($quantity, "quantity");
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
		    $foundBatches[$dRec->batch] = $dRec->batch;
		    if(!array_key_exists($dRec->batch, $batches)){
				$batches[$dRec->batch] = $dRec->quantity;
			}
		}
		
		// Филтриране на партидите
		$Detail->filterBatches($detailRecId, $batches);
		$packName = cat_UoM::getShortName($recInfo->packagingId);
		
		// Подготовка на формата
		$form = cls::get('core_Form');
		$form->title = "Задаване на партидности";
		$form->info = new core_ET(tr("Артикул|*:[#productId#]<br>|Склад|*: [#storeId#]<br>|Количество за разпределяне|*: <b>[#quantity#]</b>"));
		$form->info->replace(cat_Products::getHyperlink($recInfo->productId, TRUE), 'productId');
		$form->info->replace(store_Stores::getHyperlink($storeId, TRUE), 'storeId');
		$form->info->replace($packName, 'packName');
		$form->info->append(cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($recInfo->quantity / $recInfo->quantityInPack), 'quantity');
		
		// Какви са наличните партиди
		$Def = batch_Defs::getBatchDef($recInfo->productId);
		$batchCount = count($batches);
		
		// За всяка партида добавя се като поле
		if(is_array($batches)){
			
			// Ако е сериен номер
			if($Def instanceof batch_definitions_Serial){
				
				// Полетата излизат като списък
				$suggestions = '';
				foreach ($batches as $b => $q){
					$verbal = strip_tags($Def->toVerbal($b));
					$suggestions .= "{$b}={$verbal},";
				}
				$suggestions = trim($suggestions, ',');
				$form->FLD('serials', "set({$suggestions})", 'caption=Партиди,maxRadio=1,class=batch-quantity-fields');
				
				if(count($foundBatches)){
					$defaultBatches = $form->getFieldType('serials')->fromVerbal($foundBatches);
					$form->setDefault('serials', $defaultBatches);
				}
			} else {
				
				// Ако не е сериен номер, всяка партида излиза като ново поле
				$count = 0;
				foreach ($batches as $batch => $quantity){
					$verbal = strip_tags($Def->toVerbal($batch));
					$form->FLD("quantity{$count}", "double(Min=0)", "caption=Налични партиди->{$verbal},unit={$packName},class=batch-quantity-fields");
					if($q = self::fetchField("#detailClassId = {$recInfo->detailClassId} AND #detailRecId = {$recInfo->detailRecId} AND #productId = {$recInfo->productId} AND #batch = '{$batch}'", 'quantity')){
						$form->setDefault("quantity{$count}", ($q / $recInfo->quantityInPack));
					}
				
					$form->FLD("batch{$count}", 'varchar', "input=hidden");
					$form->setDefault("batch{$count}", $batch);
					$count++;
				}
			}
		}
		
		// Добавяне на поле за нова партида
		$autohide = count($batches) ? 'autohide' : '';
		$caption = ($Def->getFieldCaption()) ? $Def->getFieldCaption() : 'Партида';
		$form->FLD('newBatch', 'varchar', "caption=Нова партида->{$caption},placeholder={$Def->placeholder},{$autohide}");
		$form->setFieldType('newBatch', $Def->getBatchClassType());
		
		// Ако е сериен номер полето за к-во се скрива
		if(!($Def instanceof batch_definitions_Serial)){
			$form->FLD('newBatchQuantity', 'double(min=0)', "caption=Нова партида->К-во,placeholder={$Def->placeholder},{$autohide}");
			$form->setField('newBatchQuantity', "unit={$packName}");
		}
		
		$form->input();
		$saveBatches = array();
		
		// След събмит
		if($form->isSubmitted()){
			$r = $form->rec;
			
			$update = $delete = $fields = $error = $error2 = $errorFields = array();
			$total = 0;
			
			// Ако има нова партида, проверява се
			if(!empty($r->newBatch)){
				$r->newBatchQuantity = ($Def instanceof batch_definitions_Serial) ? 1 : $r->newBatchQuantity;
				
				if(empty($r->newBatchQuantity)){
					$form->setError('newBatchQuantity', 'При въвеждането на нова партида е нужно количество');
				} else {
					$total += $r->newBatchQuantity;
					$fields[] = 'newBatchQuantity';
				}
				
				// Трябва да е валидна
				if(!$Def->isValid($r->newBatch, $r->newBatchQuantity, $msg)){
					$form->setError('newBatch', $msg);
				}
			}
			
			// Ако е сериен номер
			if($Def instanceof batch_definitions_Serial){
				$batches = type_Set::toArray($r->serials);
				if(count($batches) > $recInfo->quantity){
					$form->setError('serials', "Серийните номера са повече от цялото количество");
				} else {
					foreach ($batches as $b){
						$saveBatches[$b] = 1 / $recInfo->quantityInPack;
					}
					
					if(is_array($foundBatches)){
						foreach ($foundBatches as $fb){
							if(!array_key_exists($fb, $batches)){
								$delete[] = $fb;
								unset($saveBatches[$fb]);
							}
						}
					}
				}
			} else {
					
				// Обработка и проверка на записите
				foreach (range(0, $batchCount-1) as $i){
					if(empty($r->{"quantity{$i}"})){
						$delete[] = $r->{"batch{$i}"};
					} else {
						$total += $r->{"quantity{$i}"};
						$fields[] = "quantity{$i}";
						$saveBatches[$r->{"batch{$i}"}] = $r->{"quantity{$i}"}  * $recInfo->quantityInPack;
					
						// Проверка на к-то
						if(!deals_Helper::checkQuantity($recInfo->packagingId, $r->{"quantity{$i}"}, $warning)){
							$form->setError("quantity{$i}", $warning);
						}
					}
				}
				
				// Не може да е разпределено по-голямо количество от допустимото
				if($total > ($recInfo->quantity / ($recInfo->quantityInPack))){
					$form->setError(implode(',', $fields), 'Общото количество е над допустимото');
				}
			}
			
			// Новата партида също ще се добави
			if(!empty($r->newBatch) && !empty($r->newBatchQuantity)){
				$batch = $Def->normalize($r->newBatch);
				if(array_key_exists($batch, $batches)){
					$form->setError('newBatch', 'Опитвате се да създадете същестуваща партида');
				} else {
					$saveBatches[$batch] = $r->newBatchQuantity * $recInfo->quantityInPack;
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
						$id = is_numeric($o) ? $o : $o->id;
						self::delete("#detailClassId = {$recInfo->detailClassId} AND #detailRecId = {$recInfo->detailRecId} AND #productId = {$recInfo->productId} AND #batch = '{$b}'");
					}
				}
				
				// Предизвиква се обновяване на документа
				$dRec = cls::get($detailClassId)->fetch($detailRecId);
				cls::get($detailClassId)->save($dRec);
				
				return followRetUrl();
			}
		}
		
		// Добавяне на бутони
		$form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
		
		$attr = arr::make('warning=К-то ще бъде разпределено автоматично по наличните партиди,ef_icon = img/16/arrow_refresh.png, title = Автоматично разпределяне на количеството');
		$attr['onclick'] = "$(this.form).find('.batch-quantity-fields').val('');";
		$form->toolbar->addSbBtn('Автоматично', 'auto', $attr);
		$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
		 
		// Рендиране на формата
		return $this->renderWrapping($form->renderHtml());
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
}
