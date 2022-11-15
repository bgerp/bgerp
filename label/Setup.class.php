<?php


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с label
 *
 * @category  bgerp
 * @package   label
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2022 Experta OOD
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
        'migrate::deleteOldTemplates2246',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('seeLabel'),
        array('label', 'seeLabel'),
        array('labelMaster', 'label'),
        array('seeLabelAll', 'seeLabel'),
        array('seeLabelAllGlobal', 'seeLabelAll'),
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
        $html .= $Plugins->installPlugin('Принтиране на етикети от протокол за производство', 'label_plg_Print', 'planning_DirectProductionNote', 'private');
        $html .= $Plugins->installPlugin('Принтиране на етикети от производствена операция', 'label_plg_Print', 'planning_Tasks', 'private');

        core_Interfaces::add('label_TemplateRendererIntf');

        return $html;
    }


    /**
     * Миграция за изтриване на стари шаблони
     */
    public function deleteOldTemplates2246()
    {
        $jobClassId = planning_Jobs::getClassId();
        if(isset($jobClassId)){
            $tQuery = label_Templates::getQuery();
            $tQuery->where("#classId = {$jobClassId}");
            while($tRec = $tQuery->fetch()){
                label_TemplateFormats::delete("#templateId = '{$tRec->id}'");
                label_Templates::delete($tRec->id);
                label_Prints::delete("#templateId = '{$tRec->id}'");
            }
        }
    }
}
