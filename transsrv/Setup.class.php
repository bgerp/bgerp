<?php


/**
 * Клас 'transsrv_Setup' 
 *
 *
 * @category  bgerp
 * @package   transsrv
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   Property
 * @since     v 0.1
 */
class transsrv_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'transsrv_TransportModes';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Добавя артикул за транспорт";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'transsrv_TransportModes',
            'transsrv_TransportUnits',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'transsrv';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    // var $menuItems = array(
    //        array(3.09, 'Логистика', 'trans.bid', 'transbid_Auctions', 'default', "transbid,ceo"),
    //    );

    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "transsrv_ProductDrv";
         

    
}
