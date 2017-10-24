<?php



/**
 * Клас 'planning_DirectProductNoteDetails'
 *
 * Детайли на мениджър на детайлите на протокола за производство
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_DirectProductNoteDetails extends deals_ManifactureDetail
{
    
	
	/**
     * Заглавие
     */
    public $title = 'Детайли на протокола за производство';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Ресурс';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_SaveAndNew, plg_Created, planning_Wrapper, plg_Sorting, 
                        planning_plg_ReplaceEquivalentProducts, plg_PrevAndNext,cat_plg_ShowCodes';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,planning,store,production';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,planning,store,production';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,planning,store,production';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=№,productId=Материал, packagingId, packQuantity=Количества->За влагане, quantityFromBom=Количества->Рецепта, quantityFromTasks=Количества->Задачи,storeId';
    

    /**
     * Полета, които ще се скриват ако са празни
     */
    public $hideListFieldsIfEmpty = 'quantityFromBom,quantityFromTasks,storeId';
    
    
    /**
     * Активен таб
     */
    public $currentTab = 'Протоколи->Производство';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=planning_DirectProductionNote)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('type', 'enum(input=Влагане,pop=Отпадък)', 'caption=Действие,silent,input=hidden');
        
        parent::setDetailFields($this);
        
        $this->FLD('quantityFromBom', 'double(Min=0)', 'caption=Количества->Рецепта,input=none,tdClass=quiet');
        $this->FLD('quantityFromTasks', 'double(Min=0)', 'caption=Количества->Задачи,input=none,tdClass=quiet');
        $this->setField('quantity', 'caption=Количества->За влагане');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Изписване от,input=none,tdClass=small-field nowrap,placeholder=Незавършено производство');
    
        $this->setDbIndex('productId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	$data->singleTitle = ($rec->type == 'pop') ? 'отпадък' : 'материал';
    	$data->defaultMeta = ($rec->type == 'pop') ? 'canConvert,canStore' : 'canConvert';
    	
    	if(isset($rec->productId)){
    		$storable = cat_Products::fetchField($rec->productId, 'canStore');
    		if($storable == 'yes'){
    			$form->setField('storeId', 'input');
    			
    			if(empty($rec->id) && isset($data->masterRec->inputStoreId)){
    				$form->setDefault('storeId', $data->masterRec->inputStoreId);
    			}
    		}
    	}
    	
    	if($rec->type == 'pop'){
    		$form->setField('storeId', 'input=none');
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	$rec = &$form->rec;
    	
    	if(isset($rec->productId)){
    		
    		if($form->isSubmitted()){
    			
    			// Ако добавяме отпадък, искаме да има себестойност
    			if($rec->type == 'pop'){
    				$selfValue = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $rec->productId);
    		
    				if(!isset($selfValue)){
    					$form->setError('productId', 'Отпадъкът няма себестойност');
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, &$data)
    {
    	if(!count($data->recs)) return;
    	
    	foreach ($data->rows as $id => &$row)
    	{
    		$rec = &$data->recs[$id];
    		$row->ROW_ATTR['class'] = ($rec->type == 'input') ? 'row-added' : 'row-removed';
    		if(isset($rec->storeId)){
    			$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    		}
    		
    		if(isset($rec->quantityFromBom)){
    			$rec->quantityFromBom = $rec->quantityFromBom / $rec->quantityInPack;
    			$row->quantityFromBom = $mvc->getFieldType('quantityFromBom')->toVerbal($rec->quantityFromBom);
    		}
    		
    		if(isset($rec->quantityFromTasks)){
    			$rec->quantityFromTasks = $rec->quantityFromTasks / $rec->quantityInPack;
    			$row->quantityFromTasks = $mvc->getFieldType('quantityFromTasks')->toVerbal($rec->quantityFromTasks);
    		}
    		
    		if($rec->type == 'pop'){
    			$row->packQuantity .= " {$row->packagingId}";
    		}
    	}
    }
    
    
    /**
     * След подготовка на детайлите, изчислява се общата цена
     * и данните се групират
     */
    protected static function on_AfterPrepareDetail($mvc, $res, $data)
    {
    	$data->inputArr = $data->popArr = array();
    	$countInputed = $countPoped = 1;
    	$Int = cls::get('type_Int');
    	
    	// За всеки детайл (ако има)
    	if(count($data->rows)){
    		foreach ($data->rows as $id => $row){
    			$rec = $data->recs[$id];
    			if(!is_object($row->tools)){
    				$row->tools = new ET("[#TOOLS#]");
    			}
    			
    			// Разделяме записите според това дали са вложими или не
    			if($rec->type == 'input'){
    				$num = $Int->toVerbal($countInputed);
    				$data->inputArr[$id] = $row;
    				$countInputed++;
    			} else {
    				$num = $Int->toVerbal($countPoped);
    				$data->popArr[$id] = $row;
    				$countPoped++;
    			}
    			
    			$row->tools->append($num, 'TOOLS');
    		}
    	}
    }
    
    
    /**
     * Променяме рендирането на детайлите
     * 
     * @param stdClass $data
     * @return core_ET $tpl
     */
    function renderDetail_($data)
    {
    	$tpl = new ET("");
    	
    	if(Mode::is('printing')){
    		unset($data->listFields['tools']);
    	}
    	
    	// Рендираме таблицата с вложените материали
    	$data->listFields['productId'] = 'Вложени артикули|* ';
    	
    	$fieldset = clone $this;
    	$fieldset->FNC('num', 'int');
    	$table = cls::get('core_TableView', array('mvc' => $fieldset));
    	
    	$iData = clone $data;
    	$iData->listTableMvc = clone $this;
    	$iData->rows = $data->inputArr;
    	$iData->recs = array_intersect_key($iData->recs, $iData->rows);
    	plg_AlignDecimals2::alignDecimals($this, $iData->recs, $iData->rows);
    	$this->invoke('BeforeRenderListTable', array(&$tpl, &$iData));
    	
    	$iData->listFields = core_TableView::filterEmptyColumns($iData->rows, $iData->listFields, $this->hideListFieldsIfEmpty);
    	$detailsInput = $table->get($iData->rows, $iData->listFields);
    	$tpl->append($detailsInput, 'planning_DirectProductNoteDetails');
    	
    	// Добавяне на бутон за нов материал
    	if($this->haveRightFor('add', (object)array('noteId' => $data->masterId, 'type' => 'input'))){
    		$tpl->append(ht::createBtn('Артикул', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'input', 'ret_url' => TRUE),  NULL, NULL, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/wooden-box.png', 'title' => 'Добавяне на нов материал')), 'planning_DirectProductNoteDetails');
    	}
    	
    	// Добавяне на бутон за нов материал
    	if($this->haveRightFor('addReserved', (object)array('noteId' => $data->masterId))){
    		$tpl->append(ht::createBtn('Само резервираните', array($this, 'addReserved', 'noteId' => $data->masterId, 'ret_url' => TRUE),  'Наистина ли желаете да добавите само резервираните артикули', NULL, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/wooden-box.png', 'title' => 'Добавяне само на резервираните артикули')), 'planning_DirectProductNoteDetails');
    	}
    	
    	// Рендираме таблицата с отпадъците
    	if(count($data->popArr) || $data->masterData->rec->state == 'draft'){
    		$data->listFields['productId'] = "Отпадъци|* <small style='font-weight:normal'>( |остават в незавършеното производство|* )</small>";
    		unset($data->listFields['storeId']);
    		
    		$pData = clone $data;
    		$pData->listTableMvc = clone $this;
    		$pData->rows = $data->popArr;
    		$pData->recs = array_intersect_key($pData->recs, $pData->rows);
    		plg_AlignDecimals2::alignDecimals($this, $pData->recs, $pData->rows);
    		$this->invoke('BeforeRenderListTable', array(&$tpl, &$pData));
    		
    		$pData->listFields = core_TableView::filterEmptyColumns($pData->rows, $pData->listFields, $this->hideListFieldsIfEmpty);
    		$popTable = $table->get($pData->rows, $pData->listFields);
    		$detailsPop = new core_ET("<span style='margin-top:5px;'>[#1#]</span>", $popTable);
    		
    		$tpl->append($detailsPop, 'planning_DirectProductNoteDetails');
    	}
    	
    	// Добавяне на бутон за нов отпадък
    	if($this->haveRightFor('add', (object)array('noteId' => $data->masterId, 'type' => 'pop'))){
    		$tpl->append(ht::createBtn('Отпадък', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'pop', 'ret_url' => TRUE),  NULL, NULL, array('style' => 'margin-top:5px;;margin-bottom:10px;', 'ef_icon' => 'img/16/recycle.png', 'title' => 'Добавяне на нов отпадък')), 'planning_DirectProductNoteDetails');
    	}
    	
    	// Връщаме шаблона
    	return $tpl;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	if(!count($data->recs)) return;
    	$storeId = $data->masterData->rec->inputStoreId;
    	if($data->masterData->rec->state == 'active'){
    		unset($data->listFields['quantityFromBom']);
    		unset($data->listFields['quantityFromTasks']);
    		$data->listFields['packQuantity'] = "Количество";
    	}
    	
    	foreach ($data->rows as $id => &$row){
    		$rec = $data->recs[$id];
    		
    		$difference = 0;
    		$minQuantity = min($rec->quantityFromBom, $rec->quantityFromTasks);
    		
    		if (!empty($minQuantity)) {
    		    $difference = round(abs($rec->quantityFromBom - $rec->quantityFromTasks) / $minQuantity * 100);
    		}
    		
    		if($difference >= 20){
    			if($data->masterData->rec->state != 'active'){
    				$row->packQuantity = ht::createHint($row->packQuantity, 'Има голяма разлика между количеството по рецепта и по задачи',  'warning', FALSE);
    			}
    		}
    		
    		if(empty($rec->storeId)){
    			$row->storeId = "<span class='quiet'>"  . tr('Незавършено производство') . "</span>";
    		} else {
    			if($rec->type != 'input') continue;
    			
    			$warning = deals_Helper::getQuantityHint($rec->productId, $rec->storeId, $rec->quantity);
    			if(strlen($warning) && $data->masterData->rec->state != 'active'){
    				$row->packQuantity = ht::createHint($row->packQuantity, $warning, 'warning', FALSE);
    			}
    		}
    	}
    }
    
    
    /**
     * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
     */
    protected static function on_AfterGetRowInfo($mvc, &$res, $rec)
    {
    	$rec = $mvc->fetchRec($rec);
    	if(empty($rec->storeId)){
    		unset($res->operation);
    	} else {
    		$res->operation[key($res->operation)] = $rec->storeId;
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'addreserved'){
    		$requiredRoles = $mvc->getRequiredRoles('add', $rec, $userId);
    		
    		// Може ли да се заредят само резервираните артикули
    		if($requiredRoles != 'no_one' && isset($rec->noteId)){
    			$originId = planning_DirectProductionNote::fetchField($rec->noteId, 'originId');
    			if(!store_ReserveStocks::fetchField("#originId = '{$originId}' AND #state = 'active'")){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Добавяне на резервираните артикули към протокола
     */
    function act_addreserved()
    {
    	$this->requireRightFor('addreserved');
    	expect($noteId = Request::get('noteId', 'int'));
    	expect($masterRec = planning_DirectProductionNote::fetch($noteId));
    	$this->requireRightFor('addreserved', (object)array('noteId' => $noteId));
    	
    	$details = array();
    	$reserveRec = store_ReserveStocks::fetch("#originId = '{$masterRec->originId}' AND #state = 'active'");
		$dQuery = store_ReserveStockDetails::getQuery();
		$dQuery->EXT('canConvert', 'cat_Products', 'externalName=canConvert,externalKey=productId');
		$dQuery->where("#reserveId = {$reserveRec->id}");
		$dQuery->orderBy('id', 'ASC');
		
		while($dRec = $dQuery->fetch()){
			if($dRec->canConvert != 'yes') continue;
			
			$details[] = (object)array('noteId'         => $noteId, 
					                   'productId'      => $dRec->productId, 
					                   'packagingId'    => $dRec->packagingId, 
					                   'quantityInPack' => $dRec->quantityInPack, 
					                   'type'           => 'input' ,
									   'storeId'        => $reserveRec->storeId,
					                   'quantity'       => $dRec->quantity);
		} 
    	
		if(count($details)){
			self::delete("#noteId = {$noteId}");
			$this->saveArray($details);
			$msg = 'Заредени са сано резервираните артикули';
		} else {
			$msg = 'От резервираните артикули, няма вложими';
		}
		
    	followRetUrl(NULL, $msg);
    }
}