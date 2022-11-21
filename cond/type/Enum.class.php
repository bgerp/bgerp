<?php


/**
 * Тип за параметър 'Избор'
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Избор
 */
class cond_type_Enum extends cond_type_abstract_Proto
{
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('options', 'text', 'caption=Конкретизиране->Опции,before=default,mandatory');
        $fieldset->FLD('orderBy', 'enum(no=Без,ascKey=Възходящ [ключ],ascVal=Възходящо [стойност],descKey=Низходящо [ключ], descVal=Низходящо [стойност])', 'caption=Конкретизиране->Подредба,mandatory');
        $fieldset->FLD('maxRadio', 'int(min=0,max=50)', 'caption=Конкретизиране->Радио бутон,mandatory');
        $fieldset->FLD('columns', 'int(Min=0)', 'caption=Конкретизиране->Радио бутон (колони),placeholder=2');
    }
    
    
    /**
     * Връща инстанция на типа
     *
     * @param stdClass    $rec         - запис на параметъра
     * @param mixed       $domainClass - клас на домейна
     * @param mixed       $domainId    - ид на домейна
     * @param NULL|string $value       - стойност
     *
     * @return core_Type - готовия тип
     */
    public function getType($rec, $domainClass = null, $domainId = null, $value = null)
    {
        $Type = cls::get('type_Enum');
        $Type->options = static::text2options($rec->options);
        
        // Ако има подадена стойност и тя не е в опциите, добавя се
        if (isset($value)) {
            $value = trim($value);
            if (!array_key_exists($value, $Type->options)) {
                $Type->options[$value] = $value;
            }
        }

        $orderBy = isset($this->driverRec->orderBy) ? $this->driverRec->orderBy : 'no';
        switch($orderBy){
            case 'ascKey':
                ksort($Type->options, SORT_NATURAL);
                break;
            case 'ascVal':
                asort($Type->options, SORT_NATURAL);
                break;
            case 'descKey':
                krsort($Type->options, SORT_NATURAL);
                break;
            case 'descVal':
                arsort($Type->options, SORT_NATURAL);
                break;
            default:
                break;
        }

        $maxRadio = isset($this->driverRec->maxRadio) ? $this->driverRec->maxRadio : 20;
        $columns = isset($this->driverRec->columns) ? $this->driverRec->columns : 2;
        $Type->params['maxRadio'] = $maxRadio;
        $Type->params['columns'] = $columns;

        return $Type;
    }
}
