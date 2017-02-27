<?php



/**
 * Портален изглед на състоянието на системата
 *
 * Има възможност за костюмиране за всеки потребител
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_Portal extends core_Manager
{
    
    
    /**
     * Неща за зареждане в началото
     */
    var $loadList = 'plg_Created, plg_RowTools, bgerp_Wrapper';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Елементи на портала';
    
    // Права
    
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('column', 'enum(1,2,3,4)', 'caption=Колона, mandatory');
        $this->FLD('blockSource', 'class(interface=bgerp_BlockSource)', 'caption=Контролер, mandatory');
        $this->FLD('params', 'text', 'caption=Настройки,input=none');
        $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Потребител');
        $this->FLD('mobile', 'enum(no=Не,yes=Да)', 'caption=Мобилен');
    }
    
    
    /**
     * Показва портала
     */
    function act_Show()
    {
        // Ако е инсталиран пакета за партньори
    	// И текущия потребител е контрактор, но не е powerUser
    	if(core_Users::haveRole('partner')){
        		
    		// Редирект към профила на партньора
    		return new Redirect(array('cms_Profiles', 'single'));
        }
    	
    	requireRole('powerUser');
        
        Mode::set('pageMenuKey', '_none_');
        
        if(Mode::is('screenMode', 'narrow')) {
            $tpl = new ET(tr("|*
          	<ul class='portalTabs'>
                <li class='tab-link' data-tab='notificationsPortal'>|Известия|*</li>
                <li class='tab-link' data-tab='calendarPortal'>|Календар|*</li>
                <li class='tab-link' data-tab='taskPortal'>|Задачи|*</li>
                <li class='tab-link' data-tab='recentlyPortal'>|Последно|*</li>
            </ul>
            <div class='portalContent'>
                <div class='narrowPortalBlocks' id='notificationsPortal'>[#NOTIFICATIONS#]</div>
                <div class='narrowPortalBlocks' id='calendarPortal'>[#CALENDAR_COLUMN#]</div>
                <div class='narrowPortalBlocks' id='taskPortal'>[#TASK_COLUMN#]</div>
                <div class='narrowPortalBlocks' id='recentlyPortal'>[#LEFT_COLUMN#]</div>
            </div>"));
        } else {
            $tpl = new ET("
            <table style='width:100%' class='top-table large-spacing'>
            <tr>
                <td style='width:32%'>[#LEFT_COLUMN#]</td>
                <td style='width:36%'>[#TASK_COLUMN#]</td>
                <td style='width:32%'>[#NOTIFICATIONS#][#CALENDAR_COLUMN#]</td>
            </tr>
            </table>
            ");
        }
        
        $Recently = cls::get('bgerp_Recently');
        
        // Добавяме "Наскоро" - документи и папки с които е работено наскоро
        $tpl->append($Recently->render(), 'LEFT_COLUMN');
        
        $Notifications = cls::get('bgerp_Notifications');
        
        // Добавяме нотификации
        $tpl->replace($Notifications->render(), 'NOTIFICATIONS');
        
        // Задачи
        if(Mode::is('listTasks', 'by')) {
            $taskTitle   = tr('Задачи от');
            $switchTitle = tr('Задачи към') . ' ' . crm_Profiles::getUserTitle(core_Users::getCurrent('nick'));
        } else {
            $taskTitle = tr('Задачи към');
            $switchTitle = tr('Задачи от') . ' ' . crm_Profiles::getUserTitle(core_Users::getCurrent('nick'));
        }
        
        $taskTitle = str_replace(' ', '&nbsp;', $taskTitle);
        
        $tasksTpl = new ET('<div class="clearfix21 portal" style="background-color:#fffff0;margin-bottom:25px;">
            <div class="legend" style="background-color:#ffd;">' . $taskTitle . '&nbsp;' . crm_Profiles::createLink() . '&nbsp;[#SWITCH_BTN#]&nbsp;[#ADD_BTN#]&nbsp;[#RЕМ_BTN#]</div>
            [#TASKS#]
            </div>');
        
        // Бутон за добавяне на задачи
        $addUrl = array('cal_Tasks', 'add', 'ret_url' => TRUE);
        $addBtn = ht::createLink(' ', $addUrl, NULL, array('ef_icon' => 'img/16/task-add.png', 'class' => 'addTask', 'title' => 'Добавяне на нова Задача'));
        $tasksTpl->append($addBtn, 'ADD_BTN');
        
        // Бутон за смяна от <-> към
        $addUrl = array('cal_Tasks', 'SwitchByTo');
        $addBtn = ht::createLink(' ', $addUrl, NULL, array('ef_icon' => 'img/16/arrow_switch.png', 'class' => 'addTask', 'title' => '|*' . $switchTitle, 'id' => 'switchTasks'));
        $tasksTpl->append($addBtn, 'SWITCH_BTN');
        
        // Бутон за смяна от <-> към
        $addUrl = array('cal_Reminders', 'add', 'ret_url' => TRUE);
        $addBtn = ht::createLink(' ', $addUrl, NULL, array('ef_icon' => 'img/16/alarm_clock_add.png', 'class' => 'addTask', 'title' => 'Добавяне на ново Напомняне'));
        $tasksTpl->append($addBtn, 'RЕМ_BTN');
        
        $tasksTpl->append(cal_Tasks::renderPortal(), 'TASKS');
        
        if(!Mode::is('screenMode', 'narrow')) {  
            $calTitle = tr('Календар');
        } else {
            $calTitle = '&nbsp;';
        }
        
        $calMvc = cls::get('cal_Calendar');
        $searchForm = $calMvc->getForm();
        self::prepareSearchForm($calMvc, $searchForm);

        $calendarHeader = new ET('<div class="clearfix21 portal" style="background-color:#f8fff8;">
            <div class="legend" id="calendarPortal" style="background-color:#efe;height:20px;">' . $calTitle . '
            ' . $searchForm->renderHtml() . '
            </div>
            [#CALENDAR_DETAILS#]
            </div>');
        
        $calendarHeader->append(cal_Calendar::renderPortal(), 'CALENDAR_DETAILS');
        if(Mode::is('screenMode', 'narrow')) {
            jquery_Jquery::run($tpl, "openCurrentTab();");
        }

        $tpl->append($calendarHeader, 'CALENDAR_COLUMN');
        $tpl->append($tasksTpl, 'TASK_COLUMN');
        $tpl->push('js/PortalSearch.js', 'JS');
        jquery_Jquery::run($tpl, "portalSearch();");

        bgerp_LastTouch::set('portal');
        
        self::logRead('Разглеждане на портала');
        
        return $tpl;
    }
    
    
    /**
     * Подготвя форма за търсене в портала
     * @param core_Mvc $mvc - викащия клас
     * @param core_Form $form - филтър форма
     */
    public static function prepareSearchForm(core_Mvc $mvc, core_Form &$form)
    {
        $form->layout = getTplFromFile("bgerp/tpl/PortalSearch.shtml");
        $form->layout->replace($mvc->searchInputField, 'FLD_NAME');
        
        if($search = Request::get($mvc->searchInputField)){
            $form->layout->replace($search, 'VALUE');
        }

        $findIcon = sbf("img/16or32/find.png");
     
        $form->layout->replace($mvc->className, 'LIST');
        $form->layout->replace($findIcon, 'ICON');
        static::prepareSearchDataList($mvc, $form);
        $form->toolbar->addSbBtn('', NULL, "ef_icon=img/16/find.png,class=SearchBtnPortal");
        $form->setField('id', 'input=none');
        
        // Зареждаме всички стойности от GET заявката в формата, като
        // пропускаме тези които не са параметри в нея
        foreach(getCurrentUrl() as $key => $value){
            if($key != 'App' && $key != 'Ctr' && $key != 'Act' && $key != 'Cmd' && !strpos($key, 'Search')){
                if(!$form->fields[$key]){
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
        $Recently = cls::get('recently_Values');
        $name = $form->mvc->dbTableName . "." . $mvc->searchInputField;
        $suggestions = $Recently->getSuggestions($name);
        
        $html = "<datalist id='{$mvc->className}'>\n";
        
        if(count($suggestions)){
            foreach($suggestions as $string){
                $html .= "<option value='{$string}'>\n";
            }
        }
        $html .= "</datalist>\n";
        $form->layout->append(new ET($html), 'DATA_LIST');
    }

}
