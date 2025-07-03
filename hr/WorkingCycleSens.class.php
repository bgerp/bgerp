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
 * @title     Работни графици
 * @deprecated
 */
class hr_WorkingCycleSens extends sens2_ProtoDriver
{

    /**
     * Заглавие на драйвера
     */
    public $title = 'Работни графици';


    /**
     * Входове на контролера
     */
    public $inputs = array(
        'workingAfter' => array('caption' => 'Начало след', 'uom' => 'min', 'logPeriod' => 0, 'readPeriod' => 60),
        'nonWorkingAfter' => array('caption' => 'Спиране след', 'uom' => 'min', 'logPeriod' => 0, 'readPeriod' => 60),
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
        $form->FLD('schedule', 'key(mvc=hr_Schedules, select=name, allowEmpty)', 'caption=График, input, mandatory');
    }


    /**
     * Прочитане на входовете
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        $maxDays = 10;
        $resArr = array();
        $now = dt::now();
        $to = dt::addDays($maxDays, $now);
        $Interval = hr_Schedules::getWorkingIntervals($config->schedule, $now, $to, true);

        foreach ($inputs as $input) {
            $resArr[$input] = 0;
            if ($input == 'workingAfter') {
                $resArr[$input] = $maxDays * 24 * 60 * 60;
            }
        }

        if (!$Interval) {

            return $resArr;
        }
        $dArr = $Interval->getDates();
        if (!$dArr[0]) {

            return $resArr;
        }

        $isIn = $Interval->isIn();
        foreach ($inputs as $input) {
            if ($isIn) {
                if ($input == 'workingAfter') {
                    $resArr[$input] = 0;
                }

                if ($input == 'nonWorkingAfter') {
                    $resArr[$input] = intval(dt::secsBetween($dArr[0][1], $now) / 60);
                    $resArr[$input] = abs($resArr[$input]);
                }
            } else {
                if ($input == 'nonWorkingAfter') {
                    $resArr[$input] = 0;
                }

                if ($input == 'workingAfter') {
                    $resArr[$input] = intval(dt::secsBetween($now, $dArr[0][0]) / 60);
                    $resArr[$input] = abs($resArr[$input]);
                }
            }
        }

        return $resArr;
    }
}
