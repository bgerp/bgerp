<?php


/**
 * Сензор за ...............
 *
 * @see
 *
 * @category  bgerp
 * @package   visualcrossing
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 * @title     Visualcrossing
 *
 * @since     v 0.1
 */
class visualcrossing_Sensor extends sens2_ProtoDriver
{
    /**
     * Заглавие на драйвера
     */
    public $title = 'Visualcrossing';


    /**
     * Описание на входовете
     */
    public $inputs = array(
        'currentTempMin' => array('caption' => 'Текуща температура[min]', 'uom' => 'C'),
        'currentTempMax' => array('caption' => 'Текуща температура[max]', 'uom' => 'C'),
        'tempAfter6HourMin' => array('caption' => 'Температура след 6ч[min]', 'uom' => 'C'),
        'tempAfter6HourMax' => array('caption' => 'Температура след 6ч[max]', 'uom' => 'C'),
        'windAfter2Uour' => array('caption' => 'Вятър след 2ч.', 'uom' => 'Km/h'),

    );

    /**
     * Интерфейси, поддържани от всички наследници
     */
    public $interfaces = 'sens2_ControllerIntf';


    /**
     * Описания на изходите
     */
    public $outputs = array();


    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @param core_Form
     * @see  sens2_ControllerIntf
     *
     */
    public function prepareConfigForm($form)
    {
    }


    /**
     * Прочита стойностите от сензорните входове
     *
     * @param array $inputs
     * @param array $config
     * @param array $persistentState
     *
     * @return mixed
     * @see  sens2_ControllerIntf
     *
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        $res = array();

        $time = dt::mysql2timestamp(dt::now());

        $date = dt::now(false);
        $tomorrow = dt::addDays(2, $date);

        $query = visualcrossing_Forecasts::getQuery();
        $query->where("#date >= '{$date}' AND #date < '{$tomorrow}' ");

        $currentTempMin = $currentTempMax = $tempAfter6HourMin = $tempAfter6HourMax = $windAfter2Uour = null;
        while ($fRec = $query->fetch()) {


            if ($fRec->date == date('Y-m-d', $time) && ($fRec->time == date('G', $time))) {
                $currentTempMin = $fRec->low;
                $currentTempMax = $fRec->high;
            }

            if ($fRec->date == date('Y-m-d', $time) && ($fRec->time == date('G', $time + 6 * 3600))) {
                $tempAfter6HourMin = $fRec->low;
                $tempAfter6HourMax = $fRec->high;
            }

            if ($fRec->date == date('Y-m-d', $time) && ($fRec->time == date('G', $time + 2 * 3600))) {
                $windAfter2Uour = $fRec->wind;
            }

        }

        $res['currentTempMin'] = $currentTempMin;
        $res['currentTempMax'] = $currentTempMax;
        $res['tempAfter6HourMin'] = $tempAfter6HourMin;
        $res['tempAfter6HourMax'] = $tempAfter6HourMax;
        $res['windAfter2Uour'] = $windAfter2Uour;

        return $res;
    }
}
