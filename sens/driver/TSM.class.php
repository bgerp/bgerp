<?php


/**
 * Драйвер за единична гравиметрична система на TSM (Modbus TCP/IP)
 *
 *
 * @category  bgerp
 * @package   sens
 *
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Драйвер за единична гравиметрична система на TSM
 */
class sens_driver_TSM extends sens_driver_IpDevice
{
    /**
     * Заглавие на драйвера
     */
    public $title = 'TSM';
    
    
    /**
     * Параметри които чете или записва драйвера
     */
    public $params = array(
        'KGH' => array('unit' => 'KGH', 'param' => 'Килограми за час', 'details' => 'Kgh'),
        'EO' => array('unit' => 'EO', 'param' => 'Килограми', 'details' => 'Kg'),
        'ERC' => array('unit' => 'ERC', 'param' => 'Рецепта', 'details' => '%', 'onChange' => true)
    );
    
    
    /**
     * Колко аларми/контроли да има?
     */
    public $alarmCnt = 1;
    
    
    /**
     * Подготвя формата за настройки на сензора
     * и алармите в зависимост от параметрите му
     */
    public function prepareSettingsForm($form)
    {
        $form->FNC('ip', new type_Ip(), 'caption=IP,hint=Въведете IP адреса на устройството, input, mandatory');
        $form->FNC('port', 'int(5)', 'caption=Port,hint=Порт, input, mandatory,value=502');
        $form->FNC('unit', 'int(5)', 'caption=Unit,hint=Unit, input, mandatory,value=1');
        
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
     * Връща масив със стойностите на температурата и влажността
     */
    public function updateState()
    {
        $state = array();
        
        $stateOld = $this->loadState();
        
        $driver = new modbus_Driver((array) $rec);
        
        $driver->ip = $this->settings->ip;
        $driver->port = $this->settings->port;
        $driver->unit = $this->settings->unit;
        
        // Прочитаме произведеното с компонент 1
        $driver->type = 'double';
        
        $c1 = $driver->read(400446, 2);
        
        $c2 = $driver->read(400468, 2);
        
        $c3 = $driver->read(400490, 2);
        $c4 = $driver->read(400512, 2);
        $c5 = $driver->read(400534, 2);
        $c6 = $driver->read(400556, 2);
        
        $output = ($c1[400446] + $c2[400468] + $c3[400490] + $c4[400512] + $c5[400534] + $c6[400556]) / 100;
        
        if (!$output) {
            $this->stateArr = null;
            
            return false;
        }
        
        // Минутите от 0-60 са индекси на масива за изчисление на средната стойност
        /*
        $currMin = (int)time()/60;

        $ndx = $currMin % $this->avgCnt;

        $stateOld['avgOutputArr'][$ndx] = $output;

        $state['KGH'] = round((max($stateOld['avgOutputArr']) - min($stateOld['avgOutputArr']))*$this->avgCnt/count($stateOld['avgOutputArr']),2);
        $state['avgOutputArr'] = $stateOld['avgOutputArr'];
*/
        $driver = new modbus_Driver((array) $rec);
        
        $driver->ip = $this->settings->ip;
        $driver->port = $this->settings->port;
        $driver->unit = $this->settings->unit;
        
        $driver->type = 'words';
        
        $p1 = $driver->read(400001, 1);
        $p1 = $p1[400001];
        
        $p2 = $driver->read(400002, 1);
        $p2 = $p2[400002];
        
        $p3 = $driver->read(400003, 1);
        $p3 = $p3[400003];
        
        $p4 = $driver->read(400004, 1);
        $p4 = $p4[400004];
        
        $p5 = $driver->read(400005, 1);
        $p5 = $p5[400005];
        
        $p6 = $driver->read(400006, 1);
        $p6 = $p6[400006];
        
        // Взимаме KGH
        $KGH = $driver->read(400402, 1);
        $state['KGH'] = $KGH[400402] / 100;
        
        if ($p1) {
            $recpt .= '[1] => ' . $p1 / 100;
        }
        
        if ($p2) {
            $recpt .= ($recpt ? ', ' : '') . '[2] => ' . $p2 / 100;
        }
        
        if ($p3) {
            $recpt .= ($recpt ? ', ' : '') . '[3] => ' . $p3 / 100;
        }
        
        if ($p4) {
            $recpt .= ($recpt ? ', ' : '') . '[4] => ' . $p4 / 100;
        }
        
        if ($p5) {
            $recpt .= ($recpt ? ', ' : '') . '[5] => ' . $p5 / 100;
        }
        
        if ($p6) {
            $recpt .= ($recpt ? ', ' : '') . '[6] => ' . $p6 / 100;
        }
        
        $state['EO'] = $output;
        $state['ERC'] = $recpt;
        
        $this->stateArr = $state;
        
        return true;
    }
}
