<?php



/**
 * Клас 'cms_page_External' - Шаблон за публична страница
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Стандартна публична страница
 */
class cms_page_External extends core_page_Active
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
        $this->prepend(cms_Domains::getSeoTitle(), 'PAGE_TITLE');

        // Ако е логнат потребител
        if (!core_Users::haveRole('partner')) {
            
            // Абонираме за промяна на броя на нотификациите
            bgerp_Notifications::subscribeCounter($this);
            
            // Броя на отворените нотификации
            $openNotifications = bgerp_Notifications::getOpenCnt();
            
            // Ако имаме нотификации, добавяме ги към титлата и контейнера до логото
            if($openNotifications > 0) {
                
                // Добавяме броя в заглавието
                $this->append("({$openNotifications}) ", 'PAGE_TITLE');
            }
        }
        
        $this->push('cms/css/Wide.css', 'CSS');

        $this->push('js/overthrow-detect.js', 'JS');
        
        // Евентуално се кешират страници за не PowerUsers
        if(($expires = Mode::get('BrowserCacheExpires')) && !haveRole('user')) {
            $this->push('Cache-Control: public', 'HTTP_HEADER');
            $this->push('Expires: ' . gmdate("D, d M Y H:i:s", time() + $expires) . ' GMT', 'HTTP_HEADER');
            $this->push('-Pragma', 'HTTP_HEADER');
        } else {
            $this->push('Cache-Control: private, max-age=0', 'HTTP_HEADER');
            $this->push('Expires: -1', 'HTTP_HEADER');
        }

        $pageTpl = getFileContent('cms/tpl/Page.shtml');
                
        if(isDebug() && Request::get('Debug') && haveRole('debug')) {
            $pageTpl .= '[#Debug::getLog#]';
        }
        $this->replace(new ET($pageTpl), 'PAGE_CONTENT');
        
        // Обличаме кожата
        $skin = cms_Domains::getCmsSkin();
        if ($skin) {
            $skin->prepareWrapper($this);
        }
    	
        // Скрипт за генериране на min-height, според устройството
        jquery_Jquery::run($this, "setMinHeightExt();");
        
        // Добавка за разпознаване на браузъра
        $Browser = cls::get('log_Browsers');
        $this->append($Browser->renderBrowserDetectingCode(), 'BROWSER_DETECT');

        // Добавяме основното меню
        $this->replace(cms_Content::getMenu(), 'CMS_MENU');
        
        // Добавяме лейаута
        $this->replace(cms_Content::getLayout(), 'CMS_LAYOUT');
        
        // Ако е логнат потребител, който не е powerUser
        if(core_Users::haveRole('partner')){
        	$this->placeExternalUserData();
        }
    }

    
    /**
     * Подготвя данните за контрактора
     */
    private function placeExternalUserData()
    {
    	$nick = core_Users::getNick(core_Users::getCurrent());
        $user = ht::createLink($nick, array('cms_Profiles', 'single'), FALSE, 'ef_icon=img/16/user-black.png,title=Към профила');
        $logout = ht::createLink('Изход', array('core_Users', 'logout'), FALSE, 'ef_icon=img/16/logout.png,title=Изход от системата');

        $this->replace($user, 'USERLINK');
        $this->replace($logout, 'LOGOUT');
        $this->replace("class='cmsTopContractor'", 'TOP_CLASS');
    }
    
    
    /**
     * Прихваща изпращането към изхода, за да постави нотификации, ако има
     */
    static function on_Output(&$invoker)
    {
        // Генерираме хедъра и Линка към хедъра
        $invoker->appendOnce(cms_Feeds::generateHeaders(), 'HEAD');
        
        if (!Mode::get('lastNotificationTime')) {
            Mode::setPermanent('lastNotificationTime', time());    
        }

        // Добавяне на включвания външен код
        cms_Includes::insert($invoker);
    }
}
