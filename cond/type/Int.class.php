<?php


/**
 * Тип за параметър 'Цяло число'
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Цяло число
 */
class cond_type_Int extends cond_type_abstract_Proto
{
    /**
     * Кой базов тип наследява
     */
    protected $baseType = 'type_Int';


    /**
     * Поле за дефолтна стойност
     */
    protected $defaultField = 'default';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('min', 'int', 'caption=Конкретизиране->Минимум,after=order');
        $fieldset->FLD('max', 'int', 'caption=Конкретизиране->Максимум,after=min');
        $fieldset->FLD('default', 'int', 'caption=Конкретизиране->Стойност по подразбиране,after=max');
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
        $Type = parent::getType($rec, $domainClass, $domainId, $value);
        $params = array();
        
        if (isset($rec->min)) {
            $params['min'] = $rec->min;
        }
        
        if (isset($rec->max)) {
            $params['max'] = $rec->max;
        }
        
        if (countR($params)) {
            $Type = cls::get($Type, array('params' => $params));
        }
        
        return $Type;
    }
}
