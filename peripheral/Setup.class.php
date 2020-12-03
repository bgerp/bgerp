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
class peripheral_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'peripheral_Devices';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Периферни устройства';
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'peripheral';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.99990, 'Система', 'Периферия', 'peripheral_Devices', 'default', 'peripheral, admin'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'peripheral_Devices',
        'migrate::bridAndIp0719'
    );
    
    
    /**
     * Плъгини, които трябва да се инсталират
     */
    public $plugins = array(
            array('Избор на терминал', 'peripheral_TerminalChoicePlg', 'core_Users', 'private'),
    );
    
    
    /**
     * Миграция за прехвърляне на brid и IP полетатата от устройствата
     */
    public static function bridAndIp0719()
    {
        $Devices = cls::get('peripheral_Devices');
        
        $Devices->db->connect();
        
        $bridField = str::phpToMysqlName('brid');
        $ipField = str::phpToMysqlName('ip');
        
        if (!$Devices->db->isFieldExists($Devices->dbTableName, $bridField) && !$Devices->db->isFieldExists($Devices->dbTableName, $ipField)) {
            
            return ;
        }
        
        $Devices->FLD('brid', 'text(rows=2)', 'caption=Компютър->Браузър');
        $Devices->FLD('ip', 'text(rows=2)', 'caption=Компютър->IP');
        
        $query = $Devices->getQuery();
        
        $query->where('#brid IS NOT NULL');
        $query->orWhere('#ip IS NOT NULL');
        
        while ($rec = $query->fetch()) {
            $Devices->save($rec, 'brid, ip');
        }
    }
}
