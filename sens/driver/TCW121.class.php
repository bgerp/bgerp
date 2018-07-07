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
 * @title     Драйвер за IP сензор Teracom TCW-121
 */
class sens_driver_TCW121 extends sens_driver_IpDevice
{
    
    /**
     * Заглавие на драйвера
     */
    public $title = 'TCW121';

    
    /**
     * Параметри които чете или записва драйвера
     */
    public $params = array(
        'T1' => array('unit' => 'T1', 'param' => 'Температура', 'details' => 'C', 'xmlPath' => '/Entry[5]/Value[1]'),
        'T2' => array('unit' => 'T2', 'param' => 'Температура', 'details' => 'C', 'xmlPath' => '/Entry[6]/Value[1]'),
        'Hr1' => array('unit' => 'Hr1', 'param' => 'Влажност', 'details' => '%', 'xmlPath' => '/Entry[7]/Value[1]'),
        'Hr2' => array('unit' => 'Hr2', 'param' => 'Влажност', 'details' => '%', 'xmlPath' => '/Entry[8]/Value[1]'),
        'InD1' => array('unit' => 'InD1', 'param' => 'Цифров вход 1', 'details' => '(ON,OFF)', 'xmlPath' => '/Entry[1]/Value[1]'),
        'InD2' => array('unit' => 'InD2', 'param' => 'Цифров вход 2', 'details' => '(ON,OFF)', 'xmlPath' => '/Entry[2]/Value[1]'),
        'InA1' => array('unit' => 'InA1', 'param' => 'Аналогов вход 1', 'details' => 'V', 'xmlPath' => '/Entry[3]/Value[1]'),
        'InA2' => array('unit' => 'InA2', 'param' => 'Аналогов вход 2', 'details' => 'V', 'xmlPath' => '/Entry[4]/Value[1]'),
        // Описваме и изходите за да можем да ги следим в логовете
        'OutD1' => array('unit' => 'OutD1', 'param' => 'Цифров изход 1', 'details' => '(ON,OFF)', 'xmlPath' => '/Entry[9]/Value[1]'),
        'OutD2' => array('unit' => 'OutD2', 'param' => 'Цифров изход 2', 'details' => '(ON,OFF)', 'xmlPath' => '/Entry[10]/Value[1]')
    
    );
    
    
    /**
     * Описания на изходите
     */
    public $outs = array(
        'OutD1' => array('digital' => array('0', '1'), 'cmd' => '/?r1'),
        'OutD2' => array('digital' => array('0', '1'), 'cmd' => '/?r2')
    );
    
    
    /**
     * Колко аларми/контроли да има?
     */
    public $alarmCnt = 3;
    
    
    /**
     * Подготвя формата за настройки на сензора
     * и алармите в зависимост от параметрите му
     */
    public function prepareSettingsForm($form)
    {
        $form->FNC('ip', 'ip', 'caption=IP,hint=Въведете IP адреса на устройството, input, mandatory');
        $form->FNC('port', 'int(5)', 'caption=Port,hint=Порт, input, mandatory,value=80');
        $form->FNC('user', 'varchar(10)', 'caption=User,hint=Потребител, input, mandatory, value=admin, notNull');
        $form->FNC('password', 'password(show)', 'caption=Password,hint=Парола, input, value=admin, notNull');
        
        // Добавя и стандартните параметри
        $this->getSettingsForm($form);
    }
    
    
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
     * Прочита текущото състояние на драйвера/устройството
     */
    public function updateState()
    {
        // Необходимо е само ако ни интересуват предходни стойности на базата на които да правим изчисления
        //$stateOld = $this->loadState();
        
        $settingsArr = (array) $this->getSettings();
        
        $state = array();
        
        $url = "http://{$this->settings->ip}:{$this->settings->port}/m.xml";
        
        $context = stream_context_create(array('http' => array('timeout' => 4)));
        
        $xml = @file_get_contents($url, false, $context);
        
        if (empty($xml) || !$xml) {
            $this->stateArr = null;
            
            return false;
        }
        
        $xml = str_replace('</strong><sup>o</sup>C', '', $xml);
        
        $result = array();
        
        $pRes = @simplexml_load_string($xml);

        if (!$pRes) {
            sens_MsgLog::add($this->id, 'Грешка при парсиране!', 3);
            $this->stateArr = null;

            return false;
        }
        
        $this->XMLToArrayFlat($pRes, $result);
        

        foreach ($this->params as $param => $details) {
            $state[$param] = $result[$details['xmlPath']];
            
            // Ако има изчисляеми параметри
            if (!empty($settingsArr["name_{$param}"]) && $settingsArr["name_{$param}"] != 'empty') {
                $paramValue = $settingsArr["angular_{$param}"] * $state["{$param}"] + $settingsArr["linear_{$param}"];
                $state["{$settingsArr["name_{$param}"]}"] = $paramValue;
            }
                   
            
            if ($details['details'] == '(ON,OFF)') {
                $state[$param] = trim(strtoupper($result[$details['xmlPath']]));
                
                // Санитизираме цифровите входове и изходи
                switch ($state[$param]) {
                    case 'ON':
                        $state[$param] = 1;
                        break;
                    case 'OFF':
                        $state[$param] = 0;
                        break;
                }
            }
        }
        
        $this->stateArr = $state;
        
        return true;
    }
    
    
    /**
     * Сетва изходите на драйвера по зададен масив
     *
     * @return bool
     */
    public function setOuts($outs)
    {
        $baseUrl = "http://{$this->settings->user}:{$this->settings->password}@{$this->settings->ip}:{$this->settings->port}";
        
        foreach ($this->outs as $out => $attr) {
            $res[] = $baseUrl . $attr['cmd'] . '=' . $outs[$out];
        }
        
        // Необходимо ни е Curl за този сензор
        if (!function_exists('curl_init')) {
            sens_MsgLog::add($this->id, 'Инсталирай Curl за PHP!', 3);
            exit(1);
        }
        
        // Превключваме релетата
        foreach ($res as $cmd) {
            $ch = curl_init("${cmd}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        }
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
