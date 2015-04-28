<?php



/**
 * Документ за Приходни касови ордери
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_ExpenseAllocations extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=acc_transaction_ExpenseAllocation';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Разпределения на разходи";
    
    
    /**
     * Опашка за обновяване на записите
     */
    protected $updated = array();
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools, acc_Wrapper,acc_plg_Contable, plg_Sorting,
                     doc_DocumentPlg, plg_Printing,acc_plg_DocumentSummary,plg_Search, doc_plg_HidePrices';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "tools=Пулт, valior, title=Документ, storeId, state, createdOn, createdBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, acc';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, acc';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Разпределяне на разходи';
    
    
    /**
     * Икона на единичния изглед
     */
    //var $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Eal";
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'acc, ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'acc, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'acc, ceo';
    
    
    /**
     * Кой може да го оттегля
     */
    public $canRevert = 'acc, ceo';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'acc_ExpenseAllocationProducts,acc_ExpenseAllocationExpenses';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'acc/tpl/SingleExpenseAllocationLayout.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'notes, storeId';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('valior', 'date', 'caption=Вальор,mandatory');
    	$this->FLD('notes', 'richtext(rows=3)', 'caption=Забележки');
    	$this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад,mandatory,input=hidden');
    	
    	$this->FLD('state',
    			'enum(draft=Чернова, active=Контиран, rejected=Сторнирана, closed=Контиран)',
    			'caption=Статус, input=none'
    	);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setDefault('valior', dt::today());
    	
    	if($form->rec->originId){
    		$origin = doc_Containers::getDocument($form->rec->originId);
    		$handler = $origin->getHandle();
    		$notes = "Към #{$handler}";
    		$form->setDefault('notes', $notes);
    		$form->setDefault('storeId', $origin->getStoreId());
    	}
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$row = new stdClass();
    	$row->title = $this->singleTitle . "№{$rec->id}";
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->state = $rec->state;
    	$row->recTitle = $row->title;
    
    	return $row;
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
    	$row->title = $mvc->getLink($rec->id, 0);
    	$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    }
    
    
 	/**
     * Прави заглавие на МО от данните в записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$me = cls::get(get_called_class());
    	
        return $me->singleTitle . " №{$rec->id}";
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
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    
    	// Може да се добавя само към нишка с начало документ 'Покупка'
    	if($firstDoc->getInstance() instanceof purchase_Purchases){
    		 
    		return TRUE;
    	}
    
    	return FALSE;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	expect($rec->originId);
    	expect($origin = doc_Containers::getDocument($rec->originId));
    	
    	if($origin->instance('acc_ExpenseAllocationSourceIntf')){
    		$shippedProducts = $origin->getStorableProducts();
    		
    		$totalAmount = 0;
    		if(count($shippedProducts)){
    			// Изчсляваме колко е общата сума на експедираните артикули
    			foreach ($shippedProducts as $prod1){
    				$totalAmount += $prod1->amount;
    			}
    			
    			// За всеки експедиран артикул, добавяме го към детайла
    			foreach ($shippedProducts as $prod){
    				$dRec = new stdClass();
    				$dRec->masterId = $rec->id;
    				$dRec->itemId = acc_Items::fetchItem($prod->classId, $prod->productId)->id;
    				$dRec->quantity = $prod->quantity;
    				
    				// Изчисляване на теглото
    				// @TODO възоснова на кое се определя теглото да се избира от мастъра
    				$dRec->weight = round($prod->amount / $totalAmount, 2);
    				
    				acc_ExpenseAllocationProducts::save($dRec);
    			}
    		}
    	}
    }
    
    
    /**
     * След промяна в детайлите на обект от този клас
     */
    public static function on_AfterUpdateDetail(core_Manager $mvc, $id, core_Manager $detailMvc)
    {
    	// Запомняне кои документи трябва да се обновят
    	if(!empty($id)){
    		$mvc->updated[$id] = $mvc->fetch($id);
    	}
    }
    
    
    /**
     * След изпълнение на скрипта, обновява записите, които са за ъпдейт
     */
    public static function on_Shutdown($mvc)
    {
    	if(count($mvc->updated)){
    		foreach ($mvc->updated as $rec) {
    			$mvc->save($rec);
    		}
    	}
    }
    
    
    /**
     * Документа винаги може да се активира, дори и да няма детайли
     */
    public static function canActivate($rec)
    {
    	if($rec->id){
    		$weight = 0;
    		$dQuery = acc_ExpenseAllocationProducts::getQuery();
    		$dQuery->where("#masterId = {$rec->id}");
    		$dQuery->show('weight');
    		
    		while($dRec = $dQuery->fetch()){
    			$weight += $dRec->weight;
    		}
    		
    		if($weight != 1){
    			
    			// Ако общото тегло не е 1 (100 %) не може да се активира документа
    			return FALSE;
    		} else {
    			
    			// Ако някой от детайлите е празен също не може да се активира
    			if(!acc_ExpenseAllocationProducts::fetch("#masterId = {$rec->id}") || !acc_ExpenseAllocationExpenses::fetch("#masterId = {$rec->id}")){
    				return FALSE;
    			}
    		}
    		
    		return TRUE;
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
    	if(!$rec->id) return;
    	
    	// Допълваме ключовите думи с тези на използваните пера
    	$detailsKeywords = '';
    	
    	// Към ключовите думи добавяме и имената на избраните пера в детайлите на документа
    	foreach (array('acc_ExpenseAllocationProducts', 'acc_ExpenseAllocationExpenses') as $Detail){
    		$dQuery = $Detail::getQuery();
    		$dQuery->where("#masterId = {$rec->id}");
    		$dQuery->show('itemId');
    		while($dRec = $dQuery->fetch()){
    			$itemTitle = acc_Items::getVerbal($dRec->itemId, 'title');
    			$detailsKeywords .= " " . plg_Search::normalizeText($itemTitle);
    		}
    	}
    	
    	// добавяме новите ключови думи към основните
    	$res = " " . $res . " " . $detailsKeywords;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if($data->toolbar->hasBtn('btnAdd')){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    



    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec)){
	    	if(empty($rec->originId)){
	    		
	    		// Без ориджин не може да се добавя
	    		$requiredRoles = 'no_one';
	    	} else {
	    		// Ориджина трябва да е Pокупка или Складова разписка
	    		$origin = doc_Containers::getDocument($rec->originId);
	    		if(!$origin->haveInterface('acc_ExpenseAllocationSourceIntf')){
	    			$requiredRoles = 'no_one';
	    		} else {
	    			
	    			// Ако в ориджина няма посочени скалдируеми артикули, не може да създаваме документа
	    			$productsToShip = $origin->getStorableProducts(1);
	    			if(!count($productsToShip)){
	    				$requiredRoles = 'no_one';
	    			}
	    		}
	    	}
    	}
    }
}