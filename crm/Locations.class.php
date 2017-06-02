<?php



/**
 * Локации
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class crm_Locations extends core_Master {
    
	
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'cms_ObjectSourceIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Локации на контрагенти";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools2, crm_Wrapper, plg_Rejected, plg_RowNumbering, plg_Sorting, plg_Search, plg_Printing';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "title, contragent=Контрагент, type, createdOn, createdBy";


    /**
     * Кой може да чете и записва локации?
     */
    var $canRead  = 'ceo';
    
    
    /**
     *  Поле за rowTools
     */
    //var $rowToolsField = 'tools';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'powerUser';
    
    
    /**
     * Кой има достъп до единичния изглед
     */
    var $canSingle = 'powerUser';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'powerUser';
    
	
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Локация";
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/location_pin.png';
    
    
    /**
	 * Детайли към локацията
	 */
	var $details = 'routes=sales_Routes';
	
	
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsSingleField = 'title';

    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'crm/tpl/SingleLayoutLocation.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title, countryId, place, address, email, tel';
    

    /**
     * Записи за обновяване
     */
    protected $updatedRecs = array();
    
    
    /**
     * Кой може да създава продажба за локацията
     */
    public $canCreatesale = 'ceo,sales';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('contragentCls', 'class(interface=crm_ContragentAccRegIntf)', 'caption=Собственик->Клас,input=hidden,silent');
        $this->FLD('contragentId', 'int', 'caption=Собственик->Id,input=hidden,silent');
        $this->FLD('title', 'varchar', 'caption=Наименование');
        $this->FLD('type', 'varchar(32)', 'caption=Тип,mandatory');
        $this->FLD('countryId', 'key(mvc=drdata_Countries, select=commonName, selectBg=commonNameBg, allowEmpty)', 'caption=Държава,class=contactData,mandatory');
        $this->FLD('place', 'varchar(64)', 'caption=Град,oldFieldName=city,class=contactData');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код,class=contactData');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,class=contactData');
        $this->FLD('mol', 'varchar(32)', 'caption=Отговорник');
        $this->FLD('tel', 'drdata_PhoneType', 'caption=Телефони,class=contactData');
        $this->FLD('email', 'emails', 'caption=Имейли,class=contactData');
        $this->FLD('gln', 'gs1_TypeEan(gln)', 'caption=GLN код');
        $this->FLD('gpsCoords', 'location_Type(geolocation=mobile)', 'caption=Координати');
        $this->FLD('image', 'fileman_FileType(bucket=location_Images)', 'caption=Снимка');
        $this->FLD('comment', 'richtext(bucket=Notes, rows=4)', 'caption=@Информация');

        $this->setDbUnique('gln');
        $this->setDbIndex('contragentId');
    }
    
    
    /**
     * Връща стринг с всички имейли за съответния обект
     * 
     * @param integer $clsId
     * @param integer $contragentId
     * 
     * @return string
     */
    public static function getEmails($clsId, $contragentId)
    {
        $resStr = '';
        
        $query = self::getQuery();
        $query->where(array("#contragentCls = '[#1#]'", $clsId));
        $query->where(array("#contragentId = '[#1#]'", $contragentId));
        
        while ($rec = $query->fetch()) {
            if (!trim($rec->email)) continue;
            
            $resStr .= ($resStr) ? ', ' . $rec->email : $rec->email;
        }
        
        return $resStr;
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $rec = $data->form->rec;
        
        $Contragents = cls::get($rec->contragentCls);
        expect($Contragents instanceof core_Master);
        
        $contragentRec = $Contragents->fetch($rec->contragentId);
        
        $data->form->setDefault('countryId', $contragentRec->country);
        $data->form->setDefault('place', $contragentRec->place);
        $data->form->setDefault('pCode', $contragentRec->pCode);
        
        $contragentTitle = $Contragents->getTitleById($contragentRec->id);



        $data->form->setSuggestions('type', self::getTypeSuggestions());
    }

    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	$data->form->title = core_Detail::getEditTitle($rec->contragentCls, $rec->contragentId, $mvc->singleTitle, $rec->id, 'на');
    }
    
    
     /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        if(!$rec->gpsCoords && $rec->image){
        	
        	if($gps = exif_Reader::getGps($rec->image)){
        		
        		// Ако има GPS коодинати в снимката ги извличаме
        		$rec->gpsCoords = $gps['lat'] . ", " . $gps['lon'];
        	}
        }
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec, $fields = NULL)
    {
    	$f = arr::make($fields, TRUE);
    	
    	if(!count($f) || isset($f['title']) && isset($f['countryId'])){
    		if(empty($rec->title)){
    			 
    			$lQuery = crm_Locations::getQuery();
    			$lQuery->where("#type = '{$rec->type}' AND #contragentCls = '{$rec->contragentCls}' AND #contragentId = '{$rec->contragentId}'");
    			$lQuery->XPR('count', 'int', 'COUNT(#id)');
    			$count = $lQuery->fetch()->count + 1;
    			 
    			$rec->title = $mvc->getVerbal($rec, 'type') . " ({$count})";
    		}
    	}
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        expect($rec->contragentId);
        
        if(isset($fields['-single'])){
        	if(isset($rec->image)) {
        		$Fancybox = cls::get('fancybox_Fancybox');
        		$row->image = $Fancybox->getImage($rec->image, array(188, 188), array(580, 580));
        	}
        	
        	if(!$rec->gpsCoords){
        		unset($row->gpsCoords);
        	}
        }
		
        if(isset($fields['-single']) || isset($fields['-list'])){
        	$cMvc = cls::get($rec->contragentCls);
        	$row->contragent = $cMvc->getHyperlink($rec->contragentId, TRUE);
        }
        
        if($rec->state == 'rejected'){
        	if($fields['-single']){
        		$row->headerRejected = ' state-rejected';
        	} else {
        		$row->ROW_ATTR['class'] .= ' state-rejected';
        	}
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    protected static function on_AfterSave($mvc, &$id, $rec, $fields = NULL)
    {
    	$mvc->routes->changeState($id);
    	
    	$mvc->updatedRecs[$id] = $rec;
    	
    	// Трябва да е тук, за да може да сработят on_ShutDown процесите
    	$mvc->updateNumbers($rec);
    }
    
    
    /**
     * Подготвя локациите на контрагента
     */
    public function prepareContragentLocations($data)
    {
        expect($data->masterId);
        expect($data->contragentCls = core_Classes::getId($data->masterMvc));
        
        $data->recs = static::getContragentLocations($data->contragentCls, $data->masterId);
        
        foreach ($data->recs as $rec) {
            $data->rows[$rec->id] = $this->recToVerbal($rec);
        }

        $data->TabCaption = 'Локации';
    }


    /**
     * Премахване на бутона за добавяне на нова локация от лист изгледа
     */
    protected static function on_BeforeRenderListToolbar($mvc, &$tpl, &$data)
    {
        $data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
   	 * Обработка на ListToolbar-a
   	 */
   	protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	
    	if($rec->address && $rec->place && $rec->countryId){
    		$address = "{$data->row->address},{$data->row->place},{$data->row->countryId}";
    	} elseif($rec->gpsCoords) {
    		$address = $rec->gpsCoords;
    	}
    	
    	if($rec->state != 'rejected'){
    		if($address){
    			$url = "https://maps.google.com/?daddr={$address}";
    			$data->toolbar->addBtn('Навигация', $url,  NULL, 'ef_icon=img/16/compass.png,target=_blank');
    		}
    		
    		if($mvc->haveRightFor('createsale', $rec)){
    			$data->toolbar->addBtn('Продажба', array($mvc, 'createSale', $rec->id, 'ret_url' => TRUE), 'ef_icon=img/16/cart_go.png,title=Създаване на нова продажба');
    		}
    	}
    }
    
    
    /**
     * Екшън за създаване на нова продажба
     */
    function act_CreateSale()
    {
    	$this->requireRightFor('createsale');
    	$id = Request::get('id', 'key(mvc=crm_Locations)');
    	$rec = $this->fetch($id);
    	$this->requireRightFor('createsale', $rec);
    	
    	// Форсираме папката на контрагента
    	$folderId = cls::get($rec->contragentCls)->forceCoverAndFolder($rec->contragentId);
    	if(sales_Sales::haveRightFor('add', (object)array('folderId' => $folderId))){
    		
    		return new Redirect(array('sales_Sales', 'add', 'folderId' => $folderId, 'deliveryLocationId' => $id));
    	}
    	
    	followRetUrl(NULL, 'Нямате достъп  до папката');
    }
    
    
    /**
     * Рендира данните
     */
    public function renderContragentLocations($data)
    {
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        
        $tpl->append(tr('Локации'), 'title');
        
        if(count($data->rows)) {
            foreach($data->rows as $id => $row) {
            	core_RowToolbar::createIfNotExists($row->_rowTools);
            	$block = new ET("<div>[#title#], [#type#]<!--ET_BEGIN tel-->, " . tr('тел') . ": [#tel#]<!--ET_END tel--><!--ET_BEGIN email-->, " . tr('имейл') . ": [#email#]<!--ET_END email--> <span style='position:relative;top:4px'>[#tools#]</span></div>");
            	$block->placeObject($row);
            	$block->append($row->_rowTools->renderHtml(), 'tools');
            	$block->removeBlocks();
            	
                $tpl->append($block, 'content');
            }
        } else {
            $tpl->append(tr("Все още няма локации"), 'content');
        }
        
        if(!Mode::is('printing')) {
            if ($data->masterMvc->haveRightFor('edit', $data->masterId)) {
                $url = array($this, 'add', 'contragentCls' => $data->contragentCls, 'contragentId' => $data->masterId, 'ret_url' => TRUE);
                $img = "<img src=" . sbf('img/16/add.png') . " width='16' height='16'>";
                $tpl->append(ht::createLink($img, $url, FALSE, 'title=Добавяне на нова локация'), 'title');
            }
        }
        
        return $tpl;
    }


    /**
     * След обработка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($requiredRoles == 'no_one') return;
        
    	if($rec->contragentCls) {
            $contragent = cls::get($rec->contragentCls);
            $requiredRoles = $contragent->getRequiredRoles($action, $rec->contragentId, $userId);
        }
        
    	if (($action == 'edit' || $action == 'delete') && isset($rec)) {
    		$cState = cls::get($rec->contragentCls)->fetchField($rec->contragentId, 'state');
            
        	if ($cState == 'rejected') {
                $requiredRoles = 'no_one';
            } 
        }
        
        if($action == 'createsale' && isset($rec)){
        	if(!sales_Sales::haveRightFor('add')){
        		$requiredRoles = 'no_one';
        	} elseif(!$mvc->haveRightFor('single', $rec)){
        		$requiredRoles = 'no_one';
        	} else {
        		if(!cls::get($rec->contragentCls)->haveRightFor('single', $rec->contragentId)){
        			$requiredRoles = 'no_one';
        		}
        	}
        }
    }


    /**
     * Връща масив със собствените локации
     */
    public static function getOwnLocations()
    {
        return static::getContragentOptions('crm_Companies', crm_Setup::BGERP_OWN_COMPANY_ID);
    }


    /**
     * Всички локации на зададен контрагент
     * 
     * @param mixed $contragentClassId име, ид или инстанция на клас-мениджър на контрагент
     * @param int $contragentId първичен ключ на контрагента (в мениджъра му)
     * @return array масив от записи crm_Locations
     */
    private static function getContragentLocations($contragentClassId, $contragentId)
    {
        expect($contragentClassId = core_Classes::getId($contragentClassId));
        
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->where("#contragentCls = {$contragentClassId} AND #contragentId = {$contragentId}");
        $query->where("#state != 'rejected'");
        
        $recs = array();
        
        while($rec = $query->fetch()) {
            $recs[$rec->id] = $rec;
        }

        return $recs;
    }
    

    /**
     * Наименованията на всички локации на зададен контрагент
     * 
     * @param mixed $contragentClassId име, ид или инстанция на клас-мениджър на контрагент
     * @param int $contragentId първичен ключ на контрагента (в мениджъра му)
     * @param boolean $intKeys - дали ключовите да са инт или стринг
     * @return array масив от наименования на локации, ключ - ид на локации
     */
    public static function getContragentOptions($contragentClassId, $contragentId, $intKeys = TRUE)
    {
        $locationRecs = static::getContragentLocations($contragentClassId, $contragentId);
        
        foreach ($locationRecs as &$rec) {
            $rec = static::getTitleById($rec->id, FALSE);
        }
	
        if(!$intKeys && count($locationRecs)){
        	$locationRecs = array_combine($locationRecs, $locationRecs);
        }
        
        return $locationRecs;
    }
    
    
    /**
     * GLN на всички локации на зададен контрагент + id-тата им
     * 
     * @param mixed $contragentClassId име, ид или инстанция на клас-мениджър на контрагент
     * @param int $contragentId първичен ключ на контрагента (в мениджъра му)
     * @return array масив от GLN на локации, ключ - ид на локации
     */
    public static function getContragentGLNs($contragentClassId, $contragentId)
    {
        $locationRecs = static::getContragentLocations($contragentClassId, $contragentId);
        
        $resRecs = array();
        foreach ($locationRecs as $rec) {
            $resRecs["$rec->id"] = $rec->gln;
        }
        unset($locationRecs);
        
        return $resRecs;
    }
    
    
    /**
     * Ф-я връщаща пълния адрес на локацията: Държава, ПКОД, град, адрес
     * 
     * @param int $id
     * @return core_ET $tpl 
     */
    public static function getAddress($id)
    {
    	expect($rec = static::fetch($id));
    	$row = static::recToVerbal($rec);
    	
    	$string = "";
    	if($rec->countryId){
    		$ourCompany = crm_Companies::fetchOurCompany();
    		if($ourCompany->country != $rec->countryId){
    			$string .= "{$row->countryId}, ";
    		}
    	}
    	
    	$string .= "{$row->pCode} {$row->place}, {$row->address}";
    	$string = trim($string, ",  ");
    	
    	return $string;
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->showFields = 'search';
    }
    
    
    /**
     * 
     * 
     * @param object $rec
     */
    protected static function updateRoutingRules($rec)
    {
        if (!$rec || !$rec->email) return ;
        
        if (!$rec->contragentCls || !$rec->contragentId) return ;
        
        $contragentCls = cls::get($rec->contragentCls);
        
        if (!($contragentCls instanceof crm_Persons) && !($contragentCls instanceof crm_Companies)) return ;
        
        return $contragentCls->createRoutingRules($rec->email, $rec->contragentId);
    }
    
    
    /**
     * 
     * 
     * @param object $rec
     */
    protected static function updateNumbers($rec)
    {
        if (!$rec || !$rec->tel) return ;
        
        if (!$rec->contragentCls || !$rec->contragentId) return ;
        
        $contragentCls = cls::get($rec->contragentCls);
        
        if (!($contragentCls instanceof crm_Persons) && !($contragentCls instanceof crm_Companies)) return ;
        
        $cRec = new stdClass();
        $cRec->id = $rec->contragentId;
        $cRec->tel = $rec->tel;
        
        return $contragentCls->updateNumbers($cRec);
    }
    
    
    /**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    static function on_Shutdown($mvc)
    {
        if(!empty($mvc->updatedRecs)) {
            foreach((array)$mvc->updatedRecs as $id => $rec) {
                $mvc->updateRoutingRules($rec);
            }
        }
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	// За да може да се създава нов търговски обект, трябва потребителя да има права за нова продажба, локация и маршрут
    	if(crm_Companies::haveRightFor('add') && crm_Locations::haveRightFor('add') && sales_Routes::haveRightFor('add')){
    		$data->toolbar->addBtn('Нов търговски обект', array($mvc, 'newSaleObject', 'ret_url' => TRUE), 'ef_icon=img/16/star_2.png,title=Създаване на нов търговски обект');
    	}
    }
    
    
    /**
     * Екшън създаващ нова фирма с локация към нея и търговски маршрут
     */
    function act_NewSaleObject()
    {
    	crm_Companies::requireRightFor('add');
    	crm_Locations::requireRightFor('add');
    	sales_Routes::requireRightFor('add');
    	
    	$form = cls::get('core_Form');
    	$form->title = "Добавяне на нов търговски обект";
    	
    	// Информация за фирмата
    	$form->FLD('name', 'varchar(255,ci)', 'caption=Фирма->Име,class=contactData,mandatory,remember=info,silent');
        $form->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Фирма->Държава,remember,class=contactData,mandatory');
        $form->FLD('vatId', 'drdata_VatType', 'caption=Фирма->ДДС (VAT) №,class=contactData');
        $form->FLD('uicId', 'varchar(26)', 'caption=Фирма->Национален №,class=contactData');
    	
        // Информация за локацията
        $form->FLD('title', 'varchar', 'caption=Локация->Наименование');
        $form->FLD('type', $this->fields['type']->type, 'caption=Локация->Тип,mandatory', array('suggestions' => self::getTypeSuggestions()));
        $form->FLD('place', 'varchar(64)', 'caption=Локация->Град,class=contactData');
        $form->FLD('pCode', 'varchar(16)', 'caption=Локация->П. код,class=contactData');
        $form->FLD('address', 'varchar(255)', 'caption=Локация->Адрес,class=contactData');
        $form->FLD('gpsCoords', 'location_Type(geolocation=mobile)', 'caption=Локация->Координати');
        $form->FLD('image', 'fileman_FileType(bucket=location_Images)', 'caption=Локация->Снимка');
        $form->FLD('comment', 'richtext(bucket=Notes, rows=4)', 'caption=Локация->Информация');
        
        // Информация за търговския маршрут
        $form->FLD('salesmanId', 'user(roles=sales|ceo,select=nick)', 'caption=Маршрут->Търговец,mandatory');
        $form->FLD('dateFld', 'date', 'caption=Маршрут->Начало,hint=Кога е първото посещение,mandatory');
        $form->FLD('repeat', 'time(suggestions=|1 седмица|2 седмици|3 седмици|1 месец)', 'caption=Маршрут->Период');
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис на търговския обект');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $form->setDefault('country', crm_Companies::fetchOwnCompany()->countryId);
        $form->input();
        
        // Ако е събмитната формата
        if($form->isSubmitted()){
        	$rec = $form->rec;
        	
        	// Трябва името да е уникално
        	if(crm_Companies::fetchField("#name = '{$rec->name}'")){
        		$form->setError('name', 'Има фирма със същото име');
        	}
        	
        	if(!$form->gotErrors()){
        		
        		// Създаваме първо фирмата
        		$companyId = crm_Companies::save((object)array('name' => $rec->name, 'country' => $rec->country, 'vatId' => $rec->vatId, 'uicId' => $rec->uicId));
        		
        		if($companyId){
        			
        			// Създаваме локацията към фирмата
        			$locationId = crm_Locations::save((object)array('title'         => $rec->title,
        															'countryId'     => $rec->country, 
										        					'type'          => $rec->type,
										        					'place'         => $rec->place,
										        					'pCode'         => $rec->pCode,
										        					'contragentCls' => crm_Companies::getClassId(),
										        					'contragentId'  => $companyId,
        															'gpsCoords'     => $rec->gpsCoords,
        															'image'         => $rec->image,
        															'comment'       => $rec->comment,
										        					'address'       => $rec->address));
        			
        			if($locationId){
        				
        				// Създаваме търговския маршрут към новосъздадената локация
        				$routeId = sales_Routes::save((object)array('locationId' => $locationId, 'salesmanId' => $rec->salesmanId, 'dateFld' => $rec->dateFld, 'repeat' => $rec->repeat));
        			
        				return new Redirect(array('crm_Locations', 'single', $locationId), '|Успешно е създаден търговския обект');
        			} else {
        				$form->setError('name', 'Има проблем при записа на локация');
        			}
        		} else {
        			$form->setError('name', 'Има проблем при записа на фирма');
        		}
        	}
        }
        
    	$tpl = $this->renderWrapping($form->renderHtml());
    	
    	return $tpl;
    }


    /**
     * Връща масив с предложения за типа на локацията
     */
    private static function getTypeSuggestions()
    {
        $suggArr = array('' => '',
                         'За кореспонденция' => 'За кореспонденция',
                         'Главна квартира' => 'Главна квартира',
                         'За получаване на пратки' => 'За получаване на пратки',
                         'Офис' => 'Офис',
                         'Магазин' => 'Магазин',
                         'Склад' => 'Склад',
                         'Фабрика' => 'Фабрика',
                         'Друг' => 'Друг');

        $query = self::getQuery();

        $query->groupBy('type');
        $query->show('type');
        while($rec = $query->fetch()) {
            $suggArr[$rec->type] = $rec->type;
        }

        return $suggArr;
    }
}
