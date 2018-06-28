<?php



/**
 * Имитация на драйвер за IP сензор
 *
 *
 * @category  bgerp
 * @package   sens
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Имитация на IP сензор
 */
class sens2_MockupDrv  extends sens2_ProtoDriver
{
    
    /**
     * Заглавие на драйвера
     */
    var $title = 'Mockup';
    
    
    /**
     * Интерфeйси, поддържани от всички наследници
     */
    var $interfaces = 'sens2_DriverIntf';

    
    function getInputPorts($config = NULL)
    {
        return array(
                'Temp1'  => (object) array('caption' => 'Температура 1', 'uom' => 'ºC'),
                'Memory' => (object) array('caption' => 'Свободна памет', 'uom' => 'B')
                );
    }


    function getOutputPorts()
    {
        return array('D1' => (object) array('caption' => 'Цифров изход 1', 'uom' => ''));
    }


    function prepareConfigForm($form)
    {
        $form->FLD('ip', 'ip', 'caption=Ip');
    }

    function checkConfigForm($form)
    {
        if($form->rec->ip{0} == '2') {
            $form->setError('ip', 'Ip-то не трябва да започва с 2');
        }
    }

    function readInputs($inputs, $config, &$persistentState)
    {
        if($inputs['Temp1']) {
            $res['Temp1'] = 5;
        }

        if($inputs['Memory']) {
            $res['Memory'] = memory_get_usage(TRUE);
        }

        sleep(1);
        Debug::log('Sleep 1 sec. in' . __CLASS__);

        return $res;
    }

    /**
     * Записва стойностите на изходите на контролера
     *
     * @param   array   $outputs            масив със системните имена на изходите и стойностите, които трябва да бъдат записани
     * @param   array   $config             конфигурациони параметри
     * @param   array   $persistentState    персистентно състояние на контролера от базата данни
     *
     * @return  array                       Mасив със системните имена на изходите и статус (TRUE/FALSE) на операцията с него
     */
    function writeOutputs($outputs, $config, &$persistentState)
    {
        if(!$persistentState) {
            $persistentState = array();
        }
        
        $res = array();

        foreach($outputs as $o => $v) {
            if(rand(1,100) == 54) {
                $res[$o] = FALSE;
            } else {
                $persistentState[$o] = $v;
                $res[$o] = TRUE;
            }
        }

        return $res;
    }

}