<?php


/**
 * Връзки в основното меню
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_Menu extends core_Manager
{
    /**
     * Дали да се изтриват неинсталираните менюта в текущия хит
     */
    public $deleteNotInstalledMenu = false;
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, bgerp_Wrapper';
    
    
    /**
     * Заглавие
     */
    public $title = 'Елементи на менюто';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'admin';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('row', 'double', 'caption=Ред, mandatory');
        $this->FLD('menu', 'varchar(64)', 'caption=Меню, mandatory');
        $this->FLD('subMenu', 'varchar(64)', 'caption=Под меню, mandatory');
        $this->FLD('ctr', 'varchar(128)', 'caption=Контролер,mandatory');
        $this->FLD('act', 'varchar(128)', 'caption=Екшън');
        $this->FLD('autoHide', 'enum(no=Не,yes=Да)', 'caption=Авто скриване');
        $this->FLD('accessByRoles', 'keylist(mvc=core_Roles,select=role,groupBy=type, orderBy=orderByRole)', 'caption=Роли');
        
        $this->setDbUnique('menu,subMenu');
        $this->setDbUnique('ctr,act');
    }
    
    
    /**
     * Връща обект - меню
     */
    public static function getMenuObject()
    {
        $cacheKey = 'menuObj_' . core_Lg::getCurrent();
        
        $menuObj = core_Cache::get('Menu' . core_Users::getCurrent(), $cacheKey);
        
        if (!is_array($menuObj)) {
            $query = self::getQuery();
            
            $query->orderBy('#row,#id', 'ASC');
            $pos = array();
            $next = 1;
            
            while ($rec = $query->fetch()) {
                $newRec = clone($rec);
                if (!($thisMenu = $pos[$rec->menu])) {
                    $thisMenu = $pos[$rec->menu] = $next++;
                }
                list($whole, $decimal) = explode('.', $rec->row);
                $newRec->order = $thisMenu . '.' . $decimal;
                
                $newRec->row = (int) $rec->row;
                $newRec->menuTr = tr($rec->menu);
                $newRec->subMenuTr = tr($rec->subMenu);
                $ctrArr = explode('_', $rec->ctr);
                $newRec->pack = $ctrArr[0];
                $newRec->act = $rec->act ? $rec->act : 'default';
                
                // Пропускаме не-достъпните менюта
                if (!bgerp_Menu::findFirst($newRec->ctr, $newRec->act, $newRec->accessByRoles)) {
                    continue;
                }
                
                $menuObj[$rec->menu . ':' . $rec->subMenu] = $newRec;
            }
            
            core_Cache::set('Menu' . core_Users::getCurrent(), $cacheKey, $menuObj, 1400);
        }
        
        // Ако няма нито един запис в Менюто, но имаме права за администратор,
        // и текущия контролер не е core_*, редирекваме към core_Packs
        if (!countR($menuObj) && (strpos(Request::get('Ctr'), 'core_') === false)) {
            redirect(array('core_Packs'));
        }
        
        return $menuObj;
    }
    
    
    /**
     * Изчиства кеша за дадения потребител
     */
    public static function clearCache($userId)
    {
        core_Cache::removeByType('Menu' . $userId);
    }
    
    
    /**
     * Изтриване на кеша
     */
    public function on_AfterSave($mvc, $id, $rec)
    {
        $mvc->savedItems[$rec->id] = true;
    }
    
    
    /**
     * Изтриване на кеша
     */
    public function on_AfterDelete($mvc, $id, $rec)
    {
        core_Cache::remove('Menu', 'menuObj');
    }
    
    
    /**
     * Намира активния запис
     */
    public static function getActiveItem($menuObj)
    {
        // Опит за определяне на активното меню от Mode
        $menu = Mode::get('pageMenu');
        $subMenu = Mode::get('pageSubMenu');
        $subMenu = $subMenu ? $subMenu : $menu;
        $key = "{$menu}:{$subMenu}";
        
        if (isset($menuObj[$key])) {
            return $key;
        }
        
        if (Mode::is('pageMenuKey')) {
            return Mode::get('pageMenuKey');
        }
        
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
        if ($ctr == 'core_Users' && strtolower($act) == 'login') {
            return '_none_';
        }
        
        $act = $act ? $act : 'default';
        $ctrArr = explode('_', $ctr);
        $pack = $ctrArr[0];
        
        $bestW = 0;
        $bestKey = null;
        
        if (($menuObj) && (countR($menuObj))) {
            foreach ($menuObj as $key => $rec) {
                if ($rec->ctr == $ctr && $rec->act == $act) {
                    return $key;
                }
                
                $w = 1.0 * ($rec->pack == $pack) +
                1.0 * ($rec->ctr == $ctr) +
                max(0.7 * ($rec->act == $act), 0.5 * ($rec->act == 'default' || $rec->act == 'list'));
                
                if ($w >= 1) {
                    if ($w > $bestW) {
                        $bestKey = $key;
                        $bestW = $w;
                    }
                }
            }
        } else {
            
            // Ако имаме роля админ
            if (haveRole('admin')) {
                
                // Текущото URL
                $currUrl = getCurrentUrl();
                
                // Ако контролера не е core_Packs
                if (strtolower($currUrl['Ctr']) != 'core_packs') {
                    
                    // Редиректваме към управление на пакети
                    redirect(array('core_Packs', 'list'), false, '|Няма инсталирано меню');
                }
            }
        }
        
        return $bestKey;
    }
    
    
    /**
     * Връща данните за менюто на текущия потребител
     */
    public static function prepareMenu_($menuObj, $active)
    {
        $activeArr = explode(':', $active);
        
        if (($menuObj) && (countR($menuObj))) {
            foreach ($menuObj as $key => $rec) {
                
                // state: 3 - active, 2 - normal, 1 - disabled, 0 - hidden
                // $mainMenuItems[$pageMenu] = TRUE; Дали това главно меню вече е показано
                
                // Първоначално задаваме 'нормално' състояние на елемента от менюто
                $rec->state = 2;
                $rec->link = true;
                
                if (!haveRole($rec->accessByRoles)) {
                    
                    // Менютата, които се скриват при недостатъчно права, не се обработват
                    if ($rec->autoHide == 'yes') {
                        continue;
                    }
                    
                    $rec->state = 1;      //disabled
                    $rec->link = false;
                }
                
                // Определяме дали състоянието на елемента от менюто не е 'активно'
                if (($activeArr[0] == $rec->menu) && ($activeArr[1] == $rec->subMenu)) {
                    $rec->state = 3;
                }
                
                // Дали да влезе в списъка с под-менюта?
                if ($activeArr[0] == $rec->menu) {
                    $subMenus[$rec->subMenu] = $rec;
                }
                
                // Дали да влезе в списъка с менюта?
                if ((!isset($menus[$rec->menu])) || $menus[$rec->menu]->state < $rec->state) {
                    $menus[$rec->menu] = $rec;
                }
                
                if ($lastRec->menu != $rec->menu && $rec->state != 1) {
                    $lastRec = $rec;
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
                    
                    // Редиректваме към управление на пакети
                    redirect(array('core_Packs', 'list'), false, '|Няма инсталирано меню');
                }
            }
        }
        
        return array($menus, $subMenus);
    }
    
    
    /**
     * Създава връзка отговаряща на състоянието на посочения ред
     */
    public static function createLink($title, $rec, $menu = false)
    {
        if ($menu) {
            $url = array($rec->menuCtr, $rec->menuAct);
        } else {
            $url = array($rec->ctr, $rec->act);
        }
        
        if ($rec->state == 3) {
            $attr['class'] = 'menuItem selected';
        } elseif ($rec->state == 2) {
            $attr['class'] = 'menuItem';
        } else {
            $attr['class'] = 'menuItem';
            $url = null;
        }
        
        if (!$rec->link) {
            $url = null;
        }
        
        if (!$url) {
            $attr['class'] .= ' btn-disabled';
        }
        
        return ht::createLink($title, $url, '', $attr);
    }
    
    
    /**
     * Показва страница с меню, предназначено за мобилен изглед
     */
    public function act_Show()
    {
        requireRole('user');
        
        Mode::set('pageMenuKey', '_none_');
        
        if (!Mode::is('screenMode', 'narrow')) {
            return new Redirect(array('bgerp_Portal', 'Show'));
        }
        
        $tpl = new ET(
            "<div class='menuPage noSelect'>
                        <div>[#MENU_ROW#] </div>
                    </div>
                "
        
        );
        
        $menuObj = self::getMenuObject();
        
        foreach ($menuObj as $key => $rec) {
            if (!isset($menu[$rec->menu]) || !haveRole($menu[$rec->menu]->accessByRoles)) {
                $menu[$rec->menu] = $rec;
            }
            
            $subMenu[$rec->menu][$rec->subMenu] = $rec;
        }
        
        foreach ($menu as $rec) {
            $url = haveRole($rec->accessByRoles) ?  array($rec->ctr, $rec->act) : array();
            $class = 'mainMenu';
            
            if (!countR($url)) {
                $class .= ' btn-disabled';
            }
            $link = ht::createLink($rec->menuTr, $url, null, array('class' => $class));
            $row = 'MENU_ROW';
            $tpl->append($link, $row);
            $first = true;
            
            foreach ($subMenu[$rec->menu] as $subRec) {
                $url = haveRole($subRec->accessByRoles) ?  array($subRec->ctr, $subRec->act) : array();
                $class = 'subMenu';
                
                if (!countR($url)) {
                    $class .= ' btn-disabled';
                }
                
                if ($first) {
                    $class .= ' subMenu-first';
                    $first = false;
                }
                $link = ht::createLink(
                    $subRec->subMenuTr,
                    $url,
                    null,
                    array('class' => $class)
                );
                $tpl->append($link, $row);
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Опитва се да намери първия таб, от табовете на $ctr, до който текущия потребител има достъп
     * Ако не успее, връща дали потребителя има посочените роли
     */
    public static function findFirst(&$ctr, &$act, &$roles)
    {
        // Ако имаме някоя от основните роли - ОК
        if (haveRole($roles)) {
            return true;
        }
        
        // Ако класа не може да се зареди - връщаме false
        if (!cls::load($ctr, true)) {
            return false;
        }
        
        $manager = cls::get($ctr);
        
        $plugins = arr::make($manager->loadList, true);
        if (countR($plugins)) {
            foreach ($plugins as $plg) {
                $plg = cls::get($plg);
                if ($plg instanceof plg_ProtoWrapper && !($plg instanceof cms_ExternalWrapper)) {
                    $plg->description();
                    if (countR($plg->tabs)) {
                        foreach ($plg->tabs as $caption => $obj) {
                            if ($obj->roles == 'user') {
                                if ($obj->url['Ctr'] && ($obj->url['Act'] == 'list' || $obj->url['Act'] == 'default' || $obj->url['Act'] == '')) {
                                    $inst = cls::get($obj->url['Ctr']);
                                    if ($inst->canList) {
                                        $obj->roles = $inst->canList;
                                    } elseif ($inst->canRead) {
                                        $obj->roles = $inst->canRead;
                                    } else {
                                        $obj->roles = 'ceo';
                                    }
                                } else {
                                    $obj->roles == 'ceo';
                                }
                            }
                            if ((countR($obj->url) == 1 || countR($obj->url) == 2) && haveRole($obj->roles)) {
                                $ctr = $obj->url['Ctr'];
                                $act = $obj->url['Act'];
                                $roles = $obj->roles;
                                
                                return true;
                            }
                        }
                    }
                }
            }
        }
        
        return false;
    }
    
    
    /**
     * Добавя елемент в основното меню на системата. Използва се в началното установяване
     */
    public static function addOnce($row, $menu, $subMenu, $ctr, $act, $accessByRoles = 'user', $autoHide = 'no')
    {
        $Manu = cls::get('bgerp_Menu');
        
        $rec = new stdClass();
        $rec->row = $row;
        $rec->menu = $menu;
        $rec->subMenu = $subMenu;
        $rec->ctr = $ctr;
        $rec->act = $act;
        $rec->autoHide = $autoHide;
        $rec->createdBy = -1;      // По този начин, системният потребител е автор на менюто
        $Roles = cls::get('core_Roles');
        $rec->accessByRoles = $Roles->getRolesAsKeylist($accessByRoles);
        
        $exRec = self::fetch(array("#menu = '[#1#]' AND #subMenu = '[#2#]' AND #ctr = '[#3#]' AND #act = '[#4#]'", $menu, $subMenu, $ctr, $act));
        
        if ($exRec && ($rec->id = $exRec->id)) {
            $addCond = "AND #id != {$rec->id}";
        }
        
        // Изтриване на направените точки от менюто, които влизат в противоречие с текущата
        $del = self::delete(array("#ctr = '[#1#]' AND #act = '[#2#]' {$addCond}", $ctr, $act));
        
        if ($act == 'default') {
            $del += self::delete(array("#ctr = '[#1#]' AND #act = '[#2#]' {$addCond}", $ctr, ''));
        }
        $del += self::delete(array("#menu = '[#1#]' AND #subMenu = '[#2#]' {$addCond}", $menu, $subMenu));
        
        if ($del) {
            $res .= "<li class='debug-new'>Изтриване на {$del} елемент/а на менюто, поради дублиране</li>\n";
        }
        
        self::save($rec);
        
        if ($exRec) {
            if ($exRec->row != $rec->row || $exRec->accessByRoles != $rec->accessByRoles || $exRec->autoHide != $rec->autoHide) {
                $res .= "<li class=\"debug-notice\">Обновяване елемента на менюто <b>{$rec->menu} » {$rec->subMenu}</b></li>\n";
            } else {
                $res .= "<li class='debug-info'>Без промяна на елемента на менюто <b>{$rec->menu} » {$rec->subMenu}</b></li>\n";
            }
        } else {
            if ($rec->id) {
                $res .= "<li class='debug-new'>Създаване елемент на менюто <b>{$rec->menu} » {$rec->subMenu}</b></li>";
            }
        }
        
        return $res;
    }
    
    
    /**
     * При спиране на скрипта
     */
    public static function on_Shutdown($mvc)
    {
        // Ако имаме добавения по менюто
        if (countR($mvc->savedItems)) {
            
            // Премахваме кеша на менюто за всички езици
            $lgArr = core_Lg::getLangs();
            
            foreach ($lgArr as $lg => $title) {
                $cacheKey = 'menuObj_' . $lg;
                core_Cache::remove('Menu', $cacheKey);
            }
            
            // Ако е зададено да се изтриват
            if ($mvc->deleteNotInstalledMenu) {
                $query = self::getQuery();
                
                while ($rec = $query->fetch('#createdBy = -1')) {
                    if (!$mvc->savedItems[$rec->id]) {
                        $mvc->delete($rec->id);
                    }
                }
            }
        }
    }
    
    
    /**
     * Добавя бутон за премахване на всички записи, видим само в режим Debug
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
        if (haveRole('debug')) {
            $data->toolbar->addBtn(
                'Изпразване',
                array($mvc, 'DeleteAll'),
                array(
                    'warning' => 'Наистина ли желаете да премахнете всички записи?'),
                'ef_icon = img/16/delete.png'
            );
        }
    }
    
    
    /**
     * Изтрива всички записи от менюто
     */
    public function act_DeleteAll()
    {
        if (haveRole('debug')) {
            $cnt = $this->delete('1=1');
            
            return new Redirect(array($this), "|Бяха изтрити|* {$cnt} |записа");
        }
    }
    
    
    /**
     * Премахване на пакет от менюто
     */
    public static function remove($pack)
    {
        if (is_object($pack)) {
            $name = cls::getClassName($pack);
        } else {
            expect(is_string($pack));
            $name = $pack;
        }
        
        list($name) = explode('_', $name);
        
        // Изтриване на входните точки от менюто
        $delCnt = bgerp_Menu::delete("#ctr LIKE '{$name}\\_%'");
        
        if ($delCnt == 1) {
            $msg = "<li class='debug-notice'>Беше изтрита една входна точка от менюто.</li>";
        } elseif ($delCnt > 1) {
            $msg = "<li class='debug-notice'>Бяха изтрити {$delCnt} входни точки от менюто.</li>";
        }
        
        return $msg;
    }
    
    
    /**
     * функция, която автоматично изчиства лишите линкове от менюто
     */
    public function repair()
    {
        $query = $this->getQuery();
        
        while ($rec = $query->fetch()) {
            if (!cls::load($rec->ctr, true)) {
                $this->delete($rec->id);
                
                $res .= "<li class='debug-error'>Премахнато е {$rec->menu} -> {$rec->menu}</li>";
            }
        }
    }
}
