<?php



/**
 * Клас  'type_Html' - Тип за HTML данни
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Html extends type_Text {
    
    
    /**
     * Връща стойността на текста, без изменения, защото се
     * предполага, че той е в HTML формат
     */
    function toVerbal_($value)
    {
        return $value ;
    }
}