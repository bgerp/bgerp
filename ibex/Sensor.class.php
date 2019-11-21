<?php


/**
 * Сензор за цените на енергийната борса
 *
 * @see 
 *
 * @category  bgerp
 * @package   ibex
 *
 * @author    Dimiter Minekov <mitko@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @title     Ibex
 */
class ibex_Sensor extends sens2_ProtoDriver
{
    /**
     * Заглавие на драйвера
     */
    public $title = 'Ibex';
    
    
    /**
     * Описание на входовете
     */
    public $inputs = array(
        'HOUR' => array('caption' => 'Текуща цена', 'uom' => 'BGN'),
        'BASE' => array('caption' => 'Дневна цена', 'uom' => 'BGN'),

    );
    
    
    /**
     * Описания на изходите
     */
    public $outputs = array(
    );
    
    
    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @see  sens2_ControllerIntf
     *
     * @param core_Form
     */
    public function prepareConfigForm($form)
    {
    }
    
    
    /**
     * Прочита стойностите от сензорните входове
     *
     * @see  sens2_ControllerIntf
     *
     * @param array $inputs
     * @param array $config
     * @param array $persistentState
     *
     * @return mixed
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        $res = array();
        $time = dt::mysql2timestamp(dt::now());
        $h = date('G', $time);
        $h = $h .'-' . ($h+1);
        if(date('I', $time ) == 1 && date('I', $time + 3600) == 0) {
            $h = date('G', $time - 3600) . '-' . date('G', $time) . 'a';
        }

        $date = dt::now(false);
 
        $res['BASE'] = ibex_Register::fetchField("#date = '{$date}' AND #kind = '00-24'", 'price');
        $res['HOUR'] = ibex_Register::fetchField("#date = '{$date}' AND #kind = '{$h}'", 'price');
 
        return $res;
    }
}
