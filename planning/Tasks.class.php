<?php



/**
 * Мениджър на Производствени операции
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Производствени операции
 */
class planning_Tasks extends core_Master
{
    
    
	/**
	 * Дали може да бъде само в началото на нишка
	 */
	public $onlyFirstInThread = TRUE;
	
	
	/**
	 * Интерфейси
	 */
    public $interfaces = 'label_SequenceIntf=planning_interface_TaskLabel';
	
	
	/**
	 * Шаблон за единичен изглед
	 */
	public $singleLayoutFile = 'planning/tpl/SingleLayoutTask.shtml';
	
	
	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	public $searchFields = 'title,fixedAssets,description,productId';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'doc_plg_BusinessDoc, doc_plg_Prototype, doc_DocumentPlg, planning_plg_StateManager, planning_Wrapper, acc_plg_DocumentSummary, plg_Search, plg_Clone, plg_Printing, plg_RowTools2, plg_LastUsedKeys';
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Производствени операции';
	
	
	/**
	 * Единично заглавие
	 */
	public $singleTitle = 'Производствена операция';
	
	
	/**
	 * Абревиатура
	 */
	public $abbr = 'Pts';
	
	
	/**
	 * Групиране на документите
	 */
	public $newBtnGroup = "3.8|Производство";
	
	
	/**
	 * Клас обграждащ горния таб
	 */
	public $tabTopClass = 'portal planning';
	
	
	/**
	 * Поле за начало на търсенето
	 */
	public $filterFieldDateFrom = 'timeStart';
	
	
	/**
	 * Поле за крайна дата на търсене
	 */
	public $filterFieldDateTo = 'timeEnd';
	
	
	/**
	 * Икона за единичния изглед
	 */
	public $singleIcon = 'img/16/task-normal.png';
	
	
	/**
	 * Да не се кешира документа
	 */
	public $preventCache = TRUE;
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'title, progress, folderId, state, modifiedOn, modifiedBy';
	
	
	/**
	 * Дали винаги да се форсира папка, ако не е зададена
	 * 
	 * @see doc_plg_BusinessDoc
	 */
	public $alwaysForceFolderIfEmpty = TRUE;
	
	
	/**
	 * Поле за търсене по потребител
	 */
	public $filterFieldUsers = FALSE;
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, taskWorker';
	
	
	/**
	 * Кой може да го добавя?
	 */
	public $canAdd = 'ceo, taskPlanning';
	
	
	/**
	 * Кой може да го редактира?
	 */
	public $canEdit = 'ceo, taskPlanning';
	
	
	/**
	 * Може ли да се редактират активирани документи
	 */
	public $canEditActivated = TRUE;
	
	
	/**
	 * Да се показва антетка
	 */
	public $showLetterHead = TRUE;
	
	
	/**
	 * Поле за филтриране по дата
	 */
	public $filterDateField = 'expectedTimeStart,timeStart,createdOn';
	
	
	/**
	 * Дали в листовия изглед да се показва бутона за добавяне
	 */
	public $listAddBtn = FALSE;
	
	
	/**
	 * Кои са детайлите на класа
	 */
	public $details = 'planning_ProductionTaskDetails,planning_ProductionTaskProducts';
	
	
	/**
	 * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
	 *
	 * @see plg_Clone
	 */
	public $cloneDetails = 'planning_ProductionTaskProducts,cat_products_Params';
	
	
	/**
	 * Полета, които при клониране да не са попълнени
	 *
	 * @see plg_Clone
	 */
	public $fieldsNotToClone = 'progress,totalWeight,systemId,scrappedQuantity,inputInTask';
	
	
	/**
	 * Кои ключове да се тракват, кога за последно са използвани
	 */
	public $lastUsedKeys = 'fixedAssets';
	
	
	/**
	 * Описание на модела (таблицата)
	 */
	function description()
	{
		$this->FLD('title', 'varchar(128)', 'caption=Заглавие,width=100%,changable,silent');
		$this->FLD('totalWeight', 'cat_type_Weight', 'caption=Общо тегло,input=none');
		
		$this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'mandatory,caption=Произвеждане->Артикул,removeAndRefreshForm=packagingId|inputInTask,silent');
		$this->FLD('packagingId', 'key(mvc=cat_UoM,select=name)', 'mandatory,caption=Произвеждане->Опаковка,after=productId,input=hidden,tdClass=small-field nowrap,removeAndRefreshForm,silent');
		$this->FLD('plannedQuantity', 'double(smartRound,Min=0)', 'mandatory,caption=Произвеждане->Планирано,after=packagingId');
		$this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Произвеждане->Склад,input=none');
		$this->FLD("indTime", 'time(noSmart)', 'caption=Произвеждане->Норма,smartCenter');
		$this->FLD('totalQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Количество,after=packagingId,input=none');
		$this->FLD('quantityInPack', 'double(smartRound)', 'input=none');
		$this->FLD('scrappedQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Брак,input=none');
		$this->FLD('description', 'richtext(rows=2,bucket=Notes)', 'caption=Допълнително->Описание');
		$this->FLD('showadditionalUom', 'enum(no=Не,yes=Да)', 'caption=Допълнително->Тегло');
		
