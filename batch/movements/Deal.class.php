<?php


/**
 * Помощен клас-имплементация на интерфейса batch_MovementSourceIntf за класа store_ShipmentOrders
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see batch_MovementSourceIntf
 *
 */
class batch_movements_Deal
{
	
	
	/**
     * 
     * @var store_ShipmentOrders
     */
    public $class;
    
    
    public function getMovements($rec)
    {
    	$entries = array();
    	$rec = $this->class->fetchRec($rec);
    	
    	$actions = type_Set::toArray($rec->contoActions);
    	if(!isset($actions['ship'])) return $entries;
    	
    	$storeId = $rec->shipmentStoreId;
    	
    	$Detail = cls::get($this->class->mainDetail);
    	$dQuery = $Detail->getQuery();
    	$dQuery->where("#{$Detail->masterKey} = {$rec->id}");
    	$dQuery->where("#batch IS NOT NULL OR #batch != ''");
    	
    	while($dRec = $dQuery->fetch()){
    		$entries[] = (object)array('productId' => $dRec->productId,
					    			   'batch'     => $dRec->batch,
					    			   'storeId'   => $storeId,
					    			   'quantity'  => $dRec->quantity,
					    			   'operation' => 'out',
					    			   'date'	   => $rec->valior,
    		);
    	}
    	
    	return $entries;
    }
}