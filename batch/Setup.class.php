<?php


/**
 * При какъв процент при достигането на края на срока на годност партидите да се оцветяват
 */
defIfNot('BATCH_EXPIRYDATE_PERCENT', 0.15);


/**
 * Партиди без движения колко месеца назаде да се затварят
 */
defIfNot('BATCH_CLOSE_OLD_BATCHES', core_DateTime::SECONDS_IN_MONTH);


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
    var $startCtr = 'batch_Items';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Партиди и серийни номера към складовите документи";
            
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'batch_Defs',
    		'batch_Items',
    		'batch_Movements',
    		'batch_CategoryDefinitions',
    		'batch_Features',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'batch';
    

    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "batch_definitions_Varchar,batch_definitions_Serial,batch_definitions_ExpirationDate,batch_definitions_Document";
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.4, 'Логистика', 'Партиди', 'batch_Items', 'default', "batch,ceo"),
        );
    
        
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    		'BATCH_EXPIRYDATE_PERCENT' => array("percent", 'caption=Оцветяване на изтичащите партиди->Преди края'),
    		'BATCH_CLOSE_OLD_BATCHES'  => array('time', 'caption=Затваряне на стари партиди->Без движения')
    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Партидни движения на експедиционите нареждания', 'batch_plg_DocumentMovement', 'store_ShipmentOrders', 'private');
        $html .= $Plugins->installPlugin('Партидни движения на детайлите на експедиционите нареждания', 'batch_plg_DocumentMovementDetail', 'store_ShipmentOrderDetails', 'private');
        
        $html .= $Plugins->installPlugin('Партидни движения на складовите разписки', 'batch_plg_DocumentMovement', 'store_Receipts', 'private');
        $html .= $Plugins->installPlugin('Партидни движения на детайлите на складовите разписки', 'batch_plg_DocumentMovementDetail', 'store_ReceiptDetails', 'private');
        
        $html .= $Plugins->installPlugin('Партидни движения на сделките', 'batch_plg_DocumentMovement', 'deals_DealMaster', 'family');
        $html .= $Plugins->installPlugin('Партидни движения на детайлите на продажбите', 'batch_plg_DocumentMovementDetail', 'deals_DealDetail', 'family');
        
        $html .= $Plugins->installPlugin('Партидни движения на междускладовите трансфери', 'batch_plg_DocumentMovement', 'store_Transfers', 'private');
        $html .= $Plugins->installPlugin('Партидни движения на детайлите на междускладовите трансфери', 'batch_plg_DocumentMovementDetail', 'store_TransfersDetails', 'private');
        
        $html .= $Plugins->installPlugin('Партидни движения на производствените документи', 'batch_plg_DocumentMovement', 'deals_ManifactureMaster', 'family');
        $html .= $Plugins->installPlugin('Партидни движения на детайлите на производствените документи', 'batch_plg_DocumentMovementDetail', 'deals_ManifactureDetail', 'family');
        
        $html .= $Plugins->installPlugin('Партидни движения на протокола за производство', 'batch_plg_DirectProductionNoteMovement', 'planning_DirectProductionNote', 'private');
        
        $html .= $Plugins->installPlugin('Партиден детайл на артикулите', 'batch_plg_ProductDetail', 'cat_Products', 'private');
        $html .= $Plugins->installPlugin('Детайл за дефиниции на партиди', 'batch_plg_CategoryDetail', 'cat_Categories', 'private');
        
        $html .= $Plugins->installPlugin('Партиден детайл на детайла напротоколите за отговорно пазене', 'batch_plg_DocumentMovementDetail', 'store_InternalDocumentDetail', 'family');
        $html .= $Plugins->installPlugin('Партидни движения на протоколите за отговорно пазене', 'batch_plg_DocumentMovement', 'store_ConsignmentProtocols', 'private');
        
        return $html;
    }
}