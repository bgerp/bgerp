<?php



/**
 * Движения на партиди
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_Movements extends core_Detail {
    
	
    /**
     * Заглавие
     */
    public $title = 'Движения на партиди';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_AlignDecimals2,batch_Wrapper, plg_RowNumbering, plg_Sorting, plg_Created, plg_SelectPeriod';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'quantity, operation, date, document=Документ,createdOn=Създаване||Created';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Движение на партида";
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Ключ към мастъра
     */
    public $masterKey = 'itemId';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 150;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('itemId', 'key(mvc=batch_Items)', 'input=hidden,mandatory,caption=Партида');
    	$this->FLD('operation', 'enum(in=Влиза, out=Излиза, stay=Стои)', 'mandatory,caption=Операция');
    	$this->FLD('quantity', 'double', 'input=hidden,mandatory,caption=Количество');
    	$this->FLD('docType', 'class(interface=doc_DocumentIntf)', 'caption=Документ вид');
    	$this->FLD('docId', 'int', 'caption=Документ номер');
    	$this->FLD('date', 'date', 'caption=Дата');
    	
    	$this->setDbIndex('itemId');
    	$this->setDbIndex('operation');
    }
    
    
    /**
     * Подготовка на Детайлите
     */
    function prepareDetail_($data)
    {
    	$this->unloadPlugin('plg_SelectPeriod');
    	parent::prepareDetail_($data);	 
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if(cls::load($rec->docType, TRUE)){
    		$row->document = cls::get($rec->docType)->getLink($rec->docId, 0);
    	} else {
    		$row->document = "<span class='red'>" . tr('Проблем при показването') . "</span>";
    	}
    	
    	if(isset($rec->productId)){
    		$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
    	
    		if($Definition = batch_Defs::getBatchDef($rec->productId)){
    			$row->batch = $Definition->toVerbal($rec->batch);
    		}
    	}
    	
    	if(isset($rec->storeId)){
    		$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    	}
    	
    	$row->operation = "<span style='float:center'>{$row->operation}</span>";
    	switch($rec->operation){
    		case 'in':
    			$row->ROW_ATTR['style'] = 'background-color:rgba(0, 255, 0, 0.1)';
    			break;
    		case 'out':
    			$row->ROW_ATTR['style'] = 'background-color:rgba(255, 0, 0, 0.1)';
    			break;
    		case 'stay':
    			$row->ROW_ATTR['style'] = 'background-color:rgba(0, 0, 255, 0.1)';
    			break;
    	}
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	if(isset($data->masterMvc) && $data->masterMvc instanceof batch_Items) return;
    	$data->listFilter->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));
    	
    	$data->listFilter->FLD('batch', 'varchar(128)', 'caption=Партида,silent');
    	$data->listFilter->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад');
    	$data->listFilter->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
    	$data->listFilter->FLD('document', 'varchar(128)', 'silent,caption=Документ,placeholder=Хендлър');
    	$data->listFilter->setOptions('productId', array('' => '') + batch_Items::getProductsWithDefs());
    	$data->listFilter->FNC('action', 'enum(all=Всички,in=Влиза, out=Излиза, stay=Стои)', 'caption=Операция,input');
    	$data->listFilter->FLD('from', 'date', 'caption=От,silent');
    	$data->listFilter->FLD('to', 'date', 'caption=До,silent');
    	
    	$showFields = arr::make('batch,productId,storeId,action,from,to,selectPeriod,document', TRUE);
    	$data->listFilter->showFields = $showFields;
    	if(haveRole('batch,ceo')){
    		$data->listFilter->showFields = $showFields;
    	} else {
    		if(Request::get('batch', 'varchar')){
    			$data->listFilter->setField('batch', 'input=hidden');
    		}
    		
    		if(Request::get('productId', 'varchar')){
    			$data->listFilter->setField('productId', 'input=hidden');
    		} else {
    			unset($showFields['productId']);
    		}
    		
    		$data->listFilter->showFields = implode(',', $showFields);
    		Request::setProtected('batch');
    	}
    	
    	$documentSuggestions = array();
    	$query = $mvc->getQuery();
    	$query->show("docType,docId");
    	$query->groupBy("docType,docId");
    	while($r = $query->fetch()){
    		if(!cls::load($r->docType, TRUE)) continue;
    		$handle = "#" . cls::get($r->docType)->getHandle($r->docId);
    		$documentSuggestions[$handle] = $handle;
    	}
    	
    	if(count($documentSuggestions)){
    		$data->listFilter->setSuggestions('document', array('' => '') + $documentSuggestions);
    	}
    	
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
    	$data->listFilter->input(NULL, 'silent');
    	$data->listFilter->input();
    	
    	$data->query->EXT('productId', 'batch_Items', 'externalName=productId,externalKey=itemId');
    	$data->query->EXT('storeId', 'batch_Items', 'externalName=storeId,externalKey=itemId');
    	$data->query->EXT('batch', 'batch_Items', 'externalName=batch,externalKey=itemId');
    	
    	$fields = array('RowNumb' => '№', 'batch' => 'Партида', 'productId' => 'Артикул', 'storeId' => 'Склад');
    	$data->listFields = $fields + $data->listFields;
    	
    	if($fRec = $data->listFilter->rec){
    		if(isset($fRec->productId)){
    			$data->query->where("#productId = {$fRec->productId}");
    			unset($data->listFields['productId']);
    		}
    		
    		if(isset($fRec->storeId)){
    			$data->query->where("#storeId = {$fRec->storeId}");
    			unset($data->listFields['storeId']);
    		}
    		
    		if(!empty($fRec->batch)){
    			$data->query->where("#batch = '{$fRec->batch}'");
    		}
    		
    		if(isset($fRec->action) && $fRec->action != 'all'){
    			$data->query->where("#operation = '{$fRec->action}'");
    		}
    		
    		if(isset($fRec->from)){
    			$data->query->where("#date >= '{$fRec->from}'");
    		}
    		
    		if(isset($fRec->to)){
    			$data->query->where("#date <= '{$fRec->to}'");
    		}
    		
    		if(isset($fRec->document)){
    			$document = doc_Containers::getDocumentByHandle($fRec->document);
    			if(is_object($document)){
    				$data->query->where("#docType = {$document->getClassId()} AND #docId = {$document->that}");
    			}
    		}
    	}
    }
    
    
    /**
     * Записва движение на партида от документ
     * 
     * @param mixed $class - ид на документ
     * @param mixed $rec   - ид или запис на документа
     * @return boolean     - успех или не
     */
    public static function saveMovement($containerId)
    {
    	// Кой е документа
    	$doc = doc_Containers::getDocument($containerId);
    	if($doc->isInstanceOf('deals_DealMaster')){
    		
    		// Ако е покупка/продажба трябва да има експедирано/доставено с нея
    		$actions = type_Set::toArray($doc->fetchField('contoActions'));
    		if(!isset($actions['ship'])) return;
    	}
    	
    	// Какви партиди са въведени
    	$jQuery = batch_BatchesInDocuments::getQuery();
    	$jQuery->where("#containerId = {$containerId}");
    	$jQuery->orderBy('id', 'ASC');
    	
    	// За всяка
    	while($jRec = $jQuery->fetch()){
    		$batches = batch_Defs::getBatchArray($jRec->productId, $jRec->batch);
    		$quantity = (count($batches) == 1) ? $jRec->quantity : $jRec->quantity / count($batches);
    		
    		// Записва се движението и
    		foreach ($batches as $key => $b){
    			$result = TRUE;
    			
    			try{
    				$itemId = batch_Items::forceItem($jRec->productId, $key, $jRec->storeId);
    				if(empty($jRec->date)){
    					$jRec->date = $doc->fetchField($doc->valiorFld);
    					cls::get('batch_BatchesInDocuments')->save_($jRec, $date);
    				}
    				
    				// Движението, което ще запишем
    				$mRec = (object)array('itemId'    => $itemId,
    						              'quantity'  => $quantity,
    						              'operation' => $jRec->operation,
    						              'docType'   => $doc->getClassId(),
    						              'docId'     => $doc->that,
    						              'date'	  => $jRec->date,
    				);
    				
    				// Запис на движението
    				$id = self::save($mRec);
    				 
    				// Ако има проблем със записа, сетваме грешка
    				if(!$id){
    					$result = FALSE;
    					break;
    				}
    			} catch(core_exception_Expect $e){bp($doc,$e);
    				reportException($e);
    				
    				// Ако е изникнала грешка
    				$result = FALSE;
    			}
    		}
    	}
		
		// При грешка изтриваме всички записи до сега
		if($result === FALSE){
			self::removeMovement($doc->getInstance(), $doc->that);
			core_Statuses::newStatus('Проблем със записването на партидите');
		}
		
		// Връщаме резултата
		return $result;
    }
    
    
    /**
     * Изтрива записите породени от документа
     * 
     * @param mixed $class - ид на документ
     * @param mixed $rec   - ид или запис на документа
     * @return void        - изтрива движенията породени от документа
     */
    public static function removeMovement($class, $rec)
    {
    	// Изтриване на записите, породени от документа
    	$class = cls::get($class);
    	$rec = $class->fetchRec($rec);
    	
    	static::delete("#docType = {$class->getClassId()} AND #docId = {$rec->id}");
    }
    
    
    /**
     * Изпълнява се след подготовката на листовия изглед
     */
    protected static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
    	$data->title = 'Движения на партида|*';
    	$titles = array();
    	
    	if($fRec = $data->listFilter->rec){
    		if(isset($fRec->productId)){
    			$titles[] = "<b style='color:green'>" . cat_Products::getTitleById($fRec->productId) . "</b>";
    		}
    		
    		if($fRec->batch){
    			$titles[] = "<b style='color:green'>" . cls::get('type_Varchar')->toVerbal(str_replace('|', '/', $fRec->batch)) . "</b>";
    		}
    		
    		if(isset($fRec->storeId)){
    			$titles[] = "<b style='color:green'>" . store_Stores::getTitleById($fRec->storeId) . "</b>";
    		}
    	}
    	
    	if(count($titles)){
    		$data->title .= " |*" . implode(' <b>,</b> ', $titles);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'list'){
    		
    		// Ако потребителя няма определените роли, позволяваме достъп само през защитено урл
    		if(!core_Users::haveRole('ceo,batch', $userId)){
    			
    			// Само през защитено урл имаме достъп
    			if(!Request::get('Protected')){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
}