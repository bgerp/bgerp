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
class plg_AlignDecimals extends core_Plugin
{
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    function on_AfterPrepareListRows($mvc, $data)
    {
        $recs = &$data->recs;
        $rows = &$data->rows;
        
        // Ако няма никакви записи - нищо не правим
        if(!count($recs)) return;
        
        foreach ($mvc->fields as $name=>$field) {
            if (is_a($field->type, 'type_Double')) {
                if ($field->type->params['decimals']) {
                    // Пропускаме полета, които имат зададен точен брой цифри след запетаята
                    continue;
                }
                
                setIfNot($field->type->params['minDecimals'], 0);
                setIfNot($field->type->params['maxDecimals'], 6);
                
                // Първи пас по стойностите - определяне дължината на най-дългата дробна част.
                $maxDecimals = $this->calcMaxFracLen($name, $recs, $field->type->params['maxDecimals']);
                
                // Изчисляваме "оптималната" дължина на дробните части на стойностите: това е 
                // най-малката дължина, която е не по-дълга от най-дългата, не по-къса от 
                // най-късата дробна част и да попада в границите, зададени изначално в типа.
                $optDecimals = min(
                    $field->type->params['maxDecimals'],
                    max($field->type->params['minDecimals'], $maxDecimals)
                );
                
                // Втори пас по стойностите - преформатиране според определената в $digits
                // дължина на дробната част
                $field->type->params['decimals'] = $optDecimals;
                
                foreach ($recs as $i=>$rec) {
                    $rows[$i]->{$name} = $field->type->toVerbal($rec->{$name});
                }
            }
        }
    }
    
    
    /**
     * Изчислява дължината на най-дългата дробна част на (double) поле от масив със записи.
     *
     * @param string $fieldName име на полето, което инспектираме
     * @param array $recs масив от записи (stdClass)
     * @param int $stop горна граница; дори да има стойности с дължина на дробната част по-дълги
     * от $stop, това не е съществено. Използва се за оптимизация.
     */
    private function calcMaxFracLen($fieldName, $recs, $stop)
    {
        $result = 0;
        
        foreach ($recs as $rec) {
            $fracLen = $this->getFractionLen($rec->{$fieldName});
            
            if ($fracLen > $result) {
                $result = $fracLen;
            }
            
            if ($result >= $stop) {
                break;
            }
        }
        
        return $result;
    }
    
    
    /**
     * Дължината на дробната част на число.
     *
     * @param float $number
     */
    private function getFractionLen($number)
    {
        list($floor, $frac) = explode('.', (string)$number);
        
        return strlen($frac);
    }
}