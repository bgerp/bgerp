<?php


/**
 * Мениджър на артикул от е-магазина.
 *
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eshop_Products extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Артикули в е-магазина';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_Modified, plg_RowTools2, eshop_Wrapper, plg_State2, cat_plg_AddSearchKeywords, cms_VerbalIdPlg, plg_Search, plg_Sorting, plg_StructureAndOrder,plg_BulSearch';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'code,name,groupId=Група,saleState,state';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Е-артикул';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'marketing_InquirySourceIntf,sales_RatingsSourceIntf';
    
    
    /**
     * Икона за единичен изглед
     */
    public $singleIcon = 'img/16/globe.png';
    
    
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
    public $searchFields = 'code,name,info,longInfo,showParams';
    
    
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
     * Кой е главния детайл
     */
    public $mainDetail = 'eshop_ProductDetails';
    
    
    /**
     * Кой може да променя състоянието
     */
    public $canChangestate = 'eshop,ceo';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('code', 'varchar(10)', 'caption=Код');
        $this->FLD('name', 'varchar(128)', 'caption=Артикул, mandatory,width=100%');
        
        $this->FLD('image', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация1');
        $this->FLD('image2', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация2,column=none');
        $this->FLD('image3', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация3,column=none');
        $this->FLD('image4', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация4,column=none');
        $this->FLD('image5', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация5,column=none');
        
        // В кои групи участва продукта
        $this->FLD('groupId', 'key(mvc=eshop_Groups,select=name,allowEmpty)', 'caption=Групи->Основна,mandatory,silent,refreshForm');
        $this->FLD('sharedInGroups', 'keylist(mvc=eshop_Groups,select=name)', 'caption=Групи->Допълнителни');
        
        // Допълнителна информация
        $this->FLD('info', 'richtext(bucket=Notes,rows=5)', 'caption=Описание->Кратко');
        $this->FLD('longInfo', 'richtext(bucket=Notes,rows=5)', 'caption=Описание->Разширено');
        $this->FLD('showParams', 'keylist(mvc=cat_Params,select=typeExt)', 'caption=Описание->Параметри,optionsFunc=cat_Params::getPublic');
        $this->FLD('showPacks', 'keylist(mvc=cat_UoM,select=name)', 'caption=Описание->Опаковки/Мерки');
        $this->FLD('nearProducts', 'blob(serialize)', 'caption=Описание->Виж също,input=none');
        
        // Запитване за нестандартен продукт
        $this->FLD('coDriver', 'class(interface=cat_ProductDriverIntf,allowEmpty,select=title)', 'caption=Запитване->Драйвер,removeAndRefreshForm=coParams|proto|measureId,silent');
        $this->FLD('proto', 'keylist(mvc=cat_Products,allowEmpty,select=name,select2MinItems=100)', 'caption=Запитване->Прототип,input=hidden,silent,placeholder=Популярни продукти');
        $this->FLD('coMoq', 'double', 'caption=Запитване->МКП,hint=Минимално количество за поръчка');
        $this->FLD('measureId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Мярка,tdClass=centerCol');
        $this->FLD('quantityCount', 'enum(,3=3 количества,2=2 количества,1=1 количество)', 'caption=Запитване->Количества,placeholder=Без количество');
        $this->FLD('saleState', 'enum(single=Единичен,multi=Избор,closed=Стар артикул,empty=Без опции)', 'caption=Тип,input=none,notNull,value=empty');
        
        $this->setDbIndex('groupId');
    }
    
    
    /**
     * Връща мярката от драйвера ако има
     *
     * @param stdClass $rec
     *
     * @return int|NULL
     */
    private static function getUomFromDriver($rec)
    {
        $uomId = null;
        if (cls::load($rec->coDriver, true)) {
            if ($Driver = cls::get($rec->coDriver)) {
                $uomId = $Driver->getDefaultUomId();
            }
        }
        
        return $uomId;
    }
    
    
    /**
     * Връща id на мярката по подразбиране
     */
    private static function getUomId($rec)
    {
        $rec = self::fetchRec($rec);
        
        $uomId = self::getUomFromDriver($rec);
        
        if (!$uomId) {
            $uomId = $rec->measureId;
        }
        
        if (!$uomId) {
            $uomId = cat_Setup::get('DEFAULT_MEASURE_ID');
        }
        
        if (!$uomId) {
            $uomId = cat_UoM::fetchBySysId('pcs')->id;
        }
        
        return $uomId;
    }
    
    
    /**
     * Проверка за дублиран код
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        if ($form->rec->coDriver) {
            $protoProducts = doc_Prototypes::getPrototypes('cat_Products', $form->rec->coDriver);
            
            if (countR($protoProducts)) {
                $form->setField('proto', 'input');
                $form->setSuggestions('proto', $protoProducts);
            }
            
            // Ако мярката идва от драйвера
            if ($mvc->getUomFromDriver($rec)) {
                $form->setField('measureId', 'input=none');
            }
        }
        
        if ($form->isSubmitted()) {
            $query = self::getQuery();
            $query->EXT('menuId', 'eshop_Groups', 'externalName=menuId,externalKey=groupId');
            if ($rec->id) {
                $query->where("#id != {$rec->id}");
            }
            
            $menuId = eshop_Groups::fetchField($rec->groupId, 'menuId');
            
            if (strlen($rec->code) && ($query->fetch(array("#code = '[#1#]' AND #menuId = '[#2#]'", $rec->code, $menuId)))) {
                $form->setError('code', 'Вече има продукт със същия код|*: <strong>' . $mvc->getVerbal($rec, 'name') . '</strong>');
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
     * Колко е МКП, то
     *
     * @param stdClass $rec
     *
     * @return NULL|float $moq
     */
    private function getMoq($rec)
    {
        $moq = $rec->coMoq;
        if (empty($moq) && isset($rec->coDriver)) {
            if (cls::load($rec->coDriver, true)) {
                if ($Driver = cls::get($rec->coDriver)) {
                    $moq = $Driver->getMoq();
                }
            }
        }
        
        $moq = !empty($moq) ? $moq : null;
        
        return $moq;
    }
    
    
    /**
     * След обработка на вербалните стойностти
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $row->name = tr($row->name);
        $uomId = self::getUomId($rec);
        $rec->coMoq = $mvc->getMoq($rec);
        
        // Определяме, ако има мярката на продукта
        $uom = cat_UoM::getShortName($uomId);
        
        if ($rec->coMoq) {
            $row->coMoq = cls::get('type_Double', array('params' => array('smartRound' => 'smartRound')))->toVerbal($rec->coMoq);
            if ($uom) {
                $row->coMoq .= '&nbsp;' . $uom;
            }
        } else {
            $row->coMoq = null;
        }
        
        if ($rec->coDriver) {
            if (marketing_Inquiries2::haveRightFor('new')) {
                if (cls::load($rec->coDriver, true)) {
                    $title = 'Изпратете запитване за|* ' . tr($rec->name);
                    Request::setProtected('classId,objectId');
                    $url = array('marketing_Inquiries2', 'new', 'classId' => $mvc->getClassId(), 'objectId' => $rec->id, 'ret_url' => true);
                    $row->coInquiry = ht::createLink(tr('Запитване'), $url, null, "ef_icon=img/16/help_contents.png,title={$title},class=productBtn,rel=nofollow");
                    Request::removeProtected('classId,objectId');
                }
            }
        }
        
        if (isset($rec->coDriver) && !cls::load($rec->coDriver, true)) {
            $row->coDriver = "<span class='red'>" . tr('Несъществуващ клас') . '</span>';
        }
        
        if (isset($fields['-single'])) {
            foreach (array('showPacks', 'showParams') as $fld) {
                $hint = null;
                $showPacks = eshop_Products::getSettingField($rec->id, null, $fld, $hint);
                if (countR($showPacks)) {
                    $row->{$fld} = $mvc->getFieldType($fld)->toVerbal(keylist::fromArray($showPacks));
                    if (!empty($hint)) {
                        $row->{$fld} = ht::createHint($row->{$fld}, $hint, 'notice', false);
                    }
                }
            }
        }
        
        if (isset($fields['-list'])) {
            if (haveRole('powerUser') && $rec->state != 'closed') {
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $row->_rowTools->addLink('Преглед', self::getUrl($rec), 'alwaysShow,ef_icon=img/16/monitor.png,title=Преглед във външната част');
            }
        }
        
        $row->groupId = eshop_Groups::getHyperlink($rec->groupId, true);
        
        if (is_array($rec->nearProducts) && (isset($fields['-single']) || isset($fields['-external']))) {
            $row->nearRows = $mvc->prepareNearProducts($rec);
        }
    }
    
    
    /**
     * Връща данните за свързаните артикули, за показване във външната час
     *
     * @param stdClass $rec - запис
     *
     * @return array $rows - записи
     */
    private function prepareNearProducts($rec)
    {
        $rows = array();
        
        // Ако няма свързани артикули, се връща празен обект
        if (!is_array($rec->nearProducts)) {
            
            return $rows;
        }
        
        // За всеки свързан
        $nearProducts = array_keys($rec->nearProducts);
        foreach ($nearProducts as $productId) {
            
            // Ако е затворен, се пропуска
            $productRec = eshop_Products::fetch($productId);
            if ($productRec->state == 'closed') {
                continue;
            }
            
            $row = new stdClass();
            $productUrl = self::getUrl($productRec);
            $productTitle = eshop_Products::getTitleById($productId);
            $row->nearLink = ht::createLink($productTitle, $productUrl, null, 'class=productName');
            
            // Ако има се показва тъмбнейл, към него
            $thumb = static::getProductThumb($productRec, 200, 200);
            if (empty($thumb)) {
                $thumb = new thumb_Img(getFullPath('eshop/img/noimage' . (cms_Content::getLang() == 'bg' ? 'bg' : 'en') .'.png'), 300, 300, 'path');
            }
            
            $thumbHtml = $thumb->createImg(array('class' => 'eshopNearProductThumb', 'title' => $productTitle))->getContent();
            $row->nearThumb = ht::createLink($thumbHtml, $productUrl, null, 'class=productImage');
            $rows[$productId] = $row;
        }
        
        return $rows;
    }
    
    
    /**
     * Какви са дефолтните данни за създаване на запитване
     *
     * @param mixed $id - ид или запис
     *
     * @return array $res
     *               ['title']         - заглавие
     *               ['drvId']         - ид на драйвер
     *               ['lg']            - език
     *               ['protos']        - списък от прототипни артикули
     *               ['quantityCount'] - опционален брой количества
     *               ['moq']           - МКП
     *               ['measureId']     - основна мярка
     *
     */
    public function getInquiryData($id)
    {
        $rec = $this->fetchRec($id);
        
        $res = array('title' => $rec->name,
            'drvId' => $rec->coDriver,
            'lg' => cms_Content::getLang(),
            'protos' => $rec->proto,
            'quantityCount' => empty($rec->quantityCount) ? 0 : $rec->quantityCount,
            'moq' => $this->getMoq($rec),
            'measureId' => self::getUomId($rec),
        
        );
        
        return $res;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if (haveRole('powerUser') && $data->rec->state != 'closed') {
            $data->toolbar->addBtn('Преглед', self::getUrl($data->rec), null, 'ef_icon=img/16/monitor.png,title=Преглед във външната част');
        }
    }
    
    
    /**
     * Подготвя информация за всички продукти от активните групи
     */
    public static function prepareAllProducts($data)
    {
        $gQuery = eshop_Groups::getQuery();
        $data->groups = array();
        $groups = eshop_Groups::getGroupsByDomain();
        if (countR($groups)) {
            $groupList = implode(',', array_keys($groups));
            $gQuery->where("#id IN ({$groupList})");
            while ($gRec = $gQuery->fetch("#state = 'active'")) {
                $data->groups[$gRec->id] = new stdClass();
                $data->groups[$gRec->id]->groupId = $gRec->id;
                $data->groups[$gRec->id]->groupRec = $gRec;
                self::prepareGroupList($data->groups[$gRec->id]);
            }
        }
    }
    
    
    /**
     * Показване на тъмбнейла на е-артикула
     *
     * @param stdClass $rec
     * @param int      $width
     * @param int      $height
     *
     * @return thumb_Img|NULL
     */
    public static function getProductThumb($rec, $width = 120, $height = 120)
    {
        $imageArr = array();
        foreach (array('', '2', '3', '4', '5') as $i) {
            $fh = $rec->{"image{$i}"};
            if (!empty($fh)) {
                $path = fileman::fetchByFh($fh, 'path');
                if (file_exists($path)) {
                    $imageArr[] = $fh;
                }
            }
        }
        
        if (countR($imageArr)) {
            $tact = abs(crc32($rec->id . round(time() / (24 * 60 * 60 + 537)))) % countR($imageArr);
            $image = $imageArr[$tact];
            $thumb = new thumb_Img($image, $width, $height);
        } else {
            $thumb = new thumb_Img(getFullPath('eshop/img/noimage' . (cms_Content::getLang() == 'bg' ? 'bg' : 'en') .'.png'), $width, $height, 'path');
        }
        
        return $thumb;
    }
    
    
    /**
     * Подготвя данните за продуктите от една група
     */
    public static function prepareGroupList($data)
    {
        $pQuery = self::getQuery();
        $pQuery->where("#state = 'active' AND (#groupId = {$data->groupId} OR LOCATE('|{$data->groupId}|', #sharedInGroups))");
        $pQuery->XPR('cOrder', 'double', "IF(#groupId = {$data->groupId}, #saoOrder, 999999999)");
        $pQuery->orderBy('cOrder,code');
        $perPage = eshop_Groups::fetchField($data->groupId, 'perPage');
        $perPage = !empty($perPage) ? $perPage : eshop_Setup::get('PRODUCTS_PER_PAGE');
        
        $data->Pager = cls::get('core_Pager', array('itemsPerPage' => $perPage));
        $data->Pager->itemsCount = $pQuery->count();
        $data->Pager->setLimit($pQuery);
        $settings = cms_Domains::getSettings();
        
        while ($pRec = $pQuery->fetch()) {
            $data->recs[] = $pRec;
            $pRow = $data->rows[] = self::recToVerbal($pRec, 'name,info,image,code,coMoq');
            
            // Показване на тъмбнейл на артикула
            $thumb = static::getProductThumb($pRec);
            
            $pRow->image = $thumb->createImg(array('class' => 'eshop-product-image'));
            
            if ($pRec->saleState == 'single') {
                
                // Детайлите на артикула
                $dQuery = eshop_ProductDetails::getQuery();
                $dQuery->where("#eshopProductId = {$pRec->id}");
                $dRec = $dQuery->fetch();
                
                $measureId = cat_Products::fetchField($dRec->productId, 'measureId');
                $packagings = cat_Products::getProductInfo($dRec->productId)->packagings;
                
                // Какви са к-та в опаковките
                $selectedPackagings = keylist::toArray($dRec->packagings);
                $packs = array($measureId => 1);
                foreach ($packagings as $packRec) {
                    $packs[$packRec->packagingId] = $packRec->quantity;
                }
                
                // Коя е най-малката опаковка от избраните
                $minPackagingId = $minQuantityInPack = null;
                foreach ($selectedPackagings as $selPackId) {
                    $q = $packs[$selPackId];
                    if (!$q) {
                        continue;
                    }
                    
                    if (is_null($minPackagingId) || (isset($minPackagingId) && $q < $minQuantityInPack)) {
                        $minPackagingId = $selPackId;
                        $minQuantityInPack = $q;
                    }
                }
                
                // Ако мярката е брой и е показано да се показва
                if (isset($minPackagingId)) {
                    if (eshop_ProductDetails::getPublicDisplayPrice($dRec->productId, $minPackagingId, $minQuantityInPack)) {
                        $pRecClone = clone $dRec;
                        $pRecClone->packagingId = $minPackagingId;
                        $pRecClone->quantityInPack = $minQuantityInPack;
                        $pRecClone->_listView = true;
                        $dRow = eshop_ProductDetails::getExternalRow($pRecClone);
                        
                        $pRow->saleInfo = $dRow->saleInfo;
                        $pRow->singleCurrencyId = $settings->currencyId;
                        $pRow->chargeVat = ($settings->chargeVat == 'yes') ? tr('с ДДС') : tr('без ДДС');
                        $pRow->catalogPrice = $dRow->catalogPrice;
                        $pRow->packagingId = $dRow->packagingId;
                        $pRow->btn = $dRow->btn;
                    }
                }
            } elseif ($pRec->saleState == 'multi') {
                $pRow->btn = ht::createBtn($settings->addToCartBtn . '...', self::getUrl($pRec->id), false, false, 'title=Избор на артикул,class=productBtn addToCard,ef_icon=img/16/cart_go.png');
            } elseif ($pRec->saleState == 'closed') {
                $pRow->saleInfo = "<span class='option-not-in-stock'>" . mb_strtoupper(tr(('Спрян||Not available'))) . '</span>';
            }
            
            $commonParams = self::getCommonParams($pRec->id);
            $pRow->commonParams = (countR($commonParams)) ? self::renderParams(self::getCommonParams($pRec->id)) : null;
        }
        
        // URL за добавяне на продукт
        if (self::haveRightFor('add')) {
            $data->addUrl = array('eshop_Products', 'add', 'groupId' => $data->groupId, 'ret_url' => true);
        }
    }
    
    
    /**
     * Рендира всички продукти
     */
    public static function renderAllProducts($data)
    {
        $layout = new ET();
        
        if (is_array($data->groups)) {
            foreach ($data->groups as $gData) {
                if (!countR($gData->recs)) {
                    continue;
                }
                
                $groupName = eshop_Groups::getVerbal($gData->groupRec, 'name');
                $layout->append('<h2>' . $groupName . '</h2>');
                
                if (!empty($gData->groupRec->image)) {
                    $image = fancybox_Fancybox::getImage($gData->groupRec->image, array(1200,800), array(1600, 1000), $groupName);
                    $layout->append(new core_ET("<div class='eshop-group-image'>[#IMAGE#]</div>"));
                    $layout->replace($image, 'IMAGE');
                }
                $layout->append(self::renderGroupList($gData));
            }
        }
        
        return $layout;
    }
    
    
    /**
     * Рендира списъка с групите
     *
     * @param stdClass $data
     *
     * @return core_ET $layout
     */
    public function renderGroupList_($data)
    {
        $layout = new ET("<div class='eshop-product-list-holder'>[#BLOCK#]</div>");
        
        if (is_array($data->rows)) {
            foreach ($data->rows as $id => $row) {
                $rec = $data->recs[$id];
                
                $pTpl = getTplFromFile(Mode::is('screenMode', 'narrow') ? 'eshop/tpl/ProductListGroupNarrow.shtml' : 'eshop/tpl/ProductListGroup.shtml');
                
                if ($this->haveRightFor('single', $rec)) {
                    $row->singleLink = ht::createLink('', array('eshop_Products', 'single', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/wooden-box.png');
                }
                
                if ($this->haveRightFor('edit', $rec)) {
                    $row->editLink = ht::createLink('', array('eshop_Products', 'edit', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/edit.png');
                }
                
                if ($data->groupId != $rec->groupId) {
                    $rec->altGroupId = $data->groupId;
                }
                $url = self::getUrl($rec, $data->groupId);
                
                $row->name = ht::createLink($row->name, $url);
                $row->image = ht::createLink($row->image, $url, false, 'class=eshopLink');
                
                $pTpl->placeObject($row);
                $pTpl->removePlaces();
                $pTpl->removeBlocks();
                
                $layout->append($pTpl, 'BLOCK');
            }
        }
        
        if ($data->Pager) {
            $layout->append($data->Pager->getHtml());
        }
        
        if ($data->addUrl) {
            $layout->append(ht::createBtn('Нов продукт', $data->addUrl, null, null, array('style' => 'margin-top:15px;', 'ef_icon' => 'img/16/star_2.png')));
        }
        
        $toggleLink = ht::createLink('', null, null, array('ef_icon' => 'img/menu.png', 'class' => 'toggleLink'));
        $layout->replace($toggleLink, 'TOGGLE_BTN');
        
        return $layout;
    }
    
    
    /**
     * Показва единичен изглед за продукт във външната част
     */
    public function act_Show()
    {
        // Поставя временно външният език, за език на интерфейса
        $lang = cms_Domains::getPublicDomain('lang');
        core_Lg::push($lang);
        
        $data = new stdClass();
        $data->productId = Request::get('id', 'int');
        
        if (!$data->productId) {
            $opt = cms_Content::getMenuOpt('eshop_Groups');
            if (countR($opt)) {
                
                return new Redirect(array('cms_Content', 'Show', key($opt)));
            }
            
            return new Redirect(array('cms_Content', 'Show'));
        }
        
        $data->rec = self::fetch($data->productId);
        if ($data->rec->state == 'closed') {
            $groupRec = eshop_Groups::fetch($data->rec->groupId);
            
            return new Redirect(eshop_Groups::getUrl($groupRec), 'Артикулът в момента е спрян от продажба|*!', 'warning');
        }
        
        $data->groups = new stdClass();
        $data->groups->groupId = $data->rec->groupId;
        if ($groupId = Request::get('groupId', 'int')) {
            if (strpos($data->rec->sharedInGroups, "|{$groupId}|") !== false) {
                $data->groups->groupId = $groupId;
            }
        }
        $data->groups->rec = eshop_Groups::fetch($data->groups->groupId);
        $data->groups->menuId = cms_Content::getMainMenuId($data->groups->rec->menuId, $data->groups->rec->sharedMenus);
        cms_Content::setCurrent($data->groups->menuId);
        
        $this->prepareProduct($data);
        
        // Подготвяме SEO данните
        $rec = clone($data->rec);
        cms_Content::prepareSeo($rec, array('seoDescription' => $rec->info, 'seoTitle' => $rec->name, 'seoThumb' => $rec->image));
        
        eshop_Groups::prepareNavigation($data->groups);
        
        $tpl = eshop_Groups::getLayout();
        $tpl->append(cms_Articles::renderNavigation($data->groups), 'NAVIGATION');
        
        // Поставяме SEO данните
        cms_Content::renderSeo($tpl, $rec);
        
        $tpl->append($this->renderProduct($data), 'PAGE_CONTENT');
        
        // Добавя канонично URL
        $url = toUrl(self::getUrl($data->rec, true), 'absolute');
        cms_Content::addCanonicalUrl($url, $tpl);
        
        // Страницата да се кешира в браузъра
        $conf = core_Packs::getConfig('eshop');
        Mode::set('BrowserCacheExpires', $conf->ESHOP_BROWSER_CACHE_EXPIRES);
        
        if (core_Packs::fetch("#name = 'vislog'")) {
            vislog_History::add('Продукт «' . $data->rec->name .'»');
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
        $fields['-external'] = true;
        
        $data->row = $this->recToVerbal($data->rec, $fields);
        
        $hasImage = false;
        foreach (array('image', 'image2', 'image3', 'image4', 'image5') as $i => $imgFld) {
            if (!empty($data->rec->{$imgFld})) {
                $path = fileman::fetchByFh($data->rec->{$imgFld}, 'path');
                if (file_exists($path)) {
                    $data->row->{$imgFld} = fancybox_Fancybox::getImage($data->rec->{$imgFld}, array(160, 160), array(800, 800), $data->row->name . " {$i}", array('class' => 'product-image'));
                    $hasImage = true;
                } else {
                    unset($data->row->{$imgFld});
                }
            }
        }
        
        if ($hasImage === false) {
            $data->row->image = new thumb_Img(getFullPath('eshop/img/noimage' . (cms_Content::getLang() == 'bg' ? 'bg' : 'en') . '.png'), 120, 120, 'path');
            $data->row->image = $data->row->image->createImg(array('width' => 160, 'height' => 160, 'class' => 'product-image'));
        }
        
        if (self::haveRightFor('single', $data->rec)) {
            $data->row->singleLink = ht::createLink('', array('eshop_Products', 'single', $data->rec->id, 'ret_url' => true), false, "ef_icon={$this->singleIcon},height=16px,width;16px");
        }
        
        if (self::haveRightFor('edit', $data->rec)) {
            $data->row->editLink = ht::createLink('', array('eshop_Products', 'edit', $data->rec->id, 'ret_url' => true), false, 'ef_icon=img/16/edit.png,height=16px,width;16px');
        }
        
        Mode::set('SOC_TITLE', $data->row->name);
        Mode::set('SOC_SUMMARY', $data->row->info);
        
        $data->detailData = (object) array('rec' => $data->rec);
        eshop_ProductDetails::prepareExternal($data->detailData);
        
        // Линк към менюто
        $groupRec = eshop_Groups::fetch($data->rec->groupId);
        $menu = cms_Content::getVerbal($groupRec->menuId, 'menu');
        $menuLink = ht::createLink($menu, cms_Content::getContentUrl($groupRec->menuId));
        
        // Линк към групата
        $group = eshop_Groups::getVerbal($groupRec, 'name');
        $groupLink = ht::createLink($group, eshop_Groups::getUrl($groupRec));
        $pgId = $groupRec->saoParentId;
        $used = array();
        
        while ($pgId) {
            if ($used[$pgId]) {
                break;
            }
            $pGroupRec = eshop_Groups::fetch($pgId);
            $groupLink = ht::createLink(eshop_Groups::getVerbal($pGroupRec, 'name'), eshop_Groups::getUrl($pGroupRec)) . ' » ' . $groupLink;
            $pgId = $pGroupRec->saoParentId;
            $used[$pgId] = true;
        }
        
        // Навигация до артикула
        $data->row->productPath = $menuLink . ' » ' . $groupLink;
        
        if ($data->rec->saleState == 'closed') {
            $data->row->STATE_EXTERNAL = "<span class='option-not-in-stock' style='font-size:0.9em !important'>" . tr('Този продукт вече не се предлага') . '</span>';
        } elseif (countR($data->detailData->rows) == 1 && !empty($data->detailData->rows[0]->saleInfo)) {
            $data->row->STATE_EXTERNAL = $data->detailData->rows[0]->saleInfo;
        }
    }
    
    
    /**
     * След извличане на ключовите думи
     */
    protected function on_AfterGetSearchKeywords($mvc, &$searchKeywords, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        
        if (!isset($searchKeywords)) {
            $searchKeywords = plg_Search::getKeywords($mvc, $rec);
        }
        
        if (isset($rec->groupId)) {
            $gRec = eshop_Groups::fetch($rec->groupId);
            $handleNormalized = plg_Search::normalizeText($gRec->name . ' ' . $gRec->seoKeywords);
            
            if (strpos($searchKeywords, $handleNormalized) === false) {
                $searchKeywords .= ' ' . $handleNormalized;
                cms_VerbalIdPlg::on_AfterGetSearchKeywords($mvc, $searchKeywords, $rec);
            }
        }
        
        // Всички детайли на е-артикула
        if (isset($rec->id)) {
            $dQuery = eshop_ProductDetails::getQuery();
            $dQuery->where("#eshopProductId = {$rec->id}");
            while ($dRec = $dQuery->fetch()) {
                
                // Извличат се параметрите им и се добавят към ключовите думи
                $params = cat_Products::getParams($dRec->productId, null, true);
                foreach ($params as $paramId => $paramValue) {
                    $paramName = cat_Params::getTitleById($paramId);
                    $searchKeywords .= ' ' . plg_Search::normalizeText($paramName) . ' ' . plg_Search::normalizeText($paramValue);
                }
            }
        }
    }
    
    
    /**
     * Рендира продукта
     */
    public function renderProduct_($data)
    {
        if (Mode::is('screenMode', 'wide')) {
            $tpl = getTplFromFile('eshop/tpl/ProductShow.shtml');
        } else {
            $tpl = getTplFromFile('eshop/tpl/ProductShowNarrow.shtml');
        }
        $tpl->placeObject($data->row);
        
        $tpl->push('css/no-sass.css', 'CSS');
        if (is_array($data->detailData->rows) && countR($data->detailData->rows)) {
            $tpl->replace(eshop_ProductDetails::renderExternal($data->detailData), 'PRODUCT_OPT');
        }
        
        // Рендиране на свързаните артикули
        if (is_array($data->row->nearRows)) {
            foreach ($data->row->nearRows as $nearRow) {
                $block = clone $tpl->getBlock('nearLink');
                $block->placeObject($nearRow);
                $block->removeBlocksAndPlaces();
                $tpl->append($block, 'NEAR_ROWS');
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Връща каноничното URL на продукта за външния изглед
     */
    public static function getUrl($rec, $canonical = false)
    {
        $rec = self::fetchRec($rec);
        $gRec = eshop_Groups::fetch($rec->groupId);
        if (empty($gRec->menuId)) {
            
            return array();
        }
        
        $mRec = cms_Content::fetch($gRec->menuId);
        $lg = $mRec->lang;
        
        $lg{0} = strtoupper($lg{0});
        
        $url = array('A', 'p', $rec->vid ? $rec->vid : $rec->id, 'PU' => (haveRole('powerUser') && !$canonical) ? 1 : null);
        
        if ($rec->altGroupId) {
            $url['groupId'] = $rec->altGroupId;
        }
        
        return $url;
    }
    
    
    /**
     * Връща кратко URL към продукт
     */
    public static function getShortUrl($url)
    {
        $vid = urldecode($url['id']);
        $act = strtolower($url['Act']);
        
        if ($vid && $act == 'show') {
            $id = cms_VerbalId::fetchId($vid, 'eshop_Products');
            
            if (!$id) {
                $id = self::fetchField(array("#vid = '[#1#]'", $vid), 'id');
            }
            
            if (!$id && is_numeric($vid)) {
                $id = $vid;
            }
            
            if ($id) {
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
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        
        // Добавяне на избор само на драйверите, до които потребителя има достъп
        $driverOptions = marketing_Inquiries2::getAvailableDriverOptions();
        if ($form->rec->coDriver && !array_key_exists($form->rec->coDriver, $driverOptions)) {
            $name = core_Classes::fetchField($form->rec->coDriver, 'title');
            $driverOptions[$form->rec->coDriver] = core_Classes::translateClassName($name);
        }
        $form->setOptions('coDriver', $driverOptions);
        
        $form->FNC('productId', 'int', 'caption=Артикул,silent,input=hidden');
        $form->FNC('packagings', 'keylist(mvc=cat_UoM,select=shortName)', 'caption=Опаковки,silent,after=image5');
        $form->input(null, 'hidden');
        $form->setSuggestions('showParams', cat_Params::getPublic());
        if ($id = $form->rec->id) {
            $rec = self::fetch($id);
            $gRec = eshop_Groups::fetch($rec->groupId);
            $cRec = cms_Content::fetch($gRec->menuId);
            cms_Domains::selectCurrent($cRec->domainId);
        }
        
        $groups = eshop_Groups::getByDomain();
        $form->setOptions('groupId', array('' => '') + $groups);
        if ($groupId = $form->rec->groupId) {
            unset($groups[$groupId]);
        }
        $form->setSuggestions('sharedInGroups', $groups);
        
        $form->setOptions('measureId', cat_UoM::getUomOptions());
        
        if (isset($form->rec->productId)) {
            $mvc->setDefaultsFromProductId($form);
        }
    }
    
    
    /**
     * Добавя дефолти от артикула
     *
     * @param core_Form $form
     *
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
        
        $description = cat_Products::getDescription($productRec->id, 'public')->getContent();
        $description = html2text_Converter::toRichText($description);
        $description = cls::get('type_Richtext')->fromVerbal($description);
        $description = str_replace("\n\n", "\n", $description);
        
        $description = str_replace('- ', '* ', $description);
        $form->setDefault('longInfo', $description);
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        if (isset($rec->productId)) {
            $packagings = !empty($rec->packagings) ? $rec->packagings : keylist::addKey('', cat_Products::fetchField($rec->productId, 'measureId'));
            $dRec = (object) array('productId' => $rec->productId, 'packagings' => $packagings, 'eshopProductId' => $rec->id);
            eshop_ProductDetails::save($dRec);
        }
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $rec = $data->form->rec;
        if (isset($rec->id)) {
            $data->form->title = tr('Редактиране на') . ' |*' . $mvc->getFormTitleLink($rec->id);
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
        
        $rec = $data->listFilter->input(null, 'silent');
        $data->listFilter->setField('groupId', 'autoFilter');
        
        if ($rec->groupId) {
            $data->query->where("#groupId = {$rec->groupId}");
        } else {
            $groups = eshop_Groups::getGroupsByDomain();
            if (countR($groups)) {
                $groupList = implode(',', array_keys($groups));
                $data->query->where("#groupId IN ({$groupList})");
                $data->listFilter->setOptions('groupId', $groups);
            }
        }
    }
    
    
    /**
     * Имплементация на метод, необходим за plg_StructureAndOrder
     */
    public function saoCanHaveSublevel($rec, $newRec = null)
    {
        return false;
    }
    
    
    /**
     * Необходим метод за подреждането
     */
    public static function getSaoItems($rec)
    {
        $res = array();
        $groupId = Request::get('groupId', 'int');
        if (!$groupId) {
            $groupId = $rec->groupId;
        }
        if (!$groupId) {
            
            return $res;
        }
        
        $query = self::getQuery();
        $query->where("#groupId = {$groupId}");
        while ($rec = $query->fetch()) {
            $res[$rec->id] = $rec;
        }
        
        return $res;
    }
    
    
    /**
     * Връзка на артикул към е-артикул
     */
    public function act_LinkToEshop()
    {
        // Проверки
        $this->requireRightFor('linktoeshop');
        expect($productId = Request::get('productId', 'int'));
        expect(cat_Products::fetch($productId, 'canStore,measureId'));
        
        // Редирект ако потребителя се върна с бутона 'НАЗАД'
        if (eshop_ProductDetails::isTheProductAlreadyInTheSameDomain($productId, cms_Domains::getPublicDomain()->id)) {
            redirect(array('cat_Products', 'single', $productId));
        }
        
        $this->requireRightFor('linktoeshop', (object) array('productId' => $productId));
        
        // Форсиране на домейн
        $domainId = cms_Domains::getCurrent();
        
        // Подготовка на формата
        $form = cls::get('core_Form');
        $form->title = 'Листване в е-магазина|* ' . cls::get('cat_Products')->getFormTitleLink($productId);
        $form->info = tr('Домейн') . ': ' . cms_Domains::getHyperlink($domainId, true);
        $form->FLD('eshopProductId', 'varchar', 'caption=Добавяне към,placeholder=Нов е-артикул');
        $form->FLD('packagings', 'keylist(mvc=cat_UoM,select=name)', 'caption=Опаковка,mandatory');
        $form->FLD('productId', 'int', 'caption=Артикул,mandatory,silent,input=hidden');
        $form->input(null, 'silent');
        
        // Добавяне на наличните опаковки
        $packs = cat_Products::getPacks($productId);
        $form->setSuggestions('packagings', $packs);
        $form->setDefault('packagings', keylist::addKey('', key($packs)));
        
        // Наличните е-артикули в домейна
        $productOptions = eshop_Products::getInDomain($domainId);
        $form->setOptions('eshopProductId', array('' => '') + $productOptions);
        $form->input();
        
        // Изпращане на формата
        if ($form->isSubmitted()) {
            $formRec = $form->rec;
            
            if (empty($formRec->eshopProductId)) {
                if (eshop_Products::haveRightFor('add', (object) array('productId' => $productId))) {
                    
                    return redirect(array($this, 'add', 'productId' => $productId, 'packagings' => keylist::toArray($formRec->packagings)));
                }
                
                return followRetUrl(null, 'Нямате права да свързвате артикула');
            }
            
            $thisDomainId = eshop_Products::getDomainId($formRec->eshopProductId);
            
            if (eshop_ProductDetails::isTheProductAlreadyInTheSameDomain($formRec->productId, $thisDomainId)) {
                $form->setError('eshopProductId', 'Артикулът вече е свързан с е-магазина на текущия домейн');
            } else {
                eshop_ProductDetails::save($formRec);
                
                return redirect(array(eshop_Products, 'single', $formRec->eshopProductId), false, 'Артикулът е свързан с онлайн магазина');
            }
        }
        
        // Добавяне на бутони
        $form->toolbar->addSbBtn('Напред', 'save', 'ef_icon = img/16/move.png, title = Листване на артикула към е-магазина');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        $tpl = $this->renderWrapping($form->renderHtml());
        
        $this->logInfo('Разглеждане на формата за свързване към е-артикул');
        core_Form::preventDoubleSubmission($tpl, $form);
        
        return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'linktoeshop') && isset($rec->productId)) {
            if (!self::canLinkProduct($rec->productId)) {
                $requiredRoles = 'no_one';
            } elseif (eshop_ProductDetails::isTheProductAlreadyInTheSameDomain($rec->productId, cms_Domains::getPublicDomain()->id)) {
                $requiredRoles = 'no_one';
            }
        }
        
        if (($action == 'linktoeshop' || $action == 'vieweproduct') && isset($rec)) {
            if (empty($rec->productId)) {
                $requiredRoles = 'no_one';
            } elseif (!cms_Domains::haveRightFor('select')) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Кой е-артикул отговаря на артикула от домейна
     *
     * @param int      $productId - артикул
     * @param int|NULL $domainId  - ид на домейн или NULL за текущия
     *
     * @return int|NULL - намерения е-артикул
     */
    public static function getByProductId($productId, $domainId = null)
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
     *
     * @return bool $res  - може ли артикула да се връзва към е-артикул
     */
    public static function canLinkProduct($productId)
    {
        $productRec = cat_Products::fetch($productId, 'canSell,isPublic,nameEn,state');
        $res = ($productRec->state != 'closed' && $productRec->state != 'rejected' && $productRec->state != 'template' && $productRec->isPublic == 'yes' && $productRec->canSell == 'yes' && $productRec->generic != 'yes');
        
        return $res;
    }
    
    
    /**
     * Връща домейн ид-то на артикула от е-магазина
     *
     * @param int $id
     *
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
     *
     * @return array $products   - наличните артикули
     */
    public static function getInDomain($domainId = null)
    {
        $products = array();
        $domainId = (isset($domainId)) ? $domainId : cms_Domains::getPublicDomain()->id;
        $groups = eshop_Groups::getByDomain($domainId);
        if (!countR($groups)) {
            
            return $products;
        }
        $groups = array_keys($groups);
        
        $query = self::getQuery();
        $query->in('groupId', $groups);
        while ($rec = $query->fetch()) {
            $products[$rec->id] = self::getTitleById($rec->id, false);
        }
        
        return $products;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        return tr($rec->name);
    }
    
    
    /**
     * Връща първата намерена стойност на полето в последователността:
     *      1. Ако е заданена в самия е-артикул
     *      2. Ако е зададена в групата на е-артикула
     *      3. Ако е зададена в настройките на домейна, в който е групата
     *
     * @param int|null    $eshopProductId
     * @param int|null    $groupId
     * @param string      $field
     * @param null|string $hint
     *
     * @return array $res
     */
    public static function getSettingField($eshopProductId, $groupId, $field, &$hint = null)
    {
        // Ако има зададен е-артикул търсим първо в него
        if (isset($eshopProductId)) {
            $rec = self::fetchRec($eshopProductId);
            if (!empty($rec->{$field})) {
                $res = keylist::isKeylist($rec->{$field}) ? keylist::toArray($rec->{$field}) : arr::make($rec->{$field}, true);
                
                return $res;
            }
            
            $groupId = $rec->groupId;
        }
        
        // Търсим полето в групата
        $groupRec = eshop_Groups::fetch($groupId, "{$field},menuId");
        if (!empty($groupRec->{$field})) {
            $res = keylist::isKeylist($groupRec->{$field}) ? keylist::toArray($groupRec->{$field}) : arr::make($groupRec->{$field}, true);
            if (isset($eshopProductId)) {
                $hint = 'Стойността е зададена в групата';
            }
            
            return $res;
        }
        
        // В краен случай търсим полето в настройките на домейна
        $domainId = cms_Content::fetchField($groupRec->menuId, 'domainId');
        $settings = cms_Domains::getSettings($domainId);
        $res = keylist::isKeylist($settings->{$field}) ? keylist::toArray($settings->{$field}) : arr::make($settings->{$field}, true);
        $hint = 'Стойността е зададена в настройките на домейна';
        
        return $res;
    }
    
    
    /**
     * Връща общите параметри за артикулите, тези които са с еднакви стойности за
     * всички артикули от опциите
     *
     * @param int $id
     *
     * @return array $res
     */
    public static function getCommonParams($id)
    {
        $res = $rowParams = $totalParams = array();
        $rec = self::fetchRec($id);
        
        // Има ли параметри за показване
        $displayParams = eshop_Products::getSettingField($id, null, 'showParams');
        if (!countR($displayParams)) {
            
            return $res;
        }
        
        // Опциите към артикула
        $displayPacks = eshop_Products::getSettingField($id, null, 'showPacks');
        $dQuery = eshop_ProductDetails::getQuery();
        $dQuery->where("#eshopProductId = {$rec->id}");
        $dQuery->show('productId,packagings');
        
        while ($dRec = $dQuery->fetch()) {
            if (!eshop_ProductDetails::getPublicDisplayPrice($dRec->productId)) {
                continue;
            }
            
            // Ако нито една от опаковките на артикула няма да се показва, игнорираме го
            if (countR($displayPacks)) {
                $packs = keylist::toArray($dRec->packagings);
                if (!array_intersect_key($packs, $displayPacks)) {
                    continue;
                }
            }
            
            // Какви стойности имат избраните параметри
            $intersect = array();
            $productParams = cat_Products::getParams($dRec->productId, null, true);
            foreach ($displayParams as $displayParamId) {
                $intersect[$displayParamId] = $productParams[$displayParamId];
            }
            
            $totalParams = $totalParams + array_combine(array_keys($intersect), array_keys($intersect));
            $rowParams[$dRec->productId] = $intersect;
        }
        
        // За всеки от избраните параметри
        foreach ($totalParams as $paramId) {
            $isCommon = true;
            $value = false;
            
            foreach ($rowParams as $params) {
                if ($value === false) {
                    $value = $params[$paramId];
                } elseif (trim($value) != trim($params[$paramId])) {
                    $value = false;
                    $isCommon = false;
                }
            }
            
            // Ако всичките записи имат еднаква стойност, значи параметъра е общ
            if ($isCommon === true && isset($value)) {
                $paramRow = cat_Params::recToVerbal($paramId, 'suffix');
                if (!empty($paramRow->suffix)) {
                    $value .= " {$paramRow->suffix}";
                }
                
                $res[$paramId] = $value;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     *
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);
        if (empty($rec)) {
            
            return;
        }
        
        $rec->saleState = self::getSaleState($rec->id);
        
        // Обновяване на модела, за да се преизчислят ключовите думи
        $this->save($rec);
    }
    
    
    /**
     * Какво е продажното състояние на артикула
     *
     * @param int $id на е-артоли;а
     *
     * @return string $saleState
     */
    public static function getSaleState($id)
    {
        // Всички детайли към опциите
        $dQuery = eshop_ProductDetails::getQuery();
        $dQuery->where("#eshopProductId = {$id}");
        $dQuery->show('state');
        $details = $dQuery->fetchAll();
        
        // Колко опции има и дали сред тях има затворени
        $countNotClosed = $countClosed = 0;
        $count = $dQuery->count();
        array_walk($details, function ($a) use (&$countClosed, &$countNotClosed) {
            if ($a->state != 'active') {
                $countClosed++;
            } else {
                $countNotClosed++;
            }
        });
        
        if ($count == 0) {
            $saleState = 'empty';
        } elseif ($count > 0 && $count == $countClosed) {
            $saleState = 'closed';
        } elseif ($countNotClosed == 1) {
            $saleState = 'single';
        } else {
            $saleState = 'multi';
        }
        
        return $saleState;
    }
    
    
    /**
     * Рендира параметрите на е-артикула
     *
     * @param array $array
     *
     * @return core_ET
     */
    public static function renderParams($array, $isTable = true)
    {
        $tpl = new core_ET('');
        if (!is_array($array)) {
            
            return $tpl;
        }
        
        if ($isTable) {
            $tpl = new core_ET("<table class='paramsTable'>[#row#]</table>");
            foreach ($array as $paramId => $value) {
                $paramBlock = new core_ET('<tr><td nowrap valign="top"><b>&bull; [#caption#]:<b></td><td>[#value#]</td></tr>');
                $paramBlock->placeArray(array('caption' => str::mbUcfirst(tr(cat_Params::getTitleById($paramId))), 'value' => $value));
                $paramBlock->removeBlocksAndPlaces();
                $tpl->append($paramBlock, 'row');
            }
        } else {
            $tpl = new core_ET("<div class='richtext'><ul>[#row#]</ul></div>");
            foreach ($array as $paramId => $value) {
                $paramBlock = new core_ET('<li><b>[#caption#]</b> : [#value#]</li>');
                $paramBlock->placeArray(array('caption' => str::mbUcfirst(tr(cat_Params::getTitleById($paramId))), 'value' => $value));
                $paramBlock->removeBlocksAndPlaces();
                $tpl->append($paramBlock, 'row');
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Изчислява подобните продукти
     */
    public static function saveNearProducts()
    {
        $res = $map = array();
        $gQuery = eshop_Groups::getQuery();
        while ($gRec = $gQuery->fetch("state = 'active'")) {
            $pQuery = eshop_Products::getQuery();
            while ($pRec = $pQuery->fetch("state = 'active' AND #groupId = {$gRec->id}")) {
                $dQuery = eshop_ProductDetails::getQuery();
                $pArr = array();
                while ($dRec = $dQuery->fetch("#state = 'active' AND #eshopProductId = {$pRec->id}")) {
                    $pArr[] = $dRec->productId;
                    $map[$gRec->menuId][$dRec->productId] = $pRec->id;
                }
                if (countR($pArr)) {
                    $res[$gRec->menuId][$pRec->id] = $pArr;
                }
            }
        }
        
        $r = array();
        foreach ($res as $menuId => $eshopProducts) {
            foreach ($eshopProducts as $epId => $pArr) {
                foreach ($pArr as $pId) {
                    
                    // Вземаме за този продукт близките му
                    $relData = sales_ProductRelations::fetchField("#productId = {$pId}", 'data');
                    if (is_array($relData)) {
                        foreach ($relData as $relPid => $weight) {
                            $relEshopId = $map[$menuId][$relPid];
                            if (isset($relEshopId) && $relEshopId != $epId) {
                                $r[$epId][$relEshopId] = $weight;
                            }
                        }
                    }
                    
                    if (is_array($r[$epId])) {
                        arsort($r[$epId]);
                        
                        $r[$epId] = array_slice($r[$epId], 0, 10, true);
                    }
                }
            }
        }
        
        foreach ($r as $epId => $near) {
            $rec = self::fetch($epId);
            
            if ($rec && ($rec->nearProducts != $near)) {
                $rec->nearProducts = $near;
                self::save($rec, 'nearProducts');
            }
        }
    }
    
    
    /**
     * Кои продукти са използвани в Е-маг
     *
     * @return array $eProductArr
     */
    public static function getProductsInEshop()
    {
        $query = eshop_ProductDetails::getQuery();
        $query->show('productId');
        $eProductArr = arr::extractValuesFromArray($query->fetchAll(), 'productId');
        
        return $eProductArr;
    }
    
    
    /**
     * Подготовка на рейтингите за продажба на артикулите
     * @see sales_RatingsSourceIntf
     *
     * @return array $res - масив с обекти за върнатите данни
     *                 o objectClassId - ид на клас на обект
     *                 o objectId      - ид на обект
     *                 o classId       - текущия клас
     *                 o key           - ключ
     *                 o value         - стойност
     */
    public function getSaleRatingsData()
    {
        $res = array();
        
        // Съответствие м/у артикулите и е-артикулите
        $details = array();
        $dQuery = eshop_ProductDetails::getQuery();
        $dQuery->EXT('groupId', 'eshop_Products', 'externalName=groupId,externalKey=eshopProductId');
        $dQuery->EXT('stateE', 'eshop_Products', 'externalName=state,externalKey=eshopProductId');
        $dQuery->where("#stateE = 'active'");
        while($dRec = $dQuery->fetch()){
            $domainId = self::getDomainId($dRec->eshopProductId);
            $details[$dRec->productId][$domainId] = $dRec->eshopProductId;
        }
        
        if(!countR($details)) {
            
            return $res;
        }
        
        $valiorFromTime = eshop_Setup::get('RATINGS_OLDER_THEN');
        $valiorFrom = dt::verbal2mysql(dt::addSecs(-1 * $valiorFromTime), false);
        
        // Подготовка на заявката за продажбите
        $deltaQuery = sales_PrimeCostByDocument::getQuery();
        $deltaQuery->where("#sellCost IS NOT NULL AND (#state = 'active' OR #state = 'closed') AND #isPublic = 'yes'");
        $deltaQuery->where("#valior >= '{$valiorFrom}'");
        $deltaQuery->show('productId,threadId,valior');
        
        $count = $deltaQuery->count();
        core_App::setTimeLimit($count * 0.4, false, 200);
        
        // Кои са нишките на онлайн продажби
        $cartQuery = eshop_Carts::getQuery();
        $cartQuery->EXT('threadId', 'sales_Sales', 'externalName=threadId,externalKey=saleId');
        $cartQuery->EXT('valior', 'sales_Sales', 'externalName=valior,externalKey=saleId');
        $cartQuery->where("#saleId IS NOT NULL AND #valior >= '{$valiorFrom}'");
        $cartQuery->show('threadId, domainId');
        $onlineSaleThreads = array();
        array_walk($cartQuery->fetchAll(), function ($a) use (&$onlineSaleThreads) {$onlineSaleThreads[$a->threadId] = $a->domainId;});
        
        while ($dRec = $deltaQuery->fetch()){
             
            // Ако реда е в онлайн продажба
            if(array_key_exists($dRec->threadId, $onlineSaleThreads)){
                
                // Кой е-артикул съответства на този артикул и домейнт
                $eshopProductId = $details[$dRec->productId][$onlineSaleThreads[$dRec->threadId]];
                
                // Ако има такъв
                if($eshopProductId){
                    
                    if(!array_key_exists($eshopProductId, $res)){
                        $res[$eshopProductId] = (object)array('classId'       => $this->getClassId(),
                            'objectClassId' => self::getClassId(),
                            'objectId'      => $eshopProductId,
                            'key'           => $onlineSaleThreads[$dRec->threadId],
                            'value'         => 0,);
                    }
                    
                    // Изчисляване на рейтинга му
                    $monthsBetween = countR(dt::getMonthsBetween($dRec->valior));
                    $rating = round(12 / $monthsBetween);
                    $res[$eshopProductId]->value += 1000 * $rating;
                }
            }
            
            // Проверява се във кои други домейни се среща този артикул
            if(array_key_exists($dRec->productId, $details)){
                
                // Ако се среща в поне един домейн
                $productInDomains =  $details[$dRec->productId];
                if(is_array($productInDomains)){
                    foreach ($productInDomains as $pDomainId => $eshopProductId){
                        if(!array_key_exists($eshopProductId, $res)){
                            $res[$eshopProductId] = (object)array('classId'       => $this->getClassId(),
                                                                  'objectClassId' => self::getClassId(),
                                                                  'objectId'      => $eshopProductId,
                                                                  'key'           => $pDomainId,
                                                                  'value'         => 0,);
                        }
                        
                        // Добавя се с по-малка тежест
                        $res[$eshopProductId]->value += 1;
                    }
                }
            }
        }
        
        return $res;
    }
}
