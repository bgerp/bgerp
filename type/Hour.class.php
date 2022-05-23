<?php


/**
 * Клас  'type_Hour' - Тип за поле за избор на час с минути
 *
 *
 *
 * @category  bgerp
 * @package   type
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_Hour extends type_Varchar
{

    /**
     * Рендира HTML инпут поле
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        $hourOptions = array();
        foreach (range(1, 23) as $h){
            $h = str_pad($h, 2, '0', STR_PAD_LEFT) . ":00";
            $hourOptions[$h] = $h;
        }
        $attr['style'] .= ';max-width:4em;';

        $inputHour = ht::createCombo($name, $value, $attr, $hourOptions);

        return $inputHour;
    }


    /**
     * Конвертира от вербална стойност
     */
    public function fromVerbal($value)
    {
        $value = trim($value);
        if($value){
            if(!preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $value)){
                $this->error = 'Невалиден формат за час';

                return false;
            }
        }

        if(empty($value)){
            $value = null;
        }

        return $value;
    }
}