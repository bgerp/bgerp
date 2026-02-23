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
     * Поддържани интерфейси
     */
    var $interfaces = 'ztm_interfaces_RegSyncValues';


    /**
     * Зареждане на използваните мениджъри
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_Modified, plg_Sorting';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        // Дата на прогнозата
        $this->FLD('date', 'varchar(16)', array('caption' => 'Дата'));

        // Час на прогнозата
        $this->FLD('time', 'varchar(2)', array('caption' => 'Час'));

        // Място
        $this->FLD('location', 'varchar(64, ci)', 'caption=Място,hint=Град');

        // Минимална температура
        $this->FLD('low', 'double', 'caption=Температура->Мин.,unit=C');

        // Максимална температура
        $this->FLD('high', 'double', 'caption=Температура->Макс.,unit=C');

        // Средна влажност
        $this->FLD('rh', 'percent', 'caption=Влажност->Средна');

        // Максимален вятър
        $this->FLD('wind', 'double', 'caption=Вятър->Максимален,unit=км/ч');

        // Валеже mm
        $this->FLD('precip', 'double', 'caption=Валежи,unit=mm');

        // Икона
        $this->FLD('icon', 'varchar(64)', 'caption=Икона');

        $this->setDbUnique('date,time,location');
    }


    /**
     * Връща прогнозата за времето
     */
    public static function getForecast($date, $time = '', $location = null)
    {
        if (!$location) {
            $pSettings = core_Settings::fetchKey(crm_Profiles::getSettingsKey());

            if (!empty($pSettings['VISUALCROSSING_LOCATION'])) {
                $location = $pSettings['VISUALCROSSING_LOCATION'];
            } else {
                $location = visualcrossing_Setup::get('LOCATION', false, core_Users::getCurrent());
            }
        }

        if (!$location) {

            return false;
        }

        $rec = self::fetch(array("#date = '[#1#]' && #time = '[#2#]' && #location = '[#3#]'", $date, $time, $location));

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
                foreach ($forecastday as $data) {

                    $date = $data->datetime;
                    //$date = dt::timestamp2mysql($data->datetime);
                    $time = '';

                    $rec = self::fetch(array("#date = '[#1#]' && #time = '[#2#]' && #location = '[#3#]'", $date, $time, $location));

                    if (!$rec) {
                        $rec = new stdClass();
                        $rec->date = $date;
                        $rec->time = $time;
                        $rec->location = $location;
                    }

                    $rec->low = $data->tempmin;
                    $rec->high = $data->tempmax;
                    $rec->rh = $data->humidity ? $data->humidity / 100 : 0;
                    $rec->wind = $data->windspeed;
                    $rec->precip = $data->precip;
                    $rec->icon = $data->icon;

                    self::save($rec);

                    unset($time);

                    foreach ($data->hours as $hour) {

                        if (substr($hour->datetime, 0, 1) != 0) {
                            $time = substr($hour->datetime, 0, 2);
                        } else {
                            $time = substr($hour->datetime, 1, 1);
                        }

                        $rec = self::fetch(array("#date = '[#1#]' && #time = '[#2#]' && #location = '[#3#]'", $date, $time, $location));

                        if (!$rec) {
                            $rec = new stdClass();
                            $rec->date = $date;
                            $rec->time = $time;
                            $rec->location = $location;
                        }

                        $rec->low = isset($hour->tempmin) ? $hour->tempmin : $hour->temp;
                        $rec->high = isset($hour->tempmax) ? $hour->tempmax : $hour->temp;
                        $rec->rh = $hour->humidity ? $hour->humidity / 100 : 0;
                        $rec->wind = $hour->windspeed;
                        $rec->icon = $hour->icon;
                        $rec->precip = $hour->precip;

                        self::save($rec);
                    }
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



    /**
     * Връща регистрите и стойностите им
     *
     * @param int|stdClass $deviceId
     *
     * @return array
     *
     * @see ztm_interfaces_RegSyncValues::getRegValues($deviceId)
     */
    public function getRegValues($deviceId)
    {
        $rArr = $this->prepareRegs($deviceId);

        return $rArr;
    }


    /**
     * Прочита и промяне регистрите и стойностите им
     *
     * @param null|stdClass $result
     * @param null|array $regArr
     * @param null|array $oDeviceRec
     * @param stdClass $deviceRec
     *
     * @return stdClass
     *
     * @see ztm_interfaces_RegSyncValues::prepareRegValues()
     */
    public function prepareRegValues($result, $regArr, $oDeviceRec, $deviceRec)
    {
//        $rArr = $this->prepareRegs($deviceRec);
//
//        foreach ($rArr as $reg => $valArr) {
//            $result->{$reg} = $valArr['val'];
//        }

        return $result;
    }


    /**
     * Помощна функция за подготовка на регистрите
     *
     * @param int|stdClass $deviceId
     *
     * @return array
     */
    protected function prepareRegs($deviceId)
    {
        $dRec = ztm_Devices::fetchRec($deviceId);
        $profileId = $dRec->profileId;

        static $regArr = array();

        if (!empty($regArr)) {

            return $regArr;
        }

        foreach (array(0, 3, 6) as $h) {
            $time = date('G', strtotime("{$h} hours"));
            $date = date('Y-m-d', strtotime("{$h} hours"));

            $forecast = $this->getForecast($date, $time);
            if (!$forecast) {

                continue;
            }
            foreach (array('icon' => 'icon', 'rh' => 'rh', 'temp' => 'low', 'wind' => 'wind') as $key => $field) {
                $eKey = "envm.forecast.{$key}_{$h}";

                if ($profileId) {
                    $pIds = ztm_Registers::fetchField(array("#name = '[#1#]'", $eKey), 'profileIds');
                    if ($pIds && !type_Keylist::isIn($profileId, $pIds)) {

                        continue;
                    }
                }

                $regArr[$eKey] = array('val' => $forecast->{$field}, 'time' => $forecast->modifiedOn);
                if (is_numeric($regArr[$eKey])) {
                    $regArr[$eKey] = (double) $regArr[$eKey];
                }
            }
        }

        return $regArr;
    }
}
