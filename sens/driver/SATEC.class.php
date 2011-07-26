<?php

/**
 * Драйвер за електромер SATEC
 */
class sens_driver_SATEC extends sens_driver_IpDevice
implements intf_IpSensor {
    
    
    /**
     * Връща масив със стойностите на изразходваната активна мощност
     */
    function getData()
    {
        $driver = new modbus_Driver( (array) $rec);
        
        $driver->ip = $this->ip;
        $driver->port = $this->port;
        $driver->unit = $this->unit;
        
        // Прочитаме изчерпаната до сега мощност
        $driver->type = 'double';
        
        $kw = $driver->read(405072, 2);
        $output = $kw['405072'];
        
        return array('kW' => $output);
    }
    
    
    /**
     * По входна стойност от $rec връща HTML
     *
     * @param stdClass $rec
     * @return string $sensorHtml
     */
    function renderHtml()
    {
        $sensorData = $this->getData();
        
        $sensorHtml = NULL;
        
        if (count($sensorData)) {
            foreach ($sensorData as $k => $v) {
                $sensorHtml .= "<br/>" . $k . ": " . $v;
            }
        }
        
        if (!strlen($sensorHtml)) {
            $sensorHtml = "Няма данни от този сензор";
        }
        
        return $sensorHtml;
    }
}