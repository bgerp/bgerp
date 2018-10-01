<?php


/**
 * Имитация на драйвер за IP сензор
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
 * @title     Имитация на драйвер за IP сензор
 */
class sens_driver_Mockup extends sens_driver_IpDevice
{
    /**
     * Заглавие на драйвера
     */
    public $title = 'Mockup';
    
    
    /**
     * Брой последни стойности на базата на които се изчислява средна стойност
     *
     * @var int
     */
    public $avgCnt = 10;
    
    
    /**
     * Параметри които чете или записва драйвера
     */
    public $params = array(
        'T' => array('unit' => 'T', 'param' => 'Температура', 'details' => 'C'),
        'Hr' => array('unit' => 'Hr', 'param' => 'Влажност', 'details' => '%'),
        'Dst' => array('unit' => 'Dst', 'param' => 'Запрашеност', 'details' => '%'),
        'Chm' => array('unit' => 'Chm', 'param' => 'Хим. замърсяване', 'details' => '%'),
        'avgHr' => array('unit' => 'avgHr', 'param' => 'Средна влажност', 'details' => '%'),
        'InA1' => array('unit' => 'InA1', 'param' => 'Аналогов вход 1', 'details' => 'V'),
        
        // Ако искаме описваме и изходите за да можем да ги следим в логовете
        'OutD1' => array('unit' => 'Out1', 'param' => 'Изход 1', 'details' => '(ON/OFF)'),
        'OutD2' => array('unit' => 'Out2', 'param' => 'Изход 2', 'details' => '(ON/OFF)')
    );
    
    
    /**
     * Описания на изходите ако има такива
     * Съдържащите 'D' - digital, 'A' - analog
     */
    public $outs = array(
        'OutD1' => array('digital' => array('0', '1')),
        'OutD2' => array('digital' => array('0', '1'))
    );
    
    
    /**
     * Брой аларми
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
        $this->getSettingsForm($form);
    }
    
    
    /**
     * Прочита текущото състояние на драйвера/устройството
     */
    public function updateState()
    {
        $settingsArr = (array) $this->getSettings();
        
        $stateOld = $this->loadState();
        
        $state = array();
        
        foreach ($this->params as $param => $dummy) {
            switch ($param) {
                case 'T':
                    $state['T'] = $stateOld['T'] + rand(-2, 2);
                    
                    if (date('H') > '08' && date('H') < '19') {
                        $state['T'] += 0.1;
                    }
                    
                    if (date('H') < '08' || date('H') > '19') {
                        $state['T'] -= 0.1;
                    }
                    
                    // Ако е включен изход 1 - предполагаме, че включва климатик и температурата пада
                    if ($stateOld['OutD1'] == 1) {
                        $state['T'] -= 2;
                    }
                    break;
                case 'Hr':
                    $state['Hr'] = rand(0, 100);
                    break;
                case 'InA1':
                    $stateOld['InA1'] += rand(-1, 1);
                    if ($stateOld['InA1'] < 0) {
                        $stateOld['InA1'] = 0;
                    }
                    if ($stateOld['InA1'] > 10) {
                        $stateOld['InA1'] = 10;
                    }
                    $state['InA1'] = $stateOld['InA1'];
                    
                    // Проверяваме в сетингите на драйвера, дали има зададено преизчисляване на аналоговия вход
                   if (!empty($settingsArr["name_{$param}"]) && $settingsArr["name_{$param}"] != 'empty') {
                       // Изчисляваме новата стойност по линейната зависимост
                       $paramValue = $settingsArr["angular_{$param}"] * $state["{$param}"] + $settingsArr["linear_{$param}"];
                       
                       // Присвояваме стойността на новия параметър
                       $state["{$settingsArr["name_{$param}"]}"] = $paramValue;
                   }
                    break;
                case 'avgHr':
                    
                    // Тук взимаме историята на влажностите за изчисляването на средната стойност
                    $state['avgHrArr'] = $stateOld['avgHrArr'];
                    
                    $ndx = ((int) time() / 60) % $this->avgCnt;
                    $state['avgHrArr'][$ndx] = $state['Hr'];
                    $state['avgHr'] = array_sum($state['avgHrArr']) / count($state['avgHrArr']);
                    break;
                default:
                if (!isset($this->outs[$param])) {
                    $state[$param] = '';     // Не е зададен начин на изчисление /все едно не е закачен датчик/
                }
                break;
            }
        }
        
        $outs = permanent_Data::read('sens_driver_mockupOuts');
        
        $this->stateArr = array_merge((array) $outs, $state);
        
        // Записваме състоянието като обект а не масив
//        $this->setSettings(json_decode(json_encode($settingsArr)));
        
        // Връщаме TRUE при успешно четене
        return true;
    }
    
    
    /**
     * Сетва изходите на драйвера по зададен масив
     *
     * @param array() $newOuts
     *
     * @return bool
     */
    public function setOuts($newOuts)
    {
        // Сетваме изходите според масива $outs
        foreach ($this->outs as $out => $type) {
            $outs[$out] = $newOuts[$out];
        }
        
        // За Ментак-а ползваме permanent_Data за да предаваме състоянието
        permanent_Data::write('sens_driver_mockupOuts', $outs);
    }
}
