<?php


/**
 * class drdata_Setup
 *
 * Инсталиране/Деинсталиране на
 * доктор за адресни данни
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bglocal_Setup extends core_ProtoSetup 
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.15';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'bglocal_Banks';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Готови данни и типове от различни области";

    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            
            'bglocal_Mvr',
          	'bglocal_Banks',
            'bglocal_Address',
       		'bglocal_NKID',
            'bglocal_NKPD',
            'bglocal_DistrictCourts',
        );
    

    /**
     * Роли за достъп до модула
     */
    //var $roles = 'currency';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    /*var $menuItems = array(
            array(2.2, 'Финанси', 'Валути', 'currency_Currencies', 'default', "currency, ceo"),
        );*/
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}