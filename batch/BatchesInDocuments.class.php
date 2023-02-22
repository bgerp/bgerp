<?php


/**
 * Регистър за разпределяне на разходи
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class batch_BatchesInDocuments extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Партиди в документи';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'batch_Wrapper';


    /**
     * Единично заглавие
     */
    public $singleTitle = 'Партида';


    /**
     * Кой може да променя?
     */
    public $canWrite = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,date,containerId=Документ,productId=Артикул,packagingId=Опаковка,quantityInPack=К-во в опаковка,quantity=Количество,batch=Партида,operation=Операция,storeId=Склад';


    /**
     * Описание на модела (таблицата)
     */
    public static $cache = array();


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('detailClassId', 'class(interface=core_ManagerIntf)', 'caption=Детайл,mandatory,silent,input=hidden,remember');
        $this->FLD('detailRecId', 'int', 'caption=Ред от детайл,mandatory,silent,input=hidden,remember');
        $this->FLD('productId', 'key(mvc=cat_Products)', 'caption=Артикул,mandatory,silent,input=hidden,remember');
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory,smartCenter,input=hidden,tdClass=small-field nowrap');
        $this->FLD('quantity', 'double(decimals=4)', 'caption=Количество,input=none');
        $this->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
        $this->FLD('date', 'date', 'mandatory,caption=Дата,silent,input=hidden');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'mandatory,caption=Ориджин,silent,input=hidden');
        $this->FLD('batch', 'text', 'input=none,caption=Партида,after=productId,forceField');
        $this->FLD('operation', 'enum(in=Влиза, out=Излиза, stay=Стои)', 'mandatory,caption=Операция');
        $this->FLD('storeId', 'key(mvc=store_Stores)', 'caption=Склад');

        $this->setDbIndex('detailClassId,detailRecId');
        $this->setDbIndex('productId');
        $this->setDbIndex('detailClassId,detailRecId,productId,storeId');
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        try {
            $row->containerId = doc_Containers::getDocument($rec->containerId)->getLink(0);
        } catch (core_exception_Expect $e) {
            $row->containerId = "<span class='color:red'>" . tr('Проблем при показването') . '</span>';
        }

        $row->productId = cat_Products::getHyperlink($rec->productId, true);
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'modify' && isset($rec)) {
            $requiredRoles = cls::get($rec->detailClassId)->getRolesToModifyBatches($rec->detailRecId);

            if (!isset($rec->detailClassId) || !isset($rec->detailRecId)) {
                $requiredRoles = 'no_one';
            } else {
                $recInfo = cls::get($rec->detailClassId)->getRowInfo($rec->detailRecId);
                if (cat_Products::fetchField($recInfo->productId, 'canStore') != 'yes') {
                    $requiredRoles = 'no_one';
                } elseif (!batch_Defs::getBatchDef($recInfo->productId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }


    /**
     * Рендиране на партидите на даде обект
     *
     * @param mixed $detailClassId - клас на обект
     * @param int $detailRecId - ид на обект
     * @param int $storeId - ид на склад
     *
     * @return core_ET $tpl        - шаблона с рендирането
     */
    public static function renderBatches($detailClassId, $detailRecId, $storeId)
    {
        $Class = cls::get($detailClassId);
        $detailClassId = $Class->getClassId();
        $rInfo = cls::get($detailClassId)->getRowInfo($detailRecId);
        if (!countR($rInfo->operation)) {

            return;
        }

        $showBatchLink = core_Packs::isInstalled('rack') && $rInfo->operation['in'] && ($Class->hasPlugin('rack_plg_IncomingShipmentDetails') || $Class instanceof planning_DirectProductionNote) && $rInfo->state != 'rejected';
        $palletStoreId = isset($rInfo->operation['in']) ? $rInfo->operation['in'] : $storeId;
        $operation = key($rInfo->operation);

        $query = self::getQuery();
        $query->where("#detailClassId = {$detailClassId} AND #detailRecId = {$detailRecId} AND #operation = '{$operation}'");
        $query->orderBy('id', 'ASC');
        $batchDef = batch_Defs::getBatchDef($rInfo->productId);

        $file = ($batchDef instanceof batch_definitions_Serial) ? 'batch/tpl/BatchInfoBlockSerial.shtml' : 'batch/tpl/BatchInfoBlock.shtml';
        $tpl = getTplFromFile($file);

        $count = 0;
        $total = $rInfo->quantity;
        $totalCount = $query->count() - 1;

        $blocks = array();
        while ($rec = $query->fetch()) {
            $batch = batch_Movements::getLinkArr($rec->productId, $rec->batch);
            if (is_array($batch)) {
                foreach ($batch as $key => &$b) {
                    if ($msg = self::checkBatchRow($detailClassId, $detailRecId, $key, $rec->quantity)) {
                        $b = ht::createHint($b, $msg, 'warning');
                        $b = $b->getContent();
                    }
                }
            }

            $block = clone $tpl->getBlock('BLOCK');
            $total -= $rec->quantity;
            $total = round($total, 5);

            $caption = $batchDef->getFieldCaption();
            $label = (!empty($caption)) ? tr($caption) . ':' : 'lot:';
            $batch1 = $batch;
            $batch = implode(', ', $batch);

            // Вербализацията на к-то ако е нужно
            if (countR($batch1) == 1 && (!($batchDef instanceof batch_definitions_Serial))) {
                $quantityInPack = empty($rInfo->quantityInPack) ? 1 : $rInfo->quantityInPack;
                $q = $rec->quantity / $quantityInPack;
                $quantity = core_Type::getByName('double(smartRound)')->toVerbal($q);
                if ($rInfo->operation['out'] && in_array($rInfo->state, array('draft', 'pending'))) {
                    $batchQuantityInStore = batch_Items::getQuantity($rec->productId, $rec->batch, $storeId);
                    if ($rec->quantity > $batchQuantityInStore) {
                        $batchQuantityInStoreVerbal = core_Type::getByName('double(smartRound)')->toVerbal($batchQuantityInStore / $quantityInPack);
                        $quantity = ht::createHint($quantity, 'Над наличното количество|* ' . $batchQuantityInStoreVerbal . ' |в|* "' . store_Stores::getTitleById($storeId) . '". |Проверете за контирани документи по партидата с по-нова дата|*.', 'warning', false);
                    }
                }
                $quantity .= ' ' . cat_UoM::getShortName($rInfo->packagingId);

                if ($showBatchLink) {
                    if ($palletImgLink = rack_Pallets::getFloorToPalletImgLink($palletStoreId, $rInfo->productId, $rInfo->packagingId, $q, $rec->batch, $rInfo->containerId)) {
                        $label = $palletImgLink . $label;
                    }
                }

                $block->append($quantity, 'quantity');
            }

            if ($batchDef instanceof batch_definitions_Serial) {
                if ($showBatchLink) {
                    if ($palletImgLink = rack_Pallets::getFloorToPalletImgLink($palletStoreId, $rInfo->productId, $rInfo->packagingId, 1, $rec->batch, $rInfo->containerId)) {
                        $batch = $palletImgLink . $batch;
                    }
                }

                $label = ($count == 0) ? "{$label} " : '';
                $end = ($count == $totalCount) ? '' : ',';
                $string = "{$label}{$batch}{$end}";
            } else {
                $string = "{$label} {$batch}" . '<br>';
            }

            $block->append($string, 'batch');
            $block->removePlaces();

            $blocks[$rec->batch] = $block;
            $count++;
        }

        $batchDef->orderBatchesForDisplay($blocks);
        foreach ($blocks as $block) {
            $block->append2Master();
        }

        // Ако има остатък
        if ($total > 0 || $total < 0) {

            // Показва се като 'Без партида'
            $block = clone $tpl->getBlock('NO_BATCH');
            if ($total > 0) {
                $noBatchQuantity = $total / $rInfo->quantityInPack;
                $batch = "<i style=''>" . tr('Без партида') . '</i>';
                $quantity = cls::get('type_Double', array('params' => array('smartRound' => true)))->toVerbal($noBatchQuantity);
                $quantity .= ' ' . cat_UoM::getShortName($rInfo->packagingId);

                if ($showBatchLink) {
                    if ($palletImgLink = rack_Pallets::getFloorToPalletImgLink($palletStoreId, $rInfo->productId, $rInfo->packagingId, $noBatchQuantity, null, $rInfo->containerId)) {
                        $batch = $palletImgLink . $batch;
                    }
                }
            } else {
                $batch = "<i style='color:red'>" . tr('Несъответствие') . '</i>';
                $batch = ht::createHint($batch, 'К-то на разпределените партиди е повече от това на реда', 'error');
                $quantity = '';
                $block->append('color:red', 'BATCH_STYLE');
            }

            $block->append($batch, 'nobatch');
            $block->append($quantity, 'nobatchquantity');
            $block->removePlaces();
            $block->append2Master();
        }

        $tpl->removePlaces();

        return $tpl;
    }


    /**
     * Проверка на реда дали има проблеми с партидата
     *
     * @param mixed $detailClassId
     * @param int $detailRecId
     * @param string $batch
     * @param string $quantity
     *
     * @return FALSE|string
     */
    public static function checkBatchRow($detailClassId, $detailRecId, $batch, $quantity)
    {
        $Class = cls::get($detailClassId);
        $rInfo = $Class->getRowInfo($detailRecId);
        if (empty($rInfo->operation[key($rInfo->operation)])) {

            return false;
        }

        // Ако операцията е изходяща
        if ($rInfo->operation == 'out' && $rInfo->state == 'draft') {
            $storeQuantity = batch_Items::getQuantity($rInfo->productId, $batch, $rInfo->operation['out']);
            if ($quantity > $storeQuantity) {

                return 'Недостатъчно количество в склада';
            }
        }

        $def = batch_Defs::getBatchDef($rInfo->productId);

        // Ако е сериен номер проверка дали не се повтаря
        if ($def instanceof batch_definitions_Serial) {
            if ($Class instanceof core_Detail) {
                $rec = $Class->fetch($detailRecId);
                $key = $Class->getClassId() . "|{$rec->{$Class->masterKey}}";
                if (!array_key_exists($key, self::$cache)) {
                    $siblingsQuery = $Class->getQuery();
                    $siblingsQuery->where("#{$Class->masterKey} = {$rec->{$Class->masterKey}}");
                    $siblingsQuery->show('id');
                    self::$cache[$key] = arr::extractValuesFromArray($siblingsQuery->fetchAll(), 'id');
                }
            }

            $query = self::getQuery();
            $query->where("#detailClassId = {$detailClassId}");
            $query->in('detailRecId', self::$cache[$key]);
            $query->show('batch,productId');
            $query->groupBy('batch');
            if ($detailRecId) {
                $query->where("#detailRecId != {$detailRecId}");
            }

            $oSerials = $def->makeArray($batch);

            // За всеки
            while ($oRec = $query->fetch()) {
                $serials = batch_Defs::getBatchArray($oRec->productId, $oRec->batch);

                // Проверяваме имали дублирани
                $intersectArr = array_intersect($oSerials, $serials);
                $intersect = countR($intersectArr);

                // Ако има казваме, кои се повтарят
                // един сериен номер не може да е на повече от един ред
                if ($intersect) {
                    $imploded = implode(',', $intersectArr);
                    if ($intersect == 1) {

                        return "|Серийният номер|*: {$imploded}| се повтаря в документа|*";
                    }

                    return "|Серийните номера|*: {$imploded}| се повтарят в документа|*";
                }
            }
        }
    }


    /**
     * Екшън за модифициране на партидите
     */
    public function act_Modify()
    {
        expect($detailClassId = Request::get('detailClassId', 'class'));
        expect($detailRecId = Request::get('detailRecId', 'int'));
        expect($storeId = Request::get('storeId', 'key(mvc=store_Stores)'));
        $retUrl = getRetUrl();

        // Проверка на права
        $this->requireRightFor('modify', (object)array('detailClassId' => $detailClassId, 'detailRecId' => $detailRecId));
        $Detail = cls::get($detailClassId);
        $recInfo = $Detail->getRowInfo($detailRecId);
        $recInfo->detailClassId = $detailClassId;
        $recInfo->detailRecId = $detailRecId;
        $storeId = $recInfo->operation[key($recInfo->operation)];

        // Кои са наличните партиди към момента
        $batches = batch_Items::getBatchQuantitiesInStore($recInfo->productId, $storeId, $recInfo->date);

        // Ако има други споменати партиди в нишката добавят се и те като достъпни
        $batchesInThread = array();
        $threadId = doc_Containers::fetchField($recInfo->containerId, 'threadId');
        $cQuery = doc_Containers::getQuery();
        $cQuery->where("#threadId = {$threadId} AND #id != {$recInfo->containerId}");
        $cQuery->show('id');
        $cIds = arr::extractValuesFromArray($cQuery->fetchAll(), 'id');
        if (countR($cIds)) {
            $query1 = batch_BatchesInDocuments::getQuery();
            $query1->where("#productId = {$recInfo->productId}");
            $query1->in('containerId', $cIds);
            while ($r1 = $query1->fetch()) {
                $batchesInThread[$r1->batch] = 0;
            }
        }

        $batches = $batches + $batchesInThread;
        foreach ($batches as $i => $v) {
            $itemState = batch_Items::fetchField(array("#productId = {$recInfo->productId} AND #storeId = {$storeId} AND #batch = '[#1#]'", $i), 'state');
            if ($itemState == 'closed') {
                unset($batches[$i]);
            }
        }

        // Кои са въведените партиди от документа
        $foundBatches = array();
        $dQuery = self::getQuery();
        $dQuery->where("#detailClassId = {$detailClassId} AND #detailRecId = {$detailRecId}");
        while ($dRec = $dQuery->fetch()) {
            $foundBatches[$dRec->batch] = $dRec->quantity;
            if (!array_key_exists($dRec->batch, $batches)) {
                $batches[$dRec->batch] = $dRec->quantity;
            }
        }

        // Филтриране на партидите
        $Detail->filterBatches($detailRecId, $batches);
        $packName = cat_UoM::getShortName($recInfo->packagingId);

        $link = doc_Containers::getDocument($recInfo->containerId)->getLink(0);

        // Подготовка на формата
        $form = cls::get('core_Form');
        $form->title = 'Задаване на партидности в|* ' . $link;
        $form->info = new core_ET(tr('Артикул|*:[#productId#]<br>|Склад|*: [#storeId#]<br>|Количество за разпределяне|*: <b>[#quantity#] [#packName#]</b>'));
        $form->info->replace(cat_Products::getHyperlink($recInfo->productId, true), 'productId');
        $form->info->replace(store_Stores::getHyperlink($storeId, true), 'storeId');
        $form->info->replace($packName, 'packName');
        $form->info->append(cls::get('type_Double', array('params' => array('smartRound' => true)))->toVerbal($recInfo->quantity / $recInfo->quantityInPack), 'quantity');

        // Кеширане на модифицируемите записи
        if($Detail instanceof core_Detail){
            $selArr = static::getBatchModifiableRecs($Detail, $detailRecId, $storeId);
            if (!empty($selArr)) {
                Mode::setPermanent("{$this->className}_{$Detail->className}_prevAndNext", $selArr);
            } elseif (!($form->cmd == 'save_n_next' || $form->cmd == 'save_n_prev' || Request::get('PrevAndNext'))) {
                Mode::setPermanent("{$this->className}_{$Detail->className}_prevAndNext", null);
            }
        }


        $Def = batch_Defs::getBatchDef($recInfo->productId);
        $suggestions = array();
        $Def->orderBatchesForDisplay($batches);

        $type = $Detail->getBatchMovementDocument($detailRecId);
        $bOptions = null;
        if ($type == 'in') {
            $bOptions = $Detail->getAllowedInBatches($detailRecId);
        }

        if ($Def instanceof batch_definitions_Serial) {

            // Полетата излизат като списък
            $suggestions = '';
            foreach ($batches as $b => $q) {
                $bArray = $Def->makeArray($b);
                foreach ($bArray as $b1) {
                    $verbal = strip_tags($Def->toVerbal($b1));
                    $suggestions .= "{$b1}={$verbal},";
                }
            }
            $suggestions = trim($suggestions, ',');
            if (!empty($suggestions)) {
                $form->FLD('serials', "set({$suggestions})", 'caption=Партиди,maxRadio=2,class=batch-quantity-fields');
            }

            if (countR($foundBatches)) {
                $foundArr = array();
                foreach ($foundBatches as $f => $q) {
                    $fArray = $Def->makeArray($f);
                    foreach ($fArray as $b2) {
                        $foundArr[$b2] = $b2;
                    }
                }

                $defaultBatches = $form->getFieldType('serials')->fromVerbal($foundArr);
                $form->setDefault('serials', $defaultBatches);
            }
        } else {
            Mode::push('htmlEntity', 'none');
            $i = $j = 0;
            $tableRec = $exTableRec = array();
            $batchesCount = countR($batches);
            foreach ($batches as $batch => $quantityInStore) {
                Mode::push('text', 'plain');
                $vBatch = $Def->toVerbal($batch);
                Mode::pop('text');
                $suggestions[] = $vBatch;
                $tableRec['batch'][$i] = $vBatch;
                if (array_key_exists($batch, $foundBatches)) {
                    $tableRec['quantity'][$i] = core_Math::roundNumber($foundBatches[$batch] / $recInfo->quantityInPack);
                    $exTableRec['batch'][$j] = $vBatch;
                    $exTableRec['quantity'][$j] = $foundBatches[$batch];
                    $j++;
                } else {
                    $tableRec['quantity'][$i] = '';
                }
                $i++;
            }
            Mode::pop('htmlEntity');

            if ($batchesCount > batch_Setup::get('COUNT_IN_EDIT_WINDOW')) {
                $tableRec = $exTableRec;
            }
        }

        // Добавяне на поле за нова партида
        $btnoff = ($Detail->cantCreateNewBatch === true) ? 'btnOff' : '';
        $caption = ($Def->getFieldCaption()) ? $Def->getFieldCaption() : 'Партида';
        $columns = ($Def instanceof batch_definitions_Serial) ? 'batch' : 'batch|quantity';
        $captions = ($Def instanceof batch_definitions_Serial) ? 'Партида' : 'Партида|Количество';
        $noCaptions = ($Def instanceof batch_definitions_Serial) ? 'noCaptions' : '';
        $hideTable = (($Def instanceof batch_definitions_Serial) && !empty($btnoff)) || (!empty($btnoff) && !countR($suggestions) && !($Def instanceof batch_definitions_Serial));

        if ($hideTable === false) {
            $form->FLD('newArray', "table({$btnoff},columns={$columns},batch_class=batchNameTd,batch_ro=readonly,captions={$captions},{$noCaptions},validate=batch_BatchesInDocuments::validateNewBatches)", "caption=Партиди->{$caption},placeholder={$Def->placeholder}");

            if (is_array($bOptions)) {
                $form->setFieldTypeParams('newArray', array('batch_opt' => $bOptions));
            }

            // Ако има опции от типа добавят се с възможност за избор
            $BatchType = $Def->getBatchClassType();
            if ($BatchType instanceof type_Enum) {
                $bOptions = $BatchType->options;
                $suggestions = array_combine(array_values($bOptions), array_values($bOptions)) + $suggestions;
            }

            $form->setFieldTypeParams('newArray', array('batch_sgt' => $suggestions));
            $form->setFieldTypeParams('newArray', array('batchDefinition' => $Def));
            $form->setDefault('newArray', $tableRec);
        } else {
            $form->info->append("<br>" . tr('В документа може да се използват само вече създадени партиди'));
        }

        // Какви са наличните партиди
        $Def = batch_Defs::getBatchDef($recInfo->productId);

        $form->input();
        $saveBatches = array();

        $selArr = Mode::get("{$this->className}_{$Detail->className}_prevAndNext");
        if(!empty($selArr)){
            $currentPosition = array_search($detailRecId, $selArr);
            $pos = $currentPosition + 1;
            $prevAndNextIndicator = $pos . '/' . countR($selArr);
            $form->prev = $selArr[$currentPosition - 1];
            $form->next = $selArr[$currentPosition + 1];
        }

        // След събмит
        if ($form->isSubmitted()) {
            $r = $form->rec;
            $delete = array();
            $total = 0;

            if (!empty($r->newArray)) {
                $newBatches = (array)@json_decode($r->newArray);
                $bCount = countR($newBatches['batch']);

                for ($i = 0; $i <= $bCount - 1; $i++) {
                    if (empty($newBatches['batch'][$i])) {
                        continue;
                    }
                    $batch = $Def->normalize($newBatches['batch'][$i]);

                    $Double = core_Type::getByName('double');
                    if ($Def instanceof batch_definitions_Serial) {
                        $newBatches['quantity'][$i] = 1;
                    }

                    if (!empty($newBatches['quantity'][$i])) {
                        $quantity = $Double->fromVerbal($newBatches['quantity'][$i]);
                        if ($quantity) {
                            $total += $quantity;
                        }

                        $quantity = ($Def instanceof batch_definitions_Serial) ? 1 : $quantity;
                        $saveBatches[$batch] = $quantity * $recInfo->quantityInPack;

                        // Проверка на к-то
                        $warning = null;
                        if (!deals_Helper::checkQuantity($recInfo->packagingId, $quantity, $warning)) {
                            $form->setWarning('newArray', $warning);
                        }
                    } else {
                        $delete[] = $newBatches['batch'][$i];
                    }
                }
            }

            if ($Def instanceof batch_definitions_Serial) {
                $batches = type_Set::toArray($r->serials);
                if (countR($batches) > $recInfo->quantity) {
                    if ($form->cmd != 'updateQuantity') {
                        $form->setError('serials', 'Серийните номера са повече от цялото количество');
                    }
                }

                foreach ($batches as $b) {
                    $saveBatches[$b] = 1 / $recInfo->quantityInPack;
                    ++$total;
                }

                if (is_array($foundBatches)) {
                    foreach ($foundBatches as $fb => $q) {
                        if (!array_key_exists($fb, $batches)) {
                            $delete[] = $fb;
                            unset($saveBatches[$fb]);
                        }
                    }
                }
            }

            if ($form->cmd != 'updateQuantity') {
                // Не може да е разпределено по-голямо количество от допустимото
                $round = cat_UoM::fetchField($recInfo->packagingId, 'round');
                $expectedTotal = round(($recInfo->quantity / ($recInfo->quantityInPack)), $round);
                if (round($total, $round) > $expectedTotal) {
                    $form->setError('newArray', "Общото количество е над допустимото|*: <b>{$expectedTotal}</b>");
                }
            }

            if (!$form->gotErrors()) {
                $dRec = cls::get($detailClassId)->fetch($detailRecId);
                $logMsg = 'Ръчна промяна на партидите на детайл';

                if ($form->cmd == 'auto') {
                    $old = (countR($foundBatches)) ? $foundBatches : array();
                    $saveBatches = $Def->allocateQuantityToBatches($recInfo->quantity, $storeId, $Detail, $detailRecId, $recInfo->date);
                    $intersect = array_diff_key($old, $saveBatches);
                    $delete = (countR($intersect)) ? array_keys($intersect) : array();
                    $logMsg = 'Ръчно преразпределяне на партидите';
                }

                // Ъпдейт/добавяне на записите, които трябва
                if (countR($saveBatches)) {
                    self::saveBatches($detailClassId, $detailRecId, $saveBatches);
                }

                // Изтриване
                if (countR($delete)) {
                    foreach ($delete as $b) {
                        $b = $Def->normalize($b);
                        self::delete(array("#detailClassId = {$recInfo->detailClassId} AND #detailRecId = {$recInfo->detailRecId} AND #productId = {$recInfo->productId} AND #batch = '[#1#]'", $b));
                    }
                }

                if ($form->cmd == 'updateQuantity' && !empty($total)) {
                    $logMsg = 'Ръчна промяна на партидите и задаване на ново общо количество на детайл';
                    if ($Detail instanceof store_InternalDocumentDetail) {
                        $dRec->packQuantity = $total / $recInfo->quantityInPack;
                    } else {
                        $dRec->quantity = $total * $recInfo->quantityInPack;
                    }
                }

                // Предизвиква се обновяване на документа
                cls::get($detailClassId)->save($dRec);
                if ($Detail instanceof core_Detail) {
                    $Detail->Master->logWrite($logMsg, $dRec->{$Detail->masterKey});
                } else {
                    $Detail->logWrite($logMsg, $dRec->id);
                }

                // Ако има избрани за обхождане редирект към тях
                if (!empty($selArr)) {
                    $redirectToId = null;
                    if($form->cmd == 'save_n_next'){
                        if(isset($form->next)){
                            $redirectToId = $form->next;
                        }
                    } elseif($form->cmd == 'save_n_prev'){
                        if(isset($form->prev)){
                            $redirectToId = $form->prev;
                        }
                    }

                    if(isset($redirectToId)){
                        $url = array('batch_BatchesInDocuments', 'modify', 'detailClassId' => $Detail->getClassId(), 'detailRecId' => $redirectToId, 'storeId' => $storeId, 'ret_url' => $retUrl);

                        return new Redirect($url);
                    }
                }

                return new Redirect($retUrl);
            }
        }

        // Добавяне на бутони
        $form->toolbar->addSbBtn('Промяна', 'save', 'id=btnSave,ef_icon = img/16/disk.png, title = Запис на документа');
        $form->toolbar->setBtnOrder('btnSave', 1);

        if (!($Detail instanceof planning_Jobs)) {
            $form->toolbar->addSbBtn('Това е к-то', 'updateQuantity', 'id=updateQuantity,ef_icon = img/16/disk.png,title = Обновяване на количеството');
            $form->toolbar->setBtnOrder('updateQuantity', 30);
        }

        $operation = key($recInfo->operation);
        if ($operation == 'out') {
            $attr = arr::make('id=btnAuto,warning=К-то ще бъде разпределено автоматично по наличните партиди,ef_icon = img/16/arrow_refresh.png, title = Автоматично разпределяне на количеството');
            $attr['onclick'] = "$(this.form).find('.batch-quantity-fields').val('');";
            $form->toolbar->addSbBtn('Автоматично', 'auto', $attr);
            $form->toolbar->setBtnOrder('btnSave', 6);
        }

        $form->toolbar->addBtn('Отказ', $retUrl, 'id=back,ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        $form->toolbar->setBtnOrder('back', 50);

        // Добавяне на бутони за обхождане на другите редове
        if (!empty($selArr)) {
            if (countR($selArr) > 1) {
                if (isset($form->next)) {
                    $form->toolbar->addSbBtn('»»»', 'save_n_next', 'class=noicon fright,order=30, title = Следващ');
                } else {
                    $form->toolbar->addSbBtn('»»»', 'save_n_next', 'class=btn-disabled noicon fright,disabled,order=30, title = Следващ');
                }
                $form->toolbar->addFnBtn($prevAndNextIndicator, '', 'class=noicon fright,order=30');
                if (isset($form->prev)) {
                    $form->toolbar->addSbBtn('«««', 'save_n_prev', 'class=noicon fright,order=30, title = Предишен');
                } else {
                    $form->toolbar->addSbBtn('«««', 'save_n_prev', 'class=btn-disabled noicon fright,disabled,order=30, title = Предишен');
                }
            }
        }

        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);

        // Рендиране на формата
        return $tpl;
    }


    /**
     * Връща ид-та на всички детайли, на които може да се задават партиди
     *
     * @param core_Detail $Detail
     * @param int $detailRecId
     * @param int $storeId
     * @return array $modifiableRecs
     */
    private static function getBatchModifiableRecs($Detail, $detailRecId, $storeId)
    {
        $modifiableRecs = array();
        $detailIds = $Detail->getPrevAndNextDetailQuery($detailRecId);
        foreach($detailIds as $dId){
            if(batch_BatchesInDocuments::haveRightFor('modify', (object) array('detailClassId' => $Detail->getClassId(), 'detailRecId' => $dId, 'storeId' => $storeId))) {
                $modifiableRecs[] = $dId;
            }
        }

        return $modifiableRecs;
    }

    
    /**
     * Валидира партидите
     */
    public static function validateNewBatches($tableData, $Type)
    {
        $res = array();
        $Def = $Type->params['batchDefinition'];
        $tableData = (array) $tableData;
        $isSerial = $Def instanceof batch_definitions_Serial;
        
        $error = $errorFields = array();
        $batches = $tableData['batch'];
        if (empty($tableData)) {
            
            return;
        }
        
        $bArray = array();
        foreach ($batches as $key => $batch) {
            if (!empty($batch)) {
                if ($isSerial) {
                    if (empty($tableData['quantity'][$key])) {
                        $tableData['quantity'][$key] = 1;
                    }
                }
                $msg = null;
                if (!$Def->isValid($batch, $tableData['quantity'][$key], $msg)) {
                    if($tableData['quantity'][$key] != 0){
                        $error[] = "{$batch} :|* {$msg}";
                        $errorFields['batch'][$key] = "{$batch} :|* {$msg}";
                    }
                }
                
                if (array_key_exists($batch, $bArray)) {
                    $error[] = 'Повтаряща се партида';
                    $errorFields['batch'][$key] = 'Повтаряща се партида';
                } else {
                    $bArray[$batch] = $batch;
                }
            }
        }
        
        if (is_array($tableData['quantity'])) {
            foreach ($tableData['quantity'] as $key => $quantity) {
                if (!empty($quantity)) {
                    if (empty($tableData['batch'][$key])) {
                        $error[] = 'Попълнено количество без да има партида';
                        $errorFields['quantity'][$key] = 'Попълнено количество без да има партида';
                        $errorFields['batch'][$key] = 'Попълнено количество без да има партида';
                    }
                    
                    $Max = ($isSerial) ? 'max=1' : '';
                    $Double = core_Type::getByName("double(min=0,{$Max})");
                    $qVal = $Double->isValid($quantity);
                    
                    if (!empty($qVal['error'])) {
                        $error[] = 'Количеството ' . mb_strtolower($qVal['error']);
                        $errorFields['quantity'][$key] = 'Количеството ' . mb_strtolower($qVal['error']);
                    }
                    
                    $q2 = $Double->fromVerbal($quantity);
                    if (!$q2) {
                        $error[] = 'Невалидно количество';
                        $errorFields['quantity'][$key] = 'Невалидно количество';
                    }
                }
            }
        }
        
        if (countR($error)) {
            $error = implode('|*<li>|', $error);
            $res['error'] = $error;
        }
        
        if (countR($errorFields)) {
            $res['errorFields'] = $errorFields;
        }
        
        return $res;
    }
    
    
    /**
     * Връща ид-то съответстващо на записа
     *
     * @param int    $detailClassId - ид на клас
     * @param int    $detailRecId   - ид на запис
     * @param int    $productId     - ид на артикул
     * @param string $batch         - партида
     * @param string $operation     - операция
     */
    public static function getId($detailClassId, $detailRecId, $productId, $batch, $operation)
    {
        $detailClassId = cls::get($detailClassId)->getClassId();
        $where = "#detailClassId = {$detailClassId} AND #detailRecId = {$detailRecId} AND #productId = {$productId} AND #operation = '{$operation}'";
        if (!empty($batch)) {
            $where .= " AND #batch = '[#1#]'";
            return self::fetchField(array($where, $batch));
        } else {
            return self::fetchField($where);
        }
    }
    
    
    /**
     * Записва масив с партиди и техните количества на ред
     *
     * @param mixed $detailClassId
     * @param int   $detailRecId
     * @param array $batchesArr
     * @param bool  $sync
     *
     * @return void
     */
    public static function saveBatches($detailClassId, $detailRecId, $batchesArr, $sync = false)
    {
        if (!is_array($batchesArr)) {
            
            return;
        }
        $recInfo = cls::get($detailClassId)->getRowInfo($detailRecId);
        $recInfo->detailClassId = cls::get($detailClassId)->getClassId();
        $recInfo->detailRecId = $detailRecId;

        // Подготвяне на редовете за обновяване
        $update = array();
        foreach ($batchesArr as $b => $q) {
            foreach ($recInfo->operation as $operation => $storeId) {
                $obj = clone $recInfo;
                $obj->operation = $operation;
                $obj->storeId = $storeId;
                $obj->quantity = $q;
                $obj->batch = $b;

                $b1 = ($sync === true) ? null : $obj->batch;
                if ($id = self::getId($obj->detailClassId, $obj->detailRecId, $obj->productId, $b1, $operation)) {
                    $obj->id = $id;
                }
                
                $update[] = $obj;
            }
        }

        // Запис
        if (countR($update)) {
            cls::get(get_called_class())->saveArray($update);
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->FLD('document', 'varchar(128)', 'silent,caption=Документ,placeholder=Хендлър');
        $data->listFilter->showFields = 'document';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        $data->query->orderBy('id', 'DESC');
        
        if ($fRec = $data->listFilter->rec) {
            if (isset($fRec->document)) {
                $document = doc_Containers::getDocumentByHandle($fRec->document);
                if (is_object($document)) {
                    $data->query->where("#containerId = {$document->fetchField('containerId')}");
                }
            }
        }
    }
    
    
    /**
     * Помощна ф-я за показване на партидите във фактура
     *
     * @param int    $productId
     * @param string $batches
     *
     * @return NULL|string
     */
    public static function displayBatchesForInvoice($productId, $batches)
    {
        $batches = explode(',', $batches);
        if (!countR($batches)) {
            
            return;
        }
        $res = array();
        
        foreach ($batches as $b) {
            $batch = batch_Defs::getBatchArray($productId, $b);
            if (countR($batch)) {
                foreach ($batch as $k => &$b) {
                    if (!Mode::isReadOnly() && haveRole('powerUser')) {
                        if (!haveRole('batch,ceo')) {
                            Request::setProtected('batch');
                        }
                        $b = ht::createLink($b, array('batch_Movements', 'list', 'batch' => $k, 'productId' => $productId));
                        $b = $b->getContent();
                    }
                    
                    $res[] = $b;
                }
            }
        }
        
        $res = implode('<br>', $res);
        
        return $res;
    }
    
    
    /**
     * Връща използваните партиди филтрирани по клас и вид
     *
     * @param mixed $class
     * @param array $fields
     * @param int|null $templateId
     *
     * @return array
     */
    public static function getBatchByType($class, $fields = array(), $templateId = null)
    {
        $Class = cls::get($class);
        $tQuery = batch_Templates::getQuery();
        $tQuery->where('#driverClass = ' . $Class->getClassId());
        if(isset($templateId)){
            $tQuery->where("#id = '{$templateId}'");
        }
        $tQuery->show('id');
        $templates = arr::extractValuesFromArray($tQuery->fetchAll(), 'id');
        if (!countR($templates)) {
            
            return array();
        }
        
        $bQuery = batch_BatchesInDocuments::getQuery();
        $bQuery->EXT('templateId', 'batch_Defs', 'externalName=templateId,remoteKey=productId,externalFieldName=productId');
        $bQuery->in('templateId', $templates);
        $fields = arr::make($fields, true);
        if (countR($fields)) {
            $bQuery->show($fields);
        }
        $res = $bQuery->fetchAll();
        
        return $res;
    }
}
