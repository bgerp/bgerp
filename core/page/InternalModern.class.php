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
class core_page_InternalModern extends core_page_Active {
    
    public $interfaces = 'core_page_WrapperIntf';
 
    /**
     * Конструктор за страницата по подразбиране
     * Тази страница използва internal layout, header и footer за да 
     * покаже една обща обвивка за съдържанието за вътрешни потребители
     */
    function core_page_InternalModern()
    {
    	// Конструиране на родителския клас
        $this->core_page_Active();
        
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
        $this->push('js/js.js', 'JS');
        
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
        }
        
        // Опаковките и главното съдържание заемат екрана до долу
        $this->append("runOnLoad(setMinHeight);", "JQRUN");
        
        // Акордеона в менюто
        $this->append("runOnLoad(sidebarAccordeonActions);", "JQRUN");

        // Вкарваме съдържанието
        $this->replace(self::getTemplate(), 'PAGE_CONTENT');

        // Извличаме броя на нотификациите за текущия потребител
        $openNotifications = bgerp_Notifications::getOpenCnt();
        $url  = toUrl(array('bgerp_Portal', 'Show'));
        $attr = array('id' => 'nCntLink');
        
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
    	if (isset($_COOKIE['menuInformation'])) {
    		$openMenuInfo = $_COOKIE['menuInformation'];
    		$mainContainerClass = '';
    		//в зависимост от стойсността на разбираме кои менюта са било отворени
    		if($openMenuInfo){
    			if(strrpos($openMenuInfo, "l") !== FALSE) {
    				$openLeftBtn = ' menu-active ';
    				$openLeftMenu = ' sidemenu-open ';
    				$mainContainerClass .= ' sidemenu-push-toright ';
    			}
    			if(strrpos($openMenuInfo, "r") !== FALSE) {
    				$openRightBtn = ' menu-active ';
    				$openRightMenu = ' sidemenu-open';
    				$mainContainerClass .= ' sidemenu-push-toleft ';
    				$pin = ' hidden ';
    			} else {
    				$pinned = ' hidden ';
    			}
    		} 
    	} else {
    			$pinned = ' hidden ';
    	}
    	
    	$menuImg = ht::createElement('img', array('src' => sbf('img/menu.png', ''), 'class' => 'menuIcon'));
    	$pinImg = ht::createElement('img', array('src' => sbf('img/pin.png', ''), 'class' => "menuIcon pin {$pin}"));
    	$pinnedImg = ht::createElement('img', array('src' => sbf('img/pinned.png', ''), 'class' => "menuIcon pinned {$pinned}"));
    	$img = avatar_Plugin::getImg(core_Users::getCurrent(), NULL, 26);
    	
