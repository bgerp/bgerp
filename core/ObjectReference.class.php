<?php



/**
 * Указател към обект от зададен клас, евентуално приведен (cast) към зададен интерфейс.
 *
 *
 * @category  ef
 * @package   core
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 * @property string valiorFld
 * @method fetch(mixed $fields = '*', boolean $cache = TRUE)
 * @method fetchField(string $field = 'id', boolean $cache = TRUE)
 * @method core_Query getQuery()
 * @method getHandle()
 * @method getHyperlink(boolean $icon = FALSE, boolean $short = FALSE)
 * @method getShortHyperlink(boolean $icon = FALSE)
 * @method getSingleUrlArray()
 * @method getClassId()
 * @method getLink(boolean $maxLength = FALSE, array $attr = array())
 * @method getTitleById(boolean $escaped)
 * @method getAggregateDealInfo()
 * @method forceCoverAndFolder($bForce = TRUE)
 * @method getShipmentOperations()
 * @method getOrigin()
 * @method getDocumentRow()
 * @method getIcon()
 */
class core_ObjectReference
{
    
    
    /**
     * От кой клас е обекта, към който сочи указателя
     *
     * @var string име на клас
     */
    public $className;
    
    
    /**
     * Данни, които уникално идентифицират обект от класа.
     *
     * Това може да бъде първичния ключ на модела (id от БД). Също може да е цял запис от модела.
     * Зависи от конкретния интерфейс.
     *
     * Ако е зададен интерфейс, тази стойност се поставя като първи параметър при извикването
     * на същинския метод-реализация.
     *
     * @var mixed
     */
    public $that;
    
    
    /**
     * Интерфейс, към който да бъде приведен обекта
     *
     * @var int key(mvc=core_Interfaces)
     */
    public $interface;
    
    
    /**
     * Инстанция на обекта, съдържащ методите-реализации
     *
     * @var object
     */
    public $instance;
    
    
    /**
     * Конструктор
     *
     * @param mixed  $class     име на клас, key(mvc=core_Classes) или инстанция на клас (т.е. обект)
     * @param mixed  $object
     * @param string $interface име на интерфейс
     */
    public function __construct($classId, $object, $interface = null)
    {
        $this->className = cls::getClassName($classId);
        $this->that = $object;
        
        if ($interface) {
            $this->interface = $interface;
            $this->instance = cls::getInterface($interface, $classId);
        } else {
            $this->instance = cls::get($classId);
        }
    }
    
    
    /**
     * Поставя $that в началото на списъка с аргументи и препредава контрола на същинския метод-реализация
     *
     * Не прави проверка, дали той съществува, защото реализацията би могла да бъде индиректна -
     * в плъгин.
     *
     * @param string $method
     * @param array  $args
     */
    public function __call($method, $args)
    {
        array_unshift($args, $this->that);
        
        return call_user_func_array(array($this->instance, $method), $args);
    }
    
    
    public function __get($property)
    {
        return $this->instance->{$property};
    }
    
    
    public function __isset($property)
    {
        return isset($this->instance->{$property});
    }
    
    
    /**
     * Инстанция на класа на обекта, към който сочи този указател
     *
     * @return object
     */
    public function getInstance()
    {
        if (is_null($this->interface)) {
            return $this->instance;
        }
        
        return $this->instance->class;
    }
    
    
    /**
     * Поддържа ли се зададения интерфейс от тази референция?
     *
     * @param  string  $interface
     * @return boolean
     */
    public function haveInterface($interface)
    {
        return cls::haveInterface($interface, $this->getInstance());
    }
    
    
    /**
     * Дали референцията е инстанция на подадения клас
     *
     * @param  string  $className
     * @return boolean
     */
    public function isInstanceOf($className)
    {
        if (!cls::load($className, true)) {
            return false;
        }
        $ReflectionClass = new ReflectionClass($className);
        
        if ($ReflectionClass->isAbstract()) {
            return is_subclass_of($this->getInstance(), $className);
        }
        $class = cls::get($className);
             
        return ($this->getInstance() instanceof $class->className);
    }
    
    
    /**
     * Предизвиква събитие в класа на тази референция
     *
     * @param string $event
     * @param array  $args
     */
    public function invoke($event, $args = array())
    {
        $this->instance->invoke($event, $args);
    }
    
    
    /**
     * Записа, към който е референция този обект
     *
     * @return stdClass
     */
    public function rec($field = null)
    {
        $result = $this->getInstance()->fetchRec($this->that);
        
        if (!empty($field)) {
            $result = $result->{$field};
        }
        
        return $result;
    }
    
    
    /**
     * Първичния ключ на записа, към който е референция този обект
     *
     * @return int
     */
    public function id()
    {
        if (is_object($this->that)) {
            $id = $this->that->id;
        } else {
            $id = $this->that;
        }
        
        return $id;
    }
    
    
    /**
     * Пртоверка дали имаме право да изпълним дадено действие с обекта
     */
    public function haveRightFor($action, $userId = null)
    {
        return $this->getInstance()->haveRightFor($action, $this->that, $userId);
    }
}
