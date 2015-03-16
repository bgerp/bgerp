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
class cms_page_External extends core_page_Active {
    
    public $interfaces = 'cms_page_WrapperIntf';

    /**
     * Конструктор за страницата по подразбиране
     * Тази страница използва internal layout, header и footer за да 
     * покаже една обща обвивка за съдържанието за вътрешни потребители
     */
    function cms_page_External()
    {
        // Конструктора на родителския клас
        $this->core_page_Active();
    	
        // Параметри от конфигурацията
        $conf = core_Packs::getConfig('core');
        $this->prepend(tr($conf->EF_APP_TITLE), 'PAGE_TITLE');

        // Ако е логнат потребител
        if (haveRole('user')) {
            
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

        $skin = cms_Domains::getCmsSkin();

        $this->push('css/default-theme.css', 'CSS');

        $this->push('js/overthrow-detect.js', 'JS');
        
        // Евентуално се кешират страници за не PowerUsers
        if(($expires = Mode::get('BrowserCacheExpires')) && !haveRole('powerUser')) {
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
        
        $skin->prepareWrapper($this);
        $this->replace($this->getHeaderImg(), 'HEADER_IMG');

        // ако нямаме частен пакет или в него няма специфична картинка, показваме името на приложението
        if ($this->haveToShowAppTitle()) {
        	$this->replace($conf->EF_APP_TITLE, 'CORE_APP_NAME');
    	}
    	
        // Скрипт за генериране на min-height, според устройството
        $this->append("runOnLoad(setMinHeightExt);", "JQRUN");
              
        // Добавка за разпознаване на браузъра
        $Browser = cls::get('core_Browser');
        $this->append($Browser->renderBrowserDetectingCode(), 'BROWSER_DETECT');

        // Добавяме основното меню
        $this->replace(cms_Content::getMenu(), 'CMS_MENU');
        
        // Добавяме лейаута
        $this->replace(cms_Content::getLayout(), 'CMS_LAYOUT');
    }

    
    /**
     * Прихваща изпращането към изхода, за да постави нотификации, ако има
     */
    static function on_Output(&$invoker)
    {
        // Генерираме хедъра и Линка към хедъра
        $invoker->appendOnce(cms_Feeds::generateHeaders(), 'HEAD');
        //$invoker->replace(cms_Feeds::generateFeedLink(), 'FEED');
        
        if (!Mode::get('lastNotificationTime')) {
            Mode::setPermanent('lastNotificationTime', time());    
        }

        // Добавяне на включвания външен код
        cms_Includes::insert($invoker);
    }

    /**
     * Връща пътя до картинката за главата на публичната страница
     */
    static function getHeaderImagePath()
    {
    	if(!Mode::is('screenMode', 'wide')) {
    		$screen = '-narrow';
    	} else {
    		$screen = '';
    	}
    	
    	$lg = '-' . cms_Content::getLang();
    	
    	$path = "cms/img/header{$screen}{$lg}.jpg";
    	
    	if(!getFullPath($path)) {
    		$path = "cms/img/header{$screen}.jpg";
    		if(!getFullPath($path)) {
    			$path = "cms/img/header.jpg";
    			if(!getFullPath($path)) {
    				if(Mode::is('screenMode', 'wide')) {
    					$path = "cms/img/bgERP.jpg";
    				} else {
    					$path = "cms/img/bgERP-small.jpg";
    				}
    			}
    		}
    	}
    	 
    	return $path;
    }

    
    /**
     * Връща картинката за главата на публичната страница
     */
    static function getHeaderImg() 
    {

        $skin = cms_Domains::getCmsSkin();
        
        $path = $skin->getHeaderImagePath();
 
        if(!$path) {
   		    $path = sbf(self::getHeaderImagePath(), '');
        }

        $conf = core_Packs::getConfig('core');
        
        $img = ht::createElement('img', array('src' => $path, 'alt' => tr($conf->EF_APP_TITLE), 'id' => 'headerImg'));
        
        return $img;
    }


    /**
     * Дали да показва името на приложението на заглавния имидж?
     */
    private function haveToShowAppTitle()
    {   
        $path = self::getHeaderImagePath();
        $conf = core_Packs::getConfig('core');
        $res = FALSE;
        if (!$conf->EF_PRIVATE_PATH) {
        	$res = TRUE;
        } else {
        	if (!file_exists($conf->EF_PRIVATE_PATH . "/" . $path)) {
        		$res = TRUE;
        	}
        }

        return $res;
    }

    
}
