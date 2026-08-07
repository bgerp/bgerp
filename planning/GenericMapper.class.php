<?php


/**
 * Съответствие с генерични артикули
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_GenericMapper extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Съответствия с генерични артикули';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, planning_Wrapper, plg_SaveAndNew';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,planning';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,planning';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,planning';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,planning';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId,productMeasureId=Мярка,genericProductId=Генеричен артикул,genericProductMeasureId=Мярка генеричен артикул,createdOn,createdBy'; 
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Съответствие с генерични артикули';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();


    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'id,productId';


    /**
     * До колко рецепти да се разлиства пейджъра в таб 'Употреба' - над това само съобщение за общия брой
     */
    public $maxBomsForPaging = 500;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=canConvert,hasnotProperties=generic,maxSuggestions=100,forceAjax,titleFld=name,forceOpen)', 'caption=Замества,mandatory,silent,tdClass=leftCol wrapText ,class=w100');
        $this->FLD('genericProductId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=generic,maxSuggestions=100,forceAjax,titleFld=name,forceOpen)', 'caption=Генеричен артикул,mandatory,silent,tdClass=leftCol wrapText,class=w100');
        $this->FNC('fromGeneric', 'int', 'silent,input=hidden');
        
        $this->setDbUnique('productId,genericProductId');
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $rec = $data->form->rec;
        $productId = $rec->productId;
        if(empty($rec->id) && empty($productId)){
            $productId = $rec->genericProductId;
        }
        
        $data->form->title = core_Detail::getEditTitle('cat_Products', $productId, $mvc->singleTitle, $rec->id ?? null);
		
		if (empty($rec->genericProductId)) {
				$data->form->toolbar->removeBtn('saveAndNew');
			}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        
        if(empty($rec->id) && isset($rec->genericProductId)){
            $form->setField('genericProductId', 'input=hidden');
        } else {
            $form->setField('productId', 'input=hidden');
        }
    }
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;

            $productRec = cat_Products::fetch($rec->productId, 'measureId,canStore');
            $genericRec = cat_Products::fetch($rec->genericProductId, 'measureId,canStore');

            $convertedMainProduct = cat_Products::convertToUom($rec->productId, $genericRec->measureId);
            $convertedGenericProduct = cat_Products::convertToUom($rec->genericProductId, $productRec->measureId);
            if(!$convertedMainProduct && ! $convertedGenericProduct){
                $measureId = ($rec->fromGeneric) ? $genericRec->measureId : $productRec->measureId;
                $measureName = cat_UoM::getVerbal($measureId, 'name');
                $msg = ($rec->fromGeneric) ? "Заместващият артикул трябва да е в основна или втора мярка, производна на|*: <b>{$measureName}</b>" : "Генеричният артикул трябва да е в основна или втора мярка, производна на|*: <b>{$measureName}</b>";
                $form->setError('productId', $msg);
            }
            
            if($productRec->canStore != $genericRec->canStore){
                $form->setError('productId', "И двата артикула, трябва да са складируеми или само услуги");
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->genericProductId = cat_Products::getHyperlink($rec->genericProductId, true);
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        $row->created = $row->createdOn . " " . tr('от||by') . " " . $row->createdBy;
        
        $row->genericProductMeasureId = cat_UoM::getVerbal(cat_Products::fetchField($rec->genericProductId, 'measureId'), 'name');
        $row->productMeasureId = cat_UoM::getVerbal(cat_Products::fetchField($rec->productId, 'measureId'), 'name');
        
        $pRec = cat_Products::fetch($rec->productId, 'canConvert,state');
        $row->ROW_ATTR['class'] = "state-{$pRec->state}";
        if($pRec->canConvert != 'yes'){
            $row->productId = ht::createHint($row->productId, "Артикулът вече не е вложим", 'warning', false);
        }
    }
    
    
    /**
     * Подготвя таб 'Употреба' - заместващите артикули и рецептите, в които
     * артикулът участва като ред
     */
    public function prepareResources(&$data)
    {
        if (!haveRole('ceo,planning') || $data->masterData->rec->canConvert != 'yes') {
            $data->notConvertableAnymore = true;
        }

        // Подготовка на заместващите артикули
        $data->genData = clone $data;
        $this->prepareGenericData($data->genData);

        // Подготовка на рецептите където участва
        $data->recData = clone $data;
        $this->prepareBoms($data->recData);

        if(!empty($data->notConvertableAnymore) && !countR($data->genData->rows) && !countR($data->recData->rows)){
            $data->hide = true;

            return $data;
        }

        // Не е само "Влагане" - тук е употребата на артикула изобщо: в кои чужди
        // рецепти участва и в каква роля (вкл. изходните Отпадък, Субпродукт и
        // Произвеждане от разпад), както и какво го замества - @see #Tsk9167
        $data->TabCaption = 'Употреба';
        $data->Tab = 'top';
    }


    /**
     * Подготвя данните на заместващите артикули
     *
     * @param $data
     * @return void
     */
    private function prepareGenericData($data)
    {
        $data->isGeneric = $data->masterData->rec->generic;
        $data->rows = array();
        $query = $this->getQuery();

        if($data->isGeneric == 'yes'){
            $listFields = "productId=Заместващ артикул,productMeasureId=Мярка,created=Създаване";
            $query->where("#genericProductId = {$data->masterId}");
        } else {
            $listFields = "genericProductId=Генеричен артикул,genericProductMeasureId=Мярка,created=Създаване";
            $query->where("#productId = {$data->masterId}");
        }
        while ($rec = $query->fetch()) {
            $data->rows[$rec->id] = $this->recToVerbal($rec);
        }

        $data->listFields = arr::make($listFields, true);
        if (!Mode::is('printing') && !Mode::is('inlineDocument')) {
            if($data->isGeneric == 'yes'){
                if (self::haveRightFor('add', (object) array('genericProductId' => $data->masterId))) {
                    $data->addUrl = array($this, 'add', 'genericProductId' => $data->masterId, 'fromGeneric' => true, 'ret_url' => true);
                }
            } else {
                if (self::haveRightFor('add', (object) array('productId' => $data->masterId))) {
                    $data->addUrl = array($this, 'add', 'productId' => $data->masterId, 'ret_url' => true);
                }
            }
        }
    }


    /**
     * Рендира данните на заместващите артикули
     *
     * @param $data
     * @return core_ET $tpl
     */
    private function renderGenericData($data)
    {
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');

        if (($data->notConvertableAnymore ?? null) === true) {
            if(countR($data->rows)){
                $title = tr('Артикулът вече не е вложим');
                $title = "<small class='red'>{$title}</small>";
                $tpl->append($title, 'title');
                $tpl->replace('state-rejected', 'TAB_STATE');
            } else {
                return new core_ET("");
            }
        } else {
            $tpl->append(tr('Заместващи артикули'), 'title');
        }

        $listTableMvc = clone $this;
        $table = cls::get('core_TableView', array('mvc' => $listTableMvc));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));

        $tpl->append($table->get($data->rows, $data->listFields), 'content');

        if (isset($data->addUrl)) {
            $addLink = ht::createLink('', $data->addUrl, false, 'ef_icon=img/16/add.png,title=Добавяне на информация за влагане');
            $tpl->append($addLink, 'title');
        }

        return $tpl;
    }


    /**
     * Подготвяне на рецептите, в които артикулът участва като ред - технологични
     * (като ресурс) и за разпад (@see #Tsk9167)
     *
     * @param stdClass $data
     * @return void
     */
    public function prepareBoms(&$data)
    {
        $data->rows = array();

        // Таблицата се рендира от cat_Boms::renderBoms, но заглавието ѝ се слага
        // тук - в този таб рецептите са с друг смисъл (@see renderResources)
        $data->fromConvertable = true;

        // Рецептите, в които артикулът участва като ред - технологичните (като
        // ресурс) и тези за разпад (@see #Tsk9167). В коя роля участва се вижда
        // в колонката 'Като' - и досега там имаше не само вложими роли, а и
        // Отпадък/Субпродукт, които всъщност са изходи от рецептата
        $bomSections = array(
            array('details'       => cls::get('cat_BomDetails'),
                  'master'       => cls::get('cat_Boms'),
                  'productFld'   => 'resourceId',
                  'prefix'       => 'b',
                  'isDisassembly' => false,
            ),
            array('details'       => cls::get('cat_DisassemblyBomDetails'),
                  'master'       => cls::get('cat_DisassemblyBoms'),
                  'productFld'   => 'productId',
                  'prefix'       => 'd',
                  'isDisassembly' => true,
            ),
        );

        // Първо се събират редовете от двата вида рецепти, пейджърът се прилага
        // после върху общия списък (@see core_Pager::isOnPage)
        $allRecs = array();
        foreach ($bomSections as $section) {
            $query = $section['details']->getQuery();

            // Състоянието на рецептата се взима под друго име - детайлът на
            // рецептата за разпад си има собствено поле `state` за оттеглените
            // ревизии на реда (@see doc_plg_DetailRevisions)
            $query->EXT('bomState', get_class($section['master']), 'externalName=state,externalKey=bomId');
            $query->XPR('orderByState', 'int', "(CASE #bomState WHEN 'active' THEN 1 WHEN 'closed' THEN 2 ELSE 3 END)");
            $query->where("#{$section['productFld']} = {$data->masterId} AND #bomState != 'rejected'");

            // Оттеглените ревизии на реда не участват - артикулът може вече да
            // не е в рецептата
            if ($section['details']->getField('state', false)) {
                $query->where("#state != 'rejected' OR #state IS NULL");
            }

            $query->groupBy('bomId');
            $query->orderBy('orderByState', 'ASC');

            while ($rec = $query->fetch()) {
                $allRecs[] = array('rec' => $rec, 'section' => $section);
            }
        }

        // Странициране на записите - ограничено до maxBomsForPaging, за да не се получи пейджър с хиляди страници
        $data->Pager = cls::get('core_Pager', array('itemsPerPage' => 20));
        $data->Pager->setPageVar('cat_Products', $data->masterId, 'cat_Boms');
        $data->totalCount = countR($allRecs);
        $data->Pager->itemsCount = min($data->totalCount, $this->maxBomsForPaging);

        $shortUom = tr(cat_UoM::getShortName($data->masterData->rec->measureId));
        $Param = core_Request::get($data->masterData->tabTopParam, 'varchar');
        $now = dt::now();

        foreach ($allRecs as $item) {
            if (!$data->Pager->isOnPage()) continue;

            $rec = $item['rec'];
            $section = $item['section'];
            $Master = $section['master'];
            $key = $section['prefix'] . $rec->id;

            $bomRec = $Master->fetch($rec->bomId);
            $row = $Master->recToVerbal($bomRec);

            // Вид на рецептата
            $typeVerbal = $section['isDisassembly'] ? tr('Разпад') : ($row->type ?? null);
            $row->type = cat_Boms::renderTypeBadge($typeVerbal, $section['master'], $bomRec);

            // В каква роля участва артикулът
            $actionVerbal = $section['details']->getFieldType('type')->toVerbal($rec->type);
            $actionClass = ($rec->type == 'input') ? '#e6ffe0' : ($rec->type == 'production' ? '#ffe0e0' : ($rec->type == 'pop' ? '#cce3fe' : '#ece2ff'));
            $row->action = "<div class='document-handler' style='background-color:{$actionClass};'>{$actionVerbal}</div>";

            // Изчисляване за какво количество е вложено, ако се показват рецептите, в които е вложена
            if (!$section['isDisassembly'] && $Param == 'Resources') {
                $rInfo = cat_Boms::getResourceInfo($bomRec->id, 1, $now);

                if(is_array($rInfo['resources'])){
                    $foundRec = array_filter($rInfo['resources'], function($a) use ($data){return $a->productId == $data->masterId;});
                    $quantityVerbal = "<span class='red'>???</span>";
                    if($foundRec[key($foundRec)]->propQuantity){
                        $quantityVerbal = core_Type::getByName('double(smartRound)')->toVerbal($foundRec[key($foundRec)]->propQuantity);
                    }
                    $row->quantity = "{$quantityVerbal} {$shortUom}";
                }
            }

            $data->rows[$key] = $row;
        }
    }


    /**
     * Рендира таб 'Употреба'
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderResources(&$data)
    {
        if (!empty($data->hide)) return;

        $tpl = new core_ET("[#generic#]<div style='margin-top:10px'>[#boms#]</div>");
        $genTpl = $this->renderGenericData($data->genData);
        $tpl->replace($genTpl, 'generic');

        $recTpl = cls::get('cat_Boms')->renderBoms($data->recData);
        $recTpl->append(tr('Рецепти, в които участва'), 'title');

        // При повече от maxBomsForPaging рецепти - пейджърът е ограничен, показваме реалния общ брой
        if ($data->recData->totalCount > $data->recData->Pager->itemsCount) {
            $msg = tr('Показани са само първите') . " {$data->recData->Pager->itemsCount} " . tr('от общо') . " {$data->recData->totalCount}";
            $recTpl->append("<div class='quiet small' style='margin-top:5px'>{$msg}</div>", 'content');
        }

        $tpl->replace($recTpl, 'boms');

        return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'delete' || $action == 'edit') && isset($rec)) {
            
            // Не може да добавяме запис ако не може към обекта, ако той е оттеглен или ако нямаме достъп до сингъла му
            if(isset($rec->productId)){
                $masterRec = cat_Products::fetch($rec->productId, 'state,canConvert,generic');
                if ($masterRec->state != 'active' || !cat_Products::haveRightFor('single', $rec->productId)) {
                    $res = 'no_one';
                } elseif($action != 'delete' && ($masterRec->canConvert != 'yes' || $masterRec->generic == 'yes')) {
                    $res = 'no_one';
                }
            }
            
            if(isset($rec->genericProductId)){
                $masterRec = cat_Products::fetch($rec->genericProductId, 'state,canConvert,generic');
                if ($masterRec->state != 'active' || !cat_Products::haveRightFor('single', $rec->genericProductId)) {
                    $res = 'no_one';
                } elseif($masterRec->generic != 'yes') {
                    $res = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Връща среднопритеглената цена на артикула в сметката на незавършеното производство
     *
     * @param int  $quantity - к-во
     * @param int  $objectId - ид на артикул
     * @param datetime $date     - към коя дата
     *
     * @return float $selfValue - среднопритеглената цена
     */
    public static function getWacAmountInProduction($quantity, $objectId, $date)
    {
        // Ако не е складируем взимаме среднопритеглената му цена в производството
        $item1 = acc_Items::fetchItem('cat_Products', $objectId)->id;
        if (isset($item1)) {
            $pricesArr = acc_ProductPricePerPeriods::getPricesToDate($date, $item1, null, 'production');
            $countPricesBefore = countR($pricesArr);
            if($countPricesBefore){
                $priceSum = arr::sumValuesArray($pricesArr, 'price');

                return round($quantity * ($priceSum / $countPricesBefore), 4);
            }
        }

        return null;
    }



    /**
     * Връща среднопритеглената цена на артикула в сметката за разходите за услуги
     *
     * @param int  $quantity - к-во
     * @param int  $objectId - ид на артикул
     * @param datetime $date     - към коя дата
     * @param null|int $costObjectItemId     - към коя дата
     * @return float $selfValue - среднопритеглената цена
     */
    public static function getWacAmountInAllCostsAcc($quantity, $objectId, $date, $costObjectItemId = null)
    {
        // Ако не е складируем взимаме среднопритеглената му цена в производството
        $item1 = acc_Items::fetchItem('cat_Products', $objectId)->id;
        if (isset($item1)) {
            $pricesArr = acc_ProductPricePerPeriods::getPricesToDate($date, $item1, $costObjectItemId, 'costs');
            $countPricesBefore = countR($pricesArr);
            if($countPricesBefore){
                $priceSum = arr::sumValuesArray($pricesArr, 'price');

                return round($quantity * ($priceSum / $countPricesBefore), 4);
            }
        }

        return null;
    }


    /**
     * Намира средната еденична цена на всички заместващи артикули на подаден артикул
     *
     * @param int         $productId - артикул, чиято средна цена търсим
     * @param string|NULL $date      - към коя дата
     *
     * @return NULL|float $avgPrice - средна цена
     */
    public static function getAvgPriceEquivalentProducts($productId, $date = null)
    {
        $avgPrice = null;
        expect($productId);
        
        // Проверяваме за тази група артикули, имали кеширана средна цена
        $cacheKeys = preg_grep("|{$productId}|", array_keys(static::$cache));
        $cacheKey = reset($cacheKeys);
        if ($cacheKey !== false && array_key_exists($cacheKey, static::$cache)) {
            $cachePrice = static::$cache[$cacheKey];
            
            return $cachePrice;
        }
        
        // Ако артикула не е вложим, не търсим средна цена
        $isConvertable = cat_Products::fetchField($productId, 'canConvert');
        if ($isConvertable != 'yes') {
            
            return $avgPrice;
        }
        
        // Ако няма заместващи артикули, не търсим средна цена
        $equivalentProducts = static::getEquivalentProducts($productId, null, true);
        if (!countR($equivalentProducts)) {
            
            return $avgPrice;
        }
        
        // Ще се опитаме да намерим средната цена на заместващите артикули
        $priceSum = $count = 0;
        $listId = price_ListRules::PRICE_LIST_COST;
        
        foreach ($equivalentProducts as $pId => $pName) {
            $price = price_ListRules::getPrice($listId, $pId, null, $date);
            
            // Ако има себестойност прибавяме я към средната
            if (isset($price)) {
                $priceSum += $price;
                $count++;
            }
        }
        
        // Ако има намерена ненулева цена, изчисляваме средната
        if ($count !== 0) {
            $avgPrice = round($priceSum / $count, 8);
        }
        
        // За тази група артикули, кеширваме в паметта средната цена
        $index = keylist::fromArray($equivalentProducts);
        static::$cache[$index] = $avgPrice;
        
        // Връщаме цената ако е намерена
        return $avgPrice;
    }
    
    
    /**
     * Връща себестойността на материала
     *
     * @param int $productId - ид на артикула - материал
     *
     * @return float $selfValue - себестойността му
     */
    public static function getSelfValue($productId, $quantity = 1, $date = null)
    {
        if (empty($productId)) {
            
            return;
        }
        
        // Проверяваме имали зададена търговска себестойност
        $selfValue = cat_Products::getPrimeCost($productId, null, $quantity, $date);
        
        // Ако няма търговска себестойност: проверяваме за счетоводна
        if (!isset($selfValue)) {
            if (!$date) {
                $date = dt::now();
            }

            // Ако артикула е складируем взимаме среднопритеглената му цена от склада
            $canStore = cat_Products::fetchField($productId, 'canStore');
            if ($canStore == 'yes') {
                $selfValue = cat_Products::getWacAmountInStore($quantity, $productId, $date);
            } else {
                $selfValue = static::getWacAmountInProduction($quantity, $productId, $date);
            }
        }
        
        return $selfValue;
    }


    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    protected function on_BeforeSave(&$mvc, &$id, &$rec, $fields = null)
    {
        if(empty($rec->id)){
            $rec->_updateHorizons = true;
        } else {
            $oldRec = $mvc->fetch($rec->id, '*', false);
            if($oldRec->genericProductId != $rec->genericProductId || $oldRec->productId != $rec->productId){
                $rec->_updateHorizons = true;
            }
        }
    }


    /**
     * След изтриване в детайла извиква събитието 'AfterUpdateDetail' в мастъра
     */
    protected static function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            self::updateStocksPlanningByProductId($rec);
        }
    }


    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave($mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if(!empty($rec->_updateHorizons)) {
            self::updateStocksPlanningByProductId($rec);
        }
    }


    /**
     * Обновяване на себестойностите
     *
     * @param $rec
     * @return void
     */
    private static function updateStocksPlanningByProductId($rec)
    {
        // В хоризонтите се обновява генеричния артикул на зададения
        $Stocks = cls::get('store_StockPlanning');
        $tableName = $Stocks->dbTableName;
        $productIdColName = str::phpToMysqlName('productId');
        $genericProductIdColName = str::phpToMysqlName('genericProductId');

        $genericProductId = !empty($rec->genericProductId) ? $rec->genericProductId : "NULL";
        $query = "UPDATE {$tableName} SET {$genericProductIdColName} = {$genericProductId} WHERE {$tableName}.{$productIdColName} = {$rec->productId}";

        $Stocks->db->query($query);
    }

    /**
     * Помощна ф-я за работа с генеричните артикули
     *
     * @param int $productId
     * @param int|null $genericProductId
     * @param bool $onlyIfOne
     * @return null|core_Query
     */
    public static function getHelperQuery($productId, $genericProductId = null, $onlyIfOne = false)
    {
        if (isset($genericProductId)) {
            $generics[$genericProductId] = $genericProductId;
        } else {
            if (planning_GenericMapper::fetchField("#genericProductId = {$productId}")) {
                $generics[$productId] = $productId;
            } else {
                $gQuery = planning_GenericMapper::getQuery();
                $gQuery->where("#productId = {$productId}");
                $gQuery->show('genericProductId');
                if($onlyIfOne){
                    $gQuery->limit(1);
                }
                $generics = arr::extractValuesFromArray($gQuery->fetchAll(), 'genericProductId');
            }
        }

        $count = countR($generics);
        if (!$count) return null;

        // Всички артикули, които се влагат като търсения, или се влагат като неговия генеричен
        $query = planning_GenericMapper::getQuery();
        $query->EXT('state', 'cat_Products', 'externalName=state,externalKey=productId');
        $query->EXT('canConvert', 'cat_Products', 'externalName=canConvert,externalKey=productId');
        $query->where("#state = 'active' AND #canConvert = 'yes'");
        $query->in("genericProductId", $generics);
        $query->show('productId,genericProductId');

        return $query;
    }



    /**
     * Намира еквивалентите за влагане артикули на даден артикул
     *
     * @param int $productId             - на кой артикул му търсим еквивалентните
     * @param int|null $genericProductId - конкретен генеричен артикул
     * @param bool $onlyIfGenericIsOne   - дали да се върне само ако има един генеричен артикул
     *
     * @return array  $res               - масив за избор с еквивалентни артикули
     */
    public static function getEquivalentProducts($productId, $genericProductId = null, $onlyIfGenericIsOne = true, $verbal = false)
    {
        $res = array();
        if($query = static::getHelperQuery($productId, $genericProductId, $onlyIfGenericIsOne)){
            while ($dRec = $query->fetch()) {
                $res[$dRec->productId] = ($verbal) ? cat_Products::getTitleById($dRec->productId, false) : $dRec->productId;
                if(!array_key_exists($dRec->genericProductId, $res)){
                    $res[$dRec->genericProductId] = ($verbal) ? cat_Products::getTitleById($dRec->genericProductId, false) : $dRec->genericProductId;
                }
            }
        }

        return $res;
    }


}
