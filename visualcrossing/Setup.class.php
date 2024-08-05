<?php


/**
 * Задаване на локация
 */
defIfNot('VISUALCROSSING_LOCATION', 'Veliko Tarnovo');


/**
 * Апи ключ
 */
defIfNot('VISUALCROSSING_API_KEY', '');


/**
 * class visualcrossing_Setup
 *
 * Инсталиране/Деинсталиране на пакета за прогнози за времето visualcrossing
 *
 *
 * @category  bgerp
 * @package   visualcrossing
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class visualcrossing_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';


    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'visualcrossing_Forecasts';


    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';


    /**
     * Описание на модула
     */
    public $info = 'Прогнози за времето. <a href="https://visualcrossing.com/poweredby/" target="_blank">Powered by VISUALCROSSING</a>';


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(

        // Api key за visualcrossing.com
        'VISUALCROSSING_API_KEY' => array('varchar', 'mandatory, caption=Прогноза за времето от visualcrossing.com->Ключ'),


        // Задаване на мястото по подразбиране
        'VISUALCROSSING_LOCATION' => array('varchar', 'caption=Прогноза за времето от visualcrossing.com->Място, customizeBy=powerUser'),

    );


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'visualcrossing_Forecasts',
    );


    /**
     * Плъгини, които трябва да се инсталират
     */
    public $plugins = array(
        array('VisualCrossing Forecast', 'visualcrossing_Plugin', 'cal_Calendar', 'private'),
    );

    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'visualcrossing_Sensor, visualcrossing_ForecastSens';


    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Get forecasts from visualcrossing.com',
            'description' => 'Извличане на прогнози от visualcrossing.com',
            'controller' => 'visualcrossing_Forecasts',
            'action' => 'Update',
            'period' => 30,
            'offset' => 0,
            'delay' => 0,
            'timeLimit' => 50,
        ), array(
            'systemId' => 'Delete old rec from visualcrossing',
            'description' => 'Изтриване на старите записи за прогнозата',
            'controller' => 'visualcrossing_Forecasts',
            'action' => 'DeleteOld',
            'period' => 1440,
            'timeLimit' => 50,
        ),
    );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = '';

        // При инсталация, деинсталираме пакета darksky
        if (core_Packs::isInstalled('darksky')) {
            $html .= cls::get('core_Packs')->deinstall('darksky');
        }

        $html .= parent::install();

        return $html;
    }

}