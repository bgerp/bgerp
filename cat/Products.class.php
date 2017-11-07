<?php



/**
 * Регистър на артикулите в каталога
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
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
    public $loadList = 'plg_RowTools2, plg_SaveAndNew, plg_Clone,doc_plg_Prototype, doc_DocumentPlg, plg_PrevAndNext, acc_plg_Registry, plg_State, cat_plg_Grouping, bgerp_plg_Blank,
                     cat_Wrapper, plg_Sorting, doc_ActivatePlg, doc_plg_Close, doc_plg_BusinessDoc, cond_plg_DefaultValues, plg_Printing, plg_Select, plg_Search, bgerp_plg_Import, bgerp_plg_Groups, bgerp_plg_Export,plg_ExpandInput';
    
    
    /**
     * Полето, което ще се разширява
     * @see plg_ExpandInput
     */
    public $expandFieldName = 'groups';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'Packagings=cat_products_Packagings,Prices=cat_PriceDetails,AccReports=acc_ReportDetails,
    Resources=planning_ObjectResources,Jobs=planning_Jobs,Boms=cat_Boms,Shared=cat_products_SharedInFolders';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'cat_products_Packagings';
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '321,323,61101,60201';
    
    
    /**
     * Да се показват ли в репортите нулевите редове
     */
    public $balanceRefShowZeroRows = TRUE;
    
    
    /**
     * Кой може да вижда частния сингъл
     */
    public $canViewpsingle = 'user';
    
    
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
    public $canAddacclimits = 'ceo,storeMaster,accMaster,accLimits';
    
    
    /**
     * Кой  може да клонира системни записи
     */
    public $canClonesysdata = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой  може да клонира запис
     */
    public $canClonerec = 'cat,ceo,sales,purchase';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Артикул";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'code,name,measureId,quantity,price,folderId';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да променя?
     */
    public $canEdit = 'cat,ceo,sales,purchase,catEdit';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'cat,ceo,sales,purchase,catEdit';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой може да добавя?
     */
    public $canClose = 'cat,ceo';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = FALSE;
    
    
    /**
     * Може ли да се редактират активирани документи
     */
    public $canEditActivated = TRUE;
    
    
    /**
     * Кой може да го разгледа?
     */
    public $canList = 'powerUser';
    
    
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
    public $canSingle = 'powerUser';
    
	
    /** 
	 *  Полета по които ще се търси
	 */
	public $searchFields = 'name, code, info';


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
	public static $defaultStrategies = array('groups' => 'lastDocUser');
	
	
	/**
	 * Групи за обновяване
	 */
	protected $updateGroupsCnt = FALSE;
	
	
	/**
	 * Кеширана информация за артикулите
	 */
	protected static $productInfos = array();
	
	
	/**
	 * Масив със създадените артикули
	 */
	protected $createdProducts = array();
	
	
	/**
	 * Полета, които могат да бъдат експортирани
	 */
	public $exportableCsvFields = 'code, name, measureId, groups, meta';
    
	
	/**
	 * Полета, които при клониране да не са попълнени
	 *
	 * @see plg_Clone
	 */
	public $fieldsNotToClone = 'originId, code, name, isPublic';
	
	
	/**
	 * Кои полета от листовия изглед да се скриват ако няма записи в тях
	 */
	public $hideListFieldsIfEmpty = 'price';
	
	
	/**
	 * Кое поле съдържа от кой прототип е артикула
	 */
	public $protoFieldName = 'proto';
	
	
	/**
	 * Кой може да импортира записи?
	 */
	public $canImport = 'catImpEx, admin';
	
	
	/**
	 * Кой може да експортира записи?
	 */
	public $canExport = 'catImpEx, admin';
	
	
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('proto', "key(mvc=cat_Products,allowEmpty,select=name)", "caption=Шаблон,input=hidden,silent,refreshForm,placeholder=Популярни продукти,groupByDiv=»");
		
        $this->FLD('code', 'varchar(32)', 'caption=Код,remember=info,width=15em');
        $this->FLD('name', 'varchar', 'caption=Наименование,remember=info,width=100%');
        $this->FLD('info', 'richtext(rows=4, bucket=Notes)', 'caption=Описание');
        $this->FLD('measureId', 'key(mvc=cat_UoM, select=name,allowEmpty)', 'caption=Мярка,mandatory,remember,notSorting,smartCenter');
        $this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Илюстрация,input=none');
        $this->FLD('groups', 'keylist(mvc=cat_Groups, select=name, makeLinks)', 'caption=Групи,maxColumns=2,remember');
        $this->FLD('isPublic', 'enum(no=Частен,yes=Публичен)', 'input=none');
        $this->FNC('quantity', 'double(decimals=2)', 'input=none,caption=Наличност,smartCenter');
        $this->FNC('price', 'double(minDecimals=2,maxDecimals=6)', 'input=none,caption=Цена,smartCenter');

        $this->FLD('canSell', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canBuy', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canStore', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canConvert', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('fixedAsset', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canManifacture', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('meta', 'set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим)', 'caption=Свойства->Списък,columns=2,mandatory');
        
        $this->setDbIndex('canSell');
        $this->setDbIndex('canBuy');
        $this->setDbIndex('canStore');
        $this->setDbIndex('canConvert');
        $this->setDbIndex('fixedAsset');
        $this->setDbIndex('canManifacture');
        
        $this->setDbUnique('code');
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param string $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
    	if($action == 'add'){
    		
    		// При добавяне, ако има папка и не е избран драйвер
    		$innerClass = Request::get('innerClass', 'int');
    		$folderId = Request::get('folderId', 'int');
    		if(empty($innerClass) && isset($folderId)){
    			
    			// Намира се последния избиран драйвер в папката
    			$lastDriver = cond_plg_DefaultValues::getFromLastDocument($mvc, $folderId, 'innerClass');
    			if(!$lastDriver){
    				$lastDriver = cat_GeneralProductDriver::getClassId();
    			}
    			
    			// Ако може да бъде избран редирект към формата с него да е избран
    			if(!empty($lastDriver)){
    				if(cls::load($lastDriver, TRUE)){
    					if(cls::get($lastDriver)->canSelectDriver()){
    						return redirect(array($mvc, 'add', 'folderId' => $folderId, 'innerClass' => $lastDriver));
    					}
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след подготовка на Едит Формата
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = $form->rec;
    	
    	// Всички позволени мерки
    	$measureOptions = cat_UoM::getUomOptions();
    	$form->setField($mvc->driverClassField, "remember,removeAndRefreshForm=proto|measureId|meta|groups");
    	
    	// Ако е избран драйвер слагаме задъжителните мета данни според корицата и драйвера
    	if(isset($rec->folderId)){
    		$cover = doc_Folders::getCover($rec->folderId);
    		$isTemplate = ($cover->getProductType() == 'template');
    		
    		$defMetas = array();
    		if(isset($rec->proto)){
    			$defMetas = $mvc->fetchField($rec->proto, 'meta');
    			$defMetas = type_Set::toArray($defMetas);
    		} else {
                if($Driver = $mvc->getDriver($rec)){
                    $defMetas = $Driver->getDefaultMetas();
                    if(count($defMetas)) {
                        $form->setField('meta', 'autohide=any');
                    }
                }
               
                if(!$defMetas || !count($defMetas)) {
                	$defMetas = $cover->getDefaultMeta();
                }
    		}
    		
    		if(count($defMetas)){
    			// Задаваме дефолтните свойства
    			$form->setDefault('meta', $form->getFieldType('meta')->fromVerbal($defMetas));
    		}
    		
    		// Ако корицата не е на контрагент
    		if(!$cover->haveInterface('crm_ContragentAccRegIntf')){
    			
    			// Правим кода на артикула задължителен, ако не е шаблон
    			if($isTemplate === FALSE){
    				$form->setField('code', 'mandatory');
    			}
    			
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
    					if(isset($rec->measureId)){
    						$categoryMeasures[$rec->measureId] = $rec->measureId;
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
    	if(isset($rec->originId)){
    		$document = doc_Containers::getDocument($rec->originId);
    	
    		// Задаваме за дефолти полетата от източника
    		$Driver = $document->getDriver();
    		$fields = $document->getInstance()->getDriverFields($Driver);
    		$sourceRec = $document->rec();
    		
    		$form->setDefault('name', $sourceRec->title);
    		foreach ($fields as $name => $fld){
    			$form->setDefault($name, $sourceRec->driverRec[$name]);
    		}
    	}
    	
    	// Ако има дефолтна мярка, избираме я
    	if(is_object($Driver) && $Driver->getDefaultUomId()){
    		$defaultUomId = $Driver->getDefaultUomId();
    		$form->setDefault('measureId', $defaultUomId);
    		$form->setField('measureId', 'input=hidden');
    	} else {
    		if($defMeasure = core_Packs::getConfigValue('cat', 'CAT_DEFAULT_MEASURE_ID')){
    			$measureOptions[$defMeasure] = cat_UoM::getTitleById($defMeasure, FALSE);
    			$form->setDefault('measureId', $defMeasure);
    		}
    		
    		// Задаваме позволените мерки като опция
    		$form->setOptions('measureId', array('' => '') + $measureOptions);
    		
    		// При редакция ако артикула е използван с тази мярка, тя не може да се променя
    		if(isset($rec->id) && $data->action != 'clone'){
    			
    			$isUsed = FALSE;
    			if(cat_products_Packagings::fetch("#productId = {$rec->id}")){
    				$isUsed = TRUE;
    			} else {
    				$isUsed = cat_products_Packagings::isUsed($rec->id, $rec->measureId, TRUE);
    			}
    			
    			// Ако артикулът е използван, мярката му не може да бъде сменена
    			if($isUsed === TRUE){
    				$form->setReadOnly('measureId');
    			}
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
		if(!isset($form->rec->innerClass)){
    		$form->setField('groupsInput', 'input=hidden');
    		$form->setField('meta', 'input=hidden');
    		$form->setField('measureId', 'input=hidden');
    		$form->setField('code', 'input=hidden');
    		$form->setField('name', 'input=hidden');
    		$form->setField('measureId', 'input=hidden');
    		$form->setField('info', 'input=hidden');
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
    		
    		// Ако артикулът е в папка на контрагент, и има вече артикул, със същото се сетва предупреждение
    		if(isset($rec->folderId)){
    			$Cover = doc_Folders::getCover($rec->folderId);
    			if($Cover->haveInterface('crm_ContragentAccRegIntf')){
    				while(cat_Products::fetchField(array("#folderId = {$rec->folderId} AND #name = '[#1#]' AND #id != '{$rec->id}'", $rec->name), 'id')){
    					$rec->name = str::addIncrementSuffix($rec->name, 'v', 2);
    				}
    			} elseif($Cover->getProductType() == 'template' && empty($rec->code)){
    				if(cat_Products::fetchField(array("#name = '[#1#]' AND #id != '{$rec->id}'", $rec->name), 'id')){
    					$form->setError('name', 'Има вече шаблон с това име');
    				}
    			}
    		}
        }
    }
    
    
    /**
     * Преди запис на продукт
     */
    protected static function on_BeforeSave($mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
    	// Разпределяме свойствата в отделни полета за полесно търсене
    	if($rec->meta){
    		$metas = type_Set::toArray($rec->meta);
    		foreach (array('canSell', 'canBuy', 'canStore', 'canConvert', 'fixedAsset', 'canManifacture') as $fld){
    			$rec->{$fld} = (isset($metas[$fld])) ? 'yes' : 'no';
    		}
    	}
    	
    	// Според папката се определя дали артикула е публичен/частен или е шаблон
    	if(isset($rec->folderId)){
    		$Cover = doc_Folders::getCover($rec->folderId);
    		$type = $Cover->getProductType($id);
    		
    		if(!isset($rec->id)){
    			$rec->isPublic = ($type != 'private') ? 'yes' : 'no';
    		}   
    		
    		if($rec->state != 'rejected' && $rec->state != 'closed'){
    			$rec->state = ($type == 'template') ? 'template' : 'draft';
    		}
    	}
    	
    	if($rec->state == 'draft'){
    		$rec->state = 'active';
    	}
    	
    	$rec->code = ($rec->code == '') ? NULL : $rec->code;
    }
    
    
    /**
     * Рутира публичен артикул в папка на категория
     */
	private function routePublicProduct($categorySysId, &$rec)
	{
		$categoryId = (is_numeric($categorySysId)) ? $categorySysId : NULL;
		if(!isset($categoryId)){
			$categoryId = cat_Categories::fetchField("#sysId = '{$categorySysId}'", 'id');
			if(!$categoryId){
				$categoryId = cat_Categories::fetchField("#sysId = 'goods'", 'id');
			}
		}
		
		// Ако няма такъв артикул създаваме документа
		if(!$exRec = $this->fetch("#code = '{$rec->code}'")){
			$rec->folderId = cat_Categories::forceCoverAndFolder($categoryId);
			$this->route($rec);
		}
		
		$defMetas = array();
		if($Driver = $this->getDriver($rec)){
			$defMetas = $Driver->getDefaultMetas();
		}
		
		if(!count($defMetas)){
			$defMetas = cls::get('cat_Categories')->getDefaultMeta($categoryId);
		}
		
		$rec->meta = ($rec->meta) ? $rec->meta : $this->getFieldType('meta')->fromVerbal($defMetas);
	}
    
    
	/**
	 * След подготовка на полетата за импортиране
	 *
	 * @param crm_Companies $mvc
	 * @param array $fields
	 */
	protected static function on_AfterPrepareImportFields($mvc, &$fields)
	{
	    $fields = array();
	     
	    $fields['code'] = array('caption' => 'Код', 'mandatory' => 'mandatory');
	    $fields['name'] = array('caption' => 'Наименование');
	    $fields['measureId'] = array('caption' => 'Мярка', 'mandatory' => 'mandatory');
	    $fields['groups'] = array('caption' => 'Групи');
	    $fields['meta'] = array('caption' => 'Свойства');
	    
	    $categoryType = 'key(mvc=cat_Categories,select=name,allowEmpty)';
	    $groupType = 'keylist(mvc=cat_Groups, select=name, makeLinks)';
	    $metaType = 'set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим)';
	    
	    $fields['Category'] = array('caption' => 'Допълнителен избор->Категория', 'mandatory' => 'mandatory', 'notColumn' => TRUE, 'type' => $categoryType);
	    $fields['Groups'] = array('caption' => 'Допълнителен избор->Групи', 'notColumn' => TRUE, 'type' => $groupType);
	    $fields['Meta'] = array('caption' => 'Допълнителен избор->Свойства', 'notColumn' => TRUE, 'type' => $metaType);

	    if (!$mvc->fields['Category']) {
	        $mvc->FNC('Category', $categoryType);
	    }
	    
	    if (!$mvc->fields['Groups']) {
	        $mvc->FNC('Groups', $groupType);
	    }
	     
	    if (!$mvc->fields['Meta']) {
	        $mvc->FNC('Meta', $metaType);
	    }
	}
	
	
    /**
     * 
     * Обработка, преди импортиране на запис при начално зареждане
     * 
     * @param cat_Products $mvc
     * @param stdObject $rec
     */
    protected static function on_BeforeImportRec($mvc, $rec)
    {
        // Полетата csv_ се попълват в loadSetupData
        // При 'Импорт' не се използват
    	if(empty($rec->innerClass)){
    		$rec->innerClass = cls::get('cat_GeneralProductDriver')->getClassId();
    	}
    	
    	if (isset($rec->csv_name)) {
    	    $rec->name = $rec->csv_name;
    	}
    	
    	// При дублиран запис, правим опит да намерим нов код
    	$onExist = Mode::get('onExist');
    	if ($onExist == 'duplicate') {
    	    $loopCnt = 0;
    	    while (self::fetch(array("#code = '[#1#]'", $rec->code))) {
    	        if ($loopCnt > 100) {
    	            $rec->code = str::getRand();
    	            continue;
    	        }
    	        if (is_int($rec->code)) {
    	            $rec->code++;
    	        } else {
    	            $nCode = str::increment($rec->code);
    	            
    	            if ($nCode !== FALSE) {
    	                $rec->code = $nCode;
    	            } else {
    	                $rec->code .= '_d';
    	            }
    	        }
    	        $loopCnt++;
    	    }
    	}
    	
    	if($rec->csv_measureId){
    		$rec->measureId = cat_UoM::fetchBySinonim($rec->csv_measureId)->id;
    	} else {
    	    if (isset($rec->measureId) && !is_numeric($rec->measureId)) {
    	        $measureName = $rec->measureId;
    	        $rec->measureId = cat_UoM::fetchBySinonim($rec->measureId)->id;

    	        if (!$rec->measureId) {
    	            self::logNotice('Липсваща мярка при импортиране: ' . "{$measureName}");
    	            
    	            return FALSE;
    	        }
    	    }
    	}
    	
    	if($rec->csv_groups){
    		$rec->groupsInput = cat_Groups::getKeylistBySysIds($rec->csv_groups);
    	} else {
    	    
    	    // От вербална стойност се опитваме да вземем невербалната
            if (isset($rec->groups)) {

                $delimiter = csv_Lib::getDevider($rec->groups);

                $groupArr = explode($delimiter, $rec->groups);
                
                $groupIdArr = array();
                
                foreach ($groupArr as $groupName) {
                    
                    $groupName = trim($groupName);
                    
                    if (!$groupName) continue;
                    
                    $force = FALSE;
                    if (haveRole('debug')) {
                        $force = TRUE;
                    }
                    $groupId = cat_Groups::forceGroup($groupName, NULL, $force);
                    
                    if (!isset($groupId)) {
                        self::logNotice('Липсваща група при импортиране: ' . "{$groupName}");
                        
                        return FALSE;
                    }
                    
                    $groupIdArr[$groupId] = $groupId;
                }
                
                $rec->groupsInput = type_Keylist::fromArray($groupIdArr);
            }
    	}
    	
    	// Обединяваме групите с избраните от потребителя
    	if ($rec->Groups) {
    	    $rec->groupsInput = type_Keylist::merge($rec->groupsInput, $rec->Groups);
    	}
    	
    	$nMetaArr = array();
    	if (isset($rec->meta)) {
    	    $metaArr = type_Set::toArray($rec->meta);
    	    if (!empty($metaArr)) {
    	        $mType = $mvc->getFieldType('meta');
    	        $suggArr = $mType->suggestions;
    	        
    	        foreach ($suggArr as &$s) {
    	            $s = mb_strtolower($s);
    	        }
    	        
    	        foreach ($metaArr as $m) {
    	            $m = trim($m);
    	            $metaErr = TRUE;
    	            if (isset($suggArr[$m])) {
    	                $nMetaArr[$m] = $m;
    	                $metaErr = FALSE;
    	            } else {
    	                $m = mb_strtolower($m);
    	                $searchVal = array_search($m, $suggArr);
    	                if ($searchVal !== FALSE) {
    	                    $nMetaArr[$searchVal] = $searchVal;
    	                    $metaErr = FALSE;
    	                }
    	            }
    	            
    	            if ($metaErr) {
    	                self::logNotice('Липсваща стойност за мета при импортиране: ' . "{$m}");
    	                
                        return FALSE;
    	            }
    	        }
    	    }
    	}
    	
    	// Обединяваме свойствата с избраните от потребителя
    	if ($rec->Meta) {
    	    $fMetaArr = type_Set::toArray($rec->Meta);
    	    $rec->meta .= $rec->meta ? ',' : '';
    	    $rec->meta .= $rec->Meta;
    	    
    	    $nMetaArr = array_merge($nMetaArr, $fMetaArr);
    	}
    	$rec->meta = implode(',', $nMetaArr);
    	
    	$rec->state = ($rec->state) ? $rec->state : 'active';
    	
    	$category = ($rec->csv_category) ? $rec->csv_category : $rec->Category;
    	
    	$mvc->routePublicProduct($category, $rec);
    }
    
    
    /**
     * Добавяне на полета към филтър форма
     * 
     * @param core_Form $listFilter
     * @return void
     */
    public static function expandFilter(&$listFilter)
    {
    	$orderOptions = arr::make('all=Всички,standard=Стандартни,private=Нестандартни,last=Последно добавени,prototypes=Шаблони,closed=Закрити');
    	$orderOptions = arr::fromArray($orderOptions);
    	 
    	$listFilter->FNC('order', "enum({$orderOptions})",
    	'caption=Подредба,input,silent,remember,autoFilter');
    	
    	$listFilter->FNC('groupId', 'key(mvc=cat_Groups,select=name,allowEmpty)',
    			'placeholder=Групи,input,silent,remember,autoFilter');
    	
    	$listFilter->view = 'horizontal';
    	$listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
    	static::expandFilter($data->listFilter);
    	$data->listFilter->setDefault('order', 'standard');
    	
    	$data->listFilter->FNC('meta1', 'enum(all=Свойства,
       							canSell=Продаваеми,
                                canBuy=Купуваеми,
                                canStore=Складируеми,
    							services=Услуги,
                                canConvert=Вложими,
    							canConvertServices=Вложими услуги,
                                fixedAsset=Дълготрайни активи,
    							fixedAssetStorable=Дълготрайни материални активи,
    							fixedAssetNotStorable=Дълготрайни НЕматериални активи,
        					    canManifacture=Производими)', 'input,autoFilter');
        $data->listFilter->showFields = 'search,order,meta1,groupId';
        $data->listFilter->input('order,groupId,search,meta1', 'silent');
        
        // Сортираме по име
        $order = 'name';
        
        // Ако е избран маркер и той е указано да се подрежда по код, сортираме по код
        if (!empty($data->listFilter->rec->groupId)) {
        	$gRec = cat_Groups::fetch($data->listFilter->rec->groupId);
        	if($gRec->orderProductBy == 'code'){
        		$order = 'code';
        	}
        }
        
        switch($data->listFilter->rec->order){
        	case 'all':
        		$data->query->orderBy("#state,#{$order}");
        		break;
        	case 'private':
        		$data->query->where("#isPublic = 'no'");
        		$data->query->orderBy("#state,#{$order}");
        		break;
			case 'last':
        		$data->query->orderBy("#createdOn=DESC");
        		break;
        	case 'closed':
        		$data->query->where("#state = 'closed'");
        		$data->query->orderBy("#{$order}");
        		break;
        	case 'prototypes':
        		$data->query->where("#state = 'template'");
        		break;
        	default :
        		$data->query->where("#isPublic = 'yes' AND #state != 'template' AND #state != 'closed'");
        		$data->query->orderBy("#state,#{$order}");
        		break;
        }
        
        // Филтър по свойства
        if ($data->listFilter->rec->meta1) {
        	switch($data->listFilter->rec->meta1){
        		case 'services':
        			$data->query->where("#canStore = 'no'");
        			break;
        		case 'fixedAssetStorable':
        			$data->query->where("#canStore = 'yes' and #fixedAsset = 'yes'");
        			break;
        		case 'fixedAssetNotStorable':
        			$data->query->where("#canStore = 'no' and #fixedAsset = 'yes'");
        			break;
        		case 'canConvertServices':
        			$data->query->where("#canConvert = 'yes' and #canStore = 'no'");
        			break;
        		case 'all':
        			break;
        		default:
        			$data->query->like("meta", $data->listFilter->rec->meta1);
        			break;
        	}
        }
        
        if ($data->listFilter->rec->groupId) {
        	$data->query->where("LOCATE('|{$data->listFilter->rec->groupId}|', #groups)");
        }
    }


    /**
     * Перо в номенклатурите, съответстващо на този продукт
     * 
     * @see acc_RegisterIntf
     */
    public static function getItemRec($objectId)
    {
        $result = NULL;
        
        if ($rec = self::fetch($objectId)) {
        	$Driver = cat_Products::getDriver($rec->id);
            if(!is_object($Driver)) return NULL;
            
            static::setCodeIfEmpty($rec);
        	
        	$result = (object)array(
                'num'      => $rec->code . " a",
                'title'    => self::getDisplayName($rec),
                'uomId'    => $rec->measureId,
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
     * Задава код на артикула ако няма
     * 
     * @param stdClass $rec - запис
     * @return void
     */
    public static function setCodeIfEmpty(&$rec)
    {
    	if($rec->isPublic == 'no' && empty($rec->code)){
    		$rec->code = "Art{$rec->id}";
    	} else {
    		if(empty($rec->code)){
    			$rec->code = ($rec->id) ? static::fetchField($rec->id, 'code') : NULL;
    		}
    	}
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
     * @param int $limit			  - лимит
     * @return array				  - намерените артикули
     */
    public static function getByProperty($properties, $hasnotProperties = NULL, $limit = NULL)
    {
    	return static::getProducts(NULL, NULL, NULL, $properties, $hasnotProperties, $limit);
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
    	if(isset(self::$productInfos[$productId])) return self::$productInfos[$productId];
    	
    	// Ако няма такъв продукт връщаме NULL
    	if(!$productRec = static::fetchRec($productId)) return NULL;
    	
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
    	$catPack = cat_products_Packagings::fetch(array("#eanCode = '[#1#]'", $code), 'productId,packagingId');
    	
    	if(!empty($catPack)) {
    		
    		// Ако има запис намираме ид-та на продукта и опаковката
    		$res->productId = $catPack->productId;
    		$res->packagingId = $catPack->packagingId;
    	} else {
    		
    		// Проверяваме имали продукт с такъв код
            $rec = self::fetch(array("#code = '[#1#]'", $code), 'id');
            if(!$rec) {
                $rec = self::fetch(array("LOWER(#code) = LOWER('[#1#]')", $code), 'id');
            }
            
    		if($rec) {
    			$res->productId = $rec->id;
    			$res->packagingId = NULL;
    		} else {
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
    	
    	if($groupRec = cat_products_VatGroups::getCurrentGroup($productId, $date)){
    		return $groupRec->vat;
    	}
    	
    	// Връщаме ДДС-то от периода
    	$period = acc_Periods::fetchByDate($date);
    	
    	return $period->vatRate;
    }
    
    
	/**
     * След всеки запис
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
        if($rec->groups) {
            $mvc->updateGroupsCnt = TRUE;
        }
        Mode::setPermanent('cat_LastProductCode' , $rec->code);
        
        if(isset($rec->originId)){
        	doc_DocumentCache::cacheInvalidation($rec->originId);
        }
        
        if(isset($rec->folderId)){
        	$Cover = doc_Folders::getCover($rec->folderId);
        	$type = $Cover->getProductType($rec->id);
        	$isPublic = isset($rec->isPublic) ? $rec->isPublic : $mvc->fetchField($rec->id, 'isPublic');
        	
        	if($type == 'public' && $isPublic == 'no'){
        		$rec->isPublic = 'yes';
        		$mvc->save_($rec, 'isPublic');
        	}
        }
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
        
        // За всеки от създадените артикули, създаваме му дефолтната рецепта ако можем
        if(count($mvc->createdProducts)){
        	foreach ($mvc->createdProducts as $rec) {
        		if($rec->canManifacture == 'yes'){
        			self::createDefaultBom($rec);
        		}
        		
        		// Ако е създаден артикул, базиран на прототип клонират се споделените му папки, само ако той е частен
        		if(isset($rec->proto) && $rec->isPublic == 'no'){
        			cat_products_SharedInFolders::cloneFolders($rec->proto, $rec->id);
        		}
        	}
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
    	
    	$res = $cntObj->html;
    	
    	return $res;
    }
    
    
    /**
     * Връща масив с артикули за избор, според подадения контрагент.
     * Намира всички стандартни + нестандартни артикули (тези само за клиента или споделени към него).
     * Или ако не е подаден контрагент от всички налични артикули
     * 
     * @param mixed $customerClass     - клас на контрагента
     * @param int|NULL $customerId     - ид на контрагента
     * @param string $datetime         - към коя дата
     * @param mixed $hasProperties     - свойства, които да имат артикулите
     * @param mixed $hasnotProperties  - свойства, които да нямат артикулите
     * @param int|NULL $limit          - лимит
     * @param boolean $orHasProperties - Дали трябва да имат всички свойства от зададените или поне едно
     * @return array $products         - артикулите групирани по вида им стандартни/нестандартни
     */
    public static function getProducts($customerClass, $customerId, $datetime = NULL, $hasProperties = NULL, $hasnotProperties = NULL, $limit = NULL, $orHasProperties = FALSE)
    {
		// Само активни артикули
    	$query = static::getQuery();
    	$query->where("#state = 'active'");
    	$reverseOrder = FALSE;
    	
    	// Ако е зададен контрагент, оставяме само публичните + частните за него
    	if(isset($customerClass) && isset($customerId)){
    		$reverseOrder = TRUE;
    		$folderId = cls::get($customerClass)->forceCoverAndFolder($customerId);
    		$sharedProducts = cat_products_SharedInFolders::getSharedProducts($folderId);
    		
    		// Избираме всички публични артикули, или частните за тази папка
    		$query->where("#isPublic = 'yes'");
    		if(count($sharedProducts)){
    			$sharedProducts = implode(',', $sharedProducts);
    			$query->orWhere("#isPublic = 'no' AND (#folderId = {$folderId} OR #id IN ({$sharedProducts}))");
    		} else {
    			$query->orWhere("#isPublic = 'no' AND #folderId = {$folderId}");
    		}
    	}
    	
    	$query->show('isPublic,folderId,meta,id,code,name');
    	
    	// Ограничаваме заявката при нужда
    	if(isset($limit)){
    		$query->limit($limit);
    	}
    	
    	// Ако има указано записи за игнориране, пропускат се
    	if(is_array($ignoreIds) && count($ignoreIds)){
    		$query->notIn('id', $ignoreIds);
    	}
    	
    	$private = $products = array();
    	$metaArr = arr::make($hasProperties);
    	$hasnotProperties = arr::make($hasnotProperties);
    	
    	// За всяко свойство търсим по полето за бързо търсене
    	if(count($metaArr)){
    		$count = 0;
    		foreach ($metaArr as $meta){
    			if($orHasProperties === TRUE){
    				$or = ($count == 0) ? FALSE : TRUE;
    			} else {
    				$or = FALSE;
    			}
    			
    			$query->where("#{$meta} = 'yes'", $or);
    			$count++;
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
    		
    		if($reverseOrder === TRUE){
    			$products = $private + $products;
    		} else {
    			$products = $products + $private;
    		}
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
    	
    	// Ако няма цена се опитва да намери от драйвера
    	if(!$price){
    		if($Driver = cat_Products::getDriver($productId)){
    			$price = $Driver->getPrice($productId, $quantity, 0, 0, $date);
    		}
    	}
    	
    	// Ако няма се мъчим да намерим себестойността по рецепта, ако има такава
    	if(!$price){
    		$bomRec = cat_Products::getLastActiveBom($productId, 'sales');
    		if(empty($bomRec)){
    			$bomRec = cat_Products::getLastActiveBom($productId, 'production');
    		}
    		
    		if($bomRec){
    			$price = cat_Boms::getBomPrice($bomRec, $quantity, 0, 0, $date, price_ListRules::PRICE_LIST_COST);
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
    	$options = array();
    	$pInfo = static::getProductInfo($productId);
    	if(!$pInfo) return $options;
    	
    	// Определяме основната мярка
    	$measureId = $pInfo->productRec->measureId;
    	$baseId = $measureId;
    	
    	// За всяка опаковка, извличаме опциите и намираме имали основна такава
    	if(count($pInfo->packagings) && isset($pInfo->meta['canStore'])){
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
	 * Връща стойността на параметъра с това име, или
	 * всички параметри с техните стойностти
	 * 
	 * @param string $id     - ид на записа
	 * @param string $name   - име на параметъра, или NULL ако искаме всички
	 * @param boolean $verbal - дали да са вербални стойностите
	 * @return mixed - стойност или празен масив ако няма параметри
	 */
    public static function getParams($id, $name = NULL, $verbal = FALSE)
    {
    	// Ако има драйвър, питаме него за стойността
    	if($Driver = static::getDriver($id)){
    	
    		return $Driver->getParams(cat_Products::getClassId(), $id, $name, $verbal);
    	}
    	 
    	// Ако няма връщаме празен масив
    	return (isset($name)) ? NULL : array();
    }
    
    
    /**
	 * ХТМЛ представяне на артикула (img)
	 *
	 * @param int $id - запис на артикул
	 * @param array $size - размер на картинката
	 * @param array $maxSize - макс размер на картинката
	 * @return string|NULL $preview - хтмл представянето
	 */
    public static function getPreview($id, $size = array('280', '150'), $maxSize = array('550', '550'))
    {
    	// Ако има драйвър, питаме него за стойността
    	if($Driver = static::getDriver($id)){
    		$rec = self::fetchRec($id);
    		
    		return $Driver->getPreview($rec, static::getSingleton(), $size, $maxSize);
    	}
    
    	// Ако няма връщаме FALSE
    	return NULL;
    }
    
    
    /**
     * Връща транспортното тегло за подаденото количество и опаковка
     * 
     * @param int $productId   - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @param int $quantity    - общо количество
     * @return double|NULL     - теглото на единица от продукта
     */
    public static function getWeight($productId, $packagingId = NULL, $quantity)
    {
    	// За нескладируемите не се изчислява транспортно тегло
    	if(cat_Products::fetchField($productId, 'canStore') != 'yes') return NULL;
    	
    	// Първо се гледа най-голямата опаковка за която има Бруто тегло
    	$packQuery = cat_products_Packagings::getQuery();
    	$packQuery->where("#productId = '{$productId}'");
    	$packQuery->where("#netWeight IS NOT NULL AND #tareWeight IS NOT NULL");
    	$packQuery->orderBy('quantity', "DESC");
    	$packQuery->limit(1);
    	$packQuery->show('netWeight,tareWeight,quantity');
    	$packRec = $packQuery->fetch();
    	
    	if(is_object($packRec)){
    		
    		// Ако има такава количеството се преизчислява в нея
    		$brutoWeight = $packRec->netWeight + $packRec->tareWeight;
    		$quantity /= $packRec->quantity;
    		
    		// Връща се намереното тегло
    		$weight = $brutoWeight * $quantity;
    		return round($weight, 2);
    	}
    	
    	// Ако няма транспортно тегло от опаковката гледа се от артикула
    	if($weight = static::getParams($productId, 'transportWeight')){
    		$weight *= $quantity;
    		return round($weight, 2);
    	}
    	
    	return NULL;
    }
    
    
	/**
     * Връща транспортния обем за подаденото количество и опаковка
     * 
     * @param int $productId   - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @param int $quantity    - общо количество
     * @return double - теглото на единица от продукта
     */
    public static function getVolume($productId, $packagingId = NULL, $quantity)
    {
    	// За нескладируемите не се изчислява транспортно тегло
    	if(cat_Products::fetchField($productId, 'canStore') != 'yes') return NULL;
    	 
    	// Първо се гледа най-голямата опаковка за която има Бруто тегло
    	$packQuery = cat_products_Packagings::getQuery();
    	$packQuery->where("#productId = '{$productId}'");
    	$packQuery->where("#sizeWidth IS NOT NULL AND #sizeHeight IS NOT NULL AND #sizeDepth IS NOT NULL");
    	$packQuery->orderBy('quantity', "DESC");
    	$packQuery->limit(1);
    	$packQuery->show('sizeWidth,sizeHeight,sizeDepth,quantity');
    	$packRec = $packQuery->fetch();
    	 
    	if(is_object($packRec)){
    	
    		// Ако има такава количеството се преизчислява в нея
    		$brutoVolume = $packRec->sizeWidth * $packRec->sizeHeight * $packRec->sizeDepth;
    		$quantity /= $packRec->quantity;
    	
    		// Връща се намереното тегло
    		$volume = $brutoVolume * $quantity;
    		return round($volume, 2);
    	}
    	
    	$volume = static::getParams($productId, 'transportVolume');
    	if($volume){
    		$volume *= $quantity;
    		return round($volume, 2);
    	}
    	
    	return NULL;
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
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-single']){
    		if(isset($rec->originId)){
    			$row->originId = doc_Containers::getDocument($rec->originId)->getLink(0);
    		}
    		
    		if(isset($rec->proto)){
    			$row->proto = $mvc->getHyperlink($rec->proto);
    		}
    		
    		if($mvc->haveRightFor('edit', $rec)){
    			if(!Mode::isReadOnly()){
    				$row->editGroupBtn = ht::createLink('', array($mvc, 'EditGroups', $rec->id, 'ret_url' => TRUE), FALSE, 'ef_icon=img/16/edit-icon.png,title=Промяна на групите на артикула');
    			}
    		}
    		
    		$groupLinks = cat_Groups::getLinks($rec->groupsInput);
    		$row->groupsInput = (count($groupLinks)) ? implode(' ', $groupLinks) : "<i>" . tr("Няма") . "</i>";
    	}
        
        if($fields['-list']){
            $meta = arr::make($rec->meta, TRUE);
     
           if($meta['canStore']) {  
           		$rec->quantity = store_Products::getQuantity($rec->id);
            }
            
            if($rec->quantity) {
                $row->quantity = $mvc->getVerbal($rec, 'quantity');
                if($rec->quantity < 0) {
                    $row->quantity = "<span style='color:red;'>" . $row->quantity . "</span>";
                }
            }
            
            if($meta['canSell']) { 
                if($rec->price = price_ListRules::getPrice(cat_Setup::get('DEFAULT_PRICELIST'), $rec->id)) {
                    $vat = self::getVat($rec->id);
                    $rec->price *= (1 + $vat);
                    $row->price = $mvc->getVerbal($rec, 'price');
                }
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
    	// Ако в името имаме '||' го превеждаме
    	$name = $rec->name;
    	if(strpos($rec->name, '||') !== FALSE){
    		$name = tr($rec->name);
    	}
    	
    	// Иначе го връщаме такова, каквото е
    	return $name;
    }
    
    
    /**
     * Извиква се преди извличането на вербална стойност за поле от запис
     */
    protected static function on_BeforeGetVerbal($mvc, &$part, &$rec, $field)
    {
    	if($field == 'name') {
    		if(!is_object($rec) && type_Int::isInt($rec)){
    			$rec = $mvc->fetchRec($rec);
    		}
    		
    		$part = self::getDisplayName($rec);

            return FALSE;
    	} elseif($field == 'code'){
    		if(!is_object($rec) && type_Int::isInt($rec)){
    			$rec = $mvc->fetchRec($rec);
    		}
    		
            $cRec = clone($rec);
    		self::setCodeIfEmpty($cRec);
            $part = $cRec->code;

            return FALSE;
    	}
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на ключа
     */
    public static function getTitleById($id, $escaped = TRUE)
    {
    	// Предефиниране на метода, за да е подсигурено само фечването на нужните полета
    	// За да се намали натоварването, при многократни извиквания
    	$rec = self::fetch($id, 'name,code,isPublic');
    	
    	return parent::getTitleById($rec, $escaped);
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$rec->name = self::getDisplayName($rec);
    	static::setCodeIfEmpty($rec);
    	
    	return parent::getRecTitle($rec, $escaped);
    }
    
    
	/**
	 * Връща информацията за артикула според зададения режим:
	 * 		- автоматично : ако артикула е частен се връща детайлното описание, иначе краткото
	 * 		- детайлно    : винаги връщаме детайлното описание
	 * 		- кратко      : връщаме краткото описание
	 * 
	 * @param mixed $id                 - ид или запис на артикул
	 * @param datetime $time            - време
	 * @param auto|detailed|short $mode - режим на показване
	 * @param string $lang              - език
	 * @param int $compontQuantity      - к-во на компонентите   
	 * @param boolean $showCode         - да се показва ли кода до името или не
	 * 
	 * @return mixed $res
	 * 		ако $mode e 'auto'     - ако артикула е частен се връща детайлното описание, иначе краткото
	 *      ако $mode e 'detailed' - подробно описание
	 *      ако $mode e 'short'	   - кратко описание
	 */
    public static function getAutoProductDesc($id, $time = NULL, $mode = 'auto', $documentType = 'public', $lang = 'bg', $compontQuantity = 1, $showCode = TRUE)
    {
    	$rec = static::fetchRec($id);
    	
    	$title = cat_ProductTplCache::getCache($rec->id, $time, 'title', $documentType, $lang);
    	if(!$title){
    		$title = cat_ProductTplCache::cacheTitle($rec, $time, $documentType, $lang);
    	}
    	
    	$fullTitle = $title;
    	$title = (is_array($fullTitle)) ? $fullTitle['title'] : $fullTitle;
    	$subTitle = (is_array($fullTitle)) ? $fullTitle['subTitle'] : NULL;
    	
    	if($showCode === TRUE){
    		$titleTpl = new core_ET('[#name#]<!--ET_BEGIN code--> ([#code#])<!--ET_END code-->');
    		$titleTpl->replace($title, 'name');
    		
    		
    		if(!empty($rec->code)){
    			$code = core_Type::getByName('varchar')->toVerbal($rec->code);
    			if(!mb_strpos($title, "({$code})")){
    				$titleTpl->replace($code, 'code');
    			}
    		}
    		$title = $titleTpl->getContent();
    		
    		if($rec->isPublic == 'no' && empty($rec->code)){
    			$count = cat_ProductTplCache::count("#productId = {$rec->id} AND #type = 'description' AND #documentType = '{$documentType}'", 2);
    			$title .= " (Art{$rec->id})";
    			
    			if($count > 1){
    				$vNumber = "/<small class='versionNumber'>v{$count}</small>";
    				$title = str::replaceLastOccurence($title, ')', $vNumber . ")");
    			}
    		}
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
    	    $data = cat_ProductTplCache::getCache($rec->id, $time, 'description', $documentType, $lang);
    	    if(!$data){
    	    	$data = cat_ProductTplCache::cacheDescription($rec, $time, $documentType, $lang, $compontQuantity);
    	    }
    	    $data->documentType = $documentType;
    	    $descriptionTpl = cat_Products::renderDescription($data);
    	    
    	    // Удебеляваме името само ако има допълнително описание
    	    if(strlen($descriptionTpl->getContent())){
    	    	$title = "<b>{$title}</b>";
    	    }
    	}
    	
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing')){
    		$singleUrl = static::getSingleUrlArray($rec->id);
    		$title = ht::createLinkRef($title, $singleUrl);
    	}
    	
    	// Връщаме шаблона с подготвените данни
    	$tpl = new ET("[#name#]<!--ET_BEGIN additionalTitle--><br>[#additionalTitle#]<!--ET_END additionalTitle--><!--ET_BEGIN desc--><br><div style='font-size:0.85em'>[#desc#]</div><!--ET_END desc-->");
    	$tpl->replace($title, 'name');
    	$tpl->replace($descriptionTpl, 'desc');
    	
    	
    	if(!empty($subTitle)){
    		$tpl->replace($subTitle, 'additionalTitle');
    	}
    	
    	$r = $tpl->getContent();
    	
    	return $tpl;
    }
    
    
    /**
     * Връща последната активна рецепта на артикула
     *
     * @param mixed $id - ид или запис
     * @param sales|production $type - вид работна или търговска
     * @return mixed $res - записа на рецептата или FALSE ако няма
     */
    public static function getLastActiveBom($id, $type = NULL)
    {
    	$rec = self::fetchRec($id);
    	
    	// Ако артикула не е производим не търсим рецепта
    	if($rec->canManifacture == 'no') return FALSE;
    	
    	$cond = "#productId = '{$rec->id}' AND #state = 'active'";
    	
    	if(isset($type)){ 
    		expect(in_array($type, array('sales', 'production'))); 
    		$cond .= " AND #type = '{$type}'";
    	}
    	
    	// Какво е к-то от последната активна рецепта
    	return cat_Boms::fetch($cond);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	// Бутона 'Нов запис' в листовия изглед, добавя винаги универсален артикул
    	if($mvc->haveRightFor('add')){
    		 $data->toolbar->addBtn('Нов запис', array($mvc, 'add'), 'order=1,id=btnAdd', 'ef_icon = img/16/shopping.png,title=Създаване на нова стока');
    	}
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetchRec($id);
    	$row = new stdClass();
        
    	$row->title    = $this->getTitleById($rec->id);
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
    public static function getCoversAndInterfacesForNewDoc()
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
    		}
    	}
    	
    	// Ако потребителя няма определени роли не може да добавя или променя записи в папка на категория
    	if(($action == 'add' || $action == 'edit' || $action == 'write' || $action == 'clonerec') && isset($rec)){
			if($rec->isPublic == 'yes'){
				if(!haveRole('ceo,cat')){
					$res = 'no_one';
				}
			}
    	}
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
    	// Ако има чернова оферта към нея, бутон за редакция
    	if($qRec = sales_Quotations::fetch("#originId = {$data->rec->containerId} AND #state = 'draft'")){
    		if(sales_Quotations::haveRightFor('edit', $qRec)){
    			$data->toolbar->addBtn("Оферта", array('sales_Quotations', 'edit', $qRec->id, 'ret_url' => TRUE), 'ef_icon = img/16/edit.png,title=Редактиране на оферта');
    		}
    	} elseif($data->rec->state != 'rejected'){
    		if(sales_Quotations::haveRightFor('add', (object)array('threadId' => $data->rec->threadId, 'originId' => $data->rec->containerId))){
    			$data->toolbar->addBtn("Оферта", array('sales_Quotations', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE), 'ef_icon = img/16/document_quote.png,title=Нова оферта за артикула');
    		}
    	}
    	
    	if(core_Packs::isInstalled('batch')){
    		if(batch_Defs::haveRightFor('add', (object)array('productId' => $data->rec->id))){
    			$data->toolbar->addBtn("Партидност", array('batch_Defs', 'add', 'productId' => $data->rec->id, 'ret_url' => TRUE), 'ef_icon = img/16/wooden-box.png,title=Добавяне на партидност,row=2');
    		}
    	}

    	if(sales_Sales::haveRightFor('createsaleforproduct', (object)array('folderId' => $data->rec->folderId, 'productId' => $data->rec->id))){
    		$data->toolbar->addBtn("Продажба", array('sales_Sales', 'createsaleforproduct', 'folderId' => $data->rec->folderId, 'productId' => $data->rec->id, 'ret_url' => TRUE), 'ef_icon = img/16/cart_go.png,title=Създаване на нова продажба');
    	}
    }
    
    
    /**
     * Променяме шаблона в зависимост от мода
     */
    protected static function on_BeforeRenderSingleLayout($mvc, &$tpl, $data)
    {
    	// Ако потребителя е контрактор не показваме детайлите
    	if(core_Users::haveRole('partner')){
    		$data->noDetails = TRUE;
    		unset($data->row->meta);
    	}
    }
    
    
    /**
     * Връща хендлъра на изображението представящо артикула, ако има такова
     * 
     * @param mixed $id - ид или запис
     * @return string - файлов хендлър на изображението
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
    public function cron_closePrivateProducts()
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
    		acc_Items::logWrite("Затворено е перо", $itemId);
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
    	return self::getSelfValue($id);
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
    	$data->form->toolbar->removeBtn('activate');
    }
    
    
    /**
     * Прави стандартна 'обвивка' на изгледа
     * @todo: да се отдели като плъгин
     */
    function renderWrapping_($tpl, $data = NULL)
    {
    	if(core_Packs::isInstalled('colab')){
    		if(core_Users::haveRole('partner')){
    			$this->load('cms_ExternalWrapper');
    			$this->currentTab = 'Нишка';
    		}
    	}
    	
    	return parent::renderWrapping_($tpl, $data);
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
    	if(!empty($storeId)){
    		$item1 = acc_Items::fetchItem('store_Stores', $storeId)->id;
    	}
    	
    	// Намираме сумата която струва к-то от артикула в склада
    	$maxTry = core_Packs::getConfigValue('cat', 'CAT_WAC_PRICE_PERIOD_LIMIT');
    	$amount = acc_strategy_WAC::getAmount($quantity, $date, '321', $item1, $item2, NULL, $maxTry);
    	
    	if(isset($amount)) return round($amount, 4);
    	
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
    public static function getMaterialsForProduction($id, $quantity = 1, $date = NULL, $recursive = FALSE)
    {
    	if(!$date){
    		$date = dt::now();
    	}

    	$res = array();
    	
    	// Намираме рецептата за артикула (ако има)
    	$bomId = static::getLastActiveBom($id, 'production')->id;
    	if(!$bomId) {
    		$bomId = static::getLastActiveBom($id, 'sales')->id;
    	}

    	if (isset($bomId)) {
    		
    		// Извличаме какво к-во
	    	$info = cat_Boms::getResourceInfo($bomId, $quantity, $date);
	    
	    	foreach ($info['resources'] as $materialId => $rRec){
	    		if($rRec->type != 'input') continue;
	    		
	    		// Добавяме материала в масива
	    		$quantity1 = $rRec->baseQuantity + $rRec->propQuantity;
	    		if(!array_key_exists($rRec->productId, $res)){
	    			$res[$rRec->productId] = array('productId' => $rRec->productId, 'quantity' => $quantity1);
	    		} else {
	    			$res[$rRec->productId]['quantity'] += $quantity1;
	    		}
	    		
	    		// Ако искаме рекурсивно, проверяваме дали артикула има материали
	    		if($recursive === TRUE){ 
	    			$newMaterials = self::getMaterialsForProduction($rRec->productId, $quantity1, $date, $recursive);
	    			
	    			// Ако има артикула се маха и се викат материалите му
	    			if(count($newMaterials)){
	    				unset($res[$rRec->productId]);
	    				
	    				foreach ($newMaterials as $pId => $arr){
	    					if(array_key_exists($pId, $res)){
	    						$res[$pId]['quantity'] += $arr['quantity'];
	    					} else {
	    						$res[$pId] = $arr;
	    					}
	    				}
	    			}
	    		}
	    	}
    	}
    	
    	return $res;
    }
    
    
    /**
     * Връща готовото описание на артикула
     * 
     * @param mixed $id
     * @param enum(public,internal,invoice) $documentType
     * @return core_ET
     */
    public static function getDescription($id, $documentType = 'public')
    {
    	$data = static::prepareDescription($id, $documentType);
    	
    	return self::renderDescription($data);
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
    		$showLinks = ($data->documentType == 'public' || $data->documentType == 'invoice') ? FALSE : TRUE;
    		
    		$componentTpl = cat_Products::renderComponents($data->components, $showLinks);
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
    public static function renderComponents($components, $makeLinks = TRUE)
    {
    	if(!count($components)) return;
    	
    	$compTpl = getTplFromFile('cat/tpl/Components.shtml');
    	$block = $compTpl->getBlock('COMP');
    	foreach ($components as $obj){
    		$bTpl = clone $block;
    		if($obj->quantity == cat_BomDetails::CALC_ERROR){
    			$obj->quantity = "<span class='red'>???</span>";
    		} else {
    			$obj->divideBy = ($obj->divideBy) ? $obj->divideBy : 1;
    			$quantity = $obj->quantity / $obj->divideBy;
    			
    			$Double = cls::get('type_Double', array('params' => array('smartRound' => 'smartRound')));
    			$obj->quantity = $Double->toVerbal($quantity);
    		}
    		
    		// Ако ще показваме компонента като линк, го правим такъв
    		if($makeLinks === TRUE && !Mode::is('text', 'xhtml') && !Mode::is('printing')){
    			$singleUrl = cat_Products::getSingleUrlArray($obj->componentId);
    			$obj->title = ht::createLinkRef($obj->title, $singleUrl);
    		}
    		
    		$obj->divideBy = ($obj->divideBy) ? $obj->divideBy : 1;
    		
    		$arr = array('componentTitle'       => $obj->title, 
    				     'componentDescription' => $obj->description,
    					 'titleClass'           => $obj->titleClass,
    					 'componentCode'        => $obj->code,
    					 'componentStage'       => $obj->stageName,
    					 'componentQuantity'    => $obj->quantity,
    					 'level'				=> $obj->level,
    				     'leveld'				=> $obj->leveld,
    					 'componentMeasureId'   => $obj->measureId);
    		
    		$bTpl->placeArray($arr);
    		$bTpl->removeBlocks();
    		$bTpl->append2Master();
    	}
    	$compTpl->removeBlocks();
    	
    	return $compTpl;
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$data->components = array();
    	cat_Products::prepareComponents($data->rec->id, $data->components);
    }
    
    
    /**
     * След рендиране на единичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
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
     * @param string $documentType
     * @param number $componentQuantity
     * @param string $typeBom
     * @return array
     */
    public static function prepareComponents($productId, &$res = array(), $documentType = 'internal', $componentQuantity = 1, $typeBom = NULL)
    {
    	$typeBom = (!empty($typeBom)) ? $typeBom : 'sales';
    	$rec = cat_Products::getLastActiveBom($productId, $typeBom);
    	
    	// Ако няма последна активна рецепта, и сме на 0-во ниво ще показваме от черновите ако има
    	if(empty($rec)){
    		$bQuery = cat_Boms::getQuery();
    		$bQuery->where("#productId = {$productId} AND #state = 'draft' AND #type = 'sales'");
    		$bQuery->orderBy('id', 'DESC');
    		$rec = $bQuery->fetch();
    	}
    	
    	if(!$rec || cat_Boms::showInProduct($rec) === FALSE) return $res;
    	
    	// Кои детайли от нея ще показваме като компоненти
    	$details = cat_BomDetails::getOrderedBomDetails($rec->id);
    	$qQuantity = $componentQuantity;
    	
    	if(is_array($details)){
    		$fields = cls::get('cat_BomDetails')->selectFields();
    		$fields['-components'] = TRUE;
    		
    		foreach ($details as $dRec){
    			if(!isset($dRec->parentId)){
    				$dRec->params['$T'] = $qQuantity;
    			}
    			
    			$obj = new stdClass();
    			$obj->componentId = $dRec->resourceId;
    			$row = cat_BomDetails::recToVerbal($dRec, $fields);
    			$obj->code = $row->position;
    			
    			$codeCount = strlen($obj->code);
    			$length = $codeCount - strlen(".{$dRec->position}");
    			$length = ($length < 0) ? 0 : $length;
    			$obj->parent = substr($obj->code, 0, $length);
    			
    			$obj->title = cat_Products::getTitleById($dRec->resourceId);
    			$obj->measureId = $row->packagingId;
    			$obj->quantity = ($dRec->rowQuantity == cat_BomDetails::CALC_ERROR) ? $dRec->rowQuantity : $dRec->rowQuantity;
    			
    			$obj->level = substr_count($obj->code, '.');
    			$obj->titleClass = 'product-component-title';
    			if($dRec->type == 'stage'){
    				$specTpl = cat_Products::getParams($dRec->resourceId, 'specTpl');
    				if($specTpl && count($dRec->params)){
    					$specTpl = strtr($specTpl, $dRec->params);
    					$specTpl = new core_ET($specTpl);
    					$obj->title .= " " . $specTpl->getContent();
    				}
    			}
    			
    			if($obj->parent){
    				if($res[$obj->parent]->quantity != cat_BomDetails::CALC_ERROR){
    					$obj->quantity *= $res[$obj->parent]->quantity;
    				}
    			} else {
    				$obj->quantity *= $qQuantity;
    			}
    			
    			if($dRec->description){
    				$obj->description = $row->description;
    				$obj->leveld = $obj->level;
    			}
    			$res[$obj->code] = $obj;
    			$obj->divideBy = $rec->quantity;
    		}
    	}
    	
    	return $res;
    }
    
    
    /**
     * Създава дефолтната рецепта за артикула.
     * Ако е по прототип клонира и разпъва неговата,
     * ако не проверява дали от драйвера може да се генерира
     * 
     * @param int $id - ид на артикул
     * @return void;
     */
    private static function createDefaultBom($id)
    {
    	$rec = static::fetchRec($id);
    	
    	// Ако не е производим артикула, не правим рецепта
    	if($rec->canManifacture == 'no') return;
    	
    	// Ако има прототипен артикул, клонираме му рецептата и я разпъваме
    	if(isset($rec->proto)){
    		cat_Boms::cloneBom($rec->proto, $rec);
    	} else {
    		
    		// Ако не е прототипен, питаме драйвера може ли да се генерира рецепта
    		if($Driver = static::getDriver($rec)){
    			$defaultData = $Driver->getDefaultBom($rec);
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
    	$mvc->createdProducts[] = $rec;
    }
    
    
    /**
     * Връща информация за какви дефолт задачи за производство могат да се създават по артикула
     *
     * @param mixed $id - ид или запис на артикул
     * @param double $quantity - к-во за произвеждане
     *
     * @return array $drivers - масив с информация за драйверите, с ключ името на масива
     * 				    -> title        - дефолт име на задачата
     * 					-> driverClass  - драйвър на задача
     * 					-> products     - масив от масиви с продуктите за влагане/произвеждане/отпадане
     * 						 - array input      - материали за влагане
     * 						 - array production - артикули за произвеждане
     * 						 - array waste      - отпадъци
     */
    public static function getDefaultProductionTasks($id, $quantity = 1)
    {
    	$defaultTasks = array();
    	expect($rec = self::fetch($id));
    	
    	if($rec->canManifacture != 'yes') return $defaultTasks;
    	
    	// Питаме драйвера какви дефолтни задачи да се генерират
    	$ProductDriver = cat_Products::getDriver($rec);
    	if(!empty($ProductDriver)){
    		$defaultTasks = $ProductDriver->getDefaultProductionTasks($quantity);
    	}
    	
    	// Ако няма дефолтни задачи
    	if(!count($defaultTasks)){
    		
    		// Намираме последната активна рецепта
    		$bomRec = self::getLastActiveBom($rec, 'production');
    		if(!$bomRec){
    			$bomRec = self::getLastActiveBom($rec, 'sales');
    		}
    		
    		// Ако има опитваме се да намерим задачите за производството по нейните етапи
    		if($bomRec){
    			$defaultTasks = cat_Boms::getTasksFromBom($bomRec, $quantity);
    		}
    	}
    	
    	// Връщаме намерените задачи
    	return $defaultTasks;
    }
    
    
    /**
     * Кои полета от драйвера да се добавят към форма за автоматично създаване на артикул
     * 
     * @param core_Form - $form
     * @param int $id - ид на артикул
     * @return void
     */
    public static function setAutoCloneFormFields(&$form, $id, $driverId = NULL)
    {
    	$form->FLD('name', 'varchar', 'caption=Наименование,remember=info,width=100%');
    	$form->FLD('info', 'richtext(rows=4, bucket=Notes)', 'caption=Описание');
    	$form->FLD('measureId', 'key(mvc=cat_UoM, select=name,allowEmpty)', 'caption=Мярка,mandatory,remember,notSorting,smartCenter');
    	$form->FLD('groups', 'keylist(mvc=cat_Groups, select=name, makeLinks)', 'caption=Групи,maxColumns=2,remember');
    	$form->FLD('meta', 'set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим)', 'caption=Свойства->Списък,columns=2,mandatory');
    	
    	if(isset($id)){
    		$Driver = self::getDriver($id);
    		
    		// Добавяне на стойностите от записа в $rec-a на формата
    		$rec = self::fetch($id);
    		if($rec) {
    			$fields = self::getDriverFields($Driver);
    			if(is_array($fields)) {
    				foreach($fields as $name => $caption) {
    					if(isset($rec->{$name})) {
    						$form->rec->{$name} = $rec->{$name};
    					}
    				}
    			}
    		}
    	} else {
    		$Driver = cls::get($driverId);
    	}

    	$Driver->addFields($form);
    }
    
    
    /**
     * Екшън за редактиране на групите на артикула
     */
    function act_EditGroups()
    {
    	$this->requireRightFor('edit');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('edit', $rec);
    	
    	$form = cls::get('core_Form');
    	$form->title = "Промяна на групите на|* <b>" . cat_Products::getHyperlink($id, TRUE) . "</b>";
    	$form->FNC('groupsInput', 'keylist(mvc=cat_Groups,select=name)', 'caption=Групи,input');
    	$form->setDefault('groupsInput', $rec->groupsInput);
    	$form->input();
    	if($form->isSubmitted()){
    		$fRec = $form->rec;
    		
    		if($fRec->groupsInput != $rec->groupsInput){
    			$this->save((object)array('id' => $id, 'groupsInput' => $fRec->groupsInput), 'groups');
    		}
    		
    		return followRetUrl();
    	}
    	
    	$form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
    	
    	return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Метод позволяващ на артикула да добавя бутони към rowtools-а на документ
     * 
     * @param int $id - ид на артикул
     * @param core_RowToolbar $toolbar - тулбара
     * @param mixed $detailClass - класа на детайла на документа
     * @param int $detailId - ид на реда от детайла на документа
     * @return void
     */
    public static function addButtonsToDocToolbar($id, core_RowToolbar &$toolbar, $detailClass, $detailId)
    {
    	if($Driver = self::getDriver($id)){
    		$Driver->addButtonsToDocToolbar($id, $toolbar, $detailClass, $detailId);
    	}
    }
    
    
    /**
     * Връща сметките, върху които може да се задават лимити на перото
     *
     * @param stdClass $rec
     * @return array
     */
    public function getLimitAccounts($rec)
    {
    	$rec = $this->fetchRec($rec, 'canStore,canConvert');
    	
    	$accounts = '';
    	if($rec->canStore == 'yes'){
    		$accounts .= ($rec->canConvert == 'yes') ? '321,323,61101' : '321,323';
    	} else {
    		$accounts .= ($rec->canConvert == 'yes') ? '61101,60201' : '60201';
    	}
    	
    	$accounts = arr::make($accounts, TRUE);
    	
    	return $accounts;
    }
    
    
    /**
     * Намира цена на артикул по неговия код към текущата дата, в следния ред
     * 
     * 1. Мениджърска себестойност
     * 2. Ако е вложим и има заместващи, себестойността на този с най-голямо к-во във всички складове
     * 3. Ако е производим и има търговска рецепта, цената по нея
     * 4. Ако е складируем - средната му цена във всички складове
     * 5. Ако не открие връща NULL
     * 
     * @param string $code
     * @param boolean $onlyManager
     * @return NULL|double $primeCost
     */
    public static function getPrimeCostByCode($code, $onlyManager = FALSE)
    {
    	// Имали такъв артикул?
    	$product = self::getByCode($code);
    	if(!$product) return NULL;
    	
    	$productId = $product->productId;
    
    	// Мениджърската му себестойност, ако има
    	$primeCost = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $productId);
    	if(!empty($primeCost)) return $primeCost;
    	
    	if($onlyManager === TRUE) return;
    	
    	$pRec = cat_Products::fetch($productId, 'canConvert,canManifacture,canStore');
    	
    	// Ако е вложим
    	if($pRec->canConvert == 'yes'){
    		
    		// Кои са му еквивалентните
    		$similar = planning_ObjectResources::getEquivalentProducts($productId);
    		
    		// Подреждане на еквивалентните му, по к-то им във всички складове
    		if(count($similar)){
    			$orderArr = array();
    			foreach ($similar as $k => $pId){
    				if($k == $productId) continue;
    				$query = store_Products::getQuery();
    				$query->where("#productId = {$k}");
    				$query->XPR('sum', 'double', 'SUM(#quantity)');
    				$sum = $query->fetch()->sum;
    				$orderArr["{$sum}"] = $k;
    			}
    			
    			krsort($orderArr);
    			$topKey = $orderArr[key($orderArr)];
    			
    			// Връщане на себестойността на този с най-голямо количество
    			if(!empty($topKey)){
    				$primeCost = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $topKey);
    				if(!empty($primeCost)) return $primeCost;
    			}
    		}
    	}
    	
    	// Ако е производим, и има търговска рецепта, цената по нея
    	if($pRec->canManifacture == 'yes'){
    		$bomId = cat_Products::getLastActiveBom($productId, 'sales');
    		if(!empty($bomId)){
    			$primeCost = cat_Boms::getBomPrice($bomId, 1, 0, 0, NULL, price_ListRules::PRICE_LIST_COST);
    			if(!empty($primeCost)) return $primeCost;
    		}
    	}
    	
    	// Ако е складируем, средната му цена му във всички складове
    	if($pRec->canStore == 'yes'){
    		$primeCost = cat_Products::getWacAmountInStore(1, $productId, NULL);
    		if(!empty($primeCost)) return $primeCost;
    	}
    	
    	// Ако нищо не намери
    	return NULL;
    }
    
    
    /**
     * Колко е толеранса
     * 
     * @param int $id          - ид на артикул
     * @param double $quantity - к-во
     * @return double|NULL     - толеранс или NULL, ако няма
     */
    public static function getTolerance($id, $quantity)
    {
    	// Ако има драйвър, питаме него за стойността
    	if($Driver = static::getDriver($id)){
    		$tolerance = $Driver->getTolerance($id, $quantity);
    		return (!empty($tolerance)) ? $tolerance : NULL;
    	}
    	
    	return NULL;
    }
    
    
    /**
     * Колко е срока на доставка
     *
     * @param int $id          - ид на артикул
     * @param double $quantity - к-во
     * @return double|NULL     - срока на доставка в секунди или NULL, ако няма
     */
    public static function getDeliveryTime($id, $quantity)
    {
    	// Ако има драйвър, питаме него за стойността
    	if($Driver = static::getDriver($id)){
    		$term = $Driver->getDeliveryTime($id, $quantity);
    		return (!empty($term)) ? $term : NULL;
    	}
    	
    	return NULL;
    }
    
    
    /**
     * Връща минималното количество за поръчка
     *
     * @param int|NULL $id - ид на артикул
     * @return double|NULL - минималното количество в основна мярка, или NULL ако няма
     */
    public static function getMoq($id = NULL)
    {
    	// Ако има драйвър, питаме го за МКП-то
    	if(!isset($id)) return NULL;
    	
    	if($Driver = static::getDriver($id)){
    		$moq = $Driver->getMoq($id);
    		return (!empty($moq)) ? $moq : NULL;
    	}
    	 
    	return NULL;
    }
    
    
    /**
     * Допълнителните условия за дадения продукт,
     * които автоматично се добавят към условията на договора
     *
     * @param mixed $rec       - ид или запис на артикул
     * @param double $quantity - к-во
     * @return array           - Допълнителните условия за дадения продукт
     */
    public static function getConditions($rec, $quantity)
    {
    	// Ако има драйвър, питаме него за стойността
    	if($Driver = static::getDriver($rec)){
    		$rec = self::fetchRec($rec);
    		
    		return $Driver->getConditions($rec, $quantity);
    	}
    	 
    	return array();
    }
    
    
    /**
     * Връща хеша на артикула (стойност която показва дали е уникален)
     *
     * @param mixed $rec     - ид или запис на артикул
     * @return NULL|varchar  - Допълнителните условия за дадения продукт
     */
    public static function getHash($rec)
    {
    	// Ако има драйвър, питаме него за стойността
    	if($Driver = static::getDriver($rec)){
    		$rec = self::fetchRec($rec);
    
    		return $Driver->getHash(self::getSingleton(), $rec);
    	}
    
    	return NULL;
    }
    
    
    /**
     * Дали артикула се среща в детайла на активни договори (Покупка и продажба)
     * 
     * @param int $productId
     * @return boolean
     */
    private function isUsedInActiveDeal($productId)
    {
    	$productId = (is_object($productId)) ? $productId->id : $productId;
    	
    	foreach (array('sales_SalesDetails', 'purchase_PurchasesDetails') as $Det){
    		$Detail = cls::get($Det);
    		$dQuery = $Detail->getQuery();
    		$dQuery->EXT('state', $Detail->Master, "externalName=state,externalKey={$Detail->masterKey}");
    		$dQuery->where("#productId = {$productId} AND #state = 'active'");
    		$dQuery->show('id');
    		$dQuery->limit(1);
    		
    		if($dQuery->fetch()) return TRUE;
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Преди затваряне/отваряне на записа
     * 
     * @param core_Mvc $mvc    - мениджър
     * @param stdClass $rec    - запис
     * @param string $newState - ново състояние
     * @return mixed
     */
    protected static function on_BeforeChangeState(core_Mvc $mvc, &$rec, $newState)
    {
    	if($newState == 'closed' && $mvc->isUsedInActiveDeal($rec)){
    		core_Statuses::newStatus("Артикулът не може да бъде затворен, докато се използва в активни договори", 'error');
    		return FALSE;
    	}
    }
    
    
    /**
     * Изпълнява се преди оттеглянето на документа
     */
    protected static function on_BeforeReject(core_Mvc $mvc, &$res, $id)
    {
    	if($mvc->isUsedInActiveDeal($id)){
    		core_Statuses::newStatus("Артикулът не може да бъде оттеглен, докато се използва в активни договори", 'error');
    		return FALSE;
    	}
    }
}
