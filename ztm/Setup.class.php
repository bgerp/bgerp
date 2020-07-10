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
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'ztm_Devices',
    );
}
