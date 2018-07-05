<?php



/**
 * Драйвер за IP сензор HWg-STE - мери температура и влажност
 *
 *
 * @category  bgerp
 * @package   hwgroup
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Сензори HWgSTE
 */
class hwgroup_HWgSTE extends sens2_ProtoDriver
{
    
    /**
     * Заглавие на драйвера
     */
    public $title = 'HWgSTE';


    /**
     * Описание на входовете
     */
    public $inputs = array(
        'T' => array('caption' => 'Температура', 'uom' => 'ºC', 'xmlPath' => '/SenSet[1]/Entry[1]/Value[1]'),
        'Hr' => array('caption' => 'Влажност', 'uom' => '%', 'xmlPath' => '/SenSet[1]/Entry[2]/Value[1]'),
    );


    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @see  sens2_DriverIntf
     *
     * @param core_Form
     */
    public function prepareConfigForm($form)
    {
        $form->FNC('ip', 'ip', 'caption=IP,hint=Въведете IP адреса на устройството, input, mandatory');
        $form->FNC('port', 'int(5)', 'caption=Port,hint=Порт, input, mandatory');

        // Параметри по подразбиране за настройките
        $form->setDefault('port', 80);
    }

    
    /**
     * Прочита стойностите от сензорните входове
     *
     * @see  sens2_DriverIntf
     *
     * @param array $inputs
     * @param array $config
     * @param array $persistentState
     *
     * @return mixed
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        $state = array();
        
        $url = "http://{$config->ip}:{$config->port}/values.xml";
        
        $context = stream_context_create(array('http' => array('timeout' => 4)));
        
        $xml = @file_get_contents($url, false, $context);
        
        if (empty($xml) || !$xml) {
            
            return "Грешка при четене от {$config->ip}:{$config->port}";
        }
        
        $parsed = $res = array();
        
        core_Xml::toArrayFlat(simplexml_load_string($xml), $parsed);
        
        foreach ($this->inputs as $param => $details) {
            $res[$param] = $parsed[$details['xmlPath']];
        }
                
        return $res;
    }
}
