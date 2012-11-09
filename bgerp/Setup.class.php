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
     * Инсталиране на пакета
     */
    function install($Plugins = NULL)
    {
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
        
        // Пакети, които ще се инсталират при инсталацията на bgERP
        $packs = "core,fileman,drdata,editwatch,recently,thumbnail,keyboard,acc,currency,doc,cms,
                  email,crm,cat,catpr,blast,rfid,hr,trz,catering,lab,sales,mp,store,trans,cash,bank,
                  budget,purchase,accda,sens,cams,hclean,cal,fconv,log,fconv,cms,gallery,blogm,forum,
                  vislog,avatar,statuses,google,gdocs,jqdatepick,oembed,chosen";

        if(defined('EF_PRIVATE_PATH')) {
            $packs .= ',' . strtolower(basename(EF_PRIVATE_PATH));
        }
        
        $Packs = cls::get('core_Packs');
        
        foreach(arr::make($packs) as $p) {
             $html .= $Packs->setupPack($p);
        }
        
        $pQuery = $Packs->getQuery();
        
        while($pRec = $pQuery->fetch()) {
            if(!$Packs->alreadySetup[$pRec->name]) {
                $html .= $Packs->setupPack($pRec->name);
            }
        }

        //TODO в момента се записват само при инсталация на целия пакет
        
        //Зарежда данни за инициализация от CSV файл за acc_Lists
        $html .= acc_setup_Lists::loadData();
        
        //Зарежда данни за инициализация от CSV файл за acc_Accounts
        $html .= acc_setup_Accounts::loadData();
        
        //Зарежда данни за инициализация от CSV файл за core_Lg
        $html .= bgerp_data_Translations::loadData();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за прихващане на първото логване на потребител в системата
        $html .= $Plugins->installPlugin('First Login', 'bgerp_plg_FirstLogin', 'core_Users', 'private');

        $Menu = cls::get('bgerp_Menu');
        
        $html .= $Menu->addItem(1, 'Система', 'Ядро', 'core_Packs', 'default', 'admin');
        $html .= $Menu->addItem(1, 'Система', 'bgERP', 'bgerp_Portal', 'default', 'admin');
        $html .= $Menu->addItem(1, 'Система', 'Файлове', 'fileman_Files', 'default', 'admin');
        $html .= $Menu->addItem(1, 'Система', 'Данни', 'drdata_Countries', 'default', 'admin');
        
        return $html;
    }
}