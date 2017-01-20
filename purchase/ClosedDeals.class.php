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
class purchase_ClosedDeals extends deals_ClosedDeals
{
    /**
     * Заглавие
     */
    public $title = 'Документи за приключване на покупка';


    /**
     * Абревиатура
     */
    public $abbr = 'Cdp';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=purchase_transaction_CloseDeal';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'purchase_Wrapper, acc_plg_Contable, plg_RowTools, plg_Sorting,
                    doc_DocumentPlg, doc_plg_HidePrices, plg_Search';
    
    
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
     * Списък с роли на потребители, при действията на които с дадения документ
     * абонираните потребители не се нотифицират
     */
    public $muteNotificationsBy = 'system';

    
    /**
     * След дефиниране на полетата на модела
     */
    public static function on_AfterDescription(core_Master &$mvc)
    {
    	// Добавяме към модела, поле за избор на с коя сделка да се приключи
    	$mvc->FLD('closeWith', 'key(mvc=purchase_Purchases,allowEmpty)', 'caption=Приключи с,input=none');
    }
    
    
    /**
     * Имплементиране на интерфейсен метод
     * @see deals_ClosedDeals::getDocumentRow()
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
     * Връща разликата с която ще се приключи сделката
     * @param mixed  $threadId - ид на нишката или core_ObjectReference
     * 							 към първия документ в нишката
     * @return double $amount - разликата на платеното и експедираното
     */
    public static function getClosedDealAmount($threadId)
    {
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	$jRecs = acc_Journal::getEntries(array($firstDoc->getInstance(), $firstDoc->that));
    	 
    	$cost = acc_Balances::getBlAmounts($jRecs, '6912', 'debit')->amount;
    	$inc = acc_Balances::getBlAmounts($jRecs, '7912', 'credit')->amount;
    	 
    	// Разликата между платеното и доставеното
    	return $inc - $cost;
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->DOC_NAME = tr("ПОКУПКА");
    	
    	if($rec->closeWith){
    		$row->closeWith = ht::createLink($row->closeWith, array('purchase_Purchases', 'single', $rec->closeWith));
    	}
    }
    
    
	/**
     * Дали разликата на доставеното - платеното е в допустимите граници
     */
    public static function isPurchaseDiffAllowed($purchaseRec)
    {
    	$diff = round($purchaseRec->amountBl, 2);
    	$conf = core_Packs::getConfig('acc');
    	$res = ($diff >= -1 * $conf->ACC_MONEY_TOLERANCE && $diff <= $conf->ACC_MONEY_TOLERANCE);
    	
    	return $res;
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($res == 'no_one') return;
    	
    	if(($action == 'add' || $action == 'conto' || $action == 'restore') && isset($rec)){
    		
    		// Ако има ориджин
    		if($origin = $mvc->getOrigin($rec)){
	    		$originRec = $origin->fetch();
	    		
	    		if($originRec->state == 'active' && $origin->isInstanceOf('purchase_Purchases')){
	    			
	    			// Ако разликата между доставеното/платеното е по голяма, се изисква
	    			// потребителя да има по-големи права за да създаде документа
	    			if(!self::isPurchaseDiffAllowed($originRec)){
	    				$res = 'ceo,purchaseMaster';
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
    	// Може ли да се добави към нишката
    	$res = parent::canAddToThread($threadId);
    	if(!$res) return FALSE;
    	 
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	
    	// Може само към нишка, породена от покупка
    	if(!$firstDoc->isInstanceOf('purchase_Purchases')) return FALSE;
    	 
    	return TRUE;
    }
}