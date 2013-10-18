<?php



/**
 * "Спецификация" - нестандартен продукт или услуга
 * изготвена според изискванията на даден клиент
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno_Specifications extends core_Manager {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'price_PolicyIntf, acc_RegisterIntf, cat_ProductAccRegIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Спецификации";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'techno_Wrapper, plg_Printing, plg_Search,plg_Rejected';

    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Спецификация";
    
    
    /**
     * Кой може да оттегля
     */
    var $canReject = 'no_one';
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/specification.png';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title, folderId, docClassId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,title,folderId,docClassId,common,createdOn,createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'ceo,techno';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Кой може да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,techno';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,techno';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
	
	
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = '40';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Заглавие, input=none');
		$this->FLD('docClassId', 'class(interface=techno_ProductsIntf,select=title)', 'caption=Тип,input=none,silent');
		$this->FLD('docId', 'int', 'caption=Документ,input=none');
		$this->FLD('folderId', 'key(mvc=doc_Folders)', 'caption=Папка,input=none');
		$this->FLD('common', 'enum(no=Частен,yes=Общ)', 'caption=Достъп,input=none,value=no,autoFilter');
    	$this->FLD('sharedUsers', 'userList', 'caption=Споделяне->Потребители,input=none');
    	$this->FLD('createdOn', 'datetime(format=smartTime)', 'caption=Създаване->На, notNull, input=none');
        $this->FLD('createdBy', 'key(mvc=core_Users)', 'caption=Създаване->От, notNull, input=none');
    	$this->FLD('state', 
            'enum(active=Активирано, rejected=Отказано)', 
            'caption=Статус, input=none'
        );
    	
    	$this->setDbUnique('title');
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	 $data->listFilter->view = 'horizontal';
    	 $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png'); 
    	 $data->listFilter->setOptions('common', array('' => '', 'no' => 'Общ', 'yes' => 'Частен'));
    	 $data->listFilter->setDefault('common', '');
    	 $data->listFilter->showFields = 'search,common';
    	 $data->listFilter->input();
    	 
    	 if($data->listFilter->rec->common){
    	 	$data->query->where("#common = '{$data->listFilter->rec->common}'");
    	 }
    }
    
    
	/**
     * Заглавие на политиката
     * 
     * @param mixed $customerClass
     * @param int $customerId
     * @return string
     */
    public function getPolicyTitle($customerClass, $customerId)
    {
        return $this->singleTitle;
    }
    
    
    /**
     * Връща продуктите, които могат да се продават на посочения клиент
     * Това са всички спецификации от неговата папка, както и
     * всички общи спецификации (създадени в папка "Проект")
     */
    function getProducts($customerClass, $customerId, $date = NULL)
    {
    	$Class = cls::get($customerClass);
    	$folderId = $Class->forceCoverAndFolder($customerId, FALSE);
    	
    	$products = array();
    	$query = $this->getQuery();
    	$query->where("#folderId = {$folderId}");
    	$query->orWhere("#common = 'yes'");
    	$query->where("#state = 'active'");
    	while($rec = $query->fetch()){
    		$products[$rec->id] = $this->recToVerbal($rec, 'title')->title;
    	}
    	
    	return $products;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-list']){
	    	$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
	    	$DocClass = cls::get($rec->docClassId);
	    	$docThreadId = $DocClass::fetchField($rec->docId, 'threadId');
	    	
	    	if(doc_Threads::haveRightFor('single', $docThreadId)){
	    		$icon = $DocClass->getIcon($rec->id);
		    	$attr['class'] = 'linkWithIcon';
	            $attr['style'] = 'background-image:url(' . sbf($icon) . ');';
	            $row->title = str::limitLen(strip_tags($row->title), 70);
	            $row->title = ht::createLink($row->title, array($DocClass, 'single', $rec->docId), NULL, $attr);  
	    	}
	    	
	    	$row->ROW_ATTR['class'] = "state-{$rec->state}";
    	}
    }
    
    
    /**
     * Връща ДДС-то на продукта
     * @param int $id - ид на спецификацията
     * @param date $date - дата
     */
    public static function getVat($id, $date = NULL)
    {
    	$rec = static::fetchRec($id);
    	$TechnoClass = cls::get($rec->docClassId);
    	return $TechnoClass->getVat($rec->docId);
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения
     * клиент на посочената дата
     * Цената се изчислява по формулата формулата:
     * ([начални такси] * (1 + [максимална надценка]) + [количество] * 
     *  [единична себестойност] *(1 + [минимална надценка])) / [количество]
     * 
     * @return object
     * $rec->price  - цена
     * $rec->discount - отстъпка
     */
    public function getPriceInfo($customerClass, $customerId, $id, $productManId, $packagingId = NULL, $quantity = NULL, $datetime = NULL)
    {
    	$rec = $this->fetch($id);
    	$TechnoClass = cls::get($rec->docClassId);
    	$priceInfo = $TechnoClass->getPriceInfo($rec->docId, $packagingId, $quantity, $datetime);
    	
    	if($priceInfo->price){
    		$price = new stdClass();
    		if($priceInfo->discount){
    			$price->discount = $priceInfo->discount;
    		}
    		
    		$minCharge = cond_Parameters::getParameter($customerClass, $customerId, 'minSurplusCharge');
    		$maxCharge = cond_Parameters::getParameter($customerClass, $customerId, 'maxSurplusCharge');
    		if(!$quantity){
    			$quantity = 1;
    		}
    		$calcPrice = ($priceInfo->tax * (1 + $maxCharge) 
    					+ $quantity * $priceInfo->price * (1 + $minCharge)) / $quantity;
    		
    		$price->price = currency_CurrencyRates::convertAmount($calcPrice, NULL, $data->currencyId, NULL);
    		
    		return $price;
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Предефинираме метода getTitleById да връща вербалното
     * представяне на продукта
     * @param int $id - id на спецификацията
     * @param boolean $full 
     * 	      		FALSE - връща само името на спецификацията
     * 		        TRUE - връща целия шаблон на спецификацията
     * @return core_ET - шаблон с представянето на спецификацията
     */
     public static function getTitleById($id, $escaped = TRUE, $full = FALSE)
     {
    	$rec = static::fetch($id);
	    $TechnoClass = cls::get($rec->docClassId);
	    
     	if(!$full) {
    		return $TechnoClass->getTitleById($rec->docId, $escaped);
    	}
    	
	    return $TechnoClass->getShortLayout($rec->docId);
     }
    
    
    /**
     * Метод връщаш информация за продукта и неговите опаковки
     * @param int $id - ид на продукта
     * @param int $packagingId - ид на опаковката, по дефолт NULL
     * @return stdClass $res - обект с информация за продукта
     * и опаковките му ако $packagingId не е зададено, иначе връща
     * информацията за подадената опаковка
     */
    public static function getProductInfo($id, $packagingId = NULL)
    {
    	$rec = static::fetch($id);
    	$TechnoClass = cls::get($rec->docClassId);
    	return $TechnoClass->getProductInfo($rec->docId, $packagingId);
    }
    
    
    /**
     * Връща опаковките в които се предлага даден продукт
     */
	public static function getPacks($productId)
    {
    	$rec = static::fetch($productId);
    	$TechnoClass = cls::get($rec->docClassId);
    	return $TechnoClass->getPacks($rec->docId);
    }
    
    
    /**
     * Форсира спецификация
     * @param core_Mvc $mvc - mvc на модела
     * @param stdClass $rec - запис от sales_Sales или purchase_Requests
     * @return int - ид на създадения или обновения запис
     */
    public static function forceRec(core_Mvc $mvc, $rec)
    {
    	$coverClass = doc_Folders::fetchCoverClassName($rec->folderId);
    	$classId = $mvc::getClassId();
    	$arr = array(
    		'id' => static::fetchField("#docClassId = {$classId} AND #docId = {$rec->id}", 'id'),
    		'title' => $rec->title,
    		'docClassId' => $classId,
    		'docId' => $rec->id,
    		'folderId' => $rec->folderId,
    		'state' => ($rec->state != 'rejected') ? 'active' : 'rejected',
    		'createdOn' => dt::now(),
    		'createdBy' => core_Users::getCurrent(),
    		'common' => !cls::haveInterface('doc_ContragentDataIntf', $coverClass) ? "yes" : "no",
    	);
    	
    	return static::save((object)$arr);
    }
    
    
	/**
     * Преди извличане на записите от БД
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	$data->query->orderBy('id', 'DESC');
    }
    
    
    /**
     * Ф-я извличаща спецификация по даден документ
     * @param int $docClassId - ид на класа на документа
     * @param int $docId - ид на документа
     * @return stdRec - записа на спецификацията ако го има
     */
    public static function fetchByDoc($docClassId, $docId)
    {
    	return static::fetch("#docClassId = {$docClassId} AND #docId = {$docId}");
    }
    
    
   /**
	* Преобразуване на запис на регистър към запис за перо в номенклатура (@see acc_Items)
	*
	* @param int $objectId ид на обект от регистъра, имплементиращ този интерфейс
	* @return stdClass запис за модела acc_Items:
	*
	* o num
	* o title
	* o uomId (ако има)
	* o features - списък от признаци за групиране
	*/
    function getItemRec($objectId)
    {
        $info = $this->getProductInfo($objectId);
        $itemRec = (object)array(
            'num' => 'SPC' . $objectId,
            'title' => $info->productRec->title,
            'uomId' => $info->productRec->measureId,
        );
        
        return $itemRec;
    }
    
    
   /**
	* Хипервръзка към този обект
	*
	* @param int $objectId ид на обект от регистъра, имплементиращ този интерфейс
	* @return mixed string или ET (@see ht::createLink())
	*/
    function getLinkToObj($objectId)
    {
        $rec = $this->fetchRec($objectId);
    	return ht::createLink($rec->title, array(cls::get($rec->docClassId), 'single', $rec->docId));
    }
    
    
   /**
	* Нотифицира регистъра, че обекта е станал (или престанал да бъде) перо
	*
	* @param int $objectId ид на обект от регистъра, имплементиращ този интерфейс
	* @param boolean $inUse true - обекта е перо; false - обекта не е перо
	*/
    function itemInUse($objectId, $inUse)
    {
        /* TODO */
    }
    
    
   /**
	* Имат ли обектите на регистъра размерност?
	*
	* @return boolean
	*/
    static function isDimensional()
    {
        return TRUE;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод за съвместимост със стари записи
     */
    function getDocumentRow($id)
    {
    }
    
    
    /**
     * Имплементиране на интерфейсен метод за съвместимост със стари записи
     */
    function getIcon($id)
    {
    }
    
    
    /**
     * Имплементиране на интерфейсен метод за съвместимост със стари записи
     */
    static function getHandle($id)
    {
    }
}