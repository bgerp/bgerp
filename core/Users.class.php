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
defIfNot('EF_USERS_SESS_TIMEOUT', 3600);


/**
 * 'Подправка' за кодиране на паролите
 */
defIfNot('EF_USERS_PASS_SALT', hash('sha256', (EF_SALT . 'EF_USERS_PASS_SALT')));


/**
 * Колко пъти по дължината на паролата, тя да се хешира?
 */
defIfNot('EF_USERS_HASH_FACTOR', 0);


/**
 * Колко да е минималната дължина на паролата?
 */
defIfNot('EF_USERS_PASS_MIN_LEN', 6);


/**
 * Дали да се използва имейл адресът, вместо ник
 */
defIfNot('EF_USSERS_EMAIL_AS_NICK', FALSE);


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
 * Ще има ли криптиращ протокол?
 * NO - не
 * OPTIONAL - да, където може изпозлвай криптиране
 * MANDATORY - да, използвай задължително
 */
defIfNot('EF_HTTPS', 'NO');


/**
 *  Порта на Apache, отговорен за криптиращия протокол
 */
defIfNot('EF_HTTPS_PORT', 443);


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
     * Константа за id на системния потребител
     */
    const SYSTEM_USER = -1;
    
    
    /**
     * Константа за id на анонимния потребител
     */
    const ANONYMOUS_USER = 0;

    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Потребители';


    /**
     * Наименование на единичния обект
     */
    var $singleTitle = 'Потребител';


    /**
     * Плъгини и MVC класове за предварително зареждане
     */
    var $loadList = 'plg_Created,plg_Modified,plg_State,plg_SystemWrapper,core_Roles,plg_RowTools,plg_CryptStore,plg_Search,plg_Rejected,plg_UserReg';
    
    
    /**
     * Кои колонки да се показват в табличния изглед
     */
    var $listFields = 'id,title=Имена,rolesInput,last=Последно';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin';
	
    
    /**
     * Дали в момента се работи със системния потребител (-1)
     */
    var $isSystemUser = FALSE;
    
    /**
     * URL за javascript
     */
    var $httpsURL = '';
    
    
    /**
     * Кой може да персонализира конфигурационните данни за потребителя
     */
    var $canPersonalize = 'user';
    
    
    /**
     * По кои полета да се прави пълнотекстово търсене
     */
    var $searchFields = 'nick,names,email';
    
    
    /**
     * Дали да се стартира крон-а в shutDown
     */
    public $runCron = FALSE;
    
    
    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        //Ако е активирано да се използват имейлите, като никове тогава полето имейл го правим от тип имейл, в противен случай от тип ник
        if (EF_USSERS_EMAIL_AS_NICK) {
            //Ако използваме имейлите вместо никове, скриваме полето ник
            $this->FLD('nick', 'email(link=no)', 'caption=Ник,notNull, input=none');
        } else {
            //Ако не използвам никовете, тогава полето трябва да е задължително
            $this->FLD('nick', 'nick(64)', 'caption=Ник,notNull,mandatory,width=100%');
        }
        
        $this->FLD('names', 'varchar', 'caption=Имена,mandatory,width=100%');
        $this->FLD('email', 'email(64)', 'caption=Имейл,mandatory,width=100%');
        
        // Поле за съхраняване на хеша на паролата
        $this->FLD('ps5Enc', 'varchar(128)', 'caption=Парола хеш,column=none,input=none,crypt');
        
        
        $this->FLD('rolesInput', 'keylist(mvc=core_Roles,select=role,groupBy=type, autoOpenGroups=team|rang)', 'caption=Роли');
        $this->FLD('roles', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Експандирани роли,input=none');
        
        $this->FLD('state', 'enum(active=Активен,draft=Неактивиран,blocked=Блокиран,rejected=Заличен)',
            'caption=Състояние,notNull,default=draft');
        
        $this->FLD('lastLoginTime', 'datetime(format=smartTime)', 'caption=Последно->Логване,input=none');
        $this->FLD('lastLoginIp', 'type_Ip', 'caption=Последно->IP,input=none');
        $this->FLD('lastActivityTime', 'datetime(format=smartTime)', 'caption=Последно->Активност,input=none');
        
        $this->setDbUnique('nick');
        $this->setDbUnique('email');
    }
    
    
    /**
     * Връща масив от масиви - роли и потребители, които имат съответните роли
     * 
     * @return array
     */
    public static function getRolesWithUsers()
    {
        $type = 'userRoles';
        $handle = 'userRolesArr';
        $keepMinute = 1440;
        $depends = array('core_Roles', 'core_Users');
        
        // Проверяваме дали записа фигурира в кеша
        $usersRolesArr = core_Cache::get($type, $handle, $keepMinute, $depends);
        
        if ($usersRolesArr !== FALSE) return $usersRolesArr;
        
        $uQuery = core_Users::getQuery();
//        $uQuery->where("#state != 'blocked'");
//        $uQuery->where("#state != 'rejected'");
        
        // За всяка роля добавяме потребители, които я имат
        while ($uRec = $uQuery->fetch()) {
            $rolesArr = type_Keylist::toArray($uRec->roles);
            foreach ($rolesArr as $roleId) {
                $usersRolesArr[0][$uRec->id] = $uRec->id;
                $usersRolesArr[$roleId][$uRec->id] = $uRec->id;
            }
        }
        
        // Записваме масива в кеша
        core_Cache::set($type, $handle, $usersRolesArr, $keepMinute, $depends);
        
        return $usersRolesArr;
    }
    
    
    /**
     * Връща масив с потребители в системата Ник => Имена
     * 
     * @param array $rolesArr
     * @param string $nick
     * @param integer $limit
     * 
     * return array
     */
    static function getUsersArr_($rolesArr=array(), $nick=NULL, $limit=NULL)
    {
        if ($rolesArr) {
            
            // id-та на ролите
            $roles = core_Roles::getRolesAsKeylist($rolesArr);
        }
        
        static $usersArr = array();
        
        $cash = $roles . '_' . $limit . '_' . $nick;
        
        if (!$usersArr[$cash]) {
            
            // Всичко, потребители, които не са заличени
            $query = static::getQuery();
            $query->where("#state != 'rejected'");
            $query->where("#state != 'draft'");
            
            // Ако са зададени роли
            if ($roles) {
                $query->likeKeylist('roles', $roles);
            }
            
            // Ако е зададен ник
            if ($nick) {
                $nick = strtolower($nick);
                $query->where(array("LOWER(#nick) LIKE '[#1#]%'", $nick));
            }
            
            // Ако е зададено ограничение
            if ($limit) {
                $query->limit($limit);
            }
            
            while ($rec =  $query->fetch()) {
                if (!$rec->nick) continue;
                $usersArr[$cash][$rec->nick] = static::prepareUserNames($rec->names);
            }
        }
        
        return $usersArr[$cash];
    }
    
    
    /**
     * От подадените имена връща името и фамилията
     * 
     * @param string $names
     * 
     * @return string
     */
    static function prepareUserNames_($names)
    {
        // Масив с именатата
        $namesArr = explode(' ', $names);
        
        // Име с първа главна буква
        $firstName = array_shift($namesArr);
        $firstName = str::mbUcfirst($firstName);
        
        // Фамилия с първа главна буква
        $lastName = array_pop($namesArr);
        $lastName = str::mbUcfirst($lastName);
        
        $newName = $firstName . ' ' . $lastName;
        
        return $newName;
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Филтриране по група
        $data->listFilter->FNC('role', 'key(mvc=core_Roles,select=role,allowEmpty)',
            'placeholder=Роля,caption=Роля,input,silent,refreshForm');

        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search,role';
        
        $rec = $data->listFilter->input('search,role', 'silent');
        
    	$data->query->orderBy("lastLoginTime,createdOn", "DESC");

        if($data->listFilter->rec->role) {
            $data->query->where("#roles LIKE '%|{$data->listFilter->rec->role}|%'");
        }
    }
    
    
    /**
     * Изпълнява се след създаване на формата за добавяне/редактиране
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        // Ако няма регистрирани потребители, първият задължително е администратор
        if(self::isUsersEmpty()) {
            $data->form->setOptions('state' , array('active' => 'active'));
            $data->form->title = 'Първоначална регистрация на администратор';
            
            $data->form->setField("state", 'input=none');
            $data->form->setField("rolesInput", 'input=none');
            
            if(EF_USSERS_EMAIL_AS_NICK) {
                $data->form->setField("nick", 'input=none');    
            }
        }

        // Нова парола и нейния производен ключ
        $minLenHint = 'Паролата трябва да е минимум|* ' . EF_USERS_PASS_MIN_LEN . ' |символа';
        if(EF_USSERS_EMAIL_AS_NICK) {
            $data->form->FNC('passNew', 'password(allowEmpty,autocomplete=off)', "caption=Парола,input,hint={$minLenHint},after=email");
        } else {
            $data->form->FNC('passNew', 'password(allowEmpty,autocomplete=off)', "caption=Парола,input,hint={$minLenHint},after=nick");
        }
        $data->form->FNC('passNewHash', 'varchar', 'caption=Хеш на новата парола,input=hidden');
        
        // Повторение на новата парола
        $passReHint = 'Въведете отново паролата за потвърждение, че сте я написали правилно';
        $data->form->FNC('passRe', 'password(allowEmpty,autocomplete=off)', "caption=Парола (пак),input,hint={$passReHint},after=passNew");

        self::setUserFormJS($data->form);

        if($id = $data->form->rec->id) {
            $exRec = self::fetch($id);
            if($exRec->lastLoginTime) {
                $stateType = &$mvc->fields['state']->type;
                unset($stateType->options['draft']);
            }
        }
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
                if ($recId) {
                    $mvc->changePass = TRUE;
                }
            }
        } else {
            if($recId) {
                $exRec  = self::fetch($recId);
                if($rec->nick != $exRec->nick) {
                    $form->setError('passNew,passRe', 'При промяна на ника на потребителя трябва да се зададе нова парола');
                }
            } else {
                $form->setError('passNew,passRe', 'Не е зададена парола за достъп на потребителя');
            }
        }

        if($form->gotErrors()) {
            $rec->passNewHash   = '';
            $rec->passExHash = '';
        } else {
            if ($recId) {
                $exRec  = self::fetch($recId);
                if($rec->nick != $exRec->nick) {
                    $mvc->changeNick = TRUE;
                }
            } else {
                $mvc->addNewUser = TRUE;
            }
        }
        
        // Ако регистрираме първия потребител, добавяме му роля `admin`
        if(!$rec->id && $mvc->isUsersEmpty()) {
            $rec->rolesInput = keylist::addKey($rec->rolesInput, $mvc->core_Roles->fetchByName('admin'));
            $rec->state = 'active';
        }
    }
    
    
	/**
     * След създаване на запис в модела
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	if(self::count() == 1){
    		$mvc->invoke('AfterCreateFirstUser', array(&$html));
    		$mvc->runCron = TRUE;
    	}
    }
    
    
    /**
     * Форма за вход
     */
    function act_Login()
    {
    	$conf = core_Packs::getConfig('core');
    	
        $connection = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'HTTPS' : 'HTTP';
        
        if(EF_HTTPS == 'MANDATORY' && $connection == 'HTTP'){
        		
        	static::redirectToEnableHttps();
        }
    	
        if (Request::get('popup')) {
            Mode::set('wrapper', 'page_Empty');
        }
        
        // Ако нямаме регистриран нито един потребител
        // и се намираме в дебъг режим, то тогава редиректваме
        // към вкарването на първия потребител (admin)
        if(self::isUsersEmpty()) {
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
        $form->FNC('pass', 'password(allowEmpty)', "caption=Парола,input,width=100%");
 
        if (Request::get('popup')) {
            $form->setHidden('ret_url', toUrl(array('core_Browser', 'close'), 'local'));
        } else {
            $form->setHidden('ret_url', toUrl($retUrl, 'local'));
        }
        $form->setHidden('time', time());
        $form->setHidden('hash', '');
        $form->setHidden('loadTime', '');
        
        $form->addAttr('nick,pass,email', array('style' => 'min-width:14em;' ));
        
        $form->toolbar->addSbBtn('Вход', 'default', NULL,  array('class' => 'noicon'));
       
        $httpUrl = core_App::getSelfURL();
        $httpsUrl = str_replace('http', 'https', $httpUrl);
        
        $connection = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'HTTPS' : 'HTTP';

        if(EF_HTTPS === 'OPTIONAL' && $connection === 'HTTP'){
        	$form->toolbar->addFnBtn('Вход с криптиране', "this.form.action=('{$httpsUrl}');this.form.submit();", array('style' => 'background-color: #9999FF'));
        }
        
        $form->info = "<center style='font-size:0.8em;color:#666;'>" . tr($conf->CORE_LOGIN_INFO) . "</center>";

        $this->invoke('PrepareLoginForm', array(&$form));
        
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
                
                if ($userRec->state == 'rejected') {
                    $form->setError('nick', 'Този потребител е деактивиран|*!');
                    $this->logLogin($inputs, 'missing_password');
                    core_LoginLog::add('reject', $userRec->id, $inputs->time);
                } elseif ($userRec->state == 'blocked') {
                    $form->setError('nick', 'Този потребител е блокиран|*.<br>|На имейлът от регистрацията е изпратена информация и инструкция за ре-активация|*.');
                    $this->logLogin($inputs, 'blocked_user');
                    core_LoginLog::add('block', $userRec->id, $inputs->time);
                } elseif ($userRec->state == 'draft') {
                    $form->setError('nick', 'Този потребител все още не е активиран|*.<br>|На имейлът от регистрацията е изпратена информация и инструкция за активация|*.');
                    $this->logLogin($inputs, 'draft_user');
                    core_LoginLog::add('draft', $userRec->id, $inputs->time);
                } elseif (!$inputs->hash || $inputs->isEmptyPass) {
                    $form->setError('pass', 'Липсва парола!');
                    $this->logLogin($inputs, 'missing_password');
                    core_LoginLog::add('missing_password', $userRec->id, $inputs->time);
//                } elseif (!$inputs->pass && !core_LoginLog::isTimestampDeviationInNorm($inputs->time)) {  
                } elseif (!core_LoginLog::isTimestampDeviationInNorm($inputs->time)) {  
                    $form->setError('pass', 'Прекалено дълго време за логване|*!<br>|Опитайте пак|*.');
                    $this->logLogin($inputs, 'time_deviation');
                    core_LoginLog::add('time_deviation', $userRec->id, $inputs->time);
                } elseif (core_LoginLog::isTimestampUsed($inputs->time, $userRec->id)) {
                    $form->setError('pass', 'Грешка при логване|*!<br>|Опитайте пак|*.');
                    $this->logLogin($inputs, 'used_timestamp');
                    core_LoginLog::add('used_timestamp', $userRec->id, $inputs->time);
                } elseif (!$userRec->state) {
                    $form->setError('pass', $wrongLoginErr);
                    $this->logLogin($inputs, $wrongLoginLog);
//                    core_LoginLog::add('wrong_username', NULL, $inputs->time);
                } elseif (self::applyChallenge($userRec->ps5Enc, $inputs->time) != $inputs->hash) {
                    $form->setError('pass', $wrongLoginErr);
                    $this->logLogin($inputs, 'wrong_password');
                    core_LoginLog::add('wrong_password', $userRec->id, $inputs->time);
                }
            } else {
                
                // Връща id на потребителя, което ще се използва за попълване на ника или имейла
                $uId = core_LoginLog::getUserIdForAutocomplete();
                
                // Ако има потребител
                if ($uId) {
                    $assumeRec = $this->fetch($uId);
                    $inputs->email = $assumeRec->email;
                    $inputs->nick = $assumeRec->nick;
                }
            }
            
            // Ако няма грешки, логваме потребителя
            // Ако има грешки, или липсва потребител изкарваме формата
            if ($userRec->id && !$form->gotErrors()) {
                $this->loginUser($userRec->id, $inputs);
                $this->logLogin($inputs, 'successful_login');
//                core_LoginLog::add('success', $userRec->id, $inputs->time);
            } else {
                // връщаме формата, като опресняваме времето
                $inputs->time = time();
                
                if (Mode::is('screenMode', 'narrow') || Request::get('popup')) {
                    $layout = new ET("<div id='login-form'>[#FORM#]</div>");
                } else {
                    $layout = new ET("<table ><tr><td id='login-form'>[#FORM#]</td></tr></table>");
                }
                 
                if (EF_USSERS_EMAIL_AS_NICK) {
                    $layout->append($form->renderHtml('email,pass,ret_url', $inputs), 'FORM');
                } else {
                    $layout->append($form->renderHtml('nick,pass,ret_url', $inputs), 'FORM');
                }
                
                $layout->prepend(tr('Вход') . ' « ', 'PAGE_TITLE');
                if(EF_USERS_HASH_FACTOR > 0) {
                    $layout->push('js/login.js', 'JS');
                } else {
                    $layout->push('js/loginOld.js', 'JS');
                }
                $layout->append("\n<script> scriptStart = new Date().getTime() </script>", 'HEAD');

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
        $nick = $inputs->nick ? $inputs->nick : $inputs->email;

        $this->log($msg . ' [' . $nick . '] from IP: ' . $this->getRealIpAddr());
    }
    

    /**
     * Изпълнява се след преобразуване на един запис към вербални стойности
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->lastLoginTime = $mvc->getVerbal($rec, 'lastLoginTime');
        $row->lastLoginIp = type_IP::decorateIp($rec->lastLoginIp, $rec->lastLoginTime);
        $row->nick = $mvc->getVerbal($rec, 'nick');
        $row->email = $mvc->getVerbal($rec, 'email');
        $row->names = $mvc->getVerbal($rec, 'names');
        
        $row->title = new ET("<b>[#1#]</b>", $row->names);
        
        if(!EF_USSERS_EMAIL_AS_NICK) {
            $row->title->append("<div style='margin-top:4px;font-size:0.9em;'>" .
                tr('Ник') . ": <b><u>{$row->nick}</u></b></div>");
        }
        
        $row->title->append("<div style='margin-top:4px;font-size:0.9em;'><i>{$row->email}</i></div>");
        
        $row->last = new ET($row->lastLoginIp);
        
        $row->last->append("<br>");
        
        $row->last->append($row->lastLoginTime);

        $rolesInputArr = keylist::toArray($rec->rolesInput);
        $rolesArr      = keylist::toArray($rec->roles);

        foreach($rolesArr as $roleId) {

            if(!$rolesInputArr[$roleId]) {
                $addRoles .= ($addRoles ? ', ' : '') . core_Roles::fetchByName($roleId);
            }
        }

        if($addRoles) {

            $row->rolesInput .= "<div style='color:#666;'>" . tr("индиректно") . ": " . $addRoles . "</div>";
        }

        $row->rolesInput = "<div style='max-width:400px;'>{$row->rolesInput}</div>";
    }
    
    
    /**
     * Изпълнява се преди запис на ред в таблицата
     */
    static function on_BeforeSave($mvc, &$id, &$rec, $fields = NULL)
    {
        if($rec->rolesInput) {

            $rolesArr = keylist::toArray($rec->rolesInput);
            
            $rolesArr = core_Roles::expand($rolesArr);

            $userRoleId = $mvc->core_Roles->fetchByName('user');
            
            $rolesArr[$userRoleId] = $userRoleId;

            $rec->roles = keylist::fromArray($rolesArr);
        }
        
        if ($rec->id) {
            // Ако е сменен ника
            if ($mvc->changeNick) {
                core_LoginLog::add('change_nick', $rec->id);
            }
            
            // Ако е сменена паролата
            if ($mvc->changePass) {
                core_LoginLog::add('pass_change', $rec->id);
            }
        } else {
            if ($mvc->addNewUser) {
                core_LoginLog::add('new_user', core_Users::getCurrent());
            }
        }
    }
    
    
    /**
     * Връща истина ако няма никакви регистрирани потребители до сега
     */
    static function isUsersEmpty()
    {
        return !self::fetch('1=1');
    }
    
    
    /**
     * Изпълнява се след получаването на необходимите роли
     */
    static function on_AfterGetRequiredRoles(&$invoker, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        $query = $invoker->getQuery();
        
        if ($query->count() == 0) {
            $requiredRoles = 'every_one';
        }
        
        // Ако ще се персонализира
        if (($action == 'personalize') && ($rec)) {
            
            // Текущия потребител да може да персонализира само своите, а admin на всички
            if (($rec->id != $userId) && !haveRole('admin', $userId)) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Виртуално добавяне на двата служебни потребителя
     */
    static function fetch($cond, $fields = '*', $cache = TRUE)
    { 
        if(($cond == self::SYSTEM_USER) && is_numeric($cond)) {
            $res = (object) array(
                                'id' => self::SYSTEM_USER,
                                'nick' => '@system',
                                'state' => 'active'
                            );
        } elseif(($cond == self::ANONYMOUS_USER) && is_numeric($cond)) {
            $res = (object) array(
                                'id' => self::ANONYMOUS_USER,
                                'nick' => '@anonym',
                                'state' => 'active'
                            );
        } else {
            $res = parent::fetch($cond, $fields, $cache);
        }

        return $res;
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
            } elseif(is_object($cRec)) {
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
     * Проверява дали текущуя потребител и системен
     * 
     * @return boolean
     */
    static function isSystemUser()
    {
        $Users = cls::get('core_Users');
        
        return (boolean)$Users->isSystemUser;
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
        $userRec = self::fetch($id);
        $bValid = FALSE;
        
        if (is_object($userRec)) {
            core_Mode::push('currentUserRec', $userRec);
            $bValid = TRUE;
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
    static function loginUser($id, $inputs=FALSE, $refresh=FALSE)
    {
        $Users = cls::get('core_Users');
        
        $userRec = $Users->fetch($id);
        
        $Users->invoke('beforeLogin', array(&$userRec, $inputs, $refresh));
        
        if(!$userRec) $userRec = new stdClass();
        
        $now = dt::verbal2mysql();
        
        // Ако потребителят досега не е бил логнат, записваме
        // от къде е
        if (!($sessUserRec = Mode::get('currentUserRec'))) {
            $rec = new stdClass();
            $rec->lastLoginTime = $rec->lastActivityTime = $now;
            $rec->lastLoginIp = $Users->getRealIpAddr();
            $rec->id = $userRec->id;
            $Users->save($rec, 'lastLoginTime,lastActivityTime,lastLoginIp');
            
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
                    
                core_LoginLog::add('block', $userRec->id);
            }
            
            $userRec->loginTime = $sessUserRec->loginTime;
            $userRec->lastLoginIp = $sessUserRec->lastLoginIp;
            $userRec->lastLoginTime = $sessUserRec->lastLoginTime;
            
            $userRec->maxIdleTime = max($sessUserRec->maxIdleTime, time() - $sessUserRec->lastHitUT);
            if(!Request::get('ajax_mode')) {
                $userRec->lastHitUT = time();
            } else {
                $userRec->lastHitUT = $sessUserRec->lastHitUT;
            }
        }
        
        // Ако потребителя е блокиран - излизаме от сесията и показваме грешка        
        if ($userRec->state == 'blocked') {
            $Users->logout();
            redirect(array('Index'), TRUE, tr('Този акаунт е блокиран.|*<BR>|Причината най-вероятно е едновременно използване от две места.' .
                '|*<BR>|На имейлът от регистрацията е изпратена информация и инструкция за ре-активация.'));
        }
        
        if ($userRec->state == 'draft') {
            redirect(array('Index'), TRUE, tr('Този акаунт все още не е активиран.|*<BR>' .
                '|На имейлът от регистрацията е изпратена информация и инструкция за активация.'));
        }
        
        if ($userRec->state != 'active' || $userRec->maxIdleTime > EF_USERS_SESS_TIMEOUT) {
            $Users->logout();
            redirect(getCurrentUrl());
        }
        
        $userRec->refreshTime = $now;
        
        Mode::setPermanent('currentUserRec', $userRec);
        
        if(!Request::get('ajax_mode') && dt::mysql2timestamp($userRec->lastActivityTime) < (time() - 3*60)) {
            $userRec->lastActivityTime = $now;
            self::save($userRec, 'lastActivityTime');
        }

        $Users->invoke('afterLogin', array(&$userRec, $inputs, $refresh));

        if(!isDebug() && haveRole('debug')) {
            core_Debug::setDebugCookie();
        }
        
        return $userRec;
    }
    
    
    /**
     * Извиква се след логване на потребителя в системата
     * 
     * @param core_Mvc $mvc
     * @param object $userRec
     * @param boolean $refresh
     */
    function on_AfterLogin($mvc, &$userRec, $inputs, $refresh)
    {
        // Ако не се логва, а се рефрешва потребителя
        if ($refresh) return ;
        
        $nick = $inputs->nick ? $inputs->nick : $inputs->email;
        
        vislog_IpNames::add($nick);
        
        // Обновяваме времето на BRID кукито
        core_Browser::updateBridCookieLifetime();
        
        $conf = core_Packs::getConfig('core');
        
        // Ако е зададен език на интерфейса след логване
        if ($conf->EF_USER_LANG) {
            
            // Форсираме езика
            core_Lg::set($conf->EF_USER_LANG, TRUE);
        }
        
        // IP адреса на потребителя
        $currIp = $mvc->getRealIpAddr();
        
        // Ако е първо логване
        if (core_LoginLog::isFirstLogin($currIp, $userRec->id)) {
            
            // Записваме в лога и връщаме
            core_LoginLog::add('first_login', $userRec->id, $inputs->time);
            
            return ;
        }
        
        // Ако се е логнат от различно IP
        if ($userRec->lastLoginIp && ($userRec->lastLoginIp != $currIp)) {
            
            if (core_LoginLog::isTrustedUserLogin($currIp, $userRec->id)) {
                
                $arr = core_LoginLog::getLastLoginFromOtherIp($currIp, $userRec->id);
                
                $TimeInst = cls::get('type_Time');
                
                $url = static::getUrlForLoginLogStatus($userRec->id);
                
                // Всички IP-та, от които се е логнало за първи път
                foreach ((array)$arr['first_login'] as $loginRec) {
                    
                    // Времето, когато се е логнал
                    $time = dt::secsBetween(dt::now(), $loginRec->createdOn);
                    
                    // Закръгляме времето, за да е по четимо
                    $time = type_Time::round($time);
                    
                    // Вербално време
                    $time = $TimeInst->toVerbal($time);
                    
                    // Вербално IP
                    $ip = $loginRec->ip;
                    
                    // Добавяме съответното статус съобщение
                    $text = "|Подозрително логване от|* {$ip} |преди|* {$time}";
                    
                    // Ако има УРЛ, текста да е линк към него
                    if ($url) {
                        $link = ht::createLink($text, $url);
                        $statusText = $link->getContent();
                    } else {
                        $statusText = $text;
                    }
                    
                    core_Statuses::newStatus($statusText, 'warning');
                }
                
                // Последното успешно логване от друго IP
                $successArr = (array)$arr['success'];
                reset($successArr);
                $lastSuccessLoginKey = key($successArr);
                if ($lastSuccessLoginKey) {
                    
                    $loginRec = $successArr[$lastSuccessLoginKey];
                    
                    // Времето, когато се е логнал
                    $time = dt::secsBetween(dt::now(), $loginRec->createdOn);
                    
                    // Закръгляме времето, за да е по четимо
                    $time = type_Time::round($time);
                    
                    // Вербално време
                    $time = $TimeInst->toVerbal($time);
                    
                    // Вербално IP
                    $ip = $loginRec->ip;
                    
                    // Добавяме съответното статус съобщение
                    $text = "|Логване от|* {$ip} |преди|* {$time}";
                    
                    // Ако има УРЛ, текста да е линк към него
                    if ($url) {
                        $link = ht::createLink($text, $url);
                        $statusText = $link->getContent();
                    } else {
                        $statusText = $text;
                    }
                    
                    core_Statuses::newStatus($statusText, 'notice');
                }
            }
        }
        
        // Записваме в лога успешното логване
        core_LoginLog::add('success', $userRec->id, $inputs->time);
    }
    
    
    /**
     * Връща URL към листовия изглед на логин лога за текущия потребител
     * 
     * @param integer $userId
     * 
     * return array
     */
    static function getUrlForLoginLogStatus_($userId=NULL)
    {
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        // id на потребитяля за търсене
        $userTeams = type_User::getUserFromTeams($userId);
        reset($userTeams);
        $userIdWithTeam = key($userTeams);
        
        // Ако има права за този екшън
        if (core_LoginLog::haveRightFor('list')) {
            
            return array('core_LoginLog', 'list', 'userId' => $userIdWithTeam);
        }
    }
    
    
    /**
     * Добавяне на нов потребител
     */
    function act_Add()
    {
        // Ако правим първо въвеждане и имаме логнат потребител - махаме го;
        if(Mode::get('currentUserRec')) {
            if(self::isUsersEmpty()) {
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
            $retUrl = $retUrl ? $retUrl :  getCurrentUrl();
            
            if(is_array($retUrl) && is_array($retUrl['Cmd'])) {
                unset($retUrl['Cmd']['save']);
                $retUrl['Cmd']['refresh'] = 1;
            }
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
        
        if (abs(time() - $refreshTime) > EF_USER_REC_REFRESH_TIME || (time() - $currentUserRec->lastHitUT > 3 * EF_USER_REC_REFRESH_TIME)) {
            Users::loginUser($currentUserRec->id, FALSE, TRUE);
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
        if(!is_numeric($roleId)) {
            $roleId = core_Roles::fetchByName($roleId);
        }
        
        expect($roleId > 0, $roleId);
        expect($userId > 0, $userId);
        
        $uRec = core_Users::fetch($userId, 'rolesInput');
        $rolesArr = keylist::toArray($uRec->rolesInput);
        $rolesArr[$roleId] = $roleId;

        $uRec->rolesInput = keylist::fromArray($rolesArr);
        
        core_Users::save($uRec, 'rolesInput,roles');
    }
    
    
    /**
     * Връща масив от роли, които са от посочения тип, за посочения потребител
     */
    static function getUserRolesByType($userId = NULL, $type = NULL, $result = 'keylist')
    {
        $roles = core_Users::getRoles($userId);
        
        $rolesArr = keylist::toArray($roles);
        
        $roleQuery = core_Roles::getQuery();

        $roleQuery->orderBy("#role", 'ASC');

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
        
        if($result == 'keylist') {
            $res = keylist::fromArray($res);
        }

        return $res;
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
            $query->likeKeylist('roles', $teams);
            
            while($rec = $query->fetch()) {
                $res[$rec->id] = $rec->id;
            }
            
            $teamMates = keylist::fromArray($res);
        }
        
        return $teamMates;
    }
    
    
    /**
     * Проверява дали 2 потребителя са от един и същи екип
     * 
     * @param integer $user1 - id на първия потребител
     * @param integer $user2 - id на втория потребител
     * 
     * @return boolean - Ако са от един и същи екип връща TRUE
     */
    static function isFromSameTeam($user1, $user2 = NULL)
    {   
        // Ако $user2 не е зададен, вземаме текущия потребител
        if(!$user2) {
            $user2 = core_Users::getCurrent();
        }
        
        // По-бърз отговор, ако двата потребителя съвпадат
        if($user1 == $user2) return TRUE;

        // Вземаме съотборниците на първия потребител
        $teamMates = static::getTeammates($user1);
        
        // Проверяваме дали втория е при тях
        return type_Keylist::isIn($user2, $teamMates);
    }
    
    
    /**
     * Всички потребители с дадена роля
     *
     * @param mixed $roleId ид на роля или масив от ид на роли
     * @param bool $strict     TRUE - само потребителите, имащи точно тази роля;
     * FALSE - потребителите имащи тази и/или някоя от наследените й роли
     * @return array
     */
    static function getByRole($roleId)
    {
        $users = array();
        
        expect($roleId);
        
        if(!is_numeric($roleId)) {
            $roleId   = core_Roles::fetchByName($roleId);
        }
        
        $query = static::getQuery();
        $query->where("#state = 'active'");
        $query->like('roles', "|{$roleId}|");
        
        while ($rec = $query->fetch()) {
            $users[$rec->id] = $rec->id;
        }
        
        return $users;
    }
    
    
    /**
     * Проверка дали потребителя има посочената роля/роли
     * @param $roles array, keylist, list
     */
    static function haveRole($roles, $userId = NULL)
    {
        $userRoles = core_Users::getRoles($userId);
        
        $Roles = cls::get('core_Roles');
        
        if(keylist::isKeylist($roles)) {
            foreach(keylist::toArray($roles) as $roleId) {
                $requiredRoles[] = $Roles->fetchByName($roleId);
            }
        } else {
            $requiredRoles = arr::make($roles);
        }
        
        if(count($requiredRoles)) {
            foreach ($requiredRoles as $role) {
                
                // Всеки потребител има роля 'every_one'
                if ($role == 'every_one') return TRUE;
                
                // Никой потребител, няма роля 'none'
                if ($role == 'no_one' && !isDebug()) continue;
                
                $roleId = $Roles->fetchByName($role);
                
                // Съдържа ли се ролята в keylist-а от роли на потребителя?
                if(keylist::isIn($roleId, $userRoles)) return TRUE;
            }
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

        $connection = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'HTTPS' : 'HTTP';
        
        if($requiredRoles !== 'every_one'){
        	
        	if(EF_HTTPS == 'MANDATORY' && $connection == 'HTTP' && $_GET){
        		//bp(EF_HTTPS == 'MANDATORY', $connection == 'HTTP', $_GET, $_POST);
        		static::redirectToEnableHttps();

        	}
        }
        
        if (!Users::haveRole($requiredRoles)) {
            
            Users::forceLogin($retUrl);
            
            if($requiredRoles == 'no_one') {
                $errMsg = '403 Недостъпен ресурс';
            } else {
                $errMsg = '401 Недостатъчни права за този ресурс';
            }

            error($errMsg,  $requiredRoles, $action,  Users::getCurrent('roles'));
        }
        
        return TRUE;
    }
    

    /**
     * Преизчислява за всеки потребител, всички преизчислени роли
     */
    static function rebuildRoles()
    {
        $query = self::getQuery();

        while($rec = $query->fetch()) {
            self::save($rec, 'roles');
            $i++;
        }

        return "<li> Преизчислени са ролите на {$i} потребителя</li>";
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
        
        return $_SERVER['REMOTE_ADDR'];
    }
    
    
    /**
     * Начално инсталиране в системата
     */
    static function on_AfterSetupMVC($mvc, &$res)
    { 
        // Нагласяне на Крон
        $rec = new stdClass();
        $rec->systemId = 'DeleteDraftUsers';
        $rec->description = 'Изтриване на неактивните потребители';
        $rec->controller = $mvc->className;
        $rec->action = 'DeleteDraftUsers';
        $rec->period = 24 * 60;
        $rec->offset = 5 * 60;
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $res .= core_Cron::addOnce($rec);
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
        
        $saved = static::save($rec, 'ps5Enc');
        
        if ($saved) {
            core_LoginLog::add('pass_change', $userId);
        }
        
        return $saved;
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
        
        $id = self::fetchField("#roles LIKE '%|$adminId|%' AND #state != 'rejected'", 'id');
        
        return $id;
    }
    
    function act_Test()
    {
    	$url = core_App::getSelfURL();
    	self::redirectToEnableHttps();
    	
    }
    
    
	/**
     * Проверявамед дали потребителя е активен
     */
    static function isActiveUser($nick)
    {
    	
        $user = static::fetch(array("LOWER(#nick) = LOWER('[#1#]') AND #state = 'active'", $nick));
        
        return $user;
    }

    
    /**
     * Прехвърля url–то на схема https
     */
    static public function redirectToEnableHttps()
    {
    	$url = core_App::getSelfURL();
    	
        $newUrl = static::setHttpsInUrl($url);
   
        return  Redirect($newUrl);
    }
    
    
    /**
     * Променя схемата на url-то от http към https
     * @param string $url
     */
    static public function setHttpsInUrl($url)
    {
    	$currUrl = core_Url::parseUrl($url);
    	
    	$currUrl[scheme] = 'https';

    	if($currUrl[port] != "443" && $currUrl[scheme] === 'https'){
    		
        	$newUrl = $currUrl[scheme]. "://" . $currUrl[host] . ":" . $currUrl[port]. $currUrl[path] . "?" . $currUrl[query];
    		
    	} else {
    		
    		$newUrl = $currUrl[scheme]. "://" . $currUrl[host] . $currUrl[path] . "?" . $currUrl[query];
    	}
    	
    	return $newUrl;
    }
    
    
    /**
     * Подготвяме ника
     * Всички точки и долни черти ги правим на празен символ
     * 
     * @param string $nick
     * 
     * @return string
     */
    static function prepareNick($nick)
    {
        // Преобразуваме в показване като име
        $nick = static::stringToNickCase($nick);
        
        return $nick;
    }

	
	
	/**
	 * Преобразува подадения стринг да се показва като ник
	 * 
	 * @param string $str
	 * 
	 * @return string
	 */
	static function stringToNickCase($str)
	{
	    // Всички букви в долния регистър
	    // След долна черта и интервал първата буква да е главна
	    $str = mb_strtolower($str);
	    $str = str::toUpperAfter($str);
	    $str = str::toUpperAfter($str, '.');
	    $str = str::toUpperAfter($str, '_');
	    
	    return $str;
	}
    
    
    /**
     * Връща ника, който съответсва на зададаното id
     * 
     * @param mixed $userId - id на ника или запис от модела
     * 
     * @return string
     */
    static function getNick($userId)
    {
        // Ако е обект
        if (is_object($userId)) {
            
            // Вземаме id-то от записа
            $userId = $userId->id;
            
        }
        
        // Ако е id на потребител от модела
        if ($userId > 0) {
            
            // Вземаме ника от записа
            $nick = static::fetchField($userId, 'nick');
        } elseif($userId == -1) {
            
            // Ако е сустемния потребител
            $nick = "@system" ;
        } else {
            
            // Ако е непознат потребител
            $nick = '@anonymous';
        }
        
        return $nick;
    }
    
    
    /**
     * 
     * 
     * @param core_Users $mvc
     */
    function on_ShutDown($mvc)
    {
        if ($this->runCron) {
            
            fopen(toUrl(array('core_Cron', 'cron'), 'absolute'), 'r');
            
            $this->runCron = FALSE;
        }
    }
}
