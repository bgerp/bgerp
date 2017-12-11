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
defIfNot('BGERP_NOTIFICATION_KEEP_DAYS', 31104000);


/**
 * Колко време да се съхранява историята за отворени нишки и папки 
 */
defIfNot('BGERP_RECENTLY_KEEP_DAYS', 31104000);


/**
 * Звуков сигнал при нотификация
 */
defIfNot('BGERP_SOUND_ON_NOTIFICATION', 'scanner');


/**
 * Кога е началото на работния ден
 */
defIfNot('BGERP_START_OF_WORKING_DAY', '08:00');


/**
 * Допустим % "Недоставено" за автоматично приключване на сделка
 */
defIfNot('BGERP_CLOSE_UNDELIVERED_OVER', '1');


/**
 * Клавиши за бързо избиране на бутони
 */
defIfNot('BGERP_ACCESS_KEYS',
'Чернова,Draft,Запис,Save = S
Запис и Нов,Save and New,Нов,New = N
Артикул,Item = A
Създаване,Create = R
Активиране,Activation,Контиране = K
Conto,Реконтиране = K
Отказ,Cancel = C
Връзка,Link = L
Редакция,Edit = O
»»» = >
««« = <');


/**
 * class 'bgerp_Setup' - Начално установяване на 'bgerp'
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_Setup extends core_ProtoSetup {
    

    /**
     * Да се инициализира ли, след промяна на конфигурацията?
     */
    const INIT_AFTER_CONFIG = FALSE;


    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'bgerp_Menu';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct;
    
    
    /**
     * Описание на модула
     */
    var $info = "Основно меню и портал на bgERP";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        'BGERP_COMPANY_LOGO' => array ('fileman_FileType(bucket=pictures)', 'caption=Фирмена бланка->На български, customizeBy=powerUser'),
        
        'BGERP_COMPANY_LOGO_EN' => array ('fileman_FileType(bucket=pictures)', 'caption=Фирмена бланка->На английски, customizeBy=powerUser'),
        
        'BGERP_NON_WORKING_CRON_TIME' => array ('time(suggestions=30 мин.|1 час| 3 часа)', 'caption=След колко време да дава нотификация за неработещ cron->Време'),
                
        'BGERP_SOUND_ON_NOTIFICATION' => array ('enum(none=Няма,snap=Щракване,scanner=Скенер,notification=Нотификация,beep=Beep)', 'caption=Звуков сигнал при нотификация->Звук, customizeBy=user'),

        'BGERP_NOTIFICATION_KEEP_DAYS' => array ('time(suggestions=180 дни|360 дни|540 дни,unit=days)', 'caption=Време за съхранение на нотификациите->Време'),
        
        'BGERP_RECENTLY_KEEP_DAYS' => array ('time(suggestions=180 дни|360 дни|540 дни,unit=days)', 'caption=Време за съхранение на историята в "Последно"->Време'),

        'BGERP_START_OF_WORKING_DAY' => array ('enum(08:00,09:00,10:00,11:00,12:00)', 'caption=Начало на работния ден->Час'),
        
        'BGERP_CLOSE_UNDELIVERED_OVER'    => array('percent(min=0)', 'caption=Допустимо автоматично приключване на сделка при "Доставено" минимум->Процент'),
         
        'BGERP_ACCESS_KEYS'    => array('text(rows=6)', 'caption=Клавиши за бързо избиране на бутони->Дефиниции, customizeBy=powerUser'),
    );
    
    
    /**
     * Път до js файла
     */
    //    var $commonJS = 'js/PortalSearch.js';
    
    
    /**
     * Дали пакета е системен
     */
    public $isSystem = TRUE;


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'migrate::addThreadIdToRecently',
        );
    
    
    /**
     * Настройки за Cron
     */
    var $cronSettings = array(
            array(
                    'systemId' => "Hide Inaccesable",
                    'description' => "Скрива на недостъпните нотификации",
                    'controller' => "bgerp_Notifications",
                    'action' => "HideInaccesable",
                    'period' => 1440,
                    'offset' => 50,
                    'timeLimit' => 600
            ),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Предотвратяваме логването в Debug режим
        Debug::$isLogging = FALSE;
        
        // Блокираме други процеси
        core_SystemLock::block("Prepare bgERP installation...");

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
            'bgerp_E',
            'bgerp_F',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            core_SystemLock::block("Install {$manager}");
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        core_SystemLock::block("Starting bgERP installation...");

        // Инстанция на мениджъра на пакетите
        $Packs = cls::get('core_Packs');
        
        // Това първо инсталиране ли е?
        $isFirstSetup = ($Packs->count() == 0);
        
        // Списък на основните модули на bgERP
        $packs = "core,log,fileman,drdata,bglocal,editwatch,recently,thumb,doc,acc,cond,currency,cms,
                  email,crm, cat, trans, price, blast,hr,lab,dec,sales,planning,import,marketing,store,cash,bank,
                  budget,tcost,purchase,accda,permanent,sens2,cams,frame,frame2,cal,fconv,doclog,fconv,cms,blogm,forum,deals,findeals,
                  vislog,docoffice,incoming,support,survey,pos,change,sass,
                  callcenter,social,hyphen,status,phpmailer,label,webkittopdf,jqcolorpicker";
        
        // Ако има private проект, добавяме и инсталатора на едноименния му модул
        if (defined('EF_PRIVATE_PATH')) {
            $packs .= ',' . strtolower(basename(EF_PRIVATE_PATH));
        }
        
        // Добавяме допълнителните пакети, само при първоначален Setup
        $Folders = cls::get('doc_Folders');
        
        if (!$Folders->db->tableExists($Folders->dbTableName) || ($isFirstSetup)) {
            $packs .= ",avatar,keyboard,statuses,google,gdocs,jqdatepick,imagics,fastscroll,context,autosize,oembed,hclean,select2,help,toast,minify,rtac,hljs,pixlr,tnef";
        } else {
            $packs = arr::make($packs, TRUE);
            $pQuery = $Packs->getQuery();
            $pQuery->where("#state = 'active'");
            
            while ($pRec = $pQuery->fetch()) {
                if(!$packs[$pRec->name]) {
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
        
        core_SystemLock::block("Clearing cache");

        core_Debug::$isLogging = FALSE;
        $Cache = cls::get('core_Cache');
        $Cache->eraseFull();
        core_Cache::$stopCaching = TRUE;

        do {
            $loop++;
            
            $packArr = arr::make($packs);

            $packCnt = count($packArr);
            $i = 1;

            // Извършваме инициализирането на всички включени в списъка пакети
            foreach ($packArr as $p) {
                
                $i++;
                core_SystemLock::block("Load Setup Data For {$p} ({$i}/{$packCnt})");

                if (cls::load($p . '_Setup', TRUE) && !$isSetup[$p]) {
                    try {
                        $html .= $Packs->setupPack($p);
                        $isSetup[$p] = TRUE;
                        
                        // Махаме грешките, които са възникнали, но все пак
                        // са се поправили в не дебъг режим
                        if (!isDebug()) {
                            unset($haveError[$p]);
                        }
                    } catch (core_exception_Expect $exp) {
                        $force = TRUE;
                        $Packs->alreadySetup[$p . $force] = FALSE;
                        
                        //$haveError = TRUE;
                        file_put_contents(EF_TEMP_PATH . '/' . date('H-i-s') . '.log.html', ht::mixedToHtml($exp->getTrace()) . "\n\n",  FILE_APPEND);
                        $haveError[$p] .= "<h3 class='debug-error'>Грешка при инсталиране на пакета {$p}<br>" . $exp->getMessage() . " " . date('H:i:s') . "</h3>";
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
            
        } while (!empty($haveError) && ($loop<5));
        

        core_Debug::$isLogging = TRUE;
        
        
        core_SystemLock::block("Finishing bgERP Installation");

        $html .= implode("\n", $haveError);
        
        //Създаваме, кофа, където ще държим всички прикачени файлове на бележките
        $Bucket = cls::get('fileman_Buckets');
        $Bucket->createBucket('Notes', 'Прикачени файлове в бележки', NULL, '1GB', 'user', 'user');
        $Bucket->createBucket('bnav_importCsv', 'CSV за импорт', 'csv', '20MB', 'user', 'every_one');
        $Bucket->createBucket('exportCsv', 'Експортирани CSV-та', 'csv,txt,text,', '10MB', 'user', 'powerUser');
        
        // Добавяме Импортиращия драйвър в core_Classes
        $html .= core_Classes::add('bgerp_BaseImporter');
        $html .= $Bucket->createBucket('import', 'Файлове при импортиране', NULL, '104857600', 'user', 'user');
        
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
        $Menu->deleteNotInstalledMenu = TRUE;
        
        $rec = new stdClass();
        $rec->systemId = "DeleteOldRecently";
        $rec->description = "Изтриване на изтеклите Recently";
        $rec->controller = "bgerp_Recently";
        $rec->action = "DeleteOldRecently";
        $rec->period = 24*60;
        $rec->timeLimit = 50;
        $rec->offset = mt_rand(0,300);
        $html .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = "DeleteOldNotifications";
        $rec->description = "Изтриване на изтеклите Notifications";
        $rec->controller = "bgerp_Notifications";
        $rec->action = "DeleteOldNotifications";
        $rec->period = 24*60;
        $rec->timeLimit = 50;
        $rec->offset = mt_rand(0,300);
        $html .= core_Cron::addOnce($rec);

        
        $html .= $Menu->repair();
        
        // Принудително обновяване на ролите
        $html .= core_Roles::rebuildRoles();
        $html .= core_Users::rebuildRoles();
        
        $html .= core_Classes::add('bgerp_plg_CsvExport');
        
        $html .= parent::install();

        core_SystemLock::remove();
        return $html;
    }

    
    /**
     * Захранва с начални данни посочените пакети
     * 
     * @param array|string  $packs  Масив с пакети
     * @param int           $itr    Номер на итерацията
     *
     * @return array                Грешки
     */
    function loadSetupDataProc($packs, &$haveError = array(), &$html = '', $itr = '')
    {
        // Кои пакети дотук сме засели с данни
        $isLoad = array();
        
        // Инстанции на пакетите;
        $packsInst = array();
        
        $packArr = arr::make($packs);

        $packCnt = count($packArr);
        $i = 1;

        // Извършваме инициализирането на всички включени в списъка пакети
        foreach ($packArr as $p) {
            
            $i++;
            core_SystemLock::block("Load Setup Data For {$p} ({$i}/{$packCnt})");

            if (cls::load($p . '_Setup', TRUE) && !$isLoad[$p]) {
                $packsInst[$p] = cls::get($p . '_Setup');
                
                if (method_exists($packsInst[$p], 'loadSetupData')) {
                    try {
                        
                        $loadRes = $packsInst[$p]->loadSetupData($itr);
                        
                        if ($loadRes) {
                            $html .= "<h2>Инициализиране на $p</h2>";
                            $html .= "<ul>";
                            $html .= $loadRes;
                            $html .= "</ul>";
                        }
                        
                        $isLoad[$p] = TRUE;
                        
                        // Махаме грешките, които са възникнали, но все пак са се поправили
                        // в не дебъг режим
                        if (!isDebug()) {
                            unset($haveError[$p]);
                        }
                    } catch(core_exception_Expect $exp) {
                        //$haveError = TRUE;
                        file_put_contents(EF_TEMP_PATH . '/' . date('H-i-s') . '.log.html', ht::mixedToHtml($exp->getTrace()) . "\n\n",  FILE_APPEND);
                        $haveError[$p] .= "<h3 class='debug-error'>Грешка при зареждане данните на пакета {$p} <br>" . $exp->getMessage() . " " . date('H:i:s') . "</h3>";
                        reportException($exp);
                    }
                    
                    global $setupFlag;

                    if ($setupFlag) {
                        // Махаме <h2> тага на заглавието
                       // $res = substr($res, strpos($res, "</h2>"), strlen($res));

                        do {
                            $res = @file_put_contents(EF_SETUP_LOG_PATH, $res, FILE_APPEND|LOCK_EX);
                            if($res !== FALSE) break;
                            usleep(1000);
                        } while($i++ < 100);
                        
                        unset($res);
                    }

                }
            }
        }
    }
    
    
    /**
     * Миграция за добавяне на threadId на документите
     */
    public static function addThreadIdToRecently()
    {
        $Recently = cls::get(bgerp_Recently);
        $rQuery = $Recently->getQuery();
        $rQuery->where("#threadId IS NULL");
        $rQuery->where("#objectId IS NOT NULL");
        $rQuery->where("#objectId != ''");
        $rQuery->where("#objectId != 0");
        $rQuery->where("#type = 'document'");
        
        while($rec = $rQuery->fetch()) {
            try {
                $Recently->save($rec, 'threadId');
            } catch (Exception $e) {
                continue;
            }
        }
    }
}
