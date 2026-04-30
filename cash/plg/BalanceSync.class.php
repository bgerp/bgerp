<?php


/**
 * Клас 'cash_plg_BalanceSync'
 * Плъгин който след изчисляването на горещия баланс го синхронизира с cash_Cases
 *
 *
 * @category  bgerp
 * @package   cash
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see 	  acc_Balances
 */
class cash_plg_BalanceSync extends core_Plugin
{

    /**
     * След изчисляване на баланса синхронизира касовите наличности
     */
    public static function on_AfterRecalcBalances(acc_Balances $mvc, &$data)
    {
        // Подготовка на салдата на касите от баланса
        $cashBalanceArr = self::prepareCashBalanceData();
        cash_Cases::sync($cashBalanceArr);
    }


    /**
     * Извлича информацията нужна за ъпдейт на склада
     */
    private static function prepareCashBalanceData()
    {
        $arr = $iRecs = array();
        $balanceRec = acc_Balances::getLastBalance();

        // Ако няма баланс няма какво да подготвяме
        if (empty($balanceRec)) return $arr;

        // Извличане на сметките по които ще се ситематизират данните
        $accIds = array();
        $Cases = cls::get('cash_Cases');
        foreach (arr::make($Cases->balanceRefAccounts, true) as $accSysId){
            $accRec = acc_Accounts::getRecBySystemId($accSysId);
            if(!empty($accRec)) {
                $accIds[$accRec->id] = $accRec->id;
            }
        }

        // Филтриране да се показват само касовите сметки
        $dQuery = acc_BalanceDetails::getQuery();
        $dQuery->in("accountId", $accIds);
        $dQuery->where("#balanceId = {$balanceRec->id} AND #ent1Id IS NOT NULL");
        $recs = $dQuery->fetchAll();
        if(!countR($recs)) return $arr;

        // Кои са ид-та на перата от баланса
        $itemIds = arr::extractValuesFromArray($recs, "ent1Id");
        if(countR($itemIds)){
            $itemQuery = acc_Items::getQuery();
            $itemQuery->in('id', $itemIds);
            $itemQuery->show('classId,objectId');
            $iRecs = $itemQuery->fetchAll();
        }

        // За всеки запис от баланса
        foreach ($recs as $rec) {
            $pItem = $iRecs[$rec->ent1Id];
            if(!array_key_exists($pItem->objectId, $arr)) {
                // Ако няма такъв продукт в масива, се записва
                $arr[$pItem->objectId] = new stdClass();
                $arr[$pItem->objectId]->id = $pItem->objectId;
                $arr[$pItem->objectId]->blAmount = 0;
            }
            $arr[$pItem->objectId]->blAmount += $rec->blAmount;
        }

        // Връщане на групираните крайни суми
        return $arr;
    }
}