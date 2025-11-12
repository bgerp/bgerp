<?php


/**
 * Тип за параметър 'Запис от модел'
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
 * @title     Запис от модел
 */
class cond_type_Key extends cond_type_abstract_Proto
{
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('class', 'varchar', 'caption=Конкретизиране->Клас,mandatory,silent,removeAndRefreshForm=select');
        $fieldset->FLD('select', 'varchar', 'caption=Конкретизиране->Поле за избор,after=mvc');
        $fieldset->FLD('selectBg', 'varchar', 'caption=Конкретизиране->Поле за избор (БГ),after=select');
        $fieldset->FLD('displayVerbal', 'varchar', 'caption=Конкретизиране->Поле за показване,after=selectBg');

    }


    /**
     * Модифицира формата за добавяне
     *
     * @param cond_type_abstract_Proto $Driver
     * @param embed_Manager $Embedder
     * @param $data
     * @return void
     */
    public static function modifyEditForm(cond_type_abstract_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = &$data->form;
        $managerClasses = core_Classes::getOptionsByInterface('core_ManagerIntf', 'title');
        $options = array();
        foreach ($managerClasses as $classId => $classTitle){
            if($className = cls::get($classId)->className){
                $options[$className] = $classTitle;
            }
        }

        $form->setOptions('class', array('' => '') + $options);

        if(isset($form->rec->class)){
            $fieldsOptions = arr::make(array_keys(cls::get($form->rec->class)->selectFields()), true);
            $form->setOptions('select', array('' => '') + $fieldsOptions);
        }
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     */
    protected static function on_AfterPrepareEditForm(cond_type_abstract_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        static::modifyEditForm($Driver, $Embedder, $data);

        if(isset($data->form->rec->id)){
            if(cat_products_Params::fetch("#paramId = {$data->form->rec->id}")){
                $data->form->setReadOnly('class');
            }
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

        // Ако е посочен модел с големи записи, се показва с key2
        if(in_array($rec->class, array('crm_Companies', 'crm_Persons', 'cat_Products', 'doc_Folders'))){
            $Type = core_Type::getByName("key2(mvc={$rec->class},select={$select})");
        } else {
            $typeParams = "mvc={$rec->class}";
            $typeParams .= ",select={$select}";
            if($rec->selectBg){
                $typeParams .= ",selectBg={$rec->selectBg}";
            }

            // Само незатворените записи
            $Type = core_Type::getByName("key({$typeParams})");
            $Class = cls::get($rec->class);
            if($Class->getField('state', false)){
                $options = cls::get($rec->class)->makeArray4Select($select, "#state != 'rejected' AND #state != 'closed'");
                if(!array_key_exists($value, $options)){
                    $options[$value] = $Class->getVerbal($value, $select);
                }
                $Type->options = $options;
            }
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
        $Class = cls::get($rec->class);
        $select = $rec->select;
        if(isset($rec->displayVerbal)){
            $select = $rec->displayVerbal;
        } else {
            if ($rec->selectBg && core_Lg::getCurrent() == 'bg') {
                $select = $rec->selectBg;
            }
        }

        $verbal = $Class->getVerbal($value, $select);

        // Обръщане в линк, ако може
        if(!Mode::isReadOnly()){
            if($Class instanceof core_Master){
                $singleUrlArray = $Class->getSingleUrlArray($value);

                if(countR($singleUrlArray)) return ht::createLink($verbal, $singleUrlArray);
            }
        }

        return $verbal;
    }
}