<?php


/**
 * class 'cad_Lib' - Библиотека с моливи и елементи
 *
 *
 * @category  extrapack
 * @package   bagshapes
 * @author    Donika Peneva <donyka111@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cad_Lib {
    
    /**
     * Задължителен интерфейс, който фигурите трябва да имат
     */
    var $interfaces = 'cad_ShapeIntf';
    
    
    /**
     * Наименование на фигурата
     */
    var $title = 'Библиотека с моливи';
    
    
    const EP_HANDLE_INNER_HEIGHT = 60;
    const EP_INNER_SPACE = 15;
    
    const EP_ARROW_HEIGHT = 10;
    const EP_ARROW_WIDTH = 20;
    const EP_ARROW_SIZE = 3;
    
    const EP_OVAL_HEIGHT = 35;
    const EP_OVAL_WIDTH = 20;
    
    const EP_OVAL_INNER_HEIGHT = 20;
    const EP_OVAL_INNER_WIDTH = 10;
    
    
    /**
     * Създава молив - контур
     * 
     * @param unknown $canvas
     * @param array $p
     */
    static function getOutlinePen($canvas, $p)
    {
    	extract($p);
    	
    	$conf = core_Packs::getConfig('bagshapes');
    	 
    	$stroke = $conf->EP_PEN_STROKE;
    	$strokeWidth = $conf->EP_PEN_STROKE_WIDTH;
    	
    	$canvas->startPath(
    			array(
    					'stroke' => $stroke,
    					'fill' => $fill,
    					'stroke-width' => $strokeWidth,
    					'fill-opacity' => $opacity		
    			)
    	);
    }
    
    /**
     * Създава молив - пунктир
     * 
     * @param unknown $canvas
     * @param array $p
     */
    static function getInnerlinePen($canvas, $p)
    {
    	extract($p);
    	
    	$conf = core_Packs::getConfig('bagshapes');
    	
    	$strokeWidth = $conf->EP_PEN_STROKE_WIDTH;
    	$strokeP = $conf->EP_INLINE_PEN_COLOR;
    	
    	$canvas->startPath(
    			array(
    					'fill' => "none",
    					'stroke' => $strokeP,
    					'stroke-width' => $strokeWidth,
    					'stroke-dasharray' => '6 4'
    			)
    	);
    }
    
    static function getPatternLine($canvas, $p)
    {
    	extract($p);
  
    	$conf = core_Packs::getConfig('bagshapes');
    	 
    	$strokeWidth = $conf->EP_PEN_STROKE_WIDTH;
    	$strokeP = $conf->EP_PATTERN_PEN_COLOR;
    	 
    	$canvas->startPath(
    			array(
    					'fill' => "none",
    					'stroke' => "#996666",
    					'stroke-width' => 2*$strokeWidth,
    					'stroke-dasharray' => '4 3'
    			)
    	);
    }
    
    static function getMeasureLine($canvas, $p)
    {
    	extract ($p);
    	
    	$conf = core_Packs::getConfig('bagshapes');
    	$strokeColor = $conf->EP_MEASURE_PEN_COLOR;
    	
    	$canvas->startPath(
    			array(
    					'fill' => 'none',
    					'stroke' => $strokeColor,
    					'stroke-width' => $strokeWidth)
    	);
    }
    
    
    /**
     * Изчертава тип за залепяне - стрела
     * 
     * @param unknown $canvas
     * @param array $p
     * @param float $handleH
     * @param float $handleW
     * @param float $handleS
     */
    static function drawArrow($canvas, $p = array())
    {
    	extract($p);
    /*	const EP_HANDLE_HEIGHT = 80; // от горна външна линия до ръба на торбичката
    	const EP_HANDLE_WIDTH = 140; // от лява външна линия до дясна външна линия
    	const EP_HANDLE_SIZE = 30; */
    	
    	$handleH = 80;
    	$handleW= 140;
    	$handleS = 30;
    	
    	$innerH = self::EP_HANDLE_INNER_HEIGHT;
    	$innerSpace = self::EP_INNER_SPACE;
    	$arrowH = self::EP_ARROW_HEIGHT;
    	$arrowW = self::EP_ARROW_WIDTH;
    	$arrowS = self::EP_ARROW_SIZE;
    	
    	self::getPatternLine($canvas, $p);
    	
    	$br=0;
    	for ($i=0; $i<3; $i++)
    	{
    		$canvas->moveTo($x+$w/2-$handleW/2+$handleS/2-$arrowW/2, $y+$handleH+$innerSpace+$arrowH, TRUE);
    		$canvas->lineTo($arrowW/2-$arrowS, -$arrowH+$arrowS);
    		
    		
    		$canvas->roundTo($arrowS, -$arrowS/2, 2*$arrowS, 0, $arrowS/2);
    		$canvas->lineTo($arrowW/2-$arrowS, $arrowH-$arrowS);
    		
    		
    		$canvas->lineTo(0, $arrowS);
    		$canvas->lineTo(-($arrowW/2-$arrowS), -($arrowH-$arrowS));
    		$canvas->roundTo(-$arrowS, -$arrowS/2, -2*$arrowS, 0, $arrowS/2);
    		
    		$canvas->lineTo(-($arrowW/2-$arrowS), ($arrowH-$arrowS));
    		
    		$canvas->closePath();
    		
    		$y += $arrowH;
    		
    	}
    	
    }
    
    
    /**
     * Чертае тип залепяне - овал
     * 
     * @param unknown $canvas
     * @param array $p
     * @param float $handleH
     * @param float $handleW
     * @param float $handleS
     */
    static function drawOval($canvas, $p = array())
    {
    	extract($p);
    	 

    	$handleH = 80;
    	$handleW= 140;
    	$handleS = 30;
    	
    	$innerH = self::EP_HANDLE_INNER_HEIGHT;
    	$innerSpace = self::EP_INNER_SPACE;
    	$ovalH = self::EP_OVAL_HEIGHT;
    	$ovalW = self::EP_OVAL_WIDTH;
    	$ovalInnerH = self::EP_OVAL_INNER_HEIGHT;
    	$ovalInnerW = self::EP_OVAL_INNER_WIDTH;
    	
    	
    	self:: getPatternLine($canvas, $p);
    	
    	$canvas->moveTo($x+$w/2-$handleW/2+$handleS/2-$ovalW/2, $y+$handleH+$innerSpace+$ovalH, TRUE);
    	$canvas->lineTo(0, -($ovalH/2-$ovalW/2));
    	$canvas->roundTo(0, -$ovalW/2, $ovalW/2, -$ovalW/2, $ovalW/2);
    	$canvas->roundTo($ovalW/2, 0, $ovalW/2, $ovalW/2, $ovalW/2);
    	$canvas->lineTo(0, $ovalH/2-$ovalW/2);
    	$canvas->roundTo(0, $ovalW/2, -$ovalW/2, $ovalW/2, $ovalW/2);
    	$canvas->roundTo(-$ovalW/2, 0, -$ovalW/2, -$ovalW/2, $ovalW/2);
    	
    	$canvas->moveTo($x+$w/2-$handleW/2+$handleS/2-$ovalInnerW/2, $y+$handleH+$innerSpace+$ovalH, TRUE);
    	$canvas->lineTo(0, -($ovalInnerH/2-$ovalInnerW/2));
    	
    	
    	/*
    	$canvas->roundTo($ovalW/2, 0, $ovalW/2, $ovalW/2, $ovalW/2);    	
    	$canvas->roundTo($ovalW/2, 0, $ovalW/2, $ovalW/2, $ovalW/2);
    	
    	
    	$canvas->roundTo(-$ovalW/2, 0, -$ovalW/2, -$ovalW/2, $ovalW/2);
    	$canvas->lineTo(0, -($ovalH/2-$ovalW/2));
    	
    	$canvas->moveTo($x+$w/2-$handleW/2+$handleS/2-$ovalInnerW/2, $y+$handleH+$innerSpace+$ovalInnerH, TRUE);
    	$canvas->lineTo(0, -($ovalInnerH/2-$ovalInnerW/2));
    	$canvas->roundTo(0, -$ovalInnerW/2, $ovalInnerW/2, -$ovalInnerW/2, $ovalInnerW/2);
    	$canvas->roundTo($ovalInnerW/2, 0, $ovalInnerW/2, $ovalInnerW/2, $ovalInnerW/2);
    	$canvas->lineTo(0, $ovalInnerH-2*$ovalInnerW);
    	$canvas->roundTo(0, $ovalInnerW/2, -$ovalInnerW/2, $ovalInnerW/2, $ovalInnerW/2);
    	$canvas->roundTo(-$ovalInnerW/2, 0, -$ovalInnerW/2, -$ovalInnerW/2, $ovalInnerW/2);
    	$canvas->lineTo(0, -($ovalInnerH/2-$ovalInnerW/2));
    */
    }
}