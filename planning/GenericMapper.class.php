<?php


/**
 * Съответствие с генерични артикули
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
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
     * Кой има право да чете?
     */
    public $canRead = 'ceo,planning';
    
    
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
    public $listFields = 'productId,genericProductId=Генеричен артикул,createdOn,createdBy'; 
    
    
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
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=canConvert,hasnotProperties=generic,maxSuggestions=100,forceAjax,titleFld=name)', 'caption=Замества,mandatory,silent,class=w50,smartCenter');
        $this->FLD('genericProductId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=generic,maxSuggestions=100,forceAjax,titleFld=name)', 'caption=Генеричен артикул,mandatory,silent,class=w50');
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
            
            $measureProductId = cat_Products::fetchField($rec->productId, 'measureId');
            $measureGenericId = cat_Products::fetchField($rec->genericProductId, 'measureId');
            $similarMeasures = cat_UoM::getSameTypeMeasures($measureGenericId);
            if(!array_key_exists($measureProductId, $similarMeasures)){
                $genericMeasureName = cat_UoM::getVerbal($measureGenericId, 'name');
                
                $form->setError('productId', "Заместващият артикул, трябва да е в мярка производна на|*: <b>{$genericMeasureName}</b>");
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
        
        $canConvert = cat_Products::fetchField($rec->productId, 'canConvert');
        if($canConvert != 'yes'){
            $row->ROW_ATTR['class'] = 'state-closed';
            $row->productId = ht::createHint($row->productId, "Артикулът вече не е вложим", 'warning', false);
        } else {
            $row->ROW_ATTR['class'] = 'state-active';
        }
    }
    
    
    /**
     * Подготвя показването на информацията за влагане
     */
    public function prepareResources(&$data)
    {
        if (!haveRole('ceo,planning')) {
            $data->notConvertableAnymore = true;
            
            return;
        }
        
        $data->isGeneric = $data->masterData->rec->generic;
        $data->rows = array();
        $query = $this->getQuery();
        
        if($data->isGeneric == 'yes'){
            $listFields = "productId=Заместващ артикул,created=Създаване";
            $query->where("#genericProductId = {$data->masterId}");
        } else {
            $listFields = "genericProductId=Генеричен артикул,created=Създаване";
            $query->where("#productId = {$data->masterId}");
        }
        
        while ($rec = $query->fetch()) {
            $data->rows[$rec->id] = $this->recToVerbal($rec);
        }
       
        $pInfo = $data->masterMvc->getProductInfo($data->masterId);
        if (!isset($pInfo->meta['canConvert'])) {
            $data->notConvertableAnymore = true;
        }
        
        if (!(countR($data->rows) || isset($pInfo->meta['canConvert']))) {
            
            return;
        }
        
        $data->TabCaption = 'Влагане';
        $data->Tab = 'top';
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
     * Рендира показването на ресурси
     */
    public function renderResources(&$data)
    {
        // Ако няма записи и вече не е вложим да не се показва
        if (!countR($data->rows) && $data->notConvertableAnymore) {
            
            return;
        }
        
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
        
        // За да се добави ресурс към обект, трябва самия обект да може да има ресурси
        if ($action == 'add' && isset($rec)) {
            if(isset($rec->productId)){
                if ($mvc->fetch("#productId = {$rec->productId}")) {
                    $res = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Намира еквивалентите за влагане артикули на даден артикул
     *
     * @param int $productId         - на кой артикул му търсим еквивалентните
     * @param int|null $ignoreRecId  - ид на ред, който да се игнорира
     *
     * @return array  $res           - масив за избор с еквивалентни артикули
     */
    public static function getEquivalentProducts($productId, $ignoreRecId = null)
    {
        $res = array();
        
        $inArr = array($productId => $productId);
        if($genericProductId = self::fetchField("#productId = {$productId}", 'genericProductId')){
            $inArr[$genericProductId] = $genericProductId;
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
}
    