<?php


/**
 * Клас 'plg_Settings' - Плъгин за настройка на обекти
 * Позволява да се избере обект от същия модел, чиито полета определени за настройки ($settingFields),
 * ако не са предефинирани в наследника да ползват тези на бащата.
 *
 *
 * @category  bgerp
 * @package   plg
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class plg_Settings extends core_Plugin
{
    /**
     * След инициализирането на модела
     *
     * @param core_Mvc $mvc
     * @param core_Mvc $data
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->FLD('prototypeId', "key(mvc={$mvc->className},allowEmpty)", 'caption=Прототип,silent,removeAndRefreshForm,after=id');
    
        $settingFields = arr::make($mvc->settingFields, true);
        foreach ($settingFields as $field) {
            $mvc->setField($field, 'settings');
            
            if ($mvc->getFieldType($field) instanceof type_Enum) {
                $options = $mvc->getFieldType($field)->options;
                $newOptions = ',' . arr::fromArray($options);
                $mvc->setFieldType($field, "enum({$newOptions})");
            }
        }
    }
    
    
    /**
     * Връща позволените протоипи за опции
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    public static function on_AftergetPrototypeOptions($mvc, &$res)
    {
        if (!countR($res)) {
            $query = $mvc->getQuery();
            $query->where('#prototypeId IS NULL');
            if ($mvc->getField('state', false)) {
                $query->where("#state != 'rejected' AND #state != 'closed'");
            }
            
            $res = array();
            while ($rec = $query->fetch()) {
                $res[$rec->id] = $mvc->getTitleById($rec, false);
            }
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
       
        
        $prototypeOptions = $mvc->getPrototypeOptions();
        unset($prototypeOptions[$rec->id]);
        $settingFields = $mvc->selectFields('#settings');
        $settingFields = array_keys($settingFields);
       
        if (!countR($prototypeOptions)) {
            $form->setField('prototypeId', 'input=none');
        }
        
        // Ако има избран прототип настройваемите полета могат да бъдат празни
        $form->input('prototypeId', 'silent');
        foreach ($settingFields as $field) {
            if (isset($rec->prototypeId)) {
                $form->setParams($field, array('mandatory' => null));
                $form->setFieldTypeParams($field, array('allowEmpty' => 'allowEmpty'));
                $form->setField($field, 'placeholder=Автоматично');
            } else {
                if ($form->getFieldType($field) instanceof type_Enum) {
                    if ($default = $form->getFieldParam($field, 'default')) {
                        $form->setDefault($field, $default);
                    }
                }
            }
        }
    }
    
    
    /**
     * Обработка по вербалното представяне на данните
     */
    public static function on_AfterRecToVerbal(core_Mvc $mvc, &$row, $rec, $fields = array())
    {
        $inherited = new stdClass();
        $settings = $mvc->getSettings($rec, null, $inherited);
        
        foreach ($settings as $field => $value) {
            $rec->{$field} = $value;
            $row->{$field} = $mvc->getVerbal($rec, $field);
            
            if (isset($inherited->{$field}, $row->{$field})) {
                $row->{$field} = ht::createHint($row->{$field}, 'Наследено е от прототипа', 'notice', false);
            }
        }
    }
    
    
    /**
     * Дефолтен метод за
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterGetSettings($mvc, &$res, $rec, $field = null, &$inherited = null)
    {
        if (!isset($res)) {
            $id = (is_object($rec)) ? $rec->id : $rec;
            $clone = clone $mvc->fetch($id);
           
            $res = new stdClass();
            $settingFields = arr::make($mvc->settingFields, true);
            
            if (isset($field)) {
                $settingFields = array();
                $settingFields[$field] = $field;
            }
            
            $inherited = is_object($inherited) ? $inherited : new stdClass();
            
            $protoRec = (isset($clone->prototypeId)) ? $mvc->fetch($clone->prototypeId) : null;
            foreach ($settingFields as $settingField) {
                if (isset($clone->{$settingField})) {
                    $res->{$settingField} = $clone->{$settingField};
                } elseif (is_object($protoRec)) {
                    $res->{$settingField} = $protoRec->{$settingField};
                    $inherited->{$settingField} = $settingField;
                }
            }
            
            if (isset($field)) {
                $res = $res->{$field};
            }
        }
    }
}
