<?php



/**
 * Клас 'social_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'social'
 *
 *
 * @category  bgerp
 * @package   social
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class social_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    function description()
    {
       
        
        $this->TAB('social_Sharings', 'Споделяне', 'ceo,social');
        $this->TAB('social_Followers', 'Следене', 'ceo,social');
                
        $this->title = 'Социални мрежи « Сайт';
        Mode::set('menuPage','Сайт:Социални мрежи');
    }
	/*function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));

        $tabs->TAB('dec_Declarations', 'Списък');
        $tabs->TAB('dec_Statements', 'Твърдения');
       
      

        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Декларации';
        
        Mode::set('pageMenu', 'Търговия');
		Mode::set('pageSubMenu', 'Продажби'); 
    }*/
}