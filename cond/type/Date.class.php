<?php


/**
 * Базов драйвер за драйвер на артикул
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
 * @title     Дата
 */
class cond_type_Date extends cond_type_abstract_Proto
{
    /**
     * Как се индексира стойността за филтриране (@see cat_products_ParamIndex)
     */
    protected $indexKind = 'num';


    /**
     * Кой базов тип наследява
     */
    protected $baseType = 'type_Date';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('time', 'enum(no=Без час, yes=С час)', 'caption=Конкретизиране->Дължина,before=default');
        $fieldset->FLD('autoValue', 'enum(,today=Текуща дата,firstDayOfWeek=Начало на седмицата,lastDayOfWeek=Край на седмицата,firstDayOfMonth=Начало на месеца,lastDayOfMonth=Край на месеца)', 'caption=Конкретизиране->Автоматично,before=default');
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
        
        if ($rec->time == 'yes') {
            $Type = cls::get('type_DateTime');
        }
        
        return $Type;
    }


    /**
     * Връща дефолтната стойност на параметъра
     *
     * @param stdClass    $rec         - запис на параметъра
     * @param mixed       $domainClass - клас на домейна
     * @param mixed       $domainId    - ид на домейна
     * @param NULL|string $value       - стойност
     *
     * @return mixed    $default       - дефолтната стойност (ако има)
     */
    public function getDefaultValue($rec, $domainClass = null, $domainId = null, $value = null)
    {
        $default = null;
        if(!empty($rec->autoValue)){
            $default = ($rec->time == 'yes') ? dt::now() : dt::today();
            switch($rec->autoValue){
                case 'today':
                    break;
                case 'firstDayOfWeek':
                    $date = new DateTime($default);
                    $date->modify('last Monday');
                    $default = $date->format('Y-m-d');
                    break;
                case 'lastDayOfWeek':
                    $date = new DateTime($default);
                    $date->modify('next Sunday');
                    $default = $date->format('Y-m-d');
                    if($rec->time == 'yes'){
                        $default .= " 23:59";
                    }
                    break;
                case 'firstDayOfMonth':
                    $default = date('Y-m-d', strtotime('first day of this month'));
                    break;
                case 'lastDayOfMonth':
                    $default = dt::getLastDayOfMonth();
                    if($rec->time == 'yes'){
                        $default .= " 23:59";
                    }
                    break;
            }
        }

        return $default;
    }


    /**
     * Датата се индексира като timestamp, за да се търси в диапазон
     */
    public function getIndexValues($rec, $domainClass, $domainId, $value, $langs)
    {
        $value = trim((string) $value);
        $time = strlen($value) ? strtotime($value) : false;
        if ($time === false) {

            return array();
        }

        return array($this->makeIndexRow(array('valueNum' => $time)));
    }


    /**
     * Вербално представяне на индексиран ред
     */
    public function getIndexVerbal($rec, $domainClass, $domainId, $iRec)
    {
        if (!isset($iRec->valueNum)) {

            return '';
        }

        return $this->toVerbal($rec, $domainClass, $domainId, dt::timestamp2Mysql($iRec->valueNum));
    }
}
