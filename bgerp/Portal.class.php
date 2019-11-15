<?php


/**
 * Портален изглед на състоянието на системата
 *
 * Има възможност за костюмиране за всеки потребител
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
class bgerp_Portal extends embed_Manager
{
    /**
     * Свойство, което указва интерфейса на вътрешните обекти
     */
    public $driverInterface = 'bgerp_PortalBlockIntf';
    
    public $canClonesysdata = 'powerUser';
    public $canCloneuserdata = 'powerUser';
    public $canClonerec = 'powerUser';

//     public $canList = 'powerUser';
    public $canList = 'debug';
    public $canSingle = 'powerUser';
    public $canAdd = 'powerUser';
    public $canEdit = 'powerUser';
    public $canDelete = 'powerUser';
    
    
    /**
     * Неща за зареждане в началото
     */
    public $loadList = 'plg_Created, plg_Modified, plg_RowTools2, bgerp_Wrapper, plg_Clone';
    
    
    /**
     * Полета, които да не се клонират
     */
    public $fieldsNotToClone = 'createdOn, createdBy, modifiedOn, modifiedBy, userOrRole';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Елементи на портала';
    
    
    /**
     * 
     */
    public $listFields = 'driverClass, userOrRole, column, order, color, createdOn, createdBy';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('userOrRole', 'userOrRole(rolesType=team, rolesForAllRoles=admin, rolesForAllSysTeam=admin, userRoles=powerUser)', 'caption=Потребител/Роля, silent, refreshForm');
        $this->FLD('column', 'enum(1,2,3)', 'caption=Колона, notNull');
        $this->FLD('order', 'int(min=-1000, max=1000)', 'caption=Подредба, notNull');
        $this->FLD('color', 'enum(lightgray=Светло сив,darkgray=Тъмно сив,lightred=Светло червен,darkred=Тъмно червен,lightgreen=Светло зелен,darkgreen=Тъмно зелен,lightblue=Светло син,darkblue= Тъмно син, yellow=Жълт, pink=Розов, purple=Лилав, orange=Оранжев)', 'caption=Цвят, notNull');
        $this->FLD('show', 'enum(yes=Да,no=Не)', 'caption=Показване, notNull');
        
        $this->FNC('originIdCalc', 'key(mvc=bgerp_Portal, allowEmpty)', 'caption=Източник,input=none');
        
        $optArr = array();
        foreach ($this->fields['color']->type->options as $color => $verbal) {
            if (is_object($verbal)) {
                $optArr[$color] = $verbal;
            } else {
                $opt = new stdClass();
                $opt->title = $verbal;
                $opt->attr = array('class' => "color-{$color}");
                $optArr[$color] = $opt;
            }
        }
        
        $this->fields['color']->type->options = $optArr;
    }
    
    
    /**
     * Добавя стойност на функционалното поле boxFrom
     *
     * @param bgerp_Portal $mvc
     * @param stdClass         $rec
     */
    public static function on_CalcOriginIdCalc($mvc, &$rec)
    {
        if ($rec->clonedFromId) {
            $rec->originIdCalc = $rec->clonedFromId;
        } else {
            $rec->originIdCalc = $rec->id;
        }
    }
    
    
    /**
     * Показва портала
     */
    public function act_Show2()
    {
        $maxShowCnt = 12;
        
        // Ако е инсталиран пакета за партньори
        // И текущия потребител е контрактор, но не е powerUser
        if (core_Users::haveRole('partner')) {
            if ((core_Packs::isInstalled('colab'))) {
                $folderId = colab_FolderToPartners::getLastSharedContragentFolder();
                
                if ($folderId) {
                    
                    return new Redirect(array('colab_Threads', 'list', 'folderId' => $folderId));
                }
            }
            
            // Редирект към профила на партньора
            return new Redirect(array('cms_Profiles', 'single'));
        }
        
        requireRole('powerUser');
        
        Mode::set('pageMenuKey', '_none_');
        
        $recArr = $this->getRecsForUser();
        
        $cu = core_Users::getCurrent();
        
        $isNarrow = Mode::is('screenMode', 'narrow');
        
        if ($isNarrow) {
            $tpl = new ET(tr("|*
                          	<ul class='portalTabs'>
                                <!--ET_BEGIN NOTIFICATIONS_COLOR_TAB--><li class='tab-link [#NOTIFICATIONS_COLOR_TAB#]' data-tab='notificationsPortal'>|Известия|*</li><!--ET_END NOTIFICATIONS_COLOR_TAB-->
                                <!--ET_BEGIN CALENDAR_COLOR_TAB--><li class='tab-link [#CALENDAR_COLOR_TAB#]' data-tab='calendarPortal'>|Календар|*</li><!--ET_END CALENDAR_COLOR_TAB-->
                                <!--ET_BEGIN TASKS_COLOR_TAB--><li class='tab-link [#TASKS_COLOR_TAB#]' data-tab='taskPortal'>|Задачи|*</li><!--ET_END TASKS_COLOR_TAB-->
                                <!--ET_BEGIN RECENTLY_COLOR_TAB--><li class='tab-link [#RECENTLY_COLOR_TAB#]' data-tab='recentlyPortal'>|Последно|*</li><!--ET_END RECENTLY_COLOR_TAB-->
                            </ul>
                            <div class='portalContent'>
                                <div class='narrowPortalBlocks' id='notificationsPortal'>[#NOTIFICATIONS_COLUMN#]</div>
                                <div class='narrowPortalBlocks' id='calendarPortal'>[#CALENDAR_COLUMN#]</div>
                                <div class='narrowPortalBlocks' id='taskPortal'>[#TASK_COLUMN#]</div>
                                <div class='narrowPortalBlocks' id='recentlyPortal'>[#RECENTLY_COLUMN#]</div>
                                <div class='narrowPortalOther' id='recentlyPortal'>[#OTHER#]</div>
                            </div>
                            "));
        } else {
            $tpl = new ET("
                <table style='width:100%' class='top-table large-spacing'>
                <tr>
                    <td style='width:33.3%'>[#LEFT_COLUMN#]</td>
                    <td style='width:33.4%'>[#MIDDLE_COLUMN#]</td>
                    <td style='width:33.3%'>[#RIGHT_COLUMN#]</td>
                </tr>
                </table>
            ");
        }
        
        $columnMap = array(1 => 'LEFT_COLUMN', 2 => 'MIDDLE_COLUMN', 3 => 'RIGHT_COLUMN');
        
        foreach ($recArr as $r) {
            if (!cls::load($r->{$this->driverClassField}, true)) continue;
            
            $intf = cls::getInterface('bgerp_PortalBlockIntf', $r->{$this->driverClassField});
            
            $data = $intf->prepare($r, $cu);
            $res = $intf->render($data);
            
            if (!$res) continue;
            
            if (!$r->column) {
                $r->column = 1;
            }
            
            $colorCls = 'color-' . ($r->color ? $r->color : 'all');
            
            $res->prepend("<div class='{$colorCls}'>");
            $res->append("</div>");
            
            if ($isNarrow) {
                $blockType = $intf->getBlockType();
                
                if ($intf->getBlockType() == 'other') {
                    $tpl->prepend($res, 'OTHER');
                } else {
                    switch ($blockType) {
                        case 'tasks':
                            $blockName = 'TASK_COLUMN';
                            $tabColorName = 'TASKS_COLOR_TAB';
                        break;
                        
                        case 'notifications':
                            $blockName = 'NOTIFICATIONS_COLUMN';
                            $tabColorName = 'NOTIFICATIONS_COLOR_TAB';
                        break;
                        
                        case 'calendar':
                            $blockName = 'CALENDAR_COLUMN';
                            $tabColorName = 'CALENDAR_COLOR_TAB';
                        break;
                            
                        case 'recently':
                            $blockName = 'RECENTLY_COLUMN';
                            $tabColorName = 'RECENTLY_COLOR_TAB';
                        break;
                        
                        default:
                            expect(false, $blockName);
                        break;
                    }
                    
                    $tpl->replace($colorCls, $tabColorName);
                    
                    $tpl->append($res, $blockName);
                }
            } else {
                $tpl->append($res, $columnMap[$r->column]);
            }
            
            if (!--$maxShowCnt) break;
        }
        
        if ($isNarrow) {
            jquery_Jquery::run($tpl, "openCurrentTab('" . 1000 * dt::mysql2timestamp(bgerp_Notifications::getLastNotificationTime(core_Users::getCurrent())) . "'); ");
        }
        
        $tpl->push('js/PortalSearch.js', 'JS');
        jquery_Jquery::run($tpl, 'portalSearch();', true);
        
        bgerp_LastTouch::set('portal');
        
        self::logRead('Разглеждане на портала');
        
        return $tpl;
    }
    
    
    /**
     * Помощна функция за вземане на записите в модела
     *
     * @param null|integer $userId
     * @param string $roleType
     * @return array
     */
    protected function getRecsForUser($userId = null, $roleType = 'team')
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $query = $this->getQuery();
        
        if ($roleType) {
            $rolesList = core_Users::getUserRolesByType($userId, $roleType);
        } else {
            $rolesList = core_Users::getRoles($userId);
        }
        
        $rolesArr = type_Keylist::toArray($rolesList);
        if ($rolesArr) {
            $rolesArrSysId = array_map(array('type_UserOrRole', 'getSysRoleId'), $rolesArr);
        }
        
        // Настройките за цялата система
        $rolesArrSysId[-1] = type_UserOrRole::getAllSysTeamId();
        
        if ($userId > 0) {
            $rolesArrSysId[] = $userId;
        }
        
        $query->in('userOrRole', $rolesArrSysId);
        
        // С по-голям приоритет са данните въведени от потребителя, а с най-нисък - за цялата система
        $query->XPR('orderUserOrRole', 'int', "IF(#userOrRole > 0, #userOrRole, IF(#userOrRole = '{$rolesArrSysId[-1]}', #userOrRole, 0))");
        $query->orderBy('orderUserOrRole', 'DESC');
        
        $query->orderBy('createdOn', 'DESC');
        
        $resArr = array();
        while ($rec = $query->fetch()) {
            if ($resArr[$rec->originIdCalc]) continue;
            
            $resArr[$rec->originIdCalc] = $rec;
        }
        
        // Премахваме от масива блоковете, които да не се показват
        foreach ($resArr as $rId => $rRec) {
            if ($rRec->show == 'no') {
                unset($resArr[$rId]);
            }
        }
        
        // Подреждаме масива, според order
        arr::sortObjects($resArr, 'order', 'DESC');
        
        return $resArr;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Manager $mvc
     * @param string       $requiredRoles
     * @param string       $action
     * @param stdClass     $rec
     * @param int          $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($rec) {
            if (($userId != $rec->createdBy) && !haveRole('admin', $userId)) {
                if (($action == 'edit') || ($action == 'delete')) {
                    $requiredRoles = 'no_one';
                }
                
                if (($action == 'single') && ($rec->createdBy != $userId)) {
                    if (($rec->userOrRole > 0) && $rec->createdBy > 0) {
                        $requiredRoles = 'no_one';
                    }
                }
                
                if (($requiredRoles != 'no_one') && $action == 'cloneuserdata') {
                    $requiredRoles = $mvc->getRequiredRoles('single', $rec, $userId);
                }
            }
            
            // Ако имат "баща", да не може да се изтрие
            if ($action == 'delete') {
                if ($rec->clonedFromId) {
                    if ($mvc->fetch($rec->clonedFromId)) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        // Да се показва полето за търсене
        $data->listFilter->showFields = 'userOrRole';
        
        $data->listFilter->view = 'horizontal';
        
        //Добавяме бутон "Филтрирай"
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->setDefault('userOrRole', core_Users::getCurrent());
        
        $data->listFilter->input();
        
        if ($data->listFilter->rec->userOrRole) {
            $data->query->where(array("#userOrRole = '[#1#]'", $data->listFilter->rec->userOrRole));
            if ($data->listFilter->rec->userOrRole > 0) {
                $uRoles = core_Users::fetchField(array("#id = '[#1#]'", $data->listFilter->rec->userOrRole), 'roles');
                $uRolesArr = type_Keylist::toArray($uRoles);
                foreach ($uRolesArr as &$uRole) {
                    $uRole = type_UserOrRole::getSysRoleId($uRole);
                }
                
                $data->query->in('userOrRole', $uRolesArr, false, true);
                $data->query->orWhere(array("#userOrRole = '[#1#]'", type_UserOrRole::getAllSysTeamId()));
            }
        }
        
        $data->query->orderBy('userOrRole', 'DESC');
        $data->query->orderBy('order', 'DESC');
        $data->query->orderBy('createdBy', 'DESC');
    }
    
    
    /**
     * Показва портала
     */
    public function act_Show()
    {
        // Ако е инсталиран пакета за партньори
        // И текущия потребител е контрактор, но не е powerUser
        if (core_Users::haveRole('partner')) {
            if ((core_Packs::isInstalled('colab'))) {
                $folderId = colab_FolderToPartners::getLastSharedContragentFolder();
                
                if ($folderId) {
                    
                    return new Redirect(array('colab_Threads', 'list', 'folderId' => $folderId));
                }
            }
            
            // Редирект към профила на партньора
            return new Redirect(array('cms_Profiles', 'single'));
        }
        
        requireRole('powerUser');
        
        Mode::set('pageMenuKey', '_none_');
        
        if (Mode::is('screenMode', 'narrow')) {
            $tpl = new ET(tr("|*
          	<ul class='portalTabs defaultPortal'>
                <li class='tab-link' data-tab='notificationsPortal'>|Известия|*</li>
                <li class='tab-link' data-tab='calendarPortal'>|Календар|*</li>
                <li class='tab-link' data-tab='taskPortal'>|Задачи|*</li>
                <li class='tab-link' data-tab='recentlyPortal'>|Последно|*</li>
            </ul>
            <div class='portalContent defaultPortal'>
                <div class='narrowPortalBlocks' id='notificationsPortal'>[#NOTIFICATIONS_COLUMN#]</div>
                <div class='narrowPortalBlocks' id='calendarPortal'>[#CALENDAR_COLUMN#]</div>
                <div class='narrowPortalBlocks' id='taskPortal'>[#TASK_COLUMN#]</div>
                <div class='narrowPortalBlocks' id='recentlyPortal'>[#RECENTLY_COLUMN#]</div>
            </div>"));
        } else {
            $tpl = new ET("
            <table style='width:100%' class='top-table large-spacing'>
            <tr>
                <td style='width:33.3%'>[#LEFT_COLUMN#]</td>
                <td style='width:33.4%'>[#MIDDLE_COLUMN#]</td>
                <td style='width:33.3%'>[#RIGHT_COLUMN#]</td>
            </tr>
            </table>
            ");
        }
        
        // Задачи
        if (Mode::is('listTasks', 'by')) {
            $taskTitle = tr('Задачи от');
            $switchTitle = tr('Задачи към') . ' ' . crm_Profiles::getUserTitle(core_Users::getCurrent('nick'));
        } else {
            $taskTitle = tr('Задачи към');
            $switchTitle = tr('Задачи от') . ' ' . crm_Profiles::getUserTitle(core_Users::getCurrent('nick'));
        }
        
        $taskTitle = str_replace(' ', '&nbsp;', $taskTitle);
        
        $tasksTpl = new ET('<div class="clearfix21 portal" style="margin-bottom:25px;">
            <div class="legend">' . $taskTitle . '&nbsp;' . crm_Profiles::createLink() . '&nbsp;[#SWITCH_BTN#]&nbsp;[#ADD_BTN#]&nbsp;[#REM_BTN#]</div>
            [#TASKS#]
            </div>');
        
        // Бутон за добавяне на задачи
        $addUrl = array('cal_Tasks', 'add', 'ret_url' => true);
        $addBtn = ht::createLink(' ', $addUrl, null, array('ef_icon' => 'img/16/task-add.png', 'class' => 'addTask', 'title' => 'Добавяне на нова Задача'));
        $tasksTpl->append($addBtn, 'ADD_BTN');
        
        // Бутон за смяна от <-> към
        $addUrl = array('cal_Tasks', 'SwitchByTo');
        $addBtn = ht::createLink(' ', $addUrl, null, array('ef_icon' => 'img/16/arrow_switch.png', 'class' => 'addTask', 'title' => '|*' . $switchTitle, 'id' => 'switchTasks'));
        $tasksTpl->append($addBtn, 'SWITCH_BTN');
        
        // Бутон за смяна от <-> към
        $addUrl = array('cal_Reminders', 'add', 'ret_url' => true);
        $addBtn = ht::createLink(' ', $addUrl, null, array('ef_icon' => 'img/16/alarm_clock_add.png', 'class' => 'addTask', 'title' => 'Добавяне на ново Напомняне'));
        $tasksTpl->append($addBtn, 'REM_BTN');
        
        $tasksTpl->append(cal_Tasks::renderPortal(), 'TASKS');
        
        if (!Mode::is('screenMode', 'narrow')) {
            $calTitle = tr('Календар');
        } else {
            $calTitle = '&nbsp;';
        }
        
        $calMvc = cls::get('cal_Calendar');
        $searchForm = $calMvc->getForm();
        self::prepareSearchForm($calMvc, $searchForm);
        
        $calendarHeader = new ET('<div class="clearfix21 portal">
            <div class="legend" id="calendarPortal" style="height:20px;">' . $calTitle . '
            ' . $searchForm->renderHtml() . '
            </div>
            [#CALENDAR_DETAILS#]
            </div>');
        
        $calendarHeader->append(cal_Calendar::renderPortal(), 'CALENDAR_DETAILS');
        
        $Recently = cls::get('bgerp_Recently');
        $Notifications = cls::get('bgerp_Notifications');
        $portalArrange = core_Setup::get('PORTAL_ARRANGE');
        
        if (Mode::is('screenMode', 'narrow')) {
            // подаваме времето на последната нотификация
            jquery_Jquery::run($tpl, "openCurrentTab('" . 1000 * dt::mysql2timestamp(bgerp_Notifications::getLastNotificationTime(core_Users::getCurrent())) . "'); ");
            
            // Добавяме календара
            $tpl->append($calendarHeader, 'CALENDAR_COLUMN');
            
            // Добавяме "Наскоро" - документи и папки с които е работено наскоро
            $tpl->append($Recently->render(), 'RECENTLY_COLUMN');
            
            // Добавяме нотификации
            $tpl->append($Notifications->render(), 'NOTIFICATIONS_COLUMN');
            
            // Добавяме задачи
            $tpl->append($tasksTpl, 'TASK_COLUMN');
        } else {
            if ($portalArrange == 'notifyTaskCalRecently') {
                $tpl->append($calendarHeader, 'RIGHT_COLUMN');
            } else {
                $tpl->prepend($calendarHeader, 'RIGHT_COLUMN');
            }
            if ($portalArrange == 'recentlyNotifyTaskCal') {
                // Добавяме "Наскоро" - документи и папки с които е работено наскоро
                $tpl->append($Recently->render(), 'LEFT_COLUMN');
                
                // Добавяме нотификации
                $tpl->append($Notifications->render(), 'MIDDLE_COLUMN');
                
                // Добавяме задачи
                $tpl->append($tasksTpl, 'RIGHT_COLUMN');
            } elseif ($portalArrange == 'taskNotifyRecentlyCal') {
                // Добавяме "Наскоро" - документи и папки с които е работено наскоро
                $tpl->append($Recently->render(), 'RIGHT_COLUMN');
                
                // Добавяме нотификации
                $tpl->append($Notifications->render(), 'MIDDLE_COLUMN');
                
                // Добавяме задачи
                $tpl->append($tasksTpl, 'LEFT_COLUMN');
            } else {
                // Добавяме "Наскоро" - документи и папки с които е работено наскоро
                $tpl->append($Recently->render(), 'RIGHT_COLUMN');
                
                // Добавяме нотификации
                $tpl->replace($Notifications->render(), 'LEFT_COLUMN');
                
                // Добавяме задачи
                $tpl->append($tasksTpl, 'MIDDLE_COLUMN');
            }
        }
        
        $tpl->push('js/PortalSearch.js', 'JS');
        jquery_Jquery::run($tpl, 'portalSearch();');
        jquery_Jquery::run($tpl, 'clearLocalStorage();', true);
        
        bgerp_LastTouch::set('portal');
        
        self::logRead('Разглеждане на портала');
        
        return $tpl;
    }
    
    
    /**
     * Подготвя форма за търсене в портала
     *
     * @param core_Mvc  $mvc  - викащия клас
     * @param core_Form $form - филтър форма
     */
    public static function prepareSearchForm(core_Mvc $mvc, core_Form &$form)
    {
        $form->layout = getTplFromFile('bgerp/tpl/PortalSearch.shtml');
        $form->layout->replace($mvc->searchInputField, 'FLD_NAME');
        
        if ($search = Request::get($mvc->searchInputField)) {
            $form->layout->replace($search, 'VALUE');
        }
        
        $findIcon = sbf('img/16or32/find.png');
        
        $form->layout->replace($mvc->className, 'LIST');
        $form->layout->replace($findIcon, 'ICON');
        static::prepareSearchDataList($mvc, $form);
        $form->toolbar->addSbBtn('', null, 'ef_icon=img/16/find.png,class=SearchBtnPortal');
        $form->setField('id', 'input=none');
        
        // Зареждаме всички стойности от GET заявката в формата, като
        // пропускаме тези които не са параметри в нея
        foreach (getCurrentUrl() as $key => $value) {
            if ($key != 'App' && $key != 'Ctr' && $key != 'Act' && $key != 'Cmd' && !strpos($key, 'Search')) {
                if (!$form->fields[$key]) {
                    $form->FNC($key, 'varchar', 'input=hidden');
                    $form->setDefault($key, $value);
                }
            }
        }
    }
    
    
    /**
     * Подготовка на дата листа с предложения за формата за търсене
     */
    public static function prepareSearchDataList($mvc, &$form)
    {
        $name = $form->mvc->dbTableName . '.' . $mvc->searchInputField;
        $suggestions = recently_Values::fetchSuggestions($name);
        
        $html = "<datalist id='{$mvc->className}'>\n";
        
        if (count($suggestions)) {
            foreach ($suggestions as $string) {
                $html .= "<option value='{$string}'>\n";
            }
        }
        $html .= "</datalist>\n";
        $form->layout->append(new ET($html), 'DATA_LIST');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($rec->color) {
            $row->color = "<span class='color-{$rec->color}'>{$row->color}</span>";
        }
    }
}
