<?php



/**
 * Дефиниции на партиди
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_Defs extends embed_Manager {
    
	
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'batch_BatchTypeIntf';
	
	
    /**
     * Заглавие
     */
    public $title = 'Дефиниции на партиди';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, batch_Wrapper, plg_Modified';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'productId,driverClass=Тип,modifiedOn,modifiedBy';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Дефиниция на партидa";
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'batch, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'batch,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'batch,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'batch, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'batch/tpl/SingleLayoutDefs.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,before=driverClass');
    
    	$this->setDbUnique('productId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	
    	$storable = cat_Products::getByProperty('canStore');
    	$form->setOptions('productId', array('' => '') + $storable);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
    	$row->ROW_ATTR['class'] = 'state-active';
    }
    
    
    /**
     * Връща дефиницията на партидата за продукта, ако има
     * 
     * @param int $productId - ид на продукт
     * @return batch_drivers_Proto|FALSE $BatchClass - инстанцията на класа или FALSE ако няма
     */
    public static function getBatchDef($productId)
    {
    	// Намираме записа за артикула
    	$rec = self::fetch("#productId = '{$productId}'");
    	
    	// Опитваме се да върнем инстанцията
    	if(cls::load($rec->driverClass, TRUE)){
    		$BatchClass = cls::get($rec->driverClass);
    		$BatchClass->setRec($rec);
    		
    		return $BatchClass;
    	}
    	
    	// Ако не може да се намери
    	return FALSE;
    }
    
    
    /**
     * Разбира партидата на масив от партиди
     */
    public static function getBatchArray($productId, $batch)
    {
    	$array = array($batch => $batch);
    	
    	$DefClass = self::getBatchDef($productId);
    	if(is_object($DefClass)){
    		$array = $DefClass->makeArray($batch);
    	}
    	
    	return $array;
    }
    
    
    /**
     * Добавя партидите към стринг
     * 
     * @param text $batch - партида или партиди
     * @param text $string - към кой стринг да се добавят
     * @return void
     */
    public static function appendBatch($productId, $batch, &$string = '')
    {
    	if(!empty($batch)){
    		$batch = self::getBatchArray($productId, $batch);
    		
    		foreach ($batch as $key => &$b){
    			if(!Mode::isReadOnly() && haveRole('powerUser')){
    				if(!haveRole('batch,ceo')){
    					Request::setProtected('batch');
    				}
    				$b = ht::createLink($b, array('batch_Movements', 'list', 'batch' => $key));
    			}
    		}
    		
    		$count = count($batch);
    		$batch = implode(', ', $batch);
    		
    		$batch = "[html]{$batch}[/html]";
    		$string .= ($string) ? "\n" : '';
    		
    		$label = ($count == 1) ? 'lot' : 'serials';
    		$string .= "{$label}: {$batch}";
    	}
    }
    
    
    /**
     * Връща масив с ид-та на артикулите с дефиниции на партида
     * 
     * @return array $result - масив
     */
    public static function getProductsWithDefs()
    {
    	$result = array();
    	
    	$query = self::getQuery();
    	$query->show('productId');
    	while($rec = $query->fetch()){
    		$result[$rec->productId] = $rec->productId;
    	}
    	
    	return $result;
    }
    
    
    /**
     * Пушва информацията за партидите в дийл интерфейса
     * 
     * @param int $productId - ид на артикул
     * @param varchar $batch - партиден номер
     * @param bgerp_iface_DealAggregator $aggregator - агрегатор на сделката
     */
    public static function pushBatchInfo($productId, $batch, bgerp_iface_DealAggregator &$aggregator)
    {
    	if(!core_Packs::isInstalled('batch')) return;
    	
    	if(!empty($batch)){
    		if($batches = batch_Defs::getBatchArray($productId, $batch)){
    			if(is_array($batches)){
    				foreach ($batches as $b){
    					if(!isset($aggregator->productBatches)){
    						$aggregator->productBatches = array();
    					}
    					
    					if(!isset($aggregator->productBatches[$productId])){
    						$aggregator->productBatches[$productId] = array();
    					}
    					
    					$aggregator->productBatches[$productId][$b] = $b;
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Форсира партидна дефиниция на артикула ако може
     * Партидната дефиниция се намира по следния приоритет:
     * 1. Ако артикула е базиран на прототип неговата партидна дефиниция
     * 2. Ако артикула е в папка на категория и тя има избрана дефолтна дефиниция
     * 3. От драйвера на артикула, ако върне подходящ клас
     * 
     * @param int $productId - ид на артикул
     * @return int $id - форсирания запис
     */
    public static function force($productId)
    {
    	// Трябва да е подаден складируем артикул
    	expect($productRec = cat_Products::fetchRec($productId));
    	expect($productRec->canStore == 'yes');
    	
    	// Ако има съществуваща дефиниция, не създаваме нова
    	if($id = static::fetchField("#productId = {$productRec->id}", 'id')) return $id;
    	
    	// Ако артикула е базиран на прототип, който има партида копираме му я
    	if(isset($productRec->proto)){
    		if($exRec = static::fetch("#productId = {$productRec->proto}")){
    			unset($exRec->id,$exRec->modifiedOn,$exRec->modifiedBy);
    			$exRec->productId = $productRec->id;
    			
    			// Записваме точно копие на дефиницията от прототипа
    			return self::save($exRec);
    		}
    	}
    	
    	// Ако артикула е в папка на категория, с избрана партида връщаме нея
    	$folderClassName = doc_Folders::fetchCoverClassName($productRec->folderId);
    	if($folderClassName == 'cat_Categories'){
    		$folderObjectId = doc_Folders::fetchCoverId($productRec->folderId);
    		if($categoryDefRec = batch_CategoryDefinitions::fetch("#categoryId = {$folderObjectId}")){
    			unset($categoryDefRec->id, $categoryDefRec->categoryId);
    			$categoryDefRec->productId = $productRec->id;
    				
    			// Записваме точно копие на дефиницията от категорията
    			return self::save($categoryDefRec);
    		}
    	}
    	
    	// Ако горните условия не са изпълнени, питаме драйвера дали може да върне дефиниция
    	$Driver = cat_Products::getDriver($productRec);
    	$batchClass = $Driver->getDefaultBatchDef($productRec);
    	if(!empty($batchClass)){
    		$BatchType = cls::get($batchClass);
    		$rec = (object)array('driverClass' => $BatchType->getClassId(), 'productId' => $productRec->id);
    	
    		// Записваме дефолтната партида
    		return self::save($rec);
    	}
    }
}