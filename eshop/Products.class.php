<?php



/**
 * Мениджър на  продукти от е-магазина.
 *
 *
 * @category  bgerp
 * @package   eshop
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class eshop_Products extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = "Продукти в онлайн магазина";
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = "Е-Магазин";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, eshop_Wrapper, plg_State2, cms_VerbalIdPlg, plg_Search, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'code,name,groupId,state';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Продукт";
    
    
    /**
     * Икона за единичен изглед
     */
    public $singleIcon = 'img/16/wooden-box.png';

    
    /**
     * Кой може да чете
     */
    public $canRead = 'eshop,ceo';
    
    
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
     * Кой може да го види?
     */
    public $canView = 'eshop,ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'code,name,info,longInfo';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('code', 'varchar(10)', 'caption=Код');
        $this->FLD('order', 'int', 'caption=Подредба');
        $this->FLD('groupId', 'key(mvc=eshop_Groups,select=name,allowEmpty)', 'caption=Група, mandatory, silent');
        $this->FLD('name', 'varchar(64)', 'caption=Продукт, mandatory,width=100%');
        
        $this->FLD('image', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация1');
        $this->FLD('image2', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация2,column=none');
        $this->FLD('image3', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация3,column=none');
        $this->FLD('image4', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация4,column=none');
        $this->FLD('image5', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация5,column=none');

        $this->FLD('info', 'richtext(bucket=Notes,rows=5)', 'caption=Описание->Кратко');
        $this->FLD('longInfo', 'richtext(bucket=Notes,rows=5)', 'caption=Описание->Разширено');

        // Запитване за нестандартен продукт
        $this->FLD('coDriver', 'class(interface=cat_ProductDriverIntf,allowEmpty,select=title)', 'caption=Запитване->Драйвер,removeAndRefreshForm=coParams|proto|measureId,silent');
        $this->FLD('proto', "keylist(mvc=cat_Products,allowEmpty,select=name)", "caption=Запитване->Прототип,input=hidden,silent,placeholder=Популярни продукти");
        $this->FLD('coMoq', 'double', 'caption=Запитване->МКП,hint=Минимално количество за поръчка');
        $this->FLD('measureId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Мярка,tdClass=centerCol');
        $this->FLD('quantityCount', 'enum(3=3 количества,2=2 количества,1=1 количество,0=Без количество)', 'caption=Запитване->Брой количества');
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

            if($exRec = $query->fetch(array("#code = '[#1#]' AND #menuId = [#2#]", $rec->code, $menuId))) {
                $form->setError('code', "Повторение на кода със съществуващ продукт: |* <strong>" . $mvc->getVerbal($rec, 'name') . '</strong>');
            }
            
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

        if($fields['-list']) {
            $row->name = ht::createLink($row->name, self::getUrl($rec), NULL, 'ef_icon=img/16/monitor.png');
        }
        
        if(isset($rec->coDriver) && !cls::load($rec->coDriver, TRUE)){
        	$row->coDriver = "<span class='red'>" . tr('Несъществуващ клас') . "</span>";
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
        $pQuery->orderBy("#order,#code");
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

        $data->row = $this->recToVerbal($data->rec);
        
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

        if(self::haveRightFor('edit', $data->rec)) {
            $editSbf = sbf("img/16/edit.png", '');
            $editImg = ht::createElement('img', array('src' => $editSbf, 'width' => 16, 'height' => 16));
            $data->row->editLink = ht::createLink($editImg, array('eshop_Products', 'edit', $data->rec->id, 'ret_url' => TRUE));
        }
 
        Mode::set('SOC_TITLE', $data->row->name);
        Mode::set('SOC_SUMMARY', $data->row->info);

    }


    /**
     * Рендира продукта
     */
    public function renderProduct($data)
    {
        $tpl = getTplFromFile("eshop/tpl/ProductShow.shtml");
        
        if($data->row->editLink) { 
            $data->row->name .= '&nbsp;' . $data->row->editLink;
        }
        
        $tpl->placeObject($data->row);
    

        return $tpl;
    }

    
    /**
     * Връща каноничното URL на продукта за външния изглед
     */
    public static function getUrl($rec, $canonical = FALSE)
    {   
        $gRec = eshop_Groups::fetch($rec->groupId);

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
        if($id = $data->form->rec->id) {
            $rec = self::fetch($id);
            $gRec = eshop_Groups::fetch($rec->groupId);
            $cRec = cms_Content::fetch($gRec->menuId);
            cms_Domains::selectCurrent($cRec->domainId);
        }
        
        $data->form->setOptions('measureId', cat_UoM::getUomOptions());
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
}