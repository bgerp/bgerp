<?php



/**
 * Подравняване на десетични числа, според зададени в типа type_Doubleна минималния и максималния брой цифри след запетаята
 *
 * Този плъгин е предназначен за прикачане към core_Mvc (или неговите наследници).
 * Инспектира `double` полетата на приемника си и ги форматира вербалните им стойности така,
 * че броят на десетичните цифри да е между предварително зададени минимална и максимална
 * стойност, като при нужда допълва с нули или прави закръгляване (чрез @see round()).
 * При все това, плъгина се грижи броят на десетичните цифри на всяко поле да е един и същ за
 * всички записи от
 * Тези мин. и макс. стойности се задават като параметри на типа `double`:
 * $this->FLD('fieldname', 'double(minDecimals=2, maxDecimals=4)', ...);
 *
 *
 * @category  ef
 * @package   plg
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link      https://github.com/bgerp/ef/issues/6
 */
class plg_StyleNumbers extends core_Plugin
{
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterPrepareListRows($mvc, $data)
    {
        $recs = &$data->recs;
        $rows = &$data->rows;
        
        // Ако няма никакви записи - нищо не правим
        if(!count($recs)) return;
        
        foreach ($mvc->fields as $name=>$field) {
            if (is_a($field->type, 'type_Double')) {
                foreach ($recs as $i => $rec) {
                	
                    if(core_Math::roundNumber($rec->{$name}) < 0) {
                        $rows[$i]->{$name} = "<font color='red'>" . $rows[$i]->{$name} . "</font>";
                    }
                }
            }
        }
    }
    
}