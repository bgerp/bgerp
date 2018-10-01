<?php


/**
 * Клас връщащ темата за блога
 *
 * @category  bgerp
 * @package   blogm
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class blogm_DefaultTheme extends core_Manager
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'blogm_ThemeIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Базова тема за блога';
    
    
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
        if(Mode::is('screenMode', 'wide')){
            return getTplFromFile('blogm/themes/default/Navigation.shtml');
        } else {
            return getTplFromFile('blogm/themes/default/NavigationNarrow.shtml');
        }
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
    public function getCommentFormLayout()
    {
        return getTplFromFile('blogm/themes/default/CommentForm.shtml');
    }
    
    
    /**
     * Променя изгледа на полетата от формата за добавяне на коментари
     */
    public function getCommentFormFieldsLayout()
    {
        return getTplFromFile('blogm/themes/default/CommentFormFields.shtml');
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
        if (Mode::is('screenMode', 'wide')) {
            $template = 'blogm/themes/default/BlogLayout.shtml';
        } else {
            $template = 'blogm/themes/default/BlogLayoutNarrow.shtml';
        }
        
        return $template;
    }
}
