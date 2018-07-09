<?php


/**
 * class pallet_Setup
 *
 * Инсталиране/Деинсталиране на пакета за палетния склад
 *
 *
 * @category  bgerp
 * @package   pallet
 *
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pallet_Setup extends core_ProtoSetup
{
    /**
     * Версия на компонента
     */
    public $version = '0.1';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'acc=0.1';
    
    
    /**
     * Стартов контролер за връзката в системното меню
     */
    public $startCtr = 'pallet_Movements';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Палетно складово стопанство';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
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
    public $roles = 'pallet';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.2, 'Логистика', 'Палетен склад', 'pallet_Movements', 'default', 'pallet,ceo'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
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
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
