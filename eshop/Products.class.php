<?php



/**
 * Мениджър на  продукти от е-магазина.
 *
 *
 * @category  bgerp
 * @package   eshop
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class eshop_Products extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = "Артикули в е-магазина";
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = "Е-Магазин";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, eshop_Wrapper, plg_State2, cms_VerbalIdPlg, plg_Search, plg_Sorting, plg_StructureAndOrder';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'code,name,groupId,state';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Е-артикул";
    
    
    /**
     * Икона за единичен изглед
     */
    public $singleIcon = 'img/16/wooden-box.png';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'eshop,ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'eshop,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'eshop,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'eshop,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'eshop,ceo';
	
    
    /**
     * Кой може да качва файлове
     */
    public $canWrite = 'eshop,ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'code,name,info,longInfo';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'eshop_ProductDetails';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да връзка артикул към ешоп-а
     */
    public $canLinktoeshop = 'eshop,ceo';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('code', 'varchar(10)', 'caption=Код');
        $this->FLD('groupId', 'key(mvc=eshop_Groups,select=name,allowEmpty)', 'caption=Група,mandatory,silent,refreshForm');
        $this->FLD('name', 'varchar(128)', 'caption=Продукт, mandatory,width=100%');
        
        $this->FLD('image', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация1');
        $this->FLD('image2', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация2,column=none');
        $this->FLD('image3', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация3,column=none');
        $this->FLD('image4', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация4,column=none');
        $this->FLD('image5', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация5,column=none');

        $this->FLD('info', 'richtext(bucket=Notes,rows=5)', 'caption=Описание->Кратко');
        $this->FLD('longInfo', 'richtext(bucket=Notes,rows=5)', 'caption=Описание->Разширено');

        // Запитване за нестандартен продукт
        $this->FLD('coDriver', 'class(interface=cat_ProductDriverIntf,allowEmpty,select=title)', 'caption=Запитване->Драйвер,removeAndRefreshForm=coParams|proto|measureId,silent');
        $this->FLD('proto', "keylist(mvc=cat_Products,allowEmpty,select=name,select2MinItems=100)", "caption=Запитване->Прототип,input=hidden,silent,placeholder=Популярни продукти");
        $this->FLD('coMoq', 'double', 'caption=Запитване->МКП,hint=Минимално количество за поръчка');
        $this->FLD('measureId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Мярка,tdClass=centerCol');
        $this->FLD('quantityCount', 'enum(3=3 количества,2=2 количества,1=1 количество,0=Без количество)', 'caption=Запитване->Брой количества');

        $this->setDbIndex('groupId');
    }

    
    /**
     * Връща мярката от драйвера ако има
     * 
     * @param stdClass $rec
     * @return int|NULL
     */
	private function getUomFromDriver($rec)
	{
		$uomId = NULL;
		if(cls::load($rec->coDriver, TRUE)){
			if($Driver = cls::get($rec->coDriver)){
				$uomId = $Driver->getDefaultUomId();
			}
		}
		
		return $uomId;
	}
	
	
    /**
     * Проверка за дублиран код
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = $form->rec;
    	
    	$isMandatoryMeasure = FALSE;
    	if($form->rec->coDriver){
    		$protoProducts = doc_Prototypes::getPrototypes('cat_Products', $form->rec->coDriver);
    	
    		if(count($protoProducts)){
    			$form->setField('proto', 'input');
    			$form->setSuggestions('proto', $protoProducts);
    		}
    	
    		if($uomId = $mvc->getUomFromDriver($rec)){
    			$form->setField('measureId', 'input=none');
    		}
    	}
    	
    	if($form->isSubmitted()) {
            $query = self::getQuery();
            $query->EXT('menuId', 'eshop_Groups', 'externalName=menuId,externalKey=groupId');
            if($rec->id) {
                $query->where("#id != {$rec->id}");
            }

            $menuId = eshop_Groups::fetchField($rec->groupId, 'menuId');

            if($exRec = $query->fetch(array("#code = '[#1#]' AND #menuId = '[#2#]'", $rec->code, $menuId))) {
                $form->setError('code', "Повторение на кода със съществуващ продукт: |* <strong>" . $mvc->getVerbal($rec, 'name') . '</strong>');
            }
        }
    }


    /**
     * $data->rec, $data->row
     */
    public function prepareGroupList_($data)
    {
        $data->row = $this->recToVerbal($data->rec);
    }


    /**
     * След обработка на вербалните стойностти
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
    	$row->name = tr($row->name);
    	
    	// Ако няма МКП. но има драйвер взимаме МКП-то от драйвера
        if(empty($rec->coMoq) && isset($rec->coDriver)){
            if(cls::load($rec->coDriver, TRUE)){
            	if($Driver = cls::get($rec->coDriver)){
            		if($moq = $Driver->getMoq()){
            			$rec->coMoq = $moq;
            		}
            	}
            }
        }
    	
    	if($rec->coMoq) {
        	$row->coMoq = cls::get('type_Double', array('params' => array('smartRound' => 'smartRound')))->toVerbal($rec->coMoq);
        }

        if($rec->coDriver) {
            if(marketing_Inquiries2::haveRightFor('new')){
            	$title = 'Изпратете запитване за|* ' . tr($rec->name);
            	Request::setProtected('title,drvId,protos,moq,quantityCount,lg,measureId');
            	$lg = cms_Content::getLang();
            	if(cls::load($rec->coDriver, TRUE)){
            		$url = array('marketing_Inquiries2', 'new', 'drvId' => $rec->coDriver, 'Lg' => $lg, 'protos' => $rec->proto, 'quantityCount' => $rec->quantityCount, 'moq' => $rec->coMoq, 'title' => $rec->name, 'ret_url' => TRUE);
            		$uomId = NULL;
            		$defUom = cat_Setup::get('DEFAULT_MEASURE_ID');
            		if(!$defUom){
            			$defUom = NULL;
            		}
            		
            		setIfNot($uomId, $mvc->getUomFromDriver($rec), $rec->measureId, $defUom);
            		if(empty($rec->proto) && !isset($uomId)){
            			$uomId = cat_UoM::fetchBySysId('pcs')->id;
            		}
            		$url['measureId'] = $uomId;
            		$row->coInquiry = ht::createLink(tr('Запитване'), $url, NULL, "ef_icon=img/16/button-question-icon.png,title={$title}");
            	}
            }
        }
        
        
        
        if(isset($rec->coDriver) && !cls::load($rec->coDriver, TRUE)){
        	$row->coDriver = "<span class='red'>" . tr('Несъществуващ клас') . "</span>";
        }
    }
    

    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if(haveRole('powerUser')){
    		$data->toolbar->addBtn('Преглед', self::getUrl($data->rec), NULL, 'ef_icon=img/16/monitor.png,title=Преглед във външната част');
    	}
    }
    
    
    /**
     * Подготвя информация за всички продукти от активните групи
     */
    public static function prepareAllProducts($data)
    {
        $gQuery = eshop_Groups::getQuery();

        $groups = eshop_Groups::getGroupsByDomain();
        if(count($groups)) {
            $groupList = implode(',', array_keys($groups));
            $gQuery->where("#id IN ({$groupList})");
        }

        while($gRec = $gQuery->fetch("#state = 'active'")) {
            $data->groups[$gRec->id] = new stdClass();
            $data->groups[$gRec->id]->groupId = $gRec->id;
            $data->groups[$gRec->id]->groupRec = $gRec;
            self::prepareGroupList($data->groups[$gRec->id]);
        }
    }


    /**
     * Подготвя данните за продуктите от една група
     */
    public static function prepareGroupList($data)
    {
        $pQuery = self::getQuery();

        while($pRec = $pQuery->fetch("#state = 'active' AND #groupId = {$data->groupId}")) {
            $data->recs[] = $pRec;
            $pRow = $data->rows[] = self::recToVerbal($pRec, 'name,info,image,code,coMoq');

            $imageArr = array();
            if($pRec->image) $imageArr[] = $pRec->image;
            if($pRec->image1) $imageArr[] = $pRec->image1;
            if($pRec->image2) $imageArr[] = $pRec->image2;
            if($pRec->image3) $imageArr[] = $pRec->image3;
            if($pRec->image4) $imageArr[] = $pRec->image4;
            if(count($imageArr)) {
                $tact = abs(crc32($pRec->id . round(time()/(24*60*60+537)))) % count($imageArr);
                $image = $imageArr[$tact];
                $img = new thumb_Img($image, 120, 120);
            } else {
                $img = new thumb_Img(getFullPath("eshop/img/noimage" . 
                    (cms_Content::getLang() == 'bg' ? 'bg' : 'en') . 
                    ".png"), 120, 120, 'path');
            }

            $pRow->image = $img->createImg(array('class' => 'eshop-product-image'));
            if(self::haveRightFor('edit', $pRec)) {
                $pRec->editUrl = array('eshop_Products', 'edit', $pRec->id, 'ret_url' => TRUE);
            }
        }

        // URL за добавяне на продукт
        if(self::haveRightFor('add')) {
            $data->addUrl = array('eshop_Products', 'add', 'groupId' => $data->groupId, 'ret_url' => TRUE);
        }
    }


    /**
     * Рендира всички продукти
     */
    public static function renderAllProducts($data)
    {
        $layout = new ET();

        if(is_array($data->groups)){
        	foreach($data->groups as $gData) {
        		if(!count($gData->recs)) continue;
        		$layout->append("<h2>" . eshop_Groups::getVerbal($gData->groupRec, 'name') . "</h2>");
        		$layout->append(self::renderGroupList($gData));
        	}
        }

        return $layout;
    }
    
    
    /**
     * Рендира списъка с групите
     *
     * @param stdClass $data
     * @return core_ET $layout
     */
    public function renderGroupList_($data)
    {   
        $layout = new ET("");

        if(is_array($data->rows)) {
            $editSbf = sbf("img/16/edit.png", '');
            $editImg = ht::createElement('img', array('src' => $editSbf, 'width' => 16, 'height' => 16));
            foreach($data->rows as $id => $row) {
                $rec = $data->recs[$id];

                $pTpl = getTplFromFile('eshop/tpl/ProductListGroup.shtml');

                if($rec->editUrl) {
                    $row->editLink = ht::createLink($editImg, $rec->editUrl);
                }
                
                $url = self::getUrl($rec);

                $row->name = ht::createLink($row->name, $url);
                $row->image = ht::createLink($row->image, $url);

                $pTpl->placeObject($row);

                $layout->append($pTpl);
            }
        }

        if($data->addUrl) {
            $layout->append(ht::createBtn('Нов продукт', $data->addUrl,  NULL, NULL, array('style' => 'margin-top:15px;')));
        }

        return $layout;
    }


    /**
     * Показва единичен изглед за продукт във външната част
     */
    function act_Show()
    {   
        // Поставя временно външният език, за език на интерфейса
        $lang = cms_Domains::getPublicDomain('lang');
        core_Lg::push($lang);

        $data = new stdClass();
        $data->productId = Request::get('id', 'int');

        if(!$data->productId) {
            $opt = cms_Content::getMenuOpt('eshop_Groups');
            if(count($opt)) {
                return new Redirect(array('cms_Content', 'Show', key($opt)));
            } else {
                return new Redirect(array('cms_Content', 'Show'));
            }
        }

        $data->rec = self::fetch($data->productId);
        $data->groups = new stdClass();
        $data->groups->groupId = $data->rec->groupId;
        $data->groups->rec = eshop_Groups::fetch($data->groups->groupId);
        
        cms_Content::setCurrent($data->groups->rec->menuId);

        $this->prepareProduct($data);

        eshop_Groups::prepareNavigation($data->groups);
        
        $tpl = eshop_Groups::getLayout();
        $tpl->append(cms_Articles::renderNavigation($data->groups), 'NAVIGATION');
        
        $rec = clone($data->rec);
        setIfNot($rec->seoTitle, $data->row->name);
        if(!$rec->seoDescription) {
            $rec->seoDescription = $this->getVerbal($rec, 'info');
        }

        cms_Content::setSeo($tpl, $rec);

        $tpl->append($this->renderProduct($data), 'PAGE_CONTENT');
        
        // Добавя канонично URL
        $url = toUrl(self::getUrl($data->rec, TRUE), 'absolute');
        cms_Content::addCanonicalUrl($url, $tpl);

        
        // Страницата да се кешира в браузъра
        $conf = core_Packs::getConfig('eshop');
        Mode::set('BrowserCacheExpires', $conf->ESHOP_BROWSER_CACHE_EXPIRES);
        
        if(core_Packs::fetch("#name = 'vislog'")) {
            vislog_History::add("Продукт «" . $data->rec->name ."»");
        }
        
        // Премахва зададения временно текущ език
        core_Lg::pop();

        return $tpl;
    }
    
    	
    /**
     * Подготовка на данните за рендиране на единичния изглед на продукт 
     */
    public function prepareProduct($data)
    {
        $data->rec->info = trim($data->rec->info);
        $data->rec->longInfo = trim($data->rec->longInfo);
		
        $fields = $this->selectFields();
		$fields['-external'] = TRUE;
		
        $data->row = $this->recToVerbal($data->rec, $fields);
        
        if($data->rec->image) {
            $data->row->image = fancybox_Fancybox::getImage($data->rec->image, array(160, 160), array(800, 800), $data->row->name); 
        } elseif(!$data->rec->image2 && !$data->rec->image3 && !$data->rec->image4 && !$data->rec->image5) {
            $data->row->image = new thumb_Img(getFullPath("eshop/img/noimage" . 
                    (cms_Content::getLang() == 'bg' ? 'bg' : 'en') . 
                    ".png"), 120, 120, 'path'); 
            $data->row->image = $data->row->image->createImg(array('width' => 120, 'height' => 120));
        }

        if($data->rec->image2) {
            $data->row->image2 = fancybox_Fancybox::getImage($data->rec->image2, array(160, 160), array(800, 800), $data->row->name . ' 2'); 
        }

        if($data->rec->image3) {
            $data->row->image3 = fancybox_Fancybox::getImage($data->rec->image3, array(160, 160), array(800, 800), $data->row->name3 . ' 3'); 
        }

        if($data->rec->image4) {
            $data->row->image4 = fancybox_Fancybox::getImage($data->rec->image4, array(160, 160), array(800, 800), $data->row->name4 . ' 4'); 
        }

        if($data->rec->image5) {
            $data->row->image5 = fancybox_Fancybox::getImage($data->rec->image5, array(160, 160), array(800, 6800), $data->row->name5 . ' 5'); 
        }

        if(self::haveRightFor('single', $data->rec)) {
        	$data->row->singleLink = ht::createLink('', array('eshop_Products', 'single', $data->rec->id, 'ret_url' => TRUE), FALSE, "ef_icon={$this->singleIcon},height=16px,width;16px");
        }
        
        if(self::haveRightFor('edit', $data->rec)) {
            $data->row->editLink = ht::createLink('', array('eshop_Products', 'edit', $data->rec->id, 'ret_url' => TRUE), FALSE, 'ef_icon=img/16/edit.png,height=16px,width;16px');
        }
        
        Mode::set('SOC_TITLE', $data->row->name);
        Mode::set('SOC_SUMMARY', $data->row->info);
        
        $data->detailData = (object)array('rec' => $data->rec);
        eshop_ProductDetails::prepareExternal($data->detailData);
    }


    /**
     * Рендира продукта
     */
    public function renderProduct($data)
    {
        if(Mode::is('screenMode', 'wide')) {
            $tpl = getTplFromFile("eshop/tpl/ProductShow.shtml");
        } else {
            $tpl = getTplFromFile("eshop/tpl/ProductShowNarrow.shtml");
        }
        $tpl->placeObject($data->row);
    
        if(is_array($data->detailData->rows) && count($data->detailData->rows)){
        	$tpl->replace(eshop_ProductDetails::renderExternal($data->detailData), 'PRODUCT_OPT');
        }
        
        return $tpl;
    }

    
    /**
     * Връща каноничното URL на продукта за външния изглед
     */
    public static function getUrl($rec, $canonical = FALSE)
    {   
    	$rec = self::fetchRec($rec);
    	$gRec = eshop_Groups::fetch($rec->groupId);
		if(empty($gRec->menuId)) return array();
		
        $mRec = cms_Content::fetch($gRec->menuId);
        
        $lg = $mRec->lang;

        $lg{0} = strtoupper($lg{0});

        $url = array('A', 'p', $rec->vid ? $rec->vid : $rec->id, 'PU' => (haveRole('powerUser') && !$canonical) ? 1 : NULL);
        
        return $url;
    }
    
    
    /**
     * Връща кратко URL към продукт
     */
    public static function getShortUrl($url)
    { 
        $vid = urldecode($url['id']);
        $act = strtolower($url['Act']);

        if($vid && $act == 'show') {
            $id = cms_VerbalId::fetchId($vid, 'eshop_Products'); 

            if(!$id) {
                $id = self::fetchField(array("#vid = '[#1#]'", $vid), 'id');
            }
            
            if(!$id && is_numeric($vid)) {
                $id = $vid;
            }

            if($id) {
                $url['Ctr'] = 'A';
                $url['Act'] = 'p';
                $url['id'] = $id;
            }
        }

        unset($url['PU']);

        return $url;
    }


    
    /**
     * Титлата за листовия изглед
     * Съдържа и текущия домейн
     */
    protected static function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        $data->title .= cms_Domains::getCurrentDomainInTitle();
    }

    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = $data->form;
    	$form->FNC('productId', "int", "caption=Артикул,silent,input=hidden");
    	$form->FNC('packagings', "keylist(mvc=cat_UoM,select=shortName)", "caption=Опаковки,silent,after=image5");
    	$form->input(NULL, 'hidden');
    	
    	if($id = $form->rec->id) {
            $rec = self::fetch($id);
            $gRec = eshop_Groups::fetch($rec->groupId);
            $cRec = cms_Content::fetch($gRec->menuId);
            cms_Domains::selectCurrent($cRec->domainId);
        }
        
        $groups = eshop_Groups::getByDomain();
        $form->setOptions('groupId', array('' => '') + $groups);
        $form->setOptions('measureId', cat_UoM::getUomOptions());
        
        if(isset($form->rec->productId)){
        	$mvc->setDefaultsFromProductId($form);
        }
    }
    
    
    /**
     * Добавя дефолти от артикула
     * 
     * @param core_Form $form
     * @return void
     */
    private function setDefaultsFromProductId(core_Form &$form)
    {
    	$rec = $form->rec;
    	
    	$productRec = cat_Products::fetch($rec->productId);
    	$form->setDefault('name', $productRec->name);
    	$form->setDefault('image', $productRec->photo);
    	$form->setDefault('code', ($productRec->code) ? $productRec->code : "Art{$productRec->id}");
    	$form->setField('packagings', 'input');
    	$form->setSuggestions('packagings', cat_Products::getPacks($productRec->id));
    	
    	$form->setDefault('info', $description);
    	$description = cat_Products::getDescription($productRec->id, 'public')->getContent();
    	$description = html2text_Converter::toRichText($description);
    	$description = cls::get('type_Richtext')->fromVerbal($description);
    	$description = str_replace("\n\n", "\n", $description);
    	$form->setDefault('longInfo', $description);
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
    	if(isset($rec->productId)){
    		$packagings = !empty($rec->packagings) ? $rec->packagings : keylist::addKey('', cat_Products::fetchField($rec->productId, 'measureId'));
    		$dRec = (object)array('productId' => $rec->productId, 'packagings' => $packagings, 'eshopProductId' => $rec->id);
    		eshop_ProductDetails::save($dRec);
    	}
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	if(isset($rec->id)){
    		$data->form->title = tr('Редактиране на') . " |*" . $mvc->getFormTitleLink($rec->id);
    	}
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->showFields = 'search,groupId';
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $rec = $data->listFilter->input(NULL, 'silent');
        $data->listFilter->setField('groupId', 'autoFilter');
        
        if($rec->groupId) {
            $data->query->where("#groupId = {$rec->groupId}");
        } else {

            $groups = eshop_Groups::getGroupsByDomain();
            if(count($groups)) {
                $groupList = implode(',', array_keys($groups));
                $data->query->where("#groupId IN ({$groupList})");
                $data->listFilter->setOptions('groupId', $groups);
            }
        }
    }


    /**
     * Имплементация на метод, необходим за plg_StructureAndOrder
     */
    public function saoCanHaveSublevel($rec, $newRec = NULL)
    {        
        return FALSE;
    }
    

    /**
     * Необходим метод за подреждането
     */
    public static function getSaoItems($rec)
    {
        $res = array();
        $groupId = Request::get('groupId', 'int');
        if(!$groupId) {
            $groupId = $rec->groupId;
        }
        if(!$groupId) return $res;

        $query = self::getQuery();
        $query->where("#groupId = {$groupId}");
        while($rec = $query->fetch()) {
            $res[$rec->id] = $rec;
        }

        return $res;
    }
    
    
    /**
     * Връзка на артикул към е-артикул
     */
    public function act_linktoeshop()
    {
    	// Проверки
    	$this->requireRightFor('linktoeshop');
    	expect($productId = Request::get('productId', 'int'));
    	expect($productRec = cat_Products::fetch($productId, 'canStore,measureId'));
    	
    	// Редирект ако потребителя се върна с бутона 'НАЗАД'
    	if(eshop_ProductDetails::isTheProductAlreadyInTheSameDomain($productId, cms_Domains::getPublicDomain()->id)){
        	redirect(array('cat_Products', 'single', $productId));
    	}
    	
    	$this->requireRightFor('linktoeshop', (object)array('productId' => $productId));
    	
    	// Форсиране на домейн
    	$domainId = cms_Domains::getCurrent();
    	
    	// Подготовка на формата
    	$form = cls::get('core_Form');
    	$form->title = 'Листване в е-магазина|* ' . cls::get('cat_Products')->getFormTitleLink($productId);
    	$form->info = tr('Домейн') . ": " . cms_Domains::getHyperlink($domainId, TRUE);
    	$form->FLD('eshopProductId', 'varchar', 'caption=Добавяне към,placeholder=Нов е-артикул');
    	$form->FLD('packagings', 'keylist(mvc=cat_UoM,select=name)', 'caption=Опаковка,mandatory');
    	$form->FLD('productId', 'int', 'caption=Артикул,mandatory,silent,input=hidden');
    	$form->input(NULL, 'silent');
    	
    	// Добавяне на наличните опаковки
    	$packs = cat_Products::getPacks($productId);
    	$form->setSuggestions('packagings', $packs);
    	$form->setDefault('packagings', keylist::addKey('', key($packs)));
    	
    	// Наличните е-артикули в домейна
    	$productOptions = eshop_Products::getInDomain($domainId);
    	$form->setOptions('eshopProductId', array('' => '') + $productOptions);
    	$form->input();
    	
    	// Изпращане на формата
    	if($form->isSubmitted()){
    		$formRec = $form->rec;
    		
    		if(empty($formRec->eshopProductId)){
    			if(eshop_Products::haveRightFor('add', (object)array('productId' => $productId))){
    				return redirect(array($this, 'add', 'productId' => $productId, 'packagings' => keylist::toArray($formRec->packagings)));
    			} else {
    				return followRetUrl(NULL, 'Нямате права да свързвате артикула');
    			}
    		}
    		
    		$thisDomainId = eshop_Products::getDomainId($formRec->eshopProductId);
    		
    		if(eshop_ProductDetails::isTheProductAlreadyInTheSameDomain($formRec->productId, $thisDomainId)){
    			$form->setError('eshopProductId', 'Артикулът вече е свързан с е-магазина на текущия домейн');
    		} else {
    			eshop_ProductDetails::save($formRec);
    			return redirect(array(eshop_Products, 'single', $formRec->eshopProductId), FALSE, 'Артикулът е свързан с онлайн магазина');
    		}
    	}
    	
    	// Добавяне на бутони
    	$form->toolbar->addSbBtn('Напред', 'save', 'ef_icon = img/16/move.png, title = Листване на артикула към е-магазина');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
    	$tpl = $this->renderWrapping($form->renderHtml());
    	
    	$this->logInfo("Разглеждане на формата за свързване към е-артикул");
    	core_Form::preventDoubleSubmission($tpl, $form);
    	
    	return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'linktoeshop') && isset($rec->productId)){
    		if(!self::canLinkProduct($rec->productId)){
    			$requiredRoles = 'no_one';
    		}elseif(eshop_ProductDetails::isTheProductAlreadyInTheSameDomain($rec->productId, cms_Domains::getPublicDomain()->id)){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	if(($action == 'linktoeshop' || $action == 'vieweproduct') && isset($rec)){
    		if(empty($rec->productId)){
    			$requiredRoles = 'no_one';
    		} elseif(!cms_Domains::haveRightFor('select')){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Кой е-артикул отговаря на артикула от домейна
     * 
     * @param int $productId     - артикул
     * @param int|NULL $domainId - ид на домейн или NULL за текущия
     * @return id|NULL           - намерения е-артикул
     */
    public static function getByProductId($productId, $domainId = NULL)
    {
    	$domainId = isset($domainId) ? $domainId : cms_Domains::getPublicDomain();
    	$groups = array_keys(eshop_Groups::getByDomain($domainId));
    	
    	$dQuery = eshop_ProductDetails::getQuery();
    	$dQuery->where("#productId = {$productId}");
    	$dQuery->EXT('groupId', 'eshop_Products', 'externalName=groupId,externalKey=eshopProductId');
    	$dQuery->in('groupId', $groups);
		$dQuery->show('eshopProductId');
		
		$id = $dQuery->fetch()->eshopProductId;
		
		return $id;
    }
    
    
    /**
     * Може ли артикула да се връзва към е-артикул
     * 
     * @param int $productId - артикул
     * @return boolean $res  - може ли артикула да се връзва към е-артикул
     */
    public static function canLinkProduct($productId)
    {
    	$productRec = cat_Products::fetch($productId, 'canSell,isPublic,state');
    	$res = ($productRec->state != 'closed' && $productRec->state != 'rejected' && $productRec->isPublic == 'yes' && $productRec->canSell == 'yes');
    	
    	return $res;
    }
    
    
    /**
     * Връща домейн ид-то на артикула от е-магазина
     * 
     * @param int $id
     * @return int 
     */
    public static function getDomainId($id)
    {
    	return cms_Content::fetchField(eshop_Groups::fetchField(eshop_Products::fetchField($id, 'groupId'), 'menuId'), 'domainId');
    }
    
    
    /**
     * Връща е-артикулите в подадения домейн
     * 
     * @param int|NULL $domainId - ид на домейн
     * @return array $products   - наличните артикули
     */
    public static function getInDomain($domainId = NULL)
    {
    	$products = array();
    	$domainId = (isset($domainId)) ? $domainId : cms_Domains::getPublicDomain()->id;
    	$groups = eshop_Groups::getByDomain($domainId);
    	if(!count($groups)) return $products;
    	$groups = array_keys($groups);
    	
    	$query = self::getQuery();
    	$query->in('groupId', $groups);
    	while($rec = $query->fetch()){
    		$products[$rec->id] = self::getTitleById($rec->id, FALSE);
    	}
    	
    	return $products;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	return tr($rec->name);
    }
}