		$this->FLD('timeStart', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00,format=smartTime)','caption=Времена->Начало, changable, tdClass=leftColImportant,formOrder=101');
		$this->FLD('timeDuration', 'time', 'caption=Времена->Продължителност,changable,formOrder=102');
		$this->FLD('timeEnd', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00,format=smartTime)', 'caption=Времена->Край,changable, tdClass=leftColImportant,formOrder=103');
		$this->FLD('progress', 'percent', 'caption=Прогрес,input=none,notNull,value=0');
		$this->FNC('systemId', 'int', 'silent,input=hidden');
		$this->FLD('expectedTimeStart', 'datetime(format=smartTime)', 'input=hidden,caption=Очаквано начало');
		$this->FLD('additionalFields', 'blob(serialize, compress)', 'caption=Данни,input=none');
		$this->FLD('fixedAssets', 'keylist(mvc=planning_AssetResources,select=fullName,makeLinks)', 'caption=Произвеждане->Оборудване,after=packagingId');
		$this->FLD('inputInTask', 'int', 'caption=Произвеждане->Влагане в,input=none,after=indTime');
	
		$this->setDbIndex('inputInTask');
	}
	
	
	/**
     * След подготовка на сингъла
     */
	protected static function on_AfterPrepareSingle($mvc, &$res, $data)
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
	protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
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
	 * Конвертира един запис в разбираем за човека вид
	 * Входният параметър $rec е оригиналният запис от модела
	 * резултата е вербалният еквивалент, получен до тук
	 */
	public static function recToVerbal_($rec, &$fields = '*')
	{
		static::fillGapsInRec($rec);
		
		$row = parent::recToVerbal_($rec, $fields);
		$mvc = cls::get(get_called_class());
		$row->title = self::getHyperlink($rec->id, (isset($fields['-list']) ? TRUE : FALSE));
		
		$red = new color_Object("#FF0000");
		$blue = new color_Object("green");
		$grey = new color_Object("#bbb");
	
		$progressPx = min(100, round(100 * $rec->progress));
		$progressRemainPx = 100 - $progressPx;
	
		$color = ($rec->progress <= 1) ? $blue : $red;
		$row->progressBar = "<div style='white-space: nowrap; display: inline-block;'><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$color}; width:{$progressPx}px;'> </div><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$grey};width:{$progressRemainPx}px;'></div></div>";
		 
		$grey->setGradient($color, $rec->progress);
		$row->progress = "<span style='color:{$grey};'>{$row->progress}</span>";
	
		if ($rec->timeEnd && ($rec->state != 'closed' && $rec->state != 'rejected')) {
			$remainingTime = dt::mysql2timestamp($rec->timeEnd) - time();
			$rec->remainingTime = cal_Tasks::roundTime($remainingTime);
			 
			$typeTime = cls::get('type_Time');
			if ($rec->remainingTime > 0) {
				$row->remainingTime = ' (' . tr('остават') . ' ' . $typeTime->toVerbal($rec->remainingTime) . ')';
			} else {
				$row->remainingTime = ' (' . tr('просрочване с') . ' ' . $typeTime->toVerbal(-$rec->remainingTime) . ')';
			}
		}
	
		// Ако е изчислено очакваното начало и има продължителност, изчисляваме очаквания край
		if(isset($rec->expectedTimeStart) && isset($rec->timeDuration)){
			$rec->expectedTimeEnd = dt::addSecs($rec->timeDuration, $rec->expectedTimeStart);
			$row->expectedTimeEnd = $mvc->getFieldType('expectedTimeStart')->toVerbal($rec->expectedTimeEnd);
		}
	
		if(isset($rec->originId)){
			$origin = doc_Containers::getDocument($rec->originId);
			$row->originId = $origin->getLink();
			$row->originShortLink = $origin->getShortHyperlink();
		}
	
		if(isset($rec->inputInTask)){
			$row->inputInTask = planning_Tasks::getLink($rec->inputInTask);
		}
		
		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
		$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
		$shortUom = cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId'));
		
		foreach (array('plannedQuantity', 'totalQuantity', 'scrappedQuantity') as $quantityFld){
			if(!$rec->{$quantityFld}){
				$rec->{$quantityFld} = 0;
				$row->{$quantityFld} = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($rec->{$quantityFld});
				$row->{$quantityFld} = "<span class='quiet'>{$row->{$quantityFld}}</span>";
			} else {
					$rec->{$quantityFld} *= $rec->quantityInPack;
					$row->{$quantityFld} =  cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($rec->{$quantityFld});
				}
					
				$row->{$quantityFld} .= " " . "<span style='font-weight:normal'>" . $shortUom . "</span>";
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
						$hint = tr("Зададено") . ": " . $row->{$timeField};
						$diffVerbal = ht::createHint($diffVerbal, $hint, 'notice', TRUE, array('height' => '12', 'width' => '12'));
						$row->{$eTimeField} .= " <span style='font-weight:normal'>({$diffVerbal})</span>";
					}
			}
		}
		
		if(isset($fields['-list'])){
			$row->title .= "<br><small>{$row->originShortLink}</small>";
		}
		
		return $row;
	}
	
	
	/**
	 * Интерфейсен метод на doc_DocumentInterface
	 */
	public function getDocumentRow($id)
	{
		$rec = $this->fetch($id);
		$row = new stdClass();
	
		$row->title     = self::getRecTitle($rec);
		$row->authorId  = $rec->createdBy;
		$row->author    = $this->getVerbal($rec, 'createdBy');
		$row->recTitle  = $row->title;
		$row->state     = $rec->state;
		$row->subTitle  = doc_Containers::getDocument($rec->originId)->getShortHyperlink();
		
		return $row;
	}
	
	
	/**
	 * Прави заглавие на МО от данните в записа
	 */
	public static function getRecTitle($rec, $escaped = TRUE)
	{
		$title = cat_Products::getTitleById($rec->productId);
		$title = "Pts{$rec->id} - " . $title;
		
		return $title;
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 */
	protected static function on_AfterInputEditForm($mvc, &$form)
	{
		$rec = &$form->rec;
		
		if($form->isSubmitted()){
			if($rec->timeStart && $rec->timeEnd && ($rec->timeStart > $rec->timeEnd)) {
				$form->setError('timeEnd', 'Крайният срок трябва да е след началото на операцията');
			}
	
			if(!empty($rec->timeStart) && !empty($rec->timeDuration) && !empty($rec->timeEnd)){
				if(strtotime(dt::addSecs($rec->timeDuration, $rec->timeStart)) != strtotime($rec->timeEnd)){
					$form->setWarning('timeStart,timeDuration,timeEnd', 'Въведеното начало плюс продължителноста не отговарят на въведената крайната дата');
				}
			}
			
			// Може да се избират само оборудвания от една група
			if(isset($rec->fixedAssets)){
				if(!planning_AssetGroups::haveSameGroup($rec->fixedAssets)){
					$form->setError('fixedAssets', 'Оборудванията са от различни групи');
				}
			}
			
			$pInfo = cat_Products::getProductInfo($rec->productId);
			$rec->quantityInPack = ($pInfo->packagings[$rec->packagingId]) ? $pInfo->packagings[$rec->packagingId]->quantity : 1;
			$rec->title = cat_Products::getTitleById($rec->productId);
		}
	}
	
	
	/**
	 * Добавя допълнителни полетата в антетката
	 *
	 * @param core_Master $mvc
	 * @param NULL|array $res
	 * @param object $rec
	 * @param object $row
	 */
	protected static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
	{
		$resArr['info'] = array('name' => tr('Информация'), 'val' => tr("|*<span style='font-weight:normal'>|Задание|*</span>: [#originId#]<br>
        																 <span style='font-weight:normal'>|Артикул|*</span>: [#productId#]<br>
																	     <!--ET_BEGIN inputInTask--><span style='font-weight:normal'>|Влагане в|*</span>: [#inputInTask#]<br><!--ET_END inputInTask-->
        																 <span style='font-weight:normal'>|Склад|*</span>: [#storeId#]
        																 <!--ET_BEGIN fixedAssets--><br><span style='font-weight:normal'>|Оборудване|*</span>: [#fixedAssets#]<!--ET_END fixedAssets-->
        																 <br>[#progressBar#] [#progress#]"));
		$packagingId = cat_UoM::getTitleById($rec->packagingId);
		$resArr['quantity'] = array('name' => tr("Количества"), 'val' => tr("|*<table>
				<tr><td style='font-weight:normal'>|Планирано|*:</td><td>[#plannedQuantity#]</td></tr>
				<tr><td style='font-weight:normal'>|Произведено|*:</td><td>[#totalQuantity#]</td></tr>
				<tr><td style='font-weight:normal'>|Бракувано|*:</td><td>[#scrappedQuantity#]</td></tr>
				<tr><td style='font-weight:normal'>|Произв. ед.|*:</td><td>{$packagingId}</td></tr>
				<!--ET_BEGIN indTime--><tr><td style='font-weight:normal'>|Заработка|*:</td><td>[#indTime#]</td></tr><!--ET_END indTime-->
				</table>"));
		
				if($rec->showadditionalUom == 'yes'){
				$resArr['quantity']['val'] .= tr("|*<br> <span style='font-weight:normal'>|Общо тегло|*</span> [#totalWeight#]");
		}
		
		if(!empty($rec->indTime)){
        	$row->indTime .= "/" . tr($packagingId);
        }
		
        if(!empty($row->timeStart) || !empty($row->timeDuration) || !empty($row->timeEnd) || !empty($row->expectedTimeStart) || !empty($row->expectedTimeEnd)) {
        	$resArr['start'] =  array('name' => tr('Планирани времена'), 'val' => tr("|*<!--ET_BEGIN expectedTimeStart--><div><span style='font-weight:normal'>|Очаквано начало|*</span>: [#expectedTimeStart#]</div><!--ET_END expectedTimeStart-->
		        	<!--ET_BEGIN timeDuration--><div><span style='font-weight:normal'>|Прод-ност|*</span>: [#timeDuration#]</div><!--ET_END timeDuration-->
        			 																 <!--ET_BEGIN expectedTimeEnd--><div><span style='font-weight:normal'>|Очакван край|*</span>: [#expectedTimeEnd#]</div><!--ET_END expectedTimeEnd-->
        																			 <!--ET_BEGIN remainingTime--><div>[#remainingTime#]</div><!--ET_END remainingTime-->"));
        }
	}
	
	
	/**
	 * Обновява данни в мастъра
	 *
	 * @param int $id първичен ключ на статия
	 * @return int $id ид-то на обновения запис
	 */
	public function updateMaster_($id)
	{
		$rec = $this->fetch($id);
		 
		// Колко е общото к-во досега
		$dQuery = planning_ProductionTaskDetails::getQuery();
		$dQuery->where("#taskId = {$rec->id} AND #productId = {$rec->productId} AND #type = 'production' AND #state != 'rejected'");
		$dQuery->XPR('sumQuantity', 'double', "SUM(#quantity / {$rec->quantityInPack})");
		$dQuery->XPR('sumWeight', 'double', 'SUM(#weight)');
		$dQuery->XPR('sumScrappedQuantity', 'double', "SUM(#scrappedQuantity / {$rec->quantityInPack})");
		$dQuery->show('sumQuantity,sumWeight,sumScrappedQuantity');
			
		$res = $dQuery->fetch();
			
		// Преизчисляваме общото тегло
		$rec->totalWeight = $res->sumWeight;
		$rec->totalQuantity = $res->sumQuantity;
		$rec->scrappedQuantity = $res->sumScrappedQuantity;
		
		// Изчисляваме колко % от зададеното количество е направено
		if (!empty($rec->plannedQuantity)) {
			$percent = ($rec->totalQuantity - $rec->scrappedQuantity) / $rec->plannedQuantity;
			$rec->progress = round($percent, 2);
		}
		
		$rec->progress = max(array($rec->progress, 0));
		
		return $this->save($rec, 'totalQuantity,totalWeight,scrappedQuantity,progress,modifiedOn,modifiedBy');
	}
	
	
	/**
	 * Проверка дали нов документ може да бъде добавен в
	 * посочената папка като начало на нишка
	 *
	 * @param $folderId int ид на папката
	 */
	public static function canAddToFolder($folderId)
	{
		$Cover = doc_Folders::getCover($folderId);
		 
		// Може да се добавя само в папка на 'Звено'
		return ($Cover->haveInterface('hr_DepartmentAccRegIntf'));
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'add' || $action == 'edit' || $action == 'changestate'){
			if(isset($rec->originId)){
				$origin = doc_Containers::getDocument($rec->originId);
				$state = $origin->fetchField('state');
				if($state == 'closed' || $state == 'draft' || $state == 'rejected'){
					$requiredRoles = 'no_one';
				}
			}
		}
		
		if($action == 'add' && isset($rec->originId)){
			// Може да се добавя само към активно задание
			if($origin = doc_Containers::getDocument($rec->originId)){
				if(!$origin->isInstanceOf('planning_Jobs')){
					$requiredRoles = 'no_one';
				}
			}
		}
		
		// Ако има прогрес, операцията не може да се оттегля
		if($action == 'reject' && isset($rec)){
			if(planning_ProductionTaskDetails::fetchField("#taskId = {$rec->id} AND #state != 'rejected'")){
				$requiredRoles = 'no_one';
			}
		}
	}
	
	
	/**
	 * След успешен запис
	 */
	protected static function on_AfterCreate($mvc, &$rec)
	{
		// Ако записа е създаден с клониране не се прави нищо
		if($rec->_isClone === TRUE) return;
		 
		if(isset($rec->originId)){
			$originDoc = doc_Containers::getDocument($rec->originId);
			$originRec = $originDoc->fetch();
	
			// Ако е по източник
			if(isset($rec->systemId)){
				$tasks = cat_Products::getDefaultProductionTasks($originRec->productId, $originRec->quantity);
				if(isset($tasks[$rec->systemId])){
					$def = $tasks[$rec->systemId];
					 
					// Намираме на коя дефолтна операция отговаря и извличаме продуктите от нея
					$r = array();
					foreach (array('production' => 'product', 'input' => 'input', 'waste' => 'waste') as $var => $type){
						if(is_array($def->products[$var])){
							foreach ($def->products[$var] as $p){
								$p = (object)$p;
								$nRec = new stdClass();
								$nRec->taskId          = $rec->id;
								$nRec->packagingId     = $p->packagingId;
								$nRec->quantityInPack  = $p->quantityInPack;
								$nRec->plannedQuantity = $p->packQuantity * $rec->plannedQuantity * $rec->quantityInPack * $p->quantityInPack;
								$nRec->productId       = $p->productId;
								$nRec->type			   = $type;
								$nRec->storeId		   = $rec->storeId;
									
								planning_ProductionTaskProducts::save($nRec);
							}
						}
					}
				}
			}
		}
		 
		// Копиране на параметрите на артикула към операцията
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
	
	
	/**
	 * Подготовка на формата за добавяне/редактиране
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		$rec = $form->rec;
    
		if(isset($rec->systemId)){
			$form->setField('prototypeId', 'input=none');
		}
		
		if(empty($rec->id)){
			if($folderId = Request::get('folderId', 'key(mvc=doc_Folders)')){
				unset($rec->threadId);
				$rec->folderId = $folderId;
			}
		}
		
		$form->setField('title', 'input=hidden');
		
		// За произвеждане може да се избере само артикула от заданието
		$origin = doc_Containers::getDocument($rec->originId);
		$originRec = $origin->fetch();
		if(empty($rec->id)){
			$form->setDefault('description', cat_Products::fetchField($originRec->productId, 'info'));
		}
		
		// Добавяме допустимите опции
		$products = cat_Products::getByProperty('canManifacture');
		$form->setOptions('productId', array('' => '') + $products);
		
		if(count($products) == 1){
			$form->setDefault('productId', key($products));
		}
		
		// Ако операцията е дефолтна за артикула, задаваме и дефолтите
		if(isset($rec->systemId)){
			$tasks = cat_Products::getDefaultProductionTasks($originRec->productId, $originRec->quantity);
			if(isset($tasks[$rec->systemId])){
				foreach (array('plannedQuantity', 'productId', 'quantityInPack', 'packagingId') as $fld){
					$form->setDefault($fld, $tasks[$rec->systemId]->{$fld});
				}
				$form->setReadOnly('productId');
			}
		}
		
		if(!isset($rec->productId)){
			$form->setDefault('productId', $originRec->productId);
		}
		
		if(isset($rec->productId)){
			$packs = cat_Products::getPacks($rec->productId);
			$form->setOptions('packagingId', $packs);
				
			$measureId = ($originRec->productId == $rec->productId) ? $originRec->packagingId : cat_Products::fetchField($rec->productId, 'measureId');
			$form->setDefault('packagingId', $measureId);
			$productInfo = cat_Products::getProductInfo($rec->productId);
			
			// Ако артикула е вложим, може да се влага по друга операция
			if(isset($productInfo->meta['canConvert'])){
				$tasks = self::getTasksByJob($origin->that);
				unset($tasks[$rec->id]);
				if(count($tasks)){
					$form->setField('inputInTask', 'input');
					$form->setOptions('inputInTask', array('' => '') + $tasks);
				}
			}
			
			$measureShort = cat_UoM::getShortName($rec->packagingId);
			if(!isset($productInfo->meta['canStore'])){
				$form->setField('plannedQuantity', "unit={$measureShort}");
			} else {
				$form->setField('packagingId', 'input');
				$form->setField('storeId', 'input,mandatory');
			}
			$form->setField('indTime', "unit=|за|* 1 {$measureShort}");
				
			if($rec->productId == $originRec->productId){
				$toProduce = ($originRec->quantity - $originRec->quantityProduced) / $originRec->quantityInPack;
				if($toProduce > 0){
					$form->setDefault('plannedQuantity', $toProduce);
				}
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
			$taskClassId = planning_Tasks::getClassId();
			if(planning_ProductionTaskDetails::fetch("#type = 'production' AND #taskId = {$rec->id}") || cat_products_Params::fetchField("#classId = '{$taskClassId}' AND #productId = {$rec->id}")){
				$form->setReadOnly('productId');
				$form->setReadOnly('packagingId');
		
				if($data->action != 'clone' && !empty($rec->fixedAssets)){
					$form->setReadOnly('fixedAssets');
				}
			}
		}
		
		// Наличното оборудване в департамента
		$fixedAssets = planning_AssetResources::getAvailableInFolder($rec->folderId);
		
		// Подсигуряване че вече избраното оборудване присъства в опциите винаги
		if(isset($rec->fixedAssets)){
			$alreadyIn = keylist::toArray($rec->fixedAssets);
			foreach ($alreadyIn as $fId){
				if(!array_key_exists($fId, $fixedAssets)){
					$fixedAssets[$fId] = planning_AssetResources::fetchField($fId, 'fullName');
				}
			}
		}
		
		if(count($fixedAssets)){
			$form->setSuggestions('fixedAssets', array('' => '') + $fixedAssets);
		} else {
			$form->setField('fixedAssets', 'input=none');
		}
	}
	
	
	/**
	 * Връща масив със съществуващите задачи
	 * 
	 * @param int $containerId
	 * @param stdClass $data
	 * @return void
	 */
	protected function prepareExistingTaskRows($containerId, &$data)
	{
		// Всички създадени задачи към заданието
		$query = $this->getQuery();
		$query->where("#state != 'rejected'");
		$query->where("#originId = {$containerId}");
		$query->XPR('orderByState', 'int', "(CASE #state WHEN 'wakeup' THEN 1 WHEN 'active' THEN 2 WHEN 'stopped' THEN 3 WHEN 'closed' THEN 4 WHEN 'waiting' THEN 5 ELSE 6 END)");
		$query->orderBy('#orderByState=ASC');
		
		// Подготвяме данните
		while($rec = $query->fetch()){
			$data->recs[$rec->id] = $rec;
			$row = planning_Tasks::recToVerbal($rec);
			$row->modified = $row->modifiedOn . " " . tr('от||by') . " " . $row->modifiedBy;
			$row->modified = "<div style='text-align:center'> {$row->modified} </div>";
			$data->rows[$rec->id] = $row;
		}
	}
	
	
	/**
	 * Подготвя задачите към заданията
	 */
	public function prepareTasks($data)
	{
		$masterRec = $data->masterData->rec;
		$containerId = $data->masterData->rec->containerId;
		
		$data->recs = $data->rows = array();
		$this->prepareExistingTaskRows($containerId, $data);
		
		// Ако потребителя може да добавя операция от съответния тип, ще показваме бутон за добавяне
		if($this->haveRightFor('add', (object)array('originId' => $containerId))){
			$data->addUrlArray = array('planning_Jobs', 'selectTaskAction', 'originId' => $containerId, 'ret_url' => TRUE);
		}
	}
	
	
	/**
	 * Рендира задачите на заданията
	 */
	public function renderTasks($data)
	{
		$tpl = new ET("");
	
		// Ако няма намерени записи, не се рендира нищо
		// Рендираме таблицата с намерените задачи
		$table = cls::get('core_TableView', array('mvc' => $this));
		$fields = 'title=Операция,progress=Прогрес,folderId=Папка,expectedTimeStart=Очаквано начало, timeDuration=Продължителност, timeEnd=Край, modified=Модифицирано';
		$data->listFields = core_TableView::filterEmptyColumns($data->rows, $fields, 'timeStart,timeDuration,timeEnd,expectedTimeStart');
		$this->invoke('BeforeRenderListTable', array($tpl, &$data));
		 
		$tpl = $table->get($data->rows, $data->listFields);
		 
		// Имали бутони за добавяне
		if(isset($data->addUrlArray)){
			$btn = ht::createBtn('Нова операция', $data->addUrlArray, FALSE, FALSE, "title=Създаване на производствена операция към задание,ef_icon={$this->singleIcon}");
			$tpl->append($btn, 'btnTasks');
		}
		
		// Връщаме шаблона
		return $tpl;
	}
	
	
	/**
	 * Преди запис на документ
	 */
	protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
	{
		$rec->additionalFields = array();
		
		// Вкарване на записите специфични от драйвера в блоб поле
		$productFields = self::getFieldsFromProductDriver($rec->productId);
		if(is_array($productFields)){
			foreach ($productFields as $name => $field){
				if(isset($rec->{$name})){
					$rec->additionalFields[$name] = $rec->{$name};
				}
			}
		}
		 
		$rec->additionalFields = count($rec->additionalFields) ? $rec->additionalFields : NULL;
	}
    
    
	/**
	 * Помощна функция извличаща параметрите на операцията
	 * 
	 * @param stdClass $rec     - запис
	 * @param boolean $verbal   - дали параметрите да са вербални
	 * @return array $params    - масив с обеднението на параметрите на операцията и тези на артикула
	 */
	public static function getTaskProductParams($rec, $verbal = FALSE)
	{
		// Кои са параметрите на артикула
		$classId = planning_Tasks::getClassId();
		$productParams = cat_Products::getParams($rec->productId, NULL, TRUE);
		
		// Кои са параметрите на операцията
		$params = array();
		$query = cat_products_Params::getQuery();
		$query->where("#classId = {$classId} AND #productId = {$rec->id}");
		$query->show('paramId,paramValue');
		while($dRec = $query->fetch()){
			$dRec->paramValue = ($verbal === TRUE) ? cat_Params::toVerbal($dRec->paramId, $classId, $rec->id, $dRec->paramValue) : $dRec->paramValue;
			$params[$dRec->paramId] = $dRec->paramValue;
		}
		
		// Обединяване на параметрите на операцията с тези на артикула
		$params = $params + $productParams;
		
		// Връщане на параметрите
		return $params;
	}
    
    
    /**
     * Ф-я връщаща полетата специфични за артикула от драйвера
     *
     * @param int $productId
     * @return array
     */
    public static function getFieldsFromProductDriver($productId)
    {
    	$form = cls::get('core_Form');
    	if($Driver = cat_Products::getDriver($productId)){
    		$Driver->addTaskFields($productId, $form);
    	}
    	 
    	return $form->selectFields();
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
    	// Филтър по всички налични департаменти
    	$departmentOptions = hr_Departments::makeArray4Select('name', "type = 'workshop' AND #state != 'rejected'");
    	
    	if(count($departmentOptions)){
    		$data->listFilter->FLD('departmentId', 'int', 'caption=Звено');
    		$data->listFilter->setOptions('departmentId', array('' => '') + $departmentOptions);
    		$data->listFilter->showFields .= ',departmentId';
    		
    		// Ако потребителя е служител и има само един департамент, той ще е избран по дефолт
    		$cPersonId = crm_Profiles::getProfile(core_Users::getCurrent())->id;
    		$departments = crm_ext_Employees::fetchField("#personId = {$cPersonId}", 'departments');
    		$departments = keylist::toArray($departments);
    		
    		if(count($departments) == 1){
    			$defaultDepartment = key($departments);
    			$data->listFilter->setDefault('departmentId', $defaultDepartment);
    		}
    		
    		$data->listFilter->input('departmentId');
    	}
    	
    	// Добавяне на оборудването към филтъра
    	$fixedAssets = planning_AssetResources::makeArray4Select('name', "#state != 'rejected'");
    	if(count($fixedAssets)){
    		$data->listFilter->FLD('assetId', 'int', 'caption=Оборудване');
    		$data->listFilter->setOptions('assetId', array('' => '') + $fixedAssets);
    		$data->listFilter->showFields .= ',departmentId,assetId';
    		$data->listFilter->input('assetId');
    	}
    	
    	// Филтър по департамент
    	if($departmentFolderId = $data->listFilter->rec->departmentId){
    		$folderId = hr_Departments::fetchField($departmentFolderId, 'folderId');
    		$data->query->where("#folderId = {$folderId}");
    		unset($data->listFields['folderId']);
    	}
    	
    	if($assetId = $data->listFilter->rec->assetId){
    		$data->query->where("LOCATE('|{$assetId}|', #fixedAssets)");
    	}
    	
    	// Показване на полето за филтриране
    	if($filterDateField = $data->listFilter->rec->filterDateField){
    		$filterFieldArr = array($filterDateField => ($filterDateField == 'expectedTimeStart') ? 'Очаквано начало' : ($filterDateField == 'timeStart' ? 'Начало' : 'Създаване'));
    		arr::placeInAssocArray($data->listFields, $filterFieldArr,'title');
    	}
    	
    	if(!Request::get('Rejected', 'int')){
    		$data->listFilter->setOptions('state', array('' => '') + arr::make('draft=Чернова, active=Активен, pendingandactive=Активни+Чакащи,closed=Приключен, stopped=Спрян, wakeup=Събуден,waiting=Чакащо', TRUE));
    		$data->listFilter->setField('state', 'placeholder=Всички,formOrder=1000');
    		$data->listFilter->showFields .= ',state';
    		$data->listFilter->input('state');
    	
    		if($state = $data->listFilter->rec->state){
    			if($state != 'pendingandactive'){
    				$data->query->where("#state = '{$state}'");
    			} else {
    				$data->query->where("#state = 'active' OR #state = 'waiting'");
    			}
    		}
    	}
    }
    
    
    /**
     * Връща масив от задачи към дадено задание
     * 
     * @param int $jobId
     * @return array $res
     */
    public static function getTasksByJob($jobId)
    {
    	$res = array();
    	$oldContainerId = planning_Jobs::fetchField($jobId, 'containerId');
    	$query = static::getQuery();
    	$query->where("#originId = {$oldContainerId} AND #state != 'rejected' AND #state != 'draft'");
    	while($rec = $query->fetch()){
    		$res[$rec->id] = self::getRecTitle($rec, FALSE);
    	}
    	
    	return $res;
    }
    
    
    /**
     * Ако са въведени две от времената (начало, продължителност, край) а третото е празно, изчисляваме го.
     * ако е въведено само едно време или всички не правим нищо
     *
     * @param stdClass $rec - записа който ще попълним
     * @return void
     */
    protected static function fillGapsInRec(&$rec)
    {
    	if(isset($rec->timeStart) && isset($rec->timeDuration) && empty($rec->timeEnd)){
    			
    		// Ако има начало и продължителност, изчисляваме края
    		$rec->timeEnd = dt::addSecs($rec->timeDuration, $rec->timeStart);
    	} elseif(isset($rec->timeStart) && isset($rec->timeEnd) && empty($rec->timeDuration)) {
    			
    		// Ако има начало и край, изчисляваме продължителността
    		$rec->timeDuration = $diff = strtotime($rec->timeEnd) - strtotime($rec->timeStart);
    	} elseif(isset($rec->timeDuration) && isset($rec->timeEnd) && empty($rec->timeStart)) {
    			
    		// Ако има продължителност и край, изчисляваме началото
    		$rec->timeStart = dt::addSecs(-1 * $rec->timeDuration, $rec->timeEnd);
    	}
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
    	if(empty($rec->id)) return;
    	 
    	// Добавяне на всички ключови думи от прогреса
    	$dQuery = planning_ProductionTaskDetails::getQuery();
    	$dQuery->XPR("concat", 'varchar', "GROUP_CONCAT(#searchKeywords)");
    	$dQuery->where("#taskId = {$rec->id}");
    	$dQuery->limit(1);
    	
    	if($keywords = $dQuery->fetch()->concat){
    		$keywords = str_replace(' , ', ' ', $keywords);
    		$res = " " . $res . " " . $keywords;
    	}
    }
    
    
    /**
     * Връща количеството произведено по задачи по дадено задание
     *
     * @param int $jobId
     * @param product|input|waste|start $type
     * @return double $quantity
     */
    public static function getProducedQuantityForJob($jobId)
    {
    	expect($jobRec = planning_Jobs::fetch($jobId));
    	 
    	$query = planning_Tasks::getQuery();
    	$query->where("#originId = {$jobRec->containerId} AND #productId = {$jobRec->productId}");
    	$query->where("#state != 'rejected' AND #state != 'pending'");
    	$query->XPR('sum', 'double', 'SUM((#totalQuantity - #scrappedQuantity)* #quantityInPack)');
    	$query->show('totalQuantity,sum');
    
    	$sum = $query->fetch()->sum;
    	$quantity = (!empty($sum)) ? $sum : 0;
    	
    	return $quantity;
    }
}
