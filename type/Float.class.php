<?php


/**
 * Клас  'type_Float' - Тип за 'Малки' рационални числа. Не се препоръчват
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_Float extends type_Double
{
    /**
     * MySQL тип на полето в базата данни
     */
    public $dbFieldType = 'float';
}
