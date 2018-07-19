<?php


/**
 * Тип за параметър 'Цвят'
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Цвят
 */
class cond_type_Color extends cond_type_abstract_Proto
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
        $Type = core_Type::getByName('key2(mvc=cond_Colors,select=name)');
        
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
        $valueVerbal = parent::toVerbal($id, $domainClass, $domainId, $value);
        $valueHex = cond_Colors::fetchField($value, 'hex');
       
        $attr = array('style' => "background-color: {$valueHex} !important;width:15px;height:15px;display:inline-block");
        $colorBox = ht::createElement("span", $attr, null, true);
       
        $res = new core_ET("<span>[#colorBox#] [#name#]</span>");
        $res->append($colorBox, 'colorBox');
        $res->append($valueVerbal, 'name');
        
        return $res;
    }
}