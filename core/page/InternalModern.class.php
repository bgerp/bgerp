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
 * @title     Модерна вътрешна страница
 */
class core_page_InternalModern extends core_page_Active
{
    
    
    /**
     * 
     */
    public $interfaces = 'core_page_WrapperIntf';
    
    
    /**
     * Подготовка на шаблона за вътрешна страница
     * Тази страница използва internal layout, header и footer за да 
     * покаже една обща обвивка за съдържанието за вътрешни потребители
     */
    function prepare()
    {
        
        bgerp_Notifications::subscribeCounter($this);
        
        // Стилове за темата
        $this->push('css/default-theme.css','CSS');
        $this->push('css/new-design.css','CSS');

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
        $this->push('js/jPushMenu.js', 'JS');
        $this->push('js/modernTheme.js', 'JS');
        
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
        $this->prepend(' modern-theme', 'BODY_CLASS_NAME');

        // Забраняваме мащабирането
        $this->append("disableScale();", "START_SCRIPTS");

        // Вкарваме съдържанието
        $this->replace(self::getTemplate(), 'PAGE_CONTENT');

        // Извличаме броя на нотификациите за текущия потребител
        $openNotifications = bgerp_Notifications::getOpenCnt();
        $url  = toUrl(array('bgerp_Portal', 'Show', '#' => 'notificationsPortal'));

        $attr = array('id' => 'nCntLink', 'title' => 'Неразгледани известия', 'onClick' => "openCurrentTab();");

        // Ако имаме нотификации, добавяме ги към титлата и контейнера до логото
        if($openNotifications > 0) {
            $attr['class'] = 'haveNtf';
            $this->append("({$openNotifications}) ", 'PAGE_TITLE');
        } else {
            $attr['class'] = 'noNtf';
        }
        $nLink = ht::createLink("{$openNotifications}", $url, NULL, $attr);
        $this->replace($nLink, 'NOTIFICATIONS_CNT');
    }