    	// Задаваме лейаута на страницата
    	$header = "<div style='position: relative'>
	    					<a id='nav-panel-btn' href='#nav-panel' class='fleft btn-sidemenu btn-menu-left push-body {$openLeftBtn}'>". $menuImg ."</a>
	    					<span class='fleft logoText'>[#PORTAL#]</span>
	    					<span class='notificationsCnt'>[#NOTIFICATIONS_CNT#]</span>
	    					<span class='headerPath'>[#HEADER_PATH#]</span>
	    					<a id='fav-panel-btn' href='#fav-panel' class='fright btn-sidemenu btn-menu-right push-body {$openRightBtn}'>". $pinImg . $pinnedImg . "</a>
	    					<span class='fright'>
		    						<span class='user-options'>
		    							" . $img .
    			    							"<div class='menu-holder'>
			     		   					[#USERLINK#]
		    								[#CHANGE_MODE#]
                                            [#LANG_CHANGE#]
		    								[#SIGNAL#]
	    									<div class='divider'></div>
			     		   					[#SIGN_OUT#]
		    							</div>
	    							</span>
	     		   			</span>
	    				<div class='clearfix21'></div>
	    				</div>  " ;
    	 
    	$tpl = new ET("<div id='main-container' class='clearfix21 main-container [#HAS_SCROLL_SUPPORT#] {$mainContainerClass}'>" .
    			"<div id=\"framecontentTop\"  class=\"headerBlock\"><div class='inner-framecontentTop'>" . $header . "</div></div>" .
    			"<div id=\"maincontent\">" .
    			"<!--ET_BEGIN NAV_BAR--><div id=\"navBar\">[#NAV_BAR#]</div>\n<!--ET_END NAV_BAR--><div class='clearfix' style='min-height:9px;'></div>" .
    			"<div id='statuses'>[#STATUSES#]</div>" .
    			"[#PAGE_CONTENT#]</div>" .
    			"<div id=\"framecontentBottom\" class=\"container\">" .
    			"[#PAGE_FOOTER#]" .
    			"</div></div>".
    			"<div id='nav-panel' class='sidemenu sidemenu-left {$openLeftMenu}'>[#core_page_InternalModern::renderMenu#]</div>".
    			"<div id='fav-panel' class='sidemenu sidemenu-right {$openRightMenu}'><h3><center>Бързи връзки</center></h3></div>" );
    	
    	// Опаковките и главното съдържание заемат екрана до долу
    	
    	$tpl->append("runOnLoad( slidebars );", "JQRUN");
    	
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
        
        uasort($menuObj, function($a, $b) { return($a->order > $b->order); });
 
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
                    $html .= "\n    <div><span class='arrow'></span>{$rec->menu}</div>";
                    $html .= "\n<ul>";
                }
                $lastMenu = $rec->menu;
                $html .= "\n<li{$subClass}>" . ht::createLink($rec->subMenu, array($rec->ctr, $rec->act)) . "</li>";
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
                    return redirect(array('core_Packs', 'list'), FALSE, tr('Няма инсталирано меню'));
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
        	
        	$singal = ht::createLink(tr("Сигнал"), $conf->BGERP_SUPPORT_URL , FALSE, array('title' => "Изпращане на сигнал", 'ef_icon' => 'img/16/bug-icon.png', 'onclick' => "event.preventDefault();$('#bugReportForm').submit();"));
        	
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
        	$form = new ET("<form id='bugReportForm' style='display:inline' method='post' target='_blank' onSubmit=\"prepareBugReport(this, '{$user}', '{$domain}', '{$name}', '{$ctr}', '{$act}', '{$sysDomain}');\" action='" . $conf->BGERP_SUPPORT_URL . "'></form>");
        	$tpl->append($form);
        }
        
        // Създава линк за изход
        $signOut = ht::createLink(tr("Изход"), array('core_Users', 'logout'), FALSE, array('title' => "Излизане от системата", 'ef_icon' => 'img/16/logout.png'));
       	$tpl->replace($signOut, 'SIGN_OUT');
        
        // Създава линк за превключване между режимите
       	if(Mode::is('screenMode', 'wide')) {
       		$mode = ht::createLink(tr("Мобилен"), array('core_Browser', 'setNarrowScreen', 'ret_url' => TRUE), NULL, array('ef_icon' => 'img/16/mobile-icon.png', 'title' => 'Превключване на системата в мобилен режим'));
       	} else {
       		$mode = ht::createLink(tr("Десктоп"), array('core_Browser', 'setWideScreen', 'ret_url' => TRUE), NULL, array('ef_icon' => 'img/16/Monitor-icon.png', 'title' => 'Превключване на системата в десктоп режим'));
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
        // Добавя линк към броя на отворените нотификации
        $portalLink = ht::createLink("bgERP", $url, NULL, NULL);
        $nLink = ht::createLink("{$openNotifications}", $url, NULL, $attr);

        $tpl->replace($mode, 'CHANGE_MODE');
        $tpl->replace($singal, 'SIGNAL');
        $tpl->replace($nLink, 'NOTIFICATIONS_CNT');
        $tpl->replace($portalLink, 'PORTAL');
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

        if(Mode::is('screenMode', 'wide')) {
            // Добавяме кода, за определяне параметрите на браузъра
            $Browser = cls::get('core_Browser');
            $tpl->append($Browser->renderBrowserDetectingCode(), 'BROWSER_DETECT');

            // Добавя бутон за калкулатора
            $tpl->append('&nbsp;<small>|</small>&nbsp;');
            $tpl->append(calculator_View::getBtn());
            
            if(isDebug()) {
            	$tpl->append('&nbsp;<small>|</small>&nbsp;<a href="#wer" onclick="toggleDisplay(\'debug_info\')">Debug</a>');
            }
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
