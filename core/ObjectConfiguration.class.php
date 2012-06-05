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
class core_ObjectConfiguration
{
    
    /**
     * Описание на конфигурацията
     */
    private $_description = array();
    
    /**
     * Стойности на константите
     */
    private $_data = array();
    
    /**
     * Конструктор
     */
    public function __construct($description, $data)
    {
        if (is_string($description)) {
            $description = unserialize($description);
        }

        if(is_array($description)) {
            $this->_description = $description;
        }

        if (is_string($data) && strlen($data)) {
            $data = unserialize($data);
        }
        
        if(is_array($data)) {
            $this->_data = $data;
        }
    }


    /**
     * 'Магически' метод, който връща стойността на константата
     */
    function __get($name)
    { 
        // Търси константата в данните въведени през уеб-интерфейса
        if(!empty($this->_data[$name])) {

            $value = $this->_data[$name];
        }

        // Търси константата като глобално дефинирана
        if(!isset($value) && defined($name)) {

            $value = constant($name);

        }

        expect(isset($value), "Недефинирана константа $name", $this->_description, $this->_data);

        return $value;
    }


    /**
     * Връща броя на описаните константи
     */
    function getConstCnt()
    {
        return count($this->_description);
    }


    /**
     * Връща броя на недефинираните константи
     */
    function haveErrors()
    {
        $cnt = 0;
        if(count($this->_description)) {
            foreach($this->_description as $name => $descr) {
                $params = arr::make($descr[1], TRUE);
                if(!$params['mandatory']) continue;
                if(isset($this->_data[$name]) && $this->_data[$name] !== '') continue;
                if(defined($name) && constant($name) !== '' && constant($name) !== NULL) continue;

                return TRUE;
            }
        }

        return FALSE;
    }
    
}