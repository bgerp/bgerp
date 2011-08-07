<?php

/**
 * Драйвер за IP сензор HWg-STE - мери температура и влажност
 */
class sens_driver_Sensor extends sens_driver_IpDevice
{
    /**
     * Връща масив със стойностите на температурата и влажността
     */
    function getData()
    {
        $xml = file_get_contents("http://{$this->ip}:{$this->port}/values.xml");
        
        $result = array();
        
        $this->XMLToArrayFlat(simplexml_load_string($xml), $result);
        
        return array('Температура' => $result['/SenSet[1]/Entry[1]/Value[1]'],
            'T' => $result['/SenSet[1]/Entry[1]/Value[1]'],
            'Влажност' => $result['/SenSet[1]/Entry[2]/Value[1]'],
            'Hr' => $result['/SenSet[1]/Entry[2]/Value[1]']
        );
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function XMLToArrayFlat($xml, &$return, $path='', $root=FALSE)
    {
        $children = array();
        
        if ($xml instanceof SimpleXMLElement) {
            $children = $xml->children();
            
            if ($root){ // we're at root
                $path .= '/'.$xml->getName();
            }
        }
        
        if ( count($children) == 0 ){
            $return[$path] = (string)$xml;
            
            return;
        }
        
        $seen = array();
        
        foreach ($children as $child => $value) {
            $childname = ($child instanceof SimpleXMLElement)?$child->getName():$child;
            
            if ( !isset($seen[$childname])){
                $seen[$childname] = 0;
            }
            $seen[$childname]++;
            $this->XMLToArrayFlat($value, $return, $path.'/'.$child.'['.$seen[$childname].']');
        }
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