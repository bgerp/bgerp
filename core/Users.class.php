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
 * Списък със собствени IP-та, които не се блокират
 */
defIfNot('BGERP_OWN_IPS', '');


/**
 * Писмо до потребителя за активация
 */
defIfNot('USERS_UNBLOCK_EMAIL',
                "\n|Уважаеми|* [#names#]." .
                "\n" .
                "\n|Потребителят Ви в|* [#EF_APP_TITLE#] |е блокиран|*." .
                "\n" .
                "\n|За да се отблокирате, моля отворете следния линк|*: " .
                "\n" .
                "\n[#url#]" .
                "\n" .
                "\n|Линка ще изтече в|* [#regLifetime#]." .
                "\n" .
                "\n|Поздрави|*," .
                "\n[#senderName#]");


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
    var $loadList = 'plg_Created,plg_Modified,plg_State,plg_SystemWrapper,core_Roles,plg_RowTools2,plg_CryptStore,plg_Search,plg_Rejected,plg_UserReg';
    
    
    /**
     * Кои колонки да се показват в табличния изглед
     */
    var $listFields = 'title=Данни,rolesInput,last=Последно';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin';
	
    
    
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
     * Кой има право да променя потребителите, създадени от системата?
     */
    public $canEditsysdata = 'admin';
    
    
    /**
     * Кой има право да изтрива потребителите, създадени от системата?
     */
    public $canDeletesysdata = 'admin';

    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        //Ако е активирано да се използват имейлите, като никове тогава полето имейл го правим от тип имейл, в противен случай от тип ник
        if (EF_USSERS_EMAIL_AS_NICK) {
            //Ако използваме имейлите вместо никове, скриваме полето ник
            $this->FLD('nick', 'email(link=no, ci)', 'caption=Ник,notNull, input=none');
        } else {
            //Ако не използвам никовете, тогава полето трябва да е задължително
            $this->FLD('nick', 'nick(64, ci)', 'caption=Ник,notNull,mandatory,width=100%');
        }
        $this->FLD('state', 'enum(active=Активен,draft=Непотвърден,blocked=Блокиран,closed=Затворен,rejected=Заличен)',
            'caption=Състояние,notNull,default=draft');
        
        $this->FLD('names', 'varchar', 'caption=Лице->Имена,mandatory,width=100%');
        $this->FLD('email', 'email(64, ci)', 'caption=Лице->Имейл,mandatory,width=100%');
        
        // Поле за съхраняване на хеша на паролата
        $this->FLD('ps5Enc', 'varchar(128)', 'caption=Парола хеш,column=none,input=none,crypt');
        
        
        $this->FLD('rolesInput', 'keylist(mvc=core_Roles,select=role,groupBy=type, autoOpenGroups=team|rang, orderBy=orderByRole)', 'caption=Роли');
        $this->FLD('roles', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Експандирани роли,input=none');
        
        
        $this->FLD('lastLoginTime', 'datetime(format=smartTime)', 'caption=Последно->Логване,input=none');
        $this->FLD('lastLoginIp', 'type_Ip', 'caption=Последно->IP,input=none');
        $this->FLD('lastActivityTime', 'datetime(format=smartTime)', 'caption=Последно->Активност,input=none');
        
        $this->setDbUnique('nick');
        $this->setDbUnique('email');
    }


    /**
     * Премахва масива с потребители и роли
     */
    static function on_AfterSave($mvc, &$id, $rec, $fields = NULL)
    {
        
        if(!$fields || 
            in_array('state', $fields = arr::make($fields)) || 
            in_array('nick', $fields) || 
            in_array('names', $fields) || 
            in_array('rolesInput', $fields) || 
            in_array('roles', $fields)) {

            core_Cache::remove(self::ROLES_WITH_USERS_CACHE_ID, self::ROLES_WITH_USERS_CACHE_ID);
        }
    }


    /**
     * Изпълнява се след запис/промяна на роля
     */
    static function on_AfterDelete($mvc, &$id)
    {
        core_Cache::remove(self::ROLES_WITH_USERS_CACHE_ID, self::ROLES_WITH_USERS_CACHE_ID);
    }

    
    const ROLES_WITH_USERS_CACHE_ID = 'USER_ROLES';


    /**
     * Връща масив от масиви - роли и потребители, които имат съответните роли
     * 
     * @return array
     */
    public static function getRolesWithUsers()
    {
        static $res;

        if($res) {
 
            return $res;
        }

        $keepMinute = 1440;

        // Проверяваме дали записа фигурира в кеша
        $usersRolesArr = core_Cache::get(self::ROLES_WITH_USERS_CACHE_ID, self::ROLES_WITH_USERS_CACHE_ID, $keepMinute);
        if (is_array($usersRolesArr)) {
            $res = $usersRolesArr;

            return $usersRolesArr;
        }
 
        $uQuery = core_Users::getQuery();
        $uQuery->orderBy('#nick');
        
        $usersRolesArr = array();
        
        // За всяка роля добавяме потребители, които я имат
        while ($uRec = $uQuery->fetchAndCache()) {
            $rolesArr = type_Keylist::toArray($uRec->roles);
            foreach ($rolesArr as $roleId) {
                $usersRolesArr[0][$uRec->id] = $uRec->id;
                $usersRolesArr[$roleId][$uRec->id] = $uRec->id;
                $usersRolesArr['r'][$uRec->id] = (object) array('nick' => type_Nick::normalize($uRec->nick), 'names' => $uRec->names, 'state' => $uRec->state, 'roles' => $uRec->roles);
            }
        }
        
        // Записваме масива в кеша
        core_Cache::set(self::ROLES_WITH_USERS_CACHE_ID, self::ROLES_WITH_USERS_CACHE_ID, $usersRolesArr, $keepMinute);
       
        $res = $usersRolesArr;
 
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
                $nick = mb_strtolower($nick);
                $query->where(array("LOWER(#nick) LIKE '[#1#]%'", $nick));
                $query->orWhere(array("LOWER(#names) LIKE '[#1#]%'", $nick));
                $query->orWhere(array("LOWER(#names) LIKE '% [#1#]%'", $nick));
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
     * Проверява дали подадения потребител е контрактор
     * 
     * @param object|NULL|integer $rec
     * @param boolean $force
     * 
     * @return boolean
     */
    public static function isContractor($rec = NULL, $force=FALSE)
    {
        if (is_null($rec)) {
            $rec = core_Users::getCurrent();
        }
        
        if (is_null($rec)) return FALSE;
        
        if (is_numeric($rec)) {
            $rec = self::fetch($rec);
        }
        
        if (!$force && (!$rec || ($rec->id < 1))) return FALSE;
        
        return (boolean)(!self::isPowerUser($rec));
    }
    
    
    /**
     * Проверява дали потребителя има роля powerUser
     * 
     * @param object|NULL|integer $rec
     * 
     * @return boolean
     */
    public static function isPowerUser($rec = NULL)
    {
        if (is_null($rec)) {
            $rec = core_Users::getCurrent();
        }
        
        if (is_null($rec)) return FALSE;
        
        if (!is_object($rec)) {
            $rec = self::fetch($rec);
        }
        
        static $isPowerUserArr = array();
        
        if (!isset($isPowerUserArr[$rec->id])) {
            $powerUserId = core_Roles::fetchByName('powerUser');
            
            type_Keylist::isIn($powerUserId, $rec->roles);
            
            $isPowerUserArr[$rec->id] = (boolean)type_Keylist::isIn($powerUserId, $rec->roles);
        }
        
        return $isPowerUserArr[$rec->id];
    }
    
    
    /**
     * Проверява дали има някой потребител, който не е оттеглен от подадения масив
     * 
     * @param array $usersArr
     * 
     * @return boolean
     */
    public static function checkUsersIsRejected($usersArr = array())
    {
        $usersArr = arr::make($usersArr, TRUE);
        
        $query = self::getQuery();
        $query->where("#state != 'rejected'");
        $query->orWhereArr('id', $usersArr);
        
        $query->limit(1);
        $query->show('id');
        
        $cnt = $query->count();
        
        return !(boolean) $cnt;
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
            'placeholder=Роля,caption=Роля,input,silent,autoFilter');

        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search,role';
        
        $rec = $data->listFilter->input('search,role', 'silent');
        $data->query->XPR('orderTime', 'datetime', 'if(#lastLoginTime, #lastLoginTime, #createdOn)');
    	$data->query->orderBy("orderTime", "DESC");

        if($data->listFilter->rec->role) {
            $data->query->where("#roles LIKE '%|{$data->listFilter->rec->role}|%'");
        }
    }
    
    

    /**
     * Изпълнява се след подготвянето на тулбара в листовия изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return boolean
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if(haveRole('admin')) {
            $data->toolbar->addBtn('Миграция на папки', array('core_Users', 'migrateFolders'));
        }
    }


    /**
     * Изпълнява се след създаване на формата за добавяне/редактиране
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;

        // Ако няма регистрирани потребители, първият задължително е администратор
        if(self::isUsersEmpty()) {

            $cache = cls::get('core_Cache');
            $cache->eraseFull();

            $form->setOptions('state' , array('active' => 'active'));
            
            $form->setField("state", 'input=none');
            $form->setField("rolesInput", 'input=none');
            
            if(EF_USSERS_EMAIL_AS_NICK) {
                $form->setField("nick", 'input=none');    
            }
        }

        // Нова парола и нейния производен ключ
        $minLenHint = 'Паролата трябва да е минимум|* ' . EF_USERS_PASS_MIN_LEN . ' |символа';
        if(EF_USSERS_EMAIL_AS_NICK) {
            $form->FNC('passNew', 'password(allowEmpty,autocomplete=off)', "caption=Парола,input,hint={$minLenHint},after=email");
        } else {
            $form->FNC('passNew', 'password(allowEmpty,autocomplete=off)', "caption=Парола,input,hint={$minLenHint},after=nick");
        }
        $form->FNC('passNewHash', 'varchar', 'caption=Хеш на новата парола,input=hidden');
        
        // Повторение на новата парола
        $passReHint = 'Въведете отново паролата за потвърждение, че сте я написали правилно';
        $form->FNC('passRe', 'password(allowEmpty,autocomplete=off)', "caption=Парола (пак),input,hint={$passReHint},after=passNew");

        self::setUserFormJS($form);
 
        if($id = $form->rec->id) {
            $exRec = self::fetch($id);
            if($exRec->state != 'draft') {
                $stateType = &$mvc->fields['state']->type;
                unset($stateType->options['draft']); 
            }
        } else {
            $teamsList = core_Roles::getRolesByType('team');
            $teamsArr = type_Keylist::toArray($teamsList);
            if (count($teamsArr) == 1) {
                $form->setDefault('rolesInput', $teamsArr);
            }
        }
        
        if(!self::isUsersEmpty()) {
            
            if ($form->cmd == 'refresh' && $form->rec->id && !$form->rec->roles) {
                $roles = $mvc->fetchField($form->rec->id, 'roles');
                $rolesArr = type_Keylist::toArray($roles);
            } else {
                $rolesArr = type_Keylist::toArray($form->rec->roles);
            }
            $roleTypes = core_Roles::getGroupedOptions($rolesArr);
            
            asort($roleTypes['job']);
            asort($roleTypes['system']);
            asort($roleTypes['position']);
            asort($roleTypes['external']);

     
            $form->FNC('roleRank', 'key(mvc=core_Roles,select=role,allowEmpty)', 'caption=Достъп->Ранг,after=rolesInput,input,mandatory,silent,refreshForm');

            $rangs = array();
            $rangs[core_Roles::fetchByName('ceo')] = 'ceo';
            $rangs[core_Roles::fetchByName('manager')] = 'manager';
            $rangs[core_Roles::fetchByName('officer')] = 'officer';
            $rangs[core_Roles::fetchByName('executive')] = 'executive';
            $rangs[core_Roles::fetchByName('partner')] = 'partner';

            $form->setOptions('roleRank', $rangs);
            $rec = $form->input(NULL, 'silent');
            
            if($rec->id) {
                $iRoles = keylist::toArray($rec->rolesInput);
                foreach($roleTypes['rang'] as $i => $r) {
                    if($iRoles[$i]) {
                        $form->setDefault('roleRank', $i);
                        setIfNot($rec->roleRank, $i);
                        break;
                    }
                }
            }

            $partnerR = core_Roles::fetchByName('partner');

            if($rec->roleRank == $partnerR) {
                $otherRoles = arr::combine(
                        array('external' => (object) array('title' => "Външен достъп", 'group' => TRUE)), 
                        $roleTypes['external']);
                if(count($roleTypes['external'])) {
                	$form->FNC('roleOthers', 'keylist(mvc=core_Roles,select=role,allowEmpty)', 'caption=Достъп->Роли,after=roleTesms,input');
                    $form->setSuggestions('roleOthers', $otherRoles);
                }
            } elseif($rec->roleRank) {
                $form->FNC('roleTeams', 'keylist(mvc=core_Roles,select=role,allowEmpty)', 'caption=Достъп->Екипи,after=roleRang,input,mandatory');
                $form->FNC('roleOthers', 'keylist(mvc=core_Roles,select=role,allowEmpty)', 'caption=Достъп->Роли,after=roleTesms,input');
                
                $form->setSuggestions('roleTeams', $roleTypes['team']);
                $otherRoles = arr::combine(
                    array('job' => (object) array('title' => "Модул", 'group' => TRUE)), 
                    $roleTypes['job'], 
                    array('system' => (object) array('title' => "Системни", 'group' => TRUE)), 
                    $roleTypes['system'],
                    array('position' => (object) array('title' => "Позиция", 'group' => TRUE)), 
                    $roleTypes['position']);
                $form->setSuggestions('roleOthers', $otherRoles);

                if($rec->id) {
                    $teams = array();
                    foreach($roleTypes['team'] as $i => $r) {
                        if($iRoles[$i]) {  
                            $teams[$i] = $i;
                        }
                    }
                    if(count($teams)) {
                        $form->setDefault('roleTeams', keylist::fromArray($teams));
                    }
                }
            }

            if($rec->id) {
                $other = array();
                if(is_array($otherRoles)) {
                    foreach($otherRoles as $i => $r) {
                        if($iRoles[$i]) {  
                            $other[$i] = $i;
                        }
                    }
                }
                if(count($other)) {
                    $form->setDefault('roleOthers', keylist::fromArray($other));
                }
            }
        }
 
        $form->setField('rolesInput', 'input=none');
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    public static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	if(self::isUsersEmpty()) {
    		$data->form->title = 'Първоначална регистрация на администратор';
            cls::load('crm_Setup');
            $data->form->setDefault('country', drdata_Countries::getIdByName(BGERP_OWN_COMPANY_COUNTRY));
            unset($mvc->_plugins['plg_SystemWrapper']);
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
        if ($newRecId = $mvc->fetchField("LOWER(#email) = LOWER('{$form->rec->email}')")) {
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

        $rec->nick = type_Nick::normalize($rec->nick);
 
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
                if(strtolower($rec->nick) != strtolower($exRec->nick)) {
                    $form->setError('passNew,passRe', 'При промяна на ника на потребителя трябва да се зададе нова парола');
                }
            } else {
                $form->setError('passNew,passRe', 'Не е зададена парола за достъп на потребителя');
            }
        }
        
        $rank = core_Roles::fetchById($rec->roleRank);

        if(in_Array($rank, array('ceo', 'manager', 'officer', 'executive'))) {
            if(!$rec->roleTeams) {
                $form->setError('roleTeams', 'Вътрешните потребители трябва да имат поне един екип');
            }
        } else {
            if($rec->roleTeams) {
                $form->setError('roleTeams', 'Външните потребители не могат да имат роля за екип');
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

            $rec->rolesInput = keylist::merge($rec->roleRank, $rec->roleTeams, $rec->roleOthers);
        }
        
        // Aдминистратор не може да премахне сам на себе си ролята `administrator`
        if($rec->id && $rec->id == core_Users::getCurrent()) {
            $exRec = self::fetch($rec->id);
            $adminId = core_Roles::fetchByName('admin');
            if(keylist::isIn($adminId, $exRec->rolesInput) && !keylist::isIn($adminId, $rec->rolesInput)) {
                $form->setError('roleOthers', 'Не може да премахнете сам на себе си ролята `administrator`');
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
            $form->setHidden('ret_url', toUrl(array('log_Browsers', 'close'), 'local'));
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

            // Ако логин формата е субмитната
            if (($inputs->nick || $inputs->email) && $form->isSubmitted()) {
                
                // Изчислява хешовете
                $inputs->nick = type_Nick::normalize($inputs->nick);
 
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
                
                if ($userRec->state == 'rejected' || $userRec->state == 'closed') {
                    $form->setError('nick', 'Този потребител е деактивиран|*!');
                    $this->logLoginMsg($inputs, 'missing_password');
                    core_LoginLog::add('reject', $userRec->id, $inputs->time);
                } elseif ($userRec->state == 'blocked') { 
                    if(type_Ip::isLocal()) {
                        Request::setProtected('userId');
                        $url = array('core_Users', 'unblocklocal', 'userId' => $userRec->id);
                        $url = toUrl($url);
                        $msg = 'Този потребител е блокиран|*.<br>|Може да го активирате от тук:|* <a href="' . $url . '">[*]</a>';
                    } else {
                        $msg = 'Този потребител е блокиран|*.<br>|На имейлът от регистрацията е изпратена информация и инструкция за ре-активация|*.';
                    }
                    $form->setError('nick', $msg);
                    $this->logLoginMsg($inputs, 'blocked_user');
                    core_LoginLog::add('block', $userRec->id, $inputs->time);
                } elseif ($userRec->state == 'draft') {
                    $form->setError('nick', 'Този потребител все още не е активиран|*.<br>|На имейлът от регистрацията е изпратена информация и инструкция за активация|*.');
                    $this->logLoginMsg($inputs, 'draft_user');
                    core_LoginLog::add('draft', $userRec->id, $inputs->time);
                } elseif (!$inputs->hash || $inputs->isEmptyPass) {
                    $form->setError('pass', 'Липсва парола!');
                    $this->logLoginMsg($inputs, 'missing_password');
                    core_LoginLog::add('missing_password', $userRec->id, $inputs->time);
                } elseif (!core_LoginLog::isTimestampDeviationInNorm($inputs->time)) {  
                    $form->setError('pass', 'Прекалено дълго време за логване|*!<br>|Опитайте пак|*.');
                    $this->logLoginMsg($inputs, 'time_deviation');
                    core_LoginLog::add('time_deviation', $userRec->id, $inputs->time);
                } elseif (core_LoginLog::isTimestampUsed($inputs->time, $userRec->id)) {
                    $form->setError('pass', 'Грешка при логване|*!<br>|Опитайте пак|*.');
                    $this->logLoginMsg($inputs, 'used_timestamp');
                    core_LoginLog::add('used_timestamp', $userRec->id, $inputs->time);
                } elseif (!$userRec->state) {
                    $form->setError('pass', $wrongLoginErr);
                    $this->logLoginMsg($inputs, $wrongLoginLog);
//                    core_LoginLog::add('wrong_username', NULL, $inputs->time);
                } elseif (self::applyChallenge($userRec->ps5Enc, $inputs->time) != $inputs->hash) {
                    $form->setError('pass', $wrongLoginErr);
                    $this->logLoginMsg($inputs, 'wrong_password');
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
                $this->logLoginMsg($inputs, 'successful_login');
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
    function logLoginMsg_($inputs, $msg)
    {
        $id = NULL;
        if ($inputs->nick) {
            $rec = self::fetch(array("LOWER(#nick) = LOWER('[#1#]')", $inputs->nick));
        } else {
            $rec = self::fetch(array("LOWER(#email) = LOWER('[#1#]')", $inputs->email));
        }
        
        if ($rec) {
            $id = $rec->id;
        }

        $this->logLogin($msg, $id);
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
                $addRoles .= ($addRoles ? ', ' : '') . core_Roles::getVerbal($roleId, 'role');
            }
        }

        if($addRoles && !Mode::is('screenMode', 'narrow')) {

            $row->rolesInput .= "<div style='color:#666;'>" . tr("индиректно") . ": " . $addRoles . "</div>";
        }

        $row->rolesInput = "<div style='max-width:400px;'>{$row->rolesInput}</div>";  
    }
    
    
    /**
     * Изпълнява се преди запис на ред в таблицата
     */
    static function on_BeforeSave($mvc, &$id, &$rec, $fields = NULL)
    {
        if(!$fields || in_array('roles', $fields = arr::make($fields))) {

            $rolesArr = keylist::toArray($rec->rolesInput);
            
            // Подсигуряваме се, че потребителят ще има точно една роля за ранг
            $rangs = array();
            $haveRang = FALSE;
            $rangs[core_Roles::fetchByName('ceo')] = 'ceo';
            $rangs[core_Roles::fetchByName('manager')] = 'manager';
            $rangs[core_Roles::fetchByName('officer')] = 'officer';
            $rangs[core_Roles::fetchByName('executive')] = 'executive';
            $rangs[core_Roles::fetchByName('partner')] = 'partner';
            foreach($rangs as $roleId => $roleName) {
                if(!$haveRang) {
                    if($rolesArr[$roleId]) {
                        $haveRang = TRUE;
                        continue;
                    }
                } else {
                    unset($rolesArr[$roleId]);
                }
            }

            // Ако няма никаква роля за ранг - даваме му 'partner' 
            if(!$haveRang) {
                $rolesArr[$roleId] = $roleId;
            }
            
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
    public static function on_AfterGetRequiredRoles(&$invoker, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
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
                                'nick' => core_Setup::get('SYSTEM_NICK'),
                                'state' => 'active',
                                'names' => tr('Системата')
                            );
        } elseif(($cond == self::ANONYMOUS_USER) && is_numeric($cond)) {
            $res = (object) array(
                                'id' => self::ANONYMOUS_USER,
                                'nick' => '@anonym',
                                'state' => 'active',
                                'names' => tr('Анонимен')
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
        
        expect($part);

        $cRec = Mode::get('currentUserRec');
        if ($escaped) {
            $res = core_Users::getVerbal($cRec, $part);
        } elseif(is_object($cRec)) {
            $res = $cRec->$part;
        }

        return $res;
    }
    
    
    /**
     * Форсира системния потребител да бъде текущ, преди реалния текущ или анонимния
     */
    static function forceSystemUser()
    {
        core_Users::sudo(core_Users::SYSTEM_USER);
    }
    
    
    /**
     * Форсира системния потребител да бъде текущ, преди реалния текущ или анонимния
     */
    static function cancelSystemUser()
    {
        core_Users::exitSudo(core_Users::SYSTEM_USER);
    }
    
    
    /**
     * Проверява дали текущуя потребител и системен
     * 
     * @return boolean
     */
    static function isSystemUser()
    {
        
        return self::getCurrent() == core_Users::SYSTEM_USER;
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
     * 
     * @return int|NULL
     */
    static function sudo($id)
    {
        $userRec = self::fetch((int) $id);
        
        if (is_object($userRec)) {
            $userRecS = clone($userRec);
            $userRecS->_isSudo = TRUE;
            core_Mode::push('currentUserRec', $userRecS);
            
            $rId = isset($userRec->id) ? $userRec->id : $id;
            
            return $rId;
        }
    }
    
    
    /**
     * Възстановява текущия потребител до предишна стойност.
     * 
     * Текущ става потребителя, който е бил такъв точно преди последното извикване на 
     * @see core_Users::sudo().
     * 
     */
    static function exitSudo($id = TRUE)
    {
        // Не правим нищо, ако $id е празен
        if(!isset($id)) return;

        if($id !== TRUE) {
            expect($id == core_Users::getCurrent());
        }

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
            if (self::getOwnIp($userRec->lastLoginIp) != self::getOwnIp($Users->getRealIpAddr()) &&
                $userRec->lastLoginTime > $sessUserRec->loginTime &&
                dt::mysql2timestamp($userRec->lastLoginTime) - dt::mysql2timestamp($sessUserRec->loginTime) < EF_USERS_MIN_TIME_WITHOUT_BLOCKING) {
            
                // Блокираме потребителя
                $userRec->state = 'blocked';
                $Users->save($userRec, 'state');
                
                $Users->sendActivationLetter($userRec, USERS_UNBLOCK_EMAIL, 'Отблокиране на потребител', 'unblock');
                
                $Users->logAlert("Блокиран потребител", $userRec->id);
                
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
            redirect(array('Index'), FALSE, '|Този акаунт е блокиран|*.<BR>|Причината най-вероятно е едновременно използване от две места|*.' .
                '<BR>|На имейлът от регистрацията е изпратена информация и инструкция за отблокиране|*.');
        }
        
        if ($userRec->state == 'draft') {
            redirect(array('Index'), FALSE, '|Този акаунт все още не е активиран|*.<BR>' .
                '|На имейлът от регистрацията е изпратена информация и инструкция за активация|*.');
        }
        
        if ($userRec->state != 'active' || $userRec->maxIdleTime > EF_USERS_SESS_TIMEOUT) {
            $Users->logout();
            redirect(getCurrentUrl());
        }
        
        $userRec->refreshTime = $now;
        
        // Премахваме паролата от записа
        unset($userRec->ps5Enc);

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
     * 
     * 
     * @param stdObject $rec
     * @param string $tpl
     * @param string $subject
     * @param string $act
     */
    public static function sendActivationLetter_($rec, $tpl = USERS_UNBLOCK_EMAIL, $subject = 'Отблокиране на потребител', $act = 'unblock')
    {
        
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
        
        if($nick) {
            vislog_IpNames::add($nick);
        }
        
        // Обновяваме времето на BRID кукито
        log_Browsers::updateBridCookieLifetime();
        
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
        
        if ($currentUserRec->_isSudo) return ;
        
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
            $cond = "#type = '{$type}' AND #state != 'closed'";
        } else {
            $cond = "#state != 'closed'";
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
        
        if(!$teamMates[$userId]) {
            $teams = core_Users::getUserRolesByType($userId, 'team');
            
            if(!$teams) return NULL;
            
            $query = self::getQuery();
            $query->likeKeylist('roles', $teams);
            
            $res = array();
            while($rec = $query->fetch()) {
                $res[$rec->id] = $rec->id;
            }
            
            $teamMates[$userId] = keylist::fromArray($res);
        }
        
        return $teamMates[$userId];
    }


    /**
     * Връща ранга на потребителя
     */
    public static function getRang($userId)
    {
        static $rangs;
        if(!$rangs) {
            $rangs['ceo'] = core_Roles::fetchByName('ceo');
            $rangs['manager'] = core_Roles::fetchByName('manager');
            $rangs['officer'] = core_Roles::fetchByName('officer');
            $rangs['executive'] = core_Roles::fetchByName('executive');
            $rangs['partner'] = core_Roles::fetchByName('partner');
        }
        
        $userRec = self::fetch($userId);
        $rolesArr = keylist::toArray($userRec->roles);

        foreach($rangs as $role => $roleId) {
            if($rolesArr[$roleId]) {

                return $role;
            }
        }
    }
    
    
    /**
     * Връща подчинените на потребителя
     * 
     * @param integer $userId
     * 
     * @return array
     */
    public static function getSubordinates($userId)
    {
        static $subordinatesArr = array();
        
        if (self::isContractor($userId)) return array();
        
        if (!isset($subordinatesArr[$userId])) {
            $subordinatesArr[$userId] = keylist::toArray(self::getTeammates($userId));
            
            if (!haveRole('ceo', $userId)) {
                $managers  = core_Users::getByRole('manager');
                $subordinatesArr[$userId] = array_diff($subordinatesArr[$userId], $managers);
            }
            if (!haveRole('manager', $userId)) {
                $powerUsers  = core_Users::getByRole('powerUser');
                $subordinatesArr[$userId] = array_diff($subordinatesArr[$userId], $powerUsers);
            }
            
            $ceos = core_Users::getByRole('ceo');
            $subordinatesArr[$userId] = array_diff($subordinatesArr[$userId], $ceos);
        }
        
        return $subordinatesArr[$userId];
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
        static $users = array();
        
        expect($roleId);
        
        if(!is_numeric($roleId)) {
            $roleId   = core_Roles::fetchByName($roleId);
        }

        if(!$users[$roleId]) {
        
            $query = static::getQuery();
            $query->where("#state = 'active'");
            $query->like('roles', "|{$roleId}|");
            
            while ($rec = $query->fetch()) {
                $users[$roleId][$rec->id] = $rec->id;
            }
        }
        
        return $users[$roleId];
    }
    
    
    /**
     * Проверка дали потребителя има посочената роля/роли
     * @param $roles array, keylist, list
     */
    static function haveRole($roles, $userId = NULL)
    {
        if(!$userId) {
            $userId = core_Users::getCurrent();
        }

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
                
                // Системният потребител има роля system
                if($role == 'system' && core_Users::getCurrent() == core_Users::SYSTEM_USER) return TRUE;
                
                // Анонимният потребител има роля anonym
                if($role == 'anonym' && core_Users::getCurrent() == 0) return TRUE;
  
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
     * Връща реалното IP на потребителя
     */
    static function getOwnIp($ip)
    {
        static $ips;
 
        if(!is_array($ips)) {
            $ips = arr::make(BGERP_OWN_IPS);
        }

        if(in_array($ip, $ips)) {
            $ip = $ips[0];
        }

        return $ip;
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
        
        $nick = strtolower($nick);

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
        $fAdmin = core_Setup::get('FIRST_ADMIN');
        $fAdmin = trim($fAdmin);
        
        if ($fAdmin) {
            
            return $fAdmin;
        }
        
        $Roles = cls::get('core_Roles');
        $adminId = $Roles->fetchByName('admin');
        
        $id = self::fetchField("#roles LIKE '%|$adminId|%' AND #state != 'rejected'", 'id');
        
        return $id;
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
   
        redirect($newUrl);
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

            if($userId->nick) {

                return $userId->nick;
            }
            
            // Вземаме id-то от записа
            $userId = $userId->id;
        }
        
        // Ако е id на потребител от модела
        if ($userId > 0) {
            
            // Вземаме ника от записа
            $nick = self::fetch($userId)->nick;

        } elseif($userId == core_Users::SYSTEM_USER) {
            
            // Ако е сустемния потребител
            $nick = core_Setup::get('SYSTEM_NICK');
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
            
            if (!@fopen(toUrl(array('core_Cron', 'cron'), 'absolute'), 'r')) {
                self::logWarning('Не може да се пусне крон ръчно');
            }
            
            $this->runCron = FALSE;
        }
    }


    /**
     * Филтрира опциите за избор на потребител при мограцията
     */    
    public static function filterUserForMigrateFolders($type)
    {
        foreach($type->options as $id => $opt) {
            $value = is_object($opt) ? $opt->value : $opt;

            if($value == $type->params['preventId']) {
                unset($type->options[$id]); 
            }
        }
    }


    /**
     * Мигриране на папки на потребител
     */
    public function act_MigrateFolders()
    {
        requireRole('admin');

        $form = cls::get('core_Form');

        $form->FLD('userFrom', 'user(allowEmpty)', 'caption=Потребител - образец->Избор,refreshForm,silent,mandatory');

        if($userFrom = Request::get('userFrom')) {
            
            list($team, $user) = explode('_', $userFrom);

            // bp(self::fetch($user), self::fetch($team));

            // $teamMates = self::getTeammates($userFrom);
            
            $team = core_Roles::fetchById($team);
            $rang = self::getRang($user);

            $form->FLD('userTo', "user(roles={$team},allowEmpty, preventId={$user}, filter=core_Users::filterUserForMigrateFolders)", 'caption=Приемен потребител->Избор,mandatory');
        }

        $rec = $form->input();

        if($form->isSubmitted()) {

            $fQuery = doc_Folders::getQuery();
            $fQuery->where("#shared LIKE '%|{$rec->userFrom}|%'");

            while($fRec = $fQuery->fetch()) {

                if(($fRec->inCharge == $rec->userTo) && $fRec->access == 'private') continue;
                if($fRec->access == 'secret') continue;

                if(!keylist::isIn($rec->userTo, $fRec->shared)) {

                    $mvc = cls::get($fRec->coverClass);
                    $cRec = $mvc->fetch($fRec->coverId);
                    $cRec->shared = keylist::addKey($cRec->shared, $rec->userTo);
                    $mvc->save($cRec, 'shared');
                    $res[] = doc_Folders::getLink($fRec->id);
                }
            }
        }
        
        $form->title = "Миграция на споделени папки";
        $form->toolbar->addSbbtn('Миграция', 'save');

        $form->toolbar->addBtn('Отказ', array('core_Users'), 'ef_icon=img/16/close-red.png, title=Прекратяване на миграцията');

        $html = $this->renderWrapping($form->renderHtml());

        if($cnt = count($res)) {
            $html .= "<h2 style='margin-left:15px'>Мигрирани са $cnt папки</h2>";
            $html .= "<ul><li>" . implode('</li><li>', $res) . "</li></ul>";
        } elseif($form->isSubmitted()) {
            $html .= "<h2 style='margin-left:15px'>Няма мигрирани папки</h2>";
        }

        return $html;
    }

    /**
     * Връща разбираемо за човека заглавие, отговарящо на ключа
     */
    public static function getTitleById($id, $escaped = TRUE)
    {
        $me = cls::get(get_called_class());
        
        if($id>0) {
            $uwr = $me->getRolesWithUsers();
            $rec = $uwr['r'][$id];
        }

        if(!$rec) {
            $rec = new stdClass();  
            try {$rec = $me->fetch($id);} catch(ErrorException $e) {}
        }
        
        if(!$rec) return '??????????????';
		
        return $me->getRecTitle($rec, $escaped);
    }

}
