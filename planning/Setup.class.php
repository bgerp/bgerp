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
 * Автоматично приключване на активни задания към затворени артикули->При Заскладено/Планирано
 */
defIfNot('PLANNING_JOB_AUTO_COMPLETION_PERCENT', '');


/**
 * Автоматично приключване на активни задания към затворени артикули->Без нови контиращи документи в нишката
 */
defIfNot('PLANNING_JOB_AUTO_COMPLETION_DELAY', dt::SECONDS_IN_MONTH);


/**
 * За колко време напред да се планират производствените операции към машината
 */
defIfNot('PLANNING_ASSET_HORIZON', 3 * dt::SECONDS_IN_MONTH);


/**
 * Приоритет при попълване на количеството в протокола за производство
 */
defIfNot('PLANNING_PRODUCTION_NOTE_PRIORITY', 'bom');


/**
 * Дефолтна производствена мярка на нормата
 */
defIfNot('PLANNING_PRODUCTION_RATE_DEFAULT_MEASURE', '');


/**
 * Дефолтна папка за създаване на нови производствени етапи
 */
defIfNot('PLANNING_DEFAULT_PRODUCTION_STEP_FOLDER_ID', '');


/**
 * Минимално време за продължителност на ПО
 */
defIfNot('PLANNING_MIN_TASK_DURATION', 5*60);


/**
 * Показване на предишно задание в ПО
 */
defIfNot('PLANNING_SHOW_PREVIOUS_JOB_FIELD_IN_TASK', 'yes');


/**
 * Задължителен избор за оператор в ПО
 */
defIfNot('PLANNING_TASK_PROGRESS_MANDATORY_OPERATOR', 'yes');


/**
 * Колко време след приключване на ПО може да се въвежда прогрес по нея
 */
defIfNot('PLANNING_TASK_PROGRESS_ALLOWED_AFTER_CLOSURE', 60 * 60 * 24 * 5);


/**
 * Да се показва ли предупреждение при дублирани серийни номера в ПО
 */
defIfNot('PLANNING_WARNING_DUPLICATE_TASK_PROGRESS_SERIALS', 'yes');


/**
 * Показване на статус при разминаване на нетото в ПО->Предупреждение
 */
defIfNot('PLANNING_TASK_NET_WEIGHT_WARNING', 0.05);


