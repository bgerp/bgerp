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
class cat_Products extends embed_Manager {
    
	
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'cat_ProductDriverIntf';
	
	
	/**
	 * Как се казва полето за избор на вътрешния клас
	 */
	public $driverClassField = 'innerClass';
	
	
	/**
	 * Флаг, който указва, че документа е партньорски
	 */
	public $visibleForPartners = TRUE;
	
	
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'acc_RegisterIntf,cat_ProductAccRegIntf,acc_RegistryDefaultCostIntf';
    
    
    /**
     * Заглавие
     */
    public $title = "Артикули в каталога";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_SaveAndNew, plg_Clone, doc_DocumentPlg, plg_PrevAndNext, acc_plg_Registry, plg_State, cat_plg_Grouping, bgerp_plg_Blank,
                     cat_Wrapper, plg_Sorting, doc_ActivatePlg, doc_plg_Close, doc_plg_BusinessDoc, cond_plg_DefaultValues, plg_Printing, plg_Select, plg_Search, bgerp_plg_Import, bgerp_plg_Groups';
    
    
    /**
     * Име на полето за групите на продуктите.
     * Използва се за целите на bgerp_plg_Groups
     */
    public $groupField = 'groups';

    
    /**
     * Детайла, на модела
     */
    var $details = 'Packagings=cat_products_Packagings,Prices=cat_PriceDetails,AccReports=acc_ReportDetails,Resources=planning_ObjectResources,Jobs=planning_Jobs,Boms=cat_Boms';
    
    
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
    public $canGrouping = 'cat,ceo';

	
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'cat/tpl/products/SingleProduct.shtml';
    
    
    /**
     * Икона за еденичен изглед
     */
    public $singleIcon = 'img/16/wooden-box.png';
    
    
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
     * Да се забрани ли кеширането на документа
     */
    public $preventCache = TRUE;
	
	
	/**
	 * Шаблон (ET) за заглавие на продукт
	 * 
	 * @var string
	 */
	public $recTitleTpl = '[#name#]<!--ET_BEGIN code--> ([#code#])<!--ET_END code-->';
	
	
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
	public static $defaultStrategies = array('groups'  => 'lastDocUser|lastDoc',
											 'meta'    => 'lastDocUser|lastDoc',);
	
	
	/**
	 * Групи за обновяване
	 */
	protected $updateGroupsCnt = FALSE;
	
	
	/**
	 * Кеширана информация за артикулите
	 */
	protected static $productInfos = array();
	
	
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('proto', "key(mvc=cat_Products,allowEmpty,select=name)", "caption=Прототип,input=hidden,silent,refreshForm,placeholder=Популярни продукти");
		
