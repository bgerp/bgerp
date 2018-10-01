<?php


/**
 * Цифров вход
 *
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_ioport_DI extends sens2_ioport_Abstract
{
    /**
     * Типът слотове за сензорите от този вид
     */
    const SLOT_TYPES = 'DI';
    
    
    /**
     * Описание на порта
     */
    protected $description = array(
        'di' => array(
            'subname' => null,
            'uom' => null,
            'options' => array(0,1),
            'min' => 0,
            'max' => 1,
            'readable' => true,
            'writable' => false,
        ),
    );
}