/**
 * Производствено планиране - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2022 Experta OOD
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
        'PLANNING_TASK_WEIGHT_MODE' => array('enum(no=Изключено,yes=Включено,mandatory=Задължително)', 'caption=Отчитане на теглото в ПО->Режим'),

        'PLANNING_JOB_AUTO_COMPLETION_DELAY' => array('time', 'caption=Автоматично приключване на Задание без нови контиращи документи->Повече от'),
        'PLANNING_JOB_AUTO_COMPLETION_PERCENT' => array('percent(Min=0)', 'placeholder=Никога,caption=Автоматично приключване на Задание без нови контиращи документи->И Заскладено над,callOnChange=planning_Setup::setJobAutoClose'),
        'PLANNING_PRODUCTION_NOTE_PRIORITY' => array('enum(bom=Рецепта,expected=Вложено)', 'caption=Приоритет за попълване на количеството на материалите в протокол за производство->Източник'),
        'PLANNING_PRODUCTION_RATE_DEFAULT_MEASURE' => array('set(minPer1=Минути за (мярка),per1Min=(Мярка) за минута,minPer10=Минути за 10 (мярка),minPer100=Минути за 100 (мярка),minPer1000=Минути за 1000 (мярка),per1Hour=(Мярка) за час,per8Hour=(Мярка) за 8 часа)', 'caption=Допълнителни разрешени производствени норми освен "Секунди за (мярка)"->Избор'),
        'PLANNING_DEFAULT_PRODUCTION_STEP_FOLDER_ID' => array('key2(mvc=doc_Folders,select=title,coverClasses=cat_Categories,allowEmpty)', 'caption=Дефолтна папка за създаване на нов производствен етап от рецепта->Избор'),
        'PLANNING_ASSET_HORIZON' => array('time', 'caption=Планиране на производствени операции към оборудване->Хоризонт'),
        'PLANNING_MIN_TASK_DURATION' => array('time', 'caption=Планиране на производствени операции към оборудване->Мин. прод.'),
        'PLANNING_TASK_PROGRESS_MANDATORY_OPERATOR' => array('enum(yes=Задължително,no=Опционално)', 'caption=Въвеждане на прогрес в ПО->Оператор(и)'),
        'PLANNING_SHOW_PREVIOUS_JOB_FIELD_IN_TASK' => array('enum(yes=Показване,no=Скриване)', 'caption=Показване на предишно задание в ПО->Избор'),
        'PLANNING_TASK_PROGRESS_ALLOWED_AFTER_CLOSURE' => array('time', 'caption=Колко време след приключване на ПО може да се въвежда прогрес по нея->Време'),
        'PLANNING_WARNING_DUPLICATE_TASK_PROGRESS_SERIALS' => array('enum(yes=Показване,no=Скриване)', 'caption=Показване на предупреждение при дублиране на произв. номера в ПО->Избор'),
        'PLANNING_TASK_NET_WEIGHT_WARNING' => array('percent(Min=0,Max=1)', 'caption=Показване на статус при разминаване на нетото в ПО->Предупреждение'),
    );


    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Recalc Task Start Times',
            'description' => 'Преизчисляване на началото на производствени операции',
            'controller' => 'planning_AssetResources',
            'action' => 'RecalcTaskTimes',
            'period' => 2,
            'timeLimit' => 30,
        ),
    );


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'planning_Steps',
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
        'planning_WorkCards',
        'planning_Points',
        'planning_GenericMapper',
        'planning_StepConditions',
        'migrate::updateLabelType',
        'migrate::deletePoints',
        'migrate::changeCentreFieldToKeylistInWorkflows',
        'migrate::removeOldRoles',
        'migrate::updateLastChangedOnState',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('jobSee'),
        array('job', 'jobSee'),
        array('taskSee'),
        array('taskWorker', 'taskSee'),
        array('taskPostProduction', 'taskWorker'),
        array('task', 'taskPostProduction'),
        array('consumption', 'jobSee, taskSee'),
        array('production', 'jobSee, taskSee'),
        array('planning'),
        array('planningMaster', 'planning'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.21, 'Производство', 'Планиране', 'planning_Centers', 'dispatch', 'ceo,planning,production,jobSee'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'planning_reports_PlanningImpl,planning_reports_PurchaseImpl, planning_reports_MaterialsImpl,
                          planning_reports_ArticlesWithAssignedTasks,planning_interface_ImportTaskProducts,planning_interface_ImportTaskSerial,
                          planning_interface_ImportFromLastBom,planning_interface_StepProductDriver,planning_reports_Workflows,
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
        $html .= $Plugins->installPlugin('Екстендър към драйвера за производствени етапи', 'embed_plg_Extender', 'planning_interface_StepProductDriver', 'private');

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
     * Мигриране на етикетирането
     */
    function updateLabelType()
    {
        $Tasks = cls::get('planning_Tasks');
        $Tasks->setupMvc();

        $labelTypeColName = str::phpToMysqlName('labelType');
        $query = "UPDATE {$Tasks->dbTableName} SET {$labelTypeColName} = 'both' WHERE {$labelTypeColName} = 'print'";
        $Tasks->db->query($query);

        $Steps = cls::get('planning_Steps');
        $Steps->setupMvc();

        $query = "UPDATE {$Steps->dbTableName} SET {$labelTypeColName} = 'both' WHERE {$labelTypeColName} = 'print'";
        $Steps->db->query($query);
    }

    /**
     * Миграция за поправка на centre полето от key на keylist
     */
    function changeCentreFieldToKeylistInWorkflows()
    {
        $frameCls = cls::get('frame2_Reports');

        $query = $frameCls::getQuery();

        $repClass = planning_reports_Workflows::getClassId();

        $query->where("#driverClass = $repClass");

        while ($rec = $query->fetch()) {
            if (is_integer($rec->centre)) {
                $rec->centre = keylist::fromArray(array($rec->centre => $rec->centre));
                $frameCls->save_($rec, $frameCls->centre);
            }
        }
    }


    /**
     * Изтриване на старите поризводствени точки
     */
    function deletePoints()
    {
        planning_Points::truncate();
    }


    /**
     * Премахва стари роли
     */
    function removeOldRoles()
    {
        $remRoleId = core_Roles::fetchByName('taskPlanning');
        if(!$remRoleId) return;

        core_Roles::removeRoles(array($remRoleId));
        core_Users::rebuildRoles();
    }


    /**
     * Задаване на стойности на полетата за последна промяна на състоянията
     */
    function updateLastChangedOnState()
    {
        foreach (array('planning_Jobs', 'planning_Tasks') as $class){
            $Class = cls::get($class);
            $Class->setupMvc();
            if($Class->count()){
                $tableName = $Class->dbTableName;
                $lastChangeStateOnColName = str::phpToMysqlName('lastChangeStateOn');
                $modifiedOnColName = str::phpToMysqlName('modifiedOn');

                $lastChangeStateByColName = str::phpToMysqlName('lastChangeStateBy');
                $modifiedByColName = str::phpToMysqlName('modifiedBy');

                $query = "UPDATE {$tableName} SET {$lastChangeStateOnColName} = {$modifiedOnColName}, {$lastChangeStateByColName} = {$modifiedByColName}";
                $Class->db->query($query);
            }
        }
    }
}
