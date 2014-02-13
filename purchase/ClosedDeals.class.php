<?php
/**
 * Клас 'purchase_ClosedDeals'
 * Клас с който се приключва една покупка
 * 
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_ClosedDeals extends acc_ClosedDeals
{
    /**
     * Заглавие
     */
    public $title = 'Приключване на покупка';


    /**
     * Абревиатура
     */
    public $abbr = 'Cd';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, acc_TransactionSourceIntf=acc_ClosedDealsTransactionImpl';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'purchase_Wrapper, acc_plg_Contable, plg_RowTools, plg_Sorting,
                    doc_DocumentPlg, doc_plg_HidePrices, acc_plg_Registry';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,purchase';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,purchase';
    
  
    /**
	 * Кой може да контира документите?
	 */
	public $canConto = 'ceo,purchaseMaster';
	
	
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,purchase';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,purchase';
    
	
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Приключване на покупка';
   
    
    /**
     * Групиране на документите
     */ 
    public $newBtnGroup = "3.8|Търговия";
   
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amount';
    
    
    /**
     * Какви ще са контировките на надплатеното/отписаното и авансите
     */
    public $contoAccounts = array('income' => array(
    									'debit' => '401', 
    									'credit' => '7912'),
    							  'spending' => array(
    								 	'debit' => '6912', 
    								 	'credit' => '401'),
    							  'downpayments' => array(
    									'debit' => '401',
    									'credit' => '402'));
    
    
    /**
     * Имплементиране на интерфейсен метод
     * @see acc_ClosedDeals::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
    	$row = parent::getDocumentRow($id);
    	$title = "Приключване на покупка #{$row->saleId}";
    	$row->title = $title;
    	$row->recTitle = $title;
    	
    	return $row;
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$amount = static::getClosedDealAmount($rec->threadId);
    	
    	if($amount < 0){
    		$row->costAmount = $amount;
    		$row->incomeAmount = 0;
    	} else{
    		$row->incomeAmount = $amount;
    		$row->costAmount = 0;
    	}
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
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec)){
    		
    		// Ако има ориджин
    		if($origin = $mvc->getOrigin($rec)){
	    		$originRec = $origin->fetch();
	    		$diff = round($originRec->amountDelivered - $originRec->amountPaid, 2);
	    		$conf = core_Packs::getConfig('sales');
	    			
	    		if($originRec->state != 'active') return $res = 'no_one';
	    			
	    		// Ако няма експедирано или платено неможе да се приключва
    			if($originRec->amountDelivered == 0 || $originRec->amountPaid == 0) return $res = 'no_one';
    			
    			// Може да се добавя само към тред с покупка
    			if($origin->instance instanceof sales_Sales) return $res = 'no_one';
    			
	    		// Ако разликата между доставеното/платеното е по голяма, се изисква
	    		// потребителя да има по-големи права за да създаде документа
	    		if(!($diff >= -1 * $conf->PURCHASE_CLOSE_TOLERANCE && $diff <= $conf->PURCHASE_CLOSE_TOLERANCE)){
	    			$res = 'ceo,purchaseMaster';
	    		} else {
	    			$res = 'ceo,purchase';
	    		}
    		}
    	}
    }
}