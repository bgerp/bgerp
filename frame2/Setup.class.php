<?php



/**
 * class frame2_Setup
 *
 * Инсталиране/Деинсталиране на пакета frame2
 *
 *
 * @category  bgerp
 * @package   frame2
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class frame2_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    var $depends = '';
    
    
    /**
     * Начален контролер на пакета за връзката в core_Packs
     */
    var $startCtr = 'frame2_Reports';
    
    
    /**
     * Начален екшън на пакета за връзката в core_Packs
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Динамични справки";


    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array();

    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'frame2_Reports',
    );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'report,dashboard';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
    		array(2.56, 'Обслужване', 'Отчети', 'frame2_Reports', 'default', "report, ceo, admin"),
    );
    
    
    /**
     * Настройки за Cron
     */
    var $cronSettings = array(
    	//@TODO
    );
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
