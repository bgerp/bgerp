<?php


/**
 * class batch_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със следенето на партидности
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'batch_Defs';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Партиди и движения";
            
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'batch_Defs',
    		'batch_Items',
    		'batch_Movements',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'batch';
    

    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "batch_definitions_Varchar,batch_definitions_Serial,batch_definitions_ExpirationDate";
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.4, 'Логистика', 'Партиди', 'batch_Defs', 'default', "batch,ceo"),
        );
    
        
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
        
        $Plugins = cls::get('core_Plugins');
       // $html .= $Plugins->installPlugin('Партидни движения на експедиционите нареждания', 'batch_plg_DocumentMovement', 'store_ShipmentOrders', 'private');
        //$html .= $Plugins->installPlugin('Партидни движения на детайлите на експедиционите нареждания', 'batch_plg_DocumentMovementDetail', 'store_ShipmentOrderDetails', 'private');
        
        //$html .= $Plugins->installPlugin('Партидни движения на сделките', 'batch_plg_DocumentMovement', 'deals_DealMaster', 'family');
        //$html .= $Plugins->installPlugin('Партидни движения на детайлите на продажбите', 'batch_plg_DocumentMovementDetail', 'deals_DealDetail', 'family');
        
        return $html;
    }
}