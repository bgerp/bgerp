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
     * Как се индексира стойността за филтриране (@see cat_products_ParamIndex)
     */
    protected $indexKind = 'text';


    /**
     * Кой базов тип наследява
     */
    protected $baseType = 'type_Varchar';


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
        $fieldset->FLD('lenght', 'int', 'caption=Конкретизиране->Дължина,before=order');
        $fieldset->FLD('translate', 'enum(no=Не,yes=Да)', 'caption=Конкретизиране->Превод,after=lenght');
        $fieldset->FLD('default', 'varchar(nullIfEmpty)', 'caption=Конкретизиране->Стойност по подразбиране,after=translate');
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
     * @param stdClass $rec
     * @param mixed    $domainClass - клас на домейна
     * @param mixed    $domainId    - ид на домейна
     * @param string   $value
     *
     * @return mixed
     */
    public function toVerbal($rec, $domainClass, $domainId, $value)
    {
        // Ако има тип, вербалното представяне според него
        $Type = $this->getType($rec, $domainClass, $domainId, $value);
        if ($Type) {
            $value = trim($value);
            if($this->driverRec->translate == 'yes' || (strpos($value, '||') != false)){
                $value = tr($value);
            }
            
            return $Type->toVerbal($value);
        }
        
        return false;
    }


    /**
     * Свободният текст се индексира без разлика в регистъра („КотКа“ = „Котка“)
     */
    protected function getIndexKey($verbal)
    {
        return str::mbUcfirst(mb_strtolower($verbal));
    }


    /**
     * Текст, който е число, се индексира и като число, за да се търси в диапазон
     */
    public function getIndexValues($rec, $domainClass, $domainId, $value, $langs)
    {
        $res = parent::getIndexValues($rec, $domainClass, $domainId, $value, $langs);

        $num = str_replace(',', '.', trim((string) $value));
        if (preg_match('/^-?\d+(\.\d+)?$/', $num)) {
            foreach ($res as $row) {
                $row->valueNum = (float) $num;
            }
        }

        return $res;
    }
}
