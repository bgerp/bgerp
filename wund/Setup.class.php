<?php

/**
 * Задаване на основна валута
 */
defIfNot('WUND_DEFAULT_LOCATION', 'Bulgaria/Sofia');



/**
 * class currency_Setup
 *
 * Инсталиране/Деинсталиране на пакета за прогнози за времето wund
 *
 *
 * @category  bgerp
 * @package   wund
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class wund_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'wund_Forecasts';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Прогнози за времето от Wunderground.com";
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
            
            // Задаване на мястото по подразбиране
            'WUND_DEFAULT_LOCATION' => array ('varchar'),
            
            // Api key за Wunderground.com
            'WUND_API_KEY' => array ('varchar', 'mandatory'),
       
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'wund_Forecasts',
        );
        
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'Get weather Forecasts';
        $rec->description = 'Извличане на прогнози за времето';
        $rec->controller = 'wund_Forecasts';
        $rec->action = 'Update';
        $rec->period = 2*60;
        $rec->offset = 55;
        $rec->delay = 0;
        $rec->timeLimit = 50;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $html .= "<li><font color='green'>Задаване по крон да извлича прогнози.</font></li>";
        } else {
            $html .= "<li>Отпреди Cron е бил нагласен да извлича прогнози.</li>";
        }
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме recently към формите
        $html .= $Plugins->installPlugin('Weather Forecast', 'wund_Plugin', 'cal_Calendar', 'private');

         
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
         
        return;
    }
}