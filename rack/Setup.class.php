<?php


/**
 * class rack_Setup
 *
 * Инсталиране/Деинсталиране на пакета за палетния склад
 *
 *
 * @category  bgerp
 * @package   pallet
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rack_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на компонента
     */
    var $version = '0.1';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'acc=0.1';
    
    
    /**
     * Стартов контролер за връзката в системното меню
     */
    var $startCtr = 'rack_Movements';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Палетно складово стопанство";
        
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var  $managers = array(
            'rack_Products',
            'rack_Movements',
            'rack_Pallets',
            'rack_Racks',
            'rack_RackDetails',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'rack,rackMaster';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.2, 'Логистика', 'Стелажи', 'rack_Movements', 'default', "rack,ceo,store,storeWorker"),
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
    	$Plugins = cls::get('core_Plugins');
    	$html .= $Plugins->installPlugin('Връзка между междускладовия трансфер и палетния склад', 'rack_plg_Document', 'store_TransfersDetails', 'private');
    	$html .= $Plugins->installPlugin('Връзка между ЕН и палетния склад', 'rack_plg_Document', 'store_ShipmentOrderDetails', 'private');
    	
    	return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }


    /**
     * Изпълнява се след setup-а
     */
    function checkConfig()
    {
        $sMvc = cls::get('store_Stores');
        $sMvc->setupMVC();
    }
}