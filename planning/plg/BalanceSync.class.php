<?php


/**
 * Клас 'planning_plg_BalanceSync'
 * Плъгин който след изчисляването на горещия баланс го синхронизира с planning_WorkInProgress
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
 * @see 	  acc_Balances
 */
class planning_plg_BalanceSync extends core_Plugin
{

    /**
     * След изчисляване на баланса синхронизира складовите наличности
     */
    public static function on_AfterRecalcBalances(acc_Balances $mvc, &$data)
    {
        // Извличане на незавършеното производство от баланса
        $workInProgressArr = self::prepareWorkInProgressData();

        // Синхронизиране на незавършеното производство
        planning_WorkInProgress::sync($workInProgressArr);
    }


    /**
     * Извлича информацията нужна за ъпдейт на склада
     */
    private static function prepareWorkInProgressData()
    {
        $arr = array();
        $balanceRec = acc_Balances::getLastBalance();

        // Ако няма баланс няма какво да подготвяме
        if (empty($balanceRec)) return $arr;

        // Извличане на сметките по които ще се ситематизират данните
        $wIProgressAccId = acc_Accounts::getRecBySystemId(planning_WorkInProgress::DEFAULT_ACC_SYS_ID)->id;

        // Филтриране да се показват само записите от зададените сметки
        $dQuery = acc_BalanceDetails::getQuery();
        $dQuery->where("#accountId = {$wIProgressAccId}");
        $dQuery->where("#balanceId = {$balanceRec->id}");
        $recs = $dQuery->fetchAll();

        if(!countR($recs)) return $arr;

        // Кои са ид-та на перата от баланса
        $itemIds = array();
        foreach ($recs as $rec1) {
            if (!array_key_exists($rec1->{"ent1Id"}, $itemIds)) {
                if (isset($rec1->{"ent1Id"})) {
                    $itemIds[$rec1->{"ent1Id"}] = $rec1->{"ent1Id"};
                }
            }
        }

        // Извличаме наведнъж записите им
        $iRecs = array();
        $itemQuery = acc_Items::getQuery();
        $itemQuery->in('id', $itemIds);
        $itemQuery->show('classId,objectId');
        while ($i = $itemQuery->fetch()) {
            $iRecs[$i->id] = $i;
        }

        // За всеки запис от баланса
        $now = dt::now();
        foreach ($recs as $rec) {
            // Перо 'Артикул'
            $pItem = $iRecs[$rec->ent1Id];

            // Ако няма такъв продукт в масива, се записва
            $arr[$pItem->objectId] = new stdClass();
            $arr[$pItem->objectId]->productId = $pItem->objectId;
            $arr[$pItem->objectId]->classId = $pItem->classId;
            $arr[$pItem->objectId]->quantity = $rec->blQuantity;
            $arr[$pItem->objectId]->lastUpdated = $now;
        }

        // Връщане на групираните крайни суми
        return $arr;
    }
}