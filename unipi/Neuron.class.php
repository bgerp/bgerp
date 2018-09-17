<?php


/**
 * Драйвер за Unipi Neuron
 *
 *
 * @category  bgerp
 * @package   unipi
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Unipi Neuron
 *
 * @see       https://www.unipi.technology/
 */
class unipi_Neuron extends sens2_ProtoDriver
{
    /**
     * От кой номер започва броенето на слотовете
     */
    const FIRST_SLOT_NO = 1;
    
    
    /**
     * Интерфейси, поддържани от всички наследници
     */
    public $interfaces = 'sens2_ControllerIntf';
    
    
    /**
     * Заглавие на драйвера
     */
    public $title = 'Unipi Neuron';
    
    
    /**
     * Съответствие между позиция и тип на входа/изхода
     */
    public $mapIOType = array('DI', 'DO', 'RO', 'AI', 'AO', 'ModBus', '1WIRE');
    
    
    /**
     * Инстанция на evoc API класа
     */
    public $evoc;
    
    
    /**
     * Дали драйверът има детайл
     */
    public $hasDetail = true;
    
    
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
     * Максимален брой устройства според вида на слота
     */
    private $maxPortsPerSlotArr = array('DI' => 1, 'DO' => 1, 'RO' => 1, 'AI' => 1, 'AO' => 1, 'ModBus' => 250, '1WIRE' => 12);
    
    
    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @see  sens2_ControllerIntf
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
     * @see  sens2_ControllerIntf
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
        $this->evoc = new unipi_Evoc($config->ip, $config->port);
        
        $ports = $this->discovery();
        
        $slotDrvArr = array();
        $controllerId = $this->driverRec->id;
        $pQuery = sens2_IOPorts::getQuery();
        
        foreach ($ports as $p) {
            if (!$inputs[$p->name]) {
                continue;
            }
            $inPorts[$p->slot][$p->portIdent][] = $p->lName;
        }
      
        // Прочитаме състоянието от контролера
        $this->evoc->update();
       
        while ($pRec = $pQuery->fetch("#controllerId = {$controllerId}")) {
            $nameArr = $inPorts[$pRec->slot]["{$pRec->portIdent}"];
            
            if (!$nameArr) {
                continue;
            }
            
            $pDrv = sens2_IOPorts::getDriver($pRec);
            
            list($slotType, $slotNumber) = explode('-', $pRec->slot);
            
            // Продготвяне на стойността за порта
            $prepareMethod = 'prepare' . $slotType;
            
            $data = $this->{$prepareMethod}($slotNumber, $pRec->portIdent);
             
            // Конвертиране на стойността на порта
            foreach ($nameArr as $name) {
                $val = $pDrv->convert($data, $name, $pRec);
                $res[$pRec->name . ($name ? '.':'') . $name] = $val;
            }
        }
        
        return $res;
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
     * Връща масив със портовете на устройството
     *
     * @return array
     */
    public function getSlotCnt()
    {
        $config = $this->driverRec->config;
        $model = $this->models[$config->model];
        
        $extension = $this->extensions[$config->extension] ? $this->extensions[$config->extension] : array();
        
        $res = array();
        foreach ($this->mapIOType as $id => $type) {
            $res[$type] = $model[$id] + $extension[$id];
        }
        
        return $res;
    }
    
    
    /**
     * Колко максимално порта могат да се вържат на посочения тип слот
     *
     * @param string $slotType
     *
     * @return int
     */
    public function getMaxPortsPerSlot($slotType)
    {
        return $this->maxPortsPerSlotArr[$slotType];
    }
    
    
    /**
     * Подготвя данните извлечени от дадения слот и unitId
     */
    public function prepareModBus($slotNo, $portIdent)
    {
        $data = $this->evoc->getUartData($slotNo, $portIdent);
        
        return $data;
    }
    
    
    /**
     * Подготвя данните извлечени от дадения слот и unitId
     */
    public function prepare1WIRE($slotNo, $portIdent)
    {
    }
    
    
    /**
     * Подготвя данните извлечени от дадения слот и unitId
     */
    public function prepareDI($slotNo, $portIdent)
    {
    }
    
    
    /**
     * Подготвя данните извлечени от дадения слот и unitId
     */
    public function prepareDO($slotNo, $portIdent)
    {
    }
    
    
    /**
     * Подготвя данните извлечени от дадения слот и unitId
     */
    public function prepareAI($slotNo, $portIdent)
    {
        $inputAddr = str_pad($slotNo, 2, "0", STR_PAD_LEFT);
        
        $res = $this->evoc->searchValues('1_' . $inputAddr, 'ai');
 
        return $res[0];
    }
    
    
    /**
     * Подготвя данните извлечени от дадения слот и unitId
     */
    public function prepareAO($slotNo, $portIdent)
    {
    }
}
