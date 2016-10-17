<?php


/**
 * class pallet_Setup
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
class pallet_Setup extends core_ProtoSetup
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
    var $startCtr = 'pallet_Movements';
    
    
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
            'pallet_Movements',
            'pallet_Pallets',
            'pallet_PalletTypes',
            'pallet_Racks',
            'pallet_RackDetails',
            'pallet_Zones',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'pallet';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.2, 'Логистика', 'Палетен склад', 'pallet_Movements', 'default', "pallet,ceo"),
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    
    	$html .= core_Classes::add('pallet_ArrangeStrategyTop');
    	$html .= core_Classes::add('pallet_ArrangeStrategyBottom');
    	$html .= core_Classes::add('pallet_ArrangeStrategyMain');
    	
    	$html .= core_Roles::addOnce('pallet', 'storeWorker');
    	
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
}