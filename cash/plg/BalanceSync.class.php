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
     * Извлича информацията нужна за ъпдейт на касата
     */
    private static function prepareCashBalanceData()
    {
        $arr = $iRecs = array();
        $balanceRec = acc_Balances::getLastBalance();

        // Ако няма баланс няма какво да подготвяме
        if (empty($balanceRec)) return $arr;


        // Филтриране да се показват само за 501 сметка
        $dQuery = acc_BalanceDetails::getQuery();
        $accRec = acc_Accounts::getRecBySystemId(501);
        $dQuery->in("accountId", array($accRec->id => $accRec->id));
        $dQuery->where("#balanceId = {$balanceRec->id} AND #ent1Id IS NOT NULL");
        $recs = $dQuery->fetchAll();
        if(!countR($recs)) return $arr;

        // Кои са ид-та на перата от баланса
        $itemIds = arr::extractValuesFromArray($recs, "ent1Id");
        $itemIds += arr::extractValuesFromArray($recs, "ent2Id");
        if(countR($itemIds)){
            $itemQuery = acc_Items::getQuery();
            $itemQuery->in('id', $itemIds);
            $itemQuery->show('classId,objectId');
            $iRecs = $itemQuery->fetchAll();
        }

        // За всеки запис от баланса
        foreach ($recs as $rec) {
            $pItem = $iRecs[$rec->ent1Id];
            $cItem = $iRecs[$rec->ent2Id];
            if(!array_key_exists($pItem->objectId, $arr)) {
                $arr[$pItem->objectId] = (object)array('id' => $pItem->objectId, 'currencies' => array());
            }
            if(!array_key_exists($cItem->objectId, $arr[$pItem->objectId]->currencies)) {
                $arr[$pItem->objectId]->currencies[$cItem->objectId] = (object)array('currencyId' => $cItem->objectId,
                                                                                     'quantity' => 0, 'amount' => 0);
            }
            $arr[$pItem->objectId]->currencies[$cItem->objectId]->quantity += $rec->blQuantity;
            $arr[$pItem->objectId]->currencies[$cItem->objectId]->amount += $rec->blAmount;
        }

        // Връщане на групираните крайни суми
        return $arr;
    }
}