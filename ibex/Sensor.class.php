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
        'TYPE' => array('caption' => 'Вид', 'uom' => ''),

        //'BASE' => array('caption' => 'Дневна цена', 'uom' => 'BGN'),

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
        $form->FLD('minIndex', 'int', 'caption=Индекс на часа->Евтин,value=6');
        $form->FLD('maxIndex', 'int', 'caption=Индекс на часа->Скъп,value=18');
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
        $h = date('H', $time);
        $h = $h . '-' . date('H', $time + 3600);
        if(date('I', $time ) == 1 && date('I', $time + 3600) == 0) {
            $h = date('H', $time - 3600) . '-' . date('H', $time) . 'a';
        }

        $date = dt::now(false);
 
        $res['HOUR'] = ibex_Register::fetchField("#date = '{$date}' AND #kind = '{$h}'", 'price');

        $iQuery = ibex_Register::getQuery();

        while($iRec = $iQuery->fetch("#date = '{$date}'")) {
            if($iRec->kind == '00-24') continue;
            $prices[(int) $iRec->kind] = (float) $iRec->price;
        }

        asort($prices);
        $prices = array_keys($prices);

        $pos = array_search($h, $prices);
        $minIndex = $config->minIndex ? $config->minIndex : 6;
        $maxIndex = $config->maxIndex ? $config->maxIndex : 6;
        
        $h = (int) $h;

        if($pos < 6) {
            $res['TYPE'] = -1;
        } elseif($pos >= 18) {
            $res['TYPE'] = 1;
        } else {
            $res['TYPE'] = 0;
        }

 
        return $res;
    }
}
