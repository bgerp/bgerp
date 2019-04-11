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
class peripheral_Terminal extends core_BaseClass
{
    /**
     * Разделител за потребителско име и ПИН код, когато са в едно поле
     */
    protected $userPinSeparator = '÷';
    
    
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
            
            $form->FLD('terminalId', 'key(mvc=peripheral_Devices, select=name)', 'caption=Терминал, removeAndRefreshForm=user|pin, mandatory, silent,class=w100');
            
            $dArr = peripheral_Devices::getDevices('peripheral_TerminalIntf', log_Browsers::getBrid(), core_Users::getRealIpAddr());
            
            $defTerminalIdArr = array();
            
            // Достъпните опции за терминал за потребителя
            foreach ($dArr as $dId => $dRec) {
                $defTerminalIdArr[$dId] = $dRec->name;
            }
            
            $form->setOptions('terminalId', $defTerminalIdArr);
            
            if (!empty($defTerminalIdArr)) {
                $form->setDefault('terminalId', key($defTerminalIdArr));
            }
            
            $form->input('terminalId', true);
            
            if ($form->rec->terminalId) {
                $form->FLD('user', 'key(mvc=core_Users, select=nick)', 'caption=Потребител, mandatory, silent,class=w100, removeAndRefreshForm=pin');
                
                $tRec = peripheral_Devices::fetch($form->rec->terminalId);
                
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
                
                if ($tRec->authorization != 'no') {
                    
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
                if ($tRec->authorization != 'no' && (!$uRec->pinCode || ($uRec->pinCode != $form->rec->pin)) && ($form->rec->user != $cuOutTerminal)) {
                    $form->setError('pin', 'Грешен ПИН код');
                    
                    peripheral_Devices::logWarning('Грешен ПИН код', $form->rec->terminalId);
                }
            }
            
            if ($form->isSubmitted()) {
                $terminalId = $form->rec->terminalId;
                $cu = $form->rec->user;
            } elseif (!Request::get('afterExit') && $form->rec->terminalId && $form->rec->user && $usersArr[$form->rec->user]) {
                if (($tRec->authorization == 'no') || ($form->rec->user == $cuOutTerminal)) {
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
        
        $tRec = peripheral_Devices::fetch($terminalId);
        
        $Intf = cls::getInterface('peripheral_TerminalIntf', $tRec->driverClass);
        
        return $Intf->openTerminal($tRec->id, $cu);
    }
    
    
    /**
     * Екшън за прекратяване на сесия
     */
    public function act_ExitTerminal()
    {
        $this->exitTerminal();
        
        return new Redirect(array($this, 'default', 'afterExit' => true));
    }
}
