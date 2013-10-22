<?php
/**
 * Клас 'sales_ClosedDeals'
 * Клас с който се приключва една продажба, при активация
 * сменя състоянието на затворено
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
class sales_ClosedDeals extends acc_ClosedDeals
{
    /**
     * Заглавие
     */
    public $title = 'Приключване на продажба';


    /**
     * Абревиатура
     */
    public $abbr = 'Cd';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper, plg_RowTools, sales_ClosedDealsWrapper,
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
	public $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,sales';
    
	
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Приключване на продажба';
   
    
    /**
     * Групиране на документите
     */ 
    public $newBtnGroup = "3.8|Търговия";
   
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = '';
    
    
    /**
     * В какъв тред може да се добавя документа
     * Трябва да е продажба и разликата на платеното 
     * и експедираното да е 0
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
	    		if($amount != 0){
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
}