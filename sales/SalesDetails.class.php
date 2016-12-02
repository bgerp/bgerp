<?php



/**
 * Клас 'sales_SalesDetails'
 *
 * Детайли на мениджър на документи за продажба на продукти (@see sales_Sales)
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_SalesDetails extends deals_DealDetail
{
    
    
    /**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Детайли на продажби';


    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'saleId';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'plg_RowTools2, plg_Created, sales_Wrapper, plg_RowNumbering, plg_SaveAndNew, plg_PrevAndNext,
                        plg_AlignDecimals2, plg_Sorting, deals_plg_ImportDealDetailProduct, doc_plg_HidePrices, LastPricePolicy=sales_SalesLastPricePolicy,cat_plg_CreateProductFromDocument';
    
    
    /**
     * Активен таб на менюто
     * 
     * @var string
     */
    public $menuPage = 'Търговия:Продажби';
    
    
    /**
     * Кой има право да чете?
     * 
     * @var string|array
     */
    public $canRead = 'ceo, sales';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'ceo, sales, collaborator';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'ceo, sales, collaborator';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'ceo, sales, collaborator';
    
    
    /**
     * Кой може да го импортира артикули?
     *
     * @var string|array
     */
    public $canImport = 'ceo, sales';
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'saleId';
    
    
    /**
     * Брой записи на страница
     * 
     * @var integer
     */
    public $listItemsPerPage;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, packQuantity, packPrice, discount, amount, quantityInPack';
    

    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Какви мета данни да изискват продуктите, които да се показват
     */
    public $metaProducts = 'canSell';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('saleId', 'key(mvc=sales_Sales)', 'column=none,notNull,silent,hidden,mandatory');
        
        parent::getDealDetailFields($this);
        $this->FLD('term', 'time(uom=days,suggestions=1 ден|5 дни|7 дни|10 дни|15 дни|20 дни|30 дни)', 'caption=Срок,after=tolerance,input=none');
		$this->setField('packPrice', 'silent');
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	$masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
    	if(isset($rec->productId)){
    		$pInfo = cat_Products::getProductInfo($rec->productId);
    		$masterStore = $masterRec->shipmentStoreId;
    		
    		if(isset($masterStore) && isset($pInfo->meta['canStore'])){
    			$storeInfo = deals_Helper::checkProductQuantityInStore($rec->productId, $rec->packagingId, $rec->packQuantity, $masterStore);
    			$form->info = $storeInfo->formInfo;
    		}
    	}
    	
    	parent::inputDocForm($mvc, $form);
    	
    	// След събмит
    	if($form->isSubmitted()){
    		
    		// Подготовка на сумата на транспорта, ако има
    		tcost_Calcs::prepareFee($rec, $form, $masterRec);
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	$rows = &$data->rows;
    	
    	if(!count($data->recs)) return;
    	$masterRec = $data->masterData->rec;
    	
    	foreach ($rows as $id => $row){
    		$rec = $data->recs[$id];
    		$pInfo = cat_Products::getProductInfo($rec->productId);
    			
    		if($storeId = $masterRec->shipmentStoreId){
    			if(isset($pInfo->meta['canStore']) && $masterRec->state == 'draft'){
    				$warning = deals_Helper::getQuantityHint($rec->productId, $storeId, $rec->quantity);
    				if(strlen($warning)){
    					$row->packQuantity = ht::createHint($row->packQuantity, $warning, 'warning', FALSE);
    				}
    			}
    		}
    		
    		if($rec->price < cat_Products::getSelfValue($rec->productId, NULL, $rec->quantity)){
    			if(!core_Users::haveRole('collaborator')){
    				$row->packPrice = ht::createHint($row->packPrice, 'Цената е под себестойността', 'warning', FALSE);
    			}
    		}
    		
    		// Ако е имало проблем при изчисляването на скрития транспорт, показва се хинт
    		$fee = tcost_Calcs::get($mvc->Master, $rec->saleId, $rec->id)->fee;
    		$vat = cat_Products::getVat($rec->productId, $masterRec->valior);
    		$row->amount = tcost_Calcs::getAmountHint($row->amount, $fee, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$rec = &$data->form->rec;
    	$form = &$data->form;
    	
    	if(isset($rec->productId)){
    		
    		// Ако в артикула има срок на доставка, показва се полето
    		$term = cat_Products::getParams($rec->productId, 'term');
    		if(!empty($term) && !core_Users::haveRole('collaborator')){
    			$form->setField('term', 'input');
    			if(empty($rec->id)){
    				$form->setDefault('term', $term);
    			}
    			
    			$termVerbal = $mvc->getFieldType('term')->toVerbal($term);
    			$form->setSuggestions('term', array('' => '', $termVerbal => $termVerbal));
    		}
    	}
    }
    
    
    /**
     * Приготвя информация за нестандартните артикули и техните задания
     * 
     * @param stdClass $rec
     * @param stdClass $masterRec
     * @return void|stdClass
     */
    public static function prepareJobInfo($rec, $masterRec)
    {
    	$row = new stdClass();
    	$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
    	$row->quantity = $row->quantityFromTasks = $row->quantityProduced = 0;
    	$jobRec = NULL;
    	
    	$pRec = cat_Products::fetch($rec->productId);
    	
    	// Имаме ли активно задание по тази продажба
    	$jobRec = planning_Jobs::fetch("#productId = {$rec->productId} AND #saleId = {$masterRec->id} AND (#state = 'active' || #state = 'stopped' || #state = 'wakeup')");
    	
    	// Ако няма търсим, имаме ли активно задание
    	if(!$jobRec){
    		$jobRec = planning_Jobs::fetch("#productId = {$rec->productId} AND (#state = 'active' || #state = 'stopped' || #state = 'wakeup')");
    	}
    	
    	// Ако и такова няма намираме последното задание-чернова към тази продажба
    	if(!$jobRec){
    		$jQuery = planning_Jobs::getQuery();
    		$jQuery->where("#productId = {$rec->productId} AND #saleId = {$masterRec->id} AND #state = 'draft'");
    		$jQuery->orderBy("id", 'DESC');
    		$jobRec = planning_Jobs::fetch("#productId = {$rec->productId} AND #state = 'draft'");
    	}
    	
    	if(!empty($jobRec)){
    		$Double = cls::get('type_Double', (object)array('params' => array('smartRound' => TRUE)));
    		$row->quantity = $Double->toVerbal($jobRec->quantity);
    		$row->quantityFromTasks = $Double->toVerbal(planning_TaskActions::getQuantityForJob($jobRec->id, 'product'));
    		$row->quantityProduced = $Double->toVerbal($jobRec->quantityProduced);
    		
    		if(!Mode::is('text', 'xhtml') && !Mode::is('printing')){
    			$row->dueDate = cls::get('type_Date')->toVerbal($jobRec->dueDate);
    			$row->dueDate = ht::createLink($row->dueDate, array('cal_Calendar', 'day', 'from' => $row->dueDate, 'Task' => 'true'), NULL, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
    		}
    		
    		$row->jobId = "#" . planning_Jobs::getHandle($jobRec->id);
    		$row->jobId = ht::createLink($row->jobId, planning_Jobs::getSingleUrlArray($jobRec->id), FALSE, 'ef_icon=img/16/clipboard_text.png');
    		$row->ROW_ATTR['class'] = "state-{$jobRec->state}";
    	
    		return $row;
    	}
    }
    
    
    /**
     * Изпълнява се преди клониране
     */
    protected static function on_BeforeSaveClonedDetail($mvc, &$rec, $oldRec)
    {
    	// Преди клониране клонира се и сумата на цената на транспорта
    	$fee = tcost_Calcs::get($mvc->Master, $oldRec->saleId, $oldRec->id)->fee;
    	if(isset($fee)){
    		$rec->fee = $fee;
    		$rec->syncFee = TRUE;
    	}
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	// Синхронизиране на сумата на транспорта
    	if($rec->syncFee === TRUE){
    		tcost_Calcs::sync($mvc->Master, $rec->{$mvc->masterKey}, $rec->id, $rec->fee);
    	}
    }
    
    
    /**
     * След изтриване на запис
     */
    public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
    	// Инвалидиране на изчисления транспорт, ако има
    	foreach ($query->getDeletedRecs() as $id => $rec) {
    		tcost_Calcs::sync($mvc->Master, $rec->saleId, $rec->id, NULL);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'delete' || $action == 'edit') && isset($rec)){
    		
    		if(core_Users::isPowerUser()){
    			if(!haveRole('ceo,sales')){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    	
    	if($action == 'importlisted'){
    		$requiredRoles = $mvc->getRequiredRoles('add', $rec, $userId);
    	}
    	
    	if($action == 'importlisted' && isset($rec)){
    		if($requiredRoles != 'no_one'){
    			if(isset($rec)){
    				$masterRec = sales_Sales::fetch($rec->saleId, 'contragentClassId,contragentId');
    				if(!crm_ext_ProductListToContragents::fetchField("#contragentClassId = {$masterRec->contragentClassId} AND #contragentId = {$masterRec->contragentId}")){
    					$requiredRoles = 'no_one';
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if($mvc->haveRightFor('importlisted', (object)array('saleId' => $data->masterId))){
    		$data->toolbar->addBtn('Списък', array($mvc, 'importlisted', "saleId" => $data->masterId, 'ret_url' => TRUE), "id=btnAddImp-{$masterRec->id},order=14,title=Добавяне на листвани артикули", 'ef_icon = img/16/shopping.png');
    	}
    }
    
    
    /**
     * Импорт на списък от артикули
     */
    function act_Importlisted()
    {
    	// Проверка на права
    	$this->requireRightFor('importlisted');
    	expect($saleId = Request::get('saleId', 'int'));
    	expect($saleRec = sales_Sales::fetch($saleId));
    	$this->requireRightFor('importlisted', (object)array('saleId' => $saleId));
    	
    	// Инстанциране на формата за добавяне
    	$form = cls::get('core_Form');
    	$form->title = 'Импорт на списък към|* ' . sales_Sales::getHyperlink($saleId, TRUE);
    	$form->method = 'POST';
    	
    	// Намират се всички листвани артикули
    	$listed = crm_ext_ProductListToContragents::getAll($saleRec->contragentClassId, $saleRec->contragentId);
    	
    	// И всички редове от продажбата
    	$query = $this->getQuery();
    	$query->where("#saleId = {$saleId}");
    	$recs = $query->fetchAll();
    	expect(count($listed));
    	
    	// Подготовка на полетата на формата
    	$this->prepareImportListForm($form, $listed, $recs, $saleRec);
    	$form->input();
    	
    	// Ако формата е събмитната
    	if($form->isSubmitted()){
    		$rec = $form->rec;
    		
    		// Подготовка на записите
    		$error = $error2 = $error3 = $toSave = $toUpdate = array();
    		foreach ($listed as $lId => $lRec){
    			$packQuantity = $rec->{"quantity{$lId}"};
    			$quantityInPack = $rec->{"quantityInPack{$lId}"};
    			$recId = $rec->{"rec{$lId}"};
    			$quantity = $packQuantity * $quantityInPack;
    			$productId = $rec->{"productId{$lId}"};
    			$packagingId = $rec->{"packagingId{$lId}"};
    			$packPrice = $discount = NULL;
    			
    			// Ако няма к-во пропускане на реда
    			if(empty($packQuantity)) continue;
    			
    			if(!isset($rec->id)){
    				$listId = ($saleRec->priceListId) ? $saleRec->priceListId : NULL;
    				$policyInfo = cls::get('price_ListToCustomers')->getPriceInfo($saleRec->contragentClassId, $saleRec->contragentId, $productId, $packagingId, $quantity, $saleRec->valior, $saleRec->currencyRate, $saleRec->chargeVat, $listId);
    				if(!isset($policyInfo->price)){
    					$error[$lId] = "quantity{$lId}";
    				} else {
    					$vat = cat_Products::getVat($productId, $saleRec->valior);
    					$price = deals_Helper::getPurePrice($policyInfo->price, $vat, $saleRec->currencyRate, $saleRec->chargeVat);
    					$packPrice = $price * $quantityInPack;
    					$discount = $policyInfo->discount;
    				}
    			}
    			
    			if(!deals_Helper::checkQuantity($packagingId, $packQuantity, $warning)){
    				$error3[$warning][] = "quantity{$lId}";
    			}
    			
    			if(isset($lRec->moq) && $packQuantity < $lRec->moq){
    				$error2[$lId] = "quantity{$lId}";
    			}
    			
    			// Ако няма грешка със записа
    			if(!array_key_exists($lId, $error)){
    				$obj = (object)array('quantity'       => $packQuantity * $quantityInPack, 
    						             'quantityInPack' => $quantityInPack, 
    						             'price'          => $packPrice / $quantityInPack,
    									 'discount'       => $discount,
    						             'productId'      => $productId,
    									 'packagingId'    => $packagingId,
    									 'id'             => $recId,
    									 'saleId'         => $saleRec->id,
    				);
    				
    				// Определяне дали ще се добавя или обновява
    				if(isset($obj->id)){
    					$toUpdate[] = $obj;
    				} else {
    					$toSave[] = $obj;
    				}
    			}
    		}
    		
    		if(count($error2)){
    			if(haveRole('salesMaster,ceo')){
    				$form->setWarning(implode(',', $error2), "Количеството е под МКП");
    			} else {
    				$form->setError(implode(',', $error2), "Количеството е под МКП");
    			}
    		}
    		
    		// Ако има грешка сетва се ерор
    		if(count($error)){
    			$form->setError(implode(',', $error), 'Артикулът няма цена');
    		}
    		
    		if(count($error3)){
    			foreach ($error3 as $msg => $fields){
    				$form->setError(implode(',', $fields), $msg);
    			}
    		}
    		
    		if(!count($error) && !count($error3) && (!count($error2) || (count($error2) && Request::get('Ignore')))){
    			// Запис на обновените записи
    			$this->saveArray($toUpdate, 'id,quantity');
    			$this->saveArray($toSave);
    			 
    			// Редирект към продажбата
    			followRetUrl(NULL, 'Списъкът е импортиран успешно');
    		}
    	}
    	
    	// Добавяне на тулбар
    	$form->toolbar->addSbBtn('Импорт', 'save', 'ef_icon = img/16/import.png, title = Импорт');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
    		
    	// Рендиране на опаковката
    	$tpl = $this->renderWrapping($form->renderHtml());
    	
    	return $tpl;
    }
    
    
    /**
     * Подготовка на полетата към формата за листвани артикули
     * 
     * @param core_Form $form
     * @param array $listed
     * @param array $recs
     * @param stdClass $saleRec
     * @return boolean void
     */
    private function prepareImportListForm(&$form, $listed, $recs, $saleRec)
    {
    	// За всеки листван артикул
    	foreach ($listed as $lId => $lRec){
    		$caption = "|" . cat_Products::getTitleById($lRec->productId) . "|*";
    		$caption .= " |" . cat_UoM::getShortName($lRec->packagingId);
    	
    		$listId = ($saleRec->priceListId) ? $saleRec->priceListId : NULL;
    		
    		$policyInfo = cls::get('price_ListToCustomers')->getPriceInfo($saleRec->contragentClassId, $saleRec->contragentId, $lRec->productId, $lRec->packagingId, 1, $saleRec->valior, $saleRec->currencyRate, $saleRec->chargeVat, $listId);
    		$discount = isset($policyInfo->discount) ? $policyInfo->discount : NULL;
    		
    		// Проверка дали вече не просъства в продажбата
    		$res = array_filter($recs, function (&$e) use ($lRec, $discount) {
    			if($e->productId == $lRec->productId && $e->packagingId == $lRec->packagingId && !isset($e->batch) && $e->discount == $discount && !isset($e->tolerance) && !isset($e->term)){
    				return TRUE;
    			}
    			return FALSE;
    		});
    	
    		$key = key($res);
    		$exRec = $res[$key];
    		
    		// Подготовка на полета за всеки артикул
    		$form->FLD("productId{$lId}", "int", "К-во,input=hidden");
    		$form->FLD("packagingId{$lId}", "int", "К-во,input=hidden");
    		$form->FLD("rec{$lId}", "int", "input=hidden");
    		$form->FLD("quantityInPack{$lId}", "double", "input=hidden");
    		$form->FLD("quantity{$lId}", "double(Min=0)", "caption={$caption}->Количество");
    		$form->setDefault("productId{$lId}", $lRec->productId);
    		$form->setDefault("packagingId{$lId}", $lRec->packagingId);
    		if(isset($lRec->moq)){
    			$moq = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($lRec->moq);
    			$form->setField("quantity{$lId}", "unit=|*<i>|МКП||MOQ|* {$moq}</i>");
    		}
    		
    		// Ако иам съшествуващ запис, попълват му се стойностите
    		if(isset($exRec)){
    			$form->setDefault("rec{$lId}", $exRec->id);
    			$form->setDefault("quantity{$lId}", $exRec->packQuantity);
    			$form->setDefault("quantityInPack{$lId}", $exRec->quantityInPack);
    		}
    		
    		// Задаване на к-то в опаковката
    		$packRec = cat_products_Packagings::getPack($lRec->productId, $lRec->packagingId);
    		$quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
    		$form->setDefault("quantityInPack{$lId}", $quantityInPack);
    	}
    }
}
