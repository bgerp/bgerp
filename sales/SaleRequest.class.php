<?php
/**
 * Документ "Заявка за продажба"
 *
 * Мениджър на документи за Заявки за продажба, от фактура
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_SaleRequest extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Заявки за продажба';


    /**
     * Абревиатура
     */
    var $abbr = 'Sr';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, bgerp_DealIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper, plg_Printing, doc_DocumentPlg,
    					 doc_ActivatePlg, bgerp_plg_Blank';
       
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,sales';
    
    
    /**
     * Кой е текущия таб
     */
    public $currentTab = 'Оферти';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    //public $listFields = 'id, date, recipient, attn, deliveryTermId, createdOn,createdBy';
    
    
	/**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Заявка за продажба';
    
    
    /**
     * Работен кеш за вече извлечените продукти
     */
    protected static $cache = array();

    
    /**
     * Шаблон за еденичен изглед
     */
    //var $singleLayoutFile = 'sales/tpl/SingleSaleRequest.shtml';
    
   
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('data', 'blob(serialize,compress)', 'caption=Данни,input=none');
    }
    
    
 	/**
     * Преди всеки екшън на мениджъра-домакин.
     * Показва форма за уточняване на к-та
     * на оферираните продукти
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
        if ($action != 'add' && $action != 'edit') {
            // Плъгина действа само при добавяне или редакция на документ
            return;
        }
        
        if (!$mvc->haveRightFor($action)) {
            // Няма права за този екшън - не правим нищо - оставяме реакцията на мениджъра.
            return;
        }
        
        expect($originId = Request::get('originId'));
        $origin = doc_Containers::getDocument($originId);
    	expect($origin->className == 'sales_Quotations');
    	$originRec = $origin->fetch();
    	
        $form = $mvc->getFilterForm($origin->that);
        $fRec = $form->input();
        if($form->isSubmitted()){
        	$rec = (object)array('originId' => $originId,
        						 'threadId' => $originRec->threadId,
        						 'folderId' => $originRec->folderId,);
        	$rec->data = $mvc->prepareData($fRec, $originRec);
        	bp($rec->data,$rec); //@TODO
        }
        
        $tpl = $mvc->renderWrapping($form->renderHtml());
        return FALSE;
    }
    
    
    /**
     * Подготвя данните получени от формата и от заявката във
     * формат на bgerp_iface_DealResponse
     * @param stdClass $rec
     * @param stdClass $quoteRec
     */
    private function prepareData($rec,  $quoteRec)
    {
    	$items = $this->prepareProducts($rec, $amount);
    	
        $result = new stdClass();
        $result->dealType = 'sale'; //bgerp_iface_DealResponse::TYPE_SALE;
        
        $result->agreed->amount                  = $amount;
        $result->agreed->currency                = $quoteRec->paymentCurrencyId;
        if($rec->deliveryPlaceId){
        	$result->agreed->delivery->location  = crm_Locations::fetchField("#title = '{$quoteRec->deliveryPlaceId}'", 'id');
        }
        $result->agreed->delivery->term          = $quoteRec->deliveryTermId;
    	$result->agreed->payment->method         = $quoteRec->paymentMethodId;
    	
    	$result->agreed->products = $items;
    	
    	return $result;
    }
    
    
    /**
     * Подготовка на продуктите от формата с вече уточнените
     * к-ва във подходящ вид
     * @param array $products - продуктите върнати от формата
     * @param double $amount - сума на заявката
     */
    private function prepareProducts($products, &$amount)
    {
    	$amount = 0;
    	$items = array();
    	$products = (array)$products;
    	foreach ($products as $index => $quantity){
    		list($productId, $policyId) = explode("|", $index);
    		
    		$obj = array_values(
    			array_filter(static::$cache, function ($val) use ($productId, $policyId, $quantity) {
           				if($val->productId == $productId && $val->policyId == $policyId && $val->quantity == $quantity){
            				return $val;
            			}}));
            
            $items[] = array(
    			'classId'     => cls::get($policyId)->getProductMan()->getClassId(),
            	'productId'   => $obj[0]->productId,
            	'packagingId' => NULL,
            	'discount'    => $obj[0]->discount,
            	'isOptional'  => FALSE,
            	'quantity'    => $obj[0]->quantity,
            	'price'       => $obj[0]->price,
    		);
    		
    		$amount += $quantity * ($obj[0]->price * (1 + $obj[0]->discount));
    	}
    	
    	return $items;
    }
    
    
    /**
     * Връща форма за уточняване на к-та на продуктите, За всеки
     * продукт се показва поле с опции посочените к-ва от офертата
     * Трябва на всеки един продукт да съответства точно едно к-во
     * @param int $quotationId
     */
    private function getFilterForm($quotationId)
    {
    	$form = cls::get('core_Form');
    	$filteredProducts = $this->filterProducts($quotationId);
    	foreach ($filteredProducts as $index => $product){
    		if(count($product->options) > 1) {
    			$product->options = array('' => '') + $product->options;
    			$mandatory = 'mandatory';
    		} else {
    			$mandatory = '';
    		}
    		$form->FNC($index, "double(decimals=2)", "width=7em,input,caption={$product->title},{$mandatory}");
    		$form->setOptions($index, $product->options);
    	}
    	
    	$form->title = tr("Посочете желаните количества");
    	$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
    	$form->toolbar->addBtn('Отказ', array('sales_Quotations', 'single', $quotationId), 'ef_icon = img/16/close16.png');
    	
    	return $form;
    }
    
    
    /**
     * Групира продуктите от офертата с техните к-ва
     * @param int $quoteId - ид на оферта
     */
    private function filterProducts($quoteId)
    {
    	$products = array();
    	$query = sales_QuotationsDetails::getQuery();
    	$query->where("#quotationId = {$quoteId} AND #optional = 'no'");
    	static::$cache = $query->fetchAll();
    	while ($rec = $query->fetch()){
    		$index = "{$rec->productId}|{$rec->policyId}";
    		if(!array_key_exists($index, $products)){
    			$title = cls::get($rec->policyId)->getProductMan()->getTitleById($rec->productId);
    			$products[$index] = (object)array('title' => $title, 'options' => array());
    		}
    		$products[$index]->options[$rec->quantity] = $rec->quantity;
    	}
    	
    	return $products;
    }
    
    
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     * 
     * @param int|object $id
     * @return bgerp_iface_DealResponse
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function getDealInfo($id)
    {
    	// данните на заявката са във вид 
    	// bgerp_iface_DealResponse и директно се връщат
    	return $this->fetchField($id, 'data');
    }
    
    
	/**
     * След проверка на ролите
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
    {
    	if(($action == 'add' || $action == 'edit') && isset($rec)){
    		if(!$rec->originId){
    			$res = 'no_one';
    		}
    	}
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     * 
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
	public static function canAddToThread($threadId)
    {
    	return FALSE;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = "Заявка за продажба №" .$this->abbr . $rec->id;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;

        return $row;
    }
}