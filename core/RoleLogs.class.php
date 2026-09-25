<?php


/**
 * Лог за всички смени на ролите
 *
 * @category  ef
 * @package   core
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class core_RoleLogs extends core_Manager
{
    /**
     * Заглавие на таблицата
     */
    public $title = 'Логове на роли';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    public $canReject = 'no_one';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_SystemWrapper, plg_Created, plg_State, plg_Sorting';
    
    
    /**
     * Кой може да види IP-то от последното логване
     */
    public $canViewlog = 'powerUser';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('userId', 'user(select=nick, allowEmpty)', 'caption=Потребител, silent');
        $this->FLD('state', 'enum(active=Активен,draft=Непотвърден,blocked=Блокиран,closed=Затворен,rejected=Заличен)', 'caption=Състояние,default=draft');
        $this->FLD('roles', 'keylist(mvc=core_Roles,select=role,groupBy=type, orderBy=orderByRole)', 'caption=Роли');
        
        $this->setDbIndex('userId');
        $this->setDbIndex('createdOn');
    }
    
    
    /**
     * Записва в лога опитите за логване
     *
     * @param string   $roles
     * @param string   $state
     * @param null|int $userId
     *
     * @return int
     */
    public static function add($roles, $state, $userId = null)
    {
        // Ако не е подаден потребител
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $rec = new stdClass();
        $rec->userId = $userId;
        $rec->state = $state;
        $rec->roles = $roles;
        
        static::save($rec);
        
        return $rec->id;
    }
    
    
    /**
     * Проверка дали има права за разглеждане на лога на съответния потребител
     *
     * @param int $userId
     * @param int $CUserId
     *
     * @return bool
     */
    public static function canViewUserLog($userId, $CUserId)
    {
        $rec = new stdClass();
        $rec->userId = $userId;
        
        return self::haveRightFor('viewlog', $rec, $CUserId);
    }
    
    
    /**
     * Връща масив с логовете за потребителя
     *
     * @param int $userId
     * @param int $perPage
     *
     * @return array
     *               array rows
     *               object pager
     */
    public static function getLogsForUser($userId, $perPage = 10)
    {
        $query = self::getQuery();
        $query->where(array("#userId = '[#1#]'", $userId));
        $query->orderBy('createdOn', 'DESC');
        $query->orderBy('id', 'DESC');
        
        $me = cls::get(get_called_class());
        $data = new stdClass();
        $data->query = $query;
        $me->listItemsPerPage = $perPage;
        
        $data->listFields = array('roles', 'state', 'createdOn', 'createdBy');
        
        $me->prepareListPager_($data);
        $me->prepareListRecs_($data);
        $me->prepareListRows_($data);
        
        $resArr = array('rows' => $data->rows, 'pager' => $data->pager);
        
        return $resArr;
    }
    
    
    /**
     * Откроява промените спрямо предишния запис за същия потребител.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (!isset($fields['roles']) || empty($rec->id) || empty($rec->userId) || empty($rec->createdOn)
            || Mode::is('text', 'plain') || Mode::get('text-export')) {

            return;
        }

        // Предишният запис може да е извън текущата страница или филтър.
        $query = $mvc->getQuery();
        $query->where(array("#userId = '[#1#]'", $rec->userId ?? null));
        $query->where(array("(#createdOn < '[#1#]' OR (#createdOn = '[#1#]' AND #id < [#2#]))", $rec->createdOn ?? '', $rec->id ?? null));
        $query->orderBy('createdOn', 'DESC');
        $query->orderBy('id', 'DESC');
        $query->show('roles');
        $query->limit(1);

        if (!$previousRec = $query->fetch()) {

            return;
        }

        $roles = $rec->roles ?? '';
        $diff = type_Keylist::getDiffArr($previousRec->roles ?? '', $roles);
        if (empty($diff['add']) && empty($diff['delete'])) {

            return;
        }

        $rolesType = $mvc->getFieldType('roles');
        $verbalRoles = array();
        foreach (keylist::toArray($roles) + $diff['delete'] as $roleId) {
            $role = $rolesType->toVerbal("|{$roleId}|");
            if (isset($diff['add'][$roleId])) {
                $role = ht::createElement('span', array('class' => 'green nowrap', 'title' => tr('Добавена роля||Added role')), '+ ' . $role);
            } elseif (isset($diff['delete'][$roleId])) {
                $role = ht::createElement('del', array('class' => 'red nowrap', 'title' => tr('Премахната роля||Removed role')), '- ' . $role);
            }
            $verbalRoles[] = $role;
        }

        $row->roles = implode(', ', $verbalRoles);
    }


    /**
     *
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // В хоризонтален вид
        $data->listFilter->view = 'horizontal';
        
        // Поле за избор на потребител
        $data->listFilter->FNC('users', 'users(rolesForAll = admin, rolesForTeams = admin)', 'caption=Потребител,input,silent,autoFilter');
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->fields['state']->type->options = array('' => '') + $data->listFilter->fields['state']->type->options;
        $data->listFilter->setField('state', 'placeholderType=all');
        
        // Избираме го по подразбиране
        $data->listFilter->setDefault('state', '');
        $data->listFilter->setDefault('users', 'all_users');
        
        // Кои полета да се показват
        $data->listFilter->showFields = 'users, state';
        
        // Инпутваме заявката
        $data->listFilter->input('users, state', 'silent');

        if (!$data->listFilter->rec->users) {
            $usersId = Request::get('users');

            if ($usersId && is_numeric($usersId)) {
                $optArr = $data->listFilter->fields['users']->type->prepareOptions();

                $uRec = core_Users::fetch($usersId);

                $cUserObj = new stdClass();
                $cUserObj->keylist = "|{$usersId}|";
                $cUserObj->title = $uRec->nick . ' (' . $uRec->names . ')';;

                $optArr = array($usersId => $cUserObj) + $optArr;

                $data->listFilter->fields['users']->type->options = $optArr;

                $data->listFilter->setDefault('users', $usersId);

                $data->listFilter->input('users', 'silent');
            }
        }

        // Сортиране на записите по създаване
        $data->query->orderBy('createdOn', 'DESC');
        
        // Ако има филтър
        if ($filter = $data->listFilter->rec) {
            
            // Ако се търси по потребител
            if ($filter->users && $filter->users != 'all_users') {
                
                // Масив с избраните потребители
                $usersArr = type_Keylist::toArray($filter->users);
                
                // Филтрираме всички избрани потребители
                $data->query->orWhereArr('userId', $usersArr);
            }
            
            // Ако се търси по статус
            if ($filter->state && $filter->state != '') {
                $data->query->where(array("#state = '[#1#]'", $filter->state));
            }
        }
        
        $data->query->orderBy('createdOn', 'DESC');
        $data->query->orderBy('id', 'DESC');
    }
    
    
    /**
     *
     *
     * @param core_LoginLog $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param object        $rec
     * @param int           $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Текущия потребител може да си види записите от лога, admin и ceo могат на всичките
        if ($action == 'viewlog') {
            if ($rec && ($rec->userId != $userId)) {
                if (!haveRole('ceo, admin')) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
}
