<?php

/**
 * Тестер за Modbus IP устройство
 */
class sens_driver_TSM extends sens_driver_IpDevice
implements intf_IpSensor {
    
    
    /**
     * Връща масив със стойностите на температурата и влажността
     */
    function getData()
    {
        $driver = new modbus_Driver( (array) $rec);
        
        $driver->ip = $this->ip;
        $driver->port = $this->port;
        $driver->unit = $this->unit;
        
        // Прочитаме произведеното с компонент 1
        $driver->type = 'double';
        
        $c1 = $driver->read(400446, 2);
        
        $c2 = $driver->read(400468, 2);
        
        $c3 = $driver->read(400490, 2);
        $c4 = $driver->read(400512, 2);
        $c5 = $driver->read(400534, 2);
        $c6 = $driver->read(400556, 2);
        
        $output = ($c1[400446] + $c2[400468] + $c3[400490] + $c4[400512] + $c5[400534] + $c6[400556]) / 100;
        
        $driver = new modbus_Driver( (array) $rec);
        
        $driver->ip = $this->ip;
        $driver->port = $this->port;
        $driver->unit = $this->unit;
        
        $driver->type = 'words';
        
        $p1 = $driver->read(400439, 1);
        $p1 = $p1[400439];
        
        $p2 = $driver->read(400461, 1);
        $p2 = $p2[400461];
        
        $p3 = $driver->read(400483, 1);
        $p3 = $p3[400483];
        
        $p4 = $driver->read(400505, 1);
        $p4 = $p4[400505];
        
        $p5 = $driver->read(400527, 1);
        $p5 = $p5[400527];
        
        $p6 = $driver->read(400549, 1);
        $p6 = $p6[400549];
        
        if($p1) {
            $recpt .= "[1] => " . $p1/100 . "%";
        }
        
        if($p2) {
            $recpt .= ($recpt?", ":"") . "[2] => " . $p2/100 . "%";
        }
        
        if($p3) {
            $recpt .= ($recpt?", ":"") . "[3] => " . $p3/100 . "%";
        }
        
        if($p4) {
            $recpt .= ($recpt?", ":"") . "[4] => " . $p4/100 . "%";
        }
        
        if($p5) {
            $recpt .= ($recpt?", ":"") . "[5] => " . $p5/100 . "%";
        }
        
        if($p6) {
            $recpt .= ($recpt?", ":"") . "[6] => " . $p6/100 . "%";
        }
        
        return array('EO' => $output, 'ERC' => $recpt);
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