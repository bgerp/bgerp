<?php

class core_exception_Expect extends Exception
{
    /**
     * Вербален тип на изключението
     */
    private $type;
    
    
    /**
     * Променливи, които да се дъмпват
     */
    private $dump;
    

    /**
     *  Конструктор на изключението
     */
    public function __construct($message = "", $type = 'Изключение', $dump = NULL)
    {
        parent::__construct($message);
        
        $this->type  = $type;
        $this->dump  = $dump;
    }


    /**
     * Връща параметъра $type
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * Връща параметъра $dump
     */
    public function getDump()
    {
        return $this->dump;
    }

    
    public function getDebug()
    {
        return $this->dump;
    }
}