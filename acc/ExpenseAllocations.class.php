<?php



/**
 * Документ за Разпределяне на разходи
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_ExpenseAllocations extends core_Master
{
    
	
	/**
	 * Име на перото за неразпределени разходи
	 */
	const UNALLOCATED_ITEM_NAME = 'Неразпределени разходи';

	
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Разпределяне на разходи";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, acc_Wrapper, doc_DocumentPlg, doc_ActivatePlg, plg_Printing, acc_plg_DocumentSummary, plg_Search, bgerp_plg_Blank';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "title=Документ,originId=Към документ,folderId,createdBy,createdOn";
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'acc, ceo, purchase';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'acc, ceo, purchase';
    
    
	/**
	 * Кой може да активира документа?
	 */
	public $canActivate = 'acc, ceo, purchase';
	
	
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Разпределяне на разходи';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Eal";
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'acc, ceo, purchase';
    
    
    /**
     * Детайли на документа
     */
    public $details = 'acc_ExpenseAllocationDetails';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'acc/tpl/SingleLayoutExpenseAllocation.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'originId,folderId';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	// Вальора на оригиналния документ, да не се извлича всеки път
    	$this->FLD('originValior', 'date', 'input=hidden,mandarory');
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
    	expect($origin = doc_Containers::getDocument($rec->originId));
    	$form->setDefault('originValior', $origin->fetchField($origin->valiorFld));
    	$form->info = tr("Разпределяне на разходи по редовете на") . " " . $origin->getLink(0);
    	
    	// Редовете за разпределяне
    	$products = $origin->getRecsForAllocation();
    	expect(count($products));
    	
    	$count = 1;
    	foreach ($products as $key => $product){
    		
    		// Вербалното име на реда е и секцията във формата
    		$name = acc_ExpenseAllocationDetails::getOriginRecTitle($product, $count);
    			
    		// Поставяне на полета за всеки артикул за разпределяне
    		$form->FLD("originRecId|{$key}", 'int', "input=hidden,silent");
    		$form->FLD("productId|{$key}", 'key(mvc=cat_Products)', "input=hidden,silent");
    		$form->FLD("packagingId|{$key}", 'key(mvc=cat_UoM)',"input=hidden,silent");
    		$form->FLD("quantityInPack|{$key}", 'double', "input=hidden,silent");
    		$form->FLD("quantity|{$key}", 'double', "caption=|*{$name}->К-во,silent");
    		$form->FLD("expenseItemId|{$key}", 'acc_type_Item(select=titleNum,allowEmpty,lists=600,allowEmpty)', "input,caption=|*{$name}-> ,inlineTo=quantity|{$key},placeholder=Избор на разход,silent");

    		// Попълваме полетата с дефолтите
    		foreach (array('productId', 'packagingId', 'quantityInPack', 'quantity', 'originRecId') as $fld){
    			$form->setDefault("{$fld}|{$key}", $product->{$fld});
    		}
    		
    		// Увеличаване на брояча
    		$count++;
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->originId = doc_Containers::getDocument($rec->originId)->getHyperLink(TRUE);
    	
    	if(isset($fields['-list'])){
    		$row->title = $mvc->getLink($rec->id, 0);
    	}
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	if(isset($rec->originId)){
    		$origin = doc_Containers::getDocument($rec->originId);
    		$data->form->title = core_Detail::getEditTitle($origin->className, $origin->that, $mvc->singleTitle, $rec->id);
    	}
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
    	$arr = (array)$rec;
    	
    	// Какво ще записваме в детайла
    	$recsToSave = array();
    	
    	// За всеки запис
    	foreach ($arr as $k => $v){
    		
    		 // Ако има динамични полета във формата
    		 if(strpos($k, "|") !== FALSE){
    		 	$split = explode('|', $k);
    		 	$recsToSave[$split[1]][$split[0]] = $v;
    		 }
    	}
    	
    	// За всяко поле
    	foreach ($recsToSave as $i => $a){
    		
    		// Ако за съответния ред е избран разход, записваме го
    		if(empty($a['expenseItemId'])) continue;
    		$dRec = (object)$a;
    		$dRec->allocationId = $rec->id;
    		
    		// Запис на ред от детайла
    		acc_ExpenseAllocationDetails::save($dRec);
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
    	return FALSE;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec)){
    		
    		// Трябва документа да има ориджин
    		if(!isset($rec->originId)){
    			$requiredRoles = 'no_one';
    			return;
    		}
    		
    		// Ако към ориджина има вече документ за разпределяне на разходи, не може да се добавя
    		if(acc_ExpenseAllocations::fetchField("#originId = {$rec->originId} AND #id != '{$rec->id}' AND #state != 'rejected'")){
    			$requiredRoles = 'no_one';
    			return;
    		}
    		 
    		// Към кой документ, ще се добавя разпределят разходи
    		$origin = doc_Containers::getDocument($rec->originId);
    		
    		//... и да е активен
    		$state = $origin->fetchField('state');
    		if($state != 'active'){
    			$requiredRoles = 'no_one';
    			return;
    		}
    		
    		// Ако към оригиния документ не може да се разпределят разходи, не може да се създава документ към него
    		if(!$origin->canAllocateExpenses()){
    			$requiredRoles = 'no_one';
    			return;
    		}
    		
    		//... и да има доспусимия интерфейс
    		if(!$origin->haveInterface('acc_ExpenseAllocatableIntf')){
    			$requiredRoles = 'no_one';
    			return;
    		}
    		
    		//... и потребителя да има достъп до него
    		if(!$origin->haveRightFor('single')){
    			$requiredRoles = 'no_one';
    			return;
    		}
    		
    		// Ако няма за разпределяне, не може да се добавя
    		$recsToAllocate = $origin->getRecsForAllocation(1);
    		
    		if(!count($recsToAllocate)){
    			$requiredRoles = 'no_one';
    			return;
    		}
    		
    		//... и да е в отворен период
    		if(acc_Periods::isClosed($rec->originValior)){
    			$requiredRoles = 'no_one';
    			return;
    		}
    	}
    	
    	// Не може да се възстановява, ако към същия ориджин има друг неоттеглен документ
    	if($action == 'restore' && isset($rec->id)){
    		if(acc_ExpenseAllocations::fetchField("#originId = {$rec->originId} AND #id != '{$rec->id}' AND #state != 'rejected'")){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	// При активиране
    	if($action == 'activate'){
    		if(isset($rec->id)){
    			
    			// Ако няма ред в детайла, не може да се активира
    			if(!acc_ExpenseAllocationDetails::fetchField("#allocationId = {$rec->id}")){
    				$requiredRoles = 'no_one';
    			} else {
    				if(acc_Periods::isClosed($rec->originValior)){
    					$requiredRoles = 'no_one';
    				}
    			}
    		} else {
    			
    			// Ако няма запис, не може да се активира
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	// Не може да се оттегля или възстановява, ако вальора на оригиналния документ е в затворен период
    	if(($action == 'restore' || $action == 'reject') && isset($rec)){
    		if(acc_Periods::isClosed($rec->originValior)){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Записва редът (записа) в таблицата
     */
    function save_(&$rec, $fields = NULL, $mode = NULL)
    {
    	// Викане на ф-ята за запис от бащата на класа
    	$id = parent::save_($rec, $fields, $mode);
    	
    	// Ако няма тред в записа, извличаме го
    	$threadId = $rec->threadId;
    	if(empty($threadId) && $id){
    		$threadId = $this->fetchField($id, 'threadId');
    	}
    	
    	// Инвалидираме кеша на документите в треда
    	doc_DocumentCache::threadCacheInvalidation($threadId);
    	
    	// Връщане на резултата от записа
    	return $id;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	$data->toolbar->removeBtn('btnEdit');
    	
    	// Ако вальора на документа е в затворен сч. период показваме бутона, но да показва грешка
    	if($rec->state == 'draft' && !$data->toolbar->hasBtn('btnActivate') && acc_ExpenseAllocationDetails::fetchField("#allocationId = {$rec->id}")){
    		if(haveRole($mvc->canActivate)){
    			if(acc_Periods::isClosed($rec->originValior)){
    				$data->toolbar->addBtn('Активиране', array(), array('error' => 'Не може да се активира, когато е към документ в затворен сч. период'), 'id=btnActivate,ef_icon = img/16/lightning.png,title=Активиране на документа');
    			}
    		}
    	}
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetchRec($id);
    	 
    	$row = new stdClass();
    	$row->title = $this->getRecTitle($rec);
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->state = $rec->state;
    	$row->recTitle = $rec->title;
    	 
    	return $row;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(__CLASS__);
    
    	return "{$self->singleTitle} №{$rec->id}";
    }
    
    
    /**
     * Връща кешираната информацията за реда от оридижина или за целия ориджин
     * 
     * @param int $originId        - ориджин ид
     * @param string $recId        - ид на записа от ориджина, NULL ако искаме всичките
     * @return array|stdClass $res - кешираните записи за реда или за документа;
     */
    public static function getRecsForAllocationFromOrigin($originId, $recId = NULL)
    {
    	// Ако няма запис в кеша, кешираме
    	if(!array_key_exists($originId, static::$cache)){
    		$origin = doc_Containers::getDocument($originId);
    		static::$cache[$originId] = $origin->getRecsForAllocation();
    	}
    	
    	// Връщаме кешираната информация
    	$res = isset($recId) ? static::$cache[$originId][$recId] : static::$cache[$originId];
    	
    	return $res;
    }
    
    
    /**
     * Форсира ид-то на перото за неразпределени разходи
     * 
     * @return int - ид на перото
     */
    public static function getUnallocatedItemId()
    {
    	return acc_Items::forceSystemItem(self::UNALLOCATED_ITEM_NAME, 'unallocated', 'costObjects')->id;
    }
    
    
    /**
     * Помощен метод обработващ записите за подаден ред
     * 
     * @param int $id          - ид на запис
     * @param int $originRecId - ид на запис от оригиналния документ
     * @param int $productId   - ид на артикул
     * @param double $quantity - оригинално к-во
     * @param double $amount   - обща сума за разпределяне
     * @return array $res      - обработените записи
     * 		о amount        - сума за разпределяне
     * 		o productId     - ид на артикула
     * 		о quantity      - к-во за разпределяне
     * 		o reason        - описание на контировката
     * 		o expenseItemId - ид-то на перото към което ще се разпределя
     */
    private static function getRowRecs($id, $originRecId, $productId, $quantity, $amount)
    {
    	$res = array();
    	
    	// Извличане на записите за въпросния ред от оригиналния документ
    	$dQuery = acc_ExpenseAllocationDetails::getQuery();
    	$dQuery->where(array("#allocationId = [#1#] AND #originRecId = [#2#]", $id, $originRecId));
    	$dRecs = $dQuery->fetchAll();
    	
    	// Запомнят се общата сума и к-во за разпределяне
    	$totalQuantity = $quantity;
    	$totalAmount = $amount;
    	$allocatedAmount = 0;
    	
    	// Ако са намерени записи
    	if(is_array($dRecs) && count($dRecs)){
    		$dRecs = array_values($dRecs);
    		
    		// За всеки запис
    		for($i = 0; $i <= count($dRecs) - 1; $i++){
    			$dRec = $dRecs[$i];
    			$nextRec = $dRecs[$i + 1];
    			
    			// Подготвят се данните за разпределяне
    			$r = (object)array('productId' => $productId);
    			$r->reason = 'Приети услуги и нескладируеми консумативи';
    			
    			$r->expenseItemId = $dRec->expenseItemId;
    			acc_journal_Exception::expect($r->expenseItemId, 'Невалиден раход');
    			acc_journal_Exception::expect($dRec->productId == $productId, 'Невалиден артикул');
    				
    			// Задаване на к-то и приспадане
    			$r->quantity = $dRec->quantity;
    			
    			// Какво к-во остава за разпределяне
    			$quantity -= $r->quantity;
    			
    			// Ако няма следващ обект и цялото к-во е разпределено
    			if(!is_object($nextRec) && $quantity <= 0){
    				
    				// Сумата е остатака от сумата за разпределяне и разпределеното до сега
    				// така е подсигурено че няма да има разлики в сумите
    				$r->amount = $totalAmount - $allocatedAmount;
    			} else {
    				
    				// Ако има следващ обект и още к-во за разпределяне, сумата се изчислява пропорционално
    				$r->amount = round($r->quantity / $totalQuantity * $totalAmount, 2);
    				$allocatedAmount += $r->amount;
    			}
    			
    			// Добавяне на редовете
    			$res[] = $r;
    		}
    		
    		// Ако има неразпределено количество
    		if($quantity > 0){
    			
    			// Неразпределеното количество, се отнася към неразпределения разход обект
    			$r = (object)array('productId' => $productId);
    			$r->reason = 'Приети непроизводствени услуги и нескладируеми консумативи';
    			$r->expenseItemId = self::getUnallocatedItemId();
    			$r->quantity = $quantity;
    			$r->amount = $totalAmount - $allocatedAmount;
    			 
    			$res[] = $r;
    		}
    	}
    	
    	// Връщане на намерените резултати
    	return $res;
    }
    
    
    /**
     * Помощна ф-я, определяща как ще се разпределят редовете по разходи
     * Вика се от документите за купуване на услуги. Там за всеки запис на невложима и не ДМА услуга, 
     * се проверява как трябва да се разпредели по разходи, Ако в записа има перо за разпределяне директно се
     * разпределя, Ако няма се проверява дали има към документа, документ за разпределяне на разходи.
     * Ако има се гледа какво к-во от оригиналния ред към кой разход се отнася, неразпределеното се отнася към
     * неразпределените.
     * 
     * Ако артикула е невложим и не е ДМА:
     * Ако в реда от оригиналния документ имаме разходен обект към него.
     * Ако няма се гледа имали документ за Разпределение на разходи към документа, в които е разпределено
     * к-во от реда, ако има се разбива записа по-количествата, а остатъка отива към неразпределени
     * 
     * Ако артикула е Вложим, винаги отива към неразпределени.
     * Ако е ДМА се разпределя към себе си.
     * 
     * @param int $originId         - ориджин на документа
     * @param int $productId        - ид на артикул
     * @param double $quantity      - к-во от оригиналния документ
     * @param int $expenseItemId    - перо за разпределяне от оригиналния документ
     * @param double $amount        - сума на реда за разпределяне
     * @param int $recId            - ид на реда, който ще се разпределя
     * @param double|NULL $discount - отстъпката от цената, ако има
     * @return array $res           - масив с данни за контировката на услугата
     * 			о amount        - сума за разпределяне
     * 			o productId     - ид на артикула
     * 			о quantity      - к-во за разпределяне
     * 			o reason        - описание на контировката
     * 			o expenseItemId - ид-то на перото към което ще се разпределя
     */
    public static function getRecsByExpenses($originId, $productId, $quantity, $expenseItemId, $amount, $recId, $discount = NULL)
    {
    	$res = array();
    	
    	// Ако артикула е складируем, се пропуска
    	$pInfo = cat_Products::getProductInfo($productId);
    	if(isset($pInfo->meta['canStore'])) return $res;
    
    	// От сумата се приспада отстъпката, ако има
    	$amount = ($discount) ?  $amount * (1 - $discount) : $amount;
    
    	$obj = (object)array('productId'     => $productId, 
    						 'quantity'      => $quantity, 
    						 'expenseItemId' => $expenseItemId,
    						 'amount'        => round($amount, 2),
    						 'reason'        => 'Приети услуги и нескладируеми консумативи');
    	
    	if(isset($pInfo->meta['fixedAsset'])){
    		
    		// Ако артикула е ДМА се отнася като разход към себе си
    		$obj->expenseItemId = array('cat_Products', $productId);
    		$obj->reason = 'Приети ДА';
    	} elseif($pInfo->meta['canConvert']){
    		
    		// Ако артикула е вложим, отива към 'неразпределени'
    		$obj->expenseItemId = self::getUnallocatedItemId();
    		$obj->reason = 'Приети услуги и нескладируеми консумативи за производството';
    	} else {
    		
    		// Ако няма разходен обект
    		if(empty($obj->expenseItemId)) {
    			// Проверка имали към документа, документ за разпределяне на разходи
    			if($id = self::fetchField(array("#originId = [#1#] AND #state = 'active'", $originId))){
    				
	    			// Опит за връщане на обработените записи от документа
	    			acc_journal_Exception::expect(!$expenseItemId, 'Наличен разход в документ, при пуснато разпределение на разходи');
	    			$dRecs = self::getRowRecs($id, $recId, $productId, $quantity, $amount);
	    			
	    			// Ако има записи се връщат директно
	    			if(count($dRecs)) return $dRecs;
    			}
    		}
    		
    		// Ако не е уточнено как се разпределя, отива към неразпределени
    		if(empty($obj->expenseItemId)) {
    			$obj->expenseItemId = self::getUnallocatedItemId();
    			$obj->reason = 'Приети непроизводствени услуги и нескладируеми консумативи';
    		}
    	}
    	
    	// Задължително трябва да има разходно перо
    	if(!is_array($obj->expenseItemId)){
    		acc_journal_Exception::expect(acc_Items::fetch($obj->expenseItemId), 'Невалидно разходно перо');
    	}
    	
    	$res[] = $obj;
    
    	// връщане на редовете
    	return $res;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	// При запис в активно състояние и оттегляне на активен документ
    	if($rec->state == 'active' || ($rec->state == 'rejected' && $rec->brState == 'active')){
    		$originId = ($rec->originId) ? $rec->originId : $mvc->fetchField($rec->id, 'originId');
    		$mvc->recontoOrigin($originId);
    	}
    }
    
    
    /**
     * Реконтиране на оригиналния документ
     * 
     * @param int $originId - ид на ориджина
     * @return void
     */
    private function recontoOrigin($originId)
    {
    	// Оригиналния документ трябва да не е в затворен период
    	$origin = doc_Containers::getDocument($originId);
    	expect(!acc_Periods::isClosed($origin->fetchField($origin->valiorFld)), 'Периода не трябва да е затворен');
    	
    	// Изтриване на старата транзакция на документа
    	acc_Journal::deleteTransaction($origin->getClassId(), $origin->that);
    	
    	// Записване на новата транзакция на документа
    	$success = acc_Journal::saveTransaction($origin->getClassId(), $origin->that, FALSE);
    	expect($success, $success);
    	
    	// Нотифициране на потребителя
    	$msg = "Реконтиране на|* #{$origin->getHandle()}";
    	core_Statuses::newStatus($msg);
    }
    
    
    /**
     * Визуализиране на направения разход
     * 
     * @param int|NULL $expenseItemId - ид на перо на разход, или NULL ако няма
     * @param int $productId          - ид на артикул
     * @return string $string         - визуализирането на разхода
     */
    public static function displayExpenseItemId($expenseItemId, $productId)
    {
    	$string = '';
    	
    	// Ако има разход
    	if(isset($expenseItemId)){
    		$eItem = acc_Items::getVerbal($expenseItemId, 'titleLink');
    		$pInfo = cat_Products::getProductInfo($productId);
    		$hint = isset($pInfo->meta['fixedAsset']) ? 'Артикулът вече е ДА и не може да бъде разпределян като разход' : (isset($pInfo->meta['canConvert']) ? 'Артикулът вече е вложим и не може да бъде разпределян като разход' : NULL);
    		
    		// Ако артикулът е ДМА или Вложим и има избран разход, то той ще бъде пренебренат, за това се показва информация на потребителя
    		$content = "<b class='quiet'>" . tr("Разход за") . "</b>: {$eItem}";
    		if($hint){
    			$content = ht::createHint($content, $hint, 'warning', FALSE)->getContent();
    			$content = "<span style='color:red !important'>{$content}</span>";
    		}
    		
    		$string = "<div class='small'>{$content}</div>";
    	}
    	
    	return $string;
    }
}