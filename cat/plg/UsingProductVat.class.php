<?php


/**
 * Плъгин имплементиращ 'cat_interface_DocumentUsingVatIntf'
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_plg_UsingProductVat extends core_Plugin
{

    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(&$mvc)
    {
        $mvc->declareInterface('cat_interface_DocumentVatIntf');
        setIfNot($mvc->productFld, 'productId');
        setIfNot($mvc->valiorFld, 'valior');
    }


    /**
     * Метод по подразбиране дали дадения документ участва в документи с ДДС след подадената дата
     *
     * @param $mvc
     * @param $res
     * @param $productId
     * @param $date
     * @return void
     */
    public static function on_AfterIsUsedAfterInVatDocument($mvc, &$res, $productId, $date = null)
    {
        if(isset($res)) return;

        if(isset($mvc->mainDetail)){
            $date = $date ?? dt::today();
            $Detail = cls::get($mvc->mainDetail);
            $query = $Detail->getQuery();
            $query->EXT('valior', $mvc->className, "externalName={$mvc->valiorFld},externalKey={$Detail->masterKey}");
            $query->EXT('dState', $mvc->className, "externalName=state,externalKey={$Detail->masterKey}");
            $query->where("#{$mvc->productFld} = '{$productId}' AND #valior >= '{$date}' AND #dState != 'rejected'");
            $query->limit(1);
            $foundRec = $query->fetch();

            $res = is_object($foundRec);
        }
    }
}