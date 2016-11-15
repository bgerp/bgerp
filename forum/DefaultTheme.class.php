<?php



/**
 * Клас за дефолт темата на форума
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
     * Връща изгледа на дъските
     * @return core_ET
     */
	public function getBoardsLayout()
    {
    	if(Mode::is('screenMode', 'narrow')){
    		$tpl = 'forum/themes/default/BoardsNarrow.shtml';
    	} else{
    		$tpl = 'forum/themes/default/Boards.shtml';
    	}
    	return getTplFromFile($tpl);
    }
    
    
    /**
     * Връща началната страница на форума
     * @return core_ET
     */
	public function getIndexLayout()
    {
    	return getTplFromFile('forum/themes/default/Index.shtml');
    }
    
    
    /**
     * Връща изгледа за единична тема
     * @return core_ET
     */
    public function getSingleThemeLayout()
    {
        if(Mode::is('screenMode', 'narrow')){
            return getTplFromFile('forum/themes/default/SingleThemeNarrow.shtml');
        } else{
            return getTplFromFile('forum/themes/default/SingleTheme.shtml');
        }
    }
    
    
    /**
     * Променя изгледа на формата за добавяне на нов коментар
     * @return core_ET
     */
	public function getPostFormLayout()
    {
    	$tpl = getTplFromFile('forum/themes/default/PostForm.shtml');
    	return $tpl->getBlock('FORM');
    }
    
    
	/**
     * Променя изгледа полетата от формата за добавяне на нов коментар
     * @return core_ET
     */
	public function getPostFormFieldsLayout()
    {
    	$tpl = getTplFromFile('forum/themes/default/PostForm.shtml');
    	return $tpl->getBlock('FORM_FIELDS');
    }
    
    
    /**
     * Връща шаблона на коментарите
     * @return core_ET
     */
	public function getCommentsLayout()
    {
        if(Mode::is('screenMode', 'narrow')){
            return getTplFromFile('forum/themes/default/CommentsNarrow.shtml');
        } else{
            return getTplFromFile('forum/themes/default/Comments.shtml');
        }
    }
    
    
    /**
     * Връща шаблона на страницата за показване на резултати
     * @return core_ET
     */
    public function getResultsLayout()
    {
    	return getTplFromFile('forum/themes/default/Results.shtml');
    }
    
    
    /**
     * Връща шаблона на страницата за добавяне на нова тема
     * @return core_ET
     */
	public function getAddThemeLayout()
    {
    	return getTplFromFile('forum/themes/default/New.shtml');
    }
    
    
    /**
     * Променя изгледа на формата за добавяне на нова тема
     */
    public function getAddThemeFormLayout(core_Form &$form)
    {
    	$formTpl = getTplFromFile('forum/themes/default/AddForm.shtml');
		$form->layout = $formTpl->getBlock("FORM");
        $form->fieldsLayout = $formTpl->getBlock("FORM_FIELDS");
    }
    
    
	/**
     * Връща изгледа на темата
     * @return core_ET
     */
    public function getThemeLayout()
    {
    	if(Mode::is('screenMode', 'narrow')){
    		$tpl = 'forum/themes/default/ThemesNarrow.shtml';
    	} else{
    		$tpl = 'forum/themes/default/Themes.shtml';
    	}
    	return getTplFromFile($tpl);
    }
    
    
	/**
     * Връща шаблона на браузването на една дъска
     * @return core_ET
     */
    public function getBrowseLayout()
    {
    	return getTplFromFile('forum/themes/default/Browse.shtml');
    }
    
    
	/**
     * Връща шаблона на формата за търсене
     * @return core_ET
     */
    public function getSearchFormLayout()
    {
    	return getTplFromFile('forum/themes/default/SearchForm.shtml');
    }
    
    
	/**
     * Връща пътя къмс тиловете на темата
     */
    public function getStyles()
    {
    	return 'forum/themes/default/styles.css';
    }
    
    
    /**
     * Връща картинка от темата
     * @param string $imgName - име на картинката
     * @param int $size - размер на картинката
     * Картинката трябва да е в папка 'img' на темата,
     * в подпапка '$size'
     */
    public function getImage($imgName, $size = '')
    {
    	$filePath = sbf("forum/themes/default/img/{$size}/{$imgName}", '');
    	return ht::createElement('img', array('src' => $filePath, 'width' => "{$size}px"));
    }
}