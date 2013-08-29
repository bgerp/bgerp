<?php



/**
 * Интерфейс за тема на cms-системата
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Източник на публично съдържание
 */
class cms_ThemeIntf
{
    /**
     * Връща шаблона за статия от cms-а за широк режим
     * @return файла на шаблона
     */
    function getArticleLayout()
    {
    	return $this->class->getArticleLayout();
    }
    
    
    /**
     * Връща шаблона за статия от cms-а  за мобилен изглед
     * @return файла на шаблона
     */
	function getNarrowArticleLayout()
    {
    	return $this->class->getNarrowArticleLayout();
    }
}