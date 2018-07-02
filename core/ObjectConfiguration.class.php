<?php


/**
 * Клас 'core_ObjectConfiguration' - Поддръжка на конфигурационни данни
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_ObjectConfiguration extends core_BaseClass
{
    
    /**
     * Описание на конфигурацията
     */
    public $_description = array();
    

    /**
     * Стойности на константите
     */
    public $_data = array();
    

    /**
     * Конструктор
     */
    public function init($params = array())
    {
        list($description, $data) = $params;
        
        if (is_string($description)) {
            $description = unserialize($description);
        }

        if (is_array($description)) {
            $this->_description = $description;
        }

        if (is_string($data) && strlen($data)) {
            $data = unserialize($data);
        }
        
        if (is_array($data)) {
            $this->_data = $data;
        }
    }


    /**
     * 'Магически' метод, който връща стойността на константата
     */
    public function __get($name)
    {
        if (!Mode::get('stopInvoke')) {
            $this->invoke('BeforeGetConfConst', array(&$value, $name));
        }

        // Търси константата в данните въведени през уеб-интерфейса
        if (!isset($value) && isset($this->_data[$name]) && !(empty($this->_data[$name]) && $this->_data[$name] !== (double) 0 && $this->_data[$name] !== (int) 0)) {
            $value = $this->_data[$name];
        }
 
        // Търси константата като глобално дефинирана
        if (!isset($value) && defined($name)) {
            $value = constant($name);
        }
        
        if (isset($this->_description[$name])) {
            expect(isset($value), "Константата ${name} няма стойност", $this->_description, $this->_data);
        } else {
            expect(isset($value), "Константата ${name} не е дефинирана", $this->_description, $this->_data);
        }

        return $value;
    }


    /**
     * Връща броя на описаните константи
     */
    public function getConstCnt()
    {
        return count($this->_description);
    }


    /**
     * Връща броя на недефинираните константи
     */
    public function haveErrors()
    {
        $cnt = 0;
        if (count($this->_description)) {
            foreach ($this->_description as $name => $descr) {
                $params = arr::make($descr[1], true);
                if (!$params['mandatory']) {
                    continue;
                }
                if (isset($this->_data[$name]) && $this->_data[$name] !== '') {
                    continue;
                }
                if (defined($name) && constant($name) !== '' && constant($name) !== null) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }
}
