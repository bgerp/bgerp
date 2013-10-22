<?php
/**
 * Клас 'sales_ClosedDealsCredit'
 * Клас с който се приключва една продажба, контират се извънредните
 * разходи.
 * 
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_ClosedDealsCredit extends acc_ClosedDeals
{
    /**
     * Заглавие
     */
    public $title = 'Приключване на продажба с остатък';


    /**
     * Абревиатура
     */
    var $abbr = 'Cd';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper, sales_ClosedDealsWrapper, acc_plg_Contable, plg_RowTools,
                    doc_DocumentPlg, doc_plg_HidePrices, doc_ActivatePlg, acc_plg_Registry';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
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
	var $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,sales';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,sales';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,saleId=Продажба,createdBy,createdOn';
    
	
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Приключване на продажба  с остатък';
   
    
    /**
     * Групиране на документите
     */ 
    var $newBtnGroup = "3.8|Търговия";
   
    
    /**
     * Полета свързани с цени
     */
    var $priceFields = 'amount';
    
    
    /**
     * В какъв тред може да се добавя документа
     * Трябва да е продажба и разликата на платеното 
     * и експедираното да е положителна
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
	    		if($amount >= 0){
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
    	$row->text = tr("Сделката е приключена");
    }
    
    
	/**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public static function getTransaction($id)
    {
    	$rec = static::fetchRec($id);
    	
    	// Извличане транзакцията
    	$result = parent::getTransaction($id);
    	$docRec = cls::get($rec->docClassId)->fetch($rec->docId);
    	
    	$result->entries[] = array(
    		'amount' => abs($result->totalAmount),
    		'debit' => array('7911', 'quantity' => abs($result->totalAmount)),
            'credit'  => array('411', 
                        array($docRec->contragentClassId, $docRec->contragentId), 
                        array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
                       'quantity' => abs($result->totalAmount),
                      )
        );
       
        return $result;
    }
}