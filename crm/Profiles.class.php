<?php
/**
 * Мениджър на потребителски профили
 *
 * @category  bgerp
 * @package   crm
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.12
 */
class crm_Profiles extends core_Master
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = array();


    /**
     * Заглавие на мениджъра
     */
    var $title = "Профили";


    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Профил";


    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/vcard.png';


    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    var $loadList = 'plg_Created,crm_Wrapper,plg_RowTools';


    /**
     * Кой  може да пише?
     */
    var $canWrite = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой има право да листва всички профили?
     */
    var $canList = 'admin';


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('userId', 'key(mvc=core_Users, select=nick)', 'caption=Потребител,mandatory,notNull');
        $this->FLD('personId', 'key(mvc=crm_Persons)', 'input=hidden,silent,caption=Визитка,mandatory,notNull');
        
        $this->setDbUnique('userId');
        $this->setDbUnique('personId');
    }
    
    
    /**
     * Подготовка за рендиране на единичния изглед
     * 
     * Използва crm_Persons::prepareSingle() за да подготви данните и за асоциирата визитка.
     *  
     * @param crm_Profiles $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingle(crm_Profiles $mvc, $data)
    {
        if ($data->rec->personId) {
            if(!$data->rec->Person) {
                $data->rec->Person = new stdClass();
            }
            $data->rec->Person->rec = crm_Persons::fetch($data->rec->personId);
            crm_Persons::prepareSingle($data->rec->Person);
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
     * @param core_ET $tpl
     * @param stdClass $data
     */
    public static function on_AfterRenderSingle(crm_Profiles $mvc, &$tpl, $data)
    {
        if ($data->rec->Person) {
            $tpl = crm_Persons::renderSingle($data->rec->Person);
        }
    }
    
    
    /**
     * Екшън за смяна на парола
     * 
     * return core_ET
     */
    public static function act_ChangePassword()
    {
        requireRole('user');

        $data = new stdClass();
        
        $data->form = static::getForm();
        
        /* @var $form core_Form */
        $form = &$data->form;

        foreach($form->fields as &$field) {
            $field->input = 'none';
        } 
        
        $form->FNC('old', 'password', 'caption=Стара парола,input,mandatory');
        $form->FNC('new', 'password', 'caption=Нова Парола,input,mandatory');
        $form->FNC('newAgain', 'password', 'caption=Нова Парола (пак),input,mandatory');
        
        $retUrl = getRetUrl();
        
        // Ако формата е успешно изпратена - запис, лог, редирект
        if (static::validatePasswordForm($form)) {
            // Записваме данните
            if (core_Users::setPassword($form->rec->new))  {
                // Правим запис в лога
                static::log('change_password');
            
                // Редиректваме към предварително установения адрес
                return new Redirect($retUrl, 'Паролата е сменена успешно');
            }
        }
        
        // Подготвяме лентата с инструменти на формата
        $form->toolbar->addSbBtn('Смяна', 'change_password', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', $retUrl, array('class' => 'btn-cancel'));
        
        // Потготвяме заглавието на формата
        $form->title = 'Смяна на паролата';
        
        // Получаваме изгледа на формата
        $tpl = $form->renderHtml();
        
        // Опаковаме изгледа
        $tpl = static::renderWrapping($tpl, $data);
        
        return $tpl;
    }
    
    
    /**
     * Зарежда от заявката и валидира полетата на формата за смяна на парола
     *  
     * @param core_Form $form
     * @return boolean
     */
    protected static function validatePasswordForm(core_Form $form)
    { 
        $form->input();
        
        if ($form->isSubmitted()) {
            if (core_Users::getCurrent('ps5Enc') != core_Users::encodePwd($form->rec->old)) {
                $form->setError('old', 'Грешна стара парола');
            }
            
            if ($form->rec->new != $form->rec->newAgain) {
                $form->setError('new', 'Двете пароли не съвпадат');
                $form->setError('newAgain');
            }
        }
    
        return $form->isSubmitted();
    }
    

    /**
     *
     */
    public static function on_AfterInputEditForm(crm_Profiles $mvc, core_Form $form)
    {
        $form->rec->_syncUser = TRUE;
    }
    

    /**
     *
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        $usersQuery = core_Users::getQuery();

        $opt = array();

        $query = self::getQuery();

        $used = array();

        while($rec = $query->fetch()) {
            if($rec->id != $data->form->rec->id) {
                $used[$rec->userId] = TRUE;
            }
        }
 
        while($uRec = $usersQuery->fetch("#state = 'active'")) {
            if(!$used[$uRec->id]) {
                $opt[$uRec->id] = $uRec->nick;
            }
        }
        
        $data->form->setOptions('userId', $opt);
        
        $addUserUrl = array(
            'core_Users', 
            'add', 
            'personId'=>Request::get('personId'), 
            'ret_url'=>getRetUrl()
        );
        
        $data->form->toolbar->addBtn('Нов потребител', $addUserUrl, array('class'=>'btn-add'));
    }
    

    /*
     * Методи за подготовка и показване на потребителски профил като детайл на визитка
     * 
     *  o prepareProfile()
     *  o renderProfile()
     *  
     */
    
    /**
     * Подготвя данните необходими за рендиране на профил на визитка
     */
    function prepareProfile($data)
    {
        expect($data->masterId);

        $data->profile = static::fetch("#personId = {$data->masterId}");

        if($data->profile->userId) {
            if ($data->profile) {
                $data->profile->userRec = core_Users::fetch($data->profile->userId);
                if(core_Users::getCurrent() == $data->profile->userId) {
                    $data->profile->userRec->lastLoginTime = core_Users::getCurrent('lastLoginTime');
                    $data->profile->userRec->lastLoginIp = core_Users::getCurrent('lastLoginIp');
                }
            }
        
            if($data->profile->userRec->id == core_Users::getCurrent('id')) {
                $data->changePassUrl =  array($this, 'changePassword', 'ret_url'=>TRUE);
            }
        }

        $data->canChange = haveRole('admin');
    }
    
    
    /**
     * Рендира потребителски профил
     */
    function renderProfile($data)
    {
        $tpl = new ET(getFileContent('crm/tpl/ContragentDetail.shtml'));
        
        $tpl->append(tr('Потребителски профил'), 'title');
        
        if ($data->profile->userId) {
            $profileTpl = new ET(getFileContent('crm/tpl/Profile.shtml'));
            $userRow = core_Users::recToVerbal($data->profile->userRec);
            
            $profileTpl->append(str_repeat('*', 7), 'password');

            if ($data->changePassUrl) {
                $changePasswordBtn = ht::createLink(
                    '(' . tr('cмяна' . ')'), $data->changePassUrl, FALSE, 
                    'title=' . tr('Смяна на парола')
                );
                $profileTpl->append($changePasswordBtn, 'password');
            }
            
            if (!empty($userRow->lastLoginTime)) {
                $userRow->lastLoginInfo = sprintf('%s от %s', 
                    $userRow->lastLoginTime, $userRow->lastLoginIp
                );
            } else {
                $userRow->lastLoginInfo = '<span class="quiet">Няма логин</span>';
            }

            $profileTpl->placeObject($userRow);
            $profileTpl->removeBlocks();

            $tpl->append($profileTpl, 'content');
        } 
 
        if($data->canChange && !Mode::is('printing')) {
            if(!$data->profile->userId) {
                $tpl->append('<p>' . tr("Няма профил") . '</p>', 'content');
            }
            if(!$data->profile) {
                $url = array($this, 'edit', 'personId' => $data->masterId, 'ret_url' => TRUE);
                $img = "<img src=" . sbf('img/16/user_add.png') . " width='16' height='16'>";
                $tpl->append(
                    ht::createLink(
                        $img, $url, FALSE, 
                        'title=' . tr('Асоцииране с потребител')
                    ), 
                    'title'
                );
            } else {
                $url = array('core_Users', 'edit', $data->profile->userId, 'ret_url' => TRUE);
                $img = "<img src=" . sbf('img/16/edit.png') . " width='16' height='16'>";
                $tpl->append(
                    ht::createLink(
                        $img, $url, NULL, 
                        'title=' . tr('Редактиране на потребителските данни за достъп')
                    ), 
                    'title'
                );

                $url = array($this, 'delete', $data->profile->id, 'ret_url' => TRUE);
                $img = "<img src=" . sbf('img/16/cross.png') . " width='16' height='16'>";
                $tpl->append(
                    ht::createLink(
                        $img, $url, 'Внимание! Връзката между визитката и потребителя ще бъде прекъсната!', 
                        'title=' . tr('Разкачане на визитката от потребителя')
                    ), 
                    'title'
                );


            }
        }
        
        return $tpl;
    }

    
    /**
     * След инсталиране на пакета CRM:
     * 
     *  о Създаване на CRM-група за потребителски профили (ако няма)
     *  o Конфигуриране на групата за профили с екстендер 'profile'
     *
     * @param crm_Profiles $mvc
     * @param stdClass $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $profilesGroup = $mvc::profilesGroupName();
        
        core_Users::forceSystemUser();
        
        // Създаване (ако няма) на група в crm_Groups за потребителските профили. 
        if (!$profiles = crm_Groups::fetch("#name = '{$profilesGroup}'")) {
            $profiles = (object)array(
                'name' => $profilesGroup,
                'extenders' => 'profile',
            );
            crm_Groups::save($profiles);
        }
        
        // Добавяме (ако няма) екстендер 'profiles' на групата за потребителски профили 
        $extenders = type_Keylist::toArray($profiles->extenders);
        
        if (!isset($extenders['profile'])) {
            $profiles->extenders['profile'] = 'profile';
            $profiles->extenders = type_Keylist::fromArray($profiles->extenders);
            crm_Groups::save($profiles);
        }
        
        core_Users::cancelSystemUser();
    }
    
    
    /**
     * Промяна на данните на асоциирания потребител след запис на визитка
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
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
     * Извилича служебната CRM-група в която се записват потребителските профили
     * 
     * @return stdClass
     */
    public static function fetchCrmGroup()
    {
        $profilesGroup = self::profilesGroupName(); 
        
        return crm_Groups::fetch("#name = '{$profilesGroup}'");
    }
    
    
    /**
     * Името на група на визитника в която са всички визитки асоцииран с потребител
     * 
     * @return string
     */
    public static function profilesGroupName()
    {
        return 'Потребителски профили'; // @TODO да се изнесе като клас-променлива или в конфиг.
    }
    
    
    /**
     * Визитката, асоциирана с потребителски акаунт
     * 
     * @param int $userId
     * @return stdClass
     */
    public static function getProfile($userId = NULL)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent('id');
        }
        
        if (!$profile = static::fetch("#userId = {$userId}")) {
            return NULL;
        }
        
        return crm_Persons::fetch($profile->personId);
    }
    
    
    /**
     * Синхронизиране на данните (имена, имейл) на профилната визитка с тези на потребител.
     * 
     * Ако лице с ключ $personId не съществува - създава се нов потребител.
     * 
     * 
     * @param int $personId key(mvc=crm_Persons), може и да е NULL
     * @param stdClass $user
     * @return int|boolean personId или FALSE при неуспешен запис
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
            $person = (object)array(
                'groupList' => '',
                'access'    => 'private',
                'email'     => ''
            );
            $profilesGroup = static::fetchCrmGroup();
            $person->groupList = type_Keylist::addKey($person->groupList, $profilesGroup->id);
            $mustSave = TRUE;
        }
        
        
        if(!empty($user->names)) {
            $person->name      = $user->names;
            $mustSave = TRUE;
        }
        
        // Само ако записа на потребителя има 
        if(!empty($user->email)) {
            $person->email     = type_Emails::prepend($person->email, $user->email);
            $mustSave = TRUE;
        }
        
        // Само ако досега визитката не е имала inCharge, променения потребител и става отговорник
        if(!$person->inCharge) {
            $person->inCharge  = $user->id;
            $mustSave = TRUE;
        }

        $person->_skipUserUpdate = TRUE; // Флаг за предотвратяване на безкраен цикъл
        
        if($mustSave) {

            return crm_Persons::save($person);
        }
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
        
        if (!empty($personRec->email)) {
            // Вземаме първия (валиден!) от списъка с лични имейли на лицето
            $emails = type_Emails::toArray($personRec->email);
            if (!empty($emails)) {
                $userRec->email = reset($emails);
            }
        }
        
        if (!empty($personRec->name)) {
            $userRec->names = $personRec->name;
        }
        
        if (!empty($personRec->photo)) {
            $userRec->avatar = $personRec->photo; 
        }
        
        // Флаг за предотвратяване на безкраен цикъл след промяна на визитка
        $userRec->_skipPersonUpdate = TRUE;
        
        core_Users::save($userRec);
    }
    
    
    /**
     * URL към профилната визитка на потребител
     * 
     * @param string|int $user ако е числова стойност се приема за ид на потребител; иначе - ник
     * @return array URL към визитка; FALSE ако няма такъв потребител или той няма профилна визитка
     */
    public static function getUrl($userId)
    {
        
        // Извличаме профила (връзката м/у потребител и визитка)
        $personId = static::fetchField("#userId = {$userId}", 'id');

        if (!$personId) {
            // Няма профил или не е асоцииран с визитка
            return FALSE;
        }
        
        return array(get_called_class(), 'single', $personId);
    }
    
    
    /**
     * Метод за удобство при генерирането на линкове към потребителски профили
     * 
     * @param string $title    @see core_Html::createLink()
     * @param string|int $user @see crm_Profiles::getUrl()
     * @param string $warning  @see core_Html::createLink()
     * @param array $attr      @see core_Html::createLink()
     */
    public static function createLink($title, $user, $warning = FALSE, $attr = array())
    {   
        if (is_numeric($user)) {
            // $user е ид на потребител
            $userId = intval($user);
        } else {
            // $user е nick на потребител
            $userId = core_Users::fetchField(array("#nick = '[#1#]'", $user), 'id');
        }

        $link = $title;
        
        $url  = static::getUrl($userId);

        if ($url) { 
            $attr['class'] .= ' profile';
            foreach (array('ceo', 'manager', 'officer', 'executive', 'contractor') as $role) {
                if (core_Users::haveRole($role, $userId)) {
                    $attr['class'] .= " {$role}";
                } 
            }
            
            $link = ht::createLink($title, $url, $warning, $attr);
        }
        
        return $link;
    }


    /**
     * След подготовката на редовете на списъчния изглед
     * 
     * Прави ника и името линкове към профилната визитка (в контекста на crm_Profiles)
     * 
     * @param crm_Profiles $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListRows(crm_Profiles $mvc, $data)
    {
        $rows = &$data->rows;
        $recs = &$data->recs;
        
        if(count($rows)) {
            foreach ($rows as $i=>&$row) {
                $rec = &$recs[$i];
        
                if ($url = $mvc::getUrl($rec->userId)) {
                    $row->personId = ht::createLink($row->personId, $url);
                    $row->userId   = ht::createLink($row->userId, $url);
                }
            }
        }
    }
}
