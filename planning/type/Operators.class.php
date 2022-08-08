<?php


/**
 * Клас  'planning_type_Operators' - Тип за задаване на оператори
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_type_Operators extends type_Keylist
{
    /**
     * Инициализиране на обекта
     */
    public function init($params = array())
    {
        $params['params']['mvc'] = 'crm_Persons';

        parent::init($params);
    }


    /**
     * Конвертира стойността от вербална към (int)
     *
     * @param mixed $value
     *
     * @see core_Type::fromVerbal_()
     *
     * @return mixed
     */
    public function fromVerbal_($value)
    {
        if(!empty($value)){
            $parsedValueObj = planning_Hr::parseStringToKeylist($value);
            if(!empty($parsedValueObj->error)){
                $this->error = $parsedValueObj->error;
                return false;
            }

            $value = $parsedValueObj->keylist;
            if(!$value) return null;
        }

        return $value;
    }


    /**
     * Рендира HTML инпут поле
     *
     * @param string     $name
     * @param string     $value
     * @param array|NULL $attr
     *
     * @see core_Type::renderInput_()
     *
     * @return core_ET
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        if(!$this->error){
           $value = !empty($value) ? planning_Hr::keylistToParsableString($value): null;
        }

        // Сигнализиране на потребителя, ако въведе по-дълъг текст от допустимото
        setIfNot($size, $this->params['size'], $this->params[0], $this->dbFieldLen);
        $attr['data-role'] = 'list';
        if ($this->params['readonly']) {
            $attr['readonly'] = 'readonly';
        }

        $proxy = cls::get('type_Varchar');
        $proxy->suggestions = $this->suggestions;
        $tpl = $proxy->createInput($name, $value, $attr);

        return $tpl;
    }


    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    public function toVerbal_($value)
    {
        $res = null;
        if(!empty($value)){
            $res = planning_Hr::getPersonsCodesArr($value);
            $res = implode(', ', $res);
        }

        return $res;
    }
}