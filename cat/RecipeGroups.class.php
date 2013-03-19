<?php



/**
 * Модел  Групи на рецептите
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_RecipeGroups extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = 'Групи';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, title, receipsCount, createdOn, createdBy';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, cat_Wrapper, cat_RecipeWrapper, plg_Printing, plg_Sorting';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от 
     * таблицата.
     * 
     * @see plg_RowTools
     * @var $string име на поле от този модел
     */
    var $rowToolsField = 'tools';

    
    /**
     * Кой може да чете
     */
    var $canRead = 'cat, admin';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'cat, admin';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('title', 'varchar(255)', 'caption=Име, mandatory');
    	$this->FLD('receipsCount', 'int', 'caption=Рецепти, input=none, value=0');
    	
    	$this->setDbUnique('title');
    }
}