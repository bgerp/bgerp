<?php

class core_Model
{
    /**
     * @var string|int|core_Mvc
     */
    public static $mvc;
        
    /**
     * @var int
     */
    public $id;
    
    /**
     * @var core_Mvc
     */
    protected $_mvc;

    /**
     * @var array
     */
    protected $_details = array();
    
    public function __construct($id)
    {
        expect($this->_mvc = cls::get(static::$mvc));
    
        if (isset($id)) {
            $rec = $this->fetch($id);
            $this->init($rec);
        }
    }

    public function fetch($id)
    {
        return $this->_mvc->fetchRec($id);
    }
    
    
    public function save($fields = null, $mode = null)
    {
        return $this->_mvc->save($this, $fields, $mode);
    }
    
    
    public function init($rec)
    {
        expect(is_object($rec));

        foreach (array_keys(get_object_vars($this)) as $prop) {
            if (isset($rec->{$prop})) {
                $this->{$prop} = $rec->{$prop};
            }
        }
    }
    
    
    /**
     *
     * @param  core_Mvc $detailMvc
     * @return array
     */
    public function getDetails($detailMvc, $detailModel = null)
    {
        if (is_scalar($detailMvc)) {
            $detailMvc = cls::get($detailMvc);
        }
    
        $detailName = cls::getClassName($detailMvc);
    
        if (!isset($this->_details[$detailName])) {
            $this->_details[$detailName] = array();
    
            if (!empty($this->id)) {
                /* @var $query core_Query */
                $query = $detailMvc->getQuery();
                
                while ($rec = $query->fetch("#{$detailMvc->masterKey} = {$this->id}")) {
                    if (isset($detailModel)) {
                        $rec = new $detailModel($rec);
                    }
                    
                    $this->_details[$detailName][] = $rec;
                }
            }
        }
    
        return $this->_details[$detailName];
    }
    
    public function __get($property)
    {
        if (method_exists($this, "calc_{$property}")) {
            return $this->{"calc_{$property}"}();
        }
    }
}
