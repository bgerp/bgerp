<?php


/**
 * Мениджър за "Бързи бутони"
 *
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class pos_Favourites extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Продукти за бързи бутони';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_Sorting, plg_Printing, pos_Wrapper, plg_State2';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, pack=Мярка/Опаковка, title, pointId, catId=Категория, createdOn, createdBy, state';
    
    
    /**
     * Заглавие на единичния обект
     */
    public $singleTitle = 'Бърз бутон';
    
    
    /**
     * Кой може да го прочете?
     */
    public $canRead = 'ceo, pos';
    
    
    /**
     * Кой може да променя?
     */
    public $canAdd = 'ceo, pos';
    
    
    /**
     * Кой може да променя?
     */
    public $canEdit = 'pos, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,pos';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,pos';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, pos';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=canSell,maxSuggestions=100,forceAjax)', 'class=w100,caption=Продукт, mandatory, silent,refreshForm');
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName)', 'caption=Опаковка,mandatory');
        $this->FLD('catId', 'keylist(mvc=pos_FavouritesCategories, select=name)', 'caption=Категория, mandatory');
        $this->FLD('title', 'varchar(32)', 'caption=Заглавие');
        $this->FLD('pointId', 'keylist(mvc=pos_Points, select=name, makeLinks)', 'caption=Точка на продажба');
        $this->FLD('image', 'fileman_FileType(bucket=pos_ProductsImages)', 'caption=Картинка');
        
        $this->setDbUnique('productId, packagingId');
        $this->setDbUnique('title');
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        if (isset($form->rec->productId)) {
            $packs = cat_Products::getPacks($form->rec->productId);
            $form->setOptions('packagingId', $packs);
        } else {
            $form->setReadOnly('packagingId');
        }
    }
    
    
    /**
     * Метод подготвящ продуктите и формата за филтриране
     *
     * @return stdClass - обект съдържащ пос продуктите за текущата
     *                  точка и формата за филтриране
     */
    public static function prepareProducts($rec)
    {
        $self = cls::get(get_called_class());
        $productsArr = $self->preparePosProducts();
        
        $categoriesArr = pos_FavouritesCategories::prepareAll($rec->pointId);
        
        return (object) array('arr' => $productsArr, 'categories' => $categoriesArr, 'rec' => $rec);
    }
    
    
    /**
     * Подготвя продуктите за показване в пос терминала
     *
     * @return array $arr - масив от всички позволени продукти
     */
    public function preparePosProducts()
    {
        $varchar = cls::get('type_Varchar');
        $double = cls::get('type_Double');
        $double->params['decimals'] = 2;
        
        // Коя е текущата точка на продажба и нейния дефолт контрагент
        $posRec = pos_Points::fetch(pos_Points::getCurrent('id'));
        $cache = NULL;core_Cache::get('pos_Favourites', "products{$posRec->id}");
        if (!$cache) {
            $query = static::getQuery();
            $query->where('#pointId IS NULL');
            $query->orWhere("#pointId LIKE '%{$posRec->id}%'");
            $query->where("#state = 'active'");
            
            while ($rec = $query->fetch()) {
                $obj = $this->prepareProductObject($rec);
                $obj->name = tr($varchar->toVerbal($obj->name));
                $obj->productId = $rec->productId;
                $obj->packagingId = $rec->packagingId;
                $cache[$rec->id] = $obj;
            }
            //core_Cache::set('pos_Favourites', "products{$posRec->id}", $cache, 10, array('cat_Products'));
        }
        
        if ($cache) {
            $date = dt::verbal2mysql();
            foreach ($cache as &$obj) {
                
                // За всеки обект от кеша, изчисляваме актуалната му цена
                $price = price_ListRules::getPrice($posRec->policyId, $obj->productId, $obj->packagingId, $date);
                $pack = cat_products_Packagings::getPack($obj->productId, $obj->packagingId);
                if ($pack) {
                    $price *= $pack->quantity;
                    $shortUom = cat_UoM::getShortName($obj->packagingId);
                    $obj->name .= " ({$shortUom})";
                }
                
                if (!is_null($price)) {
                    $vat = cat_Products::getVat($obj->productId, $date);
                    $obj->price = $double->toVerbal($price * (1 + $vat));
                } else {
                    $obj->price = '<span>N/A</span>';
                }
            }
        }
        
        return $cache;
    }
    
    
    /**
     * След запис в модела
     */
    protected static function on_AfterSave($mvc, &$id, $rec)
    {
        // Инвалидираме кеша
        $cPoint = pos_Points::getCurrent('id', null, false);
        core_Cache::remove('pos_Favourites', "products{$cPoint}");
    }
    
    
    /**
     * Подготвяме единичен продукт от модела
     *
     * @param stdClass $rec - запис от модела
     *
     * @return stdClass $obj - обект с информацията за продукта
     */
    public function prepareProductObject($rec)
    {
        // Информацията за продукта с неговата опаковка
        $info = cat_Products::getProductInfo($rec->productId);
        $productRec = $info->productRec;
        
        $arr = array();
        $arr['name'] = $productRec->name;
        $arr['title'] = $rec->title;
        $arr['catId'] = $rec->catId;
        $obj = new stdClass();
        $obj->quantity = (isset($info->packagings[$rec->packagingId])) ? $info->packagings[$rec->packagingId]->quantity : 1;
        
        if ($rec->image) {
            $arr['image'] = $rec->image;
        } else {
            $arr['image'] = cat_Products::getParams($rec->productId, 'preview');
        }
        
        return (object) $arr;
    }
    
    
    /**
     * Рендираме Продуктите и техните категории в подходящ вид
     *
     * @param stdClass $data - обект съдържащ масивите с продуктите,
     *                       категориите и темата по подразбиране
     *
     * @return core_ET $tpl - шаблона с продуктите
     */
    public static function renderPosProducts($data)
    {
        $file  = Mode::is('screenMode', 'narrow') ? 'pos/tpl/FavouritesNarrow.shtml' : 'pos/tpl/terminal/Favourites.shtml';
        $tpl = getTplFromFile($file);
        
        $self = cls::get(get_called_class());
        if ($data->arr) {
            $self->renderProducts($data, $tpl);
        }
        if ($data->categories) {
            $self->renderCategories($data->categories, $tpl);
        }
        
        return $tpl;
    }
    
    
    /**
     * Рендира категориите на продуктите в удобен вид
     *
     * @param array   $categories - Масив от продуктовите категории
     * @param core_ET $tpl        - шаблона в който ще поставяме категориите
     */
    public function renderCategories($categories, &$tpl)
    {
        if(Mode::is('screenMode', 'narrow')){
            $categoryOptions = array('' => tr('Всички'));
            foreach ($categories as $catObj){
                $categoryOptions[$catObj->id] = $catObj->name;
            }
            $selectHtml = ht::createSelect('favouriteCategories', $categoryOptions,  '', array('class' => 'pos-product-category button', 'title' => 'Показване на артикулите от категория'));
            $tpl->append($selectHtml, 'CATEGORY_SELECT');
        } else {
            $blockTpl = $tpl->getBlock('CAT');
            foreach ($categories as $cat) {
                $rowTpl = clone($blockTpl);
                $rowTpl->placeObject($cat);
                $rowTpl->removeBlocks();
                $rowTpl->append2master();
            }
        }
    }
    
    
    /**
     * Рендира продуктите във вид подходящ за пос терминала
     *
     * @param array $products - масив от продукти с информацията за тях
     *
     * @return core_ET $tpl - шаблон с продуктите
     */
    public function renderProducts($data, &$tpl)
    {
        $products = $data->arr;
        $blockTpl = $tpl->getBlock('ITEM');
       
        $cnt = 0;
        foreach ($products as $row) {
            $row->url = toUrl(array('pos_ReceiptDetails', 'addProduct', 'receiptId' => $data->rec->id), 'local');
            $row->name = ($row->title) ? $row->title : $row->name;
            $row->elementId = "product{$row->productId}";
            if ($row->image) {
                $img = new thumb_Img(array($row->image, 80, 80, 'fileman', 'isAbsolute' => false, 'mode' => 'large-no-change'));
                $imageURL = $img->getUrl('forced');
                $row->image = ht::createElement('img', array('src' => $imageURL, 'width' => '120px', 'height' => '120px'));
            }
            
            if($cnt == 0){
                $row->CLASS = 'selected';
            }
            
            $rowTpl = clone($blockTpl);
            $rowTpl->placeObject($row);
            $rowTpl->removeBlocks();
            $rowTpl->append2master();
            $cnt++;
        }
    }
    
    
    /**
     * Вербална обработка на продуктите
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if(!$rec->image) {
            $rec->image =  cat_Products::getParams($rec->productId, 'preview');
        
        }
        if ($rec->image) {
            $Fancybox = cls::get('fancybox_Fancybox');
            $row->image = $Fancybox->getImage($rec->image, array(30, 30), array(400, 400));
        }
        
        // До името на продукта показваме неговата основна мярка и ако
        // има зададена опаковка - колко броя в опаковката има.
        $info = cat_Products::getProductInfo($rec->productId);
        $quantity = $info->packagings[$rec->packagingId]->quantity;
        $row->pack = $mvc->getFieldType('packagingId')->toVerbal($rec->packagingId);
        
        // Показваме подробната информация за опаковката при нужда
        deals_Helper::getPackInfo($row->pack, $rec->productId, $rec->packagingId, $quantity);
        
        if (!$rec->pointId) {
            $row->pointId = tr('Всички');
        }
        
        $row->productId = cat_Products::getHyperLink($rec->productId, true);
    }
    
    
    /**
     * Крон метод който добавя група на бързите бутони че са налични във въпросната точка
     */
    public function cron_UpdateButtonsGroup()
    {
        // Ако няма бутони не се прави нищо
        if (!pos_Favourites::count()) {
            
            return;
        }
        
        // Кеширане на данни за хита
        $cache = array();
        $pQuery = pos_Points::getQuery();
        while ($pRec = $pQuery->fetch()) {
            $cache[$pRec->id] = $pRec->name;
        }
        
        $all = array_combine(array_keys($cache), array_keys($cache));
        
        // За всеки бърз бутон
        $bQuery = pos_Favourites::getQuery();
        while ($bRec = $bQuery->fetch()) {
            
            // В кои точки ще се показва
            $points = keylist::toArray($bRec->pointId);
            if (!count($points)) {
                $points = $all;
            }
            
            // За всяка точка
            if (is_array($points)) {
                foreach ($points as $p) {
                    
                    // Гледа се дали артикула е наличен в нея
                    $quantity = pos_Stocks::getQuantity($bRec->productId, $p);
                    
                    // Ако е ще се добави в група 'Налични (<име_на_групата>)', иначе се маха от нея
                    $groupId = pos_FavouritesCategories::fetchField("#name = 'Налични ({$cache[$p]})'");
                    if ($groupId) {
                        if ($quantity > 0) {
                            $bRec->catId = keylist::addKey($bRec->catId, $groupId);
                        } else {
                            $bRec->catId = keylist::removeKey($bRec->catId, $groupId);
                        }
                    }
                }
            }
            
            // Запис на категорията
            $this->save_($bRec, 'catId');
        }
        
        // Чистене на кеша за всеки случай
        core_Cache::removeByType('pos_Favourites');
    }
    
    
    /**
     * Извиква се преди подготовката на колоните
     */
    public static function on_AfterPrepareListFields($mvc, &$res, $data)
    {
        if (doc_Setup::get('LIST_FIELDS_EXTRA_LINE') != 'no') {
            $data->listFields['catId'] = '@' . $data->listFields['catId'];
        }
    }
}
