<?php


/**
 * Клас 'visualcrossing_Plugin'
 *
 * Добавя към групираната дата, икони за времето
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
class visualcrossing_Plugin extends core_Plugin
{
    /**
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $date
     */
    public function on_AfterPrepareGroupDate($mvc, &$res, $date)
    {
        $now = dt::now(false);

        if (dt::addDays(5, $now, false) < $date) {

            return;
        }

        if (dt::addDays(-5, $now, false) >= $date) {

            return;
        }

        $forRec = visualcrossing_Forecasts::getForecast($date);

        if ($forRec) {
            $iconUrl = 'https://www.visualcrossing.com/img/' . $forRec->icon . '.svg';

            $min = round($forRec->low, 1);
            $max = round($forRec->high, 1);

            $res->day .= "<div style='text-align: right; float:right;font-size:0.85em;color:#999;width: 110px;'><span style=\"color:blue\">{$min}</span>&#126;<span style=\"color:red\">{$max}</span>&#8451;&nbsp;<img style='display: inline-block;max-height: 20px;max-width: 28px; float:right;position:relative;top:-2px;' src=\"" . $iconUrl . '"></div>';
        }
    }
}
