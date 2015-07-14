<?php



/**
 * Регистър на артикулите в каталога
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class cat_Products extends core_Embedder {
    
    
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $innerObjectInterface = 'cat_ProductDriverIntf';
	
	
	/**
	 * Флаг, който указва, че документа е партньорски
	 */
	public $visibleForPartners = TRUE;
	
	
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'acc_RegisterIntf,cat_ProductAccRegIntf,doc_AddToFolderIntf,acc_RegistryDefaultCostIntf';
    
    
    /**
     * Заглавие
     */
    public $title = "Артикули в каталога";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_SaveAndNew, plg_Clone, doc_DocumentPlg, plg_PrevAndNext, acc_plg_Registry, plg_State,
                     cat_Wrapper, plg_Sorting, doc_ActivatePlg, doc_plg_Close, doc_plg_BusinessDoc, cond_plg_DefaultValues, plg_Printing, plg_Select, plg_Search, bgerp_plg_Import';
    
    
    /**
     * Име на полето за групите на продуктите.
     * Използва се за целите на bgerp_plg_Groups
     */
    public $groupField = 'groups';


    /**
     * Име на полето с групите, в които се намира продукт. Използва се от groups_Extendable
     * 
     * @var string
     */
    public $groupsField = 'groups';

    
    /**
     * Детайла, на модела
     */
    var $details = 'Packagings=cat_products_Packagings,Prices=cat_PriceDetails,AccReports=acc_ReportDetails,Resources=planning_ObjectResources,Jobs=planning_Jobs';
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '301,302,304,305,306,309,321,323,61101';
    
    
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
     * Кой  може да вижда счетоводните справки?
     */
    public $canAddacclimits = 'ceo,storeMaster,accMaster';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Артикул";
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/wooden-box.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,name,code,groups,folderId,createdOn,createdBy';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да го прочете?
     */
    public $canRead = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой може да променя?
     */
    public $canEdit = 'cat,ceo';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'cat,ceo,sales';
    
    
    /**
     * Кой може да добавя?
     */
    public $canClose = 'cat,ceo';
    
    
    /**
     * Можели да се редактират активирани документи
     */
    public $canEditActivated = TRUE;
    
    
    /**
     * Кой може да го разгледа?
     */
    public $canList = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой може да го отхвърли?
     */
    public $canReject = 'cat,ceo';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canWrite = 'cat,ceo';
    
    
    /**  
     * Кой има право да променя системните данни?  
     */  
    public $canEditsysdata = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой  може да групира "С избраните"?
     */
    public $canGrouping = 'no_one';

	
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'cat/tpl/products/SingleProduct.shtml';
    
    
    /**
     * Кой има достъп до единичния изглед
     */
    public $canSingle = 'cat,ceo,sales,purchase';
    
	
    /** 
	 *  Полета по които ще се търси
	 */
	public $searchFields = 'name, code';
	
	
	/**
	 * Кой има достъп до часния изглед на артикула
	 */
	public $canPrivatesingle = 'user';
	
	
	/**
	 * Шаблон (ET) за заглавие на продукт
	 * 
	 * @var string
	 */
	public $recTitleTpl = '[#name#]<!--ET_BEGIN code--> ([#code#])<!--ET_END code-->';
    
    
	/**
	 * Кои полета от мениджъра преди запис да се обновяват със стойностти от драйвера
	 */
	public $fieldsToBeManagedByDriver = 'info, measureId, photo';
	
	
	/**
	 * Групиране на документите
	 */
	public $newBtnGroup = "9.8|Производство";
	
	
	/**
	 * На кой ред в тулбара да се показва бутона всички
	 */
	public $allBtnToolbarRow = 1;
	
	
	/**
	 * В коя номенклатура да се добави при активиране
	 */
	public $addToListOnActivation = 'catProducts';
	
	
	/**
	 * Абревиатура
	 */
	public $abbr = 'Art';
	
	
	/**
	 * Стратегии за дефолт стойностти
	 */
	public static $defaultStrategies = array(
					'groups'  => 'lastDocUser|lastDoc',
					'meta'    => 'lastDocUser|lastDoc',
	);
	
	
	/**
	 * Групи за обновяване
	 */
	protected $updateGroupsCnt = FALSE;
	
	
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory,remember=info,width=100%');
		$this->FLD('code', 'varchar(64)', 'caption=Код,remember=info,width=15em');
        $this->FLD('info', 'richtext(bucket=Notes)', 'caption=Описание,input=none,formOrder=4');
        $this->FLD('measureId', 'key(mvc=cat_UoM, select=name,allowEmpty)', 'caption=Мярка,mandatory,remember,notSorting,input=none,formOrder=4');
        $this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Фото,input=none,formOrder=4');
        $this->FLD('groups', 'keylist(mvc=cat_Groups, select=name, makeLinks)', 'caption=Маркери,maxColumns=2,remember,formOrder=100');
        $this->FLD("isPublic", 'enum(no=Частен,yes=Публичен)', 'input=none,formOrder=100000002');
        
        // Разбивки на свойствата за по-бързо индексиране и търсене
        $this->FLD('canSell', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canBuy', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canStore', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canConvert', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('fixedAsset', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canManifacture', 'enum(yes=Да,no=Не)', 'input=none');
        
        $this->FLD('meta', 'set(canSell=Продаваем,
                                canBuy=Купуваем,
                                canStore=Складируем,
                                canConvert=Вложим,
                                fixedAsset=Дълготраен актив,
        			canManifacture=Производим)', 'caption=Свойства->Списък,columns=2,remember,formOrder=100000000,mandatory');
        
        $this->setDbIndex('canSell');
        $this->setDbIndex('canBuy');
        $this->setDbIndex('canStore');
        $this->setDbIndex('canConvert');
        $this->setDbIndex('fixedAsset');
        $this->setDbIndex('canManifacture');
        
        $this->setDbUnique('code');
    }
    
    
    /**
     * Изпълнява се след подготовка на Едит Формата
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	
    	// Слагаме полето за драйвър да е 'remember'
    	if($form->getField($mvc->innerClassField)){
    		$form->setField($mvc->innerClassField, 'remember');
    	}
    	
    	if(isset($form->rec->folderId)){
    		$cover = doc_Folders::getCover($form->rec->folderId);
    		
    		if(!$cover->haveInterface('doc_ContragentDataIntf')){
    			$form->setField('code', 'mandatory');
    			if($cover->getInstance() instanceof cat_Categories){
    				if($code = $cover->getDefaultProductCode()){
    					$form->setDefault('code', $code);
    				}
    			}
    			
    			// Запомняме последно добавения код
				if($code = Mode::get('cat_LastProductCode')) {
					if ($newCode = str::increment($code)) {
						
						// Проверяваме дали има такъв запис в системата
						if (!$mvc->fetch("#code = '$newCode'")) {
							$form->setDefault('code', $newCode);
						}
					}
				}
    		} 
    	}
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
		if(!isset($form->rec->innerClass)){
    		$form->setField('groups', 'input=hidden');
    		$form->setField('meta', 'input=hidden');
    	}
		
		//Проверяваме за недопустими символи
        if ($form->isSubmitted()){
        	$rec = &$form->rec;
        	
            if (preg_match('/[^0-9a-zа-я\- _]/iu', $rec->code)) {
                $form->setError('code', 'Полето може да съдържа само букви, цифри, тирета, интервали и долна черта!');
            }
           
        	if($rec->code) {
    				
    			// Проверяваме дали има продукт с такъв код (като изключим текущия)
	    		$check = $mvc->getByCode($rec->code);
	    		if($check && ($check->productId != $rec->id)
	    			|| ($check->productId == $rec->id && $check->packagingId != $rec->packagingId)) {
	    			$form->setError('code', 'Има вече артикул с такъв код!');
			    }
    		}
        }
    }
    
    
    /**
     * След подготовка на ембеднатата форма
     */
    public static function on_AfterPrepareEmbeddedForm(core_Mvc $mvc, &$form)
    {
		// Ако е избран драйвер слагаме задъжителните мета данни според корицата и драйвера
    	if(isset($form->rec->folderId)){
    		$cover = doc_Folders::getCover($form->rec->folderId);
    		$defMetas = ($cover->haveInterface('cat_ProductFolderCoverIntf')) ? $cover->getDefaultMeta() : array();
    		
    		$Driver = $mvc->getDriver($form->rec);
    		$defMetas = $Driver->getDefaultMetas($defMetas);
    		
    		$form->setDefault('meta', $form->getFieldType('meta')->fromVerbal($defMetas));
    	}
    	
    	if(isset($form->rec->originId)){
    		$document = doc_Containers::getDocument($form->rec->originId);
    		$fieldsFromSource = $document->getFieldsFromDriver();
    		$sourceRec = $document->rec();
    		
    		$form->setDefault('name', $sourceRec->title);
    		foreach ($fieldsFromSource as $fld){
    			$form->rec->$fld = $sourceRec->innerForm->$fld;
    		}
    	}
    }
    
    
    /**
     * Преди запис на продукт
     */
    public static function on_BeforeSave($mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
    	// Разпределяме свойствата в отделни полета за полесно търсене
    	if($rec->meta){
    		$metas = type_Set::toArray($rec->meta);
    		foreach (array('canSell', 'canBuy', 'canStore', 'canConvert', 'fixedAsset', 'canManifacture') as $fld){
    			$rec->$fld = (isset($metas[$fld])) ? 'yes' : 'no';
    		}
    	}
    	
    	// Ако кода е празен символ, правим го NULL
    	if(isset($rec->code)){
    		$rec->isPublic = ($rec->code != '') ? 'yes' : 'no';
    		if($rec->code == ''){
    			$rec->code = NULL;
    		}
    	}
    	
    	if($rec->state == 'draft'){
    		$rec->state = 'active';
    	}
    }

    
    /**
     * Рутира публичен артикул в папка на категория
     */
	private function routePublicProduct($categorySysId, &$rec)
	{
		$categorySysId = ($categorySysId) ? $categorySysId : 'goods';
		$categoryId = (is_numeric($categorySysId)) ? $categorySysId : cat_Categories::fetchField("#sysId = '{$categorySysId}'", 'id');
		
		// Ако няма такъв артикул създаваме документа
		if(!$exRec = $this->fetch("#code = '{$rec->code}'")){
			$rec->folderId = cat_Categories::forceCoverAndFolder($categoryId);
			$this->route($rec);
		}
		
		$defMetas = cls::get('cat_Categories')->getDefaultMeta($categoryId);
		$Driver = $this->getDriver($rec);
		
		$defMetas = $Driver->getDefaultMetas($defMetas);
		$rec->meta = ($rec->meta) ? $rec->meta : $this->getFieldType('meta')->fromVerbal($defMetas);
	}
    
	
    /**
     * Обработка, преди импортиране на запис при начално зареждане
     */
    public static function on_BeforeImportRec($mvc, $rec)
    {
    	if(empty($rec->innerClass)){
    		$rec->innerClass = cls::get('cat_GeneralProductDriver')->getClassId();
    	}
    	
    	$rec->name = isset($rec->csv_name) ? $rec->csv_name : $rec->name;
    	if($rec->csv_measureId){
    		$rec->measureId = cat_UoM::fetchBySinonim($rec->csv_measureId)->id;
    	}
    	
    	if($rec->csv_groups){
    		$rec->groups = cat_Groups::getKeylistBySysIds($rec->csv_groups);
    	}
    	$rec->innerForm = (object)array('name' => $rec->name, 'measureId' => $rec->measureId);
    	
    	$rec->state = ($rec->state) ? $rec->state : 'active';
    	
    	$mvc->routePublicProduct($rec->csv_category, $rec);
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->FNC('order', 'enum(alphabetic=Азбучно,last=Последно добавени,private=Частни)',
            'caption=Подредба,input,silent,remember');

        $data->listFilter->FNC('groupId', 'key(mvc=cat_Groups,select=name,allowEmpty)',
            'placeholder=Всички,caption=Група,input,silent,remember');
		
        $data->listFilter->FNC('meta1', 'enum(all=Свойства,
        						canSell=Продаваеми,
                                canBuy=Купуваеми,
                                canStore=Складируеми,
                                canConvert=Вложими,
                                fixedAsset=Дълготрайни активи,
        						canManifacture=Производими)', 'input');
		
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->showFields = 'search,order,meta1,groupId';
        $data->listFilter->input('order,groupId,search,meta1', 'silent');
        
        switch($data->listFilter->rec->order){
        	case 'last':
        		$data->query->orderBy('#createdOn=DESC');
        		break;
        	case 'private':
        		$data->query->where("#isPublic = 'no'");
        		break;
        	default :
        		$data->query->orderBy('#state,#name');
        		break;
        }
        
        if($data->listFilter->rec->order != 'private'){
        	$data->query->where("#isPublic = 'yes'");
        }
        
        if ($data->listFilter->rec->groupId) {
        	$data->query->like("groups", keylist::addKey('', $data->listFilter->rec->groupId));
        }
        
        if ($data->listFilter->rec->meta1 && $data->listFilter->rec->meta1 != 'all') {
        	$data->query->like("meta", $data->listFilter->rec->meta1);
        }
    }


    /**
     * Перо в номенклатурите, съответстващо на този продукт
     *
     * Част от интерфейса: acc_RegisterIntf
     */
    public static function getItemRec($objectId)
    {
        $result = NULL;
        $self = cls::get(__CLASS__);
        
        if ($rec = self::fetch($objectId)) {
        	$Driver = $self->getDriver($rec);

            if(!is_object($Driver)) return NULL;

        	$pInfo = $Driver->getProductInfo();
        	
        	$result = (object)array(
                'num'      => $rec->code . " a",
                'title'    => $pInfo->productRec->name,
                'uomId'    => $pInfo->productRec->measureId,
                'features' => array()
            );
            
        	// Добавяме свойствата от групите, ако има такива
        	$groupFeatures = cat_Groups::getFeaturesArray($rec->groups);
        	if(count($groupFeatures)){
        		$result->features += $groupFeatures;
        	}
           
        	// Добавяме и свойствата от драйвера, ако има такива
            $result->features = array_merge($Driver->getFeatures(), $result->features);
        }
        
        return $result;
    }
    
    
    /**
     * @see acc_RegisterIntf::itemInUse()
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
    }
    
    
    /**
     * Връща масив от продукти отговарящи на зададени мета данни:
     * canSell, canBuy, canManifacture, canConvert, fixedAsset, canStore
     * 
     * @param mixed $properties       - комбинация на горе посочените мета 
     * 							        данни, на които трябва да отговарят
     * @param mixed $hasnotProperties - комбинация на горе посочените мета 
     * 							        които не трябва да имат
     */
    public static function getByProperty($properties, $hasnotProperties = NULL)
    {
    	$me = cls::get(get_called_class());
    	
    	$products = $me->getProducts(NULL, NULL, NULL, $properties, $hasnotProperties);
    	
    	return $products;
    }
    
    
    /**
     * Метод връщаш информация за продукта и неговите опаковки
     * 
     * @param int $productId - ид на продукта
     * @param int $packagingId - ид на опаковката, по дефолт NULL
     * @return stdClass $res
     * 	-> productRec - записа на продукта
     * 	-> meta - мета данни за продукта ако има
	 * 	     meta['canSell'] 		- дали може да се продава
	 * 	     meta['canBuy']         - дали може да се купува
	 * 	     meta['canConvert']     - дали може да се влага
	 * 	     meta['canStore']       - дали може да се съхранява
	 * 	     meta['canManifacture'] - дали може да се прозивежда
	 * 	     meta['fixedAsset']     - дали е ДА
     * 	-> packagingRec - записа на опаковката, ако е зададена
     * 	-> packagings - всички опаковки на продукта, ако не е зададена
     */					
    public static function getProductInfo($productId, $packagingId = NULL)
    {
    	// Ако няма такъв продукт връщаме NULL
    	if(!$productRec = static::fetchRec($productId)) {
    		
    		return NULL;
    	}
    	
    	$self = cls::get(get_called_class());
    	$Driver = $self->getDriver($productId);
    	
    	if (!$Driver) return ;
    	
    	$res = $Driver->getProductInfo($packagingId);
    	
    	$res->productRec->code = $productRec->code;
    	$res->isPublic = ($productRec->isPublic == 'yes') ? TRUE : FALSE;
    	
    	if($grRec = cat_products_VatGroups::getCurrentGroup($productId)){
    		$res->productRec->vatGroup = $grRec->title;
    	}
    	
    	if($productRec->meta){
    		if($meta = explode(',', $productRec->meta)){
    			foreach($meta as $value){
    				$res->meta[$value] = TRUE;
    			}
    		}
    	} else {
    		$res->meta = FALSE;
    	}
    	
    	$Packagings = cls::get('cat_products_Packagings');
    	if(!$packagingId) {
    		$res->packagings = array();
    		
    	    // Ако не е зададена опаковка намираме всички опаковки
    		$packagings = $Packagings->fetchDetails($productId);
    		
    		// Пре-индексираме масива с опаковки - ключ става id на опаковката 
    		foreach ((array)$packagings as $pack) {
    		    $res->packagings[$pack->packagingId] = $pack;
    		}
    		
    		// Сортираме опаковките, така че основната опаковка да е винаги първа (ако има)
    		uasort($res->packagings, function($a, $b){
                    if($a->isBase == $b->isBase)  return 0;
					return $a->isBase == 'yes' ? -1 : 1;
                });
    		
    	} else {
    		
    		// Ако е зададена опаковка, извличаме само нейния запис
    		$res->packagingRec = $Packagings->fetchPackaging($productId, $packagingId);
    		if(!$res->packagingRec) {
    			
    			// Ако я няма зададената опаковка за този продукт
    			return NULL;
    		}
    	}
    	
    	// Връщаме информацията за продукта
    	return $res;
    }
    
    
    /**
     * Връща ид на продукта и неговата опаковка по зададен Код/Баркод
     * 
     * @param mixed $code - Код/Баркод на търсения продукт
     * @return mixed $res - Информация за намерения продукт
     * и неговата опаковка
     */
    public static function getByCode($code)
    {
    	$code = trim($code);
    	expect($code, 'Не е зададен код');
    	$res = new stdClass();
    	
    	// Проверяваме имали опаковка с този код: вътрешен или баркод
    	$Packagings = cls::get('cat_products_Packagings');
    	$catPack = $Packagings->fetchByCode($code);
    	if($catPack) {
    		
    		// Ако има запис намираме ид-та на продукта и опаковката
    		$res->productId = $catPack->productId;
    		$res->packagingId = $catPack->packagingId;
    	} else {
    		
    		// Проверяваме имали продукт с такъв код
    		$query = static::getQuery();
    		$query->where(array("#code = '[#1#]'", $code));
    		if($rec = $query->fetch()) {
    			
    			$res->productId = $rec->id;
    			$res->packagingId = NULL;
    		} else {
    			
    			// Ако няма продукт
    			return FALSE;
    		}
    	}
    	
    	return $res;
    }
    
    
    /**
     * Връща ДДС на даден продукт
     * 
     * @param int $productId - Ид на продукт
     * @param date $date - Дата към която начисляваме ДДС-то
     * @return double $vat - ДДС-то на продукта:
     * Ако има параметър ДДС за продукта го връщаме, впротивен случай
     * връщаме ДДС-то от периода
     * 		
     */
    public static function getVat($productId, $date = NULL)
    {
    	expect(static::fetch($productId), 'Няма такъв артикул');
    	
    	if(!$date){
    		$date = dt::now();
    	}
    	
    	if($groupRec = cat_products_VatGroups::getCurrentGroup($productId)){
    		
    		return $groupRec->vat;
    	}
    	
    	// Връщаме ДДС-то от периода
    	$period = acc_Periods::fetchByDate($date);
    	
    	return $period->vatRate;
    }
    
    
	/**
     * След всеки запис
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
        if($rec->groups) {
            $mvc->updateGroupsCnt = TRUE;
        }
        
        Mode::setPermanent('cat_LastProductCode' , $rec->code);
    }
    
    
    /**
     * При активиране да се добавили обекта като перо
     */
    public function canAddToListOnActivation($rec)
    {
    	$rec = $this->fetchRec($rec);
    	$isPublic = ($rec->isPublic) ? $rec->isPublic : $this->fetchField($rec->id, 'isPublic');
    	
    	return ($isPublic == 'yes') ? TRUE : FALSE;
    }
    
    
	/**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    public static function on_Shutdown($mvc)
    {
        if($mvc->updateGroupsCnt) {
            $mvc->updateGroupsCnt();
        }
    }
    
    
    /**
     * Ъпдейтване на броя продукти на всички групи
     */
    private function updateGroupsCnt()
    {
    	$groupsCnt = array();
    	$query = $this->getQuery();
        
        while($rec = $query->fetch()) {
            $keyArr = keylist::toArray($rec->groups);
            foreach($keyArr as $groupId) {
                $groupsCnt[$groupId]++;
            }
        }
        
        $groupQuery = cat_Groups::getQuery();
        while($grRec = $groupQuery->fetch()){
        	$grRec->productCnt = (int)$groupsCnt[$grRec->id];
        	cat_Groups::save($grRec);
        }
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
    	$file = "cat/csv/Products.csv";
    	$fields = array( 
	    	0 => "csv_name", 
	    	1 => "code", 
	    	2 => "csv_measureId", 
	    	3 => "csv_groups",
    		4 => "csv_category",
    		5 => "meta",
    	);
    	
    	core_Users::forceSystemUser();
    	$cntObj = csv_Lib::importOnce($this, $file, $fields);
    	core_Users::cancelSystemUser();
    	
    	$res .= $cntObj->html;
    	
    	return $res;
    }
    
    
    /**
     * Връща продуктите опции с продукти:
     * 	 Ако е зададен клиент се връщат всички публични + частните за него
     *   Ако не е зададен клиент се връщат всички активни продукти
     *
     * @return array() - масив с опции, подходящ за setOptions на форма
     */
    public function getProducts($customerClass, $customerId, $datetime = NULL, $hasProperties = NULL, $hasnotProperties = NULL, $limit = NULL)
    {
    	$query = $this->getQuery();
    	
    	// Само активни артикули
    	$query->where("#state = 'active'");
    	
    	// Ако е зададен контрагент, оставяме смао публичните + частните за него
    	if(isset($customerClass) && isset($customerId)){
    		$folderId = cls::get($customerClass)->forceCoverAndFolder($customerId);
    		 
    		// Избираме всички публични артикули, или частните за тази папка
    		$query->where("#isPublic = 'yes'");
    		$query->orWhere("#isPublic = 'no' AND #folderId = {$folderId}");
    		$query->show('isPublic,folderId,meta,id,code,name');
    	}
    	
    	// Ограничаваме заявката при нужда
    	if(isset($limit)){
    		$query->limit($limit);
    	}
    	
    	$private = $products = array();
    	$metaArr = arr::make($hasProperties);
    	$hasnotProperties = arr::make($hasnotProperties);
    	
    	$Varchar = cls::get('type_Varchar');
    	
    	// За всяко свойство търсим по полето за бързо търсене
    	if(count($metaArr)){
    		foreach ($metaArr as $meta){
    			$query->where("#{$meta} = 'yes'");
    		}
    	}
    	
    	if(count($hasnotProperties)){
    		foreach ($hasnotProperties as $meta1){
    			$query->where("#{$meta1} = 'no'");
    		}
    	}
    	
    	// Подготвяме опциите
    	while($rec = $query->fetch()){
    		$title = $this->getRecTitle($rec, FALSE);
    		
    		if($rec->isPublic == 'yes'){
    			$products[$rec->id] = $title;
    		} else {
    			$private[$rec->id] = $title;
    		}
    	}
    	
    	if(count($products)){
    		$products = array('pu' => (object)array('group' => TRUE, 'title' => tr('Стандартни'))) + $products;
    	}
    	
    	// Частните артикули излизат преди публичните
    	if(count($private)){
    		$private = array('pr' => (object)array('group' => TRUE, 'title' => tr('Нестандартни'))) + $private;
    		
    		$products = $private + $products;
    	}
    	
    	return $products;
    }
    
    
    /**
     * Връща цената по себестойност на продукта
     * 
     * @return double
     */
    public function getSelfValue($productId, $packagingId = NULL, $quantity = NULL, $date = NULL)
    {
    	// Опитваме се да намерим запис в в себестойностти за артикула
    	$listId = price_ListRules::PRICE_LIST_COST;
    	price_ListToCustomers::canonizeTime($date);
    	$price = price_ListRules::getPrice($listId, $productId, $packagingId, $date);
    	
    	// Ако няма се мъчим да намерим себестойността по рецепта, ако има такава
    	if(!$price){
    		if($amounts = cat_Boms::getPrice($this->fetchField($productId, 'containerId'))){
    			$price = ($amounts->base + $quantity * $amounts->prop) / $quantity;
    		}
    	}
    	
    	// Връщаме цената по себестойност
    	return $price;
    }
    
    
	/**
     * Връща масив със всички опаковки, в които може да участва един продукт
     */
    public function getPacks($productId)
    {
    	expect($rec = $this->fetch($productId));
    	
    	$pInfo = self::getProductInfo($productId);
    	
    	$packs = $pInfo->packagings;
    	if(count($packs)){
    		foreach ($packs as $packRec){
    			$options[$packRec->packagingId] = cat_Packagings::getTitleById($packRec->packagingId);
    		}
    	}
    	
    	return $options;
    }
    
    
    /**
     * Връща параметрите на артикула
     * @param mixed $id - ид или запис на артикул
     * 
     * @return array $res - параметрите на артикула
     * 					['weight']          -  Тегло
     * 					['volume']          -  Обем 
     * 					['thickness']       -  Дебелина
     * 					['length']          -  Дължина
     * 					['height']          -  Височина
     * 					['tolerance']       -  Толеранс
     * 					['transportWeight'] -  Транспортно тегло
     * 					['transportVolume'] -  Транспортен обем
     * 					['term']            -  Срок
     */
    public function getParams($id)
    {
    	$rec = $this->fetchRec($id);
    	$Driver = $this->getDriver($rec);
    	
    	return $Driver->getParams();
    }
    
    
    /**
     * Връща теглото на еденица от продукта, ако е в опаковка връща нейното тегло
     * 
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @return double - теглото на еденица от продукта
     */
    public function getWeight($productId, $packagingId = NULL)
    {
    	$weight = 0;
    	if($packagingId){
    		$pack = cat_products_Packagings::fetch("#productId = {$productId} AND #packagingId = {$packagingId}");
    		$weight = $pack->netWeight + $pack->tareWeight;
    	}
    	
    	if(!$weight){
    		$Driver = $this->getDriver($productId);
    		$params = $Driver->getParams();
    		$weight = $params['transportWeight'];
    	}
    	
    	return $weight;
    }
    
    
	/**
     * Връща обема на еденица от продукта, ако е в опаковка връща нейния обем
     * 
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @return double - теглото на еденица от продукта
     */
    public function getVolume($productId, $packagingId = NULL)
    {
    	$volume = 0;
    	if($packagingId){
    		$pack = cat_products_Packagings::fetch("#productId = {$productId} AND #packagingId = {$packagingId}");
    		$volume = $pack->sizeWidth * $pack->sizeHeight * $pack->sizeDepth;
    	}
    	
    	if(!$volume){
    		$Driver = $this->getDriver($productId);
    		$params = $Driver->getParams();
    		$volume = $params['transportVolume'];
    	}
    	
    	return $volume;
    }
    
    
    /**
     * След подготовка на записите в счетоводните справки
     */
    public static function on_AfterPrepareAccReportRecs($mvc, &$data)
    {
    	$recs = &$data->recs;
    	if(empty($recs) || !count($recs)) return;
    	
    	$packInfo = $mvc->getBasePackInfo($data->masterId);
    	$data->packName = $packInfo->name;
    	
    	foreach ($recs as &$dRec){
    		$dRec->blQuantity /= $packInfo->quantity;
    	}
    }
    
    
    /**
     * След подготовка на вербалнтие записи на счетоводните справки
     */
    public static function on_AfterPrepareAccReportRows($mvc, &$data)
    {
    	$rows = &$data->balanceRows;
    	$data->listFields = arr::make("tools=Пулт,ent1Id=Перо1,ent2Id=Перо2,ent3Id=Перо3,packId=Мярка,blQuantity=К-во,blAmount=Сума");
    	$data->reportTableMvc->FLD('packId', 'varchar', 'tdClass=small-field');
    	
    	foreach ($rows as &$arrs){
    		if(count($arrs['rows'])){
    			foreach ($arrs['rows'] as &$row){
    				$row['packId'] = $data->packName;
    			}
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    		
    	}
    	
    	if($fields['-single']){
    		if(isset($rec->originId)){
    			$row->originId = doc_Containers::getDocument($rec->originId)->getLink(0);
    		}
    	}
    }
    
    
    /**
     * Връща информация за основната опаковка на артикула
     * 
     * @param int $id - ид на продукт
     * @return stdClass - обект с информация
     * 				->name     - име на опаковката
     * 				->quantity - к-во на продукта в опаковката
     * 				->classId  - ид на cat_Packagings или cat_UoM
     * 				->id       - на опаковката/мярката
     */
    public function getBasePackInfo($id)
    {
    	$basePack = cat_products_Packagings::fetch("#productId = '{$id}' AND #isBase = 'yes'");
    	$arr = array();
    	
    	if($basePack){
    		$arr['name'] = cat_Packagings::getTitleById($basePack->packagingId);
    		$arr['quantity'] = $basePack->quantity;
    		$arr['classId'] = 'cat_Packagings';
    		$arr['id'] = $basePack->packagingId;
    	} else {
    		$measureId = $this->fetchField($id, 'measureId');
    		$arr['name'] = cat_UoM::getTitleById($measureId);
    		$arr['quantity'] = 1;
    		$arr['classId'] = 'cat_UoM';
    		$arr['id'] = $measureId;
    	}
    		
    	return (object)$arr;
    }
    
    
    /**
     * Връща клас имплементиращ `price_PolicyIntf`, основната ценова политика за този артикул
     */
    public function getPolicy()
    {
    	return cls::get('price_ListToCustomers');
    }
    
    
    /**
     * Връща подробното описанието на артикула
     *
     * @param mixed $id - ид/запис
     * @return mixed - подробното описанието на артикула
     */
    public function getProductDesc($id, $time = NULL)
    {
    	$rec = $this->fetchRec($id);
    	
    	return cat_ProductTplCache::cacheTpl($rec->id, $time);
    }
    
    
    /**
     * Връща заглавието на артикула като линк
     *
     * @param mixed $id - ид/запис
     * @return mixed - описанието на артикула
     */
    public function getProductDescShort($id)
    {
    	$rec = $this->fetchRec($id);
    	$title = $this->getShortHyperlink($rec->id);
    	
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$title = $this->getTitleById($rec->id);
    	}
    	
    	return $title;
    }
    
    
	/**
	 * Връща информацията за артикула според зададения режим:
	 * 		- автоматично : ако артикула е частен се връща детайлното описание, иначе краткото
	 * 		- детайлно    : винаги връщаме детайлното описание
	 * 		- кратко      : връщаме краткото описание
	 * 
	 * @param mixed $id                       - ид или запис на артикул
	 * @param datetime $time                  - време
	 * @param auto|detailed|short $mode - режим на показване
	 * 		
	 * @return mixed $res
	 * 		ако $mode e 'auto'     - ако артикула е частен се връща детайлното описание, иначе краткото
	 *      ако $mode e 'detailed' - подробно описание
	 *      ако $mode e 'short'	   - кратко описание
	 */
    public static function getAutoProductDesc($id, $time = NULL, $mode = 'auto')
    {
    	$me = cls::get(get_called_class());
    	$rec = $me->fetchRec($id);
    	
    	switch($mode){
    		case 'detailed' :
    			$res = $me->getProductDesc($rec, $time);
    			break;
    		case 'short' :
    			$res = $me->getProductDescShort($rec);
    			break;
    		default :
    			if($rec->isPublic == 'no'){
    				$res = $me->getProductDesc($rec, $time);
    			} else {
    				$res = $me->getProductDescShort($rec);
    			}
    			break;
    	}
    	
    	return $res;
    }
    
    
    /**
     * Връща последното не оттеглено или чернова задание за спецификацията
     * 
     * @param mixed $id - ид или запис
     * @return mixed $res - записа на заданието или FALSE ако няма
     */
    public static function getLastJob($id)
    {
    	$rec = self::fetchRec($id);
    	
    	// Какво е к-то от последното активно задание
    	return planning_Jobs::fetch("#productId = {$rec->id} AND #state != 'draft' AND #state != 'rejected'");
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
    	return cat_Boms::fetch("#productId = {$rec->id} AND #state = 'active'");
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    	if($mvc->haveRightFor('add')){
    		 $data->toolbar->addBtn('Нов запис', array($mvc, 'add', 'innerClass' => cat_GeneralProductDriver::getClassId()), 'order=1,id=btnAdd', 'ef_icon = img/16/shopping.png,title=Създаване на нова стока');
    	}
    	
    	if(!haveRole('ceo,cat')){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetchRec($id);
    	$row = new stdClass();
        
    	$row->title    = $this->getVerbal($rec, 'name');
        $row->authorId = $rec->createdBy;
    	$row->author   = $this->getVerbal($rec, 'createdBy');
    	$row->recTitle = $row->title;
    	$row->state    = $rec->state;
    
    	return $row;
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('folderClass' => 'cat_Categories');
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
    	 
    	return cls::haveInterface('cat_ProductFolderCoverIntf', $coverClass);
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
    	$threadRec = doc_Threads::fetch($threadId);
    	
    	return static::canAddToFolder($threadRec->folderId);
    }
    
    
    /**
     * Коя е дефолт папката за нови записи
     */
    public function getDefaultFolder()
    {
    	return cat_Categories::forceCoverAndFolder(cat_Categories::fetchField("#sysId = 'goods'", 'id'));
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add'){
    		if(isset($rec)){
    			if(isset($rec->originId)){
    				$document = doc_Containers::getDocument($rec->originId);
    				if(!$document->haveInterface('marketing_InquiryEmbedderIntf')){
    					$res = 'no_one';
    				}
    			}
    			
    			if(isset($rec->folderId)){
    				$Cover = doc_Folders::getCover($rec->folderId);
    				if(!$Cover->haveInterface('doc_ContragentDataIntf')){
    					if(!haveRole('ceo,cat')){
    						$res = 'no_one';
    					}
    				}
    			}
    		}
    	}
    	
    	// За да има достъп до орязания сингъл, трябва да не може да отвори обикновения
    	if($action == 'privatesingle' && isset($rec)){
    		if($mvc->haveRightFor('single', $rec)){
    			$res = 'no_one';
    		}
    	}
    	
    	// Ако потребителя няма достъп до папката, той няма достъп и до сингъла
    	// така дори създателя на артикула няма достъп до сингъла му, ако няма достъп до папката
    	if($action == 'single' && isset($rec->threadId)){
    		if(!doc_Threads::haveRightFor('single', $rec->threadId)){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Да се показвали бърз бутон за създаване на документа в папка
     */
    public function mustShowButton($folderRec, $userId = NULL)
    {
    	$Cover = doc_Folders::getCover($folderRec->id);
    	 
    	// Ако папката е на контрагент
    	if($Cover->getInstance() instanceof cat_Categories){
    
    		return TRUE;
    	}
    	 
    	return FALSE;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if($data->rec->state != 'rejected'){
    		$tId = $mvc->fetchField($data->rec->id, 'threadId');
    	
    		if(sales_Quotations::haveRightFor('add', (object)array('threadId' => $tId))){
    			if($qRec = sales_Quotations::fetch("#originId = {$data->rec->containerId} AND #state = 'draft'")){
    				$data->toolbar->addBtn("Оферта", array('sales_Quotations', 'edit', $qRec->id, 'ret_url' => TRUE), 'ef_icon = img/16/edit.png,title=Редактиране на оферта');
    			} else {
    				$data->toolbar->addBtn("Оферта", array('sales_Quotations', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE), 'ef_icon = img/16/document_quote.png,title=Нова оферта за спецификацията');
    			}
    		}
    	}
    	
    	if($data->rec->state == 'active'){
    		if($bRec = cat_Boms::fetch("#productId = {$data->rec->id} AND #state != 'rejected'")){
    			if(cat_Boms::haveRightFor('single', $bRec)){
    				$data->toolbar->addBtn("Рецепта", array('cat_Boms', 'single', $bRec->id, 'ret_url' => TRUE), 'ef_icon = img/16/view.png,title=Към технологичната рецепта на артикула');
    			}
    		} elseif(cat_Boms::haveRightFor('write', (object)array('productId' => $data->rec->id))){
    			$data->toolbar->addBtn("Рецепта", array('cat_Boms', 'add', 'productId' => $data->rec->id, 'originId' => $data->rec->containerId, 'ret_url' => TRUE), 'ef_icon = img/16/article.png,title=Създаване на нова технологична рецепта');
    		}
    	}
    }
    
    
    /**
     * Променяме шаблона в зависимост от мода
     */
    public static function on_BeforeRenderSingleLayout($mvc, &$tpl, $data)
    {
    	// Ако потребителя е контрактор не показваме детайлите
    	if(core_Users::isContractor()){
    		$data->noDetails = TRUE;
    		unset($data->row->meta);
    	}
    }
    
    
    /**
     * Рендира изглед за задание
     */
    public function renderJobView($id, $time = NULL)
    {
    	$rec = $this->fetchRec($id);
    	
    	return cat_ProductTplCache::cacheTpl($rec->id, $time, 'internal')->getContent();
    }
    
    
    /**
     * Връща хендлъра на изображението представящо артикула, ако има такова
     * 
     * @param mixed $id - ид или запис
     * @return fileman_FileType $hnd - файлов хендлър на изображението
     */
    public static function getProductImage($id)
    {
    	$me = cls::get(get_called_class());
    	$Driver = $me->getDriver($id);
    	
    	return $Driver->getProductImage();
    }
    
    
    /**
     * Затваряне на перата на частните артикули, по които няма движения
     * в продължение на няколко затворени периода
     */
    function cron_closePrivateProducts()
    {
    	// Намираме датата на начало на последния затворен период, Ако няма - операцията пропада
    	if(!$lastClosedPeriodRec = acc_Periods::getLastClosedPeriod()) return;
    	
    	// Намираме всички частни артикули
    	$productQuery = cat_Products::getQuery();
    	$productQuery->where("#isPublic = 'no'");
    	$productQuery->show('id');
    	$products = array_keys($productQuery->fetchAll());
    	
    	// Ако няма, не правим нищо
    	if(!count($products)) return;
    	
    	// Намираме отворените пера, създадени преди посочената дата, които са към частни артикули
    	$iQuery = acc_Items::getQuery();
    	$iQuery->where("#createdOn < '{$lastClosedPeriodRec->start}'");
    	$iQuery->where("#state = 'active'");
    	$iQuery->where("#classId = {$this->getClassId()}");
    	$iQuery->in("objectId", $products);
    	$iQuery->show('id');
    	$productItems = array();
    	while($iRec = $iQuery->fetch()){
    		$productItems[$iRec->id] = $iRec->id;
    	}
    	
    	// Ако няма отворени пера, отговарящи на условията не правим нищо
    	if(!count($productItems)) return;
    	
    	// Намираме баланса преди началото на последно затворения баланс
    	$balanceBefore = cls::get('acc_Balances')->getBalanceBefore($lastClosedPeriodRec->start);
    	
    	// Оставяме само записите където участват перата на частните артикули на произволно място
    	$bQuery = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery, $balanceBefore->id, '301,302,304,305,306,309,321,323,330,333', $productItems);
    	$bQuery->where("#ent1Id IS NOT NULL || #ent2Id IS NOT NULL || #ent3Id IS NOT NULL");
    	
    	// Групираме всички пера на частни артикули използвани в баланса
    	$itemsInBalanceBefore = array();
    	while($bRec = $bQuery->fetch()){
    		foreach (range(1, 3) as $i){
    			if(!empty($bRec->{"ent{$i}Id"}) && in_array($bRec->{"ent{$i}Id"}, $productItems)){
    				$itemsInBalanceBefore[$bRec->{"ent{$i}Id"}] = $bRec->{"ent{$i}Id"};
    			}
    		}
    	}
    	
    	// Оставяме само тез пера, които не се срещат в предходния затворен баланс
    	if(!empty($itemsInBalanceBefore)){
    		foreach ($itemsInBalanceBefore as $index => $itemId){
    			unset($productItems[$index]);
    		}
    	}
    	
    	// Ако не са останали пера за затваряне
    	if(!count($productItems)) return;
    	
    	// Затваряме останалите пера
    	foreach ($productItems as $itemId){
    		$pRec = cat_Products::fetch(acc_Items::fetchField($itemId, 'objectId'));
    		$pRec->state = 'closed';
    		$this->save($pRec);
    		$this->log("Затворено е перо: '{$itemId}'");
    	}
    }
    
    
    /**
     * Връща дефолтната цена
     *
     * @param mixed $id - ид/запис на обекта
     */
    public function getDefaultCost($id)
    {
    	// За артикула, това е цената по себестойност
    	return $this->getSelfValue($id);
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
    	$data->form->toolbar->renameBtn('save', 'Запис');
    	
    	if (!empty($data->form->toolbar->buttons['activate'])) {
    		$data->form->toolbar->removeBtn('activate');
    	}
    }
    
    
    /**
     * Орязан екшън за единичен изглед на артикула, ако потребителя няма достъп до папката му
     */
    function act_PrivateSingle()
    {
    	$this->requireRightFor('privateSingle');
    	expect($id = Request::get('id', 'int'));
    	
    	expect($rec = $this->fetchRec($id));
    	$this->requireRightFor('privateSingle', $rec);
    	
    	// Показваме съдържанието на документа
    	$tpl = $this->getInlineDocumentBody($id, 'xhtml');
    	
    	// Ако е инсталиран пакета за партньори и потребителя е партньор
    	// Слагаме за обвивка тази за партньорите
    	if(core_Packs::isInstalled('colab')){
    		if(core_Users::isContractor()){
    			$this->load('colab_Wrapper');
    			$this->currentTab = 'Нишка';
    			
    			$tpl = $this->renderWrapping($tpl);
    		}
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Връща урл-то към единичния изглед на обекта, ако потребителя има
     * права за сингъла. Ако няма права връща празен масив
     *
     * @param int $id - ид на запис
     * @return array $url - масив с урл-то на единичния изглед
     */
    public static function getSingleUrlArray($id)
    {
    	$me = cls::get(get_called_class());
    	 
    	$url = array();
    	 
    	// Ако потребителя има права за единичния изглед, подготвяме линка
    	if ($me->haveRightFor('single', $id)) {
    		$url = array($me, 'single', $id, 'ret_url' => TRUE);
    	} elseif($me->haveRightFor('privateSingle', $id)){
    		$url = array($me, 'privateSingle', $id, 'ret_url' => TRUE);
    	}
    	 
    	return $url;
    }
}