<?php

/**
 * Клас 'ztm_Plugin'
 *
 * Табло с настройки за състояния
 *
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class ztm_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'ztm_Adapter';
    

    /**
     * Описание на модула
     */
    public $info = 'Контролен панел';
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
            array('ztm'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.4, 'Мониторинг', 'ZTM', 'ztm_Devices', 'default', 'ztm, ceo'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'ztm_Devices',
            'ztm_Registers',
            'ztm_RegisterValues',
            'ztm_LongValues',
            'ztm_Profiles',
            'ztm_ProfileDetails',
            'migrate::importOnceRegistersFromZero7',
    );
    
    
    /**
     * Миграция: за зареждане на регистри от нула
     */
    public function importOnceRegistersFromZero7()
    {
        $Registers = cls::get('ztm_Registers');
        
        $file = 'ztm/csv/Registri.csv';
        
        $fields = array(
            0 => 'name',
            1 => 'type',
            2 => 'range',
            3 => 'plugin',
            4 => 'priority',
            5 => 'default',
            6 => 'description',
        );
        
        csv_Lib::importOnceFromZero($Registers, $file, $fields);
    }
}
