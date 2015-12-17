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
class batch_movements_ShipmentOrder
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
		$storeId = $rec->storeId;
		
		$dQuery = store_ShipmentOrderDetails::getQuery();
		$dQuery->where("#shipmentId = {$rec->id}");
		$dQuery->where("#batch IS NOT NULL OR #batch != ''");
		$dQuery->show('productId,batch,quantity');
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