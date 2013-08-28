<?php



/**
 * Клас връщащ темата за cms-а
 * 
 * @category  bgerp
 * @package   cms
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_DefaultTheme extends core_Manager {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'cms_ThemeIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Базова тема за публичното съдържание";
    
    
    /*
     * Имплементация на cms_ThemeIntf
     */
    
    
	/**
     * Инициализиране драйвъра
     */
    public static function getSbf()
    {
    	return 'cms/themes/default';
    }
}