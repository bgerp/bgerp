<?php



/**
 * Клас 'core_ObjectCollection' - Масив - колекция от обекти
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
class core_ObjectCollection implements  Iterator
{
 

    var $container = array();

    var $fields = array();

    public function __construct($param)
    {
        if (is_array($param)) {
            $this->container = $param;
        } elseif(is_string($param)) {
            $this->fields = arr::make($param);
        }
    }

    public function rewind()
    {
        reset($this->container);
    }
  
    public function current()
    {
        $var = current($this->container);

        return $var;
    }
  
    public function key() 
    {
        $var = key($this->container);

        return $var;
    }
  
    public function next() 
    {
        $var = next($this->container);

        return $var;
    }
  
    public function valid()
    {
        $key = key($this->container);
        $var = ($key !== NULL && $key !== FALSE);

        return $var;
    }


    /**
     * Добавя елемент в контейнера
     */
    public function add($val)
    {
        if(count($this->fields)) {
            $args = func_get_args();

            $obj = new stdClass();

            foreach($this->fields as $id => $fname) {
                $obj->{$fname} = $args[$id];
            }
            $this->container[] = $obj;
            if(!$obj->order) {
                $obj->order = count($this->container);
            }
        } else {
            $this->container[] = $val;
        }
    }
    
    
    /**
     * Сортира масив от обекти по зададеното поле поле (по подразбиране - 'order')
     */
    function order($field = 'order')
    {
        arr::order($this->container, $field);
    }


}