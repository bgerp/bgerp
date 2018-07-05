<?php

/**
 * Задаване на локация
 */
defIfNot('WUND_DEFAULT_LOCATION', 'Bulgaria/Sofia');


/**
 * Апи ключ
 */
defIfNot('WUND_API_KEY', '');


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
class wund_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'wund_Forecasts';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Прогнози за времето от Wunderground.com';
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
            
    // Api key за Wunderground.com
            'WUND_API_KEY' => array('varchar', 'mandatory, caption=Ключ от http://www.wunderground.com/weather/api/->Ключ за '),
            
            // Задаване на мястото по подразбиране
            'WUND_DEFAULT_LOCATION' => array('varchar', 'caption=Задаване на локация->Държава/Град, suggestions=Bulgaria/Sofia|Bulgaria/Veliko Tarnovo|Bulgaria/Varna|Bulgaria/Burgas|Bulgaria/Plovdiv|Bulgaria/Pleven|Bulgaria/Stara Zagora'),
       
        );
    
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'wund_Forecasts',
        );
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $res = parent::install();
           
        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'Get weather Forecasts';
        $rec->description = 'Извличане на прогнози за времето';
        $rec->controller = 'wund_Forecasts';
        $rec->action = 'Update';
        $rec->period = 3 * 60;
        $rec->offset = rand(2, 150);
        $rec->delay = 0;
        $rec->timeLimit = 50;
        $res .= core_Cron::addOnce($rec);
        
        // Инсталираме wund_Plugin
        $Plugins = cls::get('core_Plugins');
        $res .= $Plugins->installPlugin('Weather Forecast', 'wund_Plugin', 'cal_Calendar', 'private');
         
        return $res;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}
