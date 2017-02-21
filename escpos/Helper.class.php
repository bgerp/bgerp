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
     * 
     * @return string
     */
    public static function getContentXml($clsInst, $id, $drvName)
    {
        $res = self::getTpl();
        
        $res->replace($clsInst->getTitleById($id), 'title');
        
        $dataContent = self::preparePrintView($clsInst, $id);
        $dataContent = escpos_Convert::process($dataContent, $drvName);
        
        $res->replace(base64_encode($dataContent), 'data');
        
        return $res->getContent();
    }
    
    
	private static function getShipmentPreview($Inst, $id, $data)
    {
    	if($Inst instanceof sales_Sales){
    		$tpl = getTplFromFile('sales/tpl/sales/SalePrint.shtml');
    	} else {
    		$tpl = getTplFromFile('store/tpl/ShipmentOrderPrint.shtml');
    	}
    	
    	$row = $data->row;
    	
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

    	$tpl->placeObject($row);
    	$count = 0;
    	
    	if($Inst instanceof sales_Sales){
    		$detailRecs = $data->sales_SalesDetails->recs;
    		$detailRows = $data->sales_SalesDetails->rows;
    		$Detail = 'sales_SalesDetails';
    	} else {
    		$detailRecs = $data->store_ShipmentOrderDetails->recs;
    		$detailRows = $data->store_ShipmentOrderDetails->rows;
    		$Detail = 'store_ShipmentOrderDetails';
    	}
    	
    	$DoubleSmart = cls::get('type_Double', array('params' => array('smartRound' => 'smartRound')));
		$DoubleQ = core_Type::getByName('double(decimals=2)');
    	$Varchar = core_Type::getByName('varchar');

    	$block = $tpl->getBlock('PRODUCT_BLOCK');
    	foreach ($detailRows as $id => $dRow){
    		$dRec = $detailRecs[$id];
    		
    		if(core_Packs::isInstalled('batch')){
    			$query = batch_BatchesInDocuments::getQuery();
    			$query->where("#detailClassId = {$Detail::getClassId()} AND #detailRecId = {$dRec->id} AND #operation = 'out'");
    			$query->orderBy('id', "DESC");
    			
    			$res = '';
    			
    			while($bRec = $query->fetch()){
    				$batch = $Varchar->toverbal($bRec->batch);
    				$pack = cat_UoM::getShortName($bRec->packagingId);
    				$quantity = $DoubleQ->toVerbal($bRec->quantity / $bRec->quantityInPack);
    				
    				$prefix = ($res === '') ? "" : " / ";
    				$res .= "{$prefix}{$batch} {$quantity} {$pack}" . "\n";
    			}
    			if($res != ''){
    				$dRow->batch = $res;
    			}
    		}
    		
    		$count++;
    		$dRow->numb += $count;
    		$dRow->productId = cat_Products::getVerbal($dRec->productId, 'name');
    		
    		$dRec->packQuantity = round($dRec->packQuantity, 3);
    		$dRow->packQuantity = $DoubleSmart->toVerbal($dRec->packQuantity);
    		$dRow->packPrice = $DoubleQ->toVerbal($dRec->packPrice);
			$dRow->amount = $DoubleQ->toVerbal($dRec->amount);

    		$b = clone $block;
    		$b->placeObject($dRow);
    		$b->removeBlocks();
    		$b->removePlaces();
    		$b->append2Master();
    	}

    	$tpl->removeBlocks();
    	$tpl->removePlaces();

    	return $tpl;
    }
    
    
    
    /**
     * Подготвя данните за отпечатване
     * 
     * @param core_Manager $clsInst
     * @param integer $id
     * 
     * @return string
     */
    public static function preparePrintView($clsInst, $id)
    {
    	expect($Inst = cls::get($clsInst));
    	$createdBy = $Inst->fetchField($id, 'createdBy');
    	
    	$isSystemUser = core_Users::isSystemUser();
    	if ($isSystemUser) {
    	    core_Users::cancelSystemUser();
    	}
    	
    	core_Users::sudo($createdBy);
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
    	
    	$str = '';
    	switch($Inst){
    		case $Inst instanceof sales_Sales:
    			//$str = self::getShipmentPreview($Inst, $id, $data);
    			break;
    		case $Inst instanceof store_ShipmentOrders:
    			//$str = self::getShipmentPreview($Inst, $id, $data);
    			break;
    		case $Inst instanceof sales_Invoices:
    			//$str = self::getInvPreview($id, $data);
    			break;
    	}
    	
    	if($str == ''){
    		// TODO - тестово
    		$str = "<c F b>{$clsInst->singleTitle} №{$id}/28.02.17" .
    		"<p><r32 =>" .
    		"<p b>1.<l3 b>Кисело мляко" .
    		"<p><l4>2.00<l12>х 0.80<r32>= 1.60" .
    		"<p b>2.<l3 b>Хляб \"Добруджа\"" . "<l f> | годност: 03.03" .
    		"<p><l4>2.00<l12>х 0.80<r32>= 1.60" .
    		"<p b>3.<l3 b>Минерална вода" .
    		"<p><l4>2.00<l12>х 0.80<r32>= 1.60" .
    		"<p><r32 =>" .
    		"<p><r29 F b>Общо: 34.23 лв.";
    	}
    	
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
