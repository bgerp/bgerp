<?php


/**
 *  Стартов сериен номер при производствените операции
 */
defIfNot('PLANNING_TASK_SERIAL_COUNTER', 1000);


/**
 * Широчина на превюто на артикула в етикета
 */
defIfNot('PLANNING_TASK_LABEL_PREVIEW_WIDTH', 90);


/**
 * Допустим толеранс на тегллото при ПО
 */
defIfNot('PLANNING_TASK_WEIGHT_TOLERANCE_WARNING', 0.05);


/**
 * Отчитане на теглото в ПО->Режим
 */
defIfNot('PLANNING_TASK_WEIGHT_MODE', 'yes');


/**
 * Височина на превюто на артикула в етикета
 */
defIfNot('PLANNING_TASK_LABEL_PREVIEW_HEIGHT', 170);


/**
 * Детайлно влагане по подразбиране
 */
defIfNot('PLANNING_CONSUMPTION_USE_AS_RESOURCE', 'yes');


/**
 * Може ли да се оттеглят старите протоколи за производство, ако има нови
 */
defIfNot('PLANNING_PRODUCTION_NOTE_REJECTION', 'no');


/**
 * Име за показване на неопределения център на дейност
 */
defIfNot('PLANNING_UNDEFINED_CENTER_DISPLAY_NAME', 'Неопределен');


