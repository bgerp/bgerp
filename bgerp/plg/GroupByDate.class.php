<?php



/**
 * Плъгин за групиране на редовете от листовия изглед, според датата
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Групиране на редовете от листовия изглед, според датата
 */
class bgerp_plg_GroupByDate extends core_Plugin
{
    
    /**
     * Изпълнява се след подготовката на редовете за листовия изглед
     */
    static function on_AfterPrepareListRows($mvc, &$res, $data)
    {   

        setIfNot($data->groupByDateField, $mvc->groupByDateField);

        if(!($field = $data->groupByDateField)) return;

        $columns = count(arr::make($data->listFields));

        if(!count($data->recs)) return;

        $exDate = '0000-00-00';
        
        $format = Mode::is('screenMode', 'narrow') ? 'd-M-year, D': 'd F-YEAR, l';

        foreach($data->recs as $id => $rec) {

            list($d, $t) = explode(' ', $rec->{$field});

            if($d != $exDate) {
                
                $res = new stdClass();

                $res->day = dt::getRelativeDayName($rec->{$field});

                if($res->day) $res->day .= ', ';
                
                if($rec->{$field}) {
                    $res->day .= dt::mysql2verbal($rec->{$field}, $format);
                } else {
                    $res->day = tr('Без дата');
                }

                $res->color = dt::getColorByTime(time() - dt::mysql2timestamp($rec->{$field}));
                
                $mvc->invoke('AfterPrepareGroupDate', array(&$res, $d));
 
                $rows[$id . ' '] = ht::createElement('tr', 
                    $data->rows[$id]->ROW_ATTR, 
                    new ET("<td style='padding-top:9px;padding-left:5px;' colspan='{$columns}'><i style='color:#{$res->color};'>" . $res->day . "</i></td>")); 
                    
                $exDate = $d;
            }

            $rows[$id] = $data->rows[$id];
                        
            if(trim($t) && ($t != '00:00:00')) {
                list($h, $m, $s) = explode(':', $t);
                $color = dt::getColorByTime(time() - dt::mysql2timestamp($rec->{$field}));
                $rows[$id]->{$field} = "<span style='color:#{$color}'>{$h}:{$m}</span>";
            } else {
                $rows[$id]->{$field} = '';
            }
        }

        $data->rows = $rows;
    }

}