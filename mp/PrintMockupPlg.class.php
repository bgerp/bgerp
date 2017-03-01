<?php


/**
 * 
 *
 * @category  vendors
 * @package   google
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mp_PrintMockupPlg extends core_Plugin
{
    
    
    /**
     * 
     * @param core_Manager $mvc
     * @param stdObject $res
     * @param stdObject $data
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        $retUrl = array($mvc, 'single', $data->rec->id);
        
        // Добавяме бутон за тестово отпечатване в bluetooth принтер
        if (isset($data->rec->id) && $mvc->haveRightFor('single', $data->rec) && ($data->rec->state != 'rejected') && ($data->rec->state != 'draft')) {

        	$data->toolbar->addBtn('MP', 'bgerp://print/' . $mvc->protectId($data->rec->id),
        			"id=mp{$data->rec->containerId},class=fright,row=2, order=39,title=" . "Тестов печат чрез bluetoot принтер",  'ef_icon = img/16/print_go.png');
        	
        	$data->toolbar->addBtn('Принтер', array($mvc, 'sendtoprint', 'containerId' => $data->rec->containerId),
                            "id=mp1{$data->rec->containerId},class=fright,row=2, order=39,title=" . "Тестов печат чрез bluetoot принтер",  'ef_icon = img/16/print_go.png');
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param string $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
    	if(strtolower($action) == strtolower('sendtoprint')) {
    		//$mvc->requireRightFor('sendtoprint');
    			
    		expect($containerId = Request::get('containerId', 'int'));
    		expect($document = doc_Containers::getDocument($containerId));
    		
    		if($document->isInstanceOf('sales_Sales')){
    			$tpl = getTplFromFile('sales/tpl/sales/SalePrint.shtml');
    			$saleRec = $document->fetch();
    			
    			$dQuery = sales_SalesDetails::getQuery();
    			$dQuery->where("#saleId = {$saleRec->id}");
    			$recs = $dQuery->fetchAll();
    			 
    			$Sales = $document->getInstance();
    			deals_Helper::fillRecs($Sales, $recs, $saleRec);
    			
    			
    			//core_Mode::push('text', 'plain');
    			$saleRow = $document->getInstance()->recToVerbal($saleRec);
    			//$info = deals_Helper::getDocumentHeaderInfo($saleRec->contragentClassId, $saleRec->contragentId);
    			
    			$summary = deals_Helper::prepareSummary($Sales->_total, $saleRec->valior, $saleRec->currencyRate, $saleRec->currencyId, $saleRec->chargeVat, FALSE, $saleRec->tplLang);
    			$saleRow = (object)((array)$saleRow + (array)$summary);
    			unset($saleRow->id);
    			
    			$tpl->placeObject($saleRow);
    			
    			foreach ($recs as $id => $rec){
    				$row = sales_SalesDetails::recToVerbal($rec);
    				unset($row->id);
    				
    				$block = $tpl->getBlock('PRODUCT_BLOCK');
    				$block->placeObject($row);
    				$block->removeBlocks();
    				$block->append2Master();
    			}
    			
    			//$res = $tpl;
    			//return FALSE;
    			
    			 $res = escpos_Convert::process($tpl, 'escpos_driver_Ddp250');
            	 echo $res;
            	 shutdown();
    		}
    		
    	}
    }
}
