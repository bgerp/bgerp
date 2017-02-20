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
    
    
    private static function getSalePreview($id, $data)
    {
    	$tpl = getTplFromFile('sales/tpl/sales/SalePrint.shtml');
    	
    	
    	$row = $data->row;
    	
    	
    	//bp(strip_tags('aaaa <br> bbbb'));
    	foreach (array('contragentName', 'MyCompany', 'MyAddress', 'closedDocuments', 'caseId', 'deliveryLocationId', 'bankAccountId', 'contragentAddress', 'contragentName') as $fld){
    		if(!empty($data->rec->{$fld}) && $row->{$fld} instanceof core_ET){
    			$row->{$fld} = $row->{$fld}->getContent();
    		}
    		
    		if(!empty($row->{$fld})){
				$row->{$fld} = strip_tags($row->{$fld});
			}
		}
		bp($row);
    	$tpl->placeObject($row);
    	
    	$count = 0;
    	$detailRecs = $data->sales_SalesDetails->recs;
    	$detailRows = $data->sales_SalesDetails->rows;
    	$Double = core_Type::getByName('double(decimals=2)');
    	$DoubleQ = core_Type::getByName('double(decimals=3)');
    	
    	$block = $tpl->getBlock('PRODUCT_BLOCK');
    	foreach ($detailRows as $id => $dRow){
    		$dRec = $detailRecs[$id];
    		$dRow->numb += 1;
    		$dRow->productId = cat_Products::getTitleById($dRec->productId);
    		$dRow->packQuantity = $DoubleQ->toVerbal($dRec->packQuantity);
    		$dRow->packPrice = $Double->toVerbal($dRec->packPrice);
    		$dRow->amount = $Double->toVerbal($dRec->amount);
    		
    		$b = clone $block;
    		$b->placeObject($dRow);
    		$b->removeBlocks();
    		$b->removePlaces();
    		$b->append2Master();
    	}
    	
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
    protected static function preparePrintView($clsInst, $id)
    {
    	expect($Inst = cls::get($clsInst));
    	
    	Mode::push('text', 'php');
    	$data = Request::forward(array('Ctr' => $Inst->className, 'Act' => 'single', 'id' => $id));
    	Mode::pop('text');
    	expect($data);
    	
    	$str = '';
    	switch($Inst){
    		case $Inst instanceof sales_Sales:
    			//$str = self::getSalePreview($id, $data);
    			break;
    		case $Inst instanceof store_ShipmentOrders:
    			//$str = self::getSoPreview($id, $data);
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
