<?php


/**
 * Мениджър на отчети за продукти по групи
 *
 *
 *
 * @category  extrapack
 * @package   acc
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Счетоводство » Движения на материали
 */
class acc_reports_MovementArtRep extends frame2_driver_TableData
{
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
        $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Група,after=to,single=none');
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
            if (cat_Groups::checkForNestedGroups($rec->group)) {
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
        $recs = array();
        $itemAll = array();

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

        // id-to на класа на продуктите
        $productClassId = cat_Products::getClassId();

        // Извличат се всички пера
        $iQuery = acc_Items::getQuery();
        $iQuery->where("#classId = {$productClassId}");
        $iQuery->in('objectId', array_keys($productArr));
        $iQuery->show('id,objectId');
        while ($iRec = $iQuery->fetch()) {
            $itemAll[$iRec->objectId] = $iRec->id;
        }

        $productItemsFlip = array_flip($itemAll);
        $productItems = $itemAll;

        // Начално количество
        $baseQuantities = array();

        // Намира се баланса на началния период
        $periodRec = acc_Periods::fetchByDate($rec->from);
        $balanceId = acc_Balances::fetchField("#periodId = {$periodRec->id}", 'id');

        // Извличат се само записите за сметка 321 с участието на перата на артикулите
        $Balance = new acc_ActiveShortBalance(array('from' => $rec->from, 'to' => $rec->to, 'accs' => '321', 'cacheBalance' => false, 'keepUnique' => true));
        $balanceRec = $Balance->getBalance('321');

        // От баланса извличаме всички начални количества във всички складове, групирани по артикули
        foreach ($balanceRec as $bRec) {
            $productId = $productItemsFlip[$bRec->ent2Id];

            if (!array_key_exists($productId, $baseQuantities)) {
                $baseQuantities[$productId] = $bRec->baseQuantity;
            } else {
                $baseQuantities[$productId] += $bRec->baseQuantity;
            }
        }

        // Извличане на записите от журнала по желаните сметки
        $jQuery = acc_JournalDetails::getQuery();

        $from = $rec->from;
        $to = $rec->to;

        acc_JournalDetails::filterQuery($jQuery, $from, $to, '321');
        //acc_JournalDetails::filterQuery($jQuery, $from, $to, '321,401,61101,61102,61103,701,706,799');
        $jRecs = $jQuery->fetchAll();


        //Производство
        $id2 = planning_DirectProductionNote::getClassid();
        $jQuery2 = clone $jQuery;
        $jRecs2 = $jQuery2->where("#docType = {$id2}");
        $jRecs2 = $jQuery2->fetchAll();

        //връщане
        $id1 = planning_ConsumptionNotes::getClassid();
        $jQuery4 = clone $jQuery;
        $jRecs4 = $jQuery4->where("#docType = {$id1}");
        $jRecs4 = $jQuery4->fetchAll();

        // инвентаризация
        $id3 = store_InventoryNotes::getClassid();
        $jQuery6 = clone $jQuery; //bp($jRecs6,$id3);
        $jRecs6 = $jQuery6->where("#docType = {$id3}");
        $jRecs6 = $jQuery6->fetchAll();


        $jRecs3 = array_diff_key($jRecs, $jRecs2);

        $jRecs5 = array_merge($jRecs2, $jRecs4, $jRecs6);

        $recs = array();

        log_System::add(get_called_class(), 'jRecsCnt: ' . countR($jRecs) . ', producsCnt: ' . countR($productArr), null, 'debug', 1);
        log_System::add(get_called_class(), 'jRecsCnt: ' . countR($jRecs2) . ', producsCnt: ' . countR($productArr), null, 'debug', 1);

        // за всеки един продукт, се изчисляват търсените количества
        foreach ($productArr as $productRec) {
            if ($itemId = $productItems[$productRec->id]) {
                $baseQuantity = (isset($baseQuantities[$productRec->id])) ? $baseQuantities[$productRec->id] : 0;
                $obj = (object)array('baseQuantity' => $baseQuantity, 'delivered' => 0, 'converted' => 0, 'produced' => 0, 'sold' => 0, 'blQuantity' => 0);
                $obj->code = (!empty($productRec->code)) ? $productRec->code : "Art{$productRec->id}";
                $obj->measureId = $productRec->measureId;
                $obj->productId = $productRec->id;
                $obj->groups = $productRec->groups;

                // Доставено: Влязло в склада от доставчици
                if ($delRes = acc_Balances::getBlQuantities($jRecs, '321', 'debit', '401', array(null, $itemId, null))) {
                    $obj->delivered = $delRes[$itemId]->quantity;
                }

                // Доставено влязло в склада от инвентаризация
                if ($delRes1 = acc_Balances::getBlQuantities($jRecs, '321', 'credit', '799', array(null, $itemId, null))) {
                    $obj->delivered -= $delRes1[$itemId]->quantity;
                }

                // Вложено детайлно
                if ($convRes = acc_Balances::getBlQuantities($jRecs5, '61101', 'debit', '321', array($itemId, null, null))) {
                    $obj->converted = $convRes[$itemId]->quantity;
                }

//                 // Вложено в протокола за производство - мисля, че е излишно
//                 if ($convRes1 = acc_Balances::getBlQuantities($jRecs5, '61103', 'debit', '321', array($itemId, null, null))) {
//                     $obj->converted += $convRes1[$itemId]->quantity;
//                 }
                // Вложено бездетайлно
                if ($convRes2 = acc_Balances::getBlQuantities($jRecs5, '321', 'credit', '61102', array(null, $itemId, null))) {
                    $obj->converted += $convRes2[$itemId]->quantity;
                }

                // Вложено в протокола за производство
                if ($convRes3 = acc_Balances::getBlQuantities($jRecs5, '321', 'credit', '61103', array(null, $itemId, null))) {
                    $obj->converted += $convRes3[$itemId]->quantity;
                }
                // Вложено от инвентаризация
                if ($convRes4 = acc_Balances::getBlQuantities($jRecs5, '321', 'credit', '699', array(null, $itemId, null))) {
                    $obj->converted += $convRes4[$itemId]->quantity;
                }

                // Приспадане на вложеното с върнатото от производството детайлно
                if ($convRes5 = acc_Balances::getBlQuantities($jRecs3, '321', 'debit', '61101', array(null, $itemId, null))) {
                    $obj->converted -= $convRes5[$itemId]->quantity;
                }

                // Приспадане на вложеното с върнатото от производството бездетайлно
                if ($convRes6 = acc_Balances::getBlQuantities($jRecs3, '321', 'debit', '61102', array(null, $itemId, null))) {
                    $obj->converted -= $convRes6[$itemId]->quantity;
                }

                // Произведено от протокол за производство (Незавършено производство)
                if ($prodRes1 = acc_Balances::getBlQuantities($jRecs2, '321', 'debit', '61101', array(null, $itemId, null))) {
                    $obj->produced += $prodRes1[$itemId]->quantity;
                }

                // Произведено от протокол за производство (Бездетайлно произвеждане)
                if ($prodRes2 = acc_Balances::getBlQuantities($jRecs2, '321', 'debit', '61102', array(null, $itemId, null))) {
                    $obj->produced += $prodRes2[$itemId]->quantity;
                }

                // Произведено от протокол за производство (Влагане на материал в производството от склад)
                if ($prodRes3 = acc_Balances::getBlQuantities($jRecs2, '321', 'debit', '61103', array(null, $itemId, null))) {
                    $obj->produced += $prodRes3[$itemId]->quantity;
                }

                // Продадено
                if ($soldRes = acc_Balances::getBlQuantities($jRecs, '701', 'debit', '321', array(null, null, $itemId))) {
                    $obj->sold = $soldRes[$itemId]->quantity;
                }

                // Продадено
                if ($soldRes2 = acc_Balances::getBlQuantities($jRecs, '706', 'debit', '321', array(null, null, $itemId))) {
                    $obj->sold += $soldRes2[$itemId]->quantity;
                }

                // Крайно количество
                $obj->blQuantity = $baseQuantity;
                if ($blRes = acc_Balances::getBlQuantities($jRecs, '321', null, null, array(null, $itemId, null))) {
                    $obj->blQuantity += $blRes[$itemId]->quantity;
                }

                $recs[$productRec->id] = $obj;
            }
        }

        //Ако е избрано справката да е само в кегловни мерки
        if ($rec->uomKg == 'weight') {
            $recs = self::changeUomToKg($recs);
        }

        $data->groupByField = 'groupId';
        $recs = $this->groupRecs($recs, $rec->group, $data);

        return $recs;
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
            $res = array_filter($recs, function (&$e) use ($grId, $groupName, &$data) {
                if (keylist::isIn($grId, $e->groups) || $grId === 'total') {
                    $e->groupId = $grId;
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
            ${$totalFld} = core_Type::getByName('double(decimals=2)')->toVerbal($data->totals[$groupValue][$totalFld]);
            if ($data->totals[$groupValue][$totalFld] < 0) {
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

        if(haveRole('debug') && $rec->uomKg == 'weight'){
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
            $row->{$fld} = $Double->toVerbal($dRec->{$fld});
            if ($dRec->{$fld} < 0) {
                $row->{$fld} = "<span class='red'>{$row->{$fld}}</span>";
            } elseif ($dRec->{$fld} == 0) {
                $row->{$fld} = "<span class='quiet'>{$row->{$fld}}</span>";
            }
        }
        $row->singleWeight =$dRec->singleWeight;
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
     * @param cat_ProductDriver $Driver
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

        $res = $weightMeasuresId = array();
        $query = cat_UoM::getQuery();
        $query->in('name', array('килограм', 'тон', 'грам'));
        while ($qRec = $query->fetch()) {

            $weightMeasuresId[$qRec->id] = $qRec->id;
            if ($qRec->name == 'килограм') {
                $kgMeasureId = $qRec->id;
            }
        }

        $res = $recs;
        foreach ($recs as $key => $val) {

            if (!in_array($val->measureId, $weightMeasuresId)) {

                //Взема единичното тегло на целия продукт
                $singleProductWeight = null;
                $prodRec = cat_Products::fetch($val->productId);

                $singleProductWeight = cat_Products::getParams($val->productId, 'weight');

                if ($singleProductWeight) {
                    $singleProductWeight = $singleProductWeight / 1000;
                } else {
                    $singleProductWeight = cat_Products::getParams($val->productId, 'weightKg');
                }

                //Ако няма въведени параметри за единично тегло взема втората мярка, ако е кг.
                if (!$singleProductWeight) {
                    $prodInfo = cat_Products::getProductInfo($val->productId);
                    if(!empty($prodInfo->packagings)){
                        foreach ($prodInfo->packagings as $measure){
                            if($measure->isSecondMeasure == 'yes'){
                                $singleProductWeight = 1/$measure->quantity;
                            }
                        }
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
        $from = $rec->from;
        if(is_numeric($rec->from)){
            $from = acc_Periods::fetch($rec->from)->start;
        }

        $to = $rec->to;
        if(is_numeric($rec->to)){
            $to = dt::getLastDayOfMonth(acc_Periods::fetch($rec->to)->start);
        }

        return array('from' => $from, 'to' => $to);
    }
}
