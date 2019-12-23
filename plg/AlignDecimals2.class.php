<?php


/**
 * Плъгин подравняващ броя на десетичните символи, с помоща на новите функции в core_Math
 *
 *
 * @category  bgerp
 * @package   plg
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class plg_AlignDecimals2 extends core_Plugin
{
    /**
     * Метод за подравняване на десетичните числа
     *
     * @param core_FieldSet $mvc  - модел
     * @param array         $recs - записите във вътрешен вид
     * @param array         $rows - записите във вербален вид
     *
     * @return void
     */
    public static function alignDecimals(core_FieldSet $mvc, $recs, &$rows)
    {
        // Намираме всички полета, които се показват от типа Double
        $decFields = array();
        foreach ($mvc->fields as $name => $field) {
            if ($field->type instanceof type_Double && $filed->input != 'none' && !($field->type instanceof type_Percent)) {
                $decFields[] = $name;
            }
        }
        
        if (!arr::count($decFields) || !arr::count($recs)) {
            
            return;
        }
        
        //$decFields = array(5 => 'packPrice');
        
        // тука определяме най-дългата дробна част, без да записваме числото
        foreach ($recs as $id => $rec) {
            foreach ($decFields as $fName) {
                core_Math::roundNumber($rec->{$fName}, ${"{$fName}FracLen"});
            }
        }
        
        // Закръгляме сумата и я обръщаме във вербален вид
        foreach ($recs as $id => &$rec) {
            foreach ($decFields as $fName) {
                if (isset($recs[$id]->{$fName}) && !is_object($rows[$id]->{$fName}) && !is_null($rows[$id]->{$fName})) {
                    $Type = clone $mvc->fields[$fName]->type;
                    
                    if(!$Type->params['decimals']) {
                        $max = $Type->params['maxDecimals'] ? $Type->params['maxDecimals'] : 5;
                        $min = $Type->params['minDecimals'] ? $Type->params['minDecimals'] : 0;
                        $Type->params['decimals'] = min($max, max($min, ${"{$fName}FracLen"}));
                    }
                    
                    if(strpos($rows[$id]->{$fName}, $mvc->getFieldType($fName)->toVerbal($rec->{$fName})) !== FALSE) {
                        $rows[$id]->{$fName} = str_replace($mvc->getFieldType($fName)->toVerbal($rec->{$fName}), $Type->toVerbal($rec->{$fName}), $rows[$id]->{$fName});
                    } else {
                        $rows[$id]->{$fName} = $Type->toVerbal($rec->{$fName});
                    }
                }
            }
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $recs = &$data->recs;
        $rows = &$data->rows;
        
        if (!count($recs)) {
            
            return;
        }
        
        self::alignDecimals($data->listTableMvc, $recs, $rows);
    }
}
