<?php



/**
 * Документ за нестандартен артикул
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno2_SpecificationDoc extends core_Embedder
{
    
    
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'techno_SpecificationDoc';
	
	
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_RowTools, techno2_Wrapper, doc_DocumentPlg, doc_plg_BusinessDoc, doc_ActivatePlg, plg_Search, plg_Printing, doc_SharablePlg';
                      
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Нестандартен артикул';
    

    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, price_PolicyIntf, acc_RegisterIntf, cat_ProductAccRegIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Нестандартни артикули";

    
    /**
     * Права за писане
     */
    public $canWrite = 'ceo, techno';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'ceo, techno';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, techno';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, techno';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Spc";


    /**
     * Групиране на документите
     */
    public $newBtnGroup = "18.9|Производство";


    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'techno2/tpl/SingleLayoutSpecification.shtml';


    /**
     * Свойство, което указва интерфейса на вътрешните обекти
     */
    public $innerObjectInterface = 'cat_ProductDriverIntf';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/specification.png';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title, folderId, innerClass, meta';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, title, folderId, innerClass';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Дефолт мета данни за всички продукти
     */
    public static $defaultMetaData = 'canSell,canBuy,canConvert,canManifacture,canStore';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD("title", 'varchar', 'caption=Име,mandatory');
    	$this->FLD('meta', 'set(canSell=Продаваем,canBuy=Купуваем,
        						canStore=Складируем,canConvert=Вложим,
        						fixedAsset=Дма,canManifacture=Производим)', 'caption=Свойства->Списък,columns=2,formOrder=100000000,input=none');
    	$this->FLD('sharedUsers', 'userList', 'caption=Споделяне->Потребители');
    	$this->setDbUnique('title');
    }
    
    
    /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
    	if($form->rec->innerClass){
    		$form->setField('sharedUsers', 'input,formOrder=100000000');
    	} else {
    		$form->setField('sharedUsers', 'input=none,formOrder=100000000');
    	}
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    public static function on_BeforeSave($mvc, &$id, $rec, $fields = NULL, $mode = NUL)
    {
    	if(isset($rec->innerClass)){
    		
    		$Driver = $mvc->getDriver($rec);
    		 
    		$meta = $Driver->getDefaultMetas();
    		 
    		if(!count($meta)){
    			$meta = arr::make(self::$defaultMetaData, TRUE);
    		}
    		 
    		$Set = cls::get('type_Set');
    		$rec->meta = $Set->fromVerbal($meta);
    	}
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf');
    }

    
    /**
     * Може ли документа да се добави в посочената папка?
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
    	$coverClass = doc_Folders::fetchCoverClassName($folderId);
    
    	return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $res
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'activate' && empty($rec)){
    		$res = 'no_one';
    	}
    	
    	if($action == 'edit' && isset($rec)){
    		if($rec->state == 'active'){
    			$res = $mvc->getRequiredRoles('edit');
    		}
    	}
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->header = $mvc->singleTitle . " №<b>{$row->id}</b> ({$row->state})" ;
    	
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	
    	$row = new stdClass();
    	$row->title = $this->singleTitle . "№{$rec->id}";
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->state = $rec->state;
    	$row->recTitle = $rec->title;
    
    	return $row;
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	// Ако спецификацията е активирана подменяме бутона за редакция
    	if($data->toolbar->hasBtn('btnEdit') && $data->rec->state == 'active'){
    		
    		$data->toolbar->removeBtn('btnEdit');
    		
    		// Добавяме бутона за промяна
    		if($mvc->haveRightFor('edit', $data->rec)){
    			$data->toolbar->addBtn('Промяна', array($mvc, 'edit', $data->rec->id), 'id=btnEdit,order=1', 'ef_icon = img/16/to_do_list.png,title=Редакция на активирана спецификация');
    		}
    	}
    }
    
    
    /**
     * След рендиране на данните върнати от драйвера
     *
     * @param core_ET $tpl
     * @param core_ET $embededDataTpl
     */
    public static function on_AfterrenderEmbeddedData($mvc, &$res, core_ET &$tpl, core_ET $embededDataTpl, &$data)
    {
    	//$InnerClass = $mvc->getDriver($data->rec);
    	//$InnerClass->renderParams($data->params, $tpl, FALSE);
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    	$data->listFilter->FNC('driver', 'class(interface=cat_ProductDriverIntf, allowEmpty, select=title)', 'placeholder=Драйвър');
    	$data->listFilter->showFields = 'search,driver';
    	$data->listFilter->input();
    	
    	if($rec = $data->listFilter->rec){
    		
    		// Филтриране по драйвър ако е избран
    		if(isset($rec->driver)){
    			$data->query->where("#innerClass = {$rec->driver}");
    		}
    	}
    }
    
    
    /*
     * Имплементиране на cat_ProductAccRegIntf(@see cat_ProductAccRegIntf)
     */
    
    
    /**
     * Връща продуктите, които могат да се продават на посочения клиент
     * Това са всички спецификации от неговата папка, както и
     * всички общи спецификации (създадени в папка "Проект")
     */
    function getProducts($customerClass, $customerId, $date = NULL, $properties, $limit = NULL)
    {
    	$Class = cls::get($customerClass);
    	$folderId = $Class->forceCoverAndFolder($customerId, FALSE);
    	$properties = arr::make($properties);
    	
    	$count = 0;
    	$products = array();
    	$query = $this->getQuery();
    	$query->where("#folderId = {$folderId}");
    	$query->where("#state = 'active'");
    	
    	while($rec = $query->fetch()){
    		$flag = FALSE;
    		$meta = type_Set::toArray($rec->meta);
    		
	    	foreach ($properties as $prop){
	    		if(empty($meta[$prop])) $flag = TRUE;
	    	}
    			
	    	if(!$flag){
	    		$products[$rec->id] = $rec->title;
	    		$count++;
	    		if(isset($limit) && $count >= $limit) break;
	    	}
    	}
    	
    	return $products;
    }
    
    
    /**
     * Метод връщаш информация за продукта и неговите опаковки
     * 
     * @param int $id - ид на продукта
     * @param int $packagingId - ид на опаковката, по дефолт NULL
     * @return stdClass $res - обект с информация за продукта
     * и опаковките му ако $packagingId не е зададено, иначе връща
     * информацията за подадената опаковка
     */
    public static function getProductInfo($id, $packagingId = NULL)
    {
    	$self = cls::get(get_called_class());
    	$Driver = $self->getDriver($id);
    	$rec = static::fetch($id);
    	
    	$pInfo = $Driver->getProductInfo($packagingId);
    	
    	if($rec->meta){
    		$meta = explode(',', $rec->meta);
    		foreach($meta as $value){
    			$pInfo->meta[$value] = TRUE;
    		}
    	} else {
    		$pInfo->meta = FALSE;
    	}
    	
    	return $pInfo;
    }
    
    
    /**
     * Връща стойноства на даден параметър на продукта, ако я има
     * 
     * @param int $id - ид на продукт
     * @param string $sysId - sysId на параметър
     */
    public function getParam($id, $sysId)
    {
    	return;
    	expect($paramId = cat_Params::fetchIdBySysId($sysId));
    	 
    	$value = $this->Params->fetchField("#generalProductId = {$id} AND #paramId = '{$paramId}'", 'value');
    	 
    	if($value) return $value;
    	 
    	// Връщаме дефолт стойността за параметъра
    	return cat_Params::getDefault($paramId);
    }
    
    
    /**
     * Връща ДДС-то на продукта
     * 
     * @param int $id - ид на спецификацията
     * @param date $date - дата
     */
    public static function getVat($id, $date = NULL)
    {
    	$vat = cls::get(get_called_class())->getParam($id, 'vat');
    	
    	if($vat) return $vat;
    	 
    	$period = acc_Periods::fetchByDate($date);
    	
    	return $period->vatRate;
    }
    
    
    /**
     * Връща опаковките в които се предлага даден продукт
     */
    public function getPacks($productId)
    {
    	$Driver = $this->getDriver($productId);
    	
    	return $Driver->getPacks();
    }
    
    
	/**
     * Връща информация за основната опаковка на артикула
     * 
     * @param int $productId - ид на продукт
     * @return stdClass - обект с информация
     * 				->name     - име на опаковката
     * 				->quantity - к-во на продукта в опаковката
     */
    public function getBasePackInfo($id)
    {
    	return (object)array('name' => NULL, 'quantity' => 1, 'classId' => 'cat_UoM');
    }
    
    
    /**
     * Връща масив от продукти отговарящи на зададени мета данни:
     * canSell, canBuy, canManifacture, canConvert, fixedAsset, canStore
     * 
     * @param mixed $properties - комбинация на горе посочените мета
     * 							  данни или като масив или като стринг
     * @param int $limit       - Лимит на опциите
     * @return array $products - продукти отговарящи на условието, ако не са
     * 							 зададени мета данни връща всички продукти
     */
    public static function getByProperty($properties, $limit = NULL)
    {
    	$products = array();
    	$properties = arr::make($properties);
    	expect(count($properties));
    	 
    	$count = 0;
    	
    	// Всички активни спецификации
    	$query = static::getQuery();
    	$query->where("#state = 'active'");
    	while($rec = $query->fetch()){
    		$meta = type_Set::toArray($rec->meta);
    		if(count($meta)){
    			
    			// Оставяме само тези спецификации, отговарящи поне на едно условие
    			foreach ($properties as $p){
    				if(in_array($p, $meta)){
    					$products[$rec->id] = self::getTitleById($rec->id);
    					$count++;
    					break;
    				}
    			}
    		}
    		
    		// Ако сме достигнали лимита не продължаваме
    		if($count == $limit) break;
    	}
    	
    	// Връщаме намерените продукти
    	return $products;
    }
    
    
    /**
     * Дефолт метод за връщане на тегло
     */
    public function getWeight($id, $packagingId)
    {
    	return $this->getParam($id, 'transportWeight');
    }
    
    
    /**
     * Дефолт метод за връщане на обем
     */
    public function getVolume($id, $packagingId)
    {
    	$this->getParam($id, 'transportVolume');
    }
    
    
    /**
     * Връща цената по себестойност на продукта
     * 
     * @TODO себестойността да идва от заданието
     * @return double
     */
    public function getSelfValue($productId, $packagingId = NULL, $quantity = 1, $date = NULL)
    {
    	return NULL;
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
    	$self = cls::get(__CLASS__);
    	
    	$info = $this->getProductInfo($objectId);
    	
    	$itemRec = (object)array(
    			'num' => 'Sp' . $objectId,
    			'title' => $info->productRec->name,
    			'uomId' => $info->productRec->measureId,
    			'features' => array("{$self->title}" => $self->title,)
    	);
    	
    	return $itemRec;
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
    
    
   /*
    * Имплементиране на price_PolicyIntf(@see price_PolicyIntf)
    */
    

    /**
     * Връща клас имплементиращ `price_PolicyIntf`, основната ценова политика за този артикул
     */
    public function getPolicy()
    {
    	return $this;
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения клиент на посочената дата
     *
     * @return object $rec->price  - цена
     * 				  $rec->discount - отстъпка
     */
    public function getPriceInfo($customerClass, $customerId, $productId, $productManId, $packagingId = NULL, $quantity = NULL, $datetime = NULL, $rate = 1, $chargeVat = 'no')
    {
    	return (object)array('price' => NULL);
    }


    /**
     * Заглавие на артикула
     */
    public function getProductTitle($id)
    {
    	$rec = self::fetchRec($id);
    	 
    	return $rec->title;
    }
    
    
    /**
     * Дали артикула е стандартен
     * 
     * @param mixed $id - ид/запис 
     * @return boolean - дали е стандартен или не 
     */
    public function isProductStandart($id)
    {
    	return FALSE;
    }
    
    
    /**
     * Кеширане на изгледа на спецификацията
     * 
     * @param mixed $id - ид/запис
     * @param datetime $time - време
     * @return core_ET - кеширания шаблон
     */
    private static function cacheTpl($id, $time)
    {
    	//if(!$cache = techno2_SpecTplCache::getTpl($id, $time)){
    		
    		$cacheRec = new stdClass();
    		$cacheRec->time = $time;
    		$cacheRec->specId = $id;
    		
    		$Driver = cls::get(get_called_class())->getDriver($id);
    		$cacheRec->cache = $Driver->getProductDescription();
    		
    		//techno2_SpecTplCache::save($cacheRec);
    		
    		$cache = $cacheRec->cache;
    	//}
    	
    	return $cache;
    }
    
    
    /**
     * Връща описанието на артикула
     *
     * @param mixed $id - ид/запис
     * @return mixed - описанието на артикула
     */
    public function getProductDesc($id, $time = NULL)
    {
    	$tpl = self::cacheTpl($id, $time);
    	
    	return $tpl;
    }
}