<?php



/**
 * Драйвер за електромер SATEC
 *
 *
 * @category  bgerp
 * @package   sens
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Драйвер за електромер SATEC
 */
class sens_driver_SATEC extends sens_driver_IpDevice
{

    /**
     * Заглавие на драйвера
     */
    public $title = 'SATEC';

    
    /**
     * Параметри които чете или записва драйвера
     */
    public $params = array(
        'kvahTotal' => array('unit' => 'kvahTotal', 'param' => 'Обща енергия', 'details' => 'kvah'),
        'kWhImport' => array('unit' => 'kWhImport', 'param' => 'Входяща енергия', 'details' => 'kWh'),
        'kvarhExport' => array('unit' => 'kvarhExport', 'param' => 'Изходяща енергия/глоба/', 'details' => 'kvarh'),
        'kvarhImport' => array('unit' => 'kvarhImport', 'param' => 'Входяща реактивна енергия', 'details' => 'kvarh'),
        'kWTotal' => array('unit' => 'kWTotal', 'param' => '1 секунди мощност', 'details' => 'kW'),
        'kvarTotal' => array('unit' => 'kvarTotal', 'param' => '1 секунди реактивна мощност', 'details' => 'kvar'),
        'kVATotal' => array('unit' => 'kVATotal', 'param' => '1 секунди активна мощност', 'details' => 'kVA'),
        'PFTotal' => array('unit' => 'PFTotal', 'param' => 'Косинус Фи', 'details' => '')
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
        $form->FNC('unit', 'int(5)', 'caption=Unit,hint=Unit, input, mandatory,value=1');
        
        // Добавя и стандартните параметри
        $this->getSettingsForm($form);
    }
    
    
    /**
     * Връща масив със стойностите на изразходваната активна мощност
     */
    public function updateState()
    {
        $driver = new modbus_Driver((array) $rec);
        
        $driver->ip = $this->settings->ip;
        $driver->port = $this->settings->port;
        $driver->unit = $this->settings->unit;
        
        // Прочитаме изчерпаната до сега мощност
        $driver->type = 'words';
        
        $addresses = $driver->read(405073, 2);
        $state['kvahTotal'] = $addresses['405073'] + $addresses['405074'] * 65535;
        $addresses = $driver->read(405057, 2);
        $state['kWhImport'] = $addresses['405057'] + $addresses['405058'] * 65535;
        $addresses = $driver->read(405067, 2);
        $state['kvarhExport'] = $addresses['405067'] + $addresses['405068'] * 65535;
        $addresses = $driver->read(414337, 2);
        $state['kWTotal'] = $addresses['414337'] + $addresses['414338'] * 65535;
        $addresses = $driver->read(405065, 2);
        $state['kvarhImport'] = $addresses['405065'] + $addresses['405066'] * 65535;
        $addresses = $driver->read(414339, 2);
        $state['kvarTotal'] = $addresses['414339'] - $addresses['414340'];
        $addresses = $driver->read(414341, 2);
        $state['kVATotal'] = $addresses['414341'] + $addresses['414342'] * 65535;
        $addresses = $driver->read(414343, 2);
        $state['PFTotal'] = round(($addresses['414343'] - $addresses['414344']) / 1000, 4);
        
        if (empty($addresses)) {
            $this->stateArr = null;
            
            return false;
        }
        
        $this->stateArr = $state;
        
        return true;
    }
}
