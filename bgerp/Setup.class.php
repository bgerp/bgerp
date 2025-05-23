<?php


/**
 * FileHandler на логото на фирмата на английски
 */
defIfNot('BGERP_COMPANY_LOGO_EN', '');


/**
 * FileHandler на логото на фирмата на български
 */
defIfNot('BGERP_COMPANY_LOGO', '');


/**
 * FileHandler на логото на фирмата на английски
 * Генерирано от svg файл
 */
defIfNot('BGERP_COMPANY_LOGO_SVG_EN', '');


/**
 * FileHandler на логото на фирмата на български
 * Генерирано от svg файл
 */
defIfNot('BGERP_COMPANY_LOGO_SVG', '');


/**
 * След колко време, ако не работи крона да бие нотификация
 */
defIfNot('BGERP_NON_WORKING_CRON_TIME', 3600);


/**
 * Звуков сигнал при нотификация
 */
defIfNot('BGERP_SOUND_ON_NOTIFICATION', 'scanner');


/**
 * Колко време да се съхраняват нотификациите
 */
defIfNot('BGERP_NOTIFICATION_KEEP_DAYS', 15778476);


/**
 * Колко време да се съхранява историята за отворени нишки и папки
 */
defIfNot('BGERP_RECENTLY_KEEP_DAYS', 31556952);


/**
 * Кога е началото на работния ден
 */
defIfNot('BGERP_START_OF_WORKING_DAY', '08:00');


/**
 * Колко секунди за изчака, преди да сигнализира за известия
 */
defIfNot('BGERP_NOTIFY_ALERT', 60);
defIfNot('BGERP_NOTIFY_WARNING', 1800);
defIfNot('BGERP_NOTIFY_NORMAL', 86400);


/**
 * В кой часови пояс да се блокира изпращане на сигнали за известия
 */
defIfNot('BGERP_BLOCK_ALERT', 'never');
defIfNot('BGERP_BLOCK_WARNING', 'night');
defIfNot('BGERP_BLOCK_NORMAL', 'nonworking|night');


/**
 * Вида известия, които ще получават заместниците
 */
defIfNot('BGERP_ALTERNATE_PEOPLE_NOTIFICATIONS', 'all');


/**
 * Колко стари записи да се пазят в таблицата за последно видяни документи от потребител
 */
defIfNot('BGERP_LAST_SEEN_DOC_BY_USER_CACHE_LIFETIME', 12 * dt::SECONDS_IN_MONTH);


/**
 * Да се активира ли действието при дабъл клик върху линк?
 */
defIfNot('BGERP_ENABLE_DOUBLE_CLICK_ON_LINK', 'no');


/**
 * Колко символа максимална дължина опции да се показват като радио бутони (а не като селект) до 4
 */
defIfNot('BGERP_VERTICAL_FORM_DEFAULT_MAX_RADIO_LENGTH', 42);


/**
 * Изпозлване на пълнотекстово търсене на полета, в които се записват групи
 */
defIfNot('BGERP_USE_FULLTEXT_GROUP_SEARCH', 'no');


/**
 * Клавиши за бързо избиране на бутони
 */
defIfNot(
    'BGERP_ACCESS_KEYS',
'Чернова,Draft,Запис,Save = S
Запис и Нов,Save and New,Нов,New = N
Артикул,Item = A
Създаване,Create = R
Заявка,Request = Z
Активиране,Activation,Контиране = K
Conto,Реконтиране = K
Приключване,Complete = C
Отказ,Cancel = C
Връзка,Link = L
Редакция,Edit = O
»»» = >
««« = <'
);


