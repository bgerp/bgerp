<?php



/**
 * Драйвер контролер TCW-121 на фирма Тераком ООД
 *
 * @see http://www.teracom.cc/index.php/component/content/article/1/40-ip-monitoring-tcw121.html
 *
 * @category  vendors
 * @package   teracom
 * @author    Dimiter Minekov <mitko@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @title     Тераком TCW121
 */
class teracom_TCW121 extends sens2_ProtoDriver
{
    
    /**
     * Заглавие на драйвера
     */
    var $title = 'TCW121';
    
        
    /**
     * Описание на входовете
     */
    var $inputs = array(
        'T1' => array('caption'=>'Температура 1', 'uom' => 'ºC', 'xmlPath'=>'/Entry[5]/Value[1]'),
        'T2' => array('caption'=>'Температура 2', 'uom' => 'ºC', 'xmlPath'=>'/Entry[6]/Value[1]'),
        'Hr1' => array('caption'=>'Влажност 1', 'uom' => '%', 'xmlPath'=>'/Entry[7]/Value[1]'),
        'Hr2' => array('caption'=>'Влажност 2', 'uom' => '%', 'xmlPath'=>'/Entry[8]/Value[1]'),
    	'InD1' => array('caption'=>'Цифров вход 1', 'uom' => '', 'xmlPath'=>'/Entry[1]/Value[1]'),
        'InD2' => array('caption'=>'Цифров вход 2', 'uom'=>'', 'xmlPath'=>'/Entry[2]/Value[1]'),
        'InA1' => array('caption'=>'Аналогов вход 1', 'uom'=>'V', 'xmlPath'=>'/Entry[3]/Value[1]'),
        'InA2' => array('caption'=>'Аналогов вход 2', 'uom'=>'V', 'xmlPath'=>'/Entry[4]/Value[1]'),
    );
    
    
    /**
     * Описания на изходите
     */
    var $outputs = array(
        'OutD1' => array('caption'=>'Цифров изход 1', 'uom'=>'', 'xmlPath' => '/Entry[9]/Value[1]', 'cmd' => '/?r1'),
        'OutD2' => array('caption'=>'Цифров изход 2', 'uom'=>'', 'xmlPath' => '/Entry[10]/Value[1]', 'cmd' => '/?r2'),
    );
    
    
    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @see  sens2_DriverIntf
     *
     * @param core_Form
     */
    function prepareConfigForm($form)
    {
        $form->FNC('ip', 'ip', 'caption=IP,hint=Въведете IP адреса на устройството, input, mandatory');
        $form->FNC('port', 'int(5)', 'caption=Port,hint=Порт, input, mandatory');
        $form->FNC('user', 'varchar(10)', 'caption=User,hint=Потребител, input, mandatory, value=admin, notNull');
        $form->FNC('password', 'password(allowEmpty)', 'caption=Password,hint=Парола, input, value=admin, notNull,autocomplete=off');

        // Параметри по подразбиране за настройките
        $form->setDefault('port', 80);
    }
    


    /**
     * Прочита стойностите от сензорните входове
     *
     * @see  sens2_DriverIntf
     *
     * @param   array   $inputs
     * @param   array   $config
     * @param   array   $persistentState
     * 
     * @return  mixed
     */
    function readInputs($inputs, $config, &$persistentState)
    {
        // Подготвяме URL-то
        $url = new ET("http://[#ip#]:[#port#]/m.xml");
        $url->placeArray($config);
        $url = $url->getContent();
        
        // echo "<li> $url";

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
            
            return "Грешка при четене от {$config->ip}";
        }
        
        // Малък бъгфикс на фирмуеъра на контролера
        $xml = str_replace('</strong><sup>o</sup>C', '', $xml);

        // echo "<br><pre>$xml</pre>";

        // Парсираме XML-а
        $result = array();
        core_Xml::toArrayFlat(simplexml_load_string($xml), $result);
        
        // Извличаме състоянията на входовете от парсирания XML
        foreach ($this->inputs as $name => $details) {
            if($inputs[$name]) {
                $res[$name] = $result[$details['xmlPath']];
            }
        }
        
        // Извличаме състоянията на изходите от парсирания XML
        foreach ($this->outputs as $name => $details) {
            if($inputs[$name]) {
                $res[$name] = $result[$details['xmlPath']];
            }
        }
        
        // Цифровизираме стойностите
        foreach($res as $name => $value) {
            if($value) {
                switch (strtoupper($value)) {
                    case 'ON':
                        $res[$name] = 1;
                        break;
                    case 'OFF':
                        $res[$name] = 0;
                        break;
                    default:
                        $res[$name] = (float) $value;
                }
            }
        }

        return $res;
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
        
         
        // Превключваме релетата
        foreach ($res as $cmd) {
            $ch = curl_init("$cmd");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_exec($ch);
            curl_close($ch);
        }
    }
    
}