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
    public $abbr = 'Cdp';
    
    
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
     * Отчитане на извънредните приходи/разходи от сделката
     * Ако в текущата сделка салдото по сметка 401 е различно от "0"
     * 
     * Сметка 401 има Кредитно (Ct) салдо
     * 		
     * 		Намаляваме задълженията си към Доставчика с неиздължената сума с обратна (revers) операция, 
     *		със сумата на кредитното салдо на с/ка 401 но с отрицателен знак
     * 
     * 			Dt: 7912 - Отписани задължения по Покупки
     * 			Ct: 401 - Задължения към доставчици
     * 		
     * 		Отнасяме отписаните задължения (извънредния приход) по сделката като печалба по сметка 123 - Печалби и загуби от текущата година
     * 		със сумата на кредитното салдо на с/ка 401
     * 
     * 			Dt: 7912 - Отписани задължения по Покупки
     * 			Ct: 123 - Печалби и загуби от текущата година
     * 
     * Сметка 401 има Дебитно (Dt) салдо
     * 		
     * 		Намаляваме плащанията към Доставчика с надплатената сума с обратна (revers) операция, със сумата
     * 		на дебитното салдо на с/ка 401 но с отрицателен знак
     * 
     * 			Dt: 401 - Задължения към доставчици
     * 			Ct: 6912 - Извънредни разходи по Покупки
     * 
     * 		Отнасяме извънредните разходи по сделката като загуба по сметка 123 - Печалби и загуби от текущата година, със сумата на дебитното салдо на с/ка 401
     * 
     * 			Dt: 123 - Печалби и загуби от текущата година
     * 			Ct: 6912 - Извънредни разходи по Покупки
     * 
     */
	protected function getCloseEntry($amount, $totalAmount, $docRec, $firstDoc)
    {
    	$entry = array();
    	 
    	if($amount == 0) return $entry;
    	
    	// Сметка 401 има Дебитно (Dt) салдо
    	if($amount > 0){
    		$entry1 = array(
    				'amount' => -1 * $amount,
    				'debit' => array('401',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    						'quantity' => currency_Currencies::round($amount / $docRec->currencyRate)),
    				'credit'  => array('6912',
    								array($docRec->contragentClassId, $docRec->contragentId),
	            					array($firstDoc->className, $firstDoc->that)),
    				);
    		
    		$entry2 = array(
    				'amount' => $amount,
    				'debit' => array('123', $this->year->id),
    				'credit'  => array('6912',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				);
    		
        // Сметка 401 има Кредитно (Ct) салдо
    	} elseif($amount < 0){
    		$entry1 = array(
    				'amount' => $amount,
    				'debit'  => array('7912',
    								array($docRec->contragentClassId, $docRec->contragentId),
	            					array($firstDoc->className, $firstDoc->that)),
    				'credit' => array('401',
    									array($docRec->contragentClassId, $docRec->contragentId),
	    								array($firstDoc->className, $firstDoc->that), 
	                        			array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
	                       			'quantity' => currency_Currencies::round($amount / $docRec->currencyRate)));
    		
    		$entry2 = array(
    				'amount' => abs($amount),
    				'debit'  => array('7912',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit' => array('123', $this->year->id));
    	}
    	
    	// Връщане на записа
    	return array($entry1, $entry2);
    }
    
    
	/**
     * Прехвърля не неначисленото ДДС
     * Ако в текущата сделка салдото по сметка 4530 е различно от "0":
     * 
     * Сметка 4530 има Кредитно (Ct) салдо;
     * 
     * 		Увеличаваме задълженията си към Доставчика със сумата на надфактурираното ДДС, със сумата на кредитното салдо на с/ка 4530
     * 
     * 			Dt: 4530 - ДДС за начисляване
     * 			Ct: 401 - Задължения към доставчици
     * 
     * Сметка 4530 има Дебитно (Dt) салдо;
     * 
     * 		Тъй като отделеното за начисляване и нефактурирано (неначислено) ДДС не може да бъде възстановено, както се е 
     * 		очаквало при отделянето му за начисляване по с/ка 4530, го отнасяме като извънреден разход по сделката,
     * 		със сумата на дебитното салдо (отделеното, но неначислено ДДС) на с/ка 4530
     * 
     * 			Dt: 6912 - Извънредни разходи по Покупки
     * 			Ct: 4530 - ДДС за начисляване
     * 
     * 		и го приключваме като намаление на финансовия резултат за годината със същата сума
     * 
     * 			Dt: 123 - Печалби и загуби от текущата година
     * 			Ct: 6912 - Извънредни разходи по Покупки
     * 
     */
    protected function transferVatNotCharged($dealInfo, $docRec, &$total, $firstDoc)
    {
    	$entries = array();
    	
    	$jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
    	$blAmount = acc_Balances::getBlAmounts($jRecs, '4530')->amount;
    	
    	$total += abs($blAmount);
    	
    	if($blAmount == 0) return $entries;
    	
    	// Сметка 4530 има Кредитно (Ct) салдо
    	if($blAmount < 0){
    		$entries = array('amount' => abs($blAmount),
    				'debit'  => array('4530', array($firstDoc->className, $firstDoc->that)),
    				'credit' => array('401',
    								array($docRec->contragentClassId, $docRec->contragentId),
    								array($firstDoc->className, $firstDoc->that),
    								array('currency_Currencies', currency_Currencies::getIdByCode($dealInfo->get('currency'))),
    							'quantity' => abs($blAmount)));
    	} elseif($blAmount > 0){
    		
    		// Сметка 4530 има Дебитно (Dt) салдо
    		$entries1 = array('amount' => $blAmount,
    							'debit' => array('6912',
	    							array($docRec->contragentClassId, $docRec->contragentId),
	            					array($firstDoc->className, $firstDoc->that)),
    							'credit'  => array('4530', 
    								array($firstDoc->className, $firstDoc->that),
    								'quantity' => $blAmount));
    		
    		$entries2 = array('amount' => $blAmount,
    							'debit' => array('123', $this->year->id),
    							'credit' => array('6912',
    								array($docRec->contragentClassId, $docRec->contragentId),
    								array($firstDoc->className, $firstDoc->that)),
    							);
    		
    		$total += $blAmount;
    		$entries = array($entries1, $entries2);
    		
    	}
    	
    	// Връщаме ентритата
    	return $entries;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     */
    public static function canAddToThread($threadId)
    {
    	// Можели да се добави към нишката
    	$res = parent::canAddToThread($threadId);
    	if(!$res) return FALSE;
    	 
    	$dealInfo = static::getDealInfo($threadId);
    	 
    	// Може само към нишка, породена от продажба
    	if($dealInfo->dealType != purchase_Purchases::AGGREGATOR_TYPE) return FALSE;
    	 
    	return TRUE;
    }
    
    
    /**
     * Ако в текущата сделка салдото по сметка 402 е различно от "0"
     * 
     * 		Намаляваме задължението си към доставчика със сумата на платения му аванс, респективно - намаляваме 
     * 		направените към Доставчика плащания с отрицателната сума на евентуално върнат ни аванс, без да сме
     * 		платили такъв (т.к. системата допуска създаването на revert операция без наличието на права такава преди това),
     * 		със сумата 1:1 (включително и ако е отрицателна) на дебитното салдо на с/ка 402
     * 
     * 			Dt: 401 Задължения към доставчици
     * 			Ct: 402 Вземания от доставчици по аванси
     */
    public function trasnferDownpayments(bgerp_iface_DealAggregator $dealInfo, $docRec, &$total, $firstDoc)
    {
    	$entryArr = array();
    	 
    	$docRec = $firstDoc->rec();
    	 
    	$jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
    	 
    	// Колко е направеното авансовото плащане
    	$downpaymentAmount = acc_Balances::getBlAmounts($jRecs, '402')->amount;
    	if($downpaymentAmount == 0) return $entryArr;
    	
    	// Валутата на плащането е тази на сделката
    	$currencyId = currency_Currencies::getIdByCode($dealInfo->get('currency'));
    	$amount = currency_Currencies::round($downpaymentAmount / $dealInfo->get('rate'), 2);
    	
    	$entry = array();
    	$entry['amount'] = currency_Currencies::round($downpaymentAmount);
    	$entry['debit'] = array('401',
    			array($docRec->contragentClassId, $docRec->contragentId),
    			array($firstDoc->className, $firstDoc->that),
    			array('currency_Currencies', $currencyId),
    			'quantity' => $amount);
    	
    	$entry['credit'] = array('402',
    			array($docRec->contragentClassId, $docRec->contragentId),
    			array($firstDoc->className, $firstDoc->that),
    			array('currency_Currencies', $currencyId),
    			'quantity' => $amount);
    	
    	$total += $entry['amount'];
    	
    	return $entry;
    }
}