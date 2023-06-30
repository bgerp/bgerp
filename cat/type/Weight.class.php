<?php


/**
 * Клас  'cat_type_Weight'
 * Тип за Тегло
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class cat_type_Weight extends cat_type_Uom
{
    /**
     * Параметър по подразбиране
     */
    public function init($params = array())
    {
        // Основната мярка на типа е килограм
        $this->params['unit'] = 'kg';
        $this->params['smartRound'] = 'yes';
        if (is_array($params['params'])) {
            $this->params = array_merge($this->params, $params['params']);
        }
        
        parent::init($this->params);
    }

    /**
     * Форматира числото в удобна за четене форма
     */
    public function toVerbal_($value)
    {
        if(!empty($value)){
            if($this->params['smartRound'] == 'yes'){
                if($value > 10){
                    $value = round($value);
                } elseif($value >= 1){
                    $value = round($value, 1);
                } else {
                    $value = round($value, 3);
                }
            } else {
                $round = cat_UoM::fetchBySysId($this->params['unit'])->round;
                $value = round($value, $round);
            }
        }

        $value = parent::toVerbal_($value);

        return $value;
    }
}
