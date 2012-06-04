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
 * @link
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

            if(substr($value, 0, 2) == ':=') {
                $value = trim(substr($value, 2));
                
                $params = explode('::', $value);
                 
                // Очакваме поне Класс::метод
                expect(count($params) >= 2, $params);
                
                // Съставяме масива за извикване на метод
                $method[0] = $params[0];
                $method[1] = $params[1];
                
                // Махаме от параметрите класа и метода
                array_shift($params);
                array_shift($params);

                $value = call_user_method_array($method, $params);

            }
        }

        expect(isset($value), "Недефинирана константа $name", $this->_description, $this->_data);

        return $value;
    }
    
}