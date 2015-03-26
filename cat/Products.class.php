<?php



/**
 * Регистър на продуктите
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
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf,cat_ProductAccRegIntf,planning_ResourceSourceIntf,doc_AddToFolderIntf,acc_RegistryDefaultCostIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Артикули в каталога";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_SaveAndNew, plg_Clone, doc_DocumentPlg, plg_PrevAndNext, acc_plg_Registry, plg_State,
                     cat_Wrapper, plg_Sorting, doc_ActivatePlg, doc_plg_BusinessDoc, cond_plg_DefaultValues, bgerp_plg_Groups, plg_Printing, plg_Select, plg_Search, bgerp_plg_Import';
    
    
    /**
     * Име на полето за групите на продуктите.
     * Използва се за целите на bgerp_plg_Groups
     */
    var $groupField = 'groups';


    /**
     * Име на полето с групите, в които се намира продукт. Използва се от groups_Extendable
     * 
     * @var string
     */
    var $groupsField = 'groups';

    
    /**
     * Детайла, на модела
     */
    var $details = 'Packagings=cat_products_Packagings,Prices=cat_PriceDetails,AccReports=acc_ReportDetails,Resources=planning_ObjectResources,Jobs=planning_Jobs';
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '301,302,304,305,306,309,321,323';
    
    
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
     * Наименование на единичния обект
     */
    var $singleTitle = "Артикул";
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/wooden-box.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name,code,groups,folderId,createdOn,createdBy';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой може да добавя?
     */
    var $canAdd = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой може да добавя?
     */
    var $canClose = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой може да го разгледа?
     */
    var $canList = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canWrite = 'cat,ceo,sales,purchase';
    
    
    /**  
     * Кой има право да променя системните данни?  
     */  
    var $canEditsysdata = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой  може да групира "С избраните"?
     */
    var $canGrouping = 'cat,ceo,sales,purchase';

	
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'cat/tpl/products/SingleProduct.shtml';
    
    
    /**
     * Кой има достъп до единичния изглед
     */
    var $canSingle = 'cat,ceo,sales,purchase';
    
	
    /** 
	 *  Полета по които ще се търси
	 */
	var $searchFields = 'name, code';
	
	
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
    			
				if($code = Mode::get('catLastProductCode')) {
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
        
        if (!$form->gotErrors()) {
            if(!$form->rec->id && ($rec->code)) {
                Mode::setPermanent('catLastProductCode', $code);
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
		$rec->meta = $this->getFieldType('meta')->fromVerbal($defMetas);
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
     * Извлича мета данните на продукт според групите в които участва
     * 
     * @param mixed $groups - групи в които участва
     */
    public static function getMetaData($groups)
    {
    	if($groups){
    		$meta = array();
    		if(!is_array($groups)){
    			 $groups = keylist::toArray($groups);
    		}
		    foreach($groups as $grId){
		    	$grRec = cat_Groups::fetch($grId);
		    	if($grRec->meta){
		    		$arr = explode(",", $grRec->meta);
		    		$meta = array_merge($meta, array_combine($arr, $arr));
		    	}
		    }
		    
		    return implode(',', $meta);
    	}
    	
    	return '';
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
        		$data->query->orderBy('#name');
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
            
            if($rec->groups){
            	$groups = strip_tags($self->getVerbal($rec, 'groups'));
            	$result->features = $result->features + arr::make($groups, TRUE);
            }
           
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
     * @return stdClass $res - Информация за намерения продукт
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
    	);
    	
    	$cntObj = csv_Lib::importOnce($this, $file, $fields);
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
    	
    	$products = array();
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
    		$products[$rec->id] = $this->getRecTitle($rec, FALSE);
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
    			$price = new stdClass();
    			$price->price = ($amounts->base + $quantity * $amounts->prop) / $quantity;
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
    		$weight = $params['transportVolume'];
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
     * Можели обекта да се добави като ресурс?
     *
     * @param int $id - ид на обекта
     * @return boolean - TRUE/FALSE
     */
    public function canHaveResource($id)
    {
    	// Всеки артикул може да присъства само веднъж като ресурс
    	if(!planning_ObjectResources::fetch("#classId = '{$this->getClassId()}' AND #objectId = {$id}")){
    		$pInfo = $this->getProductInfo($id);
    		
    		// Може да се добавя ресурс само към артикули, които са материали, ДА или вложими
    		if(isset($pInfo->meta['canConvert']) || isset($pInfo->meta['fixedAsset'])){
    			
    			return TRUE;
    		}
    	} 
    	
    	return FALSE;
    }
    
    
    /**
     * Връща дефолт информация от източника на ресурса
     *
     * @param int $id - ид на обекта
     * @return stdClass $res  - обект с информация
     * 		o $res->name      - име
     * 		o $res->measureId - име мярка на ресурса (@see cat_UoM)
     * 		o $res->type      -  тип на ресурса (material,labor,equipment)
     */
    public function getResourceSourceInfo($id)
    {
    	$res = new stdClass();
    	$pInfo = $this->getProductInfo($id);
    	
    	$res->name = $pInfo->productRec->name;
    	$res->measureId = $pInfo->productRec->measureId;
    	
    	// Ако артикула е ДМА, ще може да се избират само ресурси - оборудване
    	if(isset($pInfo->meta['fixedAsset'])){
    		$res->type = 'equipment';
    	}
    	 
    	// Ако артикула е материал, ще може да се избират само ресурси - материали
    	if(isset($pInfo->meta['canConvert'])){
    		$res->type = 'material';
    	}
    	
    	$res->type = (empty($res->type)) ? FALSE : $res->type;
    	
    	return $res;
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
    	$title = $this->getTitleById($rec->id);
    	
    	if(!Mode::is('printing') && !Mode::is('text', 'xhtml')){
    		$title = ht::createLinkRef($title, array($this, 'single', $rec->id));
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
	 * @param enum(auto,detailed,short) $mode - режим на показване
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
    		 $data->toolbar->addBtn('Нов запис', array($mvc, 'add', 'innerClass' => cat_GeneralProductDriver::getClassId()), 'order=1', 'ef_icon = img/16/shopping.png,title=Създаване на нова стока');
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
    	if($action == 'edit' && isset($rec)){
    		if($rec->state == 'active'){
    			$res = $mvc->getRequiredRoles('edit');
    		}
    	}
    	
    	if($action == 'add' && isset($rec)){
    		if(isset($rec->originId)){
    			$document = doc_Containers::getDocument($rec->originId);
    			if(!$document->haveInterface('marketing_InquiryEmbedderIntf')){
    				$res = 'no_one';
    			}
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
    			$data->toolbar->addBtn("Рецепта", array('cat_Boms', 'add', 'productId' => $data->rec->id, 'originId' => $data->rec->containerId, 'ret_url' => TRUE), 'ef_icon = img/16/legend.png,title=Създаване на нова технологична рецепта');
    		}
    	}
    	
		if($mvc->haveRightFor('close', $data->rec)){
			if($data->rec->state == 'closed'){
				$data->toolbar->addBtn("Активиране", array($mvc, 'changeState', $data->rec->id, 'ret_url' => TRUE), 'ef_icon = img/16/lightbulb.png,title=Активиранe на артикула,warning=Сигурнили сте че искате да активирате артикула, това ще му активира перото');
			} elseif($data->rec->state == 'active'){
				$data->toolbar->addBtn("Приключване", array($mvc, 'changeState', $data->rec->id, 'ret_url' => TRUE), 'ef_icon = img/16/lightbulb_off.png,title=Затваряне артикула и перото му,warning=Сигурнили сте че искате да приключите артикула, това ще му затвори перото');
			}
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
     * Затваря/отваря артикула и перото му
     */
    public function act_changeState()
    {
    	$this->requireRightFor('close');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('close', $rec);
    	
    	$state = ($rec->state == 'closed') ? 'active' : 'closed';
    	$rec->exState = $rec->state;
    	$rec->state = $state;
    	 
    	$this->save($rec, 'state');
    	
    	return followRetUrl();
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
    	if($itemsInBalanceBefore){
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
    	// Документа не може да се създава  в нова нишка, ако е възоснова на друг
    	if(!empty($data->form->toolbar->buttons['save']) && $data->form->rec->state == 'active'){
    		$data->form->toolbar->renameBtn('save', 'Запис');
    	}
    }
}
