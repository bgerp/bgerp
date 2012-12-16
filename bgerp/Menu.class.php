<?php



/**
 * Връзки в основното меню
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_Menu extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, bgerp_Wrapper';
    
    
    /**
     * Заглавие
     */
    var $title = 'Елементи на менюто';
    
    // Права
    
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('row', 'double', 'caption=Ред, mandatory');
        $this->FLD('menu', 'varchar(64)', 'caption=Меню, mandatory');
        $this->FLD('subMenu', 'varchar(64)', 'caption=Под меню, mandatory');
        $this->FLD('ctr', 'varchar(128)', 'caption=Контролер,mandatory');
        $this->FLD('act', 'varchar(128)', 'caption=Екшън');
        $this->FLD('autoHide', 'enum(no=Не,yes=Да)', 'caption=Авто скриване');
        $this->FLD('accessByRoles', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Роли');
        
        $this->setDbUnique('menu,subMenu');
        $this->setDbUnique('ctr,act');
    }
    
    
    /**
     * Връща обект - меню
     */
    function getMenuObject()
    {
        $cacheKey = 'menuObj_' . core_Lg::getCurrent();

        $menuObj = core_Cache::get('Menu', $cacheKey);

        if(!is_array($menuObj)) {
        
            $query = $this->getQuery();
            
            $query->orderBy("#row,#id", "ASC");
             
            while($rec = $query->fetch()) {
                $rec->row = (int) $rec->row;
                $rec->menuTr = tr($rec->menu);
                $rec->subMenuTr = tr($rec->subMenu);
                $ctrArr = explode('_', $rec->ctr);
                $rec->pack = $ctrArr[0];
                $rec->act = $rec->act ? $rec->act : 'default';
                $menuObj[$rec->menu . ':' . $rec->subMenu] = $rec;
            }

            core_Cache::set('Menu', $cacheKey, $menuObj, 1400);
        } 
        
        // Ако няма нито един запис в Менюто, но имаме права за администратор, 
        // и текущия контролер не е core_*, редирекваме към core_Packs
        if(!count($menuObj) && (strpos(Request::get('Ctr'), 'core_') === FALSE)) {
            redirect(array('core_Packs'));
        }  

        return $menuObj;
    }

    
    /**
     * Изтриване на кеша
     */
    function on_AfterSave($mvc, $id, $rec)
    {
        $cacheKey = 'menuObj_' . core_Lg::getCurrent();

        core_Cache::remove('Menu', $cacheKey);

        $mvc->savedItems[$rec->id] = TRUE;
    }
    

    /**
     * Изтриване на кеша
     */
    function on_AfterDelete($mvc, $id, $rec)
    {   

        core_Cache::remove('Menu', 'menuObj');
    }
    
    
    /**
     * Намира активния запис
     */
    function getActiveItem($menuObj)
    {
        // Опит за определяне на активното меню от Mode
        $menu = Mode::get('pageMenu');
        $subMenu = Mode::get('pageSubMenu');
        $subMenu = $subMenu ? $subMenu : $menu;
        $key = "{$menu}:{$subMenu}";
        
        if(isset($menuObj[$key])) return $key;
        
        if(Mode::is('pageMenuKey')) return Mode::get('pageMenuKey');

        $ctr = Request::get('Ctr');
        
        if ($ctr) {
            $ctr = cls::getClassName($ctr);
            $mvc = cls::get($ctr);
            
            if ($mvc->menuPage && $menuObj[$mvc->menuPage]) {
                return $mvc->menuPage;
            }
        }
        $act = Request::get('Act');
        
        // При логване да не показва менютата
        if($ctr == 'core_Users' && strtolower($act) == 'login') return '_none_';

        $act = $act ? $act : 'default';
        $ctrArr = explode('_', $ctr);
        $pack = $ctrArr[0];

        $bestW = 0;
        $bestKey = NULL;
        
        if(count($menuObj)) {
            foreach($menuObj as $key => $rec) {
                
                if($rec->ctr == $ctr && $rec->act == $act) return $key;
                
                $w = 1.0 * ($rec->pack == $pack) +
                     1.0 * ($rec->ctr == $ctr) +
                     max(0.7 * ($rec->act == $act), 0.5 * ($rec->act == 'default' || $rec->act == 'list'));
                
                if($w >= 1) {
                    if($w > $bestW) {
                        $bestKey = $key;
                        $bestW = $w;
                    }
                }
            }
        }
        
        return $bestKey;
    }
    
    
    /**
     * Поставя елементите на менюто в шаблона
     */
    function place($tpl)
    {
        $menuObj = $this->getMenuObject();
        
        $active = $this->getActiveItem($menuObj);
        
        $activeArr = explode(':', $active);
        
        if(count($menuObj)) {
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
                    
                    $rec->state = 1;     //disabled
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
            }
        }
         
        // До тук имаме определени два списъка $menus (с главните менюта) и $subMenus (с под-менютата);
        
        if(Mode::is('screenMode', 'narrow')) {
            
        	$conf = core_Packs::getConfig('core');
        	
            $menuLink = ht::createLink($conf->EF_APP_TITLE, array($this, 'Show'));
            
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
                        $tpl->append("<font style='color:#ccc;font-size:0.8em;vertical-align: 20%;'>&nbsp;|&nbsp;</font>", 'SUB_MENU');
                    }
                    $link = $this->createLink($rec->subMenuTr, $rec);
                    $tpl->append($link, 'SUB_MENU');
                    $notFirst = TRUE;
                }
            }
        } else {
            // Ако сме в широк формат
            // Отпечатваме менютата
            if(count($menus)) {
                foreach($menus as $key => $rec) {
                    $link = $this->createLink($rec->menuTr, $rec);
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
                    $link = $this->createLink($rec->subMenuTr, $rec);
                    $tpl->append("&nbsp;", 'SUB_MENU');
                    $tpl->append($link, 'SUB_MENU');
                }
            }
        }
        
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
        $nLink = ht::createLink("{$openNotifications}", $url, NULL, $attr);
        $tpl->replace($nLink, 'NOTIFICATIONS_CNT');
    }
    
    
    /**
     * Създава връзка отговаряща на състоянието на посочения ред
     */
    static function createLink($title, $rec)
    {
        if($rec->state == 3 ) {
            $attr['class'] = 'menuItem selected';
            if($rec->link) {
                $url = array($rec->ctr, $rec->act);
            }
        } elseif ($rec->state == 2 ) {
            $attr['class'] = 'menuItem';
            if($rec->link) {
                $url = array($rec->ctr, $rec->act);
            }
        } else {
            $attr['class'] = 'menuItem';
        }
        
        return ht::createLink($title, $url, '', $attr);
    }
    
    
    /**
     * Показва страница с меню, предназначено за мобилен изглед
     */
    function act_Show()
    {
        requireRole('user');
        
        Mode::set('pageMenuKey', '_none_');

        if(!Mode::is('screenMode', 'narrow')) redirect(array('bgerp_Portal', 'Show'));
        
        $tpl = new ET(
            "<div class='menuPage'>
                        <div>[#MENU_ROW#] </div>
                    </div>
                ");
        
        $menuObj = $this->getMenuObject();
        
        foreach($menuObj as $key => $rec)
        {
            if(!isset($menu[$rec->menu])) {
                $menu[$rec->menu] = $rec;
            }

            $subMenu[$rec->menu][$rec->subMenu] = $rec;
        }
        
        foreach($menu as $rec) {
            $link = ht::createLink($rec->menuTr, array($rec->ctr, $rec->act),  NULL, array('style' => 'padding:1px; background-color:#ddd; '));
            $row = 'MENU_ROW';
            $tpl->append($link, $row);
            foreach($subMenu[$rec->menu] as $subRec) {
                $link = ht::createLink($subRec->subMenuTr, array($subRec->ctr, $subRec->act), NULL, array('style' => 'font-size:0.9em; margin-bottom:10px; display:inline-block !important; margin-right:10px;'));
                $tpl->append($link, $row);
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Добавя елемент в основното меню на системата. Използва се в началното установяване
     */
    function addItem($row, $menu, $subMenu, $ctr, $act, $accessByRoles = 'user', $autoHide = 'no')
    {
        $rec = new stdClass();
        $rec->row = $row;
        $rec->menu = $menu;
        $rec->subMenu = $subMenu;
        $rec->ctr = $ctr;
        $rec->act = $act;
        $rec->autoHide = $autoHide;
        $rec->createdBy = -1;     // По този начин, системният потребител е автор на менюто
        $Roles = cls::get('core_Roles');
        $rec->accessByRoles = $Roles->keylistFromVerbal($accessByRoles);
        
        $rec->id = $this->fetchField(array("#menu = '[#1#]' AND #subMenu = '[#2#]' AND #ctr = '[#1#]' AND #act = '[#2#]' AND #createdBy = -1", 
            $menu, $subMenu, $ctr, $act), 'id');
        
        if($rec->id) {
            $addCOnd = "AND #id != {$rec->id}";
        }
        
        $this->delete(array("#ctr = '[#1#]' AND #act = '[#2#]' AND #createdBy = -1 {$addCOnd}", $ctr, $act));
        $this->delete(array("#menu = '[#1#]' AND #subMenu = '[#2#]' AND #createdBy = -1 {$addCOnd}", $menu, $subMenu));

        // expect( (count(explode('|', $rec->accessByRoles)) - 2) == count(explode(',', $accessByRoles)));
        
        $oldId = $rec->id;

        $id = $this->save($rec);
        
        if($oldId) {
            return "<li style='color:#600;'> Обновен е елемент на менюто: {$rec->menu} » {$rec->subMenu}</li>";
        } else {
            if($id) {
                return "<li style='color:green;'> Добавен е елемент на менюто: {$rec->menu} » {$rec->subMenu}</li>";
            } else {
                return "<li style='color:red;'> Eлементa на менюто \"{$rec->menu} » {$rec->subMenu}\" не бе добавен, поради дублиране</li>";
            }
        }
    }

    function removeUnsavedItems()
    {
        $query = self::getQuery();
        while($rec = $query->fetch("#createdBy = 0")) {
            if(!$this->savedItems[$rec->id]) {
                $this->delete($rec->id);
                $res .= "<li style='color:green;'> Премахнат е елемент на менюто: {$rec->menu} » {$rec->subMenu}</li>";
            }
        }

        return $res;
    }
    
    
    /**
     * Добавя бутон за премахване на всички записи, видим само в режим Debug
     */
    static function on_AfterPrepareListToolbar($mvc, $data)
    {
        if(isDebug()) {
            $data->toolbar->addBtn('Изпразване', array($mvc, 'DeleteAll'), array(
                    'class' => 'btn-delete',
                    'warning' => 'Наистина ли желаете да премахнете всички записи?'));
        }
    }
    
    
    /**
     * Изтрива всички записи от менюто
     */
    function act_DeleteAll()
    {
        if(haveRole('admin')) {
            
            $cnt = $this->delete('1=1');
            
            return new Redirect(array($this), "Бяха изтрити {$cnt} записа");
        }
    }
    
        
    /**
     * Премахване на пакет от менюто
     */
    static function remove($pack)
    {
        if(is_object($pack)) {
            $name = cls::getClassName($pack);
        } else {
            expect(is_string($pack));
            $name = $pack;
        }
        
        list($name) = explode('_', $name);
        
        // Изтриване на входните точки от менюто
        $delCnt = bgerp_Menu::delete("#ctr LIKE '{$name}\\_%'");
        
        if($delCnt == 1) {
            $msg = "<li>Беше изтрита една входна точка от менюто.</li>";
        } elseif($delCnt > 1) {
            $msg = "<li>Бяха изтрити {$delCnt} входни точки от менюто.</li>";
        }
        
        return $msg;
    }

    
    /**
     * Рендира основното меню на страницата
     */
    static function renderMenu()
    {
        if(Mode::is('screenMode', 'narrow')) {
            $tpl = new ET("
                <div id='mainMenu'>
                     <div class='menuRow clearfix21'>[#MENU_ROW#]<!--ET_BEGIN NOTIFICATIONS_CNT--><div id='notificationsCnt'>[#NOTIFICATIONS_CNT#]</div><!--ET_END NOTIFICATIONS_CNT--></div>
                </div>
                <!--ET_BEGIN SUB_MENU--><div id=\"subMenu\">[#SUB_MENU#]</div>\n<!--ET_END SUB_MENU-->");
        } else {
            $tpl = new ET("
                <div id='mainMenu'>
                    <div style='float:right;'><!--ET_BEGIN NOTIFICATIONS_CNT--><span id='notificationsCnt'>[#NOTIFICATIONS_CNT#]</span><!--ET_END NOTIFICATIONS_CNT-->[#logo#]</div>
                    <div class=\"menuRow\">[#MENU_ROW1#]</div>
                    <div class=\"menuRow\" style=\"margin-top:3px; margin-bottom:3px;\">[#MENU_ROW2#]</div>
                    <div class=\"menuRow\">[#MENU_ROW3#]</div>                   

                </div> <div class='clearfix'></div>
                <!--ET_BEGIN SUB_MENU--><div id=\"subMenu\">[#SUB_MENU#]</div>\n<!--ET_END SUB_MENU-->");
            
            $logo = ht::createLink("<IMG  SRC=" .
                sbf('img/bgerp.png') . "  BORDER=\"0\" ALT=\"\" style='border-top:5px solid transparent;'>",
                array('bgerp_Portal', 'Show'));
            
            $tpl->replace($logo, 'logo');
        }
        
        $Menu = cls::get('bgerp_Menu');
        
        $Menu->place($tpl);
        
        $tpl->prepend("\n<meta name=\"robots\" content=\"noindex,nofollow\">", 'HEAD');
        $tpl->prepend("\n<meta name=\"format-detection\" content=\"telephone=no\">", 'HEAD');
        $tpl->prepend("\n<meta name=\"google\" value=\"notranslate\">", 'HEAD');

        return $tpl;
    }


    /**
     * функция, която автоматично изчиства лишите линкове от менюто
     */
    function repair()
    {
        $query = $this->getQuery();
        while($rec = $query->fetch()) {
            if(!cls::load($rec->ctr, TRUE)) {
                $this->delete($rec->id);

                $res .= "<li style='color:red;'>Премахнато е {$rec->menu} -> {$rec->menu}</li>";
            }
        }

    }
   
}
