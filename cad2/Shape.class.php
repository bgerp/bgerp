<?php

/**
 * Базов драйвер за драйвер на фигура
 *
 * @category  bgerp
 * @package   cad
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Базов драйвер за драйвер за фигура
 */
abstract class cad2_Shape extends core_BaseClass
{
		
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectSource = 'cad,ceo,admin';
	
	
	/**
	 * Интерфейси които имплементира
	 */
	public $interfaces = 'cad2_ShapeIntf';
	
 	
    /**
     * Дали може да се избира драйвера от текущия потребител
     */
    function canSelectDriver($userId = NULL)
    {
        return TRUE;
    }


    /**
     * Връща обект, поддъжащ интерфейса на класа cad2_SvgCanvas
     */
    function getCanvas()
    {
        $svg = cls::get('cad2_SvgCanvas');

        return $svg;
    }

	
}