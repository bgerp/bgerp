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
    var $title = "Стандартен дизайн";
    
    
    /*
     * Имплементация на cms_ThemeIntf
     */
    
    
	/**
     * Връща шаблона за статия от cms-а
     * @return файла на шаблона
     */
    public function getArticleLayout()
    {
    	return 'cms/themes/default/Articles.shtml';
    }
    
    
	/**
     * Връща шаблона за статия от cms-а  за мобилен изглед
     * @return файла на шаблона
     */
    public function getNarrowArticleLayout()
    {
    	return 'cms/themes/default/ArticlesNarrow.shtml';
    }
}