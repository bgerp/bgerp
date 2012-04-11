<?php


/**
 * Дефинира, ако не е, колко време записът
 * за текущия потребител да е валиден в сесията
 */
defIfNot('EF_USERS_CURRENT_REC_LIFETIME', 20);


/**
 * Колко секунди може да е максимално разликата
 * във времето между времето изчислено при потребителя
 * и това във сървъра при логване. В нормален случай
 * това трябва да е повече от времето за http трансфер
 * на логин формата и заявката за логване
 */
defIfNot('EF_USERS_LOGIN_DELAY', 20);


/**
 * 'Подправка' за кодиране на паролите
 */
defIfNot('EF_USERS_PASS_SALT', '');


/**
 * Дали да се използва имейл адресът, вместо ник
 */
defIfNot('EF_USSERS_EMAIL_AS_NICK', FALSE);


/**
 * Как да се казва променливата на cookieто
 */
defIfNot('EF_USERS_COOKIE', 'uid');


/**
 * Какво е минималното време в секунди, между хитовете от
 * два различни IP адреса, за да не бъде блокирана сметката
 */
defIfNot('EF_USERS_MIN_TIME_WITHOUT_BLOCKING', 120);


/**
 * Колко дни потребител може да не активира първоначално достъпа си
 * преди да бъде изтрит
 */
defIfNot('USERS_DRAFT_MAX_DAYS', 3);


