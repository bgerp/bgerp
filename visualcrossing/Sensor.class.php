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
        'currentTemp' => array('caption' => 'Текуща температура', 'uom' => 'C'),
        'tempAfter6Hour' => array('caption' => 'Температура след 6ч.', 'uom' => 'C'),
        'windAfter2Uour' => array('caption' => 'Вятър след 2ч.', 'uom' => 'Km/h'),

    );

    /**
     * Интерфейси, поддържани от всички наследници
     */
    public $interfaces = 'sens2_ControllerIntf';


    /**
     * Описания на изходите
     */
    public $outputs = array(
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
    }


    /**
     * Прочита стойностите от сензорните входове
     *
     * @see  sens2_ControllerIntf
     *
     * @param array $inputs
     * @param array $config
     * @param array $persistentState
     *
     * @return mixed
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        $res = array();


        $time = dt::mysql2timestamp(dt::now());
        $h = date('G', $time);
        //$h = $h .'-' . ($h+1);
        //$h = $h+2;

//        if(date('I', $time ) == 1 && date('I', $time + 3600) == 0) {
//            $h = date('G', $time - 3600) . '-' . date('G', $time) . 'a';
//        }
     //   bp($time,$h);
        $date = dt::now(false);

        $query =visualcrossing_Forecasts::getQuery();
        $query->where("#date = '{$date}'");
        //$query->where("#time = '{$h}'");
bp(visualcrossing_Forecasts::getForecast($date));
        $forRec = $query->fetchAll();
        foreach ($forRec as $fRec => $val ){
            if(!$val->time ){bp($val);
                $currentTemp = $val->low;
            }

        }


        $res['currentTemp'] = '12345';
        $res['tempAfter6Hour'] = '98765';
        $res['windAfter2Uour'] = '23432';

        return $res;
    }
}
