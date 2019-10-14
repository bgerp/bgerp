<?php


/**
 * Клас 'darksky_Forecasts' - Прогнози за времето
 *
 * Прогнози за времето по дни и населено място
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
class darksky_Forecasts extends core_Manager
{
    /**
     * Заглавие на модула
     */
    public $title = 'Прогнози за времето от darksky.com';
    
    
    /**
     * Зареждане на използваните мениджъри
     */
    public $loadList = 'plg_RowTools2';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        // Дата на прогнозата
        $this->FLD('date', 'date', array('caption' => 'Дата'));
        
        // Място
        $this->FLD('location', 'location_Type', 'caption=Място,hint=Град');
        
        // Минимална температура
        $this->FLD('low', 'double', 'caption=Температура->Мин.,unit=C');
        
        // Максимална температура
        $this->FLD('high', 'double', 'caption=Температура->Макс.,unit=C');
        
        // Средна влажност
        $this->FLD('rh', 'percent', 'caption=Влажност->Средна');
        
        // Максимален вятър
        $this->FLD('wind', 'double', 'caption=Вятър->Максимален,unit=км/ч');
        
        // Икона
        $this->FLD('icon', 'varchar(64)', 'caption=Икона');
        
        $this->setDbUnique('date,location');
    }
    
    
    /**
     * Връща прогнозата за времето
     */
    public static function getForecast($date, $location = null)
    {
        if (!$location) {
            $pSettings = core_Settings::fetchKey(crm_Profiles::getSettingsKey());
            
            if ($pSettings['DARKSKY_LOCATION']) {
                $location = $pSettings['DARKSKY_LOCATION'];
            } else {
                $location = darksky_Setup::get('LOCATION', false, core_Users::getCurrent());
            }
        }
        
        if (!$location) {
            
            return false;
        }
        
        $rec = self::fetch(array("#date = '[#1#]' && #location = '[#2#]'", $date, $location));
        
        return $rec;
    }
    
    
    /**
     * Крон за обновяване на прогнозата
     */
    public function cron_Update()
    {
        $apiKey = darksky_Setup::get('API_KEY');
        
        if (!$apiKey) {
            self::logErr('Липсващ API ключ');
            
            return ;
        }
        
        $locations = array();
        
        $locations[darksky_Setup::get('LOCATION')] = darksky_Setup::get('LOCATION');
        
        $userLoc = core_Settings::fetchPersonalConfig('DARKSKY_LOCATION', 'crm_Profiles', 'all');
        
        shuffle($userLoc);
        
        foreach($userLoc as $loc) {
            $locations[$loc] = $loc;
        }
        
        foreach($locations as $location) {
            $jsonRes = @file_get_contents("https://api.darksky.net/forecast/{$apiKey}/{$location}/?units=si");
            
            if ($jsonRes === false) {
                self::logWarning('Грешка при извличане на данни за локацията');
                
                continue;
            }
            
            $weather = json_decode($jsonRes);
            
            $forecastday = $weather->daily->data;
            
            if (is_array($forecastday)) {
                foreach ($forecastday as $day => $data) {
                    
                    $date = dt::timestamp2mysql($data->time);
                    
                    $rec = self::fetch(array("#date = '[#1#]' && #location = '[#2#]'", $date, $location));
                    if (!$rec) {
                        $rec = new stdClass();
                        $rec->date = $date;
                        $rec->location = $location;
                    }
                    
                    $rec->low = $data->temperatureMin;
                    $rec->high = $data->temperatureMax;
                    $rec->rh = $data->humidity;
                    $rec->wind = $data->windSpeed;
                    $rec->icon = $data->icon;
                    
                    self::save($rec);
                }
            }
        }
    }
    
    
    /**
     * Крон за изтриване на старите записи
     */
    function cron_DeleteOld()
    {
        darksky_Forecasts::delete(array("#date <= '[#1#]'", dt::addDays(-7, null, false)));
    }

}
