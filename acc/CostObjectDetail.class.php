<?php


/**
 * Помощен детайл подготвящ показването на разходните обекти към дадена корица
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acc_CostObjectDetail extends core_Manager
{

    /**
     * Подготовка на Детайлите
     */
    public function prepareDetail_($data)
    {
        if (!haveRole('ceo,acc')) {
            $data->hide = true;
            return;
        }

        // Извличане на разходните обекти към папката
        $data->costItemData = static::prepareCostObjectItemsInFolder($data->masterData->rec->folderId);
        if(!countR($data->costItemData->recs)){
            $data->hide = true;
            return;
        }

        // Извличане на текущите крайни салда
        $data->costItemAmounts = static::getBlAmounts($data->costItemData->ids);

        // Ако има РО - задачи
        $taskClassId = cal_Tasks::getClassId();
        if(isset($data->costItemData->recs[$taskClassId])){
            $result = array();
            arr::sortObjects($data->costItemData->recs[$taskClassId], 'id', 'ASC');

            // Групират се в дърво
            foreach ($data->costItemData->recs[$taskClassId] as $tKey => $tObject){
                $children = array();
                cal_Tasks::expandChildrenArr($children, $tKey);
                $tObject->children = array_keys($children);

                $result[$tKey] = $tObject;
                foreach ($children as $childKey => $childRec){
                    if(array_key_exists($childKey, $data->costItemData->recs[$taskClassId])){
                        $result[$childKey] = array('itemId' => $data->costItemData->recs[$taskClassId][$childKey]->itemId, 'id' => $childRec->id);
                        unset($data->costItemData->recs[$taskClassId][$childKey]);
                    }
                }
            }
            $data->costItemData->recs[$taskClassId] = $result;
        }

        // Подготовка на данните
        static::prepareCostItemData($data);

        $data->TabCaption = 'Разходи';
    }


    /**
     * Подготовка на данните за разходите
     *
     * @param stdClass $data
     * @return void
     */
    private static function prepareCostItemData($data)
    {
        $to = dt::today();
        $data->costItemRows = array();

        // Извличане на датата на най-ранно използване на перата
        $itemArr = array();
        $iQuery = acc_Items::getQuery();
        $iQuery->in('id', $data->costItemData->ids);
        $iQuery->show('earliestUsedOn,state');
        while($iRec = $iQuery->fetch()){
            $itemArr[$iRec->id] = $iRec;
        }

        // За всеки разходен обект, групиран по класа му
        $taskClassId = cal_Tasks::getClassId();
        foreach ($data->costItemData->recs as $classId => $itemData){
            $data->costItemData->rows[$classId] = array();
            foreach ($itemData as $tRec){
                $row = new stdClass();
                $row->itemId = acc_Items::getVerbal($tRec->itemId, 'titleLink');
                $blAmount = $totalAmount = null;
                if(array_key_exists($tRec->itemId, $data->costItemAmounts)){
                    $blAmount += $data->costItemAmounts[$tRec->itemId]->blAmount;
                }

                // На кое ниво е обекта в дървото
                $totalAmount = $blAmount;
                $parentArr = array_filter($data->costItemData->recs[$classId], function($a) use (&$parentCount, $tRec){
                    if(is_array($a->children)){
                        return (in_array($tRec->id, $a->children));
                    }
                    return false;
                });
                $row->level = countR($parentArr);

                // Ако има деца, сумират се сумите на децата му
                if(is_array($tRec->children)){
                    foreach ($tRec->children as $childId){
                        if(array_key_exists($childId, $data->costItemData->recs[$classId])){
                            $childItemId = $data->costItemData->recs[$classId][$childId]->itemId;
                            if(isset($childItemId)){
                                if(array_key_exists($childItemId, $data->costItemAmounts)){
                                    $totalAmount += $data->costItemAmounts[$childItemId]->blAmount;
                                }
                            }
                        }
                    }
                }

                // Вербализиране на сумите
                $Double = core_Type::getByName('double(decimals=2)');
                foreach (array('blAmount', 'totalAmount') as $quantityFld){
                    if(isset(${"{$quantityFld}"})){
                        $row->{$quantityFld} = $Double->toVerbal(${"{$quantityFld}"});
                        $row->{$quantityFld} = ht::styleNumber($row->{$quantityFld}, ${"{$quantityFld}"});
                    }
                }

                // Линк към хронологията
                if (acc_BalanceDetails::haveRightFor('history')) {
                    if(isset($itemArr[$tRec->itemId]->earliestUsedOn)){
                        $histUrl = array('acc_BalanceHistory', 'History', 'ent1Id' => $tRec->itemId,'fromDate' => $itemArr[$tRec->itemId]->earliestUsedOn, 'toDate' => $to, 'accNum' => 60201);
                        $row->history = ht::createLink('', $histUrl, null, 'title=Хронологична справка,ef_icon=img/16/clock_history.png');
                    }
                }

                // Ако е задача, ще се показва състоянието и прогреса ѝ
                $infoTpl = new core_ET("");
                if($classId == $taskClassId){
                    $documentRec = cls::get($classId)->fetch($tRec->id, 'state,progress');
                    $row->progress = cal_Tasks::getVerbal($documentRec, 'progress');
                    $infoTpl->append("<span style='color:blue'>{$row->progress}</span> ");
                } else {
                    $documentRec = cls::get($classId)->fetch($tRec->id, 'state');
                }

                $docStateVerbal = cal_Tasks::getVerbal($documentRec, 'state');
                $infoTpl->append("<span class= 'state-{$documentRec->state} document-handler'>{$docStateVerbal}</span>");

                if($itemArr[$tRec->itemId]->state == 'closed'){
                    $infoTpl = ht::createHint($infoTpl, 'Перото е затворено', 'img/16/warning-gray.png', false);
                }
                $row->info = $infoTpl;
                $data->costItemData->rows[$classId][$tRec->id] = $row;
            }
        }
    }


    /**
     * Връща крайните салда от Незавършеното производство
     *
     * @param array $arr
     * @return array $res
     */
    private static function getBlAmounts($arr)
    {
        $res = array();
        $lastBalanceRec = acc_Balances::getLastBalance();
        $bQuery = acc_BalanceDetails::getQuery();
        acc_BalanceDetails::filterQuery($bQuery, $lastBalanceRec->id, '60201', null, $arr);
        $bQuery->show('ent1Id,blAmount,debitAmount,creditAmount');
        while ($bRec = $bQuery->fetch()) {
            if(!array_key_exists($bRec->ent1Id, $res)){
                $res[$bRec->ent1Id] = (object)array('debitAmount' => 0, 'creditAmount' => 0, 'blAmount' => 0);
            }
            $res[$bRec->ent1Id]->debitAmount += $bRec->debitAmount;
            $res[$bRec->ent1Id]->creditAmount += $bRec->creditAmount;
            $res[$bRec->ent1Id]->blAmount += $bRec->blAmount;
        }

        return $res;
    }


    /**
     * Подготвя групирани разходните обекти към проекта
     *
     * @param int $folderId
     * @return stdClass $res
     */
    private static function prepareCostObjectItemsInFolder($folderId)
    {
        $res = (object)array('recs' => array(), 'ids' => array());

        $byClass = array();

        // РО се групират по класа им
        $sysId = acc_Lists::fetchBySystemId('costObjects')->id;
        $cQuery = doc_Containers::getQuery();
        $cQuery->where("#state != 'rejected' AND #folderId = {$folderId}");
        while($cRec = $cQuery->fetch()){
            $byClass[$cRec->docClass][$cRec->docId] = $cRec->docId;
        }

        foreach ($byClass as $docClassId => $ids){
            $iQuery = acc_Items::getQuery();
            $iQuery->where("#classId = {$docClassId}");
            $iQuery->in('objectId', $ids);
            $iQuery->where("LOCATE('|{$sysId}|', #lists)");
            while($iRec = $iQuery->fetch()){
                $res->recs[$iRec->classId][$iRec->objectId] = (object)array('itemId' => $iRec->id, 'id' => $iRec->objectId);
                $res->ids[$iRec->id] = $iRec->id;
            }
        }

        return $res;
    }


    /**
     * Рендиране на детайл
     */
    public function renderDetail_($data)
    {
        if($data->hide) return null;

        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $tpl->append(tr('Отнесени разходи'), 'title');

        $total = arr::sumValuesArray($data->costItemAmounts, 'blAmount');
        $Double = core_Type::getByName('double(decimals=2)');
        $totalVerbal = ($total) ? $Double->toVerbal($total) : "<span class='quiet'>n/a</span>";

        $tplTable = static::renderCostObjectTable($data);
        $tplTable->append($totalVerbal, 'totalAmount');

        $tpl->append($tplTable, 'content');
        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
        $tpl->append($baseCurrencyCode, 'currencyCode');

        $totalCount = countR($data->costItemData->ids);
        $tpl->append($totalCount, 'totalCount');

        return $tpl;
    }


    /**
     * Рендиране на таблицата с разходите
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public static function renderCostObjectTable($data)
    {
        $tpl = getTplFromFile('acc/tpl/CostObjectDetailTableTpl.shtml');

        foreach ($data->costItemData->rows as $classId => $rows){
            $Class = cls::get($classId);
            $classTitle = cls::getTitle($Class);
            $count = countR($rows);
            $countVerbal = core_Type::getByName('int')->toVerbal($count);
            $tpl->append("<tr class='costObjectCoverClassRow'><td colspan='6'class='leftCol' style='padding:5px 10px;font-weight: bold; background-color: #666; color: #fff'>{$classTitle} ({$countVerbal})</td></tr>", 'ROWS');

            foreach ($rows as $row){
                $row->ROW_CLASS = 'state-active';
                $row->indent = ($row->level) ? $row->level * 20 : 0;
                $blockTpl = clone $tpl->getBlock('BLOCK');
                $blockTpl->placeObject($row);
                $blockTpl->removeBlocksAndPlaces();
                $tpl->append($blockTpl, 'ROWS');
            }
        }

        return $tpl;
    }
}