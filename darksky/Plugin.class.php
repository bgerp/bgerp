<?php


/**
 * Клас 'darksky_Plugin'
 *
 * Добавя към групираната дата, икони за времето
 *
 * @category  bgerp
 * @package   paixu
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class darksky_Plugin extends core_Plugin
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
        
        $forRec = darksky_Forecasts::getForecast($date);
        
        if ($forRec) {
            $iconUrl = 'https://darksky.net/images/weather-icons/' . $forRec->icon . '.png';
            
            $min = round($forRec->low, 1);
            $max = round($forRec->high, 1);
            
            $res->day .= "<div style='float:right;font-size:0.85em;color:#999;'><span style=\"color:blue\">{$min}</span>&#126;<span style=\"color:red\">{$max}</span>&#8451;&nbsp;<img height=20 style='float:right;position:relative;top:-2px;' src=\"" . $iconUrl . '"></div>';
        }
    }
}
