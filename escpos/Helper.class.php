<?php


/**
 * 
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class escpos_Helper
{
    
    
    /**
     * Връща данните в XML формат, за bgERP Agent 
     * 
     * @param core_Manager $clsInst
     * @param integer $id
     * @param string $drvName
     * @param integer $userId
     * 
     * @return string
     */
    public static function getContentXml($clsInst, $id, $drvName, $userId)
    {
        $res = self::getTpl();
        
        $res->replace($clsInst->getTitleById($id), 'title');
        
        $dataContent = self::preparePrintView($clsInst, $id, $userId);
        $dataContent = escpos_Convert::process($dataContent, $drvName);
        
        $res->replace(base64_encode($dataContent), 'data');
        
        return $res->getContent();
    }
    
    
    /**
     * Подготовка за печат на мобилен принтер
     *
     * @param core_Master $Inst
     * @param int $id
     * @param stdClass $data
     * @return core_ET
     */
	private static function getShipmentPreview($Inst, $id, $data)
    {
    	// Избор на шаблон
    	if($Inst instanceof sales_Sales){
    		$tpl = getTplFromFile('sales/tpl/sales/SalePrint.shtml');
    	} elseif($Inst instanceof store_ShipmentOrders) {
    		$tpl = getTplFromFile('store/tpl/ShipmentOrderPrint.shtml');
    	}else {
    		$tpl = getTplFromFile('sales/tpl/InvoicePrint.shtml');
    	}
    	
    	$row = $data->row;
    	$row->type = mb_strtoupper($row->type);
    	
    	// Експейпване на полетата
    	$fields = clone $row;
    	$fields = (array)$fields;
    	$fields = array_keys((array)$fields);
    	$fields = array_combine($fields, $fields);
    	unset($fields['ROW_ATTR']);
    	
    	foreach ($fields as $fld){
    		if(!empty($data->rec->{$fld}) && $row->{$fld} instanceof core_ET){
    			$row->{$fld} = $row->{$fld}->getContent();
    		}
    		
    		if(!empty($row->{$fld})){
    			$row->{$fld} = trim(strip_tags($row->{$fld}));
    		}
    	}
    	$row->delimiter = "|";
    	// Поставяне на мастър данните
    	$tpl->placeObject($row);
    	$count = 0;
    	
    	// Кои са детайлите?
    	if($Inst instanceof sales_Sales){
    		$detailRecs = $data->sales_SalesDetails->recs;
    		$detailRows = $data->sales_SalesDetails->rows;
    		$Detail = 'sales_SalesDetails';
    	} elseif($Inst instanceof store_ShipmentOrders) {
    		$detailRecs = $data->store_ShipmentOrderDetails->recs;
    		$detailRows = $data->store_ShipmentOrderDetails->rows;
    		$Detail = 'store_ShipmentOrderDetails';
    	} else {
    		$detailRecs = $data->sales_InvoiceDetails->recs;
    		$detailRows = $data->sales_InvoiceDetails->rows;
    		$Detail = 'sales_InvoiceDetails';
    	}
    	
    	$DoubleSmart = cls::get('type_Double', array('params' => array('smartRound' => 'smartRound')));
		$DoubleQ = core_Type::getByName('double(decimals=2)');
    	$Varchar = core_Type::getByName('varchar');

    	$block = $tpl->getBlock('PRODUCT_BLOCK');

    	// За всеки
    	foreach ($detailRows as $id => $dRow){
    		$dRec = $detailRecs[$id];
    		$b = clone $block;
    		
    		// Ако е ДИ или КИ има специална логика
    		if($Inst instanceof sales_Invoices){
    			if($data->rec->type == 'dc_note'){
    				if($dRec->changedPrice !== TRUE && $dRec->changedQuantity !== TRUE) continue;
    				
    				if(empty($dRec->id)){
    					$dpRow = new stdClass();
    					$dpRow->downpayment = $dRow->reason;
    					$dpRow->downpayment_amount = $dRow->amount;
    					
    					$tpl->placeObject($dpRow);
    					
    					continue;
    				}
    			}
    		}
    	
    		// Ако има партиди
    		if(core_Packs::isInstalled('batch')){
    			$query = batch_BatchesInDocuments::getQuery();
    			$query->where("#detailClassId = {$Detail::getClassId()} AND #detailRecId = {$dRec->id} AND #operation = 'out'");
    			$query->orderBy('id', "DESC");
    			
    			$res = '';
    			
    			// Показват се
    			while($bRec = $query->fetch()){
    				$batch = $Varchar->toverbal($bRec->batch);
    				$pack = cat_UoM::getShortName($bRec->packagingId);
    				$quantity = $DoubleQ->toVerbal($bRec->quantity / $bRec->quantityInPack);
    				
    				$prefix = ($res === '') ? "" : "<p f>";
    				$res .= "{$prefix}{$batch} {$quantity} {$pack}" . "\n";
    			}
    			if($res != ''){
    				$dRow->batch = $res;
    			}
    		}
    		
    		// Подготовка на данните за заместване
    		$count++;
    		$dRow->numb += $count;
    		$dRow->productId = cat_Products::getVerbal($dRec->productId, 'name');
    		unset($dRow->ROW_ATTR);
    		$dRow->packagingId = cat_UoM::getShortName($dRec->packagingId);
    		
    		if($Inst instanceof sales_Invoices){
    			$dRec->packQuantity = $dRec->quantity;
    		}
    		
    		$dRec->packQuantity = round($dRec->packQuantity, 3);
    		$dRow->packQuantity = strip_tags($DoubleSmart->toVerbal($dRec->packQuantity));
    		$dRow->packQuantity = str_replace('&nbsp;', ' ', $dRow->packQuantity);
    		
    		$dRow->packPrice = strip_tags($DoubleQ->toVerbal($dRec->packPrice));
    		$dRow->packPrice = str_replace('&nbsp;', ' ', $dRow->packPrice);
			$dRow->amount = strip_tags($DoubleQ->toVerbal($dRec->amount));
			$dRow->amount = str_replace('&nbsp;', ' ', $dRow->amount);

			// Поставяне в шаблона
    		$b->placeObject($dRow);
    		$b->removeBlocks();
    		$b->removePlaces();
    		$b->append2Master();
    	}

    	// Ако е Авансова ф-ра има специална логика
    	if($Inst instanceof sales_Invoices){
    		$dpInfo = $data->sales_InvoiceDetails->dpInfo;
    		if($dpInfo->dpOperation == 'deducted'){
    			$dpRow = new stdClass();
    			$dpRow->downpayment = 'Приспадане на авансово плащане';
    			$dpRow->downpayment_amount = $DoubleQ->toVerbal($dpInfo->dpAmount);
    			$tpl->placeObject($dpRow);
    		} elseif($dpInfo->dpOperation == 'accrued'){
    			$firstDoc = doc_Threads::getFirstDocument($data->sales_InvoiceDetails->masterData->rec->threadId);
    			$valior = $firstDoc->getVerbal('valior');
    			
    			$dpRow = new stdClass();
    			$dpRow->downpayment = "Авансово плащане по договор №{$firstDoc->that} от {$valior}";
    			$dpRow->downpayment_amount = $DoubleQ->toVerbal($dpInfo->dpAmount);
    			$tpl->placeObject($dpRow);
    		}
    	}
    	
    	$tpl->removeBlocks();
    	$tpl->removePlaces();
    	
    	// Връщане на шаблона
    	return $tpl;
    }
    

    /**
     * Подготвя данните за отпечатване
     * 
     * @param core_Manager $clsInst
     * @param integer $id
     * @param integer|NULL $userId
     * 
     * @return string
     */
    public static function preparePrintView($clsInst, $id, $userId = NULL)
    {
    	expect($Inst = cls::get($clsInst));
    	
    	$iRec = $Inst->fetch($id);
    	
    	expect($iRec);
    	
    	$isSystemUser = core_Users::isSystemUser();
    	if ($isSystemUser) {
    	    core_Users::cancelSystemUser();
    	}
    	
    	if (!isset($userId)) {
    	    $userId = $iRec->createdBy;
    	}
    	core_Users::sudo($userId);
    	
    	// Записваме, че документа е принтиран
    	doclog_Documents::pushAction(
    	                array(
    	                        'action' => doclog_Documents::ACTION_PRINT,
    	                        'containerId' => $iRec->containerId,
    	                        'threadId' => $iRec->threadId
    	                )
    	);
    	// Флъшваме, за да се запише веднага
    	doclog_Documents::flushActions();
    	
    	Mode::push('dataType', 'php');
    	Mode::push('text', 'plain');
     	$data = Request::forward(array('Ctr' => $Inst->className, 'Act' => 'single', 'id' => $id));
     	Mode::pop('text');
     	Mode::pop('dataType');
     	core_Users::exitSudo();
     	
     	if ($isSystemUser) {
     	    core_Users::forceSystemUser();
     	}
     	
     	expect($data);

    	$str = self::getShipmentPreview($Inst, $id, $data);
    	
    	return $str;
    }
    
    
    /**
     * Мокъп функция за връщане на шаблон за резултат
     * 
     * @return ET
     */
    protected static function getTpl()
    {
        $tpl = '<?xml version="1.0" encoding="utf-8"?>
                <btpDriver Command="DirectIO">
                    <title>[#title#]</title>
                    <data>[#data#]</data>
                </btpDriver>';
        
        $res = new ET(tr('|*' . $tpl));
        
        return $res;
    }
}
