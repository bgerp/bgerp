<?php

/**
 * Прототип на драйвер за IP сензор
 */
class sens_driver_SensorMockup extends sens_driver_IpDevice
implements intf_IpSensor {
    
    
    /**
     * Връща масив с всички данните от сензора
     *
     * @return array $sensorData
     */
    function getData()
    {
        // Дани за всички параметри, които поддържа сензора
        $sensorData = array('T' => rand(1,20),
        'Hr' => rand(0,100),
        'Dst' => rand(0,40),
        'Chm' => rand(0,40));
        
        return $sensorData;
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