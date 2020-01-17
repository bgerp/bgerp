<?php


/**
 * Мениджър за "Бързи бутони"
 *
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
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
    public $listFields = 'productId, pointId, catId=Категория, createdOn, createdBy, state';
    
    
    /**
     * Заглавие на единичния обект
     */
    public $singleTitle = 'Бърз бутон';
    
    
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
        $this->FLD('catId', 'keylist(mvc=pos_FavouritesCategories, select=name)', 'caption=Категория, mandatory');
        $this->FLD('pointId', 'keylist(mvc=pos_Points, select=name, makeLinks)', 'caption=Точка на продажба');
        
        $this->setDbUnique('productId, packagingId');
        $this->setDbUnique('title');
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
     * Вербална обработка на продуктите
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
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
                foreach ($points as $pointId) {
                    $quantity = pos_Stocks::getBiggestQuantity($bRec->productId, $pointId);
                    
                    // Ако е ще се добави в група 'Налични (<име_на_групата>)', иначе се маха от нея
                    $groupId = pos_FavouritesCategories::fetchField("#name = 'Налични ({$cache[$pointId]})'");
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
