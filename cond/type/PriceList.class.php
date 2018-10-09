<?php


/**
 * Тип за параметър 'Ценова политика'
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Ценова политика
 */
class cond_type_PriceList extends cond_type_abstract_Proto
{
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
        $Type = core_Type::getByName('key(mvc=price_Lists,select=title,allowEmpty)');
        
        return $Type;
    }
    
    
    /**
     * Вербално представяне на стойноста
     *
     * @param mixed $class
     * @param int   $id
     *
     * @return mixed
     */
    public function toVerbal($id, $domainClass, $domainId, $value)
    {
        return price_Lists::getHyperlink($value, TRUE);
    }
}
