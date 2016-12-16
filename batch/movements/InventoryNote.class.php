<?php


/**
 * Помощен клас-имплементация на интерфейса batch_MovementSourceIntf за batch_InventoryNotes
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
class batch_movements_InventoryNote
{
	
	
	/**
     * 
     * @var store_DocumentMaster
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
		$storeId = $rec->storeId;
		
		$Detail = cls::get($this->class->mainDetail);
		$dQuery = $Detail->getQuery();
    	$dQuery->where("#{$Detail->masterKey} = {$rec->id}");
		$dQuery->show('productId,batchIn,batchOut,quantity');
		$dQuery->orderBy('id', 'ASC');
		
		$operation = ($this->class instanceof store_ShipmentOrders) ? 'out' : 'in';
		
		while($dRec = $dQuery->fetch()){
			$batchesIn = batch_Defs::getBatchArray($dRec->productId, $dRec->batchIn);
			$batchesOut = batch_Defs::getBatchArray($dRec->productId, $dRec->batchOut);
			
			foreach (array('in' => 'batchesIn', 'out' => 'batchesOut') as $operation => $var){
				$arr = ${$var};
				
				if(is_array($arr)){
					$quantity = (count($arr) == 1) ? $dRec->quantity : $dRec->quantity / count($arr);
					
					foreach ($arr as $key => $arr){
						if(empty($key)) continue;
						
						$entries[] = (object)array('productId' => $dRec->productId,
								                   'batch'     => $key,
								                   'storeId'   => $storeId,
								                   'quantity'  => $quantity,
								                   'operation' => $operation,
								                   'date'	   => $rec->valior,
						);
					}
				}
			}
		}


        return $entries;
    }
}