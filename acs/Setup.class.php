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
    public $depends = 'crm=0.1';
    
    
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
        
        return $html;
    }
}
