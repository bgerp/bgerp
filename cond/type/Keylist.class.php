<?php


/**
 * Тип за параметър 'Множество от записи'
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Множество от записи
 */
class cond_type_Keylist extends cond_type_abstract_Proto
{
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('class', 'varchar', 'caption=Конкретизиране->Клас,after=default,mandatory,silent,removeAndRefreshForm=select');
        $fieldset->FLD('select', 'varchar', 'caption=Конкретизиране->Поле за избор,after=mvc');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     */
    protected static function on_AfterPrepareEditForm(cond_type_abstract_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $managerClasses = core_Classes::getOptionsByInterface('core_ManagerIntf', 'title');
        $options = array();
        foreach ($managerClasses as $classId => $classTitle){
            if($className = cls::get($classId)->className){
                $options[$className] = $classTitle;
            }
        }

        $form = &$data->form;
        $form->setOptions('class', array('' => '') + $options);

        if(isset($form->rec->class)){
            $fieldsOptions = arr::make(array_keys(cls::get($form->rec->class)->selectFields()), true);
            $form->setOptions('select', array('' => '') + $fieldsOptions);
        }
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
        $select = !empty($rec->select) ? $rec->select : 'id';
        $Type = core_Type::getByName("keylist(mvc={$rec->class},select={$select})");

        return $Type;
    }
}