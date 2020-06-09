<?php


/**
 * Клас 'cat_products_Packagings'
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class cat_products_Packagings extends core_Detail
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'label_SequenceIntf=cat_interface_PackLabelImpl, barcode_SearchIntf';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    public $title = 'Опаковки';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Опаковка';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'packagingId=Наименование, quantity=К-во, eanCode=EAN, netWeight=, tareWeight=, weight=Тегло, sizeWidth=, sizeHeight=, sizeDepth=, dimension=Габарити,user=Потребител';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_RowTools2, plg_SaveAndNew, plg_Created,plg_Modified';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,sales,purchase,packEdit';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canAdd = 'ceo,sales,purchase,packEdit';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canEdit = 'ceo,sales,purchase,packEdit';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canDelete = 'ceo,sales,purchase,packEdit';
    
    
    /**
     * Предлог в формата за добавяне/редактиране
     */
    public $formTitlePreposition = 'на';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'eanCode';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden, silent');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'tdClass=leftCol,caption=Опаковка,mandatory,smartCenter,removeAndRefreshForm=quantity|tareWeight|sizeWidth|sizeHeight|sizeDepth|templateId,silent');
        $this->FLD('quantity', 'double(Min=0,smartRound)', 'input,caption=Количество,mandatory,smartCenter');
        $this->FLD('isBase', 'enum(yes=Да,no=Не)', 'caption=Основна,mandatory,maxRadio=2');
        $this->FLD('eanCode', 'gs1_TypeEan(mvc=cat_products_Packagings,field=eanCode,stringIfEmpty)', 'caption=EAN');
        $this->FLD('templateId', 'key(mvc=cat_PackParams,select=title)', 'caption=Размери,silent,removeAndRefreshForm=tareWeight|sizeWidth|sizeHeight|sizeDepth,class=w50');
        $this->FLD('sizeWidth', 'cat_type_Size(min=0,unit=cm)', 'caption=Подробно->Дължина,autohide=any');
        $this->FLD('sizeHeight', 'cat_type_Size(min=0,unit=cm)', 'caption=Подробно->Широчина,autohide=any');
        $this->FLD('sizeDepth', 'cat_type_Size(min=0,unit=cm)', 'caption=Подробно->Височина,autohide=any');
        $this->FLD('tareWeight', 'cat_type_Weight(min=0)', 'caption=Подробно->Тара,autohide=any');
        
        $this->setDbUnique('productId,packagingId');
        $this->setDbIndex('eanCode');
        $this->setDbIndex('productId');
    }
    
    
    /**
     * Интервала на автоматичните баркодове
     *
     * @return array - начало и край на баркодовете
     */
    public static function getEanRange()
    {
        $begin = cat_Setup::get('PACKAGING_AUTO_BARCODE_BEGIN');
        $end = cat_Setup::get('PACKAGING_AUTO_BARCODE_END');
        if (empty($begin) || empty($end)) {
            
            return array();
        }
        
        return array('0' => $begin, '1' => $end);
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            
            $baseMeasureId = cat_Products::getProductInfo($rec->productId)->productRec->measureId;
            if ($baseMeasureId == $rec->packagingId) {
                if ($rec->quantity != 1) {
                    $form->setError('quantity', 'Количеството не може да е различно от единица за избраната мярка/опаковка');
                }
            }
            
            if ($rec->eanCode) {
                
                // Проверяваме дали има продукт с такъв код (като изключим текущия)
                $check = $mvc->Master->getByCode($rec->eanCode);
                if (($check && ($check->productId != $rec->productId)) ||
                    ($check && $check->packagingId && $check->productId == $rec->productId && $check->packagingId != $rec->packagingId)) {
                        $checkProductLink = cat_Products::getHyperlink($check->productId, true);
                        $form->setError('eanCode', 'Има вече артикул с такъв код|*: ' . $checkProductLink);
                }
            }
            
            // Ако за този продукт има друга основна опаковка, тя става не основна
            if ($rec->isBase == 'yes' && $packRec = static::fetch("#productId = {$rec->productId} AND #isBase = 'yes'")) {
                $packRec->isBase = 'no';
                static::save($packRec, 'isBase');
            }
            
            if (self::allowWeightQuantityCheck($rec->productId, $rec->packagingId, $rec->id)) {
                if ($error = self::checkWeightQuantity($rec->productId, $rec->packagingId, $rec->quantity)) {
                    $form->setError('quantity', $error);
                }
            }
            
            if (!$form->gotErrors() && cat_UoM::fetch($rec->packagingId)->type == 'packaging') {
                $warning = null;
                if (!deals_Helper::setWarning($baseMeasureId, $rec->quantity, $warning)) {
                    $form->setError('quantity', $warning);
                }
            }
        }
    }
    
    
    /**
     * Колко опаковки от същия вид има артикула
     *
     * @param int $productId
     * @param int $sysId
     *
     * @return float
     */
    public static function countSameTypePackagings($productId, $sysId)
    {
        $uoms = cat_UoM::getSameTypeMeasures(cat_UoM::fetchBySysId($sysId)->id);
        unset($uoms['']);
        
        $count = cat_products_Packagings::count("#productId = {$productId} AND #packagingId IN (" . implode(array_keys($uoms), ',') . ')');
        
        return $count;
    }
    
    
    /**
     * Трябва ли да се валидира количеството
     */
    private static function allowWeightQuantityCheck($productId, $packagingId, $id)
    {
        $measureId = cat_Products::fetchField($productId, 'measureId');
        if (cat_UoM::isWeightMeasure($measureId)) {
            
            return true;
        }
        
        $count = self::countSameTypePackagings($productId, 'kg');
        if ($count != 1) {
            
            return true;
        }
        if (empty($id) && $count == 1) {
            
            return true;
        }
        
        $weightGr = cat_Products::getParams($productId, 'weight');
        if (!empty($weightGr)) {
            
            return true;
        }
        $weightKg = cat_Products::getParams($productId, 'weightKg');
        if (!empty($weightKg)) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Проверява количеството на теглото, допустимо ли е
     *
     * @param int   $productId
     * @param int   $packagingId
     * @param float $quantity
     *
     * @return string|NULL
     */
    public static function checkWeightQuantity($productId, $packagingId, $quantity)
    {
        // Ако не е тегловна не се прави нищо
        if (!cat_UoM::isWeightMeasure($packagingId)) {
            
            return;
        }
        
        if ($kgWeight = cat_Products::convertToUom($productId, 'kg')) {
            $mWeight = cat_UoM::convertValue(1, $packagingId, 'kg');
            $diff = $mWeight / $quantity;
            if (round($diff, 4) != round($kgWeight, 4)) {
                
                return 'Има разминаване спрямо очакваната стойност';
            }
        }
    }
    
    
    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($requiredRoles == 'no_one') {
            
            return;
        }
        
        if ($action == 'add' && isset($rec->productId)) {
            if (!countR($mvc::getRemainingOptions($rec->productId))) {
                $requiredRoles = 'no_one';
            }
        }
        
        if (($action == 'add' || $action == 'delete' || $action == 'edit') && isset($rec->productId)) {
            $productRec = cat_Products::fetch($rec->productId, 'isPublic,state');
            if ($productRec->state != 'active' && $productRec->state != 'template') {
                $requiredRoles = 'no_one';
            } elseif ($productRec->isPublic == 'yes') {
                if (!haveRole('ceo,packEdit')) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        // Ако потрбителя няма достъп до сингъла на артикула, не може да модифицира опаковките
        if (($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec) && $requiredRoles != 'no_one') {
            $canStore = cat_Products::fetchField($rec->productId, 'canStore');
            if ($canStore != 'yes') {
                $requiredRoles = 'no_one';
            }
        }
        
        // Ако опаковката вече е използвана не може да се изтрива
        if ($action == 'delete' && isset($rec)) {
            if (self::isUsed($rec->productId, $rec->packagingId, strtolower(Request::get('Act')) == 'list')) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass     $res
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        if (!(countR($mvc::getRemainingOptions($data->form->rec->productId)) - 1)) {
            $data->form->toolbar->removeBtn('saveAndNew');
        }
    }
    
    
    /**
     * Връща не-използваните параметри за конкретния продукт, като опции
     *
     * @param $productId int ид на продукта
     * @param $id int ид от текущия модел, което не трябва да бъде изключено
     *
     * @return array $options - опциите
     */
    public static function getRemainingOptions($productId, $id = null)
    {
        // Извличаме мерките и опаковките
        $uomArr = cat_UoM::getUomOptions();
        $packArr = cat_UoM::getPackagingOptions();
        
        // Отсяваме тези, които вече са избрани за артикула
        $query = self::getQuery();
        if ($id) {
            $query->where("#id != {$id}");
        }
        
        while ($rec = $query->fetch("#productId = ${productId}")) {
            unset($uomArr[$rec->packagingId]);
            unset($packArr[$rec->packagingId]);
        }
        
        // Групираме опциите, ако има такива
        $options = array();
        if (countR($packArr)) {
            $options = array('p' => (object) array('group' => true, 'title' => tr('Опаковки'))) + $packArr;
        }
        
        if (countR($uomArr)) {
            $options += array('u' => (object) array('group' => true, 'title' => tr('Мерки'))) + $uomArr;
        }
        
        // Връщане на намерените опции
        return $options;
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $options = self::getRemainingOptions($rec->productId, $rec->id);
        $form->setOptions('packagingId', array('' => '') + $options);
        
        // Ако има дефолтни опаковки от драйвера
        if ($Driver = cat_Products::getDriver($rec->productId)) {
            $defaults = $Driver->getDefaultPackagings($rec);
            
            if (countR($defaults)) {
                foreach ($defaults as $def) {
                    if (isset($options[$def->packagingId])) {
                        $form->setDefault('packagingId', $def->packagingId);
                    }
                }
            }
        }
        
        $form->setField('templateId', 'input=none');
        if (isset($rec->packagingId)) {
            $uomType = cat_UoM::fetchField($rec->packagingId, 'type');
            if ($uomType != 'uom') {
                $form->setField('templateId', 'input');
                
                // Намиране на наличните шаблони
                $packTemplateOptions = cat_PackParams::getTemplates($rec->packagingId);
                $form->setOptions('templateId', array('' => '') + $packTemplateOptions);
                
                if (countR($packTemplateOptions)) {
                    // Зареждане на дефолтите от шаблоните
                    if (isset($rec->templateId)) {
                        $pRec = cat_PackParams::fetch($rec->templateId);
                        $form->setDefault('sizeWidth', $pRec->sizeWidth);
                        $form->setDefault('sizeHeight', $pRec->sizeHeight);
                        $form->setDefault('sizeDepth', $pRec->sizeDepth);
                        $form->setDefault('tareWeight', $pRec->tareWeight);
                    }
                }
            }
        }
        
        $form->setDefault('isBase', 'no');
        $unit = cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId'));
        $form->setField('quantity', "unit={$unit}");
        
        // Ако редактираме, но опаковката е използвана не може да се променя
        if (!haveRole('no_one')) {
            if (isset($rec->id)) {
                if (self::isUsed($rec->productId, $rec->packagingId, true)) {
                    $form->setReadOnly('packagingId');
                    $form->setReadOnly('quantity');
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields)
    {
        foreach (array('sizeWidth', 'sizeHeight', 'sizeDepth') as $sizeFld) {
            if ($rec->{$sizeFld} == 0) {
                $row->{$sizeFld} = '-';
            }
        }
        
        if ($rec->sizeWidth || $rec->sizeHeight || $rec->sizeDepth) {
            $row->dimension = "{$row->sizeWidth} <span class='quiet'>x</span> {$row->sizeHeight} <span class='quiet'>x</span> {$row->sizeDepth}";
        }
        
        if (!empty($rec->eanCode)) {
            if (barcode_Search::haveRightFor('list') && !Mode::isReadOnly()) {
                $row->eanCode = ht::createLink($row->eanCode, array('barcode_Search', 'search' => $rec->eanCode));
            }
        }
        
        try {
            if ($netWeight = cat_Products::convertToUom($rec->productId, 'kg')) {
                $netWeight = core_Type::getByName('cat_type_Weight')->toVerbal($netWeight * $rec->quantity);
                $row->weight = "<span class='quiet'>" . tr('Нето') . ': </span>' . $netWeight . '<br>';
            }
        } catch (ErrorException $e) {}
        
        if (!empty($rec->tareWeight)) {
            $row->weight .= "<span class='quiet'>" . tr('Тара') . ': </span>' .  $row->tareWeight;
        }
        
        if ($rec->isBase == 'yes') {
            $row->packagingId = '<b>' . $row->packagingId . '</b>';
        }
        
        if($fields['-list']) {
            if($rec->modifiedOn) {
                $row->user = crm_Profiles::createLink($rec->modifiedBy) . ', ' . $mvc->getVerbal($rec, 'modifiedOn');
            } else {
                $row->user = crm_Profiles::createLink($rec->createdBy) . ', ' . $mvc->getVerbal($rec, 'createdOn');
            }
        }
    }
    
    
    /**
     * Подготвя опаковките на артикула
     *
     * @param stdClass $data
     */
    public function preparePackagings($data)
    {
        $masterRec = is_object($data->masterData->rec) ? $data->masterData->rec : $data->masterMvc->fetch($data->masterId);
        if ($masterRec->canStore == 'no') {
            $data->notStorable = true;
        }
        
        $data->recs = $data->rows = array();
        $fields = $this->selectFields();
        $fields['-list'] = true;
        
        $query = self::getQuery();
        $query->where("#productId = {$data->masterId}");
        $query->orderBy('quantity', 'ASC');
        $query->orderBy('packagingId', 'ASC');
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = self::recToVerbal($rec, $fields);
        }
        
        $data->retUrl = (isset($data->retUrl)) ? $data->retUrl : cat_Products::getSingleUrlArray($data->masterId);
        if ($data->rejected !== true && $this->haveRightFor('add', (object) array('productId' => $data->masterId))) {
            $data->addUrl = array($this, 'add', 'productId' => $data->masterId, 'ret_url' => $data->retUrl);
        }
        
        $data->listFields = arr::make($this->listFields, true);
        $shortMeasure = cat_UoM::getShortName($masterRec->measureId);
        $data->listFields['quantity'] .= "|* <span class='small'>( |{$shortMeasure}|* )</span>";
    }
    
    
    /**
     * Подготвя опаковките на артикула
     *
     * @param stdClass $data
     */
    public function renderPackagings($data)
    {
        if ($data->notStorable === true && !countR($data->recs)) {
            
            return;
        }
        $tpl = (isset($data->tpl)) ? $data->tpl : getTplFromFile('cat/tpl/PackigingDetail.shtml');
        
        if ($data->addUrl && !Mode::isReadOnly()) {
            $addBtn = ht::createLink('<img src=' . sbf('img/16/add.png') . " style='vertical-align: middle; margin-left:5px;'>", $data->addUrl, false, 'title=Добавяне на нова опаковка/мярка');
            $tpl->append($addBtn, 'TITLE');
        }
        
        // Ако артикула не е производим, показваме в детайла
        if ($data->notStorable === true) {
            $tpl->append(" <small style='color:red'>(" . tr('Артикулът не е складируем') . ')</small>', 'TITLE');
            $tpl->append('state-rejected', 'TAB_STATE');
            unset($data->listFields['tools']);
        }
        
        $table = cls::get('core_TableView', array('mvc' => $this));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        
        if ($data->rejected === true) {
            unset($data->listFields['_rowTools']);
        }
        
        $tpl->append($table->get($data->rows, $data->listFields), 'CONTENT');
        
        return $tpl;
    }
    
    
    /**
     * Връща опаковката ако има такава
     *
     * @param int $productId     - ид на продукта
     * @param int $packagingId   - ид на опаковката
     * @param string|null $field - ид на опаковката
     *
     * @return stdClass
     */
    public static function getPack($productId, $packagingId, $field = null)
    {
        if(isset($field)){
            
            return self::fetchField("#productId = {$productId} AND #packagingId = '{$packagingId}'", $field);
        }
        
        return self::fetch("#productId = {$productId} AND #packagingId = '{$packagingId}'");
    }
    
    
    /**
     * Връща количеството на дадения продукт в посочената опаковка
     */
    public static function getQuantityInPack($productId, $pack = 'pallet')
    {
        $uomRec = cat_UoM::fetchBySinonim(mb_strtolower($pack));
        if ($uomRec) {
            $packRec = self::getPack($productId, $uomRec->id);
            if ($packRec) {
                
                return $packRec->quantity;
            }
        }
    }
    
    
    /**
     * Връща най-голямата опаковка, която има по-малко бройки в себе си, от посоченото
     */
    public static function getLowerPack($productId, $quantity)
    {
        $bestRec = null;
        
        $query = self::getQuery();
        while ($rec = $query->fetch("#productId = {$productId}")) {
            if ($rec->quantity < $quantity) {
                if (!$bestRec || $bestRec->quantity < $rec->quantity) {
                    $bestRec = $rec;
                }
            }
        }
        
        return $bestRec;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        cat_PackParams::sync($rec->packagingId, $rec->sizeWidth, $rec->sizeHeight, $rec->sizeDepth, $rec->tareWeight);
    }
    
    
    /**
     * Дали в бизнес документите е използван артикула с посочената опаковка
     *
     * @param int  $productId - ид на артикул
     * @param int  $uomId     - мярка на артикула
     * @param bool $cache     - дали искаме данните да се кешират при използване или не
     *
     * @return bool $isUsed -използван или не
     */
    public static function isUsed($productId, $uomId, $cache = false)
    {
        $cacheKey = "{$productId}|{$uomId}";
        
        // Ако искаме кеширани данни
        if ($cache === true) {
            $isUsed = false;
            
            // Проверяваме имали кеш
            $hasCache = core_Cache::get('cat_Products', $cacheKey);
            
            // Ако артикула е използван в тази си опаковка, кешираме че е използван
            if ($hasCache !== 'y' && $hasCache !== 'n') {
                
                // Ако няма проверяваме дали е използван с тази опаковка (без кеш)
                if (self::isUsed($productId, $uomId)) {
                    core_Cache::set('cat_Products', $cacheKey, 'y', 10080);
                    $isUsed = true;
                } else {
                    core_Cache::set('cat_Products', $cacheKey, 'n', 10080);
                    $isUsed = false;
                }
            } else {
                $isUsed = ($hasCache == 'y');
            }
            
            // Връщаме намерения резултат
            return $isUsed;
        }
        
        // Детайли в които ще проверяваме
        $details = array('sales_SalesDetails',
            'purchase_PurchasesDetails',
            'store_ShipmentOrderDetails',
            'store_ReceiptDetails',
            'sales_QuotationsDetails',
            'sales_InvoiceDetails',
            'purchase_InvoiceDetails',
            'planning_DirectProductNoteDetails',
            'planning_ConsumptionNoteDetails',
            'cat_BomDetails',
            'sales_ProformaDetails',
            'sales_ServicesDetails',
            'purchase_ServicesDetails',
            'store_ConsignmentProtocolDetailsReceived',
            'store_ConsignmentProtocolDetailsSend');
        
        // За всеки от изброените документи проверяваме дали е избран артикула с мярката
        $isUsed = false;
        foreach ($details as $Detail) {
            if ($Detail == 'cat_BomDetails') {
                if ($Detail::fetch("#resourceId = {$productId} AND #packagingId = '{$uomId}'", 'id')) {
                    $isUsed = true;
                    break;
                }
            } else {
                if ($Detail::fetch("#productId = {$productId} AND #packagingId = '{$uomId}'", 'id')) {
                    $isUsed = true;
                    break;
                }
            }
        }
        
        // Ако няма проверяваме дали е използван с тази опаковка (без кеш)
        if ($isUsed) {
            core_Cache::set('cat_Products', $cacheKey, 'y', 10080);
        } else {
            core_Cache::set('cat_Products', $cacheKey, 'n', 10080);
        }
        
        // Връщаме резултат
        return $isUsed;
    }
    
    
    /**
     * Търси по подадения баркод
     *
     * @param string $str
     *
     * @return array
     *               ->title - заглавие на резултата
     *               ->url - линк за хипервръзка
     *               ->comment - html допълнителна информация
     *               ->priority - приоритет
     */
    public function searchByCode($str)
    {
        $resArr = array();
        
        // Има ли артикул с такъв код?
        $productData = cat_Products::getByCode($str);
        if (!is_object($productData)) {
            
            return $resArr;
        }
        
        $artStr = tr('Артикул');
        
        $obj = (object) array('title' => $artStr . ': ' . cat_Products::getHyperlink($productData->productId, true), 'url' => array(), 'priority' => 0, 'comment' => '');
        
        // Извличане на най-важната информация за артикула
        $productRec = cat_Products::fetch($productData->productId, 'canSell,canBuy,canStore,canConvert,nameEn,isPublic,folderId,state,measureId');
        setIfNot($productData->packagingId, $productRec->measureId);
        
        $packagingName = $packagingNameShort = tr(cat_UoM::getTitleById($productData->packagingId));
        $packRec = (cat_products_Packagings::getPack($productData->productId, $productData->packagingId));
        $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
        deals_helper::getPackInfo($packagingName, $productData->productId, $productData->packagingId, $quantityInPack);
        
        $obj->comment .= $packagingName;
        if ($preview = cat_Products::getPreview($productData->productId, array(200, 200))) {
            if (Mode::is('screenMode', 'wide')) {
                $obj->comment .= "<span class='imgPreview'>" . $preview . '</span>';
            } else {
                $obj->comment .= "</td><tr><td colspan='2' align = 'left'><span class='imgPreview'>" . $preview . '</span>';
            }
        }
        
        $obj->comment .= "</td><tr><td colspan='2' class='noPadding'><div class='scrolling-holder'>";
        
        $resArr[] = $obj;
        
        // Само за активните артикули ще се връщат резултати
        if ($productRec->state != 'active') {
            
            return $resArr;
        }
        
        // Има ли последно посещавани нишки от текущия потребител?
        $threadIds = bgerp_Recently::getLastThreadsId(null, null, 3600);
        if (!countR($threadIds)) {
            
            return $resArr;
        }
        
        // Кои документи, ще се разглеждат
        $DocumentIds = array();
        $Documents = array('sales_Sales', 'sales_Invoices', 'sales_Services', 'purchase_Purchases', 'purchase_Services', 'purchase_Invoices', 'store_Receipts', 'store_ShipmentOrders', 'store_Transfers', 'planning_ReturnNotes', 'planning_ConsumptionNotes');
        foreach ($Documents as $docName) {
            $DocumentIds[$docName] = $docName::getClassId();
        }
        
        // Има ли чернови документи в посочение нишки?
        $cQuery = doc_Containers::getQuery();
        $cQuery->where("#state = 'draft'");
        $cQuery->in('threadId', $threadIds);
        $cQuery->in('docClass', $DocumentIds);
        $cQuery->show('id,folderId');
        $containers = $cQuery->fetchAll();
        if (!countR($containers)) {
            
            return $resArr;
        }
        
        $onlyInFolders = cat_products_SharedInFolders::getSharedFolders($productRec);
        $documentRows = array();
        
        // За всеки намерен документ
        foreach ($containers as $containerRec) {
            $isReverse = 'no';
            try {
                // Извличане на документа и проверка може ли артикула да се добави към него
                $Doc = doc_Containers::getDocument($containerRec->id);
                if ($Doc->isInstanceOf('sales_Sales') || $Doc->isInstanceOf('sales_Invoices')) {
                    if ($productRec->canSell != 'yes') {
                        continue;
                    }
                } elseif ($Doc->isInstanceOf('purchase_Purchases') || $Doc->isInstanceOf('purchase_Invoices')) {
                    if ($productRec->canBuy != 'yes') {
                        continue;
                    }
                }
                
                if ($Doc->isInstanceOf('store_ShipmentOrders') || $Doc->isInstanceOf('store_Receipts') || $Doc->isInstanceOf('store_Transfers') || $Doc->isInstanceOf('planning_ReturnNotes') || $Doc->isInstanceOf('planning_ConsumptionNotes')) {
                    if ($productRec->canStore != 'yes') {
                        continue;
                    }
                }
                
                if ($Doc->isInstanceOf('sales_Services') || $Doc->isInstanceOf('purchase_Services')) {
                    if ($productRec->canStore != 'no') {
                        continue;
                    }
                }
                
                if ($Doc->isInstanceOf('store_ShipmentOrders') || $Doc->isInstanceOf('store_Receipts') || $Doc->isInstanceOf('sales_Services') || $Doc->isInstanceOf('purchase_Services')) {
                    $isReverse = $Doc->fetchField('isReverse');
                    $meta = ($Doc->isInstanceOf('store_ShipmentOrders') || $Doc->isInstanceOf('sales_Services')) ? (($isReverse == 'no') ? 'canSell' : 'canBuy') : (($isReverse == 'no') ? 'canBuy' : 'canSell');
                    if ($productRec->{$meta} != 'yes') {
                        continue;
                    }
                }
                
                if ($Doc->isInstanceOf('planning_ReturnNotes') || $Doc->isInstanceOf('planning_ConsumptionNotes')) {
                    if ($productRec->canConvert != 'yes') {
                        continue;
                    }
                }
                
                // Ако артикула е достъпен само към избрани папки, документа трябва да е в тях
                if (countR($onlyInFolders) && !($Doc->isInstanceOf('planning_ReturnNotes') || $Doc->isInstanceOf('planning_ConsumptionNotes') || $Doc->isInstanceOf('store_Transfers'))) {
                    $folderId = $Doc->fetchField('folderId');
                    if (!array_key_exists($folderId, $onlyInFolders)) {
                        continue;
                    }
                }
                
                if (isset($Doc->mainDetail)) {
                    $Detail = cls::get($Doc->mainDetail);
                    
                    // Ако може да се добавя артикула към детайла на документа
                    if (!$Detail->haveRightFor('add', (object) array($Detail->masterKey => $Doc->that))) {
                        continue;
                    }
                    
                    $addUrl = array($Detail, 'add', "{$Detail->masterKey}" => $Doc->that, "{$Detail->productFld}" => $productData->productId, 'packagingId' => $productData->packagingId);
                    $addLink = ht::createBtn('#' . $Doc->getHandle(), $addUrl, false, false, 'ef_icon=img/16/shopping.png');
                    
                    $documentRow = (object) array('addLink' => $addLink);
                    
                    // Ако ще може да му се показва продажната цена
                    if (!($Doc->isInstanceOf('planning_ReturnNotes') || $Doc->isInstanceOf('planning_ConsumptionNotes') || $Doc->isInstanceOf('store_Transfers'))) {
                        $Policy = ($isReverse == 'yes') ? (($Detail->ReversePolicy) ? $Detail->ReversePolicy : cls::get('price_ListToCustomers')) : (($Detail->Policy) ? $Detail->Policy : cls::get('price_ListToCustomers'));
                        $docRec = $Doc->fetch('contragentClassId, contragentId, chargeVat, valior, currencyRate,currencyId');
                        
                        $policyInfo = $Policy->getPriceInfo($docRec->contragentClassId, $docRec->contragentId, $productData->productId, $productData->packagingId, $quantityInPack, $docRec->valior, $docRec->currencyRate, $docRec->chargeVat);
                        if (!isset($policyInfo->price)) {
                            $price = 'N/A';
                        } else {
                            $price = core_Type::getByName('double(smartRound,minDecimals=2)')->toVerbal($policyInfo->price * $quantityInPack);
                            $price .= " <span class='cCode'>{$docRec->currencyId}</span>";
                        }
                        
                        $documentRow->price = $price;
                    }
                    
                    if ($productRec->canStore == 'yes') {
                        if ($storeId = $Doc->fetchField($Doc->storeFieldName)) {
                            $quantity = store_Products::getQuantity($productRec->id, $storeId, true);
                            $packQuantity = $quantity / $quantityInPack;
                            $packQuantityVerbal = (empty($packQuantity)) ? tr('няма наличност') : core_Type::getByName('double(smartRound)')->toVerbal($packQuantity);
                            
                            $documentRow->free = $packQuantityVerbal;
                            $documentRow->storeId = store_Stores::getHyperlink($storeId, true);
                        }
                    }
                    
                    $documentRows[] = $documentRow;
                }
            } catch (core_exception_Expect $e) {
                continue;
            }
        }
        
        if (countR($documentRows)) {
            $fieldset = new core_FieldSet();
            $fieldset->FLD('addLink', 'varchar', 'tdClass=centered');
            $fieldset->FLD('free', 'varchar', 'smartCenter');
            $fieldset->FLD('price', 'double', 'smartCenter');
            $fieldset->FLD('storeId', 'varchar');
            $table = cls::get('core_TableView', array('mvc' => $fieldset));
            
            $fields = arr::make("addLink=Документи,price=Ед. цена,free=Разполагаемо|* ({$packagingNameShort}),storeId=Склад", true);
            $fields = core_TableView::filterEmptyColumns($documentRows, $fields, 'free,price,storeId');
            $docTableTpl = $table->get($documentRows, $fields);
            
            $resArr[0]->comment .= $docTableTpl .  '</div>';
            $resArr[0]->comment = new ET($resArr[0]->comment);
            if (Mode::is('screenMode', 'narrow')) {
                jquery_Jquery::run($resArr[0]->comment, 'setBarcodeHolderWidth()');
            }
        }
        
        return $resArr;
    }
}
