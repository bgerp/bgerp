<?php


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с label
 *
 * @category  bgerp
 * @package   label
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class label_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'label_Prints';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Отпечатване на етикети';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.66, 'Производство', 'Етикетиране', 'label_Prints', 'default', 'label, admin, ceo'),
    );
    
    
    // Инсталиране на мениджърите
    public $managers = array(
        'label_Templates',
        'label_TemplateFormats',
        'label_Media',
        'label_Counters',
        'label_CounterItems',
        'label_Prints',
        'migrate::removeEmptyCounterItems',
        'migrate::removeOldPlugin1'
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('seeLabel'),
        array('label', 'seeLabel'),
        array('labelMaster', 'label'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Принтиране на етикети от опаковки', 'label_plg_Print', 'cat_products_Packagings', 'private');
        $html .= $Plugins->installPlugin('Принтиране на етикети от ЕН-та', 'label_plg_Print', 'store_ShipmentOrders', 'private');
        $html .= $Plugins->installPlugin('Принтиране на етикети от справки', 'label_plg_Print', 'frame2_Reports', 'private');
        $html .= $Plugins->installPlugin('Принтиране на етикети от прогрес на производствена операция', 'label_plg_Print', 'planning_ProductionTaskDetails', 'private');
        
        return $html;
    }
    
    
    /**
     * Миграция за премахване на празните записи
     */
    public static function removeEmptyCounterItems()
    {
        // Изтриваме всички празни записи без максималната стойност - за да няма дублиране
        $query = label_CounterItems::getQuery();
        $query->XPR('maxVal', 'int', 'MAX(#number)');
        $query->groupBy('counterId');
        $query->show('counterId, maxVal');
        
        $dQuery = '';
        while ($rec = $query->fetch()) {
            $dQuery .= $dQuery ? ' OR ' : '';
            $dQuery .= "(#counterId = '{$rec->counterId}' AND #number != '{$rec->maxVal}')";
        }
        if ($dQuery) {
            $dQuery = " AND ({$dQuery})";
        }
        
        label_CounterItems::delete("(#printId IS NULL || #printId = ''){$dQuery}");
    }
    
    
    /**
     * Махане на излишен плъгин
     */
    public function removeOldPlugin1()
    {
        $Plugins = cls::get('core_Plugins');
        $Plugins->deinstallPlugin('label_plg_Print', 'planning_Tasks');
        $Plugins->deinstallPlugin('label_plg_Print', 'planning_Jobs');
    }
}
