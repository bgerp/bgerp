<?php



/**
 * Клас 'wund_Plugin'
 *
 * Добавя към групираната дата, икони за времето
 *
 * @category  vendors
 * @package   recently
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class wund_Plugin extends core_Plugin
{
    
    function on_AfterPrepareGroupDate($mvc, &$res, $date) 
    { 
        $forRec = wund_Forecasts::getForecast($date);

        if($forRec) {

            $res->day .= "<div style='float:right;font-size:0.85em;color:#999;'><font color='blue'>{$forRec->low}</font>&#126;<font color='red'>{$forRec->high}</font>&#8451;&nbsp;<img height=20 style='float:right' src=\"" . $forRec->iconUrl . "\"></div>";
        }
    }
}