/**
 * class 'bgerp_Setup' - Начално установяване на 'bgerp'
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_Setup extends core_ProtoSetup
{
    /**
     * Да се инициализира ли, след промяна на конфигурацията?
     */
    const INIT_AFTER_CONFIG = false;
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'bgerp_Menu';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct;
    
    
    /**
     * Описание на модула
     */
    public $info = 'Основно меню и портал на bgERP';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'BGERP_COMPANY_LOGO' => array('fileman_FileType(bucket=pictures,focus=none)', 'caption=Фирмена бланка->На български, customizeBy=powerUser'),
        
        'BGERP_COMPANY_LOGO_EN' => array('fileman_FileType(bucket=pictures,focus=none)', 'caption=Фирмена бланка->На английски, customizeBy=powerUser'),
        
        'BGERP_NON_WORKING_CRON_TIME' => array('time(suggestions=30 мин.|1 час| 3 часа)', 'caption=След колко време да дава нотификация за неработещ cron->Време'),
        
        'BGERP_SOUND_ON_NOTIFICATION' => array('enum(none=Няма,snap=Щракване,scanner=Скенер,notification=Нотификация,beep=Beep)', 'caption=Звуков сигнал при нотификация->Звук, customizeBy=user'),
        
        'BGERP_NOTIFICATION_KEEP_DAYS' => array('time(suggestions=3 месеца|6 месеца|1 година,unit=days)', 'caption=Време за съхранение на нотификациите->Време'),
        
        'BGERP_RECENTLY_KEEP_DAYS' => array('time(suggestions=3 месеца|6 месеца|1 година,unit=days)', 'caption=Време за съхранение на историята в "Последно"->Време'),
        
        'BGERP_START_OF_WORKING_DAY' => array('enum(08:00,09:00,10:00,11:00,12:00)', 'caption=Начало на работния ден->Час'),

        'BGERP_ACCESS_KEYS' => array('text(rows=6)', 'caption=Клавиши за бързо избиране на бутони->Дефиниции, customizeBy=powerUser'),
        'BGERP_ENABLE_DOUBLE_CLICK_ON_LINK' => array('enum(no=Изключено,yes=Включено)', 'caption=Изпълняване на заложени действия при дабъл клик върху линк с икона->Избор, customizeBy=powerUser'),
        'BGERP_VERTICAL_FORM_DEFAULT_MAX_RADIO_LENGTH' => array('int(min=1)', 'caption=Максимална обща дължина на опциите за да се показват като радио бутони->Брой символи, unit=&nbsp;|(в това число 3 символа за бутона на всяка опция)|*, customizeBy=user'),

        'BGERP_NOTIFY_ALERT' => array('time(suggestions=1 min|5 min|10 min|20 min|30 min|60 min|2 hours|3 hours|6 hours|12 hours|24 hours)', 'caption=Изчакване преди сигнализация за нови известия->Критични,placeholder=Неограничено, customizeBy=powerUser'),
        
        'BGERP_NOTIFY_WARNING' => array('time(suggestions=1 min|5 min|10 min|20 min|30 min|60 min|2 hours|3 hours|6 hours|12 hours|24 hours)', 'caption=Изчакване преди сигнализация за нови известия->Спешни,placeholder=Неограничено, customizeBy=powerUser'),
        
        'BGERP_NOTIFY_NORMAL' => array('time(suggestions=1 min|5 min|10 min|20 min|30 min|60 min|2 hours|3 hours|6 hours|12 hours|24 hours)', 'caption=Изчакване преди сигнализация за нови известия->Нормални,placeholder=Неограничено, customizeBy=powerUser'),
        
        'BGERP_BLOCK_ALERT' => array('enum(working|nonworking|night=Постоянно,nonworking|night=Неработно време,night=През нощта,never=Никога)', 'caption=Блокиране на сигнализация за нови известия->Критични, customizeBy=powerUser'),
        
        'BGERP_BLOCK_WARNING' => array('enum(working|nonworking|night=Постоянно,nonworking|night=Неработно време,night=През нощта,never=Никога)', 'caption=Блокиране на сигнализация за нови известия->Спешни, customizeBy=powerUser'),
        
        'BGERP_BLOCK_NORMAL' => array('enum(working|nonworking|night=Постоянно,nonworking|night=Неработно време,night=През нощта,never=Никога)', 'caption=Блокиране на сигнализация за нови известия->Нормални, customizeBy=powerUser'),

        'BGERP_ALTERNATE_PEOPLE_NOTIFICATIONS' => array('enum(all=Всички,share=Само споделените,open=Само "Отворени теми",shareOpen=Споделени и "Отворени теми",noOpen=Без "Отворени теми",stop=Спиране)', 'caption=Известията|*&#44; |които да получават заместниците->Избор, customizeBy=powerUser'),

        'BGERP_LAST_SEEN_DOC_BY_USER_CACHE_LIFETIME' => array('time', 'caption=До колко време назад да се пазят записите в последно видяните документи от потребител->По стари от'),

        'BGERP_USE_FULLTEXT_GROUP_SEARCH' => array('enum(no=Изключено,yes=Включено)', 'caption=Използване на пълнотекстов индекс при търсене по групи на Артикули / Фирми / Лица->Избор'),
    );
    
    
    /**
     * Път до js файла
     */
    //    var $commonJS = 'js/PortalSearch.js';
    
    
    /**
     * Дали пакета е системен
     */
    public $isSystem = true;
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Hide Inaccesable',
            'description' => 'Скрива на недостъпните нотификации',
            'controller' => 'bgerp_Notifications',
            'action' => 'HideInaccesable',
            'period' => 1440,
            'offset' => 50,
            'timeLimit' => 600
        ),
        array(
            'systemId' => 'DeleteLastSeenByUserCache',
            'description' => 'Изтриване на кеша на последно видяните документи от потребител',
            'controller' => 'bgerp_LastSeenDocumentByUser',
            'action' => 'DeleteOldRecs',
            'period' => 1440,
            'offset' => 100,
        ),
    );


    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('groupingMaster'),
    );

    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'bgerp_drivers_Recently, bgerp_drivers_Notifications, bgerp_drivers_Calendar, bgerp_drivers_Tasks';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        // Предотвратяваме логването в Debug режим
        Debug::$isLogging = false;
        
        // Блокираме други процеси
        core_SystemLock::block('Prepare bgERP installation...');

        // Спираме SQL лога, ако има такъв
        core_Db::$sqlLogEnebled = false;

        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        $html = $Plugins->repair();

        $managers = array(
            'bgerp_Menu',
            'bgerp_Portal',
            'bgerp_Notifications',
            'bgerp_Recently',
            'bgerp_Bookmark',
            'bgerp_LastTouch',
            'bgerp_F',
            'bgerp_Filters',
            'bgerp_LastSeenDocumentByUser',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            core_SystemLock::block("Install {$manager}");
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        core_SystemLock::block('Starting bgERP installation...');

        // Инстанция на мениджъра на пакетите
        $Packs = cls::get('core_Packs');

        // Това първо инсталиране ли е?
        $isFirstSetup = ($Packs->count() <= 5);

        // Списък на основните модули на bgERP
        $packs = 'core,log,fileman,drdata,bglocal,editwatch,recently,thumb,doc,tags,help,acc,cond,uiext,currency,cms,ograph,
                  email,crm, cat,deals, price, blast,hr,lab,dec,sales,import2,planning,marketing,store,cash,bank,
                  tcost,purchase,accda,frame,frame2,cal,fconv,doclog,fconv,cms,blogm,trans,forum,findeals,
                  vislog,docoffice,incoming,support,survey,pos,change,sass,
                  callcenter,social,status,phpmailer,label,webkittopdf,jqcolorpicker,export,select2';

        // Ако има private проект, добавяме и инсталатора на едноименния му модул
        if (defined('EF_PRIVATE_PATH')) {
            $packs .= ',' . strtolower(basename(EF_PRIVATE_PATH));
        }

        // Добавяме допълнителните пакети, само при първоначален Setup
        if (($isFirstSetup) || (!$Packs->isInstalled('avatar') && !$Packs->isInstalled('imagics') && !$Packs->isInstalled('toast'))) {
            $packs .= ',avatar,keyboard,google,gdocs,jqdatepick,imagics,fastscroll,context,autosize,oembed,hclean,toast,minify,rtac,hljs,tnef,tinymce,barcode';
        } else {

            try {
                $Roles = cls::get('core_Roles');
                $rQuery = $Roles->getQuery();
                $rQuery->where("#createdBy = -1");
                while ($rRec = $rQuery->fetch()) {
                    if (!$rRec->inheritInput && !$rRec->inherit) {
                        continue;
                    }

                    $rRec->inheritInput = '';
                    $rRec->inherit = '';

                    $Roles->save_($rRec, 'inheritInput, inherit');
                }
            } catch (Exception $t) {

            }
            catch (Throwable $t) {

            }

            $packs = arr::make($packs, true);
            $pQuery = $Packs->getQuery();
            $pQuery->where("#state = 'active'");
            
            while ($pRec = $pQuery->fetch()) {
                if (!$packs[$pRec->name]) {
                    $packs[$pRec->name] = $pRec->name;
                }
            }
        }
        
        if (Request::get('SHUFFLE')) {
            
            // Ако е зададен параметър shuffle  в урл-то разбъркваме пакетите
            if (!is_array($packs)) {
                $packs = arr::make($packs);
            }
            shuffle($packs);
            $packs = implode(',', $packs);
        }
        
        $haveError = array();
        
        core_SystemLock::block('Clearing cache');
        
        core_Debug::$isLogging = false;
        $Cache = cls::get('core_Cache');
        $Cache->eraseFull();
        core_Cache::$stopCaching = true;

        $loop = 0;

        do {
            $loop++;
            
            $packArr = arr::make($packs);
            
            $packCnt = countR($packArr);
            $i = 1;

            $isSetup = array();

            // Извършваме инициализирането на всички включени в списъка пакети
            foreach ($packArr as $p) {
                $i++;
                core_SystemLock::block("Load Setup Data For {$p} ({$i}/{$packCnt})");
                
                if (cls::load($p . '_Setup', true) && !$isSetup[$p]) {
                    try {
                        $html .= $Packs->setupPack($p);
                        $isSetup[$p] = true;
                        
                        // Махаме грешките, които са възникнали, но все пак
                        // са се поправили в не дебъг режим
                        if (!isDebug()) {
                            unset($haveError[$p]);
                        }
                    } catch (core_exception_Expect $exp) {
                        $force = true;
                        $Packs->alreadySetup[$p . $force] = false;
                        
                        //$haveError = TRUE;
                        file_put_contents(EF_TEMP_PATH . '/' . date('H-i-s') . '.log.html', ht::mixedToHtml($exp->getTrace()) . "\n\n", FILE_APPEND);
                        $haveError[$p] .= "<h3 class='debug-error'>Грешка при инсталиране на пакета {$p}<br>" . $exp->getMessage() . ' ' . date('H:i:s') . '</h3>';
                        reportException($exp);
                    }
                }
            }
            
            // Форсираме системния потребител
            core_Users::forceSystemUser();
            
            // Първа итерация за захранване с данни
            $this->loadSetupDataProc($packs, $haveError, $html);
            
            // Втора итерация за захранване с данни
            $this->loadSetupDataProc($packs, $haveError, $html, '2');
            
            // Де-форсираме системния потребител
            core_Users::cancelSystemUser();
        } while (!empty($haveError) && ($loop < 5));

        if ($isFirstSetup) {
            $currentVersion = core_setup::CURRENT_VERSION;
        } else {
            $currentVersion = core_Updates::getNewVersionTag();
        }

        // Записваме в конфигурацията, че базата е мигрирана към текущата версия
        if ($currentVersion) {
            core_Packs::setConfig('core', array('CORE_LAST_DB_VERSION' => $currentVersion));
        }
        
        // Започваме пак да записваме дебъг съобщенията
        core_Debug::$isLogging = true;

        core_SystemLock::block('Finishing bgERP Installation');
        
        $html .= implode("\n", $haveError);
        
        //Създаваме, кофа, където ще държим всички прикачени файлове на бележките
        $Bucket = cls::get('fileman_Buckets');
        $Bucket->createBucket('Notes', 'Прикачени файлове в бележки', null, '1GB', 'user', 'user');
        $Bucket->createBucket('bnav_importCsv', 'CSV за импорт', 'csv', '20MB', 'user', 'every_one');
        $Bucket->createBucket('exportCsv', 'Експортирани CSV-та', 'csv,txt,text,', '10MB', 'user', 'powerUser');
        
        // Добавяме Импортиращия драйвър в core_Classes
        $html .= core_Classes::add('bgerp_BaseImporter');
        $html .= $Bucket->createBucket('import', 'Файлове при импортиране', null, '104857600', 'user', 'user');
        
        //TODO в момента се записват само при инсталация на целия пакет
        
        
        //Зарежда данни за инициализация от CSV файл за core_Lg
        $html .= bgerp_data_Translations::loadData();
        
        // Инсталираме плъгина за прихващане на първото логване на потребител в системата
        $html .= $Plugins->installPlugin('First Login', 'bgerp_plg_FirstLogin', 'core_Users', 'private');
        
        // Инсталираме плъгина за проверка дали работи cron
        $html .= $Plugins->installPlugin('Check cron', 'bgerp_plg_CheckCronOnLogin', 'core_Users', 'private');
        
        // Инсталираме плъгина за оцветяване в листови изглед на резултати от търсене
        $html .= $Plugins->installPlugin('Highlight list search', 'plg_HighlightListSearch', 'core_Manager', 'family');
        
        $Menu = cls::get('bgerp_Menu');
        
        // Да се изтрият необновените менюта
        $Menu->deleteNotInstalledMenu = true;
        
        $rec = new stdClass();
        $rec->systemId = 'DeleteOldRecently';
        $rec->description = 'Изтриване на изтеклите Recently';
        $rec->controller = 'bgerp_Recently';
        $rec->action = 'DeleteOldRecently';
        $rec->period = 24 * 60;
        $rec->timeLimit = 50;
        $rec->offset = mt_rand(0, 300);
        $rec->isRandOffset = true;
        $html .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'DeleteOldNotifications';
        $rec->description = 'Изтриване на изтеклите Notifications';
        $rec->controller = 'bgerp_Notifications';
        $rec->action = 'DeleteOldNotifications';
        $rec->period = 24 * 60;
        $rec->timeLimit = 50;
        $rec->offset = mt_rand(0, 300);
        $rec->isRandOffset = true;
        $html .= core_Cron::addOnce($rec);
        
        
        $html .= $Menu->repair();
        $html .= core_Classes::rebuild();
        $html .= core_Cron::cleanRecords();
        
        // Принудително обновяване на ролите
        $html .= core_Roles::rebuildRoles();
        $html .= core_Users::rebuildRoles();

        $html .= core_Classes::add('bgerp_plg_CsvExport');
        $html .= core_Classes::add('bgerp_plg_XlsExport');

        $html .= parent::install();
        
        core_SystemLock::remove();
        
        return $html;
    }
    
    
    /**
     * Захранва с начални данни посочените пакети
     *
     * @param array|string $packs Масив с пакети
     * @param int          $itr   Номер на итерацията
     *
     * @return array Грешки
     */
    public function loadSetupDataProc($packs, &$haveError = array(), &$html = '', $itr = '')
    {
        // Кои пакети дотук сме засели с данни
        $isLoad = array();
        
        // Инстанции на пакетите;
        $packsInst = array();
        
        $packArr = arr::make($packs);
        
        $packCnt = countR($packArr);
        $i = 1;
        
        // Извършваме инициализирането на всички включени в списъка пакети
        foreach ($packArr as $p) {
            $i++;
            core_SystemLock::block("Load Setup Data For {$p} ({$i}/{$packCnt})");
            
            if (cls::load($p . '_Setup', true) && !$isLoad[$p]) {
                $packsInst[$p] = cls::get($p . '_Setup');
                
                if (method_exists($packsInst[$p], 'loadSetupData')) {
                    try {
                        $loadRes = $packsInst[$p]->loadSetupData($itr);
                        
                        if ($loadRes) {
                            $html .= "<h2>Инициализиране на ${p}</h2>";
                            $html .= '<ul>';
                            $html .= $loadRes;
                            $html .= '</ul>';
                        }
                        
                        $isLoad[$p] = true;
                        
                        // Махаме грешките, които са възникнали, но все пак са се поправили
                        // в не дебъг режим
                        if (!isDebug()) {
                            unset($haveError[$p]);
                        }
                    } catch (core_exception_Expect $exp) {
                        //$haveError = TRUE;
                        file_put_contents(EF_TEMP_PATH . '/' . date('H-i-s') . '.log.html', ht::mixedToHtml($exp->getTrace()) . "\n\n", FILE_APPEND);
                        $haveError[$p] .= "<h3 class='debug-error'>Грешка при зареждане данните на пакета {$p} <br>" . $exp->getMessage() . ' ' . date('H:i:s') . '</h3>';
                        reportException($exp);
                    }
                    
                    global $setupFlag;
                    
                    if ($setupFlag) {
                        // Махаме <h2> тага на заглавието
                        // $res = substr($res, strpos($res, "</h2>"), strlen($res));

                        $res = '';

                        do {
                            $res = @file_put_contents(EF_SETUP_LOG_PATH, $res, FILE_APPEND | LOCK_EX);
                            if ($res !== false) {
                                break;
                            }
                            usleep(1000);
                        } while ($i++ < 100);
                        
                        unset($res);
                    }
                }
            }
        }
    }

    
    /**
     * Зареждане на данни
     */
    public function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);

        $res .= $this->callMigrate('oldPortalToNewPortalView2131', 'bgerp');

        // За да може да мине миграцията при нова инсталация
        $dbUpdate = core_ProtoSetup::$dbInit;
        core_ProtoSetup::$dbInit = 'update';

        $res .= $this->callMigrate('setNewPortal46194', 'bgerp');
        $res .= $this->callMigrate('removeTestFilters2824', 'bgerp');

        core_ProtoSetup::$dbInit = $dbUpdate;

        return $res;
    }


    /**
     * Миграция за изтриване на старите данни в портала и за добавяне на новите интерфейси
     * Тази миграция се пуска и при нова инсталация. Не трябва да се трие.
     * Трябва да се вика в loadSetupData
     */
    public function setNewPortal46194()
    {
        $Portal = cls::get('bgerp_Portal');

        $data = core_Packs::getConfig('core')->_data;

        $force = false;
        if (!$data['migration_bgerp_setNewPortal46193']) {
            $force = true;
        }

        if (!$force) {
            if (!bgerp_Portal::fetch("#createdBy > 0")) {
                $force = true;
            }
        }

        if (!$force) {

            return ;
        }

        $bQuery = $Portal->getQuery();
        $bQuery->delete('1=1');

        $iArr = array('bgerp_drivers_Notifications' => array('perPage' => 15, 'column' => 'left', 'order' => 500, 'color' => 'lightblue'),
            'bgerp_drivers_Calendar' => array('column' => 'center', 'order' => 700, 'fTasksPerPage' => 5, 'fTasksDays' => 2629746, 'color' => 'yellow'),
            'bgerp_drivers_Tasks' => array('perPage' => 15, 'column' => 'center', 'order' => 400, 'color' => 'pink', 'showCal' => 'yes'),
            'bgerp_drivers_Recently' => array('perPage' => 10, 'column' => 'right', 'order' => 500, 'color' => 'darkgray'),
        );

        foreach ($iArr as $iName => $iData) {

            // Ако драйверите не са добавени
            core_Classes::add($iName);

            $rec = new stdClass();
            $rec->{$Portal->driverClassField} = $iName::getClassId();

            foreach ($iData as $cName => $cVal) {
                $rec->{$cName} = $cVal;
            }

            $rec->userOrRole = type_UserOrRole::getAllSysTeamId();

            setIfNot($rec->color, 'lightgray');
            $rec->state = 'yes';

            $Portal->save($rec);
        }
    }


    /**
     * Миграция за уеднаквяване на интерфейса на портала само за стария вид
     */
    public function oldPortalToNewPortalView2131()
    {
        $Portal = cls::get('bgerp_Portal');

        $uArr = core_Users::getByRole('powerUser');
        $sKey = crm_Profiles::getSettingsKey();

        $sArr = core_Settings::fetchUsers($sKey);

        foreach ($uArr as $uId) {

            // Ако все още се използва стария портал
            if ($sArr[$uId]['BGERP_PORTAL_VIEW'] != 'standard') continue;

            $pArrange = $sArr[$uId]['CORE_PORTAL_ARRANGE'] ? $sArr[$uId]['CORE_PORTAL_ARRANGE'] : 'notifyTaskRecentlyCal';

            $pQuery = $Portal->getQuery();
            $pQuery->where(array("#createdBy = '[#1#]'", $uId));
            $pQuery->where(array("#userOrRole = '[#1#]'", $uId));
            $pQuery->limit(1);

            // Ако има добавен запис ръчно
            if ($pQuery->fetch()) {

                continue;
            }

            $iArr = array('bgerp_drivers_Notifications' => array('perPage' => 15, 'column' => 'left', 'order' => 500, 'color' => 'lightblue'),
                'bgerp_drivers_Calendar' => array('column' => 'center', 'order' => 700, 'fTasksPerPage' => 5, 'fTasksDays' => 2629746, 'color' => 'yellow'),
                'bgerp_drivers_Tasks' => array('perPage' => 15, 'column' => 'center', 'order' => 400, 'color' => 'pink', 'showCal' => 'yes'),
                'bgerp_drivers_Recently' => array('perPage' => 10, 'column' => 'right', 'order' => 500, 'color' => 'darkgray'),
            );

            if ($pArrange == 'notifyTaskRecentlyCal') {

                // Известия - Задачи - Последно и Календар

                $iArr['bgerp_drivers_Notifications']['column'] = 'left';
                $iArr['bgerp_drivers_Notifications']['order'] = '800';

                $iArr['bgerp_drivers_Calendar']['column'] = 'right';
                $iArr['bgerp_drivers_Calendar']['order'] = '500';

                $iArr['bgerp_drivers_Tasks']['column'] = 'center';
                $iArr['bgerp_drivers_Tasks']['order'] = '700';

                $iArr['bgerp_drivers_Recently']['column'] = 'right';
                $iArr['bgerp_drivers_Recently']['order'] = '600';
            } elseif ($pArrange == 'notifyTaskCalRecently') {

                //Известия - Задачи - Календар и Последно

                $iArr['bgerp_drivers_Notifications']['column'] = 'left';
                $iArr['bgerp_drivers_Notifications']['order'] = '800';

                $iArr['bgerp_drivers_Calendar']['column'] = 'right';
                $iArr['bgerp_drivers_Calendar']['order'] = '600';

                $iArr['bgerp_drivers_Tasks']['column'] = 'center';
                $iArr['bgerp_drivers_Tasks']['order'] = '700';

                $iArr['bgerp_drivers_Recently']['column'] = 'right';
                $iArr['bgerp_drivers_Recently']['order'] = '500';
            } elseif ($pArrange == 'recentlyNotifyTaskCal') {

                // Последно - Известия - Задачи и Календар

                $iArr['bgerp_drivers_Notifications']['column'] = 'center';
                $iArr['bgerp_drivers_Notifications']['order'] = '700';

                $iArr['bgerp_drivers_Calendar']['column'] = 'right';
                $iArr['bgerp_drivers_Calendar']['order'] = '500';

                $iArr['bgerp_drivers_Tasks']['column'] = 'right';
                $iArr['bgerp_drivers_Tasks']['order'] = '600';
                $iArr['bgerp_drivers_Tasks']['perPage'] = '10';

                $iArr['bgerp_drivers_Recently']['column'] = 'left';
                $iArr['bgerp_drivers_Recently']['order'] = '800';
                $iArr['bgerp_drivers_Recently']['perPage'] = '20';

            } elseif ($pArrange == 'taskNotifyRecentlyCal') {

                // Задачи - Известия - Последно и Календар

                $iArr['bgerp_drivers_Notifications']['column'] = 'center';
                $iArr['bgerp_drivers_Notifications']['order'] = '700';

                $iArr['bgerp_drivers_Calendar']['column'] = 'right';
                $iArr['bgerp_drivers_Calendar']['order'] = '500';

                $iArr['bgerp_drivers_Tasks']['column'] = 'left';
                $iArr['bgerp_drivers_Tasks']['order'] = '800';

                $iArr['bgerp_drivers_Recently']['column'] = 'right';
                $iArr['bgerp_drivers_Recently']['order'] = '600';
            }

            foreach ($iArr as $iName => $iData) {

                // Ако драйверите не са добавени
                core_Classes::add($iName);

                $rec = new stdClass();
                $rec->{$Portal->driverClassField} = $iName::getClassId();

                foreach ($iData as $cName => $cVal) {
                    $rec->{$cName} = $cVal;
                }

                $rec->userOrRole = $uId;
                $rec->createdBy = $uId;
                $rec->createdOn = dt::now();

                setIfNot($rec->color, 'lightgray');
                $rec->state = 'yes';

                $rec->clonedFromId = bgerp_Portal::fetchField(array("#{$Portal->driverClassField} = '[#1#]' AND #userOrRole = '[#2#]'", $rec->{$Portal->driverClassField}, type_UserOrRole::getAllSysTeamId()), 'id');

                $Portal->save($rec);
            }

            if (cls::load('doc_drivers_FolderPortal', true)) {
                $pRecFolders = $Portal->fetch(array("#{$Portal->driverClassField} = '[#1#]' AND #userOrRole = '[#2#]'", doc_drivers_FolderPortal::getClassId(), $uId));

                if ($pRecFolders) {
                    $pRecFolders->order = 400;
                    $Portal->save($pRecFolders, 'order');
                }
            }
        }
    }


    /**
     * Изтриване на тестови филтри
     */
    public function removeTestFilters2824()
    {
        bgerp_Filters::delete("#name IN ('vat0pur', 'vat9pur', 'vat20pur')");
    }
}
