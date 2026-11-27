<?php


/**
 * Клас  'planning_type_Operators' - Тип за задаване на оператори в производството
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
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
        // Типа в твърдо с ключове към Лицата във визитника
        $params['params']['mvc'] = 'crm_Persons';
        parent::init($params);
    }


    /**
     * Преобразува операторски кодове в keylist от лица. Структурираният вход
     * ['operatorRefs' => [...]] приема и ID-та, които трябва да имат оператор.
     *
     * @param mixed $value
     * @see core_Type::fromVerbal_()
     * @return mixed
     */
    public function fromVerbal_($value)
    {
        if (is_array($value) && array_key_exists('operatorRefs', $value)) {
            $value = $this->codesFromReferences($value['operatorRefs']);
            if ($value === false) {
                return false;
            }
        }

        if(!empty($value)){
            if(is_array($value)){
                $value = implode(',', $value);
            }
            // Опит за парсиране на на стойноста към кейлист
            $parsedValueObj = planning_Hr::parseStringToKeylist($value);
            if(!empty($parsedValueObj->error)){

                // Ако са възникнали грешки - задават се
                $this->error = $parsedValueObj->error;
                return false;
            }

            // Ако е нямало грешки, в базата ще се запише кейлист от ид-та към лицата
            $value = $parsedValueObj->keylist;
            if(!$value) return null;
        }

        return $value;
    }


    /**
     * Преобразува подадените ID-та на лица в операторски кодове за обичайната проверка на формата.
     * Списъкът може да съдържа и текстови кодове. При липсващ оператор отказва целия списък.
     *
     * @param array $refs
     * @return string|false
     */
    protected function codesFromReferences($refs)
    {
        if (!is_array($refs)) {
            $this->error = 'Очаква се списък с оператори';
            return false;
        }
        $codes = array();
        foreach ($refs as $ref) {
            if (!is_int($ref) && !is_string($ref)) {
                $this->error = 'Невалидна стойност за оператор';
                return false;
            }
            if (is_int($ref) || ctype_digit(trim($ref))) {
                $personId = trim((string) $ref);
                $code = planning_Hr::fetchField(array("#personId = '[#1#]'", $personId), 'code');
                if ($code === null || $code === false || $code === '') {
                    $this->error = 'Няма оператор за лице с ID|* ' . $personId;
                    return false;
                }
                $codes[] = $code;
            } else {
                $codes[] = trim($ref);
            }
        }

        return implode(',', $codes);
    }


    /**
     * Рендира HTML инпут поле
     *
     * @param string     $name
     * @param string     $value
     * @param array|NULL $attr
     * @return core_ET
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        // Задаване на атрибут при избор на съджешчъни да се допълват към инпута, разделени със запетая
        $attr['data-role'] = 'list';

        if(empty($this->error)){
            // Обръщане на кейлиста в стринг за парсиране
            $value = !empty($value) ? planning_Hr::keylistToParsableString($value): null;
        }

        // Сигнализиране на потребителя, ако въведе по-дълъг текст от допустимото
        $size = $this->params['size'] ?? $this->params[0] ?? $this->dbFieldLen ?? null;
        if (!empty($this->params['readonly'])) {
            $attr['readonly'] = 'readonly';
        }

        // Ще се показва, като варчар с предложения
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
