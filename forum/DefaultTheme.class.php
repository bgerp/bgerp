<?php



/**
 * Клас връщащ темата за форума
 * 
 * @category  bgerp
 * @package   forum
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class forum_DefaultTheme extends core_Manager {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'forum_ThemeIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Базова тема за форума";
    
    
    /*
     * Имплементация на forum_ThemeIntf
     */
    
    
	/**
     * Инициализиране драйвъра
     */
    public static function getSbf()
    {
    	return 'forum/themes/default';
    }
}