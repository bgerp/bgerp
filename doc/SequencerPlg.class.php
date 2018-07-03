<?php

/**
 * Клас 'doc_SequencerPlg' - Числови последователности използвани в номерации на документи
 *
 * Плъгина се прикача към документи. Попълва им пореден номер при активиране.
 *
 * $mvc->sequencerField - име на полето, съдържащо пореден номер (незадължително, по подразбиране number)
 * $mvc->sequencerMin - начало на диапазона (незадължително, по подразбиране 1)
 * $mvc->sequencerMax - край на диапазона (незадължително, по подразбиране PHP_INT_MAX)
 *
 * @category  bgerp
 * @package   doc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_SequencerPlg extends core_Plugin
{
    /**
     * Добавя поле за номерация, ако няма
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription($mvc)
    {
        $seqField = static::getSeqField($mvc);
        
        // Ако липсва, добавяме поле за номерация
        if (!$mvc->fields[$seqField]) {
            $mvc->FLD($seqField, 'int(min=0)', 'caption=Номер');
        }
    }
    
    
    /**
     *
     * Генерира следващ номер от последователността
     *
     * @param core_Mvc   $mvc
     * @param int        $number
     * @param int        $min
     * @param int        $max
     * @param core_Query $query
     */
    public static function on_AfterGetNextNumber(core_Mvc $mvc, &$number)
    {
        if (!isset($query)) {
            $query = $mvc::getQuery();
        }
        
        $seqField = static::getSeqField($mvc);
        
        setIfNot($min, $mvc->sequencerMin, 1);
        setIfNot($max, $mvc->sequencerMax, PHP_INT_MAX);
        
        $query->where("#{$seqField} IS NOT NULL");
        
        if ($min = intval($min)) {
            $query->where("#{$seqField} >= {$min}");
        }
        if (isset($max) && ($max = intval($max))) {
            $query->where("#{$seqField} < {$max}");
        }
        
        $query->orderBy($seqField, 'DESC');
        $query->limit(1);
        
        $rec = $query->fetch();
        
        if ($rec) {
            $number = $rec->{$seqField} + 1;
        } else {
            $number = $min;
        }

        expect($number < $max, 'Последователността ' . $mvc->className . '::' . $seqField . ' е изчерпана');
    }
    
    
    /**
     * При Създаване на документ слага пореден номер.
     *
     * @param core_Mvc $mvc
     * @param int      $id
     * @param stdClass $rec
     */
    public static function on_BeforeSave($mvc, &$id, $rec)
    {
        $seqField = static::getSeqField($mvc);
        if (!$rec->id) {
            if (empty($rec->{$seqField})) {
                $rec->{$seqField} = $mvc::getNextNumber();
            }
        }
    }
    
    
    /**
     * Името на полето за последователно номериране на документа.
     *
     * Името се взема от полето 'sequencerField' на модела-домакин. Ако не е зададено - използва
     * се константата 'number'
     *
     * @param  core_Mvc $mvc
     * @return string
     */
    protected static function getSeqField($mvc)
    {
        return !empty($mvc->sequencerField) ? $mvc->sequencerField : 'number';
    }
}