        $this->FLD('code', 'varchar(32)', 'caption=Код,remember=info,width=15em');
        $this->FLD('name', 'varchar', 'caption=Наименование,remember=info,width=100%');
        $this->FLD('intName', 'varchar', 'caption=Международно име,remember=info,width=100%');
        $this->FLD('info', 'richtext(bucket=Notes)', 'caption=Описание,input=none');
        $this->FLD('measureId', 'key(mvc=cat_UoM, select=name,allowEmpty)', 'caption=Мярка,mandatory,remember,notSorting');
        $this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Илюстрация,input=none');
        $this->FLD('groups', 'keylist(mvc=cat_Groups, select=name, makeLinks)', 'caption=Маркери,maxColumns=2,remember');
        $this->FLD("isPublic", 'enum(no=Частен,yes=Публичен)', 'input=none');
        
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
        			canManifacture=Производим)', 'caption=Свойства->Списък,columns=2,remember,mandatory');
        
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
    	if($form->getField($mvc->driverClassField)){
    		$form->setField($mvc->driverClassField, "remember,removeAndRefreshForm=proto|measureId|meta");
            if(!$form->rec->id && ($driverField = $mvc->driverClassField) && ($drvId = $form->rec->{$driverField})) {
                
            	$protoProducts = cat_Categories::getProtoOptions($drvId);
            	
            	if(count($protoProducts)){
            		$form->setField('proto', 'input');
            		$form->setOptions('proto', $protoProducts);
            		
            		if($proto = Request::get('proto', 'int')) {
            			if($pRec = self::fetch($proto)) {
            				$Cmd = Request::get('Cmd');
            				if($Cmd['refresh'] && is_array($pRec->driverRec)) {
            					Request::push($pRec->driverRec);
            				}
            			}
            		}
            	}
            }
    	}
    	
    	// Всички позволени мерки
    	$measureOptions = cat_UoM::getUomOptions();
    	
    	// Ако е избран драйвер слагаме задъжителните мета данни според корицата и драйвера
    	if(isset($form->rec->folderId)){
    		$cover = doc_Folders::getCover($form->rec->folderId);
    		
    		$defMetas = ($cover->haveInterface('cat_ProductFolderCoverIntf')) ? $cover->getDefaultMeta() : array();

    		if($Driver = $mvc->getDriver($form->rec)){
    			$defMetas = $Driver->getDefaultMetas($defMetas);
    			$measureName = $Driver->getDefaultUom();
    			$defaultUomId = cat_UoM::fetchBySinonim($measureName)->id;
    			
    			// Задаваме дефолтните свойства
    			$form->setDefault('meta', $form->getFieldType('meta')->fromVerbal($defMetas));
    		}
    		
    		// Ако корицата не е на контрагент
    		if(!$cover->haveInterface('doc_ContragentDataIntf')){
    			
    			// Правим кода на артикула задължителен
    			$form->setField('code', 'mandatory');
    			if($cover->isInstanceOf('cat_Categories')){
    				
    				// Ако корицата е категория слагаме дефолтен код и мерки
    				$CategoryRec = $cover->rec();
    				if($code = $cover->getDefaultProductCode()){
    					$form->setDefault('code', $code);
    				}
    		
    				$form->setDefault('groups', $CategoryRec->markers);
    				
    				// Ако има избрани мерки, оставяме от всички само тези които са посочени в корицата +
    				// вече избраната мярка ако има + дефолтната за драйвера
    				$categoryMeasures = keylist::toArray($CategoryRec->measures);
    				if(count($categoryMeasures)){
    					if(isset($form->rec->measureId)){
    						$categoryMeasures[$form->rec->measureId] = $form->rec->measureId;
    					}
    					if(isset($defaultUomId)){
    						$categoryMeasures[$defaultUomId] = $defaultUomId;
    					}
    					
    					$measureOptions = array_intersect_key($measureOptions, $categoryMeasures);
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

    	// Ако артикула е създаден от източник
    	if(isset($form->rec->originId)){
    		$document = doc_Containers::getDocument($form->rec->originId);
    	
    		// Задаваме за дефолти полетата от източника
    		$fields = $document->getInstance()->getDriverFields($Driver);
    		$sourceRec = $document->rec();
    	
    		$form->setDefault('name', $sourceRec->title);
    		foreach ($fields as $name => $fld){
    			$form->setDefault($name, $sourceRec->driverRec[$name]);
    		}
    	}
    	
    	// Задаваме позволените мерки като опция
    	$form->setOptions('measureId', array('' => '') + $measureOptions);
    	
    	// Ако има дефолтна мярка, избираме я
    	if(isset($defaultUomId)){
    		$form->setDefault('measureId', $defaultUomId);
    	}
    	
    	// При редакция ако артикула е използван с тази мярка, тя не може да се променя
    	if(isset($form->rec->id)){
    		if(cat_products_Packagings::isUsed($form->rec->id)){
    			$form->setReadOnly('measureId');
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
    		$form->setField('measureId', 'input=hidden');
    	}
		
		// Проверяваме за недопустими символи
        if ($form->isSubmitted()){
        	$rec = &$form->rec;
           
        	if(empty($rec->name)){
        		if($Driver = $mvc->getDriver($rec)){
        			$rec->name = $Driver->getProductTitle($rec);
        		}
        	}
        	
        	if(empty($rec->name)){
        		$form->setError('name', 'Моля задайте наименование на артикула');
        	}
        	
        	if(!empty($rec->code)) {
        		if (preg_match('/[^0-9a-zа-я\- _]/iu', $rec->code)) {
        			$form->setError('code', 'Полето може да съдържа само букви, цифри, тирета, интервали и долна черта!');
        		}
        		
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
		if($Driver = $this->getDriver($rec)){
			$defMetas = $Driver->getDefaultMetas($defMetas);
		}
		
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
        
        if ($rec = self::fetch($objectId)) {
        	$Driver = cat_Products::getDriver($rec->id);

            if(!is_object($Driver)) return NULL;

        	$pInfo = cat_Products::getProductInfo($objectId);
        	
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
            $result->features = array_merge($Driver->getFeatures($objectId), $result->features);
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
    	return static::getProducts(NULL, NULL, NULL, $properties, $hasnotProperties);
    }
    
    
    /**
     * Метод връщаш информация за продукта и неговите опаковки
     * 
     * @param int $productId - ид на продукта
     * @return stdClass $res
     * 	-> productRec - записа на продукта
     * 		 o name      - име
     * 		 о measureId - ид на мярка
     * 		 o code      - код
     * 	-> meta - мета данни за продукта ако има
	 * 	     meta['canSell'] 		- дали може да се продава
	 * 	     meta['canBuy']         - дали може да се купува
	 * 	     meta['canConvert']     - дали може да се влага
	 * 	     meta['canStore']       - дали може да се съхранява
	 * 	     meta['canManifacture'] - дали може да се прозивежда
	 * 	     meta['fixedAsset']     - дали е ДА
     * 	-> packagings - всички опаковки на продукта, ако не е зададена
     */					
    public static function getProductInfo($productId)
    {
    	if(isset(self::$productInfos[$productId])){
    		
    		return self::$productInfos[$productId];
    	}
    	
    	// Ако няма такъв продукт връщаме NULL
    	if(!$productRec = static::fetchRec($productId)) {
    		return NULL;
    	}
    	
    	$res = new stdClass();
    	$res->packagings = array();
    	$res->productRec = (object)array('name'      => $productRec->name,
    									 'measureId' => $productRec->measureId,
    									 'code'      => $productRec->code,);
    	
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
    	
    	// Ако не е зададена опаковка намираме всички опаковки
    	$packQuery = cat_products_Packagings::getQuery();
    	$packQuery->where("#productId = '{$productId}'");
    	while($packRec = $packQuery->fetch()){
    		$res->packagings[$packRec->packagingId] = $packRec;
    	}
    	
    	// Връщаме информацията за продукта
    	self::$productInfos[$productId] = $res;
    	
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
    	$catPack = cat_products_Packagings::fetch(array("#eanCode = '[#1#]'", $code));
    	
    	if(!empty($catPack)) {
    		
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
    public static function getProducts($customerClass, $customerId, $datetime = NULL, $hasProperties = NULL, $hasnotProperties = NULL, $limit = NULL)
    {
		// Само активни артикули
    	$query = static::getQuery();
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
    		$title = static::getRecTitle($rec, FALSE);
    		
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
    public static function getSelfValue($productId, $packagingId = NULL, $quantity = 1, $date = NULL)
    {
    	// Опитваме се да намерим запис в в себестойностти за артикула
    	$listId = price_ListRules::PRICE_LIST_COST;
    	price_ListToCustomers::canonizeTime($date);
    	$price = price_ListRules::getPrice($listId, $productId, $packagingId, $date);
    	
    	// Ако няма се мъчим да намерим себестойността по рецепта, ако има такава
    	if(!$price){
    		if($amounts = cat_Boms::getPrice($productId)){
    			$price = ($amounts->base + $quantity * $amounts->prop) / $quantity;
    		}
    	}
    	
    	// Връщаме цената по себестойност
    	return $price;
    }
    
    
	/**
     * Връща масив със всички опаковки, в които може да участва един продукт + основната му мярка
     * Първия елемент на масива е основната опаковка (ако няма основната мярка)
     * 
     * @param int $productId - ид на артикул
     * @return array $options - опаковките
     */
    public static function getPacks($productId)
    {
    	expect($pInfo = static::getProductInfo($productId));
    	
    	// Определяме основната мярка
    	$options = array();
    	$measureId = $pInfo->productRec->measureId;
    	$baseId = $measureId;
    	
    	// За всяка опаковка, извличаме опциите и намираме имали основна такава
    	if(count($pInfo->packagings)){
    		foreach ($pInfo->packagings as $packRec){
    			$options[$packRec->packagingId] = cat_UoM::getTitleById($packRec->packagingId);
    			if($packRec->isBase == 'yes'){
    				$baseId = $packRec->packagingId;
    			}
    		}
    	}
    	
    	// Подготвяме опциите
    	$options = array($measureId => cat_UoM::getTitleById($measureId)) + $options;
    	$firstVal = $options[$baseId];
    	
    	// Подсигуряваме се че основната опаковка/мярка е първа в списъка
    	unset($options[$baseId]);
    	$options = array($baseId => $firstVal) + $options;
    	
    	// Връщаме опциите
    	return $options;
    }
    
    
    /**
     * Връща стойността на параметъра с това име
     *
     * @param string $id   - ид на записа
     * @param string $name - име на параметъра
     * @return mixed - стойност или FALSE ако няма
     */
    public static function getParamValue($id, $name)
    {
    	// Ако има драйвър, питаме него за стойността
    	if($Driver = static::getDriver($id)){
    		
    		return $Driver->getParamValue($id, $name);
    	}
    	
    	// Ако няма връщаме FALSE
    	return FALSE;
    }
    
    
    /**
     * Връща теглото на еденица от продукта, ако е в опаковка връща нейното тегло
     * 
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @return double - теглото на еденица от продукта
     */
    public static function getWeight($productId, $packagingId = NULL)
    {
    	$weight = 0;
    	if(cat_products_Packagings::getPack($productId, $packagingId)){
    		$weight = $pack->netWeight + $pack->tareWeight;
    	}
    	
    	if(!$weight){
    		$weight = static::getParamValue($productId, 'transportWeight');
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
    public static function getVolume($productId, $packagingId = NULL)
    {
    	$volume = 0;
    	if(cat_products_Packagings::getPack($productId, $packagingId)){
    		$volume = $pack->sizeWidth * $pack->sizeHeight * $pack->sizeDepth;
    	}
    	
    	if(!$volume){
    		$volume = static::getParamValue($productId, 'transportVolume');
    	}
    	
    	return $volume;
    }
    
    
    /**
     * След подготовка на записите в счетоводните справки
     */
    protected static function on_AfterPrepareAccReportRecs($mvc, &$data)
    {
    	$recs = &$data->recs;
    	if(empty($recs) || !count($recs)) return;
    	
    	$basePackId = key($mvc->getPacks($data->masterId));
    	$data->packName = cat_UoM::getTitleById($basePackId);
    	
    	$quantity = 1;
    	if($pRec = cat_products_Packagings::getPack($data->masterId, $basePackId)){
    		$quantity = $pRec->quantity;
    	}
    	
    	foreach ($recs as &$dRec){
    		$dRec->blQuantity /= $quantity;
    	}
    }
    
    
    /**
     * След подготовка на вербалнтие записи на счетоводните справки
     */
    protected static function on_AfterPrepareAccReportRows($mvc, &$data)
    {
    	$rows = &$data->balanceRows;
    	arr::placeInAssocArray($data->listFields, 'packId=Мярка', 'blQuantity');
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
    	if($fields['-single']){
    		if(isset($rec->originId)){
    			$row->originId = doc_Containers::getDocument($rec->originId)->getLink(0);
    		}
    		
    		if(isset($rec->proto)){
    			$row->proto = $mvc->getHyperlink($rec->proto);
    		}
    	}
    }
    
    
    /**
     * Връща името с което ще показваме артикула според езика в сесията
     * Ако езика не е български поакзваме интернационалното име иначе зададеното
     * 
     * @param stdClass $rec
     * @return string
     */
    private static function getDisplayName($rec)
    {
    	$lg = core_Lg::getCurrent();
    	
    	if($lg != 'bg'){
    		if(!empty($rec->intName)) return $rec->intName;
    	}
    	
    	return $rec->name;
    }
    
    
    /**
     * Извиква се преди извличането на вербална стойност за поле от запис
     */
    protected static function on_BeforeGetVerbal($mvc, &$part, $rec, $field)
    {
    	if($field == 'name') {
    		if(!is_object($rec)) {
    			$rec = new stdClass();
    		}
    		
    		$rec->name = static::getDisplayName($rec);
    	}
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$rec->name = static::getDisplayName($rec);
    	
    	return parent::getRecTitle($rec, $escaped);
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
    public static function getAutoProductDesc($id, $time = NULL, $mode = 'auto', $documentType = 'public')
    {
    	$rec = static::fetchRec($id);
    	
    	$title = cat_ProductTplCache::getCache($rec->id, $time, 'title', $documentType);
    	if(!$title){
    		$title = cat_ProductTplCache::cacheTitle($rec, $time, $documentType);
    	}
    	
    	// Ако е частен показваме за код хендлъра му + версията в кеша
    	if($rec->isPublic == 'no'){
    		$handle = cat_Products::getHandle($rec);
    		$title .= " ({$handle}";
    		$count = cat_ProductTplCache::count("#productId = {$rec->id} AND #type = 'description' AND #documentType = '{$documentType}'");
    		if($count > 1){
    			$title .= "<small>v{$count}</small>";
    		}
    		$title .= ")";
    	}
    	
    	$showDescription = FALSE;
    	
    	switch($mode){
    		case 'detailed' :
    			$showDescription = TRUE;
    			break;
    		case 'short':
    			$showDescription = FALSE;
    			break;
    		default :
    			$showDescription = ($rec->isPublic == 'no') ? TRUE : FALSE;
    			break;
    	}
    	
    	// Ако ще показваме описание подготвяме го
    	if($showDescription === TRUE){
    	    $title = "<b>{$title}</b>";
    	    
    	    $data = cat_ProductTplCache::getCache($rec->id, $time, 'description', $documentType);
    	    if(!$data){
    	    	$data = cat_ProductTplCache::cacheDescription($rec, $time, $documentType);
    	    }
    	    
    	    $descriptionTpl = cat_Products::renderDescription($data);
    	}
    	
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing')){
    		$singleUrl = static::getSingleUrlArray($rec->id);
    		$title = ht::createLinkRef($title, $singleUrl);
    	}
    	
    	// Връщаме шаблона с подготвените данни
    	$tpl = new ET("[#name#]<!--ET_BEGIN desc--><br><span style='font-size:0.85em'>[#desc#]</span><!--ET_END desc-->");
    	$tpl->replace($title, 'name');
    	$tpl->replace($descriptionTpl, 'desc');
    	
    	return $tpl;
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
    	 
    	// Какво е к-то от последната активна рецепта
    	return cat_Boms::fetch("#productId = {$rec->id} AND #state = 'active'");
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    	
    	// Бутона 'Нов запис' в листовия изглед, добавя винаги универсален артикул
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
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
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
     * Връща хендлъра на изображението представящо артикула, ако има такова
     * 
     * @param mixed $id - ид или запис
     * @return fileman_FileType $hnd - файлов хендлър на изображението
     */
    public function getIcon($id)
    {
    	if($Driver = $this->getDriver($id)){
    		
    		return $Driver->getIcon();
    	} else {
    		return 'img/16/error-red.png';
    	}
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
    		acc_Items::logInfo("Затворено е перо", $itemId);
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
    	
    	if (!Request::get('ajax_mode')) {
    		// Записваме, че потребителя е разглеждал този списък
    		$this->logInfo('Показване на ограничения сингъл', $id);
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
    
    
    /**
     * Връща складовата (средно притеглената цена) на артикула в подадения склад за количеството
     * 
     * @param double $quantity - к-во
     * @param int $productId   - ид на артикула
     * @param date $date       - към коя дата
     * @param string $storeId  - склада
     * @return mixed $amount   - сумата или NULL ако няма
     */
    public static function getWacAmountInStore($quantity, $productId, $date, $storeId = NULL)
    {
    	$item2 = acc_Items::fetchItem('cat_Products', $productId)->id;
    	if(!$item2) return NULL;
    	
    	$item1 = '*';
    	if($storeId){
    		$item1 = acc_Items::fetchItem('store_Stores', $storeId)->id;
    	}
    	
    	// Намираме сумата която струва к-то от артикула в склада
    	$amount = acc_strategy_WAC::getAmount($quantity, $date, '321', $item1, $item2, NULL);
    	
    	if(isset($amount)){
    		
    		return round($amount, 4);
    	}
    	
    	// Връщаме сумата
    	return $amount;
    }
    
    
    /**
     * Какви материали са нужни за производството на 'n' бройки от подадения артикул
     * 
     * @param int $id          - ид
     * @param double $quantity - количество
     * 			o productId - ид на продукта
     * 			o quantity - к-то на продукта
     */
    public static function getMaterialsForProduction($id, $quantity = 1)
    {
    	$res = array();
    	$bomId = static::getLastActiveBom($id)->id;
    	
    	if (isset($bomId)) {
    	
	    	$info = cat_Boms::getResourceInfo($bomId);
	    	
	    	foreach ($info['resources'] as $materialId => $rRec){
	    		if($rRec->type != 'input') continue;
	    		
	    		$quantity = $rRec->baseQuantity / $info['quantity'] + $quantity * $rRec->propQuantity / $info['quantity'];
	    		$res[$rRec->productId] = array('productId' => $rRec->productId, 'quantity' => $quantity);
	    	}
    	}
    	
    	return $res;
    }
    
    
    /**
     * Връща готовото описание на артикула
     * 
     * @param mixed $id
     * @param enum(public,internal) $documentType
     * @return core_ET
     */
    public static function getDescription($id, $documentType = 'public')
    {
    	$data = static::prepareDescription($id, $documentType);
    	
    	return static::renderDescription($data);
    }
    
    
    /**
     * Подготвя описанието на артикула
     * 
     * @param int $id
     * @param enum(public,internal) $documentType
     * @return stdClass - подготвеното описание
     */
    public static function prepareDescription($id, $documentType = 'public')
    {
    	$Driver = static::getDriver($id);
    	$data = new stdClass();
    	
    	if($Driver){
    		$data->rec = static::fetchRec($id);
    		$data->row = cat_Products::recToVerbal($data->rec);
    		$data->documentType = $documentType;
    		$data->Embedder = cls::get('cat_Products');
    		$data->isSingle = FALSE;
    		$data->noChange = TRUE;
    		$Driver->prepareProductDescription($data);
    	}
    	
    	return $data;
    }
    
    
    /**
     * Рендира описанието на артикула
     * 
     * @param stdClass $data 
     * @return core_ET
     */
    private static function renderDescription($data)
    {
    	if($data->rec){
    		$Driver = static::getDriver($data->rec);
    	}
    	
    	if($Driver){
    		$tpl = $Driver->renderProductDescription($data);
    		$componentTpl = cat_Products::renderComponents($data->components);
    		$tpl->append($componentTpl, 'COMPONENTS');
    	} else {
    		$tpl = new ET(tr("|*<span class='red'>|Проблем с показването|*</span>"));
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Рендира компонентите на един артикул
     * 
     * @param array $components - компонентите на артикула
     * @return core_ET - шаблона на компонентите
     */
    public static function renderComponents($components, $makeLinks = TRUE, $showDescription = TRUE)
    {
    	if(!count($components)) return;
    	
    	$compTpl = getTplFromFile('cat/tpl/Components.shtml');
    	$block = $compTpl->getBlock('COMP');
    	foreach ($components as $obj){
    		$bTpl = clone $block;
    		if($obj->code === '0') unset($obj->code);
    		
    		// Ако ще показваме компонента като линк, го правим такъв
    		if($makeLinks === TRUE && !Mode::is('text', 'xhtml') && !Mode::is('printing')){
    			$singleUrl = cat_Products::getSingleUrlArray($obj->componentId);
    			$obj->title = ht::createLinkRef($obj->title, $singleUrl);
    		}
    		
    		$arr = array('componentTitle'       => $obj->title, 
    				     'componentDescription' => $obj->description,
    					 'titleClass'           => $obj->titleClass,
    					 'componentCode'        => $obj->code,
    					 'componentStage'       => $obj->stageName,
    					 'componentQuantity'    => $obj->quantity,
    					 'componentMeasureId'   => $obj->measureId);
    		
    		if($showDescription === FALSE){
    			unset($arr['componentDescription']);
    		}
    		
    		$bTpl->placeArray($arr);
    		if(count($obj->components)){
    			$bTpl->append(static::renderComponents($obj->components, $makeLinks, $showDescription), 'componentComponents');
    		}
    		
    		$bTpl->removeBlocks();
    		$bTpl->append2Master();
    	}
    	$compTpl->removeBlocks();
    	
    	return $compTpl;
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	//$data->components = array();
    	//cat_Products::prepareComponents($data->rec->id, $data->components, 'internal');
    }
    
    
    /**
     * След рендиране на единичния изглед
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	if(count($data->components)){
    		$componentTpl = cat_Products::renderComponents($data->components);
    		$tpl->append($componentTpl, 'COMPONENTS');
    	}
    }
    
    
    /**
     * Подготвя обект от компонентите на даден артикул
     *
     * @param int $productId
     * @param array $res
     * @param int $level
     * @param string $code
     * @return void
     */
    public static function prepareComponents($productId, &$res = array(), $documentType = 'public', $level = 0, $code = '')
    {
    	// Имали последна активна рецепта артикула?
    	$rec = cat_Products::getLastActiveBom($productId);
    	if(!$rec) return $res;
    	 
    	// Кои детайли от нея ще показваме като компоненти
    	$dQuery = cat_BomDetails::getQuery();
    	$dQuery->where("#bomId = {$rec->id}");
    	//$dQuery->where("#type != 'stage'");
    	$level++;
    	
    	// За всеки
    	while($dRec = $dQuery->fetch()){
    		$obj = new stdClass();
    		$obj->componentId = $dRec->resourceId;
    
    		$obj->code = $code . "." . (($dRec->position) ? $dRec->position : 0);
    		$obj->code = trim($obj->code, '.');
    		$obj->title = cat_Products::getTitleById($dRec->resourceId);
    		$obj->measureId = cat_BomDetails::getVerbal($dRec, 'packagingId');
    		$obj->quantity = $dRec->baseQuantity + $dRec->propQuantity / $rec->quantity;
    		
    		// Ако показваме описанието, показваме го
    		if($dRec->type != 'stage'){
    			$obj->titleClass = 'product-component-title';
    			$obj->description = cat_Products::getDescription($dRec->resourceId, $documentType);
    			
    			$obj->components = array();
    			self::prepareComponents($dRec->resourceId, $obj->components, $documentType, $level, $obj->code);
    		} else {
    			$obj->titleClass = 'product-component-stage';
    		}
    
    		$res[] = $obj;
    	}
    	 
    	if($rec->id == 104){
    		//bp($res);
    	}
    	// Сортираме по етапа и кода
    	arr::order($res, 'stageName', 'ASC');
    	 
    	// Премахваме повтарящите се етапи
    	foreach ($res as $index => &$r){
    		if(isset($r->stageName)){
    			foreach ($res as $index1 => &$r1){
    				if($r1->stageName === $r->stageName && $index != $index1){
    					//unset($r1->stageName);
    				}
    			}
    		}
    	}
    }
}
