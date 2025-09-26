<?php


/**
 * Клас 'cat_products_Packagings'
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
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
    public $listFields = 'productId=Артикул,packagingId=Наименование, quantity=К-во, eanCode=EAN, netWeight=, tareWeight=, weight=Тегло, sizeWidth=, sizeHeight=, sizeDepth=, dimension=Габарити,user=Създаване';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_RowTools2, plg_SaveAndNew, plg_Created,plg_Modified,plg_State2';


    /**
     * Кой може да променя състоянието
     */
    public $canChangestate = 'packEdit,ceo';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,sales,purchase,packEdit';

    /**
     * Кой има право да променя системните данни?
     */
    public $canDeletesysdata = 'ceo,sales,purchase,packEdit';


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
    public $fieldsNotToClone = 'eanCode,firstClassId,firstDocId';


    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;


    /**
     * Помощен масив за всички детайли и класове, в които може да се съдържа
     */
    protected static $detailsArr = array(
        'sales_SalesDetails',
        'purchase_PurchasesDetails',
        'store_ShipmentOrderDetails',
        'store_ReceiptDetails',
        'sales_QuotationsDetails',
        'purchase_QuotationDetails',
        'sales_InvoiceDetails',
        'purchase_InvoiceDetails',
        'planning_DirectProductNoteDetails',
        'planning_ConsumptionNoteDetails',
        'planning_ReturnNoteDetails',
        'cat_BomDetails',
        'planning_DirectProductionNote',
        'sales_ProformaDetails',
        'sales_ServicesDetails',
        'purchase_ServicesDetails',
        'cat_ListingDetails',
        'pos_ReceiptDetails',
        'planning_Tasks',
        'planning_ProductionTaskProducts',
        'store_ConsignmentProtocolDetailsReceived',
        'store_TransfersDetails',
        'store_InventoryNoteDetails',
        'store_ConsignmentProtocolDetailsSend');


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,maxSuggestions=100,allowEmpty)', 'input=hidden, silent');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'tdClass=leftCol,caption=Опаковка/Мярка,mandatory,smartCenter,removeAndRefreshForm=quantity|tareWeight|sizeWidth|sizeHeight|sizeDepth|templateId|isSecondMeasure,silent');
        $this->FLD('quantity', 'double(Min=0,smartRound)', 'input,caption=Количество,mandatory,smartCenter');
        $this->FLD('isBase', 'enum(yes=Да,no=Не)', 'caption=Основна,mandatory,maxRadio=2');
        $this->FLD('isSecondMeasure', 'enum(yes=Да,no=Не)', 'caption=Втора мярка,mandatory,maxRadio=2,input=none');
        $this->FLD('eanCode', 'gs1_TypeEan(mvc=cat_products_Packagings,field=eanCode,stringIfEmpty)', 'caption=EAN');
        $this->FLD('templateId', 'key(mvc=cat_PackParams,select=title)', 'caption=Размери,silent,removeAndRefreshForm=tareWeight|sizeWidth|sizeHeight|sizeDepth,class=w50');
        $this->FLD('sizeWidth', 'cat_type_Size(min=0,unit=cm)', 'caption=Подробно->Дължина,autohide=any');
        $this->FLD('sizeHeight', 'cat_type_Size(min=0,unit=cm)', 'caption=Подробно->Широчина,autohide=any');
        $this->FLD('sizeDepth', 'cat_type_Size(min=0,unit=cm)', 'caption=Подробно->Височина,autohide=any');
        $this->FLD('tareWeight', 'cat_type_Weight(min=0)', 'caption=Подробно->Тара,autohide=any');
        $this->FLD('usages', 'int', 'input=none,caption=Използвания');

        $this->FLD('firstClassId', 'class', 'input=none');
        $this->FLD('firstDocId', 'int', 'input=none');

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

            $baseMeasureId = cat_Products::fetchField($rec->productId, 'measureId');
            if ($baseMeasureId == $rec->packagingId) {
                if ($rec->quantity != 1) {
                    $form->setError('quantity', 'Количеството не може да е различно от единица за избраната мярка/опаковка');
                }
            }

            if (isset($rec->id)) {
                $packType = cat_UoM::fetchField($rec->packagingId, 'type');
                if ($packType == 'uom') {
                    $oldQuantity = $mvc->fetchField($rec->id, 'quantity');
                    if (round($oldQuantity, 5) != round($rec->quantity, 5)) {
                        $tQuery = planning_Tasks::getQuery();
                        $tQuery->EXT('jobProductId', 'planning_Jobs', 'externalName=productId,remoteKey=containerId,externalFieldName=originId');
                        $tQuery->where("#jobProductId = {$rec->productId} AND #state NOT IN ('closed', 'rejected') AND #measureId = {$rec->packagingId} AND #isFinal = 'yes'");
                        $notClosedFound = $tQuery->count();
                        if ($notClosedFound) {
                            $notClosedFoundVerbal = core_Type::getByName('int')->toVerbal($notClosedFound);
                            $errorMsgPart = ($notClosedFound == 1) ? 'неприключена финална операция! За да редактирате количеството операцита трябва да бъде приключена и след промяната на количеството в мярката - създадена (без клониране) отново' : 'неприключени финални операции! За да редактирате количеството операциите трябва да бъдат приключени и след промяната на количеството в мярката - създадени (без клониране) отново';
                            $form->setError('quantity', "За артикула има |{$notClosedFoundVerbal}|* {$errorMsgPart}!");
                        }
                    }
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

            if (self::allowWeightQuantityCheck($rec->productId, $rec->packagingId, $rec->id)) {
                if ($error = self::checkWeightQuantity($rec->productId, $rec->packagingId, $rec->quantity)) {
                    $form->setError('quantity', $error);
                }
            }

            if (!$form->gotErrors() && cat_UoM::fetch($rec->packagingId)->type == 'packaging') {
                $warning = null;
                if (!deals_Helper::checkQuantity($baseMeasureId, $rec->quantity, $warning)) {
                    $form->setError('quantity', $warning);
                }
            }

            if (!$form->gotErrors()) {

                // Ако за този продукт има друга основна опаковка, тя става не основна
                if ($rec->isBase == 'yes') {
                    if ($packRec = static::fetch("#productId = {$rec->productId} AND #isBase = 'yes' AND #id != '{$rec->id}'")) {
                        $packRec->isBase = 'no';
                        static::save($packRec, 'isBase');
                    }
                }
            }
        }
    }


    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    protected static function on_BeforeSave($mvc, $id, $rec)
    {
        // Ако за този продукт има друга втора мярка, тя става не основна
        if ($rec->isSecondMeasure == 'yes') {
            if ($packRec = static::fetch("#productId = {$rec->productId} AND #isSecondMeasure = 'yes' AND #id != '{$rec->id}'")) {
                $packRec->isSecondMeasure = 'no';
                $mvc->save_($packRec, 'isSecondMeasure');
            }
        }
    }


    /**
     * Намира първата срещната втора мярка
     *
     * @param int|null $productId
     * @return null
     */
    private static function getFirstSecondMeasureId($productId)
    {
        // Ако артикула има вече избрана друга различна мярка
        $productMeasureId = cat_Products::fetchField($productId, 'measureId');
        $productMeasures = cat_UoM::getSameTypeMeasures($productMeasureId);

        $query = static::getQuery();
        $query->where("#productId = {$productId}");
        $query->EXT('type', 'cat_UoM', 'externalName=type,externalKey=packagingId');
        $query->EXT('baseUnitRatio', 'cat_UoM', 'externalName=baseUnitRatio,externalKey=packagingId');
        $query->notIn('packagingId', array_keys($productMeasures));
        $query->where("#type = 'uom' AND #isSecondMeasure != 'no'");
        $query->orderBy('baseUnitRatio=asc');
        $query->show('packagingId');

        $rec = $query->fetch();

        return is_object($rec) ? $rec->packagingId : null;
    }


    /**
     * Коя е втората основна мярка на артикула
     *
     * @param $productId
     * @return null
     */
    public static function getSecondMeasureId($productId)
    {
        // Ако има конкретно избрана е тя, ако няма е първата
        $secondMeasureId = static::fetchField("#productId = {$productId} AND #isSecondMeasure = 'yes'", 'packagingId');
        if (empty($secondMeasureId)) {
            $secondMeasureId = static::getFirstSecondMeasureId($productId);
        }

        return $secondMeasureId;
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

        $count = cat_products_Packagings::count("#productId = {$productId} AND #state != 'closed' AND #packagingId IN (" . implode(',', array_keys($uoms)) . ')');

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
     * @param int $productId
     * @param int $packagingId
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
        if ($requiredRoles == 'no_one') return;

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

        // Ако опаковката вече е използвана не може да се изтрива
        if ($action == 'delete' && isset($rec)) {
            if (!self::canEditOrDeletePack($rec->productId, $rec->packagingId)) {
                $requiredRoles = 'no_one';
            }
        }

        if ($action == 'edit' && isset($rec)) {
            if ($rec->state == 'closed') {
                $requiredRoles = 'no_one';
            }
        }

        if ($action == 'changestate' && isset($rec)) {
            if ($rec->state == 'closed') {
                $canStore = cat_Products::fetchField($rec->productId, 'canStore');
                if ($canStore != 'yes') {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }


    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass $res
     * @param stdClass $data
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
        $pRec = cat_Products::fetch($productId, 'canStore,groups');

        $consumableGroupId = cat_Groups::fetchField("#sysId = 'consumables'");
        $packArr = ($pRec->canStore == 'yes' || keylist::isIn($consumableGroupId, $pRec->groups)) ? cat_UoM::getPackagingOptions() : array();
        if (isset($id)) {
            $pId = static::fetchField($id, 'packagingId');
            if (!array_key_exists($pId, $packArr)) {
                $packArr[$pId] = cat_UoM::getTitleById($pId);
            }
        }
        // Отсяваме тези, които вече са избрани за артикула
        $query = self::getQuery();
        $query->where("#productId = {$productId}");
        $query->EXT('type', 'cat_UoM', 'externalName=type,externalKey=packagingId');
        if (isset($id)) {
            $query->where("#id != {$id}");
        }

        while ($rec = $query->fetch()) {
            unset($uomArr[$rec->packagingId]);
            unset($packArr[$rec->packagingId]);
        }

        // Групираме опциите, ако има такива
        $options = array();
        if (countR($packArr)) {
            $options = array('p' => (object)array('group' => true, 'title' => tr('Опаковки'))) + $packArr;
        }

        if (countR($uomArr)) {
            $options += array('u' => (object)array('group' => true, 'title' => tr('Мерки'))) + $uomArr;
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

        // Има ли твърдо забита втора мярка
        $defaultSecondMeasureId = null;
        if ($Driver = cat_Products::getDriver($rec->productId)) {
            $defaultSecondMeasureId = $Driver->getSecondMeasureId($rec->productId);
        }

        $productRec = cat_Products::fetch($rec->productId, 'measureId,isPublic');
        if (isset($rec->packagingId)) {
            $derivitiveMeasures = cat_UoM::getSameTypeMeasures($productRec->measureId);
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
            } elseif (empty($defaultSecondMeasureId)) {

                // Ако драйвера няма втора мярка, и това ще е първата различна мярка, тя по-дефолт ще е втора
                if (!array_key_exists($rec->packagingId, $derivitiveMeasures)) {
                    $form->setField('isSecondMeasure', 'input');
                    $alreadyHaveSecondMeasure = cat_products_Packagings::fetchField("#productId = {$rec->productId} AND #isSecondMeasure = 'yes' AND #id != '{$rec->id}'");
                    if ($alreadyHaveSecondMeasure) {
                        $form->setDefault('isSecondMeasure', 'no');
                    } else {
                        $form->setDefault('isSecondMeasure', 'yes');
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

                $checkIfPackIsUsed = true;
                if ($productRec->isPublic == 'no') {
                    $sRec = store_Products::getQuantities($rec->productId);
                    if (empty($sRec->quantity)) {
                        $checkIfPackIsUsed = false;
                    }
                }

                if ($checkIfPackIsUsed) {
                    if (!self::canEditOrDeletePack($rec->productId, $rec->packagingId)) {
                        $form->setReadOnly('packagingId');
                        $form->setReadOnly('quantity');
                    }
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
        } catch (ErrorException $e) {
        }

        if (!empty($rec->tareWeight)) {
            $row->weight .= "<span class='quiet'>" . tr('Тара') . ': </span>' . $row->tareWeight;
        }

        if ($rec->isBase == 'yes') {
            $row->packagingId = '<b>' . $row->packagingId . '</b>';
        }

        // Маркиране на втората мярка
        if ($rec->isSecondMeasure != 'no') {
            $secondMeasureId = cat_Products::getSecondMeasureId($rec->productId);
            if ($rec->packagingId == $secondMeasureId) {
                $row->packagingId = '<i>' . $row->packagingId . '</i>';
                $row->packagingId = ht::createHint($row->packagingId, 'Втора мярка', 'notice', false);
            }
        }

        if ($fields['-list']) {
            $row->user = crm_Profiles::createLink($rec->createdBy) . ', ' . $mvc->getVerbal($rec, 'createdOn');
            if($rec->createdOn >= self::getPastHorizon()){
                $row->user = ht::createHint($row->user, "Използвания|*: {$rec->usages}");
            }

            if ($transUnitId = trans_TransportUnits::fetchField("#packagingId = {$rec->packagingId}", 'id')) {
                $transUnitName = trans_TransportUnits::getTitleById($transUnitId);
                $row->packagingId = ht::createHint($row->packagingId, "Опаковката съответства на ЛЕ|*: {$transUnitName}", 'notice', false);
            }
        }

        $packState = cat_UoM::fetchField($rec->packagingId, 'state');
        if ($packState == 'closed' && $rec->state != 'closed') {
            $row->packagingId = ht::createHint($row->packagingId, 'Мярката/Опаковката е деактивирана за системата!', 'warning', false);
        }

        $row->productId = cat_Products::getHyperlink($rec->productId, true);
    }


    /**
     * Подготвя опаковките на артикула
     *
     * @param stdClass $data
     */
    public function preparePackagings($data)
    {
        $data->_masterRec = is_object($data->masterData->rec) ? $data->masterData->rec : $data->masterMvc->fetch($data->masterId);

        $data->recs = $data->rows = array();
        $fields = $this->selectFields();
        $fields['-list'] = true;

        $query = self::getQuery();
        $query->where("#productId = {$data->masterId}");
        $query->orderBy('state,quantity', 'ASC');
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = self::recToVerbal($rec, $fields);
        }

        $data->retUrl = (isset($data->retUrl)) ? $data->retUrl : cat_Products::getSingleUrlArray($data->masterId);
        if ($data->rejected !== true && $this->haveRightFor('add', (object)array('productId' => $data->masterId))) {
            $data->addUrl = array($this, 'add', 'productId' => $data->masterId, 'ret_url' => $data->retUrl);
        }

        $data->listFields = arr::make($this->listFields, true);
        unset($data->listFields['productId']);
        $shortMeasure = cat_UoM::getShortName($data->_masterRec->measureId);
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
        $tpl = (isset($data->tpl)) ? $data->tpl : getTplFromFile('cat/tpl/PackagingDetail.shtml');

        if ($data->addUrl && !Mode::isReadOnly()) {
            $addBtn = ht::createLink('<img src=' . sbf('img/16/add.png') . " style='vertical-align: middle; margin-left:5px;'>", $data->addUrl, false, 'title=Добавяне на нова опаковка/мярка');
            $tpl->append($addBtn, 'TITLE');
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
     * @param int $productId - ид на продукта
     * @param int $packagingId - ид на опаковката
     * @param string|null $field - ид на опаковката
     *
     * @return stdClass
     */
    public static function getPack($productId, $packagingId, $field = null)
    {
        if (isset($field)) {

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
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        cat_PackParams::sync($rec->packagingId, $rec->sizeWidth, $rec->sizeHeight, $rec->sizeDepth, $rec->tareWeight);

        if ($rec->state == 'closed' && $rec->isBase == 'yes') {
            $rec->isBase = 'no';
            $mvc->save_($rec, 'isBase');
        }
    }


    /**
     * Обновява първия документ, в който се използва опаковката
     *
     * @param core_Master $Master
     * @param stdClass $rec
     * @param boolean $remove
     *
     * @return false|null
     */
    public static function updateFirstDocument($Master, $rec, $remove = false)
    {
        $me = cls::get(get_called_class());
        $Master = cls::get($Master);

        $masterClassId = $Master->getClassId();
        $docId = $rec->id;

        if (is_object($rec)) {
            $cRec = $Master->fetch($rec->id);
        } else {
            $cRec = $Master->fetch($rec);
        }

        if (!$cRec->id) {

            return false;
        }

        $detArr = arr::make(self::$detailsArr, true);

        $mDetailsArr = arr::make($Master->details);

        $allClsArr = array($Master);
        $allClsArr += $mDetailsArr;

        $allClsName = array();
        $inClsName = false;
        foreach ($allClsArr as $cls) {
            $cls = cls::get($cls);

            if (!$cls->fields['packagingId']) {

                continue;
            }

            $allClsName[$cls->className] = $cls->className;
            $productIdFld = $packagingIdFld = false;

            foreach ($cls->fields as $fName => $field) {
                if (($field->type instanceof type_Key) || (($field->type instanceof type_Key2))) {
                    if ($field->type->params['mvc'] == 'cat_Products') {
                        $productIdFld = $fName;
                    }

                    if ($field->type->params['mvc'] == 'cat_UoM') {
                        $packagingIdFld = $fName;
                    }
                }
            }

            if (($productIdFld !== false) && ($packagingIdFld !== false)) {
                $inClsName = $cls->className;

                break;
            }
        }

        if ($inClsName === false) {
            foreach ($mDetailsArr as $dName) {
                if ($allClsName[$dName]) {
                    wp('Използван пакет, който е прескочен', $mDetailsArr, $dName);
                }
            }

            return;
        }

        $productIdFld = 'productId';
        $packagingIdFld = 'packagingId';

        $pArr = array();

        // В мастера
        if ($Master->className == $inClsName) {
            $productId = $rec->{$productIdFld};
            $packagingId = $rec->{$packagingIdFld};

            if ($productId && $packagingId) {
                $pArr[] = array('productId' => $productId, 'packagingId' => $packagingId);
            }
        } else {
            $Detail = cls::get($inClsName);
            $masterKey = $Detail->masterKey;

            if ($masterKey) {
                $dQuery = $Detail->getQuery();
                $dQuery->where(array("#{$masterKey} = '[#1#]'", $rec->id));

                while ($dRec = $dQuery->fetch()) {
                    $productId = $dRec->{$productIdFld};
                    $packagingId = $dRec->{$packagingIdFld};

                    if ($productId && $packagingId) {
                        $pArr[] = array('productId' => $productId, 'packagingId' => $packagingId);
                    }
                }
            } else {
                wp('Липсва masterKey', $Detail, $Master, $detArr, $inClsName, $mDetailsArr);
            }
        }

        foreach ($pArr as $pVal) {
            $productId = $pVal['productId'];
            $packagingId = $pVal['packagingId'];

            $pRec = self::fetch(array("#productId = '[#1#]' AND #packagingId = '[#2#]'", $productId, $packagingId));

            if (!$pRec) {

                continue;
            }

            $saveArr = array('firstClassId', 'firstDocId');

            if ($remove) {

                // Ако този документ е първи
                if (($pRec->firstClassId == $masterClassId) || ($pRec->firstDocId == $docId)) {
                    // При оттегляне - премахваме текущото и намираме друго подходящо

                    $pRec->firstClassId = null;
                    $pRec->firstDocId = null;

                    $me->save_($pRec, $saveArr);
                }
            }

            $dRecArr = array();
            if ($dRecArr && ($dRecArr['classId'] != $pRec->firstClassId) && ($dRecArr['id'] != $pRec->firstDocId)) {
                $pRec->firstClassId = $dRecArr['classId'];
                $pRec->firstDocId = $dRecArr['id'];

                $me->save_($pRec, $saveArr);
            }
        }
    }


    /**
     * Помощна функция за проверка на количествата в отдалечената машина
     *
     * @param $mvc
     * @param $rec
     *
     * @return array
     */
    public static function checkRemoteQuantity($mvc, $rec)
    {
        $notMatchArr = array();

        if (core_Packs::isInstalled('sync')) {
            $exportDomain = sync_Setup::get('EXPORT_URL');
            if ($exportDomain) {
                $mvc = cls::get($mvc);

                $resArr = array();

                if (!$mvc->fields['packagingId'] && !$rec->quantityInPack && !$rec->packagingId) {
                    $dArr = arr::make($mvc->details);
                    foreach ($dArr as $detail) {
                        $Detail = cls::get($detail);

                        if (!$Detail->fields['packagingId']) {

                            continue;
                        }

                        $masterKey = $Detail->masterKey;
                        $dQuery = $Detail->getQuery();
                        $dQuery->where(array("#{$masterKey} = '[#1#]'", $rec->id));

                        while ($dRec = $dQuery->fetch()) {

                            if ($dRec->packagingId && $dRec->productId) {
                                $packRec = self::fetch(array("#productId = '[#1#]' AND #packagingId = '[#2#]'", $dRec->productId, $dRec->packagingId));

                                if ($packRec && !$packRec->firstClassId && !$packRec->firstDocId) {
                                    $packRemoteId = sync_Map::fetchField(array("#classId = '[#1#]' AND #localId = '[#2#]'", cat_UoM::getClassId(), $dRec->packagingId), 'remoteId');
                                    $prodRemoteId = sync_Map::fetchField(array("#classId = '[#1#]' AND #localId = '[#2#]'", cat_Products::getClassId(), $dRec->productId), 'remoteId');
                                    $quantity = $packRec->quantity;

                                    $resArr[] = array('packRemoteId' => $packRemoteId, 'prodRemoteId' => $prodRemoteId, 'quantity' => $quantity, 'packId' => $dRec->packagingId, 'prodId' => $dRec->productId);
                                }
                            }
                        }
                    }
                } else {
                    if ($rec->packagingId && $rec->productId) {
                        $packRec = self::fetch(array("#productId = '[#1#]' AND #packagingId = '[#2#]'", $rec->productId, $rec->packagingId));

                        if ($packRec && !$packRec->firstClassId && !$packRec->firstDocId) {
                            $packRemoteId = sync_Map::fetchField(array("#classId = '[#1#]' AND #localId = '[#2#]'", cat_UoM::getClassId(), $rec->packagingId), 'remoteId');
                            $prodRemoteId = sync_Map::fetchField(array("#classId = '[#1#]' AND #localId = '[#2#]'", cat_Products::getClassId(), $rec->productId), 'remoteId');
                            $quantity = $packRec->quantity;

                            $resArr[] = array('packRemoteId' => $packRemoteId, 'prodRemoteId' => $prodRemoteId, 'quantity' => $quantity, 'packId' => $rec->packagingId, 'prodId' => $rec->productId);
                        }
                    }
                }

                $remoteIds = '';

                $lArr = array();
                foreach ($resArr as $r) {
                    if (!$r['prodRemoteId'] || !$r['packRemoteId']) {

                        continue;
                    }

                    $rStr = $r['prodRemoteId'] . '_' . $r['packRemoteId'];
                    $lStr = $r['prodId'] . '_' . $r['packId'];
                    $lArr[$rStr] = array('quantity' => $r['quantity'], 'lStr' => $lStr);
                    $remoteIds .= $remoteIds ? '|' : '';
                    $remoteIds .= $rStr;
                }

                if (empty($lArr)) {

                    return $notMatchArr;
                }

                $options = array('http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST'));

                $context = stream_context_create($options);
                $exportUrl = rtrim($exportDomain, '/');
                $exportUrl .= "/cat_products_Packagings/getRemoteQuality/?exportIds={$remoteIds}";

                @$data = file_get_contents($exportUrl, false, $context);

                if ($data === 'FALSE' || $data === FALSE) {
                    $mvc->logWarning('Проблем при проверка на отдалечените количества', $rec->id);

                    return $notMatchArr;
                }

                $dArr = json_decode($data, true);
                if (is_array($dArr)) {
                    foreach ($dArr as $rId => $rQuantity) {
                        if ($lArr[$rId]['quantity'] != $rQuantity) {
                            list($prodId, $packId) = explode('_', $lArr[$rId]['lStr']);

                            $packId = self::fetchField(array("#productId = '[#1#]' AND #packagingId = '[#2#]'", $prodId, $packId), 'id');

                            if ($packId) {
                                $notMatchArr[$packId] = $rQuantity;
                            }
                        }
                    }
                } else {
                    wp($dArr, $data);
                }
            }
        }

        return $notMatchArr;
    }


    /**
     * Екшън за вземане на количествата на опаковката за артикула
     * За sync
     *
     * @return json
     * @throws core_exception_Expect
     *
     */
    function act_getRemoteQuality()
    {
        sync_Helper::requireRight('export');
        expect($ids = Request::get('exportIds'));

        try {
            $dArr = explode('|', $ids);
            foreach ($dArr as $rId) {
                list($prodId, $packId) = explode('_', $rId);

                $packQuantity = self::fetchField(array("#productId = '[#1#]' AND #packagingId = '[#2#]'", $prodId, $packId), 'quantity');

                if (isset($packQuantity) && ($packQuantity !== false)) {
                    $resArr[$rId] = $packQuantity;
                }
            }
        } catch (core_exception_Expect $e) {
            cat_Products::logErr("Грешка подготовка на данни за експорт");
            reportException($e);
            echo 'FALSE';

            shutdown();
        }

        core_App::outputJson($resArr);
    }


    /**
     * Масив с грешните данни
     *
     * @param array $notMatchArr
     */
    public static function showNotMatchErr($notMatchArr)
    {
        foreach ($notMatchArr as $packId => $rQuantity) {
            $packRec = self::fetch($packId);

            if ($packRec && $packRec->productId && $packRec->packagingId) {
                $cRec = clone $packRec;
                $cRec->quantity = $rQuantity;
                $msg = "|Разминаване на количествата в опаковка|* \"" . cat_UoM::getVerbal($packRec->packagingId, 'name') . "\" на артикула|* " . cat_Products::getLinkToSingle($packRec->productId, 'name');
                $msg .= '<br>|В основната система е|* ' . self::getVerbal($cRec, 'quantity');
                $msg .= '<br>|Трябва да се оправи, за да може да се активира/контира.';
                status_Messages::newStatus($msg, 'error');

                self::logErr('Разминаване на количествата в опаковката', $packId);
            }
        }
    }


    /**
     * Проверява за разлика в записаните количества
     *
     * @param core_Master $mvc
     * @param stdClass $rec
     *
     * @return array
     */
    public static function checkQuantity($mvc, $rec)
    {
        $mvc = cls::get($mvc);

        $notMatchArr = array();
        if ($mvc->dontCheckQuantityInPack === true) return $notMatchArr;

        if (!$mvc->fields['packagingId'] && !$rec->quantityInPack && !$rec->packagingId) {
            $dArr = arr::make($mvc->details);
            foreach ($dArr as $detail) {
                $Detail = cls::get($detail);

                if (!$Detail->fields['packagingId']) {

                    continue;
                }

                $masterKey = $Detail->masterKey;
                $dQuery = $Detail->getQuery();
                $dQuery->where(array("#{$masterKey} = '[#1#]'", $rec->id));

                while ($dRec = $dQuery->fetch()) {
                    if ($dRec->packagingId && $dRec->productId) {
                        $quantity = self::fetchField(array("#productId = '[#1#]' AND #packagingId = '[#2#]'", $dRec->productId, $dRec->packagingId), 'quantity');
                        if (isset($quantity)) {
                            if ($quantity != $dRec->quantityInPack) {
                                $notMatchArr[$dRec->productId] = $quantity;
                            }
                        }
                    }
                }
            }
        } else {
            if ($rec->packagingId && $rec->productId) {
                $quantity = self::fetchField(array("#productId = '[#1#]' AND #packagingId = '[#2#]'", $rec->productId, $rec->packagingId), 'quantity');

                if (isset($quantity)) {
                    if ($quantity != $rec->quantityInPack) {
                        $notMatchArr[$rec->productId] = $quantity;
                    }
                }
            }
        }

        return $notMatchArr;
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

        $obj = (object)array('title' => $artStr . ': ' . cat_Products::getHyperlink($productData->productId, true), 'url' => array(), 'priority' => 0, 'comment' => '');

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
                    if (!$Detail->haveRightFor('add', (object)array($Detail->masterKey => $Doc->that))) {
                        continue;
                    }

                    $addUrl = array($Detail, 'add', "{$Detail->masterKey}" => $Doc->that, "{$Detail->productFld}" => $productData->productId, 'packagingId' => $productData->packagingId);
                    $addLink = ht::createBtn('#' . $Doc->getHandle(), $addUrl, false, false, 'ef_icon=img/16/shopping.png');

                    $documentRow = (object)array('addLink' => $addLink);

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
                            $quantity = store_Products::getQuantities($productRec->id, $storeId)->free;
                            $packQuantity = $quantity / $quantityInPack;
                            $packQuantityVerbal = (empty($packQuantity)) ? tr('Няма наличност') : core_Type::getByName('double(smartRound)')->toVerbal($packQuantity);

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

            $resArr[0]->comment .= $docTableTpl . '</div>';
            $resArr[0]->comment = new ET($resArr[0]->comment);
            if (Mode::is('screenMode', 'narrow')) {
                jquery_Jquery::run($resArr[0]->comment, 'setBarcodeHolderWidth()');
            }
        }

        return $resArr;
    }


    /**
     * Връща транспортния обем на най-голямата опаковка за посоченото количество
     *
     * @param $productId
     * @param $quantity
     * @return double|null
     */
    public static function getVolumeOfBiggestPack($productId, $quantity)
    {
        // Първо се гледа най-голямата опаковка за която има габаритни размери
        $packQuery = cat_products_Packagings::getQuery();
        $packQuery->where("#productId = '{$productId}'");
        $packQuery->where('#sizeWidth IS NOT NULL AND #sizeHeight IS NOT NULL AND #sizeDepth IS NOT NULL');
        $packQuery->orderBy('quantity', 'DESC');
        $packQuery->limit(1);

        // Ако има посочени опаковки за игнориране - игнорират се
        $ignorePackIds = keylist::toArray(cat_Setup::get('PACKAGINGS_NOT_TO_USE_FOR_VOLUME_CALC'));
        if (countR($ignorePackIds)) {
            $packQuery->notIn('packagingId', $ignorePackIds);
        }
        $packQuery->show('sizeWidth,sizeHeight,sizeDepth,quantity,packagingId');
        $packRec = $packQuery->fetch();

        // Ако няма опаковка с въведени габарити, няма да се изчислява нищо
        if (!is_object($packRec)) return;

        // Ако има такава количеството се преизчислява в нея
        $brutoVolume = $packRec->sizeWidth * $packRec->sizeHeight * $packRec->sizeDepth;
        $quantity /= $packRec->quantity;

        // Връща се намереното тегло
        $volume = $brutoVolume * $quantity;

        return round($volume, 3);
    }


    /**
     * Връща САМО опаковките на артикула
     *
     * @param int $productId
     * @return array $options
     */
    public static function getOnlyPacks($productId)
    {
        $options = array();
        $query = static::getQuery();
        $query->where("#productId = {$productId} AND #state = 'active'");
        $query->EXT('type', 'cat_UoM', 'externalName=type,externalKey=packagingId');
        $query->where("#type = 'packaging'");
        while ($rec = $query->fetch()) {
            $options[$rec->packagingId] = cat_UoM::getTitleById($rec->packagingId, false);
        }

        return $options;
    }


    /**
     * Връща хоризонта в миналото от който ще се следят използванията на опаковките
     */
    public static function getPastHorizon()
    {
        $months = cat_Setup::get('LAST_PACK_USAGES');
        $from = dt::addMonths(-1 * $months, null, false);

        return "{$from} 00:00:00";
    }


    /**
     * Обновяване на последните използвания на продуктовите опаковки по разписание
     */
    function cron_recalcLastUsedPacks()
    {
        // Детайли в които ще проверяваме
        $from = self::getPastHorizon();

        // Извличат се продуктовите опаковки създавани след посочения хоризонт
        core_Debug::startTimer('GET_PACKS');
        $usages = array();
        $pQuery = cat_products_Packagings::getQuery();
        $pQuery->show('productId,packagingId,id');
        $pQuery->where("#createdOn >= '{$from}'");
        while ($pRec = $pQuery->fetch()) {
            $usages["{$pRec->productId}|{$pRec->packagingId}"] = (object)array('id' => $pRec->id, 'productId' => $pRec->productId, 'packagingId' => $pRec->packagingId, 'usages' => 0);
        }
        core_Debug::stopTimer('GET_PACKS');

        // За всеки от детайлите се преброяват използванията
        core_Debug::startTimer('COUNT_ALL');
        foreach (self::$detailsArr as $Detail) {
            core_Debug::startTimer("COUNT {$Detail}");

            $packagingFields = cls::get($Detail)->getPackagingFields();
            foreach ($packagingFields as $packagingFieldName){
                $this->calcUsage($Detail, $packagingFieldName, $from, $usages);
            }

            core_Debug::stopTimer("COUNT {$Detail}");
            core_Debug::log("END COUNT {$Detail}" . round(core_Debug::$timers["COUNT {$Detail}"]->workingTime, 6));
        }

        core_Debug::stopTimer('COUNT_ALL');
        core_Debug::log("END GET_PACKS" . round(core_Debug::$timers["GET_PACKS"]->workingTime, 6));
        core_Debug::log("END COUNT_ALL" . round(core_Debug::$timers["COUNT_ALL"]->workingTime, 6));

        // Ще се обновят използванията само на съществуващите продуктови опаковки
        core_Debug::startTimer('SAVE_ALL');
        $Packagings = cls::get('cat_products_Packagings');
        if (countR($usages)) {
            $Packagings->saveArray($usages, 'id,usages');
        }
        core_Debug::stopTimer('SAVE_ALL');
    }


    /**
     * Преброява използванията да продукт+опаковка в подадения документ
     *
     * @param mixed $Detail        - детайл
     * @param string $packagingFld - име на полето за опаковката
     * @param string $from         - от коя дата
     * @param array $usages        - масив към който да се добавя бройката
     * @return void
     */
    private function calcUsage($Detail, $packagingFld, $from, &$usages)
    {
        $dInst = cls::get($Detail);
        $dQuery = $dInst->getQuery();
        if (!$dInst->getField('createdOn', false)) {
            if ($dInst->Master && $dInst->masterKey) {
                $mInst = cls::get($dInst->Master);
                if ($mInst->fields['createdOn']) {
                    $dQuery->EXT('createdOn', $dInst->Master->className, "externalName=createdOn,externalKey={$dInst->masterKey}");
                }
            }
        }

        setIfNot($dInst->productFld, 'productId');
        list($productFld, $packagingFld) = array($dInst->productFld, $packagingFld);
        if ($Detail == 'pos_ReceiptDetails') {
            $dQuery->where("#action = 'sale|code'");
        }

        $dQuery->EXT('pMeasureId', 'cat_Products', "externalName=measureId,externalKey={$productFld}");
        $dQuery->where("#pMeasureId != #{$packagingFld}");

        $dQuery->XPR('count', 'int', "COUNT(#id)");
        $dQuery->groupBy("{$productFld},{$packagingFld}");
        $dQuery->where("#createdOn >= '{$from}'");
        $dQuery->show("{$productFld},{$packagingFld},count");

        while ($dRec = $dQuery->fetch()) {
            $key = "{$dRec->{$productFld}}|{$dRec->{$packagingFld}}";
            if (array_key_exists($key, $usages)) {
                $usages[$key]->usages += $dRec->count;
            }
        }
    }


    /**
     * Може ли продуктовата опаковка да бъде редактирана или изтривана
     *
     * @param int $productId
     * @param int $packagingId
     * @return bool
     */
    public static function canEditOrDeletePack($productId, $packagingId)
    {
        $rec = self::fetch("#productId = {$productId} AND #packagingId = {$packagingId}");

        // Ако е създадена повече от зададеното време - НЕ
        $pastHorizon = self::getPastHorizon();
        if($rec->createdOn <= $pastHorizon) return false;

        // Ако е създадена в хоризонта, само ако не е използвана никъде
        if(is_null($rec->usages) || $rec->usages <= 0) return true;

        return false;
    }


    /**
     * Логване на използване на продуктовата опаковка
     *
     * @param int $productId
     * @param int $packagingId
     * @param bool $remove
     * @return int
     */
    public static function logUsage($productId, $packagingId, $remove = false)
    {
        $rec = self::fetch("#productId = {$productId} AND #packagingId = {$packagingId}");
        if(empty($rec)) return null;

        if($remove){
            $rec->usages -= 1;
        } else {
            $rec->usages += 1;
        }

        return self::save($rec, 'usages');
    }


    /**
     * Подготовка на филтър формата
     *
     * @param core_Mvc $mvc
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->showFields = 'productId,packagingId';
        $data->listFilter->setField('productId', 'input,title=Артикул');
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input('productId,packagingId');

        if($filter = $data->listFilter->rec){
            if(!empty($filter->productId)){
                $data->query->where("#productId = {$filter->productId}");
            }

            if(!empty($filter->packagingId)){
                $data->query->where("#packagingId = {$filter->packagingId}");
            }
        }
    }
}
