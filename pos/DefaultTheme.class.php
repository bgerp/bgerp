<?php



/**
 * Клас връщащ темата за pos-а
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pos_DefaultTheme extends core_Manager
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'pos_ThemeIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Базова тема за pos терминала';
    
    
    /*
     * Имплементация на pos_ThemeIntf
     */
    
    
    /**
     *Pодготвя стиловете за пос-a
     */
    public function getStyleFile()
    {
        return 'pos/themes/default/style.css';
    }
    
    
    /**
     * Подготвя стиловете за пос-a
     */
    public function getFavouritesTpl()
    {
        return getTplFromFile('pos/themes/default/Favourites.shtml');
    }
}
