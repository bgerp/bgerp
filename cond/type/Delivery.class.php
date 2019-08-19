<?php


/**
 * Тип за параметър 'Условие на доставка'
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
 * @title     Условие на доставка
 */
class cond_type_Delivery extends cond_type_abstract_Proto
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
        $Type = core_Type::getByName('key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)');
        
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
        $code = cond_DeliveryTerms::fetchField($value, 'codeName');
        $code = ht::createLink($code, cond_DeliveryTerms::getSingleUrlArray($value));
        
        return $code;
    }
}
