<?php


/**
 *
 *
 * @category  bgerp
 * @package   peripheral
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class peripheral_Terminal extends core_Master
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Терминал';
    
    
    /**
     * Титлата на обекта в единичен изглед
     */
    public $singleTitle = 'Терминал';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_Modified, peripheral_Wrapper, plg_RowTools2';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'admin, peripheral';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'admin, peripheral';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin, peripheral';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin, peripheral';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin, peripheral';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'admin, peripheral';
    
    
    /**
     * Кой има достъп до сингъла
     */
    public $canSingle = 'admin, peripheral';
    
    
    /**
     * Разделител за потребителско име и ПИН код, когато са в едно поле
     */
    protected $userPinSeparator = '÷';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('brid', 'varchar(8)', 'caption=Браузър');
        $this->FLD('classId', 'class(interface=peripheral_TerminalIntf)', 'caption=Клас->Име, removeAndRefreshForm=pointId');
        $this->FLD('pointId', 'int', 'caption=Клас->Точка');
        $this->FLD('usePin', 'enum(yes=Да,no=Не)', 'caption=Пин');
        $this->FLD('users', 'keylist(mvc=core_Users, select=nick, where=#state !\\= \\\'rejected\\\')', 'caption=Потребители');
        $this->FLD('roles', 'keylist(mvc=core_Roles, select=role, where=#state !\\= \\\'rejected\\\')', 'caption=Роли');
    }
    
    
    /**
     * Задава префикс за нова сесия и връща предишния, ако успее да зададе
     *
     * @param bool   $force
     * @param string $sessName
     *
     * @return NULL|string
     */
    public static function setSessionPrefix($force = false, $sessName = 'terminal_')
    {
        $t = Mode::get('terminalId');
        
        $oPrefix = null;
        
        if ($force || isset($t)) {
            $oPrefix = core_Session::getDecoratePrefix();
            
            core_Session::setDecoratePrefix($sessName);
        }
        
        return $oPrefix;
    }
    
    
    /**
     * Функция за прекратяване на сесията
     */
    public static function exitTerminal()
    {
        Mode::setPermanent('terminalId', null);
        
        self::setSessionPrefix(true);
        
        Mode::setPermanent('terminalId', null);
        
        $cu = core_Users::getCurrent();
        
        core_Users::logout();
        
        core_Users::logLogin('logout', $cu, 180, $cu);
    }
    
    
    /**
     * Екшън по подразбиране
     */
    public function act_Default()
    {
        $titleUrl = array('Index', 'Default');
        
        if (core_Users::isContractor()) {
            $titleUrl = array('cms_Profiles', 'Single');
        } elseif (core_Users::haveRole('powerUser')) {
            $titleUrl = array('bgerp_Portal', 'Show');
        }
        
        $cuOutTerminal = core_Users::getCurrent();
        
        $screenMode = core_Mode::get('screenMode');
        
        $currLg = core_Lg::getCurrent();
        
        $oPrefix = $this->setSessionPrefix(true);
        
        core_Lg::set($currLg);
        
        // Сетваме екрана, както е бил зададен в главната сесия
        if (!core_Mode::is('screenMode')) {
            core_Mode::set('screenMode', $screenMode);
        }
        
        Mode::set('wrapper', 'page_Empty');
        
        $terminalId = Mode::get('terminalId');
        $cu = core_Users::getCurrent();
        
        // Ако не е избран терминал или няма зададен потребител
        if (!$terminalId) {
            $form = cls::get('core_Form');
            $form->class = 'simpleForm simplePortalLogin';
            
            $form->FLD('terminalId', 'key(mvc=peripheral_Terminal, select=name)', 'caption=Терминал, removeAndRefreshForm=user|pin, mandatory, silent,class=w100');
            
            $brid = log_Browsers::getBrid();
            
            $query = $this->getQuery();
            $query->where(array("#brid = '[#1#]'", $brid));
            
            $defTerminalIdArr = array();
            
            // Достъпните опции за терминал за потребителя
            while ($rec = $query->fetch()) {
                if (!$rec->classId || !cls::load($rec->classId, true)) {
                    continue;
                }
                
                try {
                    $Intf = cls::getInterface('peripheral_TerminalIntf', $rec->classId);
                } catch (core_exception_Expect $ex) {
                    continue;
                }
                
                $tOptArr = $Intf->getTerminalOptions();
                
                if (!isset($tOptArr[$rec->pointId])) {
                    continue;
                }
                
                $defTerminalIdArr[$rec->id] = $tOptArr[$rec->pointId];
            }
            
            $form->setOptions('terminalId', $defTerminalIdArr);
            
            if (!empty($defTerminalIdArr)) {
                $form->setDefault('terminalId', key($defTerminalIdArr));
            }
            
            $form->input('terminalId', true);
            
            if ($form->rec->terminalId) {
                $form->FLD('user', 'key(mvc=core_Users, select=nick)', 'caption=Потребител, mandatory, silent,class=w100');
                
                $tRec = $this->fetch($form->rec->terminalId);
                
                $uArr = type_Keylist::toArray($tRec->users);
                
                if ($tRec->roles) {
                    $rolesArr = type_Keylist::toArray($tRec->roles);
                    
                    foreach ($rolesArr as $roleId) {
                        $uArr += core_Users::getByRole($roleId);
                    }
                }
                
                $usersArr = array();
                
                // Ако потребителя от сесията извън терминала има роля дебъг, го добавяме в списъка
                if ($cuOutTerminal > 0 && !$uArr[$cuOutTerminal] && haveRole('debug', $cuOutTerminal)) {
                    $uArr[$cuOutTerminal] = $cuOutTerminal;
                }
                
                foreach ($uArr as $uId) {
                    $usersArr[$uId] = core_Users::fetchField($uId, 'nick');
                }
                
                $form->setOptions('user', $usersArr);
                
                if ($tRec->usePin == 'yes') {
                    $form->FLD('pin', 'password', 'caption=ПИН, mandatory, silent,class=w100,focus');
                }
            }
            
            $form->input(null, true);
            $form->input();
            
            if ($form->isSubmitted()) {
                // Ако се подаде потребител и ПИН код заедно в полето за ПИН код
                if ($form->rec->pin) {
                    if (stripos($form->rec->pin, $this->userPinSeparator) !== false) {
                        list($userName, $userPin) = explode($this->userPinSeparator, $form->rec->pin);
                        
                        if ($userName && $userPin) {
                            $nickId = core_Users::fetchField(array("#nick = '[#1#]'", $userName));
                            $form->rec->user = $nickId;
                            $form->rec->pin = $userPin;
                        }
                    }
                }
                
                $uRec = core_Users::fetch($form->rec->user);
                if ($tRec->usePin == 'yes' && (!$uRec->pinCode || ($uRec->pinCode != $form->rec->pin))) {
                    $form->setError('pin', 'Грешен ПИН код');
                    
                    self::logWarning('Грешен ПИН код', $form->rec->terminalId);
                }
            }
            
            if ($form->isSubmitted()) {
                $terminalId = $form->rec->terminalId;
                $cu = $form->rec->user;
            } elseif (!Request::get('afterExit') && $form->rec->terminalId && $form->rec->user && $usersArr[$form->rec->user]) {
                if (($tRec->usePin != 'yes') || ($form->rec->user == $cuOutTerminal)) {
                    $terminalId = $form->rec->terminalId;
                    $cu = $form->rec->user;
                }
            }
            
            if ($terminalId && $cu) {
                Mode::setPermanent('terminalId', $terminalId);
                core_Users::loginUser($cu);
                
                if ($oPrefix) {
                    $nPrefix = $this->setSessionPrefix(true, $oPrefix);
                    
                    Mode::setPermanent('terminalId', $terminalId);
                    
                    if ($nPrefix) {
                        $this->setSessionPrefix(true, $nPrefix);
                    }
                }
            }
            
            $form->title = 'Избор на терминал в|* ' . ht::createLink(core_Packs::getConfig('core')->EF_APP_TITLE, $titleUrl);
            
            $form->toolbar->addSbBtn('Вход', 'save', 'ef_icon = img/16/doc_stand.png, title = Отваряне');
            $form->toolbar->addBtn('Затвори', array('Index', 'Default'), 'ef_icon = img/16/close-red.png, title=Затваряне');
            
            if (!$terminalId) {
                $htmlRes = $form->renderHtml();
                $htmlRes->replace(tr('Отваряне на терминал'), 'PAGE_TITLE');
                $htmlRes->push('peripheral/css/styles.css', 'CSS');
                $htmlRes->replace('terminalWrapper', 'BODY_CLASS_NAME');
                
                return $htmlRes;
            }
        }
        
        // Ако има избран терминал - отваряме го
        
        $tRec = $this->fetch($terminalId);
        
        $Intf = cls::getInterface('peripheral_TerminalIntf', $tRec->classId);
        
        return $Intf->openTerminal($tRec->pointId, $cu);
    }
    
    
    /**
     * Екшън за прекратяване на сесия
     */
    public function act_ExitTerminal()
    {
        $this->exitTerminal();
        
        return new Redirect(array($this, 'default', 'afterExit' => true));
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $brid = log_Browsers::getBrid();
        $data->form->setSuggestions('brid', array('' => '', $brid => $brid));
        
        $data->form->setDefault('brid', $brid);
        
        $optArr = $data->form->fields['classId']->type->prepareOptions();
        
        if (!empty($optArr)) {
            $data->form->setDefault('classId', key($optArr));
        }
        
        $data->form->input('classId');
        
        if ($data->form->rec->classId) {
            $Intf = cls::getInterface('peripheral_TerminalIntf', $data->form->rec->classId);
            $data->form->setOptions('pointId', $Intf->getTerminalOptions());
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            if (!$form->rec->users && !$form->rec->roles) {
                $form->setError('users, roles', 'Поне едно от полетата трябва да има стойност');
            }
        }
    }
}
