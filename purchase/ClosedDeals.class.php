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
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf';
    
    
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
	public $canConto = 'ceo,purchase';
	
	
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
    public $priceFields = 'costAmount, incomeAmount';
    
    
    /**
     * Какви ще са контировките на надплатеното/отписаното и авансите
     */
    public $contoAccounts = array('downpayments' => array(
    									'debit' => '401',
    									'credit' => '402'),);
    
    
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
    	$row->DOC_NAME = tr("ПОКУПКА");
    	
    	if($rec->amount == 0){
    		$costAmount = $incomeAmount = 0;
    	} elseif($rec->amount < 0){
    		$incomeAmount = -1 * $rec->amount;
    		$costAmount = 0;
    		$row->type = tr('Приход');
    	} elseif($rec->amount > 0){
    		$costAmount = -1 * $rec->amount;
    		$incomeAmount = 0;
    		$row->type = tr('Разход');
    	}
    	
    	$row->costAmount = $mvc->fields['amount']->type->toVerbal($costAmount);
    	$row->incomeAmount = $mvc->fields['amount']->type->toVerbal($incomeAmount);
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
    public static function isPurchaseDiffAllowed($purchaseRec)
    {
    	$diff = round($purchaseRec->amountDelivered - $purchaseRec->amountPaid, 2);
    	$conf = core_Packs::getConfig('purchase');
    	$res = ($diff >= -1 * $conf->PURCHASE_CLOSE_TOLERANCE && $diff <= $conf->PURCHASE_CLOSE_TOLERANCE);
    	
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
	    			
	    		if($res == 'no_one') return;
    			
    			// Може да се добавя само към тред с покупка
    			if($origin->instance instanceof sales_Sales) return $res = 'no_one';
    			
	    		// Ако разликата между доставеното/платеното е по голяма, се изисква
	    		// потребителя да има по-големи права за да създаде документа
	    		if(!self::isPurchaseDiffAllowed($originRec)){
	    			$res = 'ceo,purchaseMaster';
	    		} else {
	    			$res = 'ceo,purchase';
	    		}
    		}
    	}
    }
    
    
    /**
     * Връща записа за начисляване на извънредния приход/разход
     * ------------------------------------------------------
     * Надплатеното:  Dt: 6912. Надплатени по покупки
     * 				  Ct:  401. Задължения към доставчици (Доставчици, Валути)
     * 
     * Недоплатеното: Dt:  401. Задължения към доставчици (Доставчици, Валути)
     * 				  Ct: 7912. Отписани задължения по покупки
     */
	protected function getCloseEntry($amount, $totalAmount, $docRec, $dealType)
    {
    	$entry = array();
    	
    	if($amount < 0){
    		
    		// Записа за извънреден приход
	    	$entry = array(
	    		'amount' => $totalAmount,
	    		'debit'  => array('401',
	    								array($docRec->contragentClassId, $docRec->contragentId), 
	                        			array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
	                       			'quantity' => currency_Currencies::round($totalAmount / $docRec->currencyRate)),
	            'credit' => array('7912', 'quantity' => $totalAmount),
	    	);
    	} elseif($amount > 0){
    		// Записа за извънреден разход
	    	$entry = array(
	    		'amount' => $totalAmount,
	    		'debit'  => array('6912', 'quantity' => $totalAmount),
	    		'credit' => array('401',
	    								array($docRec->contragentClassId, $docRec->contragentId), 
	                        			array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
	                       			'quantity' => currency_Currencies::round($totalAmount / $docRec->currencyRate)),
	    	);
    	}
    	
    	// Връщане на записа
    	return $entry;
    }
    
    
	/**
     * Прехвърля не неначисленото ДДС
     * 		Dt: 302. Суровини и материали     		(Складове, Суровини и Материали)
     * 			321. Стоки и продукти			    (Складове, Стоки и Продукти)
     * 			602. Разходи за външни услуги       (Услуги)
     * 
     * 		Ct: 4530. ДДС за начисляване
     * 
     */
    protected function transferVatNotCharged($dealInfo, $docRec, &$total)
    {
    	$vatToCharge = $dealInfo->invoiced->vatToCharge;
    	
    	$total = 0;
    	$entries = array();
    	foreach ($vatToCharge as $type => $amount){
    		if($amount){
    			$amount = currency_Currencies::round($amount);
    			$total += $amount;
    			list($classId, $productId, $packagingId) = explode("|", $type);
    			$meta = cls::get($classId)->getProductInfo($productId)->meta;
    			$invProduct = $dealInfo->shipped->findProduct($productId, $classId, $packagingId);
    			
    			if(isset($meta['canStore'])){
    				$debitAcc = (isset($meta['canConvert'])) ? '302' : '321';
    				$storeId = ($dealInfo->shipped->delivery->storeId) ? $dealInfo->shipped->delivery->storeId : $dealInfo->agreed->delivery->storeId;
    				$debitEnt = array($debitAcc, array('store_Stores', $storeId),array($classId, $productId), 'quantity' => $invProduct->quantity);
    			} else {
    				$debitAcc = '602';
    				$debitEnt = array($debitAcc, array($classId, $productId), 'quantity' => $invProduct->quantity);
    			}
    				
    			$entries[] = array(
	    			'amount' => $amount,
	    			'debit'  => $debitEnt,
	            	'credit' => array('4530', 'quantity' => $amount),
    			);
    		}
    	}
    	
    	return $entries;
    }
}