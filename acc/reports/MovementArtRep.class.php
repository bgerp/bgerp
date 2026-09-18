<?php


/**
 * Мениджър на отчети за продукти по групи
 *
 *
 *
 * @category  extrapack
 * @package   acc
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Счетоводство » Движения на материали
 */
class acc_reports_MovementArtRep extends frame2_driver_TableData
{
    /**
     * До колко пера журналът се филтрира по артикулите от справката
     */
    const MAX_ITEMS_IN_JOURNAL_FILTER = 10000;


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, acc, repAll, repAllGlobal';

         /**
      * Кои полета от таблицата в справката да се сумират в обобщаващия ред
      *
      * @var int
      */
     protected $summaryListFields = 'baseQuantity,delivered,produced,converted,sold,blQuantity';


    /**
     * Кои полета от таблицата да могат да се сортират
     *
     * @var string
     */
    protected $sortableListFields = 'code,productId,baseQuantity,delivered,produced,converted,sold,blQuantity,singleWeight';


    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО';


    /**
     * Кои полета са за избор на период
     */
    protected $periodFields = 'from,to';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('from', 'date', 'caption=От,after=title');
        $fieldset->FLD('to', 'date', 'caption=До,after=from');
        $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Група,placeholderType=all,after=to,single=none');
        $fieldset->FLD('uomKg', 'enum(base=Основна, weight=Тегловна)', 'notNull,caption=Мярка,maxRadio=2,after=group,single=none');

    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $form->setDefault('uomKg', 'base');

