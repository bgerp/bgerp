<?php


/**
 * Прототип на драйвер за контролер
 *
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_ProtoDriver
{
    /**
     * От кой номер започва броенето на слотовете
     */
    const FIRST_SLOT_NO = 0;
    
    
    /**
     * Интерфейси, поддържани от всички наследници
     */
    public $interfaces = 'sens2_ControllerIntf';
    
    
    /**
     * Прочита портовете от зададения слот
     */
    public function readPorts($slot, $params)
    {
        $method = 'read' . $slot;
        
        $res = self::{$method}($params);
        
        return $res;
    }
    
    
    /**
     *  Информация за входните портове на устройството
     *
     * @see  sens2_ControllerIntf
     *
     * @return array
     */
    public function getInputPorts($config = null)
    {
        $res = array();
        
        $ports = $this->discovery();
        
        foreach ($ports as $p) {
            if ($p->readable) {
                $res[$p->name] = $p;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Информация за изходните портове на устройството
     *
     * @see  sens2_ControllerIntf
     *
     * @return array
     */
    public function getOutputPorts()
    {
        $res = array();
        
        $ports = $this->discovery();
        foreach ($ports as $p) {
            if ($p->writable) {
                $res[$p->name] = $p;
            }
        }
        
        return $res;
    }
    
    
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
        return array();
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
     *
     * @param stdClass $config конфигурацията на контролера
     *
     * @return string|null
     */
    public static function getPicture($config)
    {
    }
    
    
    /**
     * Връща списъка с възможните слотове от посочени типове
     */
    public function getSlotOpt($type = array(), $onlyAviable = false)
    {
        $slots = $this->getSlotCnt();
        
        $typeArr = arr::make($type, true);
        
        if (!count($typeArr)) {
            $typeArr = array_keys($slots);
        }
        
        $used = array();
        if ($onlyAviable) {
            $used = $this->getUsedSlots();
        }
        
        $res = array();
        foreach ($typeArr as $st) {
            $cnt = (int) $slots[$st];
            
            for ($i = static::FIRST_SLOT_NO; $i < $cnt + static::FIRST_SLOT_NO; $i++) {
                $name = $st . '-' . $i;
                
                if ($onlyAviable && $used[$name] >= $this->getMaxPortsPerSlot($st)) {
                    continue;
                }
                
                $res[$name] = $name;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща всички използвани слотове на устройството и броя на закачените портове на всеки слот
     */
    private function getUsedSlots()
    {
        $pQuery = sens2_IOPorts::getQuery();
        $slots = array();
        if ($this->driverRec) {
            while ($pRec = $pQuery->fetch("#controllerId = {$this->driverRec->id}")) {
                $slots[$pRec->slot]++;
            }
        }
        
        return $slots;
    }
    
    
    /**
     * Връша информация за наличните портове
     *
     * @return array масив с обекти имащи следните полета:
     *               o name     - име на променливата
     *               о slot     - име на слота
     *               о suffix   - стринг, който се изписва след променливата (%, V, W, ...)
     *               o prefix   - стринг, който се изписва преди променливата
     *               о options  - масив с възможни стоийнисти
     *               о min      - минимална стойност
     *               о max      - максимална стойност
     *               о readable - дали порта може да се чете
     *               о writable - дали порта може да се записва
     */
    public function discovery()
    {
        $pQuery = sens2_IOPorts::getQuery();
        $ports = array();
        if ($this->driverRec) {
            while ($pRec = $pQuery->fetch("#controllerId = {$this->driverRec->id}")) {
                $pDriver = sens2_IOPorts::getDriver($pRec);
                $dPorts = $pDriver->discovery($pRec);
                foreach ($dPorts as $p) {
                    $p->lName = $p->name;
                    $p->name = $pRec->name . ($p->name ? '.' . $p->name : '');
                    $p->slot = $pRec->slot;
                    $pDriver->addTimeValues($p, $pRec);
                    $ports[] = $p;
                }
            }
        }
        
        $map = array('uom' => 'uom', 'scale' => 'scale', 'update' => 'readPeriod', 'log' => 'logPeriod');
        
        if (is_array($this->inputs) && ($config = $this->driverRec->config)) {
            foreach ($this->inputs as $name => $descArr) {
                $p = (object) $descArr;
                $p->name = $name;
                $p->readable = true;
                foreach ($map as $confPart => $portPart) {
                    $confField = $name . '_' . $confPart;
                    if (strlen($config->{$confField})) {
                        $p->{$portPart} = $config->{$confField};
                    }
                }
                $ports[] = $p;
            }
        }
        
        if (is_array($this->outputs) && ($config = $this->driverRec->config)) {
            foreach ($this->outputs as $name => $descArr) {
                $p = (object) $descArr;
                $p->name = $name;
                $p->writable = true;
                foreach ($map as $confPart => $portPart) {
                    $confField = $name . '_' . $confPart;
                    if (strlen($config->{$confField})) {
                        $p->{$portPart} = $config->{$confField};
                    }
                }
                $ports[] = $p;
            }
        }
        
        $res = array();
        foreach ($ports as $p) {
            $res[$p->name] = $p;
            if (!isset($p->caption)) {
                $p->caption = $p->name;
            }
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
        return 1;
    }
}
