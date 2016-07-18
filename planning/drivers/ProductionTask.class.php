<?php



/**
 * Драйвер за задачи за производство
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title Задача за производство
 */
class planning_drivers_ProductionTask extends tasks_BaseDriver
{
	
	
	/**
	 * Интерфейси които имплементира
	 */
	public $interfaces = 'planning_DriverIntf';
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver = 'taskPlanning,ceo';
	
	
	/**
	 * Какво да е дефолтното име на задача от драйвера
	 */
	protected $defaultTitle = 'Задача за производство';
	
	
	/**
	 * Кои детайли да се заредят динамично към мастъра
	 */
	protected $details = 'planning_drivers_ProductionTaskParameters,planning_drivers_ProductionTaskDetails,planning_drivers_ProductionTaskProducts';
	
	
	/**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    	$fieldset->FLD('totalWeight', 'cat_type_Weight', 'caption=Общо тегло,input=none');
		$fieldset->FLD('fixedAssets', 'keylist(mvc=planning_AssetResources,select=code,makeLinks)', 'caption=Машини');
		
		$fieldset->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'mandatory,caption=Произвеждане->Артикул,after=fixedAssets,removeAndRefreshForm=packagingId,silent');
		$fieldset->FLD('packagingId', 'key(mvc=cat_UoM,select=name)', 'mandatory,caption=Произвеждане->Опаковка,after=productId,input=hidden,tdClass=small-field nowrap');
		$fieldset->FLD('plannedQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Планувано,after=packagingId');
		$fieldset->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Произвеждане->Склад,mandatory,allowEmpty');
		$fieldset->FLD("indTime", 'time', 'caption=Произвеждане->Време,smartCenter');
		$fieldset->FLD("startTime", 'time', 'caption=Стартиране->Време,smartCenter');
		$fieldset->FLD('totalQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Количество,after=packagingId,input=none');
		$fieldset->FLD('quantityInPack', 'double(smartRound)', 'input=none');
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 */
	public static function on_AfterRecToVerbal(tasks_BaseDriver $Driver, embed_Manager $Embedder, &$row, $rec, $fields = array())
	{
		$row->productId = cat_Products::getShortHyperlink($rec->productId);
		if(!$rec->totalQuantity){
			$rec->totalQuantity = 0;
			$row->totalQuantity = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($rec->totalQuantity);
			$row->totalQuantity = "<span class='quiet'>{$row->totalQuantity}</span>";
		}
		
		$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
		$row->packagingId = cat_UoM::getShortName($rec->packagingId);
		
		deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param tasks_BaseDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm(tasks_BaseDriver $Driver, embed_Manager $Embedder, &$data)
	{
		$form = &$data->form;
		$rec = $form->rec;
		
		if(empty($rec->originId)){
			$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
			$rec->originId = $firstDoc->fetchField('containerId');
		}
		
		// За произвеждане може да се избере само артикула от заданието
		$origin = doc_Containers::getDocument($rec->originId);
		$productId = $origin->fetchField('productId');
		$bomRec = cat_Products::getLastActiveBom($productId, 'production');
		if(!$bomRec){
			$bomRec = cat_Products::getLastActiveBom($productId, 'sales');
		}
		
		$products[$productId] = cat_Products::getTitleById($productId, FALSE);
		
		// и ако има рецепта артикулите, които са етапи от нея
		if(!empty($bomRec)){
			$sQuery = cat_BomDetails::getQuery();
			$sQuery->where("#bomId = {$bomRec->id} AND #type = 'stage'");
			$sQuery->show('resourceId');
			while($sRec = $sQuery->fetch()){
				$products[$sRec->resourceId] = cat_Products::getTitleById($sRec->resourceId, FALSE);
			}
		}
		
		// Ако има избран артикул, той винаги присъства в опциите
		if(isset($rec->productId)){
			if(!isset($products[$rec->productId])){
				$products[$rec->productId] = cat_Products::getTitleById($rec->productId, FALSE);
			}
		}
		
		// Добавяме допустимите опции
		$form->setOptions('productId', array('' => '') + $products);
		if(count($products) == 1){
			$form->setDefault('productId', key($products));
		}
		
		// Ако задачата е дефолтна за артикула, задаваме и дефолтите
		if(isset($rec->systemId)){
			$originDoc = doc_Containers::getDocument($rec->originId);
			$originRec = $originDoc->fetch();
			
			$tasks = cat_Products::getDefaultProductionTasks($originRec->productId, $originRec->quantity);
			if(isset($tasks[$rec->systemId])){
				foreach (array('plannedQuantity', 'productId', 'quantityInPack', 'packagingId') as $fld){
					$form->setDefault($fld, $tasks[$rec->systemId]->{$fld});
				}
			}
		}

		if(isset($rec->productId)){
			$packs = cat_Products::getPacks($rec->productId);
			$form->setOptions('packagingId', $packs);
			$form->setDefault('packagingId', key($packs));
			
			$productInfo = cat_Products::getProductInfo($rec->productId);
			if(!isset($productInfo->meta['canStore'])){
				$measureShort = cat_UoM::getShortName($rec->packagingId);
				$form->setField('plannedQuantity', "unit={$measureShort}");
			} else {
				$form->setField('packagingId', 'input');
			}
			
			$jobRec = $origin->fetch();
			if($rec->productId == $jobRec->productId){
				$toProduce = $jobRec->quantity - $jobRec->quantityProduced;
				if($toProduce > 0){
					$form->setDefault('plannedQuantity', $toProduce);
				}
			}
		}
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param tasks_BaseDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param core_Form $form
	 */
	public static function on_AfterInputEditForm(tasks_BaseDriver $Driver, embed_Manager $Embedder, &$form)
	{
		$rec = $form->rec;
		if($form->isSubmitted()){
			$pInfo = cat_Products::getProductInfo($rec->productId);
    		$rec->quantityInPack = ($pInfo->packagings[$rec->packagingId]) ? $pInfo->packagings[$rec->packagingId]->quantity : 1;
		}
	}
	
	
	/**
     * Обновяване на данните на мастъра
     * 
     * @param stdClass $rec - запис на ембедъра
     * @param void
     */
	public function updateEmbedder(&$rec)
	{
		// Колко е общото к-во досега
	    $dQuery = planning_drivers_ProductionTaskDetails::getQuery();
		$dQuery->where("#taskId = {$rec->id}");
		$dQuery->where("#type = 'product'");
		$dQuery->where("#state != 'rejected'");
		$dQuery->XPR('sumQuantity', 'double', 'SUM(#quantity)');
		$dQuery->XPR('sumWeight', 'double', 'SUM(#weight)');
		$dQuery->show('sumQuantity,sumWeight');
		 
		$res = $dQuery->fetch();
		 
		// Преизчисляваме общото тегло
		$rec->totalWeight = $res->sumWeight;
		$rec->totalQuantity = $res->sumQuantity;
		 
		// Изчисляваме колко % от зададеното количество е направено
		@$rec->progress = round($rec->totalQuantity / $rec->plannedQuantity, 2);
		
		// Записваме операцията в регистъра
		$taskOrigin = doc_Containers::getDocument($rec->originId);
		$quantity = $rec->totalQuantity * $rec->quantityInPack;
		
		planning_TaskActions::add($rec->id, $rec->productId, 'product', $taskOrigin->that, $quantity);
	}


    /**
     * Добавя ключови думи за пълнотекстово търсене
     * 
     * @param tasks_BaseDriver $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $res
     * @param stdClass $rec
     */
    public static function on_AfterGetSearchKeywords(tasks_BaseDriver $Driver, embed_Manager $Embedder, &$res, $rec)
    {
    	if(empty($rec->id)) return;
    	
    	$details = $Driver->getDetails();
    	
    	if(is_array($details)){
    		foreach ($details as $Detail){
    			$Detail = cls::get($Detail);
    			
    			$dQuery = $Detail->getQuery();
    			$dQuery->where("#taskId = {$rec->id}");
    			
    			$detailsKeywords = '';
    			while($dRec = $dQuery->fetch()){
    				
    				if($dRec->serial){
    					$detailsKeywords .= " " . plg_Search::normalizeText($Detail->getVerbal($dRec, 'serial'));
    				}
    					
    				if($dRec->fixedAsset){
    					$detailsKeywords .= " " . plg_Search::normalizeText($Detail->getVerbal($dRec, 'fixedAsset'));
    				}
    			}
    			
    			// Добавяме новите ключови думи към старите
    			$res = " " . $res . " " . $detailsKeywords;
    		}
    	}
    }
    
    
    /**
     * Връща полетата, които ще се показват в антетката
     * 
     * @param stdObject $rec
     * @param stdObject $row
     * 
     * @return array
     */
    public static function prepareFieldLetterHeaded($rec, $row)
    {
        $resArr = array();
        
        $resArr['productId'] = array('name' => tr('Артикул'), 'val' =>"[#productId#]");
        
        $resArr['plannedQuantity'] =  array('name' => tr('Количество'), 'val' => tr("|*<div class='nowrap'><span style='font-weight:normal'>|Плануванo|*</span>: [#plannedQuantity#]
        		<!--ET_BEGIN totalQuantity--><br><span style='font-weight:normal'>|Произведено|*</span>: [#totalQuantity#]<!--ET_END totalQuantity--></div>"));
        
        $resArr['packagingId'] = array('name' => tr('Мярка'), 'val' =>"[#packagingId#]");
        
        if (!empty($row->totalWeight)) {
            $resArr['totalWeight'] =  array('name' => tr('Общо тегло'), 'val' =>"[#totalWeight#]");
        }
        
        if (!empty($row->fixedAssets)) {
            $resArr['fixedAssets'] =  array('name' => tr('Машини'), 'val' =>"[#fixedAssets#]");
        }
        
        if(!empty($row->indTime) || !empty($row->startTime)){
        	$resArr['indTime'] =  array('name' => tr('Времена'), 'val' => tr("|*<!--ET_BEGIN indTime--><div class='nowrap'><span style='font-weight:normal'>|Изпълнение|*:</span> [#indTime#]</div><!--ET_END indTime--><!--ET_BEGIN startTime--><div class='nowrap'><span style='font-weight:normal'>|Стартиране|*:</span> [#startTime#]</div><!--ET_END startTime-->"));
        }
       
        $resArr['progressBar'] =  array('name' => tr('Прогрес'), 'val' =>"[#progressBar#] [#progress#]");
        
        if (!empty($row->originId)) {
            $resArr['originId'] =  array('name' => tr('Информация'), 'val' => tr("|*<div class='nowrap'><span style='font-weight:normal'>|Задание|*</span>: [#originId#]<br><span style='font-weight:normal'>|Склад|*</span>: [#storeId#]</div>"));
        }
        
        if (!empty($row->timeStart)) {
        	$resArr['timeStart'] =  array('name' => tr('Начало'), 'val' =>"[#timeStart#]");
        }
        
        if (!empty($row->timeDuration)) {
        	$resArr['timeDuration'] =  array('name' => tr('Продължителност'), 'val' =>"[#timeDuration#]");
        }
        
        if (!empty($row->timeEnd)) {
        	$resArr['timeEnd'] =  array('name' => tr('Краен срок'), 'val' =>"[#timeEnd#] [#remainingTime#]");
        }
        
        if (!empty($row->expectedTimeStart)) {
        	$resArr['expectedTimeStart'] =  array('name' => tr('Очаквано начало'), 'val' =>"[#expectedTimeStart#]");
        }
        
        if (!empty($row->expectedTimeEnd)) {
        	$resArr['expectedTimeEnd'] =  array('name' => tr('Очакван край'), 'val' =>"[#expectedTimeEnd#]");
        }
        
        return $resArr;
    }
    
    
    /**
     * Преди клонирането на записа
     * 
     * @param tasks_BaseDriver $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $rec
     * @param stdClass $nRec
     */
    public static function on_BeforeSaveCloneRec(tasks_BaseDriver $Driver, embed_Manager &$Embedder, &$rec, &$nRec)
    {
    	unset($nRec->totalWeight);
    	unset($nRec->systemId);
    	
    	// Добавяме артикулите към детайлите за клониране
    	$cloneDetails = $Embedder->cloneDetailes;
    	$cloneDetails = arr::make($cloneDetails, TRUE);
    	$cloneDetails['planning_drivers_ProductionTaskProducts'] = 'planning_drivers_ProductionTaskProducts';
    	$Embedder->cloneDetailes = implode(',', $cloneDetails);
    }
    
    
    /**
     * Преди проверка за права
     * 
     * @param tasks_BaseDriver $Driver
     * @param embed_Manager $Embedder
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles(tasks_BaseDriver $Driver, embed_Manager $Embedder, &$requiredRoles, $action, $rec, $userId = NULL)
    {
    	if($action == 'reject' && isset($rec)){
    		
    		// Ако има прогрес, задачата не може да се оттегля
    		if(planning_drivers_ProductionTaskDetails::fetchField("#taskId = {$rec->id} AND #state != 'rejected'")){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * След успешен запис
     */
    public static function on_AfterCreate(tasks_BaseDriver $Driver, embed_Manager $Embedder, &$rec)
    {
    	if(isset($rec->originId)){
    		$originDoc = doc_Containers::getDocument($rec->originId);
    		$originRec = $originDoc->fetch();
    		
    		// Ако е по източник
    		if(isset($rec->systemId)){
    			$tasks = cat_Products::getDefaultProductionTasks($originRec->productId, $originRec->quantity);
    			if(isset($tasks[$rec->systemId])){
    				$def = $tasks[$rec->systemId];
    			
    				// Намираме на коя дефолтна задача отговаря и извличаме продуктите от нея
    				$r = array();
    				foreach (array('production' => 'product', 'input' => 'input', 'waste' => 'waste') as $var => $type){
    					if(is_array($def->products[$var])){
    						foreach ($def->products[$var] as $p){
    							$p = (object)$p;
    							$nRec = new stdClass();
    							$nRec->taskId         = $rec->id;
    							$nRec->packagingId    = $p->packagingId;
    							$nRec->quantityInPack = $p->quantityInPack;
    							$nRec->plannedQuantity = $p->packQuantity * $rec->plannedQuantity * $rec->quantityInPack;
    							$nRec->productId      = $p->productId;
    							$nRec->type			  = $type;
    							$nRec->storeId		  = $rec->storeId;
    							
    							planning_drivers_ProductionTaskProducts::save($nRec);
    						}
    					}
    				}
    			}
    		}
    	}
    	
    	// Обновяване на информацията за параметрите
    	planning_drivers_ProductionTaskParameters::saveProductParams($rec->id, $rec->productId);
    }
}
