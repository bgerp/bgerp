<?php


/**
 * FileHandler на логото на фирмата на английски
 */
defIfNot(BGERP_COMPANY_LOGO_EN, '');


/**
 * FileHandler на логото на фирмата на български
 */
defIfNot(BGERP_COMPANY_LOGO, '');


/**
 * class 'bgerp_Setup' - Начално установяване на 'bgerp'
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class bgerp_Setup {
    
    
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
           
       'BGERP_COMPANY_LOGO_EN' => array ('fileman_FileType(bucket=pictures)'),

       'BGERP_COMPANY_LOGO'   => array ('fileman_FileType(bucket=pictures)'),
    
    );
    
    
    /**
     * Описание на системните действия
     */
    var $systemActions = array(
           
       'Поправка' => array ('doc_folders', 'repair'),
    
    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install($Plugins = NULL)
    {
        // Предотвратяваме логването в Debug режим
        Debug::$isLogging = FALSE;
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->repair();

        $managers = array(
            'bgerp_Menu',
            'bgerp_Portal',
            'bgerp_Notifications',
            'bgerp_Recently',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Инстанция на мениджъра на пакетите
        $Packs = cls::get('core_Packs');
        
        // Това първо инсталиране ли е?
        $isFirstSetup = ($Packs->count() == 0);
        
        // Списък на основните модули на bgERP
        $packs = "core,fileman,drdata,editwatch,recently,thumbnail,acc,currency,doc,cms,
                  email,crm, cat, price, blast,rfid,hr,trz,lab,sales,mp,store,salecond,cash,bank,
                  budget,purchase,accda,sens,cams,cal,fconv,log,fconv,cms,blogm,forum,
                  vislog,docoffice,incoming,support,survey,pos,change,sass,techno";
        
        // Ако има private проект, добавяме и инсталатора на едноименния му модул
        if(defined('EF_PRIVATE_PATH')) {
            $packs .= ',' . strtolower(basename(EF_PRIVATE_PATH));
        }
        
        // Добавяме допълнителните пакети, само при първоначален Setup
        $Folders = cls::get('doc_Folders');
        if(!$Folders->db->tableExists($Folders->dbTableName) || ($isFirstSetup)) {
            $packs .= ",avatar,keyboard,statuses,google,catering,gdocs,jqdatepick,oembed,hclean,chosen";
        } else {
            $packs = arr::make($packs, TRUE);
            $pQuery = $Packs->getQuery();
            
            while($pRec = $pQuery->fetch()) {
                if(!$packs[$pRec->name]) {
                    $packs[$pRec->name] = $pRec->name;
                }
            }
        }
        
        // Извършваме инициализирането на всички включени в списъка пакети
        foreach(arr::make($packs) as $p) {
            if(cls::load($p . '_Setup', TRUE)) {
                $html .= $Packs->setupPack($p);
            }
        }

        // Извършваме инициализирането на всички включени в списъка пакети
        foreach(arr::make($packs) as $p) {
            if(cls::load($p . '_Setup', TRUE)) {
                $packsInst[$p] = cls::get($p . '_Setup');
                if(method_exists($packsInst[$p], 'loadSetupData')) {
                    $packsInst[$p]->loadSetupData();
                }
            }
        }
        
        //Създаваме, кофа, където ще държим всички прикачени файлове на бележките
        $Bucket = cls::get('fileman_Buckets');
        $Bucket->createBucket('Notes', 'Прикачени файлове в бележки', NULL, '104857600', 'user', 'user');

        //TODO в момента се записват само при инсталация на целия пакет
        
        
        //Зарежда данни за инициализация от CSV файл за core_Lg
        $html .= bgerp_data_Translations::loadData();
        

        // Инсталираме плъгина за прихващане на първото логване на потребител в системата
        $html .= $Plugins->installPlugin('First Login', 'bgerp_plg_FirstLogin', 'core_Users', 'private');

        $Menu = cls::get('bgerp_Menu');
        
        $html .= $Menu->addItem(1.62, 'Система', 'Ядро', 'core_Packs', 'default', 'admin');
        $html .= $Menu->addItem(1.64, 'Система', 'bgERP', 'bgerp_Menu', 'default', 'admin');
        $html .= $Menu->addItem(1.66, 'Система', 'Файлове', 'fileman_Files', 'default', 'admin');
        $html .= $Menu->addItem(1.68, 'Система', 'Данни', 'drdata_Countries', 'default', 'admin');
        
        $html .= $Menu->repair();
        
        // Принудително обновяване на ролите
        $html .= core_Roles::rebuildRoles();
        $html .= core_Users::rebuildRoles();


        return $html;
    }
    
    
}