/**
 * Клас 'core_Users' - Мениджър за потребителите на системата
 *
 * Необходимия набор от функции за регистриране, логране и
 * дел-логване на потребители на системата
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Users extends core_Manager
{
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Потребители';
    
    
    /**
     * Плъгини и MVC класове за предварително зареждане
     */
    var $loadList = 'plg_Created,plg_Modified,plg_State,plg_SystemWrapper,core_Roles,plg_RowTools';
    
    
    /**
     * Кои колонки да се показват в табличния изглед
     */
    var $listFields = 'id,title=Имена,roles,last=Последно';
    
    
    /**
     * Дали в момента се работи със системния потребител (-1)
     */
    var $isSystemUser = FALSE;
    
    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        //Ако е активирано да се използват имейлите, като никове тогава полето имейл го правим от тип имейл, в противен случай от тип ник
        if (EF_USSERS_EMAIL_AS_NICK) {
            //Ако използваме имейлите вместо никове, скриваме полето ник
            $this->FLD('nick', 'email', 'caption=Ник,notNull, input=none');
        } else {
            //Ако не използвам никовете, тогава полето трябва да е задължително
            $this->FLD('nick', 'nick(64)', 'caption=Ник,notNull,mandatory');
        }
        
        $this->FLD('ps5Enc', 'varchar(32)', 'caption=Ключ,column=none,input=none');
        $this->FNC('password', 'password(autocomplete=on)', 'caption=Парола,column=none,input');
        
        $this->FLD('email', 'email(64)', 'caption=Имейл,mandatory,width=100%');
        $this->FLD('names', 'varchar', 'caption=Имена,mandatory,width=100%');
        $this->FLD('roles', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Роли,oldFieldName=Role');
        
        $this->FLD('state', 'enum(active=Активен,draft=Неактивиран,blocked=Блокиран,deleted=Изтрит)',
            'caption=Състояние,notNull,default=draft');
        
        $this->FLD('lastLoginTime', 'datetime', 'caption=Последно->Логване,input=none');
        $this->FLD('lastLoginIp', 'varchar(16)', 'caption=Последно->IP,input=none');
        
        $this->setDbUnique('nick');
        $this->setDbUnique('email');
    }
    
    
    /**
     * Изпълнява се след подготовка на данните за списъчния изглед
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy("lastLoginTime,createdOn", "DESC");
    }
    
    
    /**
     * Изпълнява се след създаване на формата за добавяне/редактиране
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        // Ако няма регистрирани потребители, първият задължително е администратор
        if(!$mvc->fetch('1=1')) {
            $data->form->setOptions('state' , array('active' => 'active'));
            $data->form->setOptions('roles' , array($mvc->core_Roles->fetchByName('admin') => 'admin'));
            $data->form->title = 'Първоначална регистрация на администратор';
            $data->form->setField('nick,email,password,names', 'width=15em');
            $data->form->showFields = 'nick,email,password,names';
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        //Ако не сме субмитнали формата връщаме управлението
        if (!$form->isSubmitted()) return ;
        
        //id' то на текущия запис
        $recId = $form->rec->id;
        
        //Проверяваме дали има такъв имейл
        if ($newRecId = $mvc->fetchField("LOWER(#email)=LOWER('{$form->rec->email}')")) {
            //Проверяваме дали редактираме текущия запис или създаваме нов
            if ($newRecId != $recId) {
                //Съобщение за грешка, ако имейл-а е зает
                $form->setError('email', "Има друг регистриран потребител с този имейл.");
            }
        }
        
        //Ако използваме имейл вместо ник и няма грешки
        if ((EF_USSERS_EMAIL_AS_NICK) && (!$form->gotErrors())) {
            
            //Задаваме ник-а да е равен на имейл-а
            $form->rec->nick = $form->rec->email;
            
            //Вземаме частта локалната част на имейл-а
            $nick = type_Nick::parseEmailToNick($form->rec->nick);
            
            //Проверяваме дали имаме такава папка
            if (!type_Nick::isValid($nick)) {
                //Ако има, тогава показваме съобщение за грешка
                $form->setError('email', 'Въвели сте недопустима стойност:|* ' . $form->rec->email);
            }
        }
    }
    
    
    /**
     * Форма за вход
     */
    function act_Login()
    {
        if (Request::get('popup')) {
            Mode::set('wrapper', 'tpl_BlankPage');
        }
        
        // Ако нямаме регистриран нито един потребител
        // и се намираме в дебъг режим, то тогава редиректваме
        // към вкарването на първия потребител (admin)
        if(isDebug() && !$this->fetch('1=1')) {
            return new Redirect(array(
                    $this,
                    'add',
                    'ret_url' => TRUE
                ));
        }
        
        // Проверяваме дали сме логнати
        $currentUserRec = Mode::get('currentUserRec');
        $retUrl = getRetUrl();
        $form = $this->getForm(array(
                'title' => "|*<img src=" . sbf('img/signin.png') . " align='top'>&nbsp;|Вход в|* " . EF_APP_TITLE,
                'name' => 'login'
            ));
        
        if (Request::get('popup')) {
            $form->setHidden('ret_url', toUrl(array('core_Browser', 'close'), 'local'));
        } else {
            $form->setHidden('ret_url', toUrl($retUrl, 'local'));
        }
        $form->setHidden('time', time());
        $form->setHidden('hash', '');
        $form->setHidden('loadTime', '');
        
        $form->addAttr('nick,password,email', array('style' => 'width:240px;'));
        $form->toolbar->addSbBtn('Вход', 'default', NULL,  array('class' => 'noicon'));
        
        $this->invoke('PrepareLoginForm', array(&$form));
        
        // Декриприраме cookie
        if ($cookie = $_COOKIE[EF_USERS_COOKIE]) {
            $Crypt = cls::get('core_Crypt');
            $cookie = $Crypt->decodeVar($cookie);
        }
        
        if (!$currentUserRec->state == 'active') {
            // Ако е зададено да се използва имейл-а за ник
            if (EF_USSERS_EMAIL_AS_NICK) {
                $inputs = $form->input('email,password,ps5Enc,ret_url,time,hash');
            } else {
                $inputs = $form->input('nick,password,ps5Enc,ret_url,time,hash');
            }
            
            if (($inputs->nick || $inputs->email) && !$form->gotErrors()) {
                if ($inputs->password) {
                    $inputs->ps5Enc = core_Users::encodePwd($inputs->password);
                }
                
                if (EF_USSERS_EMAIL_AS_NICK) {
                    $userRec = $this->fetch(array(
                            "LOWER(#email) = LOWER('[#1#]')",
                            $inputs->email
                        ));
                    $wrongLoginErr = 'Грешна парола или Имейл|*!';
                    $wrongLoginLog = 'wrong_email';
                } else {
                    $userRec = $this->fetch(array("LOWER(#nick) = LOWER('[#1#]')", $inputs->nick));
                    $wrongLoginErr = 'Грешна парола или ник|*!';
                    $wrongLoginLog = 'wrong_nick';
                }
                
                if ($userRec->state == 'deleted') {
                    $form->setError('nick', 'Този потребител е деактивиран|*!');
                    $this->logLogin($inputs, 'missing_password');
                } elseif ($userRec->state == 'blocked') {
                    $form->setError('nick', 'Този потребител е блокиран|*.<br>|На имейлът от регистрацията е изпратена информация и инструкция за ре-активация|*.');
                    $this->logLogin($inputs, 'blocked_user');
                } elseif ($userRec->state == 'draft') {
                    $form->setError('nick', 'Този потребител все още не е активиран|*.<br>|На имейлът от регистрацията е изпратена информация и инструкция за активация|*.');
                    $this->logLogin($inputs, 'draft_user');
                } elseif (!$inputs->password && !$inputs->hash) {
                    $form->setError('password', 'Липсва парола!');
                    $this->logLogin($inputs, 'missing_password');
                } elseif (abs(time() - $inputs->time) > EF_USERS_LOGIN_DELAY) {
                    $form->setError('password', 'Прекалено дълго време за логване|*!<br>|Опитайте пак|*.');
                    $this->logLogin($inputs, 'too_long_login');
                } elseif (!$userRec->state) {
                    $form->setError('password', $wrongLoginErr);
                    $this->logLogin($inputs, $wrongLoginLog);
                } elseif (($userRec->ps5Enc != $inputs->ps5Enc) && (md5($userRec->ps5Enc . $inputs->time) != $inputs->hash)) {
                    $form->setError('password', $wrongLoginErr);
                    $this->logLogin($inputs, 'wrong_password');
                }
            } else {
                // Ако в cookie е записано три последователни логвания от един и същ потребител, зареждаме му ник-а/имейл-а
                if ($cookie->u[1] > 0 && ($cookie->u[1] == $cookie->u[2]) && ($cookie->u[1] == $cookie->u[3])) {
                    $uId = (int) $cookie->u[1];
                    $assumeRec = $this->fetch($uId);
                    $inputs->email = $assumeRec->email;
                    $inputs->nick = $assumeRec->nick;
                }
                
                // Ако издават параметри от URL
                if (Request::get('email')) {
                    $inputs->email = Request::get('email');
                }
                
                if (Request::get('nick')) {
                    $inputs->nick = Request::get('nick');
                }
            }
            
            // Ако няма грешки, логваме потребителя
            // Ако има грешки, или липсва потребител изкарваме формата
            if ($userRec->id && !$form->gotErrors()) {
                $this->loginUser($userRec->id);
                $this->logLogin($inputs, 'successful_login');
                
                // Подготовка и записване на cookie
                $cookie->u[3] = $cookie->u[2];
                $cookie->u[2] = $cookie->u[1];
                $cookie->u[1] = $userRec->id;
                $Crypt = cls::get('core_Crypt');
                $cookie = $Crypt->encodeVar($cookie);
                setcookie(EF_USERS_COOKIE, $cookie, time() + 60 * 60 * 24 * 30);
            } else {
                // връщаме формата, като опресняваме времето
                $inputs->time = time();
                
                if (Mode::is('screenMode', 'narrow') || Request::get('popup')) {
                    $layout = new ET("[#FORM#]");
                } else {
                    $layout = new ET("<table ><tr><td style='padding:50px;'>[#FORM#]</td></tr></table>");
                }
                
                if (EF_USSERS_EMAIL_AS_NICK) {
                    $layout->append($form->renderHtml('email,password,ret_url', $inputs), 'FORM');
                } else {
                    $layout->append($form->renderHtml('nick,password,ret_url', $inputs), 'FORM');
                }
                
                $layout->append(tr('Вход') . ' » ', 'PAGE_TITLE');
                $layout->push('js/login.js', 'JS');
                $layout->replace('LoginFormSubmit(this,\'' . EF_USERS_PASS_SALT . '\');', 'ON_SUBMIT');
                
                return $layout;
            }
        }
        
        followRetUrl();
    }
    
    
    /**
     * Записва лог за влизанията
     */
    function logLogin_($inputs, $msg)
    {
        $this->log($msg . ' [' . ($inputs->nick ? $inputs->nick : $inputs->email) . ']');
    }
    
    
    /**
     * Изпълнява се след преобразуване на един запис към вербални стойности
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->lastLoginTime = $mvc->getVerbal($rec, 'lastLoginTime');
        $row->lastLoginIp = $mvc->getVerbal($rec, 'lastLoginIp');
        $row->nick = $mvc->getVerbal($rec, 'nick');
        $row->email = $mvc->getVerbal($rec, 'email');
        $row->names = $mvc->getVerbal($rec, 'names');
        
        $row->title = new ET("<b>[#1#]</b>", $row->names);
        
        if(!EF_USSERS_EMAIL_AS_NICK) {
            $row->title->append("<div style='margin-top:4px;font-size:0.9em;'>" .
                tr('Ник') . ": <b><u>{$row->nick}</u></b></div>");
        }
        
        $row->title->append("<div style='margin-top:4px;font-size:0.9em;'><i>{$row->email}</i></div>");
        
        $row->last = ht::createLink($row->lastLoginIp,
            "http://bgwhois.com/?query=" . $rec->lastLoginIp,
            NULL,
            array('target' => '_blank'
            ));
        
        $row->last->append("<br>");
        
        $row->last->append($row->lastLoginTime);
    }
    
    
    /**
     * Изпълнява се преди запис на ред в таблицата
     */
    static function on_BeforeSave($mvc, &$id, &$rec)
    {
        $haveUsers = !!$mvc->fetch('1=1');
        
        if(!$haveUsers && !isDebug())
        error('Първия потребител може да бъде регистриран само в debug режим!');
        
        $rolesArr = type_Keylist::toArray($rec->roles);
        
        // Всеки потребител има роля 'user'
        $rolesArr[$mvc->core_Roles->fetchByName('user')] = TRUE;
        
        // Първия потребител има роля 'admin' и активен статус
        if(!$haveUsers) {
            $rolesArr[$mvc->core_Roles->fetchByName('admin')] = TRUE;
            $rec->state = 'active';
        }
        
        // Изчисляваме останалите роли на потребителя
        foreach($rolesArr as $roleId => $dummy) {
            
            $rolesArr[$roleId] = TRUE;
            
            $roleRec = $mvc->core_Roles->fetch($roleId);
            
            $inheritArr = type_Keylist::toArray($roleRec->inherit);
            
            if(count($inheritArr)) {
                foreach($inheritArr as $rId) {
                    $rolesArr[$rId] = TRUE;
                }
            }
        }
        
        // Правим масива от изчислени роли към keylist
        $rec->roles = '|';
        
        foreach($rolesArr as $roleId => $dummy) {
            $rec->roles .= $roleId . '|';
        }
        
        if ($rec->password) {
            $rec->ps5Enc = core_Users::encodePwd($rec->password);
        }
    }
    
    
    /**
     * Изпълнява се след получаването на необходимите роли
     */
    static function on_AfterGetRequiredRoles(&$invoker, &$requiredRoles)
    {
        $query = $invoker->getQuery();
        
        if ($query->count() == 0) {
            $requiredRoles = 'every_one';
        }
    }
    
    
    /**
     * Връща id-то (или друга зададена част) от записа за текущия потребител
     */
    static function getCurrent($part = 'id')
    {
        $Users = cls::get('core_Users');
        
        if($Users->isSystemUser) {
            $rec = new stdClass();
            $rec->nick = '@system';
            $rec->id = -1;
            $rec->state = 'active';
            $res = $rec->{$part};
        } else {
            $cRec = Mode::get('currentUserRec');
            $res = $cRec->{$part};
        }
        
        return $res;
    }
    
    
    /**
     * Форсира системния потребител да бъде текущ, преди реалния текущ или анонимния
     */
    static function forceSystemUser()
    {
        $Users = cls::get('core_Users');
        
        $Users->isSystemUser = TRUE;
    }
    
    
    /**
     * Форсира системния потребител да бъде текущ, преди реалния текущ или анонимния
     */
    static function cancelSystemUser()
    {
        $Users = cls::get('core_Users');
        
        $Users->isSystemUser = FALSE;
    }
    
    
    /**
     * Зарежда записа за текущия потребител в сесията
     */
    static function loginUser($id)
    {
        $Users = cls::get('core_Users');
        
        $Users->invoke('beforeLogin', array(&$id));
        
        $userRec = $Users->fetch($id);
        
        if(!$userRec) $userRec = new stdClass();
        
        $now = dt::verbal2mysql();
        
        // Ако потребителят досега не е бил логнат, записваме
        // от къде е
        if (!($sessUserRec = Mode::get('currentUserRec'))) {
            $rec = new stdClass();
            $rec->lastLoginTime = $now;
            $rec->lastLoginIp = $Users->getRealIpAddr();
            $rec->id = $userRec->id;
            $Users->save($rec, 'lastLoginTime,lastLoginIp');
            
            // Помним в сесията, кога сме се логнали
            $userRec->loginTime = $now;
        } else {
            // Дали нямаме дублирано ползване?
            if ($userRec->lastLoginIp != $Users->getRealIpAddr() &&
                $userRec->lastLoginTime > $sessUserRec->loginTime &&
                dt::mysql2timestamp($userRec->lastLoginTime) -
                dt::mysql2timestamp($sessUserRec->loginTime) <
                EF_USERS_MIN_TIME_WITHOUT_BLOCKING) {
                
                // Блокираме потребителя
                $userRec->state = 'blocked';
                $Users->save($userRec, 'state');
                
                $Users->log("Block: " . $userRec->lastLoginIp . " != " .
                    $Users->getRealIpAddr() . " && " .
                    $userRec->lastLoginTime . " > " .
                    $sessUserRec->loginTime,
                    $userRec->id);
            }
            
            $userRec->loginTime = $sessUserRec->loginTime;
            $userRec->lastLoginIp = $sessUserRec->lastLoginIp;
            $userRec->lastLoginTime = $sessUserRec->lastLoginTime;
        }
        
        if ($userRec->state == 'blocked') {
            $Users->logout();
            error('Този акаунт е блокиран.|*<BR>|Причината най-вероятно е едновременно използване от две места.' .
                '|*<BR>|На имейлът от регистрацията е изпратена информация и инструкция за ре-активация.');
        }
        
        if ($userRec->state == 'draft') {
            error('Този акаунт все още не е активиран.|*<BR>' .
                '|На имейлът от регистрацията е изпратена информация и инструкция за активация.');
        }
        
        if ($userRec->state != 'active') {
            $Users->logout();
            
            global $_GET;
            $get = $_GET;
            unset($get['virtual_url'], $get['ajax_mode']);
            
            redirect($get);
        }
        
        $userRec->refreshTime = $now;
        
        Mode::setPermanent('currentUserRec', $userRec);
        
        // Ако не е дефинирана константата EF_DEBUG и потребителя
        // има роля 'admin', то дефинираме EF_DEBUG = TRUE
        if(!defined('EF_DEBUG') && core_Users::haveRole('admin')) {
            
            
            /**
             * Включен ли е дебъга? Той ще бъде включен и когато текущия потребител има роля 'tester'
             */
            DEFINE('EF_DEBUG', TRUE);
        }
        
        $Users->invoke('afterLogin', array(&$userRec));
        
        return $userRec;
    }
    
    
    /**
     * Добавяне на нов потребител
     */
    function act_Add()
    {
        // Ако правим първо въвеждане и имаме логнат потребител - махаме го;
        if(Mode::get('currentUserRec')) {
            if(!$this->fetch('1=1')) {
                $this->logout();
            }
        }
        
        return parent::act_Add();
    }
    
    
    /**
     * 'Изход' на текущия потребител
     */
    function act_Logout()
    {
        $this->logout();
        
        followRetUrl();
    }
    
    
    /**
     * Ако потребителя не е логнат - караме го да го направи
     */
    static function forceLogin($retUrl)
    {
        $state = Users::getCurrent('state');
        
        if (!$state == 'active') {
            
            // Опитваме да получим адрес за връщане от заявката
            $retUrl = $retUrl ? $retUrl : getCurrentUrl();
            
            // Редиректваме към формата за логване, 
            // като изпращаме и адрес за връщане
            redirect(array(
                    'core_Users',
                    'login',
                    'ret_url' => $retUrl
                ));
        }
    }
    
    
    /**
     * Ако имаме логнат потребител, но сесията му не е
     * обновявана достатъчно дълго време - обновяваме я
     */
    static function refreshSession()
    {
        $currentUserRec = Mode::get('currentUserRec');
        
        if (!$currentUserRec) return;
        
        $refreshTime = dt::mysql2timestamp($currentUserRec->refreshTime);
        
        if (abs(time() - $refreshTime) > EF_USERS_CURRENT_REC_LIFETIME) {
            Users::loginUser($currentUserRec->id);
        }
    }
    
    
    /**
     * Де-логва потребителя
     */
    static function logout()
    {
        Mode::setPermanent('currentUserRec', NULL);
        Mode::destroy();
    }
    
    
    /**
     * Връща ролите на посочения потребител
     */
    static function getRoles($userId = NULL, $type = NULL)
    {
        $Users = cls::get('core_Users');
        
        if ($userId > 0) {
            
            return $Users->fetchField($userId, 'roles');
        } else {
            
            return $Users->getCurrent('roles');
        }
    }
    
    
    /**
     * Добавя роля на посочения потребител
     */
    static function addRole($userId, $roleId)
    {
        if(!is_numeric($role)) {
            $roleId = core_Roles::fetchByName($roleId);
        }
        
        expect($roleId > 0, roleId);
        expect($userId > 0, $userId);
        
        $uRec = core_Users::fetch($userId, 'roles');
        $rolesArr = type_Keylist::toArray($uRec->roles);
        $rolesArr[$roleId] = $roleId;
        $uRec->roles = type_Keylist::fromArray($rolesArr);
        
        core_Users::save($uRec);
    }
    
    
    /**
     * Връща масив от роли, които са от посочения тип, за посочения потребител
     */
    static function getUserRolesByType($userId = NULL, $type = NULL)
    {
        $roles = core_Users::getRoles($userId);
        
        $rolesArr = type_Keylist::toArray($roles);
        
        $roleQuery = core_Roles::getQuery();
        
        if($type) {
            $cond = "#type = '{$type}'";
        } else {
            $cond = "";
        }
        
        while($roleRec = $roleQuery->fetch($cond)) {
            if($rolesArr[$roleRec->id]) {
                $res[$roleRec->id] = $roleRec->id;
            }
        }
        
        return type_Keylist::fromArray($res);
    }
    
    
    /**
     * Връща всички членове на екипите, в които участва потребителя
     */
    static function getTeammates($userId)
    {
        static $teamMates;
        
        if(!$teamMates) {
            $teams = core_Users::getUserRolesByType($userId, 'team');
            
            if(!$teams) return NULL;
            
            $query = self::getQuery();
            $query->where("#state = 'active'");
            $query->likeKeylist('roles', $teams);
            
            while($rec = $query->fetch()) {
                $res[$rec->id] = $rec->id;
            }
            
            $teamMates = type_Keylist::fromArray($res);
        }
        
        return $teamMates;
    }
    
    
    /**
     * Всички потребители на системата с даден ранг
     *
     * @param string $rank - ceo, manager, officer, executive, contractor
     * @return array масив от първични ключове на потребители
     */
    static function getByRank($rank)
    {
        $users = array();
        
        if ($rankRoleId = core_Roles::fetchField("#role = '{$rank}' AND #type = 'rang'", 'id')) {
            $users = static::getByRole($rankRoleId);
        }
        
        return $users;
    }
    
    
    /**
     * Всички потребители с дадена роля
     *
     * @param mixed $roleId ид на роля или масив от ид на роли
     * @param bool $strict     TRUE - само потребителите, имащи точно тази роля;
     * FALSE - потребителите имащи тази и/или някоя от наследените й роли
     * @return array
     */
    static function getByRole($roleId, $strict = FALSE)
    {
        $users = array();
        
        expect($roleId);
        
        if(!is_numeric($roleId)) {
            $roleId   = core_Roles::fetchByName($roleId);
        }
        
        if (!$strict) {
            $roles = core_Roles::expand($roleId);
        } elseif (!is_array($roleId)) {
            $roles = array($roleId);
        } else {
            $roles = $roleId;
        }
        
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->where("#state = 'active'");
        $query->likeKeylist('roles', $roles);
        
        while ($rec = $query->fetch()) {
            $users[$rec->id] = $rec->id;
        }
        
        return $users;
    }
    
    
    /**
     * Проверка дали потребителя има посочената роля/роли
     */
    static function haveRole($roles, $userId = NULL)
    {
        $userRoles = core_Users::getRoles($userId);
        
        $Roles = cls::get('core_Roles');
        
        if($roles{0} == '|' && $roles{strlen($roles)-1} == '|') {
            foreach(type_Keylist::toArray($roles) as $roleId) {
                $requiredRoles[] = $Roles->fetchByName($roleId);
            }
        } else {
            $requiredRoles = arr::make($roles);
        }
        
        foreach ($requiredRoles as $role) {
            
            // Всеки потребител има роля 'every_one'
            if ($role == 'every_one') return TRUE;
            
            // Никой потребител, няма роля 'none'
            if ($role == 'no_one' && !isDebug()) continue;
            
            $roleId = $Roles->fetchByName($role);
            
            // Съдържа ли се ролята в keylist-а от роли на потребителя?
            if(type_Keylist::isIn($roleId, $userRoles)) return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Генерира грешка, ако указания потребител няма нито една от посочените роли
     * Ако не е логнат, потребителя се подканва да се логне
     */
    static function requireRole($requiredRoles, $retUrl = NULL, $action = NULL)
    {
        Users::refreshSession();
        
        if (!Users::haveRole($requiredRoles)) {
            Users::forceLogin($retUrl);
            error('Недостатъчни права за този ресурс', array(
                    'requiredRoles' => $requiredRoles,
                    'action' => $action,
                    'userRoles' => Users::getCurrent('roles')
                ));
        }
        
        return TRUE;
    }
    
    
    /**
     * Заглавието на потребителя в този запис
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        if($rec->id > 0) {
            
            return $rec->nick;
        } elseif($rec->id == -1) {
            
            return "@system" ;
        } else {
            
            return '@anonymous';
        }
    }
    
    
    /**
     * Да изтрива не-логналите се потребители
     */
    function cron_DeleteDraftUsers()
    {
        $cond = "#state = 'draft' AND #createdOn < '" . dt::addDays(0 - USERS_DRAFT_MAX_DAYS) . "'";
        
        $cnt = $this->delete($cond);
        
        return "Изтрити бяха {$cnt} потребители, които не са активирали достъпа си.";
    }
    
    
    /**
     * Връща реалното IP на потребителя
     */
    function getRealIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
    
    
    /**
     * Начално инсталиране в системата
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        
        // Правим конверсия на полето roles
        $query = $mvc->getQuery();
        
        while($rec = $query->fetch()) {
            if($rec->roles && $rec->roles{0} != '|') {
                $roleId = $rec->roles;
                
                if($roleId) {
                    $rec->roles = "|" . $roleId . "|";
                    $mvc->save($rec);
                }
            }
        }
        
        $res .= "<p><i>Нагласяне на Cron</i></p>";
        
        $rec = new stdClass();
        $rec->systemId = 'DeleteDraftUsers';
        $rec->description = 'Изтрива неактивните потребители';
        $rec->controller = $mvc->className;
        $rec->action = 'DeleteDraftUsers';
        $rec->period = 24 * 60;
        $rec->offset = 5 * 60;
        $rec->delay = 0;
        $rec->timeLimit = 200;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на Cron да изтрива неактивните потребители</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да изтрива неактивните потребители</li>";
        }
        
        return $res;
    }
    
    
    /**
     * Функция, с която паролата се кодира еднопосочно
     */
    static function encodePwd($password)
    {
        return md5($password . md5($password) . EF_USERS_PASS_SALT);
    }
    
    
    /**
     * Връща id' то на първия срещнат администратор в системата
     */
    static function getFirstAdmin()
    {
        $Roles = cls::get('core_Roles');
        $adminId = $Roles->fetchByName('admin');
        
        $id = self::fetchField("#roles LIKE '%|$adminId|%'", 'id');
        
        return $id;
    }
}
