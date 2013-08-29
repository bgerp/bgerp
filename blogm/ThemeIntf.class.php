<?php



/**
 * Интерфейс за тема за блога
 *
 *
 * @category  bgerp
 * @package   blogm
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blogm_ThemeIntf
{
    /**
     * Връща шаблона за статия от блога
     */
    public static function getArticleLayout()
    {
    	return $this->class->getArticleLayout();
    }
    
    
	/**
     * Връща навигацията на блога
     */
    public static function getNavigationLayout()
    {
    	return $this->class->getNavigationLayout();
    }
    
    
	/**
     * Връща формата за търсене
     */
    public function getSearchFormLayout()
    {
    	return $this->class->getSearchFormLayout();
    }
    
    
	/**
     * Връща шаблона за страницата за търсене на статии
     */
    public function getBrowseLayout()
    {
    	return $this->class->getSearchBrowseLayout();
    }
    
    
	/**
     * Връща шаблона за коментарите
     */
    public function getCommentsLayout()
    {
    	return $this->class->getCommentsLayout();
    }
    
    
	/**
     * Променя изгледа на формата за добавяне на коментари
     */
    public function getCommentFormLayout(core_Form &$form)
    {
    	return $this->class->getCommentFormLayout($form);
    }
    
    
	/**
     * Връща дефолт стиловете на блога
     */
    public function getStyles()
    {
    	return $this->class->getStyles();
    }
    
    
	/**
     * Връща пътя пътя към файла който ще бъде обвивка на блога
     */
    public function getBlogLayout()
    {
    	return $this->class->getBlogLayout();
    }
}