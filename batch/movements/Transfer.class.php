<?php


/**
 * Помощен клас-имплементация на интерфейса batch_MovementSourceIntf за наследниците на store_Transfers
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see batch_MovementSourceIntf
 *
 */
class batch_movements_Transfer
{
	
	
	/**
     * 
     * @var store_Transfers
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
    	$inStore = $rec->toStore;
    	$outStore = $rec->fromStore;
    	
    	$dQuery = store_TransfersDetails::getQuery();
    	$dQuery->where("#transferId = {$rec->id}");
    	$dQuery->where("#batch IS NOT NULL OR #batch != ''");
    	$dQuery->show('newProductId,batch,quantity');
    	
    	while($dRec = $dQuery->fetch()){
    		$entries[] = (object)array('productId' => $dRec->newProductId,
					    			   'batch'        => $dRec->batch,
					    			   'storeId'      => $outStore,
					    			   'quantity'     => $dRec->quantity,
					    			   'operation'    => 'out',
					    			   'date'	      => $rec->valior,
    		);
    		
    		$entries[] = (object)array('productId' => $dRec->newProductId,
					    			   'batch'        => $dRec->batch,
					    			   'storeId'      => $inStore,
					    			   'quantity'     => $dRec->quantity,
					    			   'operation'    => 'in',
					    			   'date'	      => $rec->valior,
    		);
    	}
    	
    	return $entries;
    }
}