/**
 * Производствено планиране - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'cat=0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'planning_Wrapper';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'planning_DirectProductionNote';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Производствено планиране';
    
    
    /**
     * Описание на конфигурационните константи за този модул
     */
    public $configDescription = array(
        'PLANNING_TASK_SERIAL_COUNTER' => array('int', 'caption=Производствени операции->Стартов сериен номер'),
        'PLANNING_TASK_LABEL_PREVIEW_WIDTH' => array('int', 'caption=Превю на артикула в етикета->Широчина,unit=px'),
        'PLANNING_TASK_LABEL_PREVIEW_HEIGHT' => array('int', 'caption=Превю на артикула в етикета->Височина,unit=px'),
        'PLANNING_CONSUMPTION_USE_AS_RESOURCE' => array('enum(yes=Да,no=Не)', 'caption=Детайлно влагане по подразбиране->Избор'),
        'PLANNING_PRODUCTION_NOTE_REJECTION' => array('enum(no=Забранено,yes=Позволено)', 'caption=Оттегляне на стари протоколи за производство ако има нови->Избор'),
        'PLANNING_UNDEFINED_CENTER_DISPLAY_NAME' => array('varchar', 'caption=Неопределен център на дейност->Име'),
        'PLANNING_TASK_WEIGHT_TOLERANCE_WARNING' => array('percent', 'caption=Отчитане на теглото в ПО->Предупреждение'),
        'PLANNING_TASK_WEIGHT_MODE' => array('enum(no=Изключено,yes=Включено,mandatory=Задължително)', 'caption=Отчитане на теглото в ПО->Режим'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'planning_Jobs',
        'planning_ConsumptionNotes',
        'planning_ConsumptionNoteDetails',
        'planning_DirectProductionNote',
        'planning_DirectProductNoteDetails',
        'planning_ReturnNotes',
        'planning_ReturnNoteDetails',
        'planning_ObjectResources',
        'planning_Tasks',
        'planning_AssetResources',
        'planning_AssetResourceFolders',
        'planning_ProductionTaskDetails',
        'planning_ProductionTaskProducts',
        'planning_AssetGroups',
        'planning_AssetResourcesNorms',
        'planning_Centers',
        'planning_Hr',
        'planning_FoldersWithResources',
        'planning_Stages',
        'planning_WorkCards',
        'planning_Points',
        'migrate::assetResourceFields',
        'migrate::updateTasks',
        'migrate::updateTasksPart2'
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('production'),
        array('taskWorker'),
        array('taskPlanning', 'taskWorker'),
        array('planning', 'taskPlanning'),
        array('planningMaster', 'planning'),
        array('job')
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.21, 'Производство', 'Планиране', 'planning_DirectProductionNote', 'list', 'ceo,planning,store,production'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'planning_reports_PlanningImpl,planning_reports_PurchaseImpl, planning_reports_MaterialsImpl,
                          planning_reports_ArticlesWithAssignedTasks,planning_interface_ImportTaskProducts,planning_interface_ImportTaskSerial,
                          planning_interface_ImportFromLastBom,planning_interface_StageDriver,planning_reports_Workflows,planning_Terminal,
                          planning_reports_ArticlesProduced';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Кофа за снимки
        $html .= fileman_Buckets::createBucket('planningImages', 'Илюстрации в производство', 'jpg,jpeg,png,bmp,gif,image/*', '10MB', 'every_one', 'powerUser');
        
        $html .= fileman_Buckets::createBucket('workCards', 'Работни карти', 'pdf,jpg,jpeg,png', '200MB', 'powerUser', 'powerUser');
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Екстендър към драйвера за производствени етапи', 'embed_plg_Extender', 'planning_interface_StageDriver', 'private');
        
        return $html;
    }
    
    
    /**
     * Мигация за поправка на key полетата към keylist в planning_AssetResources
     */
    public static function assetResourceFields()
    {
        $inst = cls::get('planning_AssetResources');
        $query = $inst->getQuery();
        while ($rec = $query->fetch()) {
            if (!$rec->systemFolderId) {
                $rec->systemFolderId = null;
            }
            
            if (!$rec->assetFolderId) {
                $rec->assetFolderId = null;
            }
            
            // Взамем от папките
            $fQuery = planning_AssetResourceFolders::getQuery();
            $fQuery->where(array("#classId = '[#1#]' AND #objectId = '[#2#]'", $inst->getClassId(), $rec->id));
            $defOptArr = array();
            while ($fRec = $fQuery->fetch()) {
                if (!$fRec->folderId) {
                    continue ;
                }
                
                $cover = doc_Folders::getCover($fRec->folderId);
                
                $systemFolderName = 'assetFolderId';
                
                if ($cover->className == 'support_Systems') {
                    $systemFolderName = 'systemFolderId';
                }
                
                $defOptArr[$systemFolderName]['folders'][$fRec->folderId] = $fRec->folderId;
                if ($fRec->users) {
                    $defOptArr[$systemFolderName]['users'] = type_Keylist::merge($defOptArr[$systemFolderName]['users'], $fRec->users);
                }
            }
            
            if ($defOptArr['systemFolderId']['folders']) {
                $rec->systemFolderId = type_Keylist::fromArray($defOptArr['systemFolderId']['folders']);
                $rec->systemUsers = $defOptArr['systemFolderId']['users'];
            }
            
            if ($defOptArr['assetFolderId']['folders']) {
                $rec->assetFolderId = type_Keylist::fromArray($defOptArr['assetFolderId']['folders']);
                $rec->assetUsers = $defOptArr['assetFolderId']['users'];
            }
            
            $inst->save($rec, 'systemFolderId, systemUsers, assetFolderId, assetUsers');
        }
    }
    
    
    /**
     * Обновява новите полета на ПО
     */
    public static function updateTasks()
    {
        $Tasks = cls::get('planning_Tasks');
        $Tasks->setupMvc();
        
        $TaskDetails = cls::get('planning_ProductionTaskDetails');
        $TaskDetails->setupMvc();
        
        if (!countR($Tasks)) {
            
            return;
        }
        
        $updateArr = array();
        $query = $Tasks->getQuery();
        $query->where('#indPackagingId IS NULL');
        $query->show('packagingId');
        while ($rec = $query->fetch()) {
            $rec->indPackagingId = $rec->packagingId;
            $updateArr[$rec->id] = $rec;
        }
        
        if (countR($updateArr)) {
            $Tasks->saveArray($updateArr, 'id,indPackagingId');
        }
    }
    
    
    /**
     * Обновява новите полета на ПО
     */
    public static function updateTasksPart2()
    {
        $Tasks = cls::get('planning_Tasks');
        $Tasks->setupMvc();
        
        $TaskDetails = cls::get('planning_ProductionTaskDetails');
        $TaskDetails->setupMvc();
        
        if (!countR($Tasks)) {
            
            return;
        }
        
        $updateArr = array();
        $query = $Tasks->getQuery();
        $query->where('#measureId IS NULL');
        $query->show('measureId,quantityInPack,productId');
        while ($rec = $query->fetch()) {
            $measureId = cat_Products::fetchField($rec->productId, 'measureId');
            $rec->measureId = $measureId;
            $rec->quantityInPack = 1;
            
            $updateArr[] = $rec;
        }
        
        if (countR($updateArr)) {
            $Tasks->saveArray($updateArr, 'id,measureId,quantityInPack');
        }
    }
    
    
    /**
     * След началното установяване на този мениджър
     */
    public function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);
        
        if (core_Packs::isInstalled('label') && core_Packs::isInstalled('escpos')) {
            core_Classes::add('escpos_printer_TD2120N');
            
            core_Users::forceSystemUser();
            if (label_Templates::addFromFile('Етикет за прогрес на производствена операция', 'planning/tpl/DefaultTaskProgressLabel.shtml', 'defaultEscposTaskRec', array('100', '72'), 'bg', planning_ProductionTaskDetails::getClassId(), escpos_printer_TD2120N::getClassId())) {
                $res = "<li class='green'>Обновен шаблон за етикети на прогреса на производствената операция";
            } else {
                $res = '<li>Пропуснато обновяване на шаблон за прогреса на производствената операция</li>';
            }
            core_Users::cancelSystemUser();
        }
        
        return $res;
    }
}
