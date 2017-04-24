<?php


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с label
 *
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'label_Labels';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Отпечатване на етикети";
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.66, 'Производство', 'Етикетиране', 'label_Labels', 'default', "label, admin, ceo"),
        );
    
        
    // Инсталиране на мениджърите
    public $managers = array(
        'label_Labels',
        'label_Templates',
        'label_TemplateFormats',
        'label_Media',
        'label_Counters',
        'label_CounterItems',
        'label_Prints',
        'migrate::addDefaultMedia'
    );
    
        
    /**
     * Инсталиране на пакета
     */
    function install()
    {   
        $html = parent::install();
        
        // Добавяме роля
        $html .= core_Roles::addOnce('label');
        
        // Добавяме роля за master
        $html .= core_Roles::addOnce('labelMaster', 'label');
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Принтиране на етикети от задачи за производство', 'label_plg_Print', 'planning_Tasks', 'private');
        $html .= $Plugins->installPlugin('Принтиране на етикети от ЕН-та', 'label_plg_Print', 'store_ShipmentOrders', 'private');
        
        return $html;
    }
    
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Миграция за добавяне на медия към шаблоните
     */
    public static function addDefaultMedia()
    {
        // Вземаме размера на първата медия
        $mQuery = label_Media::getQuery();
        while ($mRec = $mQuery->fetch()) {
            $sizes = label_Media::getSize($mRec->width, $mRec->height);
            
            if ($sizes) break;
        }
        
        if (!$sizes) return ;
        
        // Добавяме размера към всички шаблони, които нямат размери
        $tQuery = label_Templates::getQuery();
        $tQuery->where("#sizes IS NULL");
        $tQuery->orWhere("#sizes = ''");
        
        while ($tRec = $tQuery->fetch()) {
            $tRec->sizes = $sizes;
            label_Templates::save($tRec, 'sizes');
        }
    }
}
