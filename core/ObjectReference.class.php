<?php



/**
 * Указател към обект от зададен клас, евентуално приведен (cast) към зададен интерфейс.
 *
 *
 * @category  all
 * @package   core
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_ObjectReference
{
    
    
    /**
     * От кой клас е обекта, към който сочи указателя
     *
     * @var string име на клас
     */
    var $className;
    
    
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
    var $that;
    
    
    /**
     * Интерфейс, към който да бъде приведен обекта
     *
     * @var int key(mvc=core_Interfaces)
     */
    var $interface;
    
    
    /**
     * Инстанция на обекта, съдържащ методите-реализации
     *
     * @var object
     */
    var $instance;
    
    
    /**
     * Конструктор
     *
     * @param mixed $class име на клас, key(mvc=core_Classes) или инстанция на клас (т.е. обект)
     * @param mixed $object
     * @param string $interface име на интерфейс
     */
    function __construct($classId, $object, $interface = NULL)
    {
        $this->className = cls::getClassName($classId);
        $this->that = $object;
        
        if($interface) {
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
     * @param array $args
     */
    function __call($method, $args)
    {
        array_unshift($args, $this->that);
        
        return call_user_func_array(array($this->instance, $method), $args);
    }
}