    /**
     * Връща шаблона за страницата
     */
    static function getTemplate()
    {
        debug::log('StartTemplate');
        
        $data = new stdClass();

    	if (isset($_COOKIE['menuInfo']) && $_COOKIE['menuInfo']) {
    		$openMenuInfo = $_COOKIE['menuInfo'];
    		$winWidth = intval($openMenuInfo);
    		$data->mainContainerClass = '';
    		$data->pin = '';
            $data->openLeftBtn = '';
            $data->openLeftMenu = '';
		    $data->openRightBtn = '';
		    $data->openRightMenu = '';
		    $data->pinned = '';

    		//в зависимост от стойсността на разбираме кои менюта са било отворени
			if(($winWidth > 700) && strrpos($openMenuInfo, "l") !== FALSE) {
				$data->openLeftBtn = ' menu-active ';
				$data->openLeftMenu = ' sidemenu-open ';
				$data->mainContainerClass .= ' sidemenu-push-toright ';
			}
			if(($winWidth > 700) && strrpos($openMenuInfo, "r") !== FALSE) {
				$data->openRightBtn = ' menu-active ';
				$data->openRightMenu = ' sidemenu-open';
				$data->mainContainerClass .= ' sidemenu-push-toleft ';
				$data->pin = ' hidden ';
			} else {
			    $data->pinned = ' hidden ';
			}
    	} else {
    	    $data->pinned = ' hidden ';
    	}
    	$data->avatar = avatar_Plugin::getImg(core_Users::getCurrent(), NULL, 28);
    	
        $key = 'intrnalModernTpl-debug';

        if(($tpl = core_Cache::get($key, 'page'))  === FALSE) {
            $menuImg = ht::createElement('img', array('src' => sbf('img/menu.png', ''), 'class' => 'menuIcon', 'alt' => 'menu'));
            $pinImg = ht::createElement('img', array('src' => sbf('img/pin.png', ''), 'class' => "menuIcon pin [#pin#]", 'alt' => 'pin'));
            $searchImg = ht::createElement('img', array('src' => sbf('img/32/search.png', ''), 'alt' => 'search', 'width' => '20','height' => '20'));
            $pinnedImg = ht::createElement('img', array('src' => sbf('img/pinned.png', ''), 'class' => "menuIcon pinned [#pinned#]", 'alt' => 'unpin'));
            
            $pinImg = str_replace("&#91;", '[', "$pinImg");
            $pinnedImg = str_replace("&#91;", '[', "$pinnedImg");

            // Задаваме лейаута на страницата
            $header = "<div style='position: relative'>
                                <a id='nav-panel-btn' class='fleft btn-sidemenu btn-menu-left push-body [#openLeftBtn#]'>". $menuImg ."</a>
                                <span class='fleft '>
                                    <span class='menu-options search-options'>" . $searchImg .
                                         "<span class='menu-holder'>
                                                [#SEARCH_INPUT#]
                                                [#SEARCH_LINK#]
                                            </span>
                                        </span>
                                </span>
                                <span class='center-block'>
                                    <span class='logoText'>[#PORTAL#]</span><span class='notificationsCnt'>[#NOTIFICATIONS_CNT#]</span>
                                </span>
                                <a id='fav-panel-btn' class='fright btn-sidemenu btn-menu-right push-body [#openRightBtn#]'>". $pinImg . $pinnedImg . "</a>
                                <div class='fright'>
                                        <div class='menu-options user-options'>
                                             [#avatar#]
                                             <div class='menu-holder'>
                                                [#USERLINK#]
                                                [#CHANGE_MODE#]
                                                [#LANG_CHANGE#]
                                                [#SIGNAL#]
                                                [#DEBUG_BTN#]
                                                <div class='divider'></div>
                                                [#SIGN_OUT#]
                                            </div>
                                        </div>
                                </div>
                            <div class='clearfix21'></div>
                            </div>  " ;
             
            $tpl = new ET("<div id='main-container' class='clearfix21 [#HAS_SCROLL_SUPPORT#] [#mainContainerClass#]' style='top: 50px; position: relative'>" .
                    "<div id=\"framecontentTop\"  class=\"headerBlock\"><div class='inner-framecontentTop'>" . $header . "</div></div>" .
                    "<!--ET_BEGIN NAV_BAR--><div id=\"navBar\">[#NAV_BAR#]</div>\n<!--ET_END NAV_BAR--><div class='clearfix' style='min-height:9px;'></div>" .
                    "<div id='statuses'>[#STATUSES#]</div>" .
                    "[#PAGE_CONTENT#]" .
                    "[#DEBUG#]</div>".
                    "<div id='nav-panel' class='sidemenu sidemenu-left [#openLeftMenu#]'>[#core_page_InternalModern::renderMenu#]</div>".
                    "<div id='fav-panel' class='sidemenu sidemenu-right [#openRightMenu#]'><div class='inner-fav-panel'>[#bgerp_Bookmark::renderBookmarks#]</div></div>"

            );

            
            // Опаковките и главното съдържание заемат екрана до долу
            jquery_Jquery::run($tpl, "slidebars();");
            jquery_Jquery::run($tpl, "scrollToHash();");
            
            if(Mode::is('screenMode', 'narrow')){
                jquery_Jquery::run($tpl, "checkForElementWidthChange();");
                jquery_Jquery::run($tpl, "sumOfChildrenWidth();");
                jquery_Jquery::run($tpl, "removeNarrowScroll();");
            }
            
            // Добавяме кода, за определяне параметрите на браузъра
            $Browser = cls::get('log_Browsers');
            $tpl->append($Browser->renderBrowserDetectingCode(), 'BROWSER_DETECT');

            core_Cache::set($key, 'page', $tpl, 10000);
        }
        
        if(isDebug()) {
            $tpl->prepend(new ET("<div id='debug_info' style='margin:5px; display:none;overflow-x: hidden'>
                                         Време за изпълнение: [#DEBUG::getExecutionTime#]
                                         [#Debug::getLog#]</div>"), "DEBUG");
        }

        $tpl->placeObject($data);

        debug::log('EndTemplate');

        return $tpl;
    }
        
    
	/**
     * Рендира основното меню на страницата
     */
    static function renderMenu()
    {
          $tpl = new ET("
                    <ul>
                    [#MENU_ROW#]
                    </ul>");
        
        
         
        self::placeMenu($tpl);
        
        self::addLinksToMenu($tpl);

        return $tpl;
    }


    /**
     * Поставя елементите на менюто в шаблона
     */
    static function placeMenu($tpl)
    {

        $menuObj = bgerp_Menu::getMenuObject();
        
        if (is_array($menuObj)) {
            uasort($menuObj, function($a, $b) { return($a->order > $b->order); });
        }
 
        $active = bgerp_Menu::getActiveItem($menuObj);
        
        list($aMainMenu, $aSubMenu) = explode(':', $active);

        $html = '';
        $lastMenu = '';
 
        if (($menuObj) && (count($menuObj))) {
            foreach($menuObj as $key => $rec) {
   
                // Пропускаме не-достъпните менюта
                if(!haveRole($rec->accessByRoles)) {
                    continue;
                }
                // Определяме дали състоянието на елемента от менюто не е 'активно'
                $mainClass = $subClass = '';
                if(($aMainMenu == $rec->menu)) {
                    $mainClass = ' class="selected"';
                    if($aSubMenu == $rec->subMenu) {
                        $subClass = ' class="selected"';
                    } 
                }
              	
                if($lastMenu != $rec->menu) {
                    $html .= ($html ? "\n</ul></li>" : '') . "\n<li{$mainClass} data-menuid = '{$rec->id}'>";
                    $html .= "\n    <div><span class='arrow'></span>{$rec->menuTr}</div>";
                    $html .= "\n<ul class='submenuBlock'>";
                }
                $lastMenu = $rec->menu;
                $html .= "\n<li{$subClass}>" . ht::createLink($rec->subMenuTr, array($rec->ctr, $rec->act)) . "</li>";
            }
            $html .= "\n</ul></li>";
        } else {
            // Ако имаме роля админ
            if (haveRole('admin')) {
                
                // Текущото URL
                $currUrl = getCurrentUrl();
                
                // Ако контролера не е core_Packs
                if (strtolower($currUrl['Ctr']) != 'core_packs') {
                    
                    // Редиректваме към yправление на пакети
                    redirect(array('core_Packs', 'list'), FALSE, '|Няма инсталирано меню');
                }
            }
        }
    
        $tpl->append($html, 'MENU_ROW');
    }



    /**
     * Допълнителни линкове в менюто
     */
    static function addLinksToMenu($tpl)
    {
        // Създава линк в менюто за потребители
        $user = crm_Profiles::createLink(NULL, NULL, FALSE, array('ef_icon'=>'img/16/user-black.png'));
        $tpl->replace($user, 'USERLINK');
        
        // Създава линк за поддръжка
        $conf = core_Packs::getConfig('help');
        
        if($conf->BGERP_SUPPORT_URL && strpos($conf->BGERP_SUPPORT_URL, '//') !== FALSE) {
        	
        	$singal = ht::createLink(tr("Сигнал"), $conf->BGERP_SUPPORT_URL , FALSE, array('title' => "Изпращане на сигнал към разработчиците на bgERP", 'ef_icon' => 'img/16/headset.png', 'onclick' => "event.preventDefault();$('#bugReportForm').submit();"));
        	
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
        	$form = new ET("<form id='bugReportForm' style='display:inline' method='post' target='_blank' onSubmit=\"prepareBugReport(this, '{$user}', '{$domain}', '{$name}', '{$ctr}', '{$act}', '{$sysDomain}'); \" action='" . $conf->BGERP_SUPPORT_URL . "'></form>");
        	$tpl->append($form);
        }
        
        // Създава линк за изход
        $signOut = ht::createLink(tr("Изход"), array('core_Users', 'logout'), FALSE, array('title' => "Излизане от системата", 'ef_icon' => 'img/16/logout.png'));
       	$tpl->replace($signOut, 'SIGN_OUT');
        
        // Създава линк за превключване между режимите
       	if(Mode::is('screenMode', 'wide')) {
       		$mode = ht::createLink(tr("Мобилен"), array('log_Browsers', 'setNarrowScreen', 'ret_url' => TRUE), NULL, array('ef_icon' => 'img/16/mobile-icon.png', 'title' => 'Превключване на системата в мобилен режим'));
       	} else {
       		$mode = ht::createLink(tr("Десктоп"), array('log_Browsers', 'setWideScreen', 'ret_url' => TRUE), NULL, array('ef_icon' => 'img/16/Monitor-icon.png', 'title' => 'Превключване на системата в десктоп режим'));
       	}
       	if(isDebug()) {
       		$debug = ht::createLink("Debug", '#wer', FALSE, array('title' => "Показване на debug информация", 'ef_icon' => 'img/16/bug-icon.png', 'onclick' => 'toggleDisplay(\'debug_info\'); scrollToElem(\'debug_info\');'));
       	}
        // Смяна на езика
        $lgChange = self::getLgChange();
       	$tpl->replace($lgChange, 'LANG_CHANGE');

       	
        // Извличаме броя на нотификациите за текущия потребител
        $openNotifications = bgerp_Notifications::getOpenCnt();
        
        $url  = toUrl(array('bgerp_Portal', 'Show'));
        $attr = array('id' => 'nCntLink');

        // Ако имаме нотификации, добавяме ги към титлата и контейнера до логото
        if($openNotifications > 0) {
            $attr['class'] = 'haveNtf';
        } else {
            $attr['class'] = 'noNtf';
        }

        $coreConf = core_Packs::getConfig('core');
        
        
        $portalLinkAttr = array();
        
        $appLen = mb_strlen($coreConf->EF_APP_TITLE);
        
        if ($appLen >= 20) {
            $portalLinkAttr['style'] = 'letter-spacing: -2px;';
        } elseif ($appLen >= 13) {
            $portalLinkAttr['style'] = 'letter-spacing: -1px;';
        } elseif (($appLen >= 6) && ($appLen <= 12)) {
            
            $lSpacing = (5 - $appLen) / 10;
            
            $portalLinkAttr['style'] = "letter-spacing: {$lSpacing}px;";
        }
        
        // Добавя линк към броя на отворените нотификации
        $portalLink = ht::createLink($coreConf->EF_APP_TITLE, $url, NULL, $portalLinkAttr);
        $nLink = ht::createLink("{$openNotifications}", $url, NULL, $attr);
        
        $tpl->replace($debug, 'DEBUG_BTN');
        $tpl->replace($mode, 'CHANGE_MODE');
        $tpl->replace($singal, 'SIGNAL');
        $tpl->replace($nLink, 'NOTIFICATIONS_CNT');
        $tpl->replace($portalLink, 'PORTAL');
        
        $val = '';
        if ($search = Request::get('search')) {
            $search = str_replace("'", '"', $search);
            $val = "value='{$search}'";
        }
        
        // Рендираме бутоните за търсене
        $inputType = "<input {$val} class='serch-input-modern' type='search' onkeyup='onSearchEnter(event, \"modern-doc-search\", this);'/>";
        
        $tpl->replace($inputType, 'SEARCH_INPUT');
        
        $attr = array();
        $attr['onClick'] = "return searchInLink(this, 'serch-input-modern', 'search', false);";
        $searchLink = '';
        
        if (doc_Search::haveRightFor('list')) {
            $attr['ef_icon'] = 'img/16/doc_empty.png';
            $attr['id'] = 'modern-doc-search';
            $searchLink .= ht::createLink(tr("Търсене на документи"), array('doc_Search', 'list'), NULL, $attr);
        }
        
        if (doc_Folders::haveRightFor('list')) {
            $attr['ef_icon'] = 'img/16/folder_open_icon.png';
            $attr['id'] = 'modern-folder-search';
            $searchLink .= ht::createLink(tr("Търсене на папки"), array('doc_Folders', 'list'), NULL, $attr);
        }
        
        if (crm_Companies::haveRightFor('list')) {
            $attr['ef_icon'] = 'img/16/building-black.png';
            $attr['id'] = 'modern-company-seach';
            $searchLink .= ht::createLink(tr("Търсене на фирми"), array('crm_Companies', 'list'), NULL, $attr);
        }
        
        if (crm_Persons::haveRightFor('list')) {
            $attr['ef_icon'] = 'img/16/vcard-black.png';
            $attr['id'] = 'modern-person-seach';
            $searchLink .= ht::createLink(tr("Търсене на лица"), array('crm_Persons', 'list'), NULL, $attr);
        }
                
        $tpl->replace($searchLink, 'SEARCH_LINK');
    }

    
    /**
     * Добавя хипервръзки за превключване между езиците на интерфейса
     */
    static function getLgChange()
    {
        $cl = core_Lg::getCurrent();
        if($cl == 'bg') {
            $lg = 'en';
            $title = "Промяна на езика на английски";
            $lang = 'English';
        } else {
            $lg = 'bg';
            $title = "Switch language to Bulgarian";
            $lang = 'Български';
        }
        $url = toUrl(array('core_Lg', 'Set', 'lg' => $lg, 'ret_url' => TRUE));
        $attr = array('href' => $url, 'title' => $title, 'ef_icon' => 'img/16/Maps-Globe-Earth-icon.png');
        $res = ht::createLink($lang, $url, NULL, $attr);

        return $res;
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
