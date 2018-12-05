<?php


/**
 *
 *
 * @category  vendors
 * @package   peripheral
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
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
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'peripheral_Devices',
    );
}
