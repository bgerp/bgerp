<?php


/**
 * Задаване на локация
 */
defIfNot('DARKSKY_LOCATION', '43.08124,25.62904');


/**
 * Апи ключ
 */
defIfNot('DARKSKY_API_KEY', '');


/**
 * class darksky_Setup
 *
 * Инсталиране/Деинсталиране на пакета за прогнози за времето darksky
 *
 *
 * @category  bgerp
 * @package   darksky
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class darksky_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'darksky_Forecasts';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Прогнози за времето. <a href="https://darksky.net/poweredby/" target="_blank">Powered by Dark Sky</a>';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        // Api key за darksky.com
        'DARKSKY_API_KEY' => array('varchar', 'mandatory, caption=Прогноза за времето от darksky.net->Ключ'),
        
        
        // Задаване на мястото по подразбиране
        'DARKSKY_LOCATION' => array('location_Type', 'caption=Прогноза за времето от darksky.net->Място, customizeBy=powerUser'),
    
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'darksky_Forecasts',
    );
    
    
    /**
     * Плъгини, които трябва да се инсталират
     */
    public $plugins = array (
            array('DarkSky Forecast', 'darksky_Plugin', 'cal_Calendar', 'private'),
        );
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
            array(
                    'systemId' => 'Get forecasts from darksky.net',
                    'description' => 'Извличане на прогнози от darksky.net',
                    'controller' => 'darksky_Forecasts',
                    'action' => 'Update',
                    'period' => 180,
                    'offset' => 73,
                    'delay' => 0,
                    'timeLimit' => 50,
            ),array(
                    'systemId' => 'Delete old rec from forecast',
                    'description' => 'Изтриване на старите записи за прогнозата',
                    'controller' => 'darksky_Forecasts',
                    'action' => 'DeleteOld',
                    'period' => 1440,
                    'timeLimit' => 50,
            ),
        );

}
