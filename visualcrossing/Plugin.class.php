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
    $isToday = ($date == date('Y-m-d'));

    $forRec = visualcrossing_Forecasts::getForecast($date);

    if ($forRec) {
        $thumb = new thumb_Img(array(getFullPath('visualcrossing/icons/' . $forRec->icon . '.png'), 28, 20, 'path', 'default' => 'img/32/info-gray.png'));
        $imgUrl = $thumb->getUrl('deferred');

        $min = round($forRec->low, 1);
        $max = round($forRec->high, 1);
        
        $pm25 = $forRec->pm25;
        $uvindex = $forRec->uvindex;
        
        if ($isToday) {
            $currentHour = (int)date('G');
            $forRecCurr = visualcrossing_Forecasts::getForecast($date, $currentHour);

            $pm25 = ($forRecCurr && isset($forRecCurr->pm25)) ? $forRecCurr->pm25 : null;
            $uvindex = ($forRecCurr && isset($forRecCurr->uvindex)) ? $forRecCurr->uvindex : null;
        }

        $pillStyle = $isToday
            ? 'border: 1px solid #6d6d6d; border-radius: 50px; padding: 1px 10px; white-space: nowrap;'
            : 'white-space: nowrap;';


        $pm25Html = $pm25 !== null ? "<span style='color: blue;' title='PM2.5'>PM2.5: {$pm25}</span>" : "";
        $uvHtml = $uvindex !== null ? "<span style='margin-left: 6px; color: red;' title='UV Index'>UV: {$uvindex}</span>" : "";

//@todo - да изчистя кода.
//@todo - да кача промените от дев бранча в нов бранч пр. visualcrossing или visualcrossingDavid бранч.

        $res->day .= "
<div style='float:right; text-align:right; font-size:0.85em; color:#999; width:160px; display:flex; flex-direction:column; align-items:flex-end;'>

    <div style='display:flex; align-items:center; gap:4px;'>
        <span>
            <span style=\"color:blue\">{$min}</span>&#126;<span style=\"color:red\">{$max}</span>
        </span>
        <img src='{$imgUrl}' style='max-height:20px; max-width:28px;'>
    </div>

    <div style='{$pillStyle}'>
        {$pm25Html}{$uvHtml}
    </div>

</div>";
    }
}
}
