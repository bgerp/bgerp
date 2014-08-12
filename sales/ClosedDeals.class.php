<?php
/**
 * Клас 'sales_ClosedDeals'
 * Клас с който се приключва една продажба
 * 
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_ClosedDeals extends acc_ClosedDeals
{
    /**
     * Заглавие
     */
    public $title = 'Приключване на продажба';


    /**
     * Абревиатура
     */
    public $abbr = 'Cds';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, acc_TransactionSourceIntf=sales_transaction_CloseDeal';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper, acc_plg_Contable, plg_RowTools, plg_Sorting,
                    doc_DocumentPlg, doc_plg_HidePrices, acc_plg_Registry, plg_Search';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,salesMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
  
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,sales';
    
	
	/**
	 * Кой може да контира документите?
	 */
	public $canConto = 'ceo,sales';
	
	
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Приключване на продажба';
   
    
    /**
     * Групиране на документите
     */ 
    public $newBtnGroup = "3.9|Търговия";
   
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'costAmount, incomeAmount';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'notes,docId,classId';
    
    
    /**
     * Връща разликата с която ще се приключи сделката
     * @param mixed  $threadId - ид на нишката или core_ObjectReference
     * 							 към първия документ в нишката
     * @return double $amount - разликата на платеното и експедираното
     */
    public static function getClosedDealAmount($threadId)
    {
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	$jRecs = acc_Journal::getEntries(array($firstDoc->instance, $firstDoc->that));
    	 
    	$cost = acc_Balances::getBlAmounts($jRecs, '6911', 'debit')->amount;
    	$inc = acc_Balances::getBlAmounts($jRecs, '7911', 'credit')->amount;
    	 
    	// Разликата между платеното и доставеното
    	return $inc - $cost;
    }
    
    
    /**
     * След дефиниране на полетата на модела
     */
    public static function on_AfterDescription(core_Master &$mvc)
    {
    	// Добавяме към модела, поле за избор на с коя сделка да се приключи
    	$mvc->FLD('closeWith', 'key(mvc=sales_Sales,allowEmpty)', 'caption=Приключи с');
    }
    
    
    /**
     * Имплементиране на интерфейсен метод
     * @see acc_ClosedDeals::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
    	$row = parent::getDocumentRow($id);
    	$title = "Приключване на продажба #{$row->saleId}";
    	$row->title = $title;
    	$row->recTitle = $title;
    	
    	return $row;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->DOC_NAME = tr("ПРОДАЖБА");
    	
    	if($rec->closeWith){
    		$row->closeWith = ht::createLink($row->closeWith, array('sales_Sales', 'single', $rec->closeWith));
    	}
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->showFields = 'search';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list', 'show' => Request::get('show')), 'id=filter', 'ef_icon = img/16/funnel.png');
    	
        $data->listFilter->input(NULL, 'silent');
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашия документ") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    

    /**
     * Дали разликата на доставеното - платеното е в допустимите граници
     */
    public static function isSaleDiffAllowed($saleRec)
    {
    	$diff = round($saleRec->amountDelivered - $saleRec->amountPaid, 2);
    	$conf = core_Packs::getConfig('sales');
    	$res = ($diff >= -1 * $conf->SALE_CLOSE_TOLERANCE && $diff <= $conf->SALE_CLOSE_TOLERANCE);
    	
    	return $res;
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'conto') && isset($rec)){
    		
    		// Ако има ориджин
    		if($origin = $mvc->getOrigin($rec)){
    			$originRec = $origin->fetch();
    			
    			if($originRec->state == 'active' && $origin->instance instanceof sales_Sales){
    				
    				// Ако разликата между доставеното/платеното е по голяма, се изисква
    				// потребителя да има по-големи права за да създаде документа
    				if(!self::isSaleDiffAllowed($originRec)){
    					$res = 'ceo,salesMaster';
    				} else {
    					$res = 'ceo,sales';
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     */
    public static function canAddToThread($threadId)
    {
    	// Можели да се добави към нишката
    	$res = parent::canAddToThread($threadId);
    	if(!$res) return FALSE;
    	
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	
    	// Може само към нишка, породена от продажба
    	if(!($firstDoc->instance instanceof sales_Sales)) return FALSE;
    	
    	return TRUE;
    }
    
    
    /**
     * След успешно контиране на документа
     */
    public static function on_AfterConto($mvc, &$res, $id)
    {
    	$rec = $mvc->fetch($id);
    	
    	// Ако ще се приключва с друга продажба
    	if(!empty($rec->closeWith) && $rec->state == 'active'){
    		$Sales = cls::get('sales_Sales');
    		
    		// взимаме договорените продукти, от продажбата която е приключена
    		$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    		$dealInfo = $firstDoc->getAggregateDealInfo();
    		$details = $dealInfo->get('products');
    		
    		// Прехвърляме ги към детайлите на продажбата с която сме я приключили
    		$Sales->invoke('AfterTransferDataFromDeal', array($rec->closeWith, $details));
    	}
    }
}