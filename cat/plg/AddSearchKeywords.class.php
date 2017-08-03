<?php



/**
 * Плъгин добавящ артикулите от главния детайл на документа към ключовите му думи
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_plg_AddSearchKeywords extends core_Plugin
{
	
	
	/**
	 * Добавя ключови думи за пълнотекстово търсене
	 */
	public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
	{
		if($rec->id){
			$detailsKeywords = '';
			
			$Detail = cls::get($mvc->mainDetail);
			$dQuery = $Detail::getQuery();
			$dQuery->where("#{$Detail->masterKey} = '{$rec->id}'");
			setIfNot($Detail->productFld, 'productId');
			
			while($dRec = $dQuery->fetch()){
				$detailsKeywords .= " " . plg_Search::normalizeText(cat_Products::getTitleById($dRec->{$Detail->productFld}));
			}
				
			if(!empty($detailsKeywords)){
				$res = " " . $res . " " . $detailsKeywords;
			}
		}
	}
}
