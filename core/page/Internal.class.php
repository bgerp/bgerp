<?php



/**
 * Клас 'page_Internal' - Шаблон за страница на приложението, видима за вътрешни потребители
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  bgerp
 * @package   page
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Стандартна вътрешна страница
 */
class core_page_Internal extends core_page_Active
{
    
    
    /**
     * 
     */
    public $interfaces = 'core_page_WrapperIntf';
    
    
    /**
     * Конструктор за страницата по подразбиране
     * Тази страница използва internal layout, header и footer за да 
     * покаже една обща обвивка за съдържанието за вътрешни потребители
     */
    function prepare()
    {
        
        bgerp_Notifications::subscribeCounter($this);
        
        // Стилове за темата
        $this->push('css/default-theme.css','CSS');

		// Добавяне на стил само за дефоултния андроидски браузър
        $browserInfo = Mode::get("getUserAgent");
        if(strPos($browserInfo, 'Mozilla/5.0') !== FALSE && strPos($browserInfo,'Android') !== FALSE && 
        strPos($browserInfo, 'AppleWebKit') !== FALSE && strPos($browserInfo,'Chrome') === FALSE){
        	  $this->append("
		       select {padding-left: 0.2em !important;}
		         ", "STYLES");
        }
        
        // Добавяне на базовия JS
        $this->push('js/overthrow-detect.js', 'JS');
        
        // Хедъри за контрол на кеша
        $this->push('Cache-Control: private, max-age=0', 'HTTP_HEADER');
        $this->push('Expires: ' . gmdate("D, d M Y H:i:s", time() + 3600) . ' GMT', 'HTTP_HEADER');
        
        // Мета данни
        $this->prepend("\n<meta name=\"robots\" content=\"noindex,nofollow\">", 'HEAD');
        $this->prepend("\n<meta name=\"format-detection\" content=\"telephone=no\">", 'HEAD');
        $this->prepend("\n<meta name=\"google\" content=\"notranslate\">", 'HEAD');

        // Добавяне на титлата на страницата
    	$conf = core_Packs::getConfig('core');
        $this->prepend($conf->EF_APP_TITLE, 'PAGE_TITLE');
        

        // Ако сме в широк изглед извикваме функцията за мащабиране
        if(Mode::is('screenMode', 'wide')){
        	$this->append("scaleViewport();", "START_SCRIPTS");
        } else {
        	jquery_Jquery::run($this, "checkForElementWidthChange();");
        }
        
        // Опаковките и главното съдържание заемат екрана до долу
        jquery_Jquery::run($this, "setMinHeight();");

        // Вкарваме съдържанието
        $this->replace(self::getTemplate(), 'PAGE_CONTENT');
    }


    /**
     * Връща шаблона за страницата
     */
    static function getTemplate()
    {
        return new ET("<div id='main-container' class='clearfix21 main-container'><div id=\"framecontentTop\"  class=\"container\">" .
            "[#core_page_Internal::renderMenu#]" .
            "</div>" .
            "<div id=\"maincontent\"><div>" .
            "<div id='statuses'>[#STATUSES#]</div>" .
            "[#PAGE_CONTENT#]" .
            "</div></div>" .
            "<div id=\"framecontentBottom\" class=\"container\">" .
            "[#core_page_Internal::getFooter#]" .
            "</div></div>");
    }


    /**
     * Рендира основното меню на страницата
     */
    static function renderMenu()
    {
        if(Mode::is('screenMode', 'narrow')) {
            $tpl = new ET("
                <div id='mainMenu'>
                     <div class='menuRow clearfix21'><img class='favicon' src=" . sbf("img/favicon.ico") . " alt=''>[#MENU_ROW#]<!--ET_BEGIN NOTIFICATIONS_CNT--><div id='notificationsCnt'>[#NOTIFICATIONS_CNT#]</div><!--ET_END NOTIFICATIONS_CNT--></div>
                </div>
                <!--ET_BEGIN SUB_MENU--><div id=\"subMenu\">[#SUB_MENU#]</div>\n<!--ET_END SUB_MENU-->");
        } else {
            $tpl = new ET("
                <div id='mainMenu'>
                    <div style='float:right;'><!--ET_BEGIN NOTIFICATIONS_CNT--><span id='notificationsCnt'>[#NOTIFICATIONS_CNT#]</span><!--ET_END NOTIFICATIONS_CNT-->[#logo#]</div>
                    <div class=\"menuRow\">[#MENU_ROW1#]</div>
                    <div class=\"menuRow\" style=\"margin-top:3px; margin-bottom:3px;\">[#MENU_ROW2#]</div>
                    <div class=\"menuRow\">[#MENU_ROW3#]</div>                   
                </div>
                <div class='clearfix'></div>
                <!--ET_BEGIN SUB_MENU--><div id=\"subMenu\">[#SUB_MENU#]</div>\n<!--ET_END SUB_MENU-->");
            
            $img = ht::createElement('img', array('src' => sbf('img/bgerp.png', ''), 'alt' => '', 'style' => 'border:0; border-top:5px solid transparent;'));
            
            $logo = ht::createLink($img, array('bgerp_Portal', 'Show'));
            
            $tpl->replace($logo, 'logo');
        }
                
        self::placeMenu($tpl);
        
        // Извличаме броя на нотификациите за текущия потребител
        $openNotifications = bgerp_Notifications::getOpenCnt();
        $url  = toUrl(array('bgerp_Portal', 'Show'));
        $attr = array('id' => 'nCntLink');
        
        // Ако имаме нотификации, добавяме ги към титлата и контейнера до логото
        if($openNotifications > 0) {
            $attr['class'] = 'haveNtf';
            $tpl->append("({$openNotifications}) ", 'PAGE_TITLE');
        } else {
            $attr['class'] = 'noNtf';
        }
        $nLink = ht::createLink("{$openNotifications}", $url, NULL, $attr);
        $tpl->replace($nLink, 'NOTIFICATIONS_CNT');
        
        return $tpl;
    }


    /**
     * Поставя елементите на менюто в шаблона
     */
    static function placeMenu($tpl)
    {

        $menuObj = bgerp_Menu::getMenuObject();
        
        $active = bgerp_Menu::getActiveItem($menuObj);
        
        // До тук имаме определени два списъка $menus (с главните менюта) и $subMenus (с под-менютата);
        list($menus, $subMenus) = bgerp_Menu::prepareMenu($menuObj, $active);
 
        if(Mode::is('screenMode', 'narrow')) {
            
            $conf = core_Packs::getConfig('core');
            
            $menuLink = ht::createLink($conf->EF_APP_TITLE, array('bgerp_Menu', 'Show'));
            
            $tpl->append($menuLink , "MENU_ROW");
            
            if(count($menus)) {
                foreach($menus as $key => $rec) {
                    if($rec->state == 3) {
                        $tpl->append("&nbsp;»&nbsp;", "MENU_ROW");
                        $link = ht::createLink($rec->menuTr, array($rec->ctr, $rec->act));
                        $tpl->append($link, "MENU_ROW");
                    }
                }
            }
            
            if(count($subMenus)) {
                $notFirst = FALSE;
                
                foreach($subMenus as $key => $rec) {
                    if($notFirst) {
                        $tpl->append("<span style='color:#ccc;font-size:0.8em;vertical-align: 20%;'>&nbsp;|&nbsp;</span>", 'SUB_MENU');
                    }
                    $link = bgerp_Menu::createLink($rec->subMenuTr, $rec);
                    $tpl->append($link, 'SUB_MENU');
                    $notFirst = TRUE;
                }
            }
            jquery_Jquery::run($tpl, "removeNarrowScroll();");
        } else {
            // Ако сме в широк формат
            // Отпечатваме менютата
            if(count($menus)) {
                foreach($menus as $key => $rec) {
                    $link = bgerp_Menu::createLink($rec->menuTr, $rec, TRUE);
                    $row = 'MENU_ROW' . $rec->row;
                    
                    if($notFirstInFor[$rec->row]) {
                        $tpl->append("\n . ", $row);
                    } else {
                        $tpl->append("\n»&nbsp;", $row);
                    }
                    
                    $tpl->append($link, 'MENU_ROW' . $rec->row);
                    
                    $notFirstInFor[$rec->row] = TRUE;
                }
            }
            
            if(count($subMenus)) {
                foreach($subMenus as $key => $rec) {
                    $link = bgerp_Menu::createLink($rec->subMenuTr, $rec);
                    $tpl->append("&nbsp;", 'SUB_MENU');
                    $tpl->append($link, 'SUB_MENU');
                }
            }
        }
        
    }

    
    /**
     * Конструктор на шаблона
     */
    public static function getFooter()
    {
        $tpl = new ET();

        $nick = Users::getCurrent('nick');
        if(EF_USSERS_EMAIL_AS_NICK) {
            list($nick,) = explode('@', $nick);
        }

        $isGet = strtoupper($_SERVER['REQUEST_METHOD']) == 'GET';
        
        $nick = type_Nick::normalize($nick);
        
        if(Mode::is('screenMode', 'narrow')) {
            if($nick) {
                $tpl->append(ht::createLink(tr("Изход"), array('core_Users', 'logout'), FALSE, array('title' => "Изход на |*" . $nick)));
            }
                        
            if($isGet) {
                $tpl->append("&nbsp;<small>|</small>&nbsp;");
                $tpl->append(ht::createLink(tr("Широк"), array('log_Browsers', 'setWideScreen', 'ret_url' => TRUE), FALSE, array('title' => " Превключване на системата в десктоп режим")));

                // Добавяме превключване между езиците
                $tpl->append(self::getLgChange());
            }

            $tpl->append("&nbsp;<small>|</small>&nbsp;");
            $tpl->append(ht::createLink(dt::mysql2verbal(dt::verbal2mysql(), 'H:i'), array('Index', 'default'), NULL, array('title' => 'Страницата е заредена на|*' . ' ' . dt::mysql2verbal(dt::verbal2mysql(), 'd-m H:i:s'))));
        } else {
            if($nick) {
                $tpl->append(ht::createLink("&nbsp;" . tr('изход') . ":" . $nick, array('core_Users', 'logout'), FALSE, array('title' => "Прекъсване на сесията")));
                $tpl->append('&nbsp;<small>|</small>');
            }
            
            $tpl->append('&nbsp;');
            $tpl->append(dt::mysql2verbal(dt::verbal2mysql()));
            
            if($isGet) {
                $tpl->append("&nbsp;<small>|</small>&nbsp;");
                $tpl->append(ht::createLink(tr("Тесен"), array('log_Browsers', 'setNarrowScreen', 'ret_url' => TRUE), FALSE, array('title' => "Превключване на системата в мобилен режим")));
            
                // Добавяме превключване между езиците
                $tpl->append(self::getLgChange());
            }
            // Добавяме кода, за определяне параметрите на браузъра
            $Browser = cls::get('log_Browsers');
            $tpl->append($Browser->renderBrowserDetectingCode(), 'BROWSER_DETECT');

            // Добавя бутон за калкулатора
            $tpl->append('&nbsp;<small>|</small>&nbsp;');
            $tpl->append(calculator_View::getBtn());
            
            if(isDebug()) {
            	$tpl->append('&nbsp;<small>|</small>&nbsp;<a href="#wer" onclick="toggleDisplay(\'debug_info\')">Debug</a>');
            }
        }
        
        $conf = core_Packs::getConfig('help');
        
        if($conf->BGERP_SUPPORT_URL && strpos($conf->BGERP_SUPPORT_URL, '//') !== FALSE) {
            $email = email_Inboxes::getUserEmail();
            if(!$email) {
                $email = core_Users::getCurrent('email');
            }
            list($user, $domain) = explode('@', $email);
            $currUrl = getCurrentUrl();
            $ctr = $currUrl['Ctr'];
            $act = $currUrl['Act'];
            $sysDomain = $_SERVER['HTTP_HOST'];
            $name = core_Users::getCurrent('names');
            $img = sbf('img/supportmale-20.png', '');
            $btn = "<input title='Сигнал за бъг, въпрос или предложение' alt='Сигнал за бъг' class='bugReport' type=image src='{$img}' name='Cmd[refresh]'>";
            $form = new ET("<form style='display:inline' method='post' target='_blank' onSubmit=\"prepareBugReport(this, '{$user}', '{$domain}', '{$name}', '{$ctr}', '{$act}', '{$sysDomain}');\" action='" . $conf->BGERP_SUPPORT_URL . "'>[#1#]</form>", $btn);
            $tpl->append('&nbsp;<small>|</small>&nbsp;');
            $tpl->append($form);
        }
        
        if(isDebug() && Mode::is('screenMode', 'wide')) {
        	$tpl->append(new ET("<div id='debug_info' style='margin:5px; display:none;'>
                                     Време за изпълнение: [#DEBUG::getExecutionTime#]
                                     [#Debug::getLog#]</div>"));
        }

        return $tpl;
    }


    /**
     * Добавя хипервръзки за превключване между езиците на интерфейса
     */
    static function getLgChange()
    {
        $tpl = new ET();

        $langArr = core_Lg::getLangs();
        $cl      = core_Lg::getCurrent();
        unset($langArr[$cl]);
 
        if(count($langArr)) {
            foreach($langArr as $lg => $title) {
                $url = toUrl(array('core_Lg', 'Set', 'lg' => $lg, 'ret_url' => TRUE));
                $attr = array('href' => $url, 'title' => $title);
                $lg{0} = strtoupper($lg{0});
                $tpl->append('&nbsp;<small>|</small>&nbsp;');
                $tpl->append(ht::createElement('a', $attr, $lg));
            }
        }

        return $tpl;
    }


    
    /**
     * Прихваща изпращането към изхода, за да постави нотификации, ако има
     */
    static function on_Output(&$invoker)
    {
        if (!Mode::get('lastNotificationTime')) {
            Mode::setPermanent('lastNotificationTime', time());
        }
    }
} 