<?php



/**
 * Клас 'store_DocumentPackagingDetail'
 *
 * Детайли за амбалажи към складови документи
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_DocumentPackagingDetail extends store_InternalDocumentDetail
{
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Амбалажи към складови документи';
	
	
	/**
	 * Име на поле от модела, външен ключ към мастър записа
	 */
	public $masterKey = 'documentId';
	
	
	/**
	 * Дали в листовия изглед да се показва бутона за добавяне
	 */
	public $listAddBtn = FALSE;
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Амбалаж';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'plg_RowTools2, store_Wrapper, plg_SaveAndNew,plg_AlignDecimals2, LastPricePolicy=sales_SalesLastPricePolicy';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'productId=Амбалаж, packagingId, packQuantity,type,packPrice, amount';
	
	
	/**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой има право да редактира?
     *
     * @var string|array
     */
    public $canEdit = 'ceo,store,sales,purchase';
    
    
    /**
     * Кой има право да листва?
     *
     * @var string|array
     */
    public $canList = 'ceo,store,sales,purchase';
    
    
	/**
	 * Описание на модела (таблицата)
	 */
	public function description()
	{
		$this->FLD('documentClassId', 'class', 'column=none,notNull,silent,input=hidden,mandatory');
		$this->FLD('documentId', 'int', 'column=none,notNull,silent,input=hidden,mandatory');
		parent::setFields($this);
		$this->FLD('type', 'enum(in=Приемане,out=Предаване)', 'column=none,notNull,silent,mandatory,caption=Действие,after=productId,input=hidden');
		$this->setDbUnique('documentClassId,documentId,productId,packagingId,type');
	}
	
	
	/**
     * Подготвя заявката за данните на детайла
     */
    function prepareDetailQuery_($data)
    {
        // Създаваме заявката
        $data->query = $this->getQuery();
        $data->query->where("#{$data->masterKey} = {$data->masterId} AND #documentClassId = {$data->masterMvc->getClassId()}");
        
        return $data;
    }
    
    
    /**
     * Взима наличните записи за модела
     * 
     * @param mixed $mvc
     * @param int $id
     */
    public static function getRecs($mvc, $id)
    {
    	$class = cls::get($mvc);
    	$query = self::getQuery();
    	$query->where("#documentId = '{$id}' AND #documentClassId = {$class->getClassId()}");
    	
    	return $query->fetchAll();
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add'){
    		if((empty($rec->documentClassId) || empty($rec->documentId))){
    			$requiredRoles = 'no_one';
    		} elseif(isset($rec->documentClassId) && isset($rec->documentId)){
    			$Document = new core_ObjectReference($rec->documentClassId, $rec->documentId);
    			$dRec = $Document->fetch('state,contragentClassId,contragentId');
    			$isCons = cond_Parameters::getParameter($dRec->contragentClassId, $dRec->contragentId, 'consignmentContragents');
    			
    			if(!$Document->isInstanceOf('store_DocumentMaster')){
    				$requiredRoles = 'no_one';
    			} elseif($isCons !== 'yes'){
    				$requiredRoles = 'no_one';
    			} elseif($dRec->state != 'draft'){
    				$requiredRoles = 'no_one';
    			} elseif(!self::getPackagingProducts(TRUE)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    	
    	// Да не може да се променя ако документа не е чернова
    	if(($action == 'edit' || $action == 'delete') && isset($rec->documentClassId) && isset($rec->documentId)){
    		$Document = new core_ObjectReference($rec->documentClassId, $rec->documentId);
    		if($Document->fetchField('state') != 'draft'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
	 * Рендиране на детайла
	 */
	public function renderDetail_($data)
	{
		if(!count($data->recs)) return new core_ET('');
		
		return parent::renderDetail_($data);
	}
	
	
	/**
	 * Връща съответния мастер
	 */
	function getMasterMvc_($rec)
	{
		return cls::get($rec->documentClassId);
	}
	
	
	/**
	 * Връща наличния Амбалаж за предаване
	 * 
	 * @param string $onlyCount - само бройка или не
	 * @return int|array
	 */
	private static function getPackagingProducts($onlyCount = FALSE)
	{
		$groupId = cat_Groups::fetchField("#sysId = 'packagings'", 'id');
		$where = "LOCATE('|{$groupId}|', #groups) AND #state = 'active' AND #canStore = 'yes'";
		if($onlyCount === TRUE) return cat_Products::count($where);
		
		$options = array();
		$pQuery = cat_Products::getQuery();
		$pQuery->where($where);
		$pQuery->show('id,name,isPublic,code');
		while($pRec = $pQuery->fetch()){
			$options[$pRec->id] = cat_Products::getRecTitle($pRec, FALSE);
		}
		
		return $options;
	}
	
	
	/**
	 * Достъпните продукти
	 */
	protected function getProducts($masterRec)
	{
		// Намираме всички продаваеми продукти, и оттях оставяме само складируемите за избор
		$products = self::getPackagingProducts();
	
		return $products;
	}
	
	
	/**
	 * Преди подготовка на заглавието на формата
	 */
	protected static function on_BeforePrepareEditTitle($mvc, &$res, $data)
	{
		$rec = &$data->form->rec;
		$data->singleTitle = ($rec->type == 'out') ? 'предаден амбалаж' : 'приет амбалаж';
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm(core_Mvc $mvc, &$data)
	{
		$form = &$data->form;
		
		if(isset($form->rec->id)){
			$form->setField('type', 'input');
		}
	}
	
	
	/**
	 * Метод по реализация на определянето на движението генерирано от реда
	 *
	 * @param core_Mvc $mvc
	 * @param string $res
	 * @param stdClass $rec
	 * @return void
	 */
	public function getBatchMovementDocument($rec)
	{
		return isset($rec->type) ? $rec->type : 'out';
	}
	
	
	/**
	 * Подготвя записите
	 *
	 * За предадените артикули:
	 * 		Dt: 323. СМЗ на отговорно пазене				    (Контрагенти, Артикули)
	 *      Ct: 321. Суровини, материали, продукция, стоки	    (Складове, Артикули)
	 *
	 * За върнатите артикули:
	 * 		Dt: 321. Суровини, материали, продукция, стоки		(Складове, Артикули)
	 *      Ct: 323. СМЗ на отговорно пазене					(Контрагенти, Артикули)
	 */
	public static function getEntries($mvc, $rec, $isReverse = FALSE)
	{
		$entries = array();
		$sign = 1;//($isReverse) ? -1 : 1;
		
		$recs = self::getRecs($mvc->getClassId(), $rec->id);
		
		$dQuery = store_DocumentPackagingDetail::getQuery();
		$dQuery->where("#documentClassId = {$mvc->getClassId()} AND #documentId = {$rec->id}");
		while($dRec = $dQuery->fetch()){
			$quantity = $dRec->quantityInPack * $dRec->packQuantity;
			$arr323 = array('323', array($rec->contragentClassId, $rec->contragentId),
								   array('cat_Products', $dRec->productId),
							       'quantity' => $sign * $quantity);
			
			$arr321 = array('321', array('store_Stores', $rec->storeId),
								   array('cat_Products', $dRec->productId),
							       'quantity' => $sign * $quantity);
			
			if($dRec->type == 'in'){
				$entry = array('debit' => $arr321, 'credit' => $arr323);
			} else {
				$entry = array('debit' => $arr323, 'credit' => $arr321);
			}
			
			$entries[] = $entry;
		}
	
		return $entries;
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$row->type = "<div class='centered'>{$row->type}</div>";
	}
}