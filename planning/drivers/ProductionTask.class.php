<?php



/**
 * Драйвер за Производствени операции
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title Производствени операции
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
	protected $defaultTitle = 'Производствена операция';
	
	
	/**
	 * Кои детайли да се заредят динамично към мастъра
	 */
	protected $details = 'planning_drivers_ProductionTaskDetails,planning_drivers_ProductionTaskProducts';
	
	
	/**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    	$fieldset->FLD('totalWeight', 'cat_type_Weight', 'caption=Общо тегло,input=none');
		$fieldset->FLD('description', 'richtext(rows=2,bucket=Notes)', 'caption=Описание');
		$fieldset->FLD('showadditionalUom', 'enum(no=Не,yes=Да)', 'caption=Доп. мярка');
		
		$fieldset->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'mandatory,caption=Произвеждане->Артикул,removeAndRefreshForm=packagingId,silent');
		$fieldset->FLD('packagingId', 'key(mvc=cat_UoM,select=name)', 'mandatory,caption=Произвеждане->Опаковка,after=productId,input=hidden,tdClass=small-field nowrap,removeAndRefreshForm,silent');
		$fieldset->FLD('plannedQuantity', 'double(smartRound,Min=0)', 'mandatory,caption=Произвеждане->Планирано,after=packagingId');
		$fieldset->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Произвеждане->Склад,input=none');
		$fieldset->FLD("startTime", 'time(noSmart)', 'caption=Норма->Произ-во,smartCenter');
		$fieldset->FLD("indTime", 'time(noSmart)', 'caption=Норма->Пускане,smartCenter');
		$fieldset->FLD('totalQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Количество,after=packagingId,input=none');
		$fieldset->FLD('quantityInPack', 'double(smartRound)', 'input=none');
		$fieldset->FLD('scrappedQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Брак,input=none');
	}
	
	
	/**
	 * Информация за произведения артикул по задачата
	 * 
	 * @param stdClass $rec
	 * @return stdClass $arr
	 * 			  o productId         - ид на артикула
	 * 			  o packagingId       - ид на опаковката
	 * 			  o quantityInPack    - количество в опаковка
	 * 			  o plannedQuantity   - планирано количество
	 * 			  o wastedQuantity    - бракувано количество
	 * 			  o totalQuantity     - прозведено количество
	 * 			  o storeId           - склад
	 * 			  o fixedAssets       - машини
	 * 			  o indTime           - време за пускане
	 * 			  o startTime         - време за прозиводство
	 * 			  o showadditionalUom - показване на допълнителна мярка
	 */
	public function getProductDriverInfo($rec)
	{
		$arr = array();
		
		$arr['productId']         = $rec->productId;
		$arr['packagingId']       = $rec->packagingId;
		$arr['quantityInPack']    = $rec->quantityInPack;
		$arr['plannedQuantity']   = $rec->plannedQuantity;
		$arr['wastedQuantity']    = $rec->totalQuantity;
		$arr['totalQuantity']     = $rec->totalQuantity;
		$arr['storeId']           = $rec->storeId;
		$arr['fixedAssets']       = $rec->fixedAssets;
		$arr['indTime']           = $rec->indTime;
		$arr['startTime']         = $rec->startTime;
		$arr['showadditionalUom'] = $rec->showadditionalUom;
		
		return (object)$arr;
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 */
	public static function on_AfterRecToVerbal(tasks_BaseDriver $Driver, embed_Manager $Embedder, &$row, $rec, $fields = array())
	{
		$row->productId = cat_Products::getShortHyperlink($rec->productId);
		foreach (array('totalQuantity', 'scrappedQuantity') as $fld){
			if(!$rec->{$fld}){
				$rec->{$fld} = 0;
				$row->{$fld} = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($rec->{$fld});
				$row->{$fld} = "<span class='quiet'>{$row->{$fld}}</span>";
			}
		}
		
		if(isset($rec->storeId)){
			$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
		}
		
		$row->packagingId = cat_UoM::getShortName($rec->packagingId);
		
		deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
		
		// Ако няма зададено очаквано начало и край, се приема, че са стандартните
		$rec->expectedTimeStart = ($rec->expectedTimeStart) ? $rec->expectedTimeStart : ((isset($rec->timeStart)) ? $rec->timeStart : NULL);
		$rec->expectedTimeEnd = ($rec->expectedTimeEnd) ? $rec->expectedTimeEnd : ((isset($rec->timeEnd)) ? $rec->timeEnd : NULL);
		
		// Проверяване на времената
		foreach (array('expectedTimeStart' => 'timeStart', 'expectedTimeEnd' => 'timeEnd') as $eTimeField => $timeField){
			
			// Вербализиране на времената
			$DateTime = core_Type::getByName("datetime(format=d.m H:i)");
			$row->{$timeField} = $DateTime->toVerbal($rec->{$timeField});
			$row->{$eTimeField} = $DateTime->toVerbal($rec->{$eTimeField});
			
			// Ако има очаквано и оригинално време
			if(isset($rec->{$eTimeField}) && isset($rec->{$timeField})){
				
				// Колко е разликата в минути между тях?
				$diffVerbal = NULL;
				$diff = dt::secsBetween($rec->{$eTimeField}, $rec->{$timeField});
				$diff = ceil($diff / 60);
				
				// Ако има разлика
				if($diff != 0){
					
					// Подготовка на показването на разликата
					$diffVerbal = cls::get('type_Int')->toVerbal($diff);
					$diffVerbal = ($diff > 0) ? "<span class='red'>+{$diffVerbal}</span>" : "<span class='green'>{$diffVerbal}</span>";
				}
			
				// Ако има разлика
				if(isset($diffVerbal)){
					
					// Показва се след очакваното време в скоби, с хинт оригиналната дата
					$hint = tr("Зададено") . ": {$row->{$timeField}}";
					$diffVerbal = ht::createHint($diffVerbal, $hint, 'notice', TRUE, array('height' => '12', 'width' => '12'));
					$row->{$eTimeField} .= " <span style='font-weight:normal'>({$diffVerbal})</span>";
				}
			}
		}
	}
	
	
	/**
	 * Подготовка за рендиране на единичния изглед
	 *
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $res
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareSingle(planning_drivers_ProductionTask $Driver, embed_Manager $Embedder, &$res, &$data)
	{
		$rec = $data->rec;
		
		$d = new stdClass();
    	$d->masterId = $rec->id;
    	$d->masterClassId = planning_Tasks::getClassId();
    	if($rec->state == 'closed' || $rec->state == 'stopped' || $rec->state == 'rejected'){
    		$d->noChange = TRUE;
    		unset($data->editUrl);
    	}
    	
    	cat_products_Params::prepareParams($d);
    	$data->paramData = $d;
	}
	
	
	/**
	 * След рендиране на единичния изглед
	 *
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param core_ET $tpl
	 * @param stdClass $data
	 */
	public static function on_AfterRenderSingle(planning_drivers_ProductionTask $Driver, embed_Manager $Embedder, &$tpl, $data)
	{
		if(isset($data->paramData)){
			$paramTpl = cat_products_Params::renderParams($data->paramData);
			$tpl->append($paramTpl, 'PARAMS');
		}
		
		// Ако има записани допълнителни полета от артикула
		if(is_array($data->rec->additionalFields)){
			$productFields = planning_Tasks::getFieldsFromProductDriver($data->rec->productId);
		
			// Добавяне на допълнителните полета от артикула
			foreach ($data->rec->additionalFields as $field => $value){
				if(!isset($value) || $value === '') continue;
				if(!isset($productFields[$field])) continue;
				 
				// Рендират се
				$block = clone $tpl->getBlock('ADDITIONAL_VALUE');
				$field1 = $productFields[$field]->caption;
				$field1 = explode('->', $field1);
				$field1 = (count($field1) == 2) ? $field1[1] : $field1[0];
				 
				$block->placeArray(array('value' => $productFields[$field]->type->toVerbal($value), 'field' => tr($field1)));
				$block->removePlaces();
				$tpl->append($block, 'ADDITIONAL');
			}
		}
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
		$form->setField('title', 'input=hidden');
		
		// За произвеждане може да се избере само артикула от заданието
		$origin = doc_Containers::getDocument($rec->originId);
		$productId = $origin->fetchField('productId');
		
		if(empty($rec->id)){
			$form->setDefault('description', cat_Products::fetchField($productId, 'info'));
		}
		
		// Добавяме допустимите опции
		$products = cat_Products::getByProperty('canManifacture');
		if(isset($rec->productId)){
			if(!isset($products[$rec->productId])){
				$products[$rec->productId] = cat_Products::getTitleById($rec->productId, FALSE);
			}
		}
		
		$form->setOptions('productId', array('' => '') + $products);
		
		if(count($products) == 1){
			$form->setDefault('productId', key($products));
		}
		
		$originDoc = doc_Containers::getDocument($rec->originId);
		$originRec = $originDoc->fetch();
		
		// Ако задачата е дефолтна за артикула, задаваме и дефолтите
		if(isset($rec->systemId)){
			
			$tasks = cat_Products::getDefaultProductionTasks($originRec->productId, $originRec->quantity);
			if(isset($tasks[$rec->systemId])){
				foreach (array('plannedQuantity', 'productId', 'quantityInPack', 'packagingId') as $fld){
					$form->setDefault($fld, $tasks[$rec->systemId]->{$fld});
				}
				$form->setReadOnly('productId');
			}
		}

		$form->setDefault('productId', $originRec->productId);
		$form->setDefault('plannedQuantity', $originRec->quantity);
		
		if(isset($rec->productId)){
			$packs = cat_Products::getPacks($rec->productId);
			$form->setOptions('packagingId', $packs);
			
			$measureId = cat_Products::fetchField($rec->productId, 'measureId');
			$form->setDefault('packagingId', $measureId);
			
			$productInfo = cat_Products::getProductInfo($rec->productId);
			$measureShort = cat_UoM::getShortName($rec->packagingId);
			if(!isset($productInfo->meta['canStore'])){
				$form->setField('plannedQuantity', "unit={$measureShort}");
			} else {
				$form->setField('packagingId', 'input');
			}
			
			$form->setField('startTime', "unit=|за|* 1 {$measureShort}");
			$jobRec = $origin->fetch();
			if($rec->productId == $jobRec->productId){
				$toProduce = $jobRec->quantity - $jobRec->quantityProduced;
				if($toProduce > 0){
					$form->setDefault('plannedQuantity', $toProduce);
				}
			}
			
			if(cat_Products::fetchField($rec->productId, 'canStore') === 'yes'){
				$form->setField('storeId', 'input,mandatory');
			}
			
			// Подаване на формата на драйвера на артикула, ако иска да добавя полета
			$Driver = cat_Products::getDriver($rec->productId);
			$Driver->addTaskFields($rec->productId, $form);
				
			// Попълване на полетата с данните от драйвера
			$driverFields = planning_Tasks::getFieldsFromProductDriver($rec->productId);
			
			foreach ($driverFields as $name => $f){
				if(isset($rec->additionalFields[$name])){
					$rec->{$name} = $rec->additionalFields[$name];
				}
			}
		}
		
		if(isset($rec->id)){
			if(planning_drivers_ProductionTaskDetails::fetch("#type = 'product' AND #taskId = {$rec->id}")){
				$form->setReadOnly('productId');
				$form->setReadOnly('packagingId');
				
				if($data->action != 'clone'){
					$form->setReadOnly('fixedAssets');
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
		
    		$rec->title = cat_Products::getTitleById($rec->productId);
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
		$dQuery->XPR('sumScrappedQuantity', 'double', 'SUM(#scrappedQuantity)');
		$dQuery->show('sumQuantity,sumWeight,sumScrappedQuantity');
		 
		$res = $dQuery->fetch();
		 
		// Преизчисляваме общото тегло
		$rec->totalWeight = $res->sumWeight;
		$rec->totalQuantity = $res->sumQuantity;
		$rec->scrappedQuantity = $res->sumScrappedQuantity;
		
		// Изчисляваме колко % от зададеното количество е направено
		$rec->progress = 0;
		if (!empty($rec->plannedQuantity)) {
		    $percent = ($rec->totalQuantity - $rec->scrappedQuantity) / $rec->plannedQuantity;
			$rec->progress = round($percent, 2);
		}
		
		if($rec->progress < 0){
			$rec->progress = 0;
		}
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
        
        $resArr['info'] = array('name' => tr('Информация'), 'val' => tr("|*<span style='font-weight:normal'>|Задание|*</span>: [#originId#]<br>
        																 <span style='font-weight:normal'>|Артикул|*</span>: [#productId#]<br>
        																 <span style='font-weight:normal'>|Склад|*: [#storeId#]</span>
        																 <!--ET_BEGIN fixedAssets--><br><span style='font-weight:normal'>|Оборудване|*</span>: [#fixedAssets#]<!--ET_END fixedAssets-->
        																 <br>[#progressBar#] [#progress#]"));
        
        $packagingId = cat_UoM::getTitleById($rec->packagingId);
        $resArr['quantity'] = array('name' => tr("Количества|*, |{$packagingId}|*"), 'val' => tr("|*<span style='font-weight:normal'>|Планирано|*</span>: &nbsp;&nbsp;&nbsp;[#plannedQuantity#]<br>
        																						    <span style='font-weight:normal'>|Произведено|*</span>: [#totalQuantity#]<br>
        																						    <span style='font-weight:normal'>|Бракувано|*</span>: &nbsp;&nbsp;&nbsp;&nbsp;[#scrappedQuantity#]"));
        
        if($rec->showadditionalUom == 'yes'){
        	$resArr['quantity']['val'] .= tr("|*<br> <span style='font-weight:normal'>|Общо тегло|*</span> [#totalWeight#]");
        }
        
        if(!empty($rec->startTime) || !empty($rec->indTime)){
        	if(isset($rec->startTime)){
        		$row->startTime .= "/" . tr($packagingId);
        	}
        	
        	$resArr['times'] = array('name' => tr('Заработка'), 'val' => tr("|*<!--ET_BEGIN startTime--><div><span style='font-weight:normal'>|Произ-во|*</span>: [#startTime#]</div><!--ET_END startTime--><!--ET_BEGIN indTime--><div><span style='font-weight:normal'>|Пускане|*</span>: [#indTime#]</div><!--ET_END indTime-->"));
        }
        
        if(!empty($row->timeStart) || !empty($row->timeDuration) || !empty($row->timeEnd) || !empty($row->expectedTimeStart) || !empty($row->expectedTimeEnd)) {
        	
        	$resArr['start'] =  array('name' => tr('Планирани времена'), 'val' => tr("|*<!--ET_BEGIN expectedTimeStart--><div><span style='font-weight:normal'>|Очаквано начало|*</span>: [#expectedTimeStart#]</div><!--ET_END expectedTimeStart-->
																					 <!--ET_BEGIN timeDuration--><div><span style='font-weight:normal'>|Прод-ност|*</span>: [#timeDuration#]</div><!--ET_END timeDuration--> 
        			 																 <!--ET_BEGIN expectedTimeEnd--><div><span style='font-weight:normal'>|Очакван край|*</span>: [#expectedTimeEnd#]</div><!--ET_END expectedTimeEnd-->
        																			 <!--ET_BEGIN remainingTime--><div>[#remainingTime#]</div><!--ET_END remainingTime-->"));
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
    }
    
    
    /**
     * След определяне на детайлите за клониране
     * 
     * @param tasks_BaseDriver $Driver
     * @param embed_Manager $Embedder
     * @param array $details
     * @param stdClass $rec
     */
    public static function on_AfterGetDetailsToClone(tasks_BaseDriver $Driver, embed_Manager &$Embedder, &$details, $rec)
    {
    	$details['planning_drivers_ProductionTaskProducts'] = 'planning_drivers_ProductionTaskProducts';
    }
    
    
    /**
     * След определяне на полетата, които да не се клонират
     *
     * @param tasks_BaseDriver $Driver
     * @param embed_Manager $Embedder
     * @param array $res
     * @param stdClass $rec
     */
    public static function on_AfterGetFieldsNotToClone(tasks_BaseDriver $Driver, embed_Manager &$Embedder, &$res, $rec)
    {
    	$res['totalWeight'] = 'totalWeight';
    	$res['totalQuantity'] = 'totalQuantity';
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
    public static function on_AfterGetRequiredRoles(tasks_BaseDriver $Driver, embed_Manager $Embedder, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
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
    							$nRec->taskId          = $rec->id;
    							$nRec->packagingId     = $p->packagingId;
    							$nRec->quantityInPack  = $p->quantityInPack;
    							$nRec->plannedQuantity = $p->packQuantity * $rec->plannedQuantity * $rec->quantityInPack;
    							$nRec->productId       = $p->productId;
    							$nRec->type			   = $type;
    							$nRec->storeId		   = $rec->storeId;
    							
    							planning_drivers_ProductionTaskProducts::save($nRec);
    						}
    					}
    				}
    			}
    		}
    	}
    	
    	// Копиране на параметрите на артикула към задачата
    	$tasksClassId = planning_Tasks::getClassId();
    	$params = cat_Products::getParams($rec->productId);
    	if(is_array($params)){
    		foreach ($params as $k => $v){
    			if(cat_Params::fetchField($k, 'showInTasks') != 'yes') continue;
    			
    			$nRec = (object)array('paramId' => $k, 'paramValue' => $v, 'classId' => $tasksClassId, 'productId' => $rec->id);
    			if($id = cat_products_Params::fetchField("#classId = {$tasksClassId} AND #productId = {$rec->id} AND #paramId = {$k}", 'id')){
    				$nRec->id = $id;
    			}
    			 
    			cat_products_Params::save($nRec, NULL, "REPLACE");
    		}
    	}
    }
}
