<?php



/**
 * Регистър на продуктите
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class cat_Products extends core_Master {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf,cat_ProductAccRegIntf,techno_SpecificationFolderCoverIntf,mp_ResourceSourceIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Артикули в каталога";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_SaveAndNew, plg_PrevAndNext, acc_plg_Registry, plg_Rejected, plg_State,
                     cat_Wrapper, plg_Sorting, bgerp_plg_Groups, plg_Printing, Groups=cat_Groups, doc_FolderPlg, plg_Select, plg_Search, bgerp_plg_Import';
    
    
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
    var $details = 'Packagings=cat_products_Packagings,Params=cat_products_Params,Files=cat_products_Files,PriceGroup=price_GroupOfProducts,PriceList=price_ListRules,AccReports=acc_ReportDetails,VatGroups=cat_products_VatGroups,Resources=mp_ObjectResources';
    
    
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
    var $listFields = 'name,code,groups,tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'cat,ceo';
    
    
    /**
     * Кой може да добавя?
     */
    var $canAdd = 'cat,ceo';
    
    
    /**
     * Кой може да го разгледа?
     */
    var $canList = 'powerUser';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'cat,ceo';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'cat,ceo';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canWrite = 'ceo,cat';
    
    
    /**
     * Клас за елемента на обграждащия <div>
     */
    var $cssClass = 'folder-cover';
    
    
    /**  
     * Кой има право да променя системните данни?  
     */  
    var $canEditsysdata = 'ceo, cat';
    
    
    /**
     * Кой  може да групира "С избраните"?
     */
    var $canGrouping = 'ceo,cat';

	
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'cat/tpl/products/SingleProduct.shtml';
    
    
    /**
     * Кой има достъп до единичния изглед
     */
    var $canSingle = 'powerUser';
    
	
    /** 
	 *  Полета по които ще се търси
	 */
	var $searchFields = 'name, code';
	
	
	/**
	 * Шаблон (ET) за заглавие на продукт
	 * 
	 * @var string
	 */
	public $recTitleTpl = '[#name#] ( [#code#] )';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory,remember=info,width=100%');
		$this->FLD('code', 'varchar(64)', 'caption=Код, mandatory,remember=info,width=15em');
        $this->FLD('info', 'richtext(bucket=Notes)', 'caption=Детайли');
        $this->FLD('measureId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory,notSorting');
        $this->FLD('groups', 'keylist(mvc=cat_Groups, select=name, makeLinks)', 'caption=Групи,maxColumns=2,remember');
       	$this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Информация->Фото');
        
        $this->setDbUnique('code');
    }
    
    
    /**
     * Изпълнява се след подготовка на Едит Формата
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        if(!$data->form->rec->id && ($code = Mode::get('catLastProductCode'))) {
            if ($newCode = str::increment($code)) {
            	
                //Проверяваме дали има такъв запис в системата
                if (!$mvc->fetch("#code = '$newCode'")) {
                    $data->form->rec->code = $newCode;
                }
            }
        }
        
    	// Не може да се променят номенклатурите от формата
    	if($data->form->fields['lists']){
        	$data->form->setField('lists', 'input=none');
        }
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        //Проверяваме за недопустими символи
        if ($form->isSubmitted()){
        	$rec = &$form->rec;
            if (preg_match('/[^0-9a-zа-я\- _]/iu', $rec->code)) {
                $form->setError('code', 'Полето може да съдържа само букви, цифри, тирета, интервали и долна черта!');
            }
           
        	if($rec->code) {
    				
    			// Проверяваме дали има продукт с такъв код (като изключим текущия)
	    		$check = $mvc->checkIfCodeExists($rec->code);
	    		if($check && ($check->productId != $rec->id)
	    			|| ($check->productId == $rec->id && $check->packagingId != $rec->packagingId)) {
	    			$form->setError('code', 'Има вече артикул с такъв код!');
			       }
    		}
        }
                
        if (!$form->gotErrors()) {
            if(!$form->rec->id && ($code = Request::get('code', 'varchar'))) {
                Mode::setPermanent('catLastProductCode', $code);
            }    
        }
    }
    
    
    /**
     * Преди запис на продукт
     */
    public static function on_BeforeSave($mvc, $res, $rec)
    {
    	if(isset($rec->csv_measureId) && strlen($rec->csv_measureId) != 0){
    		$rec->measureId = cat_UoM::fetchField("#name = '{$rec->csv_measureId}'", "id");
    	}
    	
    	if(isset($rec->csv_groups) && strlen($rec->csv_groups) != 0){
    		$rec->groups = cat_Groups::getKeylistBySysIds($rec->csv_groups);
    	}
    	
    	if($rec->id){
    		$oldRec = $mvc->fetch($rec->id);
    		
    		// Старите мета данни
    		$rec->oldGroups = $oldRec->groups;
    		$rec->oldName = $oldRec->name;
    		$rec->oldCode = $oldRec->code;
    	}
    }
    
    
    /**
     * Извлича мета данните на продукт според групите в които участва
     * 
     * @param mixed $groups - групи в които участва
     */
    private static function getMetaData($groups)
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
     * Добавяне в таблицата на линк към детайли на продукта. Обр. на данните
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal ($mvc, $row, $rec, $fields = array())
    {
        if($fields['-single']) {
        	
        	// Ако продукта няма основна опаковка, удебеляваме мярката му
        	if(!$mvc->Packagings->fetch("#productId = '{$rec->id}' AND #isBase = 'yes'")){
        		$row->measureId = "<b>{$row->measureId}</b>";
        	}
        	
        	// извличане на мета данните според групите
    		if($meta = $mvc->getMetaData($rec->groups)){
    			$Groups = cls::get(cat_Groups);
        		$row->meta = $Groups->getFieldType('meta')->toVerbal($meta);
    		}
    		
            // fancybox ефект за картинките
            $Fancybox = cls::get('fancybox_Fancybox');
          
            $tArr = array(200, 150);
            $mArr = array(600, 450);
           
            $images_fields = array('image1',
                'image2',
                'image3',
                'image4',
                'image5');
            
            foreach ($images_fields as $image) {
                if ($rec->{$image} == '') {
                    $row->{$image} = NULL;
                } else {
                    $row->{$image} = $Fancybox->getImage($rec->{$image}, $tArr, $mArr);
                }
            }
            // ENDOF fancybox ефект за картинките
        }
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->FNC('order', 'enum(alphabetic=Азбучно,last=Последно добавени)',
            'caption=Подредба,input,silent,remember');

        $data->listFilter->FNC('groupId', 'key(mvc=cat_Groups,select=name,allowEmpty)',
            'placeholder=Всички групи,caption=Група,input,silent,remember');
		
        $data->listFilter->FNC('meta', 'enum(all=Свойства,canSell=Продаваеми,
        						canBuy=Купуваеми,
        						canStore=Складируеми,
        						canConvert=Вложими,
        						fixedAsset=ДМА,
        						canManifacture=Производими,
        						materials=Материали)', 'input');
		
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->showFields = 'search,order,meta,groupId';
        $data->listFilter->input('order,groupId,search,meta', 'silent');
        
    	// Подредба
        if($data->listFilter->rec->order == 'alphabetic' || !$data->listFilter->rec->order) {
            $data->query->orderBy('#name');
        } elseif($data->listFilter->rec->order == 'last') {
            $data->query->orderBy('#createdOn=DESC');
        }
        
        if ($data->listFilter->rec->groupId) {
            $data->query->where("#groups LIKE '%|{$data->listFilter->rec->groupId}|%'");
        }
        
        if ($data->listFilter->rec->meta && $data->listFilter->rec->meta != 'all') {
        	$groupIds = cat_Groups::getByMeta($data->listFilter->rec->meta);
        	$data->query->likeKeylist('groups', keylist::fromArray($groupIds));
        }
    }


    /**
     * Перо в номенклатурите, съответстващо на този продукт
     *
     * Част от интерфейса: acc_RegisterIntf
     */
    static function getItemRec($objectId)
    {
        $result = NULL;
        $self = cls::get(__CLASS__);
        
        if ($rec = self::fetch($objectId)) {
            $result = (object)array(
                'num' => "A" . $rec->code,
                'title' => $rec->name,
                'uomId' => $rec->measureId,
                'features' => array()
            );
            
            if($rec->groups){
            	$groups = strip_tags($self->getVerbal($rec, 'groups'));
            	$result->features = $result->features + arr::make($groups, TRUE);
            }
            
            $result->features = $self->Params->getFeatures($self, $objectId, $result->features);
        }
        
        return $result;
    }
    
    
    /**
     * @see acc_RegisterIntf::itemInUse()
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
    }
    
    
    /**
     * Връща масив от продукти отговарящи на зададени мета данни:
     * canSell, canBuy, canManifacture, canConvert, fixedAsset, canStore
     * 
     * @param mixed $properties - комбинация на горе посочените мета 
     * 							  данни или като масив или като стринг
     * @param int $limit 		- колко опции да върне
     * @return array $products - продукти отговарящи на условието, ако не са
     * 							 зададени мета данни връща всички продукти
     */
    public static function getByProperty($properties, $limit = NULL)
    {
    	$products = array();
    	$metaArr = arr::make($properties);
    	
    	if(!$allProducts = core_Cache::get('cat_Products', "productsMeta")){
    		$allProducts = static::cacheMetaData();
    	}
    	
    	if(count($metaArr)){
    		foreach ($metaArr as $meta){
    			$products = $products + $allProducts[$meta];
    		}
    	}
    	
    	// Премахват се тези продукти до които потребителя няма достъп
    	self::unsetUnavailableProducts($products);
    	
    	// Ако е посочен лимит, връщаме първите $limit продукти
    	if(isset($limit)){
    		$products = array_slice($products, 0, $limit, TRUE);
    	}
    	
    	return $products;
    }
    
    
    /**
     * Помощна ф-я премахваща от списъка с продукти отговарящи на
     * някакви мета данни тези до които потребителя няма достъп.
     * Връща се подможество състоящо се от тези продукти от подадените,
     * до които има достъп потребителя и има достъп до поне една тяхна група
     * 
     * @param array $products - продукти отговарящи на някакви критерии
     */
    private static function unsetUnavailableProducts(&$products)
    {
    	// Ако няма продукти 
    	if(!count($products)) return;
    	
    	// Извличане на групите до които текущия потребител има достъп
    	$allowedGroups = array();
    	$groupQuery = cat_Groups::getQuery();
    	cat_Groups::restrictAccess($groupQuery);
    	
    	// Запомнят се в един масив
    	while($gRec = $groupQuery->fetch()){
	    	$allowedGroups[$gRec->id] = $gRec->id;
    	}
    	
    	// Подготвяне във стринг на ид-та на продуктите
    	$productIds = implode(", ", array_keys($products));
    	
    	// Извличане на продукти
    	$accessibleProducts = array();
    	$query = static::getQuery();
    	
    	// До които потребителя има достъп
    	static::restrictAccess($query);
    	
    	// И ид-та им присъстват в $products
    	$query->in('id', $productIds);
    	
    	// За всякя заявка
    	while($rec = $query->fetch()){
    		
    		// Натрупват се всички достъпни продукти
    		$accessibleProducts[$rec->id] = $rec->id;
    		
    		// Флаг дали потребителя има достъп до поне една група на продукта
    		$flag = FALSE;
    		
    		// Ако има достъп до поне една група флага се сетва на TRUE
    		$groups = keylist::toArray($rec->groups);
    		foreach ($groups as $gr){
    			if(isset($allowedGroups[$gr])){
    				$flag = TRUE;
    				break;
    			}
    		}
    		
    		// Ако никоя от групите на продукта не е достъпна и продукта не
    		// е шернат до потребителя, той се премахва
    		if(!$flag && !keylist::isIn(core_Users::getCurrent(), $rec->shared)){
    			unset($products[$rec->id]);
    		}
    	}
    	
    	// Накрая се връща общата част от всички продукти и тези
    	// до които има достъп потребителя
    	$products = array_intersect_key($products, $accessibleProducts);
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
	 * 	     meta['fixedAsset']     - дали е ДМА
     * 	-> packagingRec - записа на опаковката, ако е зададена
     * 	-> packagings - всички опаковки на продукта, ако не е зададена
     */					
    public static function getProductInfo($productId, $packagingId = NULL)
    {
    	// Ако няма такъв продукт връщаме NULL
    	if(!$productRec = static::fetch($productId)) {
    		return NULL;
    	}
    	
    	$res = new stdClass();
    	$res->productRec = $productRec;
    	if($grRec = cat_products_VatGroups::getCurrentGroup($productId)){
    		$res->productRec->vatGroup = $grRec->title;
    	}
    	
    	// Добавяне на мета данните за продукта
    	if($meta = explode(',', self::getMetaData($productRec->groups))){
	    	foreach($meta as $value){
	    		$res->meta[$value] = TRUE;
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
     *  Проверява дали съществува продукт с такъв код, Кода и ЕАН-то на продукта както и тези на опаковките им
     *  трябва да са уникални
     *  
     *  @param string $code - Код/Баркод на продукт
     *  @return boolean int/FALSE - id на продукта с такъв код или
     *  FALSE ако няма такъв продукт
     */
    function checkIfCodeExists($code)
    {
    	if($info = cat_Products::getByCode($code)) {
    		return $info;
    	} else {
    		return FALSE;
    	}
    }
    
    
    /**
     * Връща всички продукти които са в посочените групи/група 
     * зададени, чрез техни systemId-та
     * 
     * @param mixed $group - sysId (стринг) или масив от sysId-та на групи
     * @return array $result - Продукти отговарящи на посочената група/групи
     */
    public static function getByGroup($group)
    {
    	if(!is_array($group)){
    		$group = array($group);
    	}
    	
    	$result = array();
    	$query = static::getQuery();
    	$groupIds = cat_Groups::getKeylistBySysIds($group);
    	$query->likeKeylist('groups', $groupIds, TRUE);
    	
    	while($rec = $query->fetch()){
	    	$result[$rec->id] = static::getTitleById($rec->id);
	    }
	    
	    return $result;
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
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        if($rec->groups) {
            $mvc->updateGroupsCnt = TRUE;
        }
        
        // Ако има промяна в групите, името или кода инвалидираме кеша
    	if($rec->oldGroups != $rec->groups || $rec->oldName != $rec->name || $rec->oldCode != $rec->code) {
            core_Cache::remove('cat_Products', "productsMeta");
        }
    }
    
    
	/**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    static function on_Shutdown($mvc)
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
     * Подготовка за рендиране на единичния изглед
     */
    public static function on_AfterPrepareSingle($mvc, $data)
    {
        // Ако не е зададено файл
        if (!$fileHnd = $data->rec->photo) {
            
            // Вземаме файла от прикачените файлове на детайла
            $fileHnd = cat_products_Files::getImgFh($data->rec->id);
        }
        
        // Ако има манипулатор на файл
        if ($fileHnd) {
            
            // Fancy ефект за картинката
            $Fancybox = cls::get('fancybox_Fancybox');
            
            // Размер на thumbnail' а
            $tArr = array(200, 150);
            
            // Максималния размер на изображението
            $mArr = array(600, 450);
            
            // Вземаме тумбнаил на файла
            $data->row->image = $Fancybox->getImage($fileHnd, $tArr, $mArr);
        }
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$file = "cat/csv/Products.csv";
    	$fields = array( 
	    	0 => "name", 
	    	1 => "code", 
	    	2 => "csv_measureId", 
	    	3 => "csv_groups",
	    	4 => "access");
    	
    	$cntObj = csv_Lib::importOnce($this, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
    
    
    /**
     * Връща продуктите, които могат да се продават на посочения клиент
     *
     * @return array() - масив с опции, подходящ за setOptions на форма
     */
    public function getProducts($customerClass, $customerId, $datetime = NULL, $properties, $limit = NULL)
    {
    	return static::getByProperty($properties, $limit);
    }
    
    
    /**
     * Връща цената по себестойност на продукта
     * @return double
     */
    public function getSelfValue($productId, $packagingId = NULL, $quantity = NULL, $date = NULL)
    {
    	// Ценоразпис себестойност
    	$listId = price_ListRules::PRICE_LIST_COST;
    	price_ListToCustomers::canonizeTime($date);
    	
    	return price_ListRules::getPrice($listId, $productId, $packagingId, $date);
    }
    
    
	/**
     * Връща масив със всички опаковки, в които може да участва един продукт
     */
    public function getPacks($productId)
    {
    	expect($rec = $this->fetch($productId));
    	$options = array('' => $this->getVerbal($rec, 'measureId'));
    	
    	$query = cat_products_Packagings::getQuery();
    	$query->where("#productId = {$productId}");
    	$query->show("packagingId");
    	while($rec = $query->fetch()){
    		$options[$rec->packagingId] = cat_Packagings::getTitleById($rec->packagingId);
    	}
    	
    	return $options;
    }
    
    
    /**
     * Ъпдейтва мета данните на всички продукти
     * участващи в дадена група
     * @param int $groupId - ид на променената група
     */
    public static function updateMetaData($groupId)
    {
     	// За всички продукти участващи в групата
    	$query = static::getQuery();
     	$query->like('groups', "|{$groupId}|");
     	while($rec = $query->fetch()){
     		
     		// Презаписване на продукта, което ще преизчисли мета данните му
     		static::save($rec);
     	}
    }
    
    
    /**
     * Записва в кеша продуктите групирани по техните мета данни
     */
    public static function cacheMetaData()
    {
    	$cache = array();
    	$tmp = array();
    	$metas = array('canSell', 'canBuy', 'canStore', 'canConvert', 'fixedAsset', 'canManifacture', 'costs');
    	foreach($metas as $meta){
    		$catGroups = cat_Groups::getByMeta($meta);
    		$keylist = keylist::fromArray($catGroups);
    		
    		$products = array();
    		$query = static::getQuery();
	    	$query->likeKeylist('groups', $keylist);
	    	$query->where("#state != 'rejected'");
	    	
	    	while($rec = $query->fetch()){
	    		if(!array_key_exists($rec->id, $tmp)){
	    			$tmp[$rec->id] = static::getTitleById($rec->id, FALSE);
	    		}
	    		
	    		$products[$rec->id] = $tmp[$rec->id];
	    	}
	    	
	    	$cache[$meta] = $products;
    	}
    	
    	core_Cache::set('cat_Products', "productsMeta", $cache, 2880, array('cat_Products'));
    	
    	return $cache;
    }
    
    
    /**
     * Връща стойноства на даден параметър на продукта, ако я има
     * @param int $id - ид на продукт
     * @param string $sysId - sysId на параметър
     */
    public function getParam($id, $sysId)
    {
    	expect(static::fetch($id));
    	
    	return cat_products_Params::fetchParamValue($id, $sysId);
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
    		$weight = $this->getParam($productId, 'transportWeight');
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
    		$volume = $this->getParam($productId, 'transportVolume');
    	}
    	
    	return $volume;
    }
    
    
    /**
     * Предефиниране на метода getTitleById
     * 
     * @param int $id - ид на продукт
     * @param boolean $escaped - дали да е ескейпнато
     * @param string(2) $lang - език
     * @return string $title - заглавието на продукта, ако има параметър за име на
     * зададения език, връща него.
     */
    public static function getTitleById($id, $escaped = TRUE, $full = FALSE, $lang = 'bg')
    {
     	// Ако езика е различен от българския
    	if($lang != 'bg'){
     		
    		// Проверяваме имали сетнат параметър "title<LG>" за името на продукта
     		$paramSysId = "title" . strtoupper($lang);
     		$title = cat_products_Params::fetchParamValue($id, $paramSysId);
     		
     		// ако има се връща
     		if($title) return $title;
     	}
     	
     	// Ако няма зададено заглавие за този език, връща дефолтното
     	return parent::getTitleById($id, $escaped);
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
    		$arr['classId'] = cat_Packagings::getClassId();
    		$arr['id'] = $basePack->packagingId;
    	} else {
    		$measureId = $this->fetchField($id, 'measureId');
    		$arr['name'] = cat_UoM::getTitleById($measureId);
    		$arr['quantity'] = 1;
    		$arr['classId'] = cat_UoM::getClassId();
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
    	if(!mp_ObjectResources::fetch("#classId = '{$this->getClassId()}' AND #objectId = {$id}")){
    		
    		return TRUE;
    	} 
    	
    	return FALSE;
    }
}
