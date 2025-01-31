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
 * Колко време след приключване на ПО може да се въвежда прогрес по нея
 */
defIfNot('PLANNING_TASK_PROGRESS_ALLOWED_AFTER_CLOSURE', 60 * 60 * 24 * 5);


/**
 * Колко време след приключване на ПО може да се произведе ДРУГ артикул
 */
defIfNot('PLANNING_TASK_PRODUCTION_PROGRESS_ALLOWED_AFTER_CLOSURE', 60 * 60 * 24 * 30);


/**
 * Да се показва ли предупреждение при дублирани серийни номера в ПО
 */
defIfNot('PLANNING_WARNING_DUPLICATE_TASK_PROGRESS_SERIALS', 'yes');


/**
 * Използване на един производствен номер в различни Операции
 */
defIfNot('PLANNING_ALLOW_SERIAL_IN_DIFF_TASKS', 'yes');


/**
 * Показване на статус при разминаване на нетото в ПО->Предупреждение
 */
defIfNot('PLANNING_TASK_NET_WEIGHT_WARNING', 0.05);


/**
 * Задаване на оператори в прогреса на ПО
 */
defIfNot('PLANNING_TASK_PROGRESS_OPERATOR', 'lastAndMandatory');


/**
 * Макс бруто тегло при въвеждане на прогрес в ПО
 */
defIfNot('PLANNING_TASK_PROGRESS_MAX_BRUT_WEIGHT', '100000');


/**
 * Поле което да определя опресняване на кеширането на артикула в заданието
 */
defIfNot('PLANNING_JOB_DEFAULT_INVALIDATE_PRODUCT_CACHE_ON_CHANGE', 'yes');


/**
 * Да се показва ли колонка за продажба в листа на ПО
 */
defIfNot('PLANNING_SHOW_SALE_IN_TASK_LIST', 'no');


/**
 * До колко предходни операции да се показват в сингъла и листа
 */
defIfNot('PLANNING_SHOW_PREVIOUS_TASK_BLOCKS', '2');


/**
 * Стратегия за подреждане на операциите в заданието
 */
defIfNot('PLANNING_SORT_TASKS_IN_JOB_STRATEGY', '');


/**
 * Операция от Етап в рецепта - Влагане на предходния и вложените Етапи->Планиране
 */
defIfNot('PLANNING_INPUT_PREVIOUS_BOM_STEP', 'yes');


/**
 * Настройки на полета за получил/предал в ПВ и ПВР
 */
defIfNot('PLANNING_SHOW_SENDER_AND_RECEIVER_SETTINGS', 'no');


/**
 * Дефолтен хоризонт на показването резервните части
 */
defIfNot('PLANNING_SPARE_PARTS_HORIZON_IN_LIST', 3);


/**
 * Дефолтен хоризонт на показването резервните части
 */
defIfNot('PLANNING_SPARE_PARTS_HORIZON_IN_LIST', 3);


/**
 * Състояние на ПО след автоматично създаване от Рецепта
 */
defIfNot('PLANNING_AUTO_CREATE_TASK_STATE', 'pending');


/**
 * Подредба на колонки в листа на операциите->Падредба
 */
defIfNot('PLANNING_ORDER_TASK_PARAMS_IN_LIST', '');


/**
 * Подредба на колонки в листа на операциите->Скриване
 */
defIfNot('PLANNING_ORDER_TASK_PARAMS_HIDE_IN_LIST', '');


/**
 * Колко да е изчакването между предходни операции->В една локация
 */
defIfNot('PLANNING_TASK_OFFSET_IN_SAME_LOCATION', '0');


/**
 * Колко да е изчакването между предходни операции->В различна локация
 */
defIfNot('PLANNING_TASK_OFFSET_IN_OTHER_LOCATION', '3600');


