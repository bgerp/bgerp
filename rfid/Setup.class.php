<?php

/**
 * Разрешени адреси за приемане на данни
 */
defIfNot('ALLOWED_ADDRESSES', '127.0.0.1, 120.0.1.1');

/**
 * class acc_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с rfid
 *
 *
 * @category  bgerp
 * @package   rfid
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rfid_Setup extends core_ProtoSetup
{
    /**
     * Версия
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'rfid_Events';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'RFID отчитане на раб. време';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'rfid_Readers',
        'rfid_Assignments',
        'rfid_Tags',
        'rfid_Events',
        'rfid_Holders'
    );
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'rfid_driver_HolderPerson';

    /**
     * Роли за достъп до модула
     */
    public $roles = 'rfid, admin';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.4, 'Мониторинг', 'RFID', 'rfid_Events', 'default', 'rfid, ceo,admin'),
    );

    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'ALLOWED_ADDRESSES' => array('varchar', 'mandatory, caption=IP-та от които ще идват данни')
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $html .= core_Classes::add('rfid_driver_HolderPerson');
        
        return $html;
    }
    
}
