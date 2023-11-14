<?php


/**
 * Тип за параметър 'Шаблони на документи'
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Параметър "Шаблони на документи"
 */
class cond_type_DocTemplate extends cond_type_abstract_Proto
{
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('class', 'varchar', 'caption=Конкретизиране->Документ,mandatory,silent');
        $fieldset->FLD('lang', 'enum(bg=БГ,en=ЕН)', 'caption=Конкретизиране->Език,mandatory,silent');
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
        $Type = core_Type::getByName('key(mvc=doc_TplManager,select=name,allowEmpty)');

        $options = doc_TplManager::getTemplates($rec->class, $rec->lang);
        $Type->options = $options;

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
        return doc_TplManager::getHyperlink($value, TRUE);
    }
}
