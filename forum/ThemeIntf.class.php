<?php



/**
 * Интерфейс за тема на форума на системата
 *
 *
 * @category  bgerp
 * @package   forum
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class forum_ThemeIntf
{
    /**
     * Връща изгледа на дъските
     * @return core_ET
     */
    public function getBoardsLayout()
    {
        return $this->class->getBoardsLayout();
    }
    
    
    /**
     * Връща началната страница на форума
     * @return core_ET
     */
    public function getIndexLayout()
    {
        return $this->class->getIndexLayout();
    }
    
    
    /**
     * Връща изгледа за единична тема
     * @return core_ET
     */
    public function getSingleThemeLayout()
    {
        return $this->class->getSingleThemeLayout();
    }
    
    
    /**
     * Променя изгледа на формата за добавяне на нов коментар
     * @return core_ET
     */
    public function getPostFormLayout()
    {
        return $this->class->getPostFormLayout();
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
        return $this->class->getCommentsLayout();
    }
    
    
    /**
     * Връща шаблона на страницата за показване на резултати
     * @return core_ET
     */
    public function getResultsLayout()
    {
        return $this->class->getResultsLayout();
    }
    
    
    /**
     * Връща шаблона на страницата за добавяне на нова тема
     * @return core_ET
     */
    public function getAddThemeLayout()
    {
        return $this->class->getAddThemeLayout();
    }
    
    
    /**
     * Променя изгледа на формата за добавяне на нова тема
     */
    public function getAddThemeFormLayout(core_Form &$form)
    {
        return $this->class->getAddThemeFormLayout($form);
    }
    
    
    /**
     * Връща изгледа на темата
     * @return core_ET
     */
    public function getThemeLayout()
    {
        return $this->class->getThemeLayout();
    }
    
    
    /**
     * Връща шаблона на браузването на една дъска
     * @return core_ET
     */
    public function getBrowseLayout()
    {
        return $this->class->getBrowseLayout();
    }
    
    
    /**
     * Връща шаблона на формата за търсене
     * @return core_ET
     */
    public function getSearchFormLayout()
    {
        return $this->class->getSearchFormLayout();
    }
    
    
    /**
     * Връща пътя къмс тиловете на темата
     */
    public function getStyles()
    {
        return $this->class->getStyles();
    }
    
    
    /**
     * Връща картинка от темата
     * @param string $imgName - име на картинката
     * @param int    $size    - размер на картинката
     *                        Картинката трябва да е в папка 'img' на темата,
     *                        в подпапка '$size'
     */
    public function getImage($imgName, $size = '')
    {
        return $this->class->getImage($imgName, $size);
    }
}
