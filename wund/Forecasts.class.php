<?php


/**
 * Клас 'wund_Forecasts' - Прогнози за времето
 *
 * Прогнози за времето по дни и населено място
 *
 *
 * @category  vendors
 * @package   wund
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class wund_Forecasts extends core_Manager
{
    /**
     * Заглавие на модула
     */
    public $title = 'Прогнози за времето от Wunderground.com';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        // Дата на прогнозата
        $this->FLD('date', 'date', array('caption' => 'Дата'));
        
        // Максимална температура
        $this->FLD('location', 'varchar(64)', 'caption=Място,hint=Country/City');
        
        // Минимална температура
        $this->FLD('low', 'double', 'caption=Температура->Мин.');
        
        // Максимална температура
        $this->FLD('high', 'double', 'caption=Температура->Макс.');
        
        // Максимална температура
        $this->FLD('iconUrl', 'varchar(128)', 'caption=Икона');
        
        $this->setDbUnique('date,location');
    }
    
    
    /**
     * Връща прогнозата за времето
     */
    public static function getForecast($date, $location = null)
    {
        if (!$location) {
            $conf = core_Packs::getConfig('wund');
            $location = $conf->WUND_DEFAULT_LOCATION;
        }
        
        if (!$location) {
            
            return false;
        }
        
        $rec = self::fetch(array("#date = '[#1#]' && #location = '[#2#]'", $date, $location));
        
        return $rec;
    }
    
    
    public function cron_Update()
    {
        $conf = core_Packs::getConfig('wund');
        $location = $conf->WUND_DEFAULT_LOCATION;
        $apiKey = $conf->WUND_API_KEY;
        
        $locationEsc = str_replace(' ', '%20', $location);
        $jsonRes = file_get_contents("http://api.wunderground.com/api/{$apiKey}/forecast/q/{$locationEsc}.json");
        
        $weather = json_decode($jsonRes);
        
        $forecastday = $weather->forecast->simpleforecast->forecastday;
        
        if (is_array($forecastday)) {
            foreach ($forecastday as $day) {
                $date = "{$day->date->year}-{$day->date->month}-{$day->date->day}";
                
                $rec = self::fetch(array("#date = '[#1#]' && #location = '[#2#]'", $date, $location));
                
                if (!$rec) {
                    $rec = new stdClass();
                    $rec->date = $date;
                    $rec->location = $location;
                }
                
                $rec->low = $day->low->celsius;
                $rec->high = $day->high->celsius;
                $rec->iconUrl = $day->icon_url;
                
                self::save($rec);
            }
        }
    }
}
