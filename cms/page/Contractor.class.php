<?php



/**
 * Клас 'cms_page_Contractor' - Външна страница за контрактори
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Външна страница за контрактори
 */
class cms_page_Contractor extends core_page_Active
{
    
    
    /**
     * 
     */
    public $interfaces = 'cms_page_WrapperIntf';
    

    /**
     * Подготовка на външната страница
     * Тази страница използва internal layout, header и footer за да 
     * покаже една обща обвивка за съдържанието за вътрешни потребители
     */
    function prepare()
    {
   	
        // Параметри от конфигурацията
        $conf = core_Packs::getConfig('core');
        $this->prepend(tr($conf->EF_APP_TITLE), 'PAGE_TITLE');

        $this->push('cms/css/Wide.css', 'CSS');
                
        $pageTpl = getFileContent('cms/tpl/PageContractor.shtml');

        $this->replace(new ET($pageTpl), 'PAGE_CONTENT');
        
        // Обличаме кожата
        $skin = cms_Domains::getCmsSkin();
        $skin->prepareWrapper($this);
    	
        // Скрипт за генериране на min-height, според устройството
        $this->append("runOnLoad(setMinHeightExt);", "JQRUN");

        // Добавяме лейаута
        $this->replace(cms_Content::getLayout(), 'CMS_LAYOUT');

        $nick = core_Users::getNick(core_Users::getCurrent());
        $user = ht::createLink($nick, array('colab_Profiles', 'single'), FALSE, 'ef_icon=img/16/user-black.png,title=Към профила');
        $logout = ht::createLink('Изход', array('core_Users', 'logout'), FALSE, 'ef_icon=img/16/logout.png,title=Изход от системата');

        $this->replace($user, 'USERLINK');
        $this->replace($logout, 'LOGOUT');
    }
}
