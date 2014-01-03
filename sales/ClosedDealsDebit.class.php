<?php
/**
 * Клас 'sales_ClosedDealsDebit'
 * Клас с който се приключва една продажба, контират се извънредните
 * приходи.
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
class sales_ClosedDealsDebit extends acc_ClosedDeals
{
    /**
     * Заглавие
     */
    public $title = 'Приключване на продажба с изв. приход';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper, acc_plg_Contable, plg_RowTools,
                    doc_DocumentPlg, doc_plg_HidePrices, acc_plg_Registry';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,sales';
    
    
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
     * Заглавие в единствено число
     */
    public $singleTitle = 'Приключване на продажба с изв. приход';
   
    
    /**
     * Групиране на документите
     */ 
    public $newBtnGroup = "3.8|Търговия";
   
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amount';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = '';
    
    
    /**
     * В какъв тред може да се добавя документа
     * Трябва да е продажба и разликата на платеното
     * и експедираното да е отрицателна
     */
    static function canAddToThread($threadId)
    {
    	$res = parent::canAddToThread($threadId);
    	if($res){
    		$firstDoc = doc_Threads::getFirstDocument($threadId);
    		$info = static::getDealInfo($firstDoc);
    		$res = $info->dealType == bgerp_iface_DealResponse::TYPE_SALE;
    		if($res){
	    		$amount = static::getClosedDealAmount($firstDoc);
	    		if($amount <= 0){
	    			
	    			return FALSE;
	    		}
    		}
    	}
    	
    	return $res;
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->text = tr("Приключване на сделката с изв. приход");
    	
    }
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public static function getTransaction($id)
    {
    	$rec = static::fetchRec($id);
    	
    	// Извличане на транзакцията
    	$result = parent::getTransaction($id);
    	$docRec = cls::get($rec->docClassId)->fetch($rec->docId);
    	
    	$result->entries[] = array(
    		'amount' => $result->totalAmount,
            'debit'  => array('411', 
                        array($docRec->contragentClassId, $docRec->contragentId), 
                        array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
                       'quantity' => $result->totalAmount,
                      ), 
        	'credit' => array('7911', 'quantity' => $result->totalAmount));
       
        return $result;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод
     * @see acc_ClosedDeals::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
    	$row = parent::getDocumentRow($id);
    	$title = "Приключване на продажба {$row->saleId} с изв. приход";
    	$row->title = $title;
    	$row->recTitle = $title;
    	
    	return $row;
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'conto' && isset($rec)){
    		$amount = static::getClosedDealAmount($rec->threadId);
    		if($amount < 0){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашата продажба с изв. приход") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        return $tpl->getContent();
    }
}