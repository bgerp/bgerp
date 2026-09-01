<?php


/**
 * Плъгин за детайла на документите за разпад - страната на редовете
 *
 * Добавя процента от себестойността: въвежда се при ръчно разпределяне, а при
 * останалите начини се смята на живо (@see cat_plg_DisassemblyDoc)
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_plg_DisassemblyDocDetail extends core_Plugin
{
    /**
     * След описанието на модела
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->FLD('costPercent', 'percent(min=0,max=1,allowEmpty)', 'caption=Oт сб-ст,input=none,tdClass=accCell,after=quantity,hint=Каква част от себестойността на вложения артикул се пада на този ред,unit=%');
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm(core_Mvc $mvc, &$data)
    {
        // Само при ръчно разпределяне; празният става остатъкът (@see on_AfterInputEditForm)
        if (self::isAllocatedRow($mvc, $data->form->rec) && $data->masterRec->allocationBy == 'manual') {
            $data->form->setField('costPercent', 'input,placeholder=Остатъкът');
        }
    }


    /**
     * След въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        $rec = &$form->rec;
        if (!$form->isSubmitted() || !self::isAllocatedRow($mvc, $rec)) return;

        $masterId = $rec->{$mvc->masterKey};
        $allocationBy = $mvc->Master->fetchField($masterId, 'allocationBy');

        if ($allocationBy == 'manual') {

            // Непопълненият става остатъкът до 100%, за да не се смята наум
            if (!isset($rec->costPercent)) {
                $remainder = round(1 - cat_plg_DisassemblyDoc::sumRowsPercent($mvc->Master, $masterId, $rec->id ?? null), 4);
                if ($remainder < 0) {
                    $form->setError('costPercent', 'Ръчно зададените проценти на другите редове вече надхвърлят 100%|*!');
                } else {
                    $rec->costPercent = $remainder;
                }
            }

            // Само промяна от потребител води до преизчисляване (@see on_AfterSave)
            $rec->_rebalanceOtherRows = true;
        }

        if ($allocationBy != 'quantity') return;

        // По количество се разпределя само между производни мерки
        $baseUomId = null;
        if (!cat_plg_DisassemblyDoc::areRowsInTheSameUom($mvc->Master, $masterId, $rec->id ?? null, $rec->productId, $baseUomId)) {
            $form->setError('productId', 'Артикулът трябва да е в мярка, производна на мярката на вече добавените произведени артикули|* <b>' . cat_UoM::getVerbal($baseUomId, 'name') . '</b>!');
        }
    }


    /**
     * След запис на ред - извиква се преди обновяването на мастъра, тоест преди
     * реконтирането (@see core_Detail::save_)
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = null)
    {
        // Изтриването на ред е оттегляне, докато мастърът пази ревизии - останалите
        // поемат освободения процент (@see doc_plg_DetailRevisions)
        if (self::isAllocatedRow($mvc, $rec) && ($rec->state ?? null) == 'rejected' && ($rec->rejectedReason ?? null) == 'deleted') {
            cat_plg_DisassemblyDoc::rebalanceOtherRows($mvc->Master, $rec->{$mvc->masterKey});

            return;
        }

        if (empty($rec->_rebalanceOtherRows)) return;
        unset($rec->_rebalanceOtherRows);

        cat_plg_DisassemblyDoc::rebalanceOtherRows($mvc->Master, $rec->{$mvc->masterKey}, $id);
    }


    /**
     * След подготовката на редовете - процентите зависят от всички разпределяни
     * редове наведнъж, затова не се смятат в recToVerbal
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, &$res, &$data)
    {
        $data->totalPercent = 0;
        $data->percentWarning = null;
        $data->totalQuantityData = null;
        $data->percentsArr = array();
        if (!countR($data->recs ?? null)) return;

        // За целия мастър, не само за показаните редове (заради страницирането)
        $masterIds = array();
        foreach ($data->recs as $rec) {
            if (self::isAllocatedRow($mvc, $rec)) {
                $masterIds[$rec->{$mvc->masterKey}] = $rec->{$mvc->masterKey};
            }
        }

        $warningArr = array();
        foreach ($masterIds as $masterId) {
            $statuses = array();
            $percentsArr = cat_plg_DisassemblyDoc::getPercents($mvc->Master, $masterId, null, $statuses);
            $allocationBy = $mvc->Master->fetchField($masterId, 'allocationBy');
            $data->percentsArr += $percentsArr;

            if (isset($statuses['warning'])) {
                $warningArr[$masterId] = tr($statuses['warning']);
            }

            foreach ($percentsArr as $id => $obj) {
                if (!array_key_exists($id, $data->rows)) continue;

                // Изчисленото застава на мястото на въведеното, освен при ръчно
                $percentVerbal = cat_DisassemblyBoms::getPercentVerbal($obj->percent, $allocationBy, $statuses['error'] ?? null);
                if (isset($percentVerbal)) {
                    $data->rows[$id]->costPercent = $percentVerbal;
                }

                $data->totalPercent += $obj->percent ?? 0;
            }
        }

        if (countR($warningArr)) {
            $data->percentWarning = implode('<br>', $warningArr);
        }

        // Сборът на количествата има смисъл само при сравними мерки
        if (countR($masterIds) == 1) {
            $baseMeasureId = null;
            $totalQuantity = cat_plg_DisassemblyDoc::sumRowsQuantity($mvc->Master, key($masterIds), $baseMeasureId);
            if (!empty($totalQuantity)) {
                $totalQuantity = cat_UoM::round($baseMeasureId, $totalQuantity);
                $data->totalQuantityData = (object) array('quantity' => core_Type::getByName('double(smartRound)')->toVerbal($totalQuantity),
                                                         'measure' => tr(cat_UoM::getShortName($baseMeasureId)));
            }
        }
    }


    /**
     * Дали редът участва в разпределянето на себестойността
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     *
     * @return bool
     */
    private static function isAllocatedRow(core_Mvc $mvc, $rec)
    {
        return ($rec->type ?? null) == 'production';
    }


    /**
     * Бутон за изравняване на ръчните проценти до 100%
     *
     * @param core_ET  $tpl
     * @param core_Mvc $masterMvc
     * @param int      $masterId
     * @param string   $block
     * @param string   $style
     *
     * @return void
     */
    public static function appendAllocateBtn($tpl, core_Mvc $masterMvc, $masterId, $block, $style = 'margin-bottom:5px;')
    {
        if (Mode::isReadOnly() || Mode::is('printing')) return;

        $masterRec = $masterMvc->fetchRec($masterId);
        if (!$masterMvc->haveRightFor('allocatemanualpercents', $masterRec)) return;

        // Няма какво да изравнява - ред без процент чака да поеме остатъка
        $sum = 0;
        $hasEmpty = false;
        $dQuery = cat_plg_DisassemblyDoc::getRowQuery($masterMvc, $masterId);
        $dQuery->show('costPercent');
        while ($dRec = $dQuery->fetch()) {
            if (!isset($dRec->costPercent)) {
                $hasEmpty = true;
                break;
            }

            $sum += $dRec->costPercent;
        }

        if (!$hasEmpty && abs($sum - 1) < 0.0001) return;

        $btn = ht::createBtn('Изравняване', array($masterMvc, 'allocateManualPercents', $masterId, 'ret_url' => true), 'Ръчно въведените проценти ще бъдат преизчислени, за да правят точно 100%|*!', null, array('style' => $style, 'ef_icon' => 'img/16/calculator.png', 'title' => 'Изравняване на процентите от себестойността до 100%'));

        $tpl->append($btn, $block);
    }


    /**
     * Ред "Общо" под таблицата - всеки сбор застава точно под своята колона.
     * Процентът е зелен при точно 100% и червен при над 100%
     *
     * @param core_ET  $tpl  - таблицата с разпределяните редове
     * @param stdClass $data - данните, с които е рендирана (@see on_AfterPrepareListRows)
     *
     * @return void
     */
    public static function appendTotalRow($tpl, $data)
    {
        $total = $data->totalPercent ?? 0;
        $totalVerbal = core_Type::getByName('percent')->toVerbal($total);
        if ($total > 1) {
            $totalVerbal = ht::styleIfNegative($totalVerbal, -1);
        } elseif ($total == 1) {
            $totalVerbal = "<span style='color:green'>{$totalVerbal}</span>";
        }

        // Кой сбор под коя колона застава
        $cellArr = array('costPercent' => (object) array('value' => "<b>{$totalVerbal}</b>", 'class' => 'aright'));
        if (is_object($data->totalQuantityData ?? null)) {
            $cellArr['packagingId'] = (object) array('value' => $data->totalQuantityData->measure, 'class' => 'centered');
            $cellArr['packQuantity'] = (object) array('value' => "<b>{$data->totalQuantityData->quantity}</b>", 'class' => 'centered');
        }

        $columns = static::getTableColumns($data);
        $cellArr = array_intersect_key($cellArr, $columns);
        if (!countR($cellArr)) return;

        // Колоните без сбор се сливат, а етикетът застава непосредствено преди първия
        $row = '';
        $emptyCount = 0;
        $label = tr('Общо') . ':';
        foreach ($columns as $name => $dummy) {
            if (!array_key_exists($name, $cellArr)) {
                $emptyCount++;
                continue;
            }

            if ($emptyCount) {
                $row .= "<td colspan='{$emptyCount}' class='aright'>{$label}</td>";
                $emptyCount = 0;
                $label = '';
            }

            $value = strlen($label) ? "{$label} {$cellArr[$name]->value}" : $cellArr[$name]->value;
            $label = '';
            $row .= "<td class='{$cellArr[$name]->class}'>{$value}</td>";
        }

        if ($emptyCount) {
            $row .= "<td colspan='{$emptyCount}'></td>";
        }

        // Класът пази реда от измерването на колоните (@see planning/js/DisassemblyNoteTables.js)
        $tpl->append("<tr class='disassemblyTotalRow' style='background-color:#eee'>{$row}</tr>", 'ROW_AFTER');
    }


    /**
     * Реалните колони на рендираната таблица, в реда на клетките
     * (@see core_TableView::get)
     *
     * @param stdClass $data
     *
     * @return array - име на поле => подредба
     */
    private static function getTableColumns($data)
    {
        $i = 0;
        $columns = array();
        $mvc = $data->listTableMvc;
        foreach (arr::make($data->listFields, true) as $name => $caption) {
            if (empty($caption)) continue;

            // Колоните без заглавие се рендират като отделни редове под записа
            $colHeaders = is_string($caption) ? explode('->', $caption) : $caption;
            if (strlen($colHeaders[0]) && $colHeaders[0][0] == '@') continue;
            if (isset($mvc->fields[$name]->singleRow)) continue;

            $columns[$name] = (float) ($mvc->fields[$name]->column ?? 0) ?: $i++;
        }

        asort($columns);

        return $columns;
    }
}
