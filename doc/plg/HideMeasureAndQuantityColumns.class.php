<?php


/**
 * Клас 'doc_plg_Close' - Плъгин за затваряне на мениджъри
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
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
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	$rows = &$data->rows;
    	if(!count($rows)) return;
    
    	$unset = TRUE;
    	$pcsId = cat_UoM::fetchBySinonim('pcs')->id;
    	
    	foreach ($rows as $id => $row){
    		$rec = $data->recs[$id];
    		
    		if($rec->{$mvc->packagingFld} != $pcsId || $rec->{$mvc->packQuantityFld} != 1){
    			$unset = FALSE;
    		}
    	}
    	
    	if($unset === TRUE){
    		unset($data->listFields[$mvc->packQuantityFld]);
    		unset($data->listFields[$mvc->packagingFld]);
    		unset($data->listFields[$mvc->packPriceFld]);
    	}
    }
}