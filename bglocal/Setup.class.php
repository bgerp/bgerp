<?php


/**
 * class drdata_Setup
 *
 * Инсталиране/Деинсталиране на
 * доктор за адресни данни
 *
 *
 * @category  bgerp
 * @package   bglocal
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bglocal_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.15';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'bglocal_Banks';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Готови данни и типове от различни области';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        
        'bglocal_Mvr',
        'bglocal_Banks',
        'bglocal_Address',
        'bglocal_NKID',
        'bglocal_NKPD',
        'bglocal_DistrictCourts',
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'bglocal_interface_FreeShipping';
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
