<?php


/**
 * Име на групата за посетителите
 */
defIfNot('ACS_VISITORS_GROUP_NAME', 'Посетители');

/**
 *
 *
 * @category  bgerp
 * @package   acs
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acs_Setup extends core_ProtoSetup
{
    /**
     * Необходими пакети
     */
    public $depends = 'crm=0.1, ztm=0.1';
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'acs_Zones';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Контрол на достъп';
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'acs';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.99991, 'Система', 'Достъп', 'acs_Zones', 'default', 'acs, admin'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'acs_Zones',
        'acs_Permissions',
        'acs_Logs',
        'acs_Tests',
        'migrate::updateUsersGroups2119',
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
            'ACS_VISITORS_GROUP_NAME' => array('varchar', 'caption=Име на групата във визитника->Име'),
    );
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
            array(
                    'systemId' => 'SyncPermissions',
                    'description' => 'Обновяване правата за достъп в устройствата',
                    'controller' => 'acs_Permissions',
                    'action' => 'SyncPermissions',
                    'period' => 1,
                    'offset' => 0,
                    'timeLimit' => 50
            ),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Добавяне на системна група за водачи на МПС
        $groupRec = (object)array('name' => $this->get('VISITORS_GROUP_NAME'), 'sysId' => 'visitors');
        crm_Groups::forceGroup($groupRec);

        // Инсталираме плъгина за аватари
        $html .= core_Plugins::installPlugin('Регистриране на достъпа', 'acs_RegisterPlg', 'ztm_RegisterValues', 'private');

        if (acs_ContragentGroupsPlg::getGroupId()) {
            $html .= '<li>Форсирана група "Контрол на достъпа"</li>';
        }

        $html .= core_Plugins::installPlugin('Добавяне на лицата към групата за достъп', 'acs_ContragentGroupsPlg', 'crm_Profiles', 'private');

        return $html;
    }


    /**
     * Миграция за добавяне на групи за контрол на достъпа
     */
    public function updateUsersGroups2119()
    {
        $uQuery = core_Users::getQuery();

        $execId = core_Roles::fetchByName('executive');

        if ($execId) {
            $uQuery->like('roles', "|{$execId}|");
        }

        $uQuery->show('id');

        $uArr = array();
        while ($rec = $uQuery->fetch()) {
            $uArr[$rec->id] = $rec->id;
        }

        $pQuery = crm_Profiles::getQuery();
        $pQuery->in('userId', $uArr);
        $pQuery->show('personId');
        $pArr = array();
        while ($pRec = $pQuery->fetch()) {
            if (!trim($pRec->personId)) {
                continue;
            }
            $pArr[$pRec->personId] = $pRec->personId;
        }

        if (!countR($pArr)) {

            return;
        }

        $gId = acs_ContragentGroupsPlg::getGroupId();

        expect($gId);

        $Persons = cls::get('crm_Persons');

        $perQuery = crm_Persons::getQuery();
        $perQuery->in('id', $pArr);
        $perQuery->show('groupListInput, groupList, buzCompanyId');

        $cArr = array();
        while ($perRec = $perQuery->fetch()) {

            $perRec->groupListInput = type_Keylist::addKey($perRec->groupListInput, $gId);

            // Вземаме всички въведени от потребителя стойност
            $inputArr = type_Keylist::toArray($perRec->groupListInput);

            // Намираме всички свъразани
            $resArr = $Persons->expandInput($inputArr);

            $perRec->groupList = type_Keylist::fromArray($resArr);

            $Persons->save_($perRec, 'groupListInput, groupList');

            if ($perRec->buzCompanyId) {
                $cArr[$perRec->buzCompanyId] = $perRec->buzCompanyId;
            }
        }

        $Companies = cls::get('crm_Companies');
        $ownComapnyRec = crm_Companies::fetchOurCompany();
        $cArr[$ownComapnyRec->id] = $ownComapnyRec->id;

        $cQuery = $Companies->getQuery();
        $cQuery->in('id', $cArr);
        $cQuery->show('groupListInput, groupList');

        while ($cRec = $cQuery->fetch()) {

            $cRec->groupListInput = type_Keylist::addKey($cRec->groupListInput, $gId);

            // Вземаме всички въведени от потребителя стойност
            $inputArr = type_Keylist::toArray($cRec->groupListInput);

            // Намираме всички свъразани
            $resArr = $Persons->expandInput($inputArr);

            $cRec->groupList = type_Keylist::fromArray($resArr);

            $Companies->save_($cRec, 'groupListInput, groupList');
        }

        crm_Groups::updateGroupsCnt('crm_Persons', 'personsCnt');
        crm_Groups::updateGroupsCnt('crm_Companies', 'companiesCnt');
    }
}
