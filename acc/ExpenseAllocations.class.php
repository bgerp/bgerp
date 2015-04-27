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
    var $title = "Приходни касови ордери";
    
    
    /**
     * Опашка за обновяване на записите
     */
    protected $updated = array();
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, acc_Wrapper,plg_Search,acc_plg_Contable, plg_Sorting,
                     doc_DocumentPlg, plg_Printing,acc_plg_DocumentSummary, doc_plg_HidePrices';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, valior, title=Документ, state, createdOn, createdBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo, acc';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo, acc';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Разпределяне на разходи';
    
    
    /**
     * Икона на единичния изглед
     */
    //var $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Eal";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'acc, ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'acc, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'acc, ceo';
    
    
    /**
     * Кой може да го оттегля
     */
    var $canRevert = 'acc, ceo';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'acc_ExpenseAllocationProducts,acc_ExpenseAllocationExpenses';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'acc/tpl/SingleExpenseAllocationLayout.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'notes';

    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "6.7|Счетоводни";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('valior', 'date', 'caption=Вальор,mandatory');
    	$this->FLD('notes', 'richtext(rows=3)', 'caption=Забележки');
    	
    	$this->FLD('state',
    			'enum(draft=Чернова, active=Контиран, rejected=Сторнирана, closed=Контиран)',
    			'caption=Статус, input=none'
    	);
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
    	$folderClass = doc_Folders::fetchCoverClassName($folderId);
    
    	return $folderClass == 'doc_UnsortedFolders';
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
    	$firstDocument = doc_Threads::getFirstDocument($rec->threadId);
    	if(!$firstDocument) return;
    	
    	if($firstDocument->getInstance() instanceof purchase_Purchases){
    		$dealInfo = $firstDocument->getAggregateDealInfo();
    		
    		if(count($dealInfo->shippedProducts)){
    			foreach ($dealInfo->shippedProducts as $prod){
    				$dRec = new stdClass();
    				$dRec->masterId = $rec->id;
    				$dRec->itemId = acc_Items::fetchItem($prod->classId, $prod->productId)->id;
    				$dRec->quantity = $prod->quantity;
    				
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
    	
    	$dQuery = acc_ExpenseAllocationProducts::getQuery();
    	$dQuery->where("#masterId = {$rec->id}");
    	$dQuery->show('itemId');
    	while($dRec = $dQuery->fetch()){
    		$itemTitle = acc_Items::getVerbal($dRec->itemId, 'title');
    		$detailsKeywords .= " " . plg_Search::normalizeText($itemTitle);
    	}
    	
    	$dQuery = acc_ExpenseAllocationExpenses::getQuery();
    	$dQuery->where("#masterId = {$rec->id}");
    	$dQuery->show('itemId');
    	while($dRec = $dQuery->fetch()){
    		$itemTitle = acc_Items::getVerbal($dRec->itemId, 'title');
    		$detailsKeywords .= " " . plg_Search::normalizeText($itemTitle);
    	}
    	
    	// добавяме новите ключови думи към основните
    	$res = " " . $res . " " . $detailsKeywords;
    }
}