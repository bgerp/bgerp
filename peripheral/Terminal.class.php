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
    public $title = 'Терминали';
    
    
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
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'brid, point=Точка, usePin, users, roles, createdOn, createdBy';
    
    
    /**
     * 
     * @var string
     */
    public $singleFields = 'brid, point=Точка, usePin, users, roles, createdOn, createdBy, modifiedOn, modifiedBy';
    
    
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
        $this->FLD('classId', 'class(interface=peripheral_TerminalIntf)', 'caption=Клас->Име, removeAndRefreshForm=pointId, mandatory, silent');
        $this->FLD('pointId', 'int', 'caption=Клас->Точка, mandatory, silent');
        $this->FLD('usePin', 'enum(yes=Да,no=Не)', 'caption=Оторизация');
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
        $cu = core_Users::getCurrent();
        
        if ($cu > 0) {
            core_Users::logout();
            core_Users::logLogin('logout', $cu, 180, $cu);
        }
        
        Mode::setPermanent('terminalId', null);
        
        self::setSessionPrefix(true);
        
        Mode::setPermanent('terminalId', null);
        
        $tCu = core_Users::getCurrent();
        
        if ($tCu != $cu) {
            core_Users::logout();
            core_Users::logLogin('logout', $tCu, 180, $tCu);
        }
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
                try {
                    $Intf = cls::getInterface('peripheral_TerminalIntf', $rec->classId);
                } catch (core_exception_Expect $ex) {
                    continue;
                }
                
                $tOptArr = $Intf->getTerminalOptions();
                $defTerminalIdArr[$rec->id] = $rec->name;
                
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
                $form->FLD('user', 'key(mvc=core_Users, select=nick)', 'caption=Потребител, mandatory, silent,class=w100, removeAndRefreshForm=pin');
                
                $tRec = $this->fetch($form->rec->terminalId);
                
                $uArr = type_Keylist::toArray($tRec->users);
                
                if ($tRec->roles) {
                    $rolesArr = type_Keylist::toArray($tRec->roles);
                    
                    foreach ($rolesArr as $roleId) {
                        $uArr += core_Users::getByRole($roleId);
                    }
                }
                
                $usersArr = array();
                
                foreach ($uArr as $uId) {
                    $usersArr[$uId] = core_Users::fetchField($uId, 'nick');
                }
                
                $form->setOptions('user', $usersArr);
                
                if ($cuOutTerminal && $usersArr[$cuOutTerminal]) {
                    $form->setDefault('user', $cuOutTerminal);
                }
                
                if (!$cu && ($cuOutTerminal > 0) && !empty($defTerminalIdArr) && (count($defTerminalIdArr) == 1)) {
                    $cu = $cuOutTerminal;
                    $terminalId = $form->rec->terminalId;
                }
                
                if ($tRec->usePin != 'no') {
                    
                    $form->input('user', true);
                    
                    if (!$cuOutTerminal || ($form->rec->user != $cuOutTerminal)) {
                        $form->FLD('pin', 'password', 'caption=ПИН, mandatory, silent,class=w100,focus');
                    }
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
                if ($tRec->usePin != 'no' && (!$uRec->pinCode || ($uRec->pinCode != $form->rec->pin)) && ($form->rec->user != $cuOutTerminal)) {
                    $form->setError('pin', 'Грешен ПИН код');
                    
                    $this->logWarning('Грешен ПИН код', $form->rec->terminalId);
                }
            }
            
            if ($form->isSubmitted()) {
                $terminalId = $form->rec->terminalId;
                $cu = $form->rec->user;
            } elseif (!Request::get('afterExit') && count($defTerminalIdArr) == 1 && $form->rec->user && $usersArr[$form->rec->user]) {
                if (($tRec->usePin == 'no') || ($form->rec->user == $cuOutTerminal)) {
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
            
            $closeUrl = array('Index', 'Default');
            
            if ($this->haveRightFor('list')) {
                $closeUrl = array($this, 'list');
            }
            
            $form->toolbar->addSbBtn('Вход', 'save', 'ef_icon = img/16/doc_stand.png, title = Отваряне');
            $form->toolbar->addBtn('Затвори', $closeUrl, 'ef_icon = img/16/close-red.png, title=Затваряне');
            
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
            $tOptArr = $Intf->getTerminalOptions();
            $data->form->setOptions('pointId', $tOptArr);
            if (empty($tOptArr)) {
                $clsInst = cls::get($data->form->rec->classId);
                $data->form->setReadonly('pointId');
                $errMsg = 'Няма опции|* ';
                if ($clsInst->haveRightFor('list')) {
                    $errMsg .= '|в|* ' . ht::createLink(mb_strtolower($clsInst->title), array($clsInst, 'list'));
                } else {
                    $errMsg .= "|за терминала|*";
                }
                $data->form->setError('pointId', $errMsg);
            }
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
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if ($rec->brid) {
            $row->brid = log_Browsers::getLink($rec->brid);
        }
        
        if ($rec->classId && $rec->pointId) {
            try {
                $Intf = cls::getInterface('peripheral_TerminalIntf', $rec->classId);
                
                $tOptArr = $Intf->getTerminalOptions();
                
                if (isset($tOptArr[$rec->pointId])) {
                    $row->point = $tOptArr[$rec->pointId];
                    
                    $cls = core_Cls::get($rec->classId);
                    
                    if ($cls instanceof core_Master) {
                        if ($cls::haveRightFor('single', $rec->pointId)) {
                            $row->point = ht::createLink($row->point, array($cls, 'single', $rec->pointId), null, array('ef_icon' => $cls->getIcon($rec->pointId)));
                        }
                    } else {
                        if ($cls::haveRightFor('list', $rec->pointId)) {
                            $row->point = ht::createLink($row->point, array($cls, 'list', $rec->pointId));
                        }
                    }
                }
            } catch (core_exception_Expect $ex) {
            
            }
        }
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
     * Подготовка на детайла
     *
     * @param stdClass $data
     */
    public function prepareDetail_($data)
    {
        $classId = $data->masterMvc->getClassId();
        $pointId = $data->masterData->rec->id;
        
        $query = $this->getQuery();
        $query->where(array("#classId = '[#1#]' AND #pointId = '[#2#]'", $classId, $pointId));
        
        // Извличане на записите
        $data->recs = $data->rows = array();
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = $this->recToVerbal($rec);
            
            if ($this->haveRightFor('single', $rec->id)) {
                $data->rows[$rec->id]->Link = $this->getLinkToSingle($rec->id);
            }
        }
        
        if (!empty($data->recs)) {
            $data->TabCaption = 'Терминали';
            $data->Order = '100';
            
            if ($this->haveRightFor('add')) {
                $data->AddLink = ht::createLink(tr('Нов'),
                                                array($this, 'add', 'classId' => $classId, 'pointId' => $pointId, 'ret_url' => true),
                                                false,
                                                array('ef_icon' => '/img/16/add1-16.png', 'title' => 'Добавяне на нов терминал'));
            }
        }
        
        return $data;
    }
    
    
    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderDetail_($data)
    {
        $tpl = new ET('');
        
        if (!empty($data->rows)) {
            $tpl = getTplFromFile('peripheral/tpl/TerminalDetailLayout.shtml');
            $rowBlockTpl = $tpl->getBlock('log');
            
            foreach ((array) $data->rows as $row) {
                $rowBlockTpl->placeObject($row);
                $rowBlockTpl->append2Master();
            }
            
            if ($data->AddLink) {
                $tpl->append($data->AddLink, 'AddLink');
            }
        }
        
        return $tpl;
    }

}