        /*$form = &$data->form;
        $periods = acc_Periods::getCalcedPeriods(true);
        $form->setOptions('from', array('' => '') + $periods);
        $form->setOptions('to', array('' => '') + $periods);
        
        $lastPeriod = acc_Periods::fetchByDate(dt::addMonths(-1, dt::now()));
        $form->setDefault('from', $lastPeriod->id);*/
    }


    /**
     * След изпращане на формата
     *
     * @param frame2_driver_Proto $Driver $Driver
     * @param embed_Manager $Embedder
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        $rec = &$form->rec;

        if ($form->isSubmitted()) {

            // Проверка има ли избрани вложени групи
            if (cat_Groups::checkForNestedGroups($rec->group ?? null)) {
                $form->setError('group', 'Избрани са вложени групи');
            }

            // Размяна, ако периодите са объркани
            /*if (isset($rec->from, $rec->to)) {
                $from = acc_Periods::fetch($rec->from);
                $to = acc_Periods::fetch($rec->to);
                
                if ($from->start > $to->start) {
                    $rec->from = $to->id;
                    $rec->to = $from->id;
                }
            }
            bp($rec->from, $rec->to, $rec);
            if (empty($rec->to)) {
                $currentPeriod = acc_Periods::fetchByDate(dt::today());
                $rec->to = $currentPeriod->id;
            }*/
        }
    }


    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $rec->from = $rec->from ?? date('Y-m-01');
        $rec->to = $rec->to ?? dt::today();
        $rec->group = $rec->group ?? null;
        $rec->uomKg = $rec->uomKg ?? 'base';

        // Обръщаме се към продуктите и търсим всички складируеми и неоттеглени продукти
        $query = cat_Products::getQuery();
        $query->where("#state = 'active' OR #state = 'closed'");
        $query->where("#canStore = 'yes'");
        $query->show('id,measureId,code,groups');

        if (isset($rec->group)) {
            plg_ExpandInput::applyExtendedInputSearch('cat_Products', $query, $rec->group, 'productId');
        }

        $productArr = $query->fetchAll();

        $maxTimeLimit = 15 * countR($productArr);
        $maxTimeLimit = max(array($maxTimeLimit, 300));

        // задаваме лимит пропорционален на бр. извадени продукти
        core_App::setTimeLimit($maxTimeLimit);

        // Извличат се перата на артикулите
        $productItems = array();
        $productClassId = cat_Products::getClassId();
        $iQuery = acc_Items::getQuery();
        $iQuery->where("#classId = {$productClassId}");
        $iQuery->in('objectId', array_keys($productArr));
        $iQuery->show('id,objectId');
        while ($iRec = $iQuery->fetch()) {
            $productItems[$iRec->objectId] = $iRec->id;
        }

        // Начални количества във всички складове, групирани по артикули
        $baseQuantities = $this->getBaseQuantities($rec, array_flip($productItems));
        $this->logWhilePreparing('Артикули: ' . countR($productArr) . ', с начално салдо: ' . countR($baseQuantities));

        // Движенията в периода, сумирани по перо с едно четене на журнала
        $movements = $this->aggregateMovements($rec->from, $rec->to, array_values($productItems));

        // за всеки един продукт, се изчисляват търсените количества
        $recs = array();
        foreach ($productArr as $productRec) {
            if (empty($productItems[$productRec->id])) continue;

            $itemId = $productItems[$productRec->id];
            $baseQuantity = 0;
            if (isset($baseQuantities[$productRec->id])) {
                $baseQuantity = $baseQuantities[$productRec->id];
            }

            $obj = (object) array('baseQuantity' => $baseQuantity,
                                  'delivered' => self::getSum($movements['delivered'], $itemId),
                                  'converted' => self::getSum($movements['converted'], $itemId),
                                  'produced' => self::getSum($movements['produced'], $itemId),
                                  'sold' => self::getSum($movements['sold'], $itemId),
                                  'blQuantity' => $baseQuantity + self::getSum($movements['blQuantity'], $itemId));

            $obj->code = (!empty($productRec->code)) ? $productRec->code : "Art{$productRec->id}";
            $obj->measureId = $productRec->measureId;
            $obj->productId = $productRec->id;
            $obj->groups = $productRec->groups;

            $recs[$productRec->id] = $obj;
        }

        $this->logWhilePreparing('Редове в справката: ' . countR($recs));

        //Ако е избрано справката да е само в кегловни мерки
        if ($rec->uomKg == 'weight') {
            $recs = self::changeUomToKg($recs);
        }

        $data->groupByField = 'groupId';
        $recs = $this->groupRecs($recs, $rec->group, $data);

        return $recs;
    }


    /**
     * Началните количества по артикули към началото на периода
     *
     * @param stdClass $rec
     * @param array $productItemsFlip - ид на перо => ид на артикул
     *
     * @return array - ид на артикул => количество
     */
    private function getBaseQuantities($rec, $productItemsFlip)
    {
        $res = array();
        $Balance = new acc_ActiveShortBalance(array('from' => $rec->from, 'to' => $rec->to, 'accs' => '321', 'cacheBalance' => false, 'keepUnique' => true));

        // Ползва се само началното салдо, движенията в периода се сумират отделно
        $accArr = array();
        $balanceRecs = $Balance->getBalanceBefore('321', $accArr);
        if (!is_array($balanceRecs)) return $res;

        foreach ($balanceRecs as $bRec) {

            // Записите от кореспондиращите сметки не участват в началните количества
            if (countR($accArr) && !in_array($bRec['accountId'], $accArr)) continue;
            if (empty($bRec['ent2Id'])) continue;
            if (empty($productItemsFlip[$bRec['ent2Id']])) continue;

            $productId = $productItemsFlip[$bRec['ent2Id']];
            self::addToSum($res, $productId, $bRec['baseQuantity']);
        }

        return $res;
    }


    /**
     * Сумира движенията по с/ка 321 в периода по перото на артикула, с едно четене на журнала
     *
     * @param string $from
     * @param string $to
     * @param array  $productItemIds - перата на артикулите в справката
     *
     * @return array - вид движение => (ид на перо => количество)
     */
    private function aggregateMovements($from, $to, $productItemIds)
    {
        $res = array('delivered' => array(), 'produced' => array(), 'converted' => array(), 'sold' => array(), 'blQuantity' => array());
        if (!countR($productItemIds)) {

            return $res;
        }

        $acc = array();
        foreach (array('321', '401', '799', '61101', '61102', '61103', '699', '701', '706') as $sysId) {
            $acc[$sysId] = acc_Accounts::getRecBySystemId($sysId)->id;
        }

        $productionTypeId = planning_DirectProductionNote::getClassId();
        $consumptionTypeId = planning_ConsumptionNotes::getClassId();
        $inventoryTypeId = store_InventoryNotes::getClassId();

        $jQuery = acc_JournalDetails::getQuery();
        acc_JournalDetails::filterQuery($jQuery, $from, $to);
        $jQuery->show('debitAccId,debitItem1,debitItem2,debitItem3,debitQuantity,creditAccId,creditItem1,creditItem2,creditItem3,creditQuantity,docType');

        // Четенето тръгва по сметката, а не по перото - при дълъг списък пера оптимизаторът
        // иначе минава по индекса на перото и заявката се разпада на хиляди обхождания
        $jQuery->useIndex('debit_acc_id');
        $jQuery->useIndex('credit_acc_id');

        // Само перата от справката - записите с други артикули не влизат в никоя сума
        $debitFilter = $creditFilter = '';
        if (countR($productItemIds) <= self::MAX_ITEMS_IN_JOURNAL_FILTER) {
            $itemsIn = implode(',', array_map('intval', $productItemIds));
            $debitFilter = " AND #debitItem2 IN ({$itemsIn})";
            $creditFilter = " AND #creditItem2 IN ({$itemsIn})";
        }

        // Двата клона не се застъпват, за да не се броят по два пъти записите с 321 от двете страни
        $jQuery->setUnion("#debitAccId = {$acc['321']}{$debitFilter}");
        $jQuery->setUnion("#creditAccId = {$acc['321']} AND (#debitAccId IS NULL OR #debitAccId != {$acc['321']}){$creditFilter}");
        $jQuery->useUnionAll = true;

        $jQuery->selectOnProxy();
        $this->logWhilePreparing('Записи от журнала: ' . $jQuery->numRec());

        while ($jRec = $jQuery->fetch()) {
            $debitAccId = $jRec->debitAccId;
            $creditAccId = $jRec->creditAccId;

            $isProduction = ($jRec->docType == $productionTypeId);
            $isConsumption = $isProduction || $jRec->docType == $consumptionTypeId || $jRec->docType == $inventoryTypeId;

            // Перото, по което getBlQuantities() индексира резултата
            $debitItem = null;
            if (isset($jRec->debitItem3)) {
                $debitItem = $jRec->debitItem3;
            } elseif (isset($jRec->debitItem2)) {
                $debitItem = $jRec->debitItem2;
            } elseif (isset($jRec->debitItem1)) {
                $debitItem = $jRec->debitItem1;
            }

            $creditItem = null;
            if (isset($jRec->creditItem3)) {
                $creditItem = $jRec->creditItem3;
            } elseif (isset($jRec->creditItem2)) {
                $creditItem = $jRec->creditItem2;
            } elseif (isset($jRec->creditItem1)) {
                $creditItem = $jRec->creditItem1;
            }

            $isStoreDebit = ($debitAccId == $acc['321']);
            $isStoreCredit = ($creditAccId == $acc['321']);

            // Крайно количество - getBlQuantities() филтрира по дебитното перо, ако 321 е дебитна
            if ($isStoreDebit || $isStoreCredit) {
                $filterItem = $isStoreDebit ? $jRec->debitItem2 : $jRec->creditItem2;

                if (!empty($filterItem)) {
                    if ($isStoreDebit && $debitItem == $filterItem) {
                        self::addToSum($res['blQuantity'], $filterItem, $jRec->debitQuantity);
                    }

                    if ($isStoreCredit && $creditItem == $filterItem) {
                        self::addToSum($res['blQuantity'], $filterItem, -1 * $jRec->creditQuantity);
                    }
                }
            }

            // При с/ка 321 артикулът е перо на втора позиция
            if ($isStoreDebit && !empty($debitItem) && $debitItem == $jRec->debitItem2) {

                // Доставено: Влязло в склада от доставчици
                if ($creditAccId == $acc['401']) {
                    self::addToSum($res['delivered'], $debitItem, $jRec->debitQuantity);
                }

                // Произведено с протокол за производство
                if ($isProduction && ($creditAccId == $acc['61101'] || $creditAccId == $acc['61102'] || $creditAccId == $acc['61103'])) {
                    self::addToSum($res['produced'], $debitItem, $jRec->debitQuantity);
                }

                // Приспадане на вложеното с върнатото от производството
                if (!$isProduction && ($creditAccId == $acc['61101'] || $creditAccId == $acc['61102'])) {
                    self::addToSum($res['converted'], $debitItem, -1 * $jRec->debitQuantity);
                }
            }

            if ($isStoreCredit && !empty($creditItem) && $creditItem == $jRec->creditItem2) {

                // Доставено влязло в склада от инвентаризация
                if ($debitAccId == $acc['799']) {
                    self::addToSum($res['delivered'], $creditItem, -1 * $jRec->creditQuantity);
                }

                // Вложено бездетайлно, в протокола за производство и от инвентаризация
                if ($isConsumption && ($debitAccId == $acc['61102'] || $debitAccId == $acc['61103'] || $debitAccId == $acc['699'])) {
                    self::addToSum($res['converted'], $creditItem, $jRec->creditQuantity);
                }
            }

            // Вложено детайлно - при с/ка 61101 артикулът е перо на първа позиция
            if ($isConsumption && $isStoreCredit && $debitAccId == $acc['61101'] && !empty($debitItem) && $debitItem == $jRec->debitItem1) {
                self::addToSum($res['converted'], $debitItem, $jRec->debitQuantity);
            }

            // Продадено - при сметките за приходи артикулът е перо на трета позиция
            if ($isStoreCredit && ($debitAccId == $acc['701'] || $debitAccId == $acc['706']) && !empty($debitItem) && $debitItem == $jRec->debitItem3) {
                self::addToSum($res['sold'], $debitItem, $jRec->debitQuantity);
            }
        }

        return $res;
    }


    /**
     * Натрупва количество към сумата за даден ключ
     *
     * @param array $arr
     * @param int $key
     * @param float $quantity
     *
     * @return void
     */
    private static function addToSum(&$arr, $key, $quantity)
    {
        if (!isset($arr[$key])) {
            $arr[$key] = 0;
        }

        $arr[$key] += $quantity;
    }


    /**
     * Сумата за даден ключ, или 0 ако няма движение
     *
     * @param array $arr
     * @param int $key
     *
     * @return float
     */
    private static function getSum($arr, $key)
    {
        if (!isset($arr[$key])) {

            return 0;
        }

        return $arr[$key];
    }


    /**
     * Групиране по продуктови групи
     *
     * @param array $recs
     * @param string $group
     * @param stdClass $data
     *
     * @return array
     */
    private function groupRecs($recs, $group, $data)
    {
        $ordered = array();

        $groups = keylist::toArray($group);
        if (!countR($groups)) {
            $groups = array('total' => 'Общо');
        } else {
            cls::get('cat_Groups')->invoke('AfterMakeArray4Select', array(&$groups));
        }

        $data->totals = array();

        // За всеки маркер
        foreach ($groups as $grId => $groupName) {

            // Отделяме тези записи, които съдържат текущия маркер
            $res = array_filter($recs, function ($e) use ($grId, $groupName, &$data) {
                if (keylist::isIn($grId, $e->groups ?? null) || $grId === 'total') {
                    $e->groupId = $grId;
                    if (!isset($data->totals[$e->groupId])) {
                        $data->totals[$e->groupId] = array(
                            'baseQuantity' => 0,
                            'blQuantity' => 0,
                            'delivered' => 0,
                            'produced' => 0,
                            'converted' => 0,
                            'sold' => 0,
                        );
                    }
                    $data->totals[$e->groupId]['baseQuantity'] += $e->baseQuantity;
                    $data->totals[$e->groupId]['blQuantity'] += $e->blQuantity;
                    $data->totals[$e->groupId]['delivered'] += $e->delivered;
                    $data->totals[$e->groupId]['produced'] += $e->produced;
                    $data->totals[$e->groupId]['converted'] += $e->converted;
                    $data->totals[$e->groupId]['sold'] += $e->sold;

                    return true;
                }

                return false;
            });

            if (countR($res)) {
                arr::sortObjects($res, 'code', 'asc', 'stri');
                $ordered += $res;
            }
        }

        return $ordered;
    }


    /**
     * Подготовка на реда за групиране
     *
     * @param int $columnsCount - брой колони
     * @param string $groupValue - невербалното име на групата
     * @param string $groupVerbal - вербалното име на групата
     * @param stdClass $data - датата
     *
     * @return string - съдържанието на групиращия ред
     */
    protected function getGroupedTr($columnsCount, $groupValue, $groupVerbal, &$data)
    {
        $baseQuantity = $blQuantity = $delivered = $produced = $converted = $sold = '';
        foreach (array('baseQuantity', 'blQuantity', 'delivered', 'produced', 'converted', 'sold') as $totalFld) {
            $totalValue = $data->totals[$groupValue][$totalFld] ?? 0;
            ${$totalFld} = core_Type::getByName('double(decimals=2)')->toVerbal($totalValue);
            if ($totalValue < 0) {
                ${$totalFld} = "<span class='red'>{${$totalFld}}</span>";
            }
        }

        $groupVerbal = "<td style='padding-top:9px;padding-left:5px;' colspan='3'><b>" . $groupVerbal . "</b></td><td style='text-align:right'><b>{$baseQuantity}</b></td><td style='text-align:right'><b>{$delivered}</b></td><td style='text-align:right'><b>{$produced}</b></td><td style='text-align:right'><b>{$converted}</b></td><td style='text-align:right'><b>{$sold}</b></td><td style='text-align:right'><b>{$blQuantity}</b></td>";

        return $groupVerbal;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec - записа
     * @param bool $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');

        $fld->FLD('code', 'varchar', 'caption=Код,tdClass=nowrap');
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $fld->FLD('measureId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=nowrap');
        $fld->FLD('baseQuantity', 'double(smartRound,decimals=2)', 'caption=Количество->Начално');
        $fld->FLD('delivered', 'double(smartRound,decimals=2)', 'caption=Количество->Доставено');
        $fld->FLD('produced', 'double(smartRound,decimals=2)', 'caption=Количество->Произведено');
        $fld->FLD('converted', 'double(smartRound,decimals=2)', 'caption=Количество->Вложено');
        $fld->FLD('sold', 'double(smartRound,decimals=2)', 'caption=Количество->Продадено');
        $fld->FLD('blQuantity', 'double(smartRound,decimals=2)', 'caption=Количество->Крайно');

        if(haveRole('debug') && ($rec->uomKg ?? 'base') == 'weight'){
            $fld->FLD('singleWeight', 'double(decimals=3)', 'caption=Ед.тегло');
        }

        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec - записа
     * @param stdClass $dRec - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $row = new stdClass();

        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $groArr = array();

        $row->code = $dRec->code;
        $row->productId = cat_Products::getVerbal($dRec->productId, 'name');

        $link = cat_Products::getSingleUrlArray($dRec->productId);
        $row->productId = ht::createLinkRef($row->productId, $link);

        $row->measureId = cat_UoM::getShortName($dRec->measureId);
        $row->groupId = ($dRec->groupId !== 'total') ? cat_Groups::getVerbal($dRec->groupId, 'name') : tr('Общо');

        foreach (array('baseQuantity', 'delivered', 'produced', 'converted', 'sold', 'blQuantity') as $fld) {
            $value = $dRec->{$fld} ?? 0;
            $row->{$fld} = $Double->toVerbal($value);
            if ($value < 0) {
                $row->{$fld} = "<span class='red'>{$row->{$fld}}</span>";
            } elseif ($value == 0) {
                $row->{$fld} = "<span class='quiet'>{$row->{$fld}}</span>";
            }
        }
        $row->singleWeight = $dRec->singleWeight ?? null;
        return $row;
    }


    /**
     * След вербализирането на данните
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $row
     * @param stdClass $rec
     * @param array $fields
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        // Показване на избраните групи
        if (!empty($rec->group)) {
            $groupLinks = cat_Groups::getLinks($rec->group);
            $row->group = implode(' ', $groupLinks);
        }
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN group--><div>|Групи|*: [#group#]</div><!--ET_END group-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));

        if (isset($data->rec->from)) {
            $fieldTpl->append($data->row->from, 'from');
        }

        if (isset($data->rec->to)) {
            $fieldTpl->append($data->row->to, 'to');
        }

        if (isset($data->rec->group)) {
            $fieldTpl->append($data->row->group, 'group');
        }

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }


    /**
     * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
     *
     * @param stdClass $rec
     *
     * @return bool $res
     */
    public function canSendNotificationOnRefresh($rec)
    {
        return false;
    }

    public function changeUomToKg($recs)
    {
        core_Debug::startTimer('CHANGE_UOM_TO_KG');

        $kgMeasure = cat_UoM::fetchBySysId('kg');
        $kgMeasureId = $kgMeasure->id ?? null;
        if (!$kgMeasureId) {
            core_Debug::stopTimer('CHANGE_UOM_TO_KG');

            return $recs;
        }

        // Тегловни са всички сродни на килограма мерки, включително оттеглените
        $weightMeasures = cat_UoM::getSameTypeMeasures($kgMeasureId, false, false);
        unset($weightMeasures['']);

        // Данните за артикулите се четат наведнъж, вместо с отделни заявки за всеки ред
        $productIds = array();
        foreach ($recs as $val) {
            if (!isset($weightMeasures[$val->measureId])) {
                $productIds[$val->productId] = $val->productId;
            }
        }
        self::preloadProductData($productIds);
        $secondMeasures = self::getSecondMeasuresInKg($productIds);

        // Изключва преизчисляването на параметрите - иначе драйверът ги преизчислява и записва
        Mode::push('doNotCalculate', true);

        $res = $recs;
        try {
            foreach ($recs as $key => $val) {

                if (!isset($weightMeasures[$val->measureId])) {

                    //Взема единичното тегло на целия продукт
                    $singleProductWeight = null;
                    $singleProductWeight = cat_Products::getParams($val->productId, 'weight');

                    if ($singleProductWeight) {
                        $singleProductWeight = $singleProductWeight / 1000;
                    } else {
                        $singleProductWeight = cat_Products::getParams($val->productId, 'weightKg');
                    }

                    //Ако няма въведени параметри за единично тегло взема втората мярка, ако е кг.
                    if (!$singleProductWeight) {
                        if (isset($secondMeasures[$val->productId])) {
                            $singleProductWeight = $secondMeasures[$val->productId];
                        }
                    }
                    if ($singleProductWeight) {
                        $res[$key]->baseQuantity = $val->baseQuantity * $singleProductWeight;
                        $res[$key]->delivered = $val->delivered * $singleProductWeight;
                        $res[$key]->converted = $val->converted * $singleProductWeight;
                        $res[$key]->produced = $val->produced * $singleProductWeight;
                        $res[$key]->sold = $val->sold * $singleProductWeight;
                        $res[$key]->blQuantity = $val->blQuantity * $singleProductWeight;
                        $res[$key]->measureId = $kgMeasureId;
                        $res[$key]->singleWeight = $singleProductWeight;

                    } else {
                        $res[$key]->baseQuantity = 0;
                        $res[$key]->delivered = 0;
                        $res[$key]->converted = 0;
                        $res[$key]->produced = 0;
                        $res[$key]->sold = 0;
                        $res[$key]->blQuantity = 0;
                        $res[$key]->measureId = $kgMeasureId;
                        $res[$key]->singleWeight = $singleProductWeight;
                    }
                }
            }
        } finally {
            Mode::pop('doNotCalculate');
        }

        core_Debug::stopTimer('CHANGE_UOM_TO_KG');

        return $res;
    }


    /**
     * Зарежда наведнъж данните, които иначе се четат за всеки артикул поотделно
     *
     * @param array $productIds - ид-та на артикулите
     *
     * @return void
     *
     * @author Ivelin Dimov <ivelin_pdimov@abv.bg>
     */
    private static function preloadProductData($productIds)
    {
        if (!countR($productIds)) return;

        // Записите на артикулите - иначе cat_Products::getParams() чете всеки артикул поотделно
        $Products = cls::get('cat_Products');
        $pQuery = $Products->getQuery();
        $pQuery->in('id', $productIds);
        while ($pRec = $pQuery->fetch()) {
            $Products->_cachedRecords[$pRec->id . '|*'] = $pRec;
        }

        $classId = cat_Products::getClassId();
        $paramIds = array();
        foreach (array('weight', 'weightKg') as $sysId) {
            if ($paramId = cat_Params::fetchIdBySysId($sysId)) {
                $paramIds[$paramId] = $paramId;
            }
        }

        if (!countR($paramIds)) return;

        $values = array();
        $Params = cls::get('cat_products_Params');
        $vQuery = $Params->getQuery();
        $vQuery->in('productId', $productIds);
        $vQuery->in('paramId', $paramIds);
        $vQuery->where("#classId = {$classId}");
        $vQuery->show('productId,paramId,paramValue');
        while ($vRec = $vQuery->fetch()) {
            $values[$vRec->productId][$vRec->paramId] = $vRec;
        }

        // Ключът е същият, който ползва cat_products_Params::fetchParamValue(), за да минат
        // през кеша и заявките на драйвъра, без да се заобикаля самият драйвър
        foreach ($productIds as $productId) {
            foreach ($paramIds as $paramId) {
                $cacheKey = "#productId = {$productId} AND #paramId = {$paramId} AND #classId = {$classId}|paramValue";
                $Params->_cachedRecords[$cacheKey] = $values[$productId][$paramId] ?? false;
            }
        }
    }


    /**
     * Единичните тегла от втора мярка в кг, по артикули
     *
     * @param array $productIds - ид-та на артикулите
     *
     * @return array - ид на артикул => единично тегло
     *
     * @author Ivelin Dimov <ivelin_pdimov@abv.bg>
     */
    private static function getSecondMeasuresInKg($productIds)
    {
        $res = array();
        if (!countR($productIds)) return $res;

        $pQuery = cat_products_Packagings::getQuery();
        $pQuery->in('productId', $productIds);
        $pQuery->where("#isSecondMeasure = 'yes'");
        $pQuery->show('productId,quantity');
        $pQuery->orderBy('#id', 'ASC');
        while ($pRec = $pQuery->fetch()) {
            if (!empty($pRec->quantity)) {
                $res[$pRec->productId] = 1 / $pRec->quantity;
            }
        }

        return $res;
    }


    /**
     * Връща периода на справката - ако има такъв
     *
     * @param stdClass $rec
     * @return array
     *          ['from'] - начало на период
     *          ['to']   - край на период
     */
    protected function getPeriodRange($rec)
    {
        $from = $rec->from ?? null;
        if(is_numeric($from)){
            $fromPeriod = acc_Periods::fetch($from);
            $from = $fromPeriod->start ?? null;
        }

        $to = $rec->to ?? null;
        if(is_numeric($to)){
            $toPeriod = acc_Periods::fetch($to);
            $to = isset($toPeriod->start) ? dt::getLastDayOfMonth($toPeriod->start) : null;
        }

        return array('from' => $from, 'to' => $to);
    }
}
