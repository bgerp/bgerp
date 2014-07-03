<?php
/**
 * Клас 'deals_ClosedDeals'
 * Клас с който се приключва една финансова сделка
 * 
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_ClosedDeals extends acc_ClosedDeals
{
    /**
     * Заглавие
     */
    public $title = 'Приключване на сделки';


    /**
     * Абревиатура
     */
    public $abbr = 'Cds';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'deals_Wrapper, acc_plg_Contable, plg_RowTools, plg_Sorting,
                    doc_DocumentPlg, doc_plg_HidePrices, acc_plg_Registry, plg_Search';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,deals';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,deals';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
  
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,dealsMaster';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,deals';
    
	
	/**
	 * Кой може да контира документите?
	 */
	public $canConto = 'ceo,deals';
	
	
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Приключване на сделка';
   
    
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
     * Имплементиране на интерфейсен метод
     * @see acc_ClosedDeals::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
    	$row = parent::getDocumentRow($id);
    	$title = "Приключване на сделка #{$row->saleId}";
    	$row->title = $title;
    	$row->recTitle = $title;
    	
    	return $row;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->DOC_NAME = tr("ФИНАНСОВА СДЕЛКА");
    	if($rec->amount == 0){
    		$costAmount = $incomeAmount = 0;
    	} elseif($rec->amount > 0){
    		$incomeAmount = $rec->amount;
    		$costAmount = 0;
    		$row->type = tr('Приход');
    	} elseif($rec->amount < 0){
    		$costAmount = $rec->amount;
    		$incomeAmount = 0;
    		$row->type = tr('Разход');
    	}
    	
    	$row->costAmount = $mvc->fields['amount']->type->toVerbal($costAmount);
    	$row->incomeAmount = $mvc->fields['amount']->type->toVerbal($incomeAmount);
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
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     */
    public static function canAddToThread($threadId)
    {
    	// Можели да се добави към нишката
    	$res = parent::canAddToThread($threadId);
    	if(!$res) return FALSE;
    
    	$dealInfo = static::getDealInfo($threadId);
    
    	// Може само към нишка, породена от продажба
    	if($dealInfo->dealType != bgerp_iface_DealResponse::TYPE_DEAL) return FALSE;
    
    	return TRUE;
    }
    
    
    /**
     * Връща транзакцията за документа
     */
    public function getTransaction($id)
    {
    	expect($rec = $this->fetchRec($id));
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        $info = static::getDealInfo($rec->threadId);
        $docRec = $firstDoc->fetch();
        $account = acc_Accounts::fetchField($docRec->accountId, 'systemId');
        
        $amount = round($info->agreed->amount / $info->agreed->rate, 2);
        
        // Извънреден разход
        if($amount < 0){
        	$debitArr = array('6913', 
        							array($docRec->contragentClassId, $docRec->contragentId),
	            					array($firstDoc->className, $firstDoc->that));
        	$creditArr = array($account,
        						array($docRec->contragentClassId, $docRec->contragentId),
        						array('deals_Deals', $docRec->id),
	    						array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
	                        'quantity' =>  abs($amount));
        } else {
        	// Извънреден приход
        	$debitArr = array($account,
        					array($docRec->contragentClassId, $docRec->contragentId),
	    					array('deals_Deals', $docRec->id), 
	                        array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
	                    'quantity' =>  abs($amount));
        	$creditArr = array('7913', 
        							array($docRec->contragentClassId, $docRec->contragentId),
	            					array($firstDoc->className, $firstDoc->that));
        }
        
        // Създаване на обекта за транзакция
        $result = (object)array(
        		'reason'      => $this->singleTitle . " #" . $firstDoc->getHandle(),
        		'valior'      => dt::now(),
        		'totalAmount' => abs($amount),
        		'entries'     => array(),
        );
        
        $result->entries[] = array('amount' => abs($amount), 'debit' => $debitArr, 'credit' => $creditArr);
        
        return $result;
    }
}