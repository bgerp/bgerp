<?php


/**
 * class refactor_Wrapper_Token
 *
 *
 * @category  vendors
 * @package   php
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class refactor_Token
{
    /**
     * Тип на тоукъна като целочислена константа
     *
     * @var int
     */
    public $type;
    
    
    /**
     * Вербален тип на тоукъна
     *
     * @var string
     */
    public $typeVerbal;
    
    
    /**
     * Стрингово съдържание на тоукъна
     *
     * @var string
     */
    public $str;
    
    
    /**
     * Масив с тоукъни, които да се поставят след този
     *
     * @var array
     */
    public $insertAfter = array();
    
    
    /**
     * Масив с тоукъни, които да се поставят преди този
     *
     * @var array
     */
    public $insertBefore = array();
    
    
    /**
     * Флаг, че текущия тоукън трябва да се изтрие
     */
    public $mustDelete = false;
    
    
    /**
     * Конструктор
     */
    public function __construct($type, $typeVerbal, string $str)
    {
        $this->type = $type;
        $this->typeVerbal = $typeVerbal;
        $this->str = $str;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function insertAfter($type, $typeVerbal, $str)
    {
        $add = new refactor_Token($type, $typeVerbal, $str);
        
        $this->insertAfter[] = $add;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function insertBefore($type, $typeVerbal, $str)
    {
        $add = new refactor_Token($type, $typeVerbal, $str);
        
        $this->insertBefore[] = $add;
    }
    
    
    /**
     * Задава изтриване на тоукъна
     */
    public function delete()
    {
        $this->mustDelete = true;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function mustHaveDocComment()
    {
        if (in_array($this->type, array(T_CLASS, T_FUNCTION, T_CONST, T_VAR))) {
            
            return true;
        }
    }
}
