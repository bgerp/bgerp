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
     * Единично заглавие
     */
    public $singleTitle = 'Партида';
    
    
    /**
     * Плъгини за зареждане
     */
    //public $loadList = 'plg_RowTools2, plg_Created, plg_SaveAndNew';
    
    
    /**
     * Кой може да добавя?
     */
    //public $canAdd = 'ceo, acc, purchase';
    
    
    /**
     * Кой може да редактира?
     */
    //public $canEdit = 'ceo, acc, purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    //public $canDelete = 'ceo, acc, purchase';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'admin,debug';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'id,date,containerId=Документ,productId=Артикул,packagingId=Опаковка,quantityInPack=К-во в опаковка,quantity=Количество,batch=Партида,operation=Операция,storeId=Склад';
	
	
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('detailClassId', 'class(interface=core_ManagerIntf)', 'caption=Детайл,mandatory,silent,input=hidden,remember');
    	$this->FLD('detailRecId', 'int', 'caption=Ред от детайл,mandatory,silent,input=hidden,remember');
    	$this->FLD('productId', 'key(mvc=cat_Products)', 'caption=Артикул,mandatory,silent,input=hidden,remember');
    	$this->FLD('packagingId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory,smartCenter,input=hidden,tdClass=small-field nowrap');
    	$this->FLD('quantity', 'double(decimals=2)', 'caption=Количество,input=none');
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
		//$mvc->truncate();
		$row->containerId = doc_Containers::getDocument($rec->containerId)->getLink(0);
		$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
	}
	
	
	public static function sync($detailClassId, $detailRecId, $batch, $quantity = NULL)
	{
		expect($Detail = cls::get($detailClassId));
		expect($dRec = $Detail->fetch($detailRecId));
		
		if(empty($batch)){
			return self::delete("#detailClassId = {$Detail->getClassId()} AND #detailRecId = {$detailRecId}");
		} 
		
		$rec = $Detail->getRowInfo($dRec);
		$rec->batch = $batch;
		$rec->quantity = isset($quantity) ? $quantity : $rec->quantity;
		$rec->detailClassId = $Detail->getClassId();
		$rec->detailRecId = $dRec->id;
		
		if($id = self::fetchField("#detailClassId = {$rec->detailClassId} AND #detailRecId = {$rec->detailRecId}", 'id')){
			$rec->id = $id;
		}
		
		return self::save($rec);
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
		
		$query = self::getQuery();
		$query->where("#detailClassId = {$detailClassId} AND #detailRecId = {$detailRecId}");
		$rInfo = cls::get($detailClassId)->getRowInfo($detailRecId);
		$batchDef = batch_Defs::getBatchDef($rInfo->productId);
		
		$count = 0;
		$total = $rInfo->quantity;
		while($rec = $query->fetch()){
			$batch = batch_Defs::getBatchArray($rec->productId, $rec->batch);
			
			foreach ($batch as $key => &$b){
				if(!Mode::isReadOnly() && haveRole('powerUser')){
					if(!haveRole('batch,ceo')){
						Request::setProtected('batch');
					}
					$b = ht::createLink($b, array('batch_Movements', 'list', 'batch' => $key));
				}
			}
			
			$string = '';
			$block = clone $tpl->getBlock('BLOCK');
			$total -= $rec->quantity;
			
			$label = ($batchDef instanceof batch_definitions_Serial) ? '' : 'lot:';
			
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
		
		if($total > 0){
			$block = clone $tpl->getBlock('BLOCK');
			$batch = "<i style=''>" . tr('Без партида') . "</i>";
			$block->append($batch, 'batch');
			$quantity = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($total / $rInfo->quantityInPack);
			$quantity .= " " . cat_UoM::getShortName($rInfo->packagingId);
			$block->append($quantity, "quantity");
			$block->removePlaces();
			$block->append2Master();
		}
		
		$tpl->removePlaces();
		
		return $tpl;
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
		
		// Кои са наличните партиди към момента
		$batches = batch_Items::getBatchQuantitiesInStore($recInfo->productId, $recInfo->storeId, $recInfo->date);
		
		// Кои са въведените партиди от документа
		$dQuery = self::getQuery();
		$dQuery->where("#detailClassId = {$detailClassId} AND #detailRecId = {$detailRecId}");
		while ($dRec = $dQuery->fetch()){
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
		$form->info->replace(store_Stores::getHyperlink($recInfo->storeId, TRUE), 'storeId');
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
				$form->FLD('serials', "set({$suggestions})", 'caption=Партиди,maxRadio=1');
				
				$query = self::getQuery();
				$query->where("#detailClassId = {$recInfo->detailClassId} AND #detailRecId = {$recInfo->detailRecId} AND #productId = {$recInfo->productId}");
				$query->show('batch');
				$foundBatches = arr::extractValuesFromArray($query->fetchAll(), 'batch');
				if(count($foundBatches)){
					$defaultBatches = $form->getFieldType('serials')->fromVerbal($foundBatches);
					$form->setDefault('serials', $defaultBatches);
				}
			} else {
				
				// Ако не е сериен номер, всяка партида излиза като ново поле
				$count = 0;
				foreach ($batches as $batch => $quantity){
					$verbal = strip_tags($Def->toVerbal($batch));
					$form->FLD("quantity{$count}", "double(min=0)", "caption=Налични партиди->{$verbal},unit={$packName}");
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
		$caption = ($Def->fieldCaption) ? $Def->fieldCaption : 'Партида';
		$form->FLD('newBatch', 'varchar', "caption=Нова партида->{$caption},placeholder={$Def->placeholder},autohide");
		$form->setFieldType('newBatch', $Def->getBatchClassType());
		
		// Ако е сериен номер полето за к-во се скрива
		if(!($Def instanceof batch_definitions_Serial)){
			$form->FLD('newBatchQuantity', 'double(min=0)', "caption=Нова партида->К-во,placeholder={$Def->placeholder},autohide");
		}
		
		$form->input();
		
		// След събмит
		if($form->isSubmitted()){
			$r = $form->rec;
			
			$update = $delete = $fields = $error = array();
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
						$obj = clone $recInfo;
						$obj->quantity = 1 / $recInfo->quantityInPack;
						$obj->batch = $b;
						if($id = self::getId($obj->detailClassId, $obj->detailRecId, $obj->productId, $obj->batch)){
							$obj->id = $id;
						}
						$update[$obj->batch] = $obj;
					}
					
					if(is_array($foundBatches)){
						foreach ($foundBatches as $fb){
							if(!array_key_exists($fb, $batches)){
								if($id = self::getId($recInfo->detailClassId, $recInfo->detailRecId, $recInfo->productId, $fb)){
									$delete[] = $id;
								}
							}
						}
					}
				}
			} else {
					
				// Обработка и проверка на записите
				foreach (range(0, $batchCount-1) as $i){
					$obj = clone $recInfo;
					$obj->quantity = $r->{"quantity{$i}"}/ $recInfo->quantityInPack;
					$obj->batch = $r->{"batch{$i}"};
					if($id = self::getId($obj->detailClassId, $obj->detailRecId, $obj->productId, $obj->batch)){
						$obj->id = $id;
					}
				
					if(!isset($obj->quantity)){
						if(isset($obj->id)){
							$delete[] = $obj;
						}
					} else {
						$total += $obj->quantity;
						$fields[] = "quantity{$i}";
						$update[$obj->batch] = $obj;
					}
				}
					
				// Не може да е разпределено по-голямо количество от допустимото
				if($total > $recInfo->quantity / ($recInfo->quantityInPack)){
					$form->setError(implode(',', $fields), 'Общото количество е над допустимото');
				}
			}
			
			// Новата партида също ще се добави
			if(!empty($r->newBatch) && !empty($r->newBatchQuantity)){
				$obj = clone $recInfo;
				$obj->quantity = $r->newBatchQuantity / $recInfo->quantityInPack;
				$obj->batch = $Def->normalize($r->newBatch);
				
				if(array_key_exists($obj->batch, $update)){
					$form->setError('newBatch', 'Опитвате се да създадете същестуваща партида');
				} else {
					$update[] = $obj;
				}
			}
			
			if(!$form->gotErrors()){
				// Ъпдейт/добавяне на записите, които трябва
				if(count($update)){
					foreach ($update as $o){
						$o->quantity *= $recInfo->quantityInPack;
					}
						
					$this->saveArray($update);
				}
					
				// Изтриване
				if(count($delete)){
					foreach ($delete as $o){
						$id = is_numeric($o) ? $o : $o->id;
						self::delete($id);
					}
				}
					
				return followRetUrl();
			}
		}
		
		// Добавяне на бутони
		$form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
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
	 */
	public static function getId($detailClassId, $detailRecId, $productId, $batch)
	{
		return self::fetchField("#detailClassId = {$detailClassId} AND #detailRecId = {$detailRecId} AND #productId = {$productId} AND #batch = '{$batch}'");
	}
}