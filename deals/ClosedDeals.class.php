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
    public $abbr = 'Dcd';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, acc_TransactionSourceIntf=deals_transaction_CloseDeal';
    
    
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
     * След дефиниране на полетата на модела
     */
    public static function on_AfterDescription(core_Master &$mvc)
    {
    	// Добавяме към модела, поле за избор на с коя сделка да се приключи
    	$mvc->FLD('closeWith', 'key(mvc=deals_Deals,allowEmpty)', 'caption=Приключи с,input=none');
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
    	$jRecs = acc_Journal::getEntries(array($firstDoc->instance, $firstDoc->that));
    
    	$cost = acc_Balances::getBlAmounts($jRecs, '6913', 'debit')->amount;
    	$inc = acc_Balances::getBlAmounts($jRecs, '7913', 'credit')->amount;
    	
    	bp($cost, $inc, $jRecs);
    	// Разликата между платеното и доставеното
    	return $inc - $cost;
    }
    
    
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
    	$costAmount = $incomeAmount = 0;
    	if($rec->amount > 0){
    		$incomeAmount = $rec->amount;
    		$costAmount = 0;
    		$row->type = tr('Приход');
    	} elseif($rec->amount < 0){
    		$costAmount = $rec->amount;
    		$incomeAmount = 0;
    		$row->type = tr('Разход');
    	}
    	
    	$row->costAmount = $mvc->getFieldType('amount')->toVerbal($costAmount);
    	$row->incomeAmount = $mvc->getFieldType('amount')->toVerbal($incomeAmount);
    	
    	//@TODO а ако е авансов отчет ??
    	if($rec->closeWith){
    		$row->closeWith = ht::createLink($row->closeWith, array('deals_Deals', 'single', $rec->closeWith));
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
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     */
    public static function canAddToThread($threadId)
    {
    	// Можели да се добави към нишката
    	$res = parent::canAddToThread($threadId);
    	
    	if(!$res) return FALSE;
    
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	
    	if(!($firstDoc->instance instanceof deals_Deals)) return FALSE;
    	
    	return TRUE;
    }
}
