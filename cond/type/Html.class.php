<?php


/**
 * Тип за параметър 'HTML'
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
 * @title     HTML
 */
class cond_type_Html extends cond_type_abstract_Proto
{
    /**
     * Кой базов тип наследява
     */
    protected $baseType = 'type_Html';


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
        $fieldset->FLD('rows', 'int(min=1)', 'caption=Конкретизиране->Редове,before=default');
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
        $rows = isset($rec->rows) ? $rec->rows : 2;
        $type = core_Type::getByName("html(tinyEditor=no,rows={$rows})");

        return $type;
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
        $Type = parent::getType($rec, $domainClass, $domainId, $value);

        if ($Type) {
            if(Mode::is('text', 'plain')){
                $value = strip_tags($value);
            }

            return $Type->toVerbal(trim($value));
        }

        return false;
    }
}
