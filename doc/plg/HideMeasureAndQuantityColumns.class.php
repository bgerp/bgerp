<?php


/**
 * Клас 'doc_plg_Close' - Плъгин за затваряне на мениджъри
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_plg_HideMeasureAndQuantityColumns extends core_Plugin
{
    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(&$mvc)
    {
        setIfNot($mvc->packQuantityFld, 'packQuantity');
        setIfNot($mvc->packagingFld, 'packagingId');
        setIfNot($mvc->packPriceFld, 'packPrice');
        setIfNot($mvc->productFld, 'productId');
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $rows = &$data->rows;
        
        if (!count($rows)) {
            
            return;
        }
        
        $unset = true;
        $pcsId = cat_UoM::fetchBySinonim('pcs')->id;
        
        foreach ($rows as $id => $row) {
            $rec = $data->recs[$id];
            $canStore = cat_Products::fetchField($rec->{$mvc->productFld}, 'canStore');
            
            if ($rec->{$mvc->packagingFld} != $pcsId || $rec->{$mvc->packQuantityFld} != 1 || $canStore == 'yes') {
                $unset = false;
            }
        }
        
        if ($unset === true) {
            unset($data->listFields[$mvc->packQuantityFld]);
            unset($data->listFields[$mvc->packagingFld]);
            unset($data->listFields[$mvc->packPriceFld]);
        }
    }
}
