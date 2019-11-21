<?php


/**
 * Мениджър на потребителски профили
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     0.12
 */
class crm_Profiles extends core_Master
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'crm_ProfileIntf, core_SettingsIntf';
    
    
    public $details = 'AuthorizationsList=remote_Authorizations,Personalization=crm_Personalization';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Профили';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Профил';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/user-profile.png';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'userId';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'plg_Created,crm_Wrapper,plg_RowTools, plg_Printing, plg_Search, plg_Rejected';
    
    
    /**
     * Кой  може да пише?
     */
    public $canWrite = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да листва всички профили?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'crm/tpl/SingleProfileLayout.shtml';
    
    
    /**
     *Кой има достъп до единичния изглед
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Полета за списъчния изглед
     */
    public $listFields = 'userId,personId,lastLoginTime=Последно логване';
    
    
    /**
     * Кой може да види IP-то от последното логване
     */
    public $canViewip = 'powerUser';
    
    
    /**
     * Кой може да види действията на потребителя
     */
    public $canViewlogact = 'powerUser';
    
    
    /**
     * Кой може да види действията на потребителя
     */
    public $canViewrolesact = 'powerUser';
    
    
    /**
     * Поле за търсене
     */
    public $searchFields = 'userId, personId';
    
    
    public $canReject = 'admin';
    
    
    public $canRestore = 'manager, admin';
    
    
    public $canDelete = 'no_one';
    
    
    /**
     * Помощен масив за типовете дни
     */
    public static $map = array('missing' => 'Отсъстващи','sickDay' => 'Болничен','leaveDay' => 'Отпуск', 'tripDay' => 'Командировка');
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('userId', 'key(mvc=core_Users, select=nick)', 'caption=Потребител,mandatory,notNull');
        $this->FLD('personId', 'key(mvc=crm_Persons, select=name, group=users)', 'input=hidden,silent,caption=Визитка,mandatory,notNull,tdClass=nowrap');
        $this->EXT('lastLoginTime', 'core_Users', 'externalKey=userId,input=none,smartCenter');
        $this->XPR('lastTime', 'datetime', 'if(#lastLoginTime, #lastLoginTime, #createdOn)', 'input=none,smartCenter');
        
        $this->EXT('state', 'core_Users', 'externalKey=userId,input=none');
        $this->EXT('exState', 'core_Users', 'externalKey=userId,input=none');
        $this->EXT('lastUsedOn', 'core_Users', 'externalKey=userId,input=none');
        
        $this->FLD('stateInfo', 'varchar', 'caption=Статус->Информация,input=none');
        $this->FLD('stateDateFrom', 'datetime(format=smartTime, defaultTime=00:00:00)', 'caption=Статус->От,input=none');
        $this->FLD('stateDateTo', 'datetime(format=smartTime, defaultTime=23:59:59)', 'caption=Статус->До,input=none');
        
        $this->setDbUnique('userId');
        $this->setDbUnique('personId');
    }
    
    
    /**
     * Възстановяване на оттеглен обект
     *
     * @param core_Mvc     $mvc
     * @param mixed        $res
     * @param int|stdClass $id
     */
    public static function on_BeforeRestore(core_Mvc $mvc, &$res, $id)
    {
        // Полето state е EXT поле към core_Users
        // Затова променяме състоянието там
        
        $res = false;
        $rec = $mvc->fetchRec($id);
        
        if (!isset($rec->id) || $rec->state != 'rejected') {
            
            return;
        }
        
        $coreUsers = cls::get('core_Users');
        $uRec = $coreUsers->fetch($rec->userId);
        $coreUsers->logInAct('Възстановяване', $uRec);
        
        $res = $coreUsers->restore($rec->userId);
        
        return false;
    }
    
    
    /**
     * Оттегляне на запис
     *
     * @param core_Mvc     $mvc
     * @param mixed        $res
     * @param int|stdClass $id
     */
    public static function on_BeforeReject(core_Mvc $mvc, &$res, $id)
    {
        // Полето state е EXT поле към core_Users
        // Затова променяме състоянието там
        
        $res = false;
        $rec = $mvc->fetchRec($id);
        
        if (!isset($rec->id) || $rec->state == 'rejected') {
            
            return;
        }
        
        $coreUsers = cls::get('core_Users');
        $uRec = $coreUsers->fetch($rec->userId);
        
        $res = $coreUsers->reject($rec->userId);
        
        return false;
    }
    
    
    /**
     * След подготовка на тулбара за единичния изглед
     */
    public function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        // Подготвяме табовете за логове
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'log-tabs'));
        $url = getCurrentUrl();
        
        $url['logTab'] = 'login';
        $url['#'] = 'profileLoginLog';
        $tabs->TAB('login', 'Логин', $url);
        
        $url['logTab'] = 'action';
        $url['#'] = 'profileActionLog';
        $tabs->TAB('action', 'Действия', $url);
        
        $url['logTab'] = 'roleLogs';
        $url['#'] = 'roleLogs';
        $tabs->TAB('roleLogs', 'Роли', $url);
        
        $data->tabs = $tabs;
    }
    
    
    /**
     * Подготовка за рендиране на единичния изглед
     */
    public static function on_AfterPrepareSingle(crm_Profiles $mvc, $data)
    {
        // Ако има personId
        if ($data->rec->personId) {
            
            // Създаваме обекта
            $data->Person = new stdClass();
            
            // Вземаме записите
            $data->Person->rec = crm_Persons::fetch($data->rec->personId);
            
            // Подготвяме сингъла
            crm_Persons::prepareSingle($data->Person);
            
            // Ако няма служебен имейл - показваме личния
            if (!isset($data->Person->row->buzEmail) && isset($data->Person->row->email)) {
                $data->Person->row->buzEmail = $data->Person->row->email;
            }
            
            // Ако няма служебен телефон - показваме личния
            if (!isset($data->Person->row->buzTel) && isset($data->Person->row->tel)) {
                $data->Person->row->buzTel = $data->Person->row->tel;
            }
            
            // Ако има права за сингъл
            if (crm_Persons::haveRightFor('single', $data->Person->rec)) {
                if ($data->Person->rec->id) {
                    // Добавяме бутон към сингъла на лицето
                    $data->toolbar->addBtn(tr('Визитка'), array('crm_Persons', 'single', $data->Person->rec->id), 'id=btnPerson', 'ef_icon = img/16/vcard.png');
                }
            }
            
            // Ако има вътрешен номер, показваме го
            if (core_Packs::isInstalled('callcenter')) {
                $numbersArr = callcenter_Numbers::getInternalNumbersForUsers(array($data->rec->userId));
                
                if (!empty($numbersArr)) {
                    $data->Person->row->internalNum = implode(', ', $numbersArr);
                }
            }
        }
        
        // Ако потребителя е контрактор и текущия е супер потребите
        // Показваме папките, в които е споделен
        if (core_Packs::isInstalled('colab')) {
            if (core_Users::haveRole('partner', $data->rec->userId) && core_Users::isPowerUser()) {
                $data->ColabFolders = new stdClass();
                
                // URL за промяна
                if (haveRole('admin,ceo,manager')) {
                    $data->ColabFolders->addUrl = array('colab_FolderToPartners', 'add', 'contractorId' => $data->rec->userId, 'ret_url' => true);
                }
                
                $data->ColabFolders->rowsArr = array();
                $sharedFolders = colab_Folders::getSharedFolders($data->rec->userId, false, null, false);
                
                $params = array('Ctr' => 'doc_Folders', 'Act' => 'list');
                foreach ($sharedFolders as $folderId) {
                    $params['folderId'] = $folderId;
                    $fRow = doc_Folders::recToVerbal(doc_Folders::fetch($folderId));
                    $data->ColabFolders->rowsArr[] = (object) (array('folderName' => $fRow->title . ' (' . $fRow->type . ')'));
                }
            }
        }
        
        // Ако има userId
        if ($data->rec->userId) {
            
            // Създаваме обекта
            $data->User = new stdClass();
            
            // Вземаме записите
            $data->User->rec = core_Users::fetch($data->rec->userId);
            
            // Вземаме вербалните стойности на записите
            $data->User->row = core_Users::recToVerbal($data->User->rec);
            
            // Ако е текущия потребител
            if (core_Users::getCurrent() == $data->User->rec->id) {
                
                // URL за промяна на профила
                $changePassUrl = array('crm_Profiles', 'changePassword', 'ret_url' => true);
                
                // Линк за промяна на URL
                $changePasswordLink = ht::createLink('(' . tr('смяна') . ')', $changePassUrl, false, 'title=Смяна на парола');
                
                // Променяме паролата
                $data->User->row->password = str_repeat('*', 7) . ' ' . $changePasswordLink;
            } else {
                // Премахваме информацията, която не трябва да се вижда от другите
                unset($data->User->row->password);
            }
            
            // Ако има роля admin
            if (haveRole('admin') && core_Users::haveRightFor('edit', $data->rec->userId)) {
                
                // Иконата за редактиране
                $img = '<img src=' . sbf('img/16/edit.png') . " width='16' height='16'>";
                
                // URL за промяна
                $url = array('core_Users', 'edit', $data->rec->userId, 'ret_url' => true);
                
                // Създаме линка
                $data->User->row->editLink = ht::createLink($img, $url, false, 'title=Редактиране на потребителски данни');
            }
            
            if ($data->User->rec->state != 'active') {
                $data->User->row->state = ht::createElement('span', array('class' => 'state-' . $data->User->rec->state, 'style' => 'padding:2px;'), $data->User->row->state);
            } else {
                unset($data->User->row->state);
            }
            
            $reqTab = Request::get('logTab');
            $data->LogTab = ($reqTab) ? $reqTab : 'login';
            
            // Ако има права за виждане на IP-то на последно логване
            if ($mvc->haveRightFor('viewip', $data->rec)) {
                
                // Ако има права за виждане на записите от лога
                if (($data->LogTab == 'login') && core_LoginLog::haveRightFor('viewlog')) {
                    $data->HaveRightForLog = true;
                    
                    // Създаваме обекта
                    $data->LoginLog = new stdClass();
                    
                    // Вземаме записите
                    $data->LoginLog->recsArr = core_LoginLog::getLastAttempts($data->rec->userId, 5);
                    
                    // Вземаме вербалните стойности
                    foreach ((array) $data->LoginLog->recsArr as $key => $logRec) {
                        $data->LoginLog->rowsArr[$key] = core_LoginLog::recToVerbal($logRec);
                        
                        // Ако има зададен клас
                        if ($data->LoginLog->rowsArr[$key]->ROW_ATTR['class']) {
                            $data->LoginLog->rowsArr[$key]->logClass = $data->LoginLog->rowsArr[$key]->ROW_ATTR['class'];
                        } else {
                            $data->LoginLog->rowsArr[$key]->logClass = 'loginLog-' . $logRec->status;
                        }
                    }
                    
                    // Ако има роля admin
                    if (core_LoginLog::haveRightFor('list')) {
                        
                        // id на потребитяля за търсене
                        $userTeams = type_User::getUserFromTeams($data->rec->userId);
                        reset($userTeams);
                        $userId = key($userTeams);
                        
                        $attr = array('ef_icon' => '/img/16/page_go.png', 'title' => 'Логвания на потребителя');
                        
                        // URL за промяна
                        $loginLogUrl = array('core_LoginLog', 'list', 'users' => $userId, 'ret_url' => true);
                        
                        $data->LoginLog->row = new stdClass();
                        
                        // Създаме линка
                        $data->LoginLog->row->loginLogLink = ht::createLink(tr('Още...'), $loginLogUrl, false, $attr);
                    }
                }
            } else {
                unset($data->User->row->lastLoginIp);
            }
            
            // Рендираме екшън лога на потребителя
            if (($data->LogTab == 'action') && $mvc->haveRightFor('viewlogact', $data->rec)) {
                $data->HaveRightForLog = true;
                
                $data->ActionLog = new stdClass();
                
                $userLogAct = log_Data::getLogsForUser($data->rec->userId, 20);
                
                $data->ActionLog->rowsArr = $userLogAct['rows'];
                $data->ActionLog->pager = $userLogAct['pager'];
                
                // Ако има роля admin
                if (log_Data::haveRightFor('list')) {
                    
                    // id на потребитяля за търсене
                    $userTeams = type_User::getUserFromTeams($data->rec->userId);
                    reset($userTeams);
                    $userId = key($userTeams);
                    
                    $attr = array();
                    $attr['ef_icon'] = '/img/16/page_go.png';
                    $attr['title'] = 'Екшън лог на потребителя';
                    
                    // URL за промяна
                    $loginLogUrl = array('log_Data', 'list', 'users' => $userId, 'ret_url' => true);
                    
                    // Създаме линка
                    $data->ActionLog->actionLogLink = ht::createLink(tr('Още...'), $loginLogUrl, false, $attr);
                }
            }
            
            // Рендираме екшън лога на потребителя
            if (($data->LogTab == 'roleLogs') && $mvc->haveRightFor('viewrolesact', $data->rec)) {
                $data->HaveRightForLog = true;
                
                $data->RoleLogs = new stdClass();
                
                $userLogAct = core_RoleLogs::getLogsForUser($data->rec->userId, 5);
                $data->RoleLogs->rowsArr = $userLogAct['rows'];
                $data->RoleLogs->pager = $userLogAct['pager'];
                
                // Ако има роля admin
                if (!empty($userLogAct['rows']) && core_RoleLogs::haveRightFor('list')) {
                    
                    // id на потребитяля за търсене
                    $userTeams = type_User::getUserFromTeams($data->rec->userId);
                    reset($userTeams);
                    $userId = key($userTeams);
                    
                    $attr = array();
                    $attr['ef_icon'] = '/img/16/page_go.png';
                    $attr['title'] = 'История на смяната на ролите';
                    
                    // Създаме линка
                    $data->RoleLogs->actionLogLink = ht::createLink(tr('Още...'), array('core_RoleLogs', 'list', 'users' => $userId, 'ret_url' => true), false, $attr);
                }
            }
        }
        
        // Бутон за персонализиране
        $key = self::getSettingsKey();
        $currUser = core_Users::getCurrent();
        if (self::canModifySettings($key, $data->rec->userId)) {
            core_Settings::addBtn($data->toolbar, $key, 'crm_Profiles', $data->rec->userId, 'Персонализиране');
        }
        
        if (bgerp_Portal::haveRightFor('list')) {
            $data->toolbar->addBtn('Портал', array('bgerp_Portal', 'list'), 'ef_icon=img/16/application_home.png');
        }
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * Използва crm_Persons::renderSingle() за да рендира асоциирата визитка така, както тя
     * би се показала във визитника.
     *
     * По този начин използваме визитника (crm_Persons) за подготовка и рендиране на визитка, но
     * ефективно заобикаляме неговите ограничения за достъп. Целта е да осигурим достъп до
     * всички профилни визитки
     *
     * @param crm_Profiles $mvc
     * @param core_ET      $tpl
     * @param stdClass     $data
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        // Вземам шаблона за лицето
        $pTpl = new ET(tr('|*' . getFileContent('crm/tpl/SingleProfilePersonLayout.shtml')));
        
        // Заместваме данните
        $pTpl->placeObject($data->Person->row);
        
        // Заместваме в шаблона
        $tpl->prepend($pTpl, 'personInfo');
        
        // Вземаме шаблона за потребителя
        $uTpl = new ET(tr('|*' . getFileContent('crm/tpl/SingleProfileUserLayout.shtml')));
        
        // Заместваме данните
        $uTpl->placeObject($data->User->row);
        
        // Заместваме в шаблона
        $tpl->prepend($uTpl, 'userInfo');
        
        if (isset($data->rec->stateInfo, $data->rec->stateDateFrom, $data->rec->stateDateTo)) {
            $status = self::getVerbalStatus($data->rec->stateInfo, $data->rec->stateDateFrom, $data->rec->stateDateTo);
            $tpl->append($status, 'userStatus');
            $tpl->append('statusClass', 'userClass');
        }
        
        // Показваме достъпните папки на колабораторите
        if ($data->ColabFolders) {
            $colabTpl = new ET(tr('|*' . getFileContent('crm/tpl/SingleProfileColabFoldersLayout.shtml')));
            
            if ($data->ColabFolders->rowsArr) {
                $folderBlockTpl = $colabTpl->getBlock('folders');
                foreach ((array) $data->ColabFolders->rowsArr as $rows) {
                    $folderBlockTpl->placeObject($rows);
                    $folderBlockTpl->append2Master();
                }
            }
            
            if ($data->ColabFolders->addUrl) {
                // Иконата за редактиране
                $img = '<img src=' . sbf('img/16/add.png') . " width='16' height='16'>";
                $addLink = ht::createLink($img, $data->ColabFolders->addUrl, null, 'title=Споделяне на нова папка');
                $colabTpl->replace($addLink, 'addColabBtn');
            }
            
            $tpl->prepend($colabTpl, 'colabFolders');
        }
        
        if ($data->LoginLog) {
            if ($data->LoginLog->rowsArr) {
                // Вземаме шаблона за потребителя
                $lTpl = new ET(tr('|*' . getFileContent('crm/tpl/SingleProfileLoginLogLayout.shtml')));
                
                $logBlockTpl = $lTpl->getBlock('log');
                
                foreach ((array) $data->LoginLog->rowsArr as $rows) {
                    $logBlockTpl->placeObject($rows);
                    $logBlockTpl->append2Master();
                }
                
                // Заместваме данните
                $lTpl->append($data->LoginLog->row->loginLogLink, 'loginLogLink');
            } else {
                $lTpl = new ET(tr('Няма данни'));
            }
        }
        
        if ($data->ActionLog) {
            if ($data->ActionLog->rowsArr) {
                $lTpl = getTplFromFile('crm/tpl/SingleProfileActionLogLayout.shtml');
                
                $logBlockTpl = $lTpl->getBlock('log');
                
                foreach ((array) $data->ActionLog->rowsArr as $rows) {
                    $logBlockTpl->placeObject($rows);
                    $logBlockTpl->replace($rows->ROW_ATTR['class'], 'logClass');
                    $logBlockTpl->append2Master();
                }
                
                $lTpl->append($data->ActionLog->pager->getHtml(), 'pager');
                $lTpl->append($data->ActionLog->actionLogLink, 'actionLogLink');
            } else {
                $lTpl = new ET(tr('Няма данни'));
            }
        }
        
        if ($data->RoleLogs) {
            if ($data->RoleLogs->rowsArr) {
                $lTpl = getTplFromFile('crm/tpl/SingleProfileRoleLogsLayout.shtml');
                
                $logBlockTpl = $lTpl->getBlock('log');
                
                foreach ((array) $data->RoleLogs->rowsArr as $rows) {
                    $logBlockTpl->placeObject($rows);
                    $logBlockTpl->replace($rows->ROW_ATTR['class'], 'logClass');
                    $logBlockTpl->append2Master();
                }
                
                $lTpl->append($data->RoleLogs->pager->getHtml(), 'pager');
                $lTpl->append($data->RoleLogs->actionLogLink, 'actionLogLink');
            } else {
                $lTpl = new ET(tr('Няма данни'));
            }
        }
        
        if (isset($data->tabs) && $data->HaveRightForLog) {
            if (!$lTpl) {
                $lTpl = new ET(tr('Липсва информация'));
            }
            $tabHtml = $data->tabs->renderHtml($lTpl, $data->LogTab);
            $tpl->replace($tabHtml, 'logTabs');
        }
    }
    
    
    /**
     * Връща вербална инфирмация за отсъствието
     *
     * @param string $type - вид на отсъствието
     * @param string $from - начална дата
     * @param string $to   - крайна дата
     *
     * @return string;
     */
    public static function getVerbalStatus($type, $from, $to)
    {
        $Date = cls::get('type_Date');
        
        list($dateFrom, $hoursFrom) = explode(' ', $from);
        list($dateTo, $hoursTo) = explode(' ', $to);
        
        list($today, $hoursToday) = explode(' ', dt::verbal2mysql());
        list($yesterday, $hoursYesterday) = explode(' ', dt::addDays(-1));
        list($tomorrow, $hoursTomorrow) = explode(' ', dt::addDays(1));
        
        $status = tr(static::$map[$type]) . ' ';
        
        if ($dateFrom == $dateTo && ($dateFrom == $yesterday || $dateFrom == $today || $dateFrom == $tomorrow)) {
            $status .= dt::mysql2verbal($from, 'smartDate');
        } elseif ($dateFrom == $dateTo) {
            $status .= tr('на') . ' ' . dt::mysql2verbal($from, 'smartDate');
        } else {
            $status .= tr('от') . ' ' . dt::mysql2verbal($from, 'smartDate') . ' ' . tr('до') . ' ' . dt::mysql2verbal($to, 'smartDate');
        }
        
        return $status;
    }
    
    
    /**
     * Екшън за смяна на парола
     *
     * return core_ET
     */
    public function act_ChangePassword()
    {
        requireRole('powerUser');
        
        $form = $this->prepareChangePassword();
        
        // Въвежда и проверява формата за грешки
        $form->input();
        
        if ($form->isSubmitted()) {
            $this->validateChangePasswordForm($form);
            if (!$form->gotErrors()) {
                $msg = '';
                
                // Записваме данните
                if ($form->rec->passNewHash && core_Users::setPassword($form->rec->passNewHash)) {
                    // Правим запис в лога
                    self::logWrite('Смяна на парола', $form->rec->id);

//             		if (EF_USSERS_EMAIL_AS_NICK) {
//             		    $userId = core_Users::fetchField(array("#email = '[#1#]'", $form->rec->email));
//                    } else {
//                        $userId = core_Users::fetchField(array("#nick = '[#1#]'", $form->rec->nick));
//                    }
//
                    //	                core_LoginLog::add('pass_change', $userId);
                    
                    $msg .= '|Паролата е променена успешно|*.';
                }
                
                // Редиректваме към предварително установения адрес
                return new Redirect(getRetUrl(), $msg);
            }
        }
        
        // Кои полета да се показват
        $form->showFields = (($form->fields['nick']) ? 'nick' : 'email') . ',passEx,passNew,passRe';
        
        // Получаваме изгледа на формата
        $tpl = $form->renderHtml();
        
        // Опаковаме изгледа
        $tpl = static::renderWrapping($tpl);
        
        return $tpl;
    }
    
    
    /**
     * Подготовка на формата за смяна на паролата
     */
    public function prepareChangePassword()
    {
        $form = cls::get('core_Form');
        
        //Ако е активирано да се използват имейлите, като никове тогава полето имейл го правим от тип имейл, в противен случай от тип ник
        if (EF_USSERS_EMAIL_AS_NICK) {
            //Ако използваме имейлите вместо никове, скриваме полето ник
            $form->FLD('email', 'email(link=no)', 'caption=Имейл,mandatory,width=100%');
            $nickField = 'email';
        } else {
            //Ако не използвам никовете, тогава полето трябва да е задължително
            $form->FLD('nick', 'nick(64)', 'caption=Ник,mandatory,width=100%');
            $nickField = 'nick';
        }
        
        $form->setDefault($nickField, core_Users::getCurrent($nickField));
        $form->setReadOnly($nickField);
        
        // Стара парола, когато се изисква при задаване на нова парола
        $passExHint = 'Въведете досегашната си парола';
        $form->FNC('passEx', 'password(allowEmpty,autocomplete=off)', "caption=Стара парола,input,hint={$passExHint},width=15em");
        $form->FNC('passExHash', 'varchar', 'caption=Хеш на старата парола,input=hidden');
        
        // Нова парола и нейния производен ключ
        $minLenHint = 'Паролата трябва да е минимум|* ' . EF_USERS_PASS_MIN_LEN . ' |символа';
        $form->FNC('passNew', 'password(allowEmpty,autocomplete=off)', "caption=Нова парола,input,hint={$minLenHint},width=15em");
        $form->FNC('passNewHash', 'varchar', 'caption=Хеш на новата парола,input=hidden');
        
        // Повторение на новата парола
        $passReHint = 'Въведете отново паролата за потвърждение, че сте я написали правилно';
        $form->FNC('passRe', 'password(allowEmpty,autocomplete=off)', 'caption=Нова парола (пак),input,width=15em', array('hint' => $passReHint));
        
        // Подготвяме лентата с инструменти на формата
        $form->toolbar->addSbBtn('Запис', 'change_password', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');
        
        // Потготвяме заглавието на формата
        $form->title = 'Смяна на паролата';
        $form->rec->passExHash = '';
        $form->rec->passNewHash = '';
        
        core_Users::setUserFormJS($form);
        
        return $form;
    }
    
    
    /**
     * Проверка за валидност на формата за смяна на паролата
     *
     * @param core_Form
     */
    public function validateChangePasswordForm(core_Form &$form)
    {
        core_Users::calcUserForm($form);
        
        $rec = $form->rec;
        
        if (core_Users::fetchField(core_Users::getCurrent(), 'ps5Enc') != $rec->passExHash) {
            $form->setError('passEx', 'Грешна стара парола');
        }
        
        if ($rec->isLenOK == - 1) {
            $form->setError('passNew', 'Паролата трябва да е минимум |* ' . EF_USERS_PASS_MIN_LEN . ' |символа');
        } elseif ($rec->passNew != $rec->passRe) {
            $form->setError('passNew,passRe', 'Двете пароли не съвпадат');
        } elseif (!$rec->passNewHash) {
            $form->setError('passNew,passRe', 'Моля, въведете (и повторете) новата парола');
        }
    }
    
    
    public static function on_AfterInputEditForm(crm_Profiles $mvc, core_Form $form)
    {
        $form->rec->_syncUser = true;
    }
    
    
    /**
     * Подготвя списък с потребители, които нямат профили
     *
     * @param stdClass $data
     * @param NULL|int $limit
     *
     * @return array
     */
    public static function prepareUnusedUserOptions($data, $limit = null)
    {
        $type = 'prepareUnusedUserOptions';
        $handler = 'unusedUserOptions' . '|' . $limit . '|' . $data->form->rec->id;
        $keepMinutes = 10000;
        $depends = 'crm_Profiles';
        
        $opt = core_Cache::get($type, $handler, $keepMinutes, $depends);
        
        if (isset($opt) && ($opt !== false)) {
            
            return $opt;
        }
        
        $query = self::getQuery();
        $query->show('userId');
        
        $used = array();
        while ($rec = $query->fetch()) {
            if (!isset($data->form->rec->id) || $rec->id != $data->form->rec->id) {
                $used[$rec->userId] = true;
            }
        }
        
        $usersQuery = core_Users::getQuery();
        $usersQuery->show('id, nick');
        
        $opt = array();
        while ($uRec = $usersQuery->fetch("#state = 'active'")) {
            if (!$used[$uRec->id]) {
                $opt[$uRec->id] = $uRec->nick;
                
                if (isset($limit) && !--$limit) {
                    break;
                }
            }
        }
        
        core_Cache::set($type, $handler, $opt, $keepMinutes, $depends);
        
        return $opt;
    }
    
    
    /**
     * Подготвя формата за асоцииране на потребител с профил
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $opt = self::prepareUnusedUserOptions($data);
        
        $data->form->setOptions('userId', $opt);
        
        $addUserUrl = array(
            'core_Users',
            'add',
            'personId' => Request::get('personId', 'key(mvc=crm_Persons)'),
            'ret_url' => getRetUrl()
        );
        
        $data->form->setField('personId', 'input');
        
        $data->form->toolbar->addBtn('Нов потребител', $addUserUrl, 'ef_icon = img/16/star_2.png');
    }
    
    
    /**
     * Промяна на данните на асоциирания потребител след запис на визитка
     *
     * @param core_Mvc    $mvc
     * @param stdClass    $rec
     * @param core_Master $master
     */
    public static function on_AfterMasterSave(crm_Profiles $mvc, $personRec, core_Master $master)
    {
        if (get_class($master) != 'crm_Persons') {
            
            return;
            expect(get_class($master) == 'crm_Person'); // дали не е по-добре така?
        }
        
        // След промяна на профилна визитка, променяме името и имейла на асоциирания потребител
        static::syncUser($personRec);
    }
    
    
    public static function on_AfterSave(crm_Profiles $mvc, $id, $profile)
    {
        if ($profile->_syncUser) {
            // Флага _sync се вдига само на crm_Profiles::on_AfterInputEditForm().
            $person = crm_Persons::fetch($profile->personId);
            $mvc::syncUser($person);
        }
    }
    
    
    /**
     * Визитката, асоциирана с потребителски акаунт
     *
     * @param int $userId
     *
     * @return stdClass
     */
    public static function getProfile($userId = null)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent('id');
        }
        
        if (!$profile = static::fetch("#userId = {$userId}")) {
            
            return;
        }
        
        return crm_Persons::fetch($profile->personId);
    }
    
    
    /**
     * Синхронизиране на данните (имена, имейл) на профилната визитка с тези на потребител.
     *
     * Ако лице с ключ $personId не съществува - създава се нов потребител.
     *
     *
     * @param int      $personId key(mvc=crm_Persons), може и да е NULL
     * @param stdClass $user
     *
     * @return int|bool personId или FALSE при неуспешен запис
     */
    public static function syncPerson($personId, $user)
    {
        if ($user->_skipPersonUpdate) {
            // След запис на визитка се обновяват данните (имена, имейл) на асоциирания с нея
            // потребител. Ако сме стигнали до тук по този път, не обновяваме отново данните
            // на визитката след промяна на потребителя, защото това води до безкраен цикъл!
            return;
        }
        
        if (!empty($personId)) {
            $person = crm_Persons::fetch($personId);
        }
        
        if (empty($person)) {
            $person = (object) array(
                'groupList' => '',
                'access' => 'private',
                'email' => ''
            );
            if (isset($user->country)) {
                $person->country = drdata_Countries::getIdByName($user->country);
            } elseif (!isset($person->country)) {
                $person->country = drdata_Countries::getByIp();
            }
            
            if (!isset($person->country)) {
                $person->country = drdata_Countries::getIdByName('Bulgaria');
            }
            $mustSave = true;
        }
        
        // Задаваме групата
        $profilesGroup = crm_Groups::fetch("#sysId = 'users'");
        $Persons = cls::get('crm_Persons');
        $groupExpandField = $Persons->expandInputFieldName;
        $exGroupList = $person->{$groupExpandField};
        if ($user->state == 'rejected') {
            $person->{$groupExpandField} = keylist::removeKey($person->{$groupExpandField}, $profilesGroup->id);
        } else {
            $person->{$groupExpandField} = keylist::addKey($person->{$groupExpandField}, $profilesGroup->id);
        }
        if ($person->{$groupExpandField} != $exGroupList) {
            $mustSave = true;
        }
        
        if (!empty($user->names) && ($person->name != $user->names)) {
            $person->name = $user->names;
            $mustSave = true;
        }
        
        // Само ако записа на потребителя има
        if (!empty($user->email) && (strpos($person->email, $user->email) === false)) {
            $person->email = type_Emails::prepend($person->email, $user->email);
            
            $mustSave = true;
        }
        
        // Само, ако записа на потребителя има мобилен телефон
        if (!empty($user->mobile) && ($person->mobile != $user->mobile)) {
            $person->mobile = $user->mobile;
            $mustSave = true;
        }
        
        // Само ако досега визитката не е имала inCharge, променения потребител и става отговорник
        if (!$person->inCharge) {
            
            // Ако създадения потребител е partner
            if (core_Users::haveRole('partner', $user->id)) {
                
                // За отговорник стават първия админ/ceo
                $person->inCharge = doc_FolderPlg::getDefaultInCharge();
            } else {
                
                // Ако е powerUse Лицето става отговорник на папката си
                $person->inCharge = $user->id;
            }
            
            $mustSave = true;
        }
        
        $person->_skipUserUpdate = true; // Флаг за предотвратяване на безкраен цикъл
        
        
        if ($mustSave) {
            crm_Persons::save($person);
            
            Mode::push('preventNotifications', true);
            if (core_Packs::isInstalled('colab') && core_Users::isContractor($user)) {
                $privateFolderId = crm_Persons::forceCoverAndFolder($person->id);
                if (!colab_FolderToPartners::fetch("#folderId = {$privateFolderId} AND #contractorId = {$user->id}")) {
                    colab_FolderToPartners::save((object) array('folderId' => $privateFolderId, 'contractorId' => $user->id));
                }
            }
            Mode::pop('preventNotifications');
            
            // Синхронизираме профила при промяна на `core_Users`
            if ($person->id) {
                $profile = static::fetch("#personId = {$person->id}");
                if ($profile) {
                    $profile->_syncUser = false;
                    $profile->_skipUserUpdate = true;
                    self::save($profile, 'searchKeywords');
                }
            }
        }
        
        return $person->id;
    }
    
    
    /**
     * Обновяване на данните на асоциирания потребител след промяна във визитка
     *
     * @param stdClass $personRec
     */
    public static function syncUser($personRec)
    {
        if ($personRec->_skipUserUpdate) {
            
            return;
        }
        
        $profile = static::fetch("#personId = {$personRec->id}");
        
        if (!$profile) {
            
            return;
        }
        
        // Обновяване на записа на потребителя след промяна на асоциираната му визитка
        if (!$userRec = core_Users::fetch($profile->userId)) {
            
            return;
        }
        
        // Обновяваме имейла, само ако зададения в потребителя, липсва в списъка му с лични
        if (!empty($personRec->email)) {
            // Вземаме първия (валиден!) от списъка с лични имейли на лицето
            $emails = type_Emails::toArray($personRec->email);
            if (!empty($emails) && !in_array($userRec->email, $emails)) {
                $userRec->email = reset($emails);
            }
        }
        
        if (!empty($personRec->name)) {
            $userRec->names = $personRec->name;
        }
        
        if (!empty($personRec->photo)) {
            if (is_readable(fileman_Files::fetchByFh($personRec->photo, 'path'))) {
                $userRec->avatar = $personRec->photo;
            }
        }
        
        // Флаг за предотвратяване на безкраен цикъл след промяна на визитка
        $userRec->_skipPersonUpdate = true;
        
        // Синхронизираме профила при промяна на `core_Users`
        if ($profile) {
            $profile->_syncUser = false;
            $profile->_skipUserUpdate = true;
            self::save($profile, 'searchKeywords');
        }
        
        core_Users::save($userRec);
    }
    
    
    /**
     * Функция, която връща id от този модел, който отговаря на userId
     *
     * @param int $userId
     *
     * @return int
     */
    public static function getProfileId($userId)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $profileId = self::fetch("#userId = {$userId}")->id;
        
        return $profileId;
    }
    
    
    /**
     * URL към профилната визитка на потребител
     *
     * @param string|int $user ако е числова стойност се приема за ид на потребител; иначе - ник
     *
     * @return array URL към визитка; FALSE ако няма такъв потребител или той няма профилна визитка
     */
    public static function getUrl($userId)
    {
        // Извличаме профила (връзката м/у потребител и визитка)
        $profileId = self::getProfileId($userId);
        
        if (!$profileId) {
            
            // Няма профил или не е асоцииран с визитка
            return false;
        }
        
        return array(get_called_class(), 'single', $profileId);
    }
    
    
    /**
     * Метод за удобство при генерирането на линкове към потребителски профили
     *
     * @param string|int $user    @see crm_Profiles::getUrl()
     * @param string     $title   @see core_Html::createLink()
     * @param string     $warning @see core_Html::createLink()
     * @param array      $attr    @see core_Html::createLink()
     */
    public static function createLink($userId = null, $title = null, $warning = false, $attr = array())
    {
        static $cacheArr = array();
        
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $isOut = (boolean) (Mode::is('text', 'xhtml') || Mode::is('pdf'));
        
        $key = "{$userId}|{$title}|{$warning}|{$isOut}|" . implode('|', $attr);
        
        if (!$cacheArr[$key]) {
            $userRec = core_Users::fetch($userId);
            
            if (!$userRec) {
                $userRec = core_Users::fetch(0);
            }
            
            if (!$title) {
                $title = self::getUserTitle($userRec->nick);
            }
            
            $link = $title;
            
            $url = array();
            
            $profRec = self::fetch("#userId = {$userId}");
            
            $attr['class'] .= ' profile';
            
            if ($profRec && $profRec->stateDateFrom) {
                $attr['class'] .= ' ' . self::getAbsenceClass($profRec->stateDateFrom, $profRec->stateDateTo);
            }
            
            $profileId = self::getProfileId($userId);
            
            if ($profileId) {
                if (crm_Profiles::haveRightFor('single', $profileId) && !$isOut) {
                    $url = static::getUrl($userId);
                }
                
                if (isset($attr['external'])) {
                    $url = toUrl(static::getUrl($userId), 'absolute');
                }
                
                foreach (array('ceo', 'manager', 'officer', 'executive', 'partner', 'none') as $role) {
                    if ($role == 'none') {
                        $attr['style'] .= ';color:#333;';
                        break;
                    }
                    if (core_Users::haveRole($role, $userId)) {
                        $attr['class'] .= " {$role}";
                        break;
                    }
                }
                
                if (core_Users::haveRole('no_one', $userId)) {
                    $attr['class'] .= ' no-one';
                }
                
                if ($userRec->lastActivityTime) {
                    $before = time() - dt::mysql2timestamp($userRec->lastActivityTime);
                }
                
                if (($before !== null) && $before < 5 * 60) {
                    $attr['class'] .= ' active';
                } elseif (!$before || $before > 60 * 60) {
                    $attr['class'] .= ' inactive';
                }
                
                if ($userRec->state != 'active') {
                    $attr['class'] .= ' state-' . $userRec->state;
                }
                
                $attr['title'] = '|*' . $userRec->names;
                
                $link = ht::createLink($title, $url, $warning, $attr);
            } else {
                $attr['style'] .= ';color:#999 !important;';
                $link = ht::createLink($userRec->nick, null, null, $attr);
            }
            
            $cacheArr[$key] = $link;
        }
        
        return $cacheArr[$key];
    }
    
    
    /**
     * Връща клас за ника, според началото на отсъствието и края му
     */
    public static function getAbsenceClass($from, $to)
    {
        list($dateFrom, ) = explode(' ', $from);
        list($dateTo, ) = explode(' ', $to);
        $nextWorkingDay = cal_Calendar::nextWorkingDay(null, 0);
        $today = dt::now(false);
        
        $res = '';
        
        if ($dateFrom <= $today && $today <= $dateTo) {
            $res = 'profile-state';
        } elseif ($dateFrom <= $nextWorkingDay && $nextWorkingDay <= $dateTo) {
            $res = 'profile-state-tomorrow';
        }
        
        return $res;
    }
    
    
    /**
     * Обработва ника на потребителя, така, че да изглежда добре
     */
    public static function getUserTitle($nick)
    {
        list($l, $r) = explode('@', $nick);
        $title = type_Nick::normalize($l);
        if ($r) {
            $title .= '@' . $r;
        }
        
        return $title;
    }
    
    
    /**
     *
     *
     * @param int  $id
     * @param bool $escape
     */
    public static function getTitleForId_($id, $escaped = true)
    {
        return self::getVerbal($id, 'userId');
    }
    
    
    /**
     * След подготовката на листовия тулбар
     *
     * Прави ника и името линкове към профилната визитка (в контекста на crm_Profiles)
     *
     * @param crm_Profiles $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareListToolbar(crm_Profiles $mvc, $data)
    {
        $toolbar = $data->toolbar;
        
        $toolbar->removeBtn('btnAdd');
        
        
        if ($mvc->haveRightFor('add')) {
            if (count(self::prepareUnusedUserOptions($data, 1))) {
                $toolbar->addBtn(
                    'Асоцииране',
                    array(
                        $mvc,
                        'add'
                    ),
                    'id=btnAdd',
                    'ef_icon = img/16/link.png,title=Асоцииране на визитка с потребител'
                );
            }
            $toolbar->addBtn(
            'Нов потребител',
            array(
                'core_Users',
                'add',
                'ret_url' => true,
            ),
                    'id=new',
            'ef_icon=img/16/star_2.png,title=Добавяне на нов потребител'
        );
        }
    }
    
    
    /**
     * След подготовката на редовете на списъчния изглед
     *
     * Прави ника и името линкове към профилната визитка (в контекста на crm_Profiles)
     *
     * @param crm_Profiles $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareListRows(crm_Profiles $mvc, $data)
    {
        $rows = &$data->rows;
        $recs = &$data->recs;
        
        if (count($rows)) {
            foreach ($rows as $i => &$row) {
                $rec = &$recs[$i];
                
                if ($url = $mvc::getUrl($rec->userId)) {
                    
                    // Ако имаме права за сингъла, тогава създаваме линка
                    if (crm_Persons::haveRightFor('single', $rec->personId)) {
                        $personLink = array('crm_Persons', 'single', $rec->personId);
                    } else {
                        $personLink = null;
                    }
                    
                    $row->personId = ht::createLink($row->personId, $personLink, null, array('ef_icon' => 'img/16/vcard.png'));
                    
                    if (isset($rec->stateDateFrom, $rec->stateDateTo)) {
                        $status = self::getVerbalStatus($rec->stateInfo, $rec->stateDateFrom, $rec->stateDateTo);
                        $link = static::createLink($rec->userId, null, false, array('ef_icon' => $mvc->singleIcon));
                        $row->userId = ht::createHint($link, '|*' . $status, 'notice');
                    } else {
                        $row->userId = static::createLink($rec->userId, null, false, array('ef_icon' => $mvc->singleIcon));
                    }
                }
            }
        }
    }
    
    
    /**
     * Създаваме собствена форма за филтриране
     *
     * @param core_Mvc $mvc
     * @param object   $res
     * @param object   $data
     */
    public static function on_BeforePrepareListFilter($mvc, $res, &$data)
    {
        $formParams = array(
            'method' => 'GET',
            
            //            'toolbar' => ht::createSbBtn('Филтър')
        );
        $data->listFilter = cls::get('core_Form', $formParams);
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        $rec = $data->listFilter->rec;
        
        $data->listFilter->FNC('leave', 'enum(,missing=Отсъстващи,sickDay=Болничен,leaveDay=Отпуск,tripDay=Командировка)', 'width=6em,caption=Статус,silent,allowEmpty,autoFilter');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $fields = $data->listFilter->input();
        
        $data->listFilter->showFields .= 'search,leave';
        
        $data->query->orderBy('lastTime', 'DESC');
        
        // Ако е избран 'Отсъстващи'
        switch ($fields->leave) {
            
            case 'missing':
                
                $data->query->where("(#stateInfo = 'sickDay') OR (#stateInfo = 'leaveDay') OR (#stateInfo = 'tripDay')");
                break;
            case 'sickDay':
                
                $data->query->where("#stateInfo = 'sickDay'");
                break;
            
            case 'leaveDay':
                
                $data->query->where("#stateInfo = 'leaveDay'");
                break;
            
            case 'tripDay':
                
                $data->query->where("#stateInfo = 'tripDay'");
                break;
        }
    }
    
    
    /**
     *
     *
     * @param crm_Profiles $mvc
     * @param string       $requiredRoles
     * @param string       $action
     * @param object       $rec
     * @param int          $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Текущия потребител може да си види IP-то, admin и ceo могат на всичките
        if ($action == 'viewip') {
            if ($rec && ($rec->userId != $userId)) {
                if (!haveRole('ceo, admin')) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'viewlogact') {
            if ($rec) {
                if (!log_Data::canViewUserLog($rec->userId, $userId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'viewrolesact') {
            if ($rec) {
                if (!core_RoleLogs::canViewUserLog($rec->userId, $userId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Връща ключа за персонална настройка
     *
     * @param int $userId
     *
     * @return string
     */
    public static function getSettingsKey($userId = null)
    {
        $key = 'crm_Profiles';
        
        return $key;
    }
    
    
    /**
     * Може ли текущия потребител да пороменя сетингите на посочения потребител/роля?
     *
     * @param string $key
     * @param int    $userOrRole
     *
     * @see core_SettingsIntf
     */
    public static function canModifySettings($key, $userOrRole = null)
    {
        // Всеки може да променя собствените си настройки
        // admin|ceo могат да променят на всички
        
        $currUserId = core_Users::getCurrent();
        
        if ($currUserId == $userOrRole) {
            
            return true;
        }
        
        if (core_Users::fetch($userOrRole)->state == 'rejected') {
            
            return false;
        }
        
        if (haveRole('admin, ceo', $currUserId)) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Подготвя формата за настройки
     *
     * @param core_Form $form
     *
     * @see core_SettingsIntf
     */
    public function prepareSettingsForm(&$form)
    {
        // Задаваме таба на менюто да сочи към документите
        Mode::set('pageMenu', 'Указател');
        Mode::set('pageSubMenu', 'Визитник');
        $this->currentTab = 'Профили';
        
        $userOrRoleId = $form->rec->_userOrRole;
        
        $currUserId = core_Users::getCurrent();
        
        // Определяме заглавието на формата
        if ($userOrRoleId > 0) {
            $Users = cls::get('core_Users');
            $rec = $Users->fetch($userOrRoleId);
            $row = $Users->recToVerbal($rec, 'nick');
            $title = 'на|*: ' . $row->nick;
        } else {
            $roleId = type_UserOrRole::getRoleIdFromSys($userOrRoleId);
            
            $title = 'за роля';
        }
        
        // Определяме заглавито
        $form->title = 'Персонализиране на настройките ' . $title;
        
        $form->__defaultRec = new stdClass();
        
        $settingsDefArr = array();
        
        if ($form->rec->_userOrRole > 0) {
            // Настройките по-подразбиране за потребителя
            $settingsDefArr = core_Settings::fetchKey($form->rec->_key, $form->rec->_userOrRole, false);
        }
        
        // Стринг за подразбиране
        // Ако сме в мобилен режим, да не е хинт
        $paramType = Mode::is('screenMode', 'narrow') ? 'unit' : 'hint';
        if ($paramType == 'unit') {
            $defaultStr = '|*<br>|По подразбиране|*: ';
        } else {
            $defaultStr = 'По подразбиране|*: ';
        }
        
        $query = core_Packs::getQuery();
        while ($rec = $query->fetch()) {
            
            // Зареждаме сетъп пакета
            $clsName = $rec->name . '_Setup';
            if (!cls::load($clsName, true)) {
                continue;
            }
            $clsInst = core_Cls::get($clsName);
            
            // Ако няма полета за конфигуриране
            if (!($clsInst->getConfigDescription())) {
                continue;
            }
            
            // Флаг, който указва да не се вземат данните от настройките
            // Да не се инвоква функцията от плъгините
            $savedStopInvoke = core_ObjectConfiguration::$stopInvoke;
            core_ObjectConfiguration::$stopInvoke = true;
            
            $packConf = core_Packs::getConfig($rec->name);
            
            
            // Обхождаме всички полета за конфигуриране
            foreach ((array) $clsInst->getConfigDescription() as $field => $arguments) {
                
                // Коя стойност да се използва за полето
                $fieldVal = isset($settingsDefArr[$field]) ? $settingsDefArr[$field] : $packConf->$field;
                
                // Типа на полета
                $type = $arguments[0];
                
                // Параметри на полето
                $params = arr::combine($arguments[1], $arguments[2]);
                
                // Ако не е зададено, че може да се конфигурира или не може да се конфигурира за текущия потребител
                if (!$params['customizeBy'] || !haveRole(str_replace('|', ',', $params['customizeBy']), $currUserId)) {
                    continue;
                }
                
                // Ако не е зададено, заглавието на полето е неговото име
                setIfNot($params['caption'], '|*' . $field);
                
                $typeInst = core_Type::getByName($type);
                
                $isEnum = false;
                $isKey = false;
                
                // Ако е enum поле, добавя в началото да може да се избира автоматично
                if ($typeInst instanceof type_Enum) {
                    $typeInst->options = array('default' => 'Автоматично') + (array) $typeInst->options;
                    $isEnum = true;
                } elseif (($typeInst instanceof type_Key) || ($typeInst instanceof type_Key2)) {
                    $isKey = true;
                }
                
                // Полето ще се въвежда
                $params['input'] = 'input';
                
                // Добавяме функционално поле
                $form->FNC($field, $typeInst, $params);
                
                if (isset($form->rec->$field) || $isEnum || $isKey) {
                    if ($paramType != 'unit') {
                        Mode::push('text', 'plain');
                        $defVal = $typeInst->toVerbal($fieldVal);
                        Mode::pop();
                    } else {
                        $defVal = $typeInst->toVerbal($fieldVal);
                    }
                    
                    
                    if ($defVal) {
                        $form->setParams($field, array($paramType => $defaultStr . $defVal));
                    }
                } else {
                    $form->setField($field, array('attr' => array('class' => 'const-default-value')));
                }
                
                if ($isEnum) {
                    $fieldVal = 'default';
                } elseif ($isKey) {
                    if ($typeInst->params['allowEmpty']) {
                        $fieldVal = '';
                    }
                }
                
                $form->setDefault($field, $fieldVal);
                
                $form->__defaultRec->$field = $fieldVal;
            }
            
            core_ObjectConfiguration::$stopInvoke = $savedStopInvoke;
        }
    }
    
    
    /**
     * Проверява формата за настройки
     * Премахва стойностите по-подразбиране
     *
     * @param core_Form $form
     *
     * @see core_SettingsIntf
     */
    public function checkSettingsForm(&$form)
    {
        // Премахва стойностите по-подразбиране
        $defRecArr = (array) $form->__defaultRec;
        foreach ($defRecArr as $field => $val) {
            if ($form->rec->$field == $val) {
                unset($form->rec->$field);
            }
        }
    }
    
    
    /**
     * Изпълнява се след закачане на детайлите
     *
     * @param crm_Profiles $mvc
     * @param NULL|array   $res
     * @param NULL|array   $details
     */
    public static function on_BeforeAttachDetails(crm_Profiles $mvc, &$res, &$details)
    {
        $details = arr::make($details, true);
        $mvc->details = arr::make($mvc->details, true);
        
        if (!core_Packs::isInstalled('remote')) {
            $remotePackKey = 'AuthorizationsList';
            unset($details[$remotePackKey]);
            unset($mvc->details[$remotePackKey]);
        }
    }
    
    
    /**
     * Връща потребителя към дадена визитка на човек. Възможно е да няма такъв
     *
     * @param int $peronId
     *
     * @return int $userId
     */
    public static function getUserByPerson($personId)
    {
        $rec = self::fetch("#personId = {$personId}");
        if ($rec) {
            
            return $rec->userId;
        }
    }
    
    
    /**
     * Връща id на визитка към потребител
     *
     * @param int $userId
     *
     * @return int $peronId
     */
    public static function getPersonByUser($userId)
    {
        $rec = self::fetch("#userId = {$userId}");
        if ($rec) {
            
            return $rec->peronId;
        }
    }
}
