<?php


/**
 * Тип за параметър 'Едноредов текст'
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
 * @title     Едноредов текст
 */
class cond_type_Varchar extends cond_type_abstract_Proto
{
    /**
     * Кой базов тип наследява
     */
    protected $baseType = 'type_Varchar';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('lenght', 'int', 'caption=Конкретизиране->Дължина,before=default');
        $fieldset->FLD('translate', 'enum(no=Не,yes=Да)', 'caption=Конкретизиране->Превод,after=lenght');
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
        
        if (isset($rec->lenght)) {
            $Type = cls::get($Type, array('params' => array('size' => $rec->lenght)));
        }
        
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
        // Ако има тип, вербалното представяне според него
        $Type = $this->getType($id, $domainClass, $domainId, $value);
        if ($Type) {
            $value = trim($value);
            if($this->driverRec->translate == 'yes' || (strpos($value, '||') != false)){
                $value = tr($value);
            }
            
            return $Type->toVerbal($value);
        }
        
        return false;
    }
}