/**
 * Производствено планиране - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2025 Experta OOD
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
        'PLANNING_TASK_WEIGHT_MODE' => array('enum(no=Изключено,yes=Включено)', 'caption=Отчитане на теглото в ПО->Режим'),
        'PLANNING_JOB_AUTO_COMPLETION_DELAY' => array('time', 'caption=Автоматично приключване на Задание без нови контиращи документи->Повече от'),
        'PLANNING_JOB_AUTO_COMPLETION_PERCENT' => array('percent(Min=0)', 'placeholder=Никога,caption=Автоматично приключване на Задание без нови контиращи документи->И Заскладено над,callOnChange=planning_Setup::setJobAutoClose'),
        'PLANNING_PRODUCTION_NOTE_PRIORITY' => array('enum(bom=Рецепта,expected=Вложено)', 'caption=Приоритет за попълване на количеството на материалите в протокол за производство->Източник'),
        'PLANNING_PRODUCTION_RATE_DEFAULT_MEASURE' => array('set(secsPer10=Секунди за 10 (мярка),secsPer100=Секунди за 100 (мярка),secsPer1000=Секунди за 1000 (мярка),minPer1=Минути за (мярка),per1Min=(Мярка) за минута,minPer10=Минути за 10 (мярка),minPer100=Минути за 100 (мярка),minPer1000=Минути за 1000 (мярка),hoursPer1=Часове за (мярка),per1Hour=(Мярка) за час,per8Hour=(Мярка) за 8 часа)', 'caption=Допълнителни разрешени производствени норми освен "Секунди за (мярка)"->Избор'),
        'PLANNING_DEFAULT_PRODUCTION_STEP_FOLDER_ID' => array('key2(mvc=doc_Folders,select=title,coverClasses=cat_Categories,allowEmpty)', 'caption=Дефолтна папка за създаване на нов производствен етап от рецепта->Избор'),
        'PLANNING_ASSET_HORIZON' => array('time', 'caption=Планиране на производствени операции към оборудване->Хоризонт'),
        'PLANNING_MIN_TASK_DURATION' => array('time', 'caption=Планиране на производствени операции към оборудване->Мин. прод.'),
        'PLANNING_TASK_PROGRESS_OPERATOR' => array('enum(lastAndMandatory=Последно въведен (и задължително),lastAndOptional=Последно въведен (и опционално),emptyAndMandatory=Празно (и задължително),emptyAndOptional=Празно (и опционално),current=Текущ оператор)', 'caption=Задаване на оператори в прогреса на ПО->Оператори,customizeBy=taskWorker|ceo'),
        'PLANNING_SHOW_PREVIOUS_JOB_FIELD_IN_TASK' => array('enum(yes=Показване,no=Скриване)', 'caption=Показване на предишно задание в ПО->Избор'),
        'PLANNING_TASK_PROGRESS_ALLOWED_AFTER_CLOSURE' => array('time', 'caption=Колко време след приключване на ПО може да се въвежда прогрес по нея->Време'),
        'PLANNING_TASK_PRODUCTION_PROGRESS_ALLOWED_AFTER_CLOSURE' => array('time', 'caption=Колко време след приключване на ПО може да се произведе ДРУГ артикул->Време'),
        'PLANNING_WARNING_DUPLICATE_TASK_PROGRESS_SERIALS' => array('enum(yes=Разрешено - с предупреждение,no=Разрешено - без предупреждение)', 'caption=Повторение на един производствен номер в рамките на една Операция->Избор'),
        'PLANNING_ALLOW_SERIAL_IN_DIFF_TASKS' => array('enum(yes=Разрешено,no=Забранено)', 'caption=Използване на един производствен номер в различни Операции->Избор'),
        'PLANNING_TASK_NET_WEIGHT_WARNING' => array('percent(Min=0,Max=1)', 'caption=Показване на статус при разминаване на нетото в ПО->Предупреждение'),
        'PLANNING_TASK_PROGRESS_MAX_BRUT_WEIGHT' => array('int(Min=0)', 'caption=Максимално допустимо бруто тегло в прогреса на ПО->Максимално до,unit=кг'),
        'PLANNING_SHOW_SALE_IN_TASK_LIST' => array('enum(yes=Да,no=Не)', 'caption=Показване на продажбата в списъка на ПО->Избор'),
        'PLANNING_JOB_DEFAULT_INVALIDATE_PRODUCT_CACHE_ON_CHANGE' => array('enum(yes=Да,no=Не)', 'caption=Обновяване на параметрите на артикула в заданието при Пускане/Събуждане->По подразбиране'),
        'PLANNING_SHOW_PREVIOUS_TASK_BLOCKS' => array('int(min=0)', 'caption=За колко от предходните Операции да се визуализира готовността->Брой'),
        'PLANNING_SORT_TASKS_IN_JOB_STRATEGY' => array('class(interface=planning_OrderTasksInJobStrategyIntf,select=title)', 'caption=Подреждане на операциите в заданието->Стратегия'),
        'PLANNING_INPUT_PREVIOUS_BOM_STEP' => array('enum(yes=Влагат се,no=Не се влагат)', 'caption=Операция от Етап в рецепта - Влагане на предходния и вложените Етапи->Планиране'),
        'PLANNING_SHOW_SENDER_AND_RECEIVER_SETTINGS' => array('enum(no=Скриване,yes=Показване,yesDefault=Показване с дефолт)', 'caption=Полета за получил/предал в Протоколите за влагане/връщане->Избор'),
        'PLANNING_SPARE_PARTS_HORIZON_IN_LIST' => array('int(Min=0)', 'caption=Планирани наличности на резервните части->Месеци напред'),
        'PLANNING_AUTO_CREATE_TASK_STATE' => array('enum(pending=Заявка,draft=Чернова)', 'caption=Състояние на ПО след автоматично създаване от Рецепта->Състояние'),
        'PLANNING_ORDER_TASK_PARAMS_IN_LIST' => array('table(columns=paramId,captions=Параметър)', 'caption=Подредба на колонки в листа на операциите->Показване в ред,customizeBy=taskSee|ceo'),
        'PLANNING_ORDER_TASK_PARAMS_HIDE_IN_LIST' => array('table(columns=paramId,captions=Параметър)', 'caption=Подредба на колонки в листа на операциите->Скриване,customizeBy=taskSee|ceo'),
        'PLANNING_TASK_OFFSET_IN_SAME_LOCATION' => array('time', 'caption=Колко да е изчакването между предходни операции->В една локация'),
        'PLANNING_TASK_OFFSET_IN_OTHER_LOCATION' => array('time', 'caption=Колко да е изчакването между предходни операции->В различна локация'),
    );


    /**
     * Менижиране на формата формата за настройките
     *
     * @param core_Form $configForm
     * @return void
     */
    public function manageConfigDescriptionForm(&$configForm)
    {
        $options = array();
        $params = cat_Params::getTaskParamOptions();
        foreach ($params as $paramId => $name){
            $options["param_{$paramId}"] = $name;
        }

        $additionalFields = arr::make('dependantProgress=Пред.,prevExpectedTimeEnd=Пред. край,expectedTimeStart=Тек. начало,title=Текуща,progress=Прогрес,expectedTimeEnd=Тек. край,nextExpectedTimeStart=След. начало,nextId=Следв.,dueDate=Падеж,originId=Задание,jobQuantity=Тираж (Зад.),plannedQuantity=Тираж (ПО),notes=Забележка,folderId=Папка', true);
        foreach ($additionalFields as $fld => $caption){
            $options[$fld] =  'Списък->' . tr($caption);
        }

        $configForm->setFieldTypeParams('PLANNING_ORDER_TASK_PARAMS_IN_LIST', array('paramId_opt' => array('' => '') + $options + array('_rest_' => 'Списък->Всички')));
        $configForm->setFieldTypeParams('PLANNING_ORDER_TASK_PARAMS_HIDE_IN_LIST', array('paramId_opt' => array('' => '') + $options));
    }


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

        array(
            'systemId' => 'Recalc Task Durations',
            'description' => 'Преизчисляване на продължителноста на производствените операции',
            'controller' => 'planning_TaskConstraints',
            'action' => 'RecalcTaskDuration',
            'period' => 30,
            'timeLimit' => 60,
        ),

        array(
            'systemId' => 'Recalc Task Constraints',
            'description' => 'Преизчисляване на ограниченията на производствените операции',
            'controller' => 'planning_TaskConstraints',
            'action' => 'RecalcTaskConstraints',
            'period' => 5,
            'timeLimit' => 60,
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
        'planning_WorkCards',
        'planning_Points',
        'planning_GenericMapper',
        'planning_StepConditions',
        'planning_GenericProductPerDocuments',
        'planning_WorkInProgress',
        'planning_AssetGroupIssueTemplates',
        'planning_AssetSparePartsDetail',
        'planning_TaskConstraints',
        'migrate::repairSearchKeywords2524',
        'migrate::renameResourceFields2624v2',
        'migrate::removeCachedAssetModified4124v2',
        'migrate::repairSearchKeywords2442',
        'migrate::calcTaskLastProgress2504v2',
        'migrate::syncOperatorsWithGroups2504v2',
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
        array(3.21, 'Производство', 'Планиране', 'planning_Centers', 'dispatch', 'ceo,planning,production,jobSee,planningAll'),
    );


    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'planning_reports_ArticlesWithAssignedTasks,planning_interface_ImportTaskProducts,planning_interface_ImportTaskSerial,
                          planning_interface_ImportFromLastBom,planning_interface_StepProductDriver,planning_reports_Workflows,
                          planning_reports_ArticlesProduced,planning_reports_ConsumedItemsByJob,planning_reports_MaterialPlanning,
                          planning_interface_ImportFromPreviousTasks,planning_interface_TopologicalOrderTasksInJob,planning_interface_ImportStep,
                          planning_interface_ImportFromConsignmentProtocol,planning_reports_WasteAndScrapByJobs, planning_reports_WasteAndScrapByTasks';


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        // Кофа за снимки
        $html .= fileman_Buckets::createBucket('planningImages', 'Илюстрации в производство', 'jpg,jpeg,png,bmp,gif,image/*,webp', '10MB', 'every_one', 'powerUser');
        $html .= fileman_Buckets::createBucket('workCards', 'Работни карти', 'pdf,jpg,jpeg,png,webp', '200MB', 'powerUser', 'powerUser');

        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Екстендър към драйвера за производствени етапи', 'embed_plg_Extender', 'planning_interface_StepProductDriver', 'private');

        // Закачане на плъгина за прехвърляне на собственотст на системни папки към core_Users
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Синхронизиране на незавършеното производство', 'planning_plg_BalanceSync', 'acc_Balances', 'private');

        $config = core_Packs::getConfig('planning');
        if (strlen($config->PLANNING_SORT_TASKS_IN_JOB_STRATEGY) === 0) {
            core_Classes::add('planning_interface_TopologicalOrderTasksInJob');
            $classId = core_Classes::getId('planning_interface_TopologicalOrderTasksInJob');
            core_Packs::setConfig('planning', array('PLANNING_SORT_TASKS_IN_JOB_STRATEGY' => $classId));
        }

        return $html;
    }


    /**
     * След промяна на процента за приключване на задание
     */
    public static function setJobAutoClose($Type, $oldValue, $newValue)
    {
        $exRec = core_Cron::getRecForSystemId('Close Old Jobs');
        if (empty($newValue)) {
            if (is_object($exRec)) {
                $exRec->state = 'stopped';
                core_Cron::save($exRec, 'state');
            }
        } elseif (empty($oldValue)) {
            $exRec = core_Cron::getRecForSystemId('Close Old Jobs');
            if ($exRec->state == 'stopped') {
                $exRec->state = 'free';
                core_Cron::save($exRec, 'state');
            } else {
                $rec = new stdClass();
                $rec->systemId = 'Close Old Jobs';
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
     * Миграция за регенериране на ключовите думи
     */
    public static function repairSearchKeywords2524()
    {
        $callOn = dt::addSecs(1200);
        core_CallOnTime::setCall('plg_Search', 'repairSearchKeywords', 'planning_ConsumptionNotes', $callOn);
        core_CallOnTime::setCall('plg_Search', 'repairSearchKeywords', 'planning_ReturnNotes', $callOn);
    }


    /**
     * Миграция на ресурсите
     */
    public function renameResourceFields2624v2()
    {
        $Resources = cls::get('planning_AssetResources');
        $Resources->setupMvc();
        $query = $Resources->getQuery();
        $protocolIdField = str::phpToMysqlName('protocolId');
        if ($Resources->db->isFieldExists($Resources->dbTableName, $protocolIdField)) {
            $query->FNC('protocolId', 'int');
        }

        $save = array();
        while($rec = $query->fetch()){
            if(is_numeric($rec->protocols)){
                $rec->protocols = keylist::addKey('', $rec->protocols);
                $save[] = $rec;
            } elseif(!empty($rec->protocolId) && empty($rec->protocols)){
                $rec->protocols = keylist::addKey('', $rec->protocolId);
                $save[] = $rec;
            }
        }

        if(countR($save)){
            $Resources->saveArray($save, 'id,protocols');
        }
    }


    /**
     * Изтриване на кеш да се преизчислят продължителноста на времената
     */
    public function removeCachedAssetModified4124v2()
    {
        $query = planning_AssetResources::getQuery();
        $query->show('id');
        while ($rec = $query->fetch()){
            core_Permanent::remove("assetTaskOrder|{$rec->id}");
        }
    }


    /**
     * Миграция за регенериране на ключовите думи
     */
    public static function repairSearchKeywords2442()
    {
        $callOn = dt::addSecs(120);
        core_CallOnTime::setCall('plg_Search', 'repairSearchKeywords', 'planning_Steps', $callOn);
    }


    /**
     * Миграция на последните прогреси
     */
    public function calcTaskLastProgress2504v2()
    {
        planning_Tasks::recalcTaskLastProgress(null, 'active,wakeup,stopped', 90);
    }


    /**
     * Миграция групите на операторите в центрове на дейност
     */
    public function syncOperatorsWithGroups2504v2()
    {
        $employeesGroupId = crm_Groups::getIdFromSysId('employees');
        $groupRec = (object)array('name' => 'Център на дейност', 'sysId' => 'activityCenters', 'parentId' => $employeesGroupId);
        crm_Groups::forceGroup($groupRec);

        $centerGroups = array();
        $query = planning_Centers::getQuery();
        $query->where("#state != 'rejected'");
        while($rec = $query->fetch()){
            $centerGroups[$rec->folderId] = planning_Centers::syncCrmGroup($rec);
        }

        $personArr = array();
        $hrClassId = planning_Hr::getClassId();
        $folderQuery = planning_AssetResourceFolders::getQuery();
        $folderQuery->EXT('personId', "planning_Hr", "externalName=personId,externalKey=objectId");
        $folderQuery->where("#classId = {$hrClassId}");
        $folderQuery->in('folderId', array_keys($centerGroups));
        while ($fRec = $folderQuery->fetch()){
            $personArr[$fRec->personId][$fRec->folderId] = $fRec->folderId;
        }

        $personCount  = count($personArr);
        if(!$personCount) return;

        core_App::setTimeLimit(0.3 * $personCount, false, 200);
        $personQuery = crm_Persons::getQuery();
        $personQuery->in('id', array_keys($personArr));

        $Persons = cls::get('crm_Persons');
        while ($pRec = $personQuery->fetch()){
            $centers = $personArr[$pRec->id];
            $addGroups = array_intersect_key($centerGroups, $centers);
            $addGroups = array_combine($addGroups, $addGroups);

            $pRec->groupListInput = keylist::removeKey($pRec->groupListInput, $employeesGroupId);
            $pRec->groupListInput = keylist::merge($pRec->groupListInput, $addGroups);
            $Persons->save($pRec, 'groupListInput,groupList');
        }

        crm_Groups::updateGroupsCnt('crm_Persons', 'personsCnt');
    }
}
