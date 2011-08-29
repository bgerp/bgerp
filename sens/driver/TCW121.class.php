<?php

/**
 * Драйвер за IP сензор Teracom TCW-121 - следи състоянието на първите цифров и аналогов вход
 */
class sens_driver_TCW121 extends sens_driver_IpDevice
{
	// Параметри които чете или записва драйвера 
	var $params = array(
						array('unit'=>'T', 'param'=>'Температура', 'details'=>'C'),
						array('unit'=>'Hr', 'param'=>'Влажност', 'details'=>'%'),
						array('unit'=>'In1', 'param'=>'Състояние вход 1', 'details'=>'(ON,OFF)'),
						array('unit'=>'In2', 'param'=>'Състояние вход 2', 'details'=>'(ON,OFF)'),
						array('unit'=>'Out1', 'param'=>'Състояние изход 1', 'details'=>'(ON,OFF)'),
						array('unit'=>'Out2', 'param'=>'Състояние изход 2', 'details'=>'(ON,OFF)')
					);
	 
	
	/**
	 * Записва в мениджъра на параметрите - параметрите на драйвера
	 * Ако има вече такъв unit не прави нищо
	 */
	function setParams()
	{
		
		$Params = cls::createObject('sens_Params');
		
		foreach ($this->params as $param) {
			$rec = (object) $param;
			$rec->id = $Params->fetchField("#unit = '{$param[unit]}'",'id'); 
			$Params->save($rec);
	 
		}
	}
	
	
    /**
     * Връща масив със моментните стойности на параметрите на сензора
     */
    function getData()
    {
        $context = stream_context_create(array('http' => array('timeout' => 3)));
        
        $xml = file_get_contents("http://{$this->ip}:{$this->port}/m.xml", FALSE, $context);
         
        if (FALSE === $xml) {
        	$this->log("Устройство {$this->ip}:{$this->port} е недостъпно!");
        	return;
        }
        
        if (empty($xml)) {
        	$this->log("Устройство {$this->ip}:{$this->port} не отговаря!");
        	return;
        }
        
        $result = array();
        
        $this->XMLToArrayFlat(simplexml_load_string($xml), $result);
        
        $res = array(
        	'Температура' => $result['/Entry[5]/Value[1]'],
            'T' => $result['/Entry[5]/Value[1]'],
            'Влажност' => $result['/Entry[7]/Value[1]'],
            'Hr' => $result['/Entry[7]/Value[1]'],
            'Цифров вход 1' => $result['/Entry[1]/Value[1]'],
            'In1' => $result['/Entry[1]/Value[1]'],
            'Аналогов вход 1' => $result['/Entry[3]/Value[1]'],
            'V' => $result['/Entry[3]/Value[1]'],
  	        'Цифров вход 2' => $result['/Entry[2]/Value[1]'],
            'In2' => $result['/Entry[2]/Value[1]'],
            'Аналогов вход 2' => $result['/Entry[4]/Value[1]'],
            'V' => $result['/Entry[4]/Value[1]'],
            'Изход 1' => $result['/Entry[9]/Value[1]'],
            'Out1' => $result['/Entry[9]/Value[1]'],
            'Изход 2' => $result['/Entry[10]/Value[1]'],
            'Out2' => $result['/Entry[10]/Value[1]']
        ); 
        // Всички стойности ON и OFF ги обръщаме в респективно 1 и 0
        foreach ($res as $key => $value) {
        	$value = trim(strtoupper($value));
        	switch ($value) {
        		case 'ON':
        			$res[$key] = 1;
        		break;
        		case 'OFF':
        			$res[$key] = 0;
        		break;
        	};
        }
        return $res;
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