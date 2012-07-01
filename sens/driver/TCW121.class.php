<?php



/**
 * Драйвер за IP сензор Teracom TCW-121 - следи състоянието на цифров и аналогов вход
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
class sens_driver_TCW121 extends sens_driver_IpDevice
{
    
    
    /**
     * Параметри които чете или записва драйвера
     */
    var $params = array(
        'T1' => array('unit'=>'T1', 'param'=>'Температура', 'details'=>'C', 'xmlPath'=>'/Entry[5]/Value[1]'),
        'T2' => array('unit'=>'T2', 'param'=>'Температура', 'details'=>'C', 'xmlPath'=>'/Entry[6]/Value[1]'),
        'Hr' => array('unit'=>'Hr', 'param'=>'Влажност', 'details'=>'%', 'xmlPath'=>'/Entry[7]/Value[1]'),
        'In1' => array('unit'=>'In1', 'param'=>'Състояние вход 1', 'details'=>'(ON,OFF)', 'xmlPath'=>'/Entry[1]/Value[1]'),
        'In2' => array('unit'=>'In2', 'param'=>'Състояние вход 2', 'details'=>'(ON,OFF)', 'xmlPath'=>'/Entry[2]/Value[1]'),
        'InA1' => array('unit'=>'InA1', 'param'=>'Аналогов вход 1', 'details'=>'V', 'xmlPath'=>'/Entry[3]/Value[1]'),
        'InA2' => array('unit'=>'InA2', 'param'=>'Аналогов вход 2', 'details'=>'V', 'xmlPath'=>'/Entry[4]/Value[1]'),
        'RPM' => array('unit'=>'RPM', 'param'=>'Удари в минута', 'details'=>'rpm'),
    	// Oписваме и изходите за да можем да ги следим в логовете
        'Out1' => array('unit'=>'Out1', 'param'=>'Състояние изход 1', 'details'=>'(ON,OFF)', 'xmlPath'=>'/Entry[9]/Value[1]'),
        'Out2' => array('unit'=>'Out2', 'param'=>'Състояние изход 2', 'details'=>'(ON,OFF)', 'xmlPath'=>'/Entry[10]/Value[1]')
    
    );
    
    
    /**
     * Описания на изходите
     */
    var $outs = array(
        'Out1' => array('digital' => array('0', '1'), 'cmd'=>'/?r1'),
        'Out2' => array('digital' => array('0', '1'), 'cmd'=>'/?r2')
    );
    
    
    /**
     * Колко аларми/контроли да има?
     */
    var $alarmCnt = 3;
    
    
    /**
     * Подготвя формата за настройки на сензора
     * и алармите в зависимост от параметрите му
     */
    function prepareSettingsForm($form)
    {
        
        $form->FNC('ip', new type_Ip(), 'caption=IP,hint=Въведете IP адреса на устройството, input, mandatory');
        $form->FNC('port', 'int(5)', 'caption=Port,hint=Порт, input, mandatory,value=80');
        $form->FNC('user', 'varchar(10)', 'caption=User,hint=Потребител, input, mandatory,value=admin');
        $form->FNC('password', 'varchar(10)', 'caption=Password,hint=Парола, input, mandatory,value=admin');
        
        // Добавя и стандартните параметри
        $this->getSettingsForm($form);
    }
    
    
    /**
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
     * Прочита текущото състояние на драйвера/устройството
     */
    function updateState()
    {
        // Необходимо е само ако ни интересуват предходни стойности на базата на които да правим изчисления 
        //$stateOld = $this->loadState();
        
        $state = array();
        
        $url = "http://{$this->settings->ip}:{$this->settings->port}/m.xml";
        
        $context = stream_context_create(array('http' => array('timeout' => 4)));
        
        $xml = @file_get_contents($url, FALSE, $context);
        
        if (empty($xml) || !$xml) {
            $this->stateArr = NULL;
            
            return FALSE;
        }
        
        $xml = str_replace('</strong><sup>o</sup>C', '', $xml);
        
        $result = array();
        
        $this->XMLToArrayFlat(simplexml_load_string($xml), $result);
        
        foreach ($this->params as $param => $details) {
            
            $state[$param] = $result[$details['xmlPath']];
            
            if ($details['details'] == '(ON,OFF)') {
                $state[$param] = trim(strtoupper($result[$details['xmlPath']]));
                
                // Санитизираме цифровите входове и изходи
                switch ($state[$param]) {
                    case 'ON' :
                        $state[$param] = 1;
                        break;
                    case 'OFF' :
                        $state[$param] = 0;
                        break;
                }
            }
        }
        
        $state['RPM'] = round(($state['InA1']/4.2)*100);
        
        $this->stateArr = $state;
        
        return TRUE;
    }
    
    
    /**
     * Сетва изходите на драйвера по зададен масив
     *
     * @return bool
     */
    function setOuts($outs)
    {
        $baseUrl = "http://{$this->settings->user}:{$this->settings->password}@{$this->settings->ip}:{$this->settings->port}";
        
        foreach ($this->outs as $out => $attr) {
            $res[] = $baseUrl . $attr['cmd'] . "=" . $outs[$out];
        }
        
        // Необходимо ни е Curl за този сензор
        if (!function_exists('curl_init')) {
            sens_MsgLog::add($this->id, "Инсталирай Curl за PHP!", 3);
            exit(1);
        }
        
        // Превключваме релетата
        foreach ($res as $cmd) {
            $ch = curl_init("$cmd");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_exec($ch);
            curl_close($ch);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function XMLToArrayFlat($xml, &$return, $path = '', $root = FALSE)
    {
        $children = array();
        
        if ($xml instanceof SimpleXMLElement) {
            $children = $xml->children();
            
            if ($root){ // we're at root
                $path .= '/' . $xml->getName();
            }
        }
        
        if (count($children) == 0){
            $return[$path] = (string)$xml;
            
            return;
        }
        
        $seen = array();
        
        foreach ($children as $child => $value) {
            $childname = ($child instanceof SimpleXMLElement) ? $child->getName() : $child;
            
            if (!isset($seen[$childname])){
                $seen[$childname] = 0;
            }
            $seen[$childname]++;
            $this->XMLToArrayFlat($value, $return, $path . '/' . $child . '[' . $seen[$childname] . ']');
        }
    }
}
