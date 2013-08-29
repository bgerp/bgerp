<?php



/**
 * Клас връщащ темата за блога
 * 
 * @category  bgerp
 * @package   blogm
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blogm_DefaultTheme extends core_Manager {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'blogm_ThemeIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Базова тема за блога";
    
    
    /*
     * Имплементация на blogm_ThemeIntf
     */
    
    
	/**
     * Връща шаблона за статия от блога
     */
    public static function getArticleLayout()
    {
    	return getTplFromFile('blogm/themes/default/Article.shtml');
    }
    
    
	/**
     * Връща навигацията на блога
     */
    public static function getNavigationLayout()
    {
    	return getTplFromFile('blogm/themes/default/Navigation.shtml');
    }
    
    
	/**
     * Връща формата за търсене
     */
    public function getSearchFormLayout()
    {
    	return getTplFromFile('blogm/themes/default/SearchForm.shtml');
    }
    
    
	/**
     * Връща шаблона за страницата за търсене на статии
     */
    public function getBrowseLayout()
    {
    	return getTplFromFile('blogm/themes/default/Browse.shtml');
    }
    
    
	/**
     * Връща шаблона за коментарите
     */
    public function getCommentsLayout()
    {
    	return getTplFromFile('blogm/themes/default/Comment.shtml');
    }
    
    
	/**
     * Променя изгледа на формата за добавяне на коментари
     */
    public function getCommentFormLayout(core_Form &$form)
    {
    	$form->layout = getTplFromFile('blogm/themes/default/CommentForm.shtml');
        $form->fieldsLayout = getTplFromFile('blogm/themes/default/CommentFormFields.shtml');
    }
    
    
	/**
     * Връща дефолт стиловете на блога
     */
    public function getStyles()
    {
    	return 'blogm/themes/default/styles.css';
    }
    
    
	/**
     * Връща пътя пътя към файла който ще бъде обвивка на блога
     */
    public function getBlogLayout()
    {
    	return 'blogm/themes/default/BlogLayout.shtml';
    }
}