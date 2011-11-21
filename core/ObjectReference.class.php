<?php
/**
 * Указател към обект от зададен клас, евентуално приведен (cast) към зададен интерфейс. 
 * 
 * @category   ef
 * @package    core
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 *
 */
class core_ObjectReference
{
	/**
	 * От кой клас е обекта, към който сочи указателя
	 *
	 * @var string име на клас
	 */
	protected $className;
	
	
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
	protected $that;
	
	
	/**
	 * Интерфейс, към който да бъде приведен обекта
	 *
	 * @var int key(mvc=core_Interfaces) 
	 */
	protected $interface;
	
	
	/**
	 * Инстанция на обекта, съдържащ методите-реализации
	 *
	 * @var object
	 */
	protected $instance;

	
	/**
	 * Конструктор
	 *
	 * @param mixed $class име на клас, key(mvc=core_Classes) или инстанция на клас (т.е. обект)
	 * @param mixed $object   
	 * @param string $interface име на интерфейс
	 */
	function __construct($class, $object, $interface)
	{
		$this->className = cls::getClassName($classId);
		$this->that      = $object;
		$this->interface = $interface;
		
		$this->instance = cls::getInterface($interface, $classId);
	}
	
	
	/**
	 * Поставя $that в началото на списъка с аргументи и пре-предава контрола на същинския метод-реализация
	 * 
	 * Не прави проверка, дали той съществува, защото реализацията би могла да бъде индиректна - 
	 * в плъгин.
	 *
	 * @param string $method
	 * @param array $args
	 */
	function __call($method, $args)
	{
		array_unshift($args, $args);

		return call_user_func_array(array($this->instance, $method), $args);
	}
}