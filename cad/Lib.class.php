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
    	
    	$conf = core_Packs::getConfig('cad');
    	 
    	$strokeColor = $conf->CAD_PEN_COLOR;
    	$strokeWidth = $conf->CAD_PEN_STROKE_WIDTH;
    	
    	$canvas->startPath(
    			array(
    					'stroke' => $strokeColor,
    					'fill' => $fill,
    					'stroke-width' => $strokeWidth,
    					'fill-opacity' => $opacity		
    			)
    	);
    }
    
    
    /**
     * Създава молив - пунктир
     */
    static function getTransparentLinePen($canvas, $p)
    {
    	extract($p);
    	 
    	$conf = core_Packs::getConfig('cad');
    	 
    	$canvas->startPath(
    			array(
    					'fill' => $fill,
    					'fill-opacity' => $opacity
    			)
    	);
    }
    
    
    /**
     * Създава молив - пунктир
     */
    static function getInnerlinePen($canvas, $p)
    {
    	extract($p);
    	
    	$conf = core_Packs::getConfig('cad');
    	
    	$strokeWidth = $conf->CAD_PEN_STROKE_WIDTH;
    	$strokeColor = $conf->CAD_INLINE_PEN_COLOR;
    	
    	$canvas->startPath(
    			array(
    					'fill' => "none",
    					'stroke' => $strokeColor,
    					'stroke-width' => $strokeWidth,
    					'stroke-dasharray' => '1, 0.7'
    			)
    	);
    }
    
    
    /**
     * Създава молив - патерн за залепяне
     */
    static function getPatternLine($canvas, $p)
    {
    	extract($p);
  
    	$conf = core_Packs::getConfig('cad');
    	 
    	$strokeWidth = $conf->CAD_PEN_STROKE_WIDTH;
    	$strokeColor = $conf->CAD_PATTERN_PEN_COLOR;
    	
    	
    	$canvas->startPath(
    			array(
    					'fill' => "none",
    					'stroke' => $strokeColor,
    					'stroke-width' => 2*$strokeWidth,
    					'stroke-dasharray' => '2,1'
    			)
    	);
    }
    
    
    /**
     * Създава молив - прегъване
     */
    static function getFoldingLine($canvas, $p)
    {
    	extract($p);
    
    	$conf = core_Packs::getConfig('cad');
    
    	$strokeWidth = $conf->CAD_PEN_STROKE_WIDTH;
    	$strokeColor = $conf->CAD_FOLDING_PEN_COLOR;
    
    	$canvas->startPath(
    			array(
    					'fill' => "none",
    					'stroke' => $strokeColor,
    					'stroke-width' => 2*$strokeWidth,
    					'stroke-dasharray' => '1.5, 1'
    			)
    	);
    }
    
    
    /**
     * Създава молив - перфориране
     */
    static function getPerforationLine($canvas, $p)
    {
    	extract($p);
    
    	$conf = core_Packs::getConfig('cad');
    
    	$strokeWidth = $conf->CAD_PEN_STROKE_WIDTH;
    	$strokeColor = $conf->CAD_PEN_COLOR;
    
    	$canvas->startPath(
    			array(
    					'fill' => "none",
    					'stroke' => $strokeColor,
    					'stroke-width' => 2*$strokeWidth,
    					'stroke-dasharray' => '2, 8'
    			)
    	);
    }
    
    /**
     * Създава молив - измерителни линии
     */
    static function getMeasureLine($canvas, $p)
    {
    	extract ($p);
    	
    	$conf = core_Packs::getConfig('cad');
    	$strokeColor = $conf->CAD_MEASURE_PEN_COLOR;
    	$strokeWidth = $conf->CAD_PEN_STROKE_WIDTH;
    	
    	$canvas->startPath(
    			array(
    					'fill' => 'none',
    					'stroke' => $strokeColor,
    					'stroke-width' => $strokeWidth
    			)
    	);
    }

    
    /**
     * Създава прозорец по зададения път, който вътре е защрихован
     *
     * @param unknown $canvas
     * @param array $p
     */
    static function getHatchingHolder($canvas, $p)
    {
    	extract($p);
    
    	$canvas->openDefinitions();
    	
    	$canvas->openPattern(array("id"=>"diagonalHatch",
                "patternUnits"=>"userSpaceOnUse" ,
                "width"=>"50" ,
                "height"=>"50" 
    	));
    	
    	
    	$canvas->startPath(
    			array(
    					'stroke' => '#333333',
    					'stroke-width' => "0.1",
    			)
    	);
    	
    	$canvas->moveTo(0, 0, TRUE);
    	$canvas->lineTo(50, 50, TRUE);
    	
    	$canvas->closePattern();
    	$canvas->closeDefinitions();
    	
    	
    	$strokeColor = $conf->CAD_PEN_COLOR;
    	$strokeWidth = $conf->CAD_PEN_STROKE_WIDTH;
    
    	$canvas->startPath(
    			array(
    					'stroke' => "#888",
    					'fill' => "url(#diagonalHatch)",
    					'stroke-width' => $strokeWidth
    			)
    	);
    }
}