<?php



/**
 * Прототип на драйвер за контролер
 *
 *
 * @category  bgerp
 * @package   sens2
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sens2_ProtoDriver
{
   
    /**
     * Интерфeйси, поддържани от всички наследници
     */
    var $interfaces = 'sens2_DriverIntf';

    
    /**
     *  Информация за входните портове на устройството
     *
     * @see  sens2_DriverIntf
     *
     * @return  array
     */
    function getInputPorts($config = NULL)
    {
        $res = array();
        
        if(is_array($this->inputs)) {
            foreach($this->inputs as $name => $params) {
                $res[$name] = (object) array('caption' => $params['caption'], 'uom' => $params['uom']);
            }
        }

        return $res;
    }

    
    /**
     * Информация за изходните портове на устройството
     *
     * @see  sens2_DriverIntf
     *
     * @return  array
     */
    function getOutputPorts()
    {
        $res = array();

        if(is_array($this->outputs)) {
            foreach($this->outputs as $name => $params) {
                $res[$name] = (object) array('caption' => $params['caption'], 'uom' => $params['uom']);
            }
        }

        return $res;
    }


    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @see  sens2_DriverIntf
     *
     * @param core_Form
     */
    function prepareConfigForm($form)
    {
    }
    

    /**
     * Проверява след  субмитване формата с настройки на контролера
     *
     * @see  sens2_DriverIntf
     *
     * @param   core_Form
     */
    function checkConfigForm($form)
    {
    }

    
    /**
     * Връща масив със стойностите на изразходваната активна мощност
     */
    function readInputs($inputs, $config, &$persistentState)
    {
        return array();
    }


    /**
     * Сетва изходите на драйвера по зададен масив
     *
     * @return bool
     */
    function writeOutputs($outputs, $config, &$persistentState)
    {
        return array();
    }


    /**
     * Връща снимка на контролера
     *
     * @param   stdClass     $config    конфигурацията на контролера
     * @return  string|null
     */
    public static function getPicture($config)
    {
    }
}