<?php



/**
 * Клас 'store_plg_Document'
 *
 * Плъгин даващ възможност на даден документ да бъде складов документ
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_plg_Document extends core_Plugin
{
	public static function on_AfterGetDocLink($mvc, &$res, $id)
	{
		if($mvc->haveRightFor('single', $id)){
	    	$icon = sbf($mvc->getIcon($id), '');
	    	$handle = $mvc->getHandle($id);
	    	$attr['class'] = "linkWithIcon";
	        $attr['style'] = "background-image:url('{$icon}');";
	        $attr['title'] = "{$mvc->singleTitle} №{$id}";
	        
	    	$res = ht::createLink($handle, array($mvc, 'single', $id), NULL, $attr);
	    }
	}
	
	
	public function on_AfterGetMeasures($mvc, &$res, $products)
	{
		$obj = new stdClass();
		$obj->volume = 0;
		$obj->weight = 0;
		
		foreach ($products as $p){
			$pInfo = cls::get($p->classId)->getProductInfo($p->productId, $p->packagingId);
			if($obj->volume !== NULL){
				if($pack = $pInfo->packagingRec){
					$volume = $pack->sizeWidth * $pack->sizeHeight * $pack->sizeDepth;
					(!$volume) ? $obj->volume = NULL : $obj->volume += $volume;
				} else {
					//@TODO
				}
			}
			
			if($obj->weight !== NULL){
				if($pack = $pInfo->packagingRec){
					$weight = $pack->netWeight + $pack->tareWeight;
					(!$volume) ? $obj->weight = NULL : $obj->weight += $weight;
				} else {
					//@TODO
				}
			}
		}
		
		//$obj->volume = ($obj->volume) ? $obj->volume : tr("неможе се изчисли");
		//$obj->weight = ($obj->weight) ? $obj->weight : tr("неможе се изчисли");
		
		$res = $obj;
	}
	
}