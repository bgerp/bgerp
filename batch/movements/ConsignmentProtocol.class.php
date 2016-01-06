<?php


/**
 * Помощен клас-имплементация на интерфейса batch_MovementSourceIntf за наследниците на store_ConsignmentProtocols
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see batch_MovementSourceIntf
 *
 */
class batch_movements_ConsignmentProtocol
{
	
	
	/**
     * @var store_ConsignmentProtocols
     */
    public $class;
    
    
    /**
     * Връща масива с партидните движения, които поражда документа,
     * Ако никой от артикулите няма партида връща празен масив
     *
     * @param mixed $id - ид или запис
     * @return array $res - движенията
     * 			o int productId         - ид на артикула
     * 			o int storeId           - ид на склада
     * 			o varchar batch         - номера на партидата
     * 			o double quantity       - количеството
     * 			o in|out|stay operation - операция (влиза,излиза,стои)
     * 			o date date             - дата на операцията
     */
    public function getMovements($rec)
    {
    	$entries = array();
    	$rec = $this->class->fetchRec($rec);
    	
    	$details = array('store_ConsignmentProtocolDetailsReceived', 'store_ConsignmentProtocolDetailsSend');
    	
    	foreach ($details as $det){
    		$Detail = cls::get($det);
    		$dQuery = $Detail->getQuery();
    		$dQuery->where("#{$Detail->masterKey} = {$rec->id}");
    		$dQuery->where("#batch IS NOT NULL OR #batch != ''");
    		$dQuery->show('productId,batch,quantityInPack,packQuantity');
    	
    		$operation = ($Detail instanceof store_ConsignmentProtocolDetailsReceived) ? 'in' : 'out';
    		
    		while($dRec = $dQuery->fetch()){
    			$batches = batch_Defs::getBatchArray($dRec->productId, $dRec->batch);
    			$quantity = $dRec->quantityInPack * $dRec->packQuantity;
    			$quantity = (count($batches) == 1) ? $quantity : $quantity / count($batches);
    			
    			foreach ($batches as $b){
    				$entries[] = (object)array('productId' => $dRec->productId,
				    						  'batch'      => $b,
				    						  'storeId'    => $rec->storeId,
				    						  'quantity'   => $quantity,
				    						  'operation'  => $operation,
				    						  'date'	   => $rec->valior,
    				);
    			}
    		}
    	}
    	
    	return $entries;
    }
}