<?php


/**
 * Плъгин вдигащ/намалящ броя използвания на продуктовите опаковки в различни документи
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_plg_LogPackUsage extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setPartIfNot($mvc, 'productFld', 'productId');
        setPartIfNot($mvc, 'packagingFld', 'packagingId');

        // Разширяване на полетата, които да се извличат при изтриване
        $fieldsBeforeDelete = arr::make($mvc->fetchFieldsBeforeDelete, true);
        if($mvc instanceof core_Detail){
            if(!array_key_exists($mvc->masterKey, $fieldsBeforeDelete)){
                $fieldsBeforeDelete[$mvc->masterKey] = $mvc->masterKey;
            }
        }

        $fieldsBeforeDelete[$mvc->productFld] = $mvc->productFld;
        $fieldsBeforeDelete += $mvc->getPackagingFields();
        $mvc->fetchFieldsBeforeDelete = implode(',', array_keys($fieldsBeforeDelete));


    }


    /**
     * Преди изтриване, се запомнят ид-та на перата
     */
    public static function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        $packagingFields = $mvc->getPackagingFields();
        foreach ($query->getDeletedRecs() as $rec) {
            if(!$mvc->canSyncPacks($rec)) continue;

            foreach ($packagingFields as $packagingField) {
                if(!empty($rec->{$packagingField})){
                    cat_products_Packagings::logUsage($rec->{$mvc->productFld}, $rec->{$packagingField}, true);
                }
            }
        }
    }


    /**
     * Кои са полетата за опаковките, за които ще се логва
     *
     * @param $mvc
     * @param $res
     * @return void
     */
    public static function on_AfterGetPackagingFields($mvc, &$res)
    {
        if(!$res){
            $res = array($mvc->packagingFld => $mvc->packagingFld);
        }
    }


    /**
     * Дали да се синхронизират опаковките след промяна
     *
     * @param $mvc
     * @param $res
     * @return void
     */
    public static function on_AfterCanSyncPacks($mvc, &$res)
    {
        if(!$res){
            $res = true;
        }
    }


    /**
     * Изпълнява се преди запис на номенклатурата
     */
    protected static function on_BeforeSave($mvc, $id, $rec)
    {
        $rec->_syncPacks = $mvc->canSyncPacks($rec);
        if(isset($rec->id) && !empty($rec->_syncPacks)){

            // Преди запис се извличат старите опаковки
            $packagingFields = $mvc->getPackagingFields();
            $fetchFields = $packagingFields;
            $fetchFields[$mvc->productFld] = $mvc->productFld;
            $exRec = $mvc->fetch($rec->id, $fetchFields, false);
            $rec->_exPackProductId = $exRec->{$mvc->productFld} ?? null;
            foreach ($packagingFields as $packagingField) {
                $rec->{"_ex{$packagingField}"} = $exRec->{$packagingField} ?? null;
            }
        }
    }


    /**
     * Изпълнява се преди запис на номенклатурата
     */
    protected static function on_AfterSave($mvc, &$id, &$rec, $fields = null)
    {
        // Ако няма опаковки за синхронизиране не се прави нищо
        if(empty($rec->_syncPacks)) return;

        // След запис за всяко поле на опаковка
        $packagingFields = $mvc->getPackagingFields();
        $savedFields = isset($fields) ? arr::make($fields, true) : null;
        $oldProductId = $rec->_exPackProductId ?? null;
        $productId = $oldProductId;
        if (property_exists($rec, $mvc->productFld) && (!isset($savedFields) || isset($savedFields[$mvc->productFld]))) {
            $productId = $rec->{$mvc->productFld};
        }
        foreach ($packagingFields as $packagingField) {
            $oldPackagingId = $rec->{"_ex{$packagingField}"} ?? null;
            $packagingId = $oldPackagingId;
            if (property_exists($rec, $packagingField) && (!isset($savedFields) || isset($savedFields[$packagingField]))) {
                $packagingId = $rec->{$packagingField};
            }

            // При смяна на артикула или опаковката се прехвърля използването
            if ($productId != $oldProductId || $packagingId != $oldPackagingId) {
                if (!empty($productId) && !empty($packagingId)) {
                    cat_products_Packagings::logUsage($productId, $packagingId);
                }

                // а на старата се декрементира
                if (!empty($oldProductId) && !empty($oldPackagingId)) {
                    cat_products_Packagings::logUsage($oldProductId, $oldPackagingId, true);
                }
            }
        }
    }
}
