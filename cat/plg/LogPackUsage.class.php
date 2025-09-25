<?php


/**
 * Плъгин вдигащ/намалящ броя използвания на продуктовите опаковки в различни документи
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
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
        setIfNot($mvc->productFld, 'productId');
        setIfNot($mvc->packagingFld, 'packagingId');

        // Разширяване на полетата, които да се извличат при изтриване
        $fieldsBeforeDelete = arr::make($mvc->fetchFieldsBeforeDelete, true);
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
        if(isset($rec->id) && $rec->_syncPacks){

            // Преди запис се извличат старите опаковки
            $packagingFields = $mvc->getPackagingFields();
            $exRec = $mvc->fetch($rec->id, $packagingFields, false);
            foreach ($packagingFields as $packagingField) {
                $rec->{"_ex{$packagingField}"} = $exRec->{$packagingField};
            }
        }
    }


    /**
     * Изпълнява се преди запис на номенклатурата
     */
    protected static function on_AfterSave($mvc, &$id, &$rec, $fields = null)
    {
        // Ако няма опаковки за синхронизиране не се прави нищо
        if(!$rec->_syncPacks) return;

        // След запис за всяко поле на опаковка
        $packagingFields = $mvc->getPackagingFields();
        foreach ($packagingFields as $packagingField) {

            // Ако тя е сменена се инкрементира използването на новата
            if($rec->{$packagingField} != $rec->{"_ex{$packagingField}"}){
                if(!empty($rec->{$packagingField})){
                    cat_products_Packagings::logUsage($rec->{$mvc->productFld}, $rec->{$packagingField});
                }

                // а на старата се декрементира
                if(!empty($rec->{"_ex{$packagingField}"})){
                    cat_products_Packagings::logUsage($rec->{$mvc->productFld}, $rec->{"_ex{$packagingField}"}, true);
                }
            }
        }
    }
}
