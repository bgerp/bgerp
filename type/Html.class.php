<?php

/**
 * Клас  'type_Html' - Тип за HTML данни
 *
 *
 * @category   Experta Framework
 * @package    type
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class type_Html extends type_Text {
    
    
    /**
     * Връща стойноста на текста, без изменения, защото се
     * предполага, че той е в HTML формат
     */
    function toVerbal($value)
    {
        return $value ;
    }
}