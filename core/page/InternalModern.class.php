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


        // Вкарваме съдържанието
        $this->replace(self::getTemplate(), 'PAGE_CONTENT');
    }


    /**
     * Връща шаблона за страницата
     */
    static function getTemplate()
    {
    	$menuImg = ht::createElement('img', array('src' => sbf('img/menu.png', ''), 'class' => 'menuIcon'));
    	$pinImg = ht::createElement('img', array('src' => sbf('img/pin.png', ''), 'class' => 'menuIcon'));
    	$imageUrl = sbf('img/24/me.jpg', '');
    	$img = ht::createElement('img', array('src' => $imageUrl, 'width' => 30));
    	// Задаваме лейаута на страницата
    	$header = "<div style='position: relative'>
	    					<a id='nav-panel-btn' href='#nav-panel' class='fleft btn-sidemenu btn-menu-left push-body'>". $menuImg ."</a>
	    					<span class='fleft logoText'>[#PORTAL#]</span>
	    					<span class='headerPath'>[#HEADER_PATH#]</span>
	    					<a id='fav-panel-btn' href='#fav-panel' class='fright btn-sidemenu btn-menu-right push-body'>". $pinImg ."</a>
	    					<span class='fright'>
	     		   					<span class='notificationsCnt'>[#NOTIFICATIONS_CNT#]</span>
		    						<span class='user-options'>
		    							" . $img .
    			    							"<div class='menu-holder'>
			     		   					[#USERLINK#]
		    								[#CHANGE_MODE#]
		    								[#SIGNAL#]
	    									<div class='divider'></div>
			     		   					[#SIGN_OUT#]
		    							</div>
	    							</span>
	     		   			</span>
	    				<div class='clearfix21'></div>
	    				</div>  " ;
    	 
    	$tpl = new ET("<div id='main-container' class='clearfix21 main-container [#HAS_SCROLL_SUPPORT#]'>" .
    			"<div id=\"framecontentTop\"  class=\"headerBlock\"><div class='inner-framecontentTop'>" . $header . "</div></div>" .
    			"<div id=\"maincontent\">" .
    			"<!--ET_BEGIN NAV_BAR--><div id=\"navBar\">[#NAV_BAR#]</div>\n<!--ET_END NAV_BAR--><div class='clearfix' style='min-height:10px;'></div>" .
    			"<div id='statuses'>[#STATUSES#]</div>" .
    			"[#PAGE_CONTENT#]</div>" .
    			"<div id=\"framecontentBottom\" class=\"container\">" .
    			"[#PAGE_FOOTER#]" .
    			"</div></div>".
    			"<div id='nav-panel' class='sidemenu sidemenu-left'>[#core_page_InternalModern::renderMenu#]</div>".
    			"<div id='fav-panel' class='sidemenu sidemenu-right'>test</div>" );
    	
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
                    [#SUB_MENU#]
                    <div class=\"menuRow\">[#MENU_ROW1#]</div>
                    <div class=\"menuRow\">[#MENU_ROW2#]</div>
                    <div class=\"menuRow\">[#MENU_ROW3#]</div>                   
					<div class='clearfix'></div>");
        
        
        $tpl->prepend("\n<meta name=\"robots\" content=\"noindex,nofollow\">", 'HEAD');
        $tpl->prepend("\n<meta name=\"format-detection\" content=\"telephone=no\">", 'HEAD');
        $tpl->prepend("\n<meta name=\"google\" content=\"notranslate\">", 'HEAD');
           
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
        
        $activeArr = explode(':', $active);
        
        if (($menuObj) && (count($menuObj))) {
            foreach($menuObj as $key => $rec)
            {
                // state: 3 - active, 2 - normal, 1 - disabled, 0 - hidden
                // $mainMenuItems[$pageMenu] = TRUE; Дали това главно меню вече е показано
                
                // Първоначално задаваме 'нормално' състояние на елемента от менюто
                $rec->state = 2;
                $rec->link = TRUE;
                
                if(!haveRole($rec->accessByRoles)) {
                    
                    // Менютата, които се скриват при недостатъчно права, не се обработват
                    if($rec->autoHide == 'yes') continue;
                    
                    $rec->state = 1;      //disabled
                    $rec->link  = FALSE;
                }
                
                // Определяме дали състоянието на елемента от менюто не е 'активно'
                if(($activeArr[0] == $rec->menu) && ($activeArr[1] == $rec->subMenu)) {
                    $rec->state = 3;
                }
                
                // Дали да влезе в списъка с под-менюта?
                if($activeArr[0] == $rec->menu) {
                    $subMenus[$rec->subMenu] = $rec;
                }
                
                // Дали да влезе в списъка с менюта?
                if((!isset($menus[$rec->menu])) || $menus[$rec->menu]->state < $rec->state) {
                    $menus[$rec->menu] = $rec;
                }
                
                if($lastRec->menu != $rec->menu && $rec->state != 1) {
                    $lastRec =  $rec;
                }
                
                $rec->menuCtr = $lastRec->ctr;
                $rec->menuAct = $lastRec->act;
            }
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
        
        // До тук имаме определени два списъка $menus (с главните менюта) и $subMenus (с под-менютата);
        
		// Отпечатваме менютата
		if(count($menus)) {
			foreach($menus as $key => $rec) {
				$link = self::createLink($rec->menuTr, $rec, TRUE);
				$row = 'MENU_ROW' . $rec->row;
                   
				$tpl->append($link, 'MENU_ROW' . $rec->row);
				if(count($subMenus) && $rec->state == 3) {
					foreach($subMenus as $key => $rec) {
						$link = self::createLink($rec->subMenuTr, $rec);
						$tpl->append('<span class="subAccord">' .$link. '</span>', 'MENU_ROW' . $rec->row);
					}
				}
				$notFirstInFor[$rec->row] = TRUE;
			}
        }
        
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
        $user = crm_Profiles::createLink(NULL, NULL, FALSE, array('ef_icon'=>'img/16/user-black.png'));
        $tpl->replace($user, 'USERLINK');  
        $supportUrl = BGERP_SUPPORT_URL;
        $singal = ht::createLink(tr("Сигнал"), $supportUrl, FALSE, array('title' => "Изпращане на сигнал", 'target' => '_blank', 'ef_icon' => 'img/16/bug-icon.png'));
        
        $signOut = ht::createLink(tr("Изход"), array('core_Users', 'logout'), FALSE, array('title' => "Излизане от системата", 'ef_icon' => 'img/16/logout.png'));
       	$tpl->replace($signOut, 'SIGN_OUT');
       
       	if(Mode::is('screenMode', 'wide')) {
       		$mode = ht::createLink(tr("Тесен"), array('core_Browser', 'setNarrowScreen', 'ret_url' => TRUE), NULL, array('ef_icon' => 'img/16/mobile-icon.png', 'title' => 'Превключване на системата в мобилен режим'));
       	} else {
       		$mode = ht::createLink(tr("Широк"), array('core_Browser', 'setWideScreen', 'ret_url' => TRUE), NULL, array('ef_icon' => 'img/16/Monitor-icon.png', 'title' => 'Превключване на системата в десктоп режим'));
       	}
       	
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

        if(Mode::is('screenMode', 'narrow')) {
            if($nick) {
                $tpl->append(ht::createLink(tr("Изход"), array('core_Users', 'logout'), FALSE, array('title' => "Изход на " . $nick)));
            }
                        
            if($isGet) {
                $tpl->append("&nbsp;<small>|</small>&nbsp;");
                $tpl->append(ht::createLink(tr("Широк"), array('core_Browser', 'setWideScreen', 'ret_url' => TRUE), FALSE, array('title' => " Превключване на системата в десктоп режим")));

                // Добавяме превключване между езиците
                $tpl->append(self::getLgChange());
            }

            $tpl->append("&nbsp;<small>|</small>&nbsp;");
            $tpl->append(ht::createLink(dt::mysql2verbal(dt::verbal2mysql(), 'H:i'), array('Index', 'default'), NULL, array('title' => tr('Страницата е заредена на') . ' ' . dt::mysql2verbal(dt::verbal2mysql(), 'd-m H:i:s'))));
        } else {
            if($nick) {
                $tpl->append(ht::createLink("&nbsp;" . tr('изход') . ":" . $nick, array('core_Users', 'logout'), FALSE, array('title' => "Прекъсване на сесията")));
                $tpl->append('&nbsp;<small>|</small>');
            }
            
            $tpl->append('&nbsp;');
            $tpl->append(dt::mysql2verbal(dt::verbal2mysql()));
            
            if($isGet) {
                $tpl->append("&nbsp;<small>|</small>&nbsp;");
                $tpl->append(ht::createLink(tr("Тесен"), array('core_Browser', 'setNarrowScreen', 'ret_url' => TRUE), FALSE, array('title' => "Превключване на системата в мобилен режим")));
            
                // Добавяме превключване между езиците
                $tpl->append(self::getLgChange());
            }
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
        
        $conf = core_Packs::getConfig('help');
        
        if($conf->BGERP_SUPPORT_URL && strpos($conf->BGERP_SUPPORT_URL, '//') !== FALSE) {
            $email = email_Inboxes::getUserEmail();
            if(!$email) {
                $email = core_Users::getCurrent('email');
            }
            list($user, $domain) = explode('@', $email);
            $name = core_Users::getCurrent('names');
            $img = sbf('img/supportmale-20.png', '');
            $btn = "<input title='Сигнал за бъг, въпрос или предложение' class='bugReport' type=image src='{$img}' name='Cmd[refresh]' value=1>";
            $form = new ET("<form style='display:inline' method='post' target='_blank' onSubmit=\"prepareBugReport(this, '{$user}', '{$domain}', '{$name}');\" action='" . $conf->BGERP_SUPPORT_URL . "'>[#1#]</form>", $btn);
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
     * Създава връзка отговаряща на състоянието на посочения ред
     */
    static function createLink($title, $rec, $menu = FALSE)
    {
    	if($menu) {
    		$url = array($rec->menuCtr, $rec->menuAct);
    	} else {
    		$url = array($rec->ctr, $rec->act);
    	}
    	if($rec->state == 3 ) {
    		$attr['class'] = 'itemAccord selected';
    	} elseif ($rec->state == 2 ) {
    		$attr['class'] = 'itemAccord';
    	} else {
    		$attr['class'] = 'itemAccord';
    		$url = NULL;
    	}
    
    	if(!$rec->link) {
    		$url = NULL;
    	}
    
    	if(!$url) {
    		$attr['class'] .= ' btn-disabled';
    	}
    	return ht::createLink($title, $url, '', $attr);
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