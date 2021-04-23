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
 * Допустим толеранс за втората мярка в протокола за производство
 */
defIfNot('PLANNING_PNOTE_SECOND_MEASURE_TOLERANCE_WARNING', 0.1);


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
 * При произвеждане на артикул, да се изравнява ли му производната себестойност с очакваната
 */
defIfNot('PLANNING_PRODUCTION_PRODUCT_EQUALIZING_PRIME_COST', 'yes');


/**
 * При произвеждане на артикул, да се изравнява ли му производната себестойност с очакваната
 */
defIfNot('PLANNING_PRODUCTION_PRODUCT_EQUALIZING_PRIME_COST', 'yes');


/**
 * Автоматично приключване на задание, изпълнени над
 */
defIfNot('PLANNING_JOB_AUTO_COMPLETION_PERCENT', '');


/**
 * Автоматично приключване на задание, да не са модифицирани от
 */
defIfNot('PLANNING_JOB_AUTO_COMPLETION_DELAY', '21600');


/**
 * Производствено планиране - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2021 Experta OOD
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
        'PLANNING_PNOTE_SECOND_MEASURE_TOLERANCE_WARNING' => array('percent(Min=0,Max=1)', 'caption=Толеранс за разминаване между очакваното съответствие в протоколите за производство->Предупреждение'),
        'PLANNING_TASK_WEIGHT_TOLERANCE_WARNING' => array('percent(Min=0,Max=1)', 'caption=Отчитане на теглото в ПО->Предупреждение'),
        'PLANNING_TASK_WEIGHT_MODE' => array('enum(no=Изключено,yes=Включено,mandatory=Задължително)', 'caption=Отчитане на теглото в ПО->Режим'),

        'PLANNING_JOB_AUTO_COMPLETION_PERCENT' => array('percent(Min=0)', 'placeholder=Никога,caption=Автоматично приключване на заданието->Изпълнени над,callOnChange=planning_Setup::setJobAutoClose'),
        'PLANNING_JOB_AUTO_COMPLETION_DELAY' => array('time', 'caption=Автоматично приключване на заданието->Без модификации от'),
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
        'planning_GenericMapper',
        'migrate::updateSecondMeasure',
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
                          planning_reports_ArticlesProduced,planning_reports_ConsumedItemsByJob,planning_reports_MaterialPlanning';
    
    
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
     * След промяна на процента за приключване на задание
     */
    public static function setJobAutoClose($Type, $oldValue, $newValue)
    {
        $exRec = core_Cron::getRecForSystemId('Close Old Jobs');
        if(empty($newValue)){
            if(is_object($exRec)){
                $exRec->state = 'stopped';
                core_Cron::save($exRec, 'state');
            }
        } elseif(empty($oldValue)) {
            $exRec = core_Cron::getRecForSystemId('Close Old Jobs');
            if($exRec->state == 'stopped'){
                $exRec->state = 'free';
                core_Cron::save($exRec, 'state');
            } else {
                $rec = new stdClass();
                $rec->systemId =  'Close Old Jobs';
                $rec->description = 'Затваряне на стари задания';
                $rec->controller = 'planning_Jobs';
                $rec->action = 'CloseOldJobs';
                $rec->period = 720;
                $rec->offset = 60;
                $rec->delay = 0;
                $rec->timeLimit = 120;

                core_Cron::addOnce($rec);
            }
        }
    }


    /**
     * Миграция на втората мярка на заданията
     */
    public function updateSecondMeasure()
    {
        $Jobs = cls::get('planning_Jobs');
        if(!$Jobs->count()) return;

        $cubMeasureId = cat_UoM::fetchBySinonim('cub.m')->id;
        $litreId = cat_UoM::fetchBySinonim('l')->id;

        // Обновяване на заданията, които имат втора мярка
        $updateNoSecondMeasure = $updateLitre = array();
        $query = $Jobs->getQuery();
        $query->where("#state != 'closed' AND #state != 'rejected' AND (#allowSecondMeasure = '' OR #allowSecondMeasure IS NULL)");

        // За всяко
        while($rec = $query->fetch()){

            // Ако няма втора мярка, значи ще е без
            if(empty($rec->secondMeasureId)){
                $rec->allowSecondMeasure = 'no';
                $updateNoSecondMeasure[$rec->id] = $rec;
            } else {
                $rec->allowSecondMeasure = 'yes';

                // Ако втората мярка е кубичен метър подменя се с литър
                if($rec->secondMeasureId == $cubMeasureId){
                    $rec->secondMeasureId = $litreId;
                    if(!empty($rec->secondMeasureQuantity)){
                        $rec->secondMeasureQuantity = cat_UoM::convertValue($rec->secondMeasureQuantity, $cubMeasureId, $litreId);
                    }
                    $updateLitre[$rec->id] = $rec;
                } else {
                    $updateNoSecondMeasure[$rec->id] = $rec;
                }
            }
        }

        if(countR($updateNoSecondMeasure)){
            $Jobs->saveArray($updateNoSecondMeasure, 'id,allowSecondMeasure');
        }

        if(countR($updateLitre)){
            $Jobs->saveArray($updateLitre, 'id,allowSecondMeasure,secondMeasureId,secondMeasureQuantity');
        }
    }
}
