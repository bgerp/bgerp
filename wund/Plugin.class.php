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
        if(dt::addDays(3) < $date) return;

        $forRec = wund_Forecasts::getForecast($date);

        if($forRec) {
            
            $thumb = new thumb_Img($forRec->iconUrl, 20, 20, 'url');
            $iconUrl = $thumb->getUrl();
            
            $res->day .= "<div style='float:right;font-size:0.85em;color:#999;'><span style=\"color:blue\">{$forRec->low}</span>&#126;<span style=\"color:red\">{$forRec->high}</span>&#8451;&nbsp;<img height=20 style='float:right;position:relative;top:-2px;' src=\"" . $iconUrl . "\"></div>";
        }
    }
}