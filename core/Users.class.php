<?php


/**
 * Дефинира, колко секунди записът за 
 * текущия потребител да е валиден в сесията
 */
defIfNot('EF_USER_REC_REFRESH_TIME', 20); // config


/**
 * Колко секунди след последното действие на потребителя
 * ще се пази неговата сесия?
 */
defIfNot('EF_USERS_SESS_TIMEOUT', 1200);


/**
 * Колко секунди може да е максимално разликата
 * във времето между времето изчислено при потребителя
 * и това във сървъра при логване. В нормален случай
 * това трябва да е повече от времето за http трансфер
 * на логин формата и заявката за логване
 */
defIfNot('EF_USERS_LOGIN_DELAY', 10);


/**
 * 'Подправка' за кодиране на паролите
 */
defIfNot('EF_USERS_PASS_SALT', hash('sha256', (EF_SALTH . 'EF_USERS_PASS_SALT')));


/**
 * Колко пъти по дължината на паролата, тя да се хешира?
 */
defIfNot('EF_USERS_HASH_FACTOR', 500);


/**
 * Колко да е минималната дължина на паролата?
 */
defIfNot('EF_USERS_PASS_MIN_LEN', 6);


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
    var $loadList = 'plg_Created,plg_Modified,plg_State,plg_SystemWrapper,core_Roles,plg_RowTools,plg_CryptStore';
    
    
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
            $this->FLD('nick', 'email(link=no)', 'caption=Ник,notNull, input=none,width=15em');
        } else {
            //Ако не използвам никовете, тогава полето трябва да е задължително
            $this->FLD('nick', 'nick(64)', 'caption=Ник,notNull,mandatory,width=15em');
        }
        
        $this->FLD('email', 'email(64)', 'caption=Имейл,mandatory,width=15em');
        
        // Поле за съхраняване на хеша на паролата
        $this->FLD('ps5Enc', 'varchar(128)', 'caption=Парола хеш,column=none,input=none,crypt');
        
        $this->FLD('names', 'varchar', 'caption=Имена,mandatory,width=15em');
        
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
            $data->form->setField('nick,email,pass,names', 'width=15em');
            if(EF_USSERS_EMAIL_AS_NICK) {
                $data->form->showFields = 'email,pass,passRe,names';
            } else {
                $data->form->showFields = 'nick,email,pass,passRe,names';
            }
        }

        // Нова парола и нейния производен ключ
        $minLenHint = 'Паролата трябва да е минимум|* ' . EF_USERS_PASS_MIN_LEN . ' |символа';
        $data->form->FNC('passNew', 'password(allowEmpty,autocomplete=off)', "caption=Парола,input,hint={$minLenHint},width=15em");
        $data->form->FNC('passNewHash', 'varchar', 'caption=Хеш на новата парола,input=hidden');
        
        // Повторение на новата парола
        $passReHint = 'Въведете отново паролата за потвърждение, че сте я написали правилно';
        $data->form->FNC('passRe', 'password(allowEmpty,autocomplete=off)', "caption=Парола (пак),input,hint={$passReHint},width=15em");

        self::setUserFormJS($data->form);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputEditForm($mvc, $form)
    { 
        //Ако не сме субмитнали формата връщаме управлението
        if (!$form->isSubmitted()) return ;
        
        $rec = $form->rec;

        //id' то на текущия запис
        $recId = $rec->id;
        
        //Проверяваме дали има такъв имейл
        if ($newRecId = $mvc->fetchField("LOWER(#email)=LOWER('{$form->rec->email}')")) {
            //Проверяваме дали редактираме текущия запис или създаваме нов
            if ($newRecId != $recId) {
                //Съобщение за грешка, ако имейл-а е зает
                $form->setError('email', "Има друг регистриран потребител с този имейл.");
            }
        }

        // Ако използваме имейла за ник, то полето nick копира стойността на email
        if(EF_USSERS_EMAIL_AS_NICK) {
            $rec->nick = $rec->email;
        }

        self::calcUserForm($form);  
        
        // Ако имаме въведена нова парола
        if($rec->passNewHash) {
            if($rec->isLenOK == -1) {
                $form->setError('passNew', 'Паролата трябва да е минимум |* ' . EF_USERS_PASS_MIN_LEN . ' |символа');
            } elseif($rec->passNew != $rec->passRe) {
                $form->setError('passNew,passRe', 'Двете пароли не съвпадат');
            } else {
                // Ако няма грешки, задаваме да се модифицира хеша в DB
                $rec->ps5Enc = $rec->passNewHash;
            }
        }

        if($form->gotErrors()) {
            $form->rec->passNewHash   = '';
            $form->rec->passExHash = '';
        }
    }
    
    
    /**
     * Форма за вход
     */
    function act_Login()
    {
    	$conf = core_Packs::getConfig('core');
    	
        if (Request::get('popup')) {
            Mode::set('wrapper', 'page_Empty');
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
                'title' => "|*<img src=" . sbf('img/signin.png') . " align='top'>&nbsp;|Вход в|* " . $conf->EF_APP_TITLE,
                'name' => 'login'
            ));
        
        // Парола за ауторизация (логване)
        $form->FNC('pass', 'password(allowEmpty)', "caption=Парола,input,width=15em");
 
        if (Request::get('popup')) {
            $form->setHidden('ret_url', toUrl(array('core_Browser', 'close'), 'local'));
        } else {
            $form->setHidden('ret_url', toUrl($retUrl, 'local'));
        }
        $form->setHidden('time', time());
        $form->setHidden('hash', '');
        $form->setHidden('loadTime', '');
        
        $form->addAttr('nick,pass,email', array('style' => 'width:240px;'));
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
                $inputs = $form->input('email,pass,ret_url,time,hash');
            } else {
                $inputs = $form->input('nick,pass,ret_url,time,hash');
            }

            // Изчислява хешовете
           

            if (($inputs->nick || $inputs->email) && $form->isSubmitted()) {

                self::calcLoginForm($form);

                if (EF_USSERS_EMAIL_AS_NICK) {
                    $userRec = $this->fetch(array(
                            "LOWER(#email) = LOWER('[#1#]')",
                            $inputs->email
                        ));              

                    $wrongLoginErr = 'Грешна парола или имейл|*!';
                    $wrongLoginLog = 'wrong_email';
                } else {
                    $userRec = $this->fetch(array("LOWER(#nick) = LOWER('[#1#]')", $inputs->nick));
                    $wrongLoginErr = 'Грешна парола или ник|*!';
                    $wrongLoginLog = 'wrong_nick';
                }

                if(!$userRec) {
                    $userRec = new stdClass();
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
                } elseif (!$inputs->hash || $inputs->isEmptyPass) {
                    $form->setError('pass', 'Липсва парола!');
                    $this->logLogin($inputs, 'missing_password');
                } elseif (!$inputs->pass && abs(time() - $inputs->time) > EF_USERS_LOGIN_DELAY) {  
                    $form->setError('pass', 'Прекалено дълго време за логване|*!<br>|Опитайте пак|*.');
                    $this->logLogin($inputs, 'too_long_login');
                } elseif ($userRec->lastLoginTime && abs(time() - dt::mysql2timestamp($userRec->lastLoginTime)) <  EF_USERS_LOGIN_DELAY) {
                    $form->setError('pass', 'Прекалено кратко време за ре-логване|*!<br>|Изчакайте и опитайте пак|*.');
                    $this->logLogin($inputs, 'too_fast_relogin');
                } elseif (!$userRec->state) {
                    $form->setError('pass', $wrongLoginErr);
                    $this->logLogin($inputs, $wrongLoginLog);
                } elseif (self::applyChallenge($userRec->ps5Enc, $inputs->time) != $inputs->hash) {
                    $form->setError('pass', $wrongLoginErr);
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
                    $layout->append($form->renderHtml('email,pass,ret_url', $inputs), 'FORM');
                } else {
                    $layout->append($form->renderHtml('nick,pass,ret_url', $inputs), 'FORM');
                }
                
                $layout->prepend(tr('Вход') . ' » ', 'PAGE_TITLE');
                if(EF_USERS_HASH_FACTOR > 0) {
                    $layout->push('js/login.js', 'JS');
                } else {
                    $layout->push('js/loginOld.js', 'JS');
                }
                $layout->replace("loginFormSubmit(this, '" . 
                                 EF_USERS_PASS_SALT . "', '" . 
                                 EF_USERS_HASH_FACTOR . "', '" . 
                                 (EF_USSERS_EMAIL_AS_NICK ? 'email' : 'nick') .
                                 "');", 'ON_SUBMIT');
                
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
        if($rec->id) {
            return;
        }
        $haveUsers = !!$mvc->fetch('1=1');
        
        
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
            $rolesArr += core_Roles::getRolesArr($roleId);
        }
        
        // Правим масива от изчислени роли към keylist
        $rec->roles = '|';
        
        foreach($rolesArr as $roleId => $dummy) {
            $rec->roles .= $roleId . '|';
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
    static function getCurrent($part = 'id', $escaped = FALSE)
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
            if ($escaped) {
                $res = core_Users::getVerbal($cRec, $part);    
            } else {
                $res = $cRec->$part;    
            }
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
     * Временна подмяна на текущия потребител
     * 
     * След изпълнението на този метод системата работи точно както ако зададения потребител
     * се беше логнал през логин формата.
     * 
     * Този ефект продължава до извикването на метода @see core_Users::exitSudo().
     * 
     * @param int $id key(mvc=core_Users)
     * @return boolean TRUE ако всичко е наред, FALSE ако има проблем - тогава текущия 
     *                                                                  потребител не 
     *                                                                  се променя.
     */
    static function sudo($id)
    {
        if (!$id) {
            return FALSE;
        }
        
        $userRec = static::fetch($id);
        
        $bValid = !empty($userRec);
        
        /**
         * @TODO Други проверки за допустимостта на sudo - напр. дали е активен потребителя и
         * пр.
         */ 
        
        if($bValid) {
            core_Mode::push('currentUserRec', $userRec);
        }
        
        return $bValid;
    }
    
    
    /**
     * Възстановява текущия потребител до предишна стойност.
     * 
     * Текущ става потребителя, който е бил такъв точно преди последното извикване на 
     * @see core_Users::sudo().
     * 
     */
    static function exitSudo()
    {
        core_Mode::pop('currentUserRec');
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
            $userRec->lastHitUT = time();
            $userRec->maxIdleTime = 0;
        } else {
            // Дали нямаме дублирано ползване?
            if ($userRec->lastLoginIp != $Users->getRealIpAddr() &&
                $userRec->lastLoginTime > $sessUserRec->loginTime &&
                dt::mysql2timestamp($userRec->lastLoginTime) - dt::mysql2timestamp($sessUserRec->loginTime) < EF_USERS_MIN_TIME_WITHOUT_BLOCKING
               
                ) {
                
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
            
            $userRec->maxIdleTime = max($sessUserRec->maxIdleTime, time() - $sessUserRec->lastHitUT);
            if(!Request::get('ajax_mode')) {
                $userRec->lastHitUT   = time();
            } else {
                $userRec->lastHitUT   = $sessUserRec->lastHitUT;
            }
        }
        
        // Ако потребителя е блокиран - излизаме от сесията и показваме грешка        
        if ($userRec->state == 'blocked') {
            $Users->logout();
            error('Този акаунт е блокиран.|*<BR>|Причината най-вероятно е едновременно използване от две места.' .
                '|*<BR>|На имейлът от регистрацията е изпратена информация и инструкция за ре-активация.');
        }
        
        if ($userRec->state == 'draft') {
            error('Този акаунт все още не е активиран.|*<BR>' .
                '|На имейлът от регистрацията е изпратена информация и инструкция за активация.');
        }
        
        if ($userRec->state != 'active' || $userRec->maxIdleTime > 1200) {
            $Users->logout();
            
            global $_GET;
            $get = $_GET;
            unset($get['virtual_url'], $get['ajax_mode']);
            
            redirect($get);
        }
        
        $userRec->refreshTime = $now;
         
        Mode::setPermanent('currentUserRec', $userRec);
        
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
        
        if (abs(time() - $refreshTime) > EF_USER_REC_REFRESH_TIME) {
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
    static function getRealIpAddr()
    {
 
        $ip = $_SERVER['REMOTE_ADDR'];
        
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
    static function encodePwd($password, $nick, $salt = EF_USERS_PASS_SALT, $hashFactor = EF_USERS_HASH_FACTOR)
    {   
        if($hashFactor <= 0) {
            $res = md5($password . md5($password) . $salt);
        } else {
            $nick = strtolower($nick);
            $hashFactor = min(10, strlen($password)) * $hashFactor;
            for($i = 0; $i <= $hashFactor; $i++) {
                $res = hash('sha256', $res . $nick . $salt . $i . $password);
            }
        }

        return $res;
    }



    /**
     * Хешира хеша на паролата и времето
     */
    static function applyChallenge($ps5Enc, $time)
    {
        return hash('sha256', $ps5Enc . $time);
    }

    
    /**
     * Промяна на паролата на съществуващ потребител
     * 
     * @param unknown_type $passHash - хеша на новата парола
     * @param unknown_type $userId - id на потребителя
     */
    public static function setPassword($passHash, $userId = NULL)
    {
        if (!isset($userId)) {
            $userId = static::getCurrent('id');
        }
        
        expect($rec = static::fetch($userId));
        
        $rec->ps5Enc = $passHash;
        
        return static::save($rec, 'ps5Enc');
    }


    /**
     * Изчислява хешовете във формата за логване
     */
    static function calcLoginForm($form)
    {   
        $rec = $form->rec;

        $nick = EF_USSERS_EMAIL_AS_NICK ? $rec->email : $rec->nick;

        if(!$nick) {
            $nick = core_Users::getCurrent('nick');
        }

        if ($rec->pass) {
            $rec->passHash = self::encodePwd($rec->pass, $nick);
            $rec->ps5Enc   = $rec->passHash;
            if($rec->time) {
                $rec->hash   = self::applyChallenge($rec->ps5Enc, $rec->time);
            }
        }
    }


    /**
     * Изчислява хешовете в потребителския запис
     * Вариянт 1: Логване на потребнител
     * Вариянт 2: Редкатиране на потребител (първи или следващ)
     * Вариянт 3: Смяна на паролата на потребител
     * Вариянт 4: Смяна на паролата на потребител през имейл интерфейса
     */
    static function calcUserForm($form)
    {   
        $rec = $form->rec;

        $nick = EF_USSERS_EMAIL_AS_NICK ? $rec->email : $rec->nick;

        if(!$nick) {
            $nick = core_Users::getCurrent('nick');
        }

        // Калкулиране на хеша на старата парола
        // Стара парола трябва да имаме винаги, когато потребителя е логнат
        if ($rec->passEx) {
            $rec->passExHash = self::encodePwd($rec->passEx, $nick);
        }
        
        // Калкулиране на хеша на новата парола
        if ($rec->passNew) {
            $rec->passNewHash = self::encodePwd($rec->passNew, $nick);
            if(mb_strlen($rec->passNew) < EF_USERS_PASS_MIN_LEN) {
                $rec->isLenOK = -1;
            }
            if($rec->passNew != $rec->passRe) {
                $rec->isRetypeOK = -1;
            }
        }
    }
    

    /**
     * Добавя необходимия JS на форма за промяна на паролата на потребител
     */
    static function setUserFormJS($form)
    {   
        $rec = $form->rec;

        $nickType = EF_USSERS_EMAIL_AS_NICK ? 'email' : 'nick';

 
        $tpl = new ET();
        if(EF_USERS_HASH_FACTOR > 0) {
            $tpl->push('js/login.js', 'JS');
        } else {
            $tpl->push('js/loginOld.js', 'JS');
        }

        $tpl->append("return userFormSubmit(this, '" . 
                                 EF_USERS_PASS_SALT . "', '" . 
                                 EF_USERS_HASH_FACTOR . "', '" . 
                                 $nickType . "', '" .
                                 EF_USERS_PASS_MIN_LEN . "', '" . 
                                 core_Lg::getCurrent() .
                                 "');", 'ON_SUBMIT');

        $form->info = new ET($form->info);
        $form->info->appendOnce($tpl, 'ON_SUBMIT');
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
    
    
	/**
     * Проверявамед дали потребителя е активен
     */
    static function isActiveUser($nick)
    {
        $user = static::fetch("#nick = '{$nick}' AND #state = 'active'");
        
        return $user;
    }

    
}