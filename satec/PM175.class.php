<?php


/**
 * Драйвер за електромер SATEC
 *
 *
 * @category  bgerp
 * @package   satec
 *
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Електромер SATEC
 *
 * @see       http://www.satec-global.com/UserFiles/satec/files/314_PM175%20Modbus.pdf
 */
class satec_PM175 extends sens2_ProtoDriver
{
    /**
     * Заглавие на драйвера
     */
    public $title = 'SATEC PM175';
    
    
    /**
     * Описание на входовете
     */
    public $inputs = array(
        'kvahTotal' => array('caption' => 'Обща енергия', 'uom' => 'kVAh'),
        'kWhImport' => array('caption' => 'Входяща енергия', 'uom' => 'kWh'),
        'kvarhExport' => array('caption' => 'Реакт. изх. енергия', 'uom' => 'kVArh'),
        'kvarhImport' => array('caption' => 'Реакт. вх. енергия', 'uom' => 'kVArh'),
        'kWTotal' => array('caption' => '1 сек. мощност', 'uom' => 'kW'),
        'kvarTotal' => array('caption' => '1 сек. реакт. мощност', 'uom' => 'kVAr'),
        'kVATotal' => array('caption' => '1 сек. акт. мощност', 'uom' => 'kVA'),
        'PFTotal' => array('caption' => 'Косинус Фи', 'uom' => ''),
    );
    
    
    /**
     *  Информация за входните портове на устройството
     *
     * @see  sens2_DriverIntf
     *
     * @return array
     */
    public function getInputPorts($config = null)
    {
        foreach ($this->inputs as $name => $params) {
            $res[$name] = (object) array('caption' => $params['caption'], 'uom' => $params['uom']);
        }
        
        return $res;
    }
    
    
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
        $form->FNC('port', 'int(5)', 'caption=Port,hint=Порт, input, mandatory,value=502');
        $form->FNC('unit', 'int(5)', 'caption=Unit,hint=Unit, input, mandatory,value=1');
        
        // Стойности по подразбиране
        $form->setDefault('port', 502);
        $form->setDefault('unit', 1);
    }
    
    
    /**
     * Връща масив със стойностите на изразходваната активна мощност
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        $driver = new modbus_Driver();
        
        $driver->ip = $config->ip;
        $driver->port = $config->port;
        $driver->unit = $config->unit;
        $driver->type = 'words';
        
        // Прочитаме изчерпаната до сега мощност
        $addresses = $driver->read(405073, 2);
        $res['kvahTotal'] = $addresses['405073'] + $addresses['405074'] * 65535;
        
        $addresses = $driver->read(405057, 2);
        $res['kWhImport'] = $addresses['405057'] + $addresses['405058'] * 65535;
        
        $addresses = $driver->read(405067, 2);
        $res['kvarhExport'] = $addresses['405067'] + $addresses['405068'] * 65535;
        
        $addresses = $driver->read(414337, 2);
        $res['kWTotal'] = $addresses['414337'] + $addresses['414338'] * 65535;
        
        $addresses = $driver->read(405065, 2);
        $res['kvarhImport'] = $addresses['405065'] + $addresses['405066'] * 65535;
        
        $addresses = $driver->read(414339, 2);
        $res['kvarTotal'] = $addresses['414339'] - $addresses['414340'];
        
        $addresses = $driver->read(414341, 2);
        $res['kVATotal'] = $addresses['414341'] + $addresses['414342'] * 65535;
        
        $addresses = $driver->read(414343, 2);
        $res['PFTotal'] = round(($addresses['414343'] - $addresses['414344']) / 1000, 4);
        
        if (empty($addresses)) {
            
            return "Грешка при четене от {$config->ip}";
        }
        
        return $res;
    }
}
