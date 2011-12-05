<?php

/**
 * Драйвер за IP сензор HWg-STE - мери температура и влажност
 */
class sens_driver_HWgSTE extends sens_driver_IpDevice
{

	// Параметри които чете или записва драйвера 
    var $params = array(
						'T' => array('unit'=>'T', 'param'=>'Температура', 'details'=>'C'),
						'Hr' => array('unit'=>'Hr', 'param'=>'Влажност', 'details'=>'%')
					);
	 
    // Колко аларми/контроли да има?
    var $alarmCnt = 3;
    
    // IP адрес на сензора
    var $ip = '';
    
    // Порт
    var $port = '';

	/**
	 * 
	 * Извлича данните от формата със заредени от Request данни,
	 * като може да им направи специализирана проверка коректност.
	 * Ако след извикването на този метод $form->getErrors() връща TRUE,
	 * то означава че данните не са коректни.
	 * От формата данните попадат в тази част от вътрешното състояние на обекта,
	 * която определя неговите settings
	 * 
	 * @param object $form
	 */
	function setSettingsFromForm($form)
	{

	}
	
    /**
     * Връща масив със стойностите на температурата и влажността
     */
    function getData(&$indications)
    {
        $url = "http://{$this->settings->ip}:{$this->settings->port}/values.xml"; //bp($url);

        $context = stream_context_create(array('http' => array('timeout' => 4)));

        $xml = @file_get_contents($url, FALSE, $context); 

        if (empty($xml) || !$xml) return FALSE;
        
        $result = array();
        
        $this->XMLToArrayFlat(simplexml_load_string($xml), $result);
        
        $indications = array_merge((array)$indications, array( 'T' => $result['/SenSet[1]/Entry[1]/Value[1]'],
            	      									'Hr' => $result['/SenSet[1]/Entry[2]/Value[1]']
        												)
        						);
        return $indications;
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
}