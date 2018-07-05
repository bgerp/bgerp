<?php



/**
 * Драйвер за IP сензор HWg-STE - мери температура и влажност
 *
 *
 * @category  bgerp
 * @package   sens
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Драйвери на сензори
 */
class sens_driver_HWgSTE extends sens_driver_IpDevice
{
    
    /**
     * Заглавие на драйвера
     */
    public $title = 'HWgSTE';


    /**
     * Параметри които чете или записва драйвера
     */
    public $params = array(
        'T' => array('unit' => 'T', 'param' => 'Температура', 'details' => 'C', 'xmlPath' => '/SenSet[1]/Entry[1]/Value[1]'),
        'Hr' => array('unit' => 'Hr', 'param' => 'Влажност', 'details' => '%', 'xmlPath' => '/SenSet[1]/Entry[2]/Value[1]')
    );
    
    
    /**
     * Колко аларми/контроли да има?
     */
    public $alarmCnt = 3;
    
    
    /**
     * Извлича данните от формата със заредени от Request данни,
     * като може да им направи специализирана проверка коректност.
     * Ако след извикването на този метод $form->getErrors() връща TRUE,
     * то означава, че данните не са коректни.
     * От формата данните попадат в тази част от вътрешното състояние на обекта,
     * която определя неговите settings
     *
     * @param object $form
     */
    public function setSettingsFromForm($form)
    {
    }
    
    
    /**
     * Подготвя формата за настройки на сензора
     * и алармите в зависимост от параметрите му
     */
    public function prepareSettingsForm($form)
    {
        $form->FNC('ip', new type_Ip(), 'caption=IP,hint=Въведете IP адреса на устройството, input, mandatory');
        $form->FNC('port', 'int(5)', 'caption=Port,hint=Порт, input, mandatory,value=80');
        
        // Добавя и стандартните параметри
        $this->getSettingsForm($form);
    }
    
    
    /**
     * Връща масив със стойностите на температурата и влажността
     */
    public function updateState()
    {
        $state = array();
        
        $url = "http://{$this->settings->ip}:{$this->settings->port}/values.xml";
        
        $context = stream_context_create(array('http' => array('timeout' => 4)));
        
        $xml = @file_get_contents($url, false, $context);
        
        if (empty($xml) || !$xml) {
            $this->stateArr = null;
            
            return false;
        }
        
        $result = array();
        
        $this->XMLToArrayFlat(simplexml_load_string($xml), $result);
        
        foreach ($this->params as $param => $details) {
            $state[$param] = $result[$details['xmlPath']];
        }
        
        $this->stateArr = $state;
        
        return true;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function XMLToArrayFlat($xml, &$return, $path = '', $root = false)
    {
        $children = array();
        
        if ($xml instanceof SimpleXMLElement) {
            $children = $xml->children();
            
            if ($root) { // we're at root
                $path .= '/' . $xml->getName();
            }
        }
        
        if (count($children) == 0) {
            $return[$path] = (string) $xml;
            
            return;
        }
        
        $seen = array();
        
        foreach ($children as $child => $value) {
            $childname = ($child instanceof SimpleXMLElement) ? $child->getName() : $child;
            
            if (!isset($seen[$childname])) {
                $seen[$childname] = 0;
            }
            $seen[$childname]++;
            $this->XMLToArrayFlat($value, $return, $path . '/' . $child . '[' . $seen[$childname] . ']');
        }
    }
}
