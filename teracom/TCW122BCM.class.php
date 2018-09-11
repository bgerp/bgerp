<?php


/**
 * Драйвер за IP контролер Teracom TCW122B-CM
 *
 * @see http://www.teracom.cc/index.php/products/monitoring-and-control/ethernet-controllers/tcw122b-cm.html
 *
 * @category  bgerp
 * @package   sens
 *
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @title     Тераком TCW122B-CM
 */
class teracom_TCW122BCM extends sens2_ProtoDriver
{
    /**
     * Заглавие на драйвера
     */
    public $title = 'TCW122B-CM';
    
    
    /**
     * Описание на входовете на драйвера
     */
    public $inputs = array(
        'T1' => array('caption' => 'Температура 1', 'uom' => 'ºC', 'xmlPath' => '/Temperature1[1]'),
        'T2' => array('caption' => 'Температура 2', 'uom' => 'ºC', 'xmlPath' => '/Temperature2[1]'),
        'Hr1' => array('caption' => 'Влажност 1', 'uom' => '%', 'xmlPath' => '/Humidity1[1]'),
        'Hr2' => array('caption' => 'Влажност 2', 'uom' => '%', 'xmlPath' => '/Humidity2[1]'),
        'InD1' => array('caption' => 'Цифров вход 1', 'uom' => '', 'xmlPath' => '/DigitalInput1[1]'),
        'InD2' => array('caption' => 'Цифров вход 2', 'uom' => '', 'xmlPath' => '/DigitalInput2[1]'),
        'InA1' => array('caption' => 'Аналогов вход 1', 'uom' => 'V', 'xmlPath' => '/AnalogInput1[1]'),
        'InA2' => array('caption' => 'Аналогов вход 2', 'uom' => 'V', 'xmlPath' => '/AnalogInput2[1]')
    );
    
    
    /**
     * Описание на изходите на драйвера
     */
    public $outputs = array(
        'OutD1' => array('caption' => 'Цифров изход 1', 'uom' => '', 'xmlPath' => '/Relay1[1]', 'cmd' => 'r1'),
        'OutD2' => array('caption' => 'Цифров изход 2', 'uom' => '', 'xmlPath' => '/Relay2[1]', 'cmd' => 'r2')
    );
    
    
    /**
     *  Информация за входните портове на устройството
     *
     * @see  sens2_ControllerIntf
     *
     * @return array
     */
    public function getInputPorts($config = null)
    {
        foreach ($this->inputs as $name => $params) {
            $res[$name] = (object) array (
                                          'caption'     => $params['caption'],
                                          'uom'         => $params['uom'],
                                          'readPeriod'  => $config->{$name . '_update'}, 
                                          'logPeriod'    => $config->{$name . '_log'}
                                         );
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
        foreach ($this->outputs as $name => $params) {
            $res[$name] = array('caption' => $params['caption'], 'uom' => $params['uom']);
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
        $form->FNC('ip', 'ip', 'caption=IP,hint=Въведете IP адреса на устройството, input, mandatory');
        $form->FNC('port', 'int(5)', 'caption=Port,hint=Порт, input, mandatory,value=80');
        $form->FNC('user', 'varchar(10)', 'caption=User,hint=Потребител, input, mandatory, value=admin, notNull');
        $form->FNC('password', 'password(show)', 'caption=Password,hint=Парола, input, value=admin, notNull,autocomplete=off');
    }
    
    
    /**
     * Прочита стойностите от сензорните входове
     *
     * @see  sens2_ControllerIntf
     *
     * @param array $inputs
     * @param array $config
     * @param array $persistentState
     *
     * @return mixed
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        // Подготвяме URL-то
        $url = new ET('http://[#user#]:[#password#]@[#ip#]:[#port#]/status.xml');
        $url->placeArray($config);
        $url = $url->getContent();
        log_System::add(get_called_class(), 'url: ' . $url);
        
        // Извличаме XML-a
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $xml = curl_exec($ch);
        curl_close($ch);
        
        // Ако не сме получили xml - връщаме грешка
        if (empty($xml) || !$xml) {
            
            return "Грешка при четене от {$config->ip}:{$config->port}";
        }
        
        log_System::add(get_called_class(), 'url: ' . $url);
        
        log_System::add(get_called_class(), 'xml: ' . $xml);
        
        // Парсираме XML-а
        $result = array();
        @core_Xml::toArrayFlat(simplexml_load_string($xml), $result);
        
        // Ако реазултата не е коректен
        if (!count($result)) {
            
            return "Грешка при парсиране на XML от {$config->ip}:{$config->port}";
        }
        
        // Извличаме състоянията на входовете от парсирания XML
        foreach ($this->inputs as $name => $details) {
            if ($inputs[$name]) {
                $res[$name] = $result[$details['xmlPath']];
            }
        }
        
        // Извличаме състоянията на изходите от парсирания XML
        foreach ($this->outputs as $name => $details) {
            if ($inputs[$name]) {
                $res[$name] = $result[$details['xmlPath']];
            }
        }
        
        // Цифровизираме стойностите
        foreach ($res as $name => $value) {
            if ($value) {
                switch (strtoupper($value)) {
                    case 'ON':
                    case 'OPEN':
                        $res[$name] = 1;
                        break;
                    case 'OFF':
                    case 'CLOSED':
                        $res[$name] = 0;
                        break;
                    default:
                        $res[$name] = (float) $value;
                }
            }
        }
        
        log_System::add(get_called_class(), 'res: ' . serialize($res));
        
        return $res;
    }
    
    
    /**
     * Записва стойностите на изходите на контролера
     *
     * @param array $outputs         масив със системните имена на изходите и стойностите, които трябва да бъдат записани
     * @param array $config          конфигурациони параметри
     * @param array $persistentState персистентно състояние на контролера от базата данни
     *
     * @return array Масив със системните имена на изходите и статус (TRUE/FALSE) на операцията с него
     */
    public function writeOutputs($outputs, $config, &$persistentState)
    {
        if ($config->user) {
            $baseUrl = new ET('http://[#user#]:[#password#]@[#ip#]:[#port#]/status.xml?');
        } else {
            $baseUrl = new ET('http://[#ip#]:[#port#]/status.xml?');
        }
        
        $baseUrl->placeArray($config);
        $baseUrl = $baseUrl->getContent();
        
        foreach ($this->outputs as $out => $attr) {
            if (isset($outputs[$out])) {
                $res[$out] = $baseUrl . $attr['cmd'] . '=' . $outputs[$out];
            }
        }
        
        // Превключваме релетата
        foreach ($res as $out => $cmd) {
            $ch = curl_init("${cmd}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            $res[$out] = curl_exec($ch);
            curl_close($ch);
        }
        
        return $res;
    }
}
