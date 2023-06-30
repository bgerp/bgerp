<?php


/**
 * Клас  'bgerp_type_CustomFilter' - Тип за задаване на кустом филтър
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_type_CustomFilter extends type_Varchar
{


    /**
     * Получава дата от трите входни стойности
     */
    public function fromVerbal($value)
    {
        if (empty($value)) return;
        unset($value['select2']);
        $value = arr::make($value, true);
        if(!countR($value)) return;

        $query = bgerp_Filters::getQuery();
        $query->where(self::getClassesWhereClause($this->params['classes']));
        $query->in('id', $value);
        $names = arr::extractValuesFromArray($query->fetchAll(), 'name');

        return implode(',', $names);
    }


    /**
     * Връща 'WHERE' клауза за филтър по класове
     *
     * @param mixed $classes
     * @return string $where
     */
    public static function getClassesWhereClause($classes)
    {
        $where = "";
        $classesArr = is_array($classes) ? $classes : explode('|', $classes);
        if(countR($classesArr)){
            foreach ($classesArr as $class){
                $classId = cls::get($class)->getClassId();
                $where .= ($where ? ' OR ' : '') . "LOCATE('|{$classId}|', #classes)";
            }
        }
        $where .= (!empty($where) ? ' OR ' : '') . "#classes IS NULL";

        return $where;
    }


    /**
     * Генерира полето за задаване на нормата
     */
    public function renderInput($name, $value = '', &$attr = array())
    {
        if(!empty($value)){
            // Ако има избрани стойности - зареждат се като избрани във филтъра
            $arr = explode(',', $value);
            $query = bgerp_Filters::getQuery();
            $query->where(self::getClassesWhereClause($this->params['classes']));
            $query->in('name', $arr);
            $valueArr = arr::extractValuesFromArray($query->fetchAll(), 'id');
            $value = keylist::fromArray($valueArr);
        }

        $Keylist = core_Type::getByName('keylist(mvc=bgerp_Filters,select=title)');
        $Keylist->suggestions = bgerp_Filters::getArrOptions($this->params['classes']);

        return $Keylist->renderInput($name, $value, $attr);
    }


    /**
     * Обръща стойностите в масив
     *
     * @param string $value
     * @return array
     */
    public static function toArray($value)
    {
        return arr::make(explode(',', $value), true);
    }
}