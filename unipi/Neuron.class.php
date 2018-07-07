<?php


/**
 * Драйвер за Unipi Neuron
 *
 *
 * @category  bgerp
 * @package   unipi
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Unipi Neuron
 * @see       https://www.unipi.technology/
 */
class unipi_Neuron extends sens2_ProtoDriver
{

    /**
     * Интерфейси, поддържани от всички наследници
     */
    public $interfaces = 'sens2_DriverIntf';


    /**
     * Заглавие на драйвера
     */
    public $title = 'Unipi Neuron';
    

    /**
     * съответствие между позиция и тип на входа/изхода
     */
    public $mapIOType = array('DI', 'DO', 'RO', 'AI', 'AO', 'RS485', '1WIRE');


    /**
     * Достъпни модели контролери
     *
     */
    private $models = array(
                'S103' => array(4,  4,  0,  1, 1, 1, 1),
                
                'M103' => array(12, 4,  8,  1, 1, 1, 1),
                'M203' => array(20, 4,  14, 1, 1, 1, 1),
                'M303' => array(34, 4,  0,  1, 1, 1, 1),
                'M403' => array(4,  4,  28, 1, 1, 1, 1),
                'M503' => array(10, 4,  5,  5,5, 2, 1),

                'L203' => array(36, 4,  28, 1,  1, 1, 1),
                'L303' => array(64, 4,  1,  1,  1, 1, 1),
                'L403' => array(4,  4,  56, 1,  1, 1, 1),
                'L503' => array(24, 4,  19, 5,  5, 2, 1),
                'L513' => array(16, 4,  10, 9,  9, 3, 1),
             );

    private $extensions = array(
                'xS10' => array(16, 0,  8,  0,  0,  0, 0),
                'xS30' => array(24, 0,  0,  0,  0,  0, 0),
                'xS40' => array(8,  0,  14, 0,  0,  0, 0),
                'xS50' => array(6,  0,  5,  4,  4,  0, 0),
             );
    
    /**
     *  Информация за входните портове на устройството
     *
     * @see  sens2_DriverIntf
     *
     * @return array
     */
    public function getInputPorts($config = null)
    {
        $res = array();
        
 
        return $res;
    }

    
    /**
     * Информация за изходните портове на устройството
     *
     * @see  sens2_DriverIntf
     *
     * @return array
     */
    public function getOutputPorts($config = null)
    {
        $res = array();

        if ($config) {
            //
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
    public function prepareConfigForm($form)
    {
        $optModels = implode(',', array_keys($this->models));
        $form->FNC('model', "enum({$optModels})", 'caption=Модел,input,notNull,');

        $optExtensions = implode(',', array_keys($this->extensions));
        $form->FNC('extension', "enum(,{$optExtensions})", 'caption=Разширение,input,');

        $form->FNC('ip', 'ip', 'caption=IP,hint=Въведете IP адреса на устройството, input, mandatory');
        $form->FNC('port', 'int(5)', 'caption=Port,hint=Порт, input, placeholder=80');
    }
    

    /**
     * Проверява след  субмитване формата с настройки на контролера
     *
     * @see  sens2_DriverIntf
     *
     * @param   core_Form
     */
    public function checkConfigForm($form)
    {
    }

    
    /**
     * Връща масив със стойностите на изразходваната активна мощност
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
    }


    /**
     * Сетва изходите на драйвера по зададен масив
     *
     * @return bool
     */
    public function writeOutputs($outputs, $config, &$persistentState)
    {
        return array();
    }


    /**
     * Връща снимка на контролера
     */
    public static function getPicture($config)
    {
        $path = 'unipi/pics/' . $config->model . '.jpg';

        return $path;
    }


    /**
     * Връща детайлите за единичния изглед
     */
    public function getDetails($config)
    {
        return arr::make('unipi_Synapses', true);
    }


    /**
     * Връща масив със портовете на устройството
     *
     * @return array
     */
    public function getSlotCnt()
    {
        $config = $this->driverRec->config;
        $model = $this->models[$config->model];

        $extension = $this->extensions[$config->extension] ?? array();

        $res = array();
        foreach ($this->mapIOType as $id => $type) {
            $res[$type] = $model[$id] + $extension[$id];
        }

        return $res;
    }
}
