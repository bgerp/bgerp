<?php


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с label
 *
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
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
        'migrate::addDefaultMedia',
        'migrate::labelsToPrint',
        'migrate::counterItemsLabels',
        'migrate::removePlugin3',
        'migrate::barcodeToSerial',
        'migrate::templateSource',
        'migrate::repairSerchKeywords',
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
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
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
            
            if ($sizes) {
                break;
            }
        }
        
        if (!$sizes) {
            return ;
        }
        
        // Добавяме размера към всички шаблони, които нямат размери
        $tQuery = label_Templates::getQuery();
        $tQuery->where('#sizes IS NULL');
        $tQuery->orWhere("#sizes = ''");
        
        while ($tRec = $tQuery->fetch()) {
            $tRec->sizes = $sizes;
            label_Templates::save($tRec, 'sizes');
        }
    }
    
    
    /**
     * Миграция за преместване на данните от етикетите в отпечатванията
     */
    public function labelsToPrint()
    {
        $clsName = 'label_Labels';
        
        if (!cls::load($clsName, true)) {
            return;
        }
        
        $clsInst = cls::get($clsName);
        
        if (!$clsInst->db->tableExists($clsInst->dbTableName)) {
            return;
        }
        
        $pInst = cls::get('label_Prints');
        
        if (!$pInst->db->isFieldExists($pInst->dbTableName, str::phpToMysqlName('labelId'))) {
            return;
        }
        
        $pInst->FLD('labelId', 'key(mvc=label_Labels, select=title)', 'caption=Етикет, mandatory, silent, input=none');
        
        $pQuery = $pInst->getQuery();
        $pQuery->where('#labelId IS NOT NULL');
        $pQuery->where("#labelId != ''");
        
        while ($pRec = $pQuery->fetch()) {
            if (!$pRec->labelId) {
                continue;
            }
            
            $lRec = $clsInst->fetch($pRec->labelId);
            
            if (!$lRec) {
                continue;
            }
            
            $vArr = array('templateId', 'title', 'classId', 'objectId' => 'objId', 'params');
            
            $vArr = arr::make($vArr, true);
            
            foreach ($vArr as $fName => $lFName) {
                $pRec->{$fName} = $lRec->{$lFName};
            }
            
            if ($lRec->state == 'rejected') {
                $pRec->state = 'rejected';
                $vArr['state'] = 'state';
            }
            
            $pRec->_notModified = true;
            
            $pInst->save($pRec, $vArr);
        }
    }
    
    
    /**
     * Миграция за преместване на полетата на броячите
     */
    public function counterItemsLabels()
    {
        $clsName = 'label_Labels';
        
        if (!cls::load($clsName, true)) {
            return;
        }
        
        $clsInst = cls::get($clsName);
        
        if (!$clsInst->db->tableExists($clsInst->dbTableName)) {
            return;
        }
        
        $cItemsInst = cls::get('label_CounterItems');
        
        if (!$cItemsInst->db->isFieldExists($cItemsInst->dbTableName, str::phpToMysqlName('labelId'))) {
            return;
        }
        
        $pInst = cls::get('label_Prints');
        
        if (!$pInst->db->isFieldExists($pInst->dbTableName, str::phpToMysqlName('labelId'))) {
            return;
        }
        
        $pInst->FLD('labelId', 'key(mvc=label_Labels, select=title)', 'caption=Етикет, mandatory, silent, input=none');
        $cItemsInst->FLD('labelId', 'key(mvc=label_Labels, select=title)', 'caption=Етикет');
        
        $cQuery = $cItemsInst->getQuery();
        $cQuery->where('#labelId IS NOT NULL');
        $cQuery->where("#labelId != ''");
        $cQuery->where("#printId = ''");
        $cQuery->orWhere('#printId IS NULL');
        
        while ($cRec = $cQuery->fetch()) {
            if (!$cRec->labelId) {
                continue;
            }
            
            $pRec = $pInst->fetch(array("#labelId = '[#1#]'", $cRec->labelId));
            
            if (!$pRec) {
                continue;
            }
            
            $cRec->printId = $pRec->id;
            
            $cItemsInst->save_($cRec, 'printId');
        }
    }
    
    
    /**
     * Миграция за добавяне на медия към шаблоните
     */
    public static function removePlugin3()
    {
        $Plugins = core_Plugins::getQuery();
        $Plugins2 = clone $Plugins;
        $Plugins->delete("#class LIKE 'planning_Jobs' AND #plugin LIKE 'label_plg_Print'");
        
        $Plugins2->delete("#class LIKE 'planning_Tasks' AND #plugin LIKE 'label_plg_Print'");
    }
    
    
    /**
     * Преименува всички плейсхолдери от BARCODE към SERIAL
     */
    public function barcodeToSerial()
    {
        $tQuery = label_Templates::getQuery();
        
        while ($tRec = $tQuery->fetch()) {
            if (stripos($tRec->template, '[#BARCODE#]') === false) {
                continue;
            }
            
            if (stripos($tRec->template, '[#SERIAL#]') !== false) {
                label_Templates::logErr('Не е мигриран плейсхолдер BARCODE към SERIAL, поради дублиране', $tRec->id);
                
                continue;
            }
            
            $dRec = label_TemplateFormats::fetch(array("#templateId = '[#1#]' AND LOWER(#placeHolder) = 'barcode'", $tRec->id));
            
            if ($dRecS = label_TemplateFormats::fetch(array("#templateId = '[#1#]' AND LOWER(#placeHolder) = 'serial'", $tRec->id))) {
                label_TemplateFormats::logErr('Не е мигриран плейсхолдер BARCODE към SERIAL, поради дублиране', $dRecS->id);
                
                continue;
            }
            
            $tRec->template = str_ireplace('[#BARCODE#]', '[#SERIAL#]', $tRec->template);
            
            label_Templates::save($tRec, 'template');
            
            if ($dRec) {
                $dRec->placeHolder = 'SERIAL';
                label_TemplateFormats::save($dRec, 'placeHolder');
            }
        }
    }
    
    
    /**
     * Мигриране на източника за производсвените задачи и операции към опаковки
     */
    public static function templateSource()
    {
        $oldClsArr = array();
        
        foreach (array('planning_Jobs', 'planning_Tasks') as $clsName) {
            if (cls::load($clsName, true)) {
                $cId = $clsName::getClassId();
                
                if (!$cId) {
                    continue;
                }
                
                $oldClsArr[] = $cId;
            }
        }
        
        if (empty($oldClsArr)) {
            return ;
        }
        
        $pId = null;
        
        if (cls::load('cat_products_Packagings', true)) {
            $pId = cat_products_Packagings::getClassId();
        }
        
        if (!$pId) {
            return ;
        }
        
        $tQuery = label_Templates::getQuery();
        $tQuery->orWhereArr('classId', $oldClsArr);
        
        while ($tRec = $tQuery->fetch()) {
            $tRec->classId = $pId;
            
            label_Templates::save($tRec, 'classId');
        }
    }
    
    
    /**
     * Миграция за регенериране на ключовите думи
     */
    public static function repairSerchKeywords()
    {
        $callOn = dt::addSecs(120);
        core_CallOnTime::setCall('plg_Search', 'repairSerchKeywords', 'label_Prints', $callOn);
    }
}
