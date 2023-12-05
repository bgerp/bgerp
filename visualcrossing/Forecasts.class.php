<?php


/**
 * Клас 'visualcrossing_Forecasts' - Прогнози за времето
 *
 * Прогнози за времето по дни и населено място
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
class visualcrossing_Forecasts extends core_Manager
{
    /**
     * Заглавие на модула
     */
    public $title = 'Прогнози за времето от visualcrossing.com';


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
        $this->FLD('location', 'varchar(ci)', 'caption=Място,hint=Град');

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

            if ($pSettings['VISUALCROSSING_LOCATION']) {
                $location = $pSettings['VISUALCROSSING_LOCATION'];
            } else {
                $location = visualcrossing_Setup::get('LOCATION', false, core_Users::getCurrent());
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
        if (defined('DEV_SERVER') && (DEV_SERVER === true)) {

            return;
        }

        $apiKey = visualcrossing_Setup::get('API_KEY');

        if (!$apiKey) {
            self::logErr('Липсващ API ключ');

            return;
        }

        $locations = array();

        $locations[visualcrossing_Setup::get('LOCATION')] = visualcrossing_Setup::get('LOCATION');

        $userLoc = core_Settings::fetchPersonalConfig('VISUALCROSSING_LOCATION', 'crm_Profiles', 'all');

        shuffle($userLoc);

        foreach ($userLoc as $loc) {
            $locations[$loc] = $loc;
        }

        foreach ($locations as $location) {
            $locationEncode = rawurlencode($location);

            $jsonRes = @file_get_contents("https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/{$locationEncode}?unitGroup=metric&key={$apiKey}&contentType=json");

            if ($jsonRes === false) {
                self::logWarning('Грешка при извличане на данни за локацията');

                continue;
            }

            $weather = json_decode($jsonRes);
            $forecastday = $weather->days;

            if (is_array($forecastday)) {
                foreach ($forecastday as $day => $data) {

                    $date = dt::timestamp2mysql($data->datetimeEpoch);

                    $rec = self::fetch(array("#date = '[#1#]' && #location = '[#2#]'", $date, $location));
                    if (!$rec) {
                        $rec = new stdClass();
                        $rec->date = $date;
                        $rec->location = $location;
                    }

                    $rec->low = $data->tempmin;
                    $rec->high = $data->tempmax;
                    $rec->rh = $data->humidity;
                    $rec->wind = $data->windspeed;
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
        visualcrossing_Forecasts::delete(array("#date <= '[#1#]'", dt::addDays(-7, null, false)));
    }

}

