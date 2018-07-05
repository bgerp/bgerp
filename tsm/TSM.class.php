<?php



/**
 * Драйвер за единична гравиметрична система на TSM (Modbus TCP/IP)
 *
 *
 * @category  bgerp
 * @package   tsm
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Гравиметрична с-ма TSM
 */
class tsm_TSM extends sens2_ProtoDriver
{
    
    /**
     * Заглавие на драйвера
     */
    public $title = 'TSM';
   

    /**
     * Параметри които чете или записва драйвера
     */
    public $inputs = array(
        'KGH' => array('unit' => 'kg/h', 'caption' => 'Производителност'),
        'EO' => array('unit' => 'kg',   'caption' => 'Произведено'),
    );
    
    
    /**
     * Колко аларми/контроли да има?
     */
    public $alarmCnt = 1;
    
    
    /**
     * Подготвя формата за настройки на сензора
     * и алармите в зависимост от параметрите му
     */
    public function prepareConfigForm($form)
    {
        $form->FNC('ip', new type_Ip(), 'caption=IP,hint=Въведете IP адреса на устройството, input, mandatory');
        $form->FNC('port', 'int(5)', 'caption=Port,hint=Порт, input, mandatory,value=502');
        $form->FNC('unit', 'int(5)', 'caption=Unit,hint=Unit, input, mandatory,value=1');

        // Параметри по подразбиране за настройките
        $form->setDefault('port', 502);
        $form->setDefault('unit', 1);
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
        $res = array();

        $driver = new modbus_Driver;
        $driver->ip = $config->ip;
        $driver->port = $config->port;
        $driver->unit = $config->unit;
        
        // Прочитаме произведеното с компонент 1
        $driver->type = 'double';
        
        // Вземаме текущите количества на 6-те компонента
        $c1 = $driver->read(400446, 2);
        $c2 = $driver->read(400468, 2);
        $c3 = $driver->read(400490, 2);
        $c4 = $driver->read(400512, 2);
        $c5 = $driver->read(400534, 2);
        $c6 = $driver->read(400556, 2);
        
        $output = ($c1[400446] + $c2[400468] + $c3[400490] + $c4[400512] + $c5[400534] + $c6[400556]) / 100;
        
        if (!$output) {
            return "Грешка при четене от {$config->ip}";
        }

        $res['EO'] = $output;
        
        // Минутите от 0-60 са индекси на масива за изчисление на средната стойност
        /*
        $currMin = (int)time()/60;

        $ndx = $currMin % $this->avgCnt;

        $stateOld['avgOutputArr'][$ndx] = $output;

        $state['KGH'] = round((max($stateOld['avgOutputArr']) - min($stateOld['avgOutputArr']))*$this->avgCnt/count($stateOld['avgOutputArr']),2);
        $state['avgOutputArr'] = $stateOld['avgOutputArr'];
*/
        $driver = new modbus_Driver((array) $rec);
        $driver->ip = $config->ip;
        $driver->port = $config->port;
        $driver->unit = $config->unit;
        
        $driver->type = 'words';
        
        
        // Взимаме KGH
        $KGH = $driver->read(400402, 1);
        $res['KGH'] = $KGH[400402] / 100;
        
        // Определяне на рецептата
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
        
        $persistentState['recpt'] = $recpt;
         
        return $res;
    }
}
