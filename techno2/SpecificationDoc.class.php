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
    public $loadList = 'plg_RowTools, techno2_Wrapper, doc_DocumentPlg, doc_plg_BusinessDoc, doc_ActivatePlg, plg_Search, plg_Printing, plg_Clone';
                      
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Спецификация на артикул 2';
    

    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, price_PolicyIntf, acc_RegisterIntf, cat_ProductAccRegIntf, doc_AddToFolderIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Спецификации на артикули";

    
    /**
     * Права за писане
     */
    public $canWrite = 'ceo, techno';
    
    
    /**
     * Права за запис
     */
    public $canAdd = 'ceo, techno';
    
    
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
    public $newBtnGroup = "9.9|Производство";


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
    public $listFields = 'id, title, folderId, innerClass,isPublic=Достъп';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Дефолт мета данни за всички продукти
     */
    public static $defaultMetaData = 'canSell,canBuy,canConvert,canManifacture';
    
    
    /**
     * Детайли на този мастър обект
     *
     * @var string|array
     */
    public $details = 'AccReports=acc_ReportDetails';

    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '301,302,304,305,306,309,321';
    
    
    /**
     * Да се показват ли в репортите нулевите редове
     */
    public $balanceRefShowZeroRows = TRUE;
    
    
    /**
     * По кой итнерфейс ще се групират сметките
     */
    public $balanceRefGroupBy = 'cat_ProductAccRegIntf';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canReports = 'ceo,sales,purchase,store,acc,cat';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD("title", 'varchar', 'caption=Име,mandatory');
    	$this->FLD('meta', 'set(canSell=Продаваеми,
                                canBuy=Купуваеми,
                                canStore=Складируеми,
                                canConvert=Вложими,
                                fixedAsset=Дълготрайни активи,
        						canManifacture=Производими,
        						waste=Отпаден)', 'caption=Свойства->Списък,columns=2,formOrder=100000000,input=none');
    	$this->FLD("isPublic", 'enum(no=Частен,yes=Публичен)', 'input=none,formOrder=100000002,caption=Показване за избор в документи->Достъп');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$rec = &$data->form->rec;
    	
    	// Само при редакция, потребителя може да промени дали специфиакцията е публична или не
    	if(isset($rec->id)){
    		$data->form->setField('isPublic', 'input');
    	}
    	
    	if(isset($rec->innerClass)){
    		$data->form->setField('meta', 'input');
    	}
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	
    	if(isset($rec->innerClass)){
    	
    		// При нова спецификация на артикул
    		if(empty($rec->id)){
    			$Cover = doc_Folders::getCover($rec->folderId);
    			
    			// Ако корицата е 'Спецификация
    			if($Cover->getInstance() instanceof techno2_SpecificationFolders){
    				
    				// Намираме кои са дефолтните мета данни, това са тези от корицата и идващите от драйвера
    				$defMetas = $Cover->getDefaultMeta();
    			} else {
    				$defMetas = self::$defaultMetaData;
    			}
    			
    			$Driver = $mvc->getDriver($rec);
    			$meta = $Driver->getDefaultMetas($defMetas);
    			
    			//@TODO да се направи проверка дали ако е услуга да не е складируем
    			$form->setDefault('meta', cls::get('type_Set')->fromVerbal($meta));
    		}
    	}
    }
    
    
    /**
     * След рендиране на единичния изглед
     */
    public function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	$tpl->push(('techno2/tpl/css/SpecificationStyles.css'), 'CSS');
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    public static function on_BeforeSave($mvc, &$id, $rec, $fields = NULL, $mode = NUL)
    {
    	if(isset($rec->folderId)){
    		$cover = doc_Folders::getCover($rec->folderId);
    		
    		if(empty($rec->id)){
    			$rec->isPublic = ($cover->haveInterface('crm_ContragentAccRegIntf')) ? 'no' : 'yes';
    		}
    	}
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf', 'cat_ProductFolderCoverIntf');
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
    	
    	return cls::haveInterface('doc_ContragentDataIntf', $coverClass) || cls::haveInterface('cat_ProductFolderCoverIntf', $coverClass);
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
    	$row->header = $mvc->singleTitle . " №<b>{$row->id}</b> ({$row->state})";
    	
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
    	$row->title = $this->getVerbal($rec, 'title');
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->state = $rec->state;
    	$row->recTitle = $rec->title;
    
    	return $row;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	return $rec->title;
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
    			$data->toolbar->addBtn('Промяна', array($mvc, 'edit', $data->rec->id, 'ret_url' => TRUE), 'id=btnEdit,order=1', 'ef_icon = img/16/to_do_list.png,title=Редакция на активирана спецификация');
    		}
    	}
    	
    	if($data->rec->state != 'rejected'){
    		$tId = $mvc->fetchField($data->rec->id, 'threadId');
    		
    		if(sales_Quotations::haveRightFor('add', (object)array('threadId' => $tId))){
    			if($qRec = sales_Quotations::fetch("#originId = {$data->rec->containerId} AND #state = 'draft'")){
    				$data->toolbar->addBtn("Оферта", array('sales_Quotations', 'edit', $qRec->id, 'ret_url' => TRUE), 'ef_icon = img/16/document_quote.png,title=Редактиране на оферта');
    			} else {
    				$data->toolbar->addBtn("Оферта", array('sales_Quotations', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE), 'ef_icon = img/16/document_quote.png,title=Нова оферта за спецификацията');
    			}
    		}
    		
    		if(techno2_SpecTplCache::haveRightFor('read')){
    			$data->toolbar->addBtn("История", array('techno2_SpecTplCache', 'list', 'docId' => $data->rec->id), 'ef_icon = img/16/view.png,title=Минали изгледи на спецификации');
    		}
    	}
    	
    	if($data->rec->state == 'active'){
    		if(cat_Boms::haveRightFor('write', (object)array('originId' => $data->rec->containerId))){
    			if($qRec = cat_Boms::fetch("#originId = {$data->rec->containerId} AND #state = 'draft'")){
    				$data->toolbar->addBtn("Рецепта", array('cat_Boms', 'edit', $qRec->id, 'ret_url' => TRUE), 'ef_icon = img/16/legend.png,title=Редактиране на технологична рецепта');
    			} else {
    				$data->toolbar->addBtn("Рецепта", array('cat_Boms', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE), 'ef_icon = img/16/legend.png,title=Създаване на нова технологична рецепта');
    			}
    		}
    		
    		if(planning_Jobs::haveRightFor('write', (object)array('originId' => $data->rec->containerId))){
    			if($qRec = planning_Jobs::fetch("#originId = {$data->rec->containerId} AND #state = 'draft'")){
    				$data->toolbar->addBtn("Задание", array('planning_Jobs', 'edit', $qRec->id, 'ret_url' => TRUE), 'ef_icon = img/16/clipboard_text.png,title=Редактиране на задание за производство');
    			} else {
    				$data->toolbar->addBtn("Задание", array('planning_Jobs', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE), 'ef_icon = img/16/clipboard_text.png,title=Създаване на ново задание за производство');
    			}
    		}
    	}
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
    	
    	$data->query->orderBy('id', 'DESC');
    }
    
    
    /*
     * Имплементиране на cat_ProductAccRegIntf(@see cat_ProductAccRegIntf)
     */
    
    
    /**
     * Връща продуктите, които могат да се продават на посочения клиент
     * Това са всички спецификации от неговата папка, както и
     * всички общи спецификации (създадени в папка "Проект")
     */
    function getProducts($customerClass, $customerId, $date = NULL, $properties = NULL, $limit = NULL)
    {
    	$Class = cls::get($customerClass);
    	$folderId = $Class->forceCoverAndFolder($customerId, FALSE);
    	$properties = arr::make($properties);
    	
    	$count = 0;
    	$products = array();
    	$query = $this->getQuery();
    	$query->where("#folderId = {$folderId}");
    	$query->orWhere("#isPublic = 'yes'");
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
     * Връща стойноста на даден параметър на продукта, ако я има
     * 
     * @param int $id - ид на продукт
     * @param string $sysId - sysId на параметър
     */
    public function getParam($id, $sysId)
    {
    	$Driver = $this->getDriver($id);
    	
    	return $Driver->getParamValue($sysId);
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
    	$properties = arr::make($properties, TRUE);
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
    		if(isset($limit)){
    			if($count === $limit) break;
    		}
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
    			'num' => $objectId . " sp",
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
    	$rec = $this->fetchRec($productId);
    	$price = (object)array('price' => NULL);
    	
    	// Ако има к-во в активно задание за спецификацията, да се вземе
    	
    	$quantityJob = $this->getLastActiveJob($rec)->quantity;
    	if(isset($quantityJob)){
    		$quantity = $quantityJob;
    	}
    	
    	// Опитваме се да намерим цена според технологичната карта
    	if($amounts = cat_Boms::getTotalByOrigin($rec->containerId)){
    		
    		// Какви са максималната и минималната надценка за контрагента
    		$minCharge = cond_Parameters::getParameter($customerClass, $customerId, 'minSurplusCharge');
    		$maxCharge = cond_Parameters::getParameter($customerClass, $customerId, 'maxSurplusCharge');
    		
    		// Връщаме цената спрямо минималната и максималната отстъпка, началното и пропорционалното количество
    		$price->price = ($amounts->base * (1 + $maxCharge) + $quantity * $amounts->prop * (1 + $minCharge)) / $quantity;
    		
    		// Обръщаме цената в посочената валута
    		$vat = $this->getVat($id);
    		$price->price = deals_Helper::getDisplayPrice($price->price, $vat, $rate, $chargeVat, 2);
    		
    		return $price;
    	}
    	
    	// Ако продукта няма цена, връщаме цената от последно продадената спецификация на този клиент (ако има)
    	$LastPricePolicy = cls::get('sales_SalesLastPricePolicy');
    	$lastPrice = $LastPricePolicy->getPriceInfo($customerClass, $customerId, $productId, $productManId, $packagingId, $quantity, $datetime, $rate, $chargeVatd);
    	
    	// Връщаме последната цена
    	return $lastPrice;
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
    	//Ако има кеширан изглед за тази дата връщаме го
    	if(!$cache = techno2_SpecTplCache::getTpl($id, $time)){
    		
    		// Ако няма генерираме наново и го кешираме
    		$cacheRec = new stdClass();
    		$cacheRec->time = $time;
    		$cacheRec->specId = $id;
    		
    		$Driver = cls::get(get_called_class())->getDriver($id);
    		$cacheRec->cache = $Driver->getProductDescription();
    		
    		techno2_SpecTplCache::save($cacheRec);
    		
    		$cache = $cacheRec->cache;
    	}
    	
    	// Връщаме намерения изглед
    	return $cache;
    }
    
    
    /**
     * Връща описанието на артикула
     *
     * @param mixed $id - ид/запис
     * @param core_Mvc $documentMvc - модела
     * @return mixed - описанието на артикула
     */
    public function getProductDesc($id, $documentMvc, $time = NULL)
    {
    	// Ако документа където ще се показва е договор, тогава показваме подробното описание
    	if($documentMvc instanceof deals_DealMaster || $documentMvc instanceof planning_Jobs || $documentMvc instanceof sales_Quotations){
    		$tpl = self::cacheTpl($id, $time);
    		 
    		return $tpl;
    	}
    	
    	return $this->getProductTitle($id);
    	
    }
    
    
    /**
     * Връща масив от използваните документи в даден документ (като цитат или
     * са включени в детайлите му)
     * 
     * @param int $data - сериализираната дата от документа
     * @return param $res - масив с използваните документи
     * 					[class] - инстанция на документа
     * 					[id] - ид на документа
     */
    function getUsedDocs_($productId)
    {
    	$res = array();
    	 
    	if(!$Driver = $this->getDriver($productId)) return;
    	
    	if($usedDocs = $Driver->getUsedDocs()) {
    		if(count($usedDocs)){
    			foreach ($usedDocs as $doc){
    				$res[] = (object)array('class' => $doc['mvc'], 'id' => $doc['rec']->id);
    			}
    		}
    	}
    	 
    	return $res;
    }
    
    
    /**
     * Връща последното активно задание за спецификацията
     * 
     * @param mixed $id - ид или запис
     * @return mixed $res - записа на заданието или FALSE ако няма
     */
    public static function getLastActiveJob($id)
    {
    	$rec = self::fetchRec($id);
    	
    	// Какво е к-то от последното активно задание
    	return planning_Jobs::fetch("#originId = {$rec->containerId} AND #state = 'active'");
    }
    
    
    /**
     * Връща последната активна рецепта на спецификацията
     *
     * @param mixed $id - ид или запис
     * @return mixed $res - записа на рецептата или FALSE ако няма
     */
    public static function getLastActiveBom($id)
    {
    	$rec = self::fetchRec($id);
    	 
    	// Какво е к-то от последното активно задание
    	return cat_Boms::fetch("#originId = {$rec->containerId} AND #state = 'active'");
    }

    
    /**
     * Рендира изглед за задание
     * 
     * @param mixed $id
     * @param string $time
     * @return mixed
     */
    public function renderJobView($id, $time = NULL)
    {
    	//@TODO дали е удачно да се кешира изгледа
    	$Jobs = cls::get('planning_Jobs');
    	return $this->getProductDesc($id, $Jobs, $time);
    }
    
    
    /**
     * Изпълнява се след клониране на запис
     */
    public static function on_AfterSaveCloneRec($mvc, $rec, $nRec)
    {
    	// Клонираме и параметрите
    	$pQuery = cat_products_Params::getQuery();
    	$pQuery->where("#classId = {$mvc->getClassId()} AND #productId = {$rec->id}");
    	
    	while($dRec = $pQuery->fetch()){
    		$dRec->productId = $nRec->id;
    		unset($dRec->id);
    		cat_products_Params::save($dRec);
    	}
    }
    
    
    /**
     * Екшън за създаване на нов артикул от спецификацията
     */
    public function act_CreateProduct()
    {
    	cat_Products::requireRightFor('add');
    	expect($id = Request::get('id', 'int'));
    	expect(!cat_Products::fetch("#specificationId = {$id}"));
    	expect($rec = $this->fetch($id));
    	
    	$form = cls::get('core_Form');
    	$form->title = 'Създаване на артикул от спецификация';
    	$form->FLD('code', 'varchar(64)', 'caption=Код, mandatory,width=15em');
    	 
    	$form->input();
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
    		if (preg_match('/[^0-9a-zа-я\- _]/iu', $rec->code)) {
    			$form->setError('code', 'Полето може да съдържа само букви, цифри, тирета, интервали и долна черта!');
    		}
    		
    		if(cat_Products::getByCode($rec->code)){
    			$form->setError('code', 'Има артикул с такъв код');
    		}
    		
    		if(!$form->gotErrors()){
    			$pId = self::createProduct($id, $rec->code);
    			
    			return Redirect(array('cat_Products', 'single', $pId), 'Успешно е създаден нов артикул');
    		}
    	}
    	
    	$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close16.png, title=Прекратяване на действията');
    	
    	// Рендиране на обвивката и формата
    	return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Създава артикул от спецификацията
     * 
     * @param mixed $id - ид или запис
     * @param string $code - код на продукта
     * @return number
     */
    public static function createProduct($id, $code = NULL)
    {
    	$Products = cls::get('cat_Products');
    	expect($rec = self::fetchRec($id));
    	
    	$cover = doc_Folders::getCover($rec->folderId);
    	if($cover->haveInterface('doc_ContragentDataIntf')){
    		$folderId = $rec->folderId;
    	} else {
    		$folderId = cat_Categories::forceCoverAndFolder(cat_Categories::fetchField("#sysId = 'products'", 'id'));
    		$code = "SPEC{$rec->id}";
    	}
    	
    	$pRec = (object)array('name'       => $rec->title, 
    						  'code'	   => $code,
    						  'innerClass' => $rec->innerClass, 
    						  'innerForm'  => $rec->innerForm, 
    						  'innerState' => $rec->innerState, 
    						  'folderId'   => $folderId,
    						  'state' 	   => 'active',
    						  'meta'	   => $rec->meta,
    	);
    	$Products->route($pRec);
    	
    	return cat_Products::save($pRec);
    }
    
    
    
    /**
     * Създава нова спецификация
     * 
     * @param string $title  - име на спецификацията
     * @param int $innerClass - драйвера на спецификацията
     * @param stdClass $innerForm - вътрешната форма
     * @param stdClass $innerState - вътрешното състояние
     * @param int $folderId - ид на папка
     * @param int $threadId - ид на нишка
     * @param enum(draft,active,rejected) $state - в какво състояние да се създаде
     * @return stdClass $obj - записа на спецификацията
     */
    public static function createNew($title, $innerClass, $innerForm, $innerState, $folderId = NULL, $threadId = NULL, $state = 'active')
    {
    	expect(cls::haveInterface('cat_ProductDriverIntf', $innerClass));
    	
    	// Опитваме се да определим папката ако не е зададена
    	if(!isset($folderId)){
    		if(!isset($threadId)){
    			$folderId = static::getDefaultFolder();
    		} else {
    			$folderId = doc_Threads::fetchField($threadId, 'folderId');
    		}
    	}
    	
    	expect(static::canAddToFolder($folderId));
    	
    	$obj = (object)array('title'      => $title,
    						 'innerClass' => $innerClass,
    						 'innerForm'  => $innerForm,
    						 'innerState' => $innerState,
    						 'folderId'   => $folderId,
    						 'state'      => $state,
    						 'threadId'   => $threadId,
    	);
    	
    	static::save($obj);
    	
    	return $obj;
    }
    
    
    /**
     * Да се показвали бърз бутон за създаване на документа в папка
     */
    public function mustShowButton($folderRec, $userId = NULL)
    {
    	$Cover = doc_Folders::getCover($folderRec->id);
    	
    	// Ако папката е на контрагент
    	if($Cover->haveInterface('cat_ProductFolderCoverIntf')){
    		
    		return TRUE;
    	}
    	
    	return FALSE;
    }
}