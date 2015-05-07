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
        
        // Обличаме кожата
        $skin = cms_Domains::getCmsSkin();
        $skin->prepareWrapper($this);
    	
        // Скрипт за генериране на min-height, според устройството
        $this->append("runOnLoad(setMinHeightExt);", "JQRUN");
              
        // Добавка за разпознаване на браузъра
        $Browser = cls::get('core_Browser');
        $this->append($Browser->renderBrowserDetectingCode(), 'BROWSER_DETECT');

        // Добавяме основното меню
        $this->replace(cms_Content::getMenu(), 'CMS_MENU');
        
        // Добавяме лейаута
        $this->replace(cms_Content::getLayout(), 'CMS_LAYOUT');
        
        $bulletinJsUrl = marketing_Bulletin::getJsLink();
        if (trim($bulletinJsUrl)) {
            $this->push($bulletinJsUrl, 'JS');
            $this->push('marketing/css/styles.css', 'CSS');

            $confBull = core_Packs::getConfig('marketing');
            $bg = $confBull->MARKETING_BULLETIN_BACKGROUND;

            if ($bg) {
                $css .= ".bulletinReg{ background-color: {$bg}; }";
            }

            $textColor = $confBull->MARKETING_BULLETIN_TEXTCOLOR;
            if($textColor) {
                $css .= ".bulletinReg, .bulletinHolder h2, .successText { color: {$textColor};  }";
            }

            $btnColor =  ltrim($confBull->MARKETING_BULLETIN_BUTTONCOLOR, "#");

            if($btnColor) {
                $darkBtnColor = phpcolor_Adapter::changeColor($btnColor, 'lighten', 15);
                $shadowBtnColor = phpcolor_Adapter::changeColor($darkBtnColor, 'mix', 1, '#444');

                $css .= ".push_button {
                    text-shadow:-1px -1px 0 #{$shadowBtnColor};
                    background: #{$btnColor} !important;
                    border:1px solid #{$shadowBtnColor} !important;
                    background-image:-webkit-linear-gradient(top, #{$darkBtnColor}, #{$btnColor}) !important;
                    background-image:-moz-linear-gradient(top, #{$darkBtnColor}, #{$btnColor}) !important;
                    background-image:-ms-linear-gradient(top, #{$darkBtnColor}, #{$btnColor}) !important;
                    background-image:-o-linear-gradient(top, #{$darkBtnColor}, #{$btnColor}) !important;
                    background-image:linear-gradient(top, #{$darkBtnColor}, #{$btnColor}) !important;
                    -webkit-border-radius:5px;
                    -moz-border-radius:5px;
                    border-radius:5px;
                    -webkit-box-shadow:0 1px 0 rgba(255, 255, 255, .5) inset, 0 -1px 0 rgba(255, 255, 255, .1) inset, 0 4px 0 #{$shadowBtnColor}, 0 4px 2px rgba(0, 0, 0, .5) !important;
                    -moz-box-shadow:0 1px 0 rgba(255, 255, 255, .5) inset, 0 -1px 0 rgba(255, 255, 255, .1) inset, 0 4px 0 #{$shadowBtnColor}, 0 4px 2px rgba(0, 0, 0, .5) !important;
                    box-shadow:0 1px 0 rgba(255, 255, 255, .5) inset, 0 -1px 0 rgba(255, 255, 255, .1) inset, 0 4px 0 #{$shadowBtnColor}, 0 4px 2px rgba(0, 0, 0, .5) !important;
                }
                .push_button:hover {
                    background: #48C6D4 !important;
                    background-image:-webkit-linear-gradient(top, #{$btnColor}, #{$darkBtnColor}) !important;
                    background-image:-moz-linear-gradient(top, #{$btnColor}, #{$darkBtnColor}) !important;
                    background-image:-ms-linear-gradient(top,  #{$btnColor}, #{$darkBtnColor}) !important;
                    background-image:-o-linear-gradient(top,  #{$btnColor}, #{$darkBtnColor}) !important;
                    background-image:linear-gradient(top,  #{$btnColor}, #{$darkBtnColor}) !important;
                }";

                if(phpcolor_Adapter::checkColor( $btnColor, 'light'))  {
                    $css .= ".push_button { color: #111 !important; text-shadow: none }" ;
                }
            }

            if($css) {
                $this->append($css, 'STYLES');
            }
        }
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

}
