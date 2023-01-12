<?php


/**
 * Съответствие с генерични артикули
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
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
    public $loadList = 'plg_RowTools2, plg_Created, planning_Wrapper';
    
    
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
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=canConvert,hasnotProperties=generic,maxSuggestions=100,forceAjax,titleFld=name)', 'caption=Замества,mandatory,silent,tdClass=leftCol,class=w100');
        $this->FLD('genericProductId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=generic,maxSuggestions=100,forceAjax,titleFld=name)', 'caption=Генеричен артикул,mandatory,silent,tdClass=leftCol,class=w100');
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
        
        $data->form->title = core_Detail::getEditTitle('cat_Products', $productId, $mvc->singleTitle, $rec->id);
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
            $query = self::getQuery();
            $query->show('productId');
            $alreadySelectedProductsArr = arr::extractValuesFromArray($query->fetchAll(), 'productId');
            $form->setFieldTypeParams("productId", array('notIn' => $alreadySelectedProductsArr));
            
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
            
            $similarMeasures = cat_UoM::getSameTypeMeasures($genericRec->measureId);
            if(!array_key_exists($productRec->measureId, $similarMeasures)){
                $genericMeasureName = cat_UoM::getVerbal($genericRec->measureId, 'name');
                
                $form->setError('productId', "Заместващият артикул трябва да е в мярка, производна на|*: <b>{$genericMeasureName}</b>");
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
     * Подготвя показването на информацията за влагане
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

        if($data->notConvertableAnymore && !countR($data->genData->rows) && !countR($data->recData->rows)){
            $data->hide = true;

            return $data;
        }

        $data->TabCaption = 'Влагане';
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

        if ($data->notConvertableAnymore === true) {
            $title = tr('Артикулът вече не е вложим');
            $title = "<small class='red'>{$title}</small>";
            $tpl->append($title, 'title');
            $tpl->replace('state-rejected', 'TAB_STATE');
        } else {
            $tpl->append(tr('Влагане'), 'title');
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
     * Подготвяне на рецептите за един артикул
     *
     * @param stdClass $data
     * @return void
     */
    public function prepareBoms(&$data)
    {
        $data->rows = array();
        $data->fromConvertable = true;

        // Намираме Рецептите където се използва
        $query = cat_BomDetails::getQuery();
        $query->EXT('state', 'cat_Boms', 'externalName=state,externalKey=bomId');
        $query->XPR('orderByState', 'int', "(CASE #state WHEN 'active' THEN 1 WHEN 'closed' THEN 2 ELSE 3 END)");
        $query->where("#resourceId = {$data->masterId}");
        $query->where("#state != 'rejected'");
        $query->groupBy('bomId');
        $query->orderBy('orderByState', 'ASC');
        $data->recs = $query->fetchAll();

        // Странициране на записите
        $data->Pager = cls::get('core_Pager', array('itemsPerPage' => 20));
        $data->Pager->setPageVar('cat_Products', $data->masterId, 'cat_Boms');
        $data->Pager->itemsCount = countR($data->recs);

        foreach ($data->recs as $rec) {
            if (!$data->Pager->isOnPage()) continue;
            $bomRec = cat_Boms::fetch($rec->bomId);
            $data->rows[$rec->id] = cat_Boms::recToVerbal($bomRec);
        }
    }


    /**
     * Рендира показването на ресурси
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderResources(&$data)
    {
        if($data->hide) return;

        $tpl = new core_ET("[#generic#]<div style='margin-top:10px'>[#boms#]</div>");
        $genTpl = $this->renderGenericData($data->genData);
        $tpl->replace($genTpl, 'generic');

        $recTpl = cls::get('cat_Boms')->renderBoms($data->recData);
        $recTpl->append(tr('Технологични рецепти, в които участва'), 'title');
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
     * Намира еквивалентите за влагане артикули на даден артикул
     *
     * @param int $productId             - на кой артикул му търсим еквивалентните
     * @param int|null $ignoreRecId      - ид на ред, който да се игнорира
     * @param int|null $genericProductId - конкретен генеричен артикул
     *
     * @return array  $res               - масив за избор с еквивалентни артикули
     */
    public static function getEquivalentProducts($productId, $ignoreRecId = null, $genericProductId = null)
    {
        $res = array();
        
        $inArr = array($productId => $productId);
        if(isset($genericProductId)){
            if(self::fetchField("#productId = {$productId} AND #genericProductId = {$genericProductId}")){
                $inArr[$genericProductId] = $genericProductId;
            }
        } else {
            if($genericProductId = self::fetchField("#productId = {$productId}", 'genericProductId')){
                $inArr[$genericProductId] = $genericProductId;
            }
        }
        
        // Всички артикули, които се влагат като търсения, или се влагат като неговия генеричен
        $query = self::getQuery();
        $query->EXT('state', 'cat_Products', 'externalName=state,externalKey=productId');
        $query->EXT('canConvert', 'cat_Products', 'externalName=canConvert,externalKey=productId');
        $query->where("#state = 'active' AND #canConvert = 'yes'");
        $query->in("genericProductId", $inArr);
        $query->show('productId,genericProductId');
        if (isset($ignoreRecId)) {
            $query->where("#id != {$ignoreRecId}");
        }
        
        while ($dRec = $query->fetch()) {
            if(!array_key_exists($dRec->productId, $res)){
                $res[$dRec->productId] = cat_Products::getTitleById($dRec->productId, false);
            }
            
            if(!array_key_exists($dRec->genericProductId, $res)){
                $res[$dRec->genericProductId] = cat_Products::getTitleById($dRec->genericProductId, false);
            }
        }
        
        return $res;
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
            // Намираме сумата която струва к-то от артикула в склада
            $maxTry = core_Packs::getConfigValue('cat', 'CAT_WAC_PRICE_PERIOD_LIMIT');
            $selfValue = acc_strategy_WAC::getAmount($quantity, $date, '61101', $item1, null, null, $maxTry);
            if ($selfValue) {
                $selfValue = round($selfValue, 4);
            }
        }
        
        return $selfValue;
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
        $cachePrice = static::$cache[current(preg_grep("|{$productId}|", array_keys(static::$cache)))];
        if ($cachePrice) {
            
            return $cachePrice;
        }
        
        // Ако артикула не е вложим, не търсим средна цена
        $isConvertable = cat_Products::fetchField($productId, 'canConvert');
        if ($isConvertable != 'yes') {
            
            return $avgPrice;
        }
        
        // Ако няма заместващи артикули, не търсим средна цена
        $equivalentProducts = static::getEquivalentProducts($productId);
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
            
            $pInfo = cat_Products::getProductInfo($productId);
            
            // Ако артикула е складируем взимаме среднопритеглената му цена от склада
            if (isset($pInfo->meta['canStore'])) {
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
        if($rec->_updateHorizons) {
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
}
    