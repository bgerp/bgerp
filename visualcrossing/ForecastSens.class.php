<?php


/**
 * Сензор за работен цикъл
 *
 * @category  bgerp
 * @package   hr
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Прогноза на времето
 */
class visualcrossing_ForecastSens extends sens2_ProtoDriver
{

    /**
     * Заглавие на драйвера
     */
    public $title = 'Прогноза на времето';


    /**
     * Входове на контролера
     */
    public $inputs = array(
        'low' => array('caption' => 'Мин. темп', 'uom' => 'ºC', 'logPeriod' => 0, 'readPeriod' => 60),
        'high' => array('caption' => 'Макс. темп', 'uom' => 'ºC', 'logPeriod' => 0, 'readPeriod' => 60),
        'rh' => array('caption' => 'Влажност', 'uom' => '%', 'logPeriod' => 0, 'readPeriod' => 60),
        'wind' => array('caption' => 'Вятър', 'uom' => 'km/h', 'logPeriod' => 0, 'readPeriod' => 60),
        'precip' => array('caption' => 'Валежи', 'uom' => 'mm', 'logPeriod' => 0, 'readPeriod' => 60),
    );


    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @see  sens2_ControllerIntf
     *
     * @param core_Form
     */
    public function prepareConfigForm($form)
    {
        $form->FLD('location', 'varchar(64, ci)', 'caption=Локация, input, mandatory');
        $form->FLD('period', 'enum(0=В момента, 3=След 3 часа, 6=След 6 часа, 12=След 12 часа, allDay=Цял ден)', 'caption=Период, input, mandatory');

        $query = visualcrossing_Forecasts::getQuery();
        $query->groupBy('location');
        $locationsArr = array();
        while ($rec = $query->fetch()) {
            $locationsArr[$rec->location] = $rec->location;
        }

        $form->setOptions('location', $locationsArr);
        $defLocation = visualcrossing_Setup::get('LOCATION');
        if ($defLocation && $locationsArr[$defLocation]) {
            $form->setDefault('location', $defLocation);
        }
        $form->setDefault('period', 0);
    }


    /**
     * Прочитане на входовете
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        $resArr = array();

        if ($config->period == 'allDay') {
            $date = dt::now(false);
            $time = '';
        } else {
            $time = date('G', strtotime("{$config->period} hours"));
            $date = date('Y-m-d', strtotime("{$config->period} hours"));
        }

        $forecast = visualcrossing_Forecasts::getForecast($date, $time, $config->location);

        if (!$forecast) {

            return $resArr;
        }

        foreach ($inputs as $input) {
            $resArr[$input] = $forecast->{$input};
        }

        return $resArr;
    